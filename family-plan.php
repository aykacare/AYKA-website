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
$conn = new mysqli('localhost', 'u685993406_newaykadb', '8DwLlBGb!', 'u685993406_newaykadb');
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
    $totalRupees = floatval($form['TotalPaybleAmount']);         
    $amountInPaise = (int) round($totalRupees * 100); 

    $order = $api->order->create([
        'receipt' => 'rcpt_' . time(),
        'amount' => $amountInPaise,
        'currency' => 'INR',
        'payment_capture' => $form['TotalPaybleAmount'] == 1 ? 0 : 1 // Auto-capture for real payments
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
    
    
    <!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '3328042534011710');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=3328042534011710&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
    
    
    
    <!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-MHXMM7BJ');</script>
<!-- End Google Tag Manager -->
    
    
    
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
    .page-header{
    	padding: 125px 0 0 0;
    }
</style>

<body>
    
    <!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MHXMM7BJ"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
    
    <!-- Preloader Start -->
    <div class="preloader">
        <div class="loading-container">
            <div class="loading"></div>
            <div id="loading-icon"><img src="./assets/images/loader.png" alt=""></div>
        </div>
    </div>


<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MHXMM7BJ"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->



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
                                <li class="nav-item"><a class="nav-link" href="./about-us.php">About Us</a></li>
                                <li class="nav-item"><a class="nav-link" href="https://aykacare.in/#services">Specialists</a></li>
                                <li class="nav-item submenu"><a class="nav-link" href="">Membership Plans</a>
                                    <ul>
                                        <li class="nav-item"><a class="nav-link"  href="./individual-plan.php">Individual Plan</a></li>
                                        <li class="nav-item"><a class="nav-link" href="./family-plan.php">Family Plan Lite/Elite</a></li>
                                         <!-- <li class="nav-item"><a class="nav-link" href="">Family Plan Elite</a></li>  -->
                                        <li class="nav-item"><a class="nav-link"  href="./membership-plan.php">Senior Care Plan</a></li>
                                    </ul>
                                </li>

                                <li class="nav-item"><a class="nav-link" href="https://expert.aykacare.in/?fluent-form=4">Contact Us</a></li>
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

    <div class="page-header bg-section">
        <div class="container">
             <div class="discount-banner text-center p-4 rounded-4 position-relative overflow-hidden" 
             style="background: black; 
                    color: white; 
                    box-shadow: 0 8px 22px rgba(0,0,0,0.25); 
                    transition: transform 0.3s ease;">

            <!-- Ribbon -->
            <div style="
                position: absolute; 
                top: 4px; 
                right: -10px; 
                background: #fff; 
                color: #FF6F61; 
                padding: 7px 20px; 
                font-weight: 700; 
                transform: rotate(20deg); 
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                font-size: 1rem;">
                upto 20% OFF
            </div>

            <!-- Content -->
            <h2 class="mb-2 fw-bold" style="font-size: 2rem; letter-spacing: 0.5px; font-family:cursive">Limited Time Membership Offer!</h2>
            <p class="mb-3 fs-6">Sign up now and enjoy <strong>upto 20% OFF</strong> on our Premium Membership Plan.</p>
             <a href="https://rzp.io/rzp/WZTCtRcm" 
               class="btn btn-light btn-lg fw-bold rounded-pill shadow-sm"
               style="color:#4CAF50; transition: transform 0.2s; font-size: 15px;">
               Join Now
            </a>
        </div>
            <div class="row">
                <div class="col-lg-12">
                    <!-- Page Header Box Start -->
                    <div class="page-header-box">
                        <h1 class="text-anime-style-3" data-cursor="-opaque" style="perspective: 400px;">
                            <!-- <div class="split-line" style="display: block; text-align: center; position: relative;">
                                <div style="position:relative;display:inline-block;">
                                    <div
                                        style="position: relative; display: inline-block; transform: translate(0px, 0px); opacity: 1;">
                                        M</div>
                                    <div
                                        style="position: relative; display: inline-block; transform: translate(0px, 0px); opacity: 1;">
                                        E</div>
                                    <div
                                        style="position: relative; display: inline-block; transform: translate(0px, 0px); opacity: 1;">
                                        M</div>
                                    <div
                                        style="position: relative; display: inline-block; transform: translate(0px, 0px); opacity: 1;">
                                        B</div>
                                </div>
                                <div style="position:relative;display:inline-block;">
                                    <div
                                        style="position: relative; display: inline-block; transform: translate(0px, 0px); opacity: 1;">
                                        E</div>
                                    <div
                                        style="position: relative; display: inline-block; transform: translate(0px, 0px); opacity: 1;">
                                        R</div>
                                    <div
                                        style="position: relative; display: inline-block; transform: translate(0px, 0px); opacity: 1;">
                                        S</div>
                                    <div
                                        style="position: relative; display: inline-block; transform: translate(0px, 0px); opacity: 1;">
                                        H</div>
                                    <div
                                        style="position: relative; display: inline-block; transform: translate(0px, 0px); opacity: 1;">
                                        I</div>
                                    <div
                                        style="position: relative; display: inline-block; transform: translate(0px, 0px); opacity: 1;">
                                        P</div>
                                </div>
                            </div> -->
                        </h1>
                        <!--<nav class="wow fadeInUp" data-wow-delay="0.25s"-->
                        <!--    style="visibility: visible; animation-delay: 0.25s; animation-name: fadeInUp;">-->
                        <!--    <ol class="breadcrumb">-->
                                <!-- <li class="breadcrumb-item"><a href="index-2.html">home</a></li> -->
                        <!--        <li class="breadcrumb-item active" aria-current="page">Membership Plan</li>-->
                        <!--    </ol>-->
                        <!--</nav>-->
                    </div>
                    <!-- Page Header Box End -->
                </div>
            </div>
        </div>
    </div>

   
    <!-- Individual Plan Section Start -->
<!-- Family Plan Lite Section Start -->
<section class="family-plan py-5">
  <div class="container">
    <div class="row align-items-center">

      <!-- Left Side (Plan Details) -->
      <div class="col-lg-6 mb-4 mb-lg-0">
        <div class="plan-left p-4 h-100" style="background:#ffffff; border:1px solid #eff0ef; border-radius:8px;">
          <h4 class="fw-bold" style="color:#b7c237;">Family Plan Lite</h4>
          <h5 class="mb-3">
            Affordable Care for Your Family at
            <strong style="color:#b0ba34;">₹899 / 45 Days</strong>
          </h5>

          <!-- Discounted Price -->
          <!--<h6 class="mb-3">-->
          <!--  <span style="color:#6c757d; text-decoration: line-through;">₹899</span>-->
          <!--  <span style="color:#28a745; font-weight:600;"> ₹799 / 45 Days (11% Off)</span>-->
          <!--</h6>-->

          <p style="color:#0a0a0a;">
            Designed for families who want quality healthcare for 2 members with shared benefits.
          </p>

          <img src="./assets/images/family2.png" class="img-fluid mt-3 rounded" alt="Family Plan Elite">
        </div>
      </div>

      <!-- Right Side (Features + Button) -->
      <div class="col-lg-6">
        <div class="plan-right p-4 h-100" style="background:#ffffff; border:1px solid #eff0ef; border-radius:8px;">

          <!-- Price & Duration -->

          
           <h3 class="fw-bold" style="color:#b7c237;">₹899 <span style="font-size:18px;">/ 45 Days</span> 
                        <span style="color:#c8d439; font-size:16px;">(Best Value)</span>
                    </h3>
                    <p style="color:#090a0a;">Exclusive of all taxes</p>

                    <div class="plan-inclusions rounded p-3" style="border:1px solid #b7c237;">
            <h6 class="fw-bold" style="color:#b7c237;">Features</h6>
            <ul class="mb-2" style="color:#0e0e0e;">
              <li><strong>Doctor Consultations:</strong> Unlimited consultations for 2 family members</li>
              <li><strong>Health Records:</strong> Shared health records & prescriptions for 2 members</li>
              <li><strong>Appointment Booking:</strong> Standard booking access</li>
              <li><strong>Priority Support:</strong> Dedicated priority support for both members</li>
            </ul>
            <small style="color:#c8d439;">Family wellness made simple.</small>
          </div>

          <p style="color:#090a0a; margin-top: 10px;">* Terms and Conditions Apply</p>

          <hr style="border-top: 1px solid #ddd;">

          <!-- Buttons -->
          <div class="d-flex gap-3 my-3">
            <!--<a href="https://wa.me/919220782066?text=Hi%20I%20need%20help"-->
            <!--  class="btn px-4"-->
            <!--  style="border:1px solid #0a0a0a; color:#0a0a0a;">-->
            <!--  Request A Callback-->
            <!--</a>-->
            <a href="#" 
                class="btn px-5 py-3 fs-5"
                style="background:#c8d439; color:#ffffff; width:350px;"
                data-bs-toggle="modal"
                data-bs-target="#paymentModal"
                data-plan='{
                    "name": "Family Plan",
                    "price": 899,
                    "members": "2",
                    "duration": "45 Days",
                    "features": ["Unlimited consultations for 2 family members", "Shared health records & prescriptions for 2 members", "Standard booking access", "Dedicated priority support for both members"]
                }'>
                Get Started
            </a>
          </div>

        </div>
      </div>

    </div>
  </div>
