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
    $whereConditions[] = "(first_name LIKE ? OR father_name LIKE ? OR grandfather_name LIKE ? OR family_name LIKE ? OR id_number LIKE ? OR requester_first_name LIKE ? OR requester_father_name LIKE ? OR requester_grandfather_name LIKE ? OR requester_family_name LIKE ?)";
    $searchTerm = '%' . $_GET['search'] . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

// تصفية حسب الفرع العائلي
if (!empty($_GET['family_branch'])) {
    $whereConditions[] = "family_branch = ?";
    $params[] = $_GET['family_branch'];
}

// تصفية حسب المحافظة
if (!empty($_GET['governorate'])) {
    $whereConditions[] = "governorate = ?";
    $params[] = $_GET['governorate'];
}

// تصفية حسب سبب الوفاة
if (!empty($_GET['death_reason'])) {
    $whereConditions[] = "death_reason = ?";
    $params[] = $_GET['death_reason'];
}

// تصفية حسب التاريخ
if (!empty($_GET['date_from'])) {
    $whereConditions[] = "DATE(created_at) >= ?";
    $params[] = $_GET['date_from'];
}

if (!empty($_GET['date_to'])) {
    $whereConditions[] = "DATE(created_at) <= ?";
    $params[] = $_GET['date_to'];
}

// تصفية حسب العناصر المحددة
if (!empty($_GET['selected_ids'])) {
    $selectedIds = explode(',', $_GET['selected_ids']);
    $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
    $whereConditions[] = "id IN ($placeholders)";
    $params = array_merge($params, $selectedIds);
}

$query = "SELECT * FROM deaths";
if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(" AND ", $whereConditions);
}
$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$deaths = $stmt->fetchAll(PDO::FETCH_ASSOC);

// إنشاء ملف CSV
$filename = 'الوفيات_' . date('Y-m-d') . '.csv';
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
    'المحافظة',
    'الفرع العائلي',
    'سبب الوفاة',
    'اسم مدخل الطلب',
    'اسم أب مدخل الطلب',
    'اسم جد مدخل الطلب',
    'اسم عائلة مدخل الطلب',
    'صلة مدخل الطلب',
    'تاريخ التسجيل'
], ',');

// كتابة البيانات
foreach ($deaths as $death) {
    fputcsv($output, [
        $death['id'],
        $death['first_name'],
        $death['father_name'],
        $death['grandfather_name'],
        $death['family_name'],
        $death['id_number'],
        $death['birth_date'],
        $death['governorate'],
        $death['family_branch'],
        $death['death_reason'],
        $death['requester_first_name'],
        $death['requester_father_name'],
        $death['requester_grandfather_name'],
        $death['requester_family_name'],
        $death['requester_relationship'],
        $death['created_at']
    ], ',');
}

fclose($output);
exit;
?>
