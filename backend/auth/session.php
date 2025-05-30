<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lấy URL hiện tại để redirect sau khi đăng nhập
$current_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

// Nếu chưa đăng nhập, chuyển về trang đăng nhập với redirect URL
if (!isset($_SESSION['user_id'])) {
    $redirect_url = '/IS207-hoomseeker/frontend/auth/login.php';
    if (!empty($current_url)) {
        $redirect_url .= '?redirect=' . urlencode($current_url);
    }
    header("Location: " . $redirect_url);
    exit();
}
