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

// إنشاء الجداول إذا لم تكن موجودة
$createTables = "
CREATE TABLE IF NOT EXISTS families (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(255) NOT NULL,
    father_name VARCHAR(255) NOT NULL,
    grandfather_name VARCHAR(255) NOT NULL,
    family_name VARCHAR(255) NOT NULL,
    id_number VARCHAR(20) UNIQUE NOT NULL,
    gender ENUM('male', 'female') NOT NULL,
    birth_date DATE NOT NULL,
    id_issue_date DATE NOT NULL,
    family_branch VARCHAR(255) NOT NULL,
    primary_phone VARCHAR(20) NOT NULL,
    secondary_phone VARCHAR(20),
    health_status ENUM('healthy', 'hypertension', 'diabetes', 'other') NOT NULL,
    health_details TEXT,
    marital_status ENUM('married', 'divorced', 'widowed', 'elderly', 'provider', 'special_needs') NOT NULL,
    spouse_first_name VARCHAR(255),
    spouse_father_name VARCHAR(255),
    spouse_grandfather_name VARCHAR(255),
    spouse_family_name VARCHAR(255),
    spouse_id_number VARCHAR(20),
    spouse_gender ENUM('male', 'female'),
    spouse_birth_date DATE,
    spouse_id_issue_date DATE,
    spouse_family_branch VARCHAR(255),
    spouse_primary_phone VARCHAR(20),
    spouse_secondary_phone VARCHAR(20),
    spouse_health_status ENUM('healthy', 'hypertension', 'diabetes', 'other'),
    spouse_health_details TEXT,
    original_governorate ENUM('gaza', 'khan_younis', 'rafah', 'middle', 'north_gaza') NOT NULL,
    original_area VARCHAR(255) NOT NULL,
    original_neighborhood VARCHAR(255) NOT NULL,
    displacement_governorate ENUM('gaza', 'khan_younis', 'rafah', 'middle', 'north_gaza') NOT NULL,
    displacement_area VARCHAR(255) NOT NULL,
    displacement_neighborhood VARCHAR(255) NOT NULL,
    housing_status ENUM('tent', 'apartment', 'house', 'school') NOT NULL,
    family_members_count INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS family_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    family_id INT NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    id_number VARCHAR(20) NOT NULL,
    gender ENUM('male', 'female') NOT NULL,
    birth_date DATE NOT NULL,
    relationship ENUM('son', 'daughter', 'father', 'mother', 'brother', 'sister', 'grandfather', 'grandmother') NOT NULL,
    health_status ENUM('healthy', 'hypertension', 'diabetes', 'other') NOT NULL,
    health_details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orphans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guardian_full_name VARCHAR(255) NOT NULL,
    guardian_id_number VARCHAR(20) NOT NULL,
    guardian_gender ENUM('male', 'female') NOT NULL,
    guardian_birth_date DATE NOT NULL,
    guardian_relationship ENUM('son', 'daughter', 'brother', 'sister', 'grandfather', 'grandmother', 'mother', 'father') NOT NULL,
    guardian_primary_phone VARCHAR(20) NOT NULL,
    guardian_secondary_phone VARCHAR(20),
    deceased_father_name VARCHAR(255) NOT NULL,
    deceased_father_id_number VARCHAR(20) NOT NULL,
    martyrdom_date DATE NOT NULL,
    death_certificate_image VARCHAR(255),
    orphan_full_name VARCHAR(255) NOT NULL,
    orphan_id_number VARCHAR(20) UNIQUE NOT NULL,
    orphan_gender ENUM('male', 'female') NOT NULL,
    orphan_birth_date DATE NOT NULL,
    orphan_health_status ENUM('healthy', 'hypertension', 'diabetes', 'other') NOT NULL,
    orphan_health_details TEXT,
    orphan_image VARCHAR(255),
    is_war_martyr BOOLEAN DEFAULT FALSE,
    displacement_governorate ENUM('gaza', 'khan_younis', 'rafah', 'middle', 'north_gaza') NOT NULL,
    displacement_area VARCHAR(255) NOT NULL,
    displacement_neighborhood VARCHAR(255) NOT NULL,
    housing_status ENUM('tent', 'apartment', 'house', 'school') NOT NULL,
    bank_name VARCHAR(255),
    bank_phone VARCHAR(20),
    account_number VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
";

$pdo->exec($createTables);

// إحصائيات
$familiesCount = $pdo->query("SELECT COUNT(*) FROM families")->fetchColumn();
$orphansCount = $pdo->query("SELECT COUNT(*) FROM orphans")->fetchColumn();
$warMartyrsCount = $pdo->query("SELECT COUNT(*) FROM orphans WHERE is_war_martyr = 1")->fetchColumn();
$infantsCount = $pdo->query("SELECT COUNT(*) FROM infants")->fetchColumn();
$deathsCount = $pdo->query("SELECT COUNT(*) FROM deaths")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الشاعر عائلتي - نظام جمع بيانات العائلات والأيتام</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f8f9fa; }
        .hero-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4rem 0; }
        .stat-card { background: white; border-radius: 15px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .stat-number { font-size: 3rem; font-weight: bold; color: #0d6efd; }
        .feature-card { transition: transform 0.3s ease; }
        .feature-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-heart me-2"></i>
                الشاعر عائلتي
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="admin/auth/admin-login.php">
                    <i class="fas fa-sign-in-alt me-1"></i>
                    تسجيل دخول الإدارة
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-4">الشاعر عائلتي</h1>
                    <p class="lead mb-4">
                        نظام متكامل لجمع وإدارة بيانات العائلات والأيتام والشهداء والرضع، 
                        يوفر حلولاً شاملة لتوثيق المعلومات وتقديم المساعدة المطلوبة.
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="public/registration/family-registration.php" class="btn btn-light btn-lg">
                            <i class="fas fa-users me-2"></i>
                            تسجيل بيانات الأسرة
                        </a>
                        <a href="public/family/family-login.php" class="btn btn-warning btn-lg">
                            <i class="fas fa-edit me-2"></i>
                            تحديث بيانات الأسرة
                        </a>
                        <a href="public/registration/orphan-registration.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-child me-2"></i>
                            تسجيل بيانات الأيتام
                        </a>
                        <a href="public/registration/death-registration.php" class="btn btn-danger btn-lg">
                            <i class="fas fa-cross me-2"></i>
                            تسجيل بيانات الأموات والشهداء
                        </a>
                        <a href="public/registration/infant-registration.php" class="btn btn-info btn-lg">
                            <i class="fas fa-baby me-2"></i>
                            تسجيل بيانات الأطفال الرضع
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 text-center">
                    <i class="fas fa-heart" style="font-size: 8rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics -->
    <section class="py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2 class="display-5 fw-bold mb-3">الإحصائيات</h2>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="stat-card text-center">
                        <i class="fas fa-users fa-3x text-primary mb-3"></i>
                        <div class="stat-number"><?php echo $familiesCount; ?></div>
                        <h5>إجمالي العائلات</h5>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="stat-card text-center">
                        <i class="fas fa-child fa-3x text-success mb-3"></i>
                        <div class="stat-number"><?php echo $orphansCount; ?></div>
                        <h5>إجمالي الأيتام</h5>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="stat-card text-center">
                        <i class="fas fa-baby fa-3x text-info mb-3"></i>
                        <div class="stat-number"><?php echo $infantsCount; ?></div>
                        <h5>الرضع</h5>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="stat-card text-center">
                        <i class="fas fa-cross fa-3x text-dark mb-3"></i>
                        <div class="stat-number"><?php echo $deathsCount; ?></div>
                        <h5>الوفيات</h5>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="stat-card text-center">
                        <i class="fas fa-flag fa-3x text-danger mb-3"></i>
                        <div class="stat-number"><?php echo $warMartyrsCount; ?></div>
                        <h5>شهداء الحرب</h5>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="stat-card text-center">
                        <i class="fas fa-map-marker-alt fa-3x text-warning mb-3"></i>
                        <div class="stat-number">5</div>
                        <h5>المحافظات</h5>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2 class="display-5 fw-bold mb-3">المميزات الرئيسية</h2>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card feature-card h-100 text-center p-4">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="fas fa-users text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">تسجيل بيانات الأسرة</h5>
                            <p class="card-text">
                                نظام متكامل لتسجيل جميع بيانات الأسرة بما في ذلك معلومات رب الأسرة، 
                                الزوج/الزوجة، والأبناء مع تفاصيل شاملة.
                            </p>
                            <a href="public/registration/family-registration.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>
                                تسجيل أسرة جديدة
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card feature-card h-100 text-center p-4">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="fas fa-edit text-warning" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">تحديث بيانات الأسرة</h5>
                            <p class="card-text">
                                تحديث بيانات الأسرة المسجلة مسبقاً باستخدام كلمة المرور المكونة من 8 أرقام 
                                التي تم إعطاؤها لك عند التسجيل.
                            </p>
                            <a href="public/family/family-login.php" class="btn btn-warning">
                                <i class="fas fa-edit me-2"></i>
                                تحديث البيانات
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card feature-card h-100 text-center p-4">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="fas fa-child text-success" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">تسجيل بيانات الأيتام</h5>
                            <p class="card-text">
                                تسجيل شامل لبيانات الأيتام مع معلومات المسؤول، الأب المتوفي، 
                                والعنوان الحالي مع إمكانية رفع الصور.
                            </p>
                            <a href="public/registration/orphan-registration.php" class="btn btn-success">
                                <i class="fas fa-plus me-2"></i>
                                تسجيل يتيم جديد
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card feature-card h-100 text-center p-4">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="fas fa-cross text-danger" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">تسجيل بيانات الأموات والشهداء</h5>
                            <p class="card-text">
                                تسجيل بيانات الأموات والشهداء مع معلومات مدخل الطلب 
                                وسبب الوفاة وصورة شخصية.
                            </p>
                            <a href="public/registration/death-registration.php" class="btn btn-danger">
                                <i class="fas fa-plus me-2"></i>
                                تسجيل وفاة جديدة
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card feature-card h-100 text-center p-4">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="fas fa-baby text-info" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">تسجيل بيانات الأطفال الرضع</h5>
                            <p class="card-text">
                                تسجيل بيانات الأطفال الرضع (أقل من سنتين) للحصول على 
                                الحليب والبامبرز مع شهادة الميلاد.
                            </p>
                            <a href="public/registration/infant-registration.php" class="btn btn-info">
                                <i class="fas fa-plus me-2"></i>
                                تسجيل رضيع جديد
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-heart me-2"></i>الشاعر عائلتي</h5>
                    <p>نظام متكامل لجمع وإدارة بيانات العائلات والأيتام والشهداء والرضع</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6>معلومات التواصل</h6>
                    <p><i class="fas fa-user me-2"></i> Hani Alshaer</p>
                    <p><i class="fas fa-phone me-2"></i> 00970593804084</p>
                    <p><i class="fas fa-envelope me-2"></i> haatayani@gmail.com</p>
                </div>
            </div>
            <hr class="my-3">
            <div class="text-center">
                <p>&copy; 2025 جميع الحقوق محفوظة لدي الشاعر عائلتي</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
