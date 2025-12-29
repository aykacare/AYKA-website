<?php
require('vendor/autoload.php');
use Razorpay\Api\Api;

// DB connection
$conn = new mysqli('localhost', 'root', '', 'u672220903_multiclinic');
if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}

// Razorpay credentials
$keyId = 'YOUR_KEY_ID';
$keySecret = 'YOUR_KEY_SECRET';
$api = new Api($keyId, $keySecret);

// Handle payment success
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['razorpay_payment_id'])) {
    $paymentId = $_POST['razorpay_payment_id'];
    $orderId = $_POST['razorpay_order_id'];

    $stmt = $conn->prepare("UPDATE payments SET payment_id=?, status='success' WHERE order_id=?");
    $stmt->bind_param("ss", $paymentId, $orderId);
    $stmt->execute();
    echo "<h3 style='color: green'>✅ Payment successful!</h3>";
    exit;
}

// Handle form submission to create order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $planName = $_POST['plan_name'];
    $amount = $_POST['amount'];
    $duration = $_POST['duration'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $city = $_POST['city'];

    $order = $api->order->create([
        'receipt' => 'rcpt_' . time(),
        'amount' => $amount,
        'currency' => 'INR',
        'payment_capture' => 1
    ]);

    $orderId = $order['id'];

    $stmt = $conn->prepare("INSERT INTO payments (order_id, amount, plan_name, name, email, phone, gender, dob, city, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'created')");
    $stmt->bind_param("sisssssss", $orderId, $amount, $planName, $name, $email, $phone, $gender, $dob, $city);
    $stmt->execute();

    echo "<script src='https://checkout.razorpay.com/v1/checkout.js'></script>
    <script>
    var options = {
        key: '$keyId',
        amount: '$amount',
        currency: 'INR',
        name: 'My Clinic',
        description: '$planName - $duration',
        order_id: '$orderId',
        handler: function (response) {
            fetch('index.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'razorpay_payment_id=' + response.razorpay_payment_id + '&razorpay_order_id=' + response.razorpay_order_id + '&razorpay_signature=' + response.razorpay_signature
            }).then(() => window.location.reload());
        },
        prefill: {name: '$name', email: '$email', contact: '$phone'},
        theme: {color: '#528FF0'}
    };
    new Razorpay(options).open();
    </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subscription Plans</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container my-5">
        <div class="row">
            <?php
            $plans = [
                ['name' => 'Individual Plan', 'amount' => 39900, 'duration' => '3 Month', 'members' => '1 Member'],
                ['name' => 'Family Plan', 'amount' => 59900, 'duration' => '6 Months', 'members' => '2 Members'],
                ['name' => 'Family Plus', 'amount' => 99900, 'duration' => '6 Months', 'members' => '4 Members'],
                ['name' => 'Aged Care Plan', 'amount' => 24900, 'duration' => '45 Days', 'members' => '1 (Age 55+)']
            ];
            foreach ($plans as $plan): ?>
                <div class="col-md-3">
                    <div class="card text-center mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><?= $plan['name'] ?></h5>
                            <h2 class="card-text">₹ <?= $plan['amount'] / 100 ?></h2>
                            <p><?= $plan['duration'] ?></p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal"
                                data-plan='<?= json_encode($plan) ?>'>Get Started</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enter Your Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <input type="hidden" name="plan_name" id="planName">
                    <input type="hidden" name="amount" id="planAmount">
                    <input type="hidden" name="duration" id="planDuration">
                    <div class="col-md-6"><input type="text" name="name" class="form-control" placeholder="Name" required></div>
                    <div class="col-md-6"><input type="tel" name="phone" class="form-control" placeholder="Mobile Number" required></div>
                    <div class="col-md-6"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
                    <div class="col-md-6">
                        <select name="gender" class="form-control" required>
                            <option value="">Gender</option>
                            <option>Male</option>
                            <option>Female</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6"><input type="date" name="dob" class="form-control" required></div>
                    <div class="col-md-6"><input type="text" name="city" class="form-control" placeholder="City" required></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success w-100" type="submit">Proceed to Pay</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const paymentModal = document.getElementById('paymentModal');
        paymentModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            const plan = JSON.parse(button.getAttribute('data-plan'));
            document.getElementById('planName').value = plan.name;
            document.getElementById('planAmount').value = plan.amount;
            document.getElementById('planDuration').value = plan.duration;
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
