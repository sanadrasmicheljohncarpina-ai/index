<?php
// principal_evaluations.php
// Evaluation workspace — mirrors the Dean portal's Evaluation page:
//   - Faculty tab = College/HS-eligible teaching personnel plus
//     non-teaching Staff, with the Executive Assistant in its own tab.
//   - Eligibility mirrors the Questionnaire's Dean / Principal Evaluation
//     Faculty/Staff/EA target rules.
//   - Search box, CSV export of the current filtered view, and
//     server-side pagination (5 per page), same as Dean's.
//
// Built on principal_common.php: 'principal' auth guard, Basic Education
// scope, centralized System Settings ($settings), shared sidebar/styles.
//
// eval_type='school_head' / eval_bucket='Faculty'|'Staff'|'EA' — the same storage used by the Questionnaire's Dean / Principal Evaluation flow.
//
// NOTE: this page must not derive academic year/structure/term/period/
// status itself. All of that comes from $settings (set in
// principal_common.php via get_system_settings()) plus get_school_head_settings() — the same source the
// Dashboard uses. Only query the DB here for things that are genuinely
// page-specific: the roster and who's been evaluated.
//
// ASSUMPTIONS made while mirroring the Dean page (double check / adjust):
//   1. principal_evaluate.php accepts an optional "&view=1" to open a
//      completed evaluation read-only — Dean's route uses this
//      convention, mirrored here. If principal_evaluate.php doesn't
//      support it yet, the "View" link below will need that handling
//      added, or should be dropped until it does.
//   2. Dean's Action column always shows "Evaluate" (even once
//      completed) plus a separate "View" link when completed — this
//      lets a dean re-open/redo an evaluation post-submission. Mirrored
//      as-is here; the old behavior only showed "Submitted" text with no
//      link once done. Change back to "Submitted"-only if resubmission
//      shouldn't be allowed for principals.
//   3. Previously the Evaluate link had no gating on $hasPeriod (only a
//      banner warned submissions were closed). To match Dean's pattern
//      (action column swaps to "Evaluation closed"/no-link when the
//      period isn't open), this version now gates on $hasPeriod too.
//      That's a real behavior change, not just a layout one — flagging
//      it explicitly.
//   4. The page-local <style> block below assumes the shared stylesheet
//      (loaded via html_head_open()) does NOT already define
//      .eval-tabs/.filter-bar/.export-btn/.role-pill/.table-footer/
//      .pagination. If it does, delete the block and use those classes.

require_once 'principal_common.php';
require_once dirname(__DIR__) . '/shared/QuestionnaireService.php';
qn_migrate_legacy_once($mysqli);
$settings = $schoolHeadSettings;
$period_id_int = (int)($settings['period_id'] ?? 0);
$hasPeriod = $period_id_int > 0;
$evalOpen = !empty($settings['school_head_is_open']);

$user_id = $_SESSION['user_id'];

$toast       = $_SESSION['toast']       ?? ''; unset($_SESSION['toast']);
$toast_error = $_SESSION['toast_error'] ?? ''; unset($_SESSION['toast_error']);

// ── TABS ─────────────────────────────────────────────────────────────
// These tabs mirror the Administrator's Questionnaire -> Dean / Principal
// Evaluation -> Principal tab:
//   Faculty = only Principal-eligible HS/SHS teaching personnel
//   Staff   = only non-teaching Staff, each with its own question set
//   EA      = the active Executive Assistant shared question bank
$validTabs = ['faculty', 'staff', 'executive_assistant'];
$tab = $_GET['tab'] ?? 'faculty';
if (!in_array($tab, $validTabs, true)) $tab = 'faculty';

$tabLabels = [
    'faculty' => 'Faculty',
    'staff' => 'Staff',
    'executive_assistant' => 'Executive Assistant'
];
$tabIcons = [
    'faculty' => 'fa-users',
    'staff' => 'fa-briefcase',
    'executive_assistant' => 'fa-user-tie'
];

$facultyUsers = [];
$staffUsers   = [];
$eaUsers      = [];
$donePairs    = [];
$rosterByTab  = ['faculty' => [], 'staff' => [], 'executive_assistant' => []];
$activeRoster = [];
$questionCounts = ['faculty' => 0, 'staff' => 0, 'executive_assistant' => 0];
$staffQuestionCounts = [];

function principal_roster_levels(mysqli $mysqli, int $userId): array {
    // IMPORTANT: level VALUES come from user_year_levels ONLY — this is
    // the table Account Management actually edits/displays, and it's kept
    // in sync (one row per current assignment, UNIQUE user_id+year_level).
    // teaching_assignments is NOT reliable for this: it has no
    // active/end-date column and accumulates a new row on every
    // reassignment without removing the old one (confirmed in prod data:
    // a since-reassigned-to-College user still carried an old Grade 7 row
    // from before the reassignment). Mirrors faculty_dashboard.php's
    // $my_levels, which already only reads user_year_levels for this
    // exact reason. teaching_assignments is still fine as a coarse
    // existence signal (see principal_roster_has_teaching()) — just not
    // as a source of which level someone is currently assigned to.
    $levels = [];
    $stmt = $mysqli->prepare("SELECT year_level FROM user_year_levels WHERE user_id=?");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $v = trim((string)($r['year_level'] ?? ''));
            if ($v !== '') $levels[] = $v;
        }
        $stmt->close();
    }
    return array_values(array_unique($levels));
}

function principal_roster_is_high(string $level): bool {
    return (bool)preg_match('/^Grade\s*(7|8|9|10|11|12)\b/i', trim($level));
}

function principal_roster_has_teaching(mysqli $mysqli, array $u): bool {
    if (($u['role'] ?? '') === 'teacher') return true;
    if (($u['secondary_role'] ?? '') === 'teacher') return true;
    if (($u['sector'] ?? '') === 'Teacher') return true;

    $uid = (int)($u['id'] ?? 0);
    if ($uid <= 0) return false;
    $stmt = $mysqli->prepare(
        "SELECT 1 FROM (
            SELECT user_id FROM teaching_assignments WHERE user_id=?
            UNION ALL
            SELECT user_id FROM user_year_levels WHERE user_id=?
        ) x LIMIT 1"
    );
    if (!$stmt) return false;
    $stmt->bind_param('ii', $uid, $uid);
    $stmt->execute();
    $ok = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $ok;
}