</section>

<!-- Family Plan Lite Section End -->
<!-- Family Plan Elite Section Start -->
<section class="family-plan-elite py-5">
    <div class="container">
        <div class="row align-items-center">

            <!-- Left Side (Plan Details) -->
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="plan-left p-4 h-100"
                    style="background:#ffffff; border:1px solid #eff0ef; border-radius:8px;">
                    <h4 class="fw-bold" style="color:#b7c237;">Family Plan Elite</h4>
                    <h5 class="mb-3">
                        Best Value Plan at 
                        <strong style="color:#b0ba34;">₹1299 / 45 Days</strong>
                    </h5>

                    <!-- Discounted Price -->
                    <!--<h6 class="mb-3">-->
                    <!--    <span style="color:#6c757d; text-decoration: line-through;">₹1299</span>-->
                    <!--    <span style="color:#28a745; font-weight:600;"> ₹1049 / 45 Days (19% Off)</span>-->
                    <!--</h6>-->

                    <p style="color:#0a0a0a;">Comprehensive family care designed for up to 4 members with premium support and shared records.</p>
                    <img src="./assets/images/family.png" class="img-fluid mt-3 rounded" alt="Family Plan Elite">
                </div>
            </div>



            <!-- Right Side (Features + Button) -->
            <div class="col-lg-6">
                <div class="plan-right p-4 h-100"
                    style="background:#ffffff; border:1px solid #eff0ef; border-radius:8px;">
                    
                    <!-- Price & Duration -->
             <h3 class="fw-bold" style="color:#b7c237;">₹1299 <span style="font-size:18px;">/ 45 Days</span> 
                        <span style="color:#c8d439; font-size:16px;">(Best Value)</span>
                    </h3>
                    <p style="color:#090a0a;">Exclusive of all taxes</p>

                                        <div class="plan-inclusions rounded p-3" style="border:1px solid #b7c237;">
                        <h6 class="fw-bold" style="color:#b7c237;">Features</h6>
                        <ul class="mb-2" style="color:#0e0e0e;">
                            <li><strong>Doctor Consultations:</strong> Unlimited consultations for up to 4 family members</li>
                            <li><strong>Health Records:</strong> Shared health records & prescriptions for 4 members</li>
                            <li><strong>Appointment Booking:</strong> Standard booking access</li>
                            <li><strong>Priority Support:</strong> VIP priority support for all 4 members</li>
                        </ul>
                        <small style="color:#c8d439;">Maximum value for complete family care.</small>
                    </div>

                    <p style="color:#090a0a; margin-top: 10px;">* Terms and Conditions Apply</p>

                    <hr style="border-top: 1px solid #ddd;">
                    <!-- Buttons -->
                    <div class="d-flex gap-3 my-3">
                        <!--<a href="https://wa.me/919220782066?text=Hi%20I%20need%20help" class="btn px-4"
                        style="border:1px solid #0a0a0a; color:#0a0a0a;">Request A Callback</a>-->
                        <!--<a href="#" -->
                        <!--    class="btn px-4" style="background:#c8d439; color:#ffffff;"-->
                        <!--    data-bs-toggle="modal"-->
                        <!--    data-bs-target="#paymentModal"-->
                        <!--    data-plan='{-->
                        <!--        "name": "Family Plan",-->
                        <!--        "price": 1049,-->
                        <!--        "members": "4",-->
                        <!--        "duration": "45 Days",-->
                        <!--        "features": ["Unlimited consultations for family up to 4 members", "Shared records", "Appointment booking", "Priority Support"]-->
                        <!--    }'>-->
                        <!--    Get Started-->
                        <!--</a>-->
                        
                        <a href="#" 
                class="btn px-5 py-3 fs-5"
                style="background:#c8d439; color:#ffffff; width:350px;"
                data-bs-toggle="modal"
                data-bs-target="#paymentModal"
                data-plan='{
                                "name": "Family Plan",
                                "price": 1299,
                                "members": "4",
                                "duration": "45 Days",
                                "features": ["Unlimited consultations for family up to 4 members", "Shared records", "Appointment booking", "Priority Support"]
                            }'>
                Get Started
            </a>
                        
                        
                    </div>
                </div>
            </div>
            
            
            
            
          
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
                              
                                
                                <!-- subscriptions FAQ started -->
                                
                                <!-- FAQ Item Start -->
                                <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                                    <h2 class="accordion-header" id="heading6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                            Can I consult any specialist under Individual plan?
                                        </button>
                                    </h2>
                                    <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                        data-bs-parent="#faqaccordion">
                                        <div class="accordion-body">
                                            <p>Yes, you can consult with any general or specialist doctor available on AYKA Care.</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- FAQ Item End -->
                                
                                <!-- FAQ Item Start -->
                                <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                                    <h2 class="accordion-header" id="heading6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                            ⁠Do unused consultations carry over after 45 days?
                                        </button>
                                    </h2>
                                    <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                        data-bs-parent="#faqaccordion">
                                        <div class="accordion-body">
                                            <p>No, the plan expires automatically after 45 days and unused consultations do not carry forward.</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- FAQ Item End -->
                                
                                <!-- FAQ Item Start -->
                                <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                                    <h2 class="accordion-header" id="heading6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                             ⁠Is there a waiting period before I can start using the plan?
                                        </button>
                                    </h2>
                                    <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                        data-bs-parent="#faqaccordion">
                                        <div class="accordion-body">
                                            <p>No waiting period. You can start using all features immediately after activation.</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- FAQ Item End -->
                                
                                <!-- FAQ Item Start -->
                                <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                                    <h2 class="accordion-header" id="heading6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                            How many consultations are included in Individual plan?
                                        </button>
                                    </h2>
                                    <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                        data-bs-parent="#faqaccordion">
                                        <div class="accordion-body">
                                            <p>You get 11 free consultations per plan cycle.</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- FAQ Item End -->
                                
                                <!-- FAQ Item Start -->
                                <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                                    <h2 class="accordion-header" id="heading6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                            ⁠Can I upgrade this plan to a Family Plan later?
                                        </button>
                                    </h2>
                                    <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                        data-bs-parent="#faqaccordion">
                                        <div class="accordion-body">
                                            <p>Yes, you can upgrade anytime by paying the plan difference amount.</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- FAQ Item End -->
                                
                                    <!-- FAQ Item Start -->
                                <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                                    <h2 class="accordion-header" id="heading6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                            ⁠Are medicines and lab test discounts included in all consultations?
                                        </button>
                                    </h2>
                                    <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                        data-bs-parent="#faqaccordion">
                                        <div class="accordion-body">
                                            <p>Yes, you get exclusive partner discounts on all prescribed medicines and lab tests throughout the plan duration.</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- FAQ Item End -->
                                
                                
                                    <!-- FAQ Item Start -->
                                <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                                    <h2 class="accordion-header" id="heading6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                            ⁠⁠Can I add more than 2 members under Family plan?
                                        </button>
                                    </h2>
                                    <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                        data-bs-parent="#faqaccordion">
                                        <div class="accordion-body">
                                            <p>No, this plan supports only 2 members. For 3 or more, you can opt for the Family Plan Elite.</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- FAQ Item End -->
                                
                                    <!-- FAQ Item Start -->
                                <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                                    <h2 class="accordion-header" id="heading6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                            ⁠⁠Do both members need separate logins?
                                        </button>
                                    </h2>
                                    <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                        data-bs-parent="#faqaccordion">
                                        <div class="accordion-body">
                                            <p>No, both members can access consultations under a single registered account.</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- FAQ Item End -->
                                
                                
                                    <!-- FAQ Item Start -->
                                <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                                    <h2 class="accordion-header" id="heading6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                            ⁠⁠What happens if one member doesn’t use their consultations?
                                        </button>
                                    </h2>
                                    <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                        data-bs-parent="#faqaccordion">
                                        <div class="accordion-body">
                                            <p>Consultations are shared, so either member can use the remaining consultations.</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- FAQ Item End -->
                                
                                
                                    <!-- FAQ Item Start -->
                                <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                                    <h2 class="accordion-header" id="heading6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                            ⁠How many consultations are included in Family plan?
                                        </button>
                                    </h2>
                                    <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                        data-bs-parent="#faqaccordion">
                                        <div class="accordion-body">
                                            <p>This plan offers 15 free consultations per cycle.</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- FAQ Item End -->
                                
                                
                                    <!-- FAQ Item Start -->
                                <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                                    <h2 class="accordion-header" id="heading6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                            ⁠Can this plan be renewed automatically?
                                        </button>
                                    </h2>
                                    <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                        data-bs-parent="#faqaccordion">
                                        <div class="accordion-body">
                                            <p>Yes, you can enable auto-renewal in your account settings before plan expiry.</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- FAQ Item End -->
                                
                                
                                    <!-- FAQ Item Start -->
                                <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                                    <h2 class="accordion-header" id="heading6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                            ⁠⁠Are children included in this plan?
                                        </button>
                                    </h2>
                                    <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                        data-bs-parent="#faqaccordion">
                                        <div class="accordion-body">
                                            <p>Yes, family members can include adults or children. Consultations for minors are allowed with guardian consent.</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- FAQ Item End -->
                                
                                   <!-- FAQ Item Start -->
                                <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                                    <h2 class="accordion-header" id="heading6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                            ⁠Can I choose different specialists for each family member?
                                        </button>
                                    </h2>
                                    <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                        data-bs-parent="#faqaccordion">
                                        <div class="accordion-body">
                                            <p>Yes, each member can consult different doctors based on their individual health needs.</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- FAQ Item End -->
                                
                                   <!-- FAQ Item Start -->
                                <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                                    <h2 class="accordion-header" id="heading6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                            ⁠⁠Are lab test and medicine discounts shared among members?
                                        </button>
                                    </h2>
                                    <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                        data-bs-parent="#faqaccordion">
                                        <div class="accordion-body">
                                            <p>Yes, all family members get access to the same exclusive partner discounts.</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- FAQ Item End -->
                                
                                   <!-- FAQ Item Start -->
                                <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                                    <h2 class="accordion-header" id="heading6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                            ⁠Can I replace a family member mid-plan?
                                        </button>
                                    </h2>
                                    <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                        data-bs-parent="#faqaccordion">
                                        <div class="accordion-body">
                                            <p>No, members added at the start of the plan cannot be replaced during the active cycle.</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- FAQ Item End -->
                                
                                   <!-- FAQ Item Start -->
                                <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                                    <h2 class="accordion-header" id="heading6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                            ⁠Do all members get the same priority support?
                                        </button>
                                    </h2>
                                    <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                        data-bs-parent="#faqaccordion">
                                        <div class="accordion-body">
                                            <p>Yes, VIP priority support applies equally to all registered members.</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- FAQ Item End -->
                                
                                   <!-- FAQ Item Start -->
                                <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                                    <h2 class="accordion-header" id="heading6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                            ⁠How many consultations are included in Family Elite plan?
                                        </button>
                                    </h2>
                                    <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                        data-bs-parent="#faqaccordion">
                                        <div class="accordion-body">
                                            <p>This plan includes 18 free consultations per cycle.</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- FAQ Item End -->
                                
                                   <!-- FAQ Item Start -->
                                <div class="accordion-item wow fadeInUp" data-wow-delay="1s">
                                    <h2 class="accordion-header" id="heading6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                            ⁠Can Family Elite plan be extended for more than 4 members?
                                        </button>
                                    </h2>
                                    <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6"
                                        data-bs-parent="#faqaccordion">
                                        <div class="accordion-body">
                                            <p>No, this plan is fixed for 4 members. For larger families, separate plans can be purchased.</p>
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
            
            
            
            
            
            
            
            
            
            
            
            

        </div>
    </div>
