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

// البحث والفلترة
$search = $_GET['search'] ?? '';
$governorate = $_GET['governorate'] ?? '';
$isWarMartyr = $_GET['is_war_martyr'] ?? '';

$whereConditions = [];
$params = [];

if ($search) {
    $whereConditions[] = "(orphan_full_name LIKE ? OR guardian_full_name LIKE ? OR orphan_id_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($governorate) {
    $whereConditions[] = "displacement_governorate = ?";
    $params[] = $governorate;
}

if ($isWarMartyr !== '') {
    $whereConditions[] = "is_war_martyr = ?";
    $params[] = $isWarMartyr;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// جلب البيانات
$orphans = $pdo->prepare("
    SELECT * FROM orphans 
    $whereClause
    ORDER BY created_at DESC
");
$orphans->execute($params);
$orphans = $orphans->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة الأيتام - نظام جمع بيانات العائلات والأيتام</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .table th { background-color: #f8f9fa; font-weight: 600; }
        .search-box { border-radius: 25px; border: 2px solid #e9ecef; padding: 0.5rem 1rem; }
        .search-box:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25); }
        .martyr-badge { background: linear-gradient(45deg, #ff6b6b, #ee5a24); color: white; font-weight: bold; }
        .image-thumbnail { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 2px solid #dee2e6; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">
                <i class="fas fa-heart me-2"></i>
                نظام جمع بيانات العائلات والأيتام
            </a>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="display-5 fw-bold text-success">
                            <i class="fas fa-child me-3"></i>
                            قائمة الأيتام
                        </h1>
                        <p class="lead text-muted">عرض وإدارة بيانات الأيتام المسجلين</p>
                    </div>
                    <div>
                        <a href="orphan-registration.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            إضافة يتيم جديد
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <input type="text" class="form-control search-box" name="search" 
                                           placeholder="البحث بالاسم أو رقم الهوية..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="governorate">
                                        <option value="">جميع المحافظات</option>
                                        <option value="gaza" <?php echo $governorate == 'gaza' ? 'selected' : ''; ?>>غزة</option>
                                        <option value="khan_younis" <?php echo $governorate == 'khan_younis' ? 'selected' : ''; ?>>خانيونس</option>
                                        <option value="rafah" <?php echo $governorate == 'rafah' ? 'selected' : ''; ?>>رفح</option>
                                        <option value="middle" <?php echo $governorate == 'middle' ? 'selected' : ''; ?>>الوسطى</option>
                                        <option value="north_gaza" <?php echo $governorate == 'north_gaza' ? 'selected' : ''; ?>>شمال غزة</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="is_war_martyr">
                                        <option value="">جميع الحالات</option>
                                        <option value="1" <?php echo $isWarMartyr === '1' ? 'selected' : ''; ?>>شهداء الحرب</option>
                                        <option value="0" <?php echo $isWarMartyr === '0' ? 'selected' : ''; ?>>غير شهداء الحرب</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-2"></i>
                                        بحث
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orphans Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>
                            قائمة الأيتام (<?php echo count($orphans); ?> يتيم)
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>صورة</th>
                                        <th>اسم اليتيم</th>
                                        <th>رقم الهوية</th>
                                        <th>الجنس</th>
                                        <th>اسم المسؤول</th>
                                        <th>صلة المسؤول</th>
                                        <th>اسم الأب المتوفي</th>
                                        <th>شهيد حرب</th>
                                        <th>المحافظة</th>
                                        <th>تاريخ التسجيل</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orphans)): ?>
                                    <tr>
                                        <td colspan="12" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-child fa-3x mb-3"></i>
                                                <p>لا توجد أيتام مسجلين</p>
                                                <a href="orphan-registration.php" class="btn btn-primary">
                                                    <i class="fas fa-plus me-2"></i>
                                                    إضافة يتيم جديد
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($orphans as $index => $orphan): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <?php if ($orphan['orphan_image'] && file_exists($orphan['orphan_image'])): ?>
                                                <img src="<?php echo $orphan['orphan_image']; ?>" 
                                                     class="image-thumbnail" 
                                                     alt="صورة <?php echo $orphan['orphan_full_name']; ?>">
                                            <?php else: ?>
                                                <div class="image-thumbnail d-flex align-items-center justify-content-center bg-light">
                                                    <i class="fas fa-user text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo $orphan['orphan_full_name']; ?></div>
                                            <small class="text-muted">تاريخ الميلاد: <?php echo date('Y-m-d', strtotime($orphan['orphan_birth_date'])); ?></small>
                                        </td>
                                        <td><code><?php echo $orphan['orphan_id_number']; ?></code></td>
                                        <td>
                                            <span class="badge <?php echo $orphan['orphan_gender'] == 'male' ? 'bg-primary' : 'bg-pink'; ?>">
                                                <?php echo $orphan['orphan_gender'] == 'male' ? 'ذكر' : 'أنثى'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo $orphan['guardian_full_name']; ?></div>
                                            <small class="text-muted"><?php echo $orphan['guardian_primary_phone']; ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $relationships = [
                                                'son' => 'ابن',
                                                'daughter' => 'ابنة',
                                                'brother' => 'أخ',
                                                'sister' => 'أخت',
                                                'grandfather' => 'جد',
                                                'grandmother' => 'جدة',
                                                'mother' => 'أم',
                                                'father' => 'أب'
                                            ];
                                            ?>
                                            <span class="badge bg-info"><?php echo $relationships[$orphan['guardian_relationship']] ?? $orphan['guardian_relationship']; ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo $orphan['deceased_father_name']; ?></div>
                                            <small class="text-muted">تاريخ الاستشهاد: <?php echo date('Y-m-d', strtotime($orphan['martyrdom_date'])); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($orphan['is_war_martyr']): ?>
                                                <span class="badge martyr-badge">
                                                    <i class="fas fa-flag me-1"></i>
                                                    شهيد حرب
                                                </span>
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
                                            ?>
                                            <span class="badge bg-warning"><?php echo $governorates[$orphan['displacement_governorate']] ?? $orphan['displacement_governorate']; ?></span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo date('Y-m-d', strtotime($orphan['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info" onclick="viewOrphan(<?php echo $orphan['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="editOrphan(<?php echo $orphan['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteOrphan(<?php echo $orphan['id']; ?>)">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewOrphan(id) {
            // يمكن إضافة منطق العرض هنا
            alert('عرض تفاصيل اليتيم رقم: ' + id);
        }

        function editOrphan(id) {
            // يمكن إضافة منطق التعديل هنا
            alert('تعديل اليتيم رقم: ' + id);
        }

        function deleteOrphan(id) {
            if (confirm('هل أنت متأكد من حذف هذا اليتيم؟')) {
                // يمكن إضافة منطق الحذف هنا
                alert('حذف اليتيم رقم: ' + id);
            }
        }
    </script>
</body>
</html>
