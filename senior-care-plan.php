<!DOCTYPE html>
<html lang="zxx">

<head>
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
                                <li><a href="#"><i class="fa-brands fa-instagram"></i></a></li>
                                <li><a href="#"><i class="fa-brands fa-facebook-f"></i></a></li>
                                <li><a href="#"><i class="fa-brands fa-dribbble"></i></a></li>
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
                                <li class="nav-item"><a class="nav-link" href="#services">Specialists</a></li>
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
                        <nav class="wow fadeInUp" data-wow-delay="0.25s"
                            style="visibility: visible; animation-delay: 0.25s; animation-name: fadeInUp;">
                            <ol class="breadcrumb">
                                <!-- <li class="breadcrumb-item"><a href="index-2.html">home</a></li> -->
                                <li class="breadcrumb-item active" aria-current="page">Membership Plan</li>
                            </ol>
                        </nav>
                    </div>
                    <!-- Page Header Box End -->
                </div>
            </div>
        </div>
    </div>

   
    <!-- Individual Plan Section Start -->
<!-- Senior Care Plan Section Start -->
<section class="senior-care-plan py-5">
    <div class="container">
        <div class="row align-items-center">

            <!-- Left Side (Plan Details) -->
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="plan-left p-4 h-100"
                    style="background:#ffffff; border:1px solid #eff0ef; border-radius:8px;">
                    <h4 class="fw-bold" style="color:#b7c237;">Senior Care Plan</h4>
                    <h5 class="mb-3">Comprehensive Senior Care at 
                        <strong style="color:#b0ba34;">₹649 / 45 Days</strong>
                    </h5>
                    <p style="color:#0a0a0a;">Tailored healthcare for seniors with priority support and personalized tracking.</p>
                 
                </div>
            </div>

            <!-- Right Side (Features + Button) -->
            <div class="col-lg-6">
                <div class="plan-right p-4 h-100"
                    style="background:#ffffff; border:1px solid #eff0ef; border-radius:8px;">
                    
                    <!-- Price & Duration -->
                    <h3 class="fw-bold" style="color:#b7c237;">₹649 <span style="font-size:18px;">/ 45 Days</span></h3>
                    <p style="color:#090a0a;">Inclusive of all taxes</p>

                    <!-- Features -->
                    <div class="plan-inclusions rounded p-3" style="border:1px solid #b7c237;">
                        <h6 class="fw-bold" style="color:#b7c237;">Features</h6>
                        <ul class="mb-2" style="color:#0e0e0e;">
                            <li><strong>Doctor Consultations:</strong> Unlimited consultations with priority access</li>
                            <li><strong>Prescription Tracking:</strong> Dedicated prescription tracking for seniors</li>
                            <li><strong>Appointment Booking:</strong> Instant appointment booking</li>
                            <li><strong>Support & Reminders:</strong> Senior-focused support & reminders</li>
                            <li><strong>Health Check Reminders:</strong> Regular health check notifications for ongoing care</li>
                        </ul>
                        <small style="color:#c8d439;">Ensuring golden age healthcare with ease.</small>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex gap-3 my-3">
                        <a href="https://wa.me/919220782066?text=Hi%20I%20need%20help"  class="btn px-4" style="border:1px solid #0a0a0a; color:#0a0a0a;">Request A Callback</a>
                        <a href="https://rzp.io/rzp/mjVqA0Na" class="btn px-4" style="background:#c8d439; color:#ffffff;">Get Started – Golden Age Care</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
<!-- Senior Care Plan Section End -->

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
                        <a href="#"><i class="fab fa-youtube fa-lg"></i></a>
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
                        <li><a href="#about">About us</a></li>
                        <li><a href="#">Homecare</a></li>
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
                        <li><a href="#">Privacy Policy</a></li>
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
</body>

</html>