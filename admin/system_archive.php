<?php
// admin/system_archive.php
// Secure evaluation-year archive. Super Admin has full access; Admin requires
// the system_archive edit permission. Questions, users, assignments and
// system settings are intentionally preserved. Evaluation records are snapshotted
// into system_archives and removed from the live evaluation tables so the next
// evaluation cycle starts clean.
session_start();
require_once 'db.php';
require_once 'permissions.php';

if (empty($_SESSION['user_id'])) {
    header('Location: admin_login.php');
    exit;
}

mysqli_report(MYSQLI_REPORT_OFF);

// Create the archive store automatically for existing installations.
$mysqli->query("CREATE TABLE IF NOT EXISTS system_archives (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    period_id INT UNSIGNED NOT NULL,
    period_label VARCHAR(150) NOT NULL,
    school_year VARCHAR(30) NOT NULL,
    archived_by INT UNSIGNED DEFAULT NULL,
    archived_by_name VARCHAR(150) DEFAULT NULL,
    archived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    restored_at DATETIME DEFAULT NULL,
    restored_by INT UNSIGNED DEFAULT NULL,
    status ENUM('archived','restored') NOT NULL DEFAULT 'archived',
    record_count INT UNSIGNED NOT NULL DEFAULT 0,
    summary_json LONGTEXT DEFAULT NULL,
    payload_json LONGTEXT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_system_archive_period (period_id),
    KEY idx_system_archive_year (school_year),
    KEY idx_system_archive_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

function archive_allowed(mysqli $db): bool {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'superadmin') return true;
    return $role === 'admin' && admin_can_edit($db, 'system_archive');
}

$allowed = archive_allowed($mysqli);
if (!$allowed) {
    http_response_code(403);
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function json_rows(mysqli_result|false $res): array {
    $rows = [];
    if ($res) while ($row = $res->fetch_assoc()) $rows[] = $row;
    return $rows;
}
function rows_by_ids(mysqli $db, string $table, string $key, array $ids): array {
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) return [];
    $in = implode(',', $ids);
    return json_rows($db->query("SELECT * FROM `$table` WHERE `$key` IN ($in)"));
}
function tracker_ids(array $rows): array {
    return array_values(array_unique(array_map(fn($r) => (int)$r['id'], $rows)));
}
function count_rows(array $data): int { return count($data); }

$flash = null;
$flashType = 'ok';
$viewArchive = null;

