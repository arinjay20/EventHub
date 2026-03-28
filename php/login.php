<?php
/* ================================================================
   EventHub – login.php  (AJAX-compatible)
   Handles user login via POST, starts session
   Returns JSON response
   ================================================================ */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db.php';
startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$email    = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
$password = $_POST['password'] ?? '';
$role     = trim(htmlspecialchars($_POST['role'] ?? ''));

// Validation
$errors = [];
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
if (empty($password))                           $errors[] = 'Password is required.';
if (!in_array($role, ['student','organizer','admin'])) $errors[] = 'Invalid role selected.';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1');
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Incorrect email, password, or role.']);
        exit;
    }

    // Regenerate session for security
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];

    $redirectMap = [
        'student'   => 'student-dashboard.html',
        'organizer' => 'organizer-dashboard.html',
        'admin'     => 'admin-dashboard.html',
    ];

    echo json_encode([
        'success'  => true,
        'message'  => 'Login successful! Welcome, ' . $user['first_name'] . '!',
        'role'     => $user['role'],
        'name'     => $user['first_name'],
        'redirect' => $redirectMap[$user['role']] ?? '../index.html',
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Login failed. Please try again later.']);
}
