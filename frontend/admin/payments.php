<?php
session_start();
require_once "../../backend/config/config.php";

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

// Xử lý các hành động
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);
    
    switch ($action) {
        case 'approve':
            $sql = "UPDATE payments SET status = 'completed', updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $message = "Thanh toán #$id đã được xác nhận thành công!";
            $message_type = "success";
            break;
            
        case 'cancel':
            $sql = "UPDATE payments SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $message = "Thanh toán #$id đã bị hủy!";
            $message_type = "warning";
            break;
            
        case 'delete':
            $sql = "DELETE FROM payments WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $message = "Thanh toán #$id đã được xóa thành công!";
            $message_type = "success";
            break;
    }
}

// Lấy danh sách thanh toán với bộ lọc
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = '';

if ($filter == 'pending') {
    $where_clause = "WHERE p.status = 'pending'";
} elseif ($filter == 'completed') {
    $where_clause = "WHERE p.status = 'completed'";
} elseif ($filter == 'cancelled') {
    $where_clause = "WHERE p.status = 'cancelled'";
} else {
    $where_clause = "WHERE 1=1";
}

if (!empty($search)) {
    $where_clause .= " AND (p.transaction_id LIKE '%$search%' OR u.username LIKE '%$search%' OR u.email LIKE '%$search%')";
}

// Phân trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Đếm tổng số bản ghi
$count_sql = "SELECT COUNT(*) as total FROM payments p 
              LEFT JOIN user u ON p.user_id = u.id 
              $where_clause";
$count_result = mysqli_query($conn, $count_sql);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $limit);

// Lấy danh sách thanh toán
$sql = "SELECT p.*, u.username, u.email 
        FROM payments p 
        LEFT JOIN user u ON p.user_id = u.id
        $where_clause 
        ORDER BY p.created_at DESC
        LIMIT $offset, $limit";
$result = mysqli_query($conn, $sql);

// Tính tổng doanh thu
$revenue_sql = "SELECT 
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_revenue,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count
                FROM payments";
$revenue_result = mysqli_query($conn, $revenue_sql);
$revenue_data = mysqli_fetch_assoc($revenue_result);

