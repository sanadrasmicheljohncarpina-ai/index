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

// ── AUTH GUARD ────────────────────────────────────────────
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'school_head') {
    header("Location: school_head_login.php");
    exit;
}

// Pull fresh user info (photo, designation, etc.) for the sidebar
$stmt = $mysqli->prepare("SELECT full_name, username, email, designation, photo FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();

$photo_src = !empty($me['photo']) ? '../image/' . $me['photo'] : '../image/pbi_logo';

$UPLOAD_DIR = defined('UPLOAD_DIR') ? UPLOAD_DIR : '../image/';
$UPLOAD_URL = defined('UPLOAD_URL') ? UPLOAD_URL : '../image/';

// ── SECTOR (Faculty / Staff) ──────────────────────────────────
// Same source personnel_registry.php manages: no-login entries added by
// the superadmin so they can still be included in the questionnaire.
$viewSector = $_GET['sector'] ?? 'Faculty';
$sectorRole = $viewSector === 'Staff' ? 'staff' : 'faculty';

$stmt = $mysqli->prepare("SELECT * FROM users WHERE source='admin_nologin' AND role=? ORDER BY full_name ASC");
$stmt->bind_param("s", $sectorRole);
$stmt->execute();
$entries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$counts = [];
foreach (['Faculty' => 'faculty', 'Staff' => 'staff'] as $label => $r) {
    $cr = $mysqli->prepare("SELECT COUNT(*) AS c FROM users WHERE source='admin_nologin' AND role=?");
    $cr->bind_param("s", $r);
    $cr->execute();
    $counts[$label] = $cr->get_result()->fetch_assoc()['c'] ?? 0;
    $cr->close();
}

$activeCount   = count(array_filter($entries, fn($u) => $u['is_active']));
$inactiveCount = count($entries) - $activeCount;

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Personnel — PBI School Head</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;--emerald:#059669;--emerald-h:#10B981;--light:#E0E6F0;--muted:#A0B3C6;--border:rgba(255,255,255,.08);--radius:10px;--shadow:0 8px 32px rgba(0,0,0,0.45);}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{min-height:100vh;background:var(--dark);font-family:'DM Sans',sans-serif;color:var(--light);display:flex;}

/* SIDEBAR (identical to dashboard/evaluations) */
.sidebar{width:250px;flex-shrink:0;background:rgba(23,42,69,.9);border-right:1px solid var(--border);min-height:100vh;padding:28px 20px;display:flex;flex-direction:column;}
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
.page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px;}
.page-title{font-family:'Rajdhani',sans-serif;font-size:28px;font-weight:700;color:#fff;letter-spacing:1px;}
.page-sub{font-size:13px;color:var(--muted);margin-top:4px;}

.notice{background:rgba(43,108,176,.08);border:1px solid rgba(43,108,176,.2);border-radius:var(--radius);padding:14px 18px;margin-bottom:22px;display:flex;gap:12px;align-items:flex-start;font-size:13px;color:#93c5fd;line-height:1.6;}
.notice i{flex-shrink:0;margin-top:2px;}

.sector-tabs{display:flex;gap:4px;background:var(--mid);border:1px solid var(--border);border-radius:var(--radius);padding:4px;margin-bottom:20px;width:fit-content;flex-wrap:wrap;}
.sector-tab{padding:9px 20px;border:none;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;transition:all .22s;background:transparent;color:var(--muted);display:flex;align-items:center;gap:7px;text-decoration:none;}
.sector-tab.active{background:var(--emerald);color:#fff;}
.sector-tab:not(.active):hover{color:var(--light);background:rgba(255,255,255,.05);}
.tab-badge{background:rgba(255,255,255,.15);border-radius:20px;padding:1px 8px;font-size:11px;font-weight:700;}
.sector-tab.active .tab-badge{background:rgba(255,255,255,.25);}

.stats-row{display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap;}
.stat-card{background:var(--mid);border:1px solid var(--border);border-radius:var(--radius);padding:16px 22px;flex:1;min-width:130px;}
.stat-label{font-size:11px;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:6px;}
.stat-value{font-size:26px;font-weight:700;color:#fff;}
.stat-value.green{color:#4ade80;}
.stat-value.red{color:#f87171;}

.table-wrap{background:var(--mid);border:1px solid var(--border);border-radius:14px;overflow:hidden;}
table{width:100%;border-collapse:collapse;}
thead tr{background:var(--inner);}
thead th{padding:12px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);text-align:left;white-space:nowrap;}
tbody tr{border-bottom:1px solid var(--border);}
tbody tr:last-child{border-bottom:none;}
tbody tr:hover{background:rgba(5,150,105,.06);}
tbody td{padding:14px 16px;font-size:14px;vertical-align:middle;}

.user-cell{display:flex;align-items:center;gap:12px;}
.user-avatar{width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid var(--border);background:var(--inner);flex-shrink:0;}
.avatar-placeholder{width:44px;height:44px;border-radius:50%;background:var(--inner);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:17px;flex-shrink:0;}
.user-name{font-weight:600;color:#fff;font-size:14px;}

.desig-badges{display:flex;flex-wrap:wrap;gap:4px;}
.desig-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:rgba(43,108,176,.18);color:#93c5fd;}
.desig-badge.empty{background:rgba(255,255,255,.05);color:var(--muted);font-weight:500;}

.status-pill{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.status-active{background:rgba(34,197,94,.15);color:#4ade80;}
.status-inactive{background:rgba(240,84,84,.15);color:#f87171;}

.empty-state{text-align:center;padding:56px 20px;color:var(--muted);}
.empty-state i{font-size:40px;margin-bottom:14px;display:block;opacity:.25;}

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
        <a href="school_head_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="school_head_evaluations.php"><i class="fa-solid fa-clipboard-list"></i> Evaluations</a>
        <a href="school_head_personnel.php" class="active"><i class="fa-solid fa-users"></i> Personnel</a>
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
            <div class="page-title">Personnel</div>
            <div class="page-sub">Faculty &amp; Staff entered without a login account, for questionnaire purposes</div>
        </div>
    </div>

    <div class="notice">
        <i class="fa-solid fa-circle-info"></i>
        <span>This list mirrors the Personnel Registry — anyone added there by the superadmin appears here automatically. This view is read-only; adding, editing, or hiding entries is done from the Personnel Registry.</span>
    </div>

    <div class="sector-tabs">
        <?php foreach (['Faculty' => 'fa-chalkboard-user', 'Staff' => 'fa-briefcase'] as $sector => $icon): ?>
        <a class="sector-tab <?= $viewSector===$sector?'active':'' ?>" href="school_head_personnel.php?sector=<?= $sector ?>">
            <i class="fa-solid <?= $icon ?>"></i> <?= $sector ?> <span class="tab-badge"><?= $counts[$sector] ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="stats-row">
        <div class="stat-card"><div class="stat-label">Total <?= htmlspecialchars($viewSector) ?></div><div class="stat-value"><?= count($entries) ?></div></div>
        <div class="stat-card"><div class="stat-label">Visible in Questionnaire</div><div class="stat-value green"><?= $activeCount ?></div></div>
        <div class="stat-card"><div class="stat-label">Hidden</div><div class="stat-value red"><?= $inactiveCount ?></div></div>
    </div>

    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>#</th><th>Name</th><th style="min-width:220px;">Designation</th><th>Questionnaire</th><th>Added</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($entries)): ?>
        <tr><td colspan="5">
            <div class="empty-state">
                <i class="fa-solid fa-user-slash"></i>
                <p>No <?= strtolower($viewSector) ?> entries yet.<br>They'll show up here as soon as the superadmin adds them to the Personnel Registry.</p>
            </div>
        </td></tr>
        <?php else: foreach ($entries as $i => $u):
            $desigList = array_filter(array_map('trim', explode(',', $u['designation'] ?? '')), fn($t) => $t !== '');
        ?>
        <tr>
            <td style="color:var(--muted);font-size:13px;"><?= $i+1 ?></td>
            <td>
                <div class="user-cell">
                    <?php if (!empty($u['photo']) && file_exists($UPLOAD_DIR . $u['photo'])): ?>
                    <img class="user-avatar" src="<?= $UPLOAD_URL . htmlspecialchars($u['photo']) ?>" alt=""/>
                    <?php else: ?>
                    <div class="avatar-placeholder"><i class="fa-solid fa-user"></i></div>
                    <?php endif; ?>
                    <div class="user-name"><?= htmlspecialchars($u['full_name']) ?></div>
                </div>
            </td>
            <td>
                <div class="desig-badges">
                    <?php if (!empty($desigList)): foreach ($desigList as $d): ?>
                    <span class="desig-badge"><?= htmlspecialchars($d) ?></span>
                    <?php endforeach; else: ?>
                    <span class="desig-badge empty">Not set</span>
                    <?php endif; ?>
                </div>
            </td>
            <td>
                <?php if ($u['is_active']): ?>
                <span class="status-pill status-active"><i class="fa-solid fa-circle" style="font-size:7px"></i> Visible</span>
                <?php else: ?>
                <span class="status-pill status-inactive"><i class="fa-solid fa-eye-slash" style="font-size:9px"></i> Hidden</span>
                <?php endif; ?>
            </td>
            <td style="font-size:13px;color:var(--muted);"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</main>

</body>
</html>