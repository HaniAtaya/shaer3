<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['family_logged_in']) || $_SESSION['family_logged_in'] !== true) {
    header('Location: ../public/family/family-login.php');
    exit;
}

// الاتصال بقاعدة البيانات
try {
    $pdo = new PDO('mysql:host=localhost;dbname=family_orphans_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage());
}

require_once '../includes/device-tracker.php';
require_once '../includes/generate-access-code.php';

$error_message = '';
$success_message = '';

// معالجة تغيير كلمة المرور
if ($_POST) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $error_message = 'جميع الحقول مطلوبة';
    } elseif (strlen($new_password) < 6 || strlen($new_password) > 20 || !validateAccessCode($new_password)) {
        $error_message = 'كلمة المرور يجب أن تكون بين 6 و 20 خانة وتحتوي على أحرف أو أرقام أو رموز مسموحة';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'كلمة المرور الجديدة غير متطابقة';
    } else {
        // التحقق من أن كلمة المرور الجديدة مختلفة عن الحالية
        $stmt = $pdo->prepare("SELECT access_code FROM family_access_codes WHERE family_id = ?");
        $stmt->execute([$_SESSION['family_id']]);
        $current_code = $stmt->fetchColumn();
        
        if ($new_password === $current_code) {
            $error_message = 'كلمة المرور الجديدة يجب أن تكون مختلفة عن الحالية';
        } else {
            // التحقق من عدم وجود كلمة المرور الجديدة لعائلة أخرى
            $stmt = $pdo->prepare("SELECT family_id FROM family_access_codes WHERE access_code = ? AND family_id != ?");
            $stmt->execute([$new_password, $_SESSION['family_id']]);
            
            if ($stmt->fetch()) {
                $error_message = 'كلمة المرور هذه مستخدمة من قبل عائلة أخرى، يرجى اختيار كلمة مرور مختلفة';
            } else {
                try {
                    // تحديث كلمة المرور
                    $stmt = $pdo->prepare("UPDATE family_access_codes SET access_code = ?, password_changed = 1 WHERE family_id = ?");
                    $stmt->execute([$new_password, $_SESSION['family_id']]);
                    
                    // تسجيل تغيير كلمة المرور
                    logPasswordChange($pdo, $_SESSION['family_id'], $current_code, $new_password);
                    
                    $success_message = 'تم تغيير كلمة المرور بنجاح';
                    $_SESSION['password_changed'] = true;
                    
                    // توجيه لصفحة التحديث بعد 2 ثانية
                    header("refresh:2;url=../public/family/family-update.php");
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $error_message = 'كلمة المرور هذه مستخدمة من قبل عائلة أخرى، يرجى اختيار كلمة مرور مختلفة';
                    } else {
                        $error_message = 'حدث خطأ أثناء تحديث كلمة المرور: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تغيير كلمة المرور - الشاعر عائلتي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .change-password-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .change-password-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        .change-password-header {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .change-password-body {
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
            border-color: #ffc107;
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
        }
        .btn-change {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            border: none;
            border-radius: 15px;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-change:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 193, 7, 0.4);
            color: white;
        }
        .alert {
            border-radius: 15px;
            border: none;
        }
        .info-box {
            background: #fff3cd;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #ffc107;
        }
        .password-strength {
            margin-top: 0.5rem;
        }
        .strength-bar {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        .strength-fill {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        .strength-weak { background: #dc3545; width: 25%; }
        .strength-medium { background: #ffc107; width: 50%; }
        .strength-strong { background: #28a745; width: 75%; }
        .strength-very-strong { background: #28a745; width: 100%; }
    </style>
</head>
<body>
    <div class="change-password-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="change-password-card">
                        <div class="change-password-header">
                            <h2 class="mb-0">
                                <i class="fas fa-key me-2"></i>
                                تغيير كلمة المرور
                            </h2>
                            <p class="mb-0 mt-2">إجبارية - أول دخول</p>
                        </div>
                        <div class="change-password-body">
                            <div class="info-box">
                                <h6 class="mb-2">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    تنبيه مهم
                                </h6>
                                <p class="mb-0 small">
                                    يجب عليك تغيير كلمة المرور في أول دخول لك. 
                                    اختر كلمة مرور جديدة مكونة من 8 أرقام يسهل عليك تذكرها.
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
                                <br><small>سيتم توجيهك لصفحة تحديث البيانات...</small>
                            </div>
                            <?php endif; ?>

                            <form method="POST" id="changePasswordForm">
                                <div class="mb-4">
                                    <label for="new_password" class="form-label fw-bold">
                                        <i class="fas fa-lock me-2"></i>
                                        كلمة المرور الجديدة
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="new_password" 
                                           name="new_password" 
                                           placeholder="أدخل كلمة مرور جديدة (6-20 خانة)"
                                           maxlength="20"
                                           required>
                                    <div class="password-strength">
                                        <div class="strength-bar">
                                            <div class="strength-fill" id="strengthFill"></div>
                                        </div>
                                        <small class="text-muted" id="strengthText">أدخل كلمة المرور</small>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label fw-bold">
                                        <i class="fas fa-lock me-2"></i>
                                        تأكيد كلمة المرور
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           placeholder="أعد إدخال كلمة المرور الجديدة"
                                           maxlength="20"
                                           required>
                                </div>

                                <button type="submit" class="btn btn-change" id="submitBtn" disabled>
                                    <i class="fas fa-save me-2"></i>
                                    تغيير كلمة المرور
                                </button>
                            </form>

                            <div class="text-center mt-4">
                                <a href="../public/family/family-login.php" class="text-muted">
                                    <i class="fas fa-arrow-right me-2"></i>
                                    العودة لتسجيل الدخول
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
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');

        // السماح بإدخال الحروف والأرقام والرموز فقط
        newPassword.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^A-Za-z0-9!@#$%&*]/g, '');
            checkPasswordStrength();
            validateForm();
        });

        confirmPassword.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^A-Za-z0-9!@#$%&*]/g, '');
            validateForm();
        });

        function checkPasswordStrength() {
            const password = newPassword.value;
            const length = password.length;
            
            if (length === 0) {
                strengthFill.className = 'strength-fill';
                strengthText.textContent = 'أدخل كلمة المرور';
                return;
            }

            if (length < 6) {
                strengthFill.className = 'strength-fill strength-weak';
                strengthText.textContent = 'ضعيف - يجب أن تكون 6 خانات على الأقل';
                return;
            }

            if (length > 20) {
                strengthFill.className = 'strength-fill strength-weak';
                strengthText.textContent = 'ضعيف - يجب أن تكون 20 خانة كحد أقصى';
                return;
            }

            // تحقق من التكرار
            const uniqueChars = new Set(password).size;
            
            if (uniqueChars === 1) {
                strengthFill.className = 'strength-fill strength-weak';
                strengthText.textContent = 'ضعيف جداً - جميع الأحرف متشابهة';
            } else if (uniqueChars <= 4) {
                strengthFill.className = 'strength-fill strength-medium';
                strengthText.textContent = 'متوسط - جرب أحرف أكثر تنوعاً';
            } else if (uniqueChars <= 6) {
                strengthFill.className = 'strength-fill strength-strong';
                strengthText.textContent = 'قوي - كلمة مرور جيدة';
            } else {
                strengthFill.className = 'strength-fill strength-very-strong';
                strengthText.textContent = 'قوي جداً - ممتاز!';
            }
        }

        function validateForm() {
            const newPass = newPassword.value;
            const confirmPass = confirmPassword.value;
            
            // التحقق من الطول والرموز المسموحة
            const isValidLength = newPass.length >= 6 && newPass.length <= 20;
            const hasValidChars = /^[A-Za-z0-9!@#$%&*]+$/.test(newPass);
            
            if (isValidLength && hasValidChars && 
                confirmPass.length >= 6 && confirmPass.length <= 20 && newPass === confirmPass) {
                submitBtn.disabled = false;
                confirmPassword.setCustomValidity('');
            } else {
                submitBtn.disabled = true;
                if (confirmPass.length > 0 && newPass !== confirmPass) {
                    confirmPassword.setCustomValidity('كلمة المرور غير متطابقة');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
        }

        // التركيز على حقل كلمة المرور الجديدة
        newPassword.focus();
    </script>
</body>
</html>
