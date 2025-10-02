<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    exit('غير مصرح');
}

$family_id = $_GET['id'] ?? '';

if (empty($family_id)) {
    http_response_code(400);
    exit('معرف العائلة مطلوب');
}

// الاتصال بقاعدة البيانات
try {
    $pdo = new PDO('mysql:host=localhost;dbname=family_orphans_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    exit('خطأ في الاتصال بقاعدة البيانات');
}

// جلب تفاصيل العائلة
$stmt = $pdo->prepare("SELECT * FROM families WHERE id = ?");
$stmt->execute([$family_id]);
$family = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$family) {
    http_response_code(404);
    exit('العائلة غير موجودة');
}

// جلب أفراد العائلة
$stmt = $pdo->prepare("SELECT * FROM family_members WHERE family_id = ? ORDER BY created_at ASC");
$stmt->execute([$family_id]);
$family_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
$relationship_labels = [
    'son' => 'ابن', 'daughter' => 'ابنة', 'father' => 'أب', 'mother' => 'أم',
    'brother' => 'أخ', 'sister' => 'أخت', 'grandfather' => 'جد', 'grandmother' => 'جدة'
];
?>

<style>
.info-item {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 0.5rem;
}

.info-item:last-child {
    border-bottom: none;
}

.info-value {
    margin-top: 0.25rem;
}

