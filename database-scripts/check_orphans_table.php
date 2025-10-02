<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=family_orphans_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "أعمدة جدول orphans:\n";
    $stmt = $pdo->query('DESCRIBE orphans');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    echo "\nعينة من البيانات:\n";
    $stmt = $pdo->query('SELECT id, orphan_full_name, birth_date FROM orphans LIMIT 3');
    $orphans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($orphans as $orphan) {
        echo "ID: " . $orphan['id'] . ", Name: " . $orphan['orphan_full_name'] . ", Birth Date: " . ($orphan['birth_date'] ?? 'NULL') . "\n";
    }
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage();
}
?>
