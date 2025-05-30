<?php
session_start();
$page_title = "Quản lý phòng trọ";
require_once "../config.php";

// Kiểm tra đăng nhập admin
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("Location: index.php");
    exit;
}

// Xử lý các hành động
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);
    
    switch ($action) {
        case 'approve':
            $sql = "UPDATE rooms SET status = 'approved', is_verified = 1 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $message = "Phòng #$id đã được duyệt thành công!";
            $message_type = "success";
            break;
            
        case 'reject':
            $sql = "UPDATE rooms SET status = 'rejected' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $message = "Phòng #$id đã bị từ chối!";
            $message_type = "warning";
            break;
            
        case 'delete':
            // Xóa hình ảnh liên quan trước
            $sql_images = "SELECT image_url FROM room_images WHERE room_id = ?";
            $stmt_images = $conn->prepare($sql_images);
            $stmt_images->bind_param("i", $id);
            $stmt_images->execute();
            $result_images = $stmt_images->get_result();
            
            while ($image = $result_images->fetch_assoc()) {
                if (file_exists("../" . $image['image_url'])) {
                    unlink("../" . $image['image_url']);
                }
            }
            
            // Xóa dữ liệu trong database
            $conn->begin_transaction();
            try {
                // Xóa hình ảnh
                $sql_delete_images = "DELETE FROM room_images WHERE room_id = ?";
                $stmt_delete_images = $conn->prepare($sql_delete_images);
                $stmt_delete_images->bind_param("i", $id);
                $stmt_delete_images->execute();
                
                // Xóa phòng
                $sql_delete_room = "DELETE FROM rooms WHERE id = ?";
                $stmt_delete_room = $conn->prepare($sql_delete_room);
                $stmt_delete_room->bind_param("i", $id);
                $stmt_delete_room->execute();
                
                $conn->commit();
                $message = "Phòng #$id đã được xóa thành công!";
                $message_type = "success";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Lỗi khi xóa phòng: " . $e->getMessage();
                $message_type = "danger";
            }
            break;
            
        case 'view':
            // Chuyển sang tab view và hiển thị thông tin chi tiết
            // Đây chỉ là một flag, sẽ xử lý bên dưới
            break;
    }
}

// Lấy danh sách phòng với bộ lọc
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where_clause = '';

if ($filter == 'pending') {
    $where_clause = "WHERE r.status = 'pending'";
} elseif ($filter == 'approved') {
    $where_clause = "WHERE r.status = 'approved'";
} elseif ($filter == 'rejected') {
    $where_clause = "WHERE r.status = 'rejected'";
}

$sql = "SELECT r.*, u.username 
        FROM rooms r 
        JOIN user u ON r.user_id = u.id 
        $where_clause 
        ORDER BY r.created_at DESC";
$result = $conn->query($sql);

// Include header
include "includes/header.php";
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Quản lý phòng trọ</h1>
</div>

<?php if (isset($message)): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Bộ lọc</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="btn-group" role="group">
                    <a href="rooms.php" class="btn <?php echo $filter == 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">Tất cả</a>
                    <a href="rooms.php?filter=pending" class="btn <?php echo $filter == 'pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">Chờ duyệt</a>
                    <a href="rooms.php?filter=approved" class="btn <?php echo $filter == 'approved' ? 'btn-primary' : 'btn-outline-primary'; ?>">Đã duyệt</a>
                    <a href="rooms.php?filter=rejected" class="btn <?php echo $filter == 'rejected' ? 'btn-primary' : 'btn-outline-primary'; ?>">Đã từ chối</a>
                </div>
            </div>
            <div class="col-md-6">
                <form class="d-flex" action="" method="GET">
                    <input type="text" class="form-control me-2" name="search" placeholder="Tìm kiếm...">
                    <button type="submit" class="btn btn-primary">Tìm</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Room List -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Danh sách phòng</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered datatable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tiêu đề</th>
                        <th>Người đăng</th>
                        <th>Loại phòng</th>
                        <th>Giá</th>
                        <th>Khu vực</th>
                        <th>Trạng thái</th>
                        <th>Ngày đăng</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['type']); ?></td>
                                <td><?php echo number_format($row['price']); ?> đ</td>
                                <td><?php echo htmlspecialchars($row['district'] . ', ' . $row['city']); ?></td>
                                <td>
                                    <?php if ($row['status'] == 'pending'): ?>
                                        <span class="badge bg-warning">Chờ duyệt</span>
                                    <?php elseif ($row['status'] == 'approved'): ?>
                                        <span class="badge bg-success">Đã duyệt</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Từ chối</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="../chi-tiet-phong.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-sm btn-info" title="Xem">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($row['status'] != 'approved'): ?>
                                            <a href="rooms.php?action=approve&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success" title="Duyệt" onclick="return confirm('Bạn có chắc muốn duyệt phòng này?');">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($row['status'] != 'rejected'): ?>
                                            <a href="rooms.php?action=reject&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Từ chối" onclick="return confirm('Bạn có chắc muốn từ chối phòng này?');">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="rooms.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Xóa" onclick="return confirm('Bạn có chắc muốn xóa phòng này? Thao tác này không thể hoàn tác.');">
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

