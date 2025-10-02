<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

// الاتصال بقاعدة البيانات
try {
    $pdo = new PDO('mysql:host=localhost;dbname=family_orphans_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage());
}

// جلب البيانات مع إمكانية التصفية
$whereConditions = [];
$params = [];

// تصفية حسب البحث
if (!empty($_GET['search'])) {
    $whereConditions[] = "(first_name LIKE ? OR father_name LIKE ? OR grandfather_name LIKE ? OR family_name LIKE ? OR id_number LIKE ? OR primary_phone LIKE ? OR secondary_phone LIKE ?)";
    $searchTerm = '%' . $_GET['search'] . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

// تصفية حسب الفرع العائلي
if (!empty($_GET['family_branch'])) {
    $whereConditions[] = "family_branch = ?";
    $params[] = $_GET['family_branch'];
}

// تصفية حسب المحافظة
if (!empty($_GET['governorate'])) {
    $whereConditions[] = "displacement_governorate = ?";
    $params[] = $_GET['governorate'];
}

// تصفية حسب العمر
if (!empty($_GET['age_filter'])) {
    $ageFilter = $_GET['age_filter'];
    $now = new DateTime();
    
    switch ($ageFilter) {
        case '0-6':
            $whereConditions[] = "birth_date >= ?";
            $params[] = $now->modify('-6 months')->format('Y-m-d');
            break;
        case '6-12':
            $whereConditions[] = "birth_date >= ? AND birth_date < ?";
            $params[] = $now->modify('-12 months')->format('Y-m-d');
            $params[] = $now->modify('+6 months')->format('Y-m-d');
            break;
        case '1-2':
            $whereConditions[] = "birth_date >= ? AND birth_date < ?";
            $params[] = $now->modify('-24 months')->format('Y-m-d');
            $params[] = $now->modify('+12 months')->format('Y-m-d');
            break;
    }
}

// تصفية حسب العناصر المحددة
if (!empty($_GET['selected_ids'])) {
    $selectedIds = explode(',', $_GET['selected_ids']);
    $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
    $whereConditions[] = "id IN ($placeholders)";
    $params = array_merge($params, $selectedIds);
}

$query = "SELECT * FROM infants";
if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(" AND ", $whereConditions);
}
$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$infants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// إنشاء ملف CSV
$filename = 'الرضع_' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// إضافة BOM للدعم الصحيح للعربية
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// كتابة العناوين
fputcsv($output, [
    'الرقم التسلسلي',
    'الاسم الشخصي',
    'اسم الأب',
    'اسم الجد',
    'اسم العائلة',
    'رقم الهوية',
    'تاريخ الميلاد',
    'العمر (شهر)',
    'الفرع العائلي',
    'الهاتف الأساسي',
    'الهاتف البديل',
    'المحافظة',
    'المنطقة',
    'الحي',
    'تاريخ التسجيل'
], ',');

// كتابة البيانات
foreach ($infants as $infant) {
    $birthDate = new DateTime($infant['birth_date']);
    $now = new DateTime();
    $ageInMonths = $now->diff($birthDate)->m + ($now->diff($birthDate)->y * 12);
    
    fputcsv($output, [
        $infant['id'],
        $infant['first_name'],
        $infant['father_name'],
        $infant['grandfather_name'],
        $infant['family_name'],
        $infant['id_number'],
        $infant['birth_date'],
        $ageInMonths,
        $infant['family_branch'],
        $infant['primary_phone'],
        $infant['secondary_phone'],
        $infant['displacement_governorate'],
        $infant['displacement_area'],
        $infant['displacement_neighborhood'],
        $infant['created_at']
    ], ',');
}

fclose($output);
exit;
?>
