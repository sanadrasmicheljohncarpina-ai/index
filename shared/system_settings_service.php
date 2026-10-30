<?php
/**
 * Shared evaluation scheduling service.
 *
 * All schedule comparisons are made in Asia/Manila.  The submission decision
 * is computed from the persisted control_mode on every request so:
 *   schedule -> [start, end) according to configured datetimes
 *   open     -> always open (Force Open)
 *   closed   -> always closed (Force Closed)
 *
 * The closing instant is exclusive: at the exact configured end time, new
 * submissions are blocked.
 */

const SS_TIMEZONE = 'Asia/Manila';

date_default_timezone_set(SS_TIMEZONE);

function ss_timezone(): DateTimeZone {
    static $tz = null;
    if ($tz === null) $tz = new DateTimeZone(SS_TIMEZONE);
    return $tz;
}

function ss_now(): DateTimeImmutable {
    return new DateTimeImmutable('now', ss_timezone());
}

function ss_parse_datetime(?string $raw): ?DateTimeImmutable {
    $raw = trim((string)$raw);
    if ($raw === '') return null;

    $formats = [
        'Y-m-d\\TH:i',
        'Y-m-d H:i',
        'Y-m-d H:i:s',
        'Y-m-d',
    ];

    foreach ($formats as $format) {
        $dt = DateTimeImmutable::createFromFormat('!' . $format, $raw, ss_timezone());
        $errors = DateTimeImmutable::getLastErrors();
        $hasErrors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);
        if ($dt instanceof DateTimeImmutable && !$hasErrors) {
            return $dt->setTimezone(ss_timezone());
        }
    }

    return null;
}

function ss_structure_terms(): array {
    return [
        'college' => ['1st Semester', '2nd Semester', 'Summer'],
        'jhs'     => ['School Year'],
        'shs'     => ['School Year'],
    ];
}

function ss_structure_labels(): array {
    return [
        'college' => 'College',
        'jhs'     => 'Junior High School',
        'shs'     => 'Senior High School',
    ];
}

function ss_raw(mysqli $mysqli): array {
    $defaults = [
        'acad_year' => date('Y') . '-' . (date('Y') + 1),
        'acad_structure' => 'college',
        'acad_term' => '1st Semester',
        'auto_schedule' => 1,
        'control_mode' => 'schedule',
        'eval_start' => '',
        'eval_end' => '',
        'maintenance' => 0,
        'year_level_edit_mode' => 'locked',
        'rule_countdown' => 1,
        'publish_state' => 'published',
        'notify_eval_open' => 1,
        'notify_eval_closing' => 1,
        'notify_faculty_complete' => 1,
        'notify_reminders' => 0,
    ];

    $defaults['auto_schedule'] = (int)$defaults['auto_schedule'];
    // Scheduling always uses the application timezone; never trust a stored or browser timezone.
    $defaults['schedule_timezone'] = SS_TIMEZONE;

    $table = @$mysqli->query("SHOW TABLES LIKE 'system_settings'");
    if (!$table || $table->num_rows === 0) return $defaults;

    $res = @$mysqli->query("SELECT setting_key, setting_value FROM system_settings");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $key = (string)$row['setting_key'];
            if (array_key_exists($key, $defaults)) $defaults[$key] = $row['setting_value'];
            else $defaults[$key] = $row['setting_value'];
        }
        $res->free();
    }

    if (!isset($defaults['control_mode']) || !in_array($defaults['control_mode'], ['schedule','open','closed'], true)) {
        $defaults['control_mode'] = 'schedule';
    }
    if (!isset($defaults['year_level_edit_mode']) || !in_array($defaults['year_level_edit_mode'], ['locked','open'], true)) {
        $defaults['year_level_edit_mode'] = 'locked';
    }
    $defaults['auto_schedule'] = (int)!empty($defaults['auto_schedule']);
    $defaults['maintenance'] = (int)!empty($defaults['maintenance']);

    return $defaults;
}

/**
 * Return the live submission state. Schedule mode ignores server timezone,
 * auto DST behavior, and MySQL timezone because all comparisons use Manila.
 */
