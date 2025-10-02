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

// إنشاء جدول المشرفين إذا لم يكن موجوداً
$pdo->exec("CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'admin', 'moderator', 'family_admin') DEFAULT 'admin',
    family_branch VARCHAR(100) NULL,
    permissions TEXT,
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// إضافة عمود الفرع العائلي إذا لم يكن موجوداً
try {
    $pdo->exec("ALTER TABLE admins ADD COLUMN family_branch VARCHAR(100) NULL AFTER role");
} catch (PDOException $e) {
    // العمود موجود بالفعل
}

// إضافة عمود كلمة المرور إذا لم يكن موجوداً
try {
    $pdo->exec("ALTER TABLE admins ADD COLUMN password VARCHAR(255) NOT NULL DEFAULT '' AFTER email");
} catch (PDOException $e) {
    // العمود موجود بالفعل
}

// معالجة العمليات
$action = $_GET['action'] ?? '';
$admin_id = $_GET['id'] ?? '';

$success_message = '';
$error_message = '';

if ($_POST) {
    if (isset($_POST['add_admin'])) {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $role = $_POST['role'] ?? 'admin';
        $family_branch = $_POST['family_branch'] ?? '';
        $permissions = $_POST['permissions'] ?? [];
        
        if (empty($username) || empty($email) || empty($password) || empty($name)) {
            $error_message = 'جميع الحقول مطلوبة';
        } elseif (strlen($password) < 6) {
            $error_message = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'البريد الإلكتروني غير صحيح';
        } elseif ($role === 'family_admin' && empty($family_branch)) {
            $error_message = 'يجب اختيار الفرع العائلي لمشرف العائلة';
        } else {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $permissions_json = json_encode($permissions);
                
                $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, full_name, role, family_branch, permissions) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password, $name, $role, $family_branch, $permissions_json]);
                
                $success_message = 'تم إضافة المشرف بنجاح';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error_message = 'اسم المستخدم أو البريد الإلكتروني موجود بالفعل';
                } else {
                    $error_message = 'خطأ في إضافة المشرف: ' . $e->getMessage();
                }
            }
        }
    }
    
    if (isset($_POST['update_admin'])) {
        $admin_id = $_POST['admin_id'];
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'admin';
        $family_branch = $_POST['family_branch'] ?? '';
        $permissions = $_POST['permissions'] ?? [];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name) || empty($email)) {
            $error_message = 'الاسم والبريد الإلكتروني مطلوبان';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'البريد الإلكتروني غير صحيح';
        } elseif ($role === 'family_admin' && empty($family_branch)) {
            $error_message = 'يجب اختيار الفرع العائلي لمشرف العائلة';
        } else {
            try {
                $permissions_json = json_encode($permissions);
                $stmt = $pdo->prepare("UPDATE admins SET full_name = ?, email = ?, role = ?, family_branch = ?, permissions = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$name, $email, $role, $family_branch, $permissions_json, $is_active, $admin_id]);
                
                $success_message = 'تم تحديث المشرف بنجاح';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error_message = 'البريد الإلكتروني موجود بالفعل';
                } else {
                    $error_message = 'خطأ في تحديث المشرف: ' . $e->getMessage();
                }
            }
        }
    }
}

if ($action === 'delete' && $admin_id) {
    if ($admin_id == $_SESSION['admin_id']) {
        $error_message = 'لا يمكن حذف حسابك الخاص';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->execute([$admin_id]);
            $success_message = 'تم حذف المشرف بنجاح';
        } catch (PDOException $e) {
            $error_message = 'خطأ في حذف المشرف: ' . $e->getMessage();
        }
    }
}

if ($action === 'toggle_status' && $admin_id) {
    if ($admin_id == $_SESSION['admin_id']) {
        $error_message = 'لا يمكن تعطيل حسابك الخاص';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE admins SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$admin_id]);
            $success_message = 'تم تغيير حالة المشرف بنجاح';
        } catch (PDOException $e) {
            $error_message = 'خطأ في تغيير حالة المشرف: ' . $e->getMessage();
        }
    }
}

