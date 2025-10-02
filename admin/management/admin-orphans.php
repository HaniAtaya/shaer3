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
$orphan_id = $_GET['id'] ?? '';

if ($action === 'delete' && $orphan_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM orphans WHERE id = ?");
        $stmt->execute([$orphan_id]);
        $_SESSION['success_message'] = 'تم حذف اليتيم بنجاح';
        header('Location: admin-orphans.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'خطأ في حذف اليتيم: ' . $e->getMessage();
    }
}

// معاملات البحث والفلترة
$search = $_GET['search'] ?? '';
$governorate = $_GET['governorate'] ?? '';
$gender = $_GET['gender'] ?? '';
$is_war_martyr = $_GET['is_war_martyr'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// بناء استعلام البحث
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(orphan_full_name LIKE ? OR orphan_id_number LIKE ? OR guardian_full_name LIKE ? OR deceased_father_name LIKE ? OR guardian_id_number LIKE ? OR deceased_father_id_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($governorate)) {
    $where_conditions[] = "displacement_governorate = ?";
    $params[] = $governorate;
}

if (!empty($gender)) {
    $where_conditions[] = "orphan_gender = ?";
    $params[] = $gender;
}

if ($is_war_martyr !== '') {
    $where_conditions[] = "is_war_martyr = ?";
    $params[] = $is_war_martyr;
}

// ملاحظة: لا يمكن فلترة الأيتام حسب الفرع العائلي لأن جدول الأيتام لا يحتوي على family_id
// if (!empty($family_branch)) {
//     $where_conditions[] = "f.family_branch = ?";
//     $params[] = $family_branch;
// }

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// ملاحظة: لا يمكن تطبيق فلترة الفرع العائلي على الأيتام لأن جدول الأيتام منفصل عن جدول العائلات
// $admin_id = $_SESSION['admin_id'] ?? 0;
// $where_clause = addFamilyBranchFilter($pdo, $admin_id, 'families', 'family_branch', $where_clause);

// جلب الأيتام
$sql = "SELECT * FROM orphans $where_clause ORDER BY id DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orphans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب العدد الإجمالي للصفحات
$count_sql = "SELECT COUNT(*) FROM orphans $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_orphans = $count_stmt->fetchColumn();
$total_pages = ceil($total_orphans / $per_page);

// جلب المحافظات للفلتر
$governorates = $pdo->query("SELECT DISTINCT displacement_governorate FROM orphans ORDER BY displacement_governorate")->fetchAll(PDO::FETCH_COLUMN);


// إحصائيات سريعة
$stats = [
    'total' => $total_orphans,
    'war_martyrs' => $pdo->query("SELECT COUNT(*) FROM orphans WHERE is_war_martyr = 1")->fetchColumn(),
    'males' => $pdo->query("SELECT COUNT(*) FROM orphans WHERE orphan_gender = 'male'")->fetchColumn(),
    'females' => $pdo->query("SELECT COUNT(*) FROM orphans WHERE orphan_gender = 'female'")->fetchColumn()
];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأيتام - نظام العائلات والأيتام</title>
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
            border-top: none;
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa;
            padding: 1rem 0.75rem;
            border-bottom: 2px solid #dee2e6;
        }
        .table td {
            padding: 0.75rem;
            vertical-align: middle;
            text-align: center;
        }
        
        .table th {
            padding: 0.75rem;
            font-weight: 600;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            text-align: center;
        }
         .table tbody tr:hover {
             background-color: #f8f9fa;
         }
         .table tbody tr:last-child td {
             border-bottom: none;
        }
        .btn-action {
            padding: 0.3rem 0.5rem;
            font-size: 0.75rem;
            margin: 0 0.1rem;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.3s ease;
            min-width: 60px;
            text-align: center;
        }
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
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
        .martyr-badge {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include '../../includes/admin-sidebar.php'; ?>

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">إدارة الأيتام</h1>
            <div>
                <a href="orphan-registration.php" class="btn btn-primary me-2">
                    <i class="fas fa-plus me-1"></i>
                    إضافة يتيم جديد
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
                    <div class="stats-icon">
                        <i class="fas fa-child"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['total']); ?></div>
                    <div class="text-muted">إجمالي الأيتام</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-flag"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['war_martyrs']); ?></div>
                    <div class="text-muted">شهداء الحرب</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-male"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['males']); ?></div>
                    <div class="text-muted">ذكور</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-female"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['females']); ?></div>
                    <div class="text-muted">إناث</div>
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
                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="اسم الطفل أو المسؤول أو الأب أو رقم الهوية">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">المحافظة</label>
                        <select class="form-select" name="governorate">
                            <option value="">جميع المحافظات</option>
                            <?php foreach ($governorates as $gov): ?>
                                <option value="<?php echo $gov; ?>" <?php echo $governorate === $gov ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', $gov)); ?>
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
                        <label class="form-label">نوع الوفاة</label>
                        <select class="form-select" name="is_war_martyr">
                            <option value="">جميع الحالات</option>
                            <option value="1" <?php echo $is_war_martyr === '1' ? 'selected' : ''; ?>>شهيد حرب</option>
                            <option value="0" <?php echo $is_war_martyr === '0' ? 'selected' : ''; ?>>وفاة عادية</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                                بحث
                            </button>
                            <a href="admin-orphans.php" class="btn btn-outline-secondary">
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

        <!-- Orphans Table -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">قائمة الأيتام</h5>
                <div>
                    <button class="btn btn-sm btn-outline-primary" onclick="printTable()">
                        <i class="fas fa-print me-1"></i>
                        طباعة
                    </button>
                </div>
            </div>
            
            <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
                <table class="table table-hover table-sm" id="orphansTable" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th>#</th>
                            <th>اسم الطفل</th>
                            <th>رقم هوية الطفل</th>
                            <th>الجنس</th>
                            <th>اسم المسؤول</th>
                            <th>اسم الأب المتوفي</th>
                            <th>نوع الوفاة</th>
                            <th>المحافظة</th>
                            <th>العمر</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orphans as $index => $orphan): ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="orphan-checkbox" value="<?php echo $orphan['id']; ?>" onchange="updateSelection()">
                            </td>
                            <td><?php echo $offset + $index + 1; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($orphan['orphan_image'])): ?>
                                        <img src="uploads/orphan_images/<?php echo htmlspecialchars($orphan['orphan_image']); ?>" 
                                             class="rounded-circle me-2" width="30" height="30" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                             style="width: 30px; height: 30px;">
                                            <i class="fas fa-child text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($orphan['orphan_full_name'] ?? 'غير محدد'); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary fs-6">
                                    <?php echo htmlspecialchars($orphan['orphan_id_number'] ?? 'غير محدد'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo ($orphan['orphan_gender'] ?? '') === 'male' ? 'primary' : 'info'; ?> fs-6">
                                    <?php echo ($orphan['orphan_gender'] ?? '') === 'male' ? 'ذكر' : 'أنثى'; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($orphan['guardian_full_name'] ?? 'غير محدد'); ?></td>
                            <td><?php echo htmlspecialchars($orphan['deceased_father_name'] ?? 'غير محدد'); ?></td>
                            <td>
                                <?php if (!empty($orphan['is_war_martyr'])): ?>
                                    <span class="martyr-badge">
                                        <i class="fas fa-flag me-1"></i>
                                        شهيد حرب
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary fs-6">وفاة عادية</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info fs-6">
                                    <?php echo ucfirst(str_replace('_', ' ', $orphan['displacement_governorate'] ?? 'غير محدد')); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                // حساب العمر من تاريخ الميلاد
                                $birth_date = $orphan['orphan_birth_date'] ?? null;
                                if ($birth_date) {
                                    try {
                                        $birth = new DateTime($birth_date);
                                        $today = new DateTime();
                                        $age_diff = $today->diff($birth);
                                        
                                        $years = $age_diff->y;
                                        $months = $age_diff->m;
                                        $days = $age_diff->d;
                                        
                                        // تحديد لون البادج حسب العمر
                                        $badge_class = 'bg-info'; // أزرق للعمر العادي
                                        if ($years < 2) {
                                            $badge_class = 'bg-success'; // أخضر للأطفال الصغار
                                        } elseif ($years >= 18) {
                                            $badge_class = 'bg-warning'; // أصفر للبالغين
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
                                        
                                        echo '<span class="badge ' . $badge_class . ' fs-6" title="تاريخ الميلاد: ' . date('Y-m-d', strtotime($birth_date)) . '">' . $age_text . '</span>';
                                        
                                    } catch (Exception $e) {
                                        echo '<span class="badge bg-danger fs-6">تاريخ غير صحيح</span>';
                                    }
                                } else {
                                    echo '<span class="badge bg-secondary fs-6">غير محدد</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-view btn-action" onclick="viewOrphan(<?php echo $orphan['id']; ?>)">
                                    <i class="fas fa-eye me-1"></i>
                                    عرض
                                </button>
                                <a href="orphan-registration.php?edit=<?php echo $orphan['id']; ?>" class="btn btn-sm btn-edit btn-action">
                                    <i class="fas fa-edit me-1"></i>
                                    تعديل
                                </a>
                                <button class="btn btn-sm btn-delete btn-action" onclick="deleteOrphan(<?php echo $orphan['id']; ?>)">
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

    <!-- Orphan Details Modal -->
    <div class="modal fade" id="orphanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل اليتيم</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orphanDetails">
                    <!-- Orphan details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }

        function viewOrphan(orphanId) {
            // Load orphan details via AJAX
            fetch(`get-orphan-details.php?id=${orphanId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('orphanDetails').innerHTML = data;
                    new bootstrap.Modal(document.getElementById('orphanModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('خطأ في تحميل تفاصيل اليتيم');
                });
        }

        function deleteOrphan(orphanId) {
            if (confirm('هل أنت متأكد من حذف هذا اليتيم؟')) {
                window.location.href = `admin-orphans.php?action=delete&id=${orphanId}`;
            }
        }

        function exportData(format) {
            const params = new URLSearchParams(window.location.search);
            params.set('export', format);
            window.open(`export-orphans.php?${params.toString()}`, '_blank');
        }

        function printTable() {
            window.print();
        }

        // Initialize DataTable
        $(document).ready(function() {
            $('#orphansTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json"
                },
                "pageLength": 20,
                "order": [[8, "desc"]],
                "columnDefs": [
                    { "orderable": false, "targets": 9 }
                ]
            });
        });
    </script>
    <script src="assets/js/admin-sidebar.js"></script>
</body>
</html>
