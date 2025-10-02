<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../auth/admin-login.php');
    exit;
}

// تضمين ملف التحقق من الصلاحيات
require_once '../../includes/check-permissions.php';

// الاتصال بقاعدة البيانات
try {
    $pdo = new PDO('mysql:host=localhost;dbname=family_orphans_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage());
}

// معالجة العمليات
$action = $_GET['action'] ?? '';
$family_id = $_GET['id'] ?? '';

if ($action === 'delete' && $family_id) {
    try {
            $stmt = $pdo->prepare("DELETE FROM families WHERE id = ?");
            $stmt->execute([$family_id]);
        $_SESSION['success_message'] = 'تم حذف العائلة بنجاح';
        header('Location: admin-families.php');
        exit;
        } catch (PDOException $e) {
        $_SESSION['error_message'] = 'خطأ في حذف العائلة: ' . $e->getMessage();
    }
}

// معاملات البحث والفلترة
$search = $_GET['search'] ?? '';
$governorate = $_GET['governorate'] ?? '';
$gender = $_GET['gender'] ?? '';
$health_status = $_GET['health_status'] ?? '';
$marital_status = $_GET['marital_status'] ?? '';
$family_branch = $_GET['family_branch'] ?? '';
$displacement_area = $_GET['displacement_area'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// بناء استعلام البحث
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(CONCAT(first_name, ' ', father_name, ' ', grandfather_name, ' ', family_name) LIKE ? OR id_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($governorate)) {
    $where_conditions[] = "original_governorate = ?";
    $params[] = $governorate;
}

if (!empty($health_status)) {
    $where_conditions[] = "health_status = ?";
    $params[] = $health_status;
}

if (!empty($gender)) {
    $where_conditions[] = "gender = ?";
    $params[] = $gender;
}

if (!empty($marital_status)) {
    $where_conditions[] = "marital_status = ?";
    $params[] = $marital_status;
}

if (!empty($family_branch)) {
    $where_conditions[] = "family_branch = ?";
    $params[] = $family_branch;
}

if (!empty($displacement_area)) {
    $where_conditions[] = "displacement_area = ?";
    $params[] = $displacement_area;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// تطبيق فلترة الفرع العائلي بناءً على صلاحيات المشرف
$admin_id = $_SESSION['admin_id'] ?? 0;
$where_clause = addFamilyBranchFilter($pdo, $admin_id, 'families', 'family_branch', $where_clause);

// جلب العائلات
$sql = "SELECT * FROM families $where_clause ORDER BY id DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$families = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب العدد الإجمالي للصفحات
$count_sql = "SELECT COUNT(*) FROM families $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_families = $count_stmt->fetchColumn();
$total_pages = ceil($total_families / $per_page);

// جلب المحافظات للفلتر
$governorates = $pdo->query("SELECT DISTINCT original_governorate FROM families ORDER BY original_governorate")->fetchAll(PDO::FETCH_COLUMN);

// جلب الفروع العائلية للفلتر
$family_branches = $pdo->query("SELECT DISTINCT family_branch FROM families WHERE family_branch IS NOT NULL AND family_branch != '' ORDER BY family_branch")->fetchAll(PDO::FETCH_COLUMN);

// جلب المناطق للفلتر
$displacement_areas = $pdo->query("SELECT DISTINCT displacement_area FROM families WHERE displacement_area IS NOT NULL AND displacement_area != '' ORDER BY displacement_area")->fetchAll(PDO::FETCH_COLUMN);

// جميع المحافظات المتاحة
$all_governorates = [
    'gaza' => 'غزة',
    'khan_younis' => 'خان يونس', 
    'rafah' => 'رفح',
    'middle' => 'الوسطى',
    'north_gaza' => 'شمال غزة'
];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأسر - نظام العائلات والأيتام</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="assets/css/admin-sidebar.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }
        .table th {
            background-color: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #495057;
            padding: 1rem 0.75rem;
        }
        .table td {
            border: none;
            padding: 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f4;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
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
            background: rgba(255, 255, 255, 0.3);
        }
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 5px;
        }
        .badge-relationship {
            font-size: 0.7rem;
            padding: 0.3rem 0.6rem;
        }
        .pagination {
            justify-content: center;
        }
        .page-link {
            border: none;
            color: #667eea;
            padding: 0.5rem 0.75rem;
        }
        .page-link:hover {
            background-color: #f8f9fa;
            color: #495057;
        }
        .page-item.active .page-link {
            background-color: #667eea;
            border-color: #667eea;
        }
        .btn-view {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }
        .btn-view:hover {
            background-color: #138496;
            border-color: #117a8b;
            color: white;
        }
        .btn-edit {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }
        .btn-edit:hover {
            background-color: #e0a800;
            border-color: #d39e00;
            color: #212529;
        }
        .btn-delete {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background-color: #c82333;
            border-color: #bd2130;
            color: white;
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
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }
        .pagination {
            justify-content: center;
        }
        .page-link {
            border-radius: 10px;
            margin: 0 2px;
            border: none;
            color: #667eea;
        }
        .page-item.active .page-link {
            background-color: #667eea;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <?php include '../../includes/admin-sidebar.php'; ?>

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">إدارة الأسر</h1>
            <div>
                <a href="family-registration.php" class="btn btn-primary me-2">
                    <i class="fas fa-plus me-1"></i>
                    إضافة عائلة جديدة
                </a>
                <button class="btn btn-success" onclick="exportData('excel')">
                    <i class="fas fa-file-excel me-1"></i>
                    تصدير Excel
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($total_families); ?></div>
                    <div class="text-muted">إجمالي العائلات</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($families); ?></div>
                    <div class="text-muted">العائلات المعروضة</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($governorates); ?></div>
                    <div class="text-muted">المحافظات</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $total_pages; ?></div>
                    <div class="text-muted">عدد الصفحات</div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="filter-card">
            <h5 class="mb-3">فلترة البيانات</h5>
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">البحث</label>
                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="اسم العائلة أو رقم الهوية">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">المحافظة</label>
                        <select class="form-select" name="governorate">
                            <option value="">جميع المحافظات</option>
                            <?php foreach ($all_governorates as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo $governorate === $key ? 'selected' : ''; ?>>
                                    <?php echo $value; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الجنس</label>
                        <select class="form-select" name="gender">
                            <option value="">جميع الأجناس</option>
                            <option value="male" <?php echo $gender === 'male' ? 'selected' : ''; ?>>ذكر</option>
                            <option value="female" <?php echo $gender === 'female' ? 'selected' : ''; ?>>أنثى</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الحالة الصحية</label>
                        <select class="form-select" name="health_status">
                            <option value="">جميع الحالات</option>
                            <option value="healthy" <?php echo $health_status === 'healthy' ? 'selected' : ''; ?>>سليم</option>
                            <option value="hypertension" <?php echo $health_status === 'hypertension' ? 'selected' : ''; ?>>ضغط</option>
                            <option value="diabetes" <?php echo $health_status === 'diabetes' ? 'selected' : ''; ?>>سكري</option>
                            <option value="other" <?php echo $health_status === 'other' ? 'selected' : ''; ?>>أخرى</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الحالة الاجتماعية</label>
                        <select class="form-select" name="marital_status">
                            <option value="">جميع الحالات</option>
                            <option value="married" <?php echo $marital_status === 'married' ? 'selected' : ''; ?>>متزوج</option>
                            <option value="divorced" <?php echo $marital_status === 'divorced' ? 'selected' : ''; ?>>مطلق</option>
                            <option value="widowed" <?php echo $marital_status === 'widowed' ? 'selected' : ''; ?>>أرمل</option>
                            <option value="elderly" <?php echo $marital_status === 'elderly' ? 'selected' : ''; ?>>مسن</option>
                            <option value="provider" <?php echo $marital_status === 'provider' ? 'selected' : ''; ?>>معيل</option>
                            <option value="special_needs" <?php echo $marital_status === 'special_needs' ? 'selected' : ''; ?>>احتياجات خاصة</option>
                        </select>
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
                        <label class="form-label">المنطقة</label>
                        <select class="form-select" name="displacement_area">
                            <option value="">جميع المناطق</option>
                            <?php foreach ($displacement_areas as $area): ?>
                                <option value="<?php echo htmlspecialchars($area); ?>" <?php echo $displacement_area === $area ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($area); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>
                            بحث
                        </button>
                            <a href="admin-families.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>
                                مسح
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Bulk Actions -->
        <div class="card mb-3" id="bulkActions" style="display: none;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">
                        <span id="selectedCount">0</span> عنصر محدد
                    </span>
                    <div class="btn-group">
                        <button class="btn btn-outline-warning btn-sm" onclick="bulkEdit()">
                            <i class="fas fa-edit me-1"></i>
                            تعديل جماعي
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="bulkDelete()">
                            <i class="fas fa-trash me-1"></i>
                            حذف جماعي
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="bulkExport()">
                            <i class="fas fa-file-excel me-1"></i>
                            تصدير محدد
                        </button>
                    </div>
                </div>
            </div>
        </div>
                </div>
            </form>
            </div>

        <!-- Families Table -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">قائمة العائلات</h5>
                <div>
                    <button class="btn btn-sm btn-outline-primary" onclick="printTable()">
                        <i class="fas fa-print me-1"></i>
                        طباعة
                    </button>
                </div>
            </div>
            
            <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
                <table class="table table-hover table-sm" id="familiesTable" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th>#</th>
                            <th>اسم رب الأسرة</th>
                            <th>رقم الهوية</th>
                            <th>الجنس</th>
                            <th>المحافظة</th>
                            <th>العمر</th>
                            <th>الحالة الاجتماعية</th>
                            <th>عدد الأفراد</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                            <?php foreach ($families as $index => $family): ?>
                                <tr>
                                    <td>
                                <input type="checkbox" class="family-checkbox" value="<?php echo $family['id']; ?>" onchange="updateSelection()">
                                    </td>
                                    <td><?php echo $offset + $index + 1; ?></td>
                                    <td>
                                <?php echo htmlspecialchars($family['first_name'] . ' ' . $family['father_name'] . ' ' . $family['grandfather_name'] . ' ' . $family['family_name']); ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary fs-6">
                                    <?php echo htmlspecialchars($family['id_number']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $family['gender'] === 'male' ? 'primary' : 'info'; ?>">
                                    <?php echo $family['gender'] === 'male' ? 'ذكر' : 'أنثى'; ?>
                                </span>
                                    </td>
                                    <td>
                                <span class="badge bg-info">
                                    <?php echo $all_governorates[$family['original_governorate']] ?? ucfirst(str_replace('_', ' ', $family['original_governorate'])); ?>
                                        </span>
                                    </td>
                            <td>
                                <?php
                                // حساب العمر من تاريخ الميلاد
                                $birth_date = $family['birth_date'];
                                if ($birth_date) {
                                    $birth = new DateTime($birth_date);
                                    $today = new DateTime();
                                    $age = $today->diff($birth)->y;
                                    echo '<span class="badge bg-warning fs-6">' . $age . ' سنة</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">غير محدد</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                $marital_labels = [
                                    'married' => 'متزوج',
                                    'divorced' => 'مطلق',
                                    'widowed' => 'أرمل',
                                    'elderly' => 'مسن',
                                    'provider' => 'معيل',
                                    'special_needs' => 'احتياجات خاصة'
                                ];
                                $marital_colors = [
                                    'married' => 'success',
                                    'divorced' => 'warning',
                                    'widowed' => 'info',
                                    'elderly' => 'primary',
                                    'provider' => 'secondary',
                                    'special_needs' => 'danger'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $marital_colors[$family['marital_status']] ?? 'secondary'; ?> fs-6">
                                    <?php echo $marital_labels[$family['marital_status']] ?? $family['marital_status']; ?>
                                </span>
                                    </td>
                                    <td>
                                <span class="badge bg-success fs-6">
                                    <?php echo $family['family_members_count']; ?>
                                        </span>
                                    </td>
                            <td>
                                <button class="btn btn-sm btn-view btn-action" onclick="viewFamily(<?php echo $family['id']; ?>)">
                                    <i class="fas fa-eye me-1"></i>
                                    عرض
                                        </button>
                                <a href="family-registration.php?edit=<?php echo $family['id']; ?>" class="btn btn-sm btn-edit btn-action">
                                    <i class="fas fa-edit me-1"></i>
                                    تعديل
                                </a>
                                <button class="btn btn-sm btn-delete btn-action" onclick="deleteFamily(<?php echo $family['id']; ?>)">
                                    <i class="fas fa-trash me-1"></i>
                                    حذف
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">السابق</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">التالي</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Family Details Modal -->
    <div class="modal fade" id="familyModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h4 class="modal-title">
                        <i class="fas fa-users me-2"></i>
                        تفاصيل العائلة
                    </h4>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="familyDetails" style="max-height: 70vh; overflow-y: auto;">
                    <!-- Family details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        إغلاق
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/admin-sidebar.js"></script>
    <script>

        function viewFamily(familyId) {
            // Load family details via AJAX
            fetch(`get-family-details.php?id=${familyId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('familyDetails').innerHTML = data;
                    new bootstrap.Modal(document.getElementById('familyModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('خطأ في تحميل تفاصيل العائلة');
                });
        }

        function deleteFamily(familyId) {
            if (confirm('هل أنت متأكد من حذف هذه العائلة؟')) {
                window.location.href = `admin-families.php?action=delete&id=${familyId}`;
            }
        }

        function exportData(format) {
            const params = new URLSearchParams(window.location.search);
            params.set('export', format);
            window.open(`export-families.php?${params.toString()}`, '_blank');
        }

        function printTable() {
            window.print();
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.family-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateSelection();
        }

        function updateSelection() {
            const checkboxes = document.querySelectorAll('.family-checkbox');
            const selectedCount = document.querySelectorAll('.family-checkbox:checked').length;
            const bulkActions = document.getElementById('bulkActions');
            const selectedCountSpan = document.getElementById('selectedCount');
            
            selectedCountSpan.textContent = selectedCount;
            
            if (selectedCount > 0) {
                bulkActions.style.display = 'block';
            } else {
                bulkActions.style.display = 'none';
            }
            
            // Update select all checkbox
            const selectAll = document.getElementById('selectAll');
            if (selectedCount === 0) {
                selectAll.indeterminate = false;
                selectAll.checked = false;
            } else if (selectedCount === checkboxes.length) {
                selectAll.indeterminate = false;
                selectAll.checked = true;
            } else {
                selectAll.indeterminate = true;
            }
        }

        function bulkEdit() {
            const selectedIds = Array.from(document.querySelectorAll('.family-checkbox:checked')).map(cb => cb.value);
            if (selectedIds.length === 0) {
                alert('يرجى تحديد عائلات للتعديل');
                return;
            }
            
            // Redirect to edit page with selected IDs
            window.location.href = `bulk-edit-families.php?ids=${selectedIds.join(',')}`;
        }

        function bulkDelete() {
            const selectedIds = Array.from(document.querySelectorAll('.family-checkbox:checked')).map(cb => cb.value);
            if (selectedIds.length === 0) {
                alert('يرجى تحديد عائلات للحذف');
                return;
            }
            
            if (confirm(`هل أنت متأكد من حذف ${selectedIds.length} عائلة؟`)) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'bulk-delete-families.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'family_ids';
                input.value = selectedIds.join(',');
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function bulkExport() {
            const selectedIds = Array.from(document.querySelectorAll('.family-checkbox:checked')).map(cb => cb.value);
            if (selectedIds.length === 0) {
                alert('يرجى تحديد عائلات للتصدير');
                return;
            }
            
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'excel');
            params.set('ids', selectedIds.join(','));
            window.open(`export-families.php?${params.toString()}`, '_blank');
        }

        // Initialize DataTable
        $(document).ready(function() {
            $('#familiesTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json"
                },
                "pageLength": 20,
                "order": [[8, "desc"]],
                "columnDefs": [
                    { "orderable": false, "targets": [0, 9] }
                ]
            });
        });
    </script>
</body>
</html>
