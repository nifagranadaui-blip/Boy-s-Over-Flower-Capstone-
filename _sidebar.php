<?php
// Determine current page for active nav
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar" style="background: #ffffff !important; border-right: 1px solid #e2e8f0 !important;">
    <div class="sidebar-logo" style="border-bottom: 1px solid #e2e8f0 !important;">
        <div class="logo-mark" style="color: #ffffff !important;">A</div>
        <div>
            <div class="logo-text" style="color: #121214 !important;">ALDiFOODS</div>
            <span class="logo-role">Admin </span>
        </div>
    </div>

    <nav>
        <div class="nav-section" style="color: #64748b !important;">Main</div>
        <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" style="color: #121214 !important;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </a>

        <div class="nav-section" style="color: #64748b !important;">Management</div>
        <a href="users.php" class="nav-link <?= $currentPage === 'users.php' ? 'active' : '' ?>" style="color: #121214 !important;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Users
        </a>
       
        <a href="batch_history.php" class="nav-link <?= $currentPage === 'batch_history.php' ? 'active' : '' ?>" style="color: #121214 !important;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            Batch History
        </a>
        <a href="analytics.php" class="nav-link <?= $currentPage === 'analytics.php' ? 'active' : '' ?>" style="color: #121214 !important;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
      </svg>
       Analytics
     </a>
       
        
    </nav>
    <div class="nav-section" style="color: #64748b !important;">Main</div>
        
    <div class="sidebar-footer" style="border-top: 1px solid #e2e8f0 !important;">
        <div class="sidebar-user">
            <div class="sidebar-avatar" style="background: #f1f3f5 !important; border: 1px solid #e2e8f0 !important;"><?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?></div>
            <div>
                <div class="sidebar-username" style="color: #121214 !important;"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></div>
                <div class="sidebar-role" style="color: #64748b !important;">Administrator</div>
            </div>
        </div>
        <a href="logout.php" class="btn btn-outline btn-sm w-full" style="justify-content:center; color: #121214 !important; border-color: #e2e8f0 !important;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Sign Out
        </a>
    </div>
</div>
