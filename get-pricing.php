<?php
header('Content-Type: application/json');
require 'db.php';

$name = $_GET['name'] ?? '';
$type = $_GET['type'] ?? 'specialist'; // 'specialist' or 'symptom'

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Name parameter required']);
    exit;
}

$stmt = $conn->prepare("SELECT video_price FROM consultation_pricing WHERE name = ? AND type = ? AND is_active = 1 LIMIT 1");
$stmt->bind_param("ss", $name, $type);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'video_price' => (float) $row['video_price']
    ]);
}

$stmt->close();
$conn->close();
?>