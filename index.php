<?php


session_start();
require('vendor/autoload.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

// Razorpay credentials
$keyId = 'rzp_live_1hzhzXuOlge5pF';
$keySecret = 'vGUPpWNcWAJINBJcH9GFCb3R';
$api = new Api($keyId, $keySecret);

// DB connection
$conn = new mysqli('localhost', 'u685993406_newaykadb', '8DwLlBGb!', 'u685993406_newaykadb');
if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}

// Handle form submission to create Razorpay order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && !isset($_POST['razorpay_payment_id'])) {
    $form = [
        'payment_details' => $_POST['payment_details'],   // Payment details
        'amount' => (int) $_POST['amount'],               // amount

        'symptoms_details' => $_POST['symptoms_details'], // symptoms details

        'patient_name' => $_POST['patient_name'],         // patient name
        'phone' => $_POST['phone'],                       // phone no.

        'gender' => $_POST['gender'],                     // gender
        'age' => (int) $_POST['age'],
        'coupon_code' => $_POST['applied_coupon'] ?? null  // coupon code
    ]
    ;
    $_SESSION['form'] = $form;

    // Testing: force Rs. 1, real: $form['TotalPaybleAmount'] * 100;
    $amountInPaise = 1 * 100;

    $order = $api->order->create([
        'receipt' => 'rcpt_' . time(),
        'amount' => $amountInPaise,
        'currency' => 'INR',
        'payment_capture' => 1
    ]);

    $orderId = $order['id'];
    $name = htmlspecialchars($form['f_name'] . ' ' . $form['l_name']);
    $email = htmlspecialchars($form['email']);
    $phone = htmlspecialchars($form['phone']);
    $planName = htmlspecialchars($form['plan_name']);
    $duration = htmlspecialchars($form['duration']);

    // Razorpay Checkout (JS)
    echo "
    <script src='https://checkout.razorpay.com/v1/checkout.js'></script>
    <script>
    var options = {
        key: '$keyId',
        amount: '$amountInPaise',
        currency: 'INR',
        name: 'AYKA Care',
        description: '$planName - $duration',
        order_id: '$orderId',
        handler: function (response) {
            const data = new URLSearchParams();
            data.append('razorpay_payment_id', response.razorpay_payment_id);
            data.append('razorpay_order_id', response.razorpay_order_id);
            data.append('razorpay_signature', response.razorpay_signature);

            fetch('index.php', {
                method: 'POST',
                body: data
            })
            .then(res => res.text())
            .then(result => {
                if (result.trim() === 'success') {
                    window.location.href = 'index.php?status=success';
                } else {
                    window.location.href = 'index.php?status=error';
                }
            })
            .catch(error => {
                console.error('Payment processing failed:', error);
                window.location.href = 'index.php?status=error';
            });
        },
        prefill: {
            name: '$name',
            email: '$email',
            contact: '$phone'
        },
        theme: {
            color: '#528FF0'
        },
        modal: {
            ondismiss: function () {
                window.location.href = 'index.php?status=cancel';
            }
        }
    };
    new Razorpay(options).open();
    </script>";
    exit;
}

// Handle Razorpay payment callback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['razorpay_payment_id'])) {
    $form = $_SESSION['form'] ?? null;

    try {
        $attributes = [
            'razorpay_order_id' => $_POST['razorpay_order_id'],
            'razorpay_payment_id' => $_POST['razorpay_payment_id'],
            'razorpay_signature' => $_POST['razorpay_signature']
        ];

        $api->utility->verifyPaymentSignature($attributes);

        if ($form) {
            // Insert into users table
            $stmt = $conn->prepare("INSERT INTO users (f_name, l_name, email, phone, gender, dob, city) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "sssssss",
                $form['f_name'],
                $form['l_name'],
                $form['email'],
                $form['phone'],
                $form['gender'],
                $form['dob'],
                $form['city']
            );
            $stmt->execute();
            $patient_id = $stmt->insert_id;
            $stmt->close();

            // Insert into subscriptions table
            $stmt = $conn->prepare("INSERT INTO subscriptions (patient_id, order_id, payment_id, plan_name, members, amount, startdate, expirydate, status) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'success')");
            $stmt->bind_param(
                "isssisss",
                $patient_id,
                $_POST['razorpay_order_id'],
                $_POST['razorpay_payment_id'],
                $form['plan_name'],
                $form['members'],
                $form['amount'],
                $form['startdate'],
                $form['enddate']
            );
            $stmt->execute();
            $stmt->close();

            unset($_SESSION['form']);

            echo "success"; // ✅ Correct: allow JS to handle redirect
            exit;
        } else {
            echo "form_session_missing";
            exit;
        }
    } catch (SignatureVerificationError $e) {
        file_put_contents("razorpay_error.log", $e->getMessage());
        echo "invalid_signature"; // JS will redirect to error
        exit;
    }
}

// Optional: show success/cancel messages if redirected
// if (isset($_GET['status'])) {
//     if ($_GET['status'] === 'success') {
//         echo "<h3>✅ Payment Successful!</h3>";
//     } elseif ($_GET['status'] === 'cancel') {
//         echo "<h3>❌ Payment Cancelled by User</h3>";
//     } elseif ($_GET['status'] === 'error') {
//         echo "<h3>⚠️ Payment Failed or Signature Invalid</h3>";
//     }
// }

?>
<!DOCTYPE html>
<html lang="zxx">

