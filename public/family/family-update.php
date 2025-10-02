<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['family_logged_in']) || $_SESSION['family_logged_in'] !== true) {
    header('Location: family-login.php');
    exit;
}

// إعدادات قاعدة البيانات
$host = 'localhost';
$dbname = 'family_orphans_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

require_once '../../includes/device-tracker.php';

$success = '';
$error = '';

// معالجة رسائل النجاح والخطأ من URL
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// جلب بيانات العائلة
$stmt = $pdo->prepare("SELECT * FROM families WHERE id = ?");
$stmt->execute([$_SESSION['family_id']]);
$family = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$family) {
    header('Location: family-login.php');
    exit;
}

// جلب بيانات أفراد الأسرة
$stmt = $pdo->prepare("SELECT * FROM family_members WHERE family_id = ? ORDER BY id");
$stmt->execute([$_SESSION['family_id']]);
$family_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// معالجة تحديث البيانات
if ($_POST) {
    try {
        // بدء المعاملة
        $pdo->beginTransaction();
        
        // تحديث بيانات الأسرة
        $stmt = $pdo->prepare("
            UPDATE families SET 
                first_name = ?, father_name = ?, grandfather_name = ?, family_name = ?, 
                id_number = ?, gender = ?, birth_date = ?, id_issue_date = ?, 
                family_branch = ?, primary_phone = ?, secondary_phone = ?, 
                health_status = ?, health_details = ?, marital_status = ?, 
                spouse_first_name = ?, spouse_father_name = ?, spouse_grandfather_name = ?, 
                spouse_family_name = ?, spouse_id_number = ?, spouse_gender = ?, 
                spouse_birth_date = ?, spouse_id_issue_date = ?, 
                spouse_health_status = ?, spouse_health_details = ?, 
                original_governorate = ?, original_area = ?, original_neighborhood = ?, 
                displacement_governorate = ?, displacement_area = ?, displacement_neighborhood = ?, 
                housing_status = ?, family_members_count = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        // تجميع التواريخ
        $birth_date = $_POST['birth_year'] . '-' . $_POST['birth_month'] . '-' . $_POST['birth_day'];
        $id_issue_date = $_POST['id_issue_year'] . '-' . $_POST['id_issue_month'] . '-' . $_POST['id_issue_day'];
        $spouse_birth_date = null;
        $spouse_id_issue_date = null;
        
        if ($_POST['marital_status'] == 'married') {
            $spouse_birth_date = $_POST['spouse_birth_year'] . '-' . $_POST['spouse_birth_month'] . '-' . $_POST['spouse_birth_day'];
            $spouse_id_issue_date = $_POST['spouse_id_issue_year'] . '-' . $_POST['spouse_id_issue_month'] . '-' . $_POST['spouse_id_issue_day'];
        }
        
        $stmt->execute([
            $_POST['first_name'], $_POST['father_name'], $_POST['grandfather_name'], 
            $_POST['family_name'], $_POST['id_number'], $_POST['gender'], 
            $birth_date, $id_issue_date, $_POST['family_branch'], 
            $_POST['primary_phone'], $_POST['secondary_phone'], $_POST['health_status'], 
            $_POST['health_details'], $_POST['marital_status'], 
            $_POST['marital_status'] == 'married' ? $_POST['spouse_first_name'] : null,
            $_POST['marital_status'] == 'married' ? $_POST['spouse_father_name'] : null,
            $_POST['marital_status'] == 'married' ? $_POST['spouse_grandfather_name'] : null,
            $_POST['marital_status'] == 'married' ? $_POST['spouse_family_name'] : null,
            $_POST['marital_status'] == 'married' ? $_POST['spouse_id_number'] : null,
            $_POST['marital_status'] == 'married' ? $_POST['spouse_gender'] : null,
            $spouse_birth_date,
            $spouse_id_issue_date,
            $_POST['marital_status'] == 'married' ? $_POST['spouse_health_status'] : null,
            $_POST['marital_status'] == 'married' ? $_POST['spouse_health_details'] : null,
            $_POST['original_governorate'], $_POST['original_area'], $_POST['original_neighborhood'],
            $_POST['displacement_governorate'], $_POST['displacement_area'], 
            $_POST['displacement_neighborhood'], $_POST['housing_status'], 
            $_POST['family_members_count'],
            $_SESSION['family_id']
        ]);
        
        // حذف أفراد الأسرة الحاليين
        $stmt = $pdo->prepare("DELETE FROM family_members WHERE family_id = ?");
        $stmt->execute([$_SESSION['family_id']]);
        
        // إدراج أفراد الأسرة الجدد
        if (isset($_POST['family_members']) && is_array($_POST['family_members'])) {
            $memberStmt = $pdo->prepare("
                INSERT INTO family_members (family_id, full_name, id_number, gender, 
                birth_date, relationship, health_status, health_details) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($_POST['family_members'] as $member) {
                $member_birth_date = $member['birth_year'] . '-' . $member['birth_month'] . '-' . $member['birth_day'];
                $memberStmt->execute([
                    $_SESSION['family_id'], $member['full_name'], $member['id_number'], 
                    $member['gender'], $member_birth_date, $member['relationship'], 
                    $member['health_status'], $member['health_details']
                ]);
            }
        }
        
        $pdo->commit();
        $success = "تم تحديث بيانات الأسرة بنجاح!";
        
        // تسجيل عملية التحديث
        logDataUpdate($pdo, $_SESSION['family_id'], 'family_data', 'تم تحديث بيانات العائلة', 'تحديث شامل للبيانات');
        
        // تحديث البيانات في الجلسة
        $_SESSION['family_name'] = $_POST['first_name'] . ' ' . $_POST['family_name'];
        
        // إعادة جلب البيانات المحدثة
        $stmt = $pdo->prepare("SELECT * FROM families WHERE id = ?");
        $stmt->execute([$_SESSION['family_id']]);
        $family = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT * FROM family_members WHERE family_id = ? ORDER BY id");
        $stmt->execute([$_SESSION['family_id']]);
        $family_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "حدث خطأ: " . $e->getMessage();
    }
}

// تحويل تاريخ الميلاد إلى أجزاء
$birth_parts = explode('-', $family['birth_date']);
$family['birth_year'] = $birth_parts[0] ?? '';
$family['birth_month'] = $birth_parts[1] ?? '';
$family['birth_day'] = $birth_parts[2] ?? '';

// تحويل تاريخ إصدار الهوية إلى أجزاء
$id_issue_parts = explode('-', $family['id_issue_date']);
$family['id_issue_year'] = $id_issue_parts[0] ?? '';
$family['id_issue_month'] = $id_issue_parts[1] ?? '';
$family['id_issue_day'] = $id_issue_parts[2] ?? '';

// تحويل تاريخ ميلاد الزوج/ة إلى أجزاء
if ($family['spouse_birth_date']) {
    $spouse_birth_parts = explode('-', $family['spouse_birth_date']);
    $family['spouse_birth_year'] = $spouse_birth_parts[0] ?? '';
    $family['spouse_birth_month'] = $spouse_birth_parts[1] ?? '';
    $family['spouse_birth_day'] = $spouse_birth_parts[2] ?? '';
}

// تحويل تاريخ إصدار هوية الزوج/ة إلى أجزاء
if ($family['spouse_id_issue_date']) {
    $spouse_id_issue_parts = explode('-', $family['spouse_id_issue_date']);
    $family['spouse_id_issue_year'] = $spouse_id_issue_parts[0] ?? '';
    $family['spouse_id_issue_month'] = $spouse_id_issue_parts[1] ?? '';
    $family['spouse_id_issue_day'] = $spouse_id_issue_parts[2] ?? '';
}

// تحويل تواريخ أفراد الأسرة
foreach ($family_members as &$member) {
    $member_birth_parts = explode('-', $member['birth_date']);
    $member['birth_year'] = $member_birth_parts[0] ?? '';
    $member['birth_month'] = $member_birth_parts[1] ?? '';
    $member['birth_day'] = $member_birth_parts[2] ?? '';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث بيانات الأسرة - الشاعر عائلتي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-section { background: #f8f9fa; border-radius: 10px; padding: 2rem; margin-bottom: 2rem; }
        .section-title { color: #0d6efd; border-bottom: 2px solid #0d6efd; padding-bottom: 0.5rem; margin-bottom: 1.5rem; }
        .required { color: #dc3545; }
        .family-member-card { border: 1px solid #dee2e6; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; background: white; }
        .btn-logout { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border: none; color: white; }
        .btn-logout:hover { background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%); color: white; }
        .password-strength { margin-top: 0.5rem; }
        .strength-bar { height: 4px; background: #e9ecef; border-radius: 2px; overflow: hidden; margin-top: 0.5rem; }
        .strength-fill { height: 100%; transition: all 0.3s ease; border-radius: 2px; }
        .strength-weak { background: #dc3545; width: 25%; }
        .strength-medium { background: #ffc107; width: 50%; }
        .strength-strong { background: #28a745; width: 75%; }
        .strength-very-strong { background: #28a745; width: 100%; }
    </style>
</head>
<body>
    <!-- Navigation for public pages -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">
                <i class="fas fa-heart me-2"></i>
                الشاعر عائلتي
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../../index.php">
                    <i class="fas fa-home me-1"></i>
                    الرئيسية
                </a>
                <a class="nav-link active" href="family-update.php">
                    <i class="fas fa-edit me-1"></i>
                    تحديث بيانات الأسرة
                </a>
                <a class="nav-link" href="family-logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    تسجيل الخروج
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h2 class="card-title mb-0">
                            <i class="fas fa-edit me-2"></i>
                            تحديث بيانات الأسرة
                        </h2>
                        <p class="mb-0 mt-2">مرحباً، <?php echo htmlspecialchars($_SESSION['family_name']); ?></p>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="familyForm">
                            <!-- معلومات رب الأسرة -->
                            <div class="form-section">
                                <h4 class="section-title">
                                    <i class="fas fa-user me-2"></i>
                                    معلومات رب الأسرة
                                </h4>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">الاسم الشخصي <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($family['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">اسم الأب <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="father_name" value="<?php echo htmlspecialchars($family['father_name']); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">اسم الجد <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="grandfather_name" value="<?php echo htmlspecialchars($family['grandfather_name']); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">اسم العائلة <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="family_name" value="<?php echo htmlspecialchars($family['family_name']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-3">
                                        <label class="form-label">رقم الهوية <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="id_number" value="<?php echo htmlspecialchars($family['id_number']); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">الجنس <span class="required">*</span></label>
                                        <select class="form-select" name="gender" required>
                                            <option value="">اختر الجنس</option>
                                            <option value="male" <?php echo $family['gender'] == 'male' ? 'selected' : ''; ?>>ذكر</option>
                                            <option value="female" <?php echo $family['gender'] == 'female' ? 'selected' : ''; ?>>أنثى</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">تاريخ الميلاد <span class="required">*</span></label>
                                        <div class="row">
                                            <div class="col-4">
                                                <select class="form-select" name="birth_day" required>
                                                    <option value="">اليوم</option>
                                                    <?php for($i = 1; $i <= 31; $i++): ?>
                                                        <option value="<?php echo $i; ?>" <?php echo $family['birth_day'] == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select" name="birth_month" required>
                                                    <option value="">الشهر</option>
                                                    <?php 
                                                    $months = [
                                                        '01' => 'يناير', '02' => 'فبراير', '03' => 'مارس', '04' => 'أبريل',
                                                        '05' => 'مايو', '06' => 'يونيو', '07' => 'يوليو', '08' => 'أغسطس',
                                                        '09' => 'سبتمبر', '10' => 'أكتوبر', '11' => 'نوفمبر', '12' => 'ديسمبر'
                                                    ];
                                                    foreach($months as $key => $value): ?>
                                                        <option value="<?php echo $key; ?>" <?php echo $family['birth_month'] == $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select" name="birth_year" required>
                                                    <option value="">السنة</option>
                                                    <?php for($i = 2024; $i >= 1920; $i--): ?>
                                                        <option value="<?php echo $i; ?>" <?php echo $family['birth_year'] == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <input type="hidden" name="birth_date" id="birth_date">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">تاريخ إصدار الهوية <span class="required">*</span></label>
                                        <div class="row">
                                            <div class="col-4">
                                                <select class="form-select" name="id_issue_day" required>
                                                    <option value="">اليوم</option>
                                                    <?php for($i = 1; $i <= 31; $i++): ?>
                                                        <option value="<?php echo $i; ?>" <?php echo $family['id_issue_day'] == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select" name="id_issue_month" required>
                                                    <option value="">الشهر</option>
                                                    <?php foreach($months as $key => $value): ?>
                                                        <option value="<?php echo $key; ?>" <?php echo $family['id_issue_month'] == $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select" name="id_issue_year" required>
                                                    <option value="">السنة</option>
                                                    <?php for($i = 2024; $i >= 1950; $i--): ?>
                                                        <option value="<?php echo $i; ?>" <?php echo $family['id_issue_year'] == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <input type="hidden" name="id_issue_date" id="id_issue_date">
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <label class="form-label">الفرع العائلي <span class="required">*</span></label>
                                        <select class="form-select" name="family_branch" required>
                                            <option value="">اختر الفرع العائلي</option>
                                            <?php 
                                            $branches = [
                                                'الجواهرة', 'العواودة', 'البشيتي', 'زقماط', 'حندش والحمادين ابوحمدان',
                                                'مقلد', 'الدجاجنة', 'قريده', 'الصوفي', 'مصبح', 'قرقوش', 'العوايضة',
                                                'السوالمة', 'عرادة', 'البراهمة', 'العيسة', 'المحامدة', 'ابوسري', 'المهاوشة'
                                            ];
                                            foreach($branches as $branch): ?>
                                                <option value="<?php echo $branch; ?>" <?php echo $family['family_branch'] == $branch ? 'selected' : ''; ?>><?php echo $branch; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">رقم الهاتف الأساسي <span class="required">*</span></label>
                                        <input type="tel" class="form-control" name="primary_phone" value="<?php echo htmlspecialchars($family['primary_phone']); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">رقم الهاتف البديل</label>
                                        <input type="tel" class="form-control" name="secondary_phone" value="<?php echo htmlspecialchars($family['secondary_phone']); ?>">
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label">الحالة الصحية <span class="required">*</span></label>
                                        <select class="form-select" name="health_status" required onchange="toggleHealthDetails(this)">
                                            <option value="">اختر الحالة الصحية</option>
                                            <option value="healthy" <?php echo $family['health_status'] == 'healthy' ? 'selected' : ''; ?>>سليم</option>
                                            <option value="hypertension" <?php echo $family['health_status'] == 'hypertension' ? 'selected' : ''; ?>>ضغط</option>
                                            <option value="diabetes" <?php echo $family['health_status'] == 'diabetes' ? 'selected' : ''; ?>>سكري</option>
                                            <option value="other" <?php echo $family['health_status'] == 'other' ? 'selected' : ''; ?>>أخرى</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="health-details-container" style="<?php echo in_array($family['health_status'], ['hypertension', 'diabetes', 'other']) ? '' : 'display: none;'; ?>">
                                        <label class="form-label">تفاصيل الحالة الصحية</label>
                                        <textarea class="form-control" name="health_details" rows="2"><?php echo htmlspecialchars($family['health_details']); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label">الحالة الاجتماعية <span class="required">*</span></label>
                                        <select class="form-select" name="marital_status" required onchange="toggleSpouseFields()">
                                            <option value="">اختر الحالة الاجتماعية</option>
                                            <option value="married" <?php echo $family['marital_status'] == 'married' ? 'selected' : ''; ?>>متزوج/ة</option>
                                            <option value="divorced" <?php echo $family['marital_status'] == 'divorced' ? 'selected' : ''; ?>>مطلق/ة</option>
                                            <option value="widowed" <?php echo $family['marital_status'] == 'widowed' ? 'selected' : ''; ?>>أرمل/ة</option>
                                            <option value="elderly" <?php echo $family['marital_status'] == 'elderly' ? 'selected' : ''; ?>>مسن/ة</option>
                                            <option value="provider" <?php echo $family['marital_status'] == 'provider' ? 'selected' : ''; ?>>معيل/ة</option>
                                            <option value="special_needs" <?php echo $family['marital_status'] == 'special_needs' ? 'selected' : ''; ?>>ذوي احتياجات خاصة</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- بيانات الزوج/ة -->
                            <div class="form-section" id="spouse-section" style="<?php echo $family['marital_status'] == 'married' ? '' : 'display: none;'; ?>">
                                <h4 class="section-title">
                                    <i class="fas fa-heart me-2"></i>
                                    بيانات الزوج/ة
                                </h4>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">الاسم الشخصي <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="spouse_first_name" value="<?php echo htmlspecialchars($family['spouse_first_name']); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">اسم الأب <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="spouse_father_name" value="<?php echo htmlspecialchars($family['spouse_father_name']); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">اسم الجد <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="spouse_grandfather_name" value="<?php echo htmlspecialchars($family['spouse_grandfather_name']); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">اسم العائلة <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="spouse_family_name" value="<?php echo htmlspecialchars($family['spouse_family_name']); ?>">
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-3">
                                        <label class="form-label">رقم الهوية <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="spouse_id_number" value="<?php echo htmlspecialchars($family['spouse_id_number']); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">الجنس <span class="required">*</span></label>
                                        <select class="form-select" name="spouse_gender">
                                            <option value="">اختر الجنس</option>
                                            <option value="male" <?php echo $family['spouse_gender'] == 'male' ? 'selected' : ''; ?>>ذكر</option>
                                            <option value="female" <?php echo $family['spouse_gender'] == 'female' ? 'selected' : ''; ?>>أنثى</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">تاريخ الميلاد <span class="required">*</span></label>
                                        <div class="row">
                                            <div class="col-4">
                                                <select class="form-select" name="spouse_birth_day">
                                                    <option value="">اليوم</option>
                                                    <?php for($i = 1; $i <= 31; $i++): ?>
                                                        <option value="<?php echo $i; ?>" <?php echo $family['spouse_birth_day'] == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select" name="spouse_birth_month">
                                                    <option value="">الشهر</option>
                                                    <?php foreach($months as $key => $value): ?>
                                                        <option value="<?php echo $key; ?>" <?php echo $family['spouse_birth_month'] == $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select" name="spouse_birth_year">
                                                    <option value="">السنة</option>
                                                    <?php for($i = 2024; $i >= 1920; $i--): ?>
                                                        <option value="<?php echo $i; ?>" <?php echo $family['spouse_birth_year'] == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <input type="hidden" name="spouse_birth_date" id="spouse_birth_date">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">تاريخ إصدار الهوية <span class="required">*</span></label>
                                        <div class="row">
                                            <div class="col-4">
                                                <select class="form-select" name="spouse_id_issue_day">
                                                    <option value="">اليوم</option>
                                                    <?php for($i = 1; $i <= 31; $i++): ?>
                                                        <option value="<?php echo $i; ?>" <?php echo $family['spouse_id_issue_day'] == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select" name="spouse_id_issue_month">
                                                    <option value="">الشهر</option>
                                                    <?php foreach($months as $key => $value): ?>
                                                        <option value="<?php echo $key; ?>" <?php echo $family['spouse_id_issue_month'] == $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select" name="spouse_id_issue_year">
                                                    <option value="">السنة</option>
                                                    <?php for($i = 2024; $i >= 1950; $i--): ?>
                                                        <option value="<?php echo $i; ?>" <?php echo $family['spouse_id_issue_year'] == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <input type="hidden" name="spouse_id_issue_date" id="spouse_id_issue_date">
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label">الحالة الصحية <span class="required">*</span></label>
                                        <select class="form-select" name="spouse_health_status" onchange="toggleSpouseHealthDetails(this)">
                                            <option value="">اختر الحالة الصحية</option>
                                            <option value="healthy" <?php echo $family['spouse_health_status'] == 'healthy' ? 'selected' : ''; ?>>سليم</option>
                                            <option value="hypertension" <?php echo $family['spouse_health_status'] == 'hypertension' ? 'selected' : ''; ?>>ضغط</option>
                                            <option value="diabetes" <?php echo $family['spouse_health_status'] == 'diabetes' ? 'selected' : ''; ?>>سكري</option>
                                            <option value="other" <?php echo $family['spouse_health_status'] == 'other' ? 'selected' : ''; ?>>أخرى</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="spouse-health-details-container" style="<?php echo in_array($family['spouse_health_status'], ['hypertension', 'diabetes', 'other']) ? '' : 'display: none;'; ?>">
                                        <label class="form-label">تفاصيل الحالة الصحية</label>
                                        <textarea class="form-control" name="spouse_health_details" rows="2"><?php echo htmlspecialchars($family['spouse_health_details']); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- معلومات العنوان -->
                            <div class="form-section">
                                <h4 class="section-title">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    معلومات العنوان
                                </h4>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">المحافظة الأصلية <span class="required">*</span></label>
                                        <select class="form-select" name="original_governorate" required>
                                            <option value="">اختر المحافظة الأصلية</option>
                                            <option value="gaza" <?php echo $family['original_governorate'] == 'gaza' ? 'selected' : ''; ?>>غزة</option>
                                            <option value="khan_younis" <?php echo $family['original_governorate'] == 'khan_younis' ? 'selected' : ''; ?>>خانيونس</option>
                                            <option value="rafah" <?php echo $family['original_governorate'] == 'rafah' ? 'selected' : ''; ?>>رفح</option>
                                            <option value="middle" <?php echo $family['original_governorate'] == 'middle' ? 'selected' : ''; ?>>الوسطى</option>
                                            <option value="north_gaza" <?php echo $family['original_governorate'] == 'north_gaza' ? 'selected' : ''; ?>>شمال غزة</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">المنطقة الأصلية <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="original_area" value="<?php echo htmlspecialchars($family['original_area']); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">الحي الأصلي <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="original_neighborhood" value="<?php echo htmlspecialchars($family['original_neighborhood']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <label class="form-label">المحافظة الحالية <span class="required">*</span></label>
                                        <select class="form-select" name="displacement_governorate" required>
                                            <option value="">اختر المحافظة الحالية</option>
                                            <option value="gaza" <?php echo $family['displacement_governorate'] == 'gaza' ? 'selected' : ''; ?>>غزة</option>
                                            <option value="khan_younis" <?php echo $family['displacement_governorate'] == 'khan_younis' ? 'selected' : ''; ?>>خانيونس</option>
                                            <option value="rafah" <?php echo $family['displacement_governorate'] == 'rafah' ? 'selected' : ''; ?>>رفح</option>
                                            <option value="middle" <?php echo $family['displacement_governorate'] == 'middle' ? 'selected' : ''; ?>>الوسطى</option>
                                            <option value="north_gaza" <?php echo $family['displacement_governorate'] == 'north_gaza' ? 'selected' : ''; ?>>شمال غزة</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">المنطقة الحالية <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="displacement_area" value="<?php echo htmlspecialchars($family['displacement_area']); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">الحي الحالي <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="displacement_neighborhood" value="<?php echo htmlspecialchars($family['displacement_neighborhood']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label">حالة السكن <span class="required">*</span></label>
                                        <select class="form-select" name="housing_status" required>
                                            <option value="">اختر حالة السكن</option>
                                            <option value="owned" <?php echo $family['housing_status'] == 'owned' ? 'selected' : ''; ?>>ملك</option>
                                            <option value="rented" <?php echo $family['housing_status'] == 'rented' ? 'selected' : ''; ?>>إيجار</option>
                                            <option value="shelter" <?php echo $family['housing_status'] == 'shelter' ? 'selected' : ''; ?>>مأوى</option>
                                            <option value="tent" <?php echo $family['housing_status'] == 'tent' ? 'selected' : ''; ?>>خيمة</option>
                                            <option value="other" <?php echo $family['housing_status'] == 'other' ? 'selected' : ''; ?>>أخرى</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">عدد أفراد الأسرة <span class="required">*</span></label>
                                        <input type="number" class="form-control" name="family_members_count" value="<?php echo $family['family_members_count']; ?>" min="1" required>
                                    </div>
                                </div>
                            </div>

                            <!-- أفراد الأسرة -->
                            <div class="form-section">
                                <h4 class="section-title">
                                    <i class="fas fa-users me-2"></i>
                                    أفراد الأسرة
                                </h4>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">عدد أفراد الأسرة <span class="required">*</span></label>
                                        <input type="number" class="form-control" name="family_members_count" min="0" value="<?php echo count($family_members); ?>" required>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <button type="button" class="btn btn-outline-primary" onclick="addFamilyMembers()">
                                            <i class="fas fa-plus me-2"></i>
                                            إضافة أفراد الأسرة
                                        </button>
                                    </div>
                                </div>
                                
                                <div id="family-members-container">
                                    <!-- سيتم إضافة أفراد الأسرة هنا ديناميكياً -->
                                </div>
                            </div>

                            <!-- أزرار التحكم -->
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg me-3">
                                    <i class="fas fa-save me-2"></i>
                                    حفظ التغييرات
                                </button>
                                <button type="button" class="btn btn-warning btn-lg me-3" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="fas fa-key me-2"></i>
                                    تغيير كلمة المرور
                                </button>
                                <button type="button" class="btn btn-info btn-lg me-3" data-bs-toggle="modal" data-bs-target="#securityQuestionModal">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    سؤال الأمان
                                </button>
                                <a href="family-logout.php" class="btn btn-logout btn-lg">
                                    <i class="fas fa-sign-out-alt me-2"></i>
                                    تسجيل الخروج
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- نافذة تغيير كلمة المرور -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="changePasswordModalLabel">
                        <i class="fas fa-key me-2"></i>
                        تغيير كلمة المرور
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="changePasswordForm" method="POST" action="change-password.php">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>تنبيه:</strong> كلمة المرور الجديدة يجب أن تكون مكونة من 8 أرقام فقط ولا يمكن استخدام كلمة مرور مستخدمة من قبل عائلة أخرى.
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password_modal" class="form-label fw-bold">كلمة المرور الجديدة *</label>
                            <input type="text" class="form-control" id="new_password_modal" name="new_password" 
                                   placeholder="أدخل كلمة مرور جديدة مكونة من 8 أرقام" maxlength="8" pattern="[0-9]{8}" required>
                            <div class="form-text">يجب أن تكون 8 أرقام بالضبط</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password_modal" class="form-label fw-bold">تأكيد كلمة المرور *</label>
                            <input type="text" class="form-control" id="confirm_password_modal" name="confirm_password" 
                                   placeholder="أعد إدخال كلمة المرور الجديدة" maxlength="8" pattern="[0-9]{8}" required>
                        </div>
                        
                        <div class="password-strength mb-3">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFillModal"></div>
                            </div>
                            <small class="text-muted" id="strengthTextModal">أدخل كلمة المرور</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>
                            إلغاء
                        </button>
                        <button type="submit" class="btn btn-warning" id="submitPasswordBtn" disabled>
                            <i class="fas fa-save me-1"></i>
                            تغيير كلمة المرور
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- نافذة سؤال الأمان -->
    <div class="modal fade" id="securityQuestionModal" tabindex="-1" aria-labelledby="securityQuestionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="securityQuestionModalLabel">
                        <i class="fas fa-shield-alt me-2"></i>
                        إدارة سؤال الأمان
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="securityQuestionForm" method="POST" action="update-security-question.php">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>تنبيه:</strong> سؤال الأمان اختياري، ولكنه يوفر حماية إضافية لحسابك.
                        </div>
                        
                        <div class="mb-3">
                            <label for="security_question" class="form-label fw-bold">سؤال الأمان</label>
                            <select class="form-select" name="security_question" id="security_question">
                                <option value="">اختر سؤال الأمان</option>
                                <option value="ما اسم والدتك؟">ما اسم والدتك؟</option>
                                <option value="ما اسم مدرستك الأولى؟">ما اسم مدرستك الأولى؟</option>
                                <option value="ما اسم حي طفولتك؟">ما اسم حي طفولتك؟</option>
                                <option value="ما اسم حيوانك الأليف الأول؟">ما اسم حيوانك الأليف الأول؟</option>
                                <option value="ما اسم أفضل صديق لك في المدرسة؟">ما اسم أفضل صديق لك في المدرسة؟</option>
                                <option value="ما اسم الشارع الذي نشأت فيه؟">ما اسم الشارع الذي نشأت فيه؟</option>
                                <option value="ما اسم معلمك المفضل؟">ما اسم معلمك المفضل؟</option>
                                <option value="ما اسم أول سيارة امتلكتها؟">ما اسم أول سيارة امتلكتها؟</option>
                                <option value="other">سؤال آخر</option>
                            </select>
                        </div>

                        <div class="mb-3" id="custom_question_div" style="display: none;">
                            <label for="custom_question" class="form-label fw-bold">اكتب سؤالك الخاص</label>
                            <input type="text" class="form-control" name="custom_question" id="custom_question" placeholder="اكتب سؤال الأمان الخاص بك">
                        </div>

                        <div class="mb-3">
                            <label for="security_answer" class="form-label fw-bold">الإجابة</label>
                            <input type="text" class="form-control" name="security_answer" id="security_answer" 
                                   placeholder="أدخل إجابة سؤال الأمان" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>
                            إلغاء
                        </button>
                        <button type="submit" class="btn btn-info" id="submitSecurityBtn">
                            <i class="fas fa-save me-1"></i>
                            حفظ سؤال الأمان
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let memberIndex = <?php echo count($family_members); ?>;
        
        // تحديث التواريخ المخفية
        function updateHiddenDates() {
            // تاريخ الميلاد
            const birthDay = document.querySelector('select[name="birth_day"]').value;
            const birthMonth = document.querySelector('select[name="birth_month"]').value;
            const birthYear = document.querySelector('select[name="birth_year"]').value;
            if (birthDay && birthMonth && birthYear) {
                document.getElementById('birth_date').value = birthYear + '-' + birthMonth + '-' + birthDay;
            }
            
            // تاريخ إصدار الهوية
            const idDay = document.querySelector('select[name="id_issue_day"]').value;
            const idMonth = document.querySelector('select[name="id_issue_month"]').value;
            const idYear = document.querySelector('select[name="id_issue_year"]').value;
            if (idDay && idMonth && idYear) {
                document.getElementById('id_issue_date').value = idYear + '-' + idMonth + '-' + idDay;
            }
            
            // تاريخ ميلاد الزوج/ة
            const spouseBirthDay = document.querySelector('select[name="spouse_birth_day"]').value;
            const spouseBirthMonth = document.querySelector('select[name="spouse_birth_month"]').value;
            const spouseBirthYear = document.querySelector('select[name="spouse_birth_year"]').value;
            if (spouseBirthDay && spouseBirthMonth && spouseBirthYear) {
                document.getElementById('spouse_birth_date').value = spouseBirthYear + '-' + spouseBirthMonth + '-' + spouseBirthDay;
            }
            
            // تاريخ إصدار هوية الزوج/ة
            const spouseIdDay = document.querySelector('select[name="spouse_id_issue_day"]').value;
            const spouseIdMonth = document.querySelector('select[name="spouse_id_issue_month"]').value;
            const spouseIdYear = document.querySelector('select[name="spouse_id_issue_year"]').value;
            if (spouseIdDay && spouseIdMonth && spouseIdYear) {
                document.getElementById('spouse_id_issue_date').value = spouseIdYear + '-' + spouseIdMonth + '-' + spouseIdDay;
            }
        }
        
        // تحديث تواريخ أفراد الأسرة
        function updateMemberDates() {
            document.querySelectorAll('.member-birth-date').forEach(function(input) {
                const card = input.closest('.family-member-card');
                const day = card.querySelector('select[name*="[birth_day]"]').value;
                const month = card.querySelector('select[name*="[birth_month]"]').value;
                const year = card.querySelector('select[name*="[birth_year]"]').value;
                if (day && month && year) {
                    input.value = year + '-' + month + '-' + day;
                }
            });
        }
        
        // إضافة مستمعي الأحداث
        document.addEventListener('change', function(e) {
            if (e.target.name === 'birth_day' || e.target.name === 'birth_month' || e.target.name === 'birth_year') {
                updateHiddenDates();
            } else if (e.target.name === 'id_issue_day' || e.target.name === 'id_issue_month' || e.target.name === 'id_issue_year') {
                updateHiddenDates();
            } else if (e.target.name === 'spouse_birth_day' || e.target.name === 'spouse_birth_month' || e.target.name === 'spouse_birth_year') {
                updateHiddenDates();
            } else if (e.target.name === 'spouse_id_issue_day' || e.target.name === 'spouse_id_issue_month' || e.target.name === 'spouse_id_issue_year') {
                updateHiddenDates();
            } else if (e.target.name && e.target.name.includes('birth_day') || e.target.name.includes('birth_month') || e.target.name.includes('birth_year')) {
                updateMemberDates();
            }
        });
        
        // تبديل تفاصيل الحالة الصحية
        function toggleHealthDetails(select) {
            const container = document.getElementById('health-details-container');
            if (['hypertension', 'diabetes', 'other'].includes(select.value)) {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }
        
        // تبديل تفاصيل الحالة الصحية للزوج/ة
        function toggleSpouseHealthDetails(select) {
            const container = document.getElementById('spouse-health-details-container');
            if (['hypertension', 'diabetes', 'other'].includes(select.value)) {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }
        
        // تبديل حقول الزوج/ة
        function toggleSpouseFields() {
            const maritalStatus = document.querySelector('select[name="marital_status"]').value;
            const spouseSection = document.getElementById('spouse-section');
            if (maritalStatus === 'married') {
                spouseSection.style.display = 'block';
            } else {
                spouseSection.style.display = 'none';
            }
        }
        
        // إضافة فرد جديد
        function addFamilyMember() {
            const container = document.getElementById('family-members-container');
            const newCard = document.createElement('div');
            newCard.className = 'family-member-card';
            newCard.setAttribute('data-index', memberIndex);
            
            newCard.innerHTML = `
                <div class="row">
                    <div class="col-md-2">
                        <label class="form-label">الاسم الكامل <span class="required">*</span></label>
                        <input type="text" class="form-control" name="family_members[${memberIndex}][full_name]" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">رقم الهوية</label>
                        <input type="text" class="form-control" name="family_members[${memberIndex}][id_number]">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الجنس <span class="required">*</span></label>
                        <select class="form-select" name="family_members[${memberIndex}][gender]" required>
                            <option value="">اختر الجنس</option>
                            <option value="male">ذكر</option>
                            <option value="female">أنثى</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">تاريخ الميلاد <span class="required">*</span></label>
                        <div class="row">
                            <div class="col-4">
                                <select class="form-select" name="family_members[${memberIndex}][birth_day]" required>
                                    <option value="">اليوم</option>
                                    ${Array.from({length: 31}, (_, i) => `<option value="${i+1}">${i+1}</option>`).join('')}
                                </select>
                            </div>
                            <div class="col-4">
                                <select class="form-select" name="family_members[${memberIndex}][birth_month]" required>
                                    <option value="">الشهر</option>
                                    <option value="01">يناير</option>
                                    <option value="02">فبراير</option>
                                    <option value="03">مارس</option>
                                    <option value="04">أبريل</option>
                                    <option value="05">مايو</option>
                                    <option value="06">يونيو</option>
                                    <option value="07">يوليو</option>
                                    <option value="08">أغسطس</option>
                                    <option value="09">سبتمبر</option>
                                    <option value="10">أكتوبر</option>
                                    <option value="11">نوفمبر</option>
                                    <option value="12">ديسمبر</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <select class="form-select" name="family_members[${memberIndex}][birth_year]" required>
                                    <option value="">السنة</option>
                                    ${Array.from({length: 105}, (_, i) => `<option value="${2024-i}">${2024-i}</option>`).join('')}
                                </select>
                            </div>
                        </div>
                        <input type="hidden" name="family_members[${memberIndex}][birth_date]" class="member-birth-date">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">العلاقة <span class="required">*</span></label>
                        <select class="form-select" name="family_members[${memberIndex}][relationship]" required>
                            <option value="">اختر العلاقة</option>
                            <option value="son">ابن</option>
                            <option value="daughter">ابنة</option>
                            <option value="father">أب</option>
                            <option value="mother">أم</option>
                            <option value="brother">أخ</option>
                            <option value="sister">أخت</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الحالة الصحية</label>
                        <select class="form-select" name="family_members[${memberIndex}][health_status]">
                            <option value="">اختر الحالة الصحية</option>
                            <option value="healthy">سليم</option>
                            <option value="hypertension">ضغط</option>
                            <option value="diabetes">سكري</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-10">
                        <label class="form-label">تفاصيل الحالة الصحية</label>
                        <textarea class="form-control" name="family_members[${memberIndex}][health_details]" rows="2"></textarea>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeFamilyMember(this)">
                            <i class="fas fa-trash me-1"></i>
                            حذف
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(newCard);
            memberIndex++;
        }
        
        // حذف فرد
        function removeFamilyMember(button) {
            button.closest('.family-member-card').remove();
        }
        
        // تحديث التواريخ عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            updateHiddenDates();
            updateMemberDates();
        });
        
        // تأكيد الحفظ
        document.getElementById('familyForm').addEventListener('submit', function(e) {
            if (!confirm('هل أنت متأكد من حفظ التغييرات؟')) {
                e.preventDefault();
            }
        });
        
        // التحكم في نافذة تغيير كلمة المرور
        const newPasswordModal = document.getElementById('new_password_modal');
        const confirmPasswordModal = document.getElementById('confirm_password_modal');
        const submitPasswordBtn = document.getElementById('submitPasswordBtn');
        const strengthFillModal = document.getElementById('strengthFillModal');
        const strengthTextModal = document.getElementById('strengthTextModal');
        
        // السماح بإدخال الأرقام فقط
        newPasswordModal.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            checkPasswordStrengthModal();
            validatePasswordForm();
        });
        
        confirmPasswordModal.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            validatePasswordForm();
        });
        
        function checkPasswordStrengthModal() {
            const password = newPasswordModal.value;
            const length = password.length;
            
            if (length === 0) {
                strengthFillModal.className = 'strength-fill';
                strengthTextModal.textContent = 'أدخل كلمة المرور';
                return;
            }

            if (length < 8) {
                strengthFillModal.className = 'strength-fill strength-weak';
                strengthTextModal.textContent = 'ضعيف - يجب أن تكون 8 أرقام';
                return;
            }

            // تحقق من التكرار
            const uniqueDigits = new Set(password).size;
            
            if (uniqueDigits === 1) {
                strengthFillModal.className = 'strength-fill strength-weak';
                strengthTextModal.textContent = 'ضعيف جداً - جميع الأرقام متشابهة';
            } else if (uniqueDigits <= 3) {
                strengthFillModal.className = 'strength-fill strength-medium';
                strengthTextModal.textContent = 'متوسط - جرب أرقام أكثر تنوعاً';
            } else if (uniqueDigits <= 6) {
                strengthFillModal.className = 'strength-fill strength-strong';
                strengthTextModal.textContent = 'قوي - كلمة مرور جيدة';
            } else {
                strengthFillModal.className = 'strength-fill strength-very-strong';
                strengthTextModal.textContent = 'قوي جداً - ممتاز!';
            }
        }
        
        function validatePasswordForm() {
            const newPass = newPasswordModal.value;
            const confirmPass = confirmPasswordModal.value;
            
            if (newPass.length === 8 && confirmPass.length === 8 && newPass === confirmPass) {
                submitPasswordBtn.disabled = false;
                confirmPasswordModal.setCustomValidity('');
            } else {
                submitPasswordBtn.disabled = true;
                if (confirmPass.length > 0 && newPass !== confirmPass) {
                    confirmPasswordModal.setCustomValidity('كلمة المرور غير متطابقة');
                } else {
                    confirmPasswordModal.setCustomValidity('');
                }
            }
        }
        
        // إعادة تعيين النافذة عند إغلاقها
        document.getElementById('changePasswordModal').addEventListener('hidden.bs.modal', function() {
            newPasswordModal.value = '';
            confirmPasswordModal.value = '';
            submitPasswordBtn.disabled = true;
            strengthFillModal.className = 'strength-fill';
            strengthTextModal.textContent = 'أدخل كلمة المرور';
        });
        
        // تأكيد تغيير كلمة المرور
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            if (!confirm('هل أنت متأكد من تغيير كلمة المرور؟')) {
                e.preventDefault();
            }
        });
        
        // دالة إضافة أفراد الأسرة (مثل صفحة التسجيل)
        function addFamilyMembers() {
            const count = parseInt(document.querySelector('input[name="family_members_count"]').value) || 0;
            if (count <= 0) {
                alert('يرجى إدخال عدد أفراد الأسرة أولاً');
                return;
            }

            let html = '';
            for (let i = 0; i < count; i++) {
                html += `
                    <div class="family-member-card" data-member="${i}">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">عضو الأسرة ${i + 1}</h6>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeMember(${i})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">الاسم الرباعي <span class="required">*</span></label>
                                <input type="text" class="form-control" name="family_members[${i}][full_name]" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">رقم الهوية <span class="required">*</span></label>
                                <input type="text" class="form-control" name="family_members[${i}][id_number]" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">الجنس <span class="required">*</span></label>
                                <select class="form-select" name="family_members[${i}][gender]" required>
                                    <option value="">اختر الجنس</option>
                                    <option value="male">ذكر</option>
                                    <option value="female">أنثى</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">تاريخ الميلاد <span class="required">*</span></label>
                                <div class="row">
                                    <div class="col-4">
                                        <select class="form-select" name="family_members[${i}][birth_day]" required>
                                            <option value="">اليوم</option>
                                            ${Array.from({length: 31}, (_, i) => `<option value="${i+1}">${i+1}</option>`).join('')}
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <select class="form-select" name="family_members[${i}][birth_month]" required>
                                            <option value="">الشهر</option>
                                            <option value="01">يناير</option>
                                            <option value="02">فبراير</option>
                                            <option value="03">مارس</option>
                                            <option value="04">أبريل</option>
                                            <option value="05">مايو</option>
                                            <option value="06">يونيو</option>
                                            <option value="07">يوليو</option>
                                            <option value="08">أغسطس</option>
                                            <option value="09">سبتمبر</option>
                                            <option value="10">أكتوبر</option>
                                            <option value="11">نوفمبر</option>
                                            <option value="12">ديسمبر</option>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <select class="form-select" name="family_members[${i}][birth_year]" required>
                                            <option value="">السنة</option>
                                            ${Array.from({length: 105}, (_, i) => `<option value="${2024-i}">${2024-i}</option>`).join('')}
                                        </select>
                                    </div>
                                </div>
                                <input type="hidden" name="family_members[${i}][birth_date]" id="family_member_birth_date_${i}">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <label class="form-label">الصلة <span class="required">*</span></label>
                                <select class="form-select" name="family_members[${i}][relationship]" required>
                                    <option value="">اختر الصلة</option>
                                    <option value="son">ابن</option>
                                    <option value="daughter">ابنة</option>
                                    <option value="father">أب</option>
                                    <option value="mother">أم</option>
                                    <option value="brother">أخ</option>
                                    <option value="sister">أخت</option>
                                    <option value="grandfather">جد</option>
                                    <option value="grandmother">جدة</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">الحالة الصحية <span class="required">*</span></label>
                                <select class="form-select" name="family_members[${i}][health_status]" required onchange="toggleMemberHealthDetails(${i}, this)">
                                    <option value="">اختر الحالة الصحية</option>
                                    <option value="healthy">سليم</option>
                                    <option value="hypertension">ضغط</option>
                                    <option value="diabetes">سكري</option>
                                    <option value="other">أخرى</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="member-health-details-${i}" style="display: none;">
                                <label class="form-label">تفاصيل الحالة الصحية</label>
                                <textarea class="form-control" name="family_members[${i}][health_details]" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                `;
            }
            document.getElementById('family-members-container').innerHTML = html;
            
            // إضافة مستمعي الأحداث للتواريخ الجديدة
            for (let i = 0; i < count; i++) {
                const birthDay = document.querySelector(`select[name="family_members[${i}][birth_day]"]`);
                const birthMonth = document.querySelector(`select[name="family_members[${i}][birth_month]"]`);
                const birthYear = document.querySelector(`select[name="family_members[${i}][birth_year]"]`);
                const birthDateHidden = document.getElementById(`family_member_birth_date_${i}`);
                
                [birthDay, birthMonth, birthYear].forEach(select => {
                    select.addEventListener('change', function() {
                        updateDateField(birthDay, birthMonth, birthYear, birthDateHidden);
                    });
                });
            }
        }

        function removeMember(index) {
            document.querySelector(`[data-member="${index}"]`).remove();
        }
        
        function toggleMemberHealthDetails(memberIndex, selectElement) {
            const healthDetailsContainer = document.getElementById(`member-health-details-${memberIndex}`);
            if (selectElement.value === 'other') {
                healthDetailsContainer.style.display = 'block';
            } else {
                healthDetailsContainer.style.display = 'none';
                healthDetailsContainer.querySelector('textarea').value = '';
            }
        }
        
        // دالة لتجميع التواريخ
        function updateDateField(daySelect, monthSelect, yearSelect, hiddenField) {
            const day = daySelect.value;
            const month = monthSelect.value;
            const year = yearSelect.value;
            
            if (day && month && year) {
                const date = year + '-' + month + '-' + day;
                hiddenField.value = date;
            }
        }

        // إدارة سؤال الأمان
        document.getElementById('security_question').addEventListener('change', function() {
            const customDiv = document.getElementById('custom_question_div');
            if (this.value === 'other') {
                customDiv.style.display = 'block';
                document.getElementById('custom_question').required = true;
            } else {
                customDiv.style.display = 'none';
                document.getElementById('custom_question').required = false;
            }
        });

        // التحقق من صحة نموذج سؤال الأمان
        document.getElementById('securityQuestionForm').addEventListener('submit', function(e) {
            const question = document.getElementById('security_question').value;
            const customQuestion = document.getElementById('custom_question').value;
            const answer = document.getElementById('security_answer').value;
            
            if (question === 'other' && !customQuestion.trim()) {
                e.preventDefault();
                alert('يرجى كتابة سؤال الأمان الخاص بك');
                return;
            }
            
            if (!answer.trim()) {
                e.preventDefault();
                alert('يرجى إدخال إجابة سؤال الأمان');
                return;
            }
        });
    </script>
</body>
</html>
