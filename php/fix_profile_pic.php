<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'eventhub_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add the column if it doesn't exist
$res = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
if ($res->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL AFTER department");
    echo "Added profile_pic column successfully.\n";
} else {
    echo "Column profile_pic already exists.\n";
}

$conn->close();
?>
