<?php
require 'db.php';

// Check if table exists, if not create it
$sql = "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    mobile VARCHAR(255) NOT NULL,
        message LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "✓ Table 'contact_messages' is ready!<br>";
} else {
    echo "✗ Error creating table: " . $conn->error . "<br>";
}

// Create consultation_requests table
$sql2 = "CREATE TABLE IF NOT EXISTS consultation_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_name VARCHAR(255) NOT NULL,
    phone VARCHAR(255) NOT NULL,
    gender VARCHAR(50) NOT NULL,
    age INT NOT NULL,
    symptoms_details LONGTEXT NOT NULL,
    specialization VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    coupon_code VARCHAR(255) DEFAULT NULL,
    payment_id VARCHAR(255) DEFAULT NULL,
    order_id VARCHAR(255) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql2) === TRUE) {
    echo "✓ Table 'consultation_requests' is ready!<br>";
} else {
    echo "✗ Error creating consultation_requests table: " . $conn->error . "<br>";
}

$conn->close();
?>