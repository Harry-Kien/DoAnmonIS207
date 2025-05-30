<?php
require_once '../../backend/auth/session.php';
require_once '../../backend/config/config.php';

$room_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Lấy thông tin phòng
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $room_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();

if (!$room) {
    echo "<div class='alert alert-danger'>Không tìm thấy phòng hoặc bạn không có quyền sửa.</div>";
    exit;
}

// Hiển thị lỗi nếu có
$errors = $_SESSION['room_errors'] ?? [];
unset($_SESSION['room_errors']);

// Include header
$page_title = "Sửa phòng";
include '../pages/header.php';
?>

<div class="container py-5">
    <h2 class="mb-4">Sửa thông tin phòng</h2>
    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $err) echo "<div>$err</div>"; ?>
        </div>
    <?php endif; ?>
    <form action="../../backend/rooms/xu_ly_sua_phong.php" method="post">
        <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
        <div class="mb-3">
            <label class="form-label">Tiêu đề</label>
            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($room['title']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Loại phòng</label>
            <select name="type" class="form-select" required>
                <option value="Phòng trọ" <?php if($room['type']=='Phòng trọ') echo 'selected'; ?>>Phòng trọ</option>
                <option value="Chung cư mini" <?php if($room['type']=='Chung cư mini') echo 'selected'; ?>>Chung cư mini</option>
                <option value="Nhà nguyên căn" <?php if($room['type']=='Nhà nguyên căn') echo 'selected'; ?>>Nhà nguyên căn</option>
                <option value="Ở ghép" <?php if($room['type']=='Ở ghép') echo 'selected'; ?>>Ở ghép</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Diện tích (m²)</label>
            <input type="number" name="area" class="form-control" value="<?php echo $room['area']; ?>" min="1" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Giá (VNĐ/tháng)</label>
            <input type="number" name="price" class="form-control" value="<?php echo floatval($room['price']); ?>" min="1" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Mô tả</label>
            <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($room['description']); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        <a href="my_rooms.php" class="btn btn-secondary">Quay lại</a>
    </form>
</div>

<?php include '../pages/footer.php'; ?>