<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    exit('غير مصرح');
}

$orphan_id = $_GET['id'] ?? '';

if (empty($orphan_id)) {
    http_response_code(400);
    exit('معرف اليتيم مطلوب');
}

// الاتصال بقاعدة البيانات
try {
    $pdo = new PDO('mysql:host=localhost;dbname=family_orphans_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    exit('خطأ في الاتصال بقاعدة البيانات');
}

// جلب تفاصيل اليتيم
$stmt = $pdo->prepare("SELECT * FROM orphans WHERE id = ?");
$stmt->execute([$orphan_id]);
$orphan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$orphan) {
    http_response_code(404);
    exit('اليتيم غير موجود');
}

// ترجمة القيم
$gender_labels = ['male' => 'ذكر', 'female' => 'أنثى'];
$health_labels = ['healthy' => 'سليم', 'hypertension' => 'ضغط', 'diabetes' => 'سكري', 'other' => 'أخرى'];
$relationship_labels = [
    'son' => 'ابن', 'daughter' => 'ابنة', 'brother' => 'أخ', 'sister' => 'أخت',
    'grandfather' => 'جد', 'grandmother' => 'جدة', 'mother' => 'أم', 'father' => 'أب'
];
$governorate_labels = [
    'gaza' => 'غزة', 'khan_younis' => 'خان يونس', 'rafah' => 'رفح',
    'middle' => 'الوسطى', 'north_gaza' => 'شمال غزة'
];
$housing_labels = ['tent' => 'خيمة', 'apartment' => 'شقة', 'house' => 'منزل', 'school' => 'مدرسة'];
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="text-primary mb-3">معلومات الطفل اليتيم</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>الاسم الكامل:</strong></td>
                <td><?php echo htmlspecialchars($orphan['orphan_full_name']); ?></td>
            </tr>
            <tr>
                <td><strong>رقم الهوية:</strong></td>
                <td><?php echo htmlspecialchars($orphan['orphan_id_number']); ?></td>
            </tr>
            <tr>
                <td><strong>الجنس:</strong></td>
                <td><?php echo $gender_labels[$orphan['orphan_gender']] ?? $orphan['orphan_gender']; ?></td>
            </tr>
            <tr>
                <td><strong>تاريخ الميلاد:</strong></td>
                <td><?php echo date('Y-m-d', strtotime($orphan['orphan_birth_date'])); ?></td>
            </tr>
            <tr>
                <td><strong>الحالة الصحية:</strong></td>
                <td><?php echo $health_labels[$orphan['orphan_health_status']] ?? $orphan['orphan_health_status']; ?></td>
            </tr>
            <?php if ($orphan['orphan_health_details']): ?>
            <tr>
                <td><strong>تفاصيل الحالة الصحية:</strong></td>
                <td><?php echo htmlspecialchars($orphan['orphan_health_details']); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($orphan['orphan_image']): ?>
            <tr>
                <td><strong>صورة الطفل:</strong></td>
                <td>
                    <img src="uploads/orphan_images/<?php echo $orphan['orphan_image']; ?>" 
                         class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="text-primary mb-3">معلومات المسؤول</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>الاسم الكامل:</strong></td>
                <td><?php echo htmlspecialchars($orphan['guardian_full_name']); ?></td>
            </tr>
            <tr>
                <td><strong>رقم الهوية:</strong></td>
                <td><?php echo htmlspecialchars($orphan['guardian_id_number']); ?></td>
            </tr>
            <tr>
                <td><strong>الجنس:</strong></td>
                <td><?php echo $gender_labels[$orphan['guardian_gender']] ?? $orphan['guardian_gender']; ?></td>
            </tr>
            <tr>
                <td><strong>تاريخ الميلاد:</strong></td>
                <td><?php echo date('Y-m-d', strtotime($orphan['guardian_birth_date'])); ?></td>
            </tr>
            <tr>
                <td><strong>الصلة:</strong></td>
                <td><?php echo $relationship_labels[$orphan['guardian_relationship']] ?? $orphan['guardian_relationship']; ?></td>
            </tr>
            <tr>
                <td><strong>الهاتف الأساسي:</strong></td>
                <td><?php echo htmlspecialchars($orphan['guardian_primary_phone']); ?></td>
            </tr>
            <?php if ($orphan['guardian_secondary_phone']): ?>
            <tr>
                <td><strong>الهاتف البديل:</strong></td>
                <td><?php echo htmlspecialchars($orphan['guardian_secondary_phone']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <h6 class="text-primary mb-3">معلومات الأب المتوفي</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>اسم الأب:</strong></td>
                <td><?php echo htmlspecialchars($orphan['deceased_father_name']); ?></td>
            </tr>
            <tr>
                <td><strong>رقم هوية الأب:</strong></td>
                <td><?php echo htmlspecialchars($orphan['deceased_father_id_number']); ?></td>
            </tr>
            <tr>
                <td><strong>تاريخ الاستشهاد:</strong></td>
                <td><?php echo date('Y-m-d', strtotime($orphan['martyrdom_date'])); ?></td>
            </tr>
            <tr>
                <td><strong>نوع الوفاة:</strong></td>
                <td>
                    <?php if ($orphan['is_war_martyr']): ?>
                        <span class="badge bg-danger">
                            <i class="fas fa-flag me-1"></i>
                            شهيد حرب
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary">وفاة عادية</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($orphan['death_certificate_image']): ?>
            <tr>
                <td><strong>إثبات الوفاة:</strong></td>
                <td>
                    <img src="uploads/death_certificates/<?php echo $orphan['death_certificate_image']; ?>" 
                         class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="text-primary mb-3">عنوان النزوح</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>المحافظة:</strong></td>
                <td><?php echo $governorate_labels[$orphan['displacement_governorate']] ?? $orphan['displacement_governorate']; ?></td>
            </tr>
            <tr>
                <td><strong>المنطقة:</strong></td>
                <td><?php echo htmlspecialchars($orphan['displacement_area']); ?></td>
            </tr>
            <tr>
                <td><strong>الحي:</strong></td>
                <td><?php echo htmlspecialchars($orphan['displacement_neighborhood']); ?></td>
            </tr>
            <tr>
                <td><strong>حالة السكن:</strong></td>
                <td><?php echo $housing_labels[$orphan['housing_status']] ?? $orphan['housing_status']; ?></td>
            </tr>
        </table>
    </div>
</div>

<?php if ($orphan['bank_name'] || $orphan['account_number']): ?>
<div class="row mt-4">
    <div class="col-12">
        <h6 class="text-primary mb-3">معلومات البنك أو المحفظة</h6>
        <table class="table table-sm">
            <?php if ($orphan['bank_name']): ?>
            <tr>
                <td><strong>اسم البنك أو المحفظة:</strong></td>
                <td><?php echo htmlspecialchars($orphan['bank_name']); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($orphan['bank_phone']): ?>
            <tr>
                <td><strong>رقم هاتف البنك:</strong></td>
                <td><?php echo htmlspecialchars($orphan['bank_phone']); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($orphan['account_number']): ?>
            <tr>
                <td><strong>رقم الحساب أو IBAN:</strong></td>
                <td><?php echo htmlspecialchars($orphan['account_number']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="row mt-4">
    <div class="col-12">
        <h6 class="text-primary mb-3">معلومات إضافية</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>تاريخ التسجيل:</strong></td>
                <td><?php echo date('Y-m-d H:i', strtotime($orphan['created_at'])); ?></td>
            </tr>
            <tr>
                <td><strong>آخر تحديث:</strong></td>
                <td><?php echo date('Y-m-d H:i', strtotime($orphan['updated_at'])); ?></td>
            </tr>
        </table>
    </div>
</div>
