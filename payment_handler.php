<?php
session_start();
require('db.php');
require('vendor/autoload.php');

use Razorpay\Api\Api;

// Razorpay Credentials (Move to .env or config in production)
$key    = 'rzp_live_1hzhzXuOlge5pF';
$secret = 'vGUPpWNcWAJINBJcH9GFCb3R';

$api = new Api($key, $secret);

// ------------------------------
// STEP 1: Create Razorpay Order
// ------------------------------
if (!isset($_POST['razorpay_payment_id'])) {
    if (!isset($_POST['amount']) || !is_numeric($_POST['amount'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid amount']);
        exit;
    }

    $_SESSION['form'] = $_POST;

    try {
        $order = $api->order->create([
            'receipt' => uniqid('receipt_'),
            'amount' => $_POST['amount'] * 100, // INR in paisa
            'currency' => 'INR'
        ]);

        $_SESSION['order_id'] = $order['id'];

        echo json_encode([
            'status' => 'ok',
            'order_id' => $order['id'],
            'amount' => $_POST['amount'] * 100,
            'plan' => $_POST['plan_name']
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Order creation failed: ' . $e->getMessage()]);
    }

    exit;
}

// ------------------------------
// STEP 2: Payment Verification
// ------------------------------
try {
    if (
        !isset($_POST['razorpay_order_id']) ||
        !isset($_POST['razorpay_payment_id']) ||
        !isset($_POST['razorpay_signature'])
    ) {
        throw new Exception("Missing payment details.");
    }

    $attributes = [
        'razorpay_order_id'   => $_POST['razorpay_order_id'],
        'razorpay_payment_id' => $_POST['razorpay_payment_id'],
        'razorpay_signature'  => $_POST['razorpay_signature']
    ];

    $api->utility->verifyPaymentSignature($attributes); // Will throw exception if invalid

    if (!isset($_SESSION['form'])) {
        throw new Exception("Session expired. Form data missing.");
    }

    $form = $_SESSION['form'];

    // Data to insert
    $order_id   = $_POST['razorpay_order_id'];
    $payment_id = $_POST['razorpay_payment_id'];
    $amount     = $form['amount'];
    $plan       = $form['plan_name'];
    $name       = $form['name'];
    $email      = $form['email'];
    $phone      = $form['phone'];
    $gender     = $form['gender'];
    $dob        = $form['dob'];
    $city       = $form['city'];

    // Insert into DB
    $stmt = $conn->prepare("
        INSERT INTO payments 
        (order_id, payment_id, amount, plan_name, name, email, phone, gender, dob, city, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'success')
    ");
    $stmt->bind_param("ssisssssss", $order_id, $payment_id, $amount, $plan, $name, $email, $phone, $gender, $dob, $city);
    $stmt->execute();

    unset($_SESSION['form']);

    echo json_encode(['status' => 'success', 'message' => 'Payment verified and saved.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
