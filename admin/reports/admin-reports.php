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

// إجمالي العائلات
$stmt = $pdo->query("SELECT COUNT(*) as total FROM families");
$stats['total_families'] = $stmt->fetch()['total'];

// إجمالي الأيتام
$stmt = $pdo->query("SELECT COUNT(*) as total FROM orphans");
$stats['total_orphans'] = $stmt->fetch()['total'];

// توزيع العائلات حسب المحافظة
$stmt = $pdo->query("SELECT original_governorate, COUNT(*) as count FROM families GROUP BY original_governorate ORDER BY count DESC");
$families_by_governorate = $stmt->fetchAll(PDO::FETCH_ASSOC);

// توزيع الأيتام حسب الجنس
$stmt = $pdo->query("SELECT orphan_gender, COUNT(*) as count FROM orphans GROUP BY orphan_gender");
$orphans_by_gender = $stmt->fetchAll(PDO::FETCH_ASSOC);

// توزيع العائلات حسب الحالة الاجتماعية
$stmt = $pdo->query("SELECT marital_status, COUNT(*) as count FROM families GROUP BY marital_status");
$families_by_marital = $stmt->fetchAll(PDO::FETCH_ASSOC);

// الأيتام من أبناء الشهداء
$stmt = $pdo->query("SELECT COUNT(*) as count FROM orphans WHERE is_war_martyr = 1");
$stats['war_martyrs_children'] = $stmt->fetch()['count'];

// العائلات الأكثر عدداً
$stmt = $pdo->query("SELECT f.*, COUNT(fm.id) as members_count FROM families f LEFT JOIN family_members fm ON f.id = fm.family_id GROUP BY f.id ORDER BY members_count DESC LIMIT 10");
$largest_families = $stmt->fetchAll(PDO::FETCH_ASSOC);

