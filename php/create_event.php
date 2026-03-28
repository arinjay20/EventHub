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
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please login as an organizer.']);
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

try {
    $pdo = getDB();
    
    // Check if event with same name already exists for this organizer on the same date
    $stmt = $pdo->prepare("SELECT id FROM events WHERE name = ? AND event_date = ? AND organizer_id = ?");
    $stmt->execute([$name, $full_event_date, $organizer_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You already have an event with this name on this date!']);
        exit;
    }

    // 2. Insert Event
    $stmt = $pdo->prepare("INSERT INTO events (name, organizer_id, category, event_date, venue, max_capacity, description, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$name, $organizer_id, $category, $full_event_date, $venue, $capacity, $description]);

    echo json_encode([
        'success' => true, 
        'message' => '🎉 Event created successfully!',
        'event_id' => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
