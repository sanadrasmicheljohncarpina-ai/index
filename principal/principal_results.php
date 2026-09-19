<?php
require_once 'principal_common.php';

$principalId=(int)$_SESSION['user_id'];
$history=[];$overallAvg=null;$responseCount=0;

$rows=safe_rows($mysqli,"
 SELECT et.id AS tracker_id, et.remarks AS comment, et.submitted_at, ep.period_label, ep.semester, ep.school_year,
        (SELECT AVG(qa.answer_score) FROM questionnaire_answers qa WHERE qa.tracker_id=et.id) AS score
 FROM evaluation_tracker et
 LEFT JOIN evaluation_periods ep ON ep.id=et.period_id
 WHERE et.target_user_id=? AND et.eval_type='student' AND et.evaluation_context='school_head'
   AND et.status IN ('submitted','approved')
 ORDER BY et.submitted_at DESC
",'i',[$principalId]);
foreach($rows as $i=>$r){
 $pl=(!empty($r['school_year'])&&!empty($r['semester']))?($r['school_year'].' · '.$r['semester']):($r['period_label']??$r['semester']??'');
 $history[]=['tracker_id'=>(int)$r['tracker_id'],'score'=>$r['score']!==null?round((float)$r['score'],2):null,'comment'=>$r['comment']??'','submitted_at'=>$r['submitted_at']?date('M d, Y g:i A',strtotime($r['submitted_at'])):'Unknown date','period_label'=>$pl];
}
$responseCount=count($history);
if($responseCount){$vals=array_values(array_filter(array_map(fn($x)=>$x['score'],$history),fn($v)=>$v!==null));if($vals)$overallAvg=round(array_sum($vals)/count($vals),2);}

render_principal_styles();
?>
<style>
.main{max-width:1500px}.privacy{margin-bottom:22px;padding:13px 16px;border:1px solid rgba(16,185,129,.25);background:rgba(16,185,129,.07);border-radius:10px;color:#b7ead6;font-size:12px;line-height:1.55}.top-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:22px}.stat-card{background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:20px;box-shadow:var(--shadow)}.stat-card i{color:var(--amber-h);font-size:20px;margin-bottom:10px}.stat-card .num{font-size:28px;font-weight:700;color:#fff}.stat-card .label{font-size:12px;color:var(--muted);margin-top:4px}.section{background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:24px;box-shadow:var(--shadow);margin-bottom:20px}.section h2{font-family:'Rajdhani',sans-serif;font-size:19px;color:#fff;margin-bottom:16px}.view-evals-btn{width:100%;display:flex;align-items:center;gap:10px;background:rgba(217,154,43,.12);border:1px solid rgba(217,154,43,.28);color:var(--amber-h);padding:12px 14px;border-radius:10px;font:600 13px 'DM Sans',sans-serif;cursor:pointer}.view-evals-btn i:last-child{margin-left:auto;transition:transform .2s}.received-item{display:flex;justify-content:space-between;align-items:center;gap:14px;background:var(--inner);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:14px 16px;margin-bottom:10px;cursor:pointer}.received-item:hover{border-color:rgba(217,154,43,.35)}.received-anon{font-size:13px;font-weight:700;color:#fff}.received-anon i{color:var(--muted);margin-right:5px}.received-meta{font-size:11px;color:var(--muted);margin-top:4px}.received-right{display:flex;flex-direction:column;align-items:flex-end;gap:8px}.received-score{font-size:12px;font-weight:800;color:#f0b84d;background:rgba(217,154,43,.12);border:1px solid rgba(217,154,43,.25);padding:4px 10px;border-radius:18px}.details-btn{background:rgba(217,154,43,.12);border:1px solid rgba(217,154,43,.28);color:var(--amber-h);font-size:11px;font-weight:700;padding:6px 12px;border-radius:18px;cursor:pointer}.eval-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.78);z-index:500;display:none;align-items:center;justify-content:center;padding:20px}.eval-modal-overlay.open{display:flex}.eval-modal{background:var(--mid);border:1px solid rgba(255,255,255,.08);border-radius:16px;width:100%;max-width:720px;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 24px 80px rgba(0,0,0,.6)}.eval-modal-header{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid rgba(255,255,255,.08)}.eval-modal-title{font-family:'Rajdhani',sans-serif;font-size:21px;font-weight:700;color:#fff}.eval-modal-title i{color:var(--amber-h);margin-right:8px}.eval-modal-close{background:none;border:none;color:var(--muted);font-size:19px;cursor:pointer}.eval-modal-body{padding:22px;overflow:auto}.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px}.info-grid>div{background:var(--inner);border:1px solid rgba(255,255,255,.05);border-radius:10px;padding:12px 14px}.info-label{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:5px}.info-value{font-size:13px;color:#fff;font-weight:600}.score-big{color:var(--amber-h)}.modal-section-title{font-size:14px;color:#fff;margin:18px 0 10px}.cat-row-modal{display:flex;align-items:center;gap:10px;margin:9px 0}.cat-name-modal{width:170px;font-size:12px;color:var(--light);flex-shrink:0}.cat-bar{flex:1;height:7px;background:rgba(255,255,255,.08);border-radius:6px;overflow:hidden}.cat-bar>div{height:100%;background:linear-gradient(90deg,var(--amber-dark),var(--amber-h));border-radius:6px}.cat-score-modal{width:42px;text-align:right;font-size:12px;font-weight:700;color:#fff}.q-result{background:var(--inner);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:13px 15px;margin-bottom:8px}.q-no{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:4px}.q-text{font-size:13px;color:#fff;font-weight:600;line-height:1.5}.q-score{margin-top:7px;font-size:12px;color:var(--muted)}.q-score span{margin-left:7px;font-weight:700}.star{color:rgba(255,255,255,.16);margin-right:2px}.star.filled{color:#facc15}.comment-modal{background:var(--inner);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:14px;color:var(--light);font-size:13px;line-height:1.6;font-style:italic}.comment-modal.empty{color:var(--muted);font-style:normal}.loading-eval{padding:50px 10px;text-align:center;color:var(--muted);font-size:13px}.loading-eval i{margin-right:8px}@media(max-width:768px){.top-grid{grid-template-columns:1fr}.info-grid{grid-template-columns:1fr}.received-item{flex-direction:column;align-items:flex-start}.received-right{align-items:flex-start;width:100%}}
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

<?php render_principal_sidebar('results',$me,$scopeLabel,$photo_src); ?>

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
 <div class="page-header"><div class="page-title">View Results</div><div class="page-sub">Your anonymous evaluation results, as submitted by students this period.</div></div>
 <div class="privacy"><i class="fa-solid fa-user-shield"></i> Evaluator identities are never shown here. Direct evaluations of the Principal are restricted to the result only; authorized evaluator names, IDs, photos, departments, and other identifying details are excluded.</div>
 <div class="top-grid">
  <div class="stat-card"><i class="fa-solid fa-star"></i><div class="num"><?= $overallAvg!==null?number_format($overallAvg,2):'—' ?></div><div class="label">Overall Average</div></div>
  <div class="stat-card"><i class="fa-solid fa-comments"></i><div class="num"><?= $responseCount ?></div><div class="label">Evaluations Received</div></div>
  <div class="stat-card"><i class="fa-solid fa-user-secret"></i><div class="num">Anonymous</div><div class="label">Evaluator Privacy</div></div>
 </div>
 <div class="section">
  <h2><i class="fa-solid fa-clock-rotate-left"></i> Evaluations Received</h2>
  <button type="button" class="view-evals-btn" onclick="togglePrincipalEvals()"><i class="fa-solid fa-eye"></i> View Evaluations Received <i class="fa-solid fa-chevron-down" id="principalEvalsCaret"></i></button>
  <div id="principalEvalsList" style="display:none;margin-top:14px;">
  <?php if(!$history): ?><div class="empty-note">No evaluations recorded yet.</div><?php else: foreach($history as $r): ?>
   <div class="received-item" onclick="openPrincipalEvalDetails(<?= $r['tracker_id'] ?>)">
    <div><div class="received-anon"><i class="fa-solid fa-eye-slash"></i> Anonymous Evaluator</div><div class="received-meta"><?= htmlspecialchars($r['submitted_at']) ?><?= $r['period_label']?' · '.htmlspecialchars($r['period_label']):'' ?></div></div>
    <div class="received-right"><span class="received-score"><?= $r['score']!==null?htmlspecialchars((string)$r['score']).' / 5':'—' ?></span><button type="button" class="details-btn" onclick="event.stopPropagation();openPrincipalEvalDetails(<?= $r['tracker_id'] ?>)">View Details <i class="fa-solid fa-chevron-right"></i></button></div>
   </div>
  <?php endforeach; endif; ?>
  </div>
 </div>
</main>
<div class="eval-modal-overlay" id="principalEvalModal"><div class="eval-modal"><div class="eval-modal-header"><div class="eval-modal-title"><i class="fa-solid fa-star"></i>Evaluation Details</div><button class="eval-modal-close" onclick="closePrincipalEvalDetails()"><i class="fa-solid fa-xmark"></i></button></div><div class="eval-modal-body" id="principalEvalBody"><div class="loading-eval">Loading evaluation…</div></div></div></div>
<script>
function togglePrincipalEvals(){const l=document.getElementById('principalEvalsList'),c=document.getElementById('principalEvalsCaret');const o=l.style.display!=='none';l.style.display=o?'none':'block';c.style.transform=o?'':'rotate(180deg)';}
function e(v){if(v===null||v===undefined)return '';const d=document.createElement('div');d.textContent=v;return d.innerHTML;}
function sh(s){let h='';for(let i=1;i<=5;i++)h+=`<i class="fa-solid fa-star star ${i<=s?'filled':''}"></i>`;return h;}
function openPrincipalEvalDetails(id){const m=document.getElementById('principalEvalModal'),b=document.getElementById('principalEvalBody');b.innerHTML='<div class="loading-eval"><i class="fa-solid fa-spinner fa-spin"></i> Loading evaluation…</div>';m.classList.add('open');document.body.style.overflow='hidden';fetch('get_my_evaluation_details.php?tracker_id='+encodeURIComponent(id)).then(r=>r.json()).then(d=>{if(!d.ok){b.innerHTML='<div class="loading-eval"><i class="fa-solid fa-triangle-exclamation"></i>'+e(d.error||'Unable to load this evaluation.')+'</div>';return;}let h=`<div class="info-grid"><div><div class="info-label">Evaluator</div><div class="info-value"><i class="fa-solid fa-eye-slash"></i> Anonymous Evaluator</div></div><div><div class="info-label">Period</div><div class="info-value">${e(d.period_label||'—')}</div></div><div><div class="info-label">Submitted</div><div class="info-value">${e(d.submitted_at||'—')}</div></div><div><div class="info-label">Overall Score</div><div class="info-value score-big">${Number(d.overall_score||0).toFixed(2)} / 5</div></div></div>`;if(d.categories?.length){h+='<h3 class="modal-section-title">Performance by Category</h3>';d.categories.forEach(c=>{h+=`<div class="cat-row-modal"><div class="cat-name-modal">${e(c.category)}</div><div class="cat-bar"><div style="width:${Math.round((c.avg/5)*100)}%"></div></div><div class="cat-score-modal">${Number(c.avg).toFixed(2)}</div></div>`})}if(d.questions?.length){h+='<h3 class="modal-section-title">Question-by-Question Results</h3>';d.questions.forEach((q,i)=>{h+=`<div class="q-result"><div class="q-no">Question ${i+1}</div><div class="q-text">${e(q.question_text)}</div><div class="q-score">${sh(q.score)} <span>Score: ${q.score} / 5</span></div></div>`})}h+='<h3 class="modal-section-title">Comments / Feedback</h3>';h+=d.comment?`<div class="comment-modal">“${e(d.comment)}”</div>`:'<div class="comment-modal empty">No written feedback was provided.</div>';b.innerHTML=h;}).catch(()=>{b.innerHTML='<div class="loading-eval"><i class="fa-solid fa-triangle-exclamation"></i>Something went wrong loading this evaluation.</div>';});}
function closePrincipalEvalDetails(){document.getElementById('principalEvalModal').classList.remove('open');document.body.style.overflow='';}
document.getElementById('principalEvalModal').addEventListener('click',function(ev){if(ev.target===this)closePrincipalEvalDetails();});
</script>
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
</body></html>

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