// ── POST: ARCHIVE / RESTORE ────────────────────────────────────────────────
if ($allowed && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'archive') {
        $periodId = (int)($_POST['period_id'] ?? 0);
        $stmt = $mysqli->prepare("SELECT * FROM evaluation_periods WHERE id=? LIMIT 1");
        $stmt->bind_param('i', $periodId);
        $stmt->execute();
        $period = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$period) {
            $flash = 'The selected evaluation period could not be found.';
            $flashType = 'err';
        } else {
            $stmt = $mysqli->prepare("SELECT id, status FROM system_archives WHERE period_id=? LIMIT 1");
            $stmt->bind_param('i', $periodId);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing) {
                $flash = 'This academic year already has an archive record.';
                $flashType = 'err';
            } else {
                // The modern evaluation_tracker table is the source of truth.
                $trackers = json_rows($mysqli->query("SELECT * FROM evaluation_tracker WHERE period_id=" . $periodId));
                $trackerIds = tracker_ids($trackers);

                $trackerIn = $trackerIds ? implode(',', $trackerIds) : '0';
                $submissions = json_rows($mysqli->query("SELECT * FROM evaluation_submissions WHERE period_id=" . $periodId));
                $results = json_rows($mysqli->query("SELECT * FROM evaluation_results WHERE period_id=" . $periodId . ($trackerIds ? " OR tracker_id IN ($trackerIn)" : '')));
                $peerSubmissions = json_rows($mysqli->query("SELECT * FROM peer_evaluation_submissions WHERE period_id=" . $periodId));
                $peerIds = array_map(fn($r)=>(int)$r['id'], $peerSubmissions);
                $peerIn = $peerIds ? implode(',', $peerIds) : '0';
                $peerResults = $peerIds ? json_rows($mysqli->query("SELECT * FROM peer_evaluation_results WHERE submission_id IN ($peerIn) OR period_id=" . $periodId)) : json_rows($mysqli->query("SELECT * FROM peer_evaluation_results WHERE period_id=" . $periodId));
                $questionnaireAnswers = $trackerIds ? rows_by_ids($mysqli, 'questionnaire_answers', 'tracker_id', $trackerIds) : [];
                $evaluationAnswers = $trackerIds ? rows_by_ids($mysqli, 'evaluation_answers', 'tracker_id', $trackerIds) : [];
                $reminders = json_rows($mysqli->query("SELECT * FROM evaluation_reminders WHERE period_id=" . $periodId));
                $reports = json_rows($mysqli->query("SELECT * FROM analytics_reports WHERE period=" . "'" . $mysqli->real_escape_string($period['school_year']) . "'"));

                // Notifications created for this evaluation period are archived and cleared.
                $periodIdStr = (string)$periodId;
                $schoolYearEsc = $mysqli->real_escape_string((string)$period['school_year']);
                $notifications = json_rows($mysqli->query("SELECT * FROM notifications WHERE (extra_data LIKE '%\"period_id\":$periodIdStr%' OR extra_data LIKE '%\"period_id\":\"$periodIdStr\"%' OR message LIKE '%$schoolYearEsc%')"));

                // Activity logs stay live for auditability; we snapshot only the evaluation-window logs.
                $activity = [];
                if (!empty($period['date_start']) && !empty($period['date_end'])) {
                    $start = $mysqli->real_escape_string($period['date_start'] . ' 00:00:00');
                    $end = $mysqli->real_escape_string($period['date_end'] . ' 23:59:59');
                    $activity = json_rows($mysqli->query("SELECT * FROM activity_log WHERE created_at BETWEEN '$start' AND '$end' AND (action_text LIKE '%evaluat%' OR action_text LIKE '%submission%' OR action_text LIKE '%questionnaire%') ORDER BY created_at"));
                }

                $snapshot = [
                    'evaluation_tracker' => $trackers,
                    'questionnaire_answers' => $questionnaireAnswers,
                    'evaluation_answers' => $evaluationAnswers,
                    'evaluation_submissions' => $submissions,
                    'evaluation_results' => $results,
                    'peer_evaluation_submissions' => $peerSubmissions,
                    'peer_evaluation_results' => $peerResults,
                    'evaluation_reminders' => $reminders,
                    'analytics_reports' => $reports,
                    'notifications' => $notifications,
                    'activity_log_snapshot' => $activity,
                ];
                $counts = [];
                $total = 0;
                foreach ($snapshot as $table => $rows) {
                    $counts[$table] = count_rows($rows);
                    if ($table !== 'activity_log_snapshot') $total += count_rows($rows);
                }

                $payload = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $summary = json_encode($counts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $byId = (int)$_SESSION['user_id'];
                $byName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Administrator';

                try {
                    $mysqli->begin_transaction();

                    $ins = $mysqli->prepare("INSERT INTO system_archives (period_id, period_label, school_year, archived_by, archived_by_name, record_count, summary_json, payload_json) VALUES (?,?,?,?,?,?,?,?)");
                    $ins->bind_param('issisiss', $periodId, $period['period_label'], $period['school_year'], $byId, $byName, $total, $summary, $payload);
                    if (!$ins->execute()) throw new Exception('Could not create archive record: ' . $ins->error);
                    $archiveId = $ins->insert_id;
                    $ins->close();

                    // Delete dependent rows first. Questions, users, assignments, system settings
                    // and system logs remain intact.
                    $mysqli->query("DELETE FROM questionnaire_answers WHERE tracker_id IN ($trackerIn)");
                    $mysqli->query("DELETE FROM evaluation_answers WHERE tracker_id IN ($trackerIn)");
                    $mysqli->query("DELETE FROM evaluation_results WHERE period_id=$periodId" . ($trackerIds ? " OR tracker_id IN ($trackerIn)" : ''));
                    $mysqli->query("DELETE FROM evaluation_tracker WHERE period_id=$periodId");
                    $mysqli->query("DELETE FROM evaluation_submissions WHERE period_id=$periodId");
                    $mysqli->query("DELETE FROM peer_evaluation_results WHERE period_id=$periodId" . ($peerIds ? " OR submission_id IN ($peerIn)" : ''));
                    $mysqli->query("DELETE FROM peer_evaluation_submissions WHERE period_id=$periodId");
                    $mysqli->query("DELETE FROM evaluation_reminders WHERE period_id=$periodId");
                    $mysqli->query("DELETE FROM analytics_reports WHERE period=" . "'" . $mysqli->real_escape_string($period['school_year']) . "'");
                    if ($notifications) $mysqli->query("DELETE FROM notifications WHERE (extra_data LIKE '%\"period_id\":$periodIdStr%' OR extra_data LIKE '%\"period_id\":\"$periodIdStr\"%' OR message LIKE '%$schoolYearEsc%')");
                    $mysqli->query("UPDATE evaluation_periods SET is_active=0, tracking_enabled=0 WHERE id=$periodId");

                    $actor = $mysqli->real_escape_string((string)$byName);
                    $actionText = $mysqli->real_escape_string("Archived evaluation data for {$period['school_year']} (Archive #$archiveId)");
                    $mysqli->query("INSERT INTO activity_log (actor_name, actor_type, action_text, icon, color) VALUES ('$actor','admin','$actionText','fa-box-archive','#2563EB')");

                    if ($mysqli->errno) throw new Exception($mysqli->error);
                    $mysqli->commit();

                    $flash = "Archive successful. {$period['school_year']} is now a fresh evaluation cycle with live submissions cleared.";
                    $flashType = 'ok';
                } catch (Throwable $ex) {
                    $mysqli->rollback();
                    // Remove a partially-created archive record if the transaction was rolled back by a driver without DDL.
                    $flash = 'Archive failed: ' . $ex->getMessage();
                    $flashType = 'err';
                }
            }
        }
    }

    if ($action === 'restore') {
        $archiveId = (int)($_POST['archive_id'] ?? 0);
        $stmt = $mysqli->prepare("SELECT * FROM system_archives WHERE id=? AND status='archived' LIMIT 1");
        $stmt->bind_param('i', $archiveId);
        $stmt->execute();
        $archive = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$archive) {
            $flash = 'The archive could not be found or has already been restored.';
            $flashType = 'err';
        } else {
            $payload = json_decode($archive['payload_json'], true);
            if (!is_array($payload)) {
                $flash = 'The archive payload is invalid.';
                $flashType = 'err';
            } else {
                $live = $mysqli->query("SELECT COUNT(*) c FROM evaluation_tracker WHERE period_id=" . (int)$archive['period_id']);
                $liveCount = $live ? (int)$live->fetch_assoc()['c'] : 0;
                if ($liveCount > 0) {
                    $flash = 'Restore stopped for safety because this period already contains live evaluation records.';
                    $flashType = 'err';
                } else {
                    try {
                        $mysqli->begin_transaction();
                        $order = [
                            'evaluation_tracker',
                            'questionnaire_answers',
                            'evaluation_answers',
                            'evaluation_submissions',
                            'evaluation_results',
                            'peer_evaluation_submissions',
                            'peer_evaluation_results',
                            'evaluation_reminders',
                            'analytics_reports',
                            'notifications',
                        ];
                        foreach ($order as $table) {
                            $rows = $payload[$table] ?? [];
                            foreach ($rows as $row) {
                                if (!$row) continue;
                                $columns = array_keys($row);
                                $quotedCols = '`' . implode('`,`', array_map(fn($c)=>str_replace('`','``',$c), $columns)) . '`';
                                $placeholders = implode(',', array_fill(0, count($columns), '?'));
                                $sql = "INSERT IGNORE INTO `$table` ($quotedCols) VALUES ($placeholders)";
                                $stmt = $mysqli->prepare($sql);
                                if (!$stmt) continue;
                                $types = '';
                                $values = [];
                                foreach ($columns as $c) {
                                    $v = $row[$c];
                                    $types .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
                                    $values[] = $v;
                                }
                                $bind = [$types];
                                foreach ($values as $k=>$v) $bind[] = &$values[$k];
                                call_user_func_array([$stmt, 'bind_param'], $bind);
                                $stmt->execute();
                                $stmt->close();
                            }
                        }
                        // Make the period visible only after the restore has completed.
                        $stmt = $mysqli->prepare("UPDATE evaluation_periods SET is_active=1 WHERE id=?");
                        $pid = (int)$archive['period_id'];
                        $stmt->bind_param('i', $pid);
                        $stmt->execute();
                        $stmt->close();
                        $byId = (int)$_SESSION['user_id'];
                        $stmt = $mysqli->prepare("UPDATE system_archives SET status='restored', restored_at=NOW(), restored_by=? WHERE id=?");
                        $stmt->bind_param('ii', $byId, $archiveId);
                        $stmt->execute();
                        $stmt->close();
                        $actor = $mysqli->real_escape_string($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Administrator');
                        $actionText = $mysqli->real_escape_string("Restored evaluation archive #$archiveId for {$archive['school_year']}");
                        $mysqli->query("INSERT INTO activity_log (actor_name, actor_type, action_text, icon, color) VALUES ('$actor','admin','$actionText','fa-box-open','#0F9F6E')");
                        $mysqli->commit();
                        $flash = "Archive #$archiveId was restored successfully.";
                        $flashType = 'ok';
                    } catch (Throwable $ex) {
                        $mysqli->rollback();
                        $flash = 'Restore failed: ' . $ex->getMessage();
                        $flashType = 'err';
                    }
                }
            }
        }
    }
}

