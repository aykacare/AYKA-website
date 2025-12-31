<?php
$conn = new mysqli("localhost", "u685993406_newaykadb", "8DwLlBGb!", "u685993406_newaykadb");
if ($conn->connect_error) {
    die("DB Error: " . $conn->connect_error);
}
?>