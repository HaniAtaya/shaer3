<?php
session_start();

// تدمير جميع بيانات الجلسة
session_destroy();

// إعادة توجيه إلى صفحة تسجيل الدخول
header('Location: admin-login.php');
exit;
?>