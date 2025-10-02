<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../auth/admin-login.php');
    exit;
}

require_once 'includes/db_connection.php';

// إنشاء جدول سجل التحديثات إذا لم يكن موجوداً
$create_table_sql = "
CREATE TABLE IF NOT EXISTS family_update_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    family_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    field_name VARCHAR(100),
    old_value TEXT,
    new_value TEXT,
    device_info JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_family_id (family_id),
    INDEX idx_action (action),
    INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$pdo->exec($create_table_sql);

// معالجة البحث والفلترة
$search = isset($_GET['search']) ? $_GET['search'] : '';
$action_filter = isset($_GET['action']) ? $_GET['action'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// بناء شروط البحث
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(f.first_name LIKE ? OR f.family_name LIKE ? OR f.id_number LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if (!empty($action_filter)) {
    $where_conditions[] = "ul.action = ?";
    $params[] = $action_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(ul.updated_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(ul.updated_at) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// جلب سجل التحديثات
$sql = "SELECT ul.*, f.first_name, f.family_name, f.id_number
        FROM family_update_logs ul
        JOIN families f ON ul.family_id = f.id
        $where_clause
        ORDER BY ul.updated_at DESC
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب العدد الإجمالي
$count_sql = "SELECT COUNT(*) FROM family_update_logs ul JOIN families f ON ul.family_id = f.id $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_logs = $count_stmt->fetchColumn();
$total_pages = ceil($total_logs / $limit);

// جلب إحصائيات
$stats_sql = "SELECT 
    COUNT(*) as total_updates,
    COUNT(DISTINCT family_id) as families_updated,
    COUNT(CASE WHEN action = 'login' THEN 1 END) as logins,
    COUNT(CASE WHEN action = 'update' THEN 1 END) as updates,
    COUNT(CASE WHEN action = 'password_change' THEN 1 END) as password_changes
    FROM family_update_logs ul
    JOIN families f ON ul.family_id = f.id
    $where_clause";

$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute($params);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// جلب أحدث التحديثات
$recent_sql = "SELECT ul.*, f.first_name, f.family_name
               FROM family_update_logs ul
               JOIN families f ON ul.family_id = f.id
               ORDER BY ul.updated_at DESC
               LIMIT 10";
$recent_stmt = $pdo->query($recent_sql);
$recent_logs = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial.0">
    <title>سجل التحديثات - الشاعر عائلتي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin-sidebar.css" rel="stylesheet">
    <style>
        .log-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .log-header {
            display: flex;
            justify-content-between;
            align-items-center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        .action-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .action-login { background-color: #d1ecf1; color: #0c5460; }
        .action-update { background-color: #d4edda; color: #155724; }
        .action-password_change { background-color: #fff3cd; color: #856404; }
        .action-logout { background-color: #f8d7da; color: #721c24; }
        
        .device-info {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
            font-size: 0.875rem;
        }
        .device-info h6 {
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .device-info .row {
            margin-bottom: 0.5rem;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .filter-container {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .value-change {
            background-color: #e7f3ff;
            padding: 0.5rem;
            border-radius: 5px;
            margin: 0.5rem 0;
        }
        .old-value {
            color: #dc3545;
            text-decoration: line-through;
        }
        .new-value {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include '../../includes/admin-sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-history me-2"></i>سجل التحديثات</h1>
                <div>
                    <a href="admin-dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-right me-2"></i>العودة للوحة التحكم
                    </a>
                </div>
            </div>

            <!-- إحصائيات -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['total_updates']; ?></div>
                        <div>إجمالي التحديثات</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['families_updated']; ?></div>
                        <div>عائلات محدثة</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['logins']; ?></div>
                        <div>تسجيلات دخول</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['updates']; ?></div>
                        <div>تحديثات بيانات</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['password_changes']; ?></div>
                        <div>تغييرات كلمة المرور</div>
                    </div>
                </div>
            </div>

            <!-- فلاتر البحث -->
            <div class="filter-container">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="search" 
                               placeholder="البحث بالاسم أو رقم الهوية..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="action">
                            <option value="">جميع الإجراءات</option>
                            <option value="login" <?php echo $action_filter == 'login' ? 'selected' : ''; ?>>تسجيل دخول</option>
                            <option value="update" <?php echo $action_filter == 'update' ? 'selected' : ''; ?>>تحديث بيانات</option>
                            <option value="password_change" <?php echo $action_filter == 'password_change' ? 'selected' : ''; ?>>تغيير كلمة المرور</option>
                            <option value="logout" <?php echo $action_filter == 'logout' ? 'selected' : ''; ?>>تسجيل خروج</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_from" 
                               value="<?php echo $date_from; ?>" placeholder="من تاريخ">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_to" 
                               value="<?php echo $date_to; ?>" placeholder="إلى تاريخ">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-light me-2">
                            <i class="fas fa-search me-1"></i>بحث
                        </button>
                        <a href="admin-update-logs.php" class="btn btn-outline-light">
                            <i class="fas fa-times me-1"></i>مسح
                        </a>
                    </div>
                </form>
            </div>

            <!-- سجل التحديثات -->
            <div class="row">
                <div class="col-12">
                    <?php if (empty($logs)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">لا توجد تحديثات</h4>
                        <p class="text-muted">لم يتم العثور على أي تحديثات تطابق معايير البحث</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <div class="log-card">
                            <div class="log-header">
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['family_name']); ?></h5>
                                    <small class="text-muted">رقم الهوية: <?php echo htmlspecialchars($log['id_number']); ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="action-badge action-<?php echo $log['action']; ?>">
                                        <?php
                                        $actions = [
                                            'login' => 'تسجيل دخول',
                                            'update' => 'تحديث بيانات',
                                            'password_change' => 'تغيير كلمة المرور',
                                            'logout' => 'تسجيل خروج'
                                        ];
                                        echo $actions[$log['action']] ?? $log['action'];
                                        ?>
                                    </span>
                                    <div class="text-muted small mt-1">
                                        <?php echo date('Y-m-d H:i:s', strtotime($log['updated_at'])); ?>
                                    </div>
                                </div>
                            </div>

                            <?php if ($log['field_name'] && $log['old_value'] && $log['new_value']): ?>
                            <div class="value-change">
                                <strong>تحديث الحقل:</strong> <?php echo htmlspecialchars($log['field_name']); ?><br>
                                <span class="old-value">القديم: <?php echo htmlspecialchars($log['old_value']); ?></span><br>
                                <span class="new-value">الجديد: <?php echo htmlspecialchars($log['new_value']); ?></span>
                            </div>
                            <?php endif; ?>

                            <?php if ($log['device_info']): ?>
                            <div class="device-info">
                                <h6><i class="fas fa-laptop me-1"></i>معلومات الجهاز</h6>
                                <?php 
                                $device_info = json_decode($log['device_info'], true);
                                if ($device_info):
                                ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>نظام التشغيل:</strong> <?php echo htmlspecialchars($device_info['os'] ?? 'غير محدد'); ?><br>
                                        <strong>المتصفح:</strong> <?php echo htmlspecialchars($device_info['browser'] ?? 'غير محدد'); ?><br>
                                        <strong>إصدار المتصفح:</strong> <?php echo htmlspecialchars($device_info['browser_version'] ?? 'غير محدد'); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>نوع الجهاز:</strong> <?php echo htmlspecialchars($device_info['device_type'] ?? 'غير محدد'); ?><br>
                                        <strong>اللغة:</strong> <?php echo htmlspecialchars($device_info['language'] ?? 'غير محدد'); ?><br>
                                        <strong>المنطقة الزمنية:</strong> <?php echo htmlspecialchars($device_info['timezone'] ?? 'غير محدد'); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <strong>عنوان IP:</strong> <?php echo htmlspecialchars($log['ip_address']); ?><br>
                                        <strong>User Agent:</strong> 
                                        <small class="text-muted"><?php echo htmlspecialchars(substr($log['user_agent'], 0, 100)) . (strlen($log['user_agent']) > 100 ? '...' : ''); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($action_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-sidebar.js"></script>
</body>
</html>
