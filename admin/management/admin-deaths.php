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
$death_id = $_GET['id'] ?? '';

$success_message = '';
$error_message = '';

if ($_POST) {
    if (isset($_POST['delete_death'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM deaths WHERE id = ?");
            $stmt->execute([$death_id]);
            $success_message = 'تم حذف بيانات المتوفي بنجاح';
        } catch (PDOException $e) {
            $error_message = 'خطأ في حذف بيانات المتوفي: ' . $e->getMessage();
        }
    }
}

// معالجة الفلترة
$search = $_GET['search'] ?? '';
$family_branch = $_GET['family_branch'] ?? '';
$governorate = $_GET['governorate'] ?? '';
$death_reason = $_GET['death_reason'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// بناء استعلام الفلترة
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(first_name LIKE ? OR father_name LIKE ? OR grandfather_name LIKE ? OR family_name LIKE ? OR id_number LIKE ? OR requester_first_name LIKE ? OR requester_father_name LIKE ? OR requester_grandfather_name LIKE ? OR requester_family_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
}

if (!empty($family_branch)) {
    $where_conditions[] = "family_branch = ?";
    $params[] = $family_branch;
}

if (!empty($governorate)) {
    $where_conditions[] = "governorate = ?";
    $params[] = $governorate;
}

if (!empty($death_reason)) {
    $where_conditions[] = "death_reason = ?";
    $params[] = $death_reason;
}

if (!empty($date_from)) {
    $where_conditions[] = "death_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "death_date <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// جلب قائمة الوفيات
try {
    $sql = "SELECT * FROM deaths $where_clause ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $deaths = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // التأكد من وجود البيانات
    if (empty($deaths)) {
        $deaths = [];
    }
} catch (PDOException $e) {
    $error_message = 'خطأ في جلب قائمة الوفيات: ' . $e->getMessage();
    $deaths = [];
}

// جلب قائمة الفروع العائلية للفلتر
$family_branches = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT family_branch FROM deaths ORDER BY family_branch");
    $family_branches = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // تجاهل الخطأ
}

// جلب الإحصائيات
$stats = [];
try {
    // إجمالي الوفيات
    $stats['total'] = $pdo->query("SELECT COUNT(*) FROM deaths")->fetchColumn();
    
    // الوفيات حسب سبب الوفاة
    $stats['by_reason'] = $pdo->query("SELECT death_reason, COUNT(*) as count FROM deaths GROUP BY death_reason")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // الوفيات حسب المحافظة
    $stats['by_governorate'] = $pdo->query("SELECT governorate, COUNT(*) as count FROM deaths GROUP BY governorate")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // الوفيات حسب الفرع العائلي
    $stats['by_family_branch'] = $pdo->query("SELECT family_branch, COUNT(*) as count FROM deaths GROUP BY family_branch ORDER BY count DESC LIMIT 5")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // الوفيات المسجلة هذا الشهر
    $stats['this_month'] = $pdo->query("SELECT COUNT(*) FROM deaths WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn();
    
    // الوفيات المسجلة هذا الأسبوع
    $stats['this_week'] = $pdo->query("SELECT COUNT(*) FROM deaths WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL WEEKDAY(CURRENT_DATE()) DAY)")->fetchColumn();
    
    // الوفيات هذا الشهر حسب السبب
    $stats['this_month_by_reason'] = $pdo->query("SELECT death_reason, COUNT(*) as count FROM deaths WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) GROUP BY death_reason")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // الشهداء
    $stats['martyrs'] = $pdo->query("SELECT COUNT(*) FROM deaths WHERE death_reason = 'martyr'")->fetchColumn();
    
    // الوفيات بسبب القصف
    $stats['bombing'] = $pdo->query("SELECT COUNT(*) FROM deaths WHERE death_reason = 'bombing'")->fetchColumn();
    
} catch (PDOException $e) {
    $stats = [
        'total' => 0,
        'by_reason' => [],
        'by_governorate' => [],
        'by_family_branch' => [],
        'this_month' => 0,
        'this_week' => 0,
        'this_month_by_reason' => [],
        'martyrs' => 0,
        'bombing' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الوفيات</title>
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
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h2 class="card-title mb-0">
                            <i class="fas fa-cross me-2"></i>
                            إدارة الوفيات
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
                            <a href="death-registration.php" class="btn btn-light">
                                <i class="fas fa-plus me-2"></i>
                                إضافة وفاة جديدة
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
                                        <label class="form-label">سبب الوفاة</label>
                                        <select class="form-select" name="death_reason">
                                            <option value="">جميع الأسباب</option>
                                            <option value="martyr" <?php echo $death_reason === 'martyr' ? 'selected' : ''; ?>>شهيد</option>
                                            <option value="bombing" <?php echo $death_reason === 'bombing' ? 'selected' : ''; ?>>قصف</option>
                                            <option value="disease" <?php echo $death_reason === 'disease' ? 'selected' : ''; ?>>مرض</option>
                                            <option value="accident" <?php echo $death_reason === 'accident' ? 'selected' : ''; ?>>حادث</option>
                                            <option value="natural" <?php echo $death_reason === 'natural' ? 'selected' : ''; ?>>وفاة طبيعية</option>
                                            <option value="other" <?php echo $death_reason === 'other' ? 'selected' : ''; ?>>أخرى</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary me-2">
                                            <i class="fas fa-search me-1"></i>
                                            بحث
                                        </button>
                                        <a href="admin-deaths.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i>
                                            مسح
                                        </a>
                                    </div>
                                </form>
                                
                                <!-- فلترة إضافية بالتاريخ -->
                                <div class="row g-3 mt-2">
                                    <div class="col-md-3">
                                        <label class="form-label">من تاريخ الوفاة</label>
                                        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">إلى تاريخ الوفاة</label>
                                        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- الإحصائيات -->
                        <div class="row mb-4">
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="card bg-dark text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-cross fa-2x mb-2"></i>
                                        <h4 class="mb-1"><?php echo $stats['total']; ?></h4>
                                        <p class="mb-0">إجمالي الوفيات</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-flag fa-2x mb-2"></i>
                                        <h4 class="mb-1"><?php echo $stats['martyrs']; ?></h4>
                                        <p class="mb-0">الشهداء</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-bomb fa-2x mb-2"></i>
                                        <h4 class="mb-1"><?php echo $stats['bombing']; ?></h4>
                                        <p class="mb-0">قصف</p>
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
                        </div>

                        <!-- إحصائيات تفصيلية -->
                        <div class="row mb-4">
                            <div class="col-lg-6 mb-3">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-chart-pie me-2"></i>
                                            التوزيع حسب سبب الوفاة
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <?php 
                                        $reason_labels = [
                                            'martyr' => 'شهيد',
                                            'bombing' => 'قصف',
                                            'disease' => 'مرض',
                                            'accident' => 'حادث',
                                            'natural' => 'وفاة طبيعية',
                                            'other' => 'أخرى'
                                        ];
                                        $reason_colors = [
                                            'martyr' => 'danger',
                                            'bombing' => 'warning',
                                            'disease' => 'info',
                                            'accident' => 'secondary',
                                            'natural' => 'success',
                                            'other' => 'dark'
                                        ];
                                        ?>
                                        <?php foreach ($stats['by_reason'] as $reason => $count): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span><?php echo $reason_labels[$reason] ?? $reason; ?></span>
                                            <span class="badge bg-<?php echo $reason_colors[$reason] ?? 'primary'; ?>"><?php echo $count; ?></span>
                                        </div>
                                        <?php endforeach; ?>
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

                        <!-- إحصائيات هذا الشهر -->
                        <?php if (!empty($stats['this_month_by_reason'])): ?>
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-calendar-alt me-2"></i>
                                            وفيات هذا الشهر حسب السبب
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <?php foreach ($stats['this_month_by_reason'] as $reason => $count): ?>
                                            <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                                                <div class="text-center p-2 border rounded">
                                                    <h6 class="mb-1 text-<?php echo $reason_colors[$reason] ?? 'primary'; ?>"><?php echo $count; ?></h6>
                                                    <small class="text-muted"><?php echo $reason_labels[$reason] ?? $reason; ?></small>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

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
                                تم العثور على <strong><?php echo count($deaths); ?></strong> نتيجة
                            </div>
                            <div>
                                <a href="death-registration.php" class="btn btn-dark btn-sm">
                                    <i class="fas fa-plus me-1"></i>
                                    إضافة وفاة جديدة
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="deathsTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="selectAllDeaths" onchange="toggleSelectAllDeaths()">
                                        </th>
                                        <th>#</th>
                                        <th>الاسم الكامل</th>
                                        <th>رقم الهوية</th>
                                        <th>تاريخ الميلاد</th>
                                        <th>العمر عند الوفاة</th>
                                        <th>تاريخ الوفاة</th>
                                        <th>سبب الوفاة</th>
                                        <th>الفرع العائلي</th>
                                        <th>المحافظة</th>
                                        <th>مدخل الطلب</th>
                                        <th>تاريخ التسجيل</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($deaths)): ?>
                                        <tr>
                                            <td colspan="13" class="text-center py-4">
                                                <i class="fas fa-cross fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">لا توجد بيانات وفيات مسجلة</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($deaths as $index => $death): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="death-checkbox" value="<?php echo $death['id']; ?>" onchange="updateSelectAllDeathsStatus()">
                                            </td>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($death['first_name'] ?? 'غير محدد'); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($death['father_name'] ?? ''); ?> 
                                                    <?php echo htmlspecialchars($death['grandfather_name'] ?? ''); ?> 
                                                    <?php echo htmlspecialchars($death['family_name'] ?? ''); ?>
                                                </small>
                                            </td>
                                            <td><?php echo htmlspecialchars($death['id_number'] ?? 'غير محدد'); ?></td>
                                            <td><?php echo !empty($death['birth_date']) ? date('Y-m-d', strtotime($death['birth_date'])) : 'غير محدد'; ?></td>
                                            <td>
                                                <?php 
                                                if (!empty($death['birth_date'])) {
                                                    try {
                                                        $birth_date = new DateTime($death['birth_date']);
                                                        $death_date = !empty($death['death_date']) ? new DateTime($death['death_date']) : new DateTime();
                                                        $age_diff = $death_date->diff($birth_date);
                                                        
                                                        $years = $age_diff->y;
                                                        $months = $age_diff->m;
                                                        $days = $age_diff->d;
                                                        
                                                        // تحديد لون البادج حسب العمر عند الوفاة
                                                        $badge_class = 'bg-info'; // أزرق للعمر العادي
                                                        if ($years < 1) {
                                                            $badge_class = 'bg-danger'; // أحمر للأطفال الرضع
                                                        } elseif ($years < 18) {
                                                            $badge_class = 'bg-warning'; // أصفر للأطفال
                                                        } elseif ($years >= 60) {
                                                            $badge_class = 'bg-secondary'; // رمادي للمسنين
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
                                                        
                                                        echo '<span class="badge ' . $badge_class . ' fs-6" title="تاريخ الميلاد: ' . date('Y-m-d', strtotime($death['birth_date'])) . '">' . $age_text . '</span>';
                                                        
                                                    } catch (Exception $e) {
                                                        echo '<span class="badge bg-danger fs-6">تاريخ غير صحيح</span>';
                                                    }
                                                } else {
                                                    echo '<span class="badge bg-secondary fs-6">غير محدد</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo !empty($death['death_date']) ? date('Y-m-d', strtotime($death['death_date'])) : 'غير محدد'; ?></td>
                                            <td>
                                                <?php 
                                                $death_reason = $death['death_reason'] ?? '';
                                                $reason_labels = [
                                                    'martyr' => 'شهيد',
                                                    'bombing' => 'قصف',
                                                    'disease' => 'مرض',
                                                    'accident' => 'حادث',
                                                    'natural' => 'وفاة طبيعية',
                                                    'other' => 'أخرى'
                                                ];
                                                $reason_label = $reason_labels[$death_reason] ?? $death_reason;
                                                ?>
                                                <span class="badge bg-<?php echo $death_reason === 'martyr' ? 'danger' : ($death_reason === 'bombing' ? 'warning' : 'secondary'); ?>">
                                                    <?php echo $reason_label; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($death['family_branch'] ?? 'غير محدد'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php 
                                                    $governorate = $death['governorate'] ?? '';
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
                                            <td>
                                                <small>
                                                    <?php echo htmlspecialchars($death['requester_first_name'] ?? 'غير محدد'); ?>
                                                    <br>
                                                    <span class="text-muted">
                                                        <?php 
                                                        $relationship = $death['requester_relationship'] ?? '';
                                                        $relationship_labels = [
                                                            'son' => 'ابن',
                                                            'daughter' => 'ابنة',
                                                            'brother' => 'أخ',
                                                            'sister' => 'أخت',
                                                            'father' => 'أب',
                                                            'mother' => 'أم',
                                                            'grandfather' => 'جد',
                                                            'grandmother' => 'جدة',
                                                            'uncle' => 'عم',
                                                            'aunt' => 'عمة',
                                                            'cousin' => 'ابن عم/خال',
                                                            'other' => 'أخرى'
                                                        ];
                                                        echo $relationship_labels[$relationship] ?? $relationship;
                                                        ?>
                                                    </span>
                                                </small>
                                            </td>
                                            <td><?php echo !empty($death['created_at']) ? date('Y-m-d H:i', strtotime($death['created_at'])) : 'غير محدد'; ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewDeath(<?php echo $death['id']; ?>)" title="عرض">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteDeath(<?php echo $death['id']; ?>)" title="حذف">
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

    <!-- Modal لعرض تفاصيل المتوفي -->
    <div class="modal fade" id="viewDeathModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل المتوفي</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="deathDetails">
                    <!-- سيتم تحميل التفاصيل هنا -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal لتأكيد الحذف -->
    <div class="modal fade" id="deleteDeathModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تأكيد الحذف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>هل أنت متأكد من حذف بيانات هذا المتوفي؟</p>
                    <p class="text-danger"><strong>هذا الإجراء لا يمكن التراجع عنه!</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="death_id" id="deleteDeathId">
                        <button type="submit" name="delete_death" class="btn btn-danger">حذف</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-sidebar.js"></script>
    <script>
        function viewDeath(id) {
            // يمكن إضافة AJAX لجلب تفاصيل المتوفي
            document.getElementById('deathDetails').innerHTML = '<p>جاري تحميل التفاصيل...</p>';
            new bootstrap.Modal(document.getElementById('viewDeathModal')).show();
        }

        function deleteDeath(id) {
            document.getElementById('deleteDeathId').value = id;
            new bootstrap.Modal(document.getElementById('deleteDeathModal')).show();
        }

        // دالة تحديد/إلغاء تحديد الكل للوفيات
        function toggleSelectAllDeaths() {
            const selectAll = document.getElementById('selectAllDeaths');
            const checkboxes = document.querySelectorAll('.death-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        // دالة تحديث حالة "تحديد الكل" للوفيات
        function updateSelectAllDeathsStatus() {
            const selectAll = document.getElementById('selectAllDeaths');
            const checkboxes = document.querySelectorAll('.death-checkbox');
            const checkedBoxes = document.querySelectorAll('.death-checkbox:checked');
            
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

        // دالة الحصول على العناصر المحددة للوفيات
        function getSelectedDeaths() {
            const checkboxes = document.querySelectorAll('.death-checkbox:checked');
            return Array.from(checkboxes).map(checkbox => checkbox.value);
        }

        // دالة تصدير إلى Excel
        function exportToExcel() {
            if (confirm('هل تريد تصدير البيانات إلى ملف Excel؟')) {
                const search = document.querySelector('input[name="search"]').value;
                const familyBranch = document.querySelector('select[name="family_branch"]').value;
                const governorate = document.querySelector('select[name="governorate"]').value;
                const deathReason = document.querySelector('select[name="death_reason"]').value;
                const dateFrom = document.querySelector('input[name="date_from"]').value;
                const dateTo = document.querySelector('input[name="date_to"]').value;
                const selectedIds = getSelectedDeaths();
                
                let url = 'export_deaths_excel.php?';
                const params = new URLSearchParams();
                if (search) params.append('search', search);
                if (familyBranch) params.append('family_branch', familyBranch);
                if (governorate) params.append('governorate', governorate);
                if (deathReason) params.append('death_reason', deathReason);
                if (dateFrom) params.append('date_from', dateFrom);
                if (dateTo) params.append('date_to', dateTo);
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
                const deathReason = document.querySelector('select[name="death_reason"]').value;
                const dateFrom = document.querySelector('input[name="date_from"]').value;
                const dateTo = document.querySelector('input[name="date_to"]').value;
                const selectedIds = getSelectedDeaths();
                
                let url = 'export_deaths_pdf.php?';
                const params = new URLSearchParams();
                if (search) params.append('search', search);
                if (familyBranch) params.append('family_branch', familyBranch);
                if (governorate) params.append('governorate', governorate);
                if (deathReason) params.append('death_reason', deathReason);
                if (dateFrom) params.append('date_from', dateFrom);
                if (dateTo) params.append('date_to', dateTo);
                if (selectedIds.length > 0) params.append('selected_ids', selectedIds.join(','));
                
                window.open(url + params.toString(), '_blank');
            }
        }

        // دالة الطباعة
        function printTable() {
            const printWindow = window.open('', '_blank');
            const table = document.getElementById('deathsTable');
            const tableHTML = table.outerHTML;
            
            printWindow.document.write(`
                <html dir="rtl">
                <head>
                    <title>تقرير الوفيات - الشاعر عائلتي</title>
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
                        <p>تقرير بيانات الوفيات - ${new Date().toLocaleDateString('ar-SA')}</p>
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
