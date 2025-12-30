<?php
session_start();

// Get the specialization from the URL (e.g., ?specialist=General Physician)
$specialization = isset($_GET['specialist']) ? htmlspecialchars($_GET['specialist']) : 'General Specialist';
$amount = isset($_GET['amount']) ? htmlspecialchars($_GET['amount']) : '599';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consult <?php echo $specialization; ?> | AYKA Care</title>
    <link href="./assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #8b9d3d 0%, #a8b84a 100%);
            min-height: 100vh;
            padding: 40px 0;
        }

        .form-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            max-width: 900px;
            margin: 0 auto;
        }

        .form-card .row {
            min-height: auto;
        }

        .left-section {
            background-color: #f5f5f5;
            padding: 25px;
        }

        .left-section .logo-section {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .left-section .logo-section img {
            height: 30px;
            margin-right: 8px;
        }

        .left-section .logo-section h5 {
            font-weight: bold;
            margin: 0;
            color: #c8d439;
            font-size: 16px;
        }

        .left-section h4 {
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
            font-size: 18px;
        }

        .left-section ol {
            padding-left: 18px;
            margin-bottom: 20px;
            color: #666;
            font-size: 13px;
        }

        .left-section ol li {
            margin-bottom: 8px;
            color: #666;
            font-size: 13px;
        }

        .contact-section {
            margin-top: 20px;
        }

        .contact-section h6 {
            font-weight: bold;
            color: #c8d439;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .contact-section p {
            margin-bottom: 6px;
            color: #666;
            font-size: 12px;
        }

        .contact-section p i {
            margin-right: 6px;
            color: #c8d439;
        }

        .terms-section {
            margin-top: 20px;
        }

        .terms-section h6 {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .terms-section p {
            font-size: 12px;
            color: #999;
            line-height: 1.5;
        }

        .terms-section a {
            color: #0066cc;
            text-decoration: none;
            font-size: 12px;
        }

        .right-section {
            padding: 25px;
        }

        .right-section h5 {
            font-weight: bold;
            margin-bottom: 20px;
            color: #c8d439;
            font-size: 18px;
        }

        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 6px;
            font-size: 13px;
        }

        .form-control {
            border: 1px solid #e0e0e0;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 13px;
        }

        .form-control:focus {
            border-color: #c8d439;
            box-shadow: 0 0 0 0.2rem rgba(200, 212, 57, 0.25);
        }

        .form-control::placeholder {
            color: #999;
        }

        .form-control[readonly] {
            background-color: #f9f9f9;
            color: #666;
        }

        .payment-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
            gap: 15px;
        }

        .payment-methods {
            flex: 1;
        }

        .payment-methods img {
            width: 100%;
            height: auto;
            max-width: 250px;
        }

        .btn-pay {
            background-color: #2d8659;
            color: #fff;
            font-weight: 600;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .btn-pay:hover {
            background-color: #245a47;
            color: #fff;
            text-decoration: none;
        }

        @media (max-width: 991px) {
            .form-card .row {
                min-height: auto;
            }

            .left-section,
            .right-section {
                padding: 20px;
            }
        }

        @media (max-width: 576px) {
            .payment-section {
                flex-direction: column;
                gap: 15px;
            }

            .payment-methods img {
                max-width: 100%;
            }

            .btn-pay {
                width: 100%;
            }
        }
    </style>

</head>

<body>
    <div class="container">
        <div class="form-card">
            <form method="POST" action="index.php">
                <div class="row g-0">
                    <!-- Left side with plan details, contact info, and terms -->
                    <div class="col-lg-5 left-section">
                        <div class="logo-section">
                            <img src="./assets/images/logo.svg" alt="AYKA Care Logo">
                            <h5>AYKA CARE</h5>
                        </div>

                        <h4>Teleconsultation</h4>

                        <ol id="planFeatures">
                            <li>Trusted & Verified Expert Doctors</li>
                            <li>Your Privacy, Fully Protected</li>
                            <li>Free Post-Consultation Follow-Up</li>
                            <li>Quick Digital Prescriptions</li>
                            <li>Safely Stored Health Records</li>
                        </ol>

                        <div class="contact-section">
                            <h6>Contact Us:</h6>
                            <p><i class="bi bi-envelope"></i> support@aykacare.in</p>
                            <p><i class="bi bi-telephone"></i> 9220782066</p>
                        </div>

                        <div class="terms-section">
                            <h6>Terms & Conditions:</h6>
                            <p>
                                By proceeding, you agree to share the information provided on this page with AYKA CARE
                                for medical assistance, in compliance with applicable laws and privacy standards.
                            </p>
                            <a href="#">View Business Policies</a>
                        </div>
                    </div>

                    <!-- Right side with payment form and all fields -->
                    <div class="col-lg-7 right-section">
                        <h5>Payment Details</h5>

                        <!-- Hidden fields for payment processing -->
                        <input type="hidden" name="payment_details" id="paymentDetails">
                        <input type="hidden" name="amount" id="planAmount" value="<?php echo $amount; ?>">
                        <input type="hidden" name="plan_name" value="Teleconsultation">
                        <input type="hidden" name="name" value="consultation_request">
                        <input type="hidden" name="concern" value="<?php echo $specialization; ?>">

                        <div class="row g-3">
                            <!-- Amount field (readonly) -->
                            <div class="col-12">
                                <label class="form-label">Amount</label>
                                <input type="text" id="amountDisplay" class="form-control"
                                    value="₹ <?php echo $amount; ?>" readonly>
                            </div>

                            <!-- Symptoms Details textarea -->
                            <div class="col-12">
                                <label class="form-label">Symptoms Details</label>
                                <textarea name="symptoms_details" class="form-control" rows="4"
                                    placeholder="Describe symptoms..." required></textarea>
                            </div>

                            <!-- Patient Name field -->
                            <div class="col-md-6">
                                <label class="form-label">Patient Name</label>
                                <input type="text" name="patient_name" class="form-control" placeholder="Full Name"
                                    required>
                            </div>

                            <!-- Phone Number field -->
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" placeholder="Mobile Number"
                                    required>
                            </div>

                            <!-- Gender dropdown -->
                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-control" required>
                                    <option value="">Select</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <!-- Age field -->
                            <div class="col-md-6">
                                <label class="form-label">Age</label>
                                <input type="number" name="age" class="form-control" placeholder="Age" required>
                            </div>

                            <!-- Concern textarea -->
                            <div class="col-12">
                                <label class="form-label">Concern</label>
                                <textarea name="concern_details" class="form-control" rows="3"
                                    placeholder="Explain your concern..." required></textarea>
                            </div>
                        </div>

                        <!-- Payment section with button and methods -->
                        <div class="payment-section">
                            <div class="payment-methods">
                                <img src="./assets/images/pay_methods_branding.png" alt="Payment Methods">
                            </div>
                            <button type="submit" class="btn-pay">
                                Pay ₹ <span id="totalAmount"><?php echo $amount; ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>

</html>