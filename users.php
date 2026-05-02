<?php
require_once '../includes/db.php';
requireAdminLogin();

$db = getDB();
$error = '';
$success = '';

// -------------------------------------------------------
// CREATE USER
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $username  = sanitizeInput($_POST['username'] ?? '');
        $email     = sanitizeEmail($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $full_name = sanitizeInput($_POST['full_name'] ?? '');

        if (empty($username) || empty($email) || empty($password)) {
            $error = 'Username, email and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (!validatePassword($password)) {
            $error = 'Password must be at least 8 characters and contain both letters and numbers.';
        } else {
            $chk = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $chk->bind_param('s', $username);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $error = 'Username already taken.';
            } else {
                $emailExists = false;
                $all = $db->query('SELECT email FROM users');
                while ($r = $all->fetch_assoc()) {
                    if (decryptData($r['email']) === $email) { $emailExists = true; break; }
                }
                
                if ($emailExists) {
                    $error = 'Email already registered.';
                } else {
                    $pwHash       = hashPassword($password);
                    $encEmail     = encryptData($email);
                    $encFullName  = encryptData($full_name);

                    $stmt = $db->prepare('INSERT INTO users (username, email, password_hash, full_name) VALUES (?,?,?,?)');
                    $stmt->bind_param('ssss', $username, $encEmail, $pwHash, $encFullName);
                    if ($stmt->execute()) {
                        $success = "User '{$username}' created successfully.";
                    } else {
                        $error = 'Database error: ' . $db->error;
                    }
                    $stmt->close();
                }
            }
            $chk->close();
        }
    }
}

// -------------------------------------------------------
// UPDATE USER - FIXED LOGIC
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $id        = (int)($_POST['user_id'] ?? 0);
        $email     = sanitizeEmail($_POST['email'] ?? '');
        $full_name = sanitizeInput($_POST['full_name'] ?? '');
        $password  = $_POST['password'] ?? '';

        if ($id <= 0) { 
            $error = 'Invalid user ID.'; 
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
            $error = 'Invalid email.'; 
        } else {
            $encEmail    = encryptData($email);
            $encFullName = encryptData($full_name);

            if (!empty($password)) {
                if (!validatePassword($password)) {
                    $error = 'New password must be ≥8 chars, alphanumeric.';
                } else {
                    $pwHash = hashPassword($password);
                    $stmt = $db->prepare('UPDATE users SET email=?, full_name=?, password_hash=? WHERE id=?');
                    $stmt->bind_param('sssi', $encEmail, $encFullName, $pwHash, $id);
                    $stmt->execute(); 
                    $stmt->close();
                    $success = 'User updated successfully with new password.';
                }
            } else {
                $stmt = $db->prepare('UPDATE users SET email=?, full_name=? WHERE id=?');
                $stmt->bind_param('ssi', $encEmail, $encFullName, $id);
                $stmt->execute(); 
                $stmt->close();
                $success = 'User details updated successfully.';
            }
        }
    }
}

// -------------------------------------------------------
// REMOVE USER - FIXED LOGIC
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $success = 'User removed from system.';
            } else {
                $error = 'Failed to remove user.';
            }
            $stmt->close();
        }
    }
}

// -------------------------------------------------------
// FETCH ALL USERS
// -------------------------------------------------------
$search = sanitizeInput($_GET['q'] ?? '');
$users  = [];
$result = $db->query('SELECT id, username, email, full_name, created_at FROM users ORDER BY created_at DESC');
while ($row = $result->fetch_assoc()) {
    $row['email']     = decryptData($row['email']);
    $row['full_name'] = decryptData($row['full_name']);
    
    if ($search && stripos($row['username'] . $row['email'] . $row['full_name'], $search) === false) continue;
    $users[] = $row;
}

