<?php
require_once 'db.php';
$schemaFile = __DIR__ . '/database_schema.sql';
$sql = file_get_contents($schemaFile);

try {
    // Connect without dbname to create it
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // We cannot use PDO to execute SQL with DELIMITERs easily.
    // So we'll try to run the command directly through the shell.
    $mysqlPath = "C:\\xampp\\mysql\\bin\\mysql.exe";
    $fullPathSchema = realpath($schemaFile);
    $command = "\"$mysqlPath\" -u " . DB_USER . " < \"$fullPathSchema\"";
    
    exec($command, $output, $return_var);
    
    if ($return_var === 0) {
        echo "Database setup successful!";
    } else {
        echo "MySQL command failed with code $return_var.\n";
        echo "Command: $command\n";
        echo "Output: " . (is_array($output) ? implode("\n", $output) : $output);
    }
} catch (PDOException $e) {
    echo "Connection error: " . $e->getMessage();
}
?>
