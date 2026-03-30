<?php
/**
 * test_conn_final.php
 * Final verification of the restored database connection.
 */
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    echo "CONNECTION_SUCCESSFUL\n";
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
?>
