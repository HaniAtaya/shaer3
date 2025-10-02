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
$warMartyrsCount = $pdo->query("SELECT COUNT(*) FROM orphans WHERE is_war_martyr = 1")->fetchColumn();

// إحصائيات المحافظات
$governorateStats = $pdo->query("
    SELECT 
        displacement_governorate,
        COUNT(*) as count
    FROM families 
    GROUP BY displacement_governorate
")->fetchAll(PDO::FETCH_ASSOC);

// إحصائيات الحالة الصحية
$healthStats = $pdo->query("
    SELECT 
        health_status,
        COUNT(*) as count
    FROM families 
    GROUP BY health_status
")->fetchAll(PDO::FETCH_ASSOC);

// الفروع العائلية
$familyBranches = $pdo->query("
    SELECT 
        family_branch,
        COUNT(*) as count
    FROM families 
    GROUP BY family_branch
    ORDER BY count DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// العائلات
$families = $pdo->query("
    SELECT f.*, 
           (SELECT COUNT(*) FROM family_members fm WHERE fm.family_id = f.id) as members_count
    FROM families f 
    ORDER BY f.created_at DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// الأيتام
$orphans = $pdo->query("
    SELECT * FROM orphans 
    ORDER BY created_at DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - الشاعر عائلتي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card { background: white; border-radius: 15px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .stat-number { font-size: 3rem; font-weight: bold; color: #0d6efd; }
        .chart-container { background: white; border-radius: 15px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .table th { background-color: #f8f9fa; font-weight: 600; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-heart me-2"></i>
                الشاعر عائلتي
            </a>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-5 fw-bold text-primary">
                    <i class="fas fa-heart me-3"></i>
                    الشاعر عائلتي - لوحة التحكم
                </h1>
                <p class="lead text-muted">نظرة عامة على البيانات والإحصائيات</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card text-center">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <div class="stat-number"><?php echo $familiesCount; ?></div>
                    <h5>إجمالي العائلات</h5>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-card text-center">
                    <i class="fas fa-child fa-3x text-success mb-3"></i>
                    <div class="stat-number"><?php echo $orphansCount; ?></div>
                    <h5>إجمالي الأيتام</h5>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-card text-center">
                    <i class="fas fa-flag fa-3x text-danger mb-3"></i>
                    <div class="stat-number"><?php echo $warMartyrsCount; ?></div>
                    <h5>شهداء الحرب</h5>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-card text-center">
                    <i class="fas fa-map-marker-alt fa-3x text-info mb-3"></i>
                    <div class="stat-number">5</div>
                    <h5>المحافظات</h5>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row">
            <div class="col-lg-6">
                <div class="chart-container">
                    <h4 class="mb-3">توزيع العائلات حسب المحافظات</h4>
                    <canvas id="governorateChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="chart-container">
                    <h4 class="mb-3">الحالة الصحية للعائلات</h4>
                    <canvas id="healthChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Family Branches -->
        <div class="row">
            <div class="col-lg-8">
                <div class="chart-container">
                    <h4 class="mb-3">أكثر الفروع العائلية انتشاراً</h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>الترتيب</th>
                                    <th>الفرع العائلي</th>
                                    <th>عدد العائلات</th>
                                    <th>النسبة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($familyBranches as $index => $branch): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $branch['family_branch']; ?></td>
                                    <td><?php echo $branch['count']; ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo ($branch['count'] / $familiesCount) * 100; ?>%">
                                                <?php echo number_format(($branch['count'] / $familiesCount) * 100, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="chart-container">
                    <h4 class="mb-3">الإجراءات السريعة</h4>
                    <div class="d-grid gap-2">
                        <a href="family-registration.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            إضافة عائلة جديدة
                        </a>
                        <a href="orphan-registration.php" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>
                            إضافة يتيم جديد
                        </a>
                        <a href="families-list.php" class="btn btn-info">
                            <i class="fas fa-list me-2"></i>
                            عرض جميع العائلات
                        </a>
                        <a href="orphans-list.php" class="btn btn-warning">
                            <i class="fas fa-list me-2"></i>
                            عرض جميع الأيتام
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Data -->
        <div class="row">
            <div class="col-lg-6">
                <div class="chart-container">
                    <h4 class="mb-3">أحدث العائلات المسجلة</h4>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>رقم الهوية</th>
                                    <th>المحافظة</th>
                                    <th>عدد الأبناء</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($families as $family): ?>
                                <tr>
                                    <td><?php echo $family['first_name'] . ' ' . $family['family_name']; ?></td>
                                    <td><code><?php echo $family['id_number']; ?></code></td>
                                    <td>
                                        <?php
                                        $governorates = [
                                            'gaza' => 'غزة',
                                            'khan_younis' => 'خانيونس',
                                            'rafah' => 'رفح',
                                            'middle' => 'الوسطى',
                                            'north_gaza' => 'شمال غزة'
                                        ];
                                        echo $governorates[$family['displacement_governorate']] ?? $family['displacement_governorate'];
                                        ?>
                                    </td>
                                    <td><span class="badge bg-secondary"><?php echo $family['members_count']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="chart-container">
                    <h4 class="mb-3">أحدث الأيتام المسجلين</h4>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>اسم اليتيم</th>
                                    <th>اسم المسؤول</th>
                                    <th>شهيد حرب</th>
                                    <th>المحافظة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orphans as $orphan): ?>
                                <tr>
                                    <td><?php echo $orphan['orphan_full_name']; ?></td>
                                    <td><?php echo $orphan['guardian_full_name']; ?></td>
                                    <td>
                                        <?php if ($orphan['is_war_martyr']): ?>
                                            <span class="badge bg-danger">شهيد حرب</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">غير شهيد حرب</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $governorates = [
                                            'gaza' => 'غزة',
                                            'khan_younis' => 'خانيونس',
                                            'rafah' => 'رفح',
                                            'middle' => 'الوسطى',
                                            'north_gaza' => 'شمال غزة'
                                        ];
                                        echo $governorates[$orphan['displacement_governorate']] ?? $orphan['displacement_governorate'];
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Governorate Chart
        const governorateCtx = document.getElementById('governorateChart').getContext('2d');
        new Chart(governorateCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($governorateStats, 'displacement_governorate')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($governorateStats, 'count')); ?>,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Health Chart
        const healthCtx = document.getElementById('healthChart').getContext('2d');
        new Chart(healthCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($healthStats, 'health_status')); ?>,
                datasets: [{
                    label: 'عدد العائلات',
                    data: <?php echo json_encode(array_column($healthStats, 'count')); ?>,
                    backgroundColor: '#36A2EB'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
