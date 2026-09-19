<?php
// principal_common.php — include this at the top of every principal_*.php
// page, BEFORE any HTML output. Centralizes what principal_dashboard.php
// used to duplicate: session setup, auth guard, self-healing schema, the
// safe_scalar/esc_list helpers, the logged-in principal's profile + scope,
// the active evaluation period, and the shared sidebar/header markup.

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => false,   // set true only if you're on https
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once 'db.php';

// ── AUTH GUARD ────────────────────────────────────────────
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'principal') {
    header("Location: principal_login.php");
    exit;
}

// ── SELF-HEALING SCHEMA ─────────────────────────────────────
@$mysqli->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS academic_level VARCHAR(20) NULL");
@$mysqli->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS grade_level VARCHAR(10) NULL");
@$mysqli->query("
    CREATE TABLE IF NOT EXISTS user_year_levels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        year_level VARCHAR(10) NOT NULL,
        UNIQUE KEY uniq_user_year (user_id, year_level)
    )
");

// ── SAFE QUERY HELPERS (never fatal-error the page on a schema mismatch) ─
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
function esc_list(mysqli $mysqli, array $vals): string {
    if (empty($vals)) return "''";
    return "'" . implode("','", array_map([$mysqli, 'real_escape_string'], $vals)) . "'";
}