function ss_schedule_state(array $sys, ?DateTimeImmutable $now = null): array {
    $mode = $sys['control_mode'] ?? 'schedule';
    $now = $now ? $now->setTimezone(ss_timezone()) : ss_now();

    if ($mode === 'open') {
        return [
            'open' => true,
            'mode' => 'open',
            'reason' => 'force_open',
            'now' => $now,
            'start' => null,
            'end' => null,
        ];
    }

    if ($mode === 'closed') {
        return [
            'open' => false,
            'mode' => 'closed',
            'reason' => 'force_closed',
            'now' => $now,
            'start' => null,
            'end' => null,
        ];
    }

    $start = ss_parse_datetime($sys['eval_start'] ?? '');
    $end   = ss_parse_datetime($sys['eval_end'] ?? '');

    if (!$start || !$end || $end <= $start) {
        return [
            'open' => false,
            'mode' => 'schedule',
            'reason' => 'invalid_schedule',
            'now' => $now,
            'start' => $start,
            'end' => $end,
        ];
    }

    // [start, end): exact start opens, exact end closes.
    $open = $now >= $start && $now < $end;
    return [
        'open' => $open,
        'mode' => 'schedule',
        'reason' => $open ? 'scheduled_open' : ($now < $start ? 'before_start' : 'after_end'),
        'now' => $now,
        'start' => $start,
        'end' => $end,
    ];
}

function ss_evaluation_is_open(array $sys): bool {
    return (bool)ss_schedule_state($sys)['open'];
}

function ss_is_submission_open(mysqli $mysqli): bool {
    // Fresh DB read on every submission attempt prevents a stale page from
    // submitting after the configured closing instant.
    $sys = ss_raw($mysqli);
    return ss_evaluation_is_open($sys);
}

/**
 * Read the live scheduling state directly from system_settings.
 * This is intentionally independent of evaluation_periods.is_active: a
 * configured/current period is not the same thing as an open evaluation window.
 */
function ss_live_state(mysqli $mysqli): array {
    $sys = ss_raw($mysqli);
    return [
        'settings' => $sys,
        'state' => ss_schedule_state($sys),
    ];
}

/**
 * Guard an evaluator entry point. Call this before rendering a form and again
 * immediately before inserting a submission. Force Open / Force Closed are
 * preserved because ss_schedule_state() handles those modes first.
 */
