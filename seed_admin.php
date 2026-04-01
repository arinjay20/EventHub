<?php
require_once 'php/db.php';
try {
    $pdo = getDB();
    
    // Ensure tables exist (running the final setup logic)
    require_once 'php/setup_db_final.php';
    
    $email = 'admin@eventhub.com';
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo "Admin user already exists.\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Admin', 'User', $email, $hash, 'admin']);
        echo "Admin user created successfully.\n";
    }
    
    // Also add an organizer
    $org_email = 'organizer@eventhub.com';
    $org_pass = 'organizer123';
    $org_hash = password_hash($org_pass, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$org_email]);
    if ($stmt->fetch()) {
        echo "Organizer user already exists.\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Event', 'Organizer', $org_email, $org_hash, 'organizer']);
        echo "Organizer user created successfully.\n";
    }

    echo "Login Credentials:\n";
    echo "Admin: $email / $password\n";
    echo "Organizer: $org_email / $org_pass\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
