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

// ── AUTH GUARD ────────────────────────────────────────────────
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'executive_assistant') {
    header("Location: executive_login.php"); exit;
}

$user_id     = $_SESSION['user_id'];
$full_name   = $_SESSION['full_name']   ?? 'Executive Assistant';
$designation = $_SESSION['designation'] ?? 'Executive Assistant';

// ── FETCH PROFILE PHOTO ───────────────────────────────────────
$profile_photo = null;
$pq = $mysqli->prepare("SELECT photo FROM users WHERE id = ? LIMIT 1");
$pq->bind_param("i", $user_id); $pq->execute();
$pq->bind_result($photo_db); $pq->fetch(); $pq->close();
if (!empty($photo_db)) $profile_photo = '../image/' . $photo_db;

// ── LOAD PERMISSIONS FROM SYSTEM ADMIN ───────────────────────
// NOTE: this flag no longer gates whether the Executive Assistant can
// VIEW a feature — everyone with this role can view every feature's
// contents. It only controls whether the feature is flagged as
// "Enabled" (unlocked, full access) vs "View Only" (locked, browsing
// only) in the UI. The lock icon stays visible until the System Admin
// turns a feature on via admin/manage_permissions.php, which stores
// one row per feature (feature_key => admin_can_edit) in the
// admin_permissions table.
$perms = [
    'can_questionnaire' => false,
    'can_personnel'     => false,
    'can_documents'     => false,
    'can_analytics'     => false,
    'can_eval_periods'  => false,
];

// Map the feature_key values used in admin_permissions to the
// dashboard's internal perm keys above.
$feature_to_perm = [
    'questionnaire'      => 'can_questionnaire',
    'personnel_registry' => 'can_personnel',
    'documents'          => 'can_documents',
    'reports_analytics'  => 'can_analytics',
    'eval_periods'       => 'can_eval_periods',
];

$pres = $mysqli->query("SELECT feature_key, admin_can_edit FROM admin_permissions");
if ($pres) {
    while ($prow = $pres->fetch_assoc()) {
        if (isset($feature_to_perm[$prow['feature_key']])) {
            $perms[$feature_to_perm[$prow['feature_key']]] = (bool)$prow['admin_can_edit'];
        }
    }
}

$any_access = in_array(true, $perms, true);

// ── ACTIVE PAGE ───────────────────────────────────────────────
// Every feature page is viewable regardless of $perms — the
// admin_permissions flag no longer blocks navigation. Each embedded
// admin/*.php page is responsible for its own view-only rendering
// (see admin/permissions.php: admin_can_edit() + render_view_only_banner()),
// since the executive_assistant role always falls through to
// view-only there.
$page = $_GET['page'] ?? 'dashboard';

$toast = $_SESSION['toast'] ?? ''; unset($_SESSION['toast']);

$page_perm_map = [
    'questionnaire'  => 'can_questionnaire',
    'personnel'      => 'can_personnel',
    'documents'      => 'can_documents',
    'analytics'      => 'can_analytics',
    'eval_periods'   => 'can_eval_periods',
    'eval_tracker'   => 'can_analytics', // reuses same gate as evaluation_tracker.php itself
];

$page_titles = [
    'dashboard'      => 'Dashboard',
    'questionnaire'  => 'Questionnaire',
    'personnel'      => 'Personnel Registry',
    'documents'      => 'Documents',
    'analytics'      => 'Analytics & Reports',
    'eval_periods'   => 'Evaluation Periods',
    'eval_tracker'   => 'Evaluation Tracker',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Executive Assistant Dashboard — PBI</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<style>
:root{
    --dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;
    --violet:#7C3AED;--violet-h:#8B5CF6;
    --accent:#2B6CB0;--light:#E0E6F0;--muted:#A0B3C6;
    --danger:#F05454;--amber:#FBBF24;--border:rgba(255,255,255,0.08);
    --radius:10px;--shadow:0 4px 20px rgba(0,0,0,0.35);
    --sidebar-w:250px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--dark);color:var(--light);min-height:100vh;display:flex;}

