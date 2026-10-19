<?php
// student/forgot_password.php
// Password recovery with security questions (no email involved).
//   Step 1: student enters their username.
//   Step 2: student answers their 3 security questions.
//   If all 3 are right, a short-lived reset permission is stored in the SESSION
//   (never in a URL or on screen) and the student is sent to reset_password.php.
require_once 'security.php';
secure_session_start();
require_once 'db.php';
security_ensure_tables($mysqli);

$error = '';

if (($_GET['restart'] ?? '') === '1') {
    unset($_SESSION['fp']);
    header("Location: forgot_password.php");
    exit;
}

// Recovery attempt state expires after 15 minutes
$fp = $_SESSION['fp'] ?? null;
if ($fp && (time() - (int)($fp['t'] ?? 0)) > 900) { unset($_SESSION['fp']); $fp = null; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = $_POST['step'] ?? '';

    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $error = "Your session expired. Please try again.";

    } elseif ($step === 'username') {
        $u = trim($_POST['username'] ?? '');
        if ($u === '' || mb_strlen($u) > 100) {
            $error = "Please enter your username.";
        } else {
            $_SESSION['fp'] = ['u' => $u, 't' => time()];
            $fp = $_SESSION['fp'];
        }

    } elseif ($step === 'answers' && $fp) {
        $u     = $fp['u'];
        $ident = mb_strtolower(mb_substr($u, 0, 100));

        if (auth_is_locked($mysqli, 'reset', $ident)) {
            $error = AUTH_LOCK_MESSAGE;
        } else {
            $stmt = $mysqli->prepare(
                "SELECT id FROM users
                 WHERE username = ? AND role = 'student' AND is_active = 1 AND account_status = 'approved' LIMIT 1"
            );
            $stmt->bind_param("s", $u);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $rows  = $user ? sq_load($mysqli, (int)$user['id']) : [];
            $valid = $user && count($rows) === 3;
            $given = (array)($_POST['answer'] ?? []);

            // Check all three every time (no early exit) and use a dummy hash when there is
            // nothing to compare against, so timing does not reveal whether the account exists.
            $allOk = true;
            for ($slot = 1; $slot <= 3; $slot++) {
                $hash  = $rows[$slot]['answer_hash'] ?? DUMMY_HASH;
                $ok    = password_verify(sq_normalize((string)($given[$slot] ?? '')), $hash);
                $allOk = $allOk && $ok;
            }

            if ($valid && $allOk) {
                auth_clear($mysqli, 'reset', $ident);
                auth_record($mysqli, 'reset', $ident, true);
                session_regenerate_id(true);
                unset($_SESSION['fp']);
                $_SESSION['pw_reset'] = ['uid' => (int)$user['id'], 'exp' => time() + 600];   // 10 minutes
                header("Location: reset_password.php");
                exit;
            }

            auth_record($mysqli, 'reset', $ident, false);
            $error = "Those answers do not match our records.";
        }
    }
}

// Work out which questions to show for step 2 (real ones, or made-up ones that look identical)
$fp = $_SESSION['fp'] ?? null;
$showQuestions = [];
if ($fp) {
    $all  = sq_all_questions();
    $keys = [];
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? AND role = 'student' AND is_active = 1 AND account_status = 'approved' LIMIT 1");
    $stmt->bind_param("s", $fp['u']);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($u) {
        $rows = sq_load($mysqli, (int)$u['id']);
        if (count($rows) === 3) foreach ($rows as $r) $keys[] = $r['question_key'];
    }
    if (count($keys) !== 3) $keys = sq_fake_keys($fp['u']);
    foreach ($keys as $k) $showQuestions[] = $all[$k] ?? 'Answer your security question.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI — Forgot Password</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link rel="stylesheet" href="student_ui.css"/>
<link rel="stylesheet" href="student_recovery.css"/>
</head>
<body>
<div class="bg-grid" aria-hidden="true"></div>
<svg class="hex-deco hex-1" width="260" height="260" viewBox="0 0 260 260" aria-hidden="true"><polygon points="130,10 240,70 240,190 130,250 20,190 20,70" fill="none" stroke="#D97706" stroke-width="1"/><polygon points="130,50 200,90 200,170 130,210 60,170 60,90" fill="none" stroke="#D97706" stroke-width="1"/></svg>
<svg class="hex-deco hex-2" width="300" height="300" viewBox="0 0 300 300" aria-hidden="true"><polygon points="150,10 280,80 280,220 150,290 20,220 20,80" fill="none" stroke="#2B6CB0" stroke-width="1"/><polygon points="150,60 220,100 220,200 150,240 80,200 80,100" fill="none" stroke="#2B6CB0" stroke-width="1"/></svg>

<div class="card <?= $fp ? 'wide' : '' ?>">
    <div class="card-header">
        <div class="icon-wrap"><i class="fa-solid fa-key"></i></div>
        <div class="card-title">Forgot Password</div>
        <div class="card-subtitle">
            <?php if ($fp): ?>
                Answer all three of your security questions to reset your password.
            <?php else: ?>
                Enter your username. You will be asked the security questions you chose when you registered.
            <?php endif; ?>
        </div>
    </div>
    <div class="divider"></div>

    <?php if ($error): ?>
    <div class="alert alert-error" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= htmlspecialchars($error) ?></span></div>
    <?php endif; ?>

    <?php if (!$fp): ?>
    <form method="POST" action="forgot_password.php" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="step" value="username">
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <div class="input-wrap">
                <i class="fa-solid fa-user f-icon"></i>
                <input class="form-input" type="text" id="username" name="username" maxlength="100"
                       placeholder="Enter your username" required autofocus/>
            </div>
        </div>
        <button type="submit" class="btn-main"><i class="fa-solid fa-arrow-right"></i> Continue</button>
    </form>

    <?php else: ?>
    <form method="POST" action="forgot_password.php" autocomplete="off" id="answerForm">
        <?= csrf_field() ?>
        <input type="hidden" name="step" value="answers">
        <?php foreach ($showQuestions as $i => $qText): $n = $i + 1; ?>
        <div class="sq-block">
            <span class="q-num">Question <?= $n ?></span>
            <div class="q-text"><?= htmlspecialchars($qText) ?></div>
            <div class="form-group">
                <input class="form-input plain" type="text" name="answer[<?= $n ?>]" maxlength="100"
                       placeholder="Your answer" required autocomplete="off" <?= $n === 1 ? 'autofocus' : '' ?>/>
            </div>
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn-main" id="answerBtn"><i class="fa-solid fa-unlock-keyhole"></i> Verify Answers</button>
    </form>
    <p class="help-note">Answers are not case-sensitive. If you can't remember them, or you never set security questions, please ask your administrator to reset your password.</p>
    <?php endif; ?>

    <div class="back-row">
        <?php if ($fp): ?><a href="forgot_password.php?restart=1"><i class="fa-solid fa-rotate-left"></i> Use a different username</a><br><?php endif; ?>
        <a href="student_login.php"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
    </div>
</div>
<script>
const af = document.getElementById('answerForm');
if (af) {
    af.addEventListener('submit', () => { const b = document.getElementById('answerBtn'); b.disabled = true; });
    window.addEventListener('pageshow', (e) => { if (e.persisted) document.getElementById('answerBtn').disabled = false; });
}
</script>
</body>
</html>
