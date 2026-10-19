<?php
/**
 * faculty/received_results.php
 *
 * Shared "Evaluation Received" page body for the Faculty and Staff portals.
 * Same layout as the Principal's Evaluation Received page:
 *   overview cards (Overall Average, Evaluations Received, Highest / Lowest-Rated Area),
 *   "Performance by Category" bars, and one filterable "Evaluations Received" list
 *   (term picker, sort, "With comments only") that opens the existing details modal.
 *
 * Each portal keeps its own accent colour: everything here is driven by --teal
 * (Faculty = blue, Staff = green), so no colour is hard-coded to one portal.
 *
 * Used from faculty_dashboard.php and staff_dashboard.php:
 *     <?php render_received_results($mysqli, (int)$user_id); ?>
 *
 * The dashboards already provide openEvalDetails() and the details modal
 * (get_evaluation_details.php); this file reuses them.
 *
 * -- CONFIDENTIALITY ---------------------------------------------------------
 * Evaluator identity is never selected, joined or shown. Responses are labeled only
 * "Evaluation #1", "#2", ... in submission order. Keep it that way.
 */

require_once __DIR__ . '/kept_feedback.php';

if (!function_exists('render_received_results')) {

    function rr_rows(mysqli $db, string $sql, string $types = '', array $params = []): array {
        try {
            $stmt = $db->prepare($sql);
            if (!$stmt) return [];
            if ($types !== '') { $stmt->bind_param($types, ...$params); }
            $stmt->execute();
            $res  = $stmt->get_result();
            $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            return [];
        }
    }

    function rr_num($v): string {
        return rtrim(rtrim(number_format((float)$v, 2), '0'), '.');
    }

    /** Placeholder comments such as "N/A" or "none" count as no written comment. */
    function rr_clean_comment($v): string {
        $t = trim((string)$v);
        if ($t === '' || preg_match('/^(n\/?a|none|nil|no comments?|no feedback|not applicable|[\s.\-–—]+)$/iu', $t)) return '';
        return $t;
    }

    function rr_score_class($score): string {
        if ($score === null) return 'none';
        if ($score >= 4) return 'high';
        if ($score >= 3) return 'mid';
        return 'low';
    }

    function render_received_results(mysqli $db, int $userId): void {
        $h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

        // Current term (the dashboards already loaded the active period into $period).
        $curPeriodId = (int)($GLOBALS['period']['id'] ?? 0);
        if ($curPeriodId <= 0) {
            $r = rr_rows($db, "SELECT id FROM evaluation_periods WHERE is_active=1 LIMIT 1");
            $curPeriodId = (int)($r[0]['id'] ?? 0);
        }

        // ── LIVE + KEPT responses, identity-free ───────────────────────────────
        $items = [];
        $live = rr_rows($db, "
            SELECT et.id, et.period_id, et.remarks AS comment, et.submitted_at,
                   ep.period_label, ep.semester, ep.school_year,
                   (SELECT SUM(qa.answer_score)   FROM questionnaire_answers qa WHERE qa.tracker_id = et.id) AS score_sum,
                   (SELECT COUNT(qa.answer_score) FROM questionnaire_answers qa WHERE qa.tracker_id = et.id) AS score_count
            FROM evaluation_tracker et
            LEFT JOIN evaluation_periods ep ON ep.id = et.period_id
            WHERE et.target_user_id = ? AND et.status NOT IN ('draft','in_progress')", 'i', [$userId]);
        foreach ($live as $r) {
            $items[] = ['arg' => (string)(int)$r['id'], 'live_id' => (int)$r['id'], 'pid' => (int)$r['period_id'], 'comment' => (string)($r['comment'] ?? ''),
                'submitted_at' => $r['submitted_at'], 'sum' => (float)($r['score_sum'] ?? 0), 'cnt' => (int)($r['score_count'] ?? 0),
                'school_year' => $r['school_year'] ?? '', 'semester' => $r['semester'] ?? '', 'period_label' => $r['period_label'] ?? ''];
        }
        foreach (kept_feedback_rows($db, $userId, true) as $r) {
            $items[] = ['arg' => "'k" . (int)$r['keep_id'] . "'", 'answers_json' => (string)($r['answers_json'] ?? ''), 'pid' => (int)($r['period_id'] ?? 0), 'comment' => (string)($r['remarks'] ?? ''),
                'submitted_at' => $r['submitted_at'], 'sum' => (float)$r['score_sum'], 'cnt' => (int)$r['score_count'],
                'school_year' => $r['school_year'] ?? '', 'semester' => $r['semester'] ?? '', 'period_label' => $r['period_label'] ?? '',
                'kept' => true];
        }

        // Live rows' arg is a bare number; kept rows' arg is already quoted ('k12').
        // ── TERMS (dropdown) ───────────────────────────────────────────────────
        $termIds = [];
        if ($curPeriodId > 0) $termIds[$curPeriodId] = true;
        foreach ($items as $it) { if ($it['pid'] > 0) $termIds[$it['pid']] = true; }

        $terms = [];
        if ($termIds) {
            $in = implode(',', array_map('intval', array_keys($termIds)));
            foreach (rr_rows($db, "SELECT * FROM evaluation_periods WHERE id IN ($in)") as $ep) {
                $terms[(int)$ep['id']] = ['id' => (int)$ep['id'], 'school_year' => $ep['school_year'] ?? '',
                    'period_label' => $ep['period_label'] ?? ($ep['semester'] ?? ''), 'date_start' => $ep['date_start'] ?? ''];
            }
            // A period that no longer exists can still have kept feedback — use its snapshot labels.
            foreach (array_keys($termIds) as $pid) {
                if (isset($terms[$pid])) continue;
                $snap = ['school_year' => '', 'period_label' => '', 'date_start' => ''];
                foreach ($items as $it) {
                    if ($it['pid'] === $pid) { $snap['school_year'] = $it['school_year']; $snap['period_label'] = $it['period_label'] ?: $it['semester']; break; }
                }
                $terms[$pid] = ['id' => $pid] + $snap;
            }
            $keptPids = [];
            foreach ($items as $it) { if (!empty($it['kept'])) $keptPids[$it['pid']] = true; }
            foreach ($terms as $pid => &$t) {
                $t['current']  = ($curPeriodId > 0 && $pid === $curPeriodId);
                $t['archived'] = isset($keptPids[$pid]);
                $t['label']    = ($t['school_year'] !== '' && $t['period_label'] !== '')
                    ? $t['school_year'] . ' — ' . $t['period_label']
                    : ($t['period_label'] !== '' ? $t['period_label'] : ($t['school_year'] !== '' ? $t['school_year'] : 'Period #' . $pid));
            }
            unset($t);
            uasort($terms, function ($a, $b) {
                if ($a['current'] !== $b['current']) return $a['current'] ? -1 : 1;
                $c = strcmp((string)$b['date_start'], (string)$a['date_start']);
                return $c ?: ($b['id'] <=> $a['id']);
            });
        }

        $req    = $_GET['period'] ?? 'all';
        $allSel = !is_string($req) || $req === '' || strtolower(trim($req)) === 'all';
        $sel    = $allSel ? 0 : (int)$req;
        if (!$allSel && !isset($terms[$sel])) { $allSel = true; $sel = 0; }

        $allItems = $items;   // kept for the "vs previous term" trend
        if (!$allSel) $items = array_values(array_filter($items, fn($it) => $it['pid'] === $sel));

        // Submission order only — never evaluator identity.
        usort($items, function ($a, $b) {
            $ta = $a['submitted_at'] ? strtotime($a['submitted_at']) : 0;
            $tb = $b['submitted_at'] ? strtotime($b['submitted_at']) : 0;
            return $ta <=> $tb;
        });

        $history = []; $totSum = 0.0; $totCnt = 0; $n = 1;
        $scoredCount = 0; $commentCount = 0; $liveIds = []; $keptAnswers = [];
        foreach ($items as $it) {
            $totSum += $it['sum']; $totCnt += $it['cnt'];
            $clean = rr_clean_comment($it['comment']);
            $score = $it['cnt'] > 0 ? round($it['sum'] / $it['cnt'], 2) : null;
            if ($score !== null) $scoredCount++;
            if ($clean !== '') $commentCount++;
            if (!empty($it['kept'])) { $keptAnswers[] = $it['answers_json'] ?? ''; }
            elseif (!empty($it['live_id'])) { $liveIds[] = (int)$it['live_id']; }
            $history[] = [
                'arg'     => $it['arg'],
                'idx'     => $n,
                'label'   => 'Evaluation #' . $n++,
                'score'   => $score,
                'comment' => $clean,
                'ts'      => $it['submitted_at'] ? (int)strtotime($it['submitted_at']) : 0,
                'when'    => $it['submitted_at'] ? date('M d, Y g:i A', strtotime($it['submitted_at'])) : 'Unknown date',
                'period'  => (!empty($it['school_year']) && !empty($it['semester'])) ? ($it['school_year'] . ' · ' . $it['semester']) : ($it['period_label'] ?: $it['semester']),
            ];
        }
        $respCount = count($history);
        $overall   = $totCnt > 0 ? round($totSum / $totCnt, 2) : null;
        $starsOn   = $overall !== null ? (int)round($overall) : 0;
        $scopeLabel = $allSel ? 'All Terms' : 'This Term';

        // ── CATEGORY AVERAGES (aggregates only, no evaluator identity) ─────────
        $catAgg = [];
        if ($liveIds) {
            $inLive = implode(',', array_map('intval', $liveIds));
            foreach (rr_rows($db, "
                SELECT COALESCE(uq.category, eq.category, 'General') AS category,
                       SUM(qa.answer_score) AS s, COUNT(qa.answer_score) AS c
                FROM questionnaire_answers qa
                JOIN evaluation_tracker et ON et.id = qa.tracker_id AND et.target_user_id = ?
                LEFT JOIN user_questions uq ON qa.question_source='user' AND uq.id = qa.user_question_id
                LEFT JOIN evaluation_questions eq ON qa.question_source='evaluation' AND eq.id = qa.question_id
                WHERE qa.tracker_id IN ($inLive)
                GROUP BY COALESCE(uq.category, eq.category, 'General')", 'i', [$userId]) as $r) {
                $cat = trim((string)$r['category']) !== '' ? trim((string)$r['category']) : 'General';
                if (!isset($catAgg[$cat])) $catAgg[$cat] = [0.0, 0];
                $catAgg[$cat][0] += (float)$r['s'];
                $catAgg[$cat][1] += (int)$r['c'];
            }
        }
        foreach ($keptAnswers as $json) {
            $list = json_decode($json, true);
            if (!is_array($list)) continue;
            foreach ($list as $row) {
                if (!isset($row['score']) || $row['score'] === null) continue;
                $cat = trim((string)($row['category'] ?? '')) !== '' ? trim((string)$row['category']) : 'General';
                if (!isset($catAgg[$cat])) $catAgg[$cat] = [0.0, 0];
                $catAgg[$cat][0] += (float)$row['score'];
                $catAgg[$cat][1] += 1;
            }
        }
        $catStats = [];
        foreach ($catAgg as $name => $v) { if ($v[1] > 0) $catStats[] = ['name' => (string)$name, 'avg' => round($v[0] / $v[1], 2)]; }
        usort($catStats, fn($a, $b) => $b['avg'] <=> $a['avg']);
        $topCat = $catStats[0] ?? null;
        $lowCat = count($catStats) > 1 ? $catStats[count($catStats) - 1] : null;

        // ── CHANGE VS THE PREVIOUS TERM (only when one whole term is selected) ──
        $trendText = ''; $trendClass = ''; $prevLabel = '';
        if (!$allSel && $overall !== null) {
            $keys = array_keys($terms);
            $pos  = array_search($sel, $keys, true);
            if ($pos !== false && isset($keys[$pos + 1])) {
                $prevId = (int)$keys[$pos + 1]; $pS = 0.0; $pC = 0;
                foreach ($allItems as $it) { if ($it['pid'] === $prevId) { $pS += $it['sum']; $pC += $it['cnt']; } }
                if ($pC > 0) {
                    $d = round($overall - ($pS / $pC), 2);
                    $prevLabel = $terms[$prevId]['label'];
                    if ($d > 0)      { $trendClass = 'up';   $trendText = '▲ +' . number_format($d, 2); }
                    elseif ($d < 0)  { $trendClass = 'down'; $trendText = '▼ -' . number_format(abs($d), 2); }
                    else             { $trendClass = 'flat'; $trendText = 'No change'; }
                }
            }
        }

        $displayList = array_reverse($history);   // newest first by default
        ?>
<style>
/* ── Evaluation Received — same layout as the Principal page ──────────────────
   Shared by the Faculty and Staff portals. Every accent comes from --teal, so
   Faculty stays blue and Staff stays green. Neutral surfaces use the portal's own
   --mid / --inner / --border / --light / --muted, so light and dark themes both work. */
.rr-shell{--rr-card:var(--mid);--rr-line:var(--border);--rr-soft:color-mix(in srgb,var(--teal) 14%,transparent)}

.rr-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:14px;margin-bottom:20px}
.rr-stat{display:flex;align-items:flex-start;gap:14px;background:var(--rr-card);border:1px solid var(--rr-line);border-radius:14px;padding:16px 18px}
.rr-stat-icon{flex:0 0 40px;width:40px;height:40px;border-radius:12px;background:var(--rr-soft);color:var(--teal);display:flex;align-items:center;justify-content:center;font-size:17px}
.rr-stat-body{min-width:0}
.rr-num{font-size:28px;line-height:1.1;font-weight:700;color:var(--light)}
.rr-num small{font-size:14px;font-weight:600;color:var(--muted);margin-left:4px}
.rr-lbl{font-size:12.5px;color:var(--muted);margin-top:4px}
.rr-sub{font-size:12px;color:var(--muted);margin-top:6px;line-height:1.5;overflow-wrap:anywhere}
.rr-stars{letter-spacing:2px;font-size:14px;margin-right:6px}
.rr-stars span{color:rgba(148,163,184,.45)}
.rr-stars span.on{color:#F5B301}
.rr-trend{font-weight:700}
.rr-trend.up{color:#059669}.rr-trend.down{color:#DC2626}.rr-trend.flat{color:var(--muted)}

.rr-panel{background:var(--rr-card);border:1px solid var(--rr-line);border-radius:14px;padding:24px;margin-bottom:20px}
.rr-panel:last-child{margin-bottom:0}
.rr-head{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:14px}
.rr-head h2{margin:0;font-family:'Rajdhani',sans-serif;font-size:19px;font-weight:700;color:var(--teal);display:flex;align-items:center;gap:8px}
.rr-count{font:700 12px 'DM Sans',sans-serif;background:var(--rr-soft);color:var(--teal);padding:2px 10px;border-radius:12px;margin-left:2px}
.rr-shown{font-size:12px;color:var(--muted)}

.rr-picker{display:flex;flex-direction:row;align-items:center;gap:12px;margin:0}
.rr-picker label{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);white-space:nowrap}
.rr-picker .sel-wrap{position:relative;display:inline-flex;align-items:center}
.rr-picker .sel-wrap i{position:absolute;right:15px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--light);font-size:12px}
.rr-picker select{appearance:none;-webkit-appearance:none;min-width:320px;max-width:100%;padding:9px 40px 9px 14px;border-radius:10px;border:1px solid var(--rr-line);background:var(--inner);color:var(--light);font:500 14px 'DM Sans',sans-serif;cursor:pointer}
.rr-picker select:hover,.rr-picker select:focus{border-color:var(--teal);outline:none}
.rr-picker select:focus{box-shadow:0 0 0 3px color-mix(in srgb,var(--teal) 22%,transparent)}

.rr-cat{display:grid;grid-template-columns:minmax(150px,230px) 1fr 70px;align-items:center;gap:16px;padding:10px 0}
.rr-cat-name{font-size:13.5px;font-weight:600;color:var(--light);line-height:1.35}
.rr-cat-track{height:10px;background:color-mix(in srgb,var(--muted) 22%,transparent);border-radius:8px;overflow:hidden}
.rr-cat-fill{height:100%;border-radius:8px}
.rr-cat-fill.high{background:linear-gradient(90deg,#059669,#34D399)}
.rr-cat-fill.mid{background:linear-gradient(90deg,#D97706,#FBBF24)}
.rr-cat-fill.low{background:linear-gradient(90deg,#DC2626,#F87171)}
.rr-cat-score{text-align:right;font-size:15px;font-weight:800;color:var(--light);white-space:nowrap}

.rr-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 0 14px;border-top:1px solid var(--rr-line)}
.rr-controls{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.rr-select{padding:7px 12px;border-radius:9px;border:1px solid var(--rr-line);background:var(--inner);color:var(--light);font:500 13px 'DM Sans',sans-serif;cursor:pointer}
.rr-select:focus{outline:3px solid color-mix(in srgb,var(--teal) 28%,transparent);outline-offset:1px;border-color:var(--teal)}
.rr-chip{display:inline-flex;align-items:center;gap:7px;padding:7px 12px;border-radius:9px;border:1px solid var(--rr-line);background:var(--inner);font:500 13px 'DM Sans',sans-serif;color:var(--light);cursor:pointer;user-select:none}
.rr-chip input{accent-color:var(--teal)}

.rr-item{display:flex;justify-content:space-between;align-items:center;gap:18px;background:var(--inner);border:1px solid var(--rr-line);border-radius:12px;padding:14px 18px;margin-bottom:10px;cursor:pointer;transition:border-color .15s,box-shadow .15s}
.rr-item:last-child{margin-bottom:0}
.rr-item:hover,.rr-item:focus-visible{border-color:var(--teal);box-shadow:0 2px 12px color-mix(in srgb,var(--teal) 20%,transparent);outline:none}
.rr-main{min-width:0;flex:1}
.rr-eyebrow{font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);display:flex;align-items:center;gap:6px}
.rr-meta{font-size:12px;color:var(--muted);margin-top:3px}
.rr-comment{margin:8px 0 0;font-size:13.5px;line-height:1.55;font-style:italic;color:var(--light);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.rr-comment.none{font-style:normal;color:var(--muted)}
.rr-side{display:flex;flex-direction:column;align-items:flex-end;gap:8px;flex-shrink:0}
.rr-badge{font-size:13px;font-weight:800;padding:4px 12px;border-radius:18px;border:1px solid transparent;white-space:nowrap}
.rr-badge small{font-size:11px;font-weight:600;opacity:.75}
.rr-badge.high{background:rgba(16,185,129,.12);color:#059669;border-color:rgba(16,185,129,.3)}
.rr-badge.mid{background:rgba(245,158,11,.14);color:#B45309;border-color:rgba(245,158,11,.35)}
.rr-badge.low{background:rgba(239,68,68,.12);color:#DC2626;border-color:rgba(239,68,68,.32)}
.rr-badge.none{background:rgba(100,116,139,.12);color:var(--muted);border-color:rgba(100,116,139,.25);font-size:12px;font-weight:700}
.rr-detail-btn{background:var(--rr-soft);border:1px solid color-mix(in srgb,var(--teal) 30%,transparent);color:var(--teal);font:700 11px 'DM Sans',sans-serif;padding:6px 12px;border-radius:18px;cursor:pointer}
.rr-detail-btn:hover{border-color:var(--teal)}
.rr-empty{margin:0;color:var(--muted);font-size:13px}

@media(max-width:640px){
  .rr-picker{flex-direction:column;align-items:stretch;gap:5px;width:100%}
  .rr-picker .sel-wrap{width:100%}.rr-picker select{width:100%;min-width:0}
  .rr-item{flex-direction:column;align-items:flex-start;gap:12px}
  .rr-side{flex-direction:row;align-items:center;justify-content:space-between;width:100%}
  .rr-cat{grid-template-columns:1fr 60px}.rr-cat-name{grid-column:1/-1}.rr-cat-track{grid-column:1}
}
</style>

<div class="dashboard-shell rr-shell">

    <div class="rr-stats">
        <div class="rr-stat">
            <div class="rr-stat-icon"><i class="fa-solid fa-star"></i></div>
            <div class="rr-stat-body">
                <div class="rr-num"><?= $overall !== null ? $h(rr_num($overall)) : '—' ?><?php if ($overall !== null): ?><small>/ 5</small><?php endif; ?></div>
                <div class="rr-lbl">Overall Average · <?= $h($scopeLabel) ?></div>
                <?php if ($overall !== null): ?>
                <div class="rr-sub">
                    <span class="rr-stars" aria-hidden="true"><?php for ($i = 1; $i <= 5; $i++): ?><span class="<?= $i <= $starsOn ? 'on' : '' ?>">★</span><?php endfor; ?></span>
                    <?php if ($trendText !== ''): ?><span class="rr-trend <?= $h($trendClass) ?>"><?= $h($trendText) ?></span> vs <?= $h($prevLabel) ?><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="rr-stat">
            <div class="rr-stat-icon"><i class="fa-solid fa-comments"></i></div>
            <div class="rr-stat-body">
                <div class="rr-num"><?= (int)$respCount ?></div>
                <div class="rr-lbl">Evaluations Received · <?= $h($scopeLabel) ?></div>
                <?php if ($respCount > 0): ?><div class="rr-sub"><?= (int)$scoredCount ?> scored · <?= (int)$commentCount ?> with comments</div><?php endif; ?>
            </div>
        </div>
        <?php if ($topCat): ?>
        <div class="rr-stat">
            <div class="rr-stat-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
            <div class="rr-stat-body">
                <div class="rr-num"><?= $h(rr_num($topCat['avg'])) ?><small>/ 5</small></div>
                <div class="rr-lbl">Highest-Rated Area</div>
                <div class="rr-sub"><?= $h($topCat['name']) ?></div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($lowCat): ?>
        <div class="rr-stat">
            <div class="rr-stat-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
            <div class="rr-stat-body">
                <div class="rr-num"><?= $h(rr_num($lowCat['avg'])) ?><small>/ 5</small></div>
                <div class="rr-lbl">Lowest-Rated Area</div>
                <div class="rr-sub"><?= $h($lowCat['name']) ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <section class="rr-panel" aria-label="Evaluations received">
        <div class="rr-head">
            <h2><i class="fa-solid fa-clock-rotate-left"></i> Evaluations Received <span class="rr-count"><?= (int)$respCount ?></span></h2>
            <?php if ($terms): ?>
            <form method="get" class="rr-picker">
                <input type="hidden" name="page" value="my_results">
                <label for="rrTermSelect">Evaluation Term</label>
                <span class="sel-wrap">
                    <select id="rrTermSelect" name="period" onchange="this.form.submit()">
                        <option value="all"<?= $allSel ? ' selected' : '' ?>>All Evaluation Terms — Past &amp; Current</option>
                        <?php foreach ($terms as $t): ?>
                        <option value="<?= (int)$t['id'] ?>"<?= !$allSel && (int)$t['id'] === $sel ? ' selected' : '' ?>><?= $h($t['label']) ?><?= $t['current'] ? ' (Current)' : ($t['archived'] ? ' (Archived)' : '') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                </span>
            </form>
            <?php endif; ?>
        </div>

        <?php if (!$history): ?>
            <p class="rr-empty"><?= $allSel ? 'No evaluations have been received yet.' : 'No evaluations received for this term.' ?></p>
        <?php else: ?>
        <div class="rr-toolbar">
            <div class="rr-controls">
                <select id="rrSort" class="rr-select" onchange="rrApply()" aria-label="Sort evaluations">
                    <option value="newest">Newest first</option>
                    <option value="oldest">Oldest first</option>
                    <option value="high">Highest score</option>
                    <option value="low">Lowest score</option>
                </select>
                <label class="rr-chip"><input type="checkbox" id="rrOnlyComments" onchange="rrApply()"> With comments only</label>
            </div>
            <span class="rr-shown" id="rrShown">Showing <?= (int)$respCount ?> of <?= (int)$respCount ?></span>
        </div>
        <div id="rrList">
            <?php foreach ($displayList as $e): $cls = rr_score_class($e['score']); $open = 'openEvalDetails(' . $e['arg'] . ')'; ?>
            <div class="rr-item" role="button" tabindex="0"
                 data-ts="<?= (int)$e['ts'] ?>" data-idx="<?= (int)$e['idx'] ?>"
                 data-score="<?= $e['score'] !== null ? $h((string)$e['score']) : '-1' ?>"
                 data-comment="<?= $e['comment'] !== '' ? '1' : '0' ?>"
                 onclick="<?= $open ?>"
                 onkeydown="if(event.target===this&&(event.key==='Enter'||event.key===' ')){event.preventDefault();<?= $open ?>}">
                <div class="rr-main">
                    <div class="rr-eyebrow"><i class="fa-solid fa-user-secret"></i> Anonymous — <?= $h($e['label']) ?></div>
                    <div class="rr-meta"><?= $h($e['when']) ?><?= $e['period'] !== '' ? ' · ' . $h($e['period']) : '' ?></div>
                    <?php if ($e['comment'] !== ''): ?>
                        <p class="rr-comment">&ldquo;<?= $h($e['comment']) ?>&rdquo;</p>
                    <?php else: ?>
                        <p class="rr-comment none">No written comment</p>
                    <?php endif; ?>
                </div>
                <div class="rr-side">
                    <?php if ($e['score'] !== null): ?>
                        <span class="rr-badge <?= $cls ?>"><?= $h(rr_num($e['score'])) ?><small> / 5</small></span>
                    <?php else: ?>
                        <span class="rr-badge none">No score</span>
                    <?php endif; ?>
                    <button type="button" class="rr-detail-btn" onclick="event.stopPropagation();<?= $open ?>">View Details <i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="rr-empty" id="rrNoMatch" style="display:none;">No evaluations match this filter.</p>
        <?php endif; ?>
    </section>

</div>
<script>
function rrApply(){
    var list=document.getElementById('rrList'); if(!list) return;
    var items=[].slice.call(list.querySelectorAll('.rr-item'));
    var mode=document.getElementById('rrSort').value;
    var only=document.getElementById('rrOnlyComments').checked;
    var n=function(el,k){return parseFloat(el.dataset[k])||0;};
    var lowKey=function(el){var v=parseFloat(el.dataset.score);return v<0?Infinity:v;};
    items.sort(function(a,b){
        if(mode==='oldest') return n(a,'ts')-n(b,'ts')||n(a,'idx')-n(b,'idx');
        if(mode==='high')   return parseFloat(b.dataset.score)-parseFloat(a.dataset.score)||n(b,'ts')-n(a,'ts');
        if(mode==='low'){var x=lowKey(a),y=lowKey(b);return x===y?n(b,'ts')-n(a,'ts'):(x<y?-1:1);}
        return n(b,'ts')-n(a,'ts')||n(b,'idx')-n(a,'idx');
    });
    var shown=0;
    items.forEach(function(el){var ok=!only||el.dataset.comment==='1';el.style.display=ok?'':'none';if(ok)shown++;list.appendChild(el);});
    document.getElementById('rrShown').textContent='Showing '+shown+' of '+items.length;
    document.getElementById('rrNoMatch').style.display=shown?'none':'block';
}
</script>
<?php
    }
}