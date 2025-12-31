<?php
require 'db.php';

// Check if table exists, if not create it
$sql = "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    message LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "✓ Table 'contact_messages' is ready!";
} else {
    echo "✗ Error creating table: " . $conn->error;
}

$conn->close();
?>