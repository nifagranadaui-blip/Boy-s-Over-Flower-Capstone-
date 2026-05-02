<?php
require_once '../includes/db.php';
startSecureSession();

$error = '';
$step = 'login'; // login | math_verify

// -------------------------------------------------------
// HANDLE POST ACTIONS
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh and try again.';
    } 
    
    // ACTION: Initial Login
    elseif ($_POST['action'] === 'login') {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            $db = getDB();
            $stmt = $db->prepare('SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();
            $stmt->close();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Password correct! Move to Math Step
                $_SESSION['pending_admin_id'] = $admin['id'];
                $step = 'math_verify';
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }

    // ACTION: Verify Math Captcha
    elseif ($_POST['action'] === 'verify_math') {
        $user_answer = isset($_POST['math_answer']) ? (int)$_POST['math_answer'] : null;
        $correct_answer = isset($_SESSION['captcha_ans']) ? (int)$_SESSION['captcha_ans'] : null;

        if ($user_answer === $correct_answer && isset($_SESSION['pending_admin_id'])) {
            // SUCCESS: Log them in fully
            $_SESSION['admin_id'] = $_SESSION['pending_admin_id'];
            unset($_SESSION['pending_admin_id'], $_SESSION['captcha_ans'], $_SESSION['captcha_n1'], $_SESSION['captcha_n2']);
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Incorrect answer. Please try again.";
            $step = 'math_verify'; // Keep them on this step
            unset($_SESSION['captcha_n1']); // Force new numbers
        }
    }
}

$csrf = generateCSRFToken();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — AldiFoods</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
<div class="auth-card">
    <div class="auth-logo">
        <span>ALdiFoods</span>
        <span class="badge">Admin</span>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($step === 'login'): ?>
        <h2>Welcome back</h2>
        <p class="subtitle">Sign in to the admin dashboard</p>

        <form method="POST" action="" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="login">

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter admin username" required
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn btn-primary">Continue →</button>
        </form>

    <?php elseif ($step === 'math_verify'): ?>
        <h2>Human Check</h2>
        <p class="subtitle">Solve this to verify you are human.</p>

        <?php 
        if (!isset($_SESSION['captcha_n1'])) {
            $_SESSION['captcha_n1'] = rand(1, 12);
            $_SESSION['captcha_n2'] = rand(1, 12);
            $_SESSION['captcha_ans'] = $_SESSION['captcha_n1'] + $_SESSION['captcha_n2'];
        }
        ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="verify_math">

            <div class="math-box" style="background: #f4f7f6; border: 2px solid #e0e6ed; border-radius: 12px; padding: 20px; text-align: center; font-size: 1.8rem; font-weight: 700; color: #2d3748; margin-bottom: 1.5rem;">
                <?= $_SESSION['captcha_n1'] ?> + <?= $_SESSION['captcha_n2'] ?> = ?
            </div>

            <div class="form-group">
                <input type="number" name="math_answer" class="form-control" placeholder="Your answer" required autofocus style="text-align: center; font-size: 1.2rem; height: 50px;">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Verify & Sign In</button>
        </form>

        <div class="mt-2 text-sm text-muted" style="text-align:center">
            <a href="login.php" class="link">← Back to login</a>
        </div>
    <?php endif; ?>

    <hr class="divider">
    <p class="text-sm text-muted" style="text-align:center">
        <a href="../user/login.php" class="link">Go to User Portal</a>
    </p>
</div>
</div>
</body>
</html>