// ── PULL PRINCIPAL PROFILE ────────────────────────────────
$stmt = $mysqli->prepare("SELECT full_name, username, email, designation, photo, education_level FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();
$photo_src = !empty($me['photo']) ? '../image/' . $me['photo'] : '../image/pbi_logo';

// ── SCOPE (Principal must never see College) ────────────────
$myLevel = $me['education_level'] ?? 'both';
if ($myLevel === 'junior_high') {
    $scopeAcademicLevels = ['junior_high'];
    $scopeGrades = ['7', '8', '9', '10'];
} elseif ($myLevel === 'senior_high') {
    $scopeAcademicLevels = ['senior_high'];
    $scopeGrades = ['11', '12'];
} else {
    $scopeAcademicLevels = ['junior_high', 'senior_high'];
    $scopeGrades = ['7', '8', '9', '10', '11', '12'];
}
$scopeAcademicIn = esc_list($mysqli, $scopeAcademicLevels);
$scopeGradesIn   = esc_list($mysqli, $scopeGrades);
$scopeLabel = $myLevel === 'both' ? 'Junior High & Senior High' : ($myLevel === 'junior_high' ? 'Junior High School' : 'Senior High School');

// ── ACTIVE EVALUATION PERIOD ──────────────────────────────
$period = null;
$pr = $mysqli->query("SELECT * FROM evaluation_periods WHERE is_active=1 LIMIT 1");
if ($pr) $period = $pr->fetch_assoc();
$period_id_int = $period ? (int)$period['id'] : 0;

$daysRemaining = null;
if ($period) {
    $endRaw = $period['end_date'] ?? $period['period_end'] ?? $period['deadline'] ?? null;
    if ($endRaw) {
        $diff = (strtotime($endRaw) - strtotime(date('Y-m-d')));
        $daysRemaining = (int)ceil($diff / 86400);
    }
}

// ── SHARED SIDEBAR ─────────────────────────────────────────
// $active: 'dashboard' | 'evaluations' | 'teachers' | 'staff' | 'reports' | 'settings'
function render_principal_sidebar(string $active, array $me, string $scopeLabel, string $photo_src): void {
    $links = [
        'dashboard'   => ['principal_dashboard.php',        'fa-gauge',              'Dashboard'],
        'evaluations' => ['principal_evaluations.php',      'fa-clipboard-list',     'Evaluation Tracker'],
        'teachers'    => ['principal_teachers.php',         'fa-chalkboard-user',    'Teachers'],
        'staff'       => ['principal_staff.php',             'fa-users',              'Staff'],
        'reports'     => ['principal_reports.php',           'fa-chart-line',         'Reports'],
        'settings'    => ['principal_account_settings.php',  'fa-gear',               'Settings'],
    ];
    ?>
    <aside class="sidebar">
        <div class="sb-profile">
            <img class="sb-photo" src="<?= htmlspecialchars($photo_src) ?>" alt="Profile"/>
            <div class="sb-name"><?= htmlspecialchars($me['full_name'] ?? 'Principal') ?></div>
            <div class="sb-role"><?= htmlspecialchars($me['designation'] ?? 'Principal') ?></div>
            <div class="sb-scope"><?= htmlspecialchars($scopeLabel) ?></div>
        </div>
        <nav class="sb-nav" aria-label="Principal navigation">
            <div class="sb-nav-section-label">MAIN</div>
            <?php foreach (['dashboard', 'evaluations', 'teachers', 'staff', 'reports'] as $key): ?>
                <?php [$href, $icon, $label] = $links[$key]; ?>
                <a href="<?= htmlspecialchars($href) ?>" class="<?= $key === $active ? 'active' : '' ?>">
                    <i class="fa-solid <?= htmlspecialchars($icon) ?>"></i> <?= htmlspecialchars($label) ?>
                </a>
            <?php endforeach; ?>

            <div class="sb-nav-section-label">ADMINISTRATION</div>
            <?php [$href, $icon, $label] = $links['settings']; ?>
            <a href="<?= htmlspecialchars($href) ?>" class="<?= $active === 'settings' ? 'active' : '' ?>">
                <i class="fa-solid <?= htmlspecialchars($icon) ?>"></i> <?= htmlspecialchars($label) ?>
            </a>

            <div class="sb-nav-section-label">ACCOUNT</div>
            <a href="../logout.php" class="sb-logout-link"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </nav>
    </aside>
    <?php
}

// ── SHARED <style> BLOCK ────────────────────────────────────
function render_principal_styles(): void {
    ?>
    <style>
    :root{--dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;--amber:#d99a2b;--amber-h:#f0b84d;--amber-dark:#b8801f;--light:#E0E6F0;--muted:#A0B3C6;--radius:10px;--shadow:0 8px 32px rgba(0,0,0,0.45);--danger:#f05454;--good:#10B981;}
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    body{min-height:100vh;background:var(--dark);font-family:'DM Sans',sans-serif;color:var(--light);display:flex;}
    .sidebar{width:250px;flex-shrink:0;background:rgba(23,42,69,.9);border-right:1px solid rgba(255,255,255,.08);min-height:100vh;padding:28px 20px;display:flex;flex-direction:column;}
    .sb-profile{text-align:center;margin-bottom:26px;}
    .sb-photo{width:72px;height:72px;border-radius:50%;object-fit:cover;border:2.5px solid var(--amber);box-shadow:0 0 18px rgba(217,154,43,.4);margin:0 auto 10px;display:block;}
    .sb-name{font-weight:700;font-size:15px;color:#fff;}
    .sb-role{font-size:11px;color:var(--amber-h);text-transform:uppercase;letter-spacing:.6px;margin-top:2px;}
    .sb-scope{font-size:10px;color:var(--muted);margin-top:4px;}
    .sb-nav{display:flex;flex-direction:column;gap:5px;margin-top:10px;width:100%;}
    .sb-nav-section-label{width:auto;margin:5px 12px 1px;padding:0 2px;color:var(--muted);font-size:10px;font-weight:800;letter-spacing:1.35px;line-height:1.2;text-transform:uppercase;}
    .sb-nav-section-label:first-child{margin-top:0;}
    .sb-nav a{box-sizing:border-box;width:100%;min-height:42px;margin:0;padding:5px 14px;display:flex;align-items:center;gap:10px;border-radius:8px;color:var(--muted);text-decoration:none;font-size:14px;font-weight:500;transition:background .2s,color .2s;}
    .sb-nav a:hover,.sb-nav a.active{background:rgba(217,154,43,.15);color:#fff;}
    .sb-nav a i{width:18px;flex:0 0 18px;text-align:center;color:var(--amber-h);}
    .sb-nav .sb-logout-link{color:#fca5a5;}
    .sb-nav .sb-logout-link:hover{background:rgba(240,84,84,.12);color:#fecaca;}
    .sb-logout{display:none;}
    .main{flex:1;padding:36px 44px;}
    .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:14px;}
    .page-title{font-family:'Rajdhani',sans-serif;font-size:28px;font-weight:700;color:#fff;letter-spacing:1px;}
    .page-sub{font-size:13px;color:var(--muted);margin-top:4px;}
    .card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:30px;}
    .stat-card{background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:20px;box-shadow:var(--shadow);}
    .stat-card i{color:var(--amber-h);font-size:20px;margin-bottom:10px;}
    .stat-card .num{font-size:26px;font-weight:700;color:#fff;}
    .stat-card .label{font-size:12px;color:var(--muted);margin-top:4px;}
    .section{background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:24px;box-shadow:var(--shadow);margin-bottom:26px;}
    .section h2{font-family:'Rajdhani',sans-serif;font-size:19px;color:#fff;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
    .section h2 i{color:var(--amber-h);font-size:16px;}
    table.data{width:100%;border-collapse:collapse;font-size:13px;}
    table.data th{text-align:left;color:var(--muted);font-weight:600;padding:10px 12px;border-bottom:1px solid rgba(255,255,255,.08);text-transform:uppercase;font-size:11px;letter-spacing:.4px;}
    table.data td{padding:10px 12px;border-bottom:1px solid rgba(255,255,255,.05);vertical-align:middle;}
    table.data tr:last-child td{border-bottom:none;}
    table.data tr.row-link{cursor:pointer;transition:background .15s;}
    table.data tr.row-link:hover{background:rgba(217,154,43,.06);}
    .bar-wrap{background:rgba(255,255,255,.08);border-radius:6px;height:8px;width:100%;overflow:hidden;}
    .bar-fill{height:100%;background:linear-gradient(90deg,var(--amber-dark),var(--amber-h));border-radius:6px;}
    .pill{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;}
    .pill.good{background:rgba(16,185,129,.14);color:var(--good);}
    .pill.warn{background:rgba(217,154,43,.14);color:var(--amber-h);}
    .pill.bad{background:rgba(240,84,84,.12);color:#fca5a5;}
    .two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
    .mini-list{list-style:none;font-size:13px;}
    .mini-list li{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.05);}
    .mini-list li:last-child{border-bottom:none;}
    .mini-list .name{color:var(--light);}
    .mini-list .val{color:var(--amber-h);font-weight:600;}
    .tracker-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;text-align:center;}
    .tracker-item .big{font-size:22px;font-weight:700;color:#fff;}
    .tracker-item .lbl{font-size:11px;color:var(--muted);margin-top:4px;text-transform:uppercase;letter-spacing:.4px;}
    .report-btns{display:flex;flex-wrap:wrap;gap:10px;}
    .report-btns button, .qa-btns a, .filter-btns a, a.btn{background:rgba(217,154,43,.12);border:1px solid rgba(217,154,43,.35);color:var(--amber-h);padding:10px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:7px;transition:background .2s;}
    .report-btns button:hover, .qa-btns a:hover, .filter-btns a:hover, a.btn:hover{background:rgba(217,154,43,.22);}
    .report-btns a.active, .filter-btns a.active{background:rgba(217,154,43,.32);color:#fff;}
    .notif-list{list-style:none;font-size:13px;}
    .notif-list li{padding:10px 12px;border-radius:8px;background:rgba(255,255,255,.03);margin-bottom:8px;display:flex;align-items:center;gap:10px;}
    .notif-list li i{color:var(--amber-h);}
    .notif-list li:last-child{margin-bottom:0;}
    .qa-btns{display:flex;flex-wrap:wrap;gap:10px;}
    .period-badge{background:rgba(217,154,43,.14);border:1px solid rgba(217,154,43,.3);color:var(--amber-h);padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;display:flex;align-items:center;gap:7px;}
    .period-badge.closed{background:rgba(240,84,84,.1);border-color:rgba(240,84,84,.3);color:#fca5a5;}
    .empty-note{color:var(--muted);font-size:13px;font-style:italic;}
    .search-box{display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;}
    .search-box input[type=text]{flex:1;min-width:200px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);border-radius:8px;padding:10px 14px;color:#fff;font-size:13px;}
    .search-box input[type=text]:focus{outline:none;border-color:var(--amber);}
    .search-box select{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);border-radius:8px;padding:10px 14px;color:#fff;font-size:13px;}
    .search-box button{background:var(--amber);border:none;border-radius:8px;padding:10px 18px;color:#0A192F;font-weight:700;font-size:13px;cursor:pointer;}
    .avatar-sm{width:32px;height:32px;border-radius:50%;object-fit:cover;border:1.5px solid rgba(217,154,43,.5);vertical-align:middle;margin-right:8px;}
    .profile-card{display:flex;align-items:center;gap:20px;margin-bottom:24px;}
    .profile-photo-lg{width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--amber);}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
    .form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:16px;}
    .form-group label{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;font-weight:600;}
    .form-group input{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);border-radius:8px;padding:10px 14px;color:#fff;font-size:14px;}
    .form-group input:focus{outline:none;border-color:var(--amber);}
    .btn-primary{background:var(--amber);border:none;border-radius:8px;padding:11px 22px;color:#0A192F;font-weight:700;font-size:13px;cursor:pointer;}
    .btn-primary:hover{background:var(--amber-h);}
    .alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:18px;}
    .alert.success{background:rgba(16,185,129,.12);color:var(--good);border:1px solid rgba(16,185,129,.3);}
    .alert.error{background:rgba(240,84,84,.12);color:#fca5a5;border:1px solid rgba(240,84,84,.3);}
    .back-link{display:inline-flex;align-items:center;gap:6px;color:var(--amber-h);text-decoration:none;font-size:13px;margin-bottom:16px;}
    .back-link:hover{text-decoration:underline;}
    @media(max-width:900px){.two-col{grid-template-columns:1fr;}.form-grid{grid-template-columns:1fr;}}
    @media(max-width:768px){body{flex-direction:column;}.sidebar{width:100%;min-height:auto;}}
    </style>

<style id="principal-white-theme-final">
:root{
  --dark:#ffffff!important;
  --mid:#ffffff!important;
  --inner:#f5f7fb!important;
  --light:#172033!important;
  --muted:#64748b!important;
  --border:#e2e8f0!important;
  --shadow:0 4px 18px rgba(15,23,42,.08)!important;
  --page-bg:#F8FAFC!important;
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
    <?php
}

function render_period_badge($period, $daysRemaining): void {
    if ($period): ?>
        <div class="period-badge"><i class="fa-solid fa-calendar-check"></i> <?= htmlspecialchars($period['period_label'] ?? ($period['semester'] ?? 'Active Period')) ?><?= $daysRemaining !== null ? " — {$daysRemaining}d left" : '' ?></div>
    <?php else: ?>
        <div class="period-badge closed"><i class="fa-solid fa-calendar-xmark"></i> No Active Period</div>
    <?php endif;
}

function html_head_open(string $title): void {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= htmlspecialchars($title) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<?php render_principal_styles(); ?>

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

<style id="principal-subtle-feature-background">
/* Subtle light backdrop for every Principal feature area. Feature cards remain white; sidebar stays navy. */
html{background:#F8FAFC !important;}
body{background:#F8FAFC !important;}
.main, main.main, .main-content, .content, .page-content{background:#F8FAFC !important;}
.page-header,.eval-switcher,.eval-banner,.group-tab-wrap,.group-tabs,.desig-subtabs,.faculty-subtabs,
.card,.panel,.section,.table-card,.content-card,.stat-card,.info-banner,.gl-card,.amber-card,
.green-card,.red-card,.ra-table-card,.table-wrap,.table-card-wrap,.content-panel,.history-card,
target-card,.eval-card,.comment-section,.avg-summary,.standing-panel,.person-row,.no-evaluated,
.no-archived,.evaluator-grid,.people-list,.cat-section,.period-strip,.filter-bar{background:#FFFFFF !important;}
@media print{html,body,.main,main.main,.main-content,.content,.page-content{background:#fff !important;}}


<style id="principal-ea-style-feature-canvas">
/* Match the EA System Logs feature: subtle light canvas with white content surfaces. */
html{background:#F8FAFC !important;}
body{background:#F8FAFC !important;background-image:none !important;}
.main, main.main, .main-content, .content, .page-content{background:#F8FAFC !important;}
/* Preserve clean white feature cards/panels. */
.page-header,.card,.panel,.section,.table-card,.content-card,.stat-card,
.period-strip,.filter-bar,.table-wrap,.table-card-wrap,.content-panel,
.eval-banner,.info-banner,.history-card,.gl-card,.amber-card,.green-card,.red-card,
.ra-table-card,.eval-card,.comment-section,.avg-summary,.standing-panel,
.person-row,.target-card,.no-eval,.no-data,.no-evaluated,.no-archived,
.evaluator-grid,.people-list,.cat-section,.eval-switcher,.tabs,.level-tabs,
.status-tabs,.faculty-subtabs,.group-tabs,.group-tab-wrap,
.desig-subtabs,.desig-subtab-wrap,.results-card,.result-card,.feature-card{
    background:#FFFFFF !important;
}
/* Light inner surfaces, equivalent to the EA log table header treatment. */
.main table thead th,.main .table-head,.main .table-header,.main .thead,
.main .subtle-head{background:#F4F8FF !important;}
@media print{
  html,body,.main,main.main,.main-content,.content,.page-content{background:#fff !important;}
}
</style>
</style>

<style id="principal-workspace-sharper-final">
/* Final Principal workspace composition: white outer page + sharper light workspace. */
html{background:#FFFFFF!important;color-scheme:light!important;}
body{background:#FFFFFF!important;background-image:none!important;color:#172033!important;}
.sidebar{background:#0A192F!important;}
.main,main.main{
  position:relative!important;
  background:#F3F6FA!important;
  color:#172033!important;
  border:1px solid #E5EAF0!important;
  border-bottom:0!important;
  border-radius:16px 16px 0 0!important;
  box-shadow:none!important;
}
.main > .page-header,main.main > .page-header{
  background:transparent!important;
  border:0!important;
  box-shadow:none!important;
}
.main .page-header,.main .section,.main .card,.main .panel,.main .table-card,.main .content-card,.main .stat-card,
.main .period-strip,.main .filter-bar,.main .table-wrap,.main .table-card-wrap,.main .content-panel,
.main .eval-banner,.main .info-banner,.main .history-card,.main .gl-card,.main .amber-card,.main .green-card,
.main .red-card,.main .ra-table-card,.main .eval-card,.main .comment-section,.main .avg-summary,.main .standing-panel,
.main .person-row,.main .target-card,.main .no-eval,.main .no-data,.main .no-evaluated,.main .no-archived,
.main .evaluator-grid,.main .people-list,.main .cat-section,.main .results-card,.main .result-card,.main .feature-card{
  background:#FFFFFF!important;
  color:#172033!important;
  border-color:#D9E4EF!important;
  box-shadow:0 1px 5px rgba(15,23,42,.035)!important;
}
.main table thead th,.main .table-head,.main .table-header,.main .thead,.main .subtle-head{
  background:#F4F8FF!important;color:#4B6580!important;border-color:#D9E4EF!important;
}
@media(max-width:768px){
  .main,main.main{margin:12px 12px 0!important;padding:20px 18px 28px!important;border-radius:12px 12px 0 0!important;}
}
@media print{
  html,body,.main,main.main{background:#FFFFFF!important;border-color:transparent!important;}
}
</style>
</head>
<body>
    <?php
}