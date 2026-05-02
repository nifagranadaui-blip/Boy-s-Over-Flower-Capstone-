<?php
require_once '../includes/db.php';
requireUserLogin();

$db    = getDB();
$error = '';
$success = '';

// Fetch current user data
$stmt = $db->prepare('SELECT id, username, email, full_name, phone, address, password_hash FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Decrypt sensitive fields
$user['email']     = decryptData($user['email']);
$user['full_name'] = decryptData($user['full_name']);
$user['phone']     = decryptData($user['phone']);
$user['address']   = decryptData($user['address']);

// UPDATE PROFILE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $email     = sanitizeEmail($_POST['email'] ?? '');
        $full_name = sanitizeInput($_POST['full_name'] ?? '');
        $phone     = sanitizeInput($_POST['phone'] ?? '');
        $address   = sanitizeInput($_POST['address'] ?? '');
        $newPw     = $_POST['new_password'] ?? '';
        $currentPw = $_POST['current_password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (!verifyPassword($currentPw, $user['password_hash'])) {
            $error = 'Current password is incorrect.';
        } else {
            // Check email uniqueness (excluding self)
            $emailConflict = false;
            $all = $db->query('SELECT id, email FROM users WHERE id != ' . (int)$_SESSION['user_id']);
            while ($r = $all->fetch_assoc()) {
                if (decryptData($r['email']) === $email) { $emailConflict = true; break; }
            }
            if ($emailConflict) {
                $error = 'That email is already in use by another account.';
            } else {
                $encEmail    = encryptData($email);
                $encFullName = encryptData($full_name);
                $encPhone    = encryptData($phone);
                $encAddress  = encryptData($address);

                if (!empty($newPw)) {
                    if (!validatePassword($newPw)) {
                        $error = 'New password must be ≥8 characters with letters and numbers.';
                        goto done;
                    }
                    $pwHash = hashPassword($newPw);
                    $stmt2 = $db->prepare('UPDATE users SET email=?, full_name=?, phone=?, address=?, password_hash=? WHERE id=?');
                    $stmt2->bind_param('sssssi', $encEmail, $encFullName, $encPhone, $encAddress, $pwHash, $_SESSION['user_id']);
                } else {
                    $stmt2 = $db->prepare('UPDATE users SET email=?, full_name=?, phone=?, address=? WHERE id=?');
                    $stmt2->bind_param('ssssi', $encEmail, $encFullName, $encPhone, $encAddress, $_SESSION['user_id']);
                }
                $stmt2->execute(); $stmt2->close();
                $success = 'Profile updated successfully!';

                // Refresh user data
                $user['email']     = $email;
                $user['full_name'] = $full_name;
                $user['phone']     = $phone;
                $user['address']   = $address;
            }
        }
    }
}

// -------------------------------------------------------
// DELETE ACCOUNT
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $confirmPw = $_POST['delete_password'] ?? '';
        if (!verifyPassword($confirmPw, $user['password_hash'])) {
            $error = 'Incorrect password. Account not deleted.';
        } else {
            $stmt3 = $db->prepare('DELETE FROM users WHERE id = ?');
            $stmt3->bind_param('i', $_SESSION['user_id']);
            $stmt3->execute(); $stmt3->close();
            session_destroy();
            header('Location: ../user/register.php');
            exit;
        }
    }
}

done:
$csrf = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — AldiFoods</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
<?php include '_sidebar.php'; ?>
<div class="main-content">

    <div class="page-header">
        <div>
            <h1>My Profile</h1>
            <div class="breadcrumb">Manage your account information</div>
        </div>
    </div>

    <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start">

        <!-- UPDATE PROFILE FORM -->
        <div class="table-card">
            <div class="table-card-header"><h3>Update Profile</h3></div>
            <div style="padding:1.5rem">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="update">

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled style="opacity:.5">
                </div>

                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" required maxlength="255"
                           value="<?= htmlspecialchars($user['email']) ?>">
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" maxlength="200"
                           value="<?= htmlspecialchars($user['full_name']) ?>">
                </div>

                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" maxlength="50"
                           value="<?= htmlspecialchars($user['phone']) ?>">
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" maxlength="500"><?= htmlspecialchars($user['address']) ?></textarea>
                </div>

                <hr class="divider">
                <p class="text-sm text-muted" style="margin-bottom:.8rem">Change Password (optional)</p>

                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" id="newPw" maxlength="255"
                           placeholder="Leave blank to keep current" oninput="checkPw(this)">
                    <div class="pw-hint" id="pwRules" style="display:none">
                        <div class="pw-rule" id="p-len">✗ Minimum 8 characters</div>
                        <div class="pw-rule" id="p-alpha">✗ Contains letters</div>
                        <div class="pw-rule" id="p-num">✗ Contains numbers</div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Current Password * <span class="text-muted">(required to save changes)</span></label>
                    <input type="password" name="current_password" required maxlength="255"
                           placeholder="Enter your current password">
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
            </div>
        </div>

        <!-- ACCOUNT INFO + DELETE -->
        <div style="display:flex;flex-direction:column;gap:1.5rem">
            <div class="table-card">
                <div class="table-card-header"><h3>Account Summary</h3></div>
                <div style="padding:1.5rem">
                    <div class="sidebar-user" style="margin-bottom:1rem">
                        <div class="sidebar-avatar" style="width:52px;height:52px;font-size:1.3rem;color:var(--info)">
                            <?= strtoupper(substr($user['full_name'] ?: $user['username'], 0, 1)) ?>
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:1rem"><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></div>
                            <div class="text-sm text-muted"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                    </div>
                    <div style="display:grid;gap:.5rem">
                        <?php
                        $fields = [
                            'Username'  => $user['username'],
                            'Phone'     => $user['phone'] ?: '—',
                            'Address'   => $user['address'] ?: '—',
                        ];
                        foreach ($fields as $label => $value): ?>
                        <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border);font-size:.88rem">
                            <span class="text-muted"><?= $label ?></span>
                            <span style="font-weight:500;text-align:right;max-width:60%"><?= htmlspecialchars($value) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            </div>
        </div>
    </div>
</div>
</div>


<script>
function showModal(id) { document.getElementById(id).classList.add('active'); }
function hideModal(id) { document.getElementById(id).classList.remove('active'); }

function checkPw(input) {
    const v = input.value;
    const rules = document.getElementById('pwRules');
    rules.style.display = v ? 'block' : 'none';
    setRule('p-len',   v.length >= 8);
    setRule('p-alpha', /[A-Za-z]/.test(v));
    setRule('p-num',   /[0-9]/.test(v));
}
function setRule(id, ok) {
    const el = document.getElementById(id);
    el.textContent = (ok ? '✓ ' : '✗ ') + el.textContent.slice(2);
    el.className = 'pw-rule ' + (ok ? 'ok' : 'fail');
}
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('active'); });
});
</script>
</body>
</html>
