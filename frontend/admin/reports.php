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
        case 'resolve':
            $sql = "UPDATE reports SET status = 'resolved', resolved_at = NOW(), resolved_by = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $id);
            mysqli_stmt_execute($stmt);
            $message = "Báo cáo #$id đã được đánh dấu là đã xử lý!";
            $message_type = "success";
            break;
            
        case 'delete':
            $sql = "DELETE FROM reports WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $message = "Báo cáo #$id đã được xóa thành công!";
            $message_type = "success";
            break;
    }
}

// Tạo bảng reports nếu chưa tồn tại
$check_table_sql = "SHOW TABLES LIKE 'reports'";
$check_table_result = mysqli_query($conn, $check_table_sql);

if (mysqli_num_rows($check_table_result) == 0) {
    $create_table_sql = "CREATE TABLE reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        room_id INT NOT NULL,
        report_type VARCHAR(50) NOT NULL,
        description TEXT NOT NULL,
        status ENUM('pending', 'resolved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        resolved_at TIMESTAMP NULL,
        resolved_by INT NULL,
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
    )";
    
    if (mysqli_query($conn, $create_table_sql)) {
        // Thêm dữ liệu mẫu nếu có user và room
        $check_users = mysqli_query($conn, "SELECT id FROM user LIMIT 3");
        $check_rooms = mysqli_query($conn, "SELECT id FROM rooms LIMIT 3");
        
        if (mysqli_num_rows($check_users) > 0 && mysqli_num_rows($check_rooms) > 0) {
            $users = [];
            $rooms = [];
            
            while ($row = mysqli_fetch_assoc($check_users)) {
                $users[] = $row['id'];
            }
            
            while ($row = mysqli_fetch_assoc($check_rooms)) {
                $rooms[] = $row['id'];
            }
            
            if (count($users) > 0 && count($rooms) > 0) {
                $sample_data_sql = "INSERT INTO reports (user_id, room_id, report_type, description) VALUES 
                    ({$users[0]}, {$rooms[0]}, 'spam', 'Nội dung quảng cáo không liên quan'),
                    ({$users[min(1, count($users)-1)]}, {$rooms[min(1, count($rooms)-1)]}, 'inappropriate', 'Hình ảnh không phù hợp'),
                    ({$users[min(2, count($users)-1)]}, {$rooms[min(2, count($rooms)-1)]}, 'fraud', 'Thông tin giả mạo về phòng trọ')";
                mysqli_query($conn, $sample_data_sql);
            }
        }
    }
}

// Lấy danh sách báo cáo vi phạm với bộ lọc
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = '';

if ($filter == 'pending') {
    $where_clause = "WHERE r.status = 'pending'";
} elseif ($filter == 'resolved') {
    $where_clause = "WHERE r.status = 'resolved'";
} elseif ($filter == 'rejected') {
    $where_clause = "WHERE r.status = 'rejected'";
} else {
    $where_clause = "WHERE 1=1";
}

if (!empty($search)) {
    $where_clause .= " AND (r.description LIKE '%$search%' OR u.username LIKE '%$search%' OR rm.title LIKE '%$search%')";
}

// Phân trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Đếm tổng số bản ghi
$count_sql = "SELECT COUNT(*) as total FROM reports r 
              LEFT JOIN user u ON r.user_id = u.id 
              LEFT JOIN rooms rm ON r.room_id = rm.id 
              $where_clause";
$count_result = mysqli_query($conn, $count_sql);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $limit);

// Lấy danh sách báo cáo
$sql = "SELECT r.*, u.username as reporter_name, rm.title as room_title, 
               admin.username as admin_name
        FROM reports r 
        LEFT JOIN user u ON r.user_id = u.id 
        LEFT JOIN rooms rm ON r.room_id = rm.id
        LEFT JOIN user admin ON r.resolved_by = admin.id
        $where_clause 
        ORDER BY r.created_at DESC
        LIMIT $offset, $limit";
$result = mysqli_query($conn, $sql);

// Xác định trang hiện tại
$current_page = 'reports';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý báo cáo vi phạm - Homeseeker Admin</title>
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
            <h4 class="mb-0">Quản lý báo cáo vi phạm</h4>
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

        <!-- Filter Section -->
        <div class="filter-card p-3 mb-4">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="btn-group" role="group">
                        <a href="reports.php" class="btn <?php echo $filter == 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-list"></i> Tất cả
                        </a>
                        <a href="reports.php?filter=pending" class="btn <?php echo $filter == 'pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-clock"></i> Chờ xử lý
                        </a>
                        <a href="reports.php?filter=resolved" class="btn <?php echo $filter == 'resolved' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-check"></i> Đã xử lý
                        </a>
                        <a href="reports.php?filter=rejected" class="btn <?php echo $filter == 'rejected' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-times"></i> Đã từ chối
                        </a>
                    </div>
                </div>
                <div class="col-md-6">
                    <form class="d-flex" action="" method="GET">
                        <?php if ($filter != 'all'): ?>
                            <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                        <?php endif; ?>
                        <input type="text" class="form-control me-2" name="search" placeholder="Tìm kiếm báo cáo..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reports Table -->
        <div class="table-card">
            <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Danh sách báo cáo vi phạm</h5>
                <span class="badge bg-primary"><?php echo $total_records; ?> báo cáo</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Loại báo cáo</th>
                                <th>Người báo cáo</th>
                                <th>Phòng trọ</th>
                                <th>Mô tả</th>
                                <th>Trạng thái</th>
                                <th>Ngày báo cáo</th>
                                <th>Người xử lý</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td>
                                        <?php 
                                            $report_type = htmlspecialchars($row['report_type']);
                                            $badge_class = '';
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
                                    </td>
                                    <td><?php echo htmlspecialchars($row['reporter_name']); ?></td>
                                    <td>
                                        <a href="view_room.php?id=<?php echo $row['room_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($row['room_title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . (strlen($row['description']) > 50 ? '...' : ''); ?></td>
                                    <td>
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <span class="badge badge-pending">Chờ xử lý</span>
                                        <?php elseif ($row['status'] == 'resolved'): ?>
                                            <span class="badge badge-resolved">Đã xử lý</span>
                                        <?php else: ?>
                                            <span class="badge badge-rejected">Từ chối</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td><?php echo $row['admin_name'] ? htmlspecialchars($row['admin_name']) : 'Chưa xử lý'; ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view_report.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm action-btn" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($row['status'] == 'pending'): ?>
                                                <a href="reports.php?action=resolve&id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm action-btn" title="Đánh dấu đã xử lý" onclick="return confirm('Bạn có chắc muốn đánh dấu báo cáo này là đã xử lý?');">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="reports.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm action-btn" title="Xóa" onclick="return confirm('Bạn có chắc muốn xóa báo cáo này? Thao tác này không thể hoàn tác.');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">Không có báo cáo vi phạm nào phù hợp với tiêu chí tìm kiếm</td>
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