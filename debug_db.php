<?php
require_once 'php/db.php';
header('Content-Type: text/plain');

try {
    $pdo = getDB();
    echo "--- Database Diagnostic ---\n";
    
    $tables = ['users', 'events', 'registrations'];
    
    foreach ($tables as $table) {
        echo "\nTable: $table\n";
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "- {$row['Field']} ({$row['Type']})\n";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Connection error: " . $e->getMessage();
}
?>