// جلب قائمة المشرفين
try {
    $stmt = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // التأكد من وجود البيانات
    if (empty($admins)) {
        $admins = [];
    }
} catch (PDOException $e) {
    $error_message = 'خطأ في جلب قائمة المشرفين: ' . $e->getMessage();
    $admins = [];
}

// ترجمة الأدوار
$role_labels = [
    'super_admin' => 'مدير عام',
    'admin' => 'مدير',
    'moderator' => 'مشرف',
    'family_admin' => 'مشرف فرع عائلي'
];

// ترجمة الصلاحيات
$permission_labels = [
    'families_view' => 'عرض العائلات',
    'families_add' => 'إضافة العائلات',
    'families_edit' => 'تعديل العائلات',
    'families_delete' => 'حذف العائلات',
    'orphans_view' => 'عرض الأيتام',
    'orphans_add' => 'إضافة الأيتام',
    'orphans_edit' => 'تعديل الأيتام',
    'orphans_delete' => 'حذف الأيتام',
    'reports_view' => 'عرض التقارير',
    'reports_export' => 'تصدير التقارير',
    'admins_manage' => 'إدارة المشرفين',
    'settings_manage' => 'إدارة الإعدادات'
];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المشرفين - نظام إدارة العائلات والأيتام</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin-sidebar.css" rel="stylesheet">
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
        .table-responsive {
            max-height: 50vh;
            overflow-y: auto;
        }
        .table {
            font-size: 12px;
        }
        .permission-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
            margin: 0.1rem;
        }
    </style>
