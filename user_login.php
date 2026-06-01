<?php
require_once '../includes/db.php';
startSecureSession();

// 1. INITIALIZE CAPTCHA IMMEDIATELY
// This ensures variables exist before the HTML tries to display them
if (!isset($_SESSION['captcha_n1'])) {
    $_SESSION['captcha_n1'] = rand(1, 12);
    $_SESSION['captcha_n2'] = rand(1, 12);
    $_SESSION['captcha_ans'] = $_SESSION['captcha_n1'] + $_SESSION['captcha_n2'];
}

$error = '';
$step = 'login'; 

// Persistence: If user passed password but not math, keep them on math step
if (isset($_SESSION['user_pending_id'])) {
    $step = 'math_verify';
}

// -------------------------------------------------------
// HANDLE POST ACTIONS
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // CSRF Check
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } 
    
    // ACTION: INITIAL LOGIN (Username & Password)
    elseif ($_POST['action'] === 'login') {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            $db = getDB();
            $stmt = $db->prepare('SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            // Check if user exists and password is valid
            if ($user && password_verify($password, $user['password_hash'])) {
                // Success Part 1: Set pending session and move to Math
                $_SESSION['user_pending_id']   = $user['id'];
                $_SESSION['user_pending_name'] = $user['username'];
                $step = 'math_verify';
            } else {
                $error = 'Invalid username or password.';
                sleep(1); // Anti-brute force delay
            }
        }
    } 

    // ACTION: VERIFY MATH CAPTCHA
    elseif ($_POST['action'] === 'verify_math') {
        $user_answer = isset($_POST['math_answer']) ? (int)$_POST['math_answer'] : null;
        $correct_answer = (int)$_SESSION['captcha_ans'];

        if (isset($_SESSION['user_pending_id']) && $user_answer === $correct_answer) {
            // Success Part 2: Finalize login
            $_SESSION['user_id']   = $_SESSION['user_pending_id'];
            $_SESSION['user_name'] = $_SESSION['user_pending_name'];
            
            // Cleanup security sessions
            unset($_SESSION['user_pending_id'], $_SESSION['user_pending_name'], $_SESSION['captcha_ans'], $_SESSION['captcha_n1'], $_SESSION['captcha_n2']);

            session_regenerate_id(true);
            header('Location: dashboard.php'); 
            exit;
        } else {
            $error = 'Incorrect math answer. Please try again.';
            $step = 'math_verify';
            // Force new numbers on failure
            $_SESSION['captcha_n1'] = rand(1, 12);
            $_SESSION['captcha_n2'] = rand(1, 12);
            $_SESSION['captcha_ans'] = $_SESSION['captcha_n1'] + $_SESSION['captcha_n2'];
        }
    }
}

// Reset logic if user wants to go back to login screen
if (isset($_GET['reset'])) {
    unset($_SESSION['user_pending_id'], $_SESSION['user_pending_name']);
    header("Location: login.php");
    exit();
}

$csrf  = generateCSRFToken();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login — AldiFoods</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Additional styling to layout the eye icon cleanly inside the password field */
        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-container input {
            width: 100%;
            padding-right: 40px; /* Leave space for the icon */
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
        <span class="badge" style="background:rgba(74,158,255,.15);color:var(--info)">User</span>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($step === 'login'): ?>
        <h2>Sign In</h2>
        <p class="subtitle">Welcome back to AldiFoods</p>
        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="Your username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="password-container">
                    <input type="password" name="password" id="password" required placeholder="Your password">
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
            <button type="submit" class="btn btn-primary">Continue →</button>
            <div style="text-align: center; margin-top: 15px;">
              <a href="forgot-password.php" class="link" style="font-size: 0.85rem;">Forgot password?</a>
            </div>
        </form>
        <hr class="divider">
        <p class="text-sm text-muted" style="text-align:center">
            Don't have an account? <a href="register.php" class="link">Register</a>
        </p>
        <p class="text-sm text-muted mt-1" style="text-align:center">
            <a href="../admin/login.php" class="link">Admin Login</a>
        </p>

    <?php elseif ($step === 'math_verify'): ?>
        <h2>Human Check</h2>
        <p class="subtitle">Solve this simple addition to proceed.</p>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="verify_math">
            
            <div class="math-box" style="background: #f4f7f6; border: 2px solid #e0e6ed; border-radius: 12px; padding: 25px; text-align: center; font-size: 2rem; font-weight: 800; color: #2d3748; margin-bottom: 1.5rem;">
                <?= $_SESSION['captcha_n1'] ?> + <?= $_SESSION['captcha_n2'] ?> = ?
            </div>

            <div class="form-group">
                <input type="number" name="math_answer" class="form-control" placeholder="Answer" required autofocus style="text-align: center; font-size: 1.2rem;">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Verify & Sign In →</button>
        </form>

        <div class="mt-2 text-sm text-muted" style="text-align:center">
            <a href="login.php?reset=1" class="link">← Back to login</a>
        </div>
    <?php endif; ?>
</div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const passwordInput = document.getElementById('password');
        const toggleButton = document.getElementById('togglePassword');
        const eyeIcon = document.getElementById('eyeIcon');
        const eyeSlashIcon = document.getElementById('eyeSlashIcon');

        if (toggleButton && passwordInput) {
            toggleButton.addEventListener('click', function () {
                // Toggle the type attribute
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle the visibility of the SVG icons
                if (type === 'password') {
                    eyeIcon.style.display = 'block';
                    eyeSlashIcon.style.display = 'none';
                } else {
                    eyeIcon.style.display = 'none';
                    eyeSlashIcon.style.display = 'block';
                }
            });
        }
    });
</script>
</body>
</html> 
