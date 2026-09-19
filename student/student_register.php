<?php
	// student/student_register.php
	session_start();
	require_once 'db.php';

	$error   = '';
	$success = '';

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {

		$full_name  = trim($_POST['full_name']  ?? '');
		$username   = trim($_POST['username']   ?? '');
		$email      = trim($_POST['email']      ?? '');
		$password   = $_POST['password']        ?? '';
		$confirm_pw = $_POST['confirm_password']?? '';
		$department = trim($_POST['department'] ?? '');
		$year_level = trim($_POST['year_level'] ?? '');

		// Map the selected school level (JHS/SHS/College) to the education_level
		// enum used everywhere else in the system (junior_high/senior_high/college).
		// This is what student_tracker.php filters on -- without it, new
		// accounts get education_level = NULL and never show up under any
		// level tab.
		$dept_to_level = [
			'JHS'     => 'junior_high',
			'SHS'     => 'senior_high',
			'College' => 'college',
		];
		$education_level = $dept_to_level[$department] ?? null;

		// Basic validation
		if (empty($full_name))  { $error = "Full name is required."; }
		elseif (empty($username))  { $error = "Username is required."; }
		elseif (empty($email))  { $error = "Email address is required."; }
		elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = "Please enter a valid email address."; }
		elseif (empty($department) || !isset($dept_to_level[$department])) { $error = "Please select your school level."; }
		elseif (empty($password))  { $error = "Password is required."; }
		elseif (strlen($password) < 8) { $error = "Password must be at least 8 characters."; }
		elseif ($password !== $confirm_pw) { $error = "Passwords do not match."; }
		elseif (empty($year_level)) { $error = "Please select your year/grade level."; }
		else {
			// Check duplicate username
			$chk = $mysqli->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
			$chk->bind_param("s", $username);
			$chk->execute();
			$chk->store_result();
			if ($chk->num_rows > 0) {
				$error = "Username already taken. Please choose another.";
			}
			$chk->close();

			// Check duplicate email
			if (empty($error)) {
				$chk2 = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
				$chk2->bind_param("s", $email);
				$chk2->execute();
				$chk2->store_result();
				if ($chk2->num_rows > 0) {
					$error = "Email already registered.";
				}
				$chk2->close();
			}
		}

		// Insert if no errors
		if (empty($error)) {
			$hash = password_hash($password, PASSWORD_DEFAULT);

			$stmt = $mysqli->prepare(
				"INSERT INTO users
				 (full_name, username, email, password_hash, role, designation, department, education_level, year_level, is_active)
				 VALUES (?, ?, ?, ?, 'student', 'Student', ?, ?, ?, 1)"
			);
			$stmt->bind_param(
				"sssssss",
				$full_name,
				$username,
				$email,
				$hash,
				$department,
				$education_level,
				$year_level
			);

			if ($stmt->execute()) {
				$stmt->close();
				$mysqli->close();
				$_SESSION['reg_success'] = "Account created! Please log in with your username and password.";
				header("Location: student_login.php");
				exit;
			} else {
				$error = "Registration failed: " . $mysqli->error;
				$stmt->close();
			}
		}

		if ($mysqli->ping()) $mysqli->close();
	}
	?>
	<!DOCTYPE html>
	<html lang="en">
	<head>
	<meta charset="UTF-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<title>PBI — Student Registration</title>
	<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
	<style>
	:root{--dark-blue:#0A192F;--blue-mid:#172A45;--blue-inner:#0F1F3D;--gold:#D97706;--gold-hover:#F59E0B;--light:#E0E6F0;--muted:#A0B3C6;--radius:8px;--shadow:0 8px 32px rgba(0,0,0,0.45);}
	*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
	body{min-height:100vh;background:#0A192F;font-family:'DM Sans',sans-serif;color:var(--light);display:flex;align-items:center;justify-content:center;padding:28px 20px;position:relative;overflow-x:hidden;}
	.bg-grid{display:block;position:fixed;inset:0;z-index:0;background-image:repeating-linear-gradient(45deg,rgba(217,119,6,.07) 0px,rgba(217,119,6,.07) 1px,transparent 1px,transparent 26px),repeating-linear-gradient(-45deg,rgba(217,119,6,.05) 0px,rgba(217,119,6,.05) 1px,transparent 1px,transparent 26px);}
	.hex-deco{position:fixed;z-index:0;pointer-events:none;opacity:.5;}
	.hex-1{top:-60px;left:-60px;}
	.hex-2{bottom:-70px;right:-70px;}
	.reg-card{position:relative;z-index:10;background:rgba(23,42,69,.88);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.09);border-radius:16px;padding:30px 32px 28px;width:100%;max-width:500px;box-shadow:var(--shadow);animation:cardIn .65s cubic-bezier(.22,1,.36,1) both;}
	@keyframes cardIn{from{opacity:0;transform:translateY(28px) scale(.97)}to{opacity:1;transform:none}}
	.card-header{text-align:center;margin-bottom:18px;}
	.logo-ring{width:54px;height:54px;border-radius:50%;display:block;object-fit:cover;border:2px solid var(--gold);box-shadow:0 0 16px rgba(217,119,6,.4);margin:0 auto 10px;}
	.card-title{font-family:'Rajdhani',sans-serif;font-size:21px;font-weight:700;letter-spacing:1.6px;color:#fff;text-transform:uppercase;}
	.card-subtitle{font-size:10.5px;color:var(--muted);letter-spacing:1.1px;text-transform:uppercase;margin-top:3px;}
	.divider{height:1px;background:linear-gradient(90deg,transparent,rgba(217,119,6,.4),transparent);margin-bottom:15px;}
	.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
	.form-grid .full{grid-column:1/-1;}
	.form-group{display:flex;flex-direction:column;gap:5px;}
	.form-label{font-size:10px;font-weight:600;letter-spacing:1.1px;text-transform:uppercase;color:var(--muted);}
	.req{color:#f87171;}
	.input-wrap{position:relative;}
	.input-wrap .f-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:12px;pointer-events:none;}
	.form-input{width:100%;padding:9px 11px 9px 34px;background:rgba(10,25,47,.7);border:1px solid rgba(255,255,255,.1);border-radius:var(--radius);color:var(--light);font-size:13px;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .25s,box-shadow .25s;appearance:none;}
	.form-input::placeholder{color:rgba(160,179,198,.42);}
	.form-input:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(217,119,6,.18);}
	.select-arr::after{content:'\f078';font-family:'Font Awesome 6 Free';font-weight:900;position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--muted);pointer-events:none;font-size:10px;}
	.toggle-pw{position:absolute;right:11px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;font-size:12px;padding:0;}
	.alert{display:flex;align-items:flex-start;gap:8px;border-radius:8px;padding:9px 12px;font-size:12px;margin-bottom:14px;}
	.alert-error{background:rgba(240,84,84,.12);border:1px solid rgba(240,84,84,.3);color:#fca5a5;}
	.btn-register{width:100%;padding:11px;background:var(--gold);border:none;border-radius:var(--radius);color:#fff;font-size:14px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;margin-top:14px;transition:background .2s,transform .15s;box-shadow:0 4px 16px rgba(217,119,6,.38);display:flex;align-items:center;justify-content:center;gap:8px;}
	.btn-register:hover{background:var(--gold-hover);transform:translateY(-1px);}
	.card-footer{text-align:center;margin-top:16px;font-size:11px;color:var(--muted);border-top:1px solid rgba(255,255,255,.06);padding-top:13px;}
	.card-footer a{color:var(--gold-hover);text-decoration:none;font-weight:600;}
	.college-only{display:none;}
	@media(max-width:540px){.reg-card{padding:20px 14px 18px;}.form-grid{grid-template-columns:1fr;}.form-grid .full{grid-column:1;}}
	</style>
	</head>
	<body>
	<div class="bg-grid"></div>
	<svg class="hex-deco hex-1" width="260" height="260" viewBox="0 0 260 260"><polygon points="130,10 240,70 240,190 130,250 20,190 20,70" fill="none" stroke="#D97706" stroke-width="1"/><polygon points="130,50 200,90 200,170 130,210 60,170 60,90" fill="none" stroke="#D97706" stroke-width="1"/></svg>
	<svg class="hex-deco hex-2" width="300" height="300" viewBox="0 0 300 300"><polygon points="150,10 280,80 280,220 150,290 20,220 20,80" fill="none" stroke="#2B6CB0" stroke-width="1"/><polygon points="150,60 220,100 220,200 150,240 80,200 80,100" fill="none" stroke="#2B6CB0" stroke-width="1"/></svg>

	<div class="reg-card">
		<div class="card-header">
			<img class="logo-ring" src="../image/pbi_logo" alt="PBI Logo"/>
			<div class="card-title">Student Registration</div>
			<div class="card-subtitle">Pandan Bay Institute — Evaluation System</div>
		</div>

		<?php if ($error): ?>
		<div class="alert alert-error">
			<i class="fa-solid fa-circle-exclamation" style="flex-shrink:0;margin-top:1px"></i>
			<span><?= htmlspecialchars($error) ?></span>
		</div>
		<?php endif; ?>

		<form method="POST" action="student_register.php" id="regForm" autocomplete="off" onsubmit="return validateRegistration()">
			<div class="form-group" style="margin-bottom:15px;">
				<label class="form-label">School Level <span class="req">*</span></label>
				<div class="input-wrap select-arr">
					<select class="form-input" name="department" id="dept_input" required onchange="setDept(this.value)">
						<option value="" disabled <?= empty($_POST['department']) ? 'selected' : '' ?>>Select school level</option>
						<option value="JHS" <?= (($_POST['department'] ?? '') === 'JHS') ? 'selected' : '' ?>>JHS</option>
						<option value="SHS" <?= (($_POST['department'] ?? '') === 'SHS') ? 'selected' : '' ?>>SHS</option>
						<option value="College" <?= (($_POST['department'] ?? '') === 'College') ? 'selected' : '' ?>>College</option>
					</select>
					<i class="fa-solid fa-school f-icon"></i>
				</div>
			</div>
			<div class="divider"></div>

			<div class="form-grid">
				<div class="form-group full">
					<label class="form-label">Full Name <span class="req">*</span></label>
					<div class="input-wrap">
						<input class="form-input" type="text" name="full_name"
							   placeholder="Last Name, First Name M.I." required
							   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"/>
						<i class="fa-solid fa-id-card f-icon"></i>
					</div>
				</div>

				<div class="form-group">
					<label class="form-label">Year / Grade Level <span class="req">*</span></label>
					<div class="input-wrap select-arr">
						<select class="form-input" name="year_level" id="year_level" required>
							<option value="" disabled>Select level</option>
						</select>
						<i class="fa-solid fa-layer-group f-icon"></i>
					</div>
				</div>

				<div class="form-group">
					<label class="form-label">Username <span class="req">*</span></label>
					<div class="input-wrap">
						<input class="form-input" type="text" name="username"
							   placeholder="Choose a username" required
							   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"/>
						<i class="fa-solid fa-user f-icon"></i>
					</div>
				</div>

				<div class="form-group">
					<label class="form-label">Email Address <span class="req">*</span></label>
					<div class="input-wrap">
						<input class="form-input" type="email" name="email"
							   placeholder="your@email.com" required
							   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
						<i class="fa-solid fa-envelope f-icon"></i>
					</div>
				</div>

				<div class="form-group">
					<label class="form-label">Password <span class="req">*</span></label>
					<div class="input-wrap">
						<input class="form-input" type="password" id="pw1" name="password"
							   placeholder="Min. 8 characters" required/>
						<i class="fa-solid fa-lock f-icon"></i>
						<button type="button" class="toggle-pw" onclick="togglePw('pw1','e1')">
							<i class="fa-solid fa-eye" id="e1"></i>
						</button>
					</div>
				</div>

				<div class="form-group">
					<label class="form-label">Confirm Password <span class="req">*</span></label>
					<div class="input-wrap">
						<input class="form-input" type="password" id="pw2" name="confirm_password"
							   placeholder="Re-enter password" required/>
						<i class="fa-solid fa-lock f-icon"></i>
						<button type="button" class="toggle-pw" onclick="togglePw('pw2','e2')">
							<i class="fa-solid fa-eye" id="e2"></i>
						</button>
					</div>
				</div>
			</div>

			<button type="submit" class="btn-register">
				<i class="fa-solid fa-user-plus"></i>
				<span id="regBtnLabel">Create JHS Account</span>
			</button>
		</form>

		<div class="card-footer">
			Already have an account? <a href="student_login.php">Sign in here</a><br><br>
		</div>
	</div>

	<script>
	const levels = {
		JHS:     ['Grade 7','Grade 8','Grade 9','Grade 10'],
		SHS:     ['Grade 11','Grade 12'],
		College: ['1st Year','2nd Year','3rd Year','4th Year']
	};
	function setDept(d) {
		document.getElementById('regBtnLabel').textContent = d ? ('Create ' + d + ' Account') : 'Create Account';

		const sel = document.getElementById('year_level');
		const previousLevel = <?= json_encode($_POST['year_level'] ?? '') ?>;

		sel.innerHTML = '<option value="" disabled selected>Select level</option>';

		(levels[d] || []).forEach(l => {
			const o = document.createElement('option');
			o.value = l;
			o.textContent = l;
			if (l === previousLevel) o.selected = true;
			sel.appendChild(o);
		});
	}
	function validateRegistration() {
		const form = document.getElementById('regForm');
		const requiredFields = form.querySelectorAll('[required]');

		for (const field of requiredFields) {
			if (!field.value.trim()) {
				field.focus();
				return false;
			}
		}

		const email = form.querySelector('[name="email"]');
		if (email && !email.checkValidity()) {
			email.focus();
			return false;
		}

		const password = document.getElementById('pw1');
		const confirmPassword = document.getElementById('pw2');

		if (password.value.length < 8) {
			password.focus();
			return false;
		}

		if (password.value !== confirmPassword.value) {
			confirmPassword.focus();
			return false;
		}

		return true;
	}

	function togglePw(id, ic) {
		const e = document.getElementById(id), i = document.getElementById(ic);
		e.type = e.type === 'password' ? 'text' : 'password';
		i.className = e.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
	}
	setDept(<?= json_encode($_POST['department'] ?? '') ?>);
	</script>
	</body>
	</html>