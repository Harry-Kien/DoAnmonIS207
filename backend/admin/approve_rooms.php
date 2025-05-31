<?php
session_start();

// Kiểm tra người dùng đã đăng nhập và có quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: /frontend/auth/login.php");
    exit();
}

// Kết nối database
require_once __DIR__ . '/../../backend/config/config.php';

// Kiểm tra tham số
if (isset($_GET['id']) && isset($_GET['action'])) {
    $room_id = $_GET['id'];
    $action = $_GET['action'];
    
    // Xác thực dữ liệu
    if (!is_numeric($room_id)) {
        $_SESSION['error_message'] = "ID phòng không hợp lệ";
        header("Location: /backend/payment/admin_dashboard.php");
        exit();
    }
    
    // Cập nhật trạng thái phòng
    if ($action == 'approve') {
        $status = 'approved';
        $message = "Đã duyệt tin đăng thành công!";
    } elseif ($action == 'reject') {
        $status = 'rejected';
        $message = "Đã từ chối tin đăng!";
    } else {
        $_SESSION['error_message'] = "Hành động không hợp lệ";
        header("Location: /backend/payment/admin_dashboard.php");
        exit();
    }
    
    // Cập nhật trong database
    $sql = "UPDATE rooms SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $room_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = $message;
    } else {
        $_SESSION['error_message'] = "Có lỗi xảy ra: " . $stmt->error;
    }
    
    // Đóng các resources
    $stmt->close();
    $conn->close();
    
    // Chuyển hướng về trang dashboard
    header("Location: /backend/payment/admin_dashboard.php");
    exit();
} else {
    $_SESSION['error_message'] = "Thiếu thông tin";
    header("Location: /backend/payment/admin_dashboard.php");
    exit();
}
?>