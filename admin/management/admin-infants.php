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

// معالجة العمليات
$action = $_GET['action'] ?? '';
$infant_id = $_GET['id'] ?? '';

$success_message = '';
$error_message = '';

if ($_POST) {
    if (isset($_POST['delete_infant'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM infants WHERE id = ?");
            $stmt->execute([$infant_id]);
            $success_message = 'تم حذف بيانات الرضيع بنجاح';
        } catch (PDOException $e) {
            $error_message = 'خطأ في حذف بيانات الرضيع: ' . $e->getMessage();
        }
    }
}

// معالجة الفلترة
$search = $_GET['search'] ?? '';
$family_branch = $_GET['family_branch'] ?? '';
$governorate = $_GET['governorate'] ?? '';
$age_filter = $_GET['age_filter'] ?? '';

// بناء استعلام الفلترة
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(first_name LIKE ? OR father_name LIKE ? OR grandfather_name LIKE ? OR family_name LIKE ? OR id_number LIKE ? OR primary_phone LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
}

if (!empty($family_branch)) {
    $where_conditions[] = "family_branch = ?";
    $params[] = $family_branch;
}

if (!empty($governorate)) {
    $where_conditions[] = "displacement_governorate = ?";
    $params[] = $governorate;
}

if (!empty($age_filter)) {
    $current_date = date('Y-m-d');
    switch ($age_filter) {
        case '0-6_months':
            $where_conditions[] = "birth_date >= DATE_SUB(?, INTERVAL 6 MONTH)";
            $params[] = $current_date;
            break;
        case '6-12_months':
            $where_conditions[] = "birth_date BETWEEN DATE_SUB(?, INTERVAL 12 MONTH) AND DATE_SUB(?, INTERVAL 6 MONTH)";
            $params[] = $current_date;
            $params[] = $current_date;
            break;
        case '1-2_years':
            $where_conditions[] = "birth_date BETWEEN DATE_SUB(?, INTERVAL 2 YEAR) AND DATE_SUB(?, INTERVAL 1 YEAR)";
            $params[] = $current_date;
            $params[] = $current_date;
            break;
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// جلب قائمة الرضع
try {
    $sql = "SELECT * FROM infants $where_clause ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $infants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // التأكد من وجود البيانات
    if (empty($infants)) {
        $infants = [];
    }
} catch (PDOException $e) {
    $error_message = 'خطأ في جلب قائمة الرضع: ' . $e->getMessage();
    $infants = [];
}

// جلب قائمة الفروع العائلية للفلتر
$family_branches = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT family_branch FROM infants ORDER BY family_branch");
    $family_branches = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // تجاهل الخطأ
}

// جلب الإحصائيات
$stats = [];
try {
    // إجمالي الرضع
    $stats['total'] = $pdo->query("SELECT COUNT(*) FROM infants")->fetchColumn();
    
    // الرضع حسب الفئة العمرية
    $current_date = date('Y-m-d');
    $stats['0_6_months'] = $pdo->query("SELECT COUNT(*) FROM infants WHERE birth_date >= DATE_SUB('$current_date', INTERVAL 6 MONTH)")->fetchColumn();
    $stats['6_12_months'] = $pdo->query("SELECT COUNT(*) FROM infants WHERE birth_date BETWEEN DATE_SUB('$current_date', INTERVAL 12 MONTH) AND DATE_SUB('$current_date', INTERVAL 6 MONTH)")->fetchColumn();
    $stats['1_2_years'] = $pdo->query("SELECT COUNT(*) FROM infants WHERE birth_date BETWEEN DATE_SUB('$current_date', INTERVAL 2 YEAR) AND DATE_SUB('$current_date', INTERVAL 1 YEAR)")->fetchColumn();
    
    // الرضع حسب المحافظة
    $stats['by_governorate'] = $pdo->query("SELECT displacement_governorate, COUNT(*) as count FROM infants GROUP BY displacement_governorate")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // الرضع حسب الفرع العائلي
    $stats['by_family_branch'] = $pdo->query("SELECT family_branch, COUNT(*) as count FROM infants GROUP BY family_branch ORDER BY count DESC LIMIT 5")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // الرضع المسجلين هذا الشهر
    $stats['this_month'] = $pdo->query("SELECT COUNT(*) FROM infants WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn();
    
    // الرضع المسجلين هذا الأسبوع
    $stats['this_week'] = $pdo->query("SELECT COUNT(*) FROM infants WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL WEEKDAY(CURRENT_DATE()) DAY)")->fetchColumn();
    
} catch (PDOException $e) {
    $stats = [
        'total' => 0,
        '0_6_months' => 0,
        '6_12_months' => 0,
        '1_2_years' => 0,
        'by_governorate' => [],
        'by_family_branch' => [],
        'this_month' => 0,
        'this_week' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الرضع</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin-sidebar.css" rel="stylesheet">
    <style>
        .table-responsive { border-radius: 10px; overflow: hidden; }
        .badge { font-size: 0.8em; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
    </style>
</head>
<body>
    <?php include '../../includes/admin-sidebar.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h2 class="card-title mb-0">
                            <i class="fas fa-baby me-2"></i>
                            إدارة الرضع
                        </h2>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success" onclick="exportToExcel()">
                                <i class="fas fa-file-excel me-2"></i>
                                تصدير Excel
                            </button>
                            <button class="btn btn-danger" onclick="exportToPDF()">
                                <i class="fas fa-file-pdf me-2"></i>
                                تصدير PDF
                            </button>
                            <button class="btn btn-secondary" onclick="printTable()">
                                <i class="fas fa-print me-2"></i>
                                طباعة
                            </button>
                            <a href="infant-registration.php" class="btn btn-light">
                                <i class="fas fa-plus me-2"></i>
                                إضافة رضيع جديد
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- فلاتر البحث -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-filter me-2"></i>
                                    فلاتر البحث
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">البحث</label>
                                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="الاسم أو رقم الهوية">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">الفرع العائلي</label>
                                        <select class="form-select" name="family_branch">
                                            <option value="">جميع الفروع</option>
                                            <?php foreach ($family_branches as $branch): ?>
                                                <option value="<?php echo htmlspecialchars($branch); ?>" <?php echo $family_branch === $branch ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($branch); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">المحافظة</label>
                                        <select class="form-select" name="governorate">
                                            <option value="">جميع المحافظات</option>
                                            <option value="gaza" <?php echo $governorate === 'gaza' ? 'selected' : ''; ?>>غزة</option>
                                            <option value="khan_younis" <?php echo $governorate === 'khan_younis' ? 'selected' : ''; ?>>خانيونس</option>
                                            <option value="rafah" <?php echo $governorate === 'rafah' ? 'selected' : ''; ?>>رفح</option>
                                            <option value="middle" <?php echo $governorate === 'middle' ? 'selected' : ''; ?>>الوسطى</option>
                                            <option value="north_gaza" <?php echo $governorate === 'north_gaza' ? 'selected' : ''; ?>>شمال غزة</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">الفئة العمرية</label>
                                        <select class="form-select" name="age_filter">
                                            <option value="">جميع الأعمار</option>
                                            <option value="0-6_months" <?php echo $age_filter === '0-6_months' ? 'selected' : ''; ?>>0-6 أشهر</option>
                                            <option value="6-12_months" <?php echo $age_filter === '6-12_months' ? 'selected' : ''; ?>>6-12 شهر</option>
                                            <option value="1-2_years" <?php echo $age_filter === '1-2_years' ? 'selected' : ''; ?>>1-2 سنة</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary me-2">
                                            <i class="fas fa-search me-1"></i>
                                            بحث
                                        </button>
                                        <a href="admin-infants.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i>
                                            مسح
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- الإحصائيات -->
                        <div class="row mb-4">
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-baby fa-2x mb-2"></i>
                                        <h4 class="mb-1"><?php echo $stats['total']; ?></h4>
                                        <p class="mb-0">إجمالي الرضع</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-calendar-week fa-2x mb-2"></i>
                                        <h4 class="mb-1"><?php echo $stats['this_week']; ?></h4>
                                        <p class="mb-0">هذا الأسبوع</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                                        <h4 class="mb-1"><?php echo $stats['this_month']; ?></h4>
                                        <p class="mb-0">هذا الشهر</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-clock fa-2x mb-2"></i>
                                        <h4 class="mb-1"><?php echo $stats['0_6_months']; ?></h4>
                                        <p class="mb-0">0-6 أشهر</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- إحصائيات تفصيلية -->
                        <div class="row mb-4">
                            <div class="col-lg-6 mb-3">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-chart-pie me-2"></i>
                                            التوزيع حسب الفئة العمرية
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="border rounded p-2">
                                                    <h5 class="text-primary mb-1"><?php echo $stats['0_6_months']; ?></h5>
                                                    <small class="text-muted">0-6 أشهر</small>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="border rounded p-2">
                                                    <h5 class="text-info mb-1"><?php echo $stats['6_12_months']; ?></h5>
                                                    <small class="text-muted">6-12 شهر</small>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="border rounded p-2">
                                                    <h5 class="text-success mb-1"><?php echo $stats['1_2_years']; ?></h5>
                                                    <small class="text-muted">1-2 سنة</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            التوزيع حسب المحافظة
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <?php 
                                        $governorate_names = [
                                            'gaza' => 'غزة',
                                            'khan_younis' => 'خانيونس',
                                            'rafah' => 'رفح',
                                            'middle' => 'الوسطى',
                                            'north_gaza' => 'شمال غزة'
                                        ];
                                        ?>
                                        <?php foreach ($stats['by_governorate'] as $gov => $count): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span><?php echo $governorate_names[$gov] ?? $gov; ?></span>
                                            <span class="badge bg-primary"><?php echo $count; ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- أكثر الفروع العائلية -->
                        <?php if (!empty($stats['by_family_branch'])): ?>
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-users me-2"></i>
                                            أكثر الفروع العائلية
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <?php foreach ($stats['by_family_branch'] as $branch => $count): ?>
                                            <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                                                <div class="text-center p-2 border rounded">
                                                    <h6 class="mb-1"><?php echo $count; ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($branch); ?></small>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- عداد النتائج -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                تم العثور على <strong><?php echo count($infants); ?></strong> نتيجة
                            </div>
                            <div>
                                <a href="infant-registration.php" class="btn btn-info btn-sm">
                                    <i class="fas fa-plus me-1"></i>
                                    إضافة رضيع جديد
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="infantsTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                        </th>
                                        <th>#</th>
                                        <th>الاسم الكامل</th>
                                        <th>رقم الهوية</th>
                                        <th>تاريخ الميلاد</th>
                                        <th>العمر</th>
                                        <th>الفرع العائلي</th>
                                        <th>رقم الهاتف</th>
                                        <th>المحافظة</th>
                                        <th>تاريخ التسجيل</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($infants)): ?>
                                        <tr>
                                            <td colspan="11" class="text-center py-4">
                                                <i class="fas fa-baby fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">لا توجد بيانات رضع مسجلة</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($infants as $index => $infant): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="infant-checkbox" value="<?php echo $infant['id']; ?>" onchange="updateSelectAllStatus()">
                                            </td>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($infant['first_name'] ?? 'غير محدد'); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($infant['father_name'] ?? ''); ?> 
                                                    <?php echo htmlspecialchars($infant['grandfather_name'] ?? ''); ?> 
                                                    <?php echo htmlspecialchars($infant['family_name'] ?? ''); ?>
                                                </small>
                                            </td>
                                            <td><?php echo htmlspecialchars($infant['id_number'] ?? 'غير محدد'); ?></td>
                                            <td><?php echo !empty($infant['birth_date']) ? date('Y-m-d', strtotime($infant['birth_date'])) : 'غير محدد'; ?></td>
                                            <td>
                                                <?php 
                                                if (!empty($infant['birth_date'])) {
                                                    try {
                                                        $birth_date = new DateTime($infant['birth_date']);
                                                        $now = new DateTime();
                                                        $age_diff = $now->diff($birth_date);
                                                        
                                                        $years = $age_diff->y;
                                                        $months = $age_diff->m;
                                                        $days = $age_diff->d;
                                                        
                                                        // تحديد لون البادج حسب العمر (للرضع)
                                                        $badge_class = 'bg-success'; // أخضر للرضع
                                                        if ($months >= 12) {
                                                            $badge_class = 'bg-warning'; // أصفر للأطفال الأكبر
                                                        }
                                                        if ($months >= 18) {
                                                            $badge_class = 'bg-danger'; // أحمر للأطفال الأكبر من 18 شهر
                                                        }
                                                        
                                                        // عرض العمر بالتفصيل
                                                        $age_text = '';
                                                        if ($years > 0) {
                                                            $age_text .= $years . ' سنة';
                                                        }
                                                        if ($months > 0) {
                                                            if ($age_text) $age_text .= ' و ';
                                                            $age_text .= $months . ' شهر';
                                                        }
                                                        if ($years == 0 && $months == 0 && $days > 0) {
                                                            $age_text = $days . ' يوم';
                                                        }
                                                        
                                                        if (empty($age_text)) {
                                                            $age_text = 'حديث الولادة';
                                                        }
                                                        
                                                        echo '<span class="badge ' . $badge_class . ' fs-6" title="تاريخ الميلاد: ' . date('Y-m-d', strtotime($infant['birth_date'])) . '">' . $age_text . '</span>';
                                                        
                                                    } catch (Exception $e) {
                                                        echo '<span class="badge bg-danger fs-6">تاريخ غير صحيح</span>';
                                                    }
                                                } else {
                                                    echo '<span class="badge bg-secondary fs-6">غير محدد</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($infant['family_branch'] ?? 'غير محدد'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($infant['primary_phone'] ?? 'غير محدد'); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php 
                                                    $governorate = $infant['displacement_governorate'] ?? '';
                                                    $governorate_names = [
                                                        'gaza' => 'غزة',
                                                        'khan_younis' => 'خانيونس',
                                                        'rafah' => 'رفح',
                                                        'middle' => 'الوسطى',
                                                        'north_gaza' => 'شمال غزة'
                                                    ];
                                                    echo $governorate_names[$governorate] ?? $governorate;
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo !empty($infant['created_at']) ? date('Y-m-d H:i', strtotime($infant['created_at'])) : 'غير محدد'; ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewInfant(<?php echo $infant['id']; ?>)" title="عرض">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteInfant(<?php echo $infant['id']; ?>)" title="حذف">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal لعرض تفاصيل الرضيع -->
    <div class="modal fade" id="viewInfantModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل الرضيع</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="infantDetails">
                    <!-- سيتم تحميل التفاصيل هنا -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal لتأكيد الحذف -->
    <div class="modal fade" id="deleteInfantModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تأكيد الحذف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>هل أنت متأكد من حذف بيانات هذا الرضيع؟</p>
                    <p class="text-danger"><strong>هذا الإجراء لا يمكن التراجع عنه!</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="infant_id" id="deleteInfantId">
                        <button type="submit" name="delete_infant" class="btn btn-danger">حذف</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-sidebar.js"></script>
    <script>
        function viewInfant(id) {
            // يمكن إضافة AJAX لجلب تفاصيل الرضيع
            document.getElementById('infantDetails').innerHTML = '<p>جاري تحميل التفاصيل...</p>';
            new bootstrap.Modal(document.getElementById('viewInfantModal')).show();
        }

        function deleteInfant(id) {
            document.getElementById('deleteInfantId').value = id;
            new bootstrap.Modal(document.getElementById('deleteInfantModal')).show();
        }

        // دالة تحديد/إلغاء تحديد الكل
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.infant-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        // دالة تحديث حالة "تحديد الكل"
        function updateSelectAllStatus() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.infant-checkbox');
            const checkedBoxes = document.querySelectorAll('.infant-checkbox:checked');
            
            if (checkedBoxes.length === 0) {
                selectAll.indeterminate = false;
                selectAll.checked = false;
            } else if (checkedBoxes.length === checkboxes.length) {
                selectAll.indeterminate = false;
                selectAll.checked = true;
            } else {
                selectAll.indeterminate = true;
            }
        }

        // دالة الحصول على العناصر المحددة
        function getSelectedInfants() {
            const checkboxes = document.querySelectorAll('.infant-checkbox:checked');
            return Array.from(checkboxes).map(checkbox => checkbox.value);
        }

        // دالة تصدير إلى Excel
        function exportToExcel() {
            if (confirm('هل تريد تصدير البيانات إلى ملف Excel؟')) {
                const search = document.querySelector('input[name="search"]').value;
                const familyBranch = document.querySelector('select[name="family_branch"]').value;
                const governorate = document.querySelector('select[name="governorate"]').value;
                const ageFilter = document.querySelector('select[name="age_filter"]').value;
                const selectedIds = getSelectedInfants();
                
                let url = 'export_infants_excel.php?';
                const params = new URLSearchParams();
                if (search) params.append('search', search);
                if (familyBranch) params.append('family_branch', familyBranch);
                if (governorate) params.append('governorate', governorate);
                if (ageFilter) params.append('age_filter', ageFilter);
                if (selectedIds.length > 0) params.append('selected_ids', selectedIds.join(','));
                
                window.open(url + params.toString(), '_blank');
            }
        }

        // دالة تصدير إلى PDF
        function exportToPDF() {
            if (confirm('هل تريد تصدير البيانات إلى ملف PDF؟')) {
                const search = document.querySelector('input[name="search"]').value;
                const familyBranch = document.querySelector('select[name="family_branch"]').value;
                const governorate = document.querySelector('select[name="governorate"]').value;
                const ageFilter = document.querySelector('select[name="age_filter"]').value;
                const selectedIds = getSelectedInfants();
                
                let url = 'export_infants_pdf.php?';
                const params = new URLSearchParams();
                if (search) params.append('search', search);
                if (familyBranch) params.append('family_branch', familyBranch);
                if (governorate) params.append('governorate', governorate);
                if (ageFilter) params.append('age_filter', ageFilter);
                if (selectedIds.length > 0) params.append('selected_ids', selectedIds.join(','));
                
                window.open(url + params.toString(), '_blank');
            }
        }

        // دالة الطباعة
        function printTable() {
            const printWindow = window.open('', '_blank');
            const table = document.getElementById('infantsTable');
            const tableHTML = table.outerHTML;
            
            printWindow.document.write(`
                <html dir="rtl">
                <head>
                    <title>تقرير الرضع - الشاعر عائلتي</title>
                    <style>
                        body { font-family: Arial, sans-serif; direction: rtl; }
                        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
                        th { background-color: #f2f2f2; font-weight: bold; }
                        .header { text-align: center; margin-bottom: 20px; }
                        .header h1 { color: #333; }
                        .header p { color: #666; }
                        @media print {
                            body { margin: 0; }
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>الشاعر عائلتي</h1>
                        <p>تقرير بيانات الرضع - ${new Date().toLocaleDateString('ar-SA')}</p>
                    </div>
                    ${tableHTML}
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>
</html>
