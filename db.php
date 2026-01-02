<?php
$conn = new mysqli("localhost", "root", "webmax!", "u685993406_newaykadb-2");
if ($conn->connect_error) {
    die("DB Error: " . $conn->connect_error);
}
?>