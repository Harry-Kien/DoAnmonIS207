<?php
// Khởi động session
session_start();
header('Content-Type: application/json');

// Kiểm tra người dùng đã đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

// Kết nối CSDL
require_once '../../backend/config/config.php';

$user_id = $_SESSION['user_id'];
$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;

if ($room_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit;
}

// Kiểm tra đã lưu chưa
$stmt = $conn->prepare("SELECT id FROM room_favorites WHERE user_id = ? AND room_id = ?");
$stmt->bind_param("ii", $user_id, $room_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Đã lưu, xóa khỏi yêu thích
    $stmt_del = $conn->prepare("DELETE FROM room_favorites WHERE user_id = ? AND room_id = ?");
    $stmt_del->bind_param("ii", $user_id, $room_id);
    $stmt_del->execute();
    echo json_encode(['success' => true, 'action' => 'removed']);
} else {
    // Chưa lưu, thêm vào yêu thích
    $stmt_add = $conn->prepare("INSERT INTO room_favorites (user_id, room_id) VALUES (?, ?)");
    $stmt_add->bind_param("ii", $user_id, $room_id);
    $stmt_add->execute();
    echo json_encode(['success' => true, 'action' => 'added']);
}
?>