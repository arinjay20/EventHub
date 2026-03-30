<?php
/* ================================================================
   EventHub – upload_avatar.php
   Handles profile picture uploads for logged-in users.
   ================================================================ */

header('Content-Type: application/json');

require_once 'db.php';
startSession();

// Verify login
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check for file
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or an error occurred.']);
    exit;
}

$file = $_FILES['avatar'];

// Basic validations
$maxSize = 5 * 1024 * 1024; // 5 MB
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File size exceeds the 5MB limit.']);
    exit;
}

// Ensure directory exists in the document root (C:/xampp/htdocs/eventhub/uploads/avatars or equivalent relative)
// We will upload it to "../uploads/avatars/"
$uploadDir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$mimeType = mime_content_type($file['tmp_name']);
if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and WebP images are allowed.']);
    exit;
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
$targetFilePath = $uploadDir . $filename;

// Move the file
if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
    
    // The path we store in DB (relative to site root)
    $dbPath = 'uploads/avatars/' . $filename;

    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
        $stmt->execute([$dbPath, $user_id]);

        echo json_encode([
            'success' => true, 
            'message' => 'Avatar updated successfully',
            'profile_pic' => $dbPath
        ]);
    } catch (PDOException $e) {
        // If DB fails, optionally delete the just uploaded file
        @unlink($targetFilePath);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
}
