<?php
// تحديد الصفحة النشطة
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4 class="sidebar-brand">
            <i class="fas fa-users-cog me-2"></i>
            <span>لوحة الإدارة</span>
        </h4>
    </div>
    <nav class="sidebar-menu">
        <a class="nav-link <?php echo $current_page === 'admin-dashboard.php' ? 'active' : ''; ?>" href="admin-dashboard.php">
            <i class="fas fa-home me-2"></i>
            <span>الرئيسية</span>
        </a>
        <a class="nav-link <?php echo $current_page === 'admin-families.php' ? 'active' : ''; ?>" href="admin-families.php">
            <i class="fas fa-users me-2"></i>
            <span>إدارة الأسر</span>
        </a>
        <a class="nav-link <?php echo $current_page === 'admin-orphans.php' ? 'active' : ''; ?>" href="admin-orphans.php">
            <i class="fas fa-child me-2"></i>
            <span>إدارة الأيتام</span>
        </a>
        <a class="nav-link <?php echo $current_page === 'admin-reports.php' ? 'active' : ''; ?>" href="admin-reports.php">
            <i class="fas fa-chart-bar me-2"></i>
            <span>التقارير المتقدمة</span>
        </a>
        <a class="nav-link <?php echo $current_page === 'admin-settings.php' ? 'active' : ''; ?>" href="admin-settings.php">
            <i class="fas fa-cog me-2"></i>
            <span>الإعدادات</span>
        </a>
        <a class="nav-link <?php echo $current_page === 'admin-admins.php' ? 'active' : ''; ?>" href="admin-admins.php">
            <i class="fas fa-users-cog me-2"></i>
            <span>إدارة المشرفين</span>
        </a>
        <a class="nav-link" href="admin-logout.php">
            <i class="fas fa-sign-out-alt me-2"></i>
            <span>تسجيل الخروج</span>
        </a>
    </nav>
</div>

<!-- Toggle Button -->
<button class="toggle-sidebar" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>