/* SIDEBAR */
.sidebar{width:var(--sidebar-w);flex-shrink:0;background:var(--mid);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:40;transition:transform .3s;}
/* Profile header */
.sidebar-brand{padding:18px 16px;border-bottom:1px solid var(--border);}
.sb-profile{display:flex;align-items:center;gap:12px;}
.sb-photo{width:52px;height:52px;border-radius:50%;object-fit:cover;border:2.5px solid var(--violet);box-shadow:0 0 14px rgba(124,58,237,.45);flex-shrink:0;}
.sb-photo-ph{width:52px;height:52px;border-radius:50%;background:rgba(124,58,237,.15);border:2.5px solid var(--violet);display:flex;align-items:center;justify-content:center;color:var(--violet-h);font-size:22px;flex-shrink:0;}
.sb-info{overflow:hidden;}
.sb-name{font-family:'Rajdhani',sans-serif;font-size:15px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.2;letter-spacing:.5px;}
.sb-desig{font-size:11px;color:var(--violet-h);font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;}
.sidebar-nav{flex:1;padding:16px 10px;overflow-y:auto;}
.nav-section-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;color:var(--muted);padding:0 8px;margin-bottom:6px;margin-top:16px;}
.nav-section-label:first-child{margin-top:0;}
.nav-link{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;color:var(--muted);text-decoration:none;font-size:14px;font-weight:600;transition:all .2s;margin-bottom:2px;}
.nav-link:hover{background:rgba(255,255,255,.05);color:var(--light);}
.nav-link.active{background:rgba(124,58,237,.18);color:var(--violet-h);}
.nav-link.active i{color:var(--violet);}
.nav-link i{font-size:15px;width:18px;text-align:center;}
.lock-icon{margin-left:auto;font-size:10px;color:var(--amber);opacity:.8;}
.sidebar-footer{padding:14px 16px;border-top:1px solid var(--border);}
.btn-logout{display:flex;align-items:center;gap:7px;width:100%;padding:9px 12px;border:1px solid rgba(240,84,84,.3);background:rgba(240,84,84,.08);border-radius:8px;color:#f87171;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s;font-family:'DM Sans',sans-serif;}
.btn-logout:hover{background:rgba(240,84,84,.18);}

/* TOP NAV */
.top-nav{position:fixed;top:0;left:var(--sidebar-w);right:0;height:60px;z-index:30;background:var(--mid);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 28px;box-shadow:var(--shadow);}
.nav-page-title{font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:#fff;}
.hamburger{display:none;background:none;border:none;color:var(--light);font-size:20px;cursor:pointer;}

/* MAIN */
.main{margin-left:var(--sidebar-w);margin-top:60px;padding:28px;flex:1;min-height:calc(100vh - 60px);}

/* TOAST */
.toast{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);color:#86efac;padding:12px 18px;border-radius:8px;font-size:13px;margin-bottom:22px;display:flex;align-items:center;gap:8px;animation:fadeIn .3s ease;}
@keyframes fadeIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}

/* VIEW-ONLY BANNER (shown on the dashboard shell itself, for locked feature pages) */
.view-only-banner{background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.3);border-radius:10px;padding:12px 18px;margin-bottom:20px;display:flex;gap:12px;align-items:center;font-size:13px;color:var(--amber);}
.view-only-banner i{flex-shrink:0;}

