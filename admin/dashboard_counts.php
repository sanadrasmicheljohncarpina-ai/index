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
 * ── System Audits box (feed_full) ──────────────────────────────────────────
 * Only ever contains two kinds of entries:
 *   - type=role_change → meta "Admin Module"     (a user changed their OWN
 *                         role from their own dashboard — role_change_log)
 *   - type=audit       → meta "System Automator" (evaluation submissions —
 *                         who submitted, plus a running total — pulled live
 *                         from evaluation_tracker)
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

/* ── Recent Activity Feed ──────────────────────────────────────────────────
 * System Logs now use:
 *   Date & Time | Action | Performed By | Details
 *
 * The feed combines available real activity sources:
 *   - role_change_log: role/designation changes
 *   - audit_log: actual actor for logged administrative actions, when available
 *   - evaluation_tracker: submitted evaluations with evaluator + target details
 *   - users: recent account creations
 *
 * No generic "Admin Module" / "System Automator" values are shown as Details.
 */
$feed = [];

/* Cache audit_log actors where the table is available. */
$auditActors = [];
$auditExists = false;
$al = dashboard_query($mysqli, "SHOW TABLES LIKE 'audit_log'");
if ($al && $al->num_rows > 0) {
    $auditExists = true;
    $aq = dashboard_query($mysqli, "
        SELECT action, performed_by, created_at
        FROM audit_log
        ORDER BY created_at DESC
        LIMIT 100
    ");
    if ($aq) {
        while ($ar = $aq->fetch_assoc()) {
            $key = trim((string)($ar['action'] ?? ''));
            if ($key !== '' && !isset($auditActors[$key])) {
                $auditActors[$key] = $ar['performed_by'] ?: 'System';
            }
        }
    }
}

/* ── 1. Role/designation changes ─────────────────────────────────────────── */
$rclExists = dashboard_query($mysqli, "SHOW TABLES LIKE 'role_change_log'");
if ($rclExists && $rclExists->num_rows > 0) {
    $q = dashboard_query($mysqli, "
        SELECT rcl.id, rcl.user_id, rcl.old_role, rcl.new_role,
               rcl.old_designation, rcl.new_designation,
               rcl.changed_at, u.full_name
        FROM role_change_log rcl
        LEFT JOIN users u ON u.id = rcl.user_id
        ORDER BY rcl.changed_at DESC
        LIMIT 20
    ");
    if ($q) {
        while ($row = $q->fetch_assoc()) {
            $ts = strtotime($row['changed_at']);
            $fullName = trim((string)($row['full_name'] ?? '')) ?: 'User #' . (int)$row['user_id'];

            $oldLabel = _formatRole((string)$row['old_role'], $row['old_designation'] ?? null);
            $newLabel = _formatRole((string)$row['new_role'], $row['new_designation'] ?? null);

            $actionText = "{$fullName} role updated: {$oldLabel} → {$newLabel}";
            $actor = $auditActors[$actionText] ?? null;

            /* Older logs used "changed role:" in audit_log. */
            if (!$actor) {
                $legacyAction = "{$fullName} changed role: {$oldLabel} → {$newLabel}";
                $actor = $auditActors[$legacyAction] ?? null;
            }

            $feed[] = [
                'id'     => 'rcl_' . (int)$row['id'],
                'type'   => 'role_change',
                'text'   => $actionText,
                'actor'  => $actor ?: ($fullName),
                'meta'   => 'Role: ' . $newLabel,
                'time'   => _timeAgo($ts),
                'ts'     => $ts,
                'color'  => '#f59e0b',
                'icon'   => 'fa-user-pen',
                'user'   => $fullName,
            ];
        }
    }
}

/* ── 2. Evaluation submissions ───────────────────────────────────────────── */
$levelFilter = $_GET['level'] ?? '';
$validLevels = ['junior_high', 'senior_high', 'college'];
if (!in_array($levelFilter, $validLevels, true)) $levelFilter = '';

$levelClause = $levelFilter
    ? " AND et.level = '" . $mysqli->real_escape_string($levelFilter) . "'"
    : "";

$evSubQ = dashboard_query($mysqli, "
    SELECT et.id, et.eval_bucket, et.form_type, et.eval_type, et.status,
           et.submitted_at, et.level, et.evaluator_id, et.student_id, et.target_user_id,
           evaluator.full_name AS evaluator_name,
           target.full_name AS target_name
    FROM evaluation_tracker et
    LEFT JOIN users evaluator ON evaluator.id = COALESCE(et.evaluator_id, et.student_id)
    LEFT JOIN users target ON target.id = et.target_user_id
    WHERE et.status IN ('submitted','approved','archived')" . $levelClause . "
    ORDER BY et.submitted_at DESC
    LIMIT 12
");
if ($evSubQ) {
    while ($row = $evSubQ->fetch_assoc()) {
        $ts = strtotime($row['submitted_at']);
        $evaluatorName = trim((string)($row['evaluator_name'] ?? '')) ?: 'Student';
        $targetName = trim((string)($row['target_name'] ?? '')) ?: '';
        $bucket = trim((string)($row['eval_bucket'] ?? '')) ?: 'Employee';
        $evalType = trim((string)($row['eval_type'] ?? ''));
        $levelLabel = _formatLevel((string)($row['level'] ?? ''));

        $targetDetails = $targetName
            ? 'Evaluated: ' . $targetName
            : ($evalType ? 'Type: ' . ucwords(str_replace('_', ' ', $evalType)) : 'Performance evaluation');

        if ($levelLabel) {
            $targetDetails .= ' · ' . $levelLabel;
        }

        $feed[] = [
            'id'     => 'evt_' . (int)$row['id'],
            'type'   => 'evaluation_submitted',
            'text'   => 'Evaluation submitted · ' . $bucket,
            'actor'  => $evaluatorName,
            'meta'   => $targetDetails,
            'time'   => _timeAgo($ts),
            'ts'     => $ts,
            'color'  => '#14b8a6',
            'icon'   => 'fa-file-circle-check',
            'level'  => $row['level'],
        ];
    }
}

/* ── 3. Evaluation summary ───────────────────────────────────────────────── */
$totalSubQ = dashboard_query($mysqli, "
    SELECT COUNT(*) AS c, MAX(submitted_at) AS latest
    FROM evaluation_tracker
    WHERE status IN ('submitted','approved','archived')" . $levelClause . "
");
if ($totalSubQ) {
    $tRow = $totalSubQ->fetch_assoc();
    $totalSubmitted = (int)($tRow['c'] ?? 0);
    if ($totalSubmitted > 0) {
        $ts = $tRow['latest'] ? strtotime($tRow['latest']) : time();
        $levelSuffix = $levelFilter ? ' · ' . _formatLevel($levelFilter) : '';

        $feed[] = [
            'id'     => 'evt_total_' . $totalSubmitted . '_' . $levelFilter,
            'type'   => 'evaluation_summary',
            'text'   => 'Evaluation submission summary',
            'actor'  => 'System',
            'meta'   => $totalSubmitted . ' evaluation' . ($totalSubmitted === 1 ? '' : 's') . ' submitted so far' . $levelSuffix,
            'time'   => _timeAgo($ts),
            'ts'     => $ts,
            'color'  => '#0ea5e9',
            'icon'   => 'fa-chart-line',
        ];
    }
}

/* ── 4. Recent account creations ─────────────────────────────────────────── */
$q = dashboard_query($mysqli, "
    SELECT id, full_name, role, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 8
");
if ($q) {
    while ($row = $q->fetch_assoc()) {
        $ts = strtotime($row['created_at']);
        $name = trim((string)($row['full_name'] ?? '')) ?: 'Unnamed user';
        $role = ucfirst((string)($row['role'] ?? 'user'));

        $feed[] = [
            'id'     => 'usr_' . (int)$row['id'],
            'type'   => 'account_created',
            'text'   => 'Account created',
            'actor'  => 'System Admin',
            'meta'   => $role . ' account: ' . $name,
            'time'   => _timeAgo($ts),
            'ts'     => $ts,
            'color'  => '#22c55e',
            'icon'   => 'fa-user-plus',
        ];
    }
}

/* Sort combined feed by timestamp descending. */
usort($feed, fn($a, $b) => $b['ts'] - $a['ts']);

$feedShort = array_slice($feed, 0, 6);
$auditFeed = array_slice($feed, 0, 12);

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