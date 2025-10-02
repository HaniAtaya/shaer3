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

$success = '';
$error = '';

if ($_POST) {
    try {
        // رفع الصور
        $personalPhoto = '';
        
        if (isset($_FILES['personal_photo']) && $_FILES['personal_photo']['error'] == 0) {
            $uploadDir = 'uploads/death_photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = $_POST['id_number'] . '_personal_photo.' . pathinfo($_FILES['personal_photo']['name'], PATHINFO_EXTENSION);
            $personalPhoto = $uploadDir . $fileName;
            move_uploaded_file($_FILES['personal_photo']['tmp_name'], $personalPhoto);
        }
        
        // تجميع التواريخ
        $birth_date = $_POST['birth_year'] . '-' . $_POST['birth_month'] . '-' . $_POST['birth_day'];
        $death_date = $_POST['death_year'] . '-' . $_POST['death_month'] . '-' . $_POST['death_day'];
        
        // إدراج بيانات المتوفي
        $stmt = $pdo->prepare("
            INSERT INTO deaths (
                first_name, father_name, grandfather_name, family_name, id_number, 
                birth_date, death_date, family_branch, governorate, death_reason,
                requester_first_name, requester_father_name, requester_grandfather_name, 
                requester_family_name, requester_relationship, personal_photo
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['first_name'], $_POST['father_name'], $_POST['grandfather_name'], 
            $_POST['family_name'], $_POST['id_number'], $birth_date, $death_date,
            $_POST['family_branch'], $_POST['governorate'], $_POST['death_reason'],
            $_POST['requester_first_name'], $_POST['requester_father_name'], 
            $_POST['requester_grandfather_name'], $_POST['requester_family_name'],
            $_POST['requester_relationship'], $personalPhoto
        ]);
        
        $success = "تم تسجيل بيانات المتوفي بنجاح!";
        
    } catch (Exception $e) {
        $error = "حدث خطأ: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل بيانات الوفيات - الشاعر عائلتي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-section { background: #f8f9fa; border-radius: 10px; padding: 2rem; margin-bottom: 2rem; }
        .section-title { color: #0d6efd; border-bottom: 2px solid #0d6efd; padding-bottom: 0.5rem; margin-bottom: 1.5rem; }
        .required { color: #dc3545; }
        .image-preview { max-width: 200px; max-height: 200px; border-radius: 8px; border: 2px solid #dee2e6; }
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
                <a class="nav-link" href="family-registration.php">
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
                <a class="nav-link active" href="death-registration.php">
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
                    <div class="card-header bg-dark text-white">
                        <h2 class="card-title mb-0">
                            <i class="fas fa-cross me-2"></i>
                            تسجيل بيانات الوفيات
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

                        <form method="POST" enctype="multipart/form-data">
                            <!-- معلومات المتوفي -->
                            <div class="form-section">
                                <h4 class="section-title">
                                    <i class="fas fa-user-times me-2"></i>
                                    معلومات المتوفي
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
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">تاريخ الوفاة <span class="required">*</span></label>
                                        <div class="row">
                                            <div class="col-4">
                                                <select class="form-select" name="death_day" required>
                                                    <option value="">اليوم</option>
                                                    <?php for($i = 1; $i <= 31; $i++): ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select" name="death_month" required>
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
                                                <select class="form-select" name="death_year" required>
                                                    <option value="">السنة</option>
                                                    <?php for($i = 2024; $i >= 2020; $i--): ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
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
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <label class="form-label">المحافظة <span class="required">*</span></label>
                                        <select class="form-select" name="governorate" required>
                                            <option value="">اختر المحافظة</option>
                                            <option value="gaza">غزة</option>
                                            <option value="khan_younis">خانيونس</option>
                                            <option value="rafah">رفح</option>
                                            <option value="middle">الوسطى</option>
                                            <option value="north_gaza">شمال غزة</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">سبب الوفاة <span class="required">*</span></label>
                                        <select class="form-select" name="death_reason" required>
                                            <option value="">اختر سبب الوفاة</option>
                                            <option value="martyr">شهيد</option>
                                            <option value="bombing">قصف</option>
                                            <option value="disease">مرض</option>
                                            <option value="accident">حادث</option>
                                            <option value="natural">وفاة طبيعية</option>
                                            <option value="other">أخرى</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">صورة شخصية <span class="required">*</span></label>
                                        <input type="file" class="form-control" name="personal_photo" accept="image/*" onchange="previewImage(this, 'personal-photo-preview')" required>
                                        <div class="form-text">الصور المقبولة: JPG, PNG, GIF - الحد الأقصى: 2 ميجابايت</div>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div id="personal-photo-preview" class="mt-2"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- معلومات مدخل الطلب -->
                            <div class="form-section">
                                <h4 class="section-title">
                                    <i class="fas fa-user-edit me-2"></i>
                                    معلومات مدخل الطلب
                                </h4>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">الاسم الشخصي <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="requester_first_name" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">اسم الأب <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="requester_father_name" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">اسم الجد <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="requester_grandfather_name" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">اسم العائلة <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="requester_family_name" required>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label">الصلة <span class="required">*</span></label>
                                        <select class="form-select" name="requester_relationship" required>
                                            <option value="">اختر الصلة</option>
                                            <option value="son">ابن</option>
                                            <option value="daughter">ابنة</option>
                                            <option value="brother">أخ</option>
                                            <option value="sister">أخت</option>
                                            <option value="father">أب</option>
                                            <option value="mother">أم</option>
                                            <option value="grandfather">جد</option>
                                            <option value="grandmother">جدة</option>
                                            <option value="uncle">عم</option>
                                            <option value="aunt">عمة</option>
                                            <option value="cousin">ابن عم/خال</option>
                                            <option value="other">أخرى</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- أزرار التحكم -->
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-dark btn-lg me-3">
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
        function previewImage(input, previewId) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById(previewId).innerHTML = `
                        <img src="${e.target.result}" class="image-preview" alt="معاينة الصورة">
                    `;
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>
