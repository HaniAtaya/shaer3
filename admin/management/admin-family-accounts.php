<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../auth/admin-login.php');
    exit;
}

require_once 'includes/db_connection.php';

// معالجة الطلبات
$message = '';
$error = '';

if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'reset_password':
                    $family_id = $_POST['family_id'];
                    $new_password = $_POST['new_password'];
                    
                    // التحقق من صحة كلمة المرور
                    require_once 'includes/generate-access-code.php';
                    if (!validateAccessCode($new_password)) {
                        throw new Exception('كلمة المرور يجب أن تكون بين 6 و 20 خانة وتحتوي على أحرف أو أرقام أو رموز مسموحة');
                    }
                    
                    // التحقق من عدم وجود كلمة المرور لعائلة أخرى
                    $stmt = $pdo->prepare("SELECT family_id FROM family_access_codes WHERE access_code = ? AND family_id != ?");
                    $stmt->execute([$new_password, $family_id]);
                    
                    if ($stmt->fetch()) {
                        throw new Exception('كلمة المرور هذه مستخدمة من قبل عائلة أخرى');
                    }
                    
                    // تحديث كلمة المرور
                    try {
                        $stmt = $pdo->prepare("UPDATE family_access_codes SET access_code = ?, password_changed = 0 WHERE family_id = ?");
                        $stmt->execute([$new_password, $family_id]);
                        
                        $message = 'تم تحديث كلمة المرور بنجاح';
                    } catch (PDOException $e) {
                        if ($e->getCode() == 23000) {
                            throw new Exception('كلمة المرور هذه مستخدمة من قبل عائلة أخرى');
                        } else {
                            throw new Exception('حدث خطأ أثناء تحديث كلمة المرور: ' . $e->getMessage());
                        }
                    }
                    break;
                    
                case 'delete_account':
                    $family_id = $_POST['family_id'];
                    
                    // حذف كلمة المرور
                    $stmt = $pdo->prepare("DELETE FROM family_access_codes WHERE family_id = ?");
                    $stmt->execute([$family_id]);
                    
                    $message = 'تم حذف حساب العائلة بنجاح';
                    break;
                    
                case 'generate_password':
                    $family_id = $_POST['family_id'];
                    
                    // توليد كلمة مرور جديدة
                    require_once 'includes/generate-access-code.php';
                    $new_password = generateAccessCode();
                    
                    // التحقق من عدم وجود كلمة المرور
                    $stmt = $pdo->prepare("SELECT id FROM family_access_codes WHERE access_code = ?");
                    $stmt->execute([$new_password]);
                    
                    while ($stmt->fetch()) {
                        $new_password = generateAccessCode();
                        $stmt->execute([$new_password]);
                    }
                    
                    // تحديث أو إدراج كلمة المرور
                    $stmt = $pdo->prepare("INSERT INTO family_access_codes (family_id, access_code) VALUES (?, ?) ON DUPLICATE KEY UPDATE access_code = ?");
                    $stmt->execute([$family_id, $new_password, $new_password]);
                    
                    $message = 'تم توليد كلمة مرور جديدة: ' . $new_password;
                    break;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// جلب بيانات العائلات مع كلمات المرور
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$where_clause = '';
$params = [];

if (!empty($search)) {
    $where_clause = "WHERE f.first_name LIKE ? OR f.family_name LIKE ? OR f.id_number LIKE ? OR fac.access_code LIKE ?";
    $search_term = "%$search%";
    $params = [$search_term, $search_term, $search_term, $search_term];
}

