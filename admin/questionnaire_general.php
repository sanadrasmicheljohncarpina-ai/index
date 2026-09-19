<?php
session_start();
require_once 'db.php';
require_once '../shared/EvaluationContextService.php';
require_once '../shared/QuestionnaireService.php';

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['superadmin','admin'], true)) {
    http_response_code(403);
    exit('Unauthorized');
}

qn_migrate_legacy_once($mysqli);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$scope = $_GET['scope'] ?? $_POST['scope'] ?? 'faculty';
if (!qn_scope_is_valid($scope)) $scope = 'faculty';

function qxm_scope_target(string $scope): string {
    return match ($scope) {
        'faculty' => 'Faculty',
        'staff' => 'Staff',
        'school_head', 'ea' => 'EA',
        default => 'Faculty'
    };
}
function qxm_e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
function qxm_redirect(string $scope, ?int $userId = null, string $msg = ''): never {
    $url = 'questionnaire.php?scope=' . rawurlencode($scope);
    if ($userId) $url .= '&user_id=' . $userId;
    if ($msg !== '') $url .= '&msg=' . rawurlencode($msg);
    header('Location: ' . $url);
    exit;
}
function qxm_selected_user_scope(string $scope, int $userId): ?string {
    return $scope === 'staff' ? 'Staff'
        : ($scope === 'school_head' ? null
        : ($scope === 'ea' ? 'EA' : null));
}

