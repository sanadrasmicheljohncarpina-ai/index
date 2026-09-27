<?php
session_set_cookie_params([
    'lifetime'=>0, 'path'=>'/', 'domain'=>'', 'secure'=>false, 'httponly'=>true, 'samesite'=>'Lax'
]);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'principal') {
    http_response_code(401); echo json_encode(['ok'=>false,'error'=>'unauthenticated']); exit;
}
require_once 'db.php';
require_once dirname(__DIR__) . '/shared/system_settings_service.php';
require_once 'principal_notifications_feed.php';
require_once 'school_head_structure_gate.php';
try {
    $settings=get_school_head_settings($mysqli, 'principal');
    // Academic Structure / Academic Term gate (narrow-only).
    $settings=sh_gate_apply($settings, 'principal');
    $items=principal_build_notifications($mysqli,(int)$_SESSION['user_id'],$settings);
    echo json_encode(['ok'=>true,'notifications'=>$items,'count'=>count($items),'generated_at'=>date('c')]);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['ok'=>false,'error'=>'feed_failed']);
}
$mysqli->close();
