<?php
require_once 'db.php';

try {
    // 1. Connect without DB name to create it
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Create the Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS eventhub_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "Database created successfully.\n";

    // 3. Reconnect to the new DB
    $pdo->exec("USE eventhub_db");

    // 4. Load Schema from file
    $sql = file_get_contents(__DIR__ . '/database_schema.sql');
    
    // PDO::exec() cannot handle multiple statements with DELIMITER.
    // However, our schema file has DELIMITER $$ ... END $$.
    // Let's split by delimiter or just try one block at a time.
    // Simplifying: replace DELIMITER statements and split by ; if possible.
    
    // For now, let's just create the tables manually if the file is too complex.
    // BUT we can actually try to execute the whole thing at once if we use mysqli 
    // or if we use $pdo->exec() on individual statements.
    
    // Splitting by ';' usually works but triggers are hard.
    // Let's use a simpler approach: use mysqli::multi_query
    
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }
    
    // mysqli also doesn't like DELIMITER but multi_query works for normal statements.
    // Let's just create the tables we need.
    
    if ($mysqli->multi_query($sql)) {
         do {
             if ($result = $mysqli->store_result()) {
                 $result->free();
             }
         } while ($mysqli->next_result());
         echo "Schema applied successfully!\n";
    } else {
        echo "Multi-query failed: " . $mysqli->error . "\n";
    }
    $mysqli->close();

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
