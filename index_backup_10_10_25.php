<?php


session_start();
require('vendor/autoload.php');

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

// Razorpay credentials
$keyId = 'rzp_live_1hzhzXuOlge5pF';
$keySecret = 'vGUPpWNcWAJINBJcH9GFCb3R';
$api = new Api($keyId, $keySecret);

// DB connection
$conn = new mysqli('localhost', 'u685993406_updatedayka', '*A$i*Tx2', 'u685993406_updatedayka');
if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}

// Handle form submission to create Razorpay order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && !isset($_POST['razorpay_payment_id'])) {
    $form = [
        'plan_name' => $_POST['plan_name'],
        'amount' => (int) $_POST['amount'],
        'TotalPaybleAmount' => $_POST['TotalPaybleAmount'],
        'duration' => $_POST['duration'],
        'members' => (int) $_POST['members'],
        'startdate' => $_POST['startdate'],
        'enddate' => $_POST['enddate'],
        'f_name' => $_POST['name'],
        'l_name' => $_POST['L_name'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
        'gender' => $_POST['gender'],
        'dob' => $_POST['dob'],
        'city' => $_POST['city']
    ];
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
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        echo "<h3>✅ Payment Successful!</h3>";
    } elseif ($_GET['status'] === 'cancel') {
        echo "<h3>❌ Payment Cancelled by User</h3>";
    } elseif ($_GET['status'] === 'error') {
        echo "<h3>⚠️ Payment Failed or Signature Invalid</h3>";
    }
}

?>
<!DOCTYPE html>
<html lang="zxx">

<head>
    
    
    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-LLTEYXSWTR"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-LLTEYXSWTR');
