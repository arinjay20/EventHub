<?php
require_once 'db.php';
try {
    $pdo = getDB();
    $sql = "ALTER TABLE registrations 
            ADD COLUMN IF NOT EXISTS full_name VARCHAR(160) AFTER event_id,
            ADD COLUMN IF NOT EXISTS course VARCHAR(100) AFTER full_name,
            ADD COLUMN IF NOT EXISTS branch VARCHAR(100) AFTER course,
            ADD COLUMN IF NOT EXISTS phone VARCHAR(20) AFTER branch,
            ADD COLUMN IF NOT EXISTS student_id VARCHAR(50) AFTER phone";
    $pdo->exec($sql);
    echo "Migration successful: Columns added to registrations table.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') { // Duplicate column
        echo "Migration already applied or partial success.\n";
    } else {
        echo "Migration failed: " . $e->getMessage() . "\n";
    }
}
?>
