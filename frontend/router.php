<?php
ob_start(); // Bắt đầu output buffering

// Chỉ khởi động session nếu chưa được khởi động
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Định nghĩa hằng số cho đường dẫn gốc
define('BASE_PATH', '/IS207-hoomseeker');

// Lấy tham số page, mặc định là 'home'
$page = $_GET['page'] ?? 'home';

// Kiểm tra quyền truy cập cho các trang yêu cầu đăng nhập
$protected_pages = ['my_rooms', 'admin'];
if (in_array($page, $protected_pages) && !isset($_SESSION['user_id'])) {
    $current_url = $_SERVER['REQUEST_URI'];
    header("Location: " . BASE_PATH . "/frontend/auth/login.php?redirect=" . urlencode($current_url));
    exit();
}

// Bao gồm header
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . '/frontend/pages/header.php';

// Điều hướng dựa trên tham số page
switch ($page) {
    case 'home':
        include $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . '/frontend/pages/index.php';
        break;
    case 'login':
        include $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . '/frontend/auth/login.php';
        break;
    case 'register':
        include $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . '/frontend/auth/register.php';
        break;
    case 'logout':
        include $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . '/frontend/auth/logout.php';
        break;
    case 'phong':
        include $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . '/frontend/room/phong.php';
        break;
    case 'my_rooms':
        include $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . '/frontend/room/my_rooms.php';
        break;
    case 'admin':
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            include $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . '/backend/admin/admin_dashboard.php';
        } else {
            header("Location: " . BASE_PATH . "/frontend/pages/index.php");
            exit();
        }
        break;
    case 'search':
        include $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . '/frontend/pages/search.php';
        break;
    default:
        echo "<h2>404 - Trang không tồn tại</h2>";
        break;
}

// Bao gồm footer
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . '/frontend/pages/footer.php';

// Kết thúc và gửi output
ob_end_flush();
?>