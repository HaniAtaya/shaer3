<?php
/**
 * Device Tracker - تتبع معلومات الجهاز
 * يجمع معلومات شاملة عن الجهاز والمتصفح
 */

function getDeviceInfo() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip_address = getClientIP();
    
    // تحليل User Agent
    $browser_info = getBrowserInfo($user_agent);
    $os_info = getOSInfo($user_agent);
    $device_type = getDeviceType($user_agent);
    
    // معلومات إضافية من JavaScript
    $js_device_info = [];
    if (isset($_POST['device_info'])) {
        $js_device_info = json_decode($_POST['device_info'], true) ?? [];
    }
    
    return [
        'ip_address' => $ip_address,
        'user_agent' => $user_agent,
        'browser' => $browser_info['name'],
        'browser_version' => $browser_info['version'],
        'os' => $os_info['name'],
        'os_version' => $os_info['version'],
        'device_type' => $device_type,
        'language' => $js_device_info['language'] ?? ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'غير محدد'),
        'timezone' => $js_device_info['timezone'] ?? date_default_timezone_get(),
        'screen_resolution' => $js_device_info['screen_resolution'] ?? 'غير محدد',
        'color_depth' => $js_device_info['color_depth'] ?? 'غير محدد',
        'platform' => $js_device_info['platform'] ?? 'غير محدد',
        'cookie_enabled' => $js_device_info['cookie_enabled'] ?? 'غير محدد',
        'online_status' => $js_device_info['online_status'] ?? 'غير محدد',
        'referrer' => $_SERVER['HTTP_REFERER'] ?? 'مباشر',
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

function getClientIP() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'غير محدد';
}

function getBrowserInfo($user_agent) {
    $browsers = [
        'Chrome' => '/Chrome\/([0-9\.]+)/',
        'Firefox' => '/Firefox\/([0-9\.]+)/',
        'Safari' => '/Version\/([0-9\.]+).*Safari/',
        'Edge' => '/Edge\/([0-9\.]+)/',
        'Opera' => '/Opera\/([0-9\.]+)/',
        'Internet Explorer' => '/MSIE ([0-9\.]+)/'
    ];
    
    foreach ($browsers as $name => $pattern) {
        if (preg_match($pattern, $user_agent, $matches)) {
            return [
                'name' => $name,
                'version' => $matches[1] ?? 'غير محدد'
            ];
        }
    }
    
    return ['name' => 'غير محدد', 'version' => 'غير محدد'];
}

function getOSInfo($user_agent) {
    $os_patterns = [
        'Windows 10' => '/Windows NT 10\.0/',
        'Windows 8.1' => '/Windows NT 6\.3/',
        'Windows 8' => '/Windows NT 6\.2/',
        'Windows 7' => '/Windows NT 6\.1/',
        'Windows Vista' => '/Windows NT 6\.0/',
        'Windows XP' => '/Windows NT 5\.1/',
        'macOS' => '/Mac OS X/',
        'iOS' => '/iPhone|iPad|iPod/',
        'Android' => '/Android/',
        'Linux' => '/Linux/',
        'Ubuntu' => '/Ubuntu/'
    ];
    
    foreach ($os_patterns as $name => $pattern) {
        if (preg_match($pattern, $user_agent)) {
            return [
                'name' => $name,
                'version' => 'غير محدد'
            ];
        }
    }
    
    return ['name' => 'غير محدد', 'version' => 'غير محدد'];
}

function getDeviceType($user_agent) {
    if (preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $user_agent)) {
        return 'هاتف محمول';
    } elseif (preg_match('/Tablet|iPad/i', $user_agent)) {
        return 'تابلت';
    } else {
        return 'كمبيوتر مكتبي';
    }
}

function logFamilyAction($pdo, $family_id, $action, $field_name = null, $old_value = null, $new_value = null) {
    try {
        $device_info = getDeviceInfo();
        
        $sql = "INSERT INTO family_update_logs (family_id, action, field_name, old_value, new_value, device_info, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $family_id,
            $action,
            $field_name,
            $old_value,
            $new_value,
            json_encode($device_info, JSON_UNESCAPED_UNICODE),
            $device_info['ip_address'],
            $device_info['user_agent']
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error logging family action: " . $e->getMessage());
        return false;
    }
}

function logLogin($pdo, $family_id) {
    return logFamilyAction($pdo, $family_id, 'login');
}

function logLogout($pdo, $family_id) {
    return logFamilyAction($pdo, $family_id, 'logout');
}

function logPasswordChange($pdo, $family_id, $old_password = null, $new_password = null) {
    return logFamilyAction($pdo, $family_id, 'password_change', 'access_code', $old_password, $new_password);
}

function logDataUpdate($pdo, $family_id, $field_name, $old_value, $new_value) {
    return logFamilyAction($pdo, $family_id, 'update', $field_name, $old_value, $new_value);
}
?>
