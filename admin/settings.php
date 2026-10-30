<?php
// admin/settings.php
// Unified admin Settings center. Keeps configuration features inside one place.
require_once 'session_bootstrap.php';
require_once 'db.php';

$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
$isSuperAdmin = in_array($role, ['superadmin','super admin'], true);
$defaultTab = $_GET['tab'] ?? 'system';
$allowedTabs = ['system','archive','profile','appearance'];
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
.toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:14px}
.toolbar .side{margin-bottom:0;max-width:100%}
.side-actions{margin-left:auto;display:flex;align-items:center;gap:12px}
.dirty-hint{display:inline-flex;align-items:center;gap:7px;font-size:12px;font-weight:600;color:#B26A06}
.dirty-hint[hidden]{display:none}
.dirty-hint i{width:8px;height:8px;border-radius:50%;background:#F0B429;box-shadow:0 0 0 3px rgba(240,180,41,.25)}
.side-save-btn{display:inline-flex;align-items:center;gap:8px;border:1px solid #0F9F6E;border-radius:10px;padding:9px 16px;font:700 12.5px Inter;cursor:pointer;background:#0F9F6E;color:#fff;white-space:nowrap;transition:background .15s,opacity .15s}
.side-save-btn:hover:not(:disabled){background:#0C7F59;border-color:#0C7F59}
.side-save-btn:disabled{background:transparent;color:var(--muted);border-color:var(--panel-border);cursor:not-allowed;opacity:.75}
/* Fill the workspace: only the form scrolls, not the whole page + the form */
html body.settings-page:not(.report-mode){height:100vh;overflow:hidden;box-sizing:border-box}
.settings-page .settings-shell{height:100%;display:flex;flex-direction:column}
.settings-page .layout{flex:1;min-height:0;display:flex;flex-direction:column}
.settings-page .panel{flex:1;min-height:0}
.settings-page .panel-card.section-panel{height:100%}
.settings-page .panel-card.section-panel > .frame{height:100%!important;min-height:0}
#section-system,#section-archive,#section-profile{border:0;box-shadow:none;border-radius:0;background:transparent}
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

<link rel="stylesheet" href="admin_appearance.css">
<script src="admin_appearance.js"></script>
<link rel="stylesheet" href="admin_unified_ui.css?v=<?= (int)@filemtime(__DIR__ . '/admin_unified_ui.css') ?>">
</head>
<body class="settings-page">
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
    <div class="toolbar">
    <aside class="side" aria-label="Settings sections">
      <button class="tab-btn" data-tab="system"><i class="fa-solid fa-sliders" style="color:#3B82F6"></i><span>System &amp; Period</span></button>
      <button class="tab-btn" data-tab="archive"><i class="fa-solid fa-box-archive" style="color:#F59E0B"></i><span>System Archive</span></button>
      <button class="tab-btn" data-tab="profile"><i class="fa-solid fa-user-shield" style="color:#8B5CF6"></i><span>Profile &amp; Security</span></button>
      <button class="tab-btn" data-tab="appearance"><i class="fa-solid fa-palette" style="color:#EC4899"></i><span>Appearance</span></button>
    </aside>
    <div class="side-actions" id="sideActions" style="display:none">
      <span class="dirty-hint" id="dirtyHint" hidden><i></i>Unsaved changes</span>
      <button class="side-save-btn" id="sideSaveBtn" type="button" disabled><i class="fa-solid fa-floppy-disk"></i> Save changes</button>
    </div>
    </div>
    <main class="panel">
      <section class="panel-card section-panel" id="section-system">
        <iframe class="frame" title="System and Period Settings" src="system_settings.php" scrolling="yes" frameborder="0"></iframe>
      </section>
      <section class="panel-card section-panel" id="section-archive" style="display:none">
        <iframe class="frame" title="System Archive" src="system_archive.php" scrolling="yes" frameborder="0"></iframe>
      </section>
      <section class="panel-card section-panel" id="section-profile" style="display:none">
        <iframe class="frame" title="Profile and Security" src="profile_security.php" scrolling="yes" frameborder="0"></iframe>
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
  const d = !!e.data.dirty;
  const sb = document.getElementById('sideSaveBtn'), hint = document.getElementById('dirtyHint');
  if (sb) sb.disabled = !d;
  if (hint) hint.hidden = !d;
  if (d) return;
  const banner = document.getElementById('unsavedBanner');
  if (banner) banner.classList.remove('show');
});
// The System & Period tab can ask to jump to System Archive, pre-selecting the periods to archive.
window.addEventListener('message',(e)=>{
  if(e.origin!==window.location.origin||!e.data||e.data.type!=='pbi-open-tab'||e.data.tab!=='archive') return;
  const ids=(Array.isArray(e.data.preselect)?e.data.preselect:[]).map(Number).filter(n=>n>0);
  const f=document.querySelector('#section-archive iframe.frame');
  if(f) f.src='system_archive.php'+(ids.length?'?preselect='+encodeURIComponent(ids.join(',')):'');
  setTab('archive');
});
// ---- Hooks used by the admin dashboard's sidebar (admin_dashboard.php) ----
function systemFrameWin(){
  const f=document.querySelector('#section-system iframe.frame');
  return f ? f.contentWindow : null;
}
window.pbiUnsavedState=function(){
  const w=systemFrameWin();
  if(!w||typeof w.isSystemSettingsDirty!=='function') return {dirty:false,changes:[]};
  const dirty=!!w.isSystemSettingsDirty();
  return {dirty, changes: (dirty&&typeof w.getSystemSettingsChanges==='function') ? w.getSystemSettingsChanges() : []};
};
window.pbiSaveSystemSettings=function(){
  const w=systemFrameWin();
  return (w&&typeof w.saveSystemSettings==='function') ? Promise.resolve(w.saveSystemSettings()) : Promise.resolve(false);
};
window.pbiDiscardSystemSettings=function(){
  const w=systemFrameWin();
  try{ if(w&&typeof w.discardSystemSettingsChanges==='function') w.discardSystemSettingsChanges(); }catch(e){}
  location.reload(); // reload so the discarded edits are not still sitting in the hidden page
};
function triggerSystemSettingsSave(){
  const frame=document.querySelector('#section-system iframe.frame');
  if(frame&&frame.contentWindow&&typeof frame.contentWindow.saveSystemSettings==='function'){
    frame.contentWindow.saveSystemSettings();
  }
}
document.getElementById('sideSaveBtn').addEventListener('click', triggerSystemSettingsSave);
document.getElementById('bannerSaveBtn').addEventListener('click', triggerSystemSettingsSave);
function enhanceSecurityQuestionsFrame(iframe){
  // The EA Profile & Security screen is loaded in a same-origin iframe.
  // Style only its existing Security Questions card; keep its form and save actions intact.
  const src=(iframe.getAttribute('src')||'').toLowerCase();
  if(!src.includes('profile_security.php')) return;
  let doc;
  try{ doc=iframe.contentDocument; }catch(e){ return; }
  if(!doc || !doc.body || !doc.head) return;
  if(!doc.getElementById('ea-sq-style')){
    const style=doc.createElement('style');
    style.id='ea-sq-style';
    style.textContent=`
      .ea-sq-card{box-sizing:border-box!important;padding:12px!important;border:1px solid var(--panel-border,var(--border-color,#dce8f5))!important;border-radius:15px!important;background:var(--panel-bg,var(--card-bg,#fff))!important;box-shadow:0 4px 16px rgba(32,73,120,.07)!important;overflow:hidden!important}
      .ea-sq-banner{box-sizing:border-box!important;width:100%!important;min-height:92px!important;display:flex!important;align-items:center!important;justify-content:space-between!important;gap:16px!important;padding:17px 19px!important;border:0!important;border-radius:10px!important;background:color-mix(in srgb,var(--accent,var(--primary-color,#2f6ee2)) 7%,var(--panel-bg,#fff))!important;color:var(--text,var(--text-color,#10243f))!important;text-align:left!important;font:inherit!important;cursor:pointer!important}
      .ea-sq-banner:focus-visible{outline:3px solid color-mix(in srgb,var(--accent,var(--primary-color,#2f6ee2)) 38%,transparent)!important;outline-offset:3px!important}
      .ea-sq-left{display:flex!important;align-items:center!important;gap:16px!important;min-width:0!important;flex:1 1 auto!important}
      .ea-sq-icon{width:60px!important;height:60px!important;flex:0 0 60px!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;border-radius:15px!important;background:var(--accent,var(--primary-color,#2f6ee2))!important;color:#fff!important;box-shadow:0 6px 15px color-mix(in srgb,var(--accent,var(--primary-color,#2f6ee2)) 24%,transparent)!important}
      .ea-sq-icon svg{width:25px!important;height:25px!important;display:block!important;fill:currentColor!important}
      .ea-sq-meta{display:block!important;min-width:0!important}
      .ea-sq-title-row{display:flex!important;align-items:center!important;gap:11px!important;flex-wrap:wrap!important}
      .ea-sq-title{margin:0!important;padding:0!important;border:0!important;background:none!important;color:var(--text,var(--text-color,#10243f))!important;font-size:23px!important;font-weight:800!important;line-height:1.15!important;letter-spacing:-.02em!important}
      .ea-sq-status{display:inline-flex!important;align-items:center!important;gap:7px!important;padding:5px 10px!important;border:1px solid color-mix(in srgb,var(--accent,var(--primary-color,#2f6ee2)) 32%,transparent)!important;border-radius:999px!important;background:color-mix(in srgb,var(--accent,var(--primary-color,#2f6ee2)) 8%,transparent)!important;color:var(--accent,var(--primary-color,#2f6ee2))!important;font-size:10px!important;font-weight:800!important;letter-spacing:.045em!important;white-space:nowrap!important}
      .ea-sq-status:before{content:'';width:7px;height:7px;flex:0 0 7px;border-radius:50%;background:currentColor;display:inline-block}
      .ea-sq-description{display:block!important;margin-top:5px!important;color:var(--muted,var(--text-muted,#66809c))!important;font-size:13px!important;line-height:1.45!important;font-weight:400!important}
      .ea-sq-manage{display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:10px!important;flex:0 0 auto!important;padding:12px 17px!important;border:0!important;border-radius:11px!important;background:var(--accent,var(--primary-color,#2f6ee2))!important;color:#fff!important;font-size:13px!important;font-weight:800!important;white-space:nowrap!important;box-shadow:0 5px 13px color-mix(in srgb,var(--accent,var(--primary-color,#2f6ee2)) 20%,transparent)!important}
      .ea-sq-manage svg{width:12px;height:12px;fill:none;stroke:currentColor;stroke-width:2.4;transition:transform .18s ease}
      .ea-sq-banner[aria-expanded="true"] .ea-sq-manage svg{transform:rotate(180deg)}
      .ea-sq-original-title{display:none!important}
      .ea-sq-card.ea-sq-collapsed>:not(.ea-sq-banner){display:none!important}
      @media(max-width:560px){.ea-sq-card{padding:8px!important}.ea-sq-banner{min-height:0!important;padding:13px!important;gap:11px!important;flex-wrap:wrap!important}.ea-sq-left{gap:11px!important;flex:1 1 210px!important}.ea-sq-icon{width:48px!important;height:48px!important;flex-basis:48px!important;border-radius:12px!important}.ea-sq-icon svg{width:21px!important;height:21px!important}.ea-sq-title{font-size:20px!important}.ea-sq-status{font-size:9px!important;padding:4px 8px!important}.ea-sq-description{font-size:12px!important}.ea-sq-manage{margin-left:auto!important;padding:10px 13px!important;font-size:12px!important}}
      @media(prefers-reduced-motion:reduce){.ea-sq-manage svg{transition:none!important}}
    `;
    doc.head.appendChild(style);
  }

  function findAndStyle(){
    if(doc.querySelector('.ea-sq-card')) return true;
    const normalize=s=>(s||'').replace(/\s+/g,' ').trim().replace(/[:：]$/,'');
    const titleCandidates=[...doc.querySelectorAll('h1,h2,h3,h4,h5,h6,legend,[role="heading"],.card-title,.section-title,label,strong,p,div,span')]
      .filter(el=>!el.closest('.ea-sq-banner') && /^security questions?$/i.test(normalize(el.innerText||el.textContent)));
    if(!titleCandidates.length) return false;
    titleCandidates.sort((a,b)=>normalize(a.innerText||a.textContent).length-normalize(b.innerText||b.textContent).length);
    const title=titleCandidates[0];
    let card=null, node=title.parentElement;
    for(let depth=0;node && node!==doc.body && depth<9;depth++,node=node.parentElement){
      const className=typeof node.className==='string'?node.className:'';
      const hasFields=!!node.querySelector('form,input,select,textarea,button[type="submit"]');
      const isCardLike=/(card|panel|section|security|profile|settings|form|box)/i.test(className) || ['SECTION','FIELDSET','ARTICLE','FORM'].includes(node.tagName);
      if(hasFields && isCardLike){ card=node; break; }
    }
    if(!card){
      node=title.parentElement;
      for(let depth=0;node && node!==doc.body && depth<7;depth++,node=node.parentElement){
        if(node.querySelector('form,input,select,textarea,button[type="submit"]')){card=node;break;}
      }
    }
    if(!card || card===doc.body || card===doc.documentElement) return false;

    card.classList.add('ea-sq-card','ea-sq-collapsed');
    title.classList.add('ea-sq-original-title');
    const selects=[...card.querySelectorAll('select')].filter(s=>!s.disabled);
    const selectedCount=selects.filter(s=>s.value && s.selectedIndex>0).length;
    const status=selects.length===0 || selectedCount===0 ? 'SET UP' : (selectedCount===selects.length && selectedCount>=2 ? 'READY' : 'REVIEW');
    const banner=doc.createElement('button');
    banner.type='button';
    banner.className='ea-sq-banner';
    banner.setAttribute('aria-expanded','false');
    banner.setAttribute('aria-label','Manage Security Questions');
    banner.innerHTML=`
      <span class="ea-sq-left">
        <span class="ea-sq-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 2.1 3.5 5.3v5.2c0 5.2 3.5 9.6 8.5 11.4 5-1.8 8.5-6.2 8.5-11.4V5.3L12 2.1zm0 3.1 5.6 2.1v3.2c0 3.7-2.3 7-5.6 8.5-3.3-1.5-5.6-4.8-5.6-8.5V7.3L12 5.2z"/></svg></span>
        <span class="ea-sq-meta"><span class="ea-sq-title-row"><span class="ea-sq-title">Security Questions</span><span class="ea-sq-status">${status}</span></span><span class="ea-sq-description">Used for password recovery. Click to view or change your questions.</span></span>
      </span>
      <span class="ea-sq-manage"><span>Manage</span><svg viewBox="0 0 12 12" aria-hidden="true"><path d="m2 4 4 4 4-4"/></svg></span>`;
    card.insertBefore(banner,card.firstChild);
    banner.addEventListener('click',()=>{
      const isExpanded=banner.getAttribute('aria-expanded')==='true';
      banner.setAttribute('aria-expanded',isExpanded?'false':'true');
      card.classList.toggle('ea-sq-collapsed',isExpanded);
    });
    return true;
  }

  if(findAndStyle()) return;
  // Some profile pages render their cards after the iframe load event.
  if(!iframe.__eaSqObserver && doc.body){
    const observer=new MutationObserver(()=>{if(findAndStyle()){observer.disconnect();iframe.__eaSqObserver=null;}});
    observer.observe(doc.body,{childList:true,subtree:true});
    iframe.__eaSqObserver=observer;
  }
}
function prepareFrame(iframe){
  // Keep a fixed viewport so long Settings forms scroll INSIDE the feature frame.
  // Do not redirect wheel events to window: Settings is itself commonly embedded
  // inside the dashboard content area, so window.scrollBy() can target the wrong scroller.
  /* height is handled by CSS (flex fill) so the page doesn't get a second scrollbar */
}
document.querySelectorAll('iframe.frame').forEach(f=>{
  f.addEventListener('load',()=>{prepareFrame(f);enhanceSecurityQuestionsFrame(f);});
  prepareFrame(f);
  enhanceSecurityQuestionsFrame(f);
});
buttons.forEach(b=>b.addEventListener('click',async ()=>{
  const current=buttons.find(x=>x.classList.contains('active'));
  const leavingSystemTab = current && current.dataset.tab==='system' && b.dataset.tab!=='system';
  if(leavingSystemTab){
    const frame=document.querySelector('#section-system iframe.frame');
    const dirty = frame && frame.contentWindow && typeof frame.contentWindow.isSystemSettingsDirty==='function' && frame.contentWindow.isSystemSettingsDirty();
    if(dirty){
      if(!(await PBI.confirm('You have unsaved changes in System & Period Settings. Are you sure you want to leave without saving?', {okLabel:'Leave without saving'}))){
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
<script src="admin_ajax.php"></script>
</body></html>