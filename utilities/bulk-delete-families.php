<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

// التحقق من الطريقة POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-families.php');
    exit;
}

$family_ids = $_POST['family_ids'] ?? '';

if (empty($family_ids)) {
    $_SESSION['error_message'] = 'لم يتم تحديد أي عائلات للحذف';
    header('Location: admin-families.php');
    exit;
}

// الاتصال بقاعدة البيانات
try {
    $pdo = new PDO('mysql:host=localhost;dbname=family_orphans_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
    header('Location: admin-families.php');
    exit;
}

// تقسيم معرفات العائلات
$ids_array = explode(',', $family_ids);
$ids_array = array_filter($ids_array, 'is_numeric'); // التأكد من أن القيم رقمية

if (empty($ids_array)) {
    $_SESSION['error_message'] = 'معرفات العائلات غير صحيحة';
    header('Location: admin-families.php');
    exit;
}

try {
    // بدء المعاملة
    $pdo->beginTransaction();
    
    $deleted_count = 0;
    $errors = [];
    
    foreach ($ids_array as $family_id) {
        try {
            // حذف أفراد العائلة أولاً
            $stmt = $pdo->prepare("DELETE FROM family_members WHERE family_id = ?");
            $stmt->execute([$family_id]);
            
            // حذف العائلة
            $stmt = $pdo->prepare("DELETE FROM families WHERE id = ?");
            $stmt->execute([$family_id]);
            
            if ($stmt->rowCount() > 0) {
                $deleted_count++;
            }
        } catch (PDOException $e) {
            $errors[] = "خطأ في حذف العائلة رقم $family_id: " . $e->getMessage();
        }
    }
    
    // تأكيد المعاملة
    $pdo->commit();
    
    if ($deleted_count > 0) {
        $_SESSION['success_message'] = "تم حذف $deleted_count عائلة بنجاح";
    }
    
    if (!empty($errors)) {
        $_SESSION['warning_message'] = implode('<br>', $errors);
    }
    
} catch (PDOException $e) {
    // إلغاء المعاملة في حالة الخطأ
    $pdo->rollBack();
    $_SESSION['error_message'] = 'خطأ في حذف العائلات: ' . $e->getMessage();
}

header('Location: admin-families.php');
exit;
?>
