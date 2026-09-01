<?php
// admin/settings.php
// Unified admin Settings center. Keeps configuration features inside one place.
require_once 'session_bootstrap.php';
require_once 'db.php';

$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
$isSuperAdmin = in_array($role, ['superadmin','super admin'], true);
$defaultTab = $_GET['tab'] ?? 'system';
$allowedTabs = ['system','archive','appearance'];
if (!in_array($defaultTab, $allowedTabs, true)) $defaultTab = 'system';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Settings — PBI Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="admin_ui_theme.css">
<link rel="stylesheet" href="admin_compact_ui.css">
<style>
*{box-sizing:border-box}html,body{margin:0;min-height:100%;font-family:Inter,system-ui,sans-serif;background:#f4f8ff;color:#10243f}
.settings-shell{padding:22px 24px 34px;max-width:1240px;margin:auto}
.settings-head{display:flex;justify-content:space-between;gap:16px;align-items:flex-end;margin-bottom:16px}
.settings-title{font-size:25px;font-weight:800;letter-spacing:-.02em;margin:0 0 4px}.settings-sub{margin:0;color:#66809c;font-size:13px}
.settings-badge{display:inline-flex;align-items:center;gap:7px;border:1px solid #cfe0f5;background:#fff;border-radius:999px;padding:8px 11px;font-size:11px;font-weight:700;color:#527093}
.layout{display:grid;grid-template-columns:210px 1fr;gap:15px;align-items:start}.side{background:#fff;border:1px solid #dce8f5;border-radius:13px;padding:7px;box-shadow:0 4px 18px rgba(32,73,120,.07)}
.side button{width:100%;border:0;background:transparent;text-align:left;padding:10px 10px;border-radius:9px;color:#5c7694;font:600 12px Inter;cursor:pointer;display:flex;gap:9px;align-items:center}.side button:hover{background:#f4f8ff;color:#255fd3}.side button.active{background:#eaf2ff;color:#1f61d7}.side i{width:18px;text-align:center}
.panel{min-width:0}.panel-card{background:#fff;border:1px solid #dce8f5;border-radius:13px;box-shadow:0 4px 18px rgba(32,73,120,.07);overflow:hidden}.panel-bar{padding:15px 17px;border-bottom:1px solid #e6eef7;display:flex;align-items:center;justify-content:space-between;gap:10px}.panel-bar h2{margin:0;font-size:16px}.panel-bar p{margin:3px 0 0;color:#6c83a0;font-size:11.5px}.panel-note{font-size:10.5px;color:#6c83a0}
.frame{width:100%;height:calc(100vh - 185px);min-height:620px;border:0;display:block;background:#fff}.appearance{padding:18px}.appearance-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.appearance-card{border:1px solid #dce8f5;border-radius:11px;padding:15px}.appearance-card h3{margin:0 0 4px;font-size:13px}.appearance-card p{margin:0 0 12px;color:#7187a2;font-size:11px;line-height:1.45}.choice-row{display:flex;gap:7px;flex-wrap:wrap}.choice{border:1px solid #d4e2f2;background:#f9fbff;padding:8px 10px;border-radius:8px;font:600 11px Inter;color:#5b7491;cursor:pointer}.choice.active{border-color:#2f6ee2;background:#eaf2ff;color:#1f61d7}.setting-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-top:1px solid #edf3f8}.setting-row:first-child{border-top:0}.switch{position:relative;width:36px;height:20px;display:inline-block}.switch input{display:none}.track{position:absolute;inset:0;background:#cbd8e7;border-radius:99px;cursor:pointer}.track:after{content:'';position:absolute;width:14px;height:14px;top:3px;left:3px;border-radius:50%;background:#fff;transition:.18s}.switch input:checked+.track{background:#2e6ddd}.switch input:checked+.track:after{left:19px}.toast{position:fixed;right:22px;bottom:22px;background:#10243f;color:#fff;padding:10px 13px;border-radius:9px;font-size:11px;opacity:0;transform:translateY(8px);pointer-events:none;transition:.2s}.toast.show{opacity:1;transform:none}
@media(max-width:820px){.layout{grid-template-columns:1fr}.side{display:flex;overflow:auto}.side button{width:auto;white-space:nowrap}.frame{height:calc(100vh - 255px);min-height:620px}.appearance-grid{grid-template-columns:1fr}.settings-head{align-items:flex-start;flex-direction:column}}
</style>
</head>
<body>
<div class="settings-shell">
  <div class="settings-head">
    <div><h1 class="settings-title">Settings</h1><p class="settings-sub">Manage system configuration, evaluation archiving, and interface preferences in one place.</p></div>
    <div class="settings-badge"><i class="fa-solid fa-shield-halved"></i> <?= $isSuperAdmin ? 'Super Admin access' : 'Administrator access' ?></div>
  </div>
  <div class="layout">
    <aside class="side" aria-label="Settings sections">
      <button class="tab-btn" data-tab="system"><i class="fa-solid fa-sliders"></i><span>System &amp; Period</span></button>
      <button class="tab-btn" data-tab="archive"><i class="fa-solid fa-box-archive"></i><span>System Archive</span></button>
      <button class="tab-btn" data-tab="appearance"><i class="fa-solid fa-palette"></i><span>Appearance</span></button>
    </aside>
    <main class="panel">
      <section class="panel-card section-panel" id="section-system">
        <div class="panel-bar"><div><h2>System &amp; Period Settings</h2><p>Configure the active academic structure and evaluation schedule.</p></div><span class="panel-note">Changes are managed by the system configuration module.</span></div>
        <iframe class="frame" title="System and Period Settings" src="system_settings.php"></iframe>
      </section>
      <section class="panel-card section-panel" id="section-archive" style="display:none">
        <div class="panel-bar"><div><h2>System Archive</h2><p>Safely close completed evaluation periods and preserve their history.</p></div><span class="panel-note">Archive access is permission-controlled.</span></div>
        <iframe class="frame" title="System Archive" src="system_archive.php"></iframe>
      </section>
      <section class="panel-card section-panel" id="section-appearance" style="display:none">
        <div class="panel-bar"><div><h2>Appearance</h2><p>Adjust the admin interface to your preferred working style.</p></div><span class="panel-note">Preferences are saved in this browser.</span></div>
        <div class="appearance">
          <div class="appearance-grid">
            <div class="appearance-card">
              <h3>Interface Density</h3><p>Use compact spacing to fit more records and controls on a 100% desktop view.</p>
              <div class="choice-row" id="densityChoices"><button class="choice" data-density="compact">Compact</button><button class="choice" data-density="comfortable">Comfortable</button></div>
            </div>
            <div class="appearance-card">
              <h3>Motion</h3><p>Reduce transitions and animations across the admin interface.</p>
              <div class="setting-row"><span style="font-size:11px;font-weight:600">Reduce motion</span><label class="switch"><input type="checkbox" id="reduceMotion"><span class="track"></span></label></div>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>
</div>
<div class="toast" id="toast">Preference saved</div>
<script>
const initialTab=<?= json_encode($defaultTab) ?>;
const buttons=[...document.querySelectorAll('.tab-btn')], sections=[...document.querySelectorAll('.section-panel')];
function setTab(tab){buttons.forEach(b=>b.classList.toggle('active',b.dataset.tab===tab));sections.forEach(s=>s.style.display=s.id==='section-'+tab?'block':'none');history.replaceState(null,'','settings.php?tab='+encodeURIComponent(tab));}
buttons.forEach(b=>b.addEventListener('click',()=>setTab(b.dataset.tab))); setTab(initialTab);
const toast=document.getElementById('toast'); let timer; function saved(){clearTimeout(timer);toast.classList.add('show');timer=setTimeout(()=>toast.classList.remove('show'),1400)}
const density=localStorage.getItem('pbiDensity')||'compact'; document.querySelectorAll('[data-density]').forEach(b=>b.classList.toggle('active',b.dataset.density===density));
document.querySelectorAll('[data-density]').forEach(b=>b.addEventListener('click',()=>{localStorage.setItem('pbiDensity',b.dataset.density);document.querySelectorAll('[data-density]').forEach(x=>x.classList.toggle('active',x===b));saved();}));
const rm=localStorage.getItem('pbiReduceMotion')==='1'; document.getElementById('reduceMotion').checked=rm; document.getElementById('reduceMotion').addEventListener('change',e=>{localStorage.setItem('pbiReduceMotion',e.target.checked?'1':'0');saved();});
</script>
</body></html>