</head>
<body>
    <?php include '../../includes/admin-sidebar.php'; ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-users-cog me-2"></i>إدارة المشرفين</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                        <i class="fas fa-plus me-1"></i>
                        إضافة مشرف جديد
                    </button>
                </div>

                <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- جدول المشرفين -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>قائمة المشرفين</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الاسم</th>
                                        <th>اسم المستخدم</th>
                                        <th>البريد الإلكتروني</th>
                                        <th>الدور</th>
                                        <th>الفرع العائلي</th>
                                        <th>الصلاحيات</th>
                                        <th>الحالة</th>
                                        <th>آخر دخول</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admins as $index => $admin): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($admin['full_name'] ?? 'غير محدد'); ?></td>
                                        <td><?php echo htmlspecialchars($admin['username'] ?? 'غير محدد'); ?></td>
                                        <td><?php echo htmlspecialchars($admin['email'] ?? 'غير محدد'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $admin['role'] === 'super_admin' ? 'danger' : ($admin['role'] === 'admin' ? 'primary' : ($admin['role'] === 'family_admin' ? 'warning' : 'info')); ?>">
                                                <?php echo $role_labels[$admin['role'] ?? 'admin'] ?? 'غير محدد'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (($admin['role'] ?? '') === 'family_admin' && !empty($admin['family_branch'] ?? '')): ?>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($admin['family_branch']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $permissions = json_decode($admin['permissions'] ?? '[]', true) ?: [];
                                            foreach ($permissions as $permission):
                                            ?>
                                            <span class="badge bg-secondary permission-badge">
                                                <?php echo $permission_labels[$permission] ?? $permission; ?>
                                            </span>
                                            <?php endforeach; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo ($admin['is_active'] ?? 1) ? 'success' : 'danger'; ?>">
                                                <?php echo ($admin['is_active'] ?? 1) ? 'نشط' : 'معطل'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo !empty($admin['last_login']) ? date('Y-m-d H:i', strtotime($admin['last_login'])) : 'لم يسجل دخول'; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary btn-action" onclick="editAdmin(<?php echo $admin['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                            <button class="btn btn-sm btn-outline-<?php echo $admin['is_active'] ? 'warning' : 'success'; ?> btn-action" 
                                                    onclick="toggleStatus(<?php echo $admin['id']; ?>)">
                                                <i class="fas fa-<?php echo $admin['is_active'] ? 'ban' : 'check'; ?>"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger btn-action" 
                                                    onclick="deleteAdmin(<?php echo $admin['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
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

    <!-- Modal إضافة مشرف -->
    <div class="modal fade" id="addAdminModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة مشرف جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">الاسم الكامل</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">اسم المستخدم</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">كلمة المرور</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">الدور</label>
                                    <select class="form-select" id="role" name="role" onchange="toggleFamilyBranch()">
                                        <option value="moderator">مشرف</option>
                                        <option value="admin">مدير</option>
                                        <option value="family_admin">مشرف فرع عائلي</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3" id="family_branch_div" style="display: none;">
                                    <label for="family_branch" class="form-label">الفرع العائلي</label>
                                    <select class="form-select" id="family_branch" name="family_branch">
                                        <option value="">اختر الفرع العائلي</option>
                                        <option value="الجواهرة">الجواهرة</option>
                                        <option value="العواودة">العواودة</option>
                                        <option value="البشيتي">البشيتي</option>
                                        <option value="زقماط">زقماط</option>
                                        <option value="حندش والحمادين">حندش والحمادين</option>
                                        <option value="ابوحمدان">ابوحمدان</option>
                                        <option value="مقلد">مقلد</option>
                                        <option value="الدجاجنة">الدجاجنة</option>
                                        <option value="قريده">قريده</option>
                                        <option value="الصوفي">الصوفي</option>
                                        <option value="مصبح">مصبح</option>
                                        <option value="قرقوش">قرقوش</option>
                                        <option value="العوايضة">العوايضة</option>
                                        <option value="السوالمة">السوالمة</option>
                                        <option value="عرادة">عرادة</option>
                                        <option value="البراهمة">البراهمة</option>
                                        <option value="العيسة">العيسة</option>
                                        <option value="المحامدة">المحامدة</option>
                                        <option value="ابوسري">ابوسري</option>
                                        <option value="المهاوشة">المهاوشة</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الصلاحيات</label>
                            <div class="row">
                                <?php foreach ($permission_labels as $key => $label): ?>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $key; ?>" id="perm_<?php echo $key; ?>">
                                        <label class="form-check-label" for="perm_<?php echo $key; ?>">
                                            <?php echo $label; ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="add_admin" class="btn btn-primary">إضافة المشرف</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal تعديل مشرف -->
    <div class="modal fade" id="editAdminModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تعديل المشرف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editAdminForm">
                    <input type="hidden" name="admin_id" id="edit_admin_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_name" class="form-label">الاسم الكامل</label>
                                    <input type="text" class="form-control" id="edit_name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_email" class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" id="edit_email" name="email" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_role" class="form-label">الدور</label>
                                    <select class="form-select" id="edit_role" name="role" onchange="toggleEditFamilyBranch()">
                                        <option value="moderator">مشرف</option>
                                        <option value="admin">مدير</option>
                                        <option value="family_admin">مشرف فرع عائلي</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3" id="edit_family_branch_div" style="display: none;">
                                    <label for="edit_family_branch" class="form-label">الفرع العائلي</label>
                                    <select class="form-select" id="edit_family_branch" name="family_branch">
                                        <option value="">اختر الفرع العائلي</option>
                                        <option value="الجواهرة">الجواهرة</option>
                                        <option value="العواودة">العواودة</option>
                                        <option value="البشيتي">البشيتي</option>
                                        <option value="زقماط">زقماط</option>
                                        <option value="حندش والحمادين">حندش والحمادين</option>
                                        <option value="ابوحمدان">ابوحمدان</option>
                                        <option value="مقلد">مقلد</option>
                                        <option value="الدجاجنة">الدجاجنة</option>
                                        <option value="قريده">قريده</option>
                                        <option value="الصوفي">الصوفي</option>
                                        <option value="مصبح">مصبح</option>
                                        <option value="قرقوش">قرقوش</option>
                                        <option value="العوايضة">العوايضة</option>
                                        <option value="السوالمة">السوالمة</option>
                                        <option value="عرادة">عرادة</option>
                                        <option value="البراهمة">البراهمة</option>
                                        <option value="العيسة">العيسة</option>
                                        <option value="المحامدة">المحامدة</option>
                                        <option value="ابوسري">ابوسري</option>
                                        <option value="المهاوشة">المهاوشة</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                                    <label class="form-check-label" for="edit_is_active">
                                        نشط
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الصلاحيات</label>
                            <div class="row" id="edit_permissions">
                                <!-- سيتم ملؤها بواسطة JavaScript -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="update_admin" class="btn btn-primary">حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-sidebar.js"></script>
    <script>

        function editAdmin(adminId) {
            // جلب بيانات المشرف
            fetch(`get-admin-details.php?id=${adminId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_admin_id').value = data.id;
                    document.getElementById('edit_name').value = data.name;
                    document.getElementById('edit_email').value = data.email;
                    document.getElementById('edit_role').value = data.role;
                    document.getElementById('edit_is_active').checked = data.is_active == 1;
                    
                    // ملء الصلاحيات
                    const permissionsContainer = document.getElementById('edit_permissions');
                    permissionsContainer.innerHTML = '';
                    
                    const permissions = <?php echo json_encode($permission_labels); ?>;
                    Object.keys(permissions).forEach(key => {
                        const div = document.createElement('div');
                        div.className = 'col-md-4';
                        div.innerHTML = `
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="${key}" id="edit_perm_${key}">
                                <label class="form-check-label" for="edit_perm_${key}">
                                    ${permissions[key]}
                                </label>
                            </div>
                        `;
                        permissionsContainer.appendChild(div);
                    });
                    
                    // تحديد الصلاحيات المختارة
                    const adminPermissions = JSON.parse(data.permissions || '[]');
                    adminPermissions.forEach(perm => {
                        const checkbox = document.getElementById(`edit_perm_${perm}`);
                        if (checkbox) checkbox.checked = true;
                    });
                    
                    new bootstrap.Modal(document.getElementById('editAdminModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('خطأ في جلب بيانات المشرف');
                });
        }

        function toggleStatus(adminId) {
            if (confirm('هل تريد تغيير حالة هذا المشرف؟')) {
                window.location.href = `admin-admins.php?action=toggle_status&id=${adminId}`;
            }
        }

        function deleteAdmin(adminId) {
            if (confirm('هل أنت متأكد من حذف هذا المشرف؟ هذا الإجراء لا يمكن التراجع عنه!')) {
                window.location.href = `admin-admins.php?action=delete&id=${adminId}`;
            }
        }

        // إظهار/إخفاء حقل الفرع العائلي عند إضافة مشرف
        function toggleFamilyBranch() {
            const role = document.getElementById('role').value;
            const familyBranchDiv = document.getElementById('family_branch_div');
            const familyBranchSelect = document.getElementById('family_branch');
            
            if (role === 'family_admin') {
                familyBranchDiv.style.display = 'block';
                familyBranchSelect.required = true;
            } else {
                familyBranchDiv.style.display = 'none';
                familyBranchSelect.required = false;
                familyBranchSelect.value = '';
            }
        }

        // إظهار/إخفاء حقل الفرع العائلي عند تعديل مشرف
        function toggleEditFamilyBranch() {
            const role = document.getElementById('edit_role').value;
            const familyBranchDiv = document.getElementById('edit_family_branch_div');
            const familyBranchSelect = document.getElementById('edit_family_branch');
            
            if (role === 'family_admin') {
                familyBranchDiv.style.display = 'block';
                familyBranchSelect.required = true;
            } else {
                familyBranchDiv.style.display = 'none';
                familyBranchSelect.required = false;
                familyBranchSelect.value = '';
            }
        }
    </script>
    <script src="assets/js/admin-sidebar.js"></script>
</body>
</html>
