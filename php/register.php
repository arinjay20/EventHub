<?php
/* ================================================================
   EventHub – register.php
   Handles new user registration via POST
   ================================================================ */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Sanitize inputs
$firstName  = trim(htmlspecialchars($_POST['first_name']  ?? ''));
$lastName   = trim(htmlspecialchars($_POST['last_name']   ?? ''));
$email      = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
$role       = trim(htmlspecialchars($_POST['role']        ?? ''));
$department = trim(htmlspecialchars($_POST['department']  ?? ''));
$password   = $_POST['password']         ?? '';
$confirmPwd = $_POST['confirm_password'] ?? '';

// --- Validation ---
$errors = [];

if (empty($firstName) || empty($lastName)) {
    $errors[] = 'Full name is required.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address.';
}

if (!in_array($role, ['student', 'organizer'])) {
    $errors[] = 'Invalid role selected.';
}

if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}

if ($password !== $confirmPwd) {
    $errors[] = 'Passwords do not match.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    $pdo = getDB();

    // Check if email already exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'An account with this email already exists.']);
        exit;
    }

    // Hash password securely
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // Insert user
    $stmt = $pdo->prepare(
        'INSERT INTO users (first_name, last_name, email, password_hash, role, department, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([$firstName, $lastName, $email, $passwordHash, $role, $department]);

    $userId = $pdo->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully! Please login.',
        'user_id' => $userId,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}
