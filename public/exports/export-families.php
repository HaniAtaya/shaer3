<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    exit('غير مصرح');
}

// الاتصال بقاعدة البيانات
try {
    $pdo = new PDO('mysql:host=localhost;dbname=family_orphans_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage());
}

// معاملات التصدير
$export_format = $_GET['export'] ?? 'excel';
$selected_ids = $_GET['ids'] ?? '';

// معاملات البحث والفلترة
$search = $_GET['search'] ?? '';
$governorate = $_GET['governorate'] ?? '';
$gender = $_GET['gender'] ?? '';
$health_status = $_GET['health_status'] ?? '';
$marital_status = $_GET['marital_status'] ?? '';

// بناء استعلام البحث
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(CONCAT(first_name, ' ', father_name, ' ', grandfather_name, ' ', family_name) LIKE ? OR id_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($governorate)) {
    $where_conditions[] = "original_governorate = ?";
    $params[] = $governorate;
}

if (!empty($gender)) {
    $where_conditions[] = "gender = ?";
    $params[] = $gender;
}

if (!empty($health_status)) {
    $where_conditions[] = "health_status = ?";
    $params[] = $health_status;
}

if (!empty($marital_status)) {
    $where_conditions[] = "marital_status = ?";
    $params[] = $marital_status;
}

// إذا تم تحديد عائلات معينة
if (!empty($selected_ids)) {
    $ids_array = explode(',', $selected_ids);
    $ids_array = array_filter($ids_array, 'is_numeric');
    if (!empty($ids_array)) {
        $placeholders = str_repeat('?,', count($ids_array) - 1) . '?';
        $where_conditions[] = "id IN ($placeholders)";
        $params = array_merge($params, $ids_array);
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// جلب البيانات
$sql = "SELECT * FROM families $where_clause ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$families = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ترجمة القيم
$gender_labels = ['male' => 'ذكر', 'female' => 'أنثى'];
$health_labels = ['healthy' => 'سليم', 'hypertension' => 'ضغط', 'diabetes' => 'سكري', 'other' => 'أخرى'];
$marital_labels = [
    'married' => 'متزوج', 'divorced' => 'مطلق', 'widowed' => 'أرمل',
    'elderly' => 'مسن', 'provider' => 'معيل', 'special_needs' => 'احتياجات خاصة'
];
$governorate_labels = [
    'gaza' => 'غزة', 'khan_younis' => 'خان يونس', 'rafah' => 'رفح',
    'middle' => 'الوسطى', 'north_gaza' => 'شمال غزة'
];
$housing_labels = ['tent' => 'خيمة', 'apartment' => 'شقة', 'house' => 'منزل', 'school' => 'مدرسة'];

if ($export_format === 'excel') {
    // تصدير Excel
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="عائلات_' . date('Y-m-d_H-i-s') . '.xls"');
    
    echo '<html dir="rtl">';
    echo '<head><meta charset="utf-8"></head>';
    echo '<body>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr style="background-color: #f0f0f0; font-weight: bold;">';
    echo '<th>#</th>';
    echo '<th>اسم رب الأسرة</th>';
    echo '<th>رقم الهوية</th>';
    echo '<th>الجنس</th>';
    echo '<th>تاريخ الميلاد</th>';
    echo '<th>الفرع العائلي</th>';
    echo '<th>الهاتف الأساسي</th>';
    echo '<th>الهاتف البديل</th>';
    echo '<th>الحالة الصحية</th>';
    echo '<th>الحالة الاجتماعية</th>';
    echo '<th>المحافظة الأصلية</th>';
    echo '<th>المنطقة الأصلية</th>';
    echo '<th>المحافظة الحالية</th>';
    echo '<th>المنطقة الحالية</th>';
    echo '<th>حالة السكن</th>';
    echo '<th>عدد أفراد الأسرة</th>';
    echo '<th>تاريخ التسجيل</th>';
    echo '</tr>';
    
    foreach ($families as $index => $family) {
        echo '<tr>';
        echo '<td>' . ($index + 1) . '</td>';
        echo '<td>' . htmlspecialchars($family['first_name'] . ' ' . $family['father_name'] . ' ' . $family['grandfather_name'] . ' ' . $family['family_name']) . '</td>';
        echo '<td>' . htmlspecialchars($family['id_number']) . '</td>';
        echo '<td>' . ($gender_labels[$family['gender']] ?? $family['gender']) . '</td>';
        echo '<td>' . date('Y-m-d', strtotime($family['birth_date'])) . '</td>';
        echo '<td>' . htmlspecialchars($family['family_branch']) . '</td>';
        echo '<td>' . htmlspecialchars($family['primary_phone']) . '</td>';
        echo '<td>' . htmlspecialchars($family['secondary_phone'] ?? '') . '</td>';
        echo '<td>' . ($health_labels[$family['health_status']] ?? $family['health_status']) . '</td>';
        echo '<td>' . ($marital_labels[$family['marital_status']] ?? $family['marital_status']) . '</td>';
        echo '<td>' . ($governorate_labels[$family['original_governorate']] ?? $family['original_governorate']) . '</td>';
        echo '<td>' . htmlspecialchars($family['original_area']) . '</td>';
        echo '<td>' . ($governorate_labels[$family['displacement_governorate']] ?? $family['displacement_governorate']) . '</td>';
        echo '<td>' . htmlspecialchars($family['displacement_area']) . '</td>';
        echo '<td>' . ($housing_labels[$family['housing_status']] ?? $family['housing_status']) . '</td>';
        echo '<td>' . $family['family_members_count'] . '</td>';
        echo '<td>' . date('Y-m-d H:i', strtotime($family['created_at'])) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body></html>';
    
} elseif ($export_format === 'csv') {
    // تصدير CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="عائلات_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // إضافة BOM للدعم الصحيح للعربية في Excel
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // رؤوس الأعمدة
    fputcsv($output, [
        '#', 'اسم رب الأسرة', 'رقم الهوية', 'الجنس', 'تاريخ الميلاد',
        'الفرع العائلي', 'الهاتف الأساسي', 'الهاتف البديل', 'الحالة الصحية',
        'الحالة الاجتماعية', 'المحافظة الأصلية', 'المنطقة الأصلية',
        'المحافظة الحالية', 'المنطقة الحالية', 'حالة السكن', 'عدد أفراد الأسرة', 'تاريخ التسجيل'
    ]);
    
    // البيانات
    foreach ($families as $index => $family) {
        fputcsv($output, [
            $index + 1,
            $family['first_name'] . ' ' . $family['father_name'] . ' ' . $family['grandfather_name'] . ' ' . $family['family_name'],
            $family['id_number'],
            $gender_labels[$family['gender']] ?? $family['gender'],
            date('Y-m-d', strtotime($family['birth_date'])),
            $family['family_branch'],
            $family['primary_phone'],
            $family['secondary_phone'] ?? '',
            $health_labels[$family['health_status']] ?? $family['health_status'],
            $marital_labels[$family['marital_status']] ?? $family['marital_status'],
            $governorate_labels[$family['original_governorate']] ?? $family['original_governorate'],
            $family['original_area'],
            $governorate_labels[$family['displacement_governorate']] ?? $family['displacement_governorate'],
            $family['displacement_area'],
            $housing_labels[$family['housing_status']] ?? $family['housing_status'],
            $family['family_members_count'],
            date('Y-m-d H:i', strtotime($family['created_at']))
        ]);
    }
    
    fclose($output);
    
} else {
    // تصدير PDF (يتطلب مكتبة TCPDF أو مشابهة)
    // يمكن إضافة هذا لاحقاً
    header('Content-Type: text/html; charset=utf-8');
    echo '<h2>تصدير PDF غير متاح حالياً</h2>';
    echo '<p>يرجى استخدام تصدير Excel أو CSV</p>';
}
?>
