<?php
session_start();
require_once 'config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit();
}

// Lấy danh sách phòng của người dùng
$user_id = $_SESSION['id'];
$sql = "SELECT * FROM rooms WHERE user_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Danh Sách Phòng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Danh Sách Phòng Của Bạn</h2>

        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success_message']; 
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <a href="dang_phong.php" class="btn btn-primary mb-3">Đăng Phòng Mới</a>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Tiêu Đề</th>
                    <th>Loại Phòng</th>
                    <th>Diện Tích</th>
                    <th>Giá</th>
                    <th>Ngày Đăng</th>
                    <th>Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($room = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($room['title']); ?></td>
                        <td><?php echo $room['type']; ?></td>
                        <td><?php echo $room['area']; ?> m²</td>
                        <td><?php echo number_format($room['price']); ?> VNĐ</td>
                        <td><?php echo date('d/m/Y', strtotime($room['created_at'])); ?></td>
                        <td>
                            <a href="sua_phong.php?id=<?php echo $room['id']; ?>" class="btn btn-warning btn-sm">Sửa</a>
                            <a href="xoa_phong.php?id=<?php echo $room['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn chắc chắn muốn xóa?')">Xóa</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>