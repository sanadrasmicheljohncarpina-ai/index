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
        'settings'    => ['principal_account_settings.php',  'fa-gear',               'Account Settings'],
    ];
    ?>
    <aside class="sidebar">
        <div class="sb-profile">
            <img class="sb-photo" src="<?= htmlspecialchars($photo_src) ?>" alt="Profile"/>
            <div class="sb-name"><?= htmlspecialchars($me['full_name'] ?? 'Principal') ?></div>
            <div class="sb-role"><?= htmlspecialchars($me['designation'] ?? 'Principal') ?></div>
            <div class="sb-scope"><?= htmlspecialchars($scopeLabel) ?></div>
        </div>
        <nav class="sb-nav">
            <?php foreach ($links as $key => [$href, $icon, $label]): ?>
                <a href="<?= htmlspecialchars($href) ?>" class="<?= $key === $active ? 'active' : '' ?>">
                    <i class="fa-solid <?= htmlspecialchars($icon) ?>"></i> <?= htmlspecialchars($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sb-logout">
            <a href="principal_logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
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
    .sb-nav{display:flex;flex-direction:column;gap:4px;margin-top:10px;}
    .sb-nav a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:var(--muted);text-decoration:none;font-size:14px;font-weight:500;transition:background .2s,color .2s;}
    .sb-nav a:hover,.sb-nav a.active{background:rgba(217,154,43,.15);color:#fff;}
    .sb-nav a i{width:18px;text-align:center;color:var(--amber-h);}
    .sb-logout{margin-top:auto;}
    .sb-logout a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:#fca5a5;text-decoration:none;font-size:14px;font-weight:500;transition:background .2s;}
    .sb-logout a:hover{background:rgba(240,84,84,.12);}
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
</head>
<body>
    <?php
}