</script>
    
    
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
    <link href="./assets/css/custom.css" rel="stylesheet" media="screen">
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
                                <li><a href="https://www.instagram.com/ayka.care/"><i class="fa-brands fa-instagram"></i></a></li>
                                <li><a href="https://www.facebook.com/ayka.care"><i class="fa-brands fa-facebook-f"></i></a></li>
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
                                <li class="nav-item"><a class="nav-link" href="#about">About Us</a></li>
                                <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                                 <li class="nav-item submenu"><a class="nav-link" href="">Membership Plans</a>
                                    <ul>
                                        <li class="nav-item"><a class="nav-link"  href="./individual-plan.php">Individual Plan</a></li>
                                        <li class="nav-item"><a class="nav-link" href="./family-plan.php">Family Plan Lite/Elite</a></li>
                                         <!-- <li class="nav-item"><a class="nav-link" href="">Family Plan Elite</a></li>  -->
                                        <li class="nav-item"><a class="nav-link"  href="./membership-plan.php">Senior Care Plan</a></li>
                                    </ul>
                                </li>        

                                <li class="nav-item"><a class="nav-link" href="./contact-us.php">Contact Us</a></li>
                                <li class="nav-item highlighted-menu"><a class="nav-link"
                                        href="https://aykacare.in/downloadmyapp.php">book appointment</a></li>
                            </ul>
                        </div>

                        <!-- Header Button Start-->
                        <div class="header-btn">
                            <a href="https://aykacare.in/downloadmyapp.php" class="btn-default">book appointment</a>
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
                            <h2 class="text-anime-style-3" data-cursor="-opaque">Connect with Top Medical Professionals
                            </h2>
                            <p class="wow fadeInUp" data-wow-delay="0.2s">Experience Unmatched Healthcare Excellence at AYKA Care - Connect with Top Medical Professionals.</p>
                        </div>
                        <!-- Section Title End -->

                        <!-- Hero Buttons Start -->
                        <div class="hero-btn wow fadeInUp" data-wow-delay="0.4s">
                            <a href="" class="btn-default">book a appointment</a>
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
                            <p class="wow fadeInUp" data-wow-delay="0.25s">AYKA Care delivers Next-Level Pain Relief. Our compassionate, elite team of specialists doesn't just treat symptoms—we engineer a personalized, life-changing plan to RECLAIM your quality of life. Your pain stops here. Your future starts now..</p>
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
                                    <h3>YOU are the Priority - <br>Hyper-Personalized Care.</h3>
                                    <p>We listen intensely, craft a plan just for you, and obsess over your comfort and TOTAL well-being. This is healthcare built around your life.</p>
                                </div>
                            </div>
                            <!-- About Info Item End -->

                            <!-- About Info Item Start -->
                            <div class="about-info-item wow fadeInUp" data-wow-delay="0.25s">
                                <div class="icon-box">
                                    <img src="./assets/images/icon-about-info-2.png" alt="">
                                </div>
                                <div class="about-info-item-content">
                                    <h3>Pain Relief ELITE</h3>
                                    <p> Get access to the nation's Top-Tier Pain Specialists. We utilize cutting-edge, proven techniques and medical breakthroughs for the lasting relief you deserve. (No Band-Aids. Just Breakthroughs.)</p>
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
                                    <p>Your health doesn't take a break, so neither do we. Get instant, dependable, around-the-clock support for ultimate peace of mind. AYKA Care is ALWAYS on.</p>
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
                        <h3 class="wow fadeInUp">our services</h3>
                        <h2 class="text-anime-style-3" data-cursor="-opaque">Comprehensive services for your health</h2>
                    </div>
                    <!-- Section Title End -->
                </div>
            </div>

            <div class="row align-items-center">
      <style>
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
  </style>
  <div class="row g-4" id="services">
      <!-- Repeat this block for each specialist -->
      <div class="col-6 col-sm-4 col-md-3">
        <a href="#services" class="specialist-link">
            <div class="specialist-card">
            <img src="./assets/images/service-icon/GeneralPhysician.webp" alt="General Physician">
            <div class="specialist-title">General Physician</div>
            </div>
        </a>
      </div>

      <div class="col-6 col-sm-4 col-md-3">
        <div class="specialist-card">
          <img src="./assets/images/service-icon/Gynecology.webp" alt="Gynecology">
          <div class="specialist-title">Gynecology</div>
        </div>
      </div>

      <div class="col-6 col-sm-4 col-md-3">
        <div class="specialist-card">
          <img src="./assets/images/service-icon/Psychiatry.webp" alt="Psychiatry">
          <div class="specialist-title">Psychiatry</div>
        </div>
      </div>

      <div class="col-6 col-sm-4 col-md-3">
        <div class="specialist-card">
          <img src="./assets/images/service-icon/Pediatrics.webp" alt="Pediatrics">
          <div class="specialist-title">Pediatrics</div>
        </div>
      </div>

      <div class="col-6 col-sm-4 col-md-3">
        <div class="specialist-card">
          <img src="./assets/images/service-icon/ENT (Ear, Nose, Throat).webp" alt="ENT">
          <div class="specialist-title">ENT (Ear, Nose, Throat)</div>
        </div>
      </div>

      <div class="col-6 col-sm-4 col-md-3">
        <div class="specialist-card">
          <img src="./assets/images/service-icon/Orthopedics.webp" alt="Orthopedics">
          <div class="specialist-title">Orthopedics</div>
        </div>
      </div>

      <div class="col-6 col-sm-4 col-md-3">
        <div class="specialist-card">
          <img src="./assets/images/service-icon/Cardiology.webp" alt="Cardiology">
          <div class="specialist-title">Cardiology</div>
        </div>
      </div>

      <div class="col-6 col-sm-4 col-md-3">
        <div class="specialist-card">
          <img src="./assets/images/service-icon/2025-04-30-6811cf0387c7c.webp" alt="Home Health Aid">
          <div class="specialist-title">Home Health Aid</div>
        </div>
      </div>

      <div class="col-6 col-sm-4 col-md-3">
        <div class="specialist-card">
          <img src="./assets/images/service-icon/Nutrition and Dietetics.webp" alt="Nutrition">
          <div class="specialist-title">Nutrition and Dietetics</div>
        </div>
      </div>

      <div class="col-6 col-sm-4 col-md-3">
        <div class="specialist-card">
          <img src="./assets/images/service-icon/2025-04-30-6811cf9c7e714.webp" alt="Endocrinologist">
          <div class="specialist-title">Endocrinologist</div>
        </div>
      </div>

      <div class="col-6 col-sm-4 col-md-3">
        <div class="specialist-card">
          <img src="./assets/images/service-icon/2025-04-30-68122781158b3.webp" alt="Neurologist">
          <div class="specialist-title">Neurologist</div>
        </div>
      </div>

      <div class="col-6 col-sm-4 col-md-3">
        <div class="specialist-card">
          <img src="./assets/images/service-icon/2025-04-30-68122704cc35f.webp" alt="BAMS">
          <div class="specialist-title ">BAMS Doctor (Ayurvedic Practitioner)</div>
        </div>
      </div>

      <div class="col-6 col-sm-4 col-md-3">
        <div class="specialist-card">
          <img src="./assets/images/service-icon/2025-04-30-6811d01a50be5.webp" alt="Dentistry">
          <div class="specialist-title">Dentistry</div>
        </div>
      </div>

      <div class="col-6 col-sm-4 col-md-3">
        <div class="specialist-card">
          <img src="./assets/images/service-icon/2025-04-30-6811d0f4eaa51.webp" alt="BHMS">
          <div class="specialist-title text-size">BHMS (Homeopathic Medicine and Surgery)</div>
        </div>
      </div>

      <div class="col-6 col-sm-4 col-md-3">
        <div class="specialist-card">
          <img src="./assets/images/service-icon/GeneralPhysician.webp" alt="BNYS">
          <div class="specialist-title text-size">BNYS (Naturopathy & Yogic Science)</div>
        </div>
      </div>

      <div class="col-6 col-sm-4 col-md-3">
        <div class="specialist-card">
          <img src="./assets/images/service-icon/GeneralPhysician.webp" alt="Physiotherapist">
          <div class="specialist-title">Physiotherapist</div>
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
                        <h3 class="wow fadeInUp">why choose us</h3>
                        <h2 class="text-anime-style-3" data-cursor="-opaque">Tired of Waiting? Tired of Guessing?</h2>
                        <p class="wow fadeInUp" data-wow-delay="0.25s">AYKA Care is the Future of Health. Our unshakeable commitment to Excellence, Empathy, and Personalized Results has made us the trusted choice. Discover the AYKA difference—where you move from just 'a patient' to a Health Success Story.
                        </p>
                    </div>
                    <!-- Section Title End -->
                </div>

                <div class="col-lg-5">
                    <!-- Why Choose List Start -->
                    <div class="why-choose-list wow fadeInUp">
                        <ul>
                            <li>Flexibility Engineered For YOU. Never skip a meeting for a doctor again.</li>
                            <li>Zero-Stress Zone. Our team is obsessed with your total comfort and confidence.</li>
                            <li>Immediate Impact. Get prompt, highly-effective care that delivers real results.</li>
                            <li>Lifelong Health Partner. We don't just treat; we actively manage your success at EVERY stage.</li>
                        </ul>
                    </div>
                    <!-- Why Choose List End -->
                </div>
            </div>


