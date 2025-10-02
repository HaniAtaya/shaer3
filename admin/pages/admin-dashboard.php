<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../auth/admin-login.php');
    exit;
}

// الاتصال بقاعدة البيانات
try {
    $pdo = new PDO('mysql:host=localhost;dbname=family_orphans_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage());
}

// جلب الإحصائيات
$stats = [];

// عدد الأيتام
$stmt = $pdo->query("SELECT COUNT(*) FROM orphans");
$stats['orphans_count'] = $stmt->fetchColumn();

// عدد الأسر
$stmt = $pdo->query("SELECT COUNT(*) FROM families");
$stats['families_count'] = $stmt->fetchColumn();

// عدد الأبناء (أفراد العائلات)
$stmt = $pdo->query("SELECT COUNT(*) FROM family_members");
$stats['family_members_count'] = $stmt->fetchColumn();

// عدد الرضع
$stmt = $pdo->query("SELECT COUNT(*) FROM infants");
$stats['infants_count'] = $stmt->fetchColumn();

// عدد الوفيات
$stmt = $pdo->query("SELECT COUNT(*) FROM deaths");
$stats['deaths_count'] = $stmt->fetchColumn();

// عدد المشرفين
$stmt = $pdo->query("SELECT COUNT(*) FROM admins");
$stats['admins_count'] = $stmt->fetchColumn();

// أكثر الفرع العائلي انتشاراً
$stmt = $pdo->query("SELECT family_branch, COUNT(*) as count FROM families GROUP BY family_branch ORDER BY count DESC LIMIT 1");
$most_common_branch = $stmt->fetch(PDO::FETCH_ASSOC);

// توزيع الأسر حسب المحافظات
try {
    $stmt = $pdo->query("SELECT original_governorate, COUNT(*) as count FROM families WHERE original_governorate IS NOT NULL AND original_governorate != '' GROUP BY original_governorate ORDER BY count DESC");
    $governorates_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $governorates_data = [];
}

// ترجمة أسماء المحافظات
$governorate_names = [
    'gaza' => 'غزة',
    'khan_younis' => 'خانيونس',
    'rafah' => 'رفح',
    'middle' => 'الوسطى',
    'north_gaza' => 'شمال غزة'
];

$governorates_distribution = [];
foreach ($governorates_data as $row) {
    if (!empty($row['original_governorate']) && !empty($row['count'])) {
        $governorates_distribution[] = [
            'governorate' => $governorate_names[$row['original_governorate']] ?? $row['original_governorate'],
            'count' => (int)$row['count']
        ];
    }
}

// توزيع الأيتام حسب الجنس
try {
    $stmt = $pdo->query("SELECT orphan_gender as gender, COUNT(*) as count FROM orphans WHERE orphan_gender IS NOT NULL AND orphan_gender != '' GROUP BY orphan_gender");
    $gender_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $gender_data = [];
}

// ترجمة الجنس
$gender_names = [
    'male' => 'ذكر',
    'female' => 'أنثى'
];

$orphans_by_gender = [];
foreach ($gender_data as $row) {
    if (!empty($row['gender']) && !empty($row['count'])) {
        $orphans_by_gender[] = [
            'gender' => $gender_names[$row['gender']] ?? $row['gender'],
            'count' => (int)$row['count']
        ];
    }
}

// الحالات الصحية
$stmt = $pdo->query("SELECT health_status, COUNT(*) as count FROM family_members GROUP BY health_status");
$health_status_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

// أحدث العائلات
$stmt = $pdo->query("SELECT * FROM families ORDER BY id DESC LIMIT 5");
$latest_families = $stmt->fetchAll(PDO::FETCH_ASSOC);

// أحدث الأيتام
$stmt = $pdo->query("SELECT * FROM orphans ORDER BY id DESC LIMIT 5");
$latest_orphans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - الشاعر عائلتي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: fixed;
            top: 0;
            right: 0;
            width: 250px;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .sidebar.collapsed {
            width: 70px;
        }
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar-header h4 {
            color: white;
            margin: 0;
            font-weight: 600;
        }
        .sidebar-menu {
            padding: 1rem 0;
        }
        .sidebar-menu .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            border: none;
            background: none;
            text-align: right;
            transition: all 0.3s ease;
        }
        .sidebar-menu .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        .sidebar-menu .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
        }
        .main-content {
            margin-right: 250px;
            padding: 2rem;
            transition: all 0.3s ease;
        }
        .main-content.expanded {
            margin-right: 70px;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: none;
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        .chart-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }
        .chart-container h5 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-right: 0.5rem;
        }
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }
        .table-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }
        .table-container h5 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-right: 0.5rem;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa;
        }
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            margin: 0 0.1rem;
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #495057;
        }
        .admin-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include '../../includes/admin-sidebar.php'; ?>

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">الشاعر عائلتي - لوحة التحكم الرئيسية</h1>
            <div>
                <a href="../../public/registration/family-registration.php" class="btn btn-primary me-2">
                    <i class="fas fa-plus me-1"></i>
                    إضافة عائلة
                </a>
                <a href="../../public/registration/orphan-registration.php" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i>
                    إضافة يتيم
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stats-icon text-success">
                                <i class="fas fa-child"></i>
                            </div>
                            <div class="stats-number text-success"><?php echo number_format($stats['orphans_count']); ?></div>
                            <div class="stats-label">إجمالي الأيتام</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stats-icon text-primary">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stats-number text-primary"><?php echo number_format($stats['families_count']); ?></div>
                            <div class="stats-label">إجمالي الأسر</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stats-icon text-info">
                                <i class="fas fa-user-friends"></i>
                            </div>
                            <div class="stats-number text-info"><?php echo number_format($stats['family_members_count']); ?></div>
                            <div class="stats-label">إجمالي الأبناء</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stats-icon text-warning">
                                <i class="fas fa-baby"></i>
                            </div>
                            <div class="stats-number text-warning"><?php echo number_format($stats['infants_count']); ?></div>
                            <div class="stats-label">إجمالي الرضع</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stats-icon text-dark">
                                <i class="fas fa-cross"></i>
                            </div>
                            <div class="stats-number text-dark"><?php echo number_format($stats['deaths_count']); ?></div>
                            <div class="stats-label">إجمالي الوفيات</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stats-icon text-secondary">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div class="stats-number text-secondary"><?php echo number_format($stats['admins_count']); ?></div>
                            <div class="stats-label">إجمالي المشرفين</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-lg-6 mb-3">
                <div class="chart-container">
                    <h5 class="mb-3">
                        <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                        توزيع الأسر حسب المحافظات
                    </h5>
                    <div style="position: relative; height: 300px;">
                        <canvas id="governoratesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="chart-container">
                    <h5 class="mb-3">
                        <i class="fas fa-venus-mars me-2 text-success"></i>
                        توزيع الأيتام حسب الجنس
                    </h5>
                    <div style="position: relative; height: 300px;">
                        <canvas id="genderChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Latest Data Tables -->
        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="table-container">
                    <h5 class="mb-3">أحدث العائلات المسجلة</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>اسم رب الأسرة</th>
                                    <th>رقم الهوية</th>
                                    <th>المحافظة</th>
                                    <th>التاريخ</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latest_families as $family): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($family['first_name'] . ' ' . $family['father_name'] . ' ' . $family['grandfather_name'] . ' ' . $family['family_name']); ?></td>
                                    <td><?php echo htmlspecialchars($family['id_number']); ?></td>
                                    <td><?php echo htmlspecialchars($family['original_governorate']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($family['id'])); ?></td>
                                    <td>
                                        <a href="../management/admin-families.php?action=view&id=<?php echo $family['id']; ?>" class="btn btn-sm btn-outline-primary btn-action">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../management/admin-families.php?action=edit&id=<?php echo $family['id']; ?>" class="btn btn-sm btn-outline-warning btn-action">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="table-container">
                    <h5 class="mb-3">أحدث الأيتام المسجلين</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>اسم الطفل</th>
                                    <th>رقم الهوية</th>
                                    <th>المسؤول</th>
                                    <th>التاريخ</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latest_orphans as $orphan): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($orphan['orphan_full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($orphan['orphan_id_number']); ?></td>
                                    <td><?php echo htmlspecialchars($orphan['guardian_full_name']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($orphan['id'])); ?></td>
                                    <td>
                                        <a href="../management/admin-orphans.php?action=view&id=<?php echo $orphan['id']; ?>" class="btn btn-sm btn-outline-primary btn-action">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../management/admin-orphans.php?action=edit&id=<?php echo $orphan['id']; ?>" class="btn btn-sm btn-outline-warning btn-action">
                                            <i class="fas fa-edit"></i>
                                        </a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }

        // Governorates Chart
        const governoratesCtx = document.getElementById('governoratesChart').getContext('2d');
        const governoratesData = <?php echo json_encode($governorates_distribution); ?>;
        
        if (governoratesData && governoratesData.length > 0) {
            new Chart(governoratesCtx, {
                type: 'doughnut',
                data: {
                    labels: governoratesData.map(item => item.governorate),
                    datasets: [{
                        data: governoratesData.map(item => item.count),
                        backgroundColor: [
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0',
                            '#9966FF',
                            '#FF9F40',
                            '#FF6B6B',
                            '#4ECDC4',
                            '#45B7D1',
                            '#96CEB4'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        } else {
            // عرض رسالة عدم وجود بيانات
            governoratesCtx.canvas.parentElement.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-chart-pie fa-3x mb-3"></i><p>لا توجد بيانات للعرض</p></div>';
        }

        // Gender Chart
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        const genderData = <?php echo json_encode($orphans_by_gender); ?>;
        
        if (genderData && genderData.length > 0) {
            new Chart(genderCtx, {
                type: 'pie',
                data: {
                    labels: genderData.map(item => item.gender),
                    datasets: [{
                        data: genderData.map(item => item.count),
                        backgroundColor: [
                            '#36A2EB',
                            '#FF6384',
                            '#4BC0C0',
                            '#FFCE56'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        } else {
            // عرض رسالة عدم وجود بيانات
            genderCtx.canvas.parentElement.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-chart-pie fa-3x mb-3"></i><p>لا توجد بيانات للعرض</p></div>';
        }
    </script>
    <script src="../../assets/js/admin-sidebar.js"></script>
</body>
</html>

