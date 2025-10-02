<?php
// إعدادات قاعدة البيانات
$host = 'localhost';
$dbname = 'family_orphans_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // إنشاء جدول الرضع
    $createTable = "
    CREATE TABLE IF NOT EXISTS infants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(255) NOT NULL,
        father_name VARCHAR(255) NOT NULL,
        grandfather_name VARCHAR(255) NOT NULL,
        family_name VARCHAR(255) NOT NULL,
        id_number VARCHAR(20) UNIQUE NOT NULL,
        birth_date DATE NOT NULL,
        family_branch VARCHAR(255) NOT NULL,
        primary_phone VARCHAR(20) NOT NULL,
        secondary_phone VARCHAR(20),
        original_governorate ENUM('gaza', 'khan_younis', 'rafah', 'middle', 'north_gaza') NOT NULL,
        original_area VARCHAR(255) NOT NULL,
        original_neighborhood VARCHAR(255) NOT NULL,
        displacement_governorate ENUM('gaza', 'khan_younis', 'rafah', 'middle', 'north_gaza') NOT NULL,
        displacement_area VARCHAR(255) NOT NULL,
        displacement_neighborhood VARCHAR(255) NOT NULL,
        housing_status ENUM('tent', 'apartment', 'house', 'school') NOT NULL,
        birth_certificate_image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($createTable);
    echo "تم إنشاء جدول الرضع بنجاح!";
    
} catch(PDOException $e) {
    die("خطأ في إنشاء الجدول: " . $e->getMessage());
}
?>

