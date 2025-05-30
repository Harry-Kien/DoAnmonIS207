<?php
session_start();
$page_title = "Quản lý người dùng";
require_once __DIR__ . "/../../backend/config/config.php";

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: /frontend/auth/login.php");
    exit();
}

// Xử lý các hành động
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);
    
    switch ($action) {
        case 'activate':
            $sql = "UPDATE user SET status = 1 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $message = "Người dùng #$id đã được kích hoạt!";
            $message_type = "success";
            break;
            
        case 'deactivate':
            $sql = "UPDATE user SET status = 0 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $message = "Người dùng #$id đã bị vô hiệu hóa!";
            $message_type = "warning";
            break;
            
        case 'delete':
            // Trước khi xóa người dùng, cần kiểm tra và xử lý các phòng của họ
            $sql_check_rooms = "SELECT COUNT(*) as room_count FROM rooms WHERE user_id = ?";
            $stmt_check = $conn->prepare($sql_check_rooms);
            $stmt_check->bind_param("i", $id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $room_count = $result_check->fetch_assoc()['room_count'];
            
            if ($room_count > 0) {
                $message = "Không thể xóa người dùng #$id vì họ đã đăng $room_count phòng!";
                $message_type = "danger";
            } else {
                $sql_delete = "DELETE FROM user WHERE id = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->bind_param("i", $id);
                $stmt_delete->execute();
                $message = "Người dùng #$id đã được xóa thành công!";
                $message_type = "success";
            }
            break;
    }
}

// Lấy danh sách người dùng
$sql = "SELECT * FROM user ORDER BY created_at DESC";
$result = $conn->query($sql);

// Include header
include __DIR__ . "/includes/header.php";
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Quản lý người dùng</h1>
</div>

<?php if (isset($message)): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- Filter & Search -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Tìm kiếm & Lọc</h6>
    </div>
    <div class="card-body">
        <form action="" method="GET" class="row">
            <div class="col-md-9">
                <input type="text" class="form-control" name="search" placeholder="Tìm kiếm theo tên, email...">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Tìm kiếm</button>
            </div>
        </form>
    </div>
</div>

<!-- User List -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Danh sách người dùng</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered datatable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Họ tên</th>
                        <th>Trạng thái</th>
                        <th>Phòng đã đăng</th>
                        <th>Ngày tạo</th>
                        <th>Đăng nhập cuối</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <?php
                            // Đếm số phòng đã đăng
                            $sql_count = "SELECT COUNT(*) as room_count FROM rooms WHERE user_id = ?";
                            $stmt_count = $conn->prepare($sql_count);
                            $stmt_count->bind_param("i", $row['id']);
                            $stmt_count->execute();
                            $result_count = $stmt_count->get_result();
                            $room_count = $result_count->fetch_assoc()['room_count'];
                            ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['full_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if (isset($row['status']) && $row['status'] == 1): ?>
                                        <span class="badge bg-success">Hoạt động</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Vô hiệu hóa</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $room_count; ?></td>
                                <td><?php echo isset($row['created_at']) ? date('d/m/Y', strtotime($row['created_at'])) : 'N/A'; ?></td>
                                <td><?php echo isset($row['last_login']) ? date('d/m/Y H:i', strtotime($row['last_login'])) : 'N/A'; ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="users.php?action=view&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (isset($row['status']) && $row['status'] == 0): ?>
                                            <a href="users.php?action=activate&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success" title="Kích hoạt" onclick="return confirm('Bạn có chắc muốn kích hoạt người dùng này?');">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="users.php?action=deactivate&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Vô hiệu hóa" onclick="return confirm('Bạn có chắc muốn vô hiệu hóa người dùng này?');">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="users.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Xóa" onclick="return confirm('Bạn có chắc muốn xóa người dùng này? Thao tác này không thể hoàn tác.');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">Không có dữ liệu</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>