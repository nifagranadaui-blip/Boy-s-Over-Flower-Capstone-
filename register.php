<?php
require_once '../includes/db.php';
startSecureSession();



$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $username  = sanitizeInput($_POST['username'] ?? '');
        $email     = sanitizeEmail($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';
        $full_name = sanitizeInput($_POST['full_name'] ?? '');
        $phone     = sanitizeInput($_POST['phone'] ?? '');
        $address   = sanitizeInput($_POST['address'] ?? '');

        if (empty($username) || empty($email) || empty($password)) {
            $error = 'Username, email, and password are required.';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $error = 'Username must be between 3 and 50 characters.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (!validatePassword($password)) {
            $error = 'Password must be at least 8 characters and contain both letters and numbers.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $db = getDB();

            // 1. Check if username is already taken
$chk = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$chk->bind_param('s', $username);
$chk->execute();
$res = $chk->get_result();

if ($res->num_rows > 0) {
    $error = 'Username already taken. Please choose another.';
} else {
    // 2. If username is free, proceed with registration
    $hashed_pass = password_hash($password, PASSWORD_BCRYPT);
    $ins = $db->prepare('INSERT INTO users (full_name, username, email, password_hash) VALUES (?, ?, ?, ?)');
    $ins->bind_param('ssss', $full_name, $username, $email, $hashed_pass);
    
    if ($ins->execute()) {
        setFlash('success', 'Registration successful! You can now login.');
        header('Location: login.php');
        exit;
    } else {
        $error = 'Registration failed. Please try again.';
    }
}
            
        }
    }
}

$csrf = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — AldiFoods</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
<div class="auth-card" style="max-width:520px">
    <div class="auth-logo">
        <span>AldiFoods</span>
        <span class="badge" style="background:rgba(76,175,125,.15);color:var(--success)">Register</span>
    </div>

    <h2>Create Account</h2>
    <p class="subtitle">Join AldiFoods na!!</p>

    <?php if ($error): ?>
    <div class="alert alert-danger">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/></svg>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="grid-2">
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" required maxlength="50" placeholder="johndoe"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" maxlength="200" placeholder="John Doe"
                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email" required maxlength="255" placeholder="john@example.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required id="regPw" oninput="checkPw(this)">
            </div>
            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" required id="regPwConf" oninput="checkMatch()">
            </div>
        </div>

        <div class="pw-hint" id="regRules">
            <div class="pw-rule" id="r-len">✗ Minimum 8 characters</div>
            <div class="pw-rule" id="r-alpha">✗ Contains letters (A–Z)</div>
            <div class="pw-rule" id="r-num">✗ Contains numbers (0–9)</div>
            <div class="pw-rule" id="r-match">✗ Passwords match</div>
        </div>


        <button type="submit" class="btn btn-primary">Create Account</button>
    </form>

    <hr class="divider">
    <p class="text-sm text-muted" style="text-align:center">
        Already have an account? <a href="login.php" class="link">Sign in</a>
    </p>
</div>
</div>

<script>
function checkPw(input) {
    const v = input.value;
    setRule('r-len',   v.length >= 8);
    setRule('r-alpha', /[A-Za-z]/.test(v));
    setRule('r-num',   /[0-9]/.test(v));
    checkMatch();
}
function checkMatch() {
    const a = document.getElementById('regPw').value;
    const b = document.getElementById('regPwConf').value;
    setRule('r-match', a && b && a === b);
}
function setRule(id, ok) {
    const el = document.getElementById(id);
    el.textContent = (ok ? '✓ ' : '✗ ') + el.textContent.slice(2);
    el.className = 'pw-rule ' + (ok ? 'ok' : 'fail');
}
</script>
</body>
</html>
