<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AldiFoods — AldiFoods PHP Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
<div class="auth-card" style="max-width:480px;text-align:center">
    <div style="margin-bottom:1.5rem">
        <div style="width:64px;height:64px;background:var(--accent);border-radius:14px;display:grid;place-items:center;font-family:'Syne',sans-serif;font-weight:800;font-size:1.8rem;color:#000;margin:0 auto 1rem">A</div>
        <h1 style="font-size:2rem;margin-bottom:.4rem">AldiFoods</h1>
        <p class="text-muted">AldiFoods PHP Management System</p>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem">
        <div style="background:var(--bg3);border:1px solid var(--border);border-radius:12px;padding:1.5rem">
            <div style="font-size:2rem;margin-bottom:.8rem">🔑</div>
            <div style="font-family:'Syne',sans-serif;font-weight:700;margin-bottom:.4rem">Admin Portal</div>
            <p class="text-sm text-muted" style="margin-bottom:1rem">Manage users, Raw Materials & system settings</p>
            <a href="admin/login.php" class="btn btn-primary btn-sm" style="width:100%;justify-content:center">Admin Login</a>
        </div>
        <div style="background:var(--bg3);border:1px solid var(--border);border-radius:12px;padding:1.5rem">
            <div style="font-size:2rem;margin-bottom:.8rem">👤</div>
            <div style="font-family:'Syne',sans-serif;font-weight:700;margin-bottom:.4rem">User Portal</div>
            <p class="text-sm text-muted" style="margin-bottom:1rem">Browse Raw Materials & manage your account</p>
            <a href="user/login.php" class="btn btn-primary btn-sm" style="width:100%;justify-content:center;background:var(--info)">User Login</a>
        </div>
    </div>

    <div class="alert alert-info" style="text-align:left;font-size:.82rem">
        <strong>🔐 Security Features:</strong><br>
        AES-256-CBC encryption · Bcrypt passwords · CAPTCHA · CSRF tokens · Prepared statements · Input sanitization
    </div>

    <div class="text-sm text-muted mt-2">
        Default admin: <strong>admin</strong> / <strong>Admin123</strong><br>
        Don't have a user account? <a href="user/register.php" class="link">Register here</a>
    </div>
</div>
</div>
</body>
</html>
