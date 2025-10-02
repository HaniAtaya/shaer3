<?php
// التحقق من صلاحيات المشرف بناءً على الفرع العائلي
function checkFamilyBranchAccess($pdo, $admin_id, $table_name, $family_branch_column) {
    // جلب معلومات المشرف
    $stmt = $pdo->prepare("SELECT role, family_branch FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        return false;
    }
    
    // إذا كان المشرف الرئيسي أو الإدمن العام، يمكنه رؤية كل شيء
    if ($admin['role'] === 'super_admin' || $admin['role'] === 'admin') {
        return true;
    }
    
    // إذا كان مشرف فرع عائلي، يمكنه رؤية بيانات فرعه فقط
    if ($admin['role'] === 'family_admin' && !empty($admin['family_branch'])) {
        return "WHERE $family_branch_column = '" . $admin['family_branch'] . "'";
    }
    
    return false;
}

// إضافة شرط الفرع العائلي للاستعلامات
function addFamilyBranchFilter($pdo, $admin_id, $table_name, $family_branch_column, $existing_where = '') {
    $family_filter = checkFamilyBranchAccess($pdo, $admin_id, $table_name, $family_branch_column);
    
    if ($family_filter === true) {
        return $existing_where; // لا حاجة لفلتر إضافي
    } elseif ($family_filter === false) {
        return "WHERE 1=0"; // لا يمكن الوصول لأي بيانات
    } else {
        // إضافة فلتر الفرع العائلي
        if (empty($existing_where)) {
            return $family_filter;
        } else {
            return $existing_where . " AND " . str_replace("WHERE ", "", $family_filter);
        }
    }
}

// التحقق من إمكانية الوصول لعنصر محدد
function canAccessItem($pdo, $admin_id, $table_name, $family_branch_column, $item_id) {
    $admin_stmt = $pdo->prepare("SELECT role, family_branch FROM admins WHERE id = ?");
    $admin_stmt->execute([$admin_id]);
    $admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        return false;
    }
    
    // إذا كان المشرف الرئيسي أو الإدمن العام
    if ($admin['role'] === 'super_admin' || $admin['role'] === 'admin') {
        return true;
    }
    
    // إذا كان مشرف فرع عائلي
    if ($admin['role'] === 'family_admin' && !empty($admin['family_branch'])) {
        $item_stmt = $pdo->prepare("SELECT $family_branch_column FROM $table_name WHERE id = ?");
        $item_stmt->execute([$item_id]);
        $item = $item_stmt->fetch(PDO::FETCH_ASSOC);
        
        return $item && $item[$family_branch_column] === $admin['family_branch'];
    }
    
    return false;
}
?>