</section>
<!-- Family Plan Elite Section End -->

<!-- Individual Plan Section End -->


    <!-- Dental Health Plan Section End -->

    <!-- Page Case Study End -->



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
        .newyoutube{
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
                        <a href="#"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#"><i class="fab fa-linkedin fa-lg"></i></a>
                        <a href="#"><i class="fab fa-youtube newyoutube fa-lg"></i></a>
                    </div>

                    <!-- App Store Buttons -->
                    <div class="d-flex justify-content-center gap-2">
                        <a href="#"><img src="assets/images/google-play.png" alt="Google Play" style="height:40px;"></a>
                        <a href="#"><img src="assets/images/apple-store.png" alt="App Store" style="height:40px;"></a>
                    </div>
                </div>

                <!-- AYKA Care Links -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="fw-bold mb-3">AYKA Care</h5>
                    <ul class="list-unstyled">
                        <li><a href="./about-us.php">About us</a></li>
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
                        <input type="hidden" name="members" id="members">
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

    <!-- Jquery Library File -->
    <script src="./assets/js/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap js file -->
    <script src="./assets/js/bootstrap.min.js"></script>
    <!-- Validator js file -->
    <script src="./assets/js/validator.min.js"></script>
    <!-- SlickNav js file -->
    <script src="./assets/js/jquery.slicknav.js"></script>
    <!-- Swiper js file -->
    <!-- Isotop js file -->
    <script src="./assets/js/isotope.min.js"></script>
    <!-- Magnific js file -->
    <script src="./assets/js/jquery.magnific-popup.min.js"></script>
    <!-- SmoothScroll -->
    <script src="./assets/js/SmoothScroll.js"></script>

    <!-- Main Custom js file -->
    <script src="./assets/js/function.js"></script>
    
    <script>
        $(document).ready(function () {
            $('#paymentModal').on('show.bs.modal', function (event) {
                let button = $(event.relatedTarget);
                let plan = JSON.parse(button.attr('data-plan'));
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
    </script>
</body>

</html>