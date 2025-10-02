<?php
// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin/auth/admin-login.php');
    exit;
}
?>
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4><i class="fas fa-shield-alt me-2"></i>لوحة الإدارة</h4>
    </div>
    <nav class="sidebar-menu">
        <a href="../admin/pages/admin-dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin-dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt me-2"></i>
            <span class="menu-text">الرئيسية</span>
        </a>
        <a href="../admin/management/admin-families.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin-families.php' ? 'active' : ''; ?>">
            <i class="fas fa-users me-2"></i>
            <span class="menu-text">إدارة الأسر</span>
        </a>
        <a href="../admin/management/admin-family-members.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin-family-members.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-friends me-2"></i>
            <span class="menu-text">إدارة الأبناء</span>
        </a>
        <a href="../admin/management/admin-orphans.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin-orphans.php' ? 'active' : ''; ?>">
            <i class="fas fa-child me-2"></i>
            <span class="menu-text">إدارة الأيتام</span>
        </a>
        <a href="../admin/management/admin-infants.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin-infants.php' ? 'active' : ''; ?>">
            <i class="fas fa-baby me-2"></i>
            <span class="menu-text">إدارة الرضع</span>
        </a>
        <a href="../admin/management/admin-deaths.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin-deaths.php' ? 'active' : ''; ?>">
            <i class="fas fa-cross me-2"></i>
            <span class="menu-text">إدارة الوفيات</span>
        </a>
        <a href="../admin/management/admin-admins.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin-admins.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-shield me-2"></i>
            <span class="menu-text">إدارة المشرفين</span>
        </a>
        <a href="../public/lists/lists.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'lists.php' ? 'active' : ''; ?>">
            <i class="fas fa-list-alt me-2"></i>
            <span class="menu-text">القوائم</span>
        </a>
        <a href="../admin/reports/admin-reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin-reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar me-2"></i>
            <span class="menu-text">التقارير</span>
        </a>
        <a href="../admin/management/admin-family-accounts.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin-family-accounts.php' ? 'active' : ''; ?>">
            <i class="fas fa-users-cog me-2"></i>
            <span class="menu-text">حسابات الأسر</span>
        </a>
        <a href="../admin/reports/admin-update-logs.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin-update-logs.php' ? 'active' : ''; ?>">
            <i class="fas fa-history me-2"></i>
            <span class="menu-text">سجل التحديثات</span>
        </a>
        <a href="../admin/settings/admin-settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin-settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog me-2"></i>
            <span class="menu-text">الإعدادات</span>
        </a>
        <hr class="my-3" style="border-color: rgba(255,255,255,0.1);">
        <a href="../admin/auth/admin-logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt me-2"></i>
            <span class="menu-text">تسجيل الخروج</span>
        </a>
    </nav>
</div>

<!-- زر السكرول للأعلى -->
<button id="scrollToTop" class="scroll-to-top" title="العودة للأعلى">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <button class="toggle-sidebar" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="admin-info">
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?>
                </div>
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></div>
                    <small class="text-muted"><?php echo $_SESSION['admin_role'] === 'super_admin' ? 'مشرف رئيسي' : 'مشرف'; ?></small>
                </div>
            </div>
        </div>
    </nav>
