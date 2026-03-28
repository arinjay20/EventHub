<?php
/* ================================================================
   EventHub – live_feed.php  (v2 - ID-based polling, no timezone issues)
   ================================================================ */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Access-Control-Allow-Origin: *');

require_once 'db.php';

$action  = $_GET['action']  ?? 'all';
$last_id = (int) ($_GET['last_id'] ?? 0);   // JS sends highest ID it already knows

try {
    $pdo      = getDB();
    $response = [];

    /* ---- STATS (always fresh) ---- */
    if ($action === 'all' || $action === 'stats') {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $response['total_users'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM events WHERE status IN ('active','full')");
        $response['active_events'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM registrations");
        $response['total_registrations'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
        $response['new_today'] = (int) $stmt->fetchColumn();
    }

    /* ---- RECENT REGISTRATIONS ---- */
    if ($action === 'all' || $action === 'feed') {

        if ($last_id > 0) {
            /* Incremental: only users newer than what client already has */
            $stmt = $pdo->prepare("
                SELECT
                    u.id, u.first_name, u.last_name, u.email,
                    u.role, u.department,
                    TIMESTAMPDIFF(SECOND, u.created_at, NOW()) AS seconds_ago
                FROM users u
                WHERE u.id > ?
                ORDER BY u.id DESC
                LIMIT 20
            ");
            $stmt->execute([$last_id]);
        } else {
            /* First load: return 10 most recent */
            $stmt = $pdo->query("
                SELECT
                    u.id, u.first_name, u.last_name, u.email,
                    u.role, u.department,
                    TIMESTAMPDIFF(SECOND, u.created_at, NOW()) AS seconds_ago
                FROM users u
                ORDER BY u.id DESC
                LIMIT 10
            ");
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format each row
        foreach ($rows as &$row) {
            $sec = (int) $row['seconds_ago'];
            if ($sec <= 0)           $row['time_ago'] = 'just now';
            elseif ($sec < 60)       $row['time_ago'] = 'just now';
            elseif ($sec < 3600)     $row['time_ago'] = floor($sec / 60) . ' min ago';
            elseif ($sec < 86400)    $row['time_ago'] = floor($sec / 3600) . ' hr ago';
            else                     $row['time_ago'] = floor($sec / 86400) . ' days ago';

            $row['avatar'] = strtoupper(
                substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)
            );
        }
        unset($row);

        // Return highest ID so the client can send it back next poll
        $response['feed']       = $rows;
        $response['feed_count'] = count($rows);
        $response['max_id']     = count($rows) > 0 ? (int)$rows[0]['id'] : $last_id;
    }

    $response['success'] = true;
    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Feed error: ' . $e->getMessage()]);
}
