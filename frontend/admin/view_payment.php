<?php
session_start();
require_once "../../backend/config/config.php";

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

// Kiểm tra ID thanh toán
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: payments.php");
    exit();
}

$payment_id = intval($_GET['id']);

// Xử lý các hành động
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'approve':
            $sql = "UPDATE payments SET status = 'completed', updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $payment_id);
            mysqli_stmt_execute($stmt);
            
            // Lấy thông tin thanh toán
            $payment_sql = "SELECT user_id, amount, transaction_id, payment_code FROM payments WHERE id = ?";
            $stmt = mysqli_prepare($conn, $payment_sql);
            mysqli_stmt_bind_param($stmt, "i", $payment_id);
            mysqli_stmt_execute($stmt);
            $payment_result = mysqli_stmt_get_result($stmt);
            
            if ($payment_data = mysqli_fetch_assoc($payment_result)) {
                $user_id = $payment_data['user_id'];
                $amount = $payment_data['amount'];
                $transaction_id = $payment_data['transaction_id'] ?? '';
                $payment_code = $payment_data['payment_code'] ?? '';
                
                // Xác định plan_id dựa trên số tiền hoặc mã giao dịch
                $plan_id = 1; // Mặc định là basic
                if ($amount == 199000 || strpos($transaction_id, 'standard') !== false || strpos($payment_code, 'standard') !== false) {
                    $plan_id = 2; // Standard plan
                } elseif ($amount == 399000 || strpos($transaction_id, 'premium') !== false || strpos($payment_code, 'premium') !== false) {
                    $plan_id = 3; // Premium plan
                }
                
                // Tính thời gian gói (30 ngày cho standard và premium)
                $duration = 30;
                $start_date = date('Y-m-d');
                $end_date = date('Y-m-d', strtotime("+$duration days"));
                
                // Kiểm tra xem người dùng đã có gói đăng ký chưa
                $check_user_sub = "SELECT * FROM user_subscriptions WHERE user_id = ? AND is_active = 1 ORDER BY end_date DESC LIMIT 1";
                $stmt = mysqli_prepare($conn, $check_user_sub);
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $user_sub_result = mysqli_stmt_get_result($stmt);
                
                if ($user_sub = mysqli_fetch_assoc($user_sub_result)) {
                    // Nếu đã có gói, gia hạn thêm
                    if (strtotime($user_sub['end_date']) > time()) {
                        $start_date = $user_sub['end_date'];
                        $end_date = date('Y-m-d', strtotime("$start_date +$duration days"));
                    }
                    
                    // Cập nhật gói hiện tại thành không hoạt động
                    $update_old_sub = "UPDATE user_subscriptions SET is_active = 0 WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $update_old_sub);
                    mysqli_stmt_bind_param($stmt, "i", $user_sub['id']);
                    mysqli_stmt_execute($stmt);
                }
                
                // Thêm gói mới
                $insert_sub = "INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, is_active, created_at, updated_at) 
                              VALUES (?, ?, ?, ?, 1, NOW(), NOW())";
                $stmt = mysqli_prepare($conn, $insert_sub);
                mysqli_stmt_bind_param($stmt, "iiss", $user_id, $plan_id, $start_date, $end_date);
                mysqli_stmt_execute($stmt);
            }
            
            $message = "Thanh toán #$payment_id đã được xác nhận thành công!";
            $message_type = "success";
            break;
            
        case 'cancel':
            $sql = "UPDATE payments SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $payment_id);
            mysqli_stmt_execute($stmt);
            $message = "Thanh toán #$payment_id đã bị hủy!";
            $message_type = "warning";
            break;
            
        case 'delete':
            $sql = "DELETE FROM payments WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $payment_id);
            mysqli_stmt_execute($stmt);
            
            header("Location: payments.php?message=Thanh toán đã được xóa thành công&message_type=success");
            exit();
            break;
    }
}

// Lấy thông tin chi tiết thanh toán
$sql = "SELECT p.*, u.username, u.email, u.phone
        FROM payments p 
        LEFT JOIN user u ON p.user_id = u.id
        WHERE p.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $payment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: payments.php?message=Không tìm thấy thanh toán&message_type=danger");
    exit();
}

$payment = mysqli_fetch_assoc($result);

// Xác định tên gói dựa trên mã giao dịch hoặc số tiền
$plan_name = "Không xác định";
$transaction_id = $payment['transaction_id'] ?? '';
$payment_code = $payment['payment_code'] ?? '';
$amount = $payment['amount'] ?? 0;

