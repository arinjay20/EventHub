<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "eventhub_db");

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
} else {
    echo "Connection successful using mysqli!";
    $mysqli->close();
}
?>
