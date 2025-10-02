<?php
session_start();

require_once '../../includes/db_connection.php';
require_once '../../includes/device-tracker.php';

// تسجيل عملية تسجيل الخروج قبل تدمير الجلسة
if (isset($_SESSION['family_id'])) {
    logLogout($pdo, $_SESSION['family_id']);
}

// مسح جميع متغيرات الجلسة
$_SESSION = array();

// تدمير ملف الكوكيز
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// تدمير الجلسة
session_destroy();

// توجيه لصفحة تسجيل الدخول
header('Location: family-login.php?message=logged_out');
exit;
?>