<div class="why-choose-section" style="display:flex; flex-wrap: wrap; justify-content: center; align-items: center; gap:50px; min-height:600px;">
    <!-- Left: Vertical Video -->
    <div class="video-container" style="flex: 0 0 300px; height: 600px; position: relative; border-radius: 15px; overflow: hidden; display:flex; align-items:center; justify-content:center;">
        <iframe id="yt-short-video" 
            src="https://www.youtube.com/embed/XIFm4sFGHUM?autoplay=1&mute=1&controls=1&rel=0&modestbranding=1&loop=1&playlist=XIFm4sFGHUM"
            frameborder="0" allow="autoplay; encrypted-media; fullscreen" allowfullscreen
            style="width:100%; height:100%; border-radius: 15px;">
        </iframe>
    </div>

    <!-- Right: Vertical Text Content -->
    <div class="why-choose-text" style="flex: 1; flex-direction: column; align-items: center; gap:30px; text-align:center; color:#000;">
        
        <!-- Collage Image -->
        <div class="collage" style="width:100%; display:flex; justify-content:center;">
            <img src="./assets/images/collage.jpeg" 
            alt="Collage Image" 
            style="width: 396px; height: 759px; object-fit:cover; border-radius:15px; transform: rotate(90deg);">
        </div>
        <!-- 3 Points in One Line -->
        <div class="why-choose-items" style="display:flex; justify-content: center; align-items: flex-start; gap:40px; flex-wrap:wrap; position: relative;
    top: -139px;">
            
            <!-- Item 1 -->
            <div class="why-choose-item" style="flex:1; min-width:200px; display:flex; flex-direction:column; align-items:center; gap:10px;">
                <div class="icon-box">
                    <img src="./assets/images/icon-why-choose-1.svg" alt="" style="width:50px;">
                </div>
                <h3 style="font-size:20px; margin-bottom:5px; color:#000;">2500+ Expert Doctors</h3>
                <p style="font-size:16px; color:#000;">Our team includes over 50 highly skilled doctors.</p>
            </div>

            <!-- Item 2 -->
            <div class="why-choose-item" style="flex:1; min-width:200px; display:flex; flex-direction:column; align-items:center; gap:10px;">
                <div class="icon-box">
                    <img src="./assets/images/icon-why-choose-2.svg" alt="" style="width:50px;">
                </div>
                <h3 style="font-size:20px; margin-bottom:5px; color:#000;">24/7 Instant Support</h3>
                <p style="font-size:16px; color:#000;">We provide quick responses for all your queries.</p>
            </div>

            <!-- Item 3 -->
            <div class="why-choose-item" style="flex:1; min-width:200px; display:flex; flex-direction:column; align-items:center; gap:10px;">
                <div class="icon-box">
                    <img src="./assets/images/icon-why-choose-3.svg" alt="" style="width:50px;">
                </div>
                <h3 style="font-size:20px; margin-bottom:5px; color:#000;">Expert Medical Team</h3>
                <p style="font-size:16px; color:#000;">Our team includes highly experienced doctors ready to help.</p>
            </div>

        </div>
    </div>



        </div>
    </div>
    <!-- Intro Video Section End -->
    <!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<style>
