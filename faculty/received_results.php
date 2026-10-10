<?php
/**
 * faculty/received_results.php
 *
 * Shared "Evaluations Received" page body for the Faculty and Staff portals.
 * Same layout as the Dean's / EA's Evaluation Received page:
 *   term picker, confidentiality note, Overall Average + Responses Received cards,
 *   "Evaluation History — Anonymous Responses", and the expandable
 *   "Evaluations Received" list that opens the existing details modal.
 *
 * Used from faculty_dashboard.php and staff_dashboard.php:
 *     <?php render_received_results($mysqli, (int)$user_id); ?>
 *
 * The dashboards already provide openEvalDetails(), toggleAllEvals() and the details modal
 * (get_evaluation_details.php); this file reuses them, so the ids viewAllEvalsBtn /
 * allEvalsList / allEvalsCaret below must not be renamed.
 *
 * ── CONFIDENTIALITY ────────────────────────────────────────────────────────
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
            $items[] = ['arg' => (string)(int)$r['id'], 'pid' => (int)$r['period_id'], 'comment' => (string)($r['comment'] ?? ''),
                'submitted_at' => $r['submitted_at'], 'sum' => (float)($r['score_sum'] ?? 0), 'cnt' => (int)($r['score_count'] ?? 0),
                'school_year' => $r['school_year'] ?? '', 'semester' => $r['semester'] ?? '', 'period_label' => $r['period_label'] ?? ''];
        }
        foreach (kept_feedback_rows($db, $userId) as $r) {
            $items[] = ['arg' => "'k" . (int)$r['keep_id'] . "'", 'pid' => (int)($r['period_id'] ?? 0), 'comment' => (string)($r['remarks'] ?? ''),
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

        if (!$allSel) $items = array_values(array_filter($items, fn($it) => $it['pid'] === $sel));

        // Submission order only — never evaluator identity.
        usort($items, function ($a, $b) {
            $ta = $a['submitted_at'] ? strtotime($a['submitted_at']) : 0;
            $tb = $b['submitted_at'] ? strtotime($b['submitted_at']) : 0;
            return $ta <=> $tb;
        });

        $history = []; $totSum = 0.0; $totCnt = 0; $n = 1;
        foreach ($items as $it) {
            $totSum += $it['sum']; $totCnt += $it['cnt'];
            $history[] = [
                'arg'     => $it['arg'],
                'label'   => 'Evaluation #' . $n++,
                'score'   => $it['cnt'] > 0 ? round($it['sum'] / $it['cnt'], 2) : null,
                'comment' => trim($it['comment']),
                'when'    => $it['submitted_at'] ? date('M d, Y g:i A', strtotime($it['submitted_at'])) : 'Unknown date',
                'period'  => (!empty($it['school_year']) && !empty($it['semester'])) ? ($it['school_year'] . ' · ' . $it['semester']) : ($it['period_label'] ?: $it['semester']),
            ];
        }
        $respCount = count($history);
        $overall   = $totCnt > 0 ? round($totSum / $totCnt, 2) : null;
        ?>
<style>
.rr-head{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;margin-bottom:20px}
.rr-title{font-size:26px;font-weight:800;color:var(--light);line-height:1.15}
.rr-picker{display:flex;flex-direction:column;gap:5px;min-width:260px}
.rr-picker label{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);display:flex;align-items:center;gap:6px}
.rr-picker select{appearance:none;-webkit-appearance:none;width:100%;padding:10px 38px 10px 14px;border-radius:10px;border:1px solid var(--border);background:var(--inner) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%2367819E' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 14px center;color:var(--light);font:600 13px inherit;font-family:inherit;cursor:pointer}
.rr-picker select:hover,.rr-picker select:focus{border-color:var(--accent);outline:none}
.rr-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:20px}
.rr-stat{background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:20px 22px;box-shadow:var(--shadow)}
.rr-stat i{color:var(--accent);font-size:21px;margin-bottom:10px;display:block}
.rr-stat .num{font-size:30px;font-weight:700;color:var(--light)}
.rr-stat .lbl{font-size:12.5px;color:var(--muted);margin-top:4px}
.rr-panel{background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:22px 24px;box-shadow:var(--shadow);margin-bottom:20px}
.rr-panel h2{font-size:17px;font-weight:700;margin:0 0 16px;display:flex;align-items:center;gap:9px;color:var(--light)}
.rr-panel h2 i{color:var(--accent);font-size:15px}
.rr-resp{background:var(--inner);border:1px solid var(--border);border-radius:12px;padding:15px 18px;margin-bottom:12px}
.rr-resp:last-child{margin-bottom:0}
.rr-resp-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:8px}
.rr-resp-label{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;display:flex;align-items:center;gap:7px}
.rr-resp-score{font-size:13px;font-weight:700;color:#10B981;background:rgba(16,185,129,.14);padding:3px 12px;border-radius:12px}
.rr-comment{margin:0;font-size:13.5px;color:var(--light);line-height:1.6;font-style:italic;white-space:pre-wrap}
.rr-empty{margin:0;color:var(--muted);font-size:13px;font-style:italic}
.rr-toggle{width:100%;display:flex;align-items:center;gap:10px;background:var(--teal-light, rgba(37,99,235,.12));border:1px solid var(--border);color:var(--accent);padding:12px 14px;border-radius:10px;font:700 13px inherit;font-family:inherit;cursor:pointer}
.rr-list{margin-top:14px}
.rr-item{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;background:var(--inner);border:1px solid var(--border);border-radius:12px;padding:14px 16px;margin-bottom:10px;cursor:pointer}
.rr-item:hover{border-color:var(--accent)}
.rr-item .anon{font-size:13px;font-weight:700;color:var(--light)}
.rr-item .anon i{color:var(--muted);margin-right:5px}
.rr-item .meta{font-size:11.5px;color:var(--muted);margin-top:4px}
.rr-item .right{display:flex;flex-direction:column;align-items:flex-end;gap:8px}
.rr-item .pill{font-size:12px;font-weight:800;color:#10B981;background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.28);padding:4px 11px;border-radius:18px}
</style>

<div class="rr-head">
    <div>
        <div class="rr-title">Evaluation Received</div>
    </div>
    <?php if ($terms): ?>
    <form method="get" class="rr-picker">
        <input type="hidden" name="page" value="my_results">
        <label for="rrTermSelect"><i class="fa-solid fa-calendar-days"></i> Evaluation Term</label>
        <select id="rrTermSelect" name="period" onchange="this.form.submit()">
            <option value="all"<?= $allSel ? ' selected' : '' ?>>All Evaluation Terms — Past &amp; Current</option>
            <?php foreach ($terms as $t): ?>
            <option value="<?= (int)$t['id'] ?>"<?= !$allSel && (int)$t['id'] === $sel ? ' selected' : '' ?>><?= $h($t['label']) ?><?= $t['current'] ? ' (Current)' : ($t['archived'] ? ' (Archived)' : '') ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php endif; ?>
</div>

<div class="rr-stats">
    <div class="rr-stat"><i class="fa-solid fa-star"></i><div class="num"><?= $overall !== null ? $h(rr_num($overall)) : '—' ?></div><div class="lbl">Overall Average</div></div>
    <div class="rr-stat"><i class="fa-solid fa-comments"></i><div class="num"><?= $respCount ?></div><div class="lbl">Responses Received</div></div>
</div>

<div class="rr-panel">
    <h2><i class="fa-solid fa-comment-dots"></i> Evaluation History — Anonymous Responses</h2>
    <?php if (!$history): ?>
        <p class="rr-empty"><?= $allSel ? 'No responses were submitted across any evaluation term.' : 'No responses were submitted for this term.' ?></p>
    <?php else: foreach ($history as $e): ?>
        <div class="rr-resp">
            <div class="rr-resp-head">
                <span class="rr-resp-label"><i class="fa-solid fa-user-secret"></i> Anonymous — <?= $h($e['label']) ?></span>
                <?php if ($e['score'] !== null): ?><span class="rr-resp-score"><?= $h(rr_num($e['score'])) ?></span><?php endif; ?>
            </div>
            <?php if ($e['comment'] !== ''): ?>
                <p class="rr-comment">&ldquo;<?= $h($e['comment']) ?>&rdquo;</p>
            <?php else: ?>
                <p class="rr-empty">No written comment.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; endif; ?>
</div>

<div class="rr-panel">
    <h2><i class="fa-solid fa-clock-rotate-left"></i> Evaluations Received</h2>
    <button type="button" class="rr-toggle" id="viewAllEvalsBtn" onclick="toggleAllEvals()">
        <i class="fa-solid fa-eye"></i> View Evaluations Received
        <i class="fa-solid fa-chevron-down" id="allEvalsCaret" style="transition:transform .2s;margin-left:auto;"></i>
    </button>
    <div id="allEvalsList" class="rr-list" style="display:none;">
        <?php if (!$history): ?>
            <p class="rr-empty">No evaluations have been received yet.</p>
        <?php else: foreach ($history as $e): ?>
            <div class="rr-item" onclick="openEvalDetails(<?= $e['arg'] ?>)">
                <div>
                    <div class="anon"><i class="fa-solid fa-eye-slash"></i>Anonymous Evaluator</div>
                    <div class="meta"><?= $h($e['when']) ?><?= $e['period'] !== '' ? ' · ' . $h($e['period']) : '' ?></div>
                </div>
                <div class="right">
                    <span class="pill"><?= $e['score'] !== null ? $h(number_format($e['score'], 2)) . ' / 5' : '—' ?></span>
                    <button type="button" class="btn-view-details" onclick="event.stopPropagation(); openEvalDetails(<?= $e['arg'] ?>)">
                        View Details <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
<?php
    }
}