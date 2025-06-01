<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $current_url = $_SERVER['REQUEST_URI'];
    header("Location: /IS207-hoomseeker/frontend/auth/login.php?redirect=" . urlencode($current_url));
    exit();
}

// Tùy chỉnh tiêu đề trang
$page_title = "Quản lý phòng đã đăng - Homeseeker";

// Kết nối database
require_once '../../backend/config/config.php';

// Lấy danh sách phòng của người dùng
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM rooms WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Include header sau khi đã xử lý session và chuyển hướng
include '../pages/header.php';
?>

<div class="container py-5" style="min-height: calc(100vh - 280px);">
    <h1 class="mb-4">Quản lý phòng đã đăng</h1>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-end mb-4">
        <a href="../../backend/rooms/dang_phong.php" class="btn btn-warning">
            <i class="fas fa-plus-circle me-2"></i>Đăng tin mới
        </a>
    </div>
    
    <?php if ($result->num_rows > 0): ?>
        <div class="card shadow">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Tiêu đề</th>
                                <th scope="col">Loại phòng</th>
                                <th scope="col">Giá</th>
                                <th scope="col">Địa chỉ</th>
                                <th scope="col">Trạng thái</th>
                                <th scope="col">Ngày đăng</th>
                                <th scope="col">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($room = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <a href="chi-tiet-phong.php?id=<?php echo $room['id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($room['title'] ?? 'Chưa có tiêu đề'); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($room['type'] ?? 'Không xác định'); ?></td>
                                    <td class="text-danger fw-bold"><?php echo isset($room['price']) ? number_format($room['price']) : '0'; ?> đ/tháng</td>
                                    <td>
                                        <?php 
                                        $diachi = '';
                                        if (!empty($room['address'])) {
                                            $diachi .= htmlspecialchars($room['address']);
                                        }
                                        if (!empty($room['address']) && !empty($room['district'])) {
                                            $diachi .= ', ';
                                        }
                                        if (!empty($room['district'])) {
                                            $diachi .= htmlspecialchars($room['district']);
                                        }
                                        if ((!empty($room['address']) || !empty($room['district'])) && !empty($room['city'])) {
                                            $diachi .= ', ';
                                        }
                                        if (!empty($room['city'])) {
                                            $diachi .= htmlspecialchars($room['city']);
                                        }
                                        echo $diachi ? $diachi : 'Chưa có địa chỉ'; 
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (isset($room['status'])): ?>
                                            <?php if ($room['status'] == 'approved'): ?>
                                                <span class="badge bg-success">Đã duyệt</span>
                                            <?php elseif ($room['status'] == 'pending'): ?>
                                                <span class="badge bg-warning">Chờ duyệt</span>
                                            <?php elseif ($room['status'] == 'rejected'): ?>
                                                <span class="badge bg-danger">Từ chối</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Không xác định</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Không xác định</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo isset($room['created_at']) ? date('d/m/Y', strtotime($room['created_at'])) : 'N/A'; ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="../../frontend/room/sua_phong.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="../../frontend/room/xoa_phong.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Bạn có chắc chắn muốn xóa phòng này?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info p-5 text-center">
            <i class="fas fa-info-circle fa-2x mb-3"></i>
            <h4>Bạn chưa có phòng trọ nào</h4>
            <p class="mb-4">Hãy bắt đầu đăng tin để cho thuê phòng trọ của bạn.</p>
            <a href="dang_phong.php" class="btn btn-warning px-4">Đăng tin ngay</a>
        </div>
    <?php endif; ?>
</div>
<?php include '../pages/footer.php'; ?>