function ss_require_evaluation_open(mysqli $mysqli, string $closedMessage = 'Evaluation is currently closed.'): void {
    $live = ss_live_state($mysqli);
    if (empty($live['state']['open'])) {
        $state = $live['state'];
        $detail = $closedMessage;
        if (($state['reason'] ?? '') === 'before_start' && !empty($state['start'])) {
            $detail .= ' It will open on ' . $state['start']->format('F j, Y g:i A') . ' (Asia/Manila).';
        } elseif (($state['reason'] ?? '') === 'after_end' && !empty($state['end'])) {
            $detail .= ' The scheduled window ended on ' . $state['end']->format('F j, Y g:i A') . ' (Asia/Manila).';
        } elseif (($state['reason'] ?? '') === 'invalid_schedule') {
            $detail .= ' No valid evaluation schedule is configured.';
        }
        http_response_code(403);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Evaluation Closed</title><style>body{font-family:Inter,Arial,sans-serif;background:#f8fafc;color:#172033;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}.card{max-width:640px;margin:24px;background:#fff;border:1px solid #dbe4ef;border-radius:16px;padding:32px;box-shadow:0 10px 30px rgba(15,23,42,.08)}h1{margin:0 0 10px;font-size:24px}p{margin:8px 0;color:#526581;line-height:1.6}.badge{display:inline-block;margin-bottom:16px;padding:6px 10px;border-radius:999px;background:#fff1f2;color:#b42318;border:1px solid #fecdd3;font-weight:700;font-size:12px}</style></head><body><div class="card"><div class="badge">EVALUATION CLOSED</div><h1>Evaluation is not currently available.</h1><p>' . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . '</p><p>The schedule is evaluated using the <strong>Asia/Manila</strong> timezone.</p></div></body></html>';
        exit;
    }
}

function ss_period_columns(mysqli $mysqli): array {
    $cols = [];
    $res = @$mysqli->query("SHOW COLUMNS FROM evaluation_periods");
    if ($res) {
        while ($row = $res->fetch_assoc()) $cols[strtolower($row['Field'])] = true;
        $res->free();
    }
    return [
        'start' => isset($cols['date_start']) ? 'date_start' : (isset($cols['start_date']) ? 'start_date' : null),
        'end'   => isset($cols['date_end']) ? 'date_end' : (isset($cols['end_date']) ? 'end_date' : null),
    ];
}

/**
 * Last error from ss_sync_evaluation_period() ('' when the last call succeeded).
 */
function ss_period_last_error(?string $set = null): string {
    static $err = '';
    if ($set !== null) $err = $set;
    return $err;
}

/**
 * Name of a UNIQUE index on evaluation_periods that covers school_year + semester,
 * or '' when there is none. Such an index would block a second round of the same term.
 */
function ss_find_unique_period_index(mysqli $mysqli): string {
    $byIndex = [];
    $res = @$mysqli->query("SHOW INDEX FROM evaluation_periods");
    if (!$res) return '';
    while ($r = $res->fetch_assoc()) {
        if ((int)$r['Non_unique'] === 0 && $r['Key_name'] !== 'PRIMARY') {
            $byIndex[$r['Key_name']][] = strtolower((string)$r['Column_name']);
        }
    }
    $res->free();
    foreach ($byIndex as $name => $cols) {
        if (in_array('school_year', $cols, true) && in_array('semester', $cols, true)) return (string)$name;
    }
    return '';
}

/**
 * Every evaluation period (round) of a year/term, newest first, each flagged
 * with whether it is currently archived.
 * @return array<int, array{id:int, archived:bool}>
 */
function ss_period_candidates(mysqli $mysqli, string $year, string $term): array {
    $hasArchive = false;
    $t = @$mysqli->query("SHOW TABLES LIKE 'system_archives'");
    if ($t) { $hasArchive = $t->num_rows > 0; $t->free(); }

    $join = $hasArchive ? "LEFT JOIN system_archives sa ON sa.period_id = ep.id AND sa.status = 'archived'" : '';
    $flag = $hasArchive ? '(sa.id IS NOT NULL)' : '0';
    $rows = [];
    $stmt = @$mysqli->prepare("SELECT ep.id, $flag AS archived FROM evaluation_periods ep $join WHERE ep.school_year=? AND ep.semester=? ORDER BY ep.id DESC");
    if (!$stmt) return $rows;
    $stmt->bind_param('ss', $year, $term);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $rows[] = ['id' => (int)$r['id'], 'archived' => (int)$r['archived'] === 1];
    $stmt->close();
    return $rows;
}

/** Newest round that is not archived (the preferred one wins when it still qualifies); 0 when none. */
function ss_pick_open_period(array $rows, int $preferredId = 0): int {
    foreach ($rows as $r) if (!$r['archived'] && $r['id'] === $preferredId) return $r['id'];
    foreach ($rows as $r) if (!$r['archived']) return $r['id'];
    return 0;
}

/**
 * Keep the selected academic period synchronized with the configured year /
 * term and schedule dates.  is_active identifies the current period; it is
 * not used as the submission-open flag.
 *
 * A year/term can have several rounds (evaluation_periods rows). The current
 * round is remembered in system_settings.current_period_id. An archived round is
 * never reused: when every round of the configured year/term is archived, a new
 * round ("... (Round 2)") is created, so each round can be archived on its own.
 */
function ss_sync_evaluation_period(mysqli $mysqli, array $sys): int {
    ss_period_last_error('');
    $exists = @$mysqli->query("SHOW TABLES LIKE 'evaluation_periods'");
    if (!$exists || $exists->num_rows === 0) return 0;

    $year = trim((string)($sys['acad_year'] ?? ''));
    $structure = (string)($sys['acad_structure'] ?? 'college');
    $term = trim((string)($sys['acad_term'] ?? '1st Semester'));
    $terms = ss_structure_terms();
    if (!isset($terms[$structure]) || !in_array($term, $terms[$structure], true)) {
        $term = $terms[$structure][0] ?? '1st Semester';
    }

    $label = trim($year . ' — ' . $term, ' —');
    $preferred = (int)($sys['current_period_id'] ?? 0);
    $periodId = ss_pick_open_period(ss_period_candidates($mysqli, $year, $term), $preferred);

    if ($periodId <= 0) {
        // Every round of this year/term is archived (or none exists yet): start a new round.
        // The lock stops simultaneous requests from each creating their own.
        $got = false;
        try {
            $lk = @$mysqli->query("SELECT GET_LOCK('pbi_period_sync', 5) AS l");
            if ($lk) { $got = (int)($lk->fetch_assoc()['l'] ?? 0) === 1; $lk->free(); }

            $rows = ss_period_candidates($mysqli, $year, $term);
            $periodId = ss_pick_open_period($rows);
            if ($periodId <= 0) {
                $round = count($rows) + 1;
                $newLabel = $round > 1 ? $label . ' (Round ' . $round . ')' : $label;
                $stmt = @$mysqli->prepare("INSERT INTO evaluation_periods (period_label, school_year, semester) VALUES (?,?,?)");
                if ($stmt) {
                    $stmt->bind_param('sss', $newLabel, $year, $term);
                    if ($stmt->execute()) {
                        $periodId = (int)$mysqli->insert_id;
                    } else {
                        ss_period_last_error($stmt->error);
                    }
                    $stmt->close();
                } else {
                    ss_period_last_error($mysqli->error);
                }
            }
        } catch (Throwable $e) {
            ss_period_last_error($e->getMessage());
            $periodId = 0;
        } finally {
            if ($got) @$mysqli->query("SELECT RELEASE_LOCK('pbi_period_sync')");
        }
    }

    if ($periodId <= 0) {
        error_log('ss_sync_evaluation_period: no evaluation period for ' . $label . ' (' . ss_period_last_error() . ')');
        return 0;
    }

    // Remember which round is current so it stays stable between requests.
    if ($preferred !== $periodId) {
        $stmt = @$mysqli->prepare("INSERT INTO system_settings(setting_key,setting_value) VALUES('current_period_id',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        if ($stmt) {
            $pv = (string)$periodId;
            $stmt->bind_param('s', $pv);
            $stmt->execute();
            $stmt->close();
        }
    }

    $start = ss_parse_datetime($sys['eval_start'] ?? '');
    $end = ss_parse_datetime($sys['eval_end'] ?? '');
    $cols = ss_period_columns($mysqli);

    if ($cols['start'] && $cols['end'] && $start && $end) {
        // The label is only filled in when empty, so a round keeps its "(Round N)" name.
        $sql = "UPDATE evaluation_periods SET {$cols['start']}=?, {$cols['end']}=?, period_label=COALESCE(NULLIF(period_label,''),?) WHERE id=?";
        $stmt = @$mysqli->prepare($sql);
        if ($stmt) {
            $startDate = $start->format('Y-m-d');
            $endDate = $end->format('Y-m-d');
            $stmt->bind_param('sssi', $startDate, $endDate, $label, $periodId);
            $stmt->execute();
            $stmt->close();
        }
    }

    @$mysqli->query("UPDATE evaluation_periods SET is_active=0");
    $stmt = @$mysqli->prepare("UPDATE evaluation_periods SET is_active=1 WHERE id=?");
    if ($stmt) {
        $stmt->bind_param('i', $periodId);
        $stmt->execute();
        $stmt->close();
    }

    return $periodId;
}

function ss_sync_from_database(mysqli $mysqli): array {
    $sys = ss_raw($mysqli);
    ss_sync_evaluation_period($mysqli, $sys);
    return $sys;
}

function get_system_settings(mysqli $mysqli): array {
    $sys = ss_raw($mysqli);
    $periodId = ss_sync_evaluation_period($mysqli, $sys);
    $state = ss_schedule_state($sys);

    $mode = $sys['control_mode'] ?? 'schedule';
    $isScheduledBeforeStart = ($mode === 'schedule' && $state['reason'] === 'before_start');
    $status = [
        'label' => $isScheduledBeforeStart ? 'Scheduled' : ($state['open'] ? 'Open' : 'Closed'),
        'cls' => $isScheduledBeforeStart ? 'scheduled' : ($state['open'] ? 'open' : 'closed'),
        'mode' => $mode,
        'reason' => $state['reason'],
    ];

    // Always expose the configured schedule, even when Force Open/Force Closed
    // overrides its effect on accessibility. This gives dashboards and forms a
    // consistent view of the EA-configured opening/closing timestamps.
    $evalStartDt = ss_parse_datetime($sys['eval_start'] ?? '');
    $evalEndDt   = ss_parse_datetime($sys['eval_end'] ?? '');
    $formatSchedule = static function (?DateTimeImmutable $dt): string {
        return $dt ? $dt->setTimezone(ss_timezone())->format('F j, Y · g:i A') . ' (Asia/Manila)' : '';
    };

    if ($mode === 'open') {
        $message = [
            'headline' => 'Evaluation is open.',
            'sub' => 'Force Open is overriding the configured schedule.'
        ];
    } elseif ($mode === 'closed') {
        $message = [
            'headline' => 'Evaluation is closed.',
            'sub' => 'Force Closed is overriding the configured schedule.'
        ];
    } elseif ($state['reason'] === 'before_start' && $state['start']) {
        $message = [
            'headline' => 'Evaluation is scheduled to open.',
            'sub' => 'Opens ' . $state['start']->format('M j, Y g:i A') . ' (Asia/Manila).'
        ];
    } elseif ($state['reason'] === 'after_end' && $state['end']) {
        $message = [
            'headline' => 'Evaluation is closed.',
            'sub' => 'The scheduled window ended ' . $state['end']->format('M j, Y g:i A') . ' (Asia/Manila).'
        ];
    } elseif ($state['reason'] === 'invalid_schedule') {
        $message = [
            'headline' => 'No valid evaluation schedule is configured.',
            'sub' => 'Configure the opening and closing date/time in System Settings.'
        ];
    } else {
        $message = [
            'headline' => 'Evaluation is open.',
            'sub' => 'The current time is inside the configured schedule (Asia/Manila).'
        ];
    }

    return array_merge($sys, [
        'academic_year' => $sys['acad_year'] ?? '',
        'academic_term' => $sys['acad_term'] ?? '',
        'academic_structure' => $sys['acad_structure'] ?? 'college',
        'academic_structure_label' => ss_structure_labels()[$sys['acad_structure'] ?? 'college'] ?? 'College',
        'period_id' => $periodId,
        'is_open_for_submission' => (int)$state['open'],
        'eval_start' => $sys['eval_start'] ?? '',
        'eval_end' => $sys['eval_end'] ?? '',
        'countdown_enabled' => (int)!empty($sys['rule_countdown']),
        'status' => $status,
        'message' => $message,
        'notifications' => [],
        'schedule_timezone' => SS_TIMEZONE,
        'eval_start_display' => $formatSchedule($evalStartDt),
        'eval_end_display' => $formatSchedule($evalEndDt),
    ]);
}

/**
 * Compatibility accessor for Principal/Dean school-head evaluation pages.
 *
 * The school-head pages use the same global system settings and schedule as
 * the dashboards. The role argument is retained for compatibility; it does
 * not create a separate settings source or alter Force Open / Force Closed.
 */
function ss_school_head_role_applicable(array $sys, string $role): bool {
    $role = strtolower(trim($role));
    $structure = strtolower(trim((string)($sys['acad_structure'] ?? $sys['academic_structure'] ?? 'college')));
    $term = trim((string)($sys['acad_term'] ?? $sys['academic_term'] ?? ''));

    if ($role === 'dean') {
        return $structure === 'college'
            && in_array($term, ['1st Semester', '2nd Semester', 'Summer'], true);
    }

    if ($role === 'principal') {
        return in_array($structure, ['jhs', 'shs'], true)
            && $term === 'School Year';
    }

    return false;
}

function ss_school_head_evaluation_open(array $sys, string $role): bool {
    // Force Open / Force Closed remain authoritative for the schedule, but
    // they cannot make an inapplicable school-head evaluation available.
    return ss_school_head_role_applicable($sys, $role)
        && !empty($sys['is_open_for_submission']);
}

function get_school_head_settings(mysqli $mysqli, string $role = 'principal'): array {
    $sys = get_system_settings($mysqli);
    $sys['school_head_role'] = strtolower(trim($role));
    $sys['school_head_applicable'] = ss_school_head_role_applicable($sys, $role);
    $sys['school_head_is_open'] = ss_school_head_evaluation_open($sys, $role);
    return $sys;
}

/**
 * Maintenance gate. Call this near the top of every page/endpoint that a
 * logged-in user can open (Student, Teacher, Dean, Staff, Principal dashboards),
 * AFTER session_start() and db.php:
 *
 *     require_once dirname(__DIR__) . '/shared/system_settings_service.php';
 *     ss_enforce_maintenance($mysqli);
 *
 * Do NOT call it on login pages: the Executive Assistant must still be able to
 * sign in while maintenance is on.
 *
 * When Maintenance Mode is ON, everyone except the exempt roles is sent to
 * /index/maintenance.php?return=<page they were on>, so maintenance.php can put
 * them back on that same page automatically once maintenance is turned off.
 */
function ss_enforce_maintenance(mysqli $mysqli, array $exemptRoles = ['admin', 'superadmin', 'executive_assistant']): void {
    $sys = ss_raw($mysqli);
    if (empty($sys['maintenance'])) return;

    $role = strtolower(trim((string)($_SESSION['role'] ?? '')));
    if (in_array($role, $exemptRoles, true)) return;

    // Never gate the maintenance page itself (prevents a redirect loop).
    if (basename((string)($_SERVER['SCRIPT_NAME'] ?? '')) === 'maintenance.php') return;

    // Background requests (fetch/XHR) get a 503 instead of an HTML redirect.
    $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
        || stripos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') === 0;
    if ($isAjax) {
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'maintenance']);
        exit;
    }

    $return = (string)($_SERVER['REQUEST_URI'] ?? '');
    $url = '/index/maintenance.php' . ($return !== '' ? '?return=' . rawurlencode($return) : '');
    if (!headers_sent()) header('Location: ' . $url);
    exit;
}