// ── View one archive ───────────────────────────────────────────────────────
if (isset($_GET['view'])) {
    $id = (int)$_GET['view'];
    $stmt = $mysqli->prepare("SELECT * FROM system_archives WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $viewArchive = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($viewArchive) {
        $viewArchive['summary'] = json_decode($viewArchive['summary_json'] ?? '{}', true) ?: [];
    }
}

$periods = [];
// Only show legitimate archive candidates: valid academic-year/term values,
// at least one live evaluation record, and nothing already archived.
$res = $mysqli->query("
    SELECT ep.id, ep.period_label, ep.semester, ep.school_year, ep.is_active,
           ep.start_date, ep.end_date, COUNT(et.id) AS live_count
    FROM evaluation_periods ep
    INNER JOIN evaluation_tracker et ON et.period_id = ep.id
    LEFT JOIN system_archives sa ON sa.period_id = ep.id AND sa.status = 'archived'
    WHERE sa.id IS NULL
      AND ep.school_year REGEXP '^[0-9]{4}-[0-9]{4}$'
      AND ep.semester IN ('1st Semester','2nd Semester','Summer','School Year')
    GROUP BY ep.id, ep.period_label, ep.semester, ep.school_year, ep.is_active, ep.start_date, ep.end_date
    HAVING COUNT(et.id) > 0
    ORDER BY ep.school_year DESC, FIELD(ep.semester,'1st Semester','2nd Semester','Summer','School Year'), ep.id DESC
");
if ($res) while ($r = $res->fetch_assoc()) $periods[] = $r;
$archives = [];
$res = $mysqli->query("SELECT * FROM system_archives ORDER BY archived_at DESC, id DESC");
if ($res) while ($r = $res->fetch_assoc()) {
    $r['summary'] = json_decode($r['summary_json'] ?? '{}', true) ?: [];
    $archives[] = $r;
}
$activePeriod = null;
foreach ($periods as $p) if ((int)$p['is_active'] === 1) { $activePeriod = $p; break; }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>System Archive — Evaluation System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="admin_ui_theme.css">
<link rel="stylesheet" href="admin_compact_ui.css">
<style>
:root{--bg:#F5F8FC;--card:#fff;--line:#D8E5F4;--text:#102746;--muted:#67819E;--blue:#2563EB;--blue-soft:#EEF4FF;--green:#0F9F6E;--green-soft:#EAF9F2;--amber:#C77A08;--amber-soft:#FFF7E6;--red:#D6455D;--red-soft:#FFF0F2;--shadow:0 3px 14px rgba(30,82,144,.08)}
*{box-sizing:border-box} body{margin:0;background:var(--bg);color:var(--text);font:13px/1.5 Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;-webkit-font-smoothing:antialiased}.wrap{max-width:1240px;margin:0 auto;padding:24px 28px 44px}.back{display:inline-flex;gap:8px;align-items:center;color:var(--muted);text-decoration:none;font-size:12px;font-weight:600;margin-bottom:15px}.hero{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:16px}.hero h1{font-size:25px;margin:0 0 3px;letter-spacing:-.025em}.hero p{margin:0;color:var(--muted);font-size:13px}.badge{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border-radius:999px;border:1px solid #C9D9EF;background:#fff;color:var(--blue);font-weight:700;font-size:11px}.grid{display:grid;grid-template-columns:1.25fr .75fr;gap:14px}.card{background:var(--card);border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow);padding:17px}.card h2{font-size:14px;margin:0 0 4px}.sub{color:var(--muted);font-size:11.5px}.label{display:block;font-size:11px;font-weight:700;color:#405A76;margin:14px 0 6px;text-transform:uppercase;letter-spacing:.04em}.select{width:100%;padding:9px 11px;border:1px solid #B9CDE5;border-radius:8px;background:#fff;color:var(--text);font:600 12px Inter}.actions{display:flex;gap:8px;align-items:center;margin-top:13px;flex-wrap:wrap}.btn{border:1px solid #B9CDE5;background:#fff;border-radius:8px;padding:9px 12px;font:700 12px Inter;color:var(--text);cursor:pointer}.btn.primary{border-color:var(--blue);background:var(--blue);color:#fff}.btn.danger{border-color:#F1B7C0;background:#FFF6F7;color:var(--red)}.btn:disabled{opacity:.45;cursor:not-allowed}.warn{display:flex;gap:8px;align-items:flex-start;margin-top:12px;padding:9px 10px;background:var(--amber-soft);border:1px solid #F5D48A;border-radius:8px;color:#8D5D00;font-size:11.5px}.success{display:flex;gap:8px;align-items:flex-start;padding:9px 10px;background:var(--green-soft);border:1px solid #BDE9D4;border-radius:8px;color:#08734F;font-size:11.5px}.archive-table{margin-top:14px;border:1px solid var(--line);border-radius:10px;overflow:auto;background:#fff}.archive-table table{width:100%;border-collapse:collapse;min-width:760px}.archive-table th,.archive-table td{padding:10px 11px;border-bottom:1px solid #EAF0F7;text-align:left;font-size:11.5px;white-space:nowrap}.archive-table th{background:#F8FAFC;color:#49647F;font-size:10.5px;text-transform:uppercase;letter-spacing:.05em}.archive-table tr:last-child td{border-bottom:0}.status{display:inline-flex;padding:4px 8px;border-radius:999px;font-size:10px;font-weight:800}.status.archived{background:var(--green-soft);color:var(--green)}.status.restored{background:var(--blue-soft);color:var(--blue)}.empty{padding:25px;text-align:center;color:var(--muted)}.detail{margin-top:14px}.detail-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}.mini{padding:11px;border:1px solid var(--line);background:#FBFDFF;border-radius:8px}.mini b{display:block;font-size:16px}.mini span{color:var(--muted);font-size:10.5px}.notice{margin-bottom:14px;padding:10px 12px;border-radius:9px;border:1px solid #C9D9EF;background:var(--blue-soft);color:#2855A4;font-size:11.5px;display:flex;gap:8px}.restricted{max-width:620px;margin:60px auto;text-align:center}.restricted .icon{font-size:30px;color:var(--red);margin-bottom:8px}@media(max-width:900px){.grid{grid-template-columns:1fr}.detail-grid{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body class="feature-compact">
<div class="wrap">
<a class="back" href="admin_dashboard.php"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
<?php if (!$allowed): ?>
<div class="card restricted"><div class="icon"><i class="fa-solid fa-lock"></i></div><h1>System Archive is restricted</h1><p class="sub">Only the Super Admin, or an Admin explicitly granted the <strong>System Archive</strong> edit permission, can archive or restore evaluation data.</p></div>
<?php else: ?>
<div class="hero"><div><h1>System Archive</h1><p>Archive the selected academic year and prepare the evaluation system for a fresh cycle.</p></div><div class="badge"><i class="fa-solid fa-box-archive"></i> Protected Archive</div></div>
<?php if ($flash): ?><div class="notice" style="border-color:<?= $flashType==='ok'?'#BDE9D4':'#F1B7C0' ?>;background:<?= $flashType==='ok'?'#EAF9F2':'#FFF0F2' ?>;color:<?= $flashType==='ok'?'#08734F':'#A62A42' ?>;"><i class="fa-solid <?= $flashType==='ok'?'fa-circle-check':'fa-circle-exclamation' ?>"></i><span><?= e($flash) ?></span></div><?php endif; ?>
<div class="grid">
<div class="card">
<h2>Archive This Year Evaluation</h2><div class="sub">Archive all evaluation submissions and related data for the selected academic year.</div>
<label class="label">Academic Year</label>
<select id="periodSelect" class="select">
<option value="">Select an evaluation period…</option>
<?php foreach ($periods as $p): ?><option value="<?= (int)$p['id'] ?>" data-year="<?= e($p['school_year']) ?>" data-label="<?= e($p['period_label']) ?>" data-live="<?= (int)$p['live_count'] ?>" <?= (!empty($viewArchive) && (int)$viewArchive['period_id']===(int)$p['id'])?'selected':'' ?>><?= e($p['school_year']) ?> — <?= e($p['semester']) ?> (<?= number_format((int)$p['live_count']) ?> live evaluation<?= (int)$p['live_count'] === 1 ? '' : 's' ?>)</option><?php endforeach; ?>
</select>
<div class="actions"><form method="POST" id="archiveForm" onsubmit="return confirmArchive()"><input type="hidden" name="action" value="archive"><input type="hidden" name="period_id" id="archivePeriodId"><button class="btn primary" type="submit" id="archiveBtn" disabled><i class="fa-solid fa-box-archive"></i> Archive This Year</button></form></div>
<div class="warn"><i class="fa-solid fa-triangle-exclamation"></i><span><strong>This action cannot be undone from the live dashboards.</strong> Questions, users, assignments, permissions, system settings, and system logs remain intact.</span></div>
</div>
<div class="card">
<h2>Current System State</h2><div class="sub">A quick check before you archive.</div>
<div class="detail-grid" style="grid-template-columns:repeat(2,1fr);margin-top:13px">
<div class="mini"><span>Active evaluation period</span><b><?= $activePeriod ? e($activePeriod['school_year']) : 'None' ?></b></div>
<div class="mini"><span>Live evaluations</span><b><?= number_format(array_sum(array_map(fn($p)=>(int)$p['live_count'],$periods))) ?></b></div>
</div>
<div class="success" style="margin-top:12px"><i class="fa-solid fa-shield-halved"></i><span>Archived data is stored separately and can be viewed later without deleting the original questions, accounts, or system configuration.</span></div>
</div>
</div>

<?php if ($viewArchive): ?>
<div class="card detail"><h2>Archived Evaluation Data — <?= e($viewArchive['school_year']) ?></h2><div class="sub">Archive #<?= (int)$viewArchive['id'] ?> · Archived <?= e($viewArchive['archived_at']) ?> by <?= e($viewArchive['archived_by_name']) ?> · Status: <span class="status <?= e($viewArchive['status']) ?>"><?= ucfirst(e($viewArchive['status'])) ?></span></div>
<div class="detail-grid" style="margin-top:12px">
<?php foreach (($viewArchive['summary']??[]) as $k=>$v): ?><div class="mini"><span><?= e(ucwords(str_replace('_',' ',$k))) ?></span><b><?= number_format((int)$v) ?></b></div><?php endforeach; ?>
</div>
<div class="actions"><a class="btn" href="system_archive.php"><i class="fa-solid fa-arrow-left"></i> Back to Archive</a><?php if ($viewArchive['status']==='archived'): ?><form method="POST" onsubmit="return confirmRestore()"><input type="hidden" name="action" value="restore"><input type="hidden" name="archive_id" value="<?= (int)$viewArchive['id'] ?>"><button class="btn danger" type="submit"><i class="fa-solid fa-box-open"></i> Restore This Archive</button></form><?php endif; ?></div>
</div>
<?php endif; ?>

<div class="card" style="margin-top:14px"><div style="display:flex;justify-content:space-between;align-items:end;gap:12px"><div><h2>Archived Records</h2><div class="sub">View or restore archived evaluation data by academic year.</div></div><div class="sub"><?= count($archives) ?> archive<?= count($archives)===1?'':'s' ?></div></div>
<div class="archive-table">
<table><thead><tr><th>Academic Year</th><th>Archived On</th><th>Archived By</th><th>Records</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if (!$archives): ?><tr><td colspan="6" class="empty">No archived evaluation years yet.</td></tr>
<?php else: foreach ($archives as $a): ?><tr><td><strong><?= e($a['school_year']) ?></strong><br><span style="color:#91A6BE;font-size:10.5px"><?= e($a['period_label']) ?></span></td><td><?= e($a['archived_at']) ?></td><td><?= e($a['archived_by_name']) ?></td><td><?= number_format((int)$a['record_count']) ?></td><td><span class="status <?= e($a['status']) ?>"><?= ucfirst(e($a['status'])) ?></span></td><td><a class="btn" href="system_archive.php?view=<?= (int)$a['id'] ?>"><i class="fa-solid fa-eye"></i> View</a></td></tr><?php endforeach; endif; ?>
</tbody></table></div></div>

<div class="card" style="margin-top:14px"><h2>Archive Protection Rules</h2><div class="sub" style="margin-bottom:7px">What changes — and what stays.</div><div class="detail-grid">
<div class="mini"><span>Archived</span><b>Submissions</b><span>Results, tracker rows, reminders, reports and evaluation notifications</span></div>
<div class="mini"><span>Preserved</span><b>Questions</b><span>Question banks, forms and criteria remain ready for the next cycle</span></div>
<div class="mini"><span>Preserved</span><b>Accounts</b><span>User accounts, roles and assignments are not deleted</span></div>
<div class="mini"><span>Preserved</span><b>Audit Trail</b><span>System logs remain available for traceability</span></div>
</div></div>
<?php endif; ?>
</div>
<script>
const sel=document.getElementById('periodSelect'), pid=document.getElementById('archivePeriodId'), btn=document.getElementById('archiveBtn');
function sync(){if(!sel)return;pid.value=sel.value;btn.disabled=!sel.value;}
if(sel){sel.addEventListener('change',sync);sync();}
function confirmArchive(){const o=sel.options[sel.selectedIndex];if(!o||!o.value)return false;return confirm(`Confirm Archive\n\nYou are about to archive all evaluation data for ${o.dataset.year || o.text}.\n\nThis will:\n✓ Clear live evaluation submissions and results\n✓ Keep questions, users, assignments and settings intact\n✓ Keep system logs for audit history\n✓ Store the archived data in System Archive\n\nThis action cannot be undone from the live dashboards. Continue?`);}
function confirmRestore(){return confirm('Restore this archive back into the live evaluation tables?\n\nThis is allowed only when the selected period has no live evaluation records.');}
</script>
</body>
</html>