<?php
// Nếu có action=view, hiển thị thông tin chi tiết phòng
if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['id'])) {
    $room_id = intval($_GET['id']);
    $sql_detail = "SELECT r.*, u.username 
                  FROM rooms r 
                  JOIN user u ON r.user_id = u.id 
                  WHERE r.id = ?";
    $stmt_detail = $conn->prepare($sql_detail);
    $stmt_detail->bind_param("i", $room_id);
    $stmt_detail->execute();
    $room_detail = $stmt_detail->get_result()->fetch_assoc();
    
    // Lấy hình ảnh
    $sql_images = "SELECT * FROM room_images WHERE room_id = ?";
    $stmt_images = $conn->prepare($sql_images);
    $stmt_images->bind_param("i", $room_id);
    $stmt_images->execute();
    $images = $stmt_images->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if ($room_detail):
?>
<!-- Room Detail Modal -->
<div class="modal fade" id="roomDetailModal" tabindex="-1" role="dialog" aria-labelledby="roomDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roomDetailModalLabel">Chi tiết phòng #<?php echo $room_detail['id']; ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div id="roomImageCarousel" class="carousel slide" data-ride="carousel">
                            <div class="carousel-inner">
                                <?php if (count($images) > 0): ?>
                                    <?php foreach ($images as $index => $image): ?>
                                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                            <img src="../<?php echo htmlspecialchars($image['image_url']); ?>" class="d-block w-100" alt="Hình ảnh phòng">
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="carousel-item active">
                                        <div class="text-center p-5 bg-light">
                                            <i class="fas fa-image fa-3x mb-3 text-muted"></i>
                                            <p>Không có hình ảnh</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if (count($images) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#roomImageCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#roomImageCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h4><?php echo htmlspecialchars($room_detail['title']); ?></h4>
                        <p class="text-muted"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($room_detail['address'] . ', ' . $room_detail['district'] . ', ' . $room_detail['city']); ?></p>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Loại phòng:</strong> <?php echo htmlspecialchars($room_detail['type']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Giá:</strong> <?php echo number_format($room_detail['price']); ?> đ/tháng
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Diện tích:</strong> <?php echo htmlspecialchars($room_detail['area']); ?> m²
                            </div>
                            <div class="col-md-6">
                                <strong>Số người tối đa:</strong> <?php echo htmlspecialchars($room_detail['max_occupants']); ?> người
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Người đăng:</strong> <?php echo htmlspecialchars($room_detail['username']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Ngày đăng:</strong> <?php echo date('d/m/Y', strtotime($room_detail['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Trạng thái:</strong>
                                <?php if ($room_detail['status'] == 'pending'): ?>
                                    <span class="badge bg-warning">Chờ duyệt</span>
                                <?php elseif ($room_detail['status'] == 'approved'): ?>
                                    <span class="badge bg-success">Đã duyệt</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Từ chối</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Đã xác minh:</strong>
                                <?php if ($room_detail['is_verified']): ?>
                                    <span class="badge bg-success">Có</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Không</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <div class="bg-light p-3 rounded mb-3">
                                <strong>Thông tin liên hệ:</strong>
                                <p class="mb-1"><i class="fas fa-user me-2"></i><?php echo htmlspecialchars($room_detail['contact_name']); ?></p>
                                <p class="mb-0"><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($room_detail['contact_phone']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">Mô tả chi tiết</div>
                            <div class="card-body">
                                <?php echo nl2br(htmlspecialchars($room_detail['description'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">Tiện ích</div>
                            <div class="card-body">
                                <?php if (!empty($room_detail['amenities'])): ?>
                                    <?php echo nl2br(htmlspecialchars($room_detail['amenities'])); ?>
                                <?php else: ?>
                                    <p class="text-muted">Không có thông tin</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">Cơ sở vật chất</div>
                            <div class="card-body">
                                <?php if (!empty($room_detail['facilities'])): ?>
                                    <?php echo nl2br(htmlspecialchars($room_detail['facilities'])); ?>
                                <?php else: ?>
                                    <p class="text-muted">Không có thông tin</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <?php if ($room_detail['status'] != 'approved'): ?>
                    <a href="rooms.php?action=approve&id=<?php echo $room_detail['id']; ?>" class="btn btn-success">Duyệt</a>
                <?php endif; ?>
                <?php if ($room_detail['status'] != 'rejected'): ?>
                    <a href="rooms.php?action=reject&id=<?php echo $room_detail['id']; ?>" class="btn btn-warning">Từ chối</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('#roomDetailModal').modal('show');
});
</script>
<?php
    endif;
}
?>

<?php include "includes/footer.php"; ?>