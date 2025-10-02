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

// جلب جميع البيانات
$families = $pdo->query("SELECT * FROM families ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$orphans = $pdo->query("SELECT * FROM orphans ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// إعداد التصدير كملف Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="all_reports_' . date('Y-m-d_H-i-s') . '.xls"');
header('Cache-Control: max-age=0');

// ترجمة القيم
$gender_labels = ['male' => 'ذكر', 'female' => 'أنثى'];
$health_labels = ['healthy' => 'سليم', 'hypertension' => 'ضغط', 'diabetes' => 'سكري', 'other' => 'أخرى'];
$marital_labels = [
    'married' => 'متزوج', 'divorced' => 'مطلق', 'widowed' => 'أرمل',
    'elderly' => 'مسن', 'provider' => 'معيل', 'special_needs' => 'احتياجات خاصة'
];
$governorate_labels = [
    'damascus' => 'دمشق', 'aleppo' => 'حلب', 'homs' => 'حمص', 'hama' => 'حماة',
    'latakia' => 'اللاذقية', 'tartus' => 'طرطوس', 'idlib' => 'إدلب', 'raqqa' => 'الرقة',
    'deir_ezzur' => 'دير الزور', 'hasaka' => 'الحسكة', 'daraa' => 'درعا',
    'sweida' => 'السويداء', 'quneitra' => 'القنيطرة', 'damascus_countryside' => 'ريف دمشق'
];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; direction: rtl; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .section-title { background-color: #4CAF50; color: white; font-size: 18px; font-weight: bold; }
        .sub-title { background-color: #2196F3; color: white; font-size: 16px; }
    </style>
</head>
<body>
    <h1 style="text-align: center; color: #333;">تقرير شامل - نظام إدارة العائلات والأيتام</h1>
    <p style="text-align: center; color: #666;">تاريخ التصدير: <?php echo date('Y-m-d H:i:s'); ?></p>

    <!-- إحصائيات عامة -->
    <table>
        <tr class="section-title">
            <td colspan="4">الإحصائيات العامة</td>
        </tr>
        <tr>
            <th>المؤشر</th>
            <th>القيمة</th>
            <th>التفاصيل</th>
            <th>التاريخ</th>
        </tr>
        <tr>
            <td>إجمالي العائلات</td>
            <td><?php echo count($families); ?></td>
            <td>عائلة مسجلة</td>
            <td><?php echo date('Y-m-d'); ?></td>
        </tr>
        <tr>
            <td>إجمالي الأيتام</td>
            <td><?php echo count($orphans); ?></td>
            <td>يتيم مسجل</td>
            <td><?php echo date('Y-m-d'); ?></td>
        </tr>
        <tr>
            <td>أبناء الشهداء</td>
            <td><?php echo count(array_filter($orphans, function($o) { return $o['is_war_martyr'] == 1; })); ?></td>
            <td>يتيم من أبناء الشهداء</td>
            <td><?php echo date('Y-m-d'); ?></td>
        </tr>
    </table>

    <!-- بيانات العائلات -->
    <table>
        <tr class="section-title">
            <td colspan="12">بيانات العائلات</td>
        </tr>
        <tr class="sub-title">
            <td>#</td>
            <td>اسم رب الأسرة</td>
            <td>رقم الهوية</td>
            <td>الجنس</td>
            <td>تاريخ الميلاد</td>
            <td>المحافظة الأصلية</td>
            <td>الحالة الصحية</td>
            <td>الحالة الاجتماعية</td>
            <td>عدد الأفراد</td>
            <td>الهاتف الأساسي</td>
            <td>تاريخ التسجيل</td>
            <td>آخر تحديث</td>
        </tr>
        <?php foreach ($families as $index => $family): ?>
        <tr>
            <td><?php echo $index + 1; ?></td>
            <td><?php echo htmlspecialchars($family['first_name'] . ' ' . $family['father_name'] . ' ' . $family['grandfather_name'] . ' ' . $family['family_name']); ?></td>
            <td><?php echo htmlspecialchars($family['id_number']); ?></td>
            <td><?php echo $gender_labels[$family['gender']] ?? $family['gender']; ?></td>
            <td><?php echo date('Y-m-d', strtotime($family['birth_date'])); ?></td>
            <td><?php echo $governorate_labels[$family['original_governorate']] ?? $family['original_governorate']; ?></td>
            <td><?php echo $health_labels[$family['health_status']] ?? $family['health_status']; ?></td>
            <td><?php echo $marital_labels[$family['marital_status']] ?? $family['marital_status']; ?></td>
            <td><?php echo $family['family_members_count']; ?></td>
            <td><?php echo htmlspecialchars($family['primary_phone']); ?></td>
            <td><?php echo date('Y-m-d H:i', strtotime($family['created_at'])); ?></td>
            <td><?php echo date('Y-m-d H:i', strtotime($family['updated_at'])); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- بيانات الأيتام -->
    <table>
        <tr class="section-title">
            <td colspan="11">بيانات الأيتام</td>
        </tr>
        <tr class="sub-title">
            <td>#</td>
            <td>اسم الطفل</td>
            <td>رقم هوية الطفل</td>
            <td>الجنس</td>
            <td>تاريخ الميلاد</td>
            <td>اسم المسؤول</td>
            <td>اسم الأب المتوفي</td>
            <td>نوع الوفاة</td>
            <td>المحافظة</td>
            <td>تاريخ التسجيل</td>
            <td>آخر تحديث</td>
        </tr>
        <?php foreach ($orphans as $index => $orphan): ?>
        <tr>
            <td><?php echo $index + 1; ?></td>
            <td><?php echo htmlspecialchars($orphan['orphan_full_name']); ?></td>
            <td><?php echo htmlspecialchars($orphan['orphan_id_number']); ?></td>
            <td><?php echo $gender_labels[$orphan['orphan_gender']] ?? $orphan['orphan_gender']; ?></td>
            <td><?php echo date('Y-m-d', strtotime($orphan['orphan_birth_date'])); ?></td>
            <td><?php echo htmlspecialchars($orphan['guardian_full_name']); ?></td>
            <td><?php echo htmlspecialchars($orphan['deceased_father_name']); ?></td>
            <td><?php echo $orphan['is_war_martyr'] ? 'شهيد حرب' : 'وفاة طبيعية'; ?></td>
            <td><?php echo $governorate_labels[$orphan['governorate']] ?? $orphan['governorate']; ?></td>
            <td><?php echo date('Y-m-d H:i', strtotime($orphan['created_at'])); ?></td>
            <td><?php echo date('Y-m-d H:i', strtotime($orphan['updated_at'])); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- توزيع حسب المحافظات -->
    <table>
        <tr class="section-title">
            <td colspan="4">توزيع العائلات حسب المحافظات</td>
        </tr>
        <tr class="sub-title">
            <td>المحافظة</td>
            <td>عدد العائلات</td>
            <td>النسبة المئوية</td>
            <td>التفاصيل</td>
        </tr>
        <?php
        $governorate_counts = [];
        foreach ($families as $family) {
            $gov = $family['original_governorate'];
            $governorate_counts[$gov] = ($governorate_counts[$gov] ?? 0) + 1;
        }
        arsort($governorate_counts);
        $total_families = count($families);
        foreach ($governorate_counts as $gov => $count):
        ?>
        <tr>
            <td><?php echo $governorate_labels[$gov] ?? $gov; ?></td>
            <td><?php echo $count; ?></td>
            <td><?php echo round(($count / $total_families) * 100, 1); ?>%</td>
            <td><?php echo $count; ?> عائلة من أصل <?php echo $total_families; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- توزيع الأيتام حسب الجنس -->
    <table>
        <tr class="section-title">
            <td colspan="4">توزيع الأيتام حسب الجنس</td>
        </tr>
        <tr class="sub-title">
            <td>الجنس</td>
            <td>عدد الأيتام</td>
            <td>النسبة المئوية</td>
            <td>التفاصيل</td>
        </tr>
        <?php
        $gender_counts = ['male' => 0, 'female' => 0];
        foreach ($orphans as $orphan) {
            $gender_counts[$orphan['orphan_gender']]++;
        }
        $total_orphans = count($orphans);
        foreach ($gender_counts as $gender => $count):
        ?>
        <tr>
            <td><?php echo $gender_labels[$gender]; ?></td>
            <td><?php echo $count; ?></td>
            <td><?php echo round(($count / $total_orphans) * 100, 1); ?>%</td>
            <td><?php echo $count; ?> يتيم من أصل <?php echo $total_orphans; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div style="margin-top: 30px; text-align: center; color: #666;">
        <p>تم إنشاء هذا التقرير تلقائياً من نظام إدارة العائلات والأيتام</p>
        <p>للاستفسارات والدعم التقني، يرجى التواصل مع فريق الدعم</p>
    </div>
</body>
</html>
