<?php
// session_bootstrap.php — include this BEFORE session_start() everywhere
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once 'db.php';
require_once dirname(__DIR__) . '/shared/system_settings_service.php';

// ── AUTH GUARD ────────────────────────────────────────────
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'dean') {
    header("Location: dean_login.php");
    exit;
}

// ── PHASE 2 REWRITE (kept) ───────────────────────────────────────────
// This page shows ONLY Higher Education student evaluation-submission
// participation — no evaluation scores. Students fetched the same way
// Manage Privileged Accounts does (role='student', account_status=
// 'approved', is_active=1).
//
// REDESIGN NOTE (mockup-driven): added Reset button, colored-initial
// avatars, sortable Student/Year Level columns, pagination
// (5/page), and Export List. No ID Number column — student accounts
// don't collect a student ID number at registration, so the mockup's
// "2024-123456" column was dropped rather than faked. The submission-
// status lookup was rewritten from one query per student to two bulk
// queries total, since pagination implies this page now expects
// hundreds of rows rather than a handful.
//
// FIX (this pass) — two compounding bugs that made College students never
// show up here at all:
//   1. This page was still selecting/filtering on `users.course`, a column
//      that was dropped from `users` in the DB redesign (student
//      course/program is no longer stored on the user row at all). Every
//      query referencing it was silently throwing and getting swallowed by
//      safe_rows()'s try/catch, returning an empty array — so NO students
//      showed up, not just a College-specific problem. The Program
//      dropdown/column/filter/export field are removed below; there is
//      currently no replacement source for student program, matching the
//      choice already made on dean_evaluate.php for the same removed
//      column.
//   2. College-membership was being decided by `education_level='college'`
//      alone. manage_privileged_accounts.php — the actual admin approval
//      flow — never checks education_level for students; it classifies
//      them purely from the year_level string via classify_student_level()
//      (pattern match: "college" in the string, or a leading "1st/2nd/3rd/
//      4th Year"). That's the real source of truth for "is this student
//      College", so this page now matches it (year_level pattern, with
//      education_level='college' kept as a harmless OR fallback) instead
//      of trusting education_level alone.

function safe_scalar(mysqli $mysqli, string $sql, string $types = '', array $params = []) {
    try {
        $stmt = @$mysqli->prepare($sql);
        if (!$stmt) return null;
        if ($types !== '') { $stmt->bind_param($types, ...$params); }
        if (!@$stmt->execute()) { $stmt->close(); return null; }
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ? reset($row) : null;
    } catch (mysqli_sql_exception $e) {
        return null;
    }
}
function safe_rows(mysqli $mysqli, string $sql, string $types = '', array $params = []): array {
    try {
        $stmt = @$mysqli->prepare($sql);
        if (!$stmt) return [];
        if ($types !== '') { $stmt->bind_param($types, ...$params); }
        if (!@$stmt->execute()) { $stmt->close(); return []; }
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    } catch (mysqli_sql_exception $e) {
        return [];
    }
}

