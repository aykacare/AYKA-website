<?php
session_start();
require 'db.php';

// Get the specialization or symptom from the URL
$specialization = isset($_GET['specialist']) ? htmlspecialchars($_GET['specialist']) : '';
$symptom = isset($_GET['symptom']) ? htmlspecialchars($_GET['symptom']) : '';

$amount = 399; // default
$final_specialization = 'General Physician'; // default specialist

// Check if table exists first
$table_check = $conn->query("SHOW TABLES LIKE 'consultation_pricing'");
if ($table_check && $table_check->num_rows > 0) {
    // Table exists, proceed with queries
    if (!empty($symptom)) {
        // Symptom-based consultation - get symptom pricing and related specialist from same table
        $stmt = $conn->prepare("SELECT video_price, related_specialist FROM consultation_pricing WHERE name = ? AND type = 'symptom' AND is_active = 1 LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $symptom);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $amount = (int) $row['video_price'];
                if (!empty($row['related_specialist'])) {
                    $final_specialization = $row['related_specialist'];
                }
            }
            $stmt->close();
        }
    } elseif (!empty($specialization)) {
        // Specialist-based consultation - get specialist pricing
        $final_specialization = $specialization;
        $stmt = $conn->prepare("SELECT video_price FROM consultation_pricing WHERE name = ? AND type = 'specialist' AND is_active = 1 LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $specialization);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $amount = (int) $row['video_price'];
            }
            $stmt->close();
        }
    } else {
        // Default case - get General Physician pricing
        $stmt = $conn->prepare("SELECT video_price FROM consultation_pricing WHERE name = 'General Physician' AND type = 'specialist' AND is_active = 1 LIMIT 1");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $amount = (int) $row['video_price'];
            }
            $stmt->close();
        }
    }
} else {

    if (!empty($specialization)) {
        $final_specialization = $specialization;
    }
}

// Use final specialization for display and form
$specialization = $final_specialization;


$_SESSION['consultation_amount'] = $amount;

// Determine the selected name for display (symptom or specialist)
$selected_name = '';
$selected_type = '';
if (!empty($symptom)) {
    $selected_name = $symptom;
    $selected_type = 'symptom';
} elseif (!empty($_GET['specialist'])) {
    $selected_name = htmlspecialchars($_GET['specialist']);
    $selected_type = 'specialist';
}

