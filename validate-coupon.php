<?php
header('Content-Type: application/json');
$conn = new mysqli('localhost', 'u685993406_newaykadb', '8DwLlBGb!', 'u685993406_newaykadb');

$code = $_POST['coupon_code'] ?? '';
$currentDate = date('Y-m-d');

$stmt = $conn->prepare("SELECT value FROM coupon WHERE title = ? AND active = 1 AND start_date <= ? AND end_date >= ? LIMIT 1");
$stmt->bind_param("sss", $code, $currentDate, $currentDate);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'discount' => (int) $row['value']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon code']);
}
?>