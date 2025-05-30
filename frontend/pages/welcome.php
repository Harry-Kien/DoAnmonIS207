<?php
// Khởi động session
session_start();

// Kiểm tra nếu người dùng chưa đăng nhập, chuyển hướng về trang đăng nhập
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/login.php");
    exit;
}

// Bao gồm file cấu hình để có thể truy vấn thêm thông tin người dùng nếu cần
require_once "../../backend/config/config.php";

// Tùy chỉnh tiêu đề trang
$page_title = "Trang cá nhân - Homeseeker";

// Include header
include 'header.php';
?>

<!-- Profile Section -->
<div class="container">
    <div class="profile-container">
        <h2 class="mb-4">Trang cá nhân</h2>
        <div class="user-info mb-4">
            <h4>Thông tin tài khoản</h4>
            <?php
            // Lấy thêm thông tin từ cơ sở dữ liệu
            $sql = "SELECT email, created_at, avatar FROM user WHERE id = ?";
            $avatar = '';
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $_SESSION["user_id"]);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_bind_result($stmt, $email, $created_at, $avatar);
                    if (mysqli_stmt_fetch($stmt)) {
                        echo "<p><strong>Tên đăng nhập:</strong> " . htmlspecialchars($_SESSION["username"]) . "</p>";
                        echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
                        echo "<p><strong>Ngày tạo tài khoản:</strong> " . htmlspecialchars($created_at) . "</p>";
                    }
                }
                mysqli_stmt_close($stmt);
            }
            ?>
            <div class="mt-3">
                <form action="" method="post" enctype="multipart/form-data">
                    <label for="avatar">Ảnh đại diện:</label><br>
                    <img src="<?php echo !empty($avatar) ? '../../frontend/assets/avatars/' . htmlspecialchars($avatar) : 'https://via.placeholder.com/80'; ?>" alt="Avatar" class="rounded-circle mb-2" width="80" height="80"><br>
                    <input type="file" name="avatar" id="avatar" accept="image/*" class="form-control mb-2" style="max-width:300px;">
                    <button type="submit" name="update_avatar" class="btn btn-outline-primary btn-sm">Cập nhật ảnh đại diện</button>
                </form>
            </div>
            <?php
            // Xử lý upload avatar
            if (isset($_POST['update_avatar']) && isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                $target_dir = realpath(__DIR__ . '/../assets/avatars');
                if ($target_dir === false) {
                    mkdir(__DIR__ . '/../assets/avatars', 0777, true);
                    $target_dir = realpath(__DIR__ . '/../assets/avatars');
                }
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($ext, $allowed)) {
                    $newname = "user_" . $_SESSION["user_id"] . "_" . time() . "." . $ext;
                    $target_file = $target_dir . DIRECTORY_SEPARATOR . $newname;
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                        // Lưu vào DB (chỉ lưu tên file)
                        $sql = "UPDATE user SET avatar=? WHERE id=?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $newname, $_SESSION["user_id"]);
                        $stmt->execute();
                        echo '<div class="alert alert-success mt-2">Cập nhật ảnh đại diện thành công! <meta http-equiv="refresh" content="1"></div>';
                    } else {
                        echo '<div class="alert alert-danger mt-2">Lỗi upload file!</div>';
                    }
                } else {
                    echo '<div class="alert alert-danger mt-2">Chỉ cho phép file ảnh JPG, PNG, GIF!</div>';
                }
            }
            ?>
        </div>
        
        <?php
        // Lấy danh sách phòng yêu thích
        $fav_query = "SELECT r.*, 
                         (SELECT image_path FROM room_images WHERE room_id = r.id AND is_primary = 1 LIMIT 1) as image_url
                      FROM rooms r 
                      JOIN room_favorites rf ON r.id = rf.room_id 
                      WHERE rf.user_id = ? AND r.status = 'approved' 
                      ORDER BY rf.created_at DESC
                      LIMIT 3";
        $fav_stmt = $conn->prepare($fav_query);
        $fav_stmt->bind_param("i", $_SESSION['user_id']);
        $fav_stmt->execute();
        $fav_result = $fav_stmt->get_result();
        $favorites = $fav_result->fetch_all(MYSQLI_ASSOC);
        ?>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Phòng trọ yêu thích</h5>
                        <?php if (count($favorites) > 0): ?>
                            <?php foreach ($favorites as $fav): ?>
                                <div class="d-flex align-items-center mb-2">
                                    <img src="<?php echo !empty($fav['image_url']) ? '../assets/' . $fav['image_url'] : 'https://via.placeholder.com/50'; ?>" alt="" width="50" height="40" class="rounded me-2" style="object-fit:cover;">
                                    <div>
                                        <a href="../room/chi-tiet-phong.php?id=<?php echo $fav['id']; ?>" class="fw-bold text-decoration-none"><?php echo htmlspecialchars($fav['title']); ?></a>
                                        <div class="text-muted small"><?php echo number_format($fav['price']); ?> đ/tháng</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <a href="../../backend/user/favorites.php" class="btn btn-link p-0">Xem tất cả</a>
                        <?php else: ?>
                            <p class="mb-2">Bạn chưa có phòng trọ yêu thích nào.</p>
                            <a href="../room/phong.php" class="btn btn-warning btn-sm">Tìm phòng trọ</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Thông báo</h5>
                        </div>

                        <div class="text-center py-4">
                            <i class="fas fa-bell text-muted mb-3" style="font-size: 2.5rem;"></i>
                            <p class="text-muted mb-0">Bạn không có thông báo mới nào</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="buttons">
            <a href="../auth/reset_password.php" class="btn btn-warning">Đổi mật khẩu</a>
            <a href="../auth/logout.php" class="btn btn-outline-danger">Đăng xuất</a>
        </div>
    </div>
</div>

<?php
// Include footer
include 'footer.php';
?>