<?php
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

// تضمين دالة توليد كلمة المرور
require_once '../../includes/generate-access-code.php';

$success = '';
$error = '';

if ($_POST) {
    try {
        // بدء المعاملة
        $pdo->beginTransaction();
        
        // إدراج بيانات الأسرة
        $stmt = $pdo->prepare("
            INSERT INTO families (
                first_name, father_name, grandfather_name, family_name, id_number, gender, 
                birth_date, id_issue_date, family_branch, primary_phone, secondary_phone, 
                health_status, health_details, marital_status, spouse_first_name, 
                spouse_father_name, spouse_grandfather_name, spouse_family_name, 
                spouse_id_number, spouse_gender, spouse_birth_date, spouse_id_issue_date, 
                spouse_family_branch, spouse_primary_phone, spouse_secondary_phone, 
                spouse_health_status, spouse_health_details, original_governorate, 
                original_area, original_neighborhood, displacement_governorate, 
                displacement_area, displacement_neighborhood, housing_status, 
                family_members_count
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            null, // spouse_family_branch - تم حذفه
            null, // spouse_primary_phone - تم حذفه
            null, // spouse_secondary_phone - تم حذفه
            $_POST['marital_status'] == 'married' ? $_POST['spouse_health_status'] : null,
            $_POST['marital_status'] == 'married' ? $_POST['spouse_health_details'] : null,
            $_POST['original_governorate'], $_POST['original_area'], $_POST['original_neighborhood'],
            $_POST['displacement_governorate'], $_POST['displacement_area'], 
            $_POST['displacement_neighborhood'], $_POST['housing_status'], 
            $_POST['family_members_count']
        ]);
        
        $familyId = $pdo->lastInsertId();
        
        // إدراج أفراد الأسرة
        if (isset($_POST['family_members']) && is_array($_POST['family_members'])) {
            $memberStmt = $pdo->prepare("
                INSERT INTO family_members (family_id, full_name, id_number, gender, 
                birth_date, relationship, health_status, health_details) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($_POST['family_members'] as $member) {
                $member_birth_date = $member['birth_year'] . '-' . $member['birth_month'] . '-' . $member['birth_day'];
                $memberStmt->execute([
                    $familyId, $member['full_name'], $member['id_number'], 
                    $member['gender'], $member_birth_date, $member['relationship'], 
                    $member['health_status'], $member['health_details']
                ]);
            }
        }
        
        // إعداد اسم العائلة للإرسال
        $family_name = $_POST['first_name'] . ' ' . $_POST['family_name'];
        
        // توليد كلمة المرور التلقائية
        $access_code = generateAccessCode($_POST['id_number'], $birth_date);
        
        // حفظ كلمة المرور في قاعدة البيانات
        if (saveAccessCode($pdo, $familyId, $access_code)) {
            // إرسال كلمة المرور عبر الرسائل النصية (محاكاة)
            sendAccessCodeSMS($_POST['primary_phone'], $access_code, $family_name);
        }
        
        $pdo->commit();
        $success = "تم تسجيل بيانات الأسرة بنجاح! كلمة مرور تحديث البيانات: <strong>$access_code</strong><br><small class='text-muted'>احتفظ بهذه الكلمة لتحديث بياناتك لاحقاً</small>";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "حدث خطأ: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل بيانات الأسرة - الشاعر عائلتي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-section { background: #f8f9fa; border-radius: 10px; padding: 2rem; margin-bottom: 2rem; }
        .section-title { color: #0d6efd; border-bottom: 2px solid #0d6efd; padding-bottom: 0.5rem; margin-bottom: 1.5rem; }
        .required { color: #dc3545; }
        .family-member-card { border: 1px solid #dee2e6; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; background: white; }
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
                <a class="nav-link active" href="family-registration.php">
                    <i class="fas fa-users me-1"></i>
                    تسجيل الأسرة
                </a>
                <a class="nav-link" href="orphan-registration.php">
                    <i class="fas fa-child me-1"></i>
                    تسجيل اليتيم
                </a>
                <a class="nav-link" href="infant-registration.php">
                    <i class="fas fa-baby me-1"></i>
                    تسجيل الرضع
                </a>
                <a class="nav-link" href="death-registration.php">
                    <i class="fas fa-cross me-1"></i>
                    تسجيل الوفيات
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
                            <i class="fas fa-users me-2"></i>
                            تسجيل بيانات الأسرة
                        </h2>
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
                                        <input type="text" class="form-control" name="first_name" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">اسم الأب <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="father_name" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">اسم الجد <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="grandfather_name" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">اسم العائلة <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="family_name" required>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-3">
                                        <label class="form-label">رقم الهوية <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="id_number" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">الجنس <span class="required">*</span></label>
                                        <select class="form-select" name="gender" required>
                                            <option value="">اختر الجنس</option>
                                            <option value="male">ذكر</option>
                                            <option value="female">أنثى</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">تاريخ الميلاد <span class="required">*</span></label>
                                        <div class="row">
                                            <div class="col-4">
                                                <select class="form-select" name="birth_day" required>
                                                    <option value="">اليوم</option>
                                                    <?php for($i = 1; $i <= 31; $i++): ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select" name="birth_month" required>
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
                                                <select class="form-select" name="birth_year" required>
                                                    <option value="">السنة</option>
                                                    <?php for($i = 2024; $i >= 1920; $i--): ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
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
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select" name="id_issue_month" required>
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
                                                <select class="form-select" name="id_issue_year" required>
                                                    <option value="">السنة</option>
                                                    <?php for($i = 2024; $i >= 1950; $i--): ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
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
                                            <option value="الجواهرة">الجواهرة</option>
                                            <option value="العواودة">العواودة</option>
                                            <option value="البشيتي">البشيتي</option>
                                            <option value="زقماط">زقماط</option>
                                            <option value="حندش والحمادين ابوحمدان">حندش والحمادين ابوحمدان</option>
                                            <option value="مقلد">مقلد</option>
                                            <option value="الدجاجنة">الدجاجنة</option>
                                            <option value="قريده">قريده</option>
                                            <option value="الصوفي">الصوفي</option>
                                            <option value="مصبح">مصبح</option>
                                            <option value="قرقوش">قرقوش</option>
                                            <option value="العوايضة">العوايضة</option>
                                            <option value="السوالمة">السوالمة</option>
                                            <option value="عرادة">عرادة</option>
                                            <option value="البراهمة">البراهمة</option>
                                            <option value="العيسة">العيسة</option>
                                            <option value="المحامدة">المحامدة</option>
                                            <option value="ابوسري">ابوسري</option>
                                            <option value="المهاوشة">المهاوشة</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">رقم الهاتف الأساسي <span class="required">*</span></label>
                                        <input type="tel" class="form-control" name="primary_phone" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">رقم الهاتف البديل</label>
                                        <input type="tel" class="form-control" name="secondary_phone">
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label">الحالة الصحية <span class="required">*</span></label>
                                        <select class="form-select" name="health_status" required onchange="toggleHealthDetails(this)">
                                            <option value="">اختر الحالة الصحية</option>
                                            <option value="healthy">سليم</option>
                                            <option value="hypertension">ضغط</option>
                                            <option value="diabetes">سكري</option>
                                            <option value="other">أخرى</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="health-details-container" style="display: none;">
                                        <label class="form-label">تفاصيل الحالة الصحية</label>
                                        <textarea class="form-control" name="health_details" rows="2"></textarea>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label">الحالة الاجتماعية <span class="required">*</span></label>
                                        <select class="form-select" name="marital_status" required onchange="toggleSpouseFields()">
                                            <option value="">اختر الحالة الاجتماعية</option>
                                            <option value="married">متزوج/ة</option>
                                            <option value="divorced">مطلق/ة</option>
                                            <option value="widowed">أرمل/ة</option>
                                            <option value="elderly">مسن/ة</option>
                                            <option value="provider">معيل/ة</option>
                                            <option value="special_needs">ذوي احتياجات خاصة</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- بيانات الزوج/ة -->
                            <div class="form-section" id="spouse-section" style="display: none;">
                                <h4 class="section-title">
                                    <i class="fas fa-heart me-2"></i>
                                    بيانات الزوج/ة
                                </h4>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">الاسم الشخصي <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="spouse_first_name">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">اسم الأب <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="spouse_father_name">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">اسم الجد <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="spouse_grandfather_name">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">اسم العائلة <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="spouse_family_name">
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-3">
                                        <label class="form-label">رقم الهوية <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="spouse_id_number">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">الجنس <span class="required">*</span></label>
                                        <select class="form-select" name="spouse_gender">
                                            <option value="">اختر الجنس</option>
                                            <option value="male">ذكر</option>
                                            <option value="female">أنثى</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">تاريخ الميلاد <span class="required">*</span></label>
                                        <div class="row">
                                            <div class="col-4">
                                                <select class="form-select" name="spouse_birth_day">
                                                    <option value="">اليوم</option>
                                                    <?php for($i = 1; $i <= 31; $i++): ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select" name="spouse_birth_month">
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
                                                <select class="form-select" name="spouse_birth_year">
                                                    <option value="">السنة</option>
                                                    <?php for($i = 2024; $i >= 1920; $i--): ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
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
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select" name="spouse_id_issue_month">
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
                                                <select class="form-select" name="spouse_id_issue_year">
                                                    <option value="">السنة</option>
                                                    <?php for($i = 2024; $i >= 1950; $i--): ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
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
                                            <option value="healthy">سليم</option>
                                            <option value="hypertension">ضغط</option>
                                            <option value="diabetes">سكري</option>
                                            <option value="other">أخرى</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="spouse-health-details-container" style="display: none;">
                                        <label class="form-label">تفاصيل الحالة الصحية</label>
                                        <textarea class="form-control" name="spouse_health_details" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- العنوان الأصلي -->
                            <div class="form-section">
                                <h4 class="section-title">
                                    <i class="fas fa-home me-2"></i>
                                    العنوان الأصلي
                                </h4>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">المحافظة <span class="required">*</span></label>
                                        <select class="form-select" name="original_governorate" required>
                                            <option value="">اختر المحافظة</option>
                                            <option value="gaza">غزة</option>
                                            <option value="khan_younis">خانيونس</option>
                                            <option value="rafah">رفح</option>
                                            <option value="middle">الوسطى</option>
                                            <option value="north_gaza">شمال غزة</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">المنطقة <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="original_area" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">الحي أو أقرب معلم <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="original_neighborhood" required>
                                    </div>
                                </div>
                            </div>

                            <!-- عنوان النزوح -->
                            <div class="form-section">
                                <h4 class="section-title">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    عنوان النزوح
                                </h4>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">المحافظة <span class="required">*</span></label>
                                        <select class="form-select" name="displacement_governorate" required>
                                            <option value="">اختر المحافظة</option>
                                            <option value="gaza">غزة</option>
                                            <option value="khan_younis">خانيونس</option>
                                            <option value="rafah">رفح</option>
                                            <option value="middle">الوسطى</option>
                                            <option value="north_gaza">شمال غزة</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">المنطقة <span class="required">*</span></label>
                                        <select class="form-select" name="displacement_area" required>
                                            <option value="">اختر المنطقة</option>
                                            <option value="فش فرش">فش فرش</option>
                                            <option value="العطار">العطار</option>
                                            <option value="جامع عثمان بن عفان">جامع عثمان بن عفان</option>
                                            <option value="شاليه باريس">شاليه باريس</option>
                                            <option value="شاليه ملك">شاليه ملك</option>
                                            <option value="شارع زعرب">شارع زعرب</option>
                                            <option value="بئر 19">بئر 19</option>
                                            <option value="السنية">السنية</option>
                                            <option value="ارض عويص">ارض عويص</option>
                                            <option value="بئر 20">بئر 20</option>
                                            <option value="الاقليمي">الاقليمي</option>
                                            <option value="الاقصى">الاقصى</option>
                                            <option value="شارع روني">شارع روني</option>
                                            <option value="ساند بيتش">ساند بيتش</option>
                                            <option value="دوار النص">دوار النص</option>
                                            <option value="البلدة">البلدة</option>
                                            <option value="بطن السمين">بطن السمين</option>
                                            <option value="شاله بلو سكاي">شاله بلو سكاي</option>
                                            <option value="الجواهرة">الجواهرة</option>
                                            <option value="الدراوشة">الدراوشة</option>
                                            <option value="المسلخ التركي">المسلخ التركي</option>
                                            <option value="محطة ايتا">محطة ايتا</option>
                                            <option value="بئر الشاعر وزنون">بئر الشاعر وزنون</option>
                                            <option value="شاليه اللؤلؤة">شاليه اللؤلؤة</option>
                                            <option value="شاليه الالماني">شاليه الالماني</option>
                                            <option value="منطقة الدراوشة">منطقة الدراوشة</option>
                                            <option value="منطقة الجواهرة">منطقة الجواهرة</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">الحي <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="displacement_neighborhood" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">حالة السكن <span class="required">*</span></label>
                                        <select class="form-select" name="housing_status" required>
                                            <option value="">اختر حالة السكن</option>
                                            <option value="tent">خيمة</option>
                                            <option value="apartment">شقة</option>
                                            <option value="house">بيت</option>
                                            <option value="school">مدرسة</option>
                                        </select>
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
                                        <input type="number" class="form-control" name="family_members_count" min="0" required>
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
                                    حفظ البيانات
                                </button>
                                <a href="../../index.php" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-times me-2"></i>
                                    إلغاء
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleHealthDetails(selectElement) {
            const healthDetailsContainer = document.getElementById('health-details-container');
            if (selectElement.value === 'other') {
                healthDetailsContainer.style.display = 'block';
            } else {
                healthDetailsContainer.style.display = 'none';
                healthDetailsContainer.querySelector('textarea').value = '';
            }
        }

        function toggleSpouseHealthDetails(selectElement) {
            const healthDetailsContainer = document.getElementById('spouse-health-details-container');
            if (selectElement.value === 'other') {
                healthDetailsContainer.style.display = 'block';
            } else {
                healthDetailsContainer.style.display = 'none';
                healthDetailsContainer.querySelector('textarea').value = '';
            }
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

        function toggleSpouseFields() {
            const maritalStatus = document.querySelector('select[name="marital_status"]').value;
            const spouseSection = document.getElementById('spouse-section');
            
            if (maritalStatus === 'married') {
                spouseSection.style.display = 'block';
                // جعل الحقول مطلوبة
                spouseSection.querySelectorAll('input, select').forEach(field => {
                    field.required = true;
                });
            } else {
                spouseSection.style.display = 'none';
                // إزالة الطلب من الحقول
                spouseSection.querySelectorAll('input, select').forEach(field => {
                    field.required = false;
                });
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

        // إضافة مستمعي الأحداث للتواريخ
        document.addEventListener('DOMContentLoaded', function() {
            // تاريخ الميلاد
            const birthDay = document.querySelector('select[name="birth_day"]');
            const birthMonth = document.querySelector('select[name="birth_month"]');
            const birthYear = document.querySelector('select[name="birth_year"]');
            const birthDateHidden = document.getElementById('birth_date');
            
            [birthDay, birthMonth, birthYear].forEach(select => {
                select.addEventListener('change', function() {
                    updateDateField(birthDay, birthMonth, birthYear, birthDateHidden);
                });
            });

            // تاريخ إصدار الهوية
            const idIssueDay = document.querySelector('select[name="id_issue_day"]');
            const idIssueMonth = document.querySelector('select[name="id_issue_month"]');
            const idIssueYear = document.querySelector('select[name="id_issue_year"]');
            const idIssueDateHidden = document.getElementById('id_issue_date');
            
            [idIssueDay, idIssueMonth, idIssueYear].forEach(select => {
                select.addEventListener('change', function() {
                    updateDateField(idIssueDay, idIssueMonth, idIssueYear, idIssueDateHidden);
                });
            });

            // تاريخ ميلاد الزوج/ة
            const spouseBirthDay = document.querySelector('select[name="spouse_birth_day"]');
            const spouseBirthMonth = document.querySelector('select[name="spouse_birth_month"]');
            const spouseBirthYear = document.querySelector('select[name="spouse_birth_year"]');
            const spouseBirthDateHidden = document.getElementById('spouse_birth_date');
            
            if (spouseBirthDay) {
                [spouseBirthDay, spouseBirthMonth, spouseBirthYear].forEach(select => {
                    select.addEventListener('change', function() {
                        updateDateField(spouseBirthDay, spouseBirthMonth, spouseBirthYear, spouseBirthDateHidden);
                    });
                });
            }

            // تاريخ إصدار هوية الزوج/ة
            const spouseIdIssueDay = document.querySelector('select[name="spouse_id_issue_day"]');
            const spouseIdIssueMonth = document.querySelector('select[name="spouse_id_issue_month"]');
            const spouseIdIssueYear = document.querySelector('select[name="spouse_id_issue_year"]');
            const spouseIdIssueDateHidden = document.getElementById('spouse_id_issue_date');
            
            if (spouseIdIssueDay) {
                [spouseIdIssueDay, spouseIdIssueMonth, spouseIdIssueYear].forEach(select => {
                    select.addEventListener('change', function() {
                        updateDateField(spouseIdIssueDay, spouseIdIssueMonth, spouseIdIssueYear, spouseIdIssueDateHidden);
                    });
                });
            }
        });

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
    </script>
</body>
</html>
