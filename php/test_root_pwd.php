<?php
$passwords = ['', 'root', 'admin', '123456', 'password', 'mysql'];
foreach ($passwords as $pwd) {
    echo "Testing pattern: root / '$pwd' ... ";
    try {
        $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", 'root', $pwd);
        echo "SUCCESS!\n";
        exit;
    } catch (PDOException $e) {
        echo "Failed: " . $e->getMessage() . "\n";
    }
}
echo "All patterns failed.\n";
?>
