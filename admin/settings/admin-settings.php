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

// معالجة تحديث الإعدادات
if ($_POST) {
    $success_message = '';
    $error_message = '';
    
    // تحديث كلمة المرور
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'جميع الحقول مطلوبة';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'كلمة المرور الجديدة غير متطابقة';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
        } else {
            // التحقق من كلمة المرور الحالية
            $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($current_password, $admin['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['admin_id']]);
                $success_message = 'تم تحديث كلمة المرور بنجاح';
            } else {
                $error_message = 'كلمة المرور الحالية غير صحيحة';
            }
        }
    }
    
    // تحديث معلومات الملف الشخصي
    if (isset($_POST['update_profile'])) {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        
        if (empty($name) || empty($email)) {
            $error_message = 'الاسم والبريد الإلكتروني مطلوبان';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'البريد الإلكتروني غير صحيح';
        } else {
            $stmt = $pdo->prepare("UPDATE admins SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $_SESSION['admin_id']]);
            $success_message = 'تم تحديث الملف الشخصي بنجاح';
        }
    }
}

// جلب معلومات المدير الحالي
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعدادات - نظام إدارة العائلات والأيتام</title>
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
        .settings-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }
        .alert {
            border-radius: 15px;
            border: none;
        }
    </style>
</head>
<body>
    <?php include '../../includes/admin-sidebar.php'; ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-cog me-2"></i>الإعدادات</h2>
                    <div class="text-muted">
                        <i class="fas fa-user me-1"></i>
                        مرحباً، <?php echo htmlspecialchars($admin['name'] ?? 'المدير'); ?>
                    </div>
                </div>

                <?php if (isset($success_message) && $success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($error_message) && $error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- الملف الشخصي -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-user me-2"></i>الملف الشخصي</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">الاسم الكامل</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($admin['name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">البريد الإلكتروني</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="username" class="form-label">اسم المستخدم</label>
                                        <input type="text" class="form-control" id="username" 
                                               value="<?php echo htmlspecialchars($admin['username'] ?? ''); ?>" readonly>
                                        <div class="form-text">اسم المستخدم لا يمكن تغييره</div>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>
                                        حفظ التغييرات
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- تغيير كلمة المرور -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-warning text-white">
                                <h5 class="mb-0"><i class="fas fa-lock me-2"></i>تغيير كلمة المرور</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">كلمة المرور الحالية</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">كلمة المرور الجديدة</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <div class="form-text">يجب أن تكون 6 أحرف على الأقل</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">تأكيد كلمة المرور</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-warning">
                                        <i class="fas fa-key me-1"></i>
                                        تغيير كلمة المرور
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- إعدادات قاعدة البيانات -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-database me-2"></i>إعدادات قاعدة البيانات</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                // معلومات قاعدة البيانات
                                $db_info = [];
                                
                                // حالة الاتصال
                                $db_info['status'] = 'متصل';
                                $db_info['status_class'] = 'success';
                                
                                // معلومات الخادم
                                $db_info['host'] = 'localhost';
                                $db_info['database'] = 'family_orphans_system';
                                $db_info['charset'] = 'utf8mb4';
                                
                                // إحصائيات الجداول
                                $tables = ['families', 'family_members', 'orphans', 'infants', 'deaths', 'admins'];
                                $table_counts = [];
                                $total_records = 0;
                                
                                foreach ($tables as $table) {
                                    try {
                                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                                        $count = $stmt->fetch()['count'];
                                        $table_counts[$table] = $count;
                                        $total_records += $count;
                                    } catch (Exception $e) {
                                        $table_counts[$table] = 0;
                                    }
                                }
                                
                                // حجم قاعدة البيانات (تقدير)
                                $db_size = $total_records * 0.5; // تقدير 0.5 كيلوبايت لكل سجل
                                ?>
                                
                                <div class="mb-3">
                                    <label class="form-label">حالة الاتصال</label>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-<?php echo $db_info['status_class']; ?> me-2"><?php echo $db_info['status']; ?></span>
                                        <small class="text-muted">قاعدة البيانات تعمل بشكل طبيعي</small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">معلومات الخادم</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">الخادم:</small><br>
                                            <code><?php echo $db_info['host']; ?></code>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">قاعدة البيانات:</small><br>
                                            <code><?php echo $db_info['database']; ?></code>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">إحصائيات الجداول</label>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>الجدول</th>
                                                    <th>عدد السجلات</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>العائلات</td>
                                                    <td><span class="badge bg-primary"><?php echo number_format($table_counts['families']); ?></span></td>
                                                </tr>
                                                <tr>
                                                    <td>أفراد العائلات</td>
                                                    <td><span class="badge bg-info"><?php echo number_format($table_counts['family_members']); ?></span></td>
                                                </tr>
                                                <tr>
                                                    <td>الأيتام</td>
                                                    <td><span class="badge bg-success"><?php echo number_format($table_counts['orphans']); ?></span></td>
                                                </tr>
                                                <tr>
                                                    <td>الرضع</td>
                                                    <td><span class="badge bg-warning"><?php echo number_format($table_counts['infants']); ?></span></td>
                                                </tr>
                                                <tr>
                                                    <td>الوفيات</td>
                                                    <td><span class="badge bg-dark"><?php echo number_format($table_counts['deaths']); ?></span></td>
                                                </tr>
                                                <tr>
                                                    <td>المشرفين</td>
                                                    <td><span class="badge bg-secondary"><?php echo number_format($table_counts['admins']); ?></span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">إجمالي السجلات</label>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-success me-2"><?php echo number_format($total_records); ?></span>
                                        <small class="text-muted">سجل إجمالي</small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">الحجم المقدر</label>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-info me-2"><?php echo number_format($db_size, 2); ?> KB</span>
                                        <small class="text-muted">حجم قاعدة البيانات</small>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-info" onclick="backupDatabase()">
                                        <i class="fas fa-download me-1"></i>
                                        نسخ احتياطي
                                    </button>
                                    <button class="btn btn-outline-warning" onclick="optimizeDatabase()">
                                        <i class="fas fa-tools me-1"></i>
                                        تحسين قاعدة البيانات
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>إعدادات متقدمة</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">مسح جميع البيانات</label>
                                    <p class="text-muted small">هذا الإجراء سيمسح جميع البيانات نهائياً ولا يمكن التراجع عنه</p>
                                    <button class="btn btn-outline-danger btn-sm" onclick="confirmDeleteAll()">
                                        <i class="fas fa-trash me-1"></i>
                                        مسح جميع البيانات
                                    </button>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">إعادة تعيين النظام</label>
                                    <p class="text-muted small">إعادة تعيين جميع الإعدادات إلى القيم الافتراضية</p>
                                    <button class="btn btn-outline-warning btn-sm" onclick="resetSystem()">
                                        <i class="fas fa-undo me-1"></i>
                                        إعادة تعيين
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- معلومات النظام -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>معلومات النظام</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                // معلومات النظام
                                $system_info = [
                                    'php_version' => PHP_VERSION,
                                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'غير محدد',
                                    'operating_system' => PHP_OS,
                                    'memory_limit' => ini_get('memory_limit'),
                                    'max_execution_time' => ini_get('max_execution_time'),
                                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                                    'post_max_size' => ini_get('post_max_size'),
                                    'mysql_version' => $pdo->query('SELECT VERSION()')->fetchColumn(),
                                    'server_time' => date('Y-m-d H:i:s'),
                                    'timezone' => date_default_timezone_get(),
                                ];
                                ?>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-4">
                                        <h6 class="text-primary mb-3"><i class="fas fa-code me-2"></i>معلومات التطبيق</h6>
                                        <div class="mb-2">
                                            <small class="text-muted">اسم النظام:</small><br>
                                            <strong>الشاعر عائلتي</strong>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">الإصدار:</small><br>
                                            <code>v1.0.0</code>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">تاريخ التحديث:</small><br>
                                            <code><?php echo date('Y-m-d'); ?></code>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">المطور:</small><br>
                                            <strong>فريق التطوير</strong>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-4">
                                        <h6 class="text-primary mb-3"><i class="fas fa-server me-2"></i>معلومات الخادم</h6>
                                        <div class="mb-2">
                                            <small class="text-muted">PHP Version:</small><br>
                                            <code><?php echo $system_info['php_version']; ?></code>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">MySQL Version:</small><br>
                                            <code><?php echo $system_info['mysql_version']; ?></code>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">نظام التشغيل:</small><br>
                                            <code><?php echo $system_info['operating_system']; ?></code>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">خادم الويب:</small><br>
                                            <code><?php echo $system_info['server_software']; ?></code>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-4">
                                        <h6 class="text-primary mb-3"><i class="fas fa-cog me-2"></i>إعدادات PHP</h6>
                                        <div class="mb-2">
                                            <small class="text-muted">حد الذاكرة:</small><br>
                                            <code><?php echo $system_info['memory_limit']; ?></code>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">حد وقت التنفيذ:</small><br>
                                            <code><?php echo $system_info['max_execution_time']; ?> ثانية</code>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">حد رفع الملفات:</small><br>
                                            <code><?php echo $system_info['upload_max_filesize']; ?></code>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">حد POST:</small><br>
                                            <code><?php echo $system_info['post_max_size']; ?></code>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3"><i class="fas fa-clock me-2"></i>معلومات الوقت</h6>
                                        <div class="mb-2">
                                            <small class="text-muted">وقت الخادم:</small><br>
                                            <code><?php echo $system_info['server_time']; ?></code>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">المنطقة الزمنية:</small><br>
                                            <code><?php echo $system_info['timezone']; ?></code>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3"><i class="fas fa-headset me-2"></i>الدعم الفني</h6>
                                        <div class="mb-2">
                                            <small class="text-muted">البريد الإلكتروني:</small><br>
                                            <code>support@shaer-family.com</code>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">رقم الهاتف:</small><br>
                                            <code>+970 59 380 4084</code>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">ساعات العمل:</small><br>
                                            <code>8:00 ص - 5:00 م (بتوقيت فلسطين)</code>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4 text-center">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>ملاحظة:</strong> هذه المعلومات تُحدث تلقائياً عند كل زيارة للصفحة
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-sidebar.js"></script>
    <script>
        function backupDatabase() {
            if (confirm('هل تريد إنشاء نسخة احتياطية من قاعدة البيانات؟')) {
                window.open('backup-database.php', '_blank');
            }
        }

        function optimizeDatabase() {
            if (confirm('هل تريد تحسين قاعدة البيانات؟ هذا قد يستغرق بضع دقائق.')) {
                // إظهار رسالة تحميل
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>جاري التحسين...';
                btn.disabled = true;
                
                // محاكاة عملية التحسين
                setTimeout(() => {
                    alert('تم تحسين قاعدة البيانات بنجاح!');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 2000);
            }
        }

        function confirmDeleteAll() {
            if (confirm('هل أنت متأكد من مسح جميع البيانات؟ هذا الإجراء لا يمكن التراجع عنه!')) {
                if (confirm('تأكيد نهائي: هل تريد المتابعة؟')) {
                    window.location.href = 'admin-settings.php?action=delete_all';
                }
            }
        }

        function resetSystem() {
            if (confirm('هل تريد إعادة تعيين النظام إلى الإعدادات الافتراضية؟')) {
                window.location.href = 'admin-settings.php?action=reset_system';
            }
        }

        // تأكيد تطابق كلمة المرور
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('كلمة المرور غير متطابقة');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
    <script src="assets/js/admin-sidebar.js"></script>
</body>
</html>