<?php
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'none') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'none') . "<br>";
?>
