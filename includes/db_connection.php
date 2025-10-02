<?php
/**
 * Database Connection - اتصال قاعدة البيانات
 * ملف موحد لاتصال قاعدة البيانات في جميع أنحاء النظام
 */

// إعدادات قاعدة البيانات
$host = 'localhost';
$dbname = 'family_orphans_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// دالة للحصول على اتصال قاعدة البيانات
function getDBConnection() {
    global $pdo;
    return $pdo;
}

// دالة لتنفيذ استعلام مع معالجة الأخطاء
function executeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        throw new Exception("خطأ في قاعدة البيانات: " . $e->getMessage());
    }
}

// دالة للحصول على صف واحد
function fetchOne($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->fetch();
}

// دالة للحصول على جميع الصفوف
function fetchAll($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->fetchAll();
}

// دالة للحصول على عدد الصفوف
function fetchCount($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->fetchColumn();
}

// دالة لتنفيذ معاملة (Transaction)
function executeTransaction($pdo, $queries) {
    try {
        $pdo->beginTransaction();
        
        foreach ($queries as $query) {
            $sql = $query['sql'];
            $params = $query['params'] ?? [];
            executeQuery($pdo, $sql, $params);
        }
        
        $pdo->commit();
        return true;
    } catch(Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// دالة للتحقق من وجود جدول
function tableExists($pdo, $tableName) {
    $sql = "SHOW TABLES LIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tableName]);
    return $stmt->rowCount() > 0;
}

// دالة للحصول على معلومات قاعدة البيانات
function getDatabaseInfo($pdo) {
    try {
        $info = [];
        
        // معلومات الخادم
        $stmt = $pdo->query("SELECT VERSION() as version");
        $info['mysql_version'] = $stmt->fetchColumn();
        
        // حجم قاعدة البيانات
        $stmt = $pdo->query("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size in MB'
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        $info['database_size'] = $stmt->fetchColumn();
        
        // عدد الجداول
        $stmt = $pdo->query("
            SELECT COUNT(*) as table_count 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        $info['table_count'] = $stmt->fetchColumn();
        
        return $info;
    } catch(Exception $e) {
        return ['error' => $e->getMessage()];
    }
}
?>
