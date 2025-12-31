<?php
session_start();
require 'db.php';

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $mobile = trim($_POST['mobile'] ?? '');
  $message = trim($_POST['message'] ?? '');

  if ($name && $email && $message && filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $stmt = $conn->prepare(
      "INSERT INTO contact_messages (name, email, mobile, message) VALUES (?, ?, ?, ?)"
    );

    if ($stmt) {
      $stmt->bind_param("ssss", $name, $email, $mobile, $message);

      if ($stmt->execute()) {
        $_SESSION['success'] = "Your message has been sent successfully!";
      } else {
        $_SESSION['error'] = "Something went wrong. Please try again.";
      }

      $stmt->close();
    } else {
      $_SESSION['error'] = "Database error. Please try later.";
    }

  } else {
    $_SESSION['error'] = "Please fill in all fields correctly.";
  }

  header("Location: contact-us.php");
  exit;
}

// SHOW MESSAGE AFTER REDIRECT
if (isset($_SESSION['success'])) {
  $successMessage = $_SESSION['success'];
  unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
  $errorMessage = $_SESSION['error'];
  unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="zxx">

<head>

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
  <link href="./assets/css/custom.css?v=2.8" rel="stylesheet" media="screen">
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

  .hero {
    background: linear-gradient(90deg, var(--accent-color) 0%, var(--accent-color-two) 100%);
    height: 45vh;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-align: center;
  }

  .hero h1 {
    color: black;
  }

  .hero::after {
    content: "";
    position: absolute;
    inset: 0;
  }

  .hero-content {
    position: relative;
    z-index: 1;
  }

  .hero-content h1 {
    font-size: 50px;
    margin-bottom: 10px;
  }

  .hero-content p {
    font-size: 18px;
    color: black;
  }

  /* Contact Section */
  .contact-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    padding: 60px 10%;
    align-items: center;
  }

  .info-boxes-wrapper {
    display: flex;
    flex-direction: column;
    gap: 20px;
    justify-content: center;
    height: 100%;
  }

  .info-box {
    background: #fff;
    padding: 25px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    transition: 0.3s;
  }

  .info-box:hover {
    transform: translateY(-5px);
  }

  .info-box i {
    font-size: 35px;
    background: black;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 15px;
  }

  .info-box h3 {
    margin-bottom: 10px;
    font-size: 20px;
  }

  .contact-form {
    background: #fff;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  }

  .contact-form h3 {
    margin-bottom: 20px;
    font-size: 22px;
  }

  .contact-form input,
  .contact-form textarea {
    width: 100%;
    padding: 12px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 15px;
  }

  .contact-form button {
    background: linear-gradient(90deg, var(--accent-color) 0%, var(--accent-color-two) 100%);
    color: white;
    border: none;
    padding: 14px;
    width: 100%;
    font-size: 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
  }

  .contact-form button:hover {
    background: linear-gradient(90deg, #0f5132 0%, #064420 100%);
    /* dark green shades */
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.25);
  }

  .alert {
    padding: 12px 15px;
    margin-bottom: 15px;
    border-radius: 8px;
    font-weight: 500;
  }

  .alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
  }

  .alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
  }

  /* Map */
  .map-container {
    margin: 60px 10%;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  }


  @media(max-width: 900px) {
    .contact-container {
      grid-template-columns: 1fr;
      align-items: stretch;
    }

    .info-boxes-wrapper {
      flex-direction: row;
      gap: 20px;
    }

    .hero-content h1 {
      font-size: 30px;
    }
  }

  .number-color {
    color: black;
  }
</style>

<body>
  <!-- Google Tag Manager (noscript) -->
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MHXMM7BJ" height="0" width="0"
      style="display:none;visibility:hidden"></iframe></noscript>
  <!-- End Google Tag Manager (noscript) -->

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
                <li><a href="https://www.instagram.com/ayka.care/"><i class="fa-brands fa-instagram"></i></a></li>
                <li><a href="https://www.facebook.com/ayka.care"><i class="fa-brands fa-facebook-f"></i></a></li>
                <a href="https://www.youtube.com/@ayka_care"><i class="fab fa-youtube fa-lg"></i></a>
              </ul>
            </div>
            <!-- Header Social Icons End -->

            <!-- Topbar Contact Details Start -->
            <div class="topbar-contact-info topbar-contact-details">
              <ul>
                <li><a href="tele" class="number-color"><span>Contact:</span> +91 92207 82066</a></li>
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
                    <li class="nav-item"><a class="nav-link" href="./individual-plan.php">Individual Plan</a></li>
                    <li class="nav-item"><a class="nav-link" href="./family-plan.php">Family Plan Lite/Elite</a></li>
                    <!-- <li class="nav-item"><a class="nav-link" href="">Family Plan Elite</a></li>  -->
                    <li class="nav-item"><a class="nav-link" href="./membership-plan.php">Senior Care Plan</a></li>
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

  <section class="hero">
    <div class="hero-content">
      <h1>Get In Touch</h1>
      <p>We're here to assist you 24/7</p>
    </div>
  </section>

  <!-- Contact Info + Form -->
  <section class="contact-container">
    <!-- Wrapped info boxes in a flex container to center them vertically -->
    <div class="info-boxes-wrapper">
      <div class="info-box">
        <i class="fa-solid fa-phone"></i>
        <h3>Call Us</h3>
        <p><a href="tel:+919220782066" class="number-color">+91 92207 82066</a></p>
      </div>
      <div class="info-box">
        <i class="fa-solid fa-envelope"></i>
        <h3>Email</h3>
        <p><a href="mailto:hello@aykacare.in" class="number-color">hello@aykacare.in</a></p>
      </div>
    </div>

    <!-- Contact Form -->
    <div class="contact-form">
      <h3>Send us a Message</h3>
      <?php if ($successMessage): ?>
        <div class="alert alert-success">
          <?= htmlspecialchars($successMessage) ?>
        </div>
      <?php endif; ?>
      <?php if ($errorMessage): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($errorMessage) ?>
        </div>
      <?php endif; ?>
      <form method="POST" action="contact-us.php">
        <input type="text" name="name" placeholder="Your Name" required>
        <input type="email" name="email" placeholder="Your Email" required>
        <input type="tel" name="mobile" placeholder="Your Mobile No" required>
        <textarea name="message" rows="5" placeholder="Your Comment" required></textarea>
        <button type="submit">Send Message</button>
      </form>
    </div>
  </section>

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

  <!-- Zoho Chatbot -->
  <script>window.$zoho = window.$zoho || {}; $zoho.salesiq = $zoho.salesiq || { ready: function () { } }</script>
  <script id="zsiqscript"
    src="https://salesiq.zohopublic.in/widget?wc=siqb0f830a0885067d021d45d0a48528e2d255c3bf6ab1e64226805d95539ae3b1a"
    defer></script>
</body>

</html>