// جلب العائلات
$sql = "SELECT DISTINCT f.id, f.first_name, f.family_name, f.id_number, f.primary_phone, 
               fac.access_code, fac.password_changed, fac.created_at as password_created,
               (SELECT COUNT(*) FROM family_members WHERE family_id = f.id) as members_count
        FROM families f 
        LEFT JOIN family_access_codes fac ON f.id = fac.family_id 
        $where_clause
        ORDER BY f.id DESC 
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$families = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب أسئلة الأمان لكل عائلة بشكل منفصل
foreach ($families as &$family) {
    $stmt = $pdo->prepare("SELECT question, answer FROM family_security_questions WHERE family_id = ?");
    $stmt->execute([$family['id']]);
    $security_question = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($security_question) {
        $family['security_question'] = $security_question['question'];
        $family['security_answer'] = $security_question['answer'];
    } else {
        $family['security_question'] = null;
        $family['security_answer'] = null;
    }
}

// جلب العدد الإجمالي
$count_sql = "SELECT COUNT(DISTINCT f.id) FROM families f LEFT JOIN family_access_codes fac ON f.id = fac.family_id $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_families = $count_stmt->fetchColumn();
$total_pages = ceil($total_families / $limit);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة حسابات الأسر - الشاعر عائلتي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin-sidebar.css" rel="stylesheet">
    <style>
        .account-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .account-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .password-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-changed {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .status-default {
            background-color: #f8d7da;
            color: #721c24;
        }
        .password-display {
            font-family: 'Courier New', monospace;
            font-size: 1.2rem;
            font-weight: bold;
            letter-spacing: 2px;
            background-color: #f8f9fa;
            padding: 0.5rem;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .btn-action {
            margin: 0.25rem;
        }
        .search-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .stats-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 1rem;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include '../../includes/admin-sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-users-cog me-2"></i>إدارة حسابات الأسر</h1>
                <div>
                    <a href="admin-dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-right me-2"></i>العودة للوحة التحكم
                    </a>
                </div>
            </div>

            <!-- الرسائل -->
            <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- إحصائيات سريعة -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $total_families; ?></div>
                        <div>إجمالي العائلات</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count(array_filter($families, function($f) { return !empty($f['access_code']); })); ?></div>
                        <div>حسابات نشطة</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count(array_filter($families, function($f) { return $f['password_changed'] == 1; })); ?></div>
                        <div>كلمات مرور محدثة</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count(array_filter($families, function($f) { return empty($f['access_code']); })); ?></div>
                        <div>بدون حساب</div>
                    </div>
                </div>
            </div>

            <!-- البحث -->
            <div class="search-container">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <input type="text" class="form-control form-control-lg" name="search" 
                               placeholder="البحث بالاسم أو رقم الهوية أو كلمة المرور..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-light btn-lg w-100">
                            <i class="fas fa-search me-2"></i>بحث
                        </button>
                    </div>
                </form>
            </div>

            <!-- قائمة العائلات -->
            <div class="row">
                <?php foreach ($families as $family): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="account-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($family['first_name'] . ' ' . $family['family_name']); ?></h5>
                                <small class="text-muted"><?php echo htmlspecialchars($family['id_number']); ?></small>
                            </div>
                            <span class="password-status <?php echo $family['password_changed'] ? 'status-changed' : 'status-default'; ?>">
                                <?php echo $family['password_changed'] ? 'محدثة' : 'افتراضية'; ?>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-6">
                                    <strong><i class="fas fa-id-card me-1"></i>رقم الهوية:</strong><br>
                                    <span class="text-primary fw-bold"><?php echo htmlspecialchars($family['id_number']); ?></span>
                                </div>
                                <div class="col-6">
                                    <strong><i class="fas fa-phone me-1"></i>الهاتف:</strong><br>
                                    <span class="text-success"><?php echo htmlspecialchars($family['primary_phone']); ?></span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <strong><i class="fas fa-users me-1"></i>عدد الأفراد:</strong><br>
                                    <span class="badge bg-info"><?php echo $family['members_count']; ?></span>
                                </div>
                                <?php if ($family['password_created']): ?>
                                <div class="col-6">
                                    <strong><i class="fas fa-calendar me-1"></i>تاريخ الحساب:</strong><br>
                                    <small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($family['password_created'])); ?></small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($family['security_question']): ?>
                        <div class="mb-3">
                            <div class="alert alert-info">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    <strong>سؤال الأمان</strong>
                                    <span class="badge bg-success ms-auto">مفعل</span>
                                </div>
                                <div class="mt-2">
                                    <strong>السؤال:</strong> 
                                    <span class="text-dark"><?php echo htmlspecialchars($family['security_question']); ?></span>
                                </div>
                                <div class="mt-1">
                                    <strong>الإجابة:</strong> 
                                    <span class="text-primary fw-bold"><?php echo htmlspecialchars($family['security_answer']); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="mb-3">
                            <div class="alert alert-warning">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <div>
                                        <strong>لا يوجد سؤال أمان</strong>
                                        <br><small>هذه العائلة لم تقم بإضافة سؤال أمان بعد</small>
                                    </div>
                                    <span class="badge bg-warning ms-auto">غير مفعل</span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($family['access_code']): ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">كلمة المرور الحالية:</label>
                            <div class="password-display" style="word-break: break-all; white-space: normal;">
                                <?php echo htmlspecialchars($family['access_code']); ?>
                            </div>
                            <small class="text-muted">الطول: <?php echo strlen($family['access_code']); ?> خانة</small>
                        </div>
                        <?php endif; ?>

                        <div class="d-flex flex-wrap">
                            <?php if ($family['access_code']): ?>
                            <button class="btn btn-warning btn-sm btn-action" 
                                    onclick="showPasswordModal(<?php echo $family['id']; ?>, '<?php echo $family['access_code']; ?>')">
                                <i class="fas fa-edit me-1"></i>تغيير
                            </button>
                            <button class="btn btn-info btn-sm btn-action" 
                                    onclick="generatePassword(<?php echo $family['id']; ?>)">
                                <i class="fas fa-sync me-1"></i>توليد جديد
                            </button>
                            <button class="btn btn-danger btn-sm btn-action" 
                                    onclick="deleteAccount(<?php echo $family['id']; ?>, '<?php echo htmlspecialchars($family['first_name'] . ' ' . $family['family_name']); ?>')">
                                <i class="fas fa-trash me-1"></i>حذف
                            </button>
                            <?php else: ?>
                            <button class="btn btn-success btn-sm btn-action" 
                                    onclick="generatePassword(<?php echo $family['id']; ?>)">
                                <i class="fas fa-plus me-1"></i>إنشاء حساب
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal تغيير كلمة المرور -->
    <div class="modal fade" id="passwordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تغيير كلمة المرور</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="family_id" id="modal_family_id">
                        
                        <div class="mb-3">
                            <label class="form-label">كلمة المرور الجديدة (6-20 خانة)</label>
                            <input type="text" class="form-control" name="new_password" 
                                   maxlength="20" required>
                            <div class="form-text">
                                يمكن أن تحتوي على أحرف وأرقام ورموز (!@#$%&*)
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-warning">تغيير</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-sidebar.js"></script>
    <script>
        function showPasswordModal(familyId, currentPassword) {
            document.getElementById('modal_family_id').value = familyId;
            document.querySelector('input[name="new_password"]').value = currentPassword;
            new bootstrap.Modal(document.getElementById('passwordModal')).show();
        }

        function generatePassword(familyId) {
            if (confirm('هل تريد توليد كلمة مرور جديدة لهذه العائلة؟')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="generate_password">
                    <input type="hidden" name="family_id" value="${familyId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteAccount(familyId, familyName) {
            if (confirm(`هل أنت متأكد من حذف حساب العائلة: ${familyName}؟\n\nهذا الإجراء لا يمكن التراجع عنه!`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_account">
                    <input type="hidden" name="family_id" value="${familyId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // السماح بإدخال الأرقام فقط في حقل كلمة المرور
        document.querySelector('input[name="new_password"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>
