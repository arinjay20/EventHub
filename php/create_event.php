<?php
/* ================================================================
   EventHub – create_event.php
   Inserts a new event into the database for the logged-in organizer
   ================================================================ */
require_once 'db.php';
startSession();

header('Content-Type: application/json');

// Defensive session check
$user_id = $_SESSION['user_id'] ?? null;
$role    = $_SESSION['user_role'] ?? null;

if (!$user_id || $role !== 'organizer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$organizer_id = $user_id;

// 1. Sanitize Inputs
$name        = trim($_POST['event_name'] ?? '');
$category    = trim($_POST['category'] ?? '');
$event_date  = $_POST['event_date'] ?? '';
$event_time  = $_POST['event_time'] ?? '';
$venue       = trim($_POST['venue'] ?? '');
$capacity    = (int)($_POST['capacity'] ?? 0);
$description = trim($_POST['description'] ?? '');

if (empty($name) || empty($category) || empty($event_date) || empty($event_time) || empty($venue) || $capacity <= 0) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// Combine date and time
$full_event_date = $event_date . ' ' . $event_time;

// 2. Handle Poster Upload
$poster_path = 'assets/img/default-event.jpg'; // Fallback
if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/posters/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $file_ext = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
    
    if (in_array($file_ext, $allowed_exts)) {
        $new_filename = 'poster_' . time() . '_' . uniqid() . '.' . $file_ext;
        $target_file = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['poster']['tmp_name'], $target_file)) {
            $poster_path = 'uploads/posters/' . $new_filename;
        }
    }
}

try {
    $pdo = getDB();
    
    // Check if event with same name already exists for this organizer on the same date
    $stmt = $pdo->prepare("SELECT id FROM events WHERE name = ? AND event_date = ? AND organizer_id = ?");
    $stmt->execute([$name, $full_event_date, $organizer_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You already have an event with this name on this date!']);
        exit;
    }

    // 3. Insert Event (Added poster column)
    $stmt = $pdo->prepare("INSERT INTO events (name, organizer_id, category, event_date, venue, max_capacity, description, poster, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$name, $organizer_id, $category, $full_event_date, $venue, $capacity, $description, $poster_path]);

    echo json_encode([
        'success' => true, 
        'message' => '🎉 Event published successfully!',
        'event_id' => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    if ($e->getCode() == '42S22' || strpos($e->getMessage(), 'poster') !== false) {
        echo json_encode(['success' => false, 'message' => '🛠️ Database needs a quick update. Please visit localhost:8000/migrate_db.php in your browser!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Backend Error: ' . $e->getMessage()]);
    }
}
?>
