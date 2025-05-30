<?php
session_start();
require_once 'config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit();
}

// Lấy ID phòng từ URL
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Truy vấn thông tin phòng
$sql = "SELECT * FROM rooms WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $room_id, $_SESSION['id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$room = mysqli_fetch_assoc($result);

if (!$room) {
    echo "Phòng không tồn tại hoặc bạn không có quyền chỉnh sửa.";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sửa Phòng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Sửa Phòng</h2>
        <form action="xu_ly_sua_phong.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
            
            <div class="mb-3">
                <label>Tiêu đề</label>
                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($room['title']); ?>" required>
            </div>

            <div class="mb-3">
                <label>Loại phòng</label>
                <select name="type" class="form-control">
                    <option value="Phòng trọ" <?php echo ($room['type'] == 'Phòng trọ') ? 'selected' : ''; ?>>Phòng trọ</option>
                    <option value="Chung cư mini" <?php echo ($room['type'] == 'Chung cư mini') ? 'selected' : ''; ?>>Chung cư mini</option>
                    <option value="Studio" <?php echo ($room['type'] == 'Studio') ? 'selected' : ''; ?>>Studio</option>
                    <option value="Nhà nguyên căn" <?php echo ($room['type'] == 'Nhà nguyên căn') ? 'selected' : ''; ?>>Nhà nguyên căn</option>
                    <option value="Ở ghép" <?php echo ($room['type'] == 'Ở ghép') ? 'selected' : ''; ?>>Ở ghép</option>
                </select>
            </div>

            <div class="mb-3">
                <label>Diện tích (m²)</label>
                <input type="number" name="area" class="form-control" value="<?php echo $room['area']; ?>" step="0.1" required>
            </div>

            <div class="mb-3">
                <label>Giá thuê (VNĐ)</label>
                <input type="number" name="price" class="form-control" value="<?php echo $room['price']; ?>" required>
            </div>

            <div class="mb-3">
                <label>Mô tả</label>
                <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($room['description']); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Cập Nhật Phòng</button>
        </form>
    </div>
</body>
</html>