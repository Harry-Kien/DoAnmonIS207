<?php
// Khởi động phiên làm việc
session_start(); // Bắt buộc phải có dòng này

// Xóa tất cả các biến session
$_SESSION = array();

// Xóa cookie session nếu có
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hủy phiên làm việc
session_destroy();

// Chuyển hướng về trang chủ (tùy cấu trúc dự án của bạn)
header("Location: ../../frontend/pages/index.php");
exit;
?>