$selectedUser = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0);
$message = (string)($_GET['msg'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf, (string)$_POST['csrf_token'])) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_shared_category' && $scope === 'faculty') {
        $name = trim($_POST['category_name'] ?? '');
        if ($name !== '') {
            $count = $mysqli->query("SELECT COALESCE(MAX(sort_order),0)+1 AS n FROM question_categories WHERE target_type='Faculty' AND eval_type='general' AND evaluator_role='shared'")
                ->fetch_assoc()['n'] ?? 0;
            qn_ensure_shared_category($mysqli, 'Faculty', $name, (int)$count);
            qxm_redirect($scope, null, 'Category added.');
        }
    }

    if ($action === 'rename_shared_category' && $scope === 'faculty') {
        $id = (int)($_POST['category_id'] ?? 0);
        $newName = trim($_POST['category_name'] ?? '');
        if ($id > 0 && $newName !== '') {
            $stmt = $mysqli->prepare("SELECT category_name FROM question_categories WHERE id=? AND target_type='Faculty' AND eval_type='general' AND evaluator_role='shared' LIMIT 1");
            $stmt->bind_param('i', $id); $stmt->execute(); $old = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if ($old) {
                $stmt = $mysqli->prepare("UPDATE question_categories SET category_name=? WHERE id=?");
                $stmt->bind_param('si', $newName, $id); $stmt->execute(); $stmt->close();
                $stmt = $mysqli->prepare("UPDATE evaluation_questions SET category=? WHERE category=? AND target_type='Faculty' AND eval_type='general' AND evaluator_role='shared'");
                $stmt->bind_param('ss', $newName, $old['category_name']); $stmt->execute(); $stmt->close();
            }
            qxm_redirect($scope, null, 'Category renamed.');
        }
    }

    if ($action === 'rename_shared_category' && $scope === 'faculty') {
        $id = (int)($_POST['category_id'] ?? 0);
        $newName = trim($_POST['category_name'] ?? '');
        if ($id > 0 && $newName !== '') {
            $stmt = $mysqli->prepare("SELECT category_name FROM question_categories WHERE id=? AND target_type='Faculty' AND eval_type='general' AND evaluator_role='shared' LIMIT 1");
            $stmt->bind_param('i', $id); $stmt->execute(); $old = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if ($old) {
                $stmt = $mysqli->prepare("UPDATE question_categories SET category_name=? WHERE id=?");
                $stmt->bind_param('si', $newName, $id); $stmt->execute(); $stmt->close();
                $stmt = $mysqli->prepare("UPDATE evaluation_questions SET category=? WHERE target_type='Faculty' AND eval_type='general' AND evaluator_role='shared' AND category=?");
                $stmt->bind_param('ss', $newName, $old['category_name']); $stmt->execute(); $stmt->close();
            }
            qxm_redirect($scope, null, 'Category renamed.');
        }
    }

    if ($action === 'delete_shared_category' && $scope === 'faculty') {
        $id = (int)($_POST['category_id'] ?? 0);
        if ($id > 0) {
            $stmt = $mysqli->prepare("SELECT category_name FROM question_categories WHERE id=? AND target_type='Faculty' AND eval_type='general' AND evaluator_role='shared' LIMIT 1");
            $stmt->bind_param('i', $id); $stmt->execute(); $old = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if ($old) {
                $oldName = $old['category_name'];
                $stmt = $mysqli->prepare("UPDATE evaluation_questions SET category='General' WHERE target_type='Faculty' AND eval_type='general' AND evaluator_role='shared' AND category=?");
                $stmt->bind_param('s', $oldName); $stmt->execute(); $stmt->close();
                $stmt = $mysqli->prepare("DELETE FROM question_categories WHERE id=?");
                $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
                qn_ensure_shared_category($mysqli, 'Faculty', 'General', 0);
            }
            qxm_redirect($scope, null, 'Category removed; questions moved to General.');
        }
    }

    if ($action === 'add_question') {
        $category = trim($_POST['category'] ?? 'General') ?: 'General';
        $text = trim($_POST['question_text'] ?? '');
        if ($text === '') qxm_redirect($scope, $selectedUser, 'Question text is required.');

        if ($scope === 'faculty') {
            qn_ensure_shared_question($mysqli, 'Faculty', $category, $text, 0);
        } else {
            if ($selectedUser <= 0) qxm_redirect($scope, null, 'Select a target person first.');
            $targetType = qxm_selected_user_scope($scope, $selectedUser);
            if ($scope === 'school_head') {
                $stmt = $mysqli->prepare("SELECT role FROM users WHERE id=? AND role IN ('dean','principal') AND is_active=1 AND account_status='approved' LIMIT 1");
                $stmt->bind_param('i', $selectedUser); $stmt->execute(); $u = $stmt->get_result()->fetch_assoc(); $stmt->close();
                $targetType = $u ? ucfirst(strtolower($u['role'])) : null;
            }
            if ($scope === 'ea') $targetType = 'EA';
            if (!$targetType) qxm_redirect($scope, null, 'Invalid target.');
            qn_ensure_user_question($mysqli, $selectedUser, $targetType, $category, $text, 0);
        }
        qxm_redirect($scope, $selectedUser, 'Question added.');
    }

    if ($action === 'update_question') {
        $qid = (int)($_POST['question_id'] ?? 0);
        $category = trim($_POST['category'] ?? 'General') ?: 'General';
        $text = trim($_POST['question_text'] ?? '');
        if ($qid <= 0 || $text === '') qxm_redirect($scope, $selectedUser, 'Invalid question update.');

        if ($scope === 'faculty') {
            $stmt = $mysqli->prepare("UPDATE evaluation_questions SET question_text=?, category=? WHERE id=? AND target_type='Faculty' AND eval_type='general' AND evaluator_role='shared'");
            $stmt->bind_param('ssi', $text, $category, $qid); $stmt->execute(); $stmt->close();
        } else {
            $targetType = qxm_selected_user_scope($scope, $selectedUser);
            if ($scope === 'school_head') {
                $stmt = $mysqli->prepare("SELECT role FROM users WHERE id=? LIMIT 1");
                $stmt->bind_param('i', $selectedUser); $stmt->execute(); $u = $stmt->get_result()->fetch_assoc(); $stmt->close();
                $targetType = $u ? ucfirst(strtolower($u['role'])) : null;
            }
            if ($scope === 'ea') $targetType = 'EA';
            if ($targetType) {
                $stmt = $mysqli->prepare("UPDATE user_questions SET question_text=?, category=? WHERE id=? AND user_id=? AND target_type=? AND eval_type='general'");
                $stmt->bind_param('ssiis', $text, $category, $qid, $selectedUser, $targetType); $stmt->execute(); $stmt->close();
                qn_ensure_user_category($mysqli, $selectedUser, $targetType, $category, 0);
            }
        }
        qxm_redirect($scope, $selectedUser, 'Question updated.');
    }

    if ($action === 'delete_question') {
        $qid = (int)($_POST['question_id'] ?? 0);
        if ($scope === 'faculty') {
            $stmt = $mysqli->prepare("UPDATE evaluation_questions SET is_active=0 WHERE id=? AND target_type='Faculty' AND eval_type='general' AND evaluator_role='shared'");
            $stmt->bind_param('i', $qid); $stmt->execute(); $stmt->close();
        } else {
            $targetType = qxm_selected_user_scope($scope, $selectedUser);
            if ($scope === 'school_head') {
                $stmt = $mysqli->prepare("SELECT role FROM users WHERE id=? LIMIT 1");
                $stmt->bind_param('i', $selectedUser); $stmt->execute(); $u = $stmt->get_result()->fetch_assoc(); $stmt->close();
                $targetType = $u ? ucfirst(strtolower($u['role'])) : null;
            }
            if ($scope === 'ea') $targetType = 'EA';
            if ($targetType) {
                $stmt = $mysqli->prepare("DELETE FROM user_questions WHERE id=? AND user_id=? AND target_type=? AND eval_type='general'");
                $stmt->bind_param('iis', $qid, $selectedUser, $targetType); $stmt->execute(); $stmt->close();
            }
        }
        qxm_redirect($scope, $selectedUser, 'Question removed.');
    }

    if ($action === 'rename_user_category') {
        if ($selectedUser <= 0) qxm_redirect($scope, null, 'Select a target person first.');
        $catId = (int)($_POST['category_id'] ?? 0);
        $newName = trim($_POST['category_name'] ?? '');
        $targetType = qxm_selected_user_scope($scope, $selectedUser);
        if ($scope === 'school_head') {
            $stmt = $mysqli->prepare("SELECT role FROM users WHERE id=? AND role IN ('dean','principal') LIMIT 1");
            $stmt->bind_param('i', $selectedUser); $stmt->execute(); $u = $stmt->get_result()->fetch_assoc(); $stmt->close();
            $targetType = $u ? ucfirst(strtolower($u['role'])) : null;
        }
        if ($scope === 'ea') $targetType = 'EA';
        if ($catId > 0 && $newName !== '' && $targetType) {
            $stmt = $mysqli->prepare("SELECT category_name FROM user_question_categories WHERE id=? AND user_id=? AND target_type=? AND eval_type='general' LIMIT 1");
            $stmt->bind_param('iis', $catId, $selectedUser, $targetType);
            $stmt->execute(); $old = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if ($old) {
                $stmt = $mysqli->prepare("UPDATE user_question_categories SET category_name=? WHERE id=?");
                $stmt->bind_param('si', $newName, $catId); $stmt->execute(); $stmt->close();
                $stmt = $mysqli->prepare("UPDATE user_questions SET category=? WHERE user_id=? AND target_type=? AND eval_type='general' AND category=?");
                $stmt->bind_param('siss', $newName, $selectedUser, $targetType, $old['category_name']); $stmt->execute(); $stmt->close();
            }
        }
        qxm_redirect($scope, $selectedUser, 'Category renamed.');
    }

    if ($action === 'delete_user_category') {
        if ($selectedUser <= 0) qxm_redirect($scope, null, 'Select a target person first.');
        $catId = (int)($_POST['category_id'] ?? 0);
        $targetType = qxm_selected_user_scope($scope, $selectedUser);
        if ($scope === 'school_head') {
            $stmt = $mysqli->prepare("SELECT role FROM users WHERE id=? AND role IN ('dean','principal') LIMIT 1");
            $stmt->bind_param('i', $selectedUser); $stmt->execute(); $u = $stmt->get_result()->fetch_assoc(); $stmt->close();
            $targetType = $u ? ucfirst(strtolower($u['role'])) : null;
        }
        if ($scope === 'ea') $targetType = 'EA';
        if ($catId > 0 && $targetType) {
            $stmt = $mysqli->prepare("SELECT category_name FROM user_question_categories WHERE id=? AND user_id=? AND target_type=? AND eval_type='general' LIMIT 1");
            $stmt->bind_param('iis', $catId, $selectedUser, $targetType);
            $stmt->execute(); $old = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if ($old) {
                $stmt = $mysqli->prepare("UPDATE user_questions SET category='General' WHERE user_id=? AND target_type=? AND eval_type='general' AND category=?");
                $stmt->bind_param('iss', $selectedUser, $targetType, $old['category_name']); $stmt->execute(); $stmt->close();
                $stmt = $mysqli->prepare("DELETE FROM user_question_categories WHERE id=?");
                $stmt->bind_param('i', $catId); $stmt->execute(); $stmt->close();
                qn_ensure_user_category($mysqli, $selectedUser, $targetType, 'General', 0);
            }
        }
        qxm_redirect($scope, $selectedUser, 'Category removed; questions moved to General.');
    }

    if ($action === 'add_user_category') {
        if ($selectedUser <= 0) qxm_redirect($scope, null, 'Select a target person first.');
        $name = trim($_POST['category_name'] ?? '');
        if ($name !== '') {
            $targetType = qxm_selected_user_scope($scope, $selectedUser);
            if ($scope === 'school_head') {
                $stmt = $mysqli->prepare("SELECT role FROM users WHERE id=? AND role IN ('dean','principal') LIMIT 1");
                $stmt->bind_param('i', $selectedUser); $stmt->execute(); $u = $stmt->get_result()->fetch_assoc(); $stmt->close();
                $targetType = $u ? ucfirst(strtolower($u['role'])) : null;
            }
            if ($scope === 'ea') $targetType = 'EA';
            if ($targetType) {
                $r = $mysqli->query("SELECT COALESCE(MAX(sort_order),0)+1 AS n FROM user_question_categories WHERE user_id=".(int)$selectedUser." AND target_type='". $mysqli->real_escape_string($targetType) ."' AND eval_type='general'");
                $n = (int)($r?->fetch_assoc()['n'] ?? 0);
                qn_ensure_user_category($mysqli, $selectedUser, $targetType, $name, $n);
            }
        }
        qxm_redirect($scope, $selectedUser, 'Category added.');
    }
}

