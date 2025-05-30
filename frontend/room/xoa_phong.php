<?php
require_once '../../backend/auth/session.php';
require_once '../../backend/config/config.php';

$room_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Kiểm tra quyền sở hữu phòng
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $room_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();

if (!$room) {
    echo "<div class='alert alert-danger'>Không tìm thấy phòng hoặc bạn không có quyền xóa.</div>";
    exit;
}

// Xử lý xóa khi xác nhận
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $room_id, $user_id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Xóa phòng thành công!";
        header("Location: my_rooms.php");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Lỗi xóa phòng!</div>";
    }
}

// Include header
$page_title = "Xóa phòng";
include '../pages/header.php';
?>

<div class="container py-5">
    <h2 class="mb-4 text-danger">Xóa phòng</h2>
    <div class="alert alert-warning">
        <strong>Bạn có chắc chắn muốn xóa phòng:</strong>
        <br>
        <b><?php echo htmlspecialchars($room['title']); ?></b>
        <br>
        Hành động này không thể hoàn tác!
    </div>
    <form method="post">
        <button type="submit" class="btn btn-danger">Xác nhận xóa</button>
        <a href="my_rooms.php" class="btn btn-secondary">Hủy</a>
    </form>
</div>

<?php include '../pages/footer.php'; ?>