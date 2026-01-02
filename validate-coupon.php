<?php
header('Content-Type: application/json');

try {
    $conn = new mysqli('localhost', 'u685993406_newaykadb', '8DwLlBGb!', 'u685993406_newaykadb');

    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    $code = $_POST['coupon_code'] ?? '';

    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a coupon code']);
        exit;
    }

    $currentDate = date('Y-m-d');

    $stmt = $conn->prepare("SELECT value FROM coupon WHERE title = ? AND active = 1 AND start_date <= ? AND end_date >= ? LIMIT 1");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sss", $code, $currentDate, $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $value = $row['value'];
        $discount = (float) str_replace('%', '', $value);

        echo json_encode([
            'success' => true,
            'discount' => $discount,
            'isPercentage' => true
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon code']);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("Coupon validation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error validating coupon. Please try again.']);
}
?>