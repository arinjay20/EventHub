<?php
/**
 * populate_data.php
 * Final fix for database connection and sample data insertion.
 */
$host = '127.0.0.1'; // Use IP to bypass potential socket issues
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Fix Auth Plugin (if needed)
    try {
        $pdo->exec("ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY ''");
        echo "Auth plugin fixed.\n";
    } catch (Exception $e) {
        // Might already be fixed or not needed
    }

    // 2. Create Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS eventhub_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE eventhub_db");
    echo "Database eventhub_db ready.\n";

    // 3. Create Tables (Users and Events)
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        email VARCHAR(100) UNIQUE,
        password VARCHAR(255),
        role ENUM('student', 'organizer', 'admin') DEFAULT 'student',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS events (
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (organizer_id) REFERENCES users(id)
    )");
    echo "Tables created.\n";

    // 4. Insert Sample Data
    // Check if data already exists
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("INSERT INTO users (first_name, last_name, email, password, role) VALUES 
            ('Arinjay', 'Organizer', 'organizer@eventhub.com', 'password123', 'organizer'),
            ('Test', 'Student', 'student@test.com', 'password123', 'student')");
        
        $orgId = $pdo->lastInsertId() - 1; // Assuming first insert was organizer
        
        $pdo->exec("INSERT INTO events (organizer_id, name, category, event_date, venue, description, max_capacity, registered_count) VALUES 
            ($orgId, 'Grafest 2024', 'cultural', '2024-05-15 18:00:00', 'GEU Main Ground', 'The biggest cultural extravaganza of Graphic Era!', 5000, 3200),
            ($orgId, 'Annual Codathon', 'technology', '2024-06-10 10:00:00', 'CS Lab 1', 'Showcase your coding skills and win prizes.', 500, 450),
            ($orgId, 'Inter-Department Football', 'sports', '2024-04-20 08:00:00', 'Sports Complex', 'The ultimate football showdown.', 1000, 800)");
        echo "Sample data inserted.\n";
    } else {
        echo "Data already exists, skipping insertion.\n";
    }

    echo "SUCCESS: DATABASE READY FOR REAL VALUES\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