$csrf = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users — BorroShop Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
    <?php include '_sidebar.php'; ?>
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Users</h1>
                <div class="breadcrumb">Manage registered users</div>
            </div>
            <button class="btn btn-primary" onclick="showModal('createModal')" style="width:auto">+ Add User</button>
        </div>

        <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr><th>#</th><th>Username</th><th>Email</th><th>Full Name</th><th>Registered</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="text-muted"><?= $u['id'] ?></td>
                        <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['full_name'] ?: '—') ?></td>
                        <td class="text-sm text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <div class="flex gap-1">
                                <button class="btn btn-info btn-sm" onclick='openUpdate(<?= json_encode($u) ?>)'>Update</button>
                                <button class="btn btn-danger btn-sm" onclick="confirmRemove(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')">Remove</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="createModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Create New User</h3>
            <button class="modal-close" onclick="hideModal('createModal')">✕</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="create">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required oninput="checkPw(this,'createRules')">
                    <div class="pw-hint" id="createRules">
                        <div class="pw-rule" id="cr-len">✗ Minimum 8 characters</div>
                        <div class="pw-rule" id="cr-alpha">✗ Contains letters</div>
                        <div class="pw-rule" id="cr-num">✗ Contains numbers</div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="hideModal('createModal')">Cancel</button>
                <button type="submit" class="btn btn-success">Create User</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="updateModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Update User Details</h3>
            <button class="modal-close" onclick="hideModal('updateModal')">✕</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" id="updateUserId">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="updateUsername" disabled style="opacity:.6; cursor:not-allowed">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="updateEmail" required>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" id="updateFullName">
                </div>
                <div class="form-group">
                    <label>New Password <small>(leave blank to keep current)</small></label>
                    <input type="password" name="password" oninput="checkPw(this,'updateRules')">
                    <div class="pw-hint" id="updateRules" style="display:none">
                        <div class="pw-rule" id="ur-len">✗ Minimum 8 characters</div>
                        <div class="pw-rule" id="ur-alpha">✗ Contains letters</div>
                        <div class="pw-rule" id="ur-num">✗ Contains numbers</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="hideModal('updateModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="removeModal">
    <div class="modal" style="max-width:400px">
        <div class="modal-header">
            <h3>Confirm Removal</h3>
            <button class="modal-close" onclick="hideModal('removeModal')">✕</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <p>Are you sure you want to remove user <strong id="removeUsername"></strong>?</p>
                <p class="text-sm text-muted">This action cannot be undone.</p>
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="removeUserId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="hideModal('removeModal')">Cancel</button>
                <button type="submit" class="btn btn-danger">Remove User</button>
            </div>
        </form>
    </div>
</div>

<script>
function showModal(id) { document.getElementById(id).classList.add('active'); }
function hideModal(id) { document.getElementById(id).classList.remove('active'); }

function openUpdate(user) {
    document.getElementById('updateUserId').value   = user.id;
    document.getElementById('updateUsername').value = user.username;
    document.getElementById('updateEmail').value    = user.email;
    document.getElementById('updateFullName').value = user.full_name || '';
    showModal('updateModal');
}

function confirmRemove(id, name) {
    document.getElementById('removeUserId').value = id;
    document.getElementById('removeUsername').textContent = name;
    showModal('removeModal');
}

function checkPw(input, rulesId) {
    const v = input.value;
    const rules = document.getElementById(rulesId);
    rules.style.display = (v.length > 0) ? 'block' : 'none';
    
    const prefix = (rulesId === 'createRules') ? 'cr-' : 'ur-';
    setRule(prefix + 'len',   v.length >= 8);
    setRule(prefix + 'alpha', /[A-Za-z]/.test(v));
    setRule(prefix + 'num',   /[0-9]/.test(v));
}

function setRule(id, ok) {
    const el = document.getElementById(id);
    if(!el) return;
    el.textContent = (ok ? '✓ ' : '✗ ') + el.textContent.slice(2);
    el.className = 'pw-rule ' + (ok ? 'ok' : 'fail');
}

document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('active'); });
});
</script>
</body>
</html>