<?php
// admin/manage_permissions.php
session_start();
require_once 'db.php';

// Superadmin-only page. Admin (the vice) cannot open this at all.
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'superadmin') {
    header("Location: admin_dashboard.php");
    exit;
}

$toast = '';

// ── SAVE TOGGLES ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_permissions'])) {
    $features = ['user_management','questionnaire','personnel_registry','reports_analytics','eval_periods'];
    $stmt = $mysqli->prepare("UPDATE admin_permissions SET admin_can_edit = ? WHERE feature_key = ?");
    foreach ($features as $key) {
        $val = isset($_POST['perm_' . $key]) ? 1 : 0;
        $stmt->bind_param("is", $val, $key);
        $stmt->execute();
    }
    $stmt->close();
    $_SESSION['perm_toast'] = "Permissions updated successfully.";
    header("Location: manage_permissions.php");
    exit;
}

$toast = $_SESSION['perm_toast'] ?? ''; unset($_SESSION['perm_toast']);

// ── FETCH CURRENT STATE ──────────────────────────────────────────
$perms = [];
$res = $mysqli->query("SELECT feature_key, feature_label, admin_can_edit FROM admin_permissions WHERE feature_key <> 'documents' ORDER BY id");
if ($res) while ($row = $res->fetch_assoc()) $perms[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Manage Admin Permissions — PBI Super Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<style>
:root{--dark:#F8FAFC;--mid:#FFFFFF;--inner:#F1F5F9;--accent:#2B6CB0;--hover:#4C78B8;--teal:#0D9488;--light:#0F172A;--muted:#475569;--danger:#F05454;--border:rgba(15,23,42,.12);--radius:10px;--shadow:0 4px 20px rgba(15,23,42,.10);}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--dark);color:var(--light);min-height:100vh;padding:28px;}
.toast{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);color:#86efac;border-radius:8px;padding:12px 18px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
.page-header{margin-bottom:24px;}
.page-header h1{font-family:'Rajdhani',sans-serif;font-size:28px;font-weight:700;color:#fff;margin-bottom:4px;}
.page-header p{font-size:13px;color:var(--muted);}
.info-banner{background:rgba(43,108,176,.08);border:1px solid rgba(43,108,176,.2);border-radius:var(--radius);padding:14px 18px;margin-bottom:24px;display:flex;gap:12px;align-items:flex-start;font-size:13px;color:#93c5fd;line-height:1.6;}
.info-banner i{flex-shrink:0;margin-top:2px;}
.perm-list{background:var(--mid);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:24px;}
.perm-row{display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border);}
.perm-row:last-child{border-bottom:none;}
.perm-label{font-size:15px;font-weight:600;color:#fff;display:flex;align-items:center;gap:10px;}
.perm-label i{color:var(--accent);width:20px;text-align:center;}
.perm-state{font-size:12px;color:var(--muted);margin-top:3px;margin-left:30px;}
.switch{position:relative;display:inline-block;width:50px;height:26px;flex-shrink:0;}
.switch input{opacity:0;width:0;height:0;}
.slider{position:absolute;cursor:pointer;inset:0;background:rgba(255,255,255,.12);border-radius:26px;transition:.25s;}
.slider:before{position:absolute;content:"";height:20px;width:20px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.25s;}
input:checked + .slider{background:var(--teal);}
input:checked + .slider:before{transform:translateX(24px);}
.btn-save{background:var(--accent);color:#fff;border:none;padding:13px 28px;border-radius:var(--radius);font-size:14px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:background .2s;font-family:'DM Sans',sans-serif;}
.btn-save:hover{background:var(--hover);}
.back-link{display:inline-flex;align-items:center;gap:8px;color:var(--muted);text-decoration:none;font-size:13px;margin-bottom:20px;transition:color .2s;}
.back-link:hover{color:var(--light);}

/* Admin Module light design system — matches the dashboard */
:root{
  --page-bg:#FFFFFF; --card-bg:#FFFFFF; --card-border:#E2E8F0;
  --inner:#F4F7FB; --text-dark:#172033; --text-dim:#475569;
  --light:#172033; --muted:#475569; --dark:#FFFFFF; --mid:#FFFFFF;
  --border:#E2E8F0; --accent:#3B82F6; --blue:#3B82F6;
  --gold:#D97706; --gold-h:#F59E0B; --teal:#0D9488; --violet:#7C3AED;
  --danger:#DC2626; --success:#059669; --radius:12px;
  --card-shadow:0 2px 4px rgba(15,23,42,.05),0 6px 16px rgba(15,23,42,.06);
}
html{background:#fff;color-scheme:light;}
body{background:#fff !important;color:#172033 !important;}
a{color:inherit;}
.page-header h1,.page-title,.et-title,.section-title{color:#172033 !important;}
.page-header p,.page-sub,.et-sub,.et-updated,.muted,.hint{color:#475569 !important;}
input,select,textarea{background:#fff !important;color:#172033 !important;border-color:#CBD5E1 !important;}
button{font-family:inherit;}
.table-wrap,.content-panel,.create-panel,.period-card,.stat-card,.sector-card,.person-row,
.sum-card,.standing-panel,.eval-card,.eval-banner,.info-banner,.section,.shell .section,
.history-card,.gl-card,.amber-card,.green-card,.red-card{
  background:#fff !important;border-color:#E2E8F0 !important;box-shadow:0 2px 4px rgba(15,23,42,.04),0 6px 16px rgba(15,23,42,.05) !important;
}
.sector-tabs,.eval-switcher,.tabs,.level-tabs,.status-tabs{
  background:#fff !important;border-color:#E2E8F0 !important;box-shadow:0 2px 4px rgba(15,23,42,.04) !important;
}
.sector-tab,.eval-tab,.tab,.level-tab,.status-tab{color:#475569 !important;}
.sector-tab:hover,.eval-tab:hover,.tab:hover,.level-tab:hover,.status-tab:hover{color:#172033 !important;background:#F4F7FB !important;}
thead tr{background:#F8FAFC !important;}
tbody tr:hover{background:#F8FAFC !important;}
.btn-cancel,.btn-icon,.btn-back{background:#fff !important;color:#172033 !important;border-color:#CBD5E1 !important;}
.empty-state,.empty-cta{color:#475569 !important;}
::-webkit-scrollbar-track{background:#fff;}
::-webkit-scrollbar-thumb{background:#CBD5E1;border:2px solid #fff;}
body{padding:28px !important;}

/* ── SHARP LIGHT ADMIN UI ── */
html { background:#F8FAFC; }
body {
  color:#0F172A !important;
  background:#F8FAFC !important;
  -webkit-font-smoothing:antialiased;
  text-rendering:optimizeLegibility;
}
h1,h2,h3,h4,h5,h6 { color:#0F172A; letter-spacing:-.01em; }
p, .subtitle, .description, .helper, .muted, small { color:#475569; }
label, th { color:#334155; font-weight:600; }
td { color:#0F172A; }
input, select, textarea {
  color:#0F172A;
  background:#FFFFFF;
  border-color:#CBD5E1;
}
input::placeholder, textarea::placeholder { color:#94A3B8; }
.card, .panel, .section, .table-card, .content-card {
  border-color:#CBD5E1;
  box-shadow:0 4px 14px rgba(15,23,42,.07);
}
button, .btn { font-weight:700; }
a { color:inherit; }
</style>
</head>
<body>

<a href="admin_dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>

<?php if ($toast): ?>
<div class="toast"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($toast) ?></div>
<?php endif; ?>

<div class="page-header">
    <h1>Manage Admin Permissions</h1>
    <p>Control which features the second Admin account can edit. View access is always allowed everywhere.</p>
</div>

<div class="info-banner">
    <i class="fa-solid fa-circle-info"></i>
    <span>
        Turning a switch <strong>ON</strong> lets the Admin (vice) add, edit, and delete in that section.
        Turning it <strong>OFF</strong> keeps the section visible to them, but read-only — they can browse and see everything you do, just can't make changes.
        This only affects the <strong>Admin</strong> role; <strong>Super Admin</strong> always has full access everywhere.
    </span>
</div>

<form method="POST">
    <div class="perm-list">
        <?php
        $icons = [
            'user_management'    => 'fa-users',
            'questionnaire'      => 'fa-file-signature',
            'personnel_registry' => 'fa-id-card-clip',
            'reports_analytics'  => 'fa-chart-line',
            'eval_periods'       => 'fa-calendar-check',
        ];
        foreach ($perms as $p):
            $icon = $icons[$p['feature_key']] ?? 'fa-puzzle-piece';
        ?>
        <div class="perm-row">
            <div>
                <div class="perm-label">
                    <i class="fa-solid <?= $icon ?>"></i>
                    <?= htmlspecialchars($p['feature_label']) ?>
                </div>
                <div class="perm-state">
                    <?= $p['admin_can_edit'] ? 'Admin can view & edit' : 'Admin can view only' ?>
                </div>
            </div>
            <label class="switch">
                <input type="checkbox" name="perm_<?= htmlspecialchars($p['feature_key']) ?>"
                       <?= $p['admin_can_edit'] ? 'checked' : '' ?>/>
                <span class="slider"></span>
            </label>
        </div>
        <?php endforeach; ?>
    </div>

    <button type="submit" name="save_permissions" value="1" class="btn-save">
        <i class="fa-solid fa-floppy-disk"></i> Save Permissions
    </button>
</form>

</body>
</html>
