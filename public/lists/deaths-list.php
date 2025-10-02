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
$family_branch = $_GET['family_branch'] ?? '';
$death_reason = $_GET['death_reason'] ?? '';

$whereConditions = [];
$params = [];

if ($search) {
    $whereConditions[] = "(first_name LIKE ? OR father_name LIKE ? OR grandfather_name LIKE ? OR family_name LIKE ? OR id_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($governorate) {
    $whereConditions[] = "governorate = ?";
    $params[] = $governorate;
}

if ($family_branch) {
    $whereConditions[] = "family_branch = ?";
    $params[] = $family_branch;
}

if ($death_reason) {
    $whereConditions[] = "death_reason = ?";
    $params[] = $death_reason;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// جلب البيانات
$deaths = $pdo->prepare("
    SELECT * FROM deaths 
    $whereClause
    ORDER BY created_at DESC
");
$deaths->execute($params);
$deaths = $deaths->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة الوفيات - نظام جمع بيانات العائلات والأيتام</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .table th { background-color: #f8f9fa; font-weight: 600; }
        .search-box { border-radius: 25px; border: 2px solid #e9ecef; padding: 0.5rem 1rem; }
        .search-box:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25); }
        .death-badge { background: linear-gradient(45deg, #6c757d, #495057); color: white; font-weight: bold; }
        .martyr-badge { background: linear-gradient(45deg, #dc3545, #c82333); color: white; font-weight: bold; }
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
                        <h1 class="display-5 fw-bold text-dark">
                            <i class="fas fa-cross me-3"></i>
                            قائمة الوفيات
                        </h1>
                        <p class="lead text-muted">عرض وإدارة بيانات الوفيات والشهداء المسجلين</p>
                    </div>
                    <div>
                        <a href="death-registration.php" class="btn btn-dark">
                            <i class="fas fa-plus me-2"></i>
                            إضافة وفاة جديدة
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
                                <div class="col-md-3">
                                    <input type="text" class="form-control search-box" name="search" 
                                           placeholder="البحث بالاسم أو رقم الهوية..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" name="governorate">
                                        <option value="">جميع المحافظات</option>
                                        <option value="gaza" <?php echo $governorate == 'gaza' ? 'selected' : ''; ?>>غزة</option>
                                        <option value="khan_younis" <?php echo $governorate == 'khan_younis' ? 'selected' : ''; ?>>خانيونس</option>
                                        <option value="rafah" <?php echo $governorate == 'rafah' ? 'selected' : ''; ?>>رفح</option>
                                        <option value="middle" <?php echo $governorate == 'middle' ? 'selected' : ''; ?>>الوسطى</option>
                                        <option value="north_gaza" <?php echo $governorate == 'north_gaza' ? 'selected' : ''; ?>>شمال غزة</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" name="family_branch">
                                        <option value="">جميع الفروع</option>
                                        <option value="الجواهرة" <?php echo $family_branch == 'الجواهرة' ? 'selected' : ''; ?>>الجواهرة</option>
                                        <option value="العواودة" <?php echo $family_branch == 'العواودة' ? 'selected' : ''; ?>>العواودة</option>
                                        <option value="البشيتي" <?php echo $family_branch == 'البشيتي' ? 'selected' : ''; ?>>البشيتي</option>
                                        <option value="زقماط" <?php echo $family_branch == 'زقماط' ? 'selected' : ''; ?>>زقماط</option>
                                        <option value="حندش والحمادين ابوحمدان" <?php echo $family_branch == 'حندش والحمادين ابوحمدان' ? 'selected' : ''; ?>>حندش والحمادين ابوحمدان</option>
                                        <option value="مقلد" <?php echo $family_branch == 'مقلد' ? 'selected' : ''; ?>>مقلد</option>
                                        <option value="الدجاجنة" <?php echo $family_branch == 'الدجاجنة' ? 'selected' : ''; ?>>الدجاجنة</option>
                                        <option value="قريده" <?php echo $family_branch == 'قريده' ? 'selected' : ''; ?>>قريده</option>
                                        <option value="الصوفي" <?php echo $family_branch == 'الصوفي' ? 'selected' : ''; ?>>الصوفي</option>
                                        <option value="مصبح" <?php echo $family_branch == 'مصبح' ? 'selected' : ''; ?>>مصبح</option>
                                        <option value="قرقوش" <?php echo $family_branch == 'قرقوش' ? 'selected' : ''; ?>>قرقوش</option>
                                        <option value="العوايضة" <?php echo $family_branch == 'العوايضة' ? 'selected' : ''; ?>>العوايضة</option>
                                        <option value="السوالمة" <?php echo $family_branch == 'السوالمة' ? 'selected' : ''; ?>>السوالمة</option>
                                        <option value="عرادة" <?php echo $family_branch == 'عرادة' ? 'selected' : ''; ?>>عرادة</option>
                                        <option value="البراهمة" <?php echo $family_branch == 'البراهمة' ? 'selected' : ''; ?>>البراهمة</option>
                                        <option value="العيسة" <?php echo $family_branch == 'العيسة' ? 'selected' : ''; ?>>العيسة</option>
                                        <option value="المحامدة" <?php echo $family_branch == 'المحامدة' ? 'selected' : ''; ?>>المحامدة</option>
                                        <option value="ابوسري" <?php echo $family_branch == 'ابوسري' ? 'selected' : ''; ?>>ابوسري</option>
                                        <option value="المهاوشة" <?php echo $family_branch == 'المهاوشة' ? 'selected' : ''; ?>>المهاوشة</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" name="death_reason">
                                        <option value="">جميع الأسباب</option>
                                        <option value="martyr" <?php echo $death_reason == 'martyr' ? 'selected' : ''; ?>>شهيد</option>
                                        <option value="bombing" <?php echo $death_reason == 'bombing' ? 'selected' : ''; ?>>قصف</option>
                                        <option value="disease" <?php echo $death_reason == 'disease' ? 'selected' : ''; ?>>مرض</option>
                                        <option value="accident" <?php echo $death_reason == 'accident' ? 'selected' : ''; ?>>حادث</option>
                                        <option value="natural" <?php echo $death_reason == 'natural' ? 'selected' : ''; ?>>وفاة طبيعية</option>
                                        <option value="other" <?php echo $death_reason == 'other' ? 'selected' : ''; ?>>أخرى</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-dark w-100">
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

        <!-- Deaths Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>
                            قائمة الوفيات (<?php echo count($deaths); ?> حالة وفاة)
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>صورة</th>
                                        <th>الاسم الرباعي</th>
                                        <th>رقم الهوية</th>
                                        <th>تاريخ الميلاد</th>
                                        <th>تاريخ الوفاة</th>
                                        <th>الفرع العائلي</th>
                                        <th>سبب الوفاة</th>
                                        <th>المحافظة</th>
                                        <th>مدخل الطلب</th>
                                        <th>صلة مدخل الطلب</th>
                                        <th>تاريخ التسجيل</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($deaths)): ?>
                                    <tr>
                                        <td colspan="13" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-cross fa-3x mb-3"></i>
                                                <p>لا توجد وفيات مسجلة</p>
                                                <a href="death-registration.php" class="btn btn-dark">
                                                    <i class="fas fa-plus me-2"></i>
                                                    إضافة وفاة جديدة
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($deaths as $index => $death): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <?php if ($death['personal_photo'] && file_exists($death['personal_photo'])): ?>
                                                <img src="<?php echo $death['personal_photo']; ?>" 
                                                     class="image-thumbnail" 
                                                     alt="صورة <?php echo $death['first_name'] . ' ' . $death['family_name']; ?>">
                                            <?php else: ?>
                                                <div class="image-thumbnail d-flex align-items-center justify-content-center bg-light">
                                                    <i class="fas fa-user text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo $death['first_name'] . ' ' . $death['father_name'] . ' ' . $death['grandfather_name'] . ' ' . $death['family_name']; ?></div>
                                        </td>
                                        <td><code><?php echo $death['id_number']; ?></code></td>
                                        <td>
                                            <span class="badge death-badge">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo date('Y-m-d', strtotime($death['birth_date'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $death['death_reason'] == 'martyr' ? 'martyr-badge' : 'death-badge'; ?>">
                                                <i class="fas fa-cross me-1"></i>
                                                <?php echo date('Y-m-d', strtotime($death['death_date'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $death['family_branch']; ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $death_reasons = [
                                                'martyr' => 'شهيد',
                                                'bombing' => 'قصف',
                                                'disease' => 'مرض',
                                                'accident' => 'حادث',
                                                'natural' => 'وفاة طبيعية',
                                                'other' => 'أخرى'
                                            ];
                                            ?>
                                            <span class="badge <?php echo $death['death_reason'] == 'martyr' ? 'bg-danger' : 'bg-info'; ?>">
                                                <?php echo $death_reasons[$death['death_reason']] ?? $death['death_reason']; ?>
                                            </span>
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
                                            <span class="badge bg-warning"><?php echo $governorates[$death['governorate']] ?? $death['governorate']; ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo $death['requester_first_name'] . ' ' . $death['requester_father_name'] . ' ' . $death['requester_grandfather_name'] . ' ' . $death['requester_family_name']; ?></div>
                                        </td>
                                        <td>
                                            <?php
                                            $relationships = [
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
                                            ?>
                                            <span class="badge bg-info"><?php echo $relationships[$death['requester_relationship']] ?? $death['requester_relationship']; ?></span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo date('Y-m-d', strtotime($death['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info" onclick="viewDeath(<?php echo $death['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="editDeath(<?php echo $death['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteDeath(<?php echo $death['id']; ?>)">
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
        function viewDeath(id) {
            // يمكن إضافة منطق العرض هنا
            alert('عرض تفاصيل الوفاة رقم: ' + id);
        }

        function editDeath(id) {
            // يمكن إضافة منطق التعديل هنا
            alert('تعديل الوفاة رقم: ' + id);
        }

        function deleteDeath(id) {
            if (confirm('هل أنت متأكد من حذف هذه الوفاة؟')) {
                // يمكن إضافة منطق الحذف هنا
                alert('حذف الوفاة رقم: ' + id);
            }
        }
    </script>
</body>
</html>
