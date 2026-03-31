<?php
/* ================================================================
   EventHub – upload_avatar.php
   Handles profile picture uploads for logged-in users.
   ================================================================ */

ob_start(); // Buffer output to prevent stray characters/warnings from breaking JSON

function sendJson($data, $statusCode = 200) {
    ob_clean(); // Wipe any PHP warnings captured in the buffer
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

require_once 'db.php';
startSession();

// Verify login
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    sendJson(['success' => false, 'message' => 'Unauthorized']);
}

// Check for file
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    sendJson(['success' => false, 'message' => 'No file uploaded or an error occurred.']);
}

$file = $_FILES['avatar'];

// Basic validations
$maxSize = 5 * 1024 * 1024; // 5 MB
if ($file['size'] > $maxSize) {
    sendJson(['success' => false, 'message' => 'File size exceeds the 5MB limit.']);
}

// Ensure directory exists
$uploadDir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

$mimeType = false;
if (function_exists('mime_content_type')) {
    $mimeType = @mime_content_type($file['tmp_name']);
} else {
    $imgSize = @getimagesize($file['tmp_name']);
    if ($imgSize) $mimeType = $imgSize['mime'];
}

if (!$mimeType || !in_array($mimeType, $allowedTypes)) {
    sendJson(['success' => false, 'message' => 'Only JPG, PNG, and WebP images are allowed.']);
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
if (!$ext) $ext = 'jpg'; // fallback
$filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
$targetFilePath = $uploadDir . $filename;

// Move the file
if (@move_uploaded_file($file['tmp_name'], $targetFilePath)) {
    
    // The path we store in DB (relative to site root)
    $dbPath = 'uploads/avatars/' . $filename;

    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
        $stmt->execute([$dbPath, $user_id]);

        sendJson([
            'success' => true, 
            'message' => 'Avatar updated successfully',
            'profile_pic' => $dbPath
        ]);
    } catch (PDOException $e) {
        // If DB fails, delete the just uploaded file
        @unlink($targetFilePath);
        sendJson(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

} else {
    // Check if permissions are bad
    $msg = 'Failed to move uploaded file. Check folder permissions.';
    if (!is_writable($uploadDir)) $msg = 'Uploads folder is not writable.';
    sendJson(['success' => false, 'message' => $msg]);
}
