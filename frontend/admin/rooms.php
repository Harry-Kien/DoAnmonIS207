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
            $sql = "UPDATE rooms SET status = 'approved' WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $message = "Phòng #$id đã được duyệt thành công!";
            $message_type = "success";
            break;
            
        case 'reject':
            $sql = "UPDATE rooms SET status = 'rejected' WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $message = "Phòng #$id đã bị từ chối!";
            $message_type = "warning";
            break;
            
        case 'delete':
            // Xóa hình ảnh liên quan trước
            $sql_images = "SELECT image_path FROM room_images WHERE room_id = ?";
            $stmt_images = mysqli_prepare($conn, $sql_images);
            mysqli_stmt_bind_param($stmt_images, "i", $id);
            mysqli_stmt_execute($stmt_images);
            $result_images = mysqli_stmt_get_result($stmt_images);
            
            while ($image = mysqli_fetch_assoc($result_images)) {
                if (file_exists("../../" . $image['image_path'])) {
                    unlink("../../" . $image['image_path']);
                }
            }
            
            // Xóa dữ liệu trong database
            mysqli_begin_transaction($conn);
            try {
                // Xóa hình ảnh
                $sql_delete_images = "DELETE FROM room_images WHERE room_id = ?";
                $stmt_delete_images = mysqli_prepare($conn, $sql_delete_images);
                mysqli_stmt_bind_param($stmt_delete_images, "i", $id);
                mysqli_stmt_execute($stmt_delete_images);
                
                // Xóa phòng
                $sql_delete_room = "DELETE FROM rooms WHERE id = ?";
                $stmt_delete_room = mysqli_prepare($conn, $sql_delete_room);
                mysqli_stmt_bind_param($stmt_delete_room, "i", $id);
                mysqli_stmt_execute($stmt_delete_room);
                
                mysqli_commit($conn);
                $message = "Phòng #$id đã được xóa thành công!";
                $message_type = "success";
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $message = "Lỗi khi xóa phòng: " . $e->getMessage();
                $message_type = "danger";
            }
            break;
    }
}

// Lấy danh sách phòng với bộ lọc
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = '';

if ($filter == 'pending') {
    $where_clause = "WHERE r.status = 'pending'";
} elseif ($filter == 'approved') {
    $where_clause = "WHERE r.status = 'approved'";
} elseif ($filter == 'rejected') {
    $where_clause = "WHERE r.status = 'rejected'";
} else {
    $where_clause = "WHERE 1=1";
}

if (!empty($search)) {
    $where_clause .= " AND (r.title LIKE '%$search%' OR r.description LIKE '%$search%' OR u.username LIKE '%$search%' OR r.address LIKE '%$search%')";
}

// Phân trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Đếm tổng số bản ghi
$count_sql = "SELECT COUNT(*) as total FROM rooms r JOIN user u ON r.user_id = u.id $where_clause";
$count_result = mysqli_query($conn, $count_sql);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $limit);

// Lấy danh sách phòng
$sql = "SELECT r.*, u.username 
        FROM rooms r 
        JOIN user u ON r.user_id = u.id 
        $where_clause 
        ORDER BY r.created_at DESC
        LIMIT $offset, $limit";
$result = mysqli_query($conn, $sql);

// Xác định trang hiện tại
$current_page = 'rooms';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý phòng - Homeseeker Admin</title>
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
        .badge-approved {
            background-color: #198754;
            color: #fff;
        }
        .badge-rejected {
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
            <h4 class="mb-0">Quản lý phòng</h4>
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
                        <a href="rooms.php" class="btn <?php echo $filter == 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-list"></i> Tất cả
                        </a>
                        <a href="rooms.php?filter=pending" class="btn <?php echo $filter == 'pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-clock"></i> Chờ duyệt
                        </a>
                        <a href="rooms.php?filter=approved" class="btn <?php echo $filter == 'approved' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-check"></i> Đã duyệt
                        </a>
                        <a href="rooms.php?filter=rejected" class="btn <?php echo $filter == 'rejected' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-times"></i> Đã từ chối
                        </a>
                    </div>
                </div>
                <div class="col-md-6">
                    <form class="d-flex" action="" method="GET">
                        <?php if ($filter != 'all'): ?>
                            <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                        <?php endif; ?>
                        <input type="text" class="form-control me-2" name="search" placeholder="Tìm kiếm phòng..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Rooms Table -->
        <div class="table-card">
            <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Danh sách phòng</h5>
                <span class="badge bg-primary"><?php echo $total_records; ?> phòng</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Tiêu đề</th>
                                <th>Người đăng</th>
                                <th>Loại</th>
                                <th>Giá</th>
                                <th>Địa chỉ</th>
                                <th>Trạng thái</th>
                                <th>Ngày đăng</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['type'] ?? 'Không xác định'); ?></td>
                                    <td><?php echo number_format($row['price']); ?> đ</td>
                                    <td><?php echo htmlspecialchars($row['address'] ?? ''); ?></td>
                                    <td>
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <span class="badge badge-pending">Chờ duyệt</span>
                                        <?php elseif ($row['status'] == 'approved'): ?>
                                            <span class="badge badge-approved">Đã duyệt</span>
                                        <?php else: ?>
                                            <span class="badge badge-rejected">Từ chối</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view_room.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm action-btn" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($row['status'] != 'approved'): ?>
                                                <a href="rooms.php?action=approve&id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm action-btn" title="Duyệt" onclick="return confirm('Bạn có chắc muốn duyệt phòng này?');">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($row['status'] != 'rejected'): ?>
                                                <a href="rooms.php?action=reject&id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm action-btn" title="Từ chối" onclick="return confirm('Bạn có chắc muốn từ chối phòng này?');">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="rooms.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm action-btn" title="Xóa" onclick="return confirm('Bạn có chắc muốn xóa phòng này? Thao tác này không thể hoàn tác.');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">Không có phòng nào phù hợp với tiêu chí tìm kiếm</td>
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