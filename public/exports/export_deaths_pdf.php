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

// إنشاء HTML للطباعة
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الوفيات - الشاعر عائلتي</title>
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
        <p>تقرير بيانات الوفيات</p>
        <p>تاريخ التقرير: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

    <div class="info">
        <p><strong>إجمالي عدد الوفيات:</strong> <?php echo count($deaths); ?></p>
        <p><strong>تاريخ إنشاء التقرير:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>الرقم</th>
                <th>الاسم الكامل</th>
                <th>رقم الهوية</th>
                <th>تاريخ الميلاد</th>
                <th>المحافظة</th>
                <th>الفرع العائلي</th>
                <th>سبب الوفاة</th>
                <th>مدخل الطلب</th>
                <th>الصلة</th>
                <th>تاريخ التسجيل</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($deaths as $death): ?>
                <?php
                $fullName = $death['first_name'] . ' ' . $death['father_name'] . ' ' . $death['grandfather_name'] . ' ' . $death['family_name'];
                $requesterName = $death['requester_first_name'] . ' ' . $death['requester_father_name'] . ' ' . $death['requester_grandfather_name'] . ' ' . $death['requester_family_name'];
                ?>
                <tr>
                    <td><?php echo $death['id']; ?></td>
                    <td><?php echo htmlspecialchars($fullName); ?></td>
                    <td><?php echo htmlspecialchars($death['id_number']); ?></td>
                    <td><?php echo $death['birth_date']; ?></td>
                    <td><?php echo htmlspecialchars($death['governorate']); ?></td>
                    <td><?php echo htmlspecialchars($death['family_branch']); ?></td>
                    <td><?php echo htmlspecialchars($death['death_reason']); ?></td>
                    <td><?php echo htmlspecialchars($requesterName); ?></td>
                    <td><?php echo htmlspecialchars($death['requester_relationship']); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($death['created_at'])); ?></td>
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
