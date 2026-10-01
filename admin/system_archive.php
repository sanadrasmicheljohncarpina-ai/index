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

// Days a deleted archive stays in "Recently Deleted" before it is permanently erased.
const ARCHIVE_TRASH_DAYS = 30;

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

// Permanent record of deleted archives (metadata only; the archived payload itself is not kept).
$mysqli->query("CREATE TABLE IF NOT EXISTS system_archive_deletions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    archive_id INT UNSIGNED NOT NULL,
    period_id INT UNSIGNED DEFAULT NULL,
    period_label VARCHAR(150) NOT NULL,
    school_year VARCHAR(30) NOT NULL,
    status_at_delete VARCHAR(20) NOT NULL,
    record_count INT UNSIGNED NOT NULL DEFAULT 0,
    archived_at DATETIME DEFAULT NULL,
    archived_by_name VARCHAR(150) DEFAULT NULL,
    deleted_by INT UNSIGNED DEFAULT NULL,
    deleted_by_name VARCHAR(150) DEFAULT NULL,
    deleted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    archived_by INT UNSIGNED DEFAULT NULL,
    restored_at DATETIME DEFAULT NULL,
    restored_by INT UNSIGNED DEFAULT NULL,
    summary_json LONGTEXT DEFAULT NULL,
    payload_json LONGTEXT DEFAULT NULL,
    recovered_at DATETIME DEFAULT NULL,
    recovered_by_name VARCHAR(150) DEFAULT NULL,
    purged_at DATETIME DEFAULT NULL,
    purge_reason VARCHAR(20) DEFAULT NULL,
    purged_by_name VARCHAR(150) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
// Upgrade tables created by the earlier (metadata-only) version.
$delCols = [];
if ($rc = $mysqli->query("SHOW COLUMNS FROM system_archive_deletions")) { while ($c = $rc->fetch_assoc()) $delCols[$c['Field']] = true; }
foreach ([
    'archived_by' => 'INT UNSIGNED DEFAULT NULL',
    'restored_at' => 'DATETIME DEFAULT NULL',
    'restored_by' => 'INT UNSIGNED DEFAULT NULL',
    'summary_json' => 'LONGTEXT DEFAULT NULL',
    'payload_json' => 'LONGTEXT DEFAULT NULL',
    'recovered_at' => 'DATETIME DEFAULT NULL',
    'recovered_by_name' => 'VARCHAR(150) DEFAULT NULL',
    'purged_at' => 'DATETIME DEFAULT NULL',
    'purge_reason' => 'VARCHAR(20) DEFAULT NULL',
    'purged_by_name' => 'VARCHAR(150) DEFAULT NULL',
] as $colName => $colDef) {
    if (!isset($delCols[$colName])) $mysqli->query("ALTER TABLE system_archive_deletions ADD COLUMN `$colName` $colDef");
}

function archive_allowed(mysqli $db): bool {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'superadmin') return true;
    return $role === 'admin' && admin_can_edit($db, 'system_archive');
}

$allowed = archive_allowed($mysqli);
if (!$allowed) {
    http_response_code(403);
}

