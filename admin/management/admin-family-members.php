<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../auth/admin-login.php');
    exit;
}

// تضمين ملف التحقق من الصلاحيات
require_once 'includes/check-permissions.php';

// الاتصال بقاعدة البيانات
try {
    $pdo = new PDO('mysql:host=localhost;dbname=family_orphans_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage());
}

// معالجة العمليات
$action = $_GET['action'] ?? '';
$member_id = $_GET['id'] ?? '';

if ($action === 'delete' && $member_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM family_members WHERE id = ?");
        $stmt->execute([$member_id]);
        $_SESSION['success_message'] = 'تم حذف العضو بنجاح';
        header('Location: admin-family-members.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'خطأ في حذف العضو: ' . $e->getMessage();
    }
}

// معاملات البحث والفلترة
$search = $_GET['search'] ?? '';
$governorate = $_GET['governorate'] ?? '';
$gender = $_GET['gender'] ?? '';
$health_status = $_GET['health_status'] ?? '';
$relationship = $_GET['relationship'] ?? '';
$family_branch = $_GET['family_branch'] ?? '';
$displacement_area = $_GET['displacement_area'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// بناء استعلام البحث
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(fm.full_name LIKE ? OR fm.id_number LIKE ? OR f.first_name LIKE ? OR f.family_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($governorate)) {
    $where_conditions[] = "f.original_governorate = ?";
    $params[] = $governorate;
}

if (!empty($gender)) {
    $where_conditions[] = "fm.gender = ?";
    $params[] = $gender;
}

if (!empty($health_status)) {
    $where_conditions[] = "fm.health_status = ?";
    $params[] = $health_status;
}

if (!empty($relationship)) {
    $where_conditions[] = "fm.relationship = ?";
    $params[] = $relationship;
}

if (!empty($family_branch)) {
    $where_conditions[] = "f.family_branch = ?";
    $params[] = $family_branch;
}

if (!empty($displacement_area)) {
    $where_conditions[] = "f.displacement_area = ?";
    $params[] = $displacement_area;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// تطبيق فلترة الفرع العائلي بناءً على صلاحيات المشرف
$admin_id = $_SESSION['admin_id'] ?? 0;
$where_clause = addFamilyBranchFilter($pdo, $admin_id, 'families', 'family_branch', $where_clause);

// جلب أعضاء العائلات مع JOIN مع جدول العائلات
$sql = "SELECT fm.*, f.first_name as family_first_name, f.family_name, f.original_governorate, 
        f.displacement_area, f.family_branch 
        FROM family_members fm 
        LEFT JOIN families f ON fm.family_id = f.id 
        $where_clause ORDER BY fm.id DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب العدد الإجمالي للصفحات
$count_sql = "SELECT COUNT(*) FROM family_members fm 
    LEFT JOIN families f ON fm.family_id = f.id 
    $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_members = $count_stmt->fetchColumn();
$total_pages = ceil($total_members / $per_page);

// جلب المحافظات للفلتر
$governorates = $pdo->query("SELECT DISTINCT f.original_governorate FROM families f 
    INNER JOIN family_members fm ON f.id = fm.family_id 
    ORDER BY f.original_governorate")->fetchAll(PDO::FETCH_COLUMN);

// جلب الفروع العائلية للفلتر
$family_branches = $pdo->query("SELECT DISTINCT f.family_branch FROM families f 
    INNER JOIN family_members fm ON f.id = fm.family_id 
    WHERE f.family_branch IS NOT NULL AND f.family_branch != '' 
    ORDER BY f.family_branch")->fetchAll(PDO::FETCH_COLUMN);

// جلب المناطق للفلتر
$displacement_areas = $pdo->query("SELECT DISTINCT f.displacement_area FROM families f 
    INNER JOIN family_members fm ON f.id = fm.family_id 
    WHERE f.displacement_area IS NOT NULL AND f.displacement_area != '' 
    ORDER BY f.displacement_area")->fetchAll(PDO::FETCH_COLUMN);

// جميع المحافظات المتاحة
$all_governorates = [
    'gaza' => 'غزة',
    'khan_younis' => 'خان يونس', 
    'rafah' => 'رفح',
    'middle' => 'الوسطى',
    'north_gaza' => 'شمال غزة'
];

// إحصائيات سريعة
$stats = [
    'total' => $total_members,
    'males' => $pdo->query("SELECT COUNT(*) FROM family_members WHERE gender = 'male'")->fetchColumn(),
    'females' => $pdo->query("SELECT COUNT(*) FROM family_members WHERE gender = 'female'")->fetchColumn(),
    'healthy' => $pdo->query("SELECT COUNT(*) FROM family_members WHERE health_status = 'healthy'")->fetchColumn()
];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأبناء - نظام العائلات والأيتام</title>
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
    </style>
</head>
<body>
    <?php include '../../includes/admin-sidebar.php'; ?>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">إدارة الأبناء</h1>
        <div>
            <button class="btn btn-outline-primary me-2" onclick="printTable()">
                <i class="fas fa-print me-1"></i>
                طباعة
            </button>
            <button class="btn btn-outline-success" onclick="exportToExcel()">
                <i class="fas fa-file-excel me-1"></i>
                تصدير Excel
            </button>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-number"><?php echo number_format($stats['total']); ?></div>
                <div class="text-muted">إجمالي الأبناء</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-number"><?php echo number_format($stats['males']); ?></div>
                <div class="text-muted">ذكور</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-number"><?php echo number_format($stats['females']); ?></div>
                <div class="text-muted">إناث</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-number"><?php echo number_format($stats['healthy']); ?></div>
                <div class="text-muted">أصحاء</div>
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
                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="اسم العضو أو رقم الهوية">
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
                    <label class="form-label">العلاقة</label>
                    <select class="form-select" name="relationship">
                        <option value="">جميع العلاقات</option>
                        <option value="son" <?php echo $relationship === 'son' ? 'selected' : ''; ?>>ابن</option>
                        <option value="daughter" <?php echo $relationship === 'daughter' ? 'selected' : ''; ?>>ابنة</option>
                        <option value="father" <?php echo $relationship === 'father' ? 'selected' : ''; ?>>أب</option>
                        <option value="mother" <?php echo $relationship === 'mother' ? 'selected' : ''; ?>>أم</option>
                        <option value="brother" <?php echo $relationship === 'brother' ? 'selected' : ''; ?>>أخ</option>
                        <option value="sister" <?php echo $relationship === 'sister' ? 'selected' : ''; ?>>أخت</option>
                        <option value="grandfather" <?php echo $relationship === 'grandfather' ? 'selected' : ''; ?>>جد</option>
                        <option value="grandmother" <?php echo $relationship === 'grandmother' ? 'selected' : ''; ?>>جدة</option>
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
                        <a href="admin-family-members.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>
                            مسح
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Members Table -->
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">قائمة الأبناء</h5>
            <div>
                <button class="btn btn-sm btn-outline-primary" onclick="printTable()">
                    <i class="fas fa-print me-1"></i>
                    طباعة
                </button>
            </div>
        </div>
        
        <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
            <table class="table table-hover table-sm" id="membersTable" style="font-size: 12px;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم الكامل</th>
                        <th>رقم الهوية</th>
                        <th>الجنس</th>
                        <th>تاريخ الميلاد</th>
                        <th>العلاقة</th>
                        <th>الحالة الصحية</th>
                        <th>اسم العائلة</th>
                        <th>المحافظة</th>
                        <th>المنطقة</th>
                        <th>الفرع العائلي</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">
                                <i class="fas fa-users fa-3x mb-3 d-block"></i>
                                لا توجد بيانات للعرض
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($members as $index => $member): ?>
                            <tr>
                                <td><?php echo $offset + $index + 1; ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($member['full_name']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($member['id_number']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $member['gender'] === 'male' ? 'primary' : 'success'; ?>">
                                        <?php echo $member['gender'] === 'male' ? 'ذكر' : 'أنثى'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($member['birth_date'])); ?></td>
                                <td>
                                    <span class="badge badge-relationship bg-info">
                                        <?php
                                        $relationships = [
                                            'son' => 'ابن',
                                            'daughter' => 'ابنة',
                                            'father' => 'أب',
                                            'mother' => 'أم',
                                            'brother' => 'أخ',
                                            'sister' => 'أخت',
                                            'grandfather' => 'جد',
                                            'grandmother' => 'جدة'
                                        ];
                                        echo $relationships[$member['relationship']] ?? $member['relationship'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $member['health_status'] === 'healthy' ? 'success' : 
                                            ($member['health_status'] === 'hypertension' ? 'warning' : 
                                            ($member['health_status'] === 'diabetes' ? 'danger' : 'secondary')); 
                                    ?>">
                                        <?php
                                        $health_statuses = [
                                            'healthy' => 'سليم',
                                            'hypertension' => 'ضغط',
                                            'diabetes' => 'سكري',
                                            'other' => 'أخرى'
                                        ];
                                        echo $health_statuses[$member['health_status']] ?? $member['health_status'];
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($member['family_first_name'] . ' ' . $member['family_name']); ?></td>
                                <td><?php echo $all_governorates[$member['original_governorate']] ?? $member['original_governorate']; ?></td>
                                <td><?php echo htmlspecialchars($member['displacement_area']); ?></td>
                                <td><?php echo htmlspecialchars($member['family_branch']); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-info btn-action" onclick="viewMember(<?php echo $member['id']; ?>)" title="عرض">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning btn-action" onclick="editMember(<?php echo $member['id']; ?>)" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteMember(<?php echo $member['id']; ?>)" title="حذف">
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

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-3">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // عرض تفاصيل العضو
        function viewMember(memberId) {
            // يمكن إضافة modal لعرض التفاصيل
            alert('عرض تفاصيل العضو رقم: ' + memberId);
        }

        // تعديل العضو
        function editMember(memberId) {
            // يمكن إضافة modal للتعديل
            alert('تعديل العضو رقم: ' + memberId);
        }

        // حذف العضو
        function deleteMember(memberId) {
            if (confirm('هل أنت متأكد من حذف هذا العضو؟ هذا الإجراء لا يمكن التراجع عنه!')) {
                window.location.href = `admin-family-members.php?action=delete&id=${memberId}`;
            }
        }

        // طباعة الجدول
        function printTable() {
            window.print();
        }

        // تصدير إلى Excel
        function exportToExcel() {
            // يمكن إضافة وظيفة التصدير
            alert('سيتم إضافة وظيفة التصدير قريباً');
        }

        // تهيئة DataTable
        $(document).ready(function() {
            $('#membersTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json"
                },
                "pageLength": 25,
                "order": [[0, "desc"]],
                "columnDefs": [
                    { "orderable": false, "targets": 11 }
                ]
            });
        });
    </script>
    <script src="assets/js/admin-sidebar.js"></script>
</body>
</html>
