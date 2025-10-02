<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    exit('غير مصرح');
}

$admin_id = $_GET['id'] ?? '';

if (empty($admin_id)) {
    http_response_code(400);
    exit('معرف المشرف مطلوب');
}

// الاتصال بقاعدة البيانات
try {
    $pdo = new PDO('mysql:host=localhost;dbname=family_orphans_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    exit('خطأ في الاتصال بقاعدة البيانات');
}

// جلب تفاصيل المشرف
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    http_response_code(404);
    exit('المشرف غير موجود');
}

// إرجاع البيانات كـ JSON
header('Content-Type: application/json');
echo json_encode($admin);
?>









