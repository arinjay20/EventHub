<?php
/**
 * final_db_fix.php
 * Using mysqli for maximum compatibility with XAMPP MariaDB.
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';

echo "--- DATABASE DIAGNOSTIC & FIX ---\n";

// 1. Test Connection
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error . "\n";
    echo "Attempting with 'localhost'...\n";
    $conn = new mysqli('localhost', $user, $pass);
}

if ($conn->connect_error) {
    die("CRITICAL ERROR: Could not connect to MySQL even with mysqli.\n");
}

echo "Connected successfully to MySQL server!\n";

// 2. Fix Auth Plugin (if needed)
$sql = "ALTER USER 'root'@'localhost' IDENTIFIED BY ''"; 
if ($conn->query($sql)) {
    echo "Root auth reset to default.\n";
}

// 3. Create Database
if ($conn->query("CREATE DATABASE IF NOT EXISTS eventhub_db")) {
    echo "Database 'eventhub_db' verified.\n";
}
$conn->select_db('eventhub_db');

// 4. Create Tables
$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        email VARCHAR(100) UNIQUE,
        password VARCHAR(255),
        role ENUM('student', 'organizer', 'admin') DEFAULT 'student',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "events" => "CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        organizer_id INT,
        name VARCHAR(100),
        category VARCHAR(50),
        event_date DATETIME,
        venue VARCHAR(100),
        description TEXT,
        max_capacity INT,
        registered_count INT DEFAULT 0,
        status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($tables as $name => $sql) {
    if ($conn->query($sql)) {
        echo "Table '$name' verified.\n";
    } else {
        echo "Error creating table '$name': " . $conn->error . "\n";
    }
}

// 5. Insert Sample Data for "Real Values"
$res = $conn->query("SELECT COUNT(*) as cnt FROM events");
$row = $res->fetch_assoc();
if ($row['cnt'] == 0) {
    echo "Inserting sample campus events...\n";
    $conn->query("INSERT INTO users (first_name, last_name, email, role) VALUES ('Admin', 'Hub', 'admin@geu.ac.in', 'admin')");
    $adminId = $conn->insert_id;
    
    $conn->query("INSERT INTO events (organizer_id, name, category, event_date, venue, description, max_capacity, registered_count) VALUES 
        ($adminId, 'Grafest 2024', 'cultural', '2024-05-15 18:00:00', 'GEU Main Ground', 'The biggest cultural fest!', 5000, 3200),
        ($adminId, 'Tech-Expo', 'technology', '2024-06-10 10:00:00', 'CS Hall', 'Showcasing student innovation.', 1000, 450),
        ($adminId, 'Sports Meet', 'sports', '2024-11-20 09:00:00', 'Stadium', 'Annual athletics championship.', 2000, 1500)");
    echo "Sample data inserted successfully.\n";
}

echo "--- SUCCESS: DATABASE IS READY ---\n";
$conn->close();
?>
