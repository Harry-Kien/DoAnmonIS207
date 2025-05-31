<?php
// Khởi động phiên làm việc
session_start();

// Xóa cookie remember_token nếu có
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('remember_username', '', time() - 3600, '/');
    setcookie('remember_login_username', '', time() - 3600, '/');
    setcookie('remember_login_password', '', time() - 3600, '/');
}

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

// Chuyển hướng về trang chủ
header("Location: /IS207-hoomseeker/frontend/pages/index.php");
exit;
?>