$labels = qn_scope_labels();
$counts = qn_question_counts();

$facultyQuestions = qn_get_faculty_questions($mysqli);

$facultyUsers = [];
$staffUsers = [];
$headUsers = [];
$eaUsers = [];

$ures = $mysqli->query("SELECT id, full_name, designation, photo, role, secondary_role
                        FROM users
                        WHERE role IN ('teacher','staff') AND is_active=1 AND account_status='approved'
                        ORDER BY full_name");
if ($ures) {
    while ($u = $ures->fetch_assoc()) {
        $u['id'] = (int)$u['id'];
        $isTeaching = ec_has_teacher_function($u);
        if (!$isTeaching) {
            $stmt = $mysqli->prepare("SELECT
                    NOT EXISTS(SELECT 1 FROM teaching_assignments WHERE user_id=?) AS no_teaching,
                    NOT EXISTS(SELECT 1 FROM user_year_levels WHERE user_id=?) AS no_levels");
            $stmt->bind_param('ii', $u['id'], $u['id']);
            $stmt->execute();
            $x = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $isTeaching = !((int)($x['no_teaching'] ?? 0) && (int)($x['no_levels'] ?? 0));
        }
        if ($isTeaching) $facultyUsers[] = $u;
        if (ec_has_staff_function($u) && ec_is_non_teaching_staff($mysqli, $u['id'])) $staffUsers[] = $u;
    }
}
$hres = $mysqli->query("SELECT id, full_name, designation, photo, role
                        FROM users
                        WHERE role IN ('dean','principal') AND is_active=1 AND account_status='approved'
                        ORDER BY full_name");
if ($hres) $headUsers = $hres->fetch_all(MYSQLI_ASSOC);
$eres = $mysqli->query("SELECT id, full_name, designation, photo, role
                        FROM users
                        WHERE role='superadmin' AND is_active=1 AND account_status='approved'
                        ORDER BY updated_at DESC, full_name");
if ($eres) $eaUsers = $eres->fetch_all(MYSQLI_ASSOC);

$selectedTargetType = null;
$selectedPerson = null;
$personQuestions = [];
$personCategories = [];
if ($selectedUser > 0 && $scope !== 'faculty') {
    if ($scope === 'staff') {
        foreach ($staffUsers as $u) if ((int)$u['id'] === $selectedUser) $selectedPerson = $u;
        $selectedTargetType = 'Staff';
    } elseif ($scope === 'school_head') {
        foreach ($headUsers as $u) if ((int)$u['id'] === $selectedUser) $selectedPerson = $u;
        if ($selectedPerson) $selectedTargetType = ucfirst(strtolower($selectedPerson['role']));
    } elseif ($scope === 'ea') {
        foreach ($eaUsers as $u) if ((int)$u['id'] === $selectedUser) $selectedPerson = $u;
        $selectedTargetType = 'EA';
    }
    if ($selectedPerson && $selectedTargetType) {
        $personQuestions = qn_get_person_questions($mysqli, $selectedUser, $selectedTargetType);
        $stmt = $mysqli->prepare("SELECT id, category_name, sort_order
                                  FROM user_question_categories
                                  WHERE user_id=? AND target_type=? AND eval_type='general'
                                  ORDER BY sort_order, category_name");
        $stmt->bind_param('is', $selectedUser, $selectedTargetType);
        $stmt->execute();
        $personCategories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

$facultyCategories = [];
$cr = $mysqli->query("SELECT id, category_name, sort_order
                      FROM question_categories
                      WHERE target_type='Faculty' AND eval_type='general' AND evaluator_role='shared'
                      ORDER BY sort_order, category_name");
if ($cr) $facultyCategories = $cr->fetch_all(MYSQLI_ASSOC);

$personQuestionCounts = [];
if ($scope !== 'faculty') {
    $r = $mysqli->query("SELECT user_id, target_type, COUNT(*) AS c FROM user_questions WHERE eval_type='general' GROUP BY user_id,target_type");
    if ($r) while ($row=$r->fetch_assoc()) $personQuestionCounts[(int)$row['user_id']][(string)$row['target_type']] = (int)$row['c'];
}

$scopePeople = match ($scope) {
    'staff' => $staffUsers,
    'school_head' => $headUsers,
    'ea' => $eaUsers,
    default => [],
};
$selectedPersonName = $selectedPerson['full_name'] ?? '';

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Questionnaire — PBI</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--bg:#f8fafc;--card:#fff;--line:#e2e8f0;--text:#0f172a;--muted:#64748b;--accent:#d99a2b;--blue:#2563eb;--green:#059669;--danger:#dc2626;--navy:#0a192f}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:Inter,system-ui,sans-serif}.wrap{padding:24px 28px 40px;max-width:1400px;margin:auto}
.header{display:flex;justify-content:space-between;gap:16px;align-items:flex-end;margin-bottom:18px}.title h1{margin:0;font-size:28px}.title p{margin:6px 0 0;color:var(--muted);font-size:13px}
.tabs{display:flex;gap:6px;flex-wrap:wrap;background:#fff;border:1px solid var(--line);padding:6px;border-radius:12px;box-shadow:0 4px 16px rgba(15,23,42,.05);margin-bottom:18px}.tab{display:flex;align-items:center;gap:9px;padding:10px 15px;border-radius:9px;color:var(--muted);text-decoration:none;font-weight:700;font-size:13px}.tab.active{background:var(--navy);color:#fff}.tab .count{font-size:11px;opacity:.8}.grid{display:grid;grid-template-columns:340px 1fr;gap:18px}.card{background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 4px 16px rgba(15,23,42,.05)}.card h2{font-size:16px;margin:0}.cardhead{padding:16px 18px;border-bottom:1px solid var(--line)}.cardbody{padding:16px 18px}.person{display:flex;justify-content:space-between;gap:10px;padding:11px 10px;border-radius:10px;text-decoration:none;color:var(--text);border:1px solid transparent;margin-bottom:6px}.person:hover,.person.active{background:#f8fafc;border-color:#cbd5e1}.person-main{display:flex;align-items:center;gap:10px;min-width:0}.avatar{width:36px;height:36px;border-radius:50%;background:#eef2f7;display:flex;align-items:center;justify-content:center;overflow:hidden;color:#64748b}.avatar img{width:100%;height:100%;object-fit:cover}.person-name{font-weight:700;font-size:13px}.person-meta{font-size:11px;color:var(--muted);margin-top:2px}.badge{font-size:11px;font-weight:800;padding:4px 8px;border-radius:999px;background:#f1f5f9;color:#475569;white-space:nowrap}.badge.has{background:#ecfdf5;color:#047857}.empty{padding:30px 10px;text-align:center;color:var(--muted)}.empty i{font-size:28px;margin-bottom:10px;opacity:.35}.toolbar{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:12px}.subtle{color:var(--muted);font-size:12px}.list{display:grid;gap:10px}.qrow{border:1px solid var(--line);border-radius:12px;padding:12px}.qtop{display:flex;justify-content:space-between;gap:10px;align-items:flex-start}.qtext{font-size:13.5px;line-height:1.45}.qcat{font-size:11px;color:#92400e;background:#fffbeb;border:1px solid #fde68a;border-radius:999px;padding:3px 8px;white-space:nowrap}.actions{display:flex;gap:6px}.btn{border:1px solid #cbd5e1;background:#fff;color:#334155;padding:8px 11px;border-radius:8px;text-decoration:none;font-size:12px;font-weight:700;cursor:pointer}.btn.primary{background:var(--accent);border-color:var(--accent);color:#0a192f}.btn.danger{border-color:#fecaca;color:#b91c1c;background:#fff}.btn.small{padding:6px 9px;font-size:11px}.form{display:grid;gap:8px;margin-top:12px}.field,.select{width:100%;padding:10px 11px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:var(--text)}textarea.field{min-height:80px;resize:vertical}.catbar{display:flex;flex-wrap:wrap;gap:7px;margin-bottom:14px}.chip{display:flex;align-items:center;gap:6px;border:1px solid #cbd5e1;border-radius:999px;padding:5px 9px;background:#fff;font-size:11px}.chip form{display:inline}.notice{padding:10px 12px;border-radius:10px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;font-size:12px;margin-bottom:12px}.success{background:#ecfdf5;border-color:#a7f3d0;color:#047857}.dangerbox{background:#fef2f2;border-color:#fecaca;color:#b91c1c}@media(max-width:900px){.grid{grid-template-columns:1fr}.wrap{padding:18px}.tabs{overflow:auto;flex-wrap:nowrap}.tab{white-space:nowrap}}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div class="title">
      <h1>Questionnaire</h1>
      <p>Assign questions by <strong>evaluation target</strong>. The same target questionnaire is used automatically by every applicable evaluator.</p>
    </div>
  </div>

  <nav class="tabs">
    <?php foreach ($labels as $key=>$label): ?>
      <?php
        $icon = $key==='faculty'?'fa-chalkboard-user':($key==='staff'?'fa-briefcase':($key==='school_head'?'fa-user-tie':'fa-user-shield'));
        $count = $key==='faculty' ? $counts['faculty'] : array_sum($counts[$key] ?? []);
      ?>
      <a class="tab <?= $scope===$key?'active':'' ?>" href="questionnaire.php?scope=<?= urlencode($key) ?>">
        <i class="fa-solid <?= $icon ?>"></i><?= qxm_e($label) ?><span class="count"><?= (int)$count ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <?php if ($message !== ''): ?><div class="notice success"><?= qxm_e($message) ?></div><?php endif; ?>

  <?php if ($scope === 'faculty'): ?>
    <div class="card">
      <div class="cardhead"><h2>Faculty question bank</h2></div>
      <div class="cardbody">
        <div class="notice">This is one centralized Faculty questionnaire. Student, Peer-to-Peer, Dean, Principal, and other applicable Faculty evaluations read this same bank.</div>
        <div class="catbar">
          <?php foreach ($facultyCategories as $cat): ?>
            <span class="chip">
              <details style="display:inline">
                <summary class="btn small" style="display:inline-block"><?= qxm_e($cat['category_name']) ?></summary>
                <form method="post" class="form" style="min-width:240px">
                  <input type="hidden" name="csrf_token" value="<?= qxm_e($csrf) ?>">
                  <input type="hidden" name="scope" value="faculty">
                  <input type="hidden" name="category_id" value="<?= (int)$cat['id'] ?>">
                  <input type="hidden" name="action" value="rename_shared_category">
                  <input class="field" name="category_name" value="<?= qxm_e($cat['category_name']) ?>" required>
                  <button class="btn small" type="submit">Rename</button>
                </form>
              </details>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete this category? Questions will move to General.');">
                <input type="hidden" name="csrf_token" value="<?= qxm_e($csrf) ?>">
                <input type="hidden" name="scope" value="faculty">
                <input type="hidden" name="category_id" value="<?= (int)$cat['id'] ?>">
                <input type="hidden" name="action" value="delete_shared_category">
                <button class="btn small danger" type="submit">×</button>
              </form>
            </span>
          <?php endforeach; ?>
        </div>
        <form method="post" class="form" style="max-width:520px">
          <input type="hidden" name="csrf_token" value="<?= qxm_e($csrf) ?>"><input type="hidden" name="scope" value="faculty"><input type="hidden" name="action" value="add_shared_category">
          <div style="display:flex;gap:8px"><input class="field" name="category_name" placeholder="New category" required><button class="btn primary" type="submit">Add Category</button></div>
        </form>
        <div style="border-top:1px solid var(--line);margin:18px 0 0;padding-top:18px">
          <div class="toolbar"><div><strong><?= count($facultyQuestions) ?> questions</strong><div class="subtle">Shared across all applicable Faculty evaluations</div></div></div>
          <div class="list">
          <?php if (!$facultyQuestions): ?><div class="empty"><i class="fa-regular fa-clipboard"></i><div>No Faculty questions yet.</div></div><?php endif; ?>
          <?php foreach ($facultyQuestions as $q): ?>
            <div class="qrow">
              <div class="qtop"><div class="qtext"><?= qxm_e($q['question_text']) ?></div><span class="qcat"><?= qxm_e($q['category'] ?: 'General') ?></span></div>
              <div class="actions" style="margin-top:9px">
                <details><summary class="btn small" style="display:inline-block">Edit</summary>
                  <form method="post" class="form" style="width:min(700px,100%)">
                    <input type="hidden" name="csrf_token" value="<?= qxm_e($csrf) ?>"><input type="hidden" name="scope" value="faculty"><input type="hidden" name="action" value="update_question"><input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>">
                    <select class="select" name="category"><?php foreach($facultyCategories as $cat): ?><option <?= ($cat['category_name']===$q['category'])?'selected':'' ?>><?= qxm_e($cat['category_name']) ?></option><?php endforeach; ?><option value="General" <?= (!$q['category'] || $q['category']==='General')?'selected':'' ?>>General</option></select>
                    <textarea class="field" name="question_text" required><?= qxm_e($q['question_text']) ?></textarea>
                    <button class="btn primary" type="submit">Save</button>
                  </form>
                </details>
                <form method="post" onsubmit="return confirm('Remove this question from the active questionnaire?');">
                  <input type="hidden" name="csrf_token" value="<?= qxm_e($csrf) ?>"><input type="hidden" name="scope" value="faculty"><input type="hidden" name="action" value="delete_question"><input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>">
                  <button class="btn small danger" type="submit">Remove</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
          </div>
          <form method="post" class="form" style="margin-top:16px">
            <input type="hidden" name="csrf_token" value="<?= qxm_e($csrf) ?>"><input type="hidden" name="scope" value="faculty"><input type="hidden" name="action" value="add_question">
            <select class="select" name="category"><?php foreach($facultyCategories as $cat): ?><option><?= qxm_e($cat['category_name']) ?></option><?php endforeach; ?><option>General</option></select>
            <textarea class="field" name="question_text" placeholder="Enter a Faculty evaluation question..." required></textarea>
            <button class="btn primary" type="submit">Add Faculty Question</button>
          </form>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="grid">
      <div class="card">
        <div class="cardhead">
          <h2><?= qxm_e($labels[$scope]) ?> targets</h2>
          <div class="subtle" style="margin-top:4px"><?= count($scopePeople) ?> eligible target<?= count($scopePeople)===1?'':'s' ?></div>
        </div>
        <div class="cardbody">
          <?php if (!$scopePeople): ?>
            <div class="empty"><i class="fa-regular fa-user"></i><div>No eligible targets.</div></div>
          <?php endif; ?>
          <?php foreach($scopePeople as $p): ?>
            <?php
              $keyType = $scope==='school_head' ? ucfirst(strtolower($p['role'])) : ($scope==='ea' ? 'EA' : 'Staff');
              $cnt = $personQuestionCounts[(int)$p['id']][$keyType] ?? 0;
            ?>
            <a class="person <?= $selectedPerson && (int)$selectedPerson['id']===(int)$p['id']?'active':'' ?>" href="questionnaire.php?scope=<?= urlencode($scope) ?>&user_id=<?= (int)$p['id'] ?>">
              <span class="person-main"><span class="avatar"><?php if(!empty($p['photo'])): ?><img src="../image/<?= qxm_e($p['photo']) ?>" alt=""><?php else: ?><i class="fa-solid <?= $scope==='ea'?'fa-user-shield':($scope==='school_head'?'fa-user-tie':'fa-briefcase') ?>"></i><?php endif; ?></span><span><span class="person-name"><?= qxm_e($p['full_name']) ?></span><span class="person-meta"><?= qxm_e($scope==='school_head'?ucfirst($p['role']):($p['designation'] ?: ($scope==='ea'?'Executive Assistant':'Staff'))) ?></span></span></span>
              <span class="badge <?= $cnt>0?'has':'' ?>"><?= (int)$cnt ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card">
        <div class="cardhead">
          <h2><?= $selectedPerson ? qxm_e($selectedPersonName) : 'Select a target' ?></h2>
          <?php if ($selectedPerson): ?><div class="subtle" style="margin-top:4px">One centralized questionnaire for this target, regardless of evaluator type.</div><?php endif; ?>
        </div>
        <div class="cardbody">
          <?php if (!$selectedPerson): ?>
            <div class="empty"><i class="fa-solid fa-hand-pointer"></i><div>Select a target to manage its questionnaire.</div></div>
          <?php else: ?>
            <div class="catbar">
              <?php foreach($personCategories as $cat): ?>
                <span class="chip">
                  <details style="display:inline">
                    <summary class="btn small" style="display:inline-block"><?= qxm_e($cat['category_name']) ?></summary>
                    <form method="post" class="form" style="min-width:240px">
                      <input type="hidden" name="csrf_token" value="<?= qxm_e($csrf) ?>">
                      <input type="hidden" name="scope" value="<?= qxm_e($scope) ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$selectedUser ?>">
                      <input type="hidden" name="category_id" value="<?= (int)$cat['id'] ?>">
                      <input type="hidden" name="action" value="rename_user_category">
                      <input class="field" name="category_name" value="<?= qxm_e($cat['category_name']) ?>" required>
                      <button class="btn small" type="submit">Rename</button>
                    </form>
                  </details>
                  <form method="post" style="display:inline" onsubmit="return confirm('Delete this category? Questions will move to General.');">
                    <input type="hidden" name="csrf_token" value="<?= qxm_e($csrf) ?>"><input type="hidden" name="scope" value="<?= qxm_e($scope) ?>"><input type="hidden" name="user_id" value="<?= (int)$selectedUser ?>"><input type="hidden" name="category_id" value="<?= (int)$cat['id'] ?>"><input type="hidden" name="action" value="delete_user_category">
                    <button class="btn small danger" type="submit">×</button>
                  </form>
                </span>
              <?php endforeach; ?>
            </div>
            <form method="post" class="form">
              <input type="hidden" name="csrf_token" value="<?= qxm_e($csrf) ?>"><input type="hidden" name="scope" value="<?= qxm_e($scope) ?>"><input type="hidden" name="user_id" value="<?= (int)$selectedUser ?>"><input type="hidden" name="action" value="add_user_category">
              <div style="display:flex;gap:8px"><input class="field" name="category_name" placeholder="Add category" required><button class="btn" type="submit">Add Category</button></div>
            </form>
            <div style="border-top:1px solid var(--line);margin-top:16px;padding-top:16px">
              <div class="subtle" style="margin-bottom:10px"><strong><?= count($personQuestions) ?></strong> questions</div>
              <div class="list">
              <?php if (!$personQuestions): ?><div class="empty"><i class="fa-regular fa-clipboard"></i><div>No questions assigned yet.</div></div><?php endif; ?>
              <?php foreach($personQuestions as $q): ?>
                <div class="qrow">
                  <div class="qtop"><div class="qtext"><?= qxm_e($q['question_text']) ?></div><span class="qcat"><?= qxm_e($q['category'] ?: 'General') ?></span></div>
                  <div class="actions" style="margin-top:9px">
                    <details><summary class="btn small" style="display:inline-block">Edit</summary>
                      <form method="post" class="form" style="width:min(700px,100%)">
                        <input type="hidden" name="csrf_token" value="<?= qxm_e($csrf) ?>"><input type="hidden" name="scope" value="<?= qxm_e($scope) ?>"><input type="hidden" name="user_id" value="<?= (int)$selectedUser ?>"><input type="hidden" name="action" value="update_question"><input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>">
                        <select class="select" name="category"><?php foreach($personCategories as $cat): ?><option <?= $cat['category_name']===$q['category']?'selected':'' ?>><?= qxm_e($cat['category_name']) ?></option><?php endforeach; ?><option value="General" <?= (!$q['category'] || $q['category']==='General')?'selected':'' ?>>General</option></select>
                        <textarea class="field" name="question_text" required><?= qxm_e($q['question_text']) ?></textarea>
                        <button class="btn primary" type="submit">Save</button>
                      </form>
                    </details>
                    <form method="post" onsubmit="return confirm('Remove this question?');">
                      <input type="hidden" name="csrf_token" value="<?= qxm_e($csrf) ?>"><input type="hidden" name="scope" value="<?= qxm_e($scope) ?>"><input type="hidden" name="user_id" value="<?= (int)$selectedUser ?>"><input type="hidden" name="action" value="delete_question"><input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>">
                      <button class="btn small danger" type="submit">Remove</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
              </div>
              <form method="post" class="form" style="margin-top:16px">
                <input type="hidden" name="csrf_token" value="<?= qxm_e($csrf) ?>"><input type="hidden" name="scope" value="<?= qxm_e($scope) ?>"><input type="hidden" name="user_id" value="<?= (int)$selectedUser ?>"><input type="hidden" name="action" value="add_question">
                <select class="select" name="category"><?php foreach($personCategories as $cat): ?><option><?= qxm_e($cat['category_name']) ?></option><?php endforeach; ?><option>General</option></select>
                <textarea class="field" name="question_text" placeholder="Enter a question for this target..." required></textarea>
                <button class="btn primary" type="submit">Add Question</button>
              </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
