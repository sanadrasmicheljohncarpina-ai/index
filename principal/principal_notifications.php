<?php
/* =========================================================================
   principal_notifications.php — JSON feed for the dashboard bell
   -------------------------------------------------------------------------
   GET only. Returns the same list principal_dashboard.php renders on load,
   so the bell can refresh without a page reload.

   Response: {"ok":true,"items":[{"id":"...","text":"...","level":"..."}],"ts":1699999999}
   ========================================================================= */
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('Content-Type: application/json; charset=utf-8');
// Polled data must never be served from cache, or the bell will happily
// display a snapshot from ten minutes ago.
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'principal') {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthenticated']);
    exit;
}

require_once 'db.php';
require_once dirname(__DIR__) . '/shared/system_settings_service.php';
require_once 'principal_notifications_feed.php';

try {
    $items = principal_build_notifications($mysqli, (int)$_SESSION['user_id']);
    echo json_encode(['ok' => true, 'items' => $items, 'ts' => time()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'feed_failed']);
}

$mysqli->close();
