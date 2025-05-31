<?php
session_start();
require_once "../../backend/config/config.php";

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

// Kiểm tra ID người dùng
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user_id = intval($_GET['id']);

// Lấy thông tin người dùng
$user_sql = "SELECT * FROM user WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($user_result) == 0) {
    header("Location: users.php?message=Không tìm thấy người dùng&message_type=danger");
    exit();
}

$user = mysqli_fetch_assoc($user_result);

// Lấy thông tin gói đăng ký hiện tại
$subscription_sql = "SELECT us.*, p.name as plan_name 
                    FROM user_subscriptions us 
                    LEFT JOIN plans p ON us.plan_id = p.id 
                    WHERE us.user_id = ? 
                    ORDER BY us.end_date DESC 
                    LIMIT 1";

if (!$stmt = mysqli_prepare($conn, $subscription_sql)) {
    // Nếu join không thành công, thử query đơn giản hơn
    $subscription_sql = "SELECT * FROM user_subscriptions 
                        WHERE user_id = ? 
                        ORDER BY end_date DESC 
                        LIMIT 1";
    $stmt = mysqli_prepare($conn, $subscription_sql);
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$subscription_result = mysqli_stmt_get_result($stmt);
$has_subscription = mysqli_num_rows($subscription_result) > 0;
$subscription = $has_subscription ? mysqli_fetch_assoc($subscription_result) : null;

// Kiểm tra trạng thái gói đăng ký
$is_expired = false;
$plan_name = "Cơ bản";

if ($has_subscription) {
    $is_expired = strtotime($subscription['end_date']) < time();
    
    // Cập nhật trạng thái trong DB nếu cần
    if (($subscription['is_active'] == 1 && $is_expired) || ($subscription['is_active'] == 0 && !$is_expired)) {
        $new_active_status = $is_expired ? 0 : 1;
        $update_status_sql = "UPDATE user_subscriptions SET is_active = ? WHERE id = ?";
        $status_stmt = mysqli_prepare($conn, $update_status_sql);
        mysqli_stmt_bind_param($status_stmt, "ii", $new_active_status, $subscription['id']);
        mysqli_stmt_execute($status_stmt);
        
        // Cập nhật lại biến local
        $subscription['is_active'] = $new_active_status;
    }
    
    // Xác định tên gói
    if (isset($subscription['plan_name'])) {
        $plan_name = $subscription['plan_name'];
    } else {
        // Dựa vào plan_id
        switch ($subscription['plan_id']) {
            case 1:
                $plan_name = "Cơ bản";
                break;
            case 2:
                $plan_name = "Gói phổ biến";
                break;
            case 3:
                $plan_name = "Gói cao cấp";
                break;
            default:
                $plan_name = "Không xác định";
        }
    }
}

// Đếm số phòng trọ đã đăng
$rooms_sql = "SELECT COUNT(*) as total FROM rooms WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $rooms_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$rooms_result = mysqli_stmt_get_result($stmt);
$rooms_count = mysqli_fetch_assoc($rooms_result)['total'];

// Xác định trang hiện tại
$current_page = 'users';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết người dùng - Homeseeker Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-container {
            display: flex;
        }
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background-color: #343a40;
            color: #fff;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            transition: all 0.3s;
        }
        .sidebar-header {
            padding: 20px;
            background: #212529;
        }
        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
        }
        .sidebar-menu {
            padding: 0;
            list-style: none;
        }
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        .sidebar-menu a {
            padding: 12px 20px;
            color: #adb5bd;
            display: block;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            color: #fff;
            background: #2c3136;
            border-left-color: #0d6efd;
        }
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .content {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 20px;
            transition: all 0.3s;
        }
        .navbar-admin {
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 10px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .user-card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .user-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }
        .user-body {
            padding: 20px;
        }
        .user-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #eee;
        }
        .info-item {
            margin-bottom: 1rem;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        .badge-active {
            background-color: #198754;
            color: #fff;
        }
        .badge-inactive {
            background-color: #dc3545;
            color: #fff;
        }
        .badge-subscription {
            background-color: #0d6efd;
            color: #fff;
        }
        .action-btn {
            padding: .375rem .75rem;
            font-size: .875rem;
        }
        .logout-btn {
            color: #dc3545;
        }
        .logout-btn:hover {
            background: #dc3545;
            color: #fff;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
            }
            .sidebar-brand, .menu-text {
                display: none;
            }
            .content {
                margin-left: 80px;
                width: calc(100% - 80px);
            }
            .sidebar-menu a {
                text-align: center;
                padding: 15px;
            }
            .sidebar-menu i {
                margin-right: 0;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
<div class="admin-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="home.php" class="sidebar-brand">
                <i class="fas fa-home"></i> Homeseeker
            </a>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="home.php" class="<?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="rooms.php" class="<?php echo $current_page == 'rooms' ? 'active' : ''; ?>">
                    <i class="fas fa-building"></i> <span class="menu-text">Quản lý phòng</span>
                </a>
            </li>
            <li>
                <a href="users.php" class="<?php echo $current_page == 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> <span class="menu-text">Quản lý người dùng</span>
                </a>
            </li>
            <li>
                <a href="reports.php" class="<?php echo $current_page == 'reports' ? 'active' : ''; ?>">
                    <i class="fas fa-flag"></i> <span class="menu-text">Báo cáo vi phạm</span>
                </a>
            </li>
            <li>
                <a href="payments.php" class="<?php echo $current_page == 'payments' ? 'active' : ''; ?>">
                    <i class="fas fa-credit-card"></i> <span class="menu-text">Thanh toán</span>
                </a>
            </li>
            <li>
                <a href="settings.php" class="<?php echo $current_page == 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> <span class="menu-text">Cài đặt</span>
                </a>
            </li>
            <li class="mt-5">
                <a href="../../frontend/auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> <span class="menu-text">Đăng xuất</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="navbar-admin d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Thông tin khách hàng</h4>
            </div>
            <div>
                <a href="users.php" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
                <span>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            </div>
        </div>

        <!-- User Info -->
        <div class="user-card">
            <div class="user-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-user"></i> Thông tin người dùng
                </div>
                <div>
                    <span class="badge <?php echo $user['status'] == 1 ? 'badge-active' : 'badge-inactive'; ?>">
                        <?php echo $user['status'] == 1 ? 'Đang hoạt động' : 'Đã khóa'; ?>
                    </span>
                </div>
            </div>
            <div class="user-body">
                <div class="row">
                    <div class="col-md-3 text-center mb-4 mb-md-0">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar" class="user-avatar mb-3">
                        <?php else: ?>
                            <div class="user-avatar d-flex align-items-center justify-content-center bg-light mb-3 mx-auto">
                                <i class="fas fa-user fa-3x text-secondary"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <?php if ($user['status'] == 1): ?>
                                <a href="users.php?action=block&id=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm action-btn w-100 mb-2" onclick="return confirm('Bạn có chắc muốn khóa người dùng này?');">
                                    <i class="fas fa-ban"></i> Khóa tài khoản
                                </a>
                            <?php else: ?>
                                <a href="users.php?action=unblock&id=<?php echo $user['id']; ?>" class="btn btn-success btn-sm action-btn w-100 mb-2">
                                    <i class="fas fa-check"></i> Mở khóa tài khoản
                                </a>
                            <?php endif; ?>
                            
                            <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm action-btn w-100" onclick="return confirm('Bạn có chắc muốn xóa người dùng này? Thao tác này không thể hoàn tác.');">
                                <i class="fas fa-trash"></i> Xóa tài khoản
                            </a>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Tên người dùng:</div>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Email:</div>
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Họ và tên:</div>
                                    <?php echo !empty($user['full_name']) ? htmlspecialchars($user['full_name']) : 'Chưa cập nhật'; ?>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Số điện thoại:</div>
                                    <?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Chưa cập nhật'; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Địa chỉ:</div>
                                    <?php echo !empty($user['address']) ? htmlspecialchars($user['address']) : 'Chưa cập nhật'; ?>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Ngày tạo tài khoản:</div>
                                    <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Cập nhật lần cuối:</div>
                                    <?php echo date('d/m/Y H:i', strtotime($user['updated_at'])); ?>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Vai trò:</div>
                                    <?php if ($user['is_admin'] == 1): ?>
                                        <span class="badge bg-danger">Quản trị viên</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Người dùng</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscription Info -->
        <div class="user-card">
            <div class="user-header">
                <i class="fas fa-star"></i> Thông tin gói đăng ký
            </div>
            <div class="user-body">
                <?php if ($has_subscription): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Gói hiện tại:</div>
                                <span class="badge badge-subscription"><?php echo htmlspecialchars($plan_name); ?></span>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Ngày bắt đầu:</div>
                                <?php echo date('d/m/Y', strtotime($subscription['start_date'])); ?>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Ngày kết thúc:</div>
                                <?php echo date('d/m/Y', strtotime($subscription['end_date'])); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Trạng thái:</div>
                                <span class="badge <?php echo !$is_expired ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo !$is_expired ? 'Đang hoạt động' : 'Hết hạn'; ?>
                                </span>
                            </div>
                            <?php if (!$is_expired): ?>
                                <div class="info-item">
                                    <div class="info-label">Thời gian còn lại:</div>
                                    <?php 
                                        $days_left = ceil((strtotime($subscription['end_date']) - time()) / (60 * 60 * 24));
                                        echo $days_left . ' ngày';
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Người dùng này đang sử dụng gói Cơ bản (miễn phí).
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Room Statistics -->
        <div class="user-card">
            <div class="user-header">
                <i class="fas fa-chart-bar"></i> Thống kê phòng trọ
            </div>
            <div class="user-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Tổng số phòng trọ đã đăng:</div>
                            <h3><?php echo $rooms_count; ?></h3>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-grid gap-2">
                            <a href="rooms.php?user_id=<?php echo $user['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-search"></i> Xem danh sách phòng
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-4 text-center text-muted">
            <p>&copy; <?php echo date('Y'); ?> Homeseeker Admin Panel</p>
        </footer>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 