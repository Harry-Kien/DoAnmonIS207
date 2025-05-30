<?php
session_start();
require_once __DIR__ . "/../../backend/config/config.php";

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: /frontend/auth/login.php");
    exit();
}

$page_title = "Dashboard";

// Lấy thống kê từ DB
// Tổng số phòng
$sql_rooms = "SELECT COUNT(*) as total_rooms FROM rooms";
$result_rooms = mysqli_query($conn, $sql_rooms);
$total_rooms = mysqli_fetch_assoc($result_rooms)['total_rooms'];

// Phòng chờ duyệt
$sql_pending = "SELECT COUNT(*) as pending_rooms FROM rooms WHERE status = 'pending'";
$result_pending = mysqli_query($conn, $sql_pending);
$pending_rooms = mysqli_fetch_assoc($result_pending)['pending_rooms'];

// Tổng số người dùng
$sql_users = "SELECT COUNT(*) as total_users FROM user";
$result_users = mysqli_query($conn, $sql_users);
$total_users = mysqli_fetch_assoc($result_users)['total_users'];

// Tổng lượt truy cập (giả sử có bảng visits)
$sql_visits = "SELECT COUNT(*) as total_visits FROM room_images"; // Tạm dùng room_images để lấy số lượng
$result_visits = mysqli_query($conn, $sql_visits);
$total_visits = mysqli_fetch_assoc($result_visits)['total_visits'];

// Danh sách phòng mới nhất
$sql_latest_rooms = "SELECT r.*, u.username 
                    FROM rooms r 
                    JOIN user u ON r.user_id = u.id 
                    ORDER BY r.created_at DESC 
                    LIMIT 5";
$result_latest_rooms = mysqli_query($conn, $sql_latest_rooms);

// Include header
include __DIR__ . "/includes/header.php";
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
</div>

<!-- Content Row -->
<div class="row">
    <!-- Tổng số phòng -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Tổng số phòng</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_rooms; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-home fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Phòng chờ duyệt -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Phòng chờ duyệt</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_rooms; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tổng số người dùng -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Tổng số người dùng</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_users; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lượt truy cập -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Lượt xem hình ảnh</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_visits; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-eye fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Phòng mới nhất -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Phòng mới nhất</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tiêu đề</th>
                        <th>Người đăng</th>
                        <th>Loại phòng</th>
                        <th>Giá</th>
                        <th>Trạng thái</th>
                        <th>Ngày đăng</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($room = mysqli_fetch_assoc($result_latest_rooms)): ?>
                    <tr>
                        <td><?php echo $room['id']; ?></td>
                        <td><?php echo htmlspecialchars($room['title']); ?></td>
                        <td><?php echo htmlspecialchars($room['username']); ?></td>
                        <td><?php echo htmlspecialchars($room['type']); ?></td>
                        <td><?php echo number_format($room['price']); ?> đ</td>
                        <td>
                            <?php if ($room['status'] == 'pending'): ?>
                                <span class="badge bg-warning">Chờ duyệt</span>
                            <?php elseif ($room['status'] == 'approved'): ?>
                                <span class="badge bg-success">Đã duyệt</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Từ chối</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($room['created_at'])); ?></td>
                        <td>
                            <a href="/backend/payment/rooms.php?action=view&id=<?php echo $room['id']; ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if ($room['status'] == 'pending'): ?>
                            <a href="/backend/payment/rooms.php?action=approve&id=<?php echo $room['id']; ?>" class="btn btn-sm btn-success">
                                <i class="fas fa-check"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>