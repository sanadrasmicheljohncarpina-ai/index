<?php
// session_bootstrap.php — include this BEFORE session_start() everywhere
session_set_cookie_params([
    'lifetime' => 0,        // session cookie, dies when browser closes
    'path'     => '/',      // available across the whole site, not just /admin/
    'domain'   => '',       // let the browser infer it — avoids localhost vs IP mismatches
    'secure'   => false,    // set true only if you're on https
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once 'db.php';

// ── AUTH GUARD ────────────────────────────────────────────
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'school_head') {
    header("Location: school_head_login.php");
    exit;
}

// Pull fresh user info (photo, designation, etc.)
$stmt = $mysqli->prepare("SELECT full_name, username, email, designation, photo FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ── ACTIVE EVALUATION PERIOD ──────────────────────────────
$period = null;
$pr = $mysqli->query("SELECT * FROM evaluation_periods WHERE is_active=1 LIMIT 1");
if ($pr) $period = $pr->fetch_assoc();

// ── DASHBOARD STAT COUNTS ─────────────────────────────────
// Personnel Under Review: all approved, active teacher/staff accounts —
// this is who the School Head can evaluate system-wide.
// Pending Evaluations: of those, how many this School Head has NOT yet
// submitted a 'school_head' evaluation for during the active period.
$pendingEvaluations   = 0;
$personnelUnderReview = 0;
$reportsGenerated     = 0;
$evaluatedCount       = 0;

$cnt = $mysqli->query("
    SELECT COUNT(*) AS c FROM users
    WHERE role IN ('teacher','staff') AND is_active=1 AND account_status='approved'
");
if ($cnt) {
    $row = $cnt->fetch_assoc();
    $personnelUnderReview = (int)($row['c'] ?? 0);
}

if ($period) {
    $period_id_int = (int)$period['id'];
    $evStmt = $mysqli->prepare("
        SELECT COUNT(DISTINCT target_user_id) AS c FROM evaluation_tracker
        WHERE evaluator_id = ? AND eval_type = 'school_head' AND period_id = ?
    ");
    $evStmt->bind_param("ii", $_SESSION['user_id'], $period_id_int);
    $evStmt->execute();
    $evRes = $evStmt->get_result();
    if ($evRes) $evaluatedCount = (int)($evRes->fetch_assoc()['c'] ?? 0);
    $evStmt->close();
    $pendingEvaluations = max(0, $personnelUnderReview - $evaluatedCount);
} else {
    // No active period — nothing can be submitted yet, so nothing is
    // meaningfully "pending" either.
    $pendingEvaluations = 0;
}

$mysqli->close();

$photo_src = !empty($me['photo']) ? '../image/' . $me['photo'] : '../image/pbi_logo';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI — School Head Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;--emerald:#059669;--emerald-h:#10B981;--light:#E0E6F0;--muted:#A0B3C6;--radius:10px;--shadow:0 8px 32px rgba(0,0,0,0.45);}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{min-height:100vh;background:var(--dark);font-family:'DM Sans',sans-serif;color:var(--light);display:flex;}

/* SIDEBAR */
.sidebar{width:250px;flex-shrink:0;background:rgba(23,42,69,.9);border-right:1px solid rgba(255,255,255,.08);min-height:100vh;padding:28px 20px;display:flex;flex-direction:column;}
.sb-profile{text-align:center;margin-bottom:26px;}
.sb-photo{width:72px;height:72px;border-radius:50%;object-fit:cover;border:2.5px solid var(--emerald);box-shadow:0 0 18px rgba(5,150,105,.4);margin:0 auto 10px;display:block;}
.sb-name{font-weight:700;font-size:15px;color:#fff;}
.sb-role{font-size:11px;color:var(--emerald-h);text-transform:uppercase;letter-spacing:.6px;margin-top:2px;}
.sb-nav{display:flex;flex-direction:column;gap:4px;margin-top:10px;}
.sb-nav a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:var(--muted);text-decoration:none;font-size:14px;font-weight:500;transition:background .2s,color .2s;}
.sb-nav a:hover,.sb-nav a.active{background:rgba(5,150,105,.15);color:#fff;}
.sb-nav a i{width:18px;text-align:center;color:var(--emerald-h);}
.sb-logout{margin-top:auto;}
.sb-logout a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:#fca5a5;text-decoration:none;font-size:14px;font-weight:500;transition:background .2s;}
.sb-logout a:hover{background:rgba(240,84,84,.12);}

/* MAIN */
.main{flex:1;padding:36px 44px;}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;}
.page-title{font-family:'Rajdhani',sans-serif;font-size:28px;font-weight:700;color:#fff;letter-spacing:1px;}
.page-sub{font-size:13px;color:var(--muted);margin-top:4px;}
.card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;margin-bottom:30px;}
.stat-card{background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:20px;box-shadow:var(--shadow);}
.stat-card i{color:var(--emerald-h);font-size:20px;margin-bottom:10px;}
.stat-card .num{font-size:26px;font-weight:700;color:#fff;}
.stat-card .label{font-size:12px;color:var(--muted);margin-top:4px;}
.welcome-card{background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:26px;box-shadow:var(--shadow);}
.welcome-card h2{font-family:'Rajdhani',sans-serif;font-size:20px;color:#fff;margin-bottom:8px;}
.welcome-card p{color:var(--muted);font-size:14px;line-height:1.6;}
.period-badge{background:rgba(5,150,105,.14);border:1px solid rgba(5,150,105,.3);color:var(--emerald-h);padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;display:flex;align-items:center;gap:7px;}
.period-badge.closed{background:rgba(240,84,84,.1);border-color:rgba(240,84,84,.3);color:#fca5a5;}
@media(max-width:768px){body{flex-direction:column;}.sidebar{width:100%;min-height:auto;}}
</style>
</head>
<body>

<aside class="sidebar">
    <div class="sb-profile">
        <img class="sb-photo" src="<?= htmlspecialchars($photo_src) ?>" alt="Profile"/>
        <div class="sb-name"><?= htmlspecialchars($me['full_name'] ?? 'School Head') ?></div>
        <div class="sb-role"><?= htmlspecialchars($me['designation'] ?? 'School Head') ?></div>
    </div>
    <nav class="sb-nav">
        <a href="school_head_dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="school_head_evaluations.php"><i class="fa-solid fa-clipboard-list"></i> Evaluations</a>
        <a href="school_head_personnel.php"><i class="fa-solid fa-users"></i> Personnel</a>
        <a href="#"><i class="fa-solid fa-chart-line"></i> Reports</a>
        <a href="#"><i class="fa-solid fa-gear"></i> Account Settings</a>
    </nav>
    <div class="sb-logout">
        <a href="school_head_logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
    </div>
</aside>

<main class="main">
    <div class="page-header">
        <div>
            <div class="page-title">Welcome, <?= htmlspecialchars(explode(',', $me['full_name'] ?? 'School Head')[0]) ?></div>
            <div class="page-sub">Pandan Bay Institute — Evaluation System</div>
        </div>
        <?php if ($period): ?>
        <div class="period-badge"><i class="fa-solid fa-calendar-check"></i> <?= htmlspecialchars($period['period_label'] ?? ($period['semester'] ?? 'Active Period')) ?></div>
        <?php else: ?>
        <div class="period-badge closed"><i class="fa-solid fa-calendar-xmark"></i> No Active Period</div>
        <?php endif; ?>
    </div>

    <div class="card-grid">
        <a href="school_head_evaluations.php" style="text-decoration:none;color:inherit;">
        <div class="stat-card"><i class="fa-solid fa-clipboard-check"></i><div class="num"><?= (int)$pendingEvaluations ?></div><div class="label">Pending Evaluations</div></div>
        </a>
        <a href="school_head_evaluations.php" style="text-decoration:none;color:inherit;">
        <div class="stat-card"><i class="fa-solid fa-user-group"></i><div class="num"><?= (int)$personnelUnderReview ?></div><div class="label">Personnel Under Review</div></div>
        </a>
        <div class="stat-card"><i class="fa-solid fa-file-lines"></i><div class="num"><?= (int)$reportsGenerated ?></div><div class="label">Reports Generated</div></div>
    </div>

    <div class="welcome-card">
        <h2>Getting Started</h2>
        <p>This is your School Head dashboard. From here you'll be able to review evaluations, manage personnel under your department, and generate reports. Start with <a href="school_head_evaluations.php" style="color:var(--emerald-h);">Evaluations</a> to see your teacher and staff roster.</p>
    </div>
</main>

</body>
</html>