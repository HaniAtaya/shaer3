<?php
/**
 * دالة توليد كلمة مرور تلقائية للعائلات
 * تستخرج 8 أرقام من رقم الهوية أو تاريخ الميلاد
 */

function generateAccessCode($national_id = '', $birth_date = '') {
    // حروف عشوائية
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $symbols = '!@#$%&*';
    $numbers = '0123456789';
    
    // توليد كلمة مرور من 8 خانات: حرف + رمز + 6 أرقام
    $password = '';
    
    // حرف عشوائي
    $password .= $letters[rand(0, strlen($letters) - 1)];
    
    // رمز عشوائي
    $password .= $symbols[rand(0, strlen($symbols) - 1)];
    
    // 6 أرقام عشوائية
    for ($i = 0; $i < 6; $i++) {
        $password .= $numbers[rand(0, strlen($numbers) - 1)];
    }
    
    // خلط الكلمة المرور
    $password_array = str_split($password);
    shuffle($password_array);
    $access_code = implode('', $password_array);
    
    return $access_code;
}

/**
 * دالة التحقق من صحة كلمة المرور
 */
function validateAccessCode($code) {
    // التحقق من أن كلمة المرور بين 6 و 20 خانة
    if (strlen($code) < 6 || strlen($code) > 20) {
        return false;
    }
    
    // التحقق من أن كلمة المرور تحتوي على أحرف أو أرقام أو رموز مسموحة
    return preg_match('/^[A-Za-z0-9!@#$%&*]+$/', $code);
}

/**
 * دالة حفظ كلمة المرور في قاعدة البيانات
 */
function saveAccessCode($pdo, $family_id, $access_code) {
    try {
        // التحقق من وجود كلمة مرور سابقة للعائلة
        $stmt = $pdo->prepare("SELECT id FROM family_access_codes WHERE family_id = ?");
        $stmt->execute([$family_id]);
        
        if ($stmt->fetch()) {
            // تحديث كلمة المرور الموجودة
            $stmt = $pdo->prepare("UPDATE family_access_codes SET access_code = ? WHERE family_id = ?");
            $stmt->execute([$access_code, $family_id]);
        } else {
            // التحقق من عدم وجود كلمة المرور نفسها لعائلة أخرى
            $stmt = $pdo->prepare("SELECT id FROM family_access_codes WHERE access_code = ?");
            $stmt->execute([$access_code]);
            
            if ($stmt->fetch()) {
                // كلمة المرور موجودة، نحتاج لتوليد كلمة جديدة
                $access_code = generateUniqueAccessCode($pdo, $access_code);
            }
            
            // إدراج كلمة مرور جديدة
            $stmt = $pdo->prepare("INSERT INTO family_access_codes (family_id, access_code) VALUES (?, ?)");
            $stmt->execute([$family_id, $access_code]);
        }
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * دالة توليد كلمة مرور فريدة
 */
function generateUniqueAccessCode($pdo, $original_code) {
    $attempts = 0;
    $max_attempts = 100;
    
    while ($attempts < $max_attempts) {
        // توليد كلمة مرور جديدة
        $new_code = generateAccessCode();
        
        // التحقق من عدم وجودها
        $stmt = $pdo->prepare("SELECT id FROM family_access_codes WHERE access_code = ?");
        $stmt->execute([$new_code]);
        
        if (!$stmt->fetch()) {
            return $new_code;
        }
        
        $attempts++;
    }
    
    // إذا فشلنا في توليد كلمة فريدة، نستخدم الطابع الزمني مع حروف ورموز
    $timestamp = substr(time(), -6);
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $symbols = '!@#$%&*';
    return $letters[rand(0, strlen($letters) - 1)] . $symbols[rand(0, strlen($symbols) - 1)] . $timestamp;
}

/**
 * دالة إرسال كلمة المرور عبر الرسائل النصية (محاكاة)
 */
function sendAccessCodeSMS($phone, $access_code, $family_name = '') {
    // هنا يمكن إضافة كود إرسال الرسائل النصية الفعلي
    // مثل استخدام API شركة اتصالات
    
    // التحقق من وجود اسم العائلة
    if (empty($family_name)) {
        $family_name = 'عزيزي/عزيزتي';
    }
    
    $message = "مرحباً $family_name، كلمة مرور تحديث بيانات عائلتك هي: $access_code - الشاعر عائلتي";
    
    // محاكاة إرسال الرسالة
    error_log("SMS to $phone: $message");
    
    return true;
}
?>