.card {
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.card-header {
    border-radius: 15px 15px 0 0 !important;
}

.form-label {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.badge {
    font-size: 0.85rem !important;
    padding: 0.5rem 0.75rem !important;
}

.btn-sm {
    padding: 0.4rem 0.8rem;
    font-size: 0.85rem;
}
</style>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white text-center">
                <h5 class="mb-0"><i class="fas fa-user me-2"></i>معلومات رب الأسرة</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item mb-3">
                            <label class="form-label fw-bold text-primary">الاسم الكامل:</label>
                            <div class="info-value">
                                <span class="badge bg-primary fs-6 p-2"><?php echo htmlspecialchars($family['first_name'] . ' ' . $family['father_name'] . ' ' . $family['grandfather_name'] . ' ' . $family['family_name']); ?></span>
                            </div>
                        </div>
                        
                        <div class="info-item mb-3">
                            <label class="form-label fw-bold text-primary">رقم الهوية:</label>
                            <div class="info-value">
                                <span class="badge bg-secondary fs-6 p-2"><?php echo htmlspecialchars($family['id_number']); ?></span>
                            </div>
                        </div>
                        
                        <div class="info-item mb-3">
                            <label class="form-label fw-bold text-primary">الجنس:</label>
                            <div class="info-value">
                                <span class="badge bg-<?php echo $family['gender'] === 'male' ? 'primary' : 'info'; ?> fs-6 p-2"><?php echo $gender_labels[$family['gender']] ?? $family['gender']; ?></span>
                            </div>
                        </div>
                        
                        <div class="info-item mb-3">
                            <label class="form-label fw-bold text-primary">تاريخ الميلاد:</label>
                            <div class="info-value">
                                <span class="fs-6"><?php echo date('Y-m-d', strtotime($family['birth_date'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="info-item mb-3">
                            <label class="form-label fw-bold text-primary">تاريخ إصدار الهوية:</label>
                            <div class="info-value">
                                <span class="fs-6"><?php echo date('Y-m-d', strtotime($family['id_issue_date'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="info-item mb-3">
                            <label class="form-label fw-bold text-primary">الفرع العائلي:</label>
                            <div class="info-value">
                                <span class="fs-6"><?php echo htmlspecialchars($family['family_branch']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="info-item mb-3">
                            <label class="form-label fw-bold text-primary">الهاتف الأساسي:</label>
                            <div class="info-value">
                                <a href="tel:<?php echo htmlspecialchars($family['primary_phone']); ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($family['primary_phone']); ?>
                                </a>
                            </div>
                        </div>
                        
                        <?php if ($family['secondary_phone']): ?>
                        <div class="info-item mb-3">
                            <label class="form-label fw-bold text-primary">الهاتف البديل:</label>
                            <div class="info-value">
                                <a href="tel:<?php echo htmlspecialchars($family['secondary_phone']); ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($family['secondary_phone']); ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="info-item mb-3">
                            <label class="form-label fw-bold text-primary">الحالة الصحية:</label>
                            <div class="info-value">
                                <span class="badge bg-<?php echo $family['health_status'] === 'healthy' ? 'success' : 'warning'; ?> fs-6 p-2"><?php echo $health_labels[$family['health_status']] ?? $family['health_status']; ?></span>
                            </div>
                        </div>
                        
                        <?php if ($family['health_details']): ?>
                        <div class="info-item mb-3">
                            <label class="form-label fw-bold text-primary">تفاصيل الحالة الصحية:</label>
                            <div class="info-value">
                                <span class="fs-6"><?php echo htmlspecialchars($family['health_details']); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="info-item mb-3">
                            <label class="form-label fw-bold text-primary">الحالة الاجتماعية:</label>
                            <div class="info-value">
                                <span class="badge bg-info fs-6 p-2"><?php echo $marital_labels[$family['marital_status']] ?? $family['marital_status']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    
    <div class="col-md-6">
        <?php if ($family['spouse_first_name']): ?>
        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="fas fa-heart me-2"></i>معلومات الزوج/الزوجة</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
            <tr>
                <td><strong>الاسم الكامل:</strong></td>
                <td><?php echo htmlspecialchars($family['spouse_first_name'] . ' ' . $family['spouse_father_name'] . ' ' . $family['spouse_grandfather_name'] . ' ' . $family['spouse_family_name']); ?></td>
            </tr>
            <tr>
                <td><strong>رقم الهوية:</strong></td>
                <td><?php echo htmlspecialchars($family['spouse_id_number']); ?></td>
            </tr>
            <tr>
                <td><strong>الجنس:</strong></td>
                <td><?php echo $gender_labels[$family['spouse_gender']] ?? $family['spouse_gender']; ?></td>
            </tr>
            <tr>
                <td><strong>تاريخ الميلاد:</strong></td>
                <td><?php echo date('Y-m-d', strtotime($family['spouse_birth_date'])); ?></td>
            </tr>
            <tr>
                <td><strong>الفرع العائلي:</strong></td>
                <td><?php echo htmlspecialchars($family['spouse_family_branch']); ?></td>
            </tr>
            <tr>
                <td><strong>الهاتف:</strong></td>
                <td><?php echo htmlspecialchars($family['spouse_primary_phone']); ?></td>
            </tr>
            <tr>
                <td><strong>الحالة الصحية:</strong></td>
                <td><?php echo $health_labels[$family['spouse_health_status']] ?? $family['spouse_health_status']; ?></td>
            </tr>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="fas fa-home me-2"></i>العنوان الأصلي</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>المحافظة:</strong></td>
                        <td><?php echo $governorate_labels[$family['original_governorate']] ?? $family['original_governorate']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>المنطقة:</strong></td>
                        <td><?php echo htmlspecialchars($family['original_area']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>الحي أو المعلم:</strong></td>
                        <td><?php echo htmlspecialchars($family['original_neighborhood']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>عنوان النزوح</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>المحافظة:</strong></td>
                        <td><?php echo $governorate_labels[$family['displacement_governorate']] ?? $family['displacement_governorate']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>المنطقة:</strong></td>
                        <td><?php echo htmlspecialchars($family['displacement_area']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>الحي:</strong></td>
                        <td><?php echo htmlspecialchars($family['displacement_neighborhood']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>حالة السكن:</strong></td>
                        <td><?php echo $housing_labels[$family['housing_status']] ?? $family['housing_status']; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($family_members)): ?>
<div class="row mt-4">
    <div class="col-12">
        <h6 class="text-primary mb-3">أفراد العائلة</h6>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>رقم الهوية</th>
                        <th>الجنس</th>
                        <th>تاريخ الميلاد</th>
                        <th>الصلة</th>
                        <th>الحالة الصحية</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($family_members as $member): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($member['id_number']); ?></td>
                        <td><?php echo $gender_labels[$member['gender']] ?? $member['gender']; ?></td>
                        <td><?php echo date('Y-m-d', strtotime($member['birth_date'])); ?></td>
                        <td><?php echo $relationship_labels[$member['relationship']] ?? $member['relationship']; ?></td>
                        <td><?php echo $health_labels[$member['health_status']] ?? $member['health_status']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row mt-4">
    <div class="col-12">
        <h6 class="text-primary mb-3">معلومات إضافية</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>عدد أفراد الأسرة:</strong></td>
                <td><?php echo $family['family_members_count']; ?></td>
            </tr>
            <tr>
                <td><strong>تاريخ التسجيل:</strong></td>
                <td><?php echo date('Y-m-d H:i', strtotime($family['created_at'])); ?></td>
            </tr>
            <tr>
                <td><strong>آخر تحديث:</strong></td>
                <td><?php echo date('Y-m-d H:i', strtotime($family['updated_at'])); ?></td>
            </tr>
        </table>
    </div>
</div>
