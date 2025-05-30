<?php
// Chỉ khởi động session nếu không phải là yêu cầu đăng xuất
if (!isset($_GET['page']) || $_GET['page'] !== 'logout') {
    session_start();
}

// Bao gồm header
include 'pages/header.php';

// Lấy tham số page, mặc định là 'home'
$page = $_GET['page'] ?? 'home';

// Điều hướng dựa trên tham số page
switch ($page) {
    case 'home':
        include 'pages/index.php';
        break;
    case 'login':
        include 'auth/login.php';
        break;
    case 'register':
        include 'auth/register.php';
        break;
    case 'logout':
        include 'auth/logout.php';
        break;
    case 'phong':
        include 'rooms/phong.php';
        break;
    case 'my_rooms':
        include 'rooms/my_rooms.php';
        break;
    case 'admin':
        include '../backend/admin/admin_dashboard.php';
        break;
    case 'search':
        include 'pages/search.php';
        break;
    default:
        echo "<h2>404 - Trang không tồn tại</h2>";
        break;
}

// Bao gồm footer
include 'pages/footer.php';
?>