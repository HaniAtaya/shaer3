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

// إنشاء HTML للطباعة
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الرضع - الشاعر عائلتي</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            direction: rtl;
            margin: 0;
            padding: 20px;
            background: white;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #333;
            margin: 0;
            font-size: 28px;
        }
        .header p {
            color: #666;
            margin: 5px 0;
            font-size: 16px;
        }
        .info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info p {
            margin: 5px 0;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>الشاعر عائلتي</h1>
        <p>تقرير بيانات الرضع</p>
        <p>تاريخ التقرير: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

    <div class="info">
        <p><strong>إجمالي عدد الرضع:</strong> <?php echo count($infants); ?></p>
        <p><strong>تاريخ إنشاء التقرير:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>الرقم</th>
                <th>الاسم الكامل</th>
                <th>رقم الهوية</th>
                <th>تاريخ الميلاد</th>
                <th>العمر (شهر)</th>
                <th>الفرع العائلي</th>
                <th>الهاتف الأساسي</th>
                <th>المحافظة</th>
                <th>تاريخ التسجيل</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($infants as $infant): ?>
                <?php
                $birthDate = new DateTime($infant['birth_date']);
                $now = new DateTime();
                $ageInMonths = $now->diff($birthDate)->m + ($now->diff($birthDate)->y * 12);
                $fullName = $infant['first_name'] . ' ' . $infant['father_name'] . ' ' . $infant['grandfather_name'] . ' ' . $infant['family_name'];
                ?>
                <tr>
                    <td><?php echo $infant['id']; ?></td>
                    <td><?php echo htmlspecialchars($fullName); ?></td>
                    <td><?php echo htmlspecialchars($infant['id_number']); ?></td>
                    <td><?php echo $infant['birth_date']; ?></td>
                    <td><?php echo $ageInMonths; ?></td>
                    <td><?php echo htmlspecialchars($infant['family_branch']); ?></td>
                    <td><?php echo htmlspecialchars($infant['primary_phone']); ?></td>
                    <td><?php echo htmlspecialchars($infant['displacement_governorate']); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($infant['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>© 2025 جميع الحقوق محفوظة لدي الشاعر عائلتي</p>
        <p>تم إنشاء هذا التقرير تلقائياً من نظام إدارة العائلات والأيتام</p>
    </div>

    <script>
        // طباعة تلقائية عند فتح الصفحة
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
