<?php
// Khởi động session
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Kết nối database
require_once 'config.php';

// Lấy ID phòng từ URL
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// Kiểm tra phòng có thuộc về người dùng không
$sql = "SELECT id FROM rooms WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $room_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Phòng không tồn tại hoặc không thuộc về người dùng
    $_SESSION['error_message'] = "Bạn không có quyền xóa phòng này.";
    header("Location: my_rooms.php");
    exit();
}

// Lấy danh sách ảnh để xóa file
$sql_images = "SELECT image_url FROM room_images WHERE room_id = ?";
$stmt_images = $conn->prepare($sql_images);
$stmt_images->bind_param("i", $room_id);
$stmt_images->execute();
$images = $stmt_images->get_result()->fetch_all(MYSQLI_ASSOC);

// Tiến hành xóa
$conn->begin_transaction();

try {
    // Xóa các ảnh từ bảng room_images
    $sql_delete_images = "DELETE FROM room_images WHERE room_id = ?";
    $stmt_delete_images = $conn->prepare($sql_delete_images);
    $stmt_delete_images->bind_param("i", $room_id);
    $stmt_delete_images->execute();
    
    // Xóa phòng từ bảng rooms
    $sql_delete_room = "DELETE FROM rooms WHERE id = ? AND user_id = ?";
    $stmt_delete_room = $conn->prepare($sql_delete_room);
    $stmt_delete_room->bind_param("ii", $room_id, $user_id);
    $stmt_delete_room->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Xóa file ảnh từ thư mục
    foreach ($images as $image) {
        if (file_exists($image['image_url'])) {
            unlink($image['image_url']);
        }
    }
    
    $_SESSION['success_message'] = "Xóa phòng thành công.";
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    $conn->rollback();
    $_SESSION['error_message'] = "Xóa phòng thất bại: " . $e->getMessage();
}

// Đóng kết nối và chuyển hướng
$conn->close();
header("Location: my_rooms.php");
exit();
?>