function principal_roster_is_non_teaching_staff(mysqli $mysqli, array $u): bool {
    if (($u['role'] ?? '') !== 'staff') return false;
    $uid = (int)($u['id'] ?? 0);
    if ($uid <= 0) return false;
    $stmt = $mysqli->prepare(
        "SELECT
            NOT EXISTS(SELECT 1 FROM teaching_assignments WHERE user_id=?)
            AND NOT EXISTS(SELECT 1 FROM user_year_levels WHERE user_id=?) AS is_non_teaching"
    );
    if (!$stmt) return false;
    $stmt->bind_param('ii', $uid, $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (bool)($row['is_non_teaching'] ?? false);
}

if ($structureActive) {
    // Pull the full approved personnel pool first, then classify the same way
    // the Questionnaire page classifies its Faculty and Staff cards.
    $ures = $mysqli->query(
        "SELECT id, full_name, designation, photo, role, secondary_role, sector, department
         FROM users
         WHERE role IN ('teacher','staff')
           AND is_active=1
           AND account_status='approved'
         ORDER BY full_name ASC"
    );

    if ($ures) {
        while ($u = $ures->fetch_assoc()) {
            $levels = principal_roster_levels($mysqli, (int)$u['id']);
            $hasTeaching = principal_roster_has_teaching($mysqli, $u);

            // Questionnaire's Principal -> Staff card is non-teaching Staff.
            if (principal_roster_is_non_teaching_staff($mysqli, $u)) {
                $u['role_label'] = 'Staff';
                $u['eval_bucket'] = 'Staff';
                $staffUsers[] = $u;
                continue;
            }

            // Questionnaire's Principal -> Faculty card is strictly limited
            // to High School / Senior High (Grade 7-12) teaching personnel.
            if ($hasTeaching && count(array_filter($levels, 'principal_roster_is_high')) > 0) {
                $u['role_label'] = ($u['role'] === 'staff') ? 'Teaching Staff' : 'Faculty';
                $u['eval_bucket'] = 'Faculty';
                $facultyUsers[] = $u;
            }
        }
    }

    // Questionnaire's Principal -> EA card represents the active approved EA.
    $eaRes = $mysqli->query(
        "SELECT id, full_name, designation, photo, role, department
         FROM users
         WHERE role='superadmin' AND is_active=1 AND account_status='approved'
         ORDER BY updated_at DESC, id DESC
         LIMIT 1"
    );
    if ($eaRes && ($ea = $eaRes->fetch_assoc())) {
        $ea['role_label'] = 'Executive Assistant';
        $ea['eval_bucket'] = 'EA';
        $eaUsers[] = $ea;
    }

    // ── QUESTION COUNTS — SAME SOURCE AS QUESTIONNAIRE PRINCIPAL TAB ──
    $questionCounts['faculty'] = (int)(safe_scalar($mysqli,
        "SELECT COUNT(*) FROM evaluation_questions
         WHERE eval_type='general'
           AND evaluator_role='shared'
           AND target_type='Faculty'"
    ) ?? 0);
    $questionCounts['executive_assistant'] = (int)(safe_scalar($mysqli,
        "SELECT COUNT(*) FROM evaluation_questions
         WHERE eval_type='general'
           AND target_type='EA'"
    ) ?? 0);

    // Staff questions are intentionally per person. Count only the Staff
    // targets that actually appear in this Principal Questionnaire roster.
    if (!empty($staffUsers)) {
        $ids = array_map(fn($u) => (int)$u['id'], $staffUsers);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $stmt = $mysqli->prepare(
            "SELECT user_id, COUNT(*) AS total
             FROM user_questions
             WHERE eval_type='general'
               AND target_type='Staff'
               AND user_id IN ($placeholders)
             GROUP BY user_id"
        );
        if ($stmt) {
            $stmt->bind_param($types, ...$ids);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $staffQuestionCounts[(int)$r['user_id']] = (int)$r['total'];
            }
            $stmt->close();
        }
        foreach ($staffUsers as &$u) {
            $u['question_count'] = $staffQuestionCounts[(int)$u['id']] ?? 0;
        }
        unset($u);
        $questionCounts['staff'] = array_sum($staffQuestionCounts);
    }

    // Status comes only from the current Principal's school_head evaluations
    // for the active period — not from the old questionnaire_forms flow.
    if ($period_id_int) {
        $dstmt = $mysqli->prepare(
            "SELECT target_user_id, submitted_at
             FROM evaluation_tracker
             WHERE evaluator_id=?
               AND eval_type='school_head'
               AND period_id=?
               AND status IN ('submitted','approved')"
        );
        if ($dstmt) {
            $dstmt->bind_param('ii', $user_id, $period_id_int);
            $dstmt->execute();
            $dres = $dstmt->get_result();
            while ($r = $dres->fetch_assoc()) {
                $donePairs[(int)$r['target_user_id']] = $r['submitted_at'];
            }
            $dstmt->close();
        }
    }

    foreach ($facultyUsers as &$r) {
        $uid = (int)$r['id'];
        $r['evaluation_status'] = isset($donePairs[$uid]) ? 'completed' : 'not_started';
        $r['last_evaluation_date'] = $donePairs[$uid] ?? null;
        $r['question_count'] = $questionCounts['faculty'];
    }
    unset($r);

    foreach ($staffUsers as &$r) {
        $uid = (int)$r['id'];
        $r['evaluation_status'] = isset($donePairs[$uid]) ? 'completed' : 'not_started';
        $r['last_evaluation_date'] = $donePairs[$uid] ?? null;
        $r['question_count'] = $staffQuestionCounts[$uid] ?? 0;
    }
    unset($r);

    foreach ($eaUsers as &$r) {
        $uid = (int)$r['id'];
        $r['evaluation_status'] = isset($donePairs[$uid]) ? 'completed' : 'not_started';
        $r['last_evaluation_date'] = $donePairs[$uid] ?? null;
        $r['question_count'] = $questionCounts['executive_assistant'];
    }
    unset($r);

    usort($facultyUsers, fn($a, $b) => strcmp($a['full_name'], $b['full_name']));
    usort($staffUsers, fn($a, $b) => strcmp($a['full_name'], $b['full_name']));

    $rosterByTab = [
        'faculty' => $facultyUsers,
        'staff' => $staffUsers,
        'executive_assistant' => $eaUsers,
    ];
    $activeRoster = $rosterByTab[$tab];
}

// ── TOTALS — USERS + QUESTIONS ARE SOURCED FROM PRINCIPAL QUESTIONNAIRE ──
$facultyToEvaluate = count($facultyUsers);
$staffToEvaluate = count($staffUsers);
$eaToEvaluate = count($eaUsers);
$agg_total = $facultyToEvaluate + $staffToEvaluate + $eaToEvaluate;
$agg_done = count(array_filter(
    array_merge($facultyUsers, $staffUsers, $eaUsers),
    fn($r) => ($r['evaluation_status'] ?? '') === 'completed'
));
$agg_pending = max(0, $agg_total - $agg_done);
$agg_completion = $agg_total > 0 ? (int)round($agg_done / $agg_total * 100) : 0;
$totalPrincipalQuestions = $questionCounts['faculty'] + $questionCounts['staff'] + $questionCounts['executive_assistant'];