// Permanently erase deleted archives that have sat in Recently Deleted past the retention window.
// The history row stays as a small audit record; only the archived data is removed.
if ($allowed) {
    $mysqli->query("UPDATE system_archive_deletions SET payload_json=NULL, summary_json=NULL, purged_at=NOW(), purge_reason='expired'
        WHERE recovered_at IS NULL AND purged_at IS NULL AND payload_json IS NOT NULL
        AND deleted_at < (NOW() - INTERVAL " . (int)ARCHIVE_TRASH_DAYS . " DAY)");
    $autoPurged = $mysqli->affected_rows;
    if ($autoPurged > 0) {
        $txt = $mysqli->real_escape_string("Automatically erased $autoPurged deleted archive" . ($autoPurged === 1 ? '' : 's') . " from Recently Deleted (older than " . (int)ARCHIVE_TRASH_DAYS . " days)");
        $mysqli->query("INSERT INTO activity_log (actor_name, actor_type, action_text, icon, color) VALUES ('System','admin','$txt','fa-trash-can','#D6455D')");
    }
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
        $periodIds = [];
        if (isset($_POST['period_ids']) && is_array($_POST['period_ids'])) {
            foreach ($_POST['period_ids'] as $v) { $v = (int)$v; if ($v > 0) $periodIds[$v] = $v; }
        } elseif (!empty($_POST['period_id'])) {
            $periodIds[(int)$_POST['period_id']] = (int)$_POST['period_id'];
        }
        $periodIds = array_values($periodIds);
        $archivedOk = [];
        $archiveErrs = [];

        foreach ($periodIds as $periodId) {
        $stmt = $mysqli->prepare("SELECT * FROM evaluation_periods WHERE id=? LIMIT 1");
        $stmt->bind_param('i', $periodId);
        $stmt->execute();
        $period = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$period) {
            $archiveErrs[] = "Period #$periodId could not be found.";
        } else {
            $periodName = $period['school_year'] . ' — ' . $period['period_label'];
            $stmt = $mysqli->prepare("SELECT id, status FROM system_archives WHERE period_id=? LIMIT 1");
            $stmt->bind_param('i', $periodId);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing && $existing['status'] === 'archived') {
                $archiveErrs[] = "$periodName already has an archive record.";
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

                    if ($existing) {
                        // Previously restored: refresh the existing record instead of inserting a
                        // new one, since period_id is UNIQUE in system_archives.
                        $upd = $mysqli->prepare("UPDATE system_archives SET period_label=?, school_year=?, archived_by=?, archived_by_name=?, archived_at=NOW(), restored_at=NULL, restored_by=NULL, status='archived', record_count=?, summary_json=?, payload_json=? WHERE id=?");
                        $upd->bind_param('ssisissi', $period['period_label'], $period['school_year'], $byId, $byName, $total, $summary, $payload, $existing['id']);
                        if (!$upd->execute()) throw new Exception('Could not update archive record: ' . $upd->error);
                        $archiveId = (int)$existing['id'];
                        $upd->close();
                    } else {
                        $ins = $mysqli->prepare("INSERT INTO system_archives (period_id, period_label, school_year, archived_by, archived_by_name, record_count, summary_json, payload_json) VALUES (?,?,?,?,?,?,?,?)");
                        $ins->bind_param('issisiss', $periodId, $period['period_label'], $period['school_year'], $byId, $byName, $total, $summary, $payload);
                        if (!$ins->execute()) throw new Exception('Could not create archive record: ' . $ins->error);
                        $archiveId = $ins->insert_id;
                        $ins->close();
                    }

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

                    $archivedOk[] = $periodName;
                } catch (Throwable $ex) {
                    $mysqli->rollback();
                    // Remove a partially-created archive record if the transaction was rolled back by a driver without DDL.
                    $archiveErrs[] = "$periodName failed: " . $ex->getMessage();
                }
            }
        }
        } // end foreach $periodIds

        $okCount = count($archivedOk);
        if (!$periodIds) {
            $flash = 'No evaluation periods were selected.';
            $flashType = 'err';
        } else {
            $flash = '';
            if ($okCount === 1) {
                $flash = "Archive successful. {$archivedOk[0]} is now a fresh evaluation cycle with live submissions cleared.";
            } elseif ($okCount > 1) {
                $flash = "Archive successful. $okCount periods archived (" . implode('; ', $archivedOk) . ") with live submissions cleared.";
            }
            if ($archiveErrs) $flash = trim($flash . ' Not archived: ' . implode(' ', $archiveErrs));
            $flashType = $okCount > 0 ? 'ok' : 'err';
        }
    }

    if ($action === 'restore' || $action === 'restore_selected') {
        $requestedIds = [];

        if ($action === 'restore') {
            $oneId = (int)($_POST['archive_id'] ?? 0);
            if ($oneId > 0) $requestedIds[] = $oneId;
        } else {
            $requestedIds = array_values(array_unique(array_filter(
                array_map('intval', (array)($_POST['archive_ids'] ?? [])),
                fn($id) => $id > 0
            )));
        }

        if (!$requestedIds) {
            $flash = 'No archived records were selected for restore.';
            $flashType = 'err';
        } else {
            $restoreErrors = [];
            $restored = 0;

            try {
                $mysqli->begin_transaction();

                foreach ($requestedIds as $archiveId) {
                    $stmt = $mysqli->prepare("SELECT * FROM system_archives WHERE id=? AND status='archived' LIMIT 1");
                    if (!$stmt) {
                        throw new Exception('Could not prepare archive lookup: ' . $mysqli->error);
                    }
                    $stmt->bind_param('i', $archiveId);
                    $stmt->execute();
                    $archive = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if (!$archive) {
                        $restoreErrors[] = "Archive #$archiveId could not be found or has already been restored.";
                        continue;
                    }

                    $payload = json_decode($archive['payload_json'], true);
                    if (!is_array($payload)) {
                        $restoreErrors[] = "Archive #$archiveId has an invalid archive payload.";
                        continue;
                    }

                    $live = $mysqli->query(
                        "SELECT COUNT(*) c FROM evaluation_tracker WHERE period_id=" . (int)$archive['period_id']
                    );
                    $liveCount = $live ? (int)$live->fetch_assoc()['c'] : 0;

                    if ($liveCount > 0) {
                        $restoreErrors[] = "Archive #$archiveId was skipped because {$archive['school_year']} already contains live evaluation records.";
                        continue;
                    }

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
                            $quotedCols = '`' . implode('`,`', array_map(
                                fn($c) => str_replace('`', '``', $c),
                                $columns
                            )) . '`';
                            $placeholders = implode(',', array_fill(0, count($columns), '?'));
                            $sql = "INSERT IGNORE INTO `$table` ($quotedCols) VALUES ($placeholders)";

                            $stmt = $mysqli->prepare($sql);
                            if (!$stmt) {
                                throw new Exception("Could not prepare restore for table $table: " . $mysqli->error);
                            }

                            $types = '';
                            $values = [];
                            foreach ($columns as $c) {
                                $v = $row[$c];
                                $types .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
                                $values[] = $v;
                            }

                            $bind = [$types];
                            foreach ($values as $k => $v) $bind[] = &$values[$k];
                            call_user_func_array([$stmt, 'bind_param'], $bind);

                            if (!$stmt->execute()) {
                                $err = $stmt->error;
                                $stmt->close();
                                throw new Exception("Restore failed for archive #$archiveId in $table: $err");
                            }
                            $stmt->close();
                        }
                    }

                    // Make the period visible only after this archive has been restored.
                    $stmt = $mysqli->prepare("UPDATE evaluation_periods SET is_active=1 WHERE id=?");
                    $pid = (int)$archive['period_id'];
                    $stmt->bind_param('i', $pid);
                    if (!$stmt->execute()) {
                        $err = $stmt->error;
                        $stmt->close();
                        throw new Exception("Could not reactivate evaluation period for archive #$archiveId: $err");
                    }
                    $stmt->close();

                    $byId = (int)$_SESSION['user_id'];
                    $stmt = $mysqli->prepare(
                        "UPDATE system_archives SET status='restored', restored_at=NOW(), restored_by=? WHERE id=?"
                    );
                    $stmt->bind_param('ii', $byId, $archiveId);
                    if (!$stmt->execute()) {
                        $err = $stmt->error;
                        $stmt->close();
                        throw new Exception("Could not mark archive #$archiveId as restored: $err");
                    }
                    $stmt->close();

                    $actor = $mysqli->real_escape_string(
                        $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Administrator'
                    );
                    $actionText = $mysqli->real_escape_string(
                        "Restored evaluation archive #$archiveId for {$archive['school_year']}"
                    );
                    if (!$mysqli->query(
                        "INSERT INTO activity_log (actor_name, actor_type, action_text, icon, color)
                         VALUES ('$actor','admin','$actionText','fa-box-open','#0F9F6E')"
                    )) {
                        throw new Exception('Could not write the restore audit log: ' . $mysqli->error);
                    }

                    $restored++;
                }

                // A skipped archive is not a database failure. Commit successful restores
                // and report any items that were not eligible.
                $mysqli->commit();

                if ($action === 'restore') {
                    if ($restored === 1 && !$restoreErrors) {
                        $flash = 'The selected archive was restored successfully.';
                        $flashType = 'ok';
                    } elseif ($restored === 0) {
                        $flash = $restoreErrors[0] ?? 'The selected archive could not be restored.';
                        $flashType = 'err';
                    } else {
                        $flash = 'The archive was restored with warnings: ' . implode(' ', $restoreErrors);
                        $flashType = 'ok';
                    }
                } else {
                    $summaryParts = [];
                    if ($restored > 0) {
                        $summaryParts[] = $restored . ' archive' . ($restored === 1 ? '' : 's') . ' restored successfully.';
                    }
                    if ($restoreErrors) {
                        $summaryParts[] = count($restoreErrors) . ' selection' . (count($restoreErrors) === 1 ? '' : 's') . ' skipped.';
                    }
                    $flash = $summaryParts
                        ? implode(' ', $summaryParts) . ($restoreErrors ? ' ' . implode(' ', $restoreErrors) : '')
                        : 'No selected archives were restored.';
                    $flashType = $restored > 0 ? 'ok' : 'err';
                }
            } catch (Throwable $ex) {
                $mysqli->rollback();
                $flash = 'Restore failed: ' . $ex->getMessage();
                $flashType = 'err';
            }
        }
    }

    if ($action === 'recover') {
        $histId = (int)($_POST['history_id'] ?? 0);
        $stmt = $mysqli->prepare("SELECT * FROM system_archive_deletions WHERE id=? LIMIT 1");
        $stmt->bind_param('i', $histId);
        $stmt->execute();
        $h = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$h) {
            $flash = 'That delete-history entry could not be found.';
            $flashType = 'err';
        } elseif (!empty($h['recovered_at'])) {
            $flash = 'This archive has already been restored from Recently Deleted.';
            $flashType = 'err';
        } elseif (!empty($h['purged_at'])) {
            $flash = 'This archive was permanently erased and can no longer be restored.';
            $flashType = 'err';
        } elseif ($h['payload_json'] === null || $h['payload_json'] === '') {
            $flash = 'This archive cannot be restored because its data was not kept when it was deleted.';
            $flashType = 'err';
        } else {
            $hPid = (int)$h['period_id'];
            $stmt = $mysqli->prepare("SELECT id FROM system_archives WHERE period_id=? LIMIT 1");
            $stmt->bind_param('i', $hPid);
            $stmt->execute();
            $conflict = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($conflict) {
                $flash = "{$h['school_year']} ({$h['period_label']}) already has a newer archive record, so the deleted one cannot be restored.";
                $flashType = 'err';
            } else {
                try {
                    $mysqli->begin_transaction();

                    // Keep the original archive number when it is still free.
                    $origId = (int)$h['archive_id'];
                    $stmt = $mysqli->prepare("SELECT 1 FROM system_archives WHERE id=? LIMIT 1");
                    $stmt->bind_param('i', $origId);
                    $stmt->execute();
                    $idTaken = (bool)$stmt->get_result()->fetch_row();
                    $stmt->close();
                    $newId = $idTaken ? null : $origId;

                    $ins = $mysqli->prepare("INSERT INTO system_archives (id, period_id, period_label, school_year, archived_by, archived_by_name, archived_at, restored_at, restored_by, status, record_count, summary_json, payload_json) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
                    if (!$ins) throw new Exception($mysqli->error);
                    $hStatus = ($h['status_at_delete'] === 'restored') ? 'restored' : 'archived';
                    $hCnt = (int)$h['record_count'];
                    $ins->bind_param('iississsisiss', $newId, $hPid, $h['period_label'], $h['school_year'], $h['archived_by'], $h['archived_by_name'], $h['archived_at'], $h['restored_at'], $h['restored_by'], $hStatus, $hCnt, $h['summary_json'], $h['payload_json']);
                    if (!$ins->execute()) throw new Exception('Could not restore the archive record: ' . $ins->error);
                    $newArchiveId = $ins->insert_id ?: $origId;
                    $ins->close();

                    $byName = (string)($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Administrator');
                    $mark = $mysqli->prepare("UPDATE system_archive_deletions SET recovered_at=NOW(), recovered_by_name=? WHERE id=?");
                    $mark->bind_param('si', $byName, $histId);
                    if (!$mark->execute()) throw new Exception('Could not update delete history: ' . $mark->error);
                    $mark->close();

                    $actor = $mysqli->real_escape_string($byName);
                    $actionText = $mysqli->real_escape_string("Restored deleted evaluation archive for {$h['school_year']} ({$h['period_label']}) as Archive #$newArchiveId");
                    $mysqli->query("INSERT INTO activity_log (actor_name, actor_type, action_text, icon, color) VALUES ('$actor','admin','$actionText','fa-rotate-left','#2563EB')");

                    $mysqli->commit();
                    $flash = "{$h['school_year']} ({$h['period_label']}) was restored to Archived Records as Archive #$newArchiveId.";
                    $flashType = 'ok';
                } catch (Throwable $ex) {
                    $mysqli->rollback();
                    $flash = 'Restore failed: ' . $ex->getMessage();
                    $flashType = 'err';
                }
            }
        }
    }

    if ($action === 'purge' || $action === 'empty_trash') {
        $byName = (string)($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Administrator');
        $actor = $mysqli->real_escape_string($byName);
        if ($action === 'purge') {
            $histId = (int)($_POST['history_id'] ?? 0);
            $stmt = $mysqli->prepare("UPDATE system_archive_deletions SET payload_json=NULL, summary_json=NULL, purged_at=NOW(), purge_reason='manual', purged_by_name=? WHERE id=? AND recovered_at IS NULL AND purged_at IS NULL AND payload_json IS NOT NULL");
            $stmt->bind_param('si', $byName, $histId);
            $stmt->execute();
            $n = $stmt->affected_rows;
            $stmt->close();
            if ($n > 0) {
                $mysqli->query("INSERT INTO activity_log (actor_name, actor_type, action_text, icon, color) VALUES ('$actor','admin','" . $mysqli->real_escape_string("Permanently erased a deleted evaluation archive (Recently Deleted #$histId)") . "','fa-trash-can','#D6455D')");
                $flash = 'The archive was permanently deleted.';
                $flashType = 'ok';
            } else {
                $flash = 'That item is no longer in Recently Deleted.';
                $flashType = 'err';
            }
        } else {
            $stmt = $mysqli->prepare("UPDATE system_archive_deletions SET payload_json=NULL, summary_json=NULL, purged_at=NOW(), purge_reason='manual', purged_by_name=? WHERE recovered_at IS NULL AND purged_at IS NULL AND payload_json IS NOT NULL");
            $stmt->bind_param('s', $byName);
            $stmt->execute();
            $n = $stmt->affected_rows;
            $stmt->close();
            if ($n > 0) {
                $mysqli->query("INSERT INTO activity_log (actor_name, actor_type, action_text, icon, color) VALUES ('$actor','admin','" . $mysqli->real_escape_string("Emptied Recently Deleted: permanently erased $n archive" . ($n === 1 ? '' : 's')) . "','fa-trash-can','#D6455D')");
                $flash = "Recently Deleted was emptied ($n archive" . ($n === 1 ? '' : 's') . ' permanently deleted).';
                $flashType = 'ok';
            } else {
                $flash = 'Recently Deleted is already empty.';
                $flashType = 'err';
            }
        }
    }

    if ($action === 'delete') {
        $archiveId = (int)($_POST['archive_id'] ?? 0);
        $stmt = $mysqli->prepare("SELECT * FROM system_archives WHERE id=? LIMIT 1");
        $stmt->bind_param('i', $archiveId);
        $stmt->execute();
        $archive = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$archive) {
            $flash = 'The archive could not be found.';
            $flashType = 'err';
        } else {
            // Save the history copy FIRST. If it cannot be saved, the archive is NOT deleted,
            // so a deletion can never silently lose the archived data.
            $delById = (int)$_SESSION['user_id'];
            $delByName = (string)($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Administrator');
            try {
                $mysqli->begin_transaction();

                $hist = $mysqli->prepare("INSERT INTO system_archive_deletions (archive_id, period_id, period_label, school_year, status_at_delete, record_count, archived_at, archived_by, archived_by_name, restored_at, restored_by, summary_json, payload_json, deleted_by, deleted_by_name) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                if (!$hist) throw new Exception($mysqli->error);
                $hPid = (int)$archive['period_id']; $hCnt = (int)$archive['record_count'];
                $hist->bind_param('iisssisississis', $archiveId, $hPid, $archive['period_label'], $archive['school_year'], $archive['status'], $hCnt, $archive['archived_at'], $archive['archived_by'], $archive['archived_by_name'], $archive['restored_at'], $archive['restored_by'], $archive['summary_json'], $archive['payload_json'], $delById, $delByName);
                if (!$hist->execute()) throw new Exception($hist->error ?: $mysqli->error);
                $hist->close();

                $del = $mysqli->prepare("DELETE FROM system_archives WHERE id=?");
                $del->bind_param('i', $archiveId);
                if (!$del->execute()) throw new Exception($del->error);
                $del->close();

                $mysqli->commit();

                $actor = $mysqli->real_escape_string($delByName);
                $actionText = $mysqli->real_escape_string("Deleted evaluation archive #$archiveId for {$archive['school_year']} ({$archive['period_label']})");
                $mysqli->query("INSERT INTO activity_log (actor_name, actor_type, action_text, icon, color) VALUES ('$actor','admin','$actionText','fa-trash','#D6455D')");
                $flash = "Archive #$archiveId for {$archive['school_year']} was moved to Recently Deleted. It can be restored for " . (int)ARCHIVE_TRASH_DAYS . " days.";
                $flashType = 'ok';
            } catch (Throwable $ex) {
                $mysqli->rollback();
                $flash = 'Delete cancelled, the archive was kept: ' . $ex->getMessage() . ' (If this mentions max_allowed_packet, raise it in XAMPP\'s my.ini, e.g. max_allowed_packet=64M, then restart MySQL.)';
                $flashType = 'err';
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
           ep.date_start, ep.date_end, COUNT(et.id) AS live_count
    FROM evaluation_periods ep
    INNER JOIN evaluation_tracker et ON et.period_id = ep.id
    LEFT JOIN system_archives sa ON sa.period_id = ep.id AND sa.status = 'archived'
    WHERE sa.id IS NULL
      AND ep.school_year REGEXP '^[0-9]{4}-[0-9]{4}$'
      AND ep.semester IN ('1st Semester','2nd Semester','Summer','School Year')
    GROUP BY ep.id, ep.period_label, ep.semester, ep.school_year, ep.is_active, ep.date_start, ep.date_end
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
$trashItems = [];
$deletionLog = [];
$res = $mysqli->query("SELECT id, archive_id, period_id, period_label, school_year, status_at_delete, record_count, archived_at, archived_by_name, deleted_by_name, deleted_at, recovered_at, recovered_by_name, purged_at, purge_reason, purged_by_name,
    (payload_json IS NOT NULL AND payload_json <> '') AS has_payload,
    GREATEST(0, CEIL(TIMESTAMPDIFF(SECOND, NOW(), deleted_at + INTERVAL " . (int)ARCHIVE_TRASH_DAYS . " DAY) / 86400)) AS days_left
    FROM system_archive_deletions ORDER BY deleted_at DESC, id DESC LIMIT 300");
if ($res) while ($r = $res->fetch_assoc()) {
    if ((int)$r['has_payload'] === 1 && empty($r['recovered_at']) && empty($r['purged_at'])) $trashItems[] = $r;
    else $deletionLog[] = $r;
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
*{box-sizing:border-box} body{margin:0;background:var(--bg);color:var(--text);font:13px/1.5 Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;-webkit-font-smoothing:antialiased}.wrap{max-width:1240px;margin:0 auto;padding:24px 28px 44px}.back{display:inline-flex;gap:8px;align-items:center;color:var(--muted);text-decoration:none;font-size:12px;font-weight:600;margin-bottom:15px}.hero{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:16px}.hero h1{font-size:25px;margin:0 0 3px;letter-spacing:-.025em}.hero p{margin:0;color:var(--muted);font-size:13px}.badge{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border-radius:999px;border:1px solid #C9D9EF;background:#fff;color:var(--blue);font-weight:700;font-size:11px}.grid{display:grid;grid-template-columns:1.25fr .75fr;gap:14px}.card{background:var(--card);border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow);padding:17px}.card h2{font-size:14px;margin:0 0 4px}.sub{color:var(--muted);font-size:11.5px}.label{display:block;font-size:11px;font-weight:700;color:#405A76;margin:14px 0 6px;text-transform:uppercase;letter-spacing:.04em}.select{width:100%;padding:9px 11px;border:1px solid #B9CDE5;border-radius:8px;background:#fff;color:var(--text);font:600 12px Inter}.actions{display:flex;gap:8px;align-items:center;margin-top:13px;flex-wrap:wrap}.btn{border:1px solid #B9CDE5;background:#fff;border-radius:8px;padding:9px 12px;font:700 12px Inter;color:var(--text);cursor:pointer}.btn.primary{border-color:var(--blue);background:var(--blue);color:#fff}.btn.danger{border-color:#F1B7C0;background:#FFF6F7;color:var(--red)}.btn:disabled{opacity:.45;cursor:not-allowed}.warn{display:flex;gap:8px;align-items:flex-start;margin-top:12px;padding:9px 10px;background:var(--amber-soft);border:1px solid #F5D48A;border-radius:8px;color:#8D5D00;font-size:11.5px}.success{display:flex;gap:8px;align-items:flex-start;padding:9px 10px;background:var(--green-soft);border:1px solid #BDE9D4;border-radius:8px;color:#08734F;font-size:11.5px}.archive-table{margin-top:14px;border:1px solid var(--line);border-radius:10px;overflow:auto;background:#fff}.archive-table table{width:100%;border-collapse:collapse;min-width:760px}.archive-table th,.archive-table td{padding:10px 11px;border-bottom:1px solid #EAF0F7;text-align:left;font-size:11.5px;white-space:nowrap}.archive-table th.select-col,.archive-table td.select-col{width:38px;text-align:center;padding-left:10px;padding-right:4px}.archive-table input.archive-check,.archive-table input#selectAllArchives{width:15px;height:15px;accent-color:var(--blue);cursor:pointer}.bulk-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.selection-count{font-size:11px;color:var(--muted);font-weight:600}.archive-table th{background:#F8FAFC;color:#49647F;font-size:10.5px;text-transform:uppercase;letter-spacing:.05em}.archive-table tr:last-child td{border-bottom:0}.status{display:inline-flex;padding:4px 8px;border-radius:999px;font-size:10px;font-weight:800}.status.archived{background:var(--green-soft);color:var(--green)}.status.left-warn{background:#FFF6E0;color:#8D5D00}.status.del-unrestored{background:#FFF0F2;color:var(--red)}.status.restored{background:var(--blue-soft);color:var(--blue)}.empty{padding:25px;text-align:center;color:var(--muted)}.detail{margin-top:14px}.detail-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}.mini{padding:11px;border:1px solid var(--line);background:#FBFDFF;border-radius:8px}.mini b{display:block;font-size:16px}.mini span{color:var(--muted);font-size:10.5px}.notice{margin-bottom:14px;padding:10px 12px;border-radius:9px;border:1px solid #C9D9EF;background:var(--blue-soft);color:#2855A4;font-size:11.5px;display:flex;gap:8px}.restricted{max-width:620px;margin:60px auto;text-align:center}.restricted .icon{font-size:30px;color:var(--red);margin-bottom:8px}.multi-wrap{position:relative}.multi-trigger{display:flex;align-items:center;justify-content:space-between;gap:10px;text-align:left;cursor:pointer}.multi-trigger i{font-size:10px;color:#49647F;transition:transform .15s}.multi-trigger[aria-expanded=true] i{transform:rotate(180deg)}.multi-box[hidden]{display:none}.multi-box{position:static;margin-top:6px;border:1px solid #B9CDE5;border-radius:8px;background:#fff;max-height:190px;overflow:auto}.multi-row{display:flex;align-items:center;gap:9px;margin:0;padding:8px 11px;min-height:0;height:auto;line-height:1.3;border-bottom:1px solid #EAF0F7;font-size:12px;font-weight:600;cursor:pointer}.multi-row:last-child{border-bottom:0}.multi-row:hover{background:#F5F9FF}.multi-row input{margin:0;padding:0;flex:0 0 15px;width:15px;height:15px;accent-color:var(--blue);cursor:pointer}.multi-row em{font-style:normal;color:var(--muted);font-weight:500}.multi-all{background:#F8FAFC;position:sticky;top:0;color:#405A76}@media(max-width:900px){.grid{grid-template-columns:1fr}.detail-grid{grid-template-columns:repeat(2,1fr)}}
</style>
<style id="pbi-feature-scrollbar">

/* PBI FEATURE SCROLLBAR — consistent with the compact page scrollbar */
html, body {
  scrollbar-width: thin !important;
  scrollbar-color: #888 transparent !important;
}
html::-webkit-scrollbar, body::-webkit-scrollbar,
.feature-compact ::-webkit-scrollbar { width: 10px !important; height: 10px !important; }
html::-webkit-scrollbar-track, body::-webkit-scrollbar-track,
.feature-compact ::-webkit-scrollbar-track { background: transparent !important; }
html::-webkit-scrollbar-thumb, body::-webkit-scrollbar-thumb,
.feature-compact ::-webkit-scrollbar-thumb {
  background: #888 !important; border-radius: 999px !important;
  border: 2px solid transparent !important; background-clip: padding-box !important;
}
html::-webkit-scrollbar-thumb:hover, body::-webkit-scrollbar-thumb:hover,
.feature-compact ::-webkit-scrollbar-thumb:hover { background: #777 !important; background-clip: padding-box !important; }
html::-webkit-scrollbar-button, body::-webkit-scrollbar-button,
.feature-compact ::-webkit-scrollbar-button { display: block !important; width: 10px !important; height: 10px !important; background-color: transparent !important; }

</style>

<link rel="stylesheet" href="admin_appearance.css">
<script src="admin_appearance.js"></script>
</head>
<body class="feature-compact">
<div class="wrap">
<a class="back" href="admin_dashboard.php" onclick="if(window.top&&window.top!==window&&window.top.showPage){window.top.showPage('dashboard',window.top.document.getElementById('link-dashboard'));return false;}"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
<?php if (!$allowed): ?>
<div class="card restricted"><div class="icon"><i class="fa-solid fa-lock"></i></div><h1>System Archive is restricted</h1><p class="sub">Only the Super Admin, or an Admin explicitly granted the <strong>System Archive</strong> edit permission, can archive or restore evaluation data.</p></div>
<?php else: ?>
<div class="hero"><div><h1>System Archive</h1><p>Archive the selected academic year and prepare the evaluation system for a fresh cycle.</p></div><div class="badge"><i class="fa-solid fa-box-archive"></i> Protected Archive</div></div>
<?php if ($flash): ?><div class="notice" style="border-color:<?= $flashType==='ok'?'#BDE9D4':'#F1B7C0' ?>;background:<?= $flashType==='ok'?'#EAF9F2':'#FFF0F2' ?>;color:<?= $flashType==='ok'?'#08734F':'#A62A42' ?>;"><i class="fa-solid <?= $flashType==='ok'?'fa-circle-check':'fa-circle-exclamation' ?>"></i><span><?= e($flash) ?></span></div><?php endif; ?>
<div class="grid">
<div class="card">
<h2>Archive This Year Evaluation</h2><div class="sub">Archive all evaluation submissions and related data for the selected academic year.</div>
<label class="label">Academic Year</label>
<div class="multi-wrap" id="periodWrap">
<button type="button" class="select multi-trigger" id="periodTrigger" aria-haspopup="listbox" aria-expanded="false"><span id="periodText">Select an evaluation period…</span><i class="fa-solid fa-chevron-down"></i></button>
<div class="multi-box" id="periodMulti" role="listbox" aria-multiselectable="true" hidden>
<?php if (!$periods): ?><div class="empty">No evaluation periods with live data.</div><?php else: ?>
<label class="multi-row multi-all"><input type="checkbox" id="periodAll"> <span>Select all periods</span></label>
<?php foreach ($periods as $p): ?><label class="multi-row"><input type="checkbox" class="period-check" value="<?= (int)$p['id'] ?>" data-label="<?= e($p['school_year'] . ' — ' . $p['semester']) ?>" data-live="<?= (int)$p['live_count'] ?>" <?= (!empty($viewArchive) && (int)$viewArchive['period_id']===(int)$p['id'])?'checked':'' ?>> <span><?= e($p['school_year']) ?> — <?= e($p['semester']) ?> <em>(<?= number_format((int)$p['live_count']) ?> live evaluation<?= (int)$p['live_count'] === 1 ? '' : 's' ?>)</em></span></label><?php endforeach; ?>
<?php endif; ?>
</div>
</div>
<div class="actions"><form method="POST" id="archiveForm" onsubmit="return confirmArchive(this)"><input type="hidden" name="action" value="archive"><div id="archivePeriodInputs"></div><button class="btn primary" type="submit" id="archiveBtn" disabled><i class="fa-solid fa-box-archive"></i> <span id="archiveBtnText">Archive This Year</span></button></form></div>
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
<div class="actions"><a class="btn" href="system_archive.php"><i class="fa-solid fa-arrow-left"></i> Back to Archive</a><?php if ($viewArchive['status']==='archived'): ?><form method="POST" onsubmit="return confirmRestore(this)"><input type="hidden" name="action" value="restore"><input type="hidden" name="archive_id" value="<?= (int)$viewArchive['id'] ?>"><button class="btn danger" type="submit"><i class="fa-solid fa-box-open"></i> Restore This Archive</button></form><?php endif; ?><form method="POST" onsubmit="return confirmDelete(this, <?= json_encode($viewArchive['school_year'] . ' — ' . $viewArchive['period_label']) ?>, <?= json_encode($viewArchive['status']) ?>)"><input type="hidden" name="action" value="delete"><input type="hidden" name="archive_id" value="<?= (int)$viewArchive['id'] ?>"><button class="btn danger" type="submit"><i class="fa-solid fa-trash"></i> Delete This Archive</button></form></div>
</div>
<?php endif; ?>

<div class="card" style="margin-top:14px"><div style="display:flex;justify-content:space-between;align-items:end;gap:12px"><div><h2>Archived Records</h2><div class="sub">Select one or more archived evaluation periods to restore.</div></div><div class="bulk-actions"><span class="selection-count" id="selectionCount">0 selected</span><form method="POST" id="bulkRestoreForm" onsubmit="return confirmBulkRestore(this)"><input type="hidden" name="action" value="restore_selected"><div id="bulkRestoreInputs"></div><button class="btn primary" type="submit" id="bulkRestoreBtn" disabled><i class="fa-solid fa-box-open"></i> Restore Selected</button></form><div class="sub"><?= count($archives) ?> archive<?= count($archives)===1?'':'s' ?></div></div></div>
<div class="archive-table">
<table><thead><tr><th class="select-col"><input type="checkbox" id="selectAllArchives" title="Select all archived records"></th><th>Academic Year</th><th>Archived On</th><th>Archived By</th><th>Records</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if (!$archives): ?><tr><td colspan="7" class="empty">No archived evaluation years yet.</td></tr>
<?php else: foreach ($archives as $a): ?><tr><td class="select-col"><?php if ($a['status']==='archived'): ?><input type="checkbox" class="archive-check" value="<?= (int)$a['id'] ?>" data-label="<?= e($a['school_year'] . ' — ' . $a['period_label']) ?>"><?php else: ?><span style="color:#B7C5D5">—</span><?php endif; ?></td><td><strong><?= e($a['school_year']) ?></strong><br><span style="color:#91A6BE;font-size:10.5px"><?= e($a['period_label']) ?></span></td><td><?= e($a['archived_at']) ?></td><td><?= e($a['archived_by_name']) ?></td><td><?= number_format((int)$a['record_count']) ?></td><td><span class="status <?= e($a['status']) ?>"><?= ucfirst(e($a['status'])) ?></span></td><td style="display:flex;gap:6px;align-items:center"><?php if ($a['status']==='archived'): ?><form method="POST" onsubmit="return confirmRestore(this)"><input type="hidden" name="action" value="restore"><input type="hidden" name="archive_id" value="<?= (int)$a['id'] ?>"><button class="btn primary" type="submit"><i class="fa-solid fa-box-open"></i> Restore</button></form><?php else: ?><span style="font-size:11px;color:#7A90AA;font-weight:600"><i class="fa-solid fa-circle-check"></i> Restored</span><?php endif; ?><form method="POST" onsubmit="return confirmDelete(this, <?= json_encode($a['school_year'] . ' — ' . $a['period_label']) ?>, <?= json_encode($a['status']) ?>)"><input type="hidden" name="action" value="delete"><input type="hidden" name="archive_id" value="<?= (int)$a['id'] ?>"><button class="btn danger" type="submit"><i class="fa-solid fa-trash"></i> Delete</button></form></td></tr><?php endforeach; endif; ?>
</tbody></table></div></div>

<div class="card" style="margin-top:14px"><div style="display:flex;justify-content:space-between;align-items:end;gap:12px;flex-wrap:wrap"><div><h2><i class="fa-solid fa-trash-can-arrow-up" style="color:var(--red)"></i> Recently Deleted</h2><div class="sub">Deleted archives stay here for <?= (int)ARCHIVE_TRASH_DAYS ?> days and can be restored. After that they are permanently erased.</div></div><div class="bulk-actions"><span class="selection-count"><?= count($trashItems) ?> item<?= count($trashItems)===1?'':'s' ?></span><?php if ($trashItems): ?><form method="POST" onsubmit="return confirmEmptyTrash(this, <?= count($trashItems) ?>)"><input type="hidden" name="action" value="empty_trash"><button class="btn danger" type="submit"><i class="fa-solid fa-trash-can"></i> Empty Recently Deleted</button></form><?php endif; ?></div></div>
<div class="archive-table">
<table><thead><tr><th>Academic Year</th><th>Records</th><th>Deleted By</th><th>Deleted On</th><th>Time Left</th><th>Actions</th></tr></thead><tbody>
<?php if (!$trashItems): ?><tr><td colspan="6" class="empty">Nothing here. Deleted archives will appear for <?= (int)ARCHIVE_TRASH_DAYS ?> days.</td></tr>
<?php else: foreach ($trashItems as $d): $left = (int)$d['days_left']; $leftClass = $left <= 3 ? 'del-unrestored' : ($left <= 7 ? 'left-warn' : 'restored'); $leftText = $left <= 0 ? 'Erased soon' : ($left === 1 ? '1 day left' : $left . ' days left'); $lbl = $d['school_year'] . ' — ' . $d['period_label']; ?><tr><td><strong><?= e($d['school_year']) ?></strong><br><span style="color:#91A6BE;font-size:10.5px"><?= e($d['period_label']) ?> · Archive #<?= (int)$d['archive_id'] ?></span></td><td><?= number_format((int)$d['record_count']) ?></td><td><?= e($d['deleted_by_name'] ?: '—') ?></td><td><?= e($d['deleted_at']) ?></td><td><span class="status <?= $leftClass ?>"><?= e($leftText) ?></span></td><td style="display:flex;gap:6px;align-items:center"><form method="POST" onsubmit="return confirmRecover(this, <?= json_encode($lbl) ?>)"><input type="hidden" name="action" value="recover"><input type="hidden" name="history_id" value="<?= (int)$d['id'] ?>"><button class="btn primary" type="submit"><i class="fa-solid fa-rotate-left"></i> Restore</button></form><form method="POST" onsubmit="return confirmPurge(this, <?= json_encode($lbl) ?>)"><input type="hidden" name="action" value="purge"><input type="hidden" name="history_id" value="<?= (int)$d['id'] ?>"><button class="btn danger" type="submit"><i class="fa-solid fa-trash"></i> Delete Now</button></form></td></tr><?php endforeach; endif; ?>
</tbody></table></div>
<?php if ($deletionLog): ?>
<details style="margin-top:12px"><summary style="cursor:pointer;font-size:11.5px;font-weight:700;color:#49647F">Deletion log (<?= count($deletionLog) ?>)</summary>
<div class="archive-table">
<table><thead><tr><th>Academic Year</th><th>Deleted By</th><th>Deleted On</th><th>Outcome</th></tr></thead><tbody>
<?php foreach ($deletionLog as $d): ?><tr><td><strong><?= e($d['school_year']) ?></strong><br><span style="color:#91A6BE;font-size:10.5px"><?= e($d['period_label']) ?> · Archive #<?= (int)$d['archive_id'] ?></span></td><td><?= e($d['deleted_by_name'] ?: '—') ?></td><td><?= e($d['deleted_at']) ?></td><td><?php if (!empty($d['recovered_at'])): ?><span class="status restored">Restored <?= e($d['recovered_at']) ?></span><?php elseif (!empty($d['purged_at'])): ?><span class="status del-unrestored"><?= $d['purge_reason']==='expired' ? 'Expired — erased ' : 'Erased by ' . e($d['purged_by_name'] ?: 'admin') . ' ' ?><?= e($d['purged_at']) ?></span><?php else: ?><span style="font-size:11px;color:#9AA9BB">Not restorable (deleted before data was kept)</span><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div></details>
<?php endif; ?>
</div>

<div class="card" style="margin-top:14px"><h2>Archive Protection Rules</h2><div class="sub" style="margin-bottom:7px">What changes — and what stays.</div><div class="detail-grid">
<div class="mini"><span>Archived</span><b>Submissions</b><span>Results, tracker rows, reminders, reports and evaluation notifications</span></div>
<div class="mini"><span>Preserved</span><b>Questions</b><span>Question banks, forms and criteria remain ready for the next cycle</span></div>
<div class="mini"><span>Preserved</span><b>Accounts</b><span>User accounts, roles and assignments are not deleted</span></div>
<div class="mini"><span>Preserved</span><b>Audit Trail</b><span>System logs remain available for traceability</span></div>
</div></div>
<?php endif; ?>
</div>
<script>
const periodChecks=[...document.querySelectorAll('.period-check')];
const periodAll=document.getElementById('periodAll');
const btn=document.getElementById('archiveBtn');
const periodInputs=document.getElementById('archivePeriodInputs');
const periodTrigger=document.getElementById('periodTrigger');
const periodText=document.getElementById('periodText');
const periodBox=document.getElementById('periodMulti');
const periodWrap=document.getElementById('periodWrap');
const archiveBtnText=document.getElementById('archiveBtnText');
function sync(){
    if(!btn) return;
    const chosen=periodChecks.filter(c=>c.checked);
    btn.disabled=chosen.length===0;
    if(periodText) periodText.textContent=chosen.length===0?'Select an evaluation period…':(chosen.length===1?chosen[0].dataset.label:`${chosen.length} evaluation periods selected`);
    if(archiveBtnText) archiveBtnText.textContent=chosen.length>1?`Archive ${chosen.length} Periods`:'Archive This Year';
    if(periodInputs) periodInputs.innerHTML=chosen.map(c=>`<input type="hidden" name="period_ids[]" value="${c.value}">`).join('');
    if(periodAll){
        periodAll.checked=periodChecks.length>0 && chosen.length===periodChecks.length;
        periodAll.indeterminate=chosen.length>0 && chosen.length<periodChecks.length;
    }
}
function setPeriodOpen(open){if(!periodBox)return;periodBox.hidden=!open;periodTrigger.setAttribute('aria-expanded',open?'true':'false');}
if(periodTrigger){
    periodTrigger.addEventListener('click',()=>setPeriodOpen(periodBox.hidden));
    document.addEventListener('click',e=>{if(periodWrap&&!periodWrap.contains(e.target))setPeriodOpen(false);});
    document.addEventListener('keydown',e=>{if(e.key==='Escape')setPeriodOpen(false);});
}
periodChecks.forEach(c=>c.addEventListener('change',sync));
if(periodAll) periodAll.addEventListener('change',()=>{periodChecks.forEach(c=>c.checked=periodAll.checked);sync();});
sync();
// Submits a form only after the custom (non-native) confirm modal is accepted.
// Always returns false synchronously so the browser's own submit is blocked;
// HTMLFormElement.prototype.submit bypasses onsubmit so there's no re-trigger loop.
function confirmSubmit(form, message, opts){
    (async () => {
        if (await PBI.confirm(message, opts)) {
            HTMLFormElement.prototype.submit.call(form);
        }
    })();
    return false;
}
function confirmArchive(form){const chosen=periodChecks.filter(c=>c.checked);if(!chosen.length)return false;const n=chosen.length;const names=chosen.map(c=>`• ${c.dataset.label} (${c.dataset.live} live)`).join('\n');return confirmSubmit(form, `Archive ${n===1?'this period':n+' periods'}?\n\n${names}\n\nLive submissions and results will be cleared and stored in System Archive. Questions, users, assignments, settings and system logs are kept.\n\nThis cannot be undone from the live dashboards.`, {okLabel:'Archive'});}
function confirmRestore(form){return confirmSubmit(form, 'Restore this archive back into the live evaluation tables?\n\nThis is allowed only when the selected period has no live evaluation records.', {okLabel:'Restore', danger:false});}
const archiveChecks=[...document.querySelectorAll('.archive-check')];
const selectAllArchives=document.getElementById('selectAllArchives');
const bulkRestoreBtn=document.getElementById('bulkRestoreBtn');
const bulkRestoreForm=document.getElementById('bulkRestoreForm');
const bulkRestoreInputs=document.getElementById('bulkRestoreInputs');
const selectionCount=document.getElementById('selectionCount');

function syncArchiveSelection(){
    if(!bulkRestoreBtn || !bulkRestoreForm) return;
    const selected=archiveChecks.filter(c=>c.checked);
    if(selectionCount) selectionCount.textContent=`${selected.length} selected`;
    bulkRestoreBtn.disabled=selected.length===0;
    if(selectAllArchives){
        selectAllArchives.checked=archiveChecks.length>0 && selected.length===archiveChecks.length;
        selectAllArchives.indeterminate=selected.length>0 && selected.length<archiveChecks.length;
    }
    if(bulkRestoreInputs){
        bulkRestoreInputs.innerHTML=selected.map(c=>`<input type="hidden" name="archive_ids[]" value="${c.value}">`).join('');
    }
}
archiveChecks.forEach(c=>c.addEventListener('change',syncArchiveSelection));
if(selectAllArchives){
    selectAllArchives.addEventListener('change',()=>{
        archiveChecks.forEach(c=>c.checked=selectAllArchives.checked);
        syncArchiveSelection();
    });
}
function confirmBulkRestore(form){
    const selected=archiveChecks.filter(c=>c.checked);
    if(!selected.length) return false;
    const labels=selected.map(c=>c.dataset.label || `Archive #${c.value}`);
    return confirmSubmit(form, `Restore ${selected.length} selected archive${selected.length===1?'':'s'}?\n\n${labels.join('\n')}\n\nEach selected period must have no live evaluation records. Continue?`, {okLabel:'Restore', danger:false});
}
syncArchiveSelection();
function confirmDelete(form, label, status){const d=<?= (int)ARCHIVE_TRASH_DAYS ?>;const risk=status==='archived'?`⚠ This archive has NOT been restored, so it holds the only copy of this evaluation data.\n\n`:`This archive was already restored to the live tables.\n\n`;return confirmSubmit(form, `Are you sure you want to delete the archive for ${label}?\n\n${risk}It will move to Recently Deleted, where you can restore it for ${d} days. After ${d} days it is permanently erased and cannot be recovered.`, {okLabel:'Delete'});}
function confirmRecover(form, label){return confirmSubmit(form, `Restore the deleted archive for ${label}?\n\nIt will return to Archived Records with its original data.`, {okLabel:'Restore', danger:false});}
function confirmPurge(form, label){return confirmSubmit(form, `Permanently delete the archive for ${label}?\n\nThis erases its data now and cannot be undone.`, {okLabel:'Delete Now'});}
function confirmEmptyTrash(form, n){return confirmSubmit(form, `Empty Recently Deleted?\n\n${n} archive${n===1?'':'s'} will be permanently erased. This cannot be undone.`, {okLabel:'Empty'});}
</script>
<script src="admin_ajax.php"></script>
</body>
</html>