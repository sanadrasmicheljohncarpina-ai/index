<?php
// Real-time notification endpoint for Faculty and Staff dashboards.
// Supports lightweight polling (GET) and AJAX "mark all read" (POST).
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once 'db.php';
require_once dirname(__DIR__) . '/shared/notification_message.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['teacher', 'staff'], true)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

function notif_muted_types_sql(array $prefs): string {
    $map = ['notify_evaluation_schedule'=>'evaluation_schedule','notify_teaching_assignment'=>'teaching_assignment','notify_academic_period'=>'academic_period'];
    $m = [];
    foreach ($map as $key => $type) { if (isset($prefs[$key]) && (int)$prefs[$key] === 0) $m[] = "'" . $type . "'"; }
    return $m ? ' AND type NOT IN (' . implode(',', $m) . ')' : '';
}

$prefs = [];
try {
    $pst = $mysqli->prepare('SELECT * FROM user_preferences WHERE user_id=? LIMIT 1');
    if ($pst) { $pst->bind_param('i', $user_id); $pst->execute(); $prefs = $pst->get_result()->fetch_assoc() ?: []; $pst->close(); }
} catch (Throwable $e) { $prefs = []; }
$muted_sql = notif_muted_types_sql($prefs);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_notifications_read'])) {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    $stmt = $mysqli->prepare('UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0');
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

$notifications = [];
$unread_count = 0;
$stmt = $mysqli->prepare('SELECT id, type, message, extra_data, is_read, created_at FROM notifications WHERE user_id=?' . $muted_sql . ' ORDER BY created_at DESC, id DESC LIMIT 15');
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int)$row['id'];
            $row['is_read'] = (int)$row['is_read'];
            $row['message'] = notification_message_for_view($row, $user_id);
            unset($row['extra_data']);
            $row['created_at_label'] = date('M d, Y g:i A', strtotime($row['created_at']));
            $notifications[] = $row;
            if (!$row['is_read']) $unread_count++;
        }
    }
    $stmt->close();
}

$mysqli->close();
echo json_encode([
    'success' => true,
    'unread_count' => $unread_count,
    'notifications' => $notifications,
], JSON_UNESCAPED_SLASHES);
