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
    <style>
        /* CSS to carefully align the eye icon inside the password input */
        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-container input {
            width: 100%;
            padding-right: 40px; /* Leaves room so text doesn't overlap the icon */
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            color: #718096;
        }
        .toggle-password:hover {
            color: #4a5568;
        }
        .toggle-password svg {
            width: 20px;
            height: 20px;
        }
    </style>
</head>
<body>
<div class="auth-wrap">
<div class="auth-card">
    <div class="auth-logo">
        <span>AldiFoods</span>
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
                <div class="password-container">
                    <input type="password" name="password" id="password" placeholder="Enter password" required>
                    <button type="button" id="togglePassword" class="toggle-password" aria-label="Toggle password visibility">
                        <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <svg id="eyeSlashIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 1-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Continue →</button>
            
            <div style="text-align: center; margin-top: 15px;">
                <a href="forgot-password.php" class="link" style="font-size: 0.85rem;">Forgot password?</a>
            </div>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const passwordInput = document.getElementById('password');
        const toggleButton = document.getElementById('togglePassword');
        const eyeIcon = document.getElementById('eyeIcon');
        const eyeSlashIcon = document.getElementById('eyeSlashIcon');

        // Only run script if elements exist (avoids errors when step !== 'login')
        if (toggleButton && passwordInput) {
            toggleButton.addEventListener('click', function () {
                // Toggle type between 'password' and 'text'
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                
                // Switch icon display properties
                if (isPassword) {
                    eyeIcon.style.display = 'none';
                    eyeSlashIcon.style.display = 'block';
                } else {
                    eyeIcon.style.display = 'block';
                    eyeSlashIcon.style.display = 'none';
                }
            });
        }
    });
</script>
</body>
</html>
