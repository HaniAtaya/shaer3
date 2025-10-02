<?php
session_start();

// إذا كان مسجل دخول بالفعل، توجيهه لصفحة التحديث
if (isset($_SESSION['family_logged_in']) && $_SESSION['family_logged_in'] === true) {
    header('Location: family-update.php');
    exit;
}

// الاتصال بقاعدة البيانات
try {
    $pdo = new PDO('mysql:host=localhost;dbname=family_orphans_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage());
}

require_once '../../includes/device-tracker.php';

$error_message = '';
$success_message = '';
$show_security_question = false;

// معالجة تسجيل الدخول
if ($_POST) {
    $id_number = $_POST['id_number'] ?? '';
    $access_code = $_POST['access_code'] ?? '';
    
    if (empty($id_number) || empty($access_code)) {
        $error_message = 'يرجى إدخال رقم الهوية وكلمة المرور';
    } else {
        // البحث عن العائلة برقم الهوية وكلمة المرور
        $stmt = $pdo->prepare("
            SELECT f.*, fac.password_changed, fsq.question, fsq.answer as security_answer
            FROM families f 
            JOIN family_access_codes fac ON f.id = fac.family_id 
            LEFT JOIN family_security_questions fsq ON f.id = fsq.family_id
            WHERE f.id_number = ? AND fac.access_code = ?
        ");
        $stmt->execute([$id_number, $access_code]);
        $family = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($family) {
            // التحقق من وجود سؤال أمان
            if (!empty($family['question']) && !empty($family['security_answer'])) {
                // إذا كان هناك سؤال أمان، نتحقق من الإجابة
                if (!isset($_POST['security_answer'])) {
                    // عرض سؤال الأمان
                    $show_security_question = true;
                } else {
                    $security_answer = $_POST['security_answer'];
                    if (strtolower(trim($security_answer)) === strtolower(trim($family['security_answer']))) {
                        // الإجابة صحيحة، تسجيل الدخول
                        $_SESSION['family_logged_in'] = true;
                        $_SESSION['family_id'] = $family['id'];
                        $_SESSION['family_name'] = $family['first_name'] . ' ' . $family['family_name'];
                        $_SESSION['password_changed'] = $family['password_changed'];
                        
                        // تسجيل عملية تسجيل الدخول
                        logLogin($pdo, $family['id']);
                        
                        // إذا لم يغير كلمة المرور، توجيهه لتغييرها
                        if (!$family['password_changed']) {
                            header('Location: ../../utilities/change-password.php');
                            exit;
                        } else {
                            header('Location: family-update.php');
                            exit;
                        }
                    } else {
                        $error_message = 'إجابة سؤال الأمان غير صحيحة';
                    }
                }
            } else {
                // لا يوجد سؤال أمان، تسجيل الدخول مباشرة
                $_SESSION['family_logged_in'] = true;
                $_SESSION['family_id'] = $family['id'];
                $_SESSION['family_name'] = $family['first_name'] . ' ' . $family['family_name'];
                $_SESSION['password_changed'] = $family['password_changed'];
                
                // تسجيل عملية تسجيل الدخول
                logLogin($pdo, $family['id']);
                
                // إذا لم يغير كلمة المرور، توجيهه لتغييرها
                if (!$family['password_changed']) {
                    header('Location: ../../utilities/change-password.php');
                    exit;
                } else {
                    header('Location: family-update.php');
                    exit;
                }
            }
        } else {
            $error_message = 'رقم الهوية أو كلمة المرور غير صحيحة';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول العائلة - الشاعر عائلتي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 15px;
            border: 2px solid #e9ecef;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 15px;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .alert {
            border-radius: 15px;
            border: none;
        }
        .info-box {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #667eea;
        }
        .back-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            color: #5a6fd8;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="login-card">
                        <div class="login-header">
                            <h2 class="mb-0">
                                <i class="fas fa-home me-2"></i>
                                تسجيل دخول العائلة
                            </h2>
                            <p class="mb-0 mt-2">الشاعر عائلتي</p>
                        </div>
                        <div class="login-body">
                            <div class="info-box">
                                <h6 class="mb-2">
                                    <i class="fas fa-info-circle me-2"></i>
                                    معلومات مهمة
                                </h6>
                                <p class="mb-0 small">
                                    استخدم كلمة المرور المكونة من 8 أرقام التي تم إعطاؤها لك عند التسجيل. 
                                    يمكنك العثور عليها في رسالة التأكيد أو استخراجها من رقم الهوية أو تاريخ الميلاد.
                                </p>
                            </div>

                            <?php if ($error_message): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error_message; ?>
                            </div>
                            <?php endif; ?>

                            <?php if ($success_message): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success_message; ?>
                            </div>
                            <?php endif; ?>

                            <form method="POST" id="loginForm">
                                <div class="mb-4">
                                    <label for="id_number" class="form-label fw-bold">
                                        <i class="fas fa-id-card me-2"></i>
                                        رقم الهوية
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="id_number" 
                                           name="id_number" 
                                           placeholder="أدخل رقم الهوية"
                                           required>
                                </div>

                                <div class="mb-4">
                                    <label for="access_code" class="form-label fw-bold">
                                        <i class="fas fa-key me-2"></i>
                                        كلمة المرور
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="access_code" 
                                           name="access_code" 
                                           placeholder="أدخل كلمة المرور (6-20 خانة)"
                                           maxlength="20"
                                           required>
                                    <div class="form-text">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        كلمة المرور من 6 إلى 20 خانة (أحرف، أرقام، رموز)
                                    </div>
                                </div>

                                <?php if ($show_security_question && isset($family)): ?>
                                <div class="mb-4">
                                    <label for="security_answer" class="form-label fw-bold">
                                        <i class="fas fa-shield-alt me-2"></i>
                                        سؤال الأمان
                                    </label>
                                    <div class="alert alert-info">
                                        <strong>السؤال:</strong> <?php echo htmlspecialchars($family['question']); ?>
                                    </div>
                                    <input type="text" 
                                           class="form-control" 
                                           id="security_answer" 
                                           name="security_answer" 
                                           placeholder="أدخل إجابة سؤال الأمان"
                                           required>
                                </div>
                                <?php endif; ?>

                                <button type="submit" class="btn btn-login">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    تسجيل الدخول
                                </button>
                            </form>

                            <div class="text-center mt-4">
                                <a href="../../index.php" class="back-link">
                                    <i class="fas fa-arrow-right me-2"></i>
                                    العودة للصفحة الرئيسية
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // السماح بإدخال الحروف والأرقام والرموز فقط
        document.getElementById('access_code').addEventListener('input', function(e) {
            // السماح بالحروف الإنجليزية والأرقام والرموز المحددة
            this.value = this.value.replace(/[^A-Za-z0-9!@#$%&*]/g, '');
            
            // التحقق من الطول
            if (this.value.length < 6) {
                this.setCustomValidity('كلمة المرور يجب أن تكون 6 خانات على الأقل');
            } else if (this.value.length > 20) {
                this.value = this.value.substring(0, 20);
            } else {
                this.setCustomValidity('');
            }
        });

        // التركيز على حقل كلمة المرور
        document.getElementById('access_code').focus();
        
        // جمع معلومات الجهاز وإرسالها مع النموذج
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            
            // إضافة حقل مخفي لمعلومات الجهاز
            const deviceInfoInput = document.createElement('input');
            deviceInfoInput.type = 'hidden';
            deviceInfoInput.name = 'device_info';
            
            // جمع معلومات الجهاز
            const deviceInfo = {
                screen_resolution: screen.width + 'x' + screen.height,
                color_depth: screen.colorDepth,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                language: navigator.language,
                platform: navigator.platform,
                cookie_enabled: navigator.cookieEnabled,
                online_status: navigator.onLine
            };
            
            deviceInfoInput.value = JSON.stringify(deviceInfo);
            form.appendChild(deviceInfoInput);
        });
    </script>
</body>
</html>

