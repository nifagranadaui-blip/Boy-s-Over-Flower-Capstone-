<?php
require_once '../includes/db.php';
requireUserLogin();

$db = getDB();
$userId = $_SESSION['user_id'] ?? 0;

// 1. Fetch user info
$stmt = $db->prepare('SELECT username, email, full_name, phone, address, created_at FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Decrypt sensitive data
$user['email']     = decryptData($user['email'] ?? '');
$user['full_name'] = decryptData($user['full_name'] ?? '');
$user['phone']     = decryptData($user['phone'] ?? '');
$user['address']   = decryptData($user['address'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — ALDiFOODS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
    <?php include '_sidebar.php'; ?>
    
    <div class="main-content">
        <!-- PAGE HEADER -->
        <div class="page-header">
            <div>
                <h1>Hello, <span class="text-accent"><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></span> 👋</h1>
                <div class="breadcrumb">Employee Dashboard — ALDiFOODS Corp. Portal</div>
            </div>
        </div>

        <!-- STATS GRID -->
        <div class="stats-grid">
            <div class="stat-card green">
                <div class="stat-label">Account Status</div>
                <div class="stat-value">● Active</div>
            </div>
            <div class="stat-card amber">
                <div class="stat-label">Member Since</div>
                <div class="stat-value"><?= date('M Y', strtotime($user['created_at'] ?: 'now')) ?></div>
            </div>
            <div class="stat-card blue">
                <div class="stat-label">Current Time</div>
                <div class="stat-value" id="automated-clock">🕒 --:-- --</div>
            </div>
        </div>

        <!-- ACCOUNT INFORMATION INFO CARD -->
        <div class="table-card" style="max-width: 800px;">
            <div class="table-card-header">
                <h3>Account Information</h3>
                <a href="profile.php" class="btn btn-outline btn-sm">Edit Profile</a>
            </div>
            <div style="padding: 1.5rem;">
                <div class="sidebar-user" style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 1.5rem;">
                    <div class="sidebar-avatar" style="width: 52px; height: 52px; font-size: 1.3rem; color: var(--accent); display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.03); border-radius: 50%;">
                        <?= strtoupper(substr($user['full_name'] ?: $user['username'], 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight: 700; font-size: 1.1rem; color: var(--text);"><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></div>
                        <div class="text-sm text-muted"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                </div>

                <div style="display: grid; gap: .5rem;">
                    <?php
                    $dashboardFields = [
                        'Username'      => $user['username'],
                        'Email Address' => $user['email'],
                        'Full Name'     => $user['full_name'] ?: 'Not Provided',
                        'Phone'         => $user['phone'] ?: '—',
                        'Address'       => $user['address'] ?: '—'
                    ];
                    foreach ($dashboardFields as $label => $value): ?>
                    <div style="display: flex; justify-content: space-between; padding: .75rem 0; border-bottom: 1px solid var(--border); font-size: .88rem;">
                        <span class="text-muted" style="font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;"><?= $label ?></span>
                        <span style="font-weight: 500; text-align: right; max-width: 60%; color: var(--text);"><?= htmlspecialchars($value) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- AUTOMATED REAL-TIME ENGINE -->
<script>
function startAutomatedClock() {
    const clockElement = document.getElementById('automated-clock');
    
    function updateTime() {
        const now = new Date();
        let hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        
        hours = hours % 12;
        hours = hours ? hours : 12; // Formats midnight (0) to 12
        const hoursStr = String(hours).padStart(2, '0');
        
        clockElement.textContent = `🕒 ${hoursStr}:${minutes} ${ampm}`;
    }
    
    updateTime();
    setInterval(updateTime, 1000); // Ticks dynamically every second
}

document.addEventListener('DOMContentLoaded', startAutomatedClock);
</script>
</body>
</html>
