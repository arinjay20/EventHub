<?php
/**
 * setup_db_final.php
 * Unifies the database schema and ensures the 'registrations' table has the correct columns.
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'eventhub_db';

$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Create DB if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS $db");
$conn->select_db($db);

echo "--- EventHub Database Setup ---\n";

// 2. Users Table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student','organizer','admin') NOT NULL DEFAULT 'student',
    department VARCHAR(100) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";
if ($conn->query($sql)) echo "Table 'users' ready.\n";

// 3. Events Table
$sql = "CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    category ENUM('technology','cultural','sports','academic','workshop') NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME DEFAULT NULL,
    venue VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    max_capacity INT UNSIGNED NOT NULL DEFAULT 100,
    registered_count INT UNSIGNED NOT NULL DEFAULT 0,
    organizer_id INT NOT NULL,
    status ENUM('active','full','completed','cancelled') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB";
if ($conn->query($sql)) echo "Table 'events' ready.\n";

// 4. Registrations Table (The critical fix)
// We check if columns exist, if not we add them or recreate the table if empty
$res = $conn->query("SHOW TABLES LIKE 'registrations'");
if ($res->num_rows > 0) {
    // Check for missing columns
    $columns = [];
    $res = $conn->query("DESCRIBE registrations");
    while($row = $res->fetch_assoc()) $columns[] = $row['Field'];
    
    if (!in_array('full_name', $columns)) {
        echo "Updating 'registrations' table with student detail columns...\n";
        $conn->query("ALTER TABLE registrations 
            ADD COLUMN full_name VARCHAR(200) AFTER event_id,
            ADD COLUMN course VARCHAR(100) AFTER full_name,
            ADD COLUMN branch VARCHAR(200) AFTER course,
            ADD COLUMN phone VARCHAR(20) AFTER branch,
            ADD COLUMN student_id VARCHAR(50) AFTER phone");
    }
} else {
    echo "Creating 'registrations' table...\n";
    $sql = "CREATE TABLE registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        event_id INT NOT NULL,
        full_name VARCHAR(200),
        course VARCHAR(100),
        branch VARCHAR(200),
        phone VARCHAR(20),
        student_id VARCHAR(50),
        registration_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        UNIQUE KEY unique_reg (user_id, event_id)
    ) ENGINE=InnoDB";
    $conn->query($sql);
}
echo "Table 'registrations' ready.\n";

// 5. Triggers
$conn->query("DROP TRIGGER IF EXISTS after_registration_insert");
$trigger = "
CREATE TRIGGER after_registration_insert
AFTER INSERT ON registrations
FOR EACH ROW
BEGIN
    UPDATE events
    SET registered_count = registered_count + 1,
        status = IF(registered_count + 1 >= max_capacity, 'full', 'active')
    WHERE id = NEW.event_id;
END";
if ($conn->query($trigger)) echo "Trigger 'after_registration_insert' ready.\n";

echo "--- Setup Complete ---\n";
$conn->close();
?>