/* DASHBOARD WELCOME */
.welcome-bar{background:linear-gradient(135deg,var(--mid) 0%,rgba(124,58,237,.15) 100%);border:1px solid rgba(124,58,237,.2);border-radius:14px;padding:24px 28px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;}
.welcome-bar h2{font-family:'Rajdhani',sans-serif;font-size:24px;font-weight:700;color:#fff;margin-bottom:4px;}
.welcome-bar p{font-size:13px;color:var(--muted);}

/* FEATURE CARDS */
.features-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:16px;}
.feature-card{background:var(--mid);border:2px solid var(--border);border-radius:14px;padding:22px 18px;display:flex;flex-direction:column;align-items:flex-start;gap:12px;text-decoration:none;color:inherit;transition:all .22s;position:relative;overflow:hidden;}
.feature-card:hover{border-color:var(--violet);transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.3);}
.feature-card.locked::after{content:'VIEW ONLY';position:absolute;top:12px;right:12px;font-size:9px;font-weight:700;letter-spacing:1px;background:rgba(251,191,36,.12);color:var(--amber);padding:2px 8px;border-radius:10px;}
.feature-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;}
.feature-title{font-family:'Rajdhani',sans-serif;font-size:17px;font-weight:700;color:#fff;}
.feature-desc{font-size:12px;color:var(--muted);line-height:1.5;}
.feature-arrow{margin-top:auto;font-size:12px;color:var(--violet-h);font-weight:600;display:flex;align-items:center;gap:5px;}
.feature-card.locked .feature-arrow{color:var(--amber);}

/* IFRAME PAGES */
.iframe-box{width:100%;height:calc(100vh - 120px);border:none;border-radius:var(--radius);}

/* RESPONSIVE */
@media(max-width:900px){.sidebar{transform:translateX(-100%);}.sidebar.open{transform:translateX(0);}.top-nav{left:0;}.main{margin-left:0;}.hamburger{display:block;}}
@media(max-width:600px){.main{padding:16px;}.features-grid{grid-template-columns:1fr 1fr;}}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sb-profile">
            <?php if ($profile_photo): ?>
            <img class="sb-photo" src="<?= htmlspecialchars($profile_photo) ?>" alt="<?= htmlspecialchars($full_name) ?>"/>
            <?php else: ?>
            <div class="sb-photo-ph"><i class="fa-solid fa-user-tie"></i></div>
            <?php endif; ?>
            <div class="sb-info">
                <div class="sb-name"><?= htmlspecialchars($full_name) ?></div>
                <div class="sb-desig"><?= htmlspecialchars($designation) ?></div>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a href="executive_dashboard.php?page=dashboard"
           class="nav-link <?= $page==='dashboard'?'active':'' ?>">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>

        <?php
$nav_items = [
    'questionnaire' => ['icon'=>'fa-file-signature', 'label'=>'Questionnaire',     'perm'=>'can_questionnaire'],
    'personnel'     => ['icon'=>'fa-id-card-clip',   'label'=>'Personnel Registry','perm'=>'can_personnel'],
    'documents'     => ['icon'=>'fa-folder-open',    'label'=>'Documents',         'perm'=>'can_documents'],
    'analytics'     => ['icon'=>'fa-chart-line',     'label'=>'Analytics',         'perm'=>'can_analytics'],
    'eval_periods'  => ['icon'=>'fa-calendar-check', 'label'=>'Eval Periods',      'perm'=>'can_eval_periods'],
    'eval_tracker'  => ['icon'=>'fa-user-check',     'label'=>'Evaluation Tracker','perm'=>'can_analytics'],
];
$sections = [
    'Evaluation' => ['questionnaire','personnel','eval_periods','eval_tracker'],
    'Reports'    => ['analytics','documents'],
];     // All nav links are always clickable — every feature is viewable.
        // The lock icon is kept purely as a status indicator: it shows when
        // the System Admin has not yet enabled full access for that feature.
        foreach ($sections as $sec_label => $keys):
        ?>
        <div class="nav-section-label"><?= $sec_label ?></div>
        <?php foreach ($keys as $key):
            $item     = $nav_items[$key];
            $unlocked = $perms[$item['perm']];
            $isActive = $page === $key;
        ?>
        <a href="executive_dashboard.php?page=<?= $key ?>"
           class="nav-link <?= $isActive?'active':'' ?>">
            <i class="fa-solid <?= $item['icon'] ?>"></i> <?= $item['label'] ?>
            <?php if (!$unlocked): ?><i class="fa-solid fa-lock lock-icon" title="View only — not yet enabled by System Admin"></i><?php endif; ?>
        </a>
        <?php endforeach; endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="../logout.php" class="btn-logout">
            <i class="fa-solid fa-power-off"></i> Log Out
        </a>
    </div>
</aside>

<!-- TOP NAV -->
<nav class="top-nav">
    <div style="display:flex;align-items:center;gap:14px;">
        <button class="hamburger" onclick="document.getElementById('sidebar').classList.toggle('open')">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="nav-page-title"><?= $page_titles[$page] ?? 'Dashboard' ?></div>
    </div>
</nav>

<!-- MAIN -->
<main class="main">

<?php if ($toast): ?>
<div class="toast"><i class="fa-solid fa-circle-check"></i><?= htmlspecialchars($toast) ?></div>
<?php endif; ?>

<!-- ══ DASHBOARD ════════════════════════════════════════════ -->
<?php if ($page === 'dashboard'): ?>

<div class="welcome-bar">
    <div>
        <h2>Welcome, <?= htmlspecialchars(explode(',', $full_name)[0] ?? $full_name) ?>!</h2>
        <p>Executive Assistant &nbsp;·&nbsp; Pandan Bay Institute</p>
    </div>
</div>

<!-- FEATURE CARDS — every feature is viewable; locked ones are marked "View Only" -->
<div style="font-size:13px;color:var(--muted);margin-bottom:20px;">
    <?php if ($any_access): ?>
    You can view all features below. Ones marked <strong style="color:var(--amber)">View Only</strong> are still awaiting full access approval from the <strong style="color:#fff">System Admin</strong>.
    <?php else: ?>
    You can view all features below, but everything is currently <strong style="color:var(--amber)">View Only</strong> — contact the <strong style="color:#fff">System Admin</strong> to request full access.
    <?php endif; ?>
</div>
<div class="features-grid">
    <?php
$feature_details = [
    'questionnaire' => ['icon'=>'fa-file-signature', 'bg'=>'#059669','title'=>'Questionnaire',     'desc'=>'Manage evaluation forms and questions.', 'perm'=>'can_questionnaire'],
    'personnel'     => ['icon'=>'fa-id-card-clip',   'bg'=>'#D97706','title'=>'Personnel Registry','desc'=>'Add and manage non-login personnel entries.', 'perm'=>'can_personnel'],
    'documents'     => ['icon'=>'fa-folder-open',    'bg'=>'#0D9488','title'=>'Documents',         'desc'=>'Upload and manage institutional documents.', 'perm'=>'can_documents'],
    'analytics'     => ['icon'=>'fa-chart-line',     'bg'=>'#7C3AED','title'=>'Analytics',         'desc'=>'View evaluation reports and performance data.', 'perm'=>'can_analytics'],
    'eval_periods'  => ['icon'=>'fa-calendar-check', 'bg'=>'#BE185D','title'=>'Eval Periods',      'desc'=>'Open and close evaluation periods.', 'perm'=>'can_eval_periods'],
    'eval_tracker'  => ['icon'=>'fa-user-check',     'bg'=>'#2B6CB0','title'=>'Evaluation Tracker','desc'=>'Track student and peer evaluation completion progress.', 'perm'=>'can_analytics'],
];
    foreach ($feature_details as $key => $fd):
        $unlocked = $perms[$fd['perm']];
    ?>
    <a class="feature-card <?= $unlocked ? '' : 'locked' ?>"
       href="executive_dashboard.php?page=<?= $key ?>">
        <div class="feature-icon" style="background:<?= $fd['bg'] ?>">
            <i class="fa-solid <?= $fd['icon'] ?>"></i>
        </div>
        <div class="feature-title"><?= $fd['title'] ?></div>
        <div class="feature-desc"><?= $fd['desc'] ?></div>
        <div class="feature-arrow">
            <?php if ($unlocked): ?>
            <i class="fa-solid fa-arrow-right"></i> Open
            <?php else: ?>
            <i class="fa-solid fa-lock"></i> View Only
            <?php endif; ?>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<!-- ══ FEATURE IFRAMES ══════════════════════════════════════ -->
<!-- Every feature page is now reachable for viewing regardless of $perms.
     Full edit access inside each embedded page is controlled separately
     by admin/permissions.php (admin_can_edit) — the executive_assistant
     role always renders those pages in their built-in view-only mode. -->
<?php elseif ($page === 'user_mgmt'): ?>
<?php elseif ($page === 'questionnaire'): ?>
<?php if (!$perms['can_questionnaire']): ?><div class="view-only-banner"><i class="fa-solid fa-lock"></i><span><strong>View-only access.</strong> You can browse Questionnaire, but full access hasn't been enabled by the System Admin yet.</span></div><?php endif; ?>
<iframe src="../admin/questionnaire.php" class="iframe-box"></iframe>

<?php elseif ($page === 'personnel'): ?>
<?php if (!$perms['can_personnel']): ?><div class="view-only-banner"><i class="fa-solid fa-lock"></i><span><strong>View-only access.</strong> You can browse Personnel Registry, but full access hasn't been enabled by the System Admin yet.</span></div><?php endif; ?>
<iframe src="../admin/personnel_registry.php" class="iframe-box"></iframe>

<?php elseif ($page === 'documents'): ?>
<?php if (!$perms['can_documents']): ?><div class="view-only-banner"><i class="fa-solid fa-lock"></i><span><strong>View-only access.</strong> You can browse Documents, but full access hasn't been enabled by the System Admin yet.</span></div><?php endif; ?>
<iframe src="../admin/documents.php" class="iframe-box"></iframe>

<?php elseif ($page === 'analytics'): ?>
<?php if (!$perms['can_analytics']): ?><div class="view-only-banner"><i class="fa-solid fa-lock"></i><span><strong>View-only access.</strong> You can browse Analytics, but full access hasn't been enabled by the System Admin yet.</span></div><?php endif; ?>
<iframe src="../admin/admin_analytics.php" class="iframe-box"></iframe>

<?php elseif ($page === 'eval_periods'): ?>
<?php if (!$perms['can_eval_periods']): ?><div class="view-only-banner"><i class="fa-solid fa-lock"></i><span><strong>View-only access.</strong> You can browse Eval Periods, but full access hasn't been enabled by the System Admin yet.</span></div><?php endif; ?>
<iframe src="../admin/eval_periods.php" class="iframe-box"></iframe>

<?php else: ?>
<div class="no-access-card" style="background:var(--mid);border:1px solid var(--border);border-radius:16px;padding:48px 32px;text-align:center;max-width:500px;margin:40px auto;">
    <div style="font-size:52px;color:var(--violet);opacity:.4;margin-bottom:20px;"><i class="fa-solid fa-ban"></i></div>
    <div style="font-family:'Rajdhani',sans-serif;font-size:24px;font-weight:700;color:#fff;margin-bottom:10px;">Page Not Found</div>
    <div style="font-size:14px;color:var(--muted);line-height:1.7;">That page doesn't exist. Use the sidebar to navigate.</div>
</div>
<?php endif; ?>

</main>

<script>
document.addEventListener('click', function(e) {
    const sb = document.getElementById('sidebar');
    if (sb.classList.contains('open') && !sb.contains(e.target) && !e.target.closest('.hamburger')) {
        sb.classList.remove('open');
    }
});
</script>
<?php $mysqli->close(); ?>
</body>
</html>