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
$event_id    = $_POST['event_id'] ?? null;
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
    
    // Check if event with same name already exists for this organizer (excluding current event if editing)
    $check_sql = "SELECT id FROM events WHERE name = ? AND event_date = ? AND organizer_id = ?";
    $check_params = [$name, $full_event_date, $organizer_id];
    if ($event_id) {
        $check_sql .= " AND id != ?";
        $check_params[] = $event_id;
    }
    $stmt = $pdo->prepare($check_sql);
    $stmt->execute($check_params);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You already have another event with this name on this date!']);
        exit;
    }

    // 2. Handle Poster Upload
    $poster_path = null;
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

    if ($event_id) {
        // UPDATE EXISTING
        if ($poster_path) {
            $stmt = $pdo->prepare("UPDATE events SET name=?, category=?, event_date=?, venue=?, max_capacity=?, description=?, poster=? WHERE id=? AND organizer_id=?");
            $stmt->execute([$name, $category, $full_event_date, $venue, $capacity, $description, $poster_path, $event_id, $organizer_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE events SET name=?, category=?, event_date=?, venue=?, max_capacity=?, description=? WHERE id=? AND organizer_id=?");
            $stmt->execute([$name, $category, $full_event_date, $venue, $capacity, $description, $event_id, $organizer_id]);
        }
        
        echo json_encode(['success' => true, 'message' => '🚀 Event updated successfully!']);
    } else {
        // INSERT NEW
        $poster_path = $poster_path ?: 'assets/img/default-event.jpg';
        $stmt = $pdo->prepare("INSERT INTO events (name, organizer_id, category, event_date, venue, max_capacity, description, poster, status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$name, $organizer_id, $category, $full_event_date, $venue, $capacity, $description, $poster_path]);

        echo json_encode(['success' => true, 'message' => '🎉 Event published successfully!', 'event_id' => $pdo->lastInsertId()]);
    }

} catch (PDOException $e) {
    if ($e->getCode() == '42S22' || strpos($e->getMessage(), 'poster') !== false) {
        echo json_encode(['success' => false, 'message' => '🛠️ Database needs a quick update. Please visit localhost:8000/migrate_db.php in your browser!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Backend Error: ' . $e->getMessage()]);
    }
}
?>