// ── FILTER OPTIONS ─────────────────────────────────────────────────────
$departmentOptions = [];
foreach ($facultyUsers as $r) {
    $d = trim((string)($r['department'] ?? ''));
    if ($d !== '') $departmentOptions[$d] = true;
}
$departmentOptions = array_keys($departmentOptions);
sort($departmentOptions);

$deptFilter = trim($_GET['dept'] ?? 'all');
$search = trim($_GET['q'] ?? '');

$filteredRoster = $activeRoster;
if ($tab === 'faculty' && $deptFilter !== 'all' && $deptFilter !== '') {
    $filteredRoster = array_values(array_filter($filteredRoster, fn($r) => ($r['department'] ?? '') === $deptFilter));
}
if ($search !== '') {
    $needle = mb_strtolower($search);
    $filteredRoster = array_values(array_filter($filteredRoster, function ($r) use ($needle) {
        $haystack = mb_strtolower(
            ($r['full_name'] ?? '') . ' ' .
            ($r['department'] ?? '') . ' ' .
            ($r['designation'] ?? '') . ' ' .
            ($r['role_label'] ?? '')
        );
        return str_contains($haystack, $needle);
    }));
}

// Pagination values are derived from the already-filtered roster.
// The old version depended on server-side table variables that were removed
// when Department/Search/Export controls were taken out of the UI.
$totalFiltered = count($filteredRoster);
$perPage = 10;
$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
$page = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$pageRoster = array_slice($filteredRoster, ($page - 1) * $perPage, $perPage);
$showingFrom = $totalFiltered === 0 ? 0 : (($page - 1) * $perPage) + 1;
$showingTo = min($totalFiltered, $page * $perPage);

// Helper to rebuild the current query string with one param overridden —
// used by filter controls, search, and pagination links. No DB
// dependency, so it's fine outside the $structureActive block.
function principal_eval_qs(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
    // Changing a filter/search always resets back to page 1.
    if (!isset($overrides['page'])) $params['page'] = 1;
    return htmlspecialchars('?' . http_build_query($params));
}

$mysqli->close();