// ── DEAN PROFILE (for sidebar) ─────────────────────────────
$stmt = $mysqli->prepare("SELECT full_name, designation, photo FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();
$photo_src = !empty($me['photo']) ? UPLOAD_URL . $me['photo'] : UPLOAD_URL . 'pbi_logo';

// ── GLOBAL SYSTEM SETTINGS (single source of truth) ───────────────────
$settings = get_system_settings($mysqli);
$structureActive = ($settings['academic_structure'] === 'college');
$period_id_int   = $settings['period_id'] ?? 0;
$hasPeriod       = $period_id_int > 0;
$evalOpen        = $settings['is_open_for_submission'];

const HIGHER_ED_LABEL = 'Higher Education';
const REMINDER_COOLDOWN_HOURS = 24; // must match dean_send_reminder.php

// ── FILTER + SORT + PAGE INPUT (GET) ──────────────────────────────────
$search    = trim($_GET['search'] ?? '');
$yearLevel = trim($_GET['year_level'] ?? '');
$status    = trim($_GET['status'] ?? '');

// Keep the old query-string values working after the tracker redesign.
if ($status === 'pending')   $status = 'not_started';
if ($status === 'submitted') $status = 'completed';

$validStatus = ['', 'not_started', 'in_progress', 'completed'];
if (!in_array($status, $validStatus, true)) $status = '';

// The Dean tracker is limited to College.  The filter below normalizes the
// common year-level formats already used by the system (e.g. 1st_year,
// 1st Year, 1st Year College) without changing the stored database values.
$yearLevelOptions = [
    '1st_year' => '1st Year',
    '2nd_year' => '2nd Year',
    '3rd_year' => '3rd Year',
    '4th_year' => '4th Year',
];
$yearLevelRegex = [
    '1st_year' => '(^|[^0-9a-z])(1st|first)[[:space:]_-]*year([^0-9a-z]|$)',
    '2nd_year' => '(^|[^0-9a-z])(2nd|second)[[:space:]_-]*year([^0-9a-z]|$)',
    '3rd_year' => '(^|[^0-9a-z])(3rd|third)[[:space:]_-]*year([^0-9a-z]|$)',
    '4th_year' => '(^|[^0-9a-z])(4th|fourth)[[:space:]_-]*year([^0-9a-z]|$)',
];
if (!isset($yearLevelRegex[$yearLevel])) $yearLevel = '';

$sortableColumns = ['name' => 'full_name', 'year_level' => 'year_level'];
$sort = $_GET['sort'] ?? 'name';
if (!in_array($sort, array_merge(array_keys($sortableColumns), ['status']), true)) $sort = 'name';
$dir = (strtolower($_GET['dir'] ?? 'asc') === 'desc') ? 'desc' : 'asc';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 5;

// Defaults so the page still renders the structure-mismatch state.
$students           = [];
$pageStudents       = [];
$studentsAssigned   = 0;
$studentsSubmitted  = 0;
$pendingStudents    = 0;
$remainingStudents  = 0;
$completionPct      = 0;
$totalPages         = 1;

if ($structureActive) {
    // ── QUESTIONNAIRE CONFIGURATION / REQUIRED TARGETS ─────────────────
    // Required is NOT derived from submitted rows. It is the number of
    // currently configured College Student-Evaluation targets that this Dean
    // is responsible for. This keeps students with zero submissions at
    // 0 / N instead of incorrectly reporting 0 / 0.
    // The shared Faculty question pool has existed under both target_type='Teacher'
    // (legacy) and target_type='Faculty' (current questionnaire model). Treat
    // either as the Faculty questionnaire so Required never collapses to 0.
    $teacherQuestionCount = (int)(safe_scalar($mysqli,
        "SELECT COUNT(*) FROM evaluation_questions WHERE eval_type='student' AND target_type IN ('Teacher','Faculty')"
    ) ?? 0);
    $multiRoleQuestionCount = (int)(safe_scalar($mysqli,
        "SELECT COUNT(*) FROM evaluation_questions WHERE eval_type='student' AND target_type='Multi-Role'"
    ) ?? 0);
    if ($multiRoleQuestionCount === 0) {
        $multiRoleQuestionCount = (int)(safe_scalar($mysqli,
            "SELECT COUNT(*) FROM user_questions WHERE eval_type='student' AND target_type='Multi-Role'"
        ) ?? 0);
    }

    // Faculty in the Dean's College scope: teachers and staff who have a
    // College academic level / College year-level assignment. Teaching Staff
    // who are also Staff remain included here as Faculty as required by the
    // existing personnel rules.
    $collegeFacultyRequired = $teacherQuestionCount > 0 ? (int)(safe_scalar($mysqli, "
        SELECT COUNT(*) FROM users u
        WHERE u.role IN ('teacher','staff')
          AND u.is_active=1
          AND u.account_status='approved'
          AND (
              u.academic_level='college'
              OR EXISTS (
                  SELECT 1 FROM user_year_levels yl
                  WHERE yl.user_id=u.id
                    AND LOWER(COALESCE(yl.year_level,'')) REGEXP '(^|[^0-9a-z])(college|1st|2nd|3rd|4th)[[:space:]_-]*year([^0-9a-z]|$)'
              )
          )
          AND (
              u.role='teacher'
              OR u.secondary_role='teacher'
              OR u.sector='Teacher'
              OR EXISTS (SELECT 1 FROM teaching_assignments ta WHERE ta.user_id=u.id)
              OR EXISTS (SELECT 1 FROM user_year_levels yl2 WHERE yl2.user_id=u.id)
          )
    ") ?? 0) : 0;

    // Staff is a Student Evaluation context for approved Staff accounts. The
    // actual question bank is per-person, so only Staff with at least one
    // Student/Staff question are counted as required targets.
    $staffRequired = (int)(safe_scalar($mysqli, "
        SELECT COUNT(*) FROM users u
        WHERE u.role='staff'
          AND u.is_active=1
          AND u.account_status='approved'
          AND EXISTS (
              SELECT 1 FROM user_questions uq
              WHERE uq.user_id=u.id
                AND uq.eval_type='student'
                AND uq.target_type='Staff'
          )
    ") ?? 0);

    // Multi-Role is an additional Student Evaluation context. The same rules
    // used by the Questionnaire page are mirrored here: explicit Teacher+Staff
    // roles, or a Staff account that also has a teaching/year-level assignment.
    $multiRoleRequired = $multiRoleQuestionCount > 0 ? (int)(safe_scalar($mysqli, "
        SELECT COUNT(*) FROM users u
        WHERE u.role IN ('teacher','staff')
          AND u.is_active=1
          AND u.account_status='approved'
          AND (
              (u.role='teacher' AND LOWER(COALESCE(u.secondary_role,''))='staff')
              OR (u.role='staff' AND (
                    LOWER(COALESCE(u.secondary_role,''))='teacher'
                    OR EXISTS (SELECT 1 FROM teaching_assignments ta WHERE ta.user_id=u.id)
                    OR EXISTS (SELECT 1 FROM user_year_levels yl WHERE yl.user_id=u.id)
              ))
              OR LOWER(COALESCE(u.designation,'')) REGEXP 'teacher.*staff|staff.*teacher'
          )
          AND (
              u.academic_level='college'
              OR EXISTS (
                  SELECT 1 FROM user_year_levels yl2
                  WHERE yl2.user_id=u.id
                    AND LOWER(COALESCE(yl2.year_level,'')) REGEXP '(^|[^0-9a-z])(college|1st|2nd|3rd|4th)[[:space:]_-]*year([^0-9a-z]|$)'
              )
              OR EXISTS (SELECT 1 FROM teaching_assignments ta2 WHERE ta2.user_id=u.id)
          )
    ") ?? 0) : 0;

    // College students evaluate the Dean as the College School Head. The
    // Principal belongs to the Higher-School scope and is not required here.
    $deanHeadRequired = (int)(safe_scalar($mysqli, "
        SELECT COUNT(*) FROM users u
        WHERE u.role='dean'
          AND u.is_active=1
          AND u.account_status='approved'
          AND EXISTS (
              SELECT 1 FROM user_questions uq
              WHERE uq.user_id=u.id
                AND uq.eval_type='student'
                AND uq.target_type='Dean'
          )
    ") ?? 0);

    $requiredTotal = $collegeFacultyRequired + $staffRequired + $multiRoleRequired + $deanHeadRequired;

    // ── STUDENTS IN SCOPE ───────────────────────────────────────────────
    $whereSql = "role='student' AND is_active=1 AND account_status='approved'
        AND (education_level IN ('college','higher_ed')
             OR year_level REGEXP '^(1st|2nd|3rd|4th)[[:space:]_-]*Year'
             OR year_level LIKE '%College%')";
    $types = '';
    $params = [];

    if ($search !== '') {
        $whereSql .= " AND full_name LIKE ?";
        $types .= 's';
        $params[] = '%' . $search . '%';
    }
    if ($yearLevel !== '') {
        $whereSql .= " AND LOWER(COALESCE(year_level,'')) REGEXP ?";
        $types .= 's';
        $params[] = strtolower($yearLevelRegex[$yearLevel]);
    }

    $orderSql = ($sort !== 'status') ? ($sortableColumns[$sort] . ' ' . strtoupper($dir)) : 'full_name ASC';

    $allRows = safe_rows($mysqli, "
        SELECT id, full_name, photo, year_level
        FROM users WHERE $whereSql ORDER BY $orderSql
    ", $types, $params);

    // ── ACTUAL SUBMISSIONS / COMPLETED CONTEXTS ─────────────────────────
    // A submission is counted against the same target/context only once.
    // eval_bucket is the canonical Student Evaluation bucket written by the
    // evaluation form (Faculty / Staff / Multi-Role / School Head). Older rows
    // may not have it, so evaluation_context and answered user questions are
    // used as safe fallbacks. Do not require et.level='college' here: legacy
    // and current student forms can legitimately leave level NULL. The
    // evaluator's own College eligibility is enforced by the student roster.
    $completedMap = [];
    $allIds = array_map('intval', array_column($allRows, 'id'));
    if ($hasPeriod && !empty($allIds)) {
        $ph = implode(',', array_fill(0, count($allIds), '?'));
        $completedRows = safe_rows($mysqli, "
            SELECT
                et.evaluator_id,
                COUNT(DISTINCT CASE
                    WHEN LOWER(COALESCE(et.eval_bucket,'')) IN ('faculty','teacher')
                      OR LOWER(COALESCE(et.evaluation_context,'')) IN ('teacher','faculty')
                    THEN CONCAT('teacher:', et.target_user_id) END) AS teacher_completed,
                COUNT(DISTINCT CASE
                    WHEN LOWER(COALESCE(et.eval_bucket,'')) IN ('staff','non-teaching staff','non_teaching_staff')
                      OR LOWER(COALESCE(et.evaluation_context,''))='staff'
                    THEN CONCAT('staff:', et.target_user_id) END) AS staff_completed,
                COUNT(DISTINCT CASE
                    WHEN LOWER(COALESCE(et.eval_bucket,'')) IN ('multi-role','multi_role')
                      OR LOWER(COALESCE(et.evaluation_context,'')) IN ('multi-role','multi_role')
                      OR EXISTS (
                          SELECT 1
                          FROM questionnaire_answers qam
                          JOIN user_questions uqm ON uqm.id=qam.user_question_id
                          WHERE qam.tracker_id=et.id
                            AND uqm.eval_type='student'
                            AND uqm.target_type='Multi-Role'
                      )
                    THEN CONCAT('multi:', et.target_user_id) END) AS multi_completed,
                COUNT(DISTINCT CASE
                    WHEN LOWER(COALESCE(et.eval_bucket,'')) IN ('school head','school_head','dean','principal')
                      OR LOWER(COALESCE(et.evaluation_context,''))='school_head'
                      OR EXISTS (
                          SELECT 1 FROM users uh
                          WHERE uh.id=et.target_user_id AND uh.role IN ('dean','principal')
                      )
                    THEN CONCAT('head:', et.target_user_id) END) AS head_completed,
                MAX(CASE
                    WHEN et.status IN ('submitted','approved') OR et.submitted_at IS NOT NULL
                    THEN et.submitted_at END) AS latest_submitted_at
            FROM evaluation_tracker et
            WHERE et.eval_type='student'
              AND et.period_id=?
              AND et.evaluator_id IN ($ph)
              AND et.status IN ('submitted','approved')
            GROUP BY et.evaluator_id
        ", 'i' . str_repeat('i', count($allIds)), array_merge([$period_id_int], $allIds));

        foreach ($completedRows as $row) {
            $completedMap[(int)$row['evaluator_id']] = [
                'teacher'  => max(0, (int)$row['teacher_completed']),
                'staff'    => max(0, (int)$row['staff_completed']),
                'multi'    => max(0, (int)$row['multi_completed']),
                'head'     => max(0, (int)$row['head_completed']),
                'latest_at'=> $row['latest_submitted_at'] ?? null,
            ];
        }
    }

    foreach ($allRows as $s) {
        $sid = (int)$s['id'];
        $rawYear = trim((string)($s['year_level'] ?? ''));
        $collegeYear = 'College';
        foreach ($yearLevelRegex as $key => $rx) {
            if ($rawYear !== '' && preg_match('/' . $rx . '/i', $rawYear)) {
                $collegeYear = $yearLevelOptions[$key];
                break;
            }
        }

        $c = $completedMap[$sid] ?? ['teacher'=>0,'staff'=>0,'multi'=>0,'head'=>0,'latest_at'=>null];
        $completed = min($requiredTotal, $c['teacher'] + $c['staff'] + $c['multi'] + $c['head']);
        $progress = $requiredTotal > 0 ? min(100, (int)round(($completed / $requiredTotal) * 100)) : 0;

        if ($requiredTotal > 0 && $completed >= $requiredTotal) {
            $state = 'completed';
            $stateLabel = 'Completed';
        } elseif ($completed > 0) {
            $state = 'in_progress';
            $stateLabel = 'In Progress';
        } else {
            $state = 'not_started';
            $stateLabel = 'Not Started';
        }

        $students[] = [
            'id'           => $sid,
            'name'         => $s['full_name'],
            'photo'        => !empty($s['photo']) ? UPLOAD_URL . $s['photo'] : UPLOAD_URL . 'pbi_logo',
            'year_level'   => $collegeYear,
            'status'       => $state,
            'status_label' => $stateLabel,
            'required'     => $requiredTotal,
            'completed'    => $completed,
            'progress'     => $progress,
            'submitted_at' => $c['latest_at'],
        ];
    }

    // Status sorting is derived from the calculated participation state.
    if ($sort === 'status') {
        $rank = ['not_started' => 1, 'in_progress' => 2, 'completed' => 3];
        usort($students, function ($a, $b) use ($dir, $rank) {
            $cmp = ($rank[$a['status']] ?? 0) <=> ($rank[$b['status']] ?? 0);
            if ($cmp === 0) $cmp = strcasecmp($a['name'], $b['name']);
            return $dir === 'desc' ? -$cmp : $cmp;
        });
    }

    if ($status !== '') {
        $students = array_values(array_filter($students, fn($s) => $s['status'] === $status));
    }

    $studentsAssigned  = count($students);
    $studentsSubmitted = count(array_filter($students, fn($s) => $s['status'] === 'completed'));
    $pendingStudents   = count(array_filter($students, fn($s) => $s['status'] !== 'completed'));
    $remainingStudents = $pendingStudents;
    $completionPct     = $studentsAssigned > 0 ? (int)round($studentsSubmitted / $studentsAssigned * 100) : 0;

    // ── EXPORT (full filtered set) ─────────────────────────────────────
    if (($_GET['export'] ?? '') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="dean_tracker_export_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Student', 'Level', 'Required', 'Completed', 'Status', 'Progress']);
        foreach ($students as $s) {
            fputcsv($out, [
                $s['name'], 'College', $s['required'], $s['completed'] . ' / ' . $s['required'],
                $s['status_label'], $s['progress'] . '%',
            ]);
        }
        fclose($out);
        $mysqli->close();
        exit;
    }

    $totalPages = max(1, (int)ceil($studentsAssigned / $perPage));
    $page = max(1, min($totalPages, $page));
    $pageStudents = array_slice($students, ($page - 1) * $perPage, $perPage);

    // ── LIVE JSON ENDPOINT ───────────────────────────────────────────────
    // The page polls itself every 5 seconds. No separate endpoint or schema
    // change is required, and the live query always uses the current period.
    if (($_GET['ajax'] ?? '') === '1') {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo json_encode([
            'ok' => true,
            'structureActive' => $structureActive,
            'hasPeriod' => $hasPeriod,
            'evalOpen' => $evalOpen,
            'requiredTotal' => $requiredTotal,
            'studentsAssigned' => $studentsAssigned,
            'studentsSubmitted' => $studentsSubmitted,
            'pendingStudents' => $pendingStudents,
            'remainingStudents' => $remainingStudents,
            'completionPct' => $completionPct,
            'totalPages' => $totalPages,
            'page' => $page,
            'students' => $pageStudents,
            'checkedAt' => date('c'),
        ], JSON_UNESCAPED_SLASHES);
        $mysqli->close();
        exit;
    }
}

$scopeParts = [];
if ($yearLevel !== '') $scopeParts[] = $yearLevelOptions[$yearLevel] ?? $yearLevel;
$scopeLabel = $scopeParts ? implode(' — ', $scopeParts) : HIGHER_ED_LABEL . ' Division';

// Rebuilds the current query string with overrides — used by sorting,
// filtering and pagination links.
function tracker_qs(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
    if (!isset($overrides['page'])) $params['page'] = 1;
    return htmlspecialchars('?' . http_build_query($params));
}
function tracker_sort_url(string $col, string $curSort, string $curDir): string {
    $newDir = ($curSort === $col && $curDir === 'asc') ? 'desc' : 'asc';
    return tracker_qs(['sort' => $col, 'dir' => $newDir]);
}
function tracker_sort_icon(string $col, string $curSort, string $curDir): string {
    if ($curSort !== $col) return 'fa-sort';
    return $curDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
}

// Small status helpers keep the table markup readable.
function tracker_initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $first = $parts[0][0] ?? '';
    $last  = count($parts) > 1 ? $parts[count($parts) - 1][0] : '';
    return strtoupper($first . $last);
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI — Evaluation Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{
  --page:#F5F8FC; --card:#FFFFFF; --line:#DCE7F1; --line-strong:#9FB2C3;
  --text:#12263A; --muted:#6D8194; --muted-2:#8CA0B1;
  --teal:#19B39D; --teal-soft:#E9F8F5; --teal-border:#74CFC3;
  --green:#0F9F6E; --green-soft:#EAF8F2;
  --amber:#B7791F; --amber-soft:#FFF8E8;
  --blue:#2563EB; --shadow:0 4px 16px rgba(28,64,92,.07);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{background:var(--page);}
body{min-height:100vh;background:var(--page);font-family:'DM Sans',system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:var(--text);display:flex;}

/* Keep the existing Dean navigation; only the tracker content is restyled. */
.sidebar{width:250px;flex-shrink:0;background:rgba(23,42,69,.96);border-right:1px solid rgba(255,255,255,.08);min-height:100vh;padding:28px 20px;display:flex;flex-direction:column;}
.sb-profile{text-align:center;margin-bottom:26px}.sb-photo{width:72px;height:72px;border-radius:50%;object-fit:cover;border:2.5px solid #7C5FD9;box-shadow:0 0 18px rgba(124,95,217,.4);margin:0 auto 10px;display:block}.sb-name{font-weight:700;font-size:15px;color:#fff}.sb-role{font-size:11px;color:#9C85F0;text-transform:uppercase;letter-spacing:.6px;margin-top:2px}.sb-scope{font-size:10px;color:#A0B3C6;margin-top:4px}.sb-nav{display:flex;flex-direction:column;gap:4px;margin-top:10px}.sb-nav a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:#A0B3C6;text-decoration:none;font-size:14px;font-weight:500;transition:background .2s,color .2s}.sb-nav a:hover,.sb-nav a.active{background:rgba(124,95,217,.15);color:#fff}.sb-nav a i{width:18px;text-align:center;color:#9C85F0}.sb-logout{margin-top:auto}.sb-logout a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:#fca5a5;text-decoration:none;font-size:14px;font-weight:500}.sb-logout a:hover{background:rgba(240,84,84,.12)}

.main{flex:1;min-width:0;padding:34px 40px 42px;}
.page-header{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;flex-wrap:wrap;margin-bottom:20px;}
.page-title{font-size:28px;font-weight:700;letter-spacing:-.02em;color:var(--text)}
.page-sub{font-size:13px;color:var(--muted);margin-top:5px}
.period-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 13px;border-radius:999px;border:1px solid var(--line);background:#fff;color:#587086;font-size:12px;font-weight:700;box-shadow:0 1px 2px rgba(15,23,42,.03)}
.period-badge i{color:var(--teal)}
.period-badge.closed{background:#FFF4F5;border-color:#F2C7CC;color:#A94250}.period-badge.closed i{color:#D6455D}.period-badge.amber{background:#FFF8E8;border-color:#F4D7A0;color:#9A650F}.period-badge.gray{color:var(--muted)}

.structure-note{display:flex;align-items:flex-start;gap:12px;padding:16px 18px;background:#fff;border:1px solid var(--line);border-radius:12px;margin-bottom:18px;box-shadow:var(--shadow)}
.structure-note i{color:var(--blue);font-size:17px;margin-top:2px}.structure-note p{font-size:13px;color:#445B70;line-height:1.6}.structure-note p b{color:var(--text)}

.tracker-card{background:var(--card);border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow);overflow:visible}
.tracker-toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 18px 14px;border-bottom:1px solid #E7EEF4;flex-wrap:wrap}
.tracker-heading{display:flex;align-items:center;gap:10px}.tracker-heading h2{font-size:18px;font-weight:700;color:var(--text)}.tracker-heading .count{font-size:12px;color:var(--muted)}
.toolbar-actions{display:flex;align-items:center;gap:8px;position:relative}
.filter-wrap{position:relative}.filter-toggle,.export-btn{height:36px;padding:0 13px;display:inline-flex;align-items:center;gap:8px;border-radius:8px;font-size:12px;font-weight:700;font-family:inherit;text-decoration:none;cursor:pointer}
.filter-toggle{background:#fff;border:1px solid #C9D7E2;color:#334C60}.filter-toggle:hover{background:#F8FAFC;border-color:#AFC1D0}.filter-toggle.active{border-color:var(--teal-border);color:#128D7E;background:var(--teal-soft)}
.filter-count{min-width:18px;height:18px;padding:0 5px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;background:var(--teal);color:#fff;font-size:10px;line-height:1}
.export-btn{background:#F4FBFA;border:1px solid #BEE6DF;color:#138D7D}.export-btn:hover{background:#E8F7F4}

.filter-menu{position:absolute;right:0;top:44px;width:310px;background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 12px 30px rgba(30,70,100,.12);padding:14px;z-index:30;display:none}
.filter-menu.open{display:block}.filter-menu-title{font-size:12px;font-weight:800;color:var(--text);margin-bottom:10px}.filter-field{display:flex;flex-direction:column;gap:6px;margin-bottom:10px}.filter-field label{font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#71869A}.filter-field input,.filter-field select{width:100%;height:36px;background:#fff;border:1px solid #C8D6E1;border-radius:8px;color:var(--text);font:12px 'DM Sans',sans-serif;padding:0 11px;outline:none}.filter-field input:focus,.filter-field select:focus{border-color:var(--teal-border);box-shadow:0 0 0 3px rgba(25,179,157,.10)}
.filter-menu-actions{display:flex;justify-content:flex-end;gap:8px;padding-top:3px}.filter-apply,.filter-clear{height:34px;padding:0 12px;border-radius:8px;font:700 12px 'DM Sans',sans-serif;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:7px}.filter-apply{border:1px solid var(--teal);background:var(--teal);color:#fff}.filter-clear{border:1px solid #C8D6E1;background:#fff;color:#61768A}

.table-wrap{overflow:auto}
table.data{width:100%;border-collapse:separate;border-spacing:0;font-size:13px;min-width:840px}
table.data th{height:58px;text-align:left;color:#657A8E;font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;padding:0 15px;border-bottom:1px solid var(--line-strong);background:#FCFDFE;white-space:nowrap}
table.data th:first-child{padding-left:16px;width:32%}table.data th:nth-child(2){width:18%}table.data th:nth-child(3){width:10%}table.data th:nth-child(4){width:11%}table.data th:nth-child(5){width:13%}table.data th:nth-child(6){width:24%}
table.data td{height:92px;padding:0 15px;border-bottom:1px solid #E4EDF4;vertical-align:middle;background:#fff;color:var(--text)}
table.data tbody tr:hover td{background:#FBFEFD}table.data tbody tr:last-child td{border-bottom:none}
.stu-cell{display:flex;align-items:center;gap:12px;min-width:0}.stu-avatar{width:48px;height:48px;border-radius:50%;flex:0 0 48px;display:flex;align-items:center;justify-content:center;background:var(--teal-soft);border:1px solid #B6E6DE;color:#159C8A;font-size:18px}.stu-copy{min-width:0}.stu-name{font-size:14px;font-weight:700;color:#10263A;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.stu-sub{font-size:11px;color:#8092A3;margin-top:3px}
.level-pill{display:inline-flex;align-items:center;justify-content:center;min-width:82px;height:50px;padding:0 16px;border-radius:14px;border:1px solid var(--teal-border);background:var(--teal-soft);color:#19A995;font-size:12px;font-weight:800}
.req-number,.completed-number{font-size:14px;color:#344D60;font-weight:500}.completed-number{font-weight:500}
.status-pill{display:inline-flex;align-items:center;justify-content:center;padding:7px 12px;border-radius:999px;font-size:11px;font-weight:800;white-space:nowrap}.status-pill.not_started{background:#F3F7FA;color:#6E8396}.status-pill.in_progress{background:var(--amber-soft);color:var(--amber)}.status-pill.completed{background:var(--green-soft);color:var(--green)}
.progress-cell{display:flex;align-items:center;gap:10px;min-width:0}.progress-pct{width:36px;flex:0 0 36px;font-size:12px;font-weight:600;color:#607589}.progress-main{min-width:130px;flex:1}.progress-track{width:100%;height:7px;border-radius:999px;background:#DDE7EF;overflow:hidden}.progress-fill{height:100%;border-radius:inherit;background:#15A57D;transition:width .2s}.progress-last{font-size:10.5px;color:#7C8FA0;margin-top:6px;white-space:nowrap}.progress-chevron{width:14px;flex:0 0 14px;color:#486176;font-size:17px;text-align:right}
.empty-note{color:#8295A6;font-size:13px;padding:26px 16px;text-align:center}.table-empty{padding:18px 0 8px}
.table-footer{display:flex;justify-content:space-between;align-items:center;padding:13px 16px 14px;font-size:12px;color:#7890A3;gap:12px;flex-wrap:wrap}.pagination{display:flex;align-items:center;gap:5px}.page-btn{min-width:30px;height:30px;padding:0 8px;display:flex;align-items:center;justify-content:center;border-radius:7px;background:#fff;border:1px solid #C9D7E2;color:#6D8194;text-decoration:none;font-size:12px;font-weight:700}.page-btn.active{background:#E8F7F4;color:#118E7E;border-color:#8BD6CB}.page-btn.disabled{opacity:.35;pointer-events:none}.page-ellipsis{color:#91A2B0;padding:0 3px}

.info-banner{display:flex;align-items:flex-start;gap:12px;background:#fff;border:1px solid var(--line);border-radius:12px;padding:15px 17px;margin-top:16px;box-shadow:var(--shadow)}.info-banner i{color:var(--blue);font-size:15px;margin-top:2px}.info-banner b{display:block;color:var(--text);font-size:12.5px;margin-bottom:2px}.info-banner p{font-size:12px;color:#74899B}.info-banner.closed{background:#FFF7F7;border-color:#F2D1D5}.info-banner.closed i{color:#D6455D}
.live-tracker{display:inline-flex;align-items:center;gap:6px;padding:4px 8px;border-radius:999px;background:#ECFDF5;border:1px solid #A7F3D0;color:#0F9F6E;font-size:10.5px;font-weight:800;letter-spacing:.02em}.live-tracker.offline{background:#F8FAFC;border-color:#DCE7F1;color:#8092A2}.live-dot{width:6px;height:6px;border-radius:50%;background:#0F9F6E;display:inline-block;animation:livePulse 2s ease-in-out infinite}.live-tracker.offline .live-dot{background:#9AA9B5;animation:none}@keyframes livePulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.8)}}.tracker-table-state.is-empty-state .table-footer{display:none}.tracker-live-updated{font-size:10.5px;color:#7A8FA1;margin-left:4px;white-space:nowrap}

@media(max-width:1000px){.main{padding:26px 22px 36px}.period-badge{width:100%;justify-content:flex-start}.tracker-toolbar{align-items:flex-start}.toolbar-actions{width:100%;justify-content:flex-end}.filter-menu{right:0}}
@media(max-width:768px){body{flex-direction:column}.sidebar{width:100%;min-height:auto}.main{padding:20px 14px 30px}.page-title{font-size:24px}.tracker-toolbar{padding:15px 14px}.toolbar-actions{justify-content:stretch}.filter-wrap,.filter-toggle,.export-btn{flex:1}.filter-toggle,.export-btn{justify-content:center}.filter-menu{width:min(310px,calc(100vw - 28px));right:0}}
</style>
<link rel="stylesheet" href="includes/dean_light_theme.css"/>
</head>
<body>

<?php
$active = 'tracker';
$sidebarScope = HIGHER_ED_LABEL . ' Division';
include __DIR__ . '/includes/dean_sidebar.php';
?>

<main class="main">
    <div class="page-header">
        <div>
            <div class="page-title">Evaluation Tracker</div>
            <div class="page-sub">Monitor <?= HIGHER_ED_LABEL ?> student evaluation participation.</div>
        </div>
        <div class="period-badge <?= htmlspecialchars($settings['status']['cls']) ?>">
            <i class="fa-solid fa-calendar-check"></i>
            <?= htmlspecialchars($settings['academic_year']) ?> · <?= HIGHER_ED_LABEL ?> · <?= htmlspecialchars($settings['academic_term']) ?>
            — <?= htmlspecialchars($settings['status']['label']) ?>
        </div>
    </div>

    <?php if (!$structureActive): ?>
    <div class="structure-note">
        <i class="fa-solid fa-circle-info"></i>
        <p>
            <b><?= HIGHER_ED_LABEL ?> is not the active academic structure.</b><br>
            The current evaluation period is configured for <b><?= htmlspecialchars($settings['academic_structure_label']) ?></b>.
            Tracker data is unavailable until the Executive Assistant switches it back.
        </p>
    </div>
    <?php else: ?>

    <?php $activeFilterCount = ($search !== '' ? 1 : 0) + ($yearLevel !== '' ? 1 : 0) + ($status !== '' ? 1 : 0); ?>
    <div class="tracker-card">
        <div class="tracker-toolbar">
            <div class="tracker-heading">
                <h2>Students Evaluation Tracker</h2>
                <span class="count" id="trackerStudentCount"><?= number_format($studentsAssigned) ?> student<?= $studentsAssigned === 1 ? '' : 's' ?></span>
                <span class="live-tracker" id="trackerLiveStatus"><span class="live-dot"></span> Live</span>
            </div>
            <div class="toolbar-actions">
                <div class="filter-wrap">
                    <button type="button" class="filter-toggle<?= $activeFilterCount ? ' active' : '' ?>" id="filterToggle" aria-expanded="false" aria-controls="trackerFilterMenu">
                        <i class="fa-solid fa-filter"></i> Filter
                        <?php if ($activeFilterCount): ?><span class="filter-count"><?= $activeFilterCount ?></span><?php endif; ?>
                    </button>
                    <form class="filter-menu" id="trackerFilterMenu" method="GET" action="dean_evaluation_tracker.php">
                        <div class="filter-menu-title">Filter Students</div>
                        <div class="filter-field">
                            <label for="filterSearch">Search Student</label>
                            <input type="text" id="filterSearch" name="search" placeholder="Student name..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="filter-field">
                            <label for="filterYear">College Year Level</label>
                            <select id="filterYear" name="year_level">
                                <option value="">All College Years</option>
                                <?php foreach ($yearLevelOptions as $val => $lbl): ?>
                                    <option value="<?= htmlspecialchars($val) ?>" <?= $yearLevel === $val ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label for="filterStatus">Evaluation Status</label>
                            <select id="filterStatus" name="status">
                                <option value="" <?= $status === '' ? 'selected' : '' ?>>All Statuses</option>
                                <option value="not_started" <?= $status === 'not_started' ? 'selected' : '' ?>>Not Started</option>
                                <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                        <input type="hidden" name="page" value="1">
                        <div class="filter-menu-actions">
                            <a class="filter-clear" href="dean_evaluation_tracker.php"><i class="fa-solid fa-rotate-left"></i> Clear</a>
                            <button type="submit" class="filter-apply"><i class="fa-solid fa-check"></i> Apply Filters</button>
                        </div>
                    </form>
                </div>
                <a class="export-btn" href="<?= tracker_qs(['export' => 'csv']) ?>"><i class="fa-solid fa-download"></i> Export</a>
            </div>
        </div>

        <div class="tracker-table-state<?= (!$hasPeriod || empty($pageStudents)) ? ' is-empty-state' : '' ?>" id="trackerTableState">
        <?php if (!$hasPeriod): ?>
            <div class="table-empty"><p class="empty-note">No active evaluation period right now.</p></div>
        <?php else: ?>
        <div class="table-wrap" id="trackerTableWrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Level</th>
                        <th>Required</th>
                        <th>Completed</th>
                        <th>Status</th>
                        <th>Progress</th>
                    </tr>
                </thead>
                <tbody id="trackerTableBody">
                    <?php if (empty($pageStudents)): ?>
                    <tr><td colspan="6"><p class="empty-note">No students match the current filters.</p></td></tr>
                    <?php else: ?>
                    <?php foreach ($pageStudents as $s): ?>
                    <tr>
                        <td>
                            <div class="stu-cell">
                                <span class="stu-avatar"><i class="fa-solid fa-user"></i></span>
                                <div class="stu-copy">
                                    <div class="stu-name"><?= htmlspecialchars($s['name']) ?></div>
                                    <div class="stu-sub">College</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="level-pill"><?= htmlspecialchars($s['year_level']) ?></span></td>
                        <td><span class="req-number"><?= number_format($s['required']) ?></span></td>
                        <td><span class="completed-number"><?= number_format($s['completed']) ?> / <?= number_format($s['required']) ?></span></td>
                        <td><span class="status-pill <?= htmlspecialchars($s['status']) ?>"><?= htmlspecialchars($s['status_label']) ?></span></td>
                        <td>
                            <div class="progress-cell">
                                <span class="progress-pct"><?= (int)$s['progress'] ?>%</span>
                                <div class="progress-main">
                                    <div class="progress-track" aria-label="<?= (int)$s['progress'] ?> percent complete">
                                        <div class="progress-fill" style="width:<?= (int)$s['progress'] ?>%;"></div>
                                    </div>
                                    <?php if (!empty($s['submitted_at'])): ?>
                                        <div class="progress-last">Last: <?= htmlspecialchars(date('M j, Y g:i A', strtotime($s['submitted_at']))) ?></div>
                                    <?php endif; ?>
                                </div>
                                <span class="progress-chevron" aria-hidden="true">›</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer" id="trackerTableFooter">
            <div id="trackerShowingText"><?php if ($studentsAssigned > 0): ?>Showing <?= (($page - 1) * $perPage) + 1 ?>–<?= min($studentsAssigned, $page * $perPage) ?> of <?= $studentsAssigned ?> students<?php else: ?>No students to display<?php endif; ?></div>
            <div class="pagination" id="trackerPagination">
                <?php if ($totalPages > 1): ?>
                <?php
                $shown = [];
                for ($i = 1; $i <= $totalPages; $i++) {
                    if ($i === 1 || $i === $totalPages || abs($i - $page) <= 2) $shown[] = $i;
                }
                $prev = null;
                ?>
                <a class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= tracker_qs(['page' => max(1, $page - 1)]) ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></a>
                <?php foreach ($shown as $i): ?>
                    <?php if ($prev !== null && $i - $prev > 1): ?><span class="page-ellipsis">…</span><?php endif; ?>
                    <a class="page-btn <?= $i === $page ? 'active' : '' ?>" href="<?= tracker_qs(['page' => $i]) ?>"><?= $i ?></a>
                <?php $prev = $i; endforeach; ?>
                <a class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= tracker_qs(['page' => min($totalPages, $page + 1)]) ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        </div>
    <?php if ($hasPeriod): ?>
    <div class="info-banner <?= $evalOpen ? '' : 'closed' ?>">
        <i class="fa-solid fa-circle-info"></i>
        <div>
            <b>Student evaluation is currently <?= $evalOpen ? 'open' : 'closed' ?>.</b>
            <p><?= $evalOpen
                ? 'The tracker updates automatically as student evaluation assignments are completed.'
                : 'No new submissions will be recorded until the evaluation window reopens.' ?></p>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</main>
<script>
(function(){
    const toggle = document.getElementById('filterToggle');
    const menu = document.getElementById('trackerFilterMenu');
    if (toggle && menu) {
        toggle.addEventListener('click', function(e){
            e.stopPropagation();
            const open = menu.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        menu.addEventListener('click', function(e){ e.stopPropagation(); });
        document.addEventListener('click', function(){
            if (!menu.classList.contains('open')) return;
            menu.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
        });
    }

    const liveStatus = document.getElementById('trackerLiveStatus');
    const countEl = document.getElementById('trackerStudentCount');
    const stateEl = document.getElementById('trackerTableState');
    const bodyEl = document.getElementById('trackerTableBody');
    const footerEl = document.getElementById('trackerTableFooter');
    const showingEl = document.getElementById('trackerShowingText');
    const paginationEl = document.getElementById('trackerPagination');
    if (!liveStatus || !bodyEl || !stateEl) return;

    let busy = false;
    let lastSignature = '';

    function esc(value){
        return String(value ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\"/g,'&quot;').replace(/'/g,'&#039;');
    }
    function setLive(text, offline){
        liveStatus.classList.toggle('offline', !!offline);
        liveStatus.innerHTML = '<span class="live-dot"></span> ' + esc(text);
    }
    function studentRow(s){
        const last = s.submitted_at ? '<div class="progress-last">Last: ' + esc(formatDate(s.submitted_at)) + '</div>' : '';
        return '<tr>' +
            '<td><div class="stu-cell"><span class="stu-avatar"><i class="fa-solid fa-user"></i></span><div class="stu-copy"><div class="stu-name">' + esc(s.name) + '</div><div class="stu-sub">College</div></div></div></td>' +
            '<td><span class="level-pill">' + esc(s.year_level || 'College') + '</span></td>' +
            '<td><span class="req-number">' + Number(s.required || 0).toLocaleString() + '</span></td>' +
            '<td><span class="completed-number">' + Number(s.completed || 0).toLocaleString() + ' / ' + Number(s.required || 0).toLocaleString() + '</span></td>' +
            '<td><span class="status-pill ' + esc(s.status) + '">' + esc(s.status_label) + '</span></td>' +
            '<td><div class="progress-cell"><span class="progress-pct">' + Number(s.progress || 0) + '%</span><div class="progress-main"><div class="progress-track" aria-label="' + Number(s.progress || 0) + ' percent complete"><div class="progress-fill" style="width:' + Number(s.progress || 0) + '%"></div></div>' + last + '</div><span class="progress-chevron" aria-hidden="true">›</span></div></td>' +
            '</tr>';
    }
    function formatDate(value){
        const d = new Date(value);
        if (Number.isNaN(d.getTime())) return value;
        return d.toLocaleString(undefined,{month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit'});
    }
    function pageUrl(page){
        const u = new URL(window.location.href);
        u.searchParams.set('page', String(page));
        u.searchParams.delete('ajax');
        return u.toString();
    }
    function renderPagination(page,totalPages){
        if (!paginationEl) return;
        if (totalPages <= 1){ paginationEl.innerHTML=''; return; }
        const shown=[];
        for(let i=1;i<=totalPages;i++) if(i===1||i===totalPages||Math.abs(i-page)<=2) shown.push(i);
        let html='<a class="page-btn ' + (page<=1?'disabled':'') + '" href="' + esc(pageUrl(Math.max(1,page-1))) + '" aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></a>';
        let prev=null;
        shown.forEach(i=>{
            if(prev!==null && i-prev>1) html+='<span class="page-ellipsis">…</span>';
            html+='<a class="page-btn ' + (i===page?'active':'') + '" href="' + esc(pageUrl(i)) + '">' + i + '</a>';
            prev=i;
        });
        html+='<a class="page-btn ' + (page>=totalPages?'disabled':'') + '" href="' + esc(pageUrl(Math.min(totalPages,page+1))) + '" aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></a>';
        paginationEl.innerHTML=html;
    }
    function signature(d){
        return JSON.stringify([d.requiredTotal,d.studentsAssigned,d.studentsSubmitted,d.pendingStudents,d.completionPct,d.totalPages,d.page,(d.students||[]).map(s=>[s.id,s.completed,s.required,s.progress,s.status,s.submitted_at])]);
    }
    async function refresh(){
        if (busy) return;
        busy=true;
        try {
            const u = new URL(window.location.href);
            u.searchParams.set('ajax','1');
            u.searchParams.delete('export');
            u.searchParams.set('_ts', Date.now().toString());
            const res = await fetch(u.toString(), {credentials:'same-origin',cache:'no-store',headers:{'Accept':'application/json'}});
            if(!res.ok) throw new Error('Tracker update failed');
            const d = await res.json();
            if(!d.ok) throw new Error('Tracker update failed');

            const sig=signature(d);
            if(sig!==lastSignature){
                lastSignature=sig;
                if(countEl) countEl.textContent = Number(d.studentsAssigned||0).toLocaleString() + ' student' + (Number(d.studentsAssigned||0)===1?'':'s');
                if(d.students && d.students.length){
                    stateEl.classList.remove('is-empty-state');
                    bodyEl.innerHTML=d.students.map(studentRow).join('');
                    if(footerEl) footerEl.style.display='flex';
                    if(showingEl){
                        const first=((Number(d.page||1)-1)*<?= (int)$perPage ?>)+1;
                        const last=Math.min(Number(d.studentsAssigned||0),Number(d.page||1)*<?= (int)$perPage ?>);
                        showingEl.textContent='Showing ' + first + '–' + last + ' of ' + Number(d.studentsAssigned||0).toLocaleString() + ' students';
                    }
                    renderPagination(Number(d.page||1),Number(d.totalPages||1));
                }else{
                    stateEl.classList.add('is-empty-state');
                    bodyEl.innerHTML='<tr><td colspan="6"><p class="empty-note">No students match the current filters.</p></td></tr>';
                    if(footerEl) footerEl.style.display='none';
                    if(showingEl) showingEl.textContent='No students to display';
                    if(paginationEl) paginationEl.innerHTML='';
                }
            }
            setLive('Live · ' + new Date().toLocaleTimeString([], {hour:'numeric',minute:'2-digit',second:'2-digit'}), false);
        } catch(e){
            setLive('Live check paused', true);
        } finally {
            busy=false;
        }
    }
    refresh();
    setInterval(refresh,5000);
})();
</script>
</script>
</body>
<link rel="stylesheet" href="includes/dean_light_theme.css" id="dean-light-theme-final"/>
</html>