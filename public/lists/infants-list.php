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
    $whereConditions[] = "displacement_governorate = ?";
    $params[] = $governorate;
}

if ($family_branch) {
    $whereConditions[] = "family_branch = ?";
    $params[] = $family_branch;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// جلب البيانات
$infants = $pdo->prepare("
    SELECT * FROM infants 
    $whereClause
    ORDER BY created_at DESC
");
$infants->execute($params);
$infants = $infants->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة الرضع - نظام جمع بيانات العائلات والأيتام</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .table th { background-color: #f8f9fa; font-weight: 600; }
        .search-box { border-radius: 25px; border: 2px solid #e9ecef; padding: 0.5rem 1rem; }
        .search-box:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25); }
        .infant-badge { background: linear-gradient(45deg, #17a2b8, #138496); color: white; font-weight: bold; }
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
                        <h1 class="display-5 fw-bold text-info">
                            <i class="fas fa-baby me-3"></i>
                            قائمة الرضع
                        </h1>
                        <p class="lead text-muted">عرض وإدارة بيانات الرضع المسجلين (أقل من سنتين)</p>
                    </div>
                    <div>
                        <a href="infant-registration.php" class="btn btn-info">
                            <i class="fas fa-plus me-2"></i>
                            إضافة رضيع جديد
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
                                    <select class="form-select" name="family_branch">
                                        <option value="">جميع الفروع العائلية</option>
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
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-info w-100">
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

        <!-- Infants Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>
                            قائمة الرضع (<?php echo count($infants); ?> رضيع)
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الاسم الرباعي</th>
                                        <th>رقم الهوية</th>
                                        <th>تاريخ الميلاد</th>
                                        <th>الفرع العائلي</th>
                                        <th>رقم الهاتف</th>
                                        <th>المحافظة</th>
                                        <th>المنطقة</th>
                                        <th>حالة السكن</th>
                                        <th>تاريخ التسجيل</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($infants)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-baby fa-3x mb-3"></i>
                                                <p>لا توجد رضع مسجلين</p>
                                                <a href="infant-registration.php" class="btn btn-info">
                                                    <i class="fas fa-plus me-2"></i>
                                                    إضافة رضيع جديد
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($infants as $index => $infant): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <div class="fw-bold"><?php echo $infant['first_name'] . ' ' . $infant['father_name'] . ' ' . $infant['grandfather_name'] . ' ' . $infant['family_name']; ?></div>
                                        </td>
                                        <td><code><?php echo $infant['id_number']; ?></code></td>
                                        <td>
                                            <span class="badge infant-badge">
                                                <i class="fas fa-baby me-1"></i>
                                                <?php echo date('Y-m-d', strtotime($infant['birth_date'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $infant['family_branch']; ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo $infant['primary_phone']; ?></div>
                                            <?php if ($infant['secondary_phone']): ?>
                                                <small class="text-muted"><?php echo $infant['secondary_phone']; ?></small>
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
                                            <span class="badge bg-warning"><?php echo $governorates[$infant['displacement_governorate']] ?? $infant['displacement_governorate']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $infant['displacement_area']; ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $housing_status = [
                                                'tent' => 'خيمة',
                                                'apartment' => 'شقة',
                                                'house' => 'بيت',
                                                'school' => 'مدرسة'
                                            ];
                                            ?>
                                            <span class="badge bg-success"><?php echo $housing_status[$infant['housing_status']] ?? $infant['housing_status']; ?></span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo date('Y-m-d', strtotime($infant['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info" onclick="viewInfant(<?php echo $infant['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="editInfant(<?php echo $infant['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteInfant(<?php echo $infant['id']; ?>)">
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
        function viewInfant(id) {
            // يمكن إضافة منطق العرض هنا
            alert('عرض تفاصيل الرضيع رقم: ' + id);
        }

        function editInfant(id) {
            // يمكن إضافة منطق التعديل هنا
            alert('تعديل الرضيع رقم: ' + id);
        }

        function deleteInfant(id) {
            if (confirm('هل أنت متأكد من حذف هذا الرضيع؟')) {
                // يمكن إضافة منطق الحذف هنا
                alert('حذف الرضيع رقم: ' + id);
            }
        }
    </script>
</body>
</html>