if (strpos($transaction_id, 'standard') !== false || strpos($payment_code, 'standard') !== false || $amount == 199000) {
    $plan_name = "Gói phổ biến";
    $plan_description = "Gói phổ biến cho chủ nhà trọ";
} elseif (strpos($transaction_id, 'premium') !== false || strpos($payment_code, 'premium') !== false || $amount == 399000) {
    $plan_name = "Gói cao cấp";
    $plan_description = "Gói cao cấp cho chủ nhà trọ chuyên nghiệp";
}

// Xác định trang hiện tại
$current_page = 'payments';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết thanh toán - Homeseeker Admin</title>
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
        .payment-card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .payment-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }
        .payment-body {
            padding: 20px;
        }
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-completed {
            background-color: #198754;
            color: #fff;
        }
        .badge-cancelled {
            background-color: #dc3545;
            color: #fff;
        }
        .badge-subscription {
            background-color: #0d6efd;
            color: #fff;
        }
        .logout-btn {
            color: #dc3545;
        }
        .logout-btn:hover {
            background: #dc3545;
            color: #fff;
        }
        .info-item {
            margin-bottom: 1rem;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        .payment-amount {
            font-size: 2rem;
            font-weight: 700;
            color: #0d6efd;
        }
        .payment-id {
            font-size: 1.2rem;
            color: #6c757d;
        }
        .transaction-box {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
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
                <h4 class="mb-0">Chi tiết thanh toán #<?php echo $payment_id; ?></h4>
            </div>
            <div>
                <a href="payments.php" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
                <span>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            </div>
        </div>

        <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Payment Summary -->
        <div class="payment-card">
            <div class="payment-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-info-circle"></i> Thông tin thanh toán
                </div>
                <div>
                    <?php 
                        $status_class = '';
                        $status_text = '';
                        switch ($payment['status']) {
                            case 'pending':
                                $status_class = 'badge-pending';
                                $status_text = 'Chờ xử lý';
                                break;
                            case 'completed':
                                $status_class = 'badge-completed';
                                $status_text = 'Hoàn thành';
                                break;
                            case 'cancelled':
                                $status_class = 'badge-cancelled';
                                $status_text = 'Đã hủy';
                                break;
                        }
                    ?>
                    <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                </div>
            </div>
            <div class="payment-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="payment-amount mb-2"><?php echo number_format($payment['amount']); ?> đ</div>
                        <div class="payment-id mb-4">Mã giao dịch: <?php echo htmlspecialchars($payment['transaction_id'] ?? 'Chưa có'); ?></div>
                        
                        <div class="info-item">
                            <div class="info-label">Phương thức thanh toán:</div>
                            <?php echo htmlspecialchars(ucfirst($payment['payment_method'] ?? 'N/A')); ?>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Ngày tạo:</div>
                            <?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?>
                        </div>
                        <?php if (!empty($payment['updated_at'])): ?>
                        <div class="info-item">
                            <div class="info-label">Ngày cập nhật:</div>
                            <?php echo date('d/m/Y H:i', strtotime($payment['updated_at'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <?php if ($payment['status'] == 'pending'): ?>
                        <div class="d-grid gap-2">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-success w-100 mb-2" onclick="return confirm('Bạn có chắc muốn xác nhận thanh toán này?');">
                                    <i class="fas fa-check"></i> Xác nhận thanh toán
                                </button>
                            </form>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="cancel">
                                <button type="submit" class="btn btn-warning w-100 mb-2" onclick="return confirm('Bạn có chắc muốn hủy thanh toán này?');">
                                    <i class="fas fa-times"></i> Hủy thanh toán
                                </button>
                            </form>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Bạn có chắc muốn xóa thanh toán này? Thao tác này không thể hoàn tác.');">
                                    <i class="fas fa-trash"></i> Xóa thanh toán
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="d-grid gap-2">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Bạn có chắc muốn xóa thanh toán này? Thao tác này không thể hoàn tác.');">
                                    <i class="fas fa-trash"></i> Xóa thanh toán
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscription Info -->
        <?php if (!empty($payment['subscription_id'])): ?>
        <div class="payment-card">
            <div class="payment-header">
                <i class="fas fa-star"></i> Thông tin gói đăng ký
            </div>
            <div class="payment-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="info-item">
                            <div class="info-label">Tên gói:</div>
                            <span class="badge badge-subscription"><?php echo htmlspecialchars($plan_name); ?></span>
                        </div>
                        <?php if (isset($plan_description)): ?>
                        <div class="info-item">
                            <div class="info-label">Mô tả:</div>
                            <?php echo htmlspecialchars($plan_description); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        // Kiểm tra trạng thái gói đăng ký
                        if ($payment['status'] == 'completed') {
                            // Từ ảnh cơ sở dữ liệu, chúng ta thấy bảng user_subscriptions có cấu trúc:
                            // id, user_id, plan_id, start_date, end_date, is_active, created_at, updated_at
                            // Không có cột payment_id, nên tìm dựa trên user_id và thời gian tạo gần với thời gian thanh toán
                            $sub_sql = "SELECT * FROM user_subscriptions 
                                      WHERE user_id = ? 
                                      ORDER BY ABS(TIMESTAMPDIFF(SECOND, created_at, ?)) ASC 
                                      LIMIT 1";
                            $stmt = mysqli_prepare($conn, $sub_sql);
                            mysqli_stmt_bind_param($stmt, "is", $payment['user_id'], $payment['created_at']);
                            mysqli_stmt_execute($stmt);
                            $sub_result = mysqli_stmt_get_result($stmt);
                            
                            if ($sub = mysqli_fetch_assoc($sub_result)) {
                                echo '<div class="info-item">
                                    <div class="info-label">Ngày bắt đầu:</div>
                                    ' . date('d/m/Y', strtotime($sub['start_date'])) . '
                                </div>';
                                
                                echo '<div class="info-item">
                                    <div class="info-label">Ngày kết thúc:</div>
                                    ' . date('d/m/Y', strtotime($sub['end_date'])) . '
                                </div>';
                                
                                // Kiểm tra ngày hết hạn để xác định trạng thái thực tế
                                $is_expired = strtotime($sub['end_date']) < time();
                                
                                // Hiển thị trạng thái dựa trên ngày hết hạn thực tế, không phụ thuộc vào trường is_active
                                $status_text = !$is_expired ? 'Đang hoạt động' : 'Hết hạn';
                                $status_class = !$is_expired ? 'badge-completed' : 'badge-cancelled';
                                
                                echo '<div class="info-item">
                                    <div class="info-label">Trạng thái gói:</div>
                                    <span class="badge ' . $status_class . '">' . $status_text . '</span>
                                </div>';
                                
                                // Nếu trạng thái thực tế và trạng thái trong DB không khớp, cập nhật DB
                                if (($sub['is_active'] == 1 && $is_expired) || ($sub['is_active'] == 0 && !$is_expired)) {
                                    $new_active_status = $is_expired ? 0 : 1;
                                    $update_status_sql = "UPDATE user_subscriptions SET is_active = ? WHERE id = ?";
                                    $status_stmt = mysqli_prepare($conn, $update_status_sql);
                                    mysqli_stmt_bind_param($status_stmt, "ii", $new_active_status, $sub['id']);
                                    mysqli_stmt_execute($status_stmt);
                                }
                            } else {
                                echo '<div class="alert alert-warning">Gói đăng ký chưa được kích hoạt.</div>';
                            }
                        } else {
                            echo '<div class="alert alert-info">Gói đăng ký sẽ được kích hoạt sau khi thanh toán được xác nhận.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Customer Info -->
        <div class="payment-card">
            <div class="payment-header">
                <i class="fas fa-user"></i> Thông tin khách hàng
            </div>
            <div class="payment-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Tên người dùng:</div>
                            <a href="view_user.php?id=<?php echo $payment['user_id']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($payment['username']); ?>
                            </a>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email:</div>
                            <?php echo htmlspecialchars($payment['email']); ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Số điện thoại:</div>
                            <?php echo htmlspecialchars($payment['phone'] ?? 'Không có'); ?>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Hành động:</div>
                            <a href="view_user.php?id=<?php echo $payment['user_id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> Xem thông tin người dùng
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Details -->
        <?php if (!empty($payment['transaction_id']) || !empty($payment['payment_details'])): ?>
        <div class="payment-card">
            <div class="payment-header">
                <i class="fas fa-receipt"></i> Chi tiết giao dịch
            </div>
            <div class="payment-body">
                <div class="transaction-box">
                    <?php if (!empty($payment['transaction_id'])): ?>
                    <div class="info-item">
                        <div class="info-label">Mã giao dịch:</div>
                        <?php echo htmlspecialchars($payment['transaction_id']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($payment['payment_details'])): ?>
                    <div class="info-item">
                        <div class="info-label">Thông tin bổ sung:</div>
                        <pre class="mb-0"><?php echo htmlspecialchars($payment['payment_details']); ?></pre>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

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