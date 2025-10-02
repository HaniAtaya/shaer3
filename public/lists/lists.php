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

// إحصائيات
$familiesCount = $pdo->query("SELECT COUNT(*) FROM families")->fetchColumn();
$orphansCount = $pdo->query("SELECT COUNT(*) FROM orphans")->fetchColumn();
$infantsCount = $pdo->query("SELECT COUNT(*) FROM infants")->fetchColumn();
$deathsCount = $pdo->query("SELECT COUNT(*) FROM deaths")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>القوائم - نظام جمع بيانات العائلات والأيتام</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .list-card { 
            transition: transform 0.3s ease, box-shadow 0.3s ease; 
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
        .list-card:hover { 
            transform: translateY(-10px); 
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        .list-card .card-body {
            padding: 2rem;
        }
        .stat-number { 
            font-size: 3rem; 
            font-weight: bold; 
            margin-bottom: 0.5rem;
        }
        .list-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 3rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">
                <i class="fas fa-heart me-2"></i>
                نظام جمع بيانات العائلات والأيتام
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="admin-dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i>
                    لوحة القيادة
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-12">
                    <h1 class="display-4 fw-bold mb-4">
                        <i class="fas fa-list-alt me-3"></i>
                        القوائم
                    </h1>
                    <p class="lead mb-0">
                        عرض وإدارة جميع البيانات المسجلة في النظام
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="container py-5">
        <!-- Statistics Overview -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-center mb-4">نظرة عامة على الإحصائيات</h2>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-users text-primary list-icon"></i>
                        <div class="stat-number text-primary"><?php echo $familiesCount; ?></div>
                        <h5>إجمالي العائلات</h5>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-child text-success list-icon"></i>
                        <div class="stat-number text-success"><?php echo $orphansCount; ?></div>
                        <h5>إجمالي الأيتام</h5>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-baby text-info list-icon"></i>
                        <div class="stat-number text-info"><?php echo $infantsCount; ?></div>
                        <h5>إجمالي الرضع</h5>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-cross text-dark list-icon"></i>
                        <div class="stat-number text-dark"><?php echo $deathsCount; ?></div>
                        <h5>إجمالي الوفيات</h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lists Cards -->
        <div class="row g-4">
            <!-- قائمة العائلات -->
            <div class="col-lg-6 col-md-6">
                <div class="card list-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-users text-primary list-icon"></i>
                        <h3 class="card-title mb-3">قائمة العائلات</h3>
                        <p class="card-text mb-4">
                            عرض وإدارة بيانات جميع العائلات المسجلة في النظام مع إمكانية البحث والفلترة
                        </p>
                        <div class="mb-4">
                            <span class="badge bg-primary fs-6"><?php echo $familiesCount; ?> عائلة</span>
                        </div>
                        <a href="families-list.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-list me-2"></i>
                            عرض القائمة
                        </a>
                    </div>
                </div>
            </div>

            <!-- قائمة الأيتام -->
            <div class="col-lg-6 col-md-6">
                <div class="card list-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-child text-success list-icon"></i>
                        <h3 class="card-title mb-3">قائمة الأيتام</h3>
                        <p class="card-text mb-4">
                            عرض وإدارة بيانات جميع الأيتام المسجلين مع تفاصيل المسؤولين والأب المتوفي
                        </p>
                        <div class="mb-4">
                            <span class="badge bg-success fs-6"><?php echo $orphansCount; ?> يتيم</span>
                        </div>
                        <a href="orphans-list.php" class="btn btn-success btn-lg">
                            <i class="fas fa-list me-2"></i>
                            عرض القائمة
                        </a>
                    </div>
                </div>
            </div>

            <!-- قائمة الرضع -->
            <div class="col-lg-6 col-md-6">
                <div class="card list-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-baby text-info list-icon"></i>
                        <h3 class="card-title mb-3">قائمة الرضع</h3>
                        <p class="card-text mb-4">
                            عرض وإدارة بيانات الأطفال الرضع (أقل من سنتين) المسجلين للحصول على الحليب والبامبرز
                        </p>
                        <div class="mb-4">
                            <span class="badge bg-info fs-6"><?php echo $infantsCount; ?> رضيع</span>
                        </div>
                        <a href="infants-list.php" class="btn btn-info btn-lg">
                            <i class="fas fa-list me-2"></i>
                            عرض القائمة
                        </a>
                    </div>
                </div>
            </div>

            <!-- قائمة الوفيات -->
            <div class="col-lg-6 col-md-6">
                <div class="card list-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-cross text-dark list-icon"></i>
                        <h3 class="card-title mb-3">قائمة الوفيات</h3>
                        <p class="card-text mb-4">
                            عرض وإدارة بيانات الوفيات والشهداء المسجلين مع تفاصيل مدخل الطلب وسبب الوفاة
                        </p>
                        <div class="mb-4">
                            <span class="badge bg-dark fs-6"><?php echo $deathsCount; ?> حالة وفاة</span>
                        </div>
                        <a href="deaths-list.php" class="btn btn-dark btn-lg">
                            <i class="fas fa-list me-2"></i>
                            عرض القائمة
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bolt me-2"></i>
                            إجراءات سريعة
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="family-registration.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-plus me-2"></i>
                                    إضافة عائلة جديدة
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="orphan-registration.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-plus me-2"></i>
                                    إضافة يتيم جديد
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="infant-registration.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-plus me-2"></i>
                                    إضافة رضيع جديد
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="death-registration.php" class="btn btn-outline-dark w-100">
                                    <i class="fas fa-plus me-2"></i>
                                    إضافة وفاة جديدة
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
