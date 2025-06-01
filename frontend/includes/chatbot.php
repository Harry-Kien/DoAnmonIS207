<?php
/**
 * File include để thêm chatbot vào các trang
 * Sử dụng: include_once 'frontend/includes/chatbot.php';
 */

// Kiểm tra xem đã có session chưa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lấy user_id từ session nếu đã đăng nhập
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Đường dẫn đến component chatbot
include_once __DIR__ . '/../components/chatbot.php';
?> 