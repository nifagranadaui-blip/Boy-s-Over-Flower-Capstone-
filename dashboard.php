<?php
require_once '../includes/db.php';
requireAdminLogin();

$db = getDB();

// Fetch stats
$userCount    = $db->query('SELECT COUNT(*) AS c FROM users')->fetch_assoc()['c'];
$recentUsers  = $db->query('SELECT id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — ALDiFOODS Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
<?php include '_sidebar.php'; ?>
<div class="main-content">

    <div class="page-header">
        <div>
            <h1>Dashboard</h1>
            <div class="breadcrumb">Overview of your store</div>
        </div>
        <div style="font-size:.82rem; color:var(--muted)"><?= date('l, F j, Y') ?></div>
    </div>

    <div class="stats-grid">
        <div class="stat-card amber">
            <div class="stat-label">Total Users</div>
            <div class="stat-value"><?= number_format($userCount) ?></div>
        </div>
        <div class="stat-card red">
            <div class="stat-label">Admins Online</div>
            <div class="stat-value">1</div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <h3>Recently Registered Users</h3>
            <a href="users.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($recentUsers->num_rows === 0): ?>
                <tr><td colspan="5"><div class="empty-state"><p>No users registered yet.</p></div></td></tr>
            <?php else: ?>
            <?php while ($u = $recentUsers->fetch_assoc()): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                    <td><?= htmlspecialchars(decryptData($u['email'])) ?></td>
                    <td><span class="text-muted text-sm"><?= date('M j, Y', strtotime($u['created_at'])) ?></span></td>
                    <td>
                        <a href="users.php?view=<?= $u['id'] ?>" class="btn btn-info btn-sm">View</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="flex gap-2" style="flex-wrap:wrap">
        <a href="users.php" class="btn btn-secondary" style="flex:1;min-width:200px;justify-content:center">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            Manage Users
        </a>
    </div>

</div>
</div>
</body>
</html>
