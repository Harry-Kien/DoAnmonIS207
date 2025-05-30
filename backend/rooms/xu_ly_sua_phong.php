<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form
    $room_id = intval($_POST['room_id'] ?? 0);
    $user_id = $_SESSION['id']; // SỬA DÒNG NÀY
    $title = $_POST['title'] ?? '';
    $type = $_POST['type'] ?? '';
    $area = floatval($_POST['area'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $description = $_POST['description'] ?? '';

    // Validate dữ liệu
    $errors = [];
    if (empty($title)) $errors[] = "Tiêu đề không được để trống";
    if ($area <= 0) $errors[] = "Diện tích phải lớn hơn 0";
    if ($price <= 0) $errors[] = "Giá phải lớn hơn 0";

    // Nếu không có lỗi, cập nhật CSDL
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE rooms SET title=?, type=?, area=?, price=?, description=? WHERE id=? AND user_id=?");
        $stmt->bind_param("ssddssi", $title, $type, $area, $price, $description, $room_id, $user_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Cập nhật phòng thành công!";
            header("Location: danh_sach_phong.php");
            exit();
        } else {
            $errors[] = "Lỗi: " . mysqli_error($conn);
        }
    }

    // Nếu có lỗi, quay lại trang sửa
    if (!empty($errors)) {
        $_SESSION['room_errors'] = $errors;
        header("Location: sua_phong.php?id=" . urlencode($room_id));
        exit();
    }
} else {
    // Nếu không phải POST, chuyển về danh sách phòng
    header("Location: danh_sach_phong.php");
    exit();
}
?>
<input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">