/* Work Step Icon Styling */
.work-step-icon {
    width: 100px;
    height: 100px;
    background: #b7c237; /* leaf green background */
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    position: relative;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.work-step-icon i {
    font-size: 40px; /* Bigger icon size */
    color: #ffffff; /* White icon */
}

.work-step-no {
    position: absolute;
    bottom: -15px;
    right: -15px;
    background: #c8d439; /* yellow-green background */
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
                            <p>Join the Health Revolution. Get instant access to the AYKA Care community and your personalized dashboard. (Takes 60 Seconds!).</p>
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
                            <p>Use our smart-search to connect with the perfect, vetted expert for your needs—fast and frustration-free.</p>
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
                            <p>Secure your appointment slot instantly at a time that works with your life. Zero phone tag. Zero hassle.</p>
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
                            <p>Connect with your trusted doctor and START the journey to BETTER HEALTH OUTCOMES TODAY.</p>
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
                <?php
                $plans = [
                    [
                        'name' => 'Individual Plan',
                        'price' => '399',
                        'members' => '1',
                        'duration' => '90 Days',
                        'features' => [
                            'Unlimited consultations',
                            'Health records management',
                            'Appointment booking',
                            'Priority Support'
                        ]
                    ],
                    [
                        'name' => 'Family Plan Lite',
                        'price' => '599',
                        'members' => '2',
                        'duration' => '90 Days',
                        'features' => [
                            'Unlimited consultations for 2 family members',
                            'Shared health records & prescriptions',
                            'Appointment booking',
                            'Priority support'
                        ]
                    ],
                    [
                        'name' => 'Family Plan Elite',
                        'price' => '999',
                        'members' => '4',
                        'duration' => '6 months',
                        'features' => [
                            'Unlimited consultations for 4 family members',
                            'Shared health records & prescriptions',
                            'Appointment booking',
                            'Priority support'
                        ],
                        'popular' => true
                    ],
                    [
                        'name' => 'Senior Care Plan',
                        'price' => '249',
                        'members' => '1',
                        'duration' => '45 Days',
                        'features' => [
                            'Unlimited consultations',
                            'Appointment booking',
                            'Regular health check reminders',
                            'Priority doctor access',
                            'Prescription tracking'
                        ]
                    ]
                ];

                foreach ($plans as $plan): ?>
                    <!-- <div class="col-md-3 mb-4">
                        <div class="pricing-card <?= isset($plan['popular']) ? 'popular' : '' ?>">
                            <div class="pricing-header">
                                <?= $plan['name'] ?>
                            </div>
                            <div class="card-body text-center p-3">
                                <h2 class="pricing-price">₹<?= $plan['price'] ?></h2>
                                <p class="pricing-duration">/ <?= $plan['duration'] ?></p>
                                <ul class="pricing-features">
                                    <?php foreach ($plan['features'] as $feature): ?>
                                        <li><?= $feature ?></li>
                                    <?php endforeach; ?>
                                </ul> -->
                                <!-- <button class="btn-pricing" data-bs-toggle="modal" data-bs-target="#paymentModal"
                                    data-plan='<?= json_encode($plan) ?>'>
                                    Get Started
                                </button> -->
                                <!-- <button class="btn-pricing" data-bs-toggle="modal" data-bs-target="#paymentModal"
                                    data-plan='<?= json_encode($plan, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                    Get Started
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div> -->

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
                                <img src="./assets/images/icon-faq-cta.svg" alt="" style="filter: invert(46%) sepia(97%) saturate(346%) hue-rotate(90deg) brightness(93%) contrast(90%);">
                            </div>
                            <div class="faq-cta-content">
                                <p>We always take care of your smile</p>
                                <h3>24/7 Emergency</h3>
                                <p><a href="tel:9220782066">92207 82066</a></p>
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
                                    <p>AYKA Care - Connect with Top Medical Professionals offers a comprehensive range of medical services, including dental, gynecology, orthology, neurology, general medicine, dermatology, and cardiology. We also provide advanced lab testing and diagnostic services.</p>
                                </div>
                            </div>
                        </div>
                        <!-- FAQ Item End -->

                        <!-- FAQ Item Start -->
                        <div class="accordion-item wow fadeInUp" data-wow-delay="0.2s">
                            <h2 class="accordion-header" id="heading2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                                   What makes AYKA Care - Connect with Top Medical Professionals different from other healthcare providers?
                                </button>
                            </h2>
                            <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="heading2"
                                data-bs-parent="#faqaccordion">
                                <div class="accordion-body">
                                    <p>AYKA Care - Connect with Top Medical Professionals stands out due to its commitment to affordable healthcare, advanced medical technology, top-tier specialists, and 24-hour service. We also offer discounts on all medical treatments and ensure a swift enrollment process.</p>
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
                                    <p>You can easily book an Appointment through our website by navigating to the 'Book An Appointment' section. Simply select the service you need, choose a convenient time, and confirm your booking.</p>
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
                                    <p>Our laboratory offers a wide range of diagnostic tests, including Complete Blood Count (CBC), Hemoglobin (Hb) tests, X-rays, and CT scans. We provide timely and accurate results to support your healthcare needs.</p>
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
                                    <p>Yes, AYKA Care - Connect with Top Medical Professionals offers discounts on all medical treatments. For example, we provide a 5% discount on CBC and Hemoglobin tests, and a 10% discount on X-rays and CT scans.</p>
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
                                    <p>AYKA Care - Connect with Top Medical Professionals operates 24 hours a day, 7 days a week, ensuring that you receive the care you need whenever you need it.</p>
                                </div>
                            </div>
                        </div>
                        <!-- FAQ Item End -->
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
<a href="tel:+919220782066" 
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


<a href="https://wa.me/919220782066?text=Hi%20I%20need%20help" 
   style="position:fixed; bottom:20px; right:20px; background:#25D366; color:#fff; padding:12px 18px; border-radius:50px; display:flex; align-items:center; gap:10px; text-decoration:none; font-size:16px; font-weight:bold; box-shadow:0 4px 8px rgba(0,0,0,0.3); z-index:999;">
   <img src="https://cdn-icons-png.flaticon.com/512/733/733585.png" 
        alt="WhatsApp" style="width:24px; height:24px;">
   How can I help you?
</a>

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
                        <p class="wow fadeInUp" data-wow-delay="0.25s">AYKA Care doesn't just promise—we DELIVER. Our relentless pursuit of Excellence is proven by our Unmatched Success Metrics. See the proof: From Record-High Patient Happiness to Life-Changing Treatment Outcomes, our numbers tell your next health story.</p>
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
        </div>
    </div>
    <!-- Our Health Section End -->

 



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
                        <a href="https://www.youtube.com/@ayka_care"><i class="fab fa-youtube fa-lg"></i></a>
                    </div>

                    <!-- App Store Buttons -->
                    <div class="d-flex justify-content-center gap-2">
                        <a href="https://play.google.com/store/search?q=ayka%20care&c=apps"><img src="./assets/images/google-play.png" alt="Google Play" style="height:40px;"></a>
                        <a href="https://apps.apple.com/in/app/ayka-care/id6744580942"><img src="./assets/images/apple-store.png" alt="App Store" style="height:40px;"></a>
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
                        <li><a href="#">Contact Us</a></li>
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
    <!-- Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <form method="POST" class="modal-content border-0 shadow-lg">
                <div class="row g-0">
                    <!-- Left Side (Plan Details) -->
                    <div class="col-lg-5 p-5 bg-light">
                        <div class="d-flex align-items-center mb-4">
                            <img src="./assets/images/logo-2.png" alt="Atka Care Logo" class="me-2"
                                style="height:40px;">
                            <h5 class="fw-bold mb-0">AYKA CARE</h5>
                        </div>

                        <h4 class="fw-bold mb-3" id="planTitle">Plan Name</h4>
                        <ol class="mb-4 ps-3" id="planFeatures">
                            <li>Unlimited consultations</li>
                            <li>Shared health records & prescriptions</li>
                            <li>Appointment booking</li>
                        </ol>

                        <div class="mt-4">
                            <h6 class="fw-bold">Contact Us:</h6>
                            <p class="mb-1">
                                <i class="bi bi-envelope"></i> support@aykacare.in
                            </p>
                            <p>
                                <i class="bi bi-telephone"></i> 9220782066
                            </p>
                        </div>

                        <div class="mt-4">
                            <h6 class="fw-bold">Terms & Conditions:</h6>
                            <p class="small text-muted">
                                You agree to share information entered on this page with AYKA CARE
                                and Razorpay, adhering to applicable laws.
                            </p>
                            <a href="#" class="small text-decoration-none">Merchant’s business policies</a>
                        </div>
                    </div>

                    <!-- Right Side (Payment Form) -->
                    <div class="col-lg-7 p-5">
                        <h5 class="fw-bold mb-4">Payment Details</h5>
                        <div class="row g-3">

                            <!-- Hidden plan data -->
                            <input type="hidden" name="plan_name" id="planName">
                            <input type="hidden" name="amount" id="planAmount">
                            <input type="hidden" name="TotalPaybleAmount" id="TotalPaybleAmount">
                            <input type="hidden" name="duration" id="planDuration">
                            <input type="text" name="members" id="members">
                            <input type="hidden" name="startdate" id="startdate">
                            <input type="hidden" name="enddate" id="enddate">

                            <div class="col-12">
                                <label class="form-label">Amount</label>
                                <input type="text" id="amountDisplay" class="form-control" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" placeholder="First Name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="L_name" class="form-control" placeholder="Last Name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" placeholder="Mobile Number"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-control" required>
                                    <option value="">Select</option>
                                    <option>Male</option>
                                    <option>Female</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" placeholder="City" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">GST</label>
                                <input type="text" id="gstAmount" class="form-control" readonly>
                            </div>
                        </div>

                        <!-- Payment Button -->
                        <div class="d-flex justify-content-between align-items-center mt-4 border-top pt-3">
                            <div>
                                <img src="./assets/images/pay_methods_branding.png" alt="UPI"
                                    style="width: 80%; height: auto;">
                            </div>
                            <button class="btn btn-success px-5" type="submit">
                                Pay <span id="totalAmount">₹0.00</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Download App Modal -->
    <div id="appModal" class="custom-modal">
        <div class="custom-modal-content">
            <span class="close-btn">&times;</span>
            <h3>📲 Download Our App</h3>
            <p>For the best experience, download our app today!</p>
            <div class="app-buttons">
                <a href="https://aykacare.in/downloadmyapp.php"> Download Now</a>
            </div>
        </div>
    </div>

    <!-- Consultation Modal -->
<div class="modal fade" id="consultationModal" tabindex="-1" aria-labelledby="consultationLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow-lg border-0 position-relative overflow-hidden">

            <!-- Gradient Background Overlay -->
            <div style="
                position: absolute;
                top: 0; left: 0;
                width: 100%; height: 100%;
                background: linear-gradient(135deg, #84fab0, #8fd3f4);
                opacity: 0.3;
                z-index: 0;
                filter: blur(15px);
            "></div>

            <!-- Modal Header -->
            <div class="modal-header border-0 flex-column align-items-center position-relative z-index-1">
                <h5 class="modal-title text-success fw-bold fs-3" id="consultationLabel">
                    🎉 First Consultation 100% Free!
                </h5>
                <p class="text-center text-dark mb-0 fw-semibold">Join thousands of happy patients who trust AYKA Care</p>
            </div>

            <!-- Modal Body -->
            <div class="modal-body text-center px-4 position-relative z-index-1">
                <p class="fs-5 fw-semibold">Get expert advice from <b>2500+ doctors</b> — at zero cost!</p>

                <!-- Feature List with Icons -->
                <ul class="list-unstyled text-start mx-auto" style="max-width: 300px;">
                    <li class="mb-2"><span class="text-success fw-bold me-2">✔</span> No Fees</li>
                    <li class="mb-2"><span class="text-success fw-bold me-2">✔</span> No Hassle</li>
                    <li class="mb-2"><span class="text-success fw-bold me-2">✔</span> Just Quality Care</li>
                </ul>
            </div>

            <!-- Modal Footer / CTA -->
            <div class="modal-footer border-0 justify-content-center position-relative z-index-1">
                <a href="https://aykacare.in/downloadmyapp.php" 
                   class="btn btn-success btn-lg rounded-pill px-5 fw-bold shadow-lg"
                   style="background: linear-gradient(45deg,#28a745,#84fab0); box-shadow: 0 8px 15px rgba(0,0,0,0.3);">
                   Book Now – Limited Offer
                </a>
            </div>

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
                alert(plan.members);
                // return
                // Fill hidden inputs
                $('#planName').val(plan.name);
                $('#planAmount').val(plan.price);  // <-- FIX
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
                $('#planAmount').val(plan.price);                 // base price only
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
        

      document.addEventListener("DOMContentLoaded", function() {
        const modal = document.getElementById("appModal");
        const closeBtn = document.querySelector(".close-btn");

        // Attach click listener to all cards
        document.querySelectorAll('.specialist-card').forEach(card => {
            card.addEventListener('click', () => {
                modal.style.display = "flex"; // open modal
            });
        });

        // Close modal on X button
        closeBtn.addEventListener("click", function() {
            modal.style.display = "none";
        });

        // Close modal when clicking outside
        window.addEventListener("click", function(e) {
            if (e.target === modal) {
                modal.style.display = "none";
            }
        });
    });

    document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById("appModal");
    const closeBtn = document.querySelector(".close-btn");

    // Attach click listener to all slider items
    document.querySelectorAll('.slide').forEach(slide => {
        slide.addEventListener('click', () => {
            modal.style.display = "flex"; // open the same modal
        });
    });

    // Close modal on X button
    closeBtn.addEventListener("click", function() {
        modal.style.display = "none";
    });

    // Close modal when clicking outside
    window.addEventListener("click", function(e) {
        if (e.target === modal) {
            modal.style.display = "none";
        }
    });
});

  window.addEventListener('load', function() {
    var consultationModal = new bootstrap.Modal(document.getElementById('consultationModal'));
    consultationModal.show();
  });



    </script>
</body>

</html>