// Xác định trang hiện tại
$current_page = 'payments';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý thanh toán - Homeseeker Admin</title>
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
        .filter-card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .table-card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .action-btn {
            padding: .25rem .5rem;
            font-size: .75rem;
        }
        .logout-btn {
            color: #dc3545;
        }
        .logout-btn:hover {
            background: #dc3545;
            color: #fff;
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
        .badge-refunded {
            background-color: #6c757d;
            color: #fff;
        }
        .badge-subscription {
            background-color: #0d6efd;
            color: #fff;
        }
        .badge-promotion {
            background-color: #6f42c1;
            color: #fff;
        }
        .badge-deposit {
            background-color: #fd7e14;
            color: #fff;
        }
        .stat-card {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .pagination {
            justify-content: center;
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
            <h4 class="mb-0">Quản lý thanh toán</h4>
            <div>
                <span>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            </div>
        </div>

        <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="bg-primary text-white p-4 stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Tổng doanh thu</h5>
                            <h2 class="mb-0"><?php echo number_format($revenue_data['total_revenue'] ?? 0); ?> đ</h2>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="bg-success text-white p-4 stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Giao dịch thành công</h5>
                            <h2 class="mb-0"><?php echo $revenue_data['completed_count'] ?? 0; ?></h2>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="bg-warning text-dark p-4 stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Chờ xử lý</h5>
                            <h2 class="mb-0"><?php echo $revenue_data['pending_count'] ?? 0; ?></h2>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="bg-info text-white p-4 stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Doanh thu chờ xử lý</h5>
                            <h2 class="mb-0"><?php echo number_format($revenue_data['pending_revenue'] ?? 0); ?> đ</h2>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-card p-3 mb-4">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="btn-group" role="group">
                        <a href="payments.php" class="btn <?php echo $filter == 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-list"></i> Tất cả
                        </a>
                        <a href="payments.php?filter=pending" class="btn <?php echo $filter == 'pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-clock"></i> Chờ xử lý
                        </a>
                        <a href="payments.php?filter=completed" class="btn <?php echo $filter == 'completed' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-check"></i> Hoàn thành
                        </a>
                        <a href="payments.php?filter=cancelled" class="btn <?php echo $filter == 'cancelled' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-times"></i> Đã hủy
                        </a>
                    </div>
                </div>
                <div class="col-md-6">
                    <form class="d-flex" action="" method="GET">
                        <?php if ($filter != 'all'): ?>
                            <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                        <?php endif; ?>
                        <input type="text" class="form-control me-2" name="search" placeholder="Tìm kiếm giao dịch..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="table-card">
            <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Danh sách giao dịch thanh toán</h5>
                <span class="badge bg-primary"><?php echo $total_records; ?> giao dịch</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Người dùng</th>
                                <th>Gói đăng ký</th>
                                <th>Số tiền</th>
                                <th>Phương thức</th>
                                <th>Mã giao dịch</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td>
                                        <a href="view_user.php?id=<?php echo $row['user_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($row['username'] ?? 'N/A'); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        // Xác định loại gói dựa trên mã giao dịch hoặc thông tin khác
                                        $transaction_id = $row['transaction_id'] ?? '';
                                        $payment_code = $row['payment_code'] ?? '';
                                        
                                        if (strpos($transaction_id, 'standard') !== false || strpos($payment_code, 'standard') !== false) {
                                            echo '<span class="badge badge-subscription">Gói phổ biến</span>';
                                        } elseif (strpos($transaction_id, 'premium') !== false || strpos($payment_code, 'premium') !== false) {
                                            echo '<span class="badge badge-subscription">Gói cao cấp</span>';
                                        } else {
                                            // Kiểm tra số tiền để đoán gói
                                            $amount = $row['amount'] ?? 0;
                                            if ($amount == 199000) {
                                                echo '<span class="badge badge-subscription">Gói phổ biến</span>';
                                            } elseif ($amount == 399000) {
                                                echo '<span class="badge badge-subscription">Gói cao cấp</span>';
                                            } else {
                                                echo '<span class="text-muted">Không xác định</span>';
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo number_format($row['amount']); ?> đ</td>
                                    <td><?php echo htmlspecialchars(ucfirst($row['payment_method'] ?? 'N/A')); ?></td>
                                    <td><?php echo htmlspecialchars($row['transaction_id'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <span class="badge badge-pending">Chờ xử lý</span>
                                        <?php elseif ($row['status'] == 'completed'): ?>
                                            <span class="badge badge-completed">Hoàn thành</span>
                                        <?php elseif ($row['status'] == 'cancelled'): ?>
                                            <span class="badge badge-cancelled">Đã hủy</span>
                                        <?php else: ?>
                                            <span class="badge badge-refunded">Hoàn tiền</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view_payment.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm action-btn" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($row['status'] == 'pending'): ?>
                                                <a href="payments.php?action=approve&id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm action-btn" title="Xác nhận thanh toán" onclick="return confirm('Bạn có chắc muốn xác nhận giao dịch này?');">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="payments.php?action=cancel&id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm action-btn" title="Hủy thanh toán" onclick="return confirm('Bạn có chắc muốn hủy giao dịch này?');">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="payments.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm action-btn" title="Xóa" onclick="return confirm('Bạn có chắc muốn xóa giao dịch này? Thao tác này không thể hoàn tác.');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">Không có giao dịch nào phù hợp với tiêu chí tìm kiếm</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-white p-3">
                <nav aria-label="Page navigation">
                    <ul class="pagination mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($filter) && $filter != 'all' ? '&filter='.$filter : ''; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($filter) && $filter != 'all' ? '&filter='.$filter : ''; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($filter) && $filter != 'all' ? '&filter='.$filter : ''; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
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