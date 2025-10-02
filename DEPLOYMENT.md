# 🚀 دليل النشر

هذا الدليل يوضح كيفية نشر مشروع "الشاعر عائلتي" على خادم الإنتاج.

## 📋 المتطلبات

### متطلبات الخادم
- **PHP**: 7.4 أو أحدث
- **MySQL**: 5.7 أو أحدث
- **Apache/Nginx**: خادم ويب
- **SSL**: شهادة SSL للاتصال الآمن
- **مساحة التخزين**: 500MB على الأقل

### متطلبات PHP
- `mysqli` أو `pdo_mysql`
- `gd` (للمعالجة)
- `mbstring` (للنصوص العربية)
- `openssl` (للأمان)
- `fileinfo` (لرفع الملفات)

## 🔧 خطوات النشر

### 1. إعداد الخادم

#### Apache
```apache
# .htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# حماية الملفات الحساسة
<Files "*.php">
    Order Allow,Deny
    Allow from all
</Files>

<Files "*.sql">
    Order Deny,Allow
    Deny from all
</Files>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html/family-orphans-system;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 2. رفع الملفات

```bash
# استنساخ المشروع
git clone https://github.com/yourusername/family-orphans-system.git

# أو رفع الملفات عبر FTP/SFTP
# تأكد من رفع جميع الملفات والمجلدات
```

### 3. إعداد قاعدة البيانات

```sql
-- إنشاء قاعدة البيانات
CREATE DATABASE family_orphans_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- إنشاء مستخدم
CREATE USER 'family_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON family_orphans_system.* TO 'family_user'@'localhost';
FLUSH PRIVILEGES;
```

### 4. تكوين الاتصال

تحديث إعدادات قاعدة البيانات في الملفات:
- `includes/db_connection.php`
- `index.php`
- جميع ملفات الإدارة

```php
// مثال على التكوين
$host = 'localhost';
$dbname = 'family_orphans_system';
$username = 'family_user';
$password = 'secure_password';
```

### 5. إعداد الصلاحيات

```bash
# تعيين صلاحيات الملفات
chmod 755 /var/www/html/family-orphans-system
chmod 644 /var/www/html/family-orphans-system/*.php
chmod 755 /var/www/html/family-orphans-system/uploads/
chmod 644 /var/www/html/family-orphans-system/uploads/*

# تعيين مالك الملفات
chown -R www-data:www-data /var/www/html/family-orphans-system
```

### 6. إعداد SSL

```bash
# استخدام Let's Encrypt
certbot --apache -d yourdomain.com
# أو
certbot --nginx -d yourdomain.com
```

## 🔒 الأمان

### حماية الملفات الحساسة
```apache
# .htaccess
<Files "*.sql">
    Order Deny,Allow
    Deny from all
</Files>

<Files "*.log">
    Order Deny,Allow
    Deny from all
</Files>
```

### حماية المجلدات
```apache
# حماية مجلد includes
<Directory "includes">
    Order Deny,Allow
    Deny from all
</Directory>

# حماية مجلد uploads
<Directory "uploads">
    Options -Indexes
</Directory>
```

### إعدادات PHP
```ini
# php.ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 30
memory_limit = 128M
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
```

## 📊 مراقبة الأداء

### سجلات الأخطاء
```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log

# PHP
tail -f /var/log/php_errors.log
```

### مراقبة قاعدة البيانات
```sql
-- مراقبة الاستعلامات البطيئة
SHOW PROCESSLIST;

-- مراقبة الاتصالات
SHOW STATUS LIKE 'Connections';
```

## 🔄 النسخ الاحتياطية

### نسخ احتياطية تلقائية
```bash
#!/bin/bash
# backup.sh
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u family_user -p family_orphans_system > backup_$DATE.sql
tar -czf files_$DATE.tar.gz /var/www/html/family-orphans-system/uploads/
```

### جدولة النسخ الاحتياطية
```bash
# crontab
0 2 * * * /path/to/backup.sh
```

## 🚨 استكشاف الأخطاء

### مشاكل شائعة

#### خطأ في الاتصال بقاعدة البيانات
```php
// تحقق من الإعدادات
$pdo = new PDO('mysql:host=localhost;dbname=family_orphans_system', $username, $password);
```

#### مشاكل في الصلاحيات
```bash
# تحقق من صلاحيات الملفات
ls -la /var/www/html/family-orphans-system/
```

#### مشاكل في رفع الملفات
```php
// تحقق من إعدادات PHP
echo ini_get('upload_max_filesize');
echo ini_get('post_max_size');
```

## 📞 الدعم

للحصول على الدعم في النشر:
- **البريد الإلكتروني**: haatayani@gmail.com
- **الهاتف**: 00970593804084

---

**ملاحظة**: تأكد من اختبار النظام في بيئة التطوير قبل النشر على الإنتاج.
