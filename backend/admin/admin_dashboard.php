<?php
session_start();

// Kiểm tra người dùng đã đăng nhập và có quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: /frontend/auth/login.php");
    exit();
}

// Kết nối database
require_once __DIR__ . '/../../backend/config/config.php';

// Lấy danh sách phòng chờ duyệt
$sql = "SELECT r.*, u.username FROM rooms r 
        JOIN user u ON r.user_id = u.id 
        WHERE r.status = 'pending' 
        ORDER BY r.created_at DESC";
$result = $conn->query($sql);

// Include header
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Quản lý tin đăng</h1>
    
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active" href="#pending" data-bs-toggle="tab">Chờ duyệt</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#approved" data-bs-toggle="tab">Đã duyệt</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#rejected" data-bs-toggle="tab">Từ chối</a>
        </li>
    </ul>
    
    <div class="tab-content">
        <div class="tab-pane active" id="pending">
            <div class="card shadow">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Tiêu đề</th>
                                    <th>Người đăng</th>
                                    <th>Địa chỉ</th>
                                    <th>Giá</th>
                                    <th>Ngày đăng</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td>
                                                <a href="/frontend/pages/chi-tiet-phong.php?id=<?php echo $row['id']; ?>" target="_blank">
                                                    <?php echo htmlspecialchars($row['title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                            <td><?php echo htmlspecialchars($row['address'] . ', ' . $row['district'] . ', ' . $row['city']); ?></td>
                                            <td><?php echo number_format($row['price']); ?> đ/tháng</td>
                                            <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <a href="/backend/payment/admin_approve.php?id=<?php echo $row['id']; ?>&action=approve" class="btn btn-success btn-sm">Duyệt</a>
                                                <a href="/backend/payment/admin_approve.php?id=<?php echo $row['id']; ?>&action=reject" class="btn btn-danger btn-sm">Từ chối</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-3">Không có tin đang chờ duyệt</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Các tab khác tương tự -->
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>