<head>


    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-Y4TLSBLNYC"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());

        gtag('config', 'G-Y4TLSBLNYC');
    </script>


    <!-- Google Tag Manager -->
    <script>(function (w, d, s, l, i) {
            w[l] = w[l] || []; w[l].push({
                'gtm.start':
                    new Date().getTime(), event: 'gtm.js'
            }); var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : ''; j.async = true; j.src =
                    'https://www.googletagmanager.com/gtm.js?id=' + i + dl; f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', 'GTM-MHXMM7BJ');</script>
    <!-- End Google Tag Manager -->







    <!-- Meta Pixel Code -->
    <script>
        !function (f, b, e, v, n, t, s) {
            if (f.fbq) return; n = f.fbq = function () {
                n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0';
            n.queue = []; t = b.createElement(e); t.async = !0;
            t.src = v; s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }(window, document, 'script',
            'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '3328042534011710');
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id=3328042534011710&ev=PageView&noscript=1" /></noscript>
    <!-- End Meta Pixel Code -->


    <!-- Meta -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="Awaiken">
    <!-- Page Title -->
    <title>AYKA Care</title>
    <!-- Favicon Icon -->
    <link rel="shortcut icon" type="image/x-icon" href="./assets/images/favicon.png">
    <!-- Google Fonts Css-->
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&amp;display=swap"
        rel="stylesheet">
    <!-- Bootstrap Css -->
    <link href="./assets/css/bootstrap.min.css" rel="stylesheet" media="screen">
    <!-- SlickNav Css -->
    <link href="./assets/css/slicknav.min.css" rel="stylesheet">
    <!-- Swiper Css -->
    <link rel="stylesheet" href="css/swiper-bundle.min.css">
    <!-- Font Awesome Icon Css-->
    <link href="./assets/css/all.min.css" rel="stylesheet" media="screen">
    <!-- Animated Css -->
    <link href="./assets/css/animate.css" rel="stylesheet">
    <!-- Magnific Popup Core Css File -->
    <link rel="stylesheet" href="./assets/css/magnific-popup.css">
    <!-- Mouse Cursor Css File -->
    <!-- <link rel="stylesheet" href="./assets/css/mousecursor.css"> -->
    <!-- Main Custom Css -->
    <link href="./assets/css/custom.css?v=2.9" rel="stylesheet" media="screen">
</head>
<style>
    #main {
        margin-top: 5rem;

        .cards-info {
            max-width: 380px;
            width: 95%;
            box-shadow: 0 10px 10px darken(#f7f8fc, 15%);

            @media (max-width: 807px) {
                max-width: unset;
            }

            .prices {
                background-color: darken(#f7f8fc, 10%);

                h1 {
                    font-size: 2.875rem;
                }

                a,
                a:visited {
                    background-color: #10182f;
                    max-width: 160px;
                    width: 100%;
                }
            }
        }
    }
</style>

<body>
    <!-- Preloader Start -->
    <div class="preloader">
        <div class="loading-container">
            <div class="loading"></div>
            <div id="loading-icon"><img src="./assets/images/loader.png" alt=""></div>
        </div>
    </div>
    <!-- Preloader End -->
    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] === 'success'): ?>
            <div class="alert alert-success text-center">✅ Payment Successful!</div>
        <?php elseif ($_GET['status'] === 'cancel'): ?>
            <div class="alert alert-warning text-center">⚠️ Payment Cancelled.</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Topbar Section Start -->



    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MHXMM7BJ" height="0" width="0"
            style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->



    <div class="topbar">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 col-md-9">
                    <!-- Topbar Contact Information Start -->
                    <div class="topbar-contact-info">
                        <ul>
                            <!--<li><a href="#"><i class="fa-solid fa-clock"></i> <span>Working Hour:</span> 08:00am to-->
                            <!--        09:00pm</a></li>-->
                            <li><a href="#"><i class="fa-solid fa-envelope"></i> <span>Email:</span>
                                    hello@aykacare.in</a></li>
                        </ul>
                    </div>
                    <!-- Topbar Contact Information End -->
                </div>

                <div class="col-lg-6 col-md-3">
                    <!-- Topbar Social Details Start -->
                    <div class="topbar-social-details">
                        <!-- Header Social Icons Start -->
                        <div class="topbar-social-icons">
                            <ul>
                                <li><a href="https://www.instagram.com/ayka.care/"><i
                                            class="fa-brands fa-instagram"></i></a></li>
                                <li><a href="https://www.facebook.com/ayka.care"><i
                                            class="fa-brands fa-facebook-f"></i></a></li>
                                <a href="https://www.youtube.com/@ayka_care"><i class="fab fa-youtube fa-lg"></i></a>
                            </ul>
                        </div>
                        <!-- Header Social Icons End -->

                        <!-- Topbar Contact Details Start -->
                        <div class="topbar-contact-info topbar-contact-details">
                            <ul>
                                <li><a href="tele"><span>Contact:</span> +91 92207 82066</a></li>
                            </ul>
                        </div>
                        <!-- Topbar Contact Details End -->
                    </div>
                    <!-- Topbar Social Details End -->
                </div>
            </div>
        </div>
    </div>




    <!-- Topbar Section End -->

    <!-- Header Start -->
    <header class="main-header">
        <div class="header-sticky">
            <nav class="navbar navbar-expand-lg">
                <div class="container">
                    <!-- Logo Start -->
                    <a class="navbar-brand" href="./">
                        <img src="./assets/images/logo.svg" style="height: 61px;" alt="Logo">
                    </a>
                    <!-- Logo End -->

                    <!-- Main Menu Start -->
                    <div class="collapse navbar-collapse main-menu">
                        <div class="nav-menu-wrapper">
                            <ul class="navbar-nav mr-auto" id="menu">
                                <li class="nav-item"><a class="nav-link" href="./">Home</a>

                                </li>
                                <li class="nav-item"><a class="nav-link" href="./about-us.php">About Us</a></li>
                                <li class="nav-item"><a class="nav-link" href="#services">Specialists</a></li>
                                <li class="nav-item submenu"><a class="nav-link" href="">Membership Plans</a>
                                    <ul>
                                        <li class="nav-item"><a class="nav-link" href="./individual-plan.php">Individual
                                                Plan</a></li>
                                        <li class="nav-item"><a class="nav-link" href="./family-plan.php">Family Plan
                                                Lite/Elite</a></li>
                                        <!-- <li class="nav-item"><a class="nav-link" href="">Family Plan Elite</a></li>  -->
                                        <li class="nav-item"><a class="nav-link" href="./membership-plan.php">Senior
                                                Care Plan</a></li>
                                    </ul>
                                </li>

                                <li class="nav-item"><a class="nav-link"
                                        href="https://expert.aykacare.in/?fluent-form=4">Contact Us</a></li>
                                <li class="nav-item highlighted-menu"><a class="nav-link"
                                        href="https://expert.aykacare.in/?fluent-form=4">book appointment</a></li>
                            </ul>
                        </div>

                        <!-- Header Button Start-->
                        <div class="header-btn">
                            <a href="https://expert.aykacare.in/?fluent-form=4" class="btn-default">book appointment</a>
                        </div>
                        <!-- Header Button End-->
                    </div>
                    <!-- Main Menu End -->
                    <div class="navbar-toggle"></div>
                </div>
            </nav>
            <div class="responsive-menu"></div>
        </div>
    </header>
    <!-- Header End -->

    <!-- Hero Section Start -->
    <div class="hero bg-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <!-- Hero Content Start -->
                    <div class="hero-content">
                        <!-- Section Title Start -->
                        <div class="section-title">
                            <h3 class="wow fadeInUp welcome-size">Welcome to AYKA Care</h3>
                            <h2 class="text-anime-style-3" data-cursor="-opaque">Online Doctor Consultation India |
                                Telemedicine & Video Visits
                            </h2>
                            <p class="wow fadeInUp" data-wow-delay="0.2s">Book online doctor consultations in India with
                                verified physicians and specialists. Get video, chat, or phone consultations, digital
                                prescriptions, and follow-ups — anytime, anywhere.</p>
                        </div>
                        <!-- Section Title End -->

                        <!-- Hero Buttons Start -->
                        <div class="hero-btn wow fadeInUp" data-wow-delay="0.4s">
                            <a href="https://expert.aykacare.in/?fluent-form=4" class="btn-default">book a
                                appointment</a>
                            <a href="#about" class="btn-default">about us</a>
                        </div>
                        <!-- Hero Buttons End -->

                        <!-- Google Rating Start -->
                        <div class="trust-factors wow fadeInUp" data-wow-delay="0.75s">
                            <!--<ul class="trust-list">-->
                            <!--  <li>-->
                            <!--    <i class="fa-solid fa-universal-access"></i>-->
                            <!--    <span>Accessible</span>-->
                            <!--  </li>-->
                            <!--  <li>-->
                            <!--    <i class="fa-solid fa-user-check"></i>-->
                            <!--    <span>Available</span>-->
                            <!--  </li>-->
                            <!--  <li>-->
                            <!--    <i class="fa-solid fa-hand-holding-medical"></i>-->
                            <!--    <span>Affordable</span>-->
                            <!--  </li>-->
                            <!--</ul>-->
                        </div>
                        <!-- Google Rating End -->
                    </div>
                    <!-- Hero Content End -->
                </div>

                <div class="col-lg-6">
                    <!-- Hero Image Start -->
                    <div class="hero-image">
                        <!-- Hero Img Start -->
                        <div class="hero-img">
                            <figure>
                                <img src="./assets/images/ayka-doctor.png" alt="">
                            </figure>
                        </div>
                        <!-- Hero Img End -->

                        <!-- Excerpt Doctor Box Start -->
                        <div class="excerpt-doctor-box">
                            <!-- Excerpt Doctor Images Start -->
                            <div class="excerpt-doctor-images">
                                <div class="excerpt-doctor-image">
                                    <figure class="image-anime">
                                        <img src="./assets/images/doctor1.jpeg" alt="">
                                    </figure>
                                </div>
                                <div class="excerpt-doctor-image">
                                    <figure class="image-anime">
                                        <img src="./assets/images/doctor2.jpeg" alt="">
                                    </figure>
                                </div>
                                <div class="excerpt-doctor-image">
                                    <figure class="image-anime">
                                        <img src="./assets/images/doctor3.jpeg" alt="">
                                    </figure>
                                </div>
                                <div class="excerpt-doctor-image">
                                    <figure class="image-anime">
                                        <img src="./assets/images/doctor4.jpeg" alt="">
                                    </figure>
                                </div>
                            </div>
                            <!-- Excerpt Doctor Images End -->

                            <!-- Excerpt Doctor Content Start -->
                            <div class="excerpt-doctor-content">
                                <p>Talk to our <span class="counter">2500</span>+ Doctors</p>
                            </div>
                            <!-- Excerpt Doctor Content End -->
                        </div>
                        <!-- Excerpt Doctor Box End -->

                        <!-- Satisfied Clients Box Start -->
                        <div class="satisfied-clients-box">
                            <div class="icon-box">
                                <img src="./assets/images/hero-satisfied-clients.svg" alt="">
                            </div>
                            <div class="satisfied-clients-content">
                                <h3><span class="counter">5000</span>+</h3>
                                <p>satisfied clients</p>
                            </div>
                        </div>
                        <!-- Satisfied Clients Box End -->
                    </div>
                    <!-- Hero Image End -->
                </div>
            </div>
        </div>
    </div>
    <!-- Hero Section End -->

    <!-- About Us Section Start -->
    <div class="about-us" id="about">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5">
                    <!-- About Us Content Start -->
                    <div class="about-us-content">
                        <!-- Section Title Start -->
                        <div class="section-title">
                            <h3 class="wow fadeInUp">about us</h3>
                            <h2 class="text-anime-style-3" data-cursor="-opaque">
                                Comprehensive Pain Management Solutions
                            </h2>
                            <p class="wow fadeInUp" data-wow-delay="0.25s">AYKA Care delivers Next-Level Pain Relief.
                                Our compassionate, elite team of specialists doesn't just treat symptoms—we engineer a
                                personalized, life-changing plan to RECLAIM your quality of life. Your pain stops here.
                                Your future starts now..</p>
                        </div>
                        <!-- Section Title End -->

                        <!-- About Us Body Start -->
                        <div class="about-us-body">
                            <!-- About Info Item Start -->
                            <div class="about-info-item wow fadeInUp">
                                <div class="icon-box">
                                    <img src="./assets/images/icon-about-info-1.png" alt="">
                                </div>
                                <div class="about-info-item-content">
                                    <h3>Online Doctor Consultation in India – <br>Easy, Secure, Trusted</h3>
                                    <p>AYKA Care offers reliable online doctor consultations in India, connecting you
                                        with qualified general physicians and medical specialists via video, chat, or
                                        phone. Whether it’s everyday health concerns, specialist advice, or follow-up
                                        care, our telemedicine platform makes expert healthcare accessible wherever you
                                        are.</p>
                                </div>
                            </div>
                            <!-- About Info Item End -->

                            <!-- About Info Item Start -->
                            <div class="about-info-item wow fadeInUp" data-wow-delay="0.25s">
                                <div class="icon-box">
                                    <img src="./assets/images/icon-about-info-2.png" alt="">
                                </div>
                                <div class="about-info-item-content">
                                    <h3>What Is Online Doctor Consultation?</h3>
                                    <p>Online doctor consultation lets you speak with a doctor through video call, phone
                                        call, or secure chat from your mobile or computer. This modern telemedicine
                                        approach helps you get medical advice, diagnosis, and digital prescriptions
                                        without traveling to a clinic or hospital.</p>
                                </div>
                            </div>
                            <!-- About Info Item End -->

                            <!-- About Info Item Start -->
                            <div class="about-info-item wow fadeInUp" data-wow-delay="0.5s">
                                <div class="icon-box">
                                    <img src="./assets/images/icon-about-info-3.png" alt="">
                                </div>
                                <div class="about-info-item-content">
                                    <h3>Never Wait - Never Worry (24/7 Care)</h3>
                                    <p>Your health doesn't take a break, so neither do we. Get instant, dependable,
                                        around-the-clock support for ultimate peace of mind. AYKA Care is ALWAYS on.</p>
                                </div>
                            </div>
                            <!-- About Info Item End -->
                        </div>
                        <!-- About Us Body End -->

                        <!-- About Us Button Start -->
                        <div class="about-us-btn wow fadeInUp" data-wow-delay="0.75s">
                            <a href="./about-us.php" class="btn-default">view more about us</a>
                        </div>
                        <!-- About Us Button End -->
                    </div>
                    <!-- About Us Content End -->
                </div>

                <div class="col-lg-7">
                    <!-- About Us Images Start -->
                    <div class="about-us-images">
                        <!-- About Image 1 Start -->
                        <div class="about-img-1">
                            <figure class="image-anime reveal">
                                <img src="./assets/images/background_img.jpg" alt="">
                            </figure>
                        </div>
                        <!-- About Image 1 End -->

                        <!-- About Image 2 Start -->
                        <div class="about-img-2">
                            <!--<figure class="image-anime">-->
                            <img src="./assets/images/video-call-image.jpg" alt="">
                            <!--<h3>video call support</h3>-->
                            <!--</figure>-->
                        </div>
                        <!-- About Image 2 End -->


                    </div>
                    <!-- About Us Images End -->
                </div>
            </div>
        </div>
    </div>
    <!-- About Us Section End -->

    <!-- Our Service Section Start -->
    <div class="our-services bg-section" id="services">
        <div class="container">
            <div class="row section-row align-items-center" style=" margin-bottom: 0px;">
                <div class="col-lg-12">
                    <!-- Section Title Start -->
                    <div class="section-title">
                        <h3 class="wow fadeInUp">our specialists</h3>
                        <h2 class="text-anime-style-3" data-cursor="-opaque">specialists for your health</h2>
                    </div>
                    <!-- Section Title End -->
                </div>
            </div>

            <div class="row align-items-center">
                <style>
                    .row {
                        margin-right: 0px;
                        margin-left: 0px;
                    }

                    .specialist-card {
                        border: 1px solid #dceae4;
                        border-radius: 12px;
                        padding: 25px;
                        text-align: center;
                        transition: all 0.3s ease;
                        background-color: #fff;
                    }

                    .specialist-card:hover {
                        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
                        transform: translateY(-5px);
                        border-color: #aad1bf;
                    }

                    .specialist-card img {
                        width: 61px;
                        height: 61px;
                        margin-bottom: 15px;
                        transition: transform 0.3s ease, filter 0.3s ease;
                    }

                    .specialist-card:hover img {
                        filter: none;
                        transform: scale(1.1);
                    }

                    .specialist-title {
                        font-size: 1rem;
                        font-weight: 600;
                        color: #2a4638;
                    }

                    .section-heading {
                        color: #1e3a34;
                    }

                    .section-subtitle {
                        color: #65766d;
                    }

                    .text-size {
                        font-size: 10px
                    }

                    .specialist-card {
                        border: 1px solid #dceae4;
                        border-radius: 12px;
                        width: 100%;
                        /* full width on larger screens */
                        min-height: 150px;
                        /* height grows based on content */
                        padding: 10px;
                        text-align: center;
                        transition: all 0.3s ease;
                        background-color: #fff;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                        overflow: hidden;
                        word-wrap: break-word;
                    }

                    .specialist-card img {
                        width: 50px;
                        height: 50px;
                        margin-bottom: 8px;
                        object-fit: contain;
                        transition: transform 0.3s ease;
                    }


                    .specialist-title {
                        font-size: 0.9rem;
                        font-weight: 600;
                        color: #2a4638;
                        overflow-wrap: break-word;
                        text-align: center;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        display: -webkit-box;
                        -webkit-line-clamp: 3;
                        /* maximum 3 lines */
                        -webkit-box-orient: vertical;
                    }

                    .specialist-title {
                        font-size: 0.8rem;
                        -webkit-line-clamp: 2;
                        /* maximum 2 lines on mobile */
                    }

                    /* Mobile: 2 columns */
                    @media (max-width: 576px) {

                        /* Responsive grid */
                        #services {
                            display: grid;
                            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                            gap: 15px;
                            justify-content: center;
                        }

                        .specialist-card {
                            width: 150px;
                            height: 150px;
                            /* fixed height on mobile */
                        }

                        #services {
                            grid-template-columns: repeat(2, 1fr);
                            gap: 10px;
                        }

                        .specialist-card img {
                            width: 40px;
                            height: 40px;
                            margin-bottom: 6px;
                        }

                        .specialist-title {
                            font-size: 0.8rem;
                            -webkit-line-clamp: 2;
                            /* maximum 2 lines on mobile */
                        }
                    }
                </style>
                <div class="row g-4" id="services">

                    <div class="col-6 col-sm-4 col-md-3">
                        <div class="specialist-card h-100">
                            <img src="./assets/images/service-icon/GeneralPhysician.webp" alt="General Physician">
                            <div class="specialist-title mb-3">General Physician</div>
                            <a href="consultation-form.php?specialist=General Physician" class="btn btn-sm w-100"
                                style="background:#c8d439; color:#ffffff; border-radius: 5px; font-weight: 600;">
                                Consult Now
                            </a>
                        </div>
                    </div>

                    <div class="col-6 col-sm-4 col-md-3">
                        <div class="specialist-card h-100">
                            <img src="./assets/images/service-icon/Gynecology.webp" alt="Gynecology">
                            <div class="specialist-title mb-3">Gynecology</div>
                            <a href="consultation-form.php?specialist=Gynecology" class="btn btn-sm w-100"
                                style="background:#c8d439; color:#ffffff; border-radius: 5px; font-weight: 600;">
                                Consult Now
                            </a>
                        </div>
                    </div>

                    <div class="col-6 col-sm-4 col-md-3">
                        <div class="specialist-card h-100">
                            <img src="./assets/images/service-icon/Psychiatry.webp" alt="Psychiatry">
                            <div class="specialist-title mb-3">Psychiatry</div>
                            <a href="consultation-form.php?specialist=Psychiatry" class="btn btn-sm w-100"
                                style="background:#c8d439; color:#ffffff; border-radius: 5px; font-weight: 600;">
                                Consult Now
                            </a>
                        </div>
                    </div>

                    <div class="col-6 col-sm-4 col-md-3">
                        <div class="specialist-card h-100">
                            <img src="./assets/images/service-icon/Pediatrics.webp" alt="Pediatrics">
                            <div class="specialist-title mb-3">Pediatrics</div>
                            <a href="consultation-form.php?specialist=Pediatrics" class="btn btn-sm w-100"
                                style="background:#c8d439; color:#ffffff; border-radius: 5px; font-weight: 600;">
                                Consult Now
                            </a>
                        </div>
                    </div>

                    <div class="col-6 col-sm-4 col-md-3">
                        <div class="specialist-card h-100">
                            <img src="./assets/images/service-icon/ENT (Ear, Nose, Throat).webp" alt="ENT">
                            <div class="specialist-title mb-3">ENT (Ear, Nose, Throat)</div>
                            <a href="consultation-form.php?specialist=ENT" class="btn btn-sm w-100"
                                style="background:#c8d439; color:#ffffff; border-radius: 5px; font-weight: 600;">
                                Consult Now
                            </a>
                        </div>
                    </div>

                    <div class="col-6 col-sm-4 col-md-3">
                        <div class="specialist-card h-100">
                            <img src="./assets/images/service-icon/Orthopedics.webp" alt="Orthopedics">
                            <div class="specialist-title mb-3">Orthopedics</div>
                            <a href="consultation-form.php?specialist=Orthopedics" class="btn btn-sm w-100"
                                style="background:#c8d439; color:#ffffff; border-radius: 5px; font-weight: 600;">
                                Consult Now
                            </a>
                        </div>
                    </div>

                    <div class="col-6 col-sm-4 col-md-3">
                        <div class="specialist-card h-100">
                            <img src="./assets/images/service-icon/Cardiology.webp" alt="Cardiology">
                            <div class="specialist-title mb-3">Cardiology</div>
                            <a href="consultation-form.php?specialist=Cardiology" class="btn btn-sm w-100"
                                style="background:#c8d439; color:#ffffff; border-radius: 5px; font-weight: 600;">
                                Consult Now
                            </a>
                        </div>
                    </div>

                    <div class="col-6 col-sm-4 col-md-3">
                        <div class="specialist-card h-100">
                            <img src="./assets/images/service-icon/2025-04-30-6811cf0387c7c.webp" alt="Home Health Aid">
                            <div class="specialist-title mb-3">Home Health Aid</div>
                            <a href="consultation-form.php?specialist=Home Health Aid" class="btn btn-sm w-100"
                                style="background:#c8d439; color:#ffffff; border-radius: 5px; font-weight: 600;">
                                Consult Now
                            </a>
                        </div>
                    </div>

                    <div class="col-6 col-sm-4 col-md-3">
                        <div class="specialist-card h-100">
                            <img src="./assets/images/service-icon/Nutrition and Dietetics.webp" alt="Nutrition">
                            <div class="specialist-title mb-3">Nutrition and Dietetics</div>
                            <a href="consultation-form.php?specialist=Nutrition and Dietetics" class="btn btn-sm w-100"
                                style="background:#c8d439; color:#ffffff; border-radius: 5px; font-weight: 600;">
                                Consult Now
                            </a>
                        </div>
                    </div>

                    <div class="col-6 col-sm-4 col-md-3">
                        <div class="specialist-card h-100">
                            <img src="./assets/images/service-icon/2025-04-30-6811cf9c7e714.webp" alt="Endocrinologist">
                            <div class="specialist-title mb-3">Endocrinologist</div>
                            <a href="consultation-form.php?specialist=Endocrinologist" class="btn btn-sm w-100"
                                style="background:#c8d439; color:#ffffff; border-radius: 5px; font-weight: 600;">
                                Consult Now
                            </a>
                        </div>
                    </div>

                    <div class="col-6 col-sm-4 col-md-3">
                        <div class="specialist-card h-100">
                            <img src="./assets/images/service-icon/2025-04-30-68122781158b3.webp" alt="Neurologist">
                            <div class="specialist-title mb-3">Neurologist</div>
                            <a href="consultation-form.php?specialist=Neurologist" class="btn btn-sm w-100"
                                style="background:#c8d439; color:#ffffff; border-radius: 5px; font-weight: 600;">
                                Consult Now
                            </a>
                        </div>
                    </div>

                    <div class="col-6 col-sm-4 col-md-3">
                        <div class="specialist-card h-100">
                            <img src="./assets/images/service-icon/2025-04-30-68122704cc35f.webp" alt="BAMS">
                            <div class="specialist-title mb-3">BAMS Doctor (Ayurvedic Practitioner)</div>
                            <a href="consultation-form.php?specialist=BAMS Doctor" class="btn btn-sm w-100"
                                style="background:#c8d439; color:#ffffff; border-radius: 5px; font-weight: 600;">
                                Consult Now
                            </a>
                        </div>
                    </div>

                    <div class="col-6 col-sm-4 col-md-3">
                        <div class="specialist-card h-100">
                            <img src="./assets/images/service-icon/2025-04-30-6811d01a50be5.webp" alt="Dentistry">
                            <div class="specialist-title mb-3">Dentistry</div>
                            <a href="consultation-form.php?specialist=Dentistry" class="btn btn-sm w-100"
                                style="background:#c8d439; color:#ffffff; border-radius: 5px; font-weight: 600;">
                                Consult Now
                            </a>
                        </div>
                    </div>

                    <div class="col-6 col-sm-4 col-md-3">
                        <div class="specialist-card h-100">
                            <img src="./assets/images/service-icon/2025-04-30-6811d0f4eaa51.webp" alt="BHMS">
                            <div class="specialist-title text-size mb-3">BHMS (Homeopathic Medicine and Surgery)</div>
                            <a href="consultation-form.php?specialist=BHMS" class="btn btn-sm w-100"
                                style="background:#c8d439; color:#ffffff; border-radius: 5px; font-weight: 600;">
                                Consult Now
                            </a>
                        </div>
                    </div>

                    <div class="col-6 col-sm-4 col-md-3">
                        <div class="specialist-card h-100">
                            <img src="./assets/images/service-icon/GeneralPhysician.webp" alt="BNYS">
                            <div class="specialist-title text-size mb-3">BNYS (Naturopathy & Yogic Science)</div>
                            <a href="consultation-form.php?specialist=BNYS" class="btn btn-sm w-100"
                                style="background:#c8d439; color:#ffffff; border-radius: 5px; font-weight: 600;">
                                Consult Now
                            </a>
                        </div>
                    </div>

                    <div class="col-6 col-sm-4 col-md-3">
                        <div class="specialist-card h-100">
                            <img src="./assets/images/service-icon/GeneralPhysician.webp" alt="Physiotherapist">
                            <div class="specialist-title mb-3">Physiotherapist</div>
                            <a href="consultation-form.php?specialist=Physiotherapist" class="btn btn-sm w-100"
                                style="background:#c8d439; color:#ffffff; border-radius: 5px; font-weight: 600;">
                                Consult Now
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- Our Service Section End -->
    <br><br>
    <!-- Intro Video Section Start -->
    <div class="intro-video">
        <div class="container">
            <div class="row section-row align-items-center">
                <div class="col-lg-7">
                    <!-- Section Title Start -->
                    <div class="section-title">
                        <h3 class="wow fadeInUp">How Online Doctor Consultation Works</h3>
                        <h2 class="text-anime-style-3" data-cursor="-opaque">Tired of Waiting? Tired of Guessing?</h2>
                        <p class="wow fadeInUp" data-wow-delay="0.25s">AYKA Care is the Future of Health. Our
                            unshakeable commitment to Excellence, Empathy, and Personalized Results has made us the
                            trusted choice. Discover the AYKA difference—where you move from just 'a patient' to a
                            Health Success Story.
                        </p>
                    </div>
                    <!-- Section Title End -->
                </div>

                <div class="col-lg-5">
                    <!-- Why Choose List Start -->
                    <div class="why-choose-list wow fadeInUp">
                        <ul>
                            <li>
                                <h3>Search for a Doctor
                                </h3> Choose from a list of specialist and general physicians.
                            </li>
                            <li>
                                <h3>Book Your Appointment
                                </h3> Select a time that works for you and confirm your consultation.
                            </li>
                            <li>
                                <h3>Consult via Video/Chat/Phone
                                </h3> Connect with the doctor from the comfort of your home.
                            </li>
                            <li>
                                <h3>Receive Your Prescription Online
                                </h3> Download your e-prescription and manage health records conveniently.
                            </li>

                            <!--<li>Zero-Stress Zone. Our team is obsessed with your total comfort and confidence.</li>-->
                            <!--<li>Immediate Impact. Get prompt, highly-effective care that delivers real results.</li>-->
                            <!--<li>Lifelong Health Partner. We don't just treat; we actively manage your success at EVERY stage.</li>-->
                        </ul>
                    </div>
                    <!-- Why Choose List End -->
                </div>
            </div>


            <div class="why-choose-section"
                style="display:flex; flex-wrap: wrap; justify-content: center; align-items: center; gap:50px; min-height:600px;">
                <!-- Left: Vertical Video -->
                <!--<div class="video-container" style="flex: 0 0 300px; height: 600px; position: relative; border-radius: 15px; overflow: hidden; display:flex; align-items:center; justify-content:center;">-->
                <!--    <iframe id="yt-short-video"-->
                <!--        src="https://www.youtube.com/embed/XIFm4sFGHUM?autoplay=1&mute=1&controls=1&rel=0&modestbranding=1&loop=1&playlist=XIFm4sFGHUM"-->
                <!--        frameborder="0" allow="autoplay; encrypted-media; fullscreen" allowfullscreen-->
                <!--        style="width:100%; height:100%; border-radius: 15px;">-->
                <!--    </iframe>-->
                <!--</div>-->

                <div class="image-container" style="flex: 0 0 450px; height: 600px; position: relative; border-radius: 15px; 
            overflow: hidden; display:flex; align-items:center; justify-content:center;">

                    <img src="./assets/images/ayka-ai-doc.jpg" alt="Educational Banner"
                        style="width:100%; height:100%; object-fit:cover; border-radius:15px;">
                </div>

                <!-- Right: Vertical Text Content -->
                <div class="why-choose-text"
                    style="flex: 1; flex-direction: column; align-items: center; gap:30px; text-align:center; color:#000;">

                    <!-- Collage Image -->
                    <div class="collage-wrapper">
                        <div class="collage">
                            <img src="./assets/images/collage.jpeg" alt="Collage Image" class="responsive-collage">
                        </div>
                    </div>
                    <!-- 3 Points in One Line -->
                    <div class="why-choose-items" style="display:flex; justify-content: center; align-items: flex-start; gap:40px; flex-wrap:wrap; position: relative;
        top: -15px;">

                        <!-- Item 1 -->
                        <div class="why-choose-item"
                            style="flex:1; min-width:200px; display:flex; flex-direction:column; align-items:center; gap:10px;">
                            <div class="icon-box">
                                <img src="./assets/images/icon-why-choose-1.svg" alt="" style="width:50px;">
                            </div>
                            <h3 style="font-size:20px; margin-bottom:5px; color:#000;">2500+ Expert Doctors</h3>
                            <p style="font-size:16px; color:#000;">Our team includes over 50 highly skilled doctors.</p>
                        </div>

                        <!-- Item 2 -->
                        <div class="why-choose-item"
                            style="flex:1; min-width:200px; display:flex; flex-direction:column; align-items:center; gap:10px;">
                            <div class="icon-box">
                                <img src="./assets/images/icon-why-choose-2.svg" alt="" style="width:50px;">
                            </div>
                            <h3 style="font-size:20px; margin-bottom:5px; color:#000;">24/7 Instant Support</h3>
                            <p style="font-size:16px; color:#000;">We provide quick responses for all your queries.</p>
                        </div>

                        <!-- Item 3 -->
                        <div class="why-choose-item"
                            style="flex:1; min-width:200px; display:flex; flex-direction:column; align-items:center; gap:10px;">
                            <div class="icon-box">
                                <img src="./assets/images/icon-why-choose-3.svg" alt="" style="width:50px;">
                            </div>
                            <h3 style="font-size:20px; margin-bottom:5px; color:#000;">Expert Medical Team</h3>
                            <p style="font-size:16px; color:#000;">Our team includes highly experienced doctors ready to
                                help.</p>
                        </div>

                    </div>
                </div>



            </div>
        </div>
        <!-- Intro Video Section End -->
        <!-- Font Awesome CDN -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

        <style>
            .responsive-collage {
                width: 100%;
                max-width: 400px;
                /* maintain max size on large screens */
                height: auto;
                /* scale proportionally */
                border-radius: 15px;
                object-fit: cover;
                transform: rotate(90deg);
            }

            /* Tablet and Mobile Adjustments */
            @media (max-width: 992px) {
                .responsive-collage {
                    max-width: 300px;
                    transform: rotate(90deg);
                    /* keep rotation, scales down */
                }
            }

            @media (max-width: 576px) {
                .responsive-collage {
                    max-width: 200px;
                    transform: rotate(90deg);
                    /* optional: you can rotate back to 0 if needed */
                }
            }

            /* Work Step Icon Styling */
            .work-step-icon {
                width: 100px;
                height: 100px;
                background: #b7c237;
                /* leaf green background */
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 20px;
                position: relative;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            }

            .work-step-icon i {
                font-size: 40px;
                /* Bigger icon size */
                color: #ffffff;
                /* White icon */
            }

            .work-step-no {
                position: absolute;
                bottom: -15px;
                right: -15px;
                background: #c8d439;
                /* yellow-green background */
                color: #000;
                font-size: 14px;
                font-weight: bold;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        </style>

        <div class="we-work bg-section">
            <div class="container">
                <div class="row section-row align-items-center">
                    <div class="col-lg-12">
                        <!-- Section Title Start -->
                        <div class="section-title">
                            <h3 class="wow fadeInUp">how we work</h3>
                            <h2 class="text-anime-style-3" data-cursor="-opaque">
                                We work to achieve better health outcomes
                            </h2>
                            <p class="wow fadeInUp" data-wow-delay="0.25s">
                                We are committed to improving health outcomes
                                through personalized care, innovative treatments, and a focus on prevention.
                            </p>
                        </div>
                        <!-- Section Title End -->
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <!-- Work Steps Box Start -->
                        <div class="work-steps-box">
                            <!-- Work Step Item Start -->
                            <div class="work-step-item">
                                <br>
                                <div class="work-step-icon">
                                    <i class="fas fa-user-plus"></i>
                                    <div class="work-step-no">
                                        <h3>01</h3>
                                    </div>
                                </div>
                                <div class="work-step-content">
                                    <br>
                                    <h3>UNLOCK Your Account</h3>
                                    <p>Join the Health Revolution. Get instant access to the AYKA Care community and
                                        your personalized dashboard. (Takes 60 Seconds!).</p>
                                </div>
                            </div>
                            <!-- Work Step Item End -->

                            <!-- Work Step Item Start -->
                            <div class="work-step-item">
                                <div class="work-step-icon">
                                    <i class="fas fa-user-md"></i>
                                    <div class="work-step-no">
                                        <h3>02</h3>
                                    </div>
                                </div>
                                <div class="work-step-content">
                                    <br>
                                    <h3>FIND Your Specialist MATCH</h3>
                                    <p>Use our smart-search to connect with the perfect, vetted expert for your
                                        needs—fast and frustration-free.</p>
                                </div>
                            </div>
                            <!-- Work Step Item End -->

                            <!-- Work Step Item Start -->
                            <div class="work-step-item">
                                <div class="work-step-icon">
                                    <i class="fas fa-calendar-check"></i>
                                    <div class="work-step-no">
                                        <h3>03</h3>
                                    </div>
                                </div>
                                <div class="work-step-content">
                                    <br>
                                    <h3>BOOK Your Breakthrough</h3>
                                    <p>Secure your appointment slot instantly at a time that works with your life. Zero
                                        phone tag. Zero hassle.</p>
                                </div>
                            </div>
                            <!-- Work Step Item End -->

                            <!-- Work Step Item Start -->
                            <div class="work-step-item">
                                <div class="work-step-icon">
                                    <i class="fas fa-stethoscope"></i>
                                    <div class="work-step-no">
                                        <h3>04</h3>
                                    </div>
                                </div>
                                <div class="work-step-content">
                                    <br>
                                    <h3>LAUNCH Your Transformation</h3>
                                    <p>Connect with your trusted doctor and START the journey to BETTER HEALTH OUTCOMES
                                        TODAY.</p>
                                </div>
                            </div>
                            <!-- Work Step Item End -->
                        </div>
                        <!-- Work Steps Box End -->
                    </div>
                </div>
            </div>
        </div>



        <!-- Our Excellence Section Start -->
        <style>
            .work-step-icon {
                margin: 0 auto 15px auto;
                /* Center the icon */
            }

            @media (max-width: 576px) {
                .work-step-item {
                    flex: 1 1 100%;
                    /* Full width */
                    text-align: center;
                    /* Center all text */
                }



                .work-step-no {
                    left: 50%;
                    /* Center the number circle horizontally */
                    transform: translateX(-50%);
                }

                .work-step-content h3,
                .work-step-content p {
                    text-align: center;
                    /* Center heading and paragraph */
                }
            }

            .pricing-card {
                border: none;
                border-radius: 16px;
                overflow: hidden;
                transition: all 0.3s ease;
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
                background: #fff;
            }

            .pricing-card:hover {
                transform: translateY(-8px);
                box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
            }

            .pricing-header {
                background: #2d6a4f;
                /* Dark green */
                color: #fff;
                padding: 20px;
                font-weight: 600;
                text-align: center;
                font-size: 18px;
            }

            .pricing-price {
                font-size: 36px;
                font-weight: 700;
                color: #1b4332;
            }

            .pricing-duration {
                font-size: 14px;
                color: #6c757d;
                margin-bottom: 20px;
            }

            .pricing-features {
                text-align: left;
                padding: 0;
                margin: 0 0 25px 0;
                list-style: none;
            }

            .pricing-features li {
                margin-bottom: 12px;
                padding-left: 28px;
                position: relative;
                font-size: 15px;
                color: #333;
            }

            .pricing-features li::before {
                content: "✔";
                position: absolute;
                left: 0;
                top: 0;
                color: #2d6a4f;
                font-weight: bold;
            }

            .btn-pricing {
                background: #2d6a4f;
                color: #fff;
                padding: 10px 28px;
                border-radius: 30px;
                font-size: 16px;
                font-weight: 600;
                transition: 0.3s ease;
                border: none;
            }

            .btn-pricing:hover {
                background: #40916c;
                transform: scale(1.05);
            }

            /* Highlight Popular Plan */
            .popular {
                border: 2px solid #52b788;
            }

            .popular .pricing-header {
                background: #40916c;
            }
        </style>

        <!-- <div class="our-excellence">
        <div class="container">
            <div class="section-title text-center mb-5">
                <h3 class="wow fadeInUp">Plans & Pricing</h3>
                <h2 class="text-anime-style-3" data-cursor="-opaque">
                    By the numbers: excellence in health
                </h2>
                <p class="wow fadeInUp" data-wow-delay="0.25s">
                    Excellence in healthcare is our standard, and our numbers back it up.
                    From patient satisfaction rates to successful treatment outcomes.
                </p>
            </div> -->

        <!-- <div class="row justify-content-center"> -->
        <?php if (!empty($plans) && is_array($plans)): ?>
            <?php foreach ($plans as $plan): ?>
                <div class="col-md-3 mb-4">
                    <div class="pricing-card <?= isset($plan['popular']) ? 'popular' : '' ?>">
                        <div class="pricing-header">
                            <?= htmlspecialchars($plan['name']) ?>
                        </div>

                        <div class="card-body text-center p-3">
                            <h2 class="pricing-price">₹<?= htmlspecialchars($plan['price']) ?></h2>
                            <p class="pricing-duration">/ <?= htmlspecialchars($plan['duration']) ?></p>

                            <ul class="pricing-features">
                                <?php if (!empty($plan['features']) && is_array($plan['features'])): ?>
                                    <?php foreach ($plan['features'] as $feature): ?>
                                        <li><?= htmlspecialchars($feature) ?></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>

                            <button class="btn-pricing" data-bs-toggle="modal" data-bs-target="#paymentModal"
                                data-plan='<?= json_encode($plan, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                Get Started
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Optional fallback message -->
            <!--<p>No plans available right now.</p>-->
        <?php endif; ?>

    </div>
    </div>
    </div>

    <section class="symptoms">
        <h2>Common Known Symptoms</h2>
        <p>Learn more about common health issues and how to manage them effectively.</p>

        <div class="slider-container">
            <!-- Left Button -->
            <button class="nav-btn prev">&#8592;</button>

            <div class="slider">
                <div class="slide-track">
                    <div class="slide">
                        <img src="./assets/images/food_poisoning.png" alt="Food Poisoning">
                        <br>
                        <span>Food Poisoning</span>
                    </div>
                    <div class="slide">
                        <img src="./assets/images/diabetes.png" alt="Diabetes">
                        <br>
                        <span>Diabetes</span>
                    </div>
                    <div class="slide">
                        <img src="./assets/images/cold_cough.png" alt="Cold & Cough">
                        <br>
                        <span>Cold & Cough</span>
                    </div>
                    <div class="slide">
                        <img src="./assets/images/diet_nutrition.png" alt="Dieting">
                        <br>
                        <span>Dieting & Nutritional Deficiency</span>
                    </div>
                    <!-- duplicate items for seamless infinite scroll -->
                    <div class="slide">
                        <img src="./assets/images/acid_reflux.png" alt="Food Poisoning">
                        <br>
                        <span>Acid Reflux & Indigestion</span>
                    </div>
                    <div class="slide">
                        <img src="./assets/images/hypertension.png" alt="Diabetes">
                        <br>
                        <span>High Blood Pressure (Hypertension)</span>
                    </div>
                    <div class="slide">
                        <img src="./assets/images/obesity.png" alt="Cold & Cough">
                        <br>
                        <span>Obesity & Weight Gain</span>
                    </div>
                    <div class="slide">
                        <img src="./assets/images/back_pain.png" alt="Dieting">
                        <br>
                        <span>Back Pain & Poor Posture</span>
                    </div>
                    <div class="slide">
                        <img src="./assets/images/period.png" alt="Dieting">
                        <br>
                        <span>Period Cramps</span>
                    </div>
                </div>
            </div>
            <!-- Right Button -->
            <button class="nav-btn next">&#8594;</button>
        </div>
    </section>

    <!-- Our Excellence Section End -->

    <!-- Our Faqs Section Start -->
    <div class="our-faqs bg-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="our-faqs-content">
                        <!-- Section Title Start -->
                        <div class="section-title">
                            <h3 class="wow fadeInUp">frequently asked questions</h3>
                            <h2 class="text-anime-style-3" data-cursor="-opaque">Helping you understand healthcare</h2>
                            <p class="wow fadeInUp" data-wow-delay="0.25s">Here to make your experience as seamless as
                                possible—explore answers to common questions about our services, policies, and patient
                                care.</p>
                        </div>
                        <!-- Section Title End -->

                        <!-- Faq CTA Box Start -->
                        <div class="faq-cta-box wow fadeInUp" data-wow-delay="0.5s">
                            <div class="icon-box">
                                <img src="./assets/images/icon-faq-cta.svg" alt=""
                                    style="filter: invert(75%) sepia(26%) saturate(1500%) hue-rotate(28deg) brightness(95%) contrast(90%);">
                            </div>
                            <div class="faq-cta-content">
                                <p>We always take care of your smile</p>
                                <!--<h3>24/7 Emergency</h3>-->
                                <p>92207 82066</p>
                                <p>hello@aykacare.in</p>
                            </div>
                        </div>
                        <!-- Faq CTA Box End -->
                    </div>
                </div>

                <div class="col-lg-6">
                    <!-- FAQ Accordion Start -->
                    <div class="faq-accordion" id="faqaccordion">
                        <!-- FAQ Item Start -->
                        <div class="accordion-item wow fadeInUp">
                            <h2 class="accordion-header" id="heading1">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse1" aria-expanded="true" aria-controls="collapse1">
                                    What services does AYKA Care - Connect with Top Medical Professionals offer?
                                </button>
                            </h2>
                            <div id="collapse1" class="accordion-collapse collapse show" aria-labelledby="heading1"
                                data-bs-parent="#faqaccordion">
                                <div class="accordion-body">
                                    <p>AYKA Care - Connect with Top Medical Professionals offers a comprehensive range
                                        of medical services, including dental, gynecology, orthology, neurology, general
                                        medicine, dermatology, and cardiology. We also provide advanced lab testing and
                                        diagnostic services.</p>
                                </div>
                            </div>
                        </div>
                        <!-- FAQ Item End -->

                        <!-- FAQ Item Start -->
                        <div class="accordion-item wow fadeInUp" data-wow-delay="0.2s">
                            <h2 class="accordion-header" id="heading2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                                    What makes AYKA Care - Connect with Top Medical Professionals different from other
                                    healthcare providers?
                                </button>
                            </h2>
                            <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="heading2"
                                data-bs-parent="#faqaccordion">
                                <div class="accordion-body">
                                    <p>AYKA Care - Connect with Top Medical Professionals stands out due to its
                                        commitment to affordable healthcare, advanced medical technology, top-tier
                                        specialists, and 24-hour service. We also offer discounts on all medical
                                        treatments and ensure a swift enrollment process.</p>
                                </div>
                            </div>
                        </div>
                        <!-- FAQ Item End -->

                        <!-- FAQ Item Start -->
                        <div class="accordion-item wow fadeInUp" data-wow-delay="0.4s">
                            <h2 class="accordion-header" id="heading3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                                    How can I book an Appointment at AYKA Care - Connect with Top Medical Professionals?
                                </button>
                            </h2>
                            <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="heading3"
                                data-bs-parent="#faqaccordion">
                                <div class="accordion-body">
                                    <p>You can easily book an Appointment through our website by navigating to the 'Book
                                        An Appointment' section. Simply select the service you need, choose a convenient
                                        time, and confirm your booking.</p>
                                </div>
                            </div>
                        </div>
                        <!-- FAQ Item End -->

                        <!-- FAQ Item Start -->
                        <div class="accordion-item wow fadeInUp" data-wow-delay="0.6s">
                            <h2 class="accordion-header" id="heading4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                                    What types of diagnostic tests are available at your lab?
                                </button>
                            </h2>
                            <div id="collapse4" class="accordion-collapse collapse" aria-labelledby="heading4"
                                data-bs-parent="#faqaccordion">
                                <div class="accordion-body">
                                    <p>Our laboratory offers a wide range of diagnostic tests, including Complete Blood
                                        Count (CBC), Hemoglobin (Hb) tests, X-rays, and CT scans. We provide timely and
                                        accurate results to support your healthcare needs.</p>
                                </div>
                            </div>
                        </div>
                        <!-- FAQ Item End -->

                        <!-- FAQ Item Start -->
                        <div class="accordion-item wow fadeInUp" data-wow-delay="0.8s">
                            <h2 class="accordion-header" id="heading5">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse5" aria-expanded="true" aria-controls="collapse5">
                                    Are there any discounts available on medical treatments?
                                </button>
                            </h2>
                            <div id="collapse5" class="accordion-collapse collapse" aria-labelledby="heading5"
                                data-bs-parent="#faqaccordion">
                                <div class="accordion-body">
                                    <p>Yes, AYKA Care - Connect with Top Medical Professionals offers discounts on all
                                        medical treatments. For example, we provide a 5% discount on CBC and Hemoglobin
                                        tests, and a 10% discount on X-rays and CT scans.</p>
                                </div>
                            </div>
                        </div>
                        <!-- FAQ Item End -->

                        <!-- FAQ Item Start -->
                        <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                            <h2 class="accordion-header" id="heading6">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                    What are your operating hours?
                                </button>
                            </h2>
                            <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                data-bs-parent="#faqaccordion">
                                <div class="accordion-body">
                                    <p>AYKA Care - Connect with Top Medical Professionals operates 24 hours a day, 7
                                        days a week, ensuring that you receive the care you need whenever you need it.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <!-- FAQ Item End -->

                        <!-- subscriptions FAQ started -->

                    </div>
                    <!-- FAQ Accordion End -->
                </div>
            </div>
        </div>
    </div>
    <!-- Our Faqs Section End -->



    <!-- CTA Section Start -->
    <div class="cta-section bg-section mt-5" style="
    position: relative;
    overflow: hidden;
">
        <!-- Background Image with Blur -->
        <div style="
        background: url('./assets/images/ayka.webp') no-repeat center center;
        background-size: cover;
        filter: blur(4px);
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
    "></div>
        <!--<a href="tel:+919220782066"
                    style="
    position: absolute; 
    bottom: 20px; 
    right: 20px; 
    background:#25D366; 
    color:#fff; 
    padding:12px 25px; 
    border-radius:30px; 
    font-size:18px; 
    text-decoration:none; 
    font-weight:bold; 
    box-shadow:0 4px 8px rgba(0,0,0,0.2); 
    z-index: 100;
    cursor: pointer;
">
                    📞 Call Now
                </a>
-->

        <!--<a href="https://wa.me/919220782066?text=Hi%20I%20need%20help"-->
        <!--    style="position:fixed; bottom:20px; right:20px; background:#25D366; color:#fff; padding:12px 12px; border-radius:50px; display:flex; align-items:center; gap:10px; text-decoration:none; font-size:16px; font-weight:bold; box-shadow:0 4px 8px rgba(0,0,0,0.3); z-index:999;">-->
        <!--    <img src="https://cdn-icons-png.flaticon.com/512/733/733585.png"-->
        <!--        alt="WhatsApp" style="width:24px; height:24px;">-->


        <!--</a>-->

        <!-- Content above background -->
        <div class="container" style="position: relative; z-index: 2; min-height: 350px;">
            <div class="row">
                <div class="col-lg-4 col-md-3 col-6 order-lg-1 order-md-1 order-2">
                    <!-- CTA Box img 1 Start -->
                    <div class="cta-img-1" style="display: none;">
                        <img src="./assets/images/cta-img-1.png" alt="">
                    </div>
                    <!-- CTA Box img 1 End -->
                </div>

                <div class="col-lg-4 col-md-6 order-lg-2 order-md-2 order-1">
                    <!-- CTA Box Content Start -->
                    <div class="cta-box-content">
                        <!-- Section Title Start -->
                        <div class="section-title">
                            <h2 class="text-anime-style-3" data-cursor="-opaque">
                                Take the first step to better health
                            </h2>
                        </div>
                        <!-- Section Title End -->

                        <!-- CTA Box Text Start -->
                        <div class="cta-box-text wow fadeInUp" data-wow-delay="0.5s">
                            <p>It only <span>takes 2 minutes</span> to complete</p>
                        </div>
                        <!-- CTA Box Text End -->
                    </div>
                    <!-- CTA Box Content End -->
                </div>

                <div class="col-lg-4 col-md-3 col-6 order-lg-3 order-md-3 order-3">
                    <!-- CTA Box img 2 Start -->
                    <div class="cta-img-2" style="display: none;">
                        <img src="./assets/images/cta-img-2.png" alt="">
                    </div>
                    <!-- CTA Box img 2 End -->
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section End -->

    <!-- Our Health Section Start -->
    <div class="our-health">
        <div class="container">
            <div class="row section-row align-items-center mb-0">
                <div class="col-lg-12">
                    <!-- Section Title Start -->
                    <div class="section-title">
                        <h3 class="wow fadeInUp">our numbers</h3>
                        <h2 class="text-anime-style-3" data-cursor="-opaque">RESULTS SPEAK LOUDER THAN WORDS
                        </h2>
                        <p class="wow fadeInUp" data-wow-delay="0.25s">AYKA Care doesn't just promise—we DELIVER. Our
                            relentless pursuit of Excellence is proven by our Unmatched Success Metrics. See the proof:
                            From Record-High Patient Happiness to Life-Changing Treatment Outcomes, our numbers tell
                            your next health story.</p>
                    </div>
                    <!-- Section Title End -->
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <!-- Our Health Boxes Start -->
                    <div class="our-health-boxes">
                        <!-- Our Health Item Start -->
                        <div class="our-health-item health-box-1">
                            <div class="health-item-content">
                                <img src="./assets/images/icon-health-item-1.svg" alt="">
                                <h3>Your Health, Our Priority in Wellcare</h3>
                                <a href="#" class="btn-default">read more</a>
                            </div>
                            <div class="health-item-image">
                                <img src="./assets/images/health-item-img-1.png" alt="">
                            </div>
                        </div>
                        <!-- Our Health Item End -->

                        <!-- Our Health Item Start -->
                        <div class="our-health-image health-box-2">
                            <figure class="image-anime reveal">
                                <img src="./assets/images/health-item.jpg" alt="">
                            </figure>
                        </div>
                        <!-- Our Health Item End -->

                        <!-- Our Health Item Start -->
                        <div class="our-health-image health-box-3">
                            <figure class="image-anime reveal">
                                <img src="./assets/images/health-item-img-3.jpg" alt="">
                            </figure>
                        </div>
                        <!-- Our Health Item End -->

                        <!-- Our Health Item Start -->
                        <div class="our-health-item health-box-4">
                            <div class="health-item-content">

                                <h3><span class="counter">5000</span>+ Happy Clients</h3>
                            </div>
                            <div class="happy-client-images">
                                <div class="happy-client-img">
                                    <figure class="image-anime reveal">
                                        <img src="./assets/images/patient4.jpeg" alt="">
                                    </figure>
                                </div>
                                <div class="happy-client-img">
                                    <figure class="image-anime reveal">
                                        <img src="./assets/images/patient3.jpeg" alt="">
                                    </figure>
                                </div>
                                <div class="happy-client-img">
                                    <figure class="image-anime reveal">
                                        <img src="./assets/images/patient2.jpeg" alt="">
                                    </figure>
                                </div>
                                <div class="happy-client-img">
                                    <figure class="image-anime reveal">
                                        <img src="./assets/images/patient1.jpeg" alt="">
                                    </figure>
                                </div>
                                <div class="happy-client-img add-more">
                                    <figure>
                                        <a href="#"><i class="fa-solid fa-plus"></i></a>
                                    </figure>
                                </div>
                            </div>
                        </div>
                        <!-- Our Health Item End -->

                        <!-- Our Health Item Start -->
                        <div class="our-health-item health-box-5">
                            <div class="health-item-content">
                                <h3>Healing Starts Here Caring for You Always</h3>
                                <a href="#" class="learn-btn">learn more</a>
                            </div>
                            <div class="health-item-image">
                                <img src="./assets/images/health-item-img-4.png" alt="">
                            </div>
                        </div>
                        <!-- Our Health Item End -->
                    </div>
                    <!-- Our Health Boxes End -->
                </div>
            </div>
            <section id="testimonials" style="padding: 20px;">
                <h2 style="text-align:center; color:#2a4638; margin-bottom:20px;">Testimonials</h2>
                <div class="testimonial-wrapper">
                    <button class="scroll-btn left">&lt;</button>
                    <div class="testimonial-container">
                        <div class="testimonial-card">
                            <div class="testimonial-header">
                                <div class="testimonial-stars">★★★★★</div>
                            </div>
                            <img src="./assets/images/patient4.jpeg" alt="Alice" class="testimonial-img">
                            <p class="testimonial-text">“I was impressed with the professionalism of the doctors. The
                                whole process felt very secure.”</p>
                            <h6 class="testimonial-author">- Mr. Sandeep Chaudhary</h6>
                        </div>
                        <div class="testimonial-card">
                            <div class="testimonial-header">
                                <div class="testimonial-stars">★★★★☆</div>
                            </div>
                            <img src="./assets/images/patient3.jpeg" alt="Bob" class="testimonial-img">
                            <p class="testimonial-text">“Excellent platform! AYKA Care gave me confidence in accessing
                                healthcare from home.”</p>
                            <h6 class="testimonial-author">- Ms. Neha Pal</h6>
                        </div>
                        <div class="testimonial-card">
                            <div class="testimonial-header">
                                <div class="testimonial-stars">★★★★★</div>
                            </div>
                            <img src="./assets/images/patient2.jpeg" alt="Carol" class="testimonial-img">
                            <p class="testimonial-text">“From booking to consultation, everything was simple and smooth.
                                AYKA Care really delivers quality.”</p>
                            <h6 class="testimonial-author">Mrs. Vikesh Choudhary</h6>
                        </div>
                        <div class="testimonial-card">
                            <div class="testimonial-header">
                                <div class="testimonial-stars">★★★★☆</div>
                            </div>
                            <img src="./assets/images/patient1.jpeg" alt="Dave" class="testimonial-img">
                            <p class="testimonial-text">“AYKA Care made it so easy to get a second opinion without any
                                hassle. Thank you for the support!”</p>
                            <h6 class="testimonial-author">- Mr. Shubham Maurya</h6>
                        </div>
                        <div class="testimonial-card">
                            <div class="testimonial-header">
                                <div class="testimonial-stars">★★★★★</div>
                            </div>
                            <img src="./assets/images/meenakshi.jpeg" alt="Eve" class="testimonial-img">
                            <p class="testimonial-text">“Acne lowered my confidence, but AYKA Care connected me with a
                                dermatologist who treated me quickly. Trusted advice, real results, and smooth
                                experience. A true blessing for women!”</p>
                            <h6 class="testimonial-author">- Ms. Meenakshi</h6>
                        </div>
                        <div class="testimonial-card">
                            <div class="testimonial-header">
                                <div class="testimonial-stars">★★★★★</div>
                            </div>
                            <img src="./assets/images/subhash.jpeg" alt="Eve" class="testimonial-img">
                            <p class="testimonial-text">“When my son had sudden fever at night, AYKA Care instantly
                                connected me with a pediatrician. The doctor guided us and even arranged a clinic visit.
                                Truly stress-free.”</p>
                            <h6 class="testimonial-author">- Mr. Subhash Sharma</h6>

                        </div>
                        <button class="scroll-btn right">&gt;</button>
                    </div>
            </section>
        </div>

    </div>
    <!-- Our Health Section End -->

    <!-- Testimonial Section -->
    <!-- Testimonial Section -->


    <style>
        /* Testimonial wrapper */
        .testimonial-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        .testimonial-container {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding-bottom: 10px;
        }

        .testimonial-container::-webkit-scrollbar {
            display: none;
        }

        /* Testimonial Card */
        .testimonial-card {
            flex: 0 0 auto;
            width: 250px;
            min-height: 220px;
            background-color: #fff;
            border: 1px solid #dceae4;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            text-align: center;
            color: #2a4638;
        }

        /* Header for stars */
        .testimonial-header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-size: 0.85rem;
            color: #FF671F;
        }

        .testimonial-stars {
            font-size: 1rem;
        }

        .testimonial-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            border: 2px solid #4d793c;
        }

        .testimonial-text {
            font-size: 0.9rem;
            margin-bottom: 10px;
            color: #0e0e0e;
        }

        .testimonial-author {
            font-weight: 600;
            color: #0e0e0e;
        }

        /* Scroll buttons */
        .scroll-btn {
            background-color: #4d793c;
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 50%;
            cursor: pointer;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            transition: opacity 0.3s ease;
        }

        .scroll-btn.left {
            left: 0;
        }

        .scroll-btn.right {
            right: 0;
        }

        .scroll-btn.hidden {
            opacity: 0;
            pointer-events: none;
        }

        /* Responsive Mobile */
        @media (max-width: 576px) {
            .testimonial-card {
                width: 150px;
                min-height: 170px;
                padding: 10px;
            }

            .testimonial-header {
                font-size: 0.7rem;
            }

            .testimonial-stars {
                font-size: 0.8rem;
            }

            .testimonial-img {
                width: 40px;
                height: 40px;
                margin-bottom: 6px;
            }

            .testimonial-text {
                font-size: 0.8rem;
            }

            .testimonial-author {
                font-size: 0.75rem;
            }
        }
    </style>

    <script>
        const container = document.querySelector('.testimonial-container');
        const leftBtn = document.querySelector('.scroll-btn.left');
        const rightBtn = document.querySelector('.scroll-btn.right');

        // Scroll behavior
        leftBtn.addEventListener('click', () => {
            container.scrollBy({ left: -200, behavior: 'smooth' });
        });

        rightBtn.addEventListener('click', () => {
            container.scrollBy({ left: 200, behavior: 'smooth' });
        });

        // Function to check visibility of buttons
        function updateButtons() {
            const maxScrollLeft = container.scrollWidth - container.clientWidth;

            // Hide both if all items fit in container
            if (container.scrollWidth <= container.clientWidth + 1) {
                leftBtn.classList.add('hidden');
                rightBtn.classList.add('hidden');
            } else {
                // Left button
                if (container.scrollLeft <= 0) {
                    leftBtn.classList.add('hidden');
                } else {
                    leftBtn.classList.remove('hidden');
                }

                // Right button
                if (container.scrollLeft >= maxScrollLeft - 1) {
                    rightBtn.classList.add('hidden');
                } else {
                    rightBtn.classList.remove('hidden');
                }
            }
        }

        // Update on scroll and load
        container.addEventListener('scroll', updateButtons);
        window.addEventListener('resize', updateButtons);
        window.addEventListener('load', updateButtons);
    </script>
    <!-- Testimonial Section end-->

    <style>
        .footer {
            background: #f9f9f9;
            border-top-left-radius: 40px;
            border-top-right-radius: 40px;
        }

        .footer a {
            color: #000;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        .footer img {
            object-fit: contain;
        }

        .newyoutube {
            color: #000000;
        }
    </style>

    <!-- Footer Start -->
    <footer class="footer bg-light pt-5 pb-4">
        <div class="container text-center">
            <div class="row justify-content-center">

                <!-- Logo + Copyright + Social -->
                <div class="col-lg-4 col-md-12 mb-4">
                    <img src="./assets/images/logo.svg" alt="AYKA Care" class="mb-3" style="height:50px;">
                    <p class="mb-2">© 2025 AYKA Clyvora Private Limited <br> All rights reserved</p>

                    <!-- Social Icons -->
                    <div class="d-flex justify-content-center gap-3 mb-3">
                        <a href="https://www.facebook.com/ayka.care"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="https://www.instagram.com/ayka.care/"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="https://www.linkedin.com/company/iayka-care/"><i class="fab fa-linkedin fa-lg"></i></a>
                        <a href="https://www.youtube.com/@ayka_care"><i class="fab fa-youtube newyoutube fa-lg"></i></a>
                    </div>

                    <!-- App Store Buttons -->
                    <div class="d-flex justify-content-center gap-2">
                        <a href="https://play.google.com/store/search?q=ayka%20care&c=apps"><img
                                src="./assets/images/google-play.png" alt="Google Play" style="height:40px;"></a>
                        <a href="https://apps.apple.com/in/app/ayka-care/id6744580942"><img
                                src="./assets/images/apple-store.png" alt="App Store" style="height:40px;"></a>
                    </div>
                </div>

                <!-- AYKA Care Links -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="fw-bold mb-3">AYKA Care</h5>
                    <ul class="list-unstyled">
                        <li><a href="#about">About us</a></li>
                        <!--<li><a href="#">Homecare</a></li>-->
                        <li><a href="#">Specialist</a></li>
                        <li><a href="https://expert.aykacare.in/">Join AYKA Care</a></li>
                        <li><a href="https://expert.aykacare.in/?fluent-form=4">Contact Us</a></li>
                    </ul>
                </div>

                <!-- Support Links -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="fw-bold mb-3">Support</h5>
                    <ul class="list-unstyled">
                        <li><a href="https://policy.aykacare.in/" target="_blank">Terms and Conditions</a></li>
                        <li><a href="https://policy.aykacare.in/disclaimer/" target="_blank">Disclaimer</a></li>
                        <li><a href="https://policy.aykacare.in/">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>

            <!-- Payment Icons -->
            <div class="d-flex justify-content-center gap-3 mt-4">
                <img src="./assets/images/master.png" alt="Master" style="height:20px;">
                <img src="./assets/images/razorpay.png" alt="Razorpay" style="height:20px;">
                <img src="./assets/images/upi.png" alt="Upi" style="height:20px;">
                <img src="./assets/images/visa.jpg" alt="visa" style="height:20px;">
                <img src="./assets/images/secure.jpg" alt="secure" style="height:20px;">
            </div>
        </div>
    </footer>

    <!-- Footer End -->

    <!-- Download App Modal -->
    <!--<div id="appModal" class="custom-modal">-->
    <!--    <div class="custom-modal-content">-->
    <!--        <span class="close-btn">&times;</span>-->
    <!--        <h3>📲 Download Our App</h3>-->
    <!--        <p>For the best experience, download our app today!</p>-->
    <!--        <div class="app-buttons">-->
    <!--            <a href="https://aykacare.in/downloadmyapp.php"> Download Now</a>-->
    <!--        </div>-->
    <!--    </div>-->
    <!--</div>-->

    <!-- Consultation Modal -->





    <!--    <div class="modal fade" id="consultationModal" tabindex="-1" aria-labelledby="consultationLabel" aria-hidden="true">-->
    <!--<div class="modal-dialog modal-dialog-centered">-->
    <!--    <div class="modal-content rounded-4 shadow-lg border-0 position-relative overflow-hidden">-->

    <!--        <div style="-->
    <!--            position: absolute;-->
    <!--            top: 0; left: 0;-->
    <!--            width: 100%; height: 100%;-->
    <!--            background: #d8dc9a;-->
    <!--            opacity: 0.3;-->
    <!--            z-index: 0;-->
    <!--            filter: blur(15px);-->
    <!--        "></div>-->

    <!--<div class="modal-header border-0 flex-column align-items-center position-relative z-index-1">-->
    <!--    <h5 class="modal-title text-success fw-bold fs-3" id="consultationLabel">-->
    <!--        This Diwali, Gift the Light of Health!-->
    <!--    </h5>-->
    <!--    <p class="text-center text-dark mb-0 fw-semibold">Light up your life with wellness this festive season.</p>-->
    <!--</div>-->

    <!--<div class="modal-body text-center px-4 position-relative z-index-1">-->
    <!--    <p class="fs-5 fw-semibold">Get the peace of mind you deserve! Expert advice from our 2500+ doctors, </b>100% Free for a limited time.</p>-->

    <!--    <ul class="list-unstyled d-inline-block mx-auto text-center" style="max-width: 300px; padding-left: 0;">-->
    <!--        <li class="mb-2"><span class="text-success fw-bold me-2">✔</span>No Consultation Fees</li>-->
    <!--        <li class="mb-2"><span class="text-success fw-bold me-2">✔</span>Zero Stress, Maximum Care</li>-->
    <!--        <li class="mb-2"><span class="text-success fw-bold me-2">✔</span>Start Your New Year Healthy</li>                </ul>-->
    <!--</div>-->

    <!--<div class="modal-footer border-0 justify-content-center position-relative z-index-1">-->
    <!--    <a href="https://aykacare.in/downloadmyapp.php"-->
    <!--        class="btn btn-success btn-lg rounded-pill px-5 fw-bold shadow-lg custom-btn-glow"-->
    <!--        style="background-image: linear-gradient(to right, #4CAF50, #8BC34A); border: none; color: white; padding: 15px 30px; font-size: 20px; animation: pulse 1.5s infinite;">-->
    <!--        Claim Your FREE Consultation Now!-->

    <!--    </a>-->
    <!--</div>-->

    <!--    </div>-->
    <!--</div>-->
    <!--</div>-->

    <style>
        /* Custom CSS to make the button more lucrative and add animation */
        .custom-btn-glow {
            transition: all 0.3s ease-in-out;
        }

        .custom-btn-glow:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.03);
            }

            100% {
                transform: scale(1);
            }
        }
    </style>





    <!--dialog end-->

    </div>
    </div>
    </div>





    <!-- Jquery Library File -->
    <script src="./assets/js/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap js file -->
    <script src="./assets/js/bootstrap.min.js"></script>
    <!-- Validator js file -->
    <script src="./assets/js/validator.min.js"></script>
    <!-- SlickNav js file -->
    <script src="./assets/js/jquery.slicknav.js"></script>
    <!-- Swiper js file -->
    <script src="./assets/js/swiper-bundle.min.js"></script>
    <!-- Counter js file -->
    <script src="./assets/js/jquery.waypoints.min.js"></script>
    <script src="./assets/js/jquery.counterup.min.js"></script>
    <!-- Isotop js file -->
    <script src="./assets/js/isotope.min.js"></script>
    <!-- Magnific js file -->
    <!--<script src="./assets/js/jquery.magnific-popup.min.js"></script>-->
    <!-- SmoothScroll -->
    <script src="./assets/js/SmoothScroll.js"></script>
    <!-- Parallax js -->
    <script src="./assets/js/parallaxie.js"></script>
    <!-- MagicCursor js file -->
    <script src="./assets/js/gsap.min.js"></script>
    <!--<script src="./assets/js/magiccursor.js"></script>-->
    <!-- Text Effect js file -->
    <script src="./assets/js/SplitText.js"></script>
    <script src="./assets/js/ScrollTrigger.min.js"></script>
    <!-- YTPlayer js File -->
    <!--<script src="./assets/js/jquery.mb.YTPlayer.min.js"></script>-->
    <!-- Wow js file -->
    <!--<script src="./assets/js/wow.min.js"></script>-->
    <!-- Main Custom js file -->
    <script src="./assets/js/function.js"></script>

    <script>
        $(document).ready(function () {
            $('#paymentModal').on('show.bs.modal', function (event) {
                let button = $(event.relatedTarget);
                let plan = JSON.parse(button.attr('data-plan'));
                //   alert(plan.members);
                // return
                // Fill hidden inputs
                $('#planName').val(plan.name);
                $('#planAmount').val(plan.price); // <-- FIX
                // $("#TotalPaybleAmount").val(plan.total);
                $('#planDuration').val(plan.duration);
                $('#members').val(plan.members ? plan.members : "N/A"); // fallback

                // Display plan title + features
                $('#planTitle').text(plan.name);
                $('#planFeatures').html('');
                if (plan.features && plan.features.length) {
                    plan.features.forEach(f => {
                        $('#planFeatures').append(`<li>${f}</li>`);
                    });
                }

                // Calculate GST (18%)
                let gst = (plan.price * 0.18).toFixed(2);
                let total = (parseFloat(plan.price) + parseFloat(gst)).toFixed(2);
                // Update hidden fields for form submission
                $('#planAmount').val(plan.price); // base price only
                $('#TotalPaybleAmount').val(total);

                $('#amountDisplay').val(`₹ ${plan.price}`);
                $('#gstAmount').val(`₹ ${gst}`);
                $('#totalAmount').text(`₹ ${total}`);

                // Calculate Start + End Dates
                let startDate = new Date();
                let durationParts = plan.duration.split(' ');
                let durationValue = parseInt(durationParts[0]);
                let durationUnit = durationParts[1].toLowerCase();

                let endDate = new Date(startDate);
                if (durationUnit.includes('month')) {
                    endDate.setMonth(endDate.getMonth() + durationValue);
                } else if (durationUnit.includes('year')) {
                    endDate.setFullYear(endDate.getFullYear() + durationValue);
                } else if (durationUnit.includes('day')) {
                    endDate.setDate(endDate.getDate() + durationValue);
                }

                // Format YYYY-MM-DD
                const formatDate = (date) => date.toISOString().split('T')[0];
                $('#startdate').val(formatDate(startDate));
                $('#enddate').val(formatDate(endDate));
            });
        });

        const track = document.querySelector('.slide-track');
        const prevBtn = document.querySelector('.prev');
        const nextBtn = document.querySelector('.next');

        let pos = 0;
        let speed = 0.5; // lower = slower
        let slideWidth = 290; // item + margin
        let isPaused = false;

        function animate() {
            if (!isPaused) {
                pos -= speed;
                if (Math.abs(pos) >= track.scrollWidth / 2) {
                    pos = 0; // reset for infinite loop
                }
                track.style.transform = `translateX(${pos}px)`;
            }
            requestAnimationFrame(animate);
        }
        animate();

        // buttons control
        nextBtn.addEventListener('click', () => {
            isPaused = true;
            pos -= slideWidth;
            track.style.transform = `translateX(${pos}px)`;
            setTimeout(() => (isPaused = false), 1000);
        });

        prevBtn.addEventListener('click', () => {
            isPaused = true;
            pos += slideWidth;
            track.style.transform = `translateX(${pos}px)`;
            setTimeout(() => (isPaused = false), 1000);
        });


        document.addEventListener("DOMContentLoaded", function () {
            const modal = document.getElementById("appModal");
            const closeBtn = document.querySelector(".close-btn");

            // Attach click listener to all cards
            document.querySelectorAll('.specialist-card').forEach(card => {
                card.addEventListener('click', () => {
                    modal.style.display = "flex"; // open modal
                });
            });

            // Close modal on X button
            closeBtn.addEventListener("click", function () {
                modal.style.display = "none";
            });

            // Close modal when clicking outside
            window.addEventListener("click", function (e) {
                if (e.target === modal) {
                    modal.style.display = "none";
                }
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            const modal = document.getElementById("appModal");
            const closeBtn = document.querySelector(".close-btn");

            // Attach click listener to all slider items
            document.querySelectorAll('.slide').forEach(slide => {
                slide.addEventListener('click', () => {
                    modal.style.display = "flex"; // open the same modal
                });
            });

            // Close modal on X button
            closeBtn.addEventListener("click", function () {
                modal.style.display = "none";
            });

            // Close modal when clicking outside
            window.addEventListener("click", function (e) {
                if (e.target === modal) {
                    modal.style.display = "none";
                }
            });
        });

        window.addEventListener('load', function () {
            var consultationModal = new bootstrap.Modal(document.getElementById('consultationModal'));
            consultationModal.show();
        });
    </script>

    <script>window.$zoho = window.$zoho || {}; $zoho.salesiq = $zoho.salesiq || { ready: function () { } }</script>
    <script id="zsiqscript"
        src="https://salesiq.zohopublic.in/widget?wc=siqb0f830a0885067d021d45d0a48528e2d255c3bf6ab1e64226805d95539ae3b1a"
        defer></script>

</body>


</html>