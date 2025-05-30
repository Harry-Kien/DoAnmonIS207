<?php
ob_start(); // Bắt đầu output buffering

// Chỉ khởi động session nếu không phải là yêu cầu đăng xuất
if (!isset($_GET['page']) || $_GET['page'] !== 'logout') {
    session_start();
}

// Định nghĩa hằng số cho đường dẫn gốc
define('BASE_PATH', '/IS207-hoomseeker');

// Bao gồm header
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . '/frontend/pages/header.php';

// Lấy tham số page, mặc định là 'home'
$page = $_GET['page'] ?? 'home';

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
        include $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . '/backend/admin/admin_dashboard.php';
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