<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['family_logged_in']) || $_SESSION['family_logged_in'] !== true) {
    header('Location: ../public/family/family-login.php');
    exit;
}

require_once '../includes/db_connection.php';
require_once '../includes/device-tracker.php';

$family_id = $_SESSION['family_id'];
$success_message = '';
$error_message = '';

// معالجة تحديث سؤال الأمان
if ($_POST) {
    $question = $_POST['security_question'] ?? '';
    $custom_question = $_POST['custom_question'] ?? '';
    $answer = trim($_POST['security_answer'] ?? '');
    
    if (empty($answer)) {
        $error_message = 'يرجى إدخال إجابة سؤال الأمان';
    } else {
        // استخدام السؤال المخصص إذا تم اختيار "سؤال آخر"
        if ($question === 'other' && !empty($custom_question)) {
            $question = trim($custom_question);
        }
        
        if (empty($question)) {
            $error_message = 'يرجى اختيار أو كتابة سؤال الأمان';
        } else {
            try {
                // التحقق من وجود سؤال أمان سابق
                $stmt = $pdo->prepare("SELECT id FROM family_security_questions WHERE family_id = ?");
                $stmt->execute([$family_id]);
                
                if ($stmt->fetch()) {
                    // تحديث السؤال الموجود
                    $stmt = $pdo->prepare("UPDATE family_security_questions SET question = ?, answer = ?, updated_at = CURRENT_TIMESTAMP WHERE family_id = ?");
                    $stmt->execute([$question, $answer, $family_id]);
                } else {
                    // إدراج سؤال جديد
                    $stmt = $pdo->prepare("INSERT INTO family_security_questions (family_id, question, answer) VALUES (?, ?, ?)");
                    $stmt->execute([$family_id, $question, $answer]);
                }
                
                // تسجيل العملية
                logDataUpdate($pdo, $family_id, 'security_question', 'تم تحديث سؤال الأمان', 'تحديث سؤال الأمان: ' . $question);
                
                $success_message = 'تم حفظ سؤال الأمان بنجاح';
                
            } catch (PDOException $e) {
                $error_message = 'حدث خطأ أثناء حفظ سؤال الأمان: ' . $e->getMessage();
            }
        }
    }
}

// إعادة التوجيه إلى صفحة تحديث البيانات مع رسالة
$redirect_url = '../public/family/family-update.php';
if ($success_message) {
    $redirect_url .= '?success=' . urlencode($success_message);
} elseif ($error_message) {
    $redirect_url .= '?error=' . urlencode($error_message);
}

header('Location: ' . $redirect_url);
exit;
?>