html_head_open('PBI — Principal Evaluations');
?>
<style>
/* Page-local additions: compact, normal tabs matching Principal Reports. */
:root{ --eval-accent: var(--accent, #D99A2B); }
.eval-tabs{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:12px;width:auto;background:transparent;border:0;border-radius:0;padding:0;box-shadow:none;overflow:visible;}
.eval-tab{display:flex;align-items:center;gap:9px;padding:11px 20px;border-radius:10px;border:1px solid var(--border,#E2E8F0);background:var(--mid,#F8FAFC);color:var(--muted,#64748B);text-decoration:none;font-size:13px;font-weight:700;transition:all .2s;white-space:nowrap;min-width:auto;justify-content:flex-start;}
.eval-tab:hover{color:#334155;background:#F8FAFC;}
.eval-tab.active{background:#FFF7ED;color:#B45309;border-color:#FED7AA;box-shadow:none;}
.eval-tab .badge{background:#F1F5F9;color:#475569;border-radius:20px;padding:2px 8px;font-size:11px;font-weight:700;}
.eval-tab.active .badge{background:#FFEDD5;color:#B45309;}
@media(max-width:700px){.eval-tabs{gap:6px}.eval-tab{padding:10px 14px;}}
.filter-bar{display:flex;align-items:flex-end;gap:20px;flex-wrap:wrap;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:18px 20px;margin-bottom:18px;}
.filter-field{display:flex;flex-direction:column;gap:6px;}
.filter-field label{font-size:10.5px;font-weight:700;color:var(--muted,#A0B3C6);text-transform:uppercase;letter-spacing:.5px;}
.filter-field select{background:rgba(0,0,0,.2);border:1px solid rgba(255,255,255,.12);color:var(--light,#E0E6F0);padding:9px 12px;border-radius:8px;font-size:13px;min-width:190px;}
.filter-field select option{color:#0A192F;background:#fff;}
.filter-hint{font-size:10.5px;color:var(--muted,#A0B3C6);margin-top:2px;}
.search-wrap{flex:1;min-width:220px;position:relative;}
.search-wrap input{width:100%;background:rgba(0,0,0,.2);border:1px solid rgba(255,255,255,.12);color:inherit;padding:9px 36px 9px 12px;border-radius:8px;font-size:13px;}
.search-wrap i{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--muted,#A0B3C6);font-size:13px;}
.export-btn{background:rgba(217,154,43,.14);border:1px solid rgba(217,154,43,.35);color:var(--eval-accent);padding:10px 16px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:7px;white-space:nowrap;align-self:flex-end;}
.export-btn:hover{background:rgba(217,154,43,.22);}
.role-pill{display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:rgba(255,255,255,.08);}
.table-footer{display:flex;justify-content:space-between;align-items:center;padding:14px 20px;font-size:12.5px;color:var(--muted,#A0B3C6);flex-wrap:wrap;gap:10px;}
.pagination{display:flex;align-items:center;gap:6px;}
.page-btn{width:30px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:7px;background:rgba(0,0,0,.2);border:1px solid rgba(255,255,255,.1);color:var(--muted,#A0B3C6);text-decoration:none;font-size:12.5px;font-weight:600;}
.page-btn.active{background:var(--eval-accent);color:#fff;border-color:var(--eval-accent);}
.page-btn.disabled{opacity:.35;pointer-events:none;}
</style>

<style id="principal-integrated-light-view">
/* ================================================================
   PRINCIPAL INTEGRATED LIGHT VIEW
   Visual direction: same overall canvas treatment as the EA dashboard.
   The content area is one continuous light surface; feature sections
   remain white, but are flatter and more naturally integrated instead
   of looking like isolated floating cards.
   ================================================================ */
html, body {
  background: #F8FAFC !important;
  color: #172033 !important;
}
body {
  background-image: none !important;
}

/* Continuous page canvas */
.main,
main.main,
.main-content,
.content,
.page-content {
  background: #F8FAFC !important;
  color: #172033 !important;
  min-height: 100vh;
}

/* Keep the existing navy Principal sidebar exactly as-is */
.sidebar {
  background: #0A192F !important;
}

/* Page heading sits directly on the canvas — not in a floating card. */
.main > .page-header,
main.main > .page-header {
  background: transparent !important;
  border: 0 !important;
  box-shadow: none !important;
  border-radius: 0 !important;
  padding: 0 !important;
  margin: 0 0 22px !important;
}

.page-title,
.page-header h1 {
  color: #0F172A !important;
}
.page-sub,
.page-header p {
  color: #64748B !important;
}

/* Major feature sections: white, but visually grounded on the light canvas. */
.period-strip,
.structure-note,
.stub-note,
.section,
.card-grid,
.eval-switcher,
.eval-banner,
.group-tab-wrap,
.group-tabs,
.desig-subtabs,
.faculty-subtabs,
.filter-bar,
.ra-table-card,
.ra-filters-panel,
.table-wrap,
.table-card,
.content-card,
.summary-card,
.standing-panel,
.history-card,
.people-list,
.evaluator-grid,
.no-archived,
.info-grid,
.sheet-header,
.scale-bar,
.q-table,
.comment-section,
.avg-summary {
  border-color: #E2E8F0 !important;
}

.period-strip,
.structure-note,
.section,
.sheet-header,
.scale-bar,
.comment-section,
.avg-summary,
.ra-table-card,
.ra-filters-panel,
.table-wrap,
.table-card,
.content-card,
.summary-card,
.standing-panel,
.history-card,
.people-list,
.evaluator-grid,
.no-archived,
.info-grid {
  background: #FFFFFF !important;
  color: #172033 !important;
  box-shadow: 0 2px 10px rgba(15,23,42,.055) !important;
}

/* Dashboard KPI/detail cards stay visible, but lose the heavy "floating" effect. */
.card-grid {
  background: transparent !important;
  box-shadow: none !important;
  border: 0 !important;
  padding: 0 !important;
}
.stat-card,
.stat-link .stat-card {
  background: #FFFFFF !important;
  border-color: #E2E8F0 !important;
  box-shadow: 0 3px 12px rgba(15,23,42,.055) !important;
}
.stat-card:hover,
.stat-link:hover .stat-card {
  box-shadow: 0 5px 14px rgba(15,23,42,.075) !important;
}

/* Common tables/inner surfaces stay clean and readable. */
table,
.eval-table-wrap,
.eval-q-card,
.q-result,
.received-item {
  background: #FFFFFF !important;
  color: #172033 !important;
  border-color: #E2E8F0 !important;
}

table thead th,
.eval-table th,
.q-table thead tr,
table.data th,
.q-table th {
  background: #F8FAFC !important;
  color: #475569 !important;
  border-color: #E2E8F0 !important;
}

table tbody td,
.eval-table td,
.q-table td,
table.data td {
  background: #FFFFFF !important;
  color: #334155 !important;
  border-color: #E2E8F0 !important;
}

table tbody tr:hover td,
.eval-table tr:hover td,
.q-table tr:hover td {
  background: #F8FAFC !important;
}

/* Report feature area follows the same integrated page treatment. */
.eval-tab,
.tab,
.level-tab,
.status-tab,
.group-tab,
.desig-subtab,
.faculty-subtab {
  background: transparent !important;
}

/* Inner highlighted controls can still use soft tint, never dark navy. */
.main .subtle-head,
.main .table-head,
.main .table-header,
.main .thead {
  background: #F4F8FF !important;
}

input,
select,
textarea,
.search-box input[type=text],
.search-box select,
.form-group input,
.filter-field select,
.filter-field input[type=text],
.ra-filter-row select,
.ra-search,
.eval-comment-box {
  background: #FFFFFF !important;
  color: #172033 !important;
  border-color: #CBD5E1 !important;
}

/* Avoid accidental dark-mode remnants in common feature containers. */
.main .modal,
.main .modal-content,
.main .dropdown,
.main .menu,
.main .popover,
.main .panel {
  background: #FFFFFF !important;
  color: #172033 !important;
  border-color: #E2E8F0 !important;
}

@media (max-width: 768px) {
  .main,
  main.main,
  .main-content,
  .content,
  .page-content {
    padding: 24px 18px !important;
  }
}

@media print {
  html, body, .main, main.main, .main-content, .content, .page-content {
    background: #FFFFFF !important;
  }
  .main > .page-header,
  main.main > .page-header {
    box-shadow: none !important;
  }
}
</style>


<?php render_principal_sidebar('evaluations', $me, $scopeLabel, $photo_src); ?>


<style id="principal-ea-logs-canvas-final">
/* EA System Logs-like visual treatment:
   white viewport + a subtle #F8FAFC feature canvas inset inside it. */
html, body {
  background: #FFFFFF !important;
  background-image: none !important;
  color: #172033 !important;
}

.main,
main.main,
.main-content,
.content,
.page-content {
  position: relative !important;
  isolation: isolate !important;
  background: #FFFFFF !important;
  color: #172033 !important;
  min-height: 100vh;
}

/* The feature canvas does NOT cover the whole white page. */
.main::before,
main.main::before {
  content: "";
  position: absolute;
  left: 16px;
  right: 0;
  top: 56px;
  bottom: 0;
  background: #F8FAFC !important;
  border-radius: 16px 0 0 0;
  pointer-events: none;
  z-index: -1;
}

/* Preserve the existing navy Principal sidebar. */
.sidebar {
  background: #0A192F !important;
}

/* Keep feature surfaces white, with only a very light edge/shadow. */
.main .page-header,
.main .section,
.main .card,
.main .panel,
.main .table-card,
.main .content-card,
.main .stat-card,
.main .period-strip,
.main .filter-bar,
.main .table-wrap,
.main .table-card-wrap,
.main .content-panel,
.main .eval-banner,
.main .info-banner,
.main .history-card,
.main .gl-card,
.main .amber-card,
.main .green-card,
.main .red-card,
.main .ra-table-card,
.main .eval-card,
.main .comment-section,
.main .avg-summary,
.main .standing-panel,
.main .person-row,
.main .target-card,
.main .no-eval,
.main .no-data,
.main .no-evaluated,
.main .no-archived,
.main .evaluator-grid,
.main .people-list,
.main .cat-section,
.main .results-card,
.main .result-card,
.main .feature-card {
  background: #FFFFFF !important;
  color: #172033 !important;
  border-color: #D9E4EF !important;
  box-shadow: 0 1px 5px rgba(15,23,42,.035) !important;
}

.main table thead th,
.main .table-head,
.main .table-header,
.main .thead,
.main .subtle-head {
  background: #F4F8FF !important;
  color: #4B6580 !important;
  border-color: #D9E4EF !important;
}

.main table tbody td {
  background: #FFFFFF !important;
  color: #172033 !important;
  border-color: #D9E4EF !important;
}

.main table tbody tr:hover td,
.main .person-row:hover,
.main .standing-item:hover {
  background: #F8FAFC !important;
}

.main input,
.main select,
.main textarea,
.main .search-box input[type=text],
.main .search-box select,
.main .form-group input,
.main .filter-field select,
.main .filter-field input[type=text],
.main .ra-filter-row select,
.main .ra-search,
.main .eval-comment-box {
  background: #FFFFFF !important;
  color: #172033 !important;
  border-color: #CBD5E1 !important;
}

.main .modal,
.main .modal-content,
.main .dropdown,
.main .menu,
.main .popover,
.main .panel {
  background: #FFFFFF !important;
  color: #172033 !important;
  border-color: #D9E4EF !important;
}

@media (max-width: 768px) {
  .main::before,
  main.main::before {
    left: 0;
    top: 52px;
    border-radius: 12px 0 0 0;
  }
}

@media print {
  html, body, .main, main.main, .main-content, .content, .page-content {
    background: #FFFFFF !important;
  }
  .main::before,
  main.main::before {
    display: none !important;
  }
}
</style>

<main class="main">
    <div class="page-header">
        <div>
            <div class="page-title">My Evaluation</div>
            <div class="page-sub">Evaluate Faculty &amp; Executive Assistants — <?= htmlspecialchars($scopeLabel) ?></div>
        </div>
        <?php render_period_badge($settings); ?>
    </div>

    <?php if ($toast): ?>
    <div class="alert success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($toast) ?></div>
    <?php endif; ?>
    <?php if ($toast_error): ?>
    <div class="alert error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($toast_error) ?></div>
    <?php endif; ?>

    <?php if (!$structureActive): ?>
        <?php render_scope_status($settings, 'evaluation'); ?>

    <!-- DASH-STATE STATS — page keeps its shape instead of disappearing;
         see render_scope_status() for why nothing is queryable right now. -->
    <div class="card-grid">
        <div class="stat-card"><i class="fa-solid fa-users"></i><div class="num">—</div><div class="label">Faculty Targets</div></div>
        <div class="stat-card"><i class="fa-solid fa-briefcase"></i><div class="num">—</div><div class="label">Staff Targets</div></div>
        <div class="stat-card"><i class="fa-solid fa-user-tie"></i><div class="num">—</div><div class="label">Executive Assistant</div></div>
        <div class="stat-card"><i class="fa-solid fa-list-check"></i><div class="num">—</div><div class="label">Principal Questionnaire Questions</div></div>
        <div class="stat-card"><i class="fa-solid fa-circle-check"></i><div class="num">—</div><div class="label">Completed Evaluations</div></div>
        <div class="stat-card"><i class="fa-solid fa-hourglass-half"></i><div class="num">—</div><div class="label">Pending Evaluations</div></div>
        <div class="stat-card"><i class="fa-solid fa-chart-simple"></i><div class="num">—</div><div class="label">Completion Percentage</div></div>
    </div>
    <div class="alert error" style="margin-top:-10px;"><i class="fa-solid fa-hourglass-half"></i> Waiting for <?= BASIC_ED_LABEL ?> evaluation period</div>

    <?php else: ?>

    <!-- SUMMARY CARDS -->
    <div class="card-grid">
        <div class="stat-card"><i class="fa-solid fa-users"></i><div class="num"><?= $facultyToEvaluate ?></div><div class="label">Faculty Targets</div></div>
        <div class="stat-card"><i class="fa-solid fa-briefcase"></i><div class="num"><?= $staffToEvaluate ?></div><div class="label">Staff Targets</div></div>
        <div class="stat-card"><i class="fa-solid fa-user-tie"></i><div class="num"><?= $eaToEvaluate ?></div><div class="label">Executive Assistant</div></div>
        <div class="stat-card"><i class="fa-solid fa-list-check"></i><div class="num"><?= $totalPrincipalQuestions ?></div><div class="label">Principal Questionnaire Questions</div></div>
        <div class="stat-card"><i class="fa-solid fa-circle-check"></i><div class="num"><?= $agg_done ?></div><div class="label">Completed Evaluations</div></div>
        <div class="stat-card"><i class="fa-solid fa-hourglass-half"></i><div class="num"><?= $agg_pending ?></div><div class="label">Pending Evaluations</div></div>
        <div class="stat-card"><i class="fa-solid fa-chart-simple"></i><div class="num"><?= $agg_completion ?>%</div><div class="label">Completion Percentage</div></div>
    </div>

    <?php if (!$hasPeriod): ?>
    <div class="alert error"><i class="fa-solid fa-clock"></i> No active evaluation period. You can browse the roster, but submissions are closed until an admin opens a period.</div>
    <?php endif; ?>

    <!-- TABS -->
    <div class="eval-tabs">
        <?php foreach ($validTabs as $t): ?>
        <a class="eval-tab <?= $tab === $t ? 'active' : '' ?>" href="?tab=<?= urlencode($t) ?>">
            <i class="fa-solid <?= $tabIcons[$t] ?>"></i> <?= htmlspecialchars($tabLabels[$t]) ?>
            <span class="badge"><?= count($rosterByTab[$t]) ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ROSTER TABLE -->
    <div class="section" style="padding:0;overflow:hidden;">
    <table class="data">
        <thead>
            <tr>
                <th>Profile</th>
                <th>Full Name</th>
                <th>Department</th>
                <th>Position</th>
                <th>Role</th>
                <th>Questions</th>
                <th>Evaluation Status</th>
                <th>Last Evaluation Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php $colspan = 9; ?>
        <?php if (empty($pageRoster)): ?>
        <tr><td colspan="<?= $colspan ?>">
            <p class="empty-note" style="text-align:center;padding:40px 0;">No <?= strtolower($tabLabels[$tab]) ?> match the current filters.</p>
        </td></tr>
        <?php else: foreach ($pageRoster as $p): ?>
        <tr>
            <td>
                <?php if (!empty($p['photo'])): ?>
                <img class="avatar-sm" src="../image/<?= htmlspecialchars($p['photo']) ?>" alt="">
                <?php else: ?>
                <div class="avatar-sm" style="display:inline-flex;align-items:center;justify-content:center;background:var(--inner,rgba(0,0,0,.2));color:var(--muted,#A0B3C6);"><i class="fa-solid fa-user" style="font-size:12px;"></i></div>
                <?php endif; ?>
            </td>
            <td style="font-weight:600;"><?= htmlspecialchars($p['full_name']) ?></td>
            <td><?= !empty($p['department']) ? htmlspecialchars($p['department']) : '<span class="empty-note">—</span>' ?></td>
            <td><?= htmlspecialchars($p['designation'] ?: $p['role_label']) ?></td>
            <td><span class="role-pill"><?= htmlspecialchars($p['role_label']) ?></span></td>
            <td><span class="role-pill"><?= (int)($p['question_count'] ?? 0) ?></span></td>
            <td>
                <?php if ($p['evaluation_status'] === 'completed'): ?>
                <span class="pill good"><i class="fa-solid fa-circle-check" style="font-size:9px;"></i> Completed</span>
                <?php else: ?>
                <span class="pill warn"><i class="fa-solid fa-hourglass-half" style="font-size:9px;"></i> Not Started</span>
                <?php endif; ?>
            </td>
            <td><?= $p['last_evaluation_date'] ? htmlspecialchars(date('M j, Y', strtotime($p['last_evaluation_date']))) : '<span class="empty-note">—</span>' ?></td>
            <td>
                <?php if (!$hasPeriod): ?>
                    <span class="empty-note">Evaluation closed</span>
                <?php else: ?>
                    <a class="btn" href="principal_evaluate.php?tid=<?= (int)$p['id'] ?>&bucket=<?= urlencode($p['role_label']) ?>"><i class="fa-solid fa-pen-to-square"></i> Evaluate</a>
                    <?php if ($p['evaluation_status'] === 'completed'): ?>
                    <a class="btn" style="margin-left:6px;opacity:.75;" href="principal_evaluate.php?tid=<?= (int)$p['id'] ?>&bucket=<?= urlencode($p['role_label']) ?>&view=1"><i class="fa-solid fa-eye"></i> View</a>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php if ($totalFiltered > 0): ?>
    <div class="table-footer">
        <div>Showing <?= $showingFrom ?> to <?= $showingTo ?> of <?= $totalFiltered ?> <?= strtolower($tabLabels[$tab]) ?> member<?= $totalFiltered === 1 ? '' : 's' ?></div>
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <a class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= principal_eval_qs(['page' => max(1, $page - 1)]) ?>"><i class="fa-solid fa-chevron-left"></i></a>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="page-btn <?= $i === $page ? 'active' : '' ?>" href="<?= principal_eval_qs(['page' => $i]) ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= principal_eval_qs(['page' => min($totalPages, $page + 1)]) ?>"><i class="fa-solid fa-chevron-right"></i></a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    </div>
    <?php if ($tab === 'faculty'): ?>
    <p class="filter-hint" style="margin-top:10px;">Matches Questionnaire → Dean / Principal Evaluation → Principal → Faculty: High School / SHS-assigned teaching personnel only. Every Faculty member uses the Principal shared Faculty question bank.</p>
    <?php elseif ($tab === 'staff'): ?>
    <p class="filter-hint" style="margin-top:10px;">Matches Questionnaire → Dean / Principal Evaluation → Principal → Staff: non-teaching Staff only. Each Staff member uses the questions assigned to that person.</p>
    <?php else: ?>
    <p class="filter-hint" style="margin-top:10px;">Matches Questionnaire → Dean / Principal Evaluation → Principal → EA: the active Executive Assistant uses the Principal EA question bank.</p>
    <?php endif; ?>

    <?php endif; ?>
</main>
<style id="principal-white-theme-final">
:root{
  --dark:#ffffff!important;
  --mid:#ffffff!important;
  --inner:#f5f7fb!important;
  --light:#172033!important;
  --muted:#64748b!important;
  --border:#e2e8f0!important;
  --shadow:0 4px 18px rgba(15,23,42,.08)!important;
  --page-bg:#ffffff!important;
  --card-bg:#ffffff!important;
  --card-border:#e2e8f0!important;
}
html{background:#F8FAFC!important;color-scheme:light!important;}
body{background:#F8FAFC!important;background-image:none!important;color:#172033!important;}

/* Keep the existing navy sidebar; the content area is the white-theme area. */
.sidebar{background:#0A192F!important;color:#E0E6F0!important;border-right:1px solid #172A45!important;}
.sidebar *{color:inherit;}
.sidebar .sb-name{color:#fff!important;}
.sidebar .sb-role,.sidebar .sb-nav a i{color:#f0b84d!important;}
.sidebar .sb-scope,.sidebar .sb-nav a{color:#A0B3C6!important;}
.sidebar .sb-nav a:hover,.sidebar .sb-nav a.active{background:rgba(217,154,43,.15)!important;color:#fff!important;}
.sidebar .sb-logout a{color:#fca5a5!important;}

/* Main content surfaces */
main,.main,.main-content,.content,.page-content{background:#F8FAFC!important;color:#172033!important;}
.stat-card,.section,.period-strip,.filter-bar,.card,.panel,.table-wrap,
.content-card,.table-card,.summary-card,.sum-card,.standing-panel,.eval-card,
.eval-banner,.info-banner,.history-card,.gl-card,.amber-card,.green-card,.red-card,
.person-row,.target-card,.no-eval,.no-data,.no-evaluated,.comment-section,
.avg-summary,.cat-section,.people-list,.evaluator-grid,.eval-q-card,.eval-table-wrap,
.received-item,.q-result,.info-grid>div,.comment-modal,.ra-table-card,
.eval-switcher,.tabs,.level-tabs,.status-tabs,.faculty-subtabs{
  background:#fff!important;
  color:#172033!important;
  border-color:#e2e8f0!important;
  box-shadow:var(--shadow)!important;
}

/* Headings and readable data text */
.page-title,.page-header h1,.section-title,.sheet-name,.target-name,
h1,h2,h3,h4,h5,h6,.section h2,.eval-modal-title,.modal-section-title,
.stat-card .num,.tracker-item .big,.eval-q-text,.eval-qtext,.q-text,.info-value,
.received-anon,.cat-name-modal,.cat-score-modal{color:#0f172a!important;}
.page-sub,.sheet-desig,.target-desig,.muted,.hint,.helper,.filter-hint,.empty-note,
.main p,.main label,.main td,.main li,.main small,.q-score,.q-no,.received-meta,
.info-label,.loading-eval,.eval-rating-scale-note,.eval-read-score{color:#64748b!important;}
table{color:#172033!important;}
table thead th,table.data th,.q-table th{background:#f8fafc!important;color:#334155!important;border-color:#e2e8f0!important;}
table tbody td,table.data td,.q-table td{color:#334155!important;border-color:#e2e8f0!important;}
table tbody tr:hover,.person-row:hover,.standing-item:hover{background:#f8fafc!important;}

/* Accent elements stay amber/semantic rather than reverting to dark-mode text. */
.stat-card i,.section h2 i,.eval-q-category,.eval-category-heading,
.eval-modal-title i,.period-item .v,.period-badge,.back-link,
.stat-card .label,.period-item .k,.received-score,.score-big,.cat-score-modal,
.bell-btn,.bell-head button,.bell-list li i,.eval-banner-title,.eval-banner-icon,
.cat-title,.comment-title,.search-box button,.section h2 i{color:#d99a2b!important;}
.period-badge{background:rgba(217,154,43,.12)!important;border-color:rgba(217,154,43,.28)!important;}
.period-badge.closed{background:rgba(240,84,84,.10)!important;border-color:rgba(240,84,84,.28)!important;color:#dc2626!important;}
.period-badge.gray{background:#f1f5f9!important;border-color:#cbd5e1!important;color:#64748b!important;}

/* Forms */
input,select,textarea,
.search-box input[type=text],.search-box select,.form-group input,
.filter-field select,.filter-field input[type=text],.ra-filter-row select,.ra-search,
.eval-comment-box{
  background:#fff!important;color:#172033!important;border-color:#cbd5e1!important;
}
input::placeholder,textarea::placeholder{color:#94a3b8!important;}
input:focus,select:focus,textarea:focus{border-color:#d99a2b!important;box-shadow:0 0 0 3px rgba(217,154,43,.10)!important;outline:none!important;}

/* Buttons / tabs */
.report-btns button,.qa-btns a,.filter-btns a,a.btn,
.btn,.action-btn,.back-btn,.btn-print,.btn-archive,.btn-restore,.btn-solid,
.btn-archived-link,.ra-tool-btn,.page-btn{
  background:rgba(217,154,43,.10)!important;
  border-color:rgba(217,154,43,.30)!important;
  color:#a16207!important;
}
.report-btns button:hover,.qa-btns a:hover,.filter-btns a:hover,a.btn:hover,
.btn:hover,.action-btn:hover,.back-btn:hover,.btn-print:hover,.btn-archive:hover,
.btn-restore:hover,.btn-archived-link:hover,.ra-tool-btn:hover,.page-btn:hover{
  background:rgba(217,154,43,.16)!important;color:#92400e!important;
}
.btn-primary,.btn-solid{background:#d99a2b!important;color:#0A192F!important;border-color:#d99a2b!important;}
.btn-primary:hover,.btn-solid:hover{background:#f0b84d!important;color:#0A192F!important;}
.report-btns a.active,.filter-btns a.active,.group-tab.active,
.eval-tab.student.active,.eval-tab.peer.active,.eval-tab.multi-role.active,
.tab.active,.level-tab.active,.status-tab.active,.desig-subtab.active,
.page-btn.active{background:rgba(217,154,43,.14)!important;color:#a16207!important;border-color:rgba(217,154,43,.35)!important;}
.eval-tab,.tab,.level-tab,.status-tab,.group-tab,.desig-subtab{color:#64748b!important;background:transparent!important;}
.eval-tab:hover,.tab:hover,.level-tab:hover,.status-tab:hover,.group-tab:hover,.desig-subtab:hover{color:#334155!important;background:#f8fafc!important;}

/* Evaluation questionnaire */
.eval-q-card,.eval-table-wrap{background:#fff!important;}
.eval-rating-opt{background:#f8fafc!important;color:#334155!important;border-color:#cbd5e1!important;}
.eval-rating-opt:has(input:checked){background:rgba(217,154,43,.10)!important;color:#92400e!important;border-color:#d99a2b!important;}
.eval-table th{background:#f8fafc!important;color:#64748b!important;border-color:#e2e8f0!important;}
.eval-table td{border-color:#e2e8f0!important;color:#334155!important;}
.eval-rating-cell label{background:#fff!important;color:#64748b!important;border-color:#cbd5e1!important;}
.eval-rating-cell label:hover{background:rgba(217,154,43,.08)!important;border-color:#d99a2b!important;}
.eval-rating-cell input:checked + label{background:#d99a2b!important;color:#fff!important;border-color:#d99a2b!important;}
.eval-qno{color:#b8801f!important;}
.eval-comment-box{color:#334155!important;}

/* Status pills */
.pill.good{background:rgba(16,185,129,.12)!important;color:#047857!important;}
.pill.warn{background:rgba(217,154,43,.12)!important;color:#a16207!important;}
.pill.bad{background:rgba(240,84,84,.10)!important;color:#b91c1c!important;}
.alert.success{background:rgba(16,185,129,.10)!important;color:#047857!important;border-color:rgba(16,185,129,.25)!important;}
.alert.error{background:rgba(240,84,84,.10)!important;color:#b91c1c!important;border-color:rgba(240,84,84,.25)!important;}

/* Progress bars */
.bar-wrap,.score-bar-bg,.eval-bar-bg,.avg-bar-bg,.cat-bar{background:#e2e8f0!important;}
.bar-fill,.avg-bar-fill,.cat-bar>div{background:linear-gradient(90deg,#b8801f,#f0b84d)!important;}

/* Notifications */
.notif-list li,.bell-list li{background:#f8fafc!important;color:#334155!important;border-color:#e2e8f0!important;}
.notif-list li.unseen,.bell-list li.unseen{background:rgba(217,154,43,.08)!important;}
.bell-btn{background:#fff!important;border-color:#cbd5e1!important;color:#d99a2b!important;}
.bell-btn:hover,.bell-btn[aria-expanded="true"]{background:rgba(217,154,43,.10)!important;border-color:rgba(217,154,43,.35)!important;}
.bell-panel{background:#fff!important;border-color:#e2e8f0!important;box-shadow:0 18px 44px rgba(15,23,42,.16)!important;color:#172033!important;}
.bell-head{border-color:#e2e8f0!important;}
.bell-head h3{color:#0f172a!important;}
.bell-list li{color:#334155!important;}
.bell-count{border-color:#fff!important;}

/* Reports / analytics data surfaces and modal */
.standing-score,.pstat-val,.avg-score-big,.avg-score-label{color:#047857!important;}
.standing-title.top{color:#047857!important;}
.standing-title.low{color:#dc2626!important;}
.comment-text{background:#f8fafc!important;color:#475569!important;border-color:#e2e8f0!important;}
.info-grid>div,.q-result,.received-item{border-color:#e2e8f0!important;}
.eval-modal-overlay{background:rgba(15,23,42,.55)!important;}
.eval-modal{background:#fff!important;color:#172033!important;border-color:#e2e8f0!important;}
.eval-modal-header{border-color:#e2e8f0!important;}
.eval-modal-close{color:#64748b!important;}
.star{color:#cbd5e1!important;}
.star.filled{color:#facc15!important;}

/* Account / settings / roster utilities */
.profile-photo-lg{border-color:#d99a2b!important;}
.avatar-sm{border-color:rgba(217,154,43,.45)!important;}
.btn-reset{color:#475569!important;border-color:#cbd5e1!important;background:#fff!important;}
.structure-note{background:rgba(217,154,43,.06)!important;border-color:rgba(217,154,43,.24)!important;}
.structure-note p{color:#475569!important;}
.structure-note p b{color:#0f172a!important;}

@media(max-width:768px){
  body{background:#F8FAFC!important;}
}
</style>
<style id="principal-evaluation-tabs-final">
/* Compact report-style tabs: same visual treatment as Principal Reports. */
.eval-tabs{width:auto!important;display:flex!important;align-items:center!important;gap:8px!important;flex-wrap:wrap!important;background:transparent!important;border:0!important;border-radius:0!important;padding:0!important;box-shadow:none!important;box-sizing:border-box!important;overflow:visible!important;}
.eval-tab{display:flex!important;align-items:center!important;gap:9px!important;padding:11px 20px!important;border-radius:10px!important;border:1px solid #E2E8F0!important;background:#F8FAFC!important;color:#475569!important;min-width:0!important;box-shadow:none!important;justify-content:flex-start!important;}
.eval-tab:hover{color:#334155!important;background:#F8FAFC!important;}
.eval-tab.active{background:#FFF7ED!important;color:#B45309!important;border-color:#FED7AA!important;box-shadow:none!important;}
.eval-tab .badge{background:#F1F5F9!important;color:#475569!important;}
.eval-tab.active .badge{background:#FFEDD5!important;color:#B45309!important;}
@media(max-width:700px){.eval-tabs{gap:6px!important;}.eval-tab{padding:10px 14px!important;}}
</style>
</body>
</html>
<style id="white-theme-override">
:root{--dark:#ffffff;--mid:#ffffff;--inner:#f5f7fb;--light:#172033;--muted:#64748b;--shadow:0 4px 18px rgba(15,23,42,.08);}
html,body{background:#F8FAFC!important;color:#172033!important;}
body{background-image:none!important;}
main,.main-content,.content,.page-content{background:#F8FAFC!important;color:#172033!important;}
.sidebar{background:#0A192F!important;color:#E0E6F0!important;border-right:1px solid #172A45!important;}
.sidebar *,.sidebar a{color:inherit;}
.stat-card,.section,.period-strip,.filter-bar,.card,.panel,.table-wrap,.modal-content{background:#fff!important;color:#172033!important;border-color:#e2e8f0!important;box-shadow:var(--shadow)!important;}
h1,h2,h3,h4,h5,h6,.section-title,.page-title{color:#0f172a!important;}
p,span,label,td,th,small{color:inherit;}
table{color:#172033!important;}
table thead th{background:#f8fafc!important;color:#334155!important;border-color:#e2e8f0!important;}
table tbody td{border-color:#e2e8f0!important;}
input,select,textarea{background:#fff!important;color:#172033!important;border-color:#cbd5e1!important;}
.search-box input[type=text],.search-box select,.form-group input,.filter-field select,.filter-field input[type=text]{background:#fff!important;color:#172033!important;}
.btn-reset{color:#475569!important;border-color:#cbd5e1!important;}
.notif-list li{background:#f8fafc!important;}
</style>

<style id="principal-ea-exact-workspace-canvas">
/*
  Principal workspace shell — matches the EA feature/iframe composition:
  white outer viewport + an inset #F8FAFC workspace canvas.
  The canvas is the feature surface; white cards remain white.
*/
html{
  background:#FFFFFF !important;
  color-scheme:light !important;
}
body{
  background:#FFFFFF !important;
  background-image:none !important;
  color:#172033 !important;
}

/* Leave the navy Principal sidebar unchanged. */
.sidebar{
  background:#0A192F !important;
  color:#E0E6F0 !important;
}

/* The feature workspace is inset, like the EA iframe inside its white page. */
.main,
main.main{
  position:relative !important;
  flex:1 1 auto !important;
  width:auto !important;
  max-width:none !important;
  min-height:calc(100vh - 20px) !important;
  margin:20px 20px 0 20px !important;
  padding:24px 34px 34px !important;
  background:#F8FAFC !important;
  color:#172033 !important;
  border-radius:16px 16px 0 0 !important;
  box-shadow:none !important;
  isolation:auto !important;
  overflow:visible !important;
}

/* Disable the earlier pseudo-canvas implementation; the main itself is now
   the correctly inset workspace surface. */
.main::before,
main.main::before{
  display:none !important;
  content:none !important;
}

/* Page headings live on the same light workspace surface, as on the EA
   feature pages rendered inside their iframe. */
.main > .page-header,
main.main > .page-header{
  background:transparent !important;
  border:0 !important;
  box-shadow:none !important;
}

/* White feature surfaces remain clearly distinct from the #F8FAFC canvas. */
.main .page-header:has(.page-title),
.main .section,
.main .card,
.main .panel,
.main .table-card,
.main .content-card,
.main .stat-card,
.main .period-strip,
.main .filter-bar,
.main .table-wrap,
.main .table-card-wrap,
.main .content-panel,
.main .eval-banner,
.main .info-banner,
.main .history-card,
.main .gl-card,
.main .amber-card,
.main .green-card,
.main .red-card,
.main .ra-table-card,
.main .eval-card,
.main .comment-section,
.main .avg-summary,
.main .standing-panel,
.main .person-row,
.main .target-card,
.main .no-eval,
.main .no-data,
.main .no-evaluated,
.main .no-archived,
.main .evaluator-grid,
.main .people-list,
.main .cat-section,
.main .results-card,
.main .result-card,
.main .feature-card{
  background:#FFFFFF !important;
  color:#172033 !important;
  border-color:#D9E4EF !important;
  box-shadow:0 1px 5px rgba(15,23,42,.035) !important;
}

/* Soft inner tint, matching the EA logs table header / page surfaces. */
.main table thead th,
.main .table-head,
.main .table-header,
.main .thead,
.main .subtle-head{
  background:#F4F8FF !important;
  color:#4B6580 !important;
  border-color:#D9E4EF !important;
}

/* Responsive: retain the inset composition without squeezing narrow screens. */
@media (max-width:768px){
  .main,
  main.main{
    margin:12px 12px 0 12px !important;
    padding:20px 18px 28px !important;
    min-height:calc(100vh - 12px) !important;
    border-radius:12px 12px 0 0 !important;
  }
}

@media print{
  html,body{
    background:#FFFFFF !important;
  }
  .main,
  main.main{
    margin:0 !important;
    padding:20px !important;
    min-height:0 !important;
    background:#FFFFFF !important;
    border-radius:0 !important;
  }
}
</style>
