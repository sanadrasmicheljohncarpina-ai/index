<?php
/**
 * dashboard_counts.php
 * Real-time JSON endpoint for the admin dashboard.
 * Returns live user/eval counts + recent activity feed (role changes, evaluation
 * submissions, new users).
 *
 * Called by the admin dashboard every 10 seconds via fetch().
 * Also used by the notification poller to push role-change alerts.
 *
 * Pass ?mark_read=1 to mark all current role-change notifications as seen
 * (updates the session's "last seen" timestamp so the unread badge clears).
 *
 * ── System Logs box (feed_full) ───────────────────────────────────────────
 * Contains real-person activity from role/designation changes and evaluation
 * submissions. The displayed performer is the actual user tied to the event,
 * while the Source column names the specific module/process that created it.
 * "New user registered" events are intentionally excluded from feed_full;
 * they only show up in the bell dropdown (feed), not the audit box.
 */
session_start();

// This endpoint is polled by the dashboard every 10 seconds. Never allow a
// PHP warning/notice or a database exception to turn the response into HTML,
// because the browser would then fail JSON parsing and leave the dashboard's
// loading row on screen forever.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ob_start();

require_once 'db.php';

// Guard — only authenticated admins
if (
    empty($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin', 'registrar'])
) {
    http_response_code(403);
    dashboard_json(['error' => 'Unauthorized']);
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function dashboard_query($mysqli, $sql) {
    try {
        return $mysqli->query($sql);
    } catch (Throwable $e) {
        error_log('dashboard_counts query failed: ' . $e->getMessage());
        return false;
    }
}

function dashboard_json($payload) {
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function _humanize($value) {
    $value = trim((string)$value);
    if ($value === '') return '';
    return ucwords(str_replace(['_', '-'], ' ', strtolower($value)));
}

function _formatTargetType($row) {
    $role = trim((string)($row['target_role'] ?? ''));
    $designation = trim((string)($row['target_designation'] ?? ''));

    // Prefer the actual designation stored for the evaluated person. When it
    // is not available, use the person's actual role value from users and
    // humanize it. No fixed role-to-label lookup is needed here.
    if ($designation !== '') return $designation;
    return _humanize($role) ?: 'Employee';
}

function _formatEvaluationSource($row) {
    $evalType   = strtolower(trim((string)($row['eval_type'] ?? '')));
    $context    = strtolower(trim((string)($row['evaluation_context'] ?? '')));
    $peerGroup  = trim((string)($row['peer_group'] ?? ''));
    $formType   = trim((string)($row['form_type'] ?? ''));
    $evaluator  = trim((string)($row['evaluator_role'] ?? ''));
    $targetType = _formatTargetType($row);

    /*
     * The Source is derived from values already stored in the database:
     * eval_type / evaluation_context / peer_group / evaluator role.
     * There is intentionally no lookup table in PHP that translates fixed
     * values such as "student" => "Student Evaluation".
     */
    $prefix = '';

    if ($formType !== '') {
        $prefix = _humanize($formType);
    }

    if ($prefix === '') {
        if ($evalType !== '' && str_contains($evalType, '_peer')) {
            // Example database value: staff_peer -> Staff Peer Evaluation.
            $prefix = _humanize($evalType) . ' Evaluation';
        } elseif ($evalType !== '' && str_contains($evalType, '_to_')) {
            // Example database value: student_to_faculty. Keep the real
            // direction stored in the data while presenting it compactly.
            [$from] = explode('_to_', $evalType, 2);
            $fromLabel = _humanize($from);
            $prefix = ($fromLabel !== '' ? $fromLabel . ' Evaluation' : 'Evaluation');
        } elseif ($evalType !== '') {
            $typeLabel = _humanize($evalType);
            $prefix = $typeLabel !== '' ? $typeLabel . ' Evaluation' : '';
        }
    }

    // When the tracker only has a generic eval_type such as "student",
    // use the actual evaluator role/context instead of a hardcoded label.
    if ($prefix === '' || in_array($evalType, ['student','ea','peer'], true)) {
        $roleLabel = _humanize($evaluator);
        if ($peerGroup !== '' || $context === 'peer' || ($evalType !== '' && str_contains($evalType, 'peer'))) {
            $prefix = ($roleLabel !== '' ? $roleLabel . ' ' : '') . 'Peer Evaluation';
        } elseif ($roleLabel !== '') {
            $prefix = $roleLabel . ' Evaluation';
        } elseif ($evalType !== '') {
            $prefix = _humanize($evalType) . ' Evaluation';
        } else {
            $prefix = 'Evaluation';
        }
    }

    return trim($prefix) . ' · ' . $targetType;
}

/* ── Mark as read (bell "Mark all read" button) ─────────────────────────── */
if (isset($_GET['mark_read'])) {
    $_SESSION['rcl_last_seen'] = time();
}

/* ── Counts ──────────────────────────────────────────────────────────────── */
$counts = [
    'total'    => 0,
    'teacher'  => 0,
    'staff'    => 0,
    'students' => 0,
    'evals'    => 0,
    'evals_submitted' => 0,
];

$rows = [
    ['teacher',  "SELECT COUNT(*) AS c FROM users WHERE role='teacher'"],
    ['staff',    "SELECT COUNT(*) AS c FROM users WHERE role='staff'"],
    ['students', "SELECT COUNT(*) AS c FROM users WHERE role='student'"],
    ['evals',    "SELECT COUNT(*) AS c FROM evaluation_tracker"],
    ['evals_submitted', "SELECT COUNT(*) AS c FROM evaluation_tracker WHERE status IN ('submitted','approved','archived')"],
];
foreach ($rows as [$key, $sql]) {
    $r = dashboard_query($mysqli, $sql);
    if ($r) $counts[$key] = (int)($r->fetch_assoc()['c'] ?? 0);
}
$counts['total']         = $counts['teacher'] + $counts['staff'] + $counts['students'];
$counts['faculty_staff'] = $counts['teacher'] + $counts['staff']; // for the "Teacher" card

/* ── Recent Activity Feed ────────────────────────────────────────────────── */
// $feed holds EVERYTHING (role changes, evaluation submissions, new registrations) —
// this is what powers the bell dropdown.
//
// $auditFeed holds ONLY role_change + audit entries — this is what powers the
// System Logs box on the dashboard.

$feed = [];

/* ── 1. role_change_log → real person + real source ───────────────────── */
$rclExists = dashboard_query($mysqli, "SHOW TABLES LIKE 'role_change_log'");
if ($rclExists && $rclExists->num_rows > 0) {
    // Backward-compatible migration for older databases.
    $actorCol = dashboard_query($mysqli, "SHOW COLUMNS FROM role_change_log LIKE 'performed_by_id'");
    if ($actorCol && $actorCol->num_rows === 0) {
        dashboard_query($mysqli, "ALTER TABLE role_change_log ADD COLUMN performed_by_id INT UNSIGNED NULL AFTER user_id, ADD INDEX idx_performed_by (performed_by_id)");
    }
    $q = dashboard_query($mysqli, "
        SELECT rcl.user_id, rcl.old_role, rcl.new_role,
               rcl.old_designation, rcl.new_designation,
               rcl.changed_at, u.full_name,
               COALESCE(actor.full_name, u.full_name) AS actor_name
        FROM role_change_log rcl
        LEFT JOIN users u ON u.id = rcl.user_id
        LEFT JOIN users actor ON actor.id = rcl.performed_by_id
        ORDER BY rcl.changed_at DESC
        LIMIT 20
    ");
    if ($q) {
        while ($row = $q->fetch_assoc()) {
            $ts = strtotime($row['changed_at']);
            $person = $row['full_name'] ?: ('User #' . (int)$row['user_id']);
            $oldLabel = _formatRole($row['old_role'], $row['old_designation']);
            $newLabel = _formatRole($row['new_role'], $row['new_designation']);
            $text = $person . ' updated their role/designation from ' . $oldLabel . ' → ' . $newLabel;
            $actor = $row['actor_name'] ?: $person;

            $roleChanged = strtolower((string)$row['old_role']) !== strtolower((string)$row['new_role']);
            $designationChanged = trim((string)$row['old_designation']) !== trim((string)$row['new_designation']);
            if ($roleChanged && $designationChanged) {
                $source = 'Personnel Registry · Role & Designation Update';
            } elseif ($roleChanged) {
                $source = 'Personnel Registry · Role Update';
            } elseif ($designationChanged) {
                $source = 'Personnel Registry · Designation Update';
            } else {
                $source = 'Personnel Registry · Personnel Update';
            }

            $feed[] = [
                'id'      => 'rcl_' . $row['user_id'] . '_' . $ts,
                'type'    => 'role_change',
                'text'    => htmlspecialchars($text),
                'meta'    => htmlspecialchars($source),
                'actor'   => htmlspecialchars($actor),
                'time'    => _timeAgo($ts),
                'ts'      => $ts,
                'color'   => '#f59e0b',
                'icon'    => 'fa-user-pen',
                'user'    => htmlspecialchars($person),
                'new_role'=> htmlspecialchars($newLabel),
            ];
        }
    }
}

/* ── 2. evaluation_tracker → real evaluator + evaluated employee ───────── */
$levelFilter = $_GET['level'] ?? '';
$validLevels = ['junior_high', 'senior_high', 'college'];
if (!in_array($levelFilter, $validLevels, true)) $levelFilter = '';

$levelClause = $levelFilter ? " AND et.level = '" . $mysqli->real_escape_string($levelFilter) . "'" : "";
$evSubQ = dashboard_query($mysqli, "
    SELECT et.id, et.eval_bucket, et.form_type, et.eval_type, et.evaluation_context, et.peer_group, et.status, et.submitted_at, et.level,
           evaluator.full_name AS evaluator_name,
           evaluator.role AS evaluator_role,
           target.full_name AS target_name,
           target.role AS target_role,
           target.designation AS target_designation
    FROM evaluation_tracker et
    LEFT JOIN users evaluator ON evaluator.id = et.evaluator_id
    LEFT JOIN users target ON target.id = et.target_user_id
    WHERE et.status IN ('submitted','approved','archived')" . $levelClause . "
    ORDER BY et.submitted_at DESC
    LIMIT 10
");
if ($evSubQ) {
    while ($row = $evSubQ->fetch_assoc()) {
        $ts = strtotime($row['submitted_at']);
        $evaluator = $row['evaluator_name'] ?: ('User #' . (int)($row['evaluator_id'] ?? 0));
        $target = $row['target_name'] ?: ('User #' . (int)($row['target_user_id'] ?? 0));
        $bucket = trim((string)($row['eval_bucket'] ?? '')) ?: 'Employee';
        $levelLabel = _formatLevel($row['level']);
        $source = _formatEvaluationSource($row);

        $feed[] = [
            'id'    => 'evt_' . $row['id'],
            'type'  => 'audit',
            'text'  => htmlspecialchars($evaluator . ' evaluated ' . $target),
            'actor' => htmlspecialchars($evaluator),
            'meta'  => htmlspecialchars($source),
            'time'  => _timeAgo($ts),
            'ts'    => $ts,
            'color' => '#14b8a6',
            'icon'  => 'fa-file-circle-check',
            'level' => $row['level'],
        ];
    }
}

/* ── 3. Synthetic from users → bell dropdown only, NOT the audit box ── */
$q = dashboard_query($mysqli, "SELECT full_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
if ($q) {
    while ($row = $q->fetch_assoc()) {
        $ts = strtotime($row['created_at']);
        $feed[] = [
            'id'    => 'usr_' . md5($row['full_name'] . $ts),
            'type'  => 'new_user',
            'text'  => 'New ' . ucfirst($row['role']) . ' account registered: ' . htmlspecialchars($row['full_name']),
            'meta'  => 'User Registration',
            'time'  => _timeAgo($ts),
            'ts'    => $ts,
            'color' => '#22c55e',
            'icon'  => 'fa-user-plus',
        ];
    }
}

// Sort combined feed by timestamp descending.
usort($feed, fn($a, $b) => $b['ts'] - $a['ts']);

// Bell dropdown: top 6 of EVERYTHING (role changes, submissions, new registrations).
$feedShort = array_slice($feed, 0, 6);

// System Logs box: role changes + evaluation activity; new-user registrations
// remain in the bell feed and are deliberately left out here.
$auditFeed = array_values(array_filter($feed, fn($e) => in_array($e['type'], ['role_change', 'audit'])));
$auditFeed = array_slice($auditFeed, 0, 12);

/* ── Unread role-change count (for badge) ── */
$unreadRoleChanges = 0;
if ($rclExists && $rclExists->num_rows > 0) {
    $lastSeen = $_SESSION['rcl_last_seen'] ?? 0;
    $q = dashboard_query($mysqli, "SELECT COUNT(*) AS c FROM role_change_log WHERE UNIX_TIMESTAMP(changed_at) > " . (int)$lastSeen);
    if ($q) $unreadRoleChanges = (int)($q->fetch_assoc()['c'] ?? 0);
}

dashboard_json([
    'counts'              => $counts,
    'feed'                => $feedShort,
    'feed_full'           => $auditFeed,
    'unread_role_changes' => $unreadRoleChanges,
    'server_time'         => time(),
]);

/* ── Helpers ── */
function _timeAgo(int $ts): string {
    $diff = time() - $ts;
    if ($diff < 60)   return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $ts);
}
function _formatRole(string $role, ?string $designation): string {
    if (!empty($designation)) return ucwords(str_replace('_', ' ', $designation));
    return ucfirst($role);
}
function _formatLevel(?string $level): string {
    switch ($level) {
        case 'junior_high': return 'Junior High';
        case 'senior_high': return 'Senior High';
        case 'college':     return 'College';
        default:            return '';
    }
}