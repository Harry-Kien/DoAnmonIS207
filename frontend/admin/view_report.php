<?php
session_start();
require_once "../../backend/config/config.php";

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

// Kiểm tra ID báo cáo
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: reports.php");
    exit();
}

$report_id = intval($_GET['id']);

// Xử lý các hành động
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'resolve':
            $sql = "UPDATE reports SET status = 'resolved', resolved_at = NOW(), resolved_by = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $report_id);
            mysqli_stmt_execute($stmt);
            $message = "Báo cáo #$report_id đã được đánh dấu là đã xử lý!";
            $message_type = "success";
            break;
            
        case 'reject':
            $sql = "UPDATE reports SET status = 'rejected', resolved_at = NOW(), resolved_by = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $report_id);
            mysqli_stmt_execute($stmt);
            $message = "Báo cáo #$report_id đã được đánh dấu là từ chối!";
            $message_type = "warning";
            break;
            
        case 'delete':
            $sql = "DELETE FROM reports WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $report_id);
            mysqli_stmt_execute($stmt);
            
            header("Location: reports.php?message=Báo cáo đã được xóa thành công&message_type=success");
            exit();
            break;
    }
}

// Lấy thông tin chi tiết báo cáo
$sql = "SELECT r.*, u.username as reporter_name, u.email as reporter_email, 
               rm.title as room_title, rm.address as room_address, rm.price as room_price,
               owner.username as owner_name, owner.email as owner_email,
               admin.username as admin_name
        FROM reports r 
        LEFT JOIN user u ON r.user_id = u.id 
        LEFT JOIN rooms rm ON r.room_id = rm.id
        LEFT JOIN user owner ON rm.user_id = owner.id
        LEFT JOIN user admin ON r.resolved_by = admin.id
        WHERE r.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $report_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: reports.php?message=Không tìm thấy báo cáo&message_type=danger");
    exit();
}

$report = mysqli_fetch_assoc($result);

// Xác định trang hiện tại
$current_page = 'reports';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết báo cáo - Homeseeker Admin</title>
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
        .report-card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .report-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }
        .report-body {
            padding: 20px;
        }
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-resolved {
            background-color: #198754;
            color: #fff;
        }
        .badge-rejected {
            background-color: #dc3545;
            color: #fff;
        }
        .badge-spam {
            background-color: #fd7e14;
            color: #fff;
        }
        .badge-inappropriate {
            background-color: #6f42c1;
            color: #fff;
        }
        .badge-fraud {
            background-color: #dc3545;
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
        .description-box {
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
                <h4 class="mb-0">Chi tiết báo cáo #<?php echo $report_id; ?></h4>
            </div>
            <div>
                <a href="reports.php" class="btn btn-outline-secondary btn-sm me-2">
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

        <!-- Report Status -->
        <div class="report-card">
            <div class="report-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-info-circle"></i> Trạng thái báo cáo
                </div>
                <div>
                    <?php 
                        $status_class = '';
                        $status_text = '';
                        switch ($report['status']) {
                            case 'pending':
                                $status_class = 'badge-pending';
                                $status_text = 'Chờ xử lý';
                                break;
                            case 'resolved':
                                $status_class = 'badge-resolved';
                                $status_text = 'Đã xử lý';
                                break;
                            case 'rejected':
                                $status_class = 'badge-rejected';
                                $status_text = 'Đã từ chối';
                                break;
                        }
                    ?>
                    <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                </div>
            </div>
            <div class="report-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Loại báo cáo:</div>
                            <?php 
                                $report_type = htmlspecialchars($report['report_type']);
                                $badge_class = '';
                                $report_type_text = '';
                                switch ($report_type) {
                                    case 'spam':
                                        $badge_class = 'badge-spam';
                                        $report_type_text = 'Spam';
                                        break;
                                    case 'inappropriate':
                                        $badge_class = 'badge-inappropriate';
                                        $report_type_text = 'Không phù hợp';
                                        break;
                                    case 'fraud':
                                        $badge_class = 'badge-fraud';
                                        $report_type_text = 'Lừa đảo';
                                        break;
                                    default:
                                        $badge_class = 'bg-secondary';
                                        $report_type_text = $report_type;
                                }
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo $report_type_text; ?></span>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Ngày báo cáo:</div>
                            <?php echo date('d/m/Y H:i', strtotime($report['created_at'])); ?>
                        </div>
                        <?php if ($report['status'] != 'pending'): ?>
                        <div class="info-item">
                            <div class="info-label">Ngày xử lý:</div>
                            <?php echo date('d/m/Y H:i', strtotime($report['resolved_at'])); ?>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Người xử lý:</div>
                            <?php echo htmlspecialchars($report['admin_name']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <?php if ($report['status'] == 'pending'): ?>
                        <div class="d-grid gap-2">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="resolve">
                                <button type="submit" class="btn btn-success w-100 mb-2" onclick="return confirm('Bạn có chắc muốn đánh dấu báo cáo này là đã xử lý?');">
                                    <i class="fas fa-check"></i> Đánh dấu đã xử lý
                                </button>
                            </form>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-warning w-100 mb-2" onclick="return confirm('Bạn có chắc muốn từ chối báo cáo này?');">
                                    <i class="fas fa-times"></i> Từ chối báo cáo
                                </button>
                            </form>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Bạn có chắc muốn xóa báo cáo này? Thao tác này không thể hoàn tác.');">
                                    <i class="fas fa-trash"></i> Xóa báo cáo
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="d-grid gap-2">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Bạn có chắc muốn xóa báo cáo này? Thao tác này không thể hoàn tác.');">
                                    <i class="fas fa-trash"></i> Xóa báo cáo
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Content -->
        <div class="report-card">
            <div class="report-header">
                <i class="fas fa-file-alt"></i> Nội dung báo cáo
            </div>
            <div class="report-body">
                <div class="description-box">
                    <?php echo nl2br(htmlspecialchars($report['description'])); ?>
                </div>
            </div>
        </div>

        <!-- Reporter Info -->
        <div class="report-card">
            <div class="report-header">
                <i class="fas fa-user"></i> Thông tin người báo cáo
            </div>
            <div class="report-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Tên người dùng:</div>
                            <a href="view_user.php?id=<?php echo $report['user_id']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($report['reporter_name']); ?>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Email:</div>
                            <?php echo htmlspecialchars($report['reporter_email']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room Info -->
        <div class="report-card">
            <div class="report-header">
                <i class="fas fa-building"></i> Thông tin phòng trọ
            </div>
            <div class="report-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Tiêu đề:</div>
                            <a href="view_room.php?id=<?php echo $report['room_id']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($report['room_title']); ?>
                            </a>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Địa chỉ:</div>
                            <?php echo htmlspecialchars($report['room_address']); ?>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Giá:</div>
                            <?php echo number_format($report['room_price']); ?> đ
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Chủ phòng:</div>
                            <a href="view_user.php?id=<?php echo $report['user_id']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($report['owner_name']); ?>
                            </a>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email chủ phòng:</div>
                            <?php echo htmlspecialchars($report['owner_email']); ?>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Hành động:</div>
                            <a href="view_room.php?id=<?php echo $report['room_id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> Xem phòng
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