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
<script>
// Apply saved appearance before first paint to avoid a flash of default styling.
(function(){
  try{
    var d=localStorage.getItem('pbiDensity')||'compact';
    var m=localStorage.getItem('pbiReduceMotion')==='1';
    var a=localStorage.getItem('pbiAccent')||'#2f6ee2';
    var t=localStorage.getItem('pbiTheme')||'light';
    var resolved = t==='system' ? (window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light') : t;
    var html=document.documentElement;
    html.classList.toggle('density-comfortable', d==='comfortable');
    html.classList.toggle('reduce-motion', m);
    html.setAttribute('data-theme', resolved);
    html.style.setProperty('--accent', a);
  }catch(e){}
})();
</script>
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
:root{--accent:#2f6ee2;--accent-soft:#eaf2ff;--bg:#f4f8ff;--text:#10243f;--muted:#66809c;--panel-bg:#fff;--panel-border:#dce8f5;--input-bg:#f9fbff;--input-border:#d4e2f2}
html[data-theme="dark"]{--bg:#0b1626;--text:#e7eef8;--muted:#8ba0bd;--panel-bg:#121f33;--panel-border:#22344d;--input-bg:#16243a;--input-border:#25374f;--accent-soft:color-mix(in srgb, var(--accent) 22%, #121f33)}
*{box-sizing:border-box}html,body{margin:0;min-height:100%;font-family:Inter,system-ui,sans-serif;background:var(--bg);color:var(--text)}
.settings-shell{padding:22px 24px 34px;max-width:1240px;margin:auto}
.settings-head{display:flex;justify-content:space-between;gap:16px;align-items:flex-end;margin-bottom:16px}
.settings-title{font-size:25px;font-weight:800;letter-spacing:-.02em;margin:0 0 4px}.settings-sub{margin:0;color:var(--muted);font-size:13px}
.settings-badge{display:inline-flex;align-items:center;gap:7px;border:1px solid var(--panel-border);background:var(--panel-bg);border-radius:999px;padding:8px 11px;font-size:11px;font-weight:700;color:var(--muted)}
.layout{display:block}
.side{background:var(--panel-bg);border:1px solid var(--panel-border);border-radius:13px;padding:4px;box-shadow:0 4px 18px rgba(32,73,120,.07);display:flex;gap:3px;margin-bottom:15px;overflow-x:auto;-webkit-overflow-scrolling:touch}
.side button{flex:0 0 auto;border:0;background:transparent;text-align:left;padding:8px 12px;border-radius:8px;color:var(--muted);font:600 12px Inter;cursor:pointer;display:flex;gap:8px;align-items:center;white-space:nowrap}.side button:hover{background:var(--bg);color:var(--accent)}.side button.active{background:var(--accent-soft);color:var(--accent)}.side i{width:16px;text-align:center}
.side-actions{margin-left:auto;display:flex;align-items:center;gap:10px}
.side button.side-save-btn{flex:0 0 auto;border:0;border-radius:8px;padding:8px 14px;font:700 12px Inter!important;cursor:pointer;background:#3B6FE0!important;color:#FFFFFF!important;white-space:nowrap;text-align:center}
.side button.side-save-btn:hover{background:#4C7CEA!important;color:#FFFFFF!important}
.unsaved-banner{position:fixed;top:0;left:0;right:0;z-index:9999;display:flex;align-items:center;justify-content:center;gap:14px;padding:12px 20px;background:#FFF3D6;border-bottom:2px solid #F0B429;color:#7A5A0A;font:700 12.5px Inter;box-shadow:0 6px 16px rgba(122,90,10,.15);transform:translateY(-100%);opacity:0;transition:transform .22s ease,opacity .22s ease;pointer-events:none}
.unsaved-banner.show{transform:translateY(0);opacity:1;pointer-events:auto}
.unsaved-banner i{color:#D97706}
.unsaved-banner button{border:0;border-radius:8px;padding:6px 13px;font:700 11.5px Inter;cursor:pointer;background:#3B6FE0;color:#fff}
.unsaved-banner button:hover{background:#4C7CEA}
.panel{min-width:0}.panel-card{background:var(--panel-bg);border:1px solid var(--panel-border);border-radius:13px;box-shadow:0 4px 18px rgba(32,73,120,.07);overflow:hidden}.section-panel{display:block}
.panel-bar{padding:15px 17px;border-bottom:1px solid var(--panel-border);display:flex;align-items:center;justify-content:space-between;gap:10px}.panel-bar h2{margin:0;font-size:16px}.panel-bar p{margin:3px 0 0;color:var(--muted);font-size:11.5px}.panel-note{font-size:10.5px;color:var(--muted)}
.frame{width:100%;height:min(760px,calc(100vh - 205px));min-height:560px;border:0;display:block;background:var(--panel-bg);overflow:auto;overscroll-behavior:contain;}.appearance{padding:18px}.appearance-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.appearance-card{border:1px solid var(--panel-border);border-radius:11px;padding:15px}.appearance-card h3{margin:0 0 4px;font-size:13px}.appearance-card p{margin:0 0 12px;color:var(--muted);font-size:11px;line-height:1.45}.choice-row{display:flex;gap:7px;flex-wrap:wrap}.choice{border:1px solid var(--input-border);background:var(--input-bg);padding:8px 10px;border-radius:8px;font:600 11px Inter;color:var(--muted);cursor:pointer}.choice.active{border-color:var(--accent);background:var(--accent-soft);color:var(--accent)}.setting-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-top:1px solid var(--panel-border)}.setting-row:first-child{border-top:0}.switch{position:relative;width:36px;height:20px;display:inline-block}.switch input{display:none}.track{position:absolute;inset:0;background:#cbd8e7;border-radius:99px;cursor:pointer}.track:after{content:'';position:absolute;width:14px;height:14px;top:3px;left:3px;border-radius:50%;background:#fff;transition:.18s}.switch input:checked+.track{background:var(--accent)}.switch input:checked+.track:after{left:19px}.toast{position:fixed;right:22px;bottom:22px;background:#10243f;color:#fff;padding:10px 13px;border-radius:9px;font-size:11px;opacity:0;transform:translateY(8px);pointer-events:none;transition:.2s}.toast.show{opacity:1;transform:none}
.swatch-row{display:flex;gap:9px;flex-wrap:wrap}
.swatch{width:26px;height:26px;border-radius:50%;border:0;cursor:pointer;background:var(--sw);box-shadow:0 0 0 1px var(--input-border);position:relative;padding:0}
.swatch.active{box-shadow:0 0 0 2px var(--panel-bg),0 0 0 4px var(--sw)}
.swatch.active::after{content:'\2713';position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700;text-shadow:0 1px 1px rgba(0,0,0,.3)}
.reset-link{border:0;background:none;color:var(--muted);font:600 11px Inter;cursor:pointer;text-decoration:underline;white-space:nowrap;padding:0}
.reset-link:hover{color:var(--accent)}
html.reduce-motion *,html.reduce-motion *::before,html.reduce-motion *::after{transition-duration:0s !important;animation-duration:0s !important;scroll-behavior:auto !important}
html.density-comfortable .panel-bar{padding:20px 22px}
html.density-comfortable .appearance{padding:24px}
html.density-comfortable .appearance-grid{gap:16px}
html.density-comfortable .appearance-card{padding:18px}
html.density-comfortable .setting-row{padding:14px 0}
html.density-comfortable .side button{padding:10px 14px}
html.density-comfortable .choice{padding:10px 14px}
@media(max-width:820px){.appearance-grid{grid-template-columns:1fr}.settings-head{align-items:flex-start;flex-direction:column}}
</style>
<style id="pbi-feature-scrollbar">

/* PBI FEATURE SCROLLBAR — consistent with the compact page scrollbar */
html, body {
  scrollbar-width: thin !important;
  scrollbar-color: #888 transparent !important;
}
html::-webkit-scrollbar, body::-webkit-scrollbar,
.feature-compact ::-webkit-scrollbar { width: 10px !important; height: 10px !important; }
html::-webkit-scrollbar-track, body::-webkit-scrollbar-track,
.feature-compact ::-webkit-scrollbar-track { background: transparent !important; }
html::-webkit-scrollbar-thumb, body::-webkit-scrollbar-thumb,
.feature-compact ::-webkit-scrollbar-thumb {
  background: #888 !important; border-radius: 999px !important;
  border: 2px solid transparent !important; background-clip: padding-box !important;
}
html::-webkit-scrollbar-thumb:hover, body::-webkit-scrollbar-thumb:hover,
.feature-compact ::-webkit-scrollbar-thumb:hover { background: #777 !important; background-clip: padding-box !important; }
html::-webkit-scrollbar-button, body::-webkit-scrollbar-button,
.feature-compact ::-webkit-scrollbar-button { display: block !important; width: 10px !important; height: 10px !important; background-color: transparent !important; }

</style>
</head>
<body>
<div class="unsaved-banner" id="unsavedBanner">
  <i class="fa-solid fa-triangle-exclamation"></i>
  <span>You have unsaved changes in System &amp; Period Settings.</span>
  <button id="bannerSaveBtn" type="button">Save now</button>
</div>
<div class="settings-shell">
  <div class="settings-head">
    <div><h1 class="settings-title">Settings</h1><p class="settings-sub"></p></div>
  </div>
  <div class="layout">
    <aside class="side" aria-label="Settings sections">
      <button class="tab-btn" data-tab="system"><i class="fa-solid fa-sliders" style="color:#3B82F6"></i><span>System &amp; Period</span></button>
      <button class="tab-btn" data-tab="archive"><i class="fa-solid fa-box-archive" style="color:#F59E0B"></i><span>System Archive</span></button>
      <button class="tab-btn" data-tab="appearance"><i class="fa-solid fa-palette" style="color:#EC4899"></i><span>Appearance</span></button>
      <div class="side-actions" id="sideActions" style="display:none">
        <button class="side-save-btn" id="sideSaveBtn" type="button">Save System Settings</button>
      </div>
    </aside>
    <main class="panel">
      <section class="panel-card section-panel" id="section-system">
        <div class="panel-bar"><div><h2>System &amp; Period Settings</h2><p>Configure the active academic structure and evaluation schedule.</p></div><span class="panel-note">Edit access is permission-controlled.</span></div>
        <iframe class="frame" title="System and Period Settings" src="system_settings.php" scrolling="yes" frameborder="0"></iframe>
      </section>
      <section class="panel-card section-panel" id="section-archive" style="display:none">
        <div class="panel-bar"><div><h2>System Archive</h2><p>Safely close completed evaluation periods and preserve their history.</p></div><span class="panel-note">Archive access is permission-controlled.</span></div>
        <iframe class="frame" title="System Archive" src="system_archive.php" scrolling="yes" frameborder="0"></iframe>
      </section>
      <section class="panel-card section-panel" id="section-appearance" style="display:none">
        <div class="panel-bar"><div><h2>Appearance</h2><p>Adjust the admin interface to your preferred working style.</p></div><div style="display:flex;align-items:center;gap:12px"><span class="panel-note">Preferences are saved in this browser.</span><button class="reset-link" id="resetAppearance" type="button">Reset to defaults</button></div></div>
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
            <div class="appearance-card">
              <h3>Accent Color</h3><p>Sets the highlight color used for active tabs, buttons, and toggles.</p>
              <div class="swatch-row" id="accentChoices">
                <button class="swatch" data-accent="#2f6ee2" style="--sw:#2f6ee2" title="Blue" aria-label="Blue"></button>
                <button class="swatch" data-accent="#1f9d63" style="--sw:#1f9d63" title="Green" aria-label="Green"></button>
                <button class="swatch" data-accent="#8b5cf6" style="--sw:#8b5cf6" title="Purple" aria-label="Purple"></button>
                <button class="swatch" data-accent="#e2792f" style="--sw:#e2792f" title="Amber" aria-label="Amber"></button>
                <button class="swatch" data-accent="#e23f6c" style="--sw:#e23f6c" title="Rose" aria-label="Rose"></button>
              </div>
            </div>
            <div class="appearance-card">
              <h3>Theme</h3><p>Switch between light and dark, or follow your device setting.</p>
              <div class="choice-row" id="themeChoices"><button class="choice" data-theme="light">Light</button><button class="choice" data-theme="dark">Dark</button><button class="choice" data-theme="system">System</button></div>
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
function setTab(tab){buttons.forEach(b=>b.classList.toggle('active',b.dataset.tab===tab));sections.forEach(s=>s.style.display=s.id==='section-'+tab?'block':'none');history.replaceState(null,'','settings.php?tab='+encodeURIComponent(tab));const activeFrame=document.querySelector('#section-'+tab+' iframe.frame');if(activeFrame)prepareFrame(activeFrame);const sideActions=document.getElementById('sideActions');if(sideActions)sideActions.style.display=(tab==='system')?'flex':'none';}
// The System & Period iframe posts {type:'system-settings-dirty', dirty:bool}
// whenever its unsaved-changes state changes. We only ever use this to HIDE
// the banner once a save actually goes through (from either Save button, or
// the form's own bottom Save action) -- showing it is handled separately,
// only at the moment the user actually navigates away with changes pending
// (see the tab click handler below), not the instant they start typing.
window.addEventListener('message', (e) => {
  if (!e.data || e.data.type !== 'system-settings-dirty') return;
  if (e.data.dirty) return;
  const banner = document.getElementById('unsavedBanner');
  if (banner) banner.classList.remove('show');
});
function triggerSystemSettingsSave(){
  const frame=document.querySelector('#section-system iframe.frame');
  if(frame&&frame.contentWindow&&typeof frame.contentWindow.saveSystemSettings==='function'){
    frame.contentWindow.saveSystemSettings();
  }
}
document.getElementById('sideSaveBtn').addEventListener('click', triggerSystemSettingsSave);
document.getElementById('bannerSaveBtn').addEventListener('click', triggerSystemSettingsSave);
function prepareFrame(iframe){
  // Keep a fixed viewport so long Settings forms scroll INSIDE the feature frame.
  // Do not redirect wheel events to window: Settings is itself commonly embedded
  // inside the dashboard content area, so window.scrollBy() can target the wrong scroller.
  iframe.style.height='min(760px, calc(100vh - 205px))';
}
document.querySelectorAll('iframe.frame').forEach(f=>{
  f.addEventListener('load',()=>prepareFrame(f));
  prepareFrame(f);
});
buttons.forEach(b=>b.addEventListener('click',()=>{
  const current=buttons.find(x=>x.classList.contains('active'));
  const leavingSystemTab = current && current.dataset.tab==='system' && b.dataset.tab!=='system';
  if(leavingSystemTab){
    const frame=document.querySelector('#section-system iframe.frame');
    const dirty = frame && frame.contentWindow && typeof frame.contentWindow.isSystemSettingsDirty==='function' && frame.contentWindow.isSystemSettingsDirty();
    if(dirty){
      if(!confirm('You have unsaved changes in System & Period Settings. Are you sure you want to leave without saving?')){
        return;
      }
      // They chose to leave anyway -- surface the reminder banner now that
      // the change has actually been left behind unsaved.
      const banner=document.getElementById('unsavedBanner');
      if(banner) banner.classList.add('show');
    }
  }
  setTab(b.dataset.tab);
}));
setTab(initialTab);
const toast=document.getElementById('toast'); let timer; function saved(msg){clearTimeout(timer);toast.textContent=msg||'Preference saved';toast.classList.add('show');timer=setTimeout(()=>toast.classList.remove('show'),1400)}

// ---- Appearance ----
const APPEARANCE_DEFAULTS={density:'compact',motion:false,accent:'#2f6ee2',theme:'light'};
function readAppearance(){
  return {
    density: localStorage.getItem('pbiDensity')||APPEARANCE_DEFAULTS.density,
    motion: localStorage.getItem('pbiReduceMotion')==='1',
    accent: localStorage.getItem('pbiAccent')||APPEARANCE_DEFAULTS.accent,
    theme: localStorage.getItem('pbiTheme')||APPEARANCE_DEFAULTS.theme,
  };
}
function resolveTheme(pref){
  return pref==='system' ? (window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light') : pref;
}
function applyAppearance(){
  const a=readAppearance();
  const html=document.documentElement;
  html.classList.toggle('density-comfortable', a.density==='comfortable');
  html.classList.toggle('reduce-motion', a.motion);
  html.setAttribute('data-theme', resolveTheme(a.theme));
  html.style.setProperty('--accent', a.accent);
  document.querySelectorAll('#densityChoices [data-density]').forEach(b=>b.classList.toggle('active', b.dataset.density===a.density));
  document.getElementById('reduceMotion').checked=a.motion;
  document.querySelectorAll('#accentChoices [data-accent]').forEach(b=>b.classList.toggle('active', b.dataset.accent.toLowerCase()===a.accent.toLowerCase()));
  document.querySelectorAll('#themeChoices [data-theme]').forEach(b=>b.classList.toggle('active', b.dataset.theme===a.theme));
}
applyAppearance();

document.querySelectorAll('#densityChoices [data-density]').forEach(b=>b.addEventListener('click',()=>{localStorage.setItem('pbiDensity',b.dataset.density);applyAppearance();saved('Density updated');}));
document.getElementById('reduceMotion').addEventListener('change',e=>{localStorage.setItem('pbiReduceMotion',e.target.checked?'1':'0');applyAppearance();saved('Motion preference updated');});
document.querySelectorAll('#accentChoices [data-accent]').forEach(b=>b.addEventListener('click',()=>{localStorage.setItem('pbiAccent',b.dataset.accent);applyAppearance();saved('Accent color updated');}));
document.querySelectorAll('#themeChoices [data-theme]').forEach(b=>b.addEventListener('click',()=>{localStorage.setItem('pbiTheme',b.dataset.theme);applyAppearance();saved('Theme updated');}));

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change',()=>{ if(readAppearance().theme==='system') applyAppearance(); });
window.addEventListener('storage', e=>{ if(['pbiDensity','pbiReduceMotion','pbiAccent','pbiTheme'].includes(e.key)) applyAppearance(); });

document.getElementById('resetAppearance').addEventListener('click',()=>{
  ['pbiDensity','pbiReduceMotion','pbiAccent','pbiTheme'].forEach(k=>localStorage.removeItem(k));
  applyAppearance();
  saved('Appearance reset to defaults');
});
</script>
</body></html>
