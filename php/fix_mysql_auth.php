<?php
/**
 * fix_mysql_auth.php
 * Utility to switch root@localhost to mysql_native_password
 * so modern PHP can connect to XAMPP MySQL correctly.
 */

$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP pass is empty

try {
    // Attempt 1: Standard connection
    $conn = new mysqli($host, $user, $pass);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "Successfully connected to MySQL!\n";
    
    // Attempt to fix the authentication plugin
    $sql = "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY ''";
    if ($conn->query($sql) === TRUE) {
        echo "Successfully updated 'root'@'localhost' to use mysql_native_password.\n";
    } else {
        echo "Error updating user: " . $conn->error . "\n";
    }

    $conn->close();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "If this fails, please run this manually in XAMPP MySQL Console:\n";
    echo "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';\n";
}
?>