$conn->close();
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
            background: linear-gradient(rgba(255, 255, 255, 0.50), rgba(255, 255, 255, 0.50)),
                url('/assets/images/clinic.jpeg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
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
            position: relative;
        }

        .back-button-top {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 10;
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
            flex-wrap: wrap;
            gap: 8px;
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

        .selected-item-badge {
            display: inline-flex;
            align-items: center;
            background-color: #e8f5e9;
            border: 1px solid #4caf50;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            color: #2e7d32;
            font-weight: 600;
            margin-left: auto;
            max-width: 100%;
            word-wrap: break-word;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .selected-item-badge .check-icon {
            margin-right: 6px;
            color: #4caf50;
            font-weight: bold;
            font-size: 14px;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .left-section .logo-section {
                flex-direction: column;
                align-items: flex-start;
            }

            .selected-item-badge {
                margin-left: 0;
                margin-top: 8px;
                max-width: 100%;
                white-space: normal;
                word-break: break-word;
            }
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
            <div class="back-button-top">
                <a href="index.php#services" class="btn btn-outline-secondary"
                    style="text-decoration: none; color: #333; border-color: #c8d439; padding: 6px 15px; border-radius: 5px; font-size: 13px;">
                    Back
                </a>
            </div>
            <form method="POST" action="index.php">
                <div class="row g-0">
                    <!-- Left side with plan details, contact info, and terms -->
                    <div class="col-lg-5 left-section">
                        <div class="logo-section">
                            <img src="./assets/images/logo.svg" alt="AYKA Care Logo">
                            <?php if (!empty($selected_name)): ?>
                                <span class="selected-item-badge">
                                    <span class="check-icon">✓</span>
                                    <span class="selected-name"><?php echo htmlspecialchars($selected_name); ?></span>
                                </span>
                            <?php endif; ?>
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

                            <!-- Symptoms Details textarea -->
                            <div class="col-12">
                                <label class="form-label">Tell us your Symptoms/Health Concerns</label>
                                <textarea name="symptoms_details" class="form-control" rows="4"
                                    placeholder="eg.. I have a fever, cough, etc."></textarea>
                            </div>

                        </div>

                        <!-- <CHANGE> Added promo code field -->
                        <div class="col-12 mt-3">
                            <label class="form-label">Promo Code (Optional)</label>
                            <div class="input-group">
                                <input type="text" id="couponCode" class="form-control"
                                    placeholder="Enter code (e.g AYKA100)">
                                <button class="btn btn-outline-secondary" type="button" id="applyCoupon"
                                    style="font-size: 13px;">Apply</button>
                                <button class="btn btn-outline-danger" type="button" id="removeCoupon"
                                    style="font-size: 13px; display: none;">Remove</button>
                            </div>
                            <small id="couponMessage" class="mt-1 d-block"></small>
                        </div>
                        <input type="hidden" name="applied_coupon" id="appliedCouponInput" value="">

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

    <script>
        // Ensure amount is set before form submission
        document.querySelector('form').addEventListener('submit', function (e) {
            const amountField = document.getElementById('planAmount');
            const baseAmount = <?php echo $amount; ?>;
            let amountValue = parseFloat(amountField.value);


            if (!amountField.value || isNaN(amountValue) || amountValue <= 0) {
                amountField.value = baseAmount;
                amountValue = baseAmount;
            }

            // Ensure minimum amount of 1 for Razorpay
            if (amountValue < 1) {
                amountField.value = 1;
            }

            // Debug log (remove in production)
            console.log('Form submitting with amount:', amountField.value, 'Base amount:', baseAmount);
        });

        document.getElementById('applyCoupon').addEventListener('click', function () {
            const code = document.getElementById('couponCode').value;
            const msg = document.getElementById('couponMessage');
            const baseAmount = <?php echo $amount; ?>;

            if (!code) {
                msg.innerText = "Please enter a coupon code";
                msg.style.color = "red";
                return;
            }

            fetch('validate-coupon.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'coupon_code=' + encodeURIComponent(code)
            })
                .then(response => {
                    // Check if response is ok
                    if (!response.ok) {
                        throw new Error('Server error: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Coupon validation response:', data); // Debug log

                    if (data.success) {
                        let discount = 0;
                        if (data.isPercentage) {
                            discount = Math.round((baseAmount * data.discount) / 100);
                        } else {
                            discount = data.discount;
                        }

                        const newTotal = Math.max(baseAmount - discount, 1); // Minimum ₹1 for Razorpay

                        // Update UI with new total
                        document.getElementById('totalAmount').innerText = newTotal;
                        document.getElementById('planAmount').value = newTotal; // Hidden field for payment
                        document.getElementById('amountDisplay').value = "₹ " + newTotal + " (Discount Applied)";
                        document.getElementById('appliedCouponInput').value = code;

                        msg.style.color = "green";
                        msg.innerText = "✓ Success! ₹" + discount + " discount applied. New total: ₹" + newTotal;
                        document.getElementById('applyCoupon').disabled = true;
                        document.getElementById('couponCode').readOnly = true;
                        document.getElementById('removeCoupon').style.display = 'inline-block';

                        console.log('Coupon applied successfully. Base:', baseAmount, 'Discount:', discount, 'New Total:', newTotal);
                    } else {
                        msg.style.color = "red";
                        msg.innerText = "✗ " + (data.message || 'Invalid coupon code');
                        console.error('Coupon validation failed:', data.message);
                    }
                })
                .catch(error => {
                    msg.style.color = "red";
                    msg.innerText = "✗ Error validating coupon. Please try again.";
                    console.error('Coupon validation error:', error);
                });
        });

        // Remove coupon functionality
        document.getElementById('removeCoupon').addEventListener('click', function () {
            const baseAmount = <?php echo $amount; ?>;
            const msg = document.getElementById('couponMessage');

            // Reset to original amount
            document.getElementById('totalAmount').innerText = baseAmount;
            document.getElementById('planAmount').value = baseAmount;
            document.getElementById('amountDisplay').value = "₹ " + baseAmount;
            document.getElementById('appliedCouponInput').value = "";
            document.getElementById('couponCode').value = "";
            document.getElementById('couponCode').readOnly = false;
            document.getElementById('applyCoupon').disabled = false;
            document.getElementById('removeCoupon').style.display = 'none';
            msg.innerText = "";
        });
    </script>
</body>

</html>