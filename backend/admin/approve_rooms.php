<?php
session_start();

// Kiểm tra người dùng đã đăng nhập và có quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

// Kết nối database
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/notifications/notification_handler.php';

// Khởi tạo notification handler
$notificationHandler = new NotificationHandler($conn);

// Kiểm tra tham số
if (isset($_GET['id']) && isset($_GET['action'])) {
    $room_id = $_GET['id'];
    $action = $_GET['action'];
    
    // Xác thực dữ liệu
    if (!is_numeric($room_id)) {
        $_SESSION['error_message'] = "ID phòng không hợp lệ";
        header("Location: ../../backend/admin/rooms.php");
        exit();
    }
    
    // Lấy thông tin phòng và user_id
    $sql_room = "SELECT user_id, title FROM rooms WHERE id = ?";
    $stmt_room = $conn->prepare($sql_room);
    $stmt_room->bind_param("i", $room_id);
    $stmt_room->execute();
    $room_result = $stmt_room->get_result();
    
    if ($room_result->num_rows == 0) {
        $_SESSION['error_message'] = "Không tìm thấy phòng";
        header("Location: ../../backend/admin/rooms.php");
        exit();
    }
    
    $room_data = $room_result->fetch_assoc();
    $user_id = $room_data['user_id'];
    $room_title = $room_data['title'];
    
    // Cập nhật trạng thái phòng
    if ($action == 'approve') {
        $status = 'approved';
        $message = "Đã duyệt tin đăng thành công!";
        
        // Tạo thông báo cho chủ phòng
        $notificationHandler->addNotification(
            $user_id,
            'room_approved',
            'Phòng đã được duyệt',
            'Phòng trọ "' . $room_title . '" của bạn đã được quản trị viên phê duyệt và hiển thị công khai.',
            $room_id
        );
    } elseif ($action == 'reject') {
        $status = 'rejected';
        $message = "Đã từ chối tin đăng!";
        
        // Tạo thông báo cho chủ phòng
        $notificationHandler->addNotification(
            $user_id,
            'room_rejected',
            'Phòng đã bị từ chối',
            'Phòng trọ "' . $room_title . '" của bạn đã bị từ chối. Vui lòng kiểm tra lại thông tin và cập nhật.',
            $room_id
        );
    } else {
        $_SESSION['error_message'] = "Hành động không hợp lệ";
        header("Location: ../../backend/admin/rooms.php");
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
    $stmt_room->close();
    $conn->close();
    
    // Chuyển hướng về trang dashboard
    header("Location: ../../backend/admin/rooms.php");
    exit();
} else {
    $_SESSION['error_message'] = "Thiếu thông tin";
    header("Location: ../../backend/admin/rooms.php");
    exit();
}
?>