// المحافظات الأكثر
$stmt = $pdo->query("SELECT original_governorate, COUNT(*) as count FROM families GROUP BY original_governorate ORDER BY count DESC LIMIT 5");
$top_governorates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// قائمة المحافظات
$all_governorates = [
    'damascus' => 'دمشق',
    'aleppo' => 'حلب',
    'homs' => 'حمص',
    'hama' => 'حماة',
    'latakia' => 'اللاذقية',
    'tartus' => 'طرطوس',
    'idlib' => 'إدلب',
    'raqqa' => 'الرقة',
    'deir_ezzur' => 'دير الزور',
    'hasaka' => 'الحسكة',
    'daraa' => 'درعا',
    'sweida' => 'السويداء',
    'quneitra' => 'القنيطرة',
    'damascus_countryside' => 'ريف دمشق'
];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير المتقدمة - نظام إدارة العائلات والأيتام</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin-sidebar.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .report-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        .btn-export {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../../includes/admin-sidebar.php'; ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-chart-bar me-2"></i>التقارير المتقدمة</h2>
                    <div>
                        <button class="btn btn-export me-2" onclick="exportAllReports()">
                            <i class="fas fa-download me-1"></i>
                            تصدير جميع التقارير
                        </button>
                        <button class="btn btn-outline-primary" onclick="printReports()">
                            <i class="fas fa-print me-1"></i>
                            طباعة
                        </button>
                    </div>
                </div>

                <!-- إحصائيات عامة -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x mb-2"></i>
                                <h3><?php echo $stats['total_families']; ?></h3>
                                <p class="mb-0">إجمالي العائلات</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-child fa-2x mb-2"></i>
                                <h3><?php echo $stats['total_orphans']; ?></h3>
                                <p class="mb-0">إجمالي الأيتام</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-flag fa-2x mb-2"></i>
                                <h3><?php echo $stats['war_martyrs_children']; ?></h3>
                                <p class="mb-0">أبناء الشهداء</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <h3><?php echo count($all_governorates); ?></h3>
                                <p class="mb-0">المحافظات المشمولة</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- الرسوم البيانية -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-pie me-2"></i>توزيع العائلات حسب المحافظة</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="governorateChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-pie me-2"></i>توزيع الأيتام حسب الجنس</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="genderChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-bar me-2"></i>توزيع العائلات حسب الحالة الاجتماعية</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="maritalChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-bar me-2"></i>أكثر المحافظات</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="topGovernoratesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- جداول تفصيلية -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-users me-2"></i>أكبر العائلات</h5>
                                <button class="btn btn-sm btn-outline-primary" onclick="exportLargestFamilies()">
                                    <i class="fas fa-download me-1"></i>تصدير
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>اسم رب الأسرة</th>
                                                <th>عدد الأفراد</th>
                                                <th>المحافظة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($largest_families as $index => $family): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($family['first_name'] . ' ' . $family['father_name'] . ' ' . $family['grandfather_name'] . ' ' . $family['family_name']); ?></td>
                                                <td><span class="badge bg-primary"><?php echo $family['members_count']; ?></span></td>
                                                <td><?php echo $all_governorates[$family['original_governorate']] ?? $family['original_governorate']; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-map-marker-alt me-2"></i>توزيع المحافظات</h5>
                                <button class="btn btn-sm btn-outline-primary" onclick="exportGovernorates()">
                                    <i class="fas fa-download me-1"></i>تصدير
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>المحافظة</th>
                                                <th>عدد العائلات</th>
                                                <th>النسبة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_governorates as $index => $gov): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo $all_governorates[$gov['original_governorate']] ?? $gov['original_governorate']; ?></td>
                                                <td><span class="badge bg-info"><?php echo $gov['count']; ?></span></td>
                                                <td><?php echo round(($gov['count'] / $stats['total_families']) * 100, 1); ?>%</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-sidebar.js"></script>
    <script>
        // توزيع العائلات حسب المحافظة
        const governorateCtx = document.getElementById('governorateChart').getContext('2d');
        const governorateData = <?php echo json_encode($families_by_governorate); ?>;
        
        const governorateLabels = governorateData.map(item => {
            const govNames = <?php echo json_encode($all_governorates); ?>;
            return govNames[item.original_governorate] || item.original_governorate;
        });
        const governorateCounts = governorateData.map(item => item.count);
        
        new Chart(governorateCtx, {
            type: 'doughnut',
            data: {
                labels: governorateLabels,
                datasets: [{
                    data: governorateCounts,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // توزيع الأيتام حسب الجنس
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        const genderData = <?php echo json_encode($orphans_by_gender); ?>;
        
        const genderLabels = genderData.map(item => item.orphan_gender === 'male' ? 'ذكر' : 'أنثى');
        const genderCounts = genderData.map(item => item.count);
        
        new Chart(genderCtx, {
            type: 'pie',
            data: {
                labels: genderLabels,
                datasets: [{
                    data: genderCounts,
                    backgroundColor: ['#36A2EB', '#FF6384']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // توزيع العائلات حسب الحالة الاجتماعية
        const maritalCtx = document.getElementById('maritalChart').getContext('2d');
        const maritalData = <?php echo json_encode($families_by_marital); ?>;
        
        const maritalLabels = maritalData.map(item => {
            const maritalNames = {
                'married': 'متزوج',
                'divorced': 'مطلق',
                'widowed': 'أرمل',
                'elderly': 'مسن',
                'provider': 'معيل',
                'special_needs': 'احتياجات خاصة'
            };
            return maritalNames[item.marital_status] || item.marital_status;
        });
        const maritalCounts = maritalData.map(item => item.count);
        
        new Chart(maritalCtx, {
            type: 'bar',
            data: {
                labels: maritalLabels,
                datasets: [{
                    label: 'عدد العائلات',
                    data: maritalCounts,
                    backgroundColor: '#36A2EB'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // أكثر المحافظات
        const topGovCtx = document.getElementById('topGovernoratesChart').getContext('2d');
        const topGovData = <?php echo json_encode($top_governorates); ?>;
        
        const topGovLabels = topGovData.map(item => {
            const govNames = <?php echo json_encode($all_governorates); ?>;
            return govNames[item.original_governorate] || item.original_governorate;
        });
        const topGovCounts = topGovData.map(item => item.count);
        
        new Chart(topGovCtx, {
            type: 'bar',
            data: {
                labels: topGovLabels,
                datasets: [{
                    label: 'عدد العائلات',
                    data: topGovCounts,
                    backgroundColor: '#FF6384'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // وظائف التصدير
        function exportAllReports() {
            window.open('export-all-reports.php', '_blank');
        }

        function exportLargestFamilies() {
            window.open('export-largest-families.php', '_blank');
        }

        function exportGovernorates() {
            window.open('export-governorates.php', '_blank');
        }

        function printReports() {
            window.print();
        }
    </script>
    <script src="assets/js/admin-sidebar.js"></script>
</body>
</html>
