<?php
require_once 'php/db.php';
try {
    $pdo = getDB();
    echo "Connection successful\n";
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(", ", $tables) . "\n";
    
    if (in_array('events', $tables)) {
        $count = $pdo->query("SELECT count(*) FROM events")->fetchColumn();
        echo "Events count: $count\n";
        
        $rows = $pdo->query("SELECT id, name, organizer_id FROM events")->fetchAll(PDO::FETCH_ASSOC);
        print_r($rows);
    } else {
        echo "Events table not found!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
