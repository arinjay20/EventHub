<?php
require 'php/db.php';
$pdo = getDB();

try {
    // 1. Add group_name to registrations
    $pdo->exec("ALTER TABLE registrations ADD COLUMN group_name VARCHAR(255) NULL AFTER event_id");
    echo "Added group_name column.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "group_name column already exists.\n";
    } else {
        echo "Error adding column: " . $e->getMessage() . "\n";
    }
}

try {
    // 2. Create team_members table
    $sql = "
    CREATE TABLE IF NOT EXISTS team_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        registration_id INT NOT NULL,
        full_name VARCHAR(160) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        student_id VARCHAR(50) NOT NULL,
        FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($sql);
    echo "Created team_members table.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}

?>
