<?php
require_once '../../backend/auth/session.php';
require_once '../../backend/config/config.php';

$room_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Kiểm tra quyền sở hữu phòng
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $room_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();

if (!$room) {
    $_SESSION['error_message'] = "Không tìm thấy phòng hoặc bạn không có quyền xóa.";
    header("Location: my_rooms.php");
    exit;
}

// Xử lý xóa khi xác nhận
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    try {
        // Lấy danh sách hình ảnh
        $stmt_images = $conn->prepare("SELECT image_url FROM room_images WHERE room_id = ?");
        $stmt_images->bind_param("i", $room_id);
        $stmt_images->execute();
        $result_images = $stmt_images->get_result();
        
        // Xóa các file hình ảnh
        while ($image = $result_images->fetch_assoc()) {
            $image_path = "../../" . $image['image_url'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Xóa các bản ghi hình ảnh
        $stmt_delete_images = $conn->prepare("DELETE FROM room_images WHERE room_id = ?");
        $stmt_delete_images->bind_param("i", $room_id);
        $stmt_delete_images->execute();
        
        // Xóa các bản ghi yêu thích
        $stmt_delete_favorites = $conn->prepare("DELETE FROM room_favorites WHERE room_id = ?");
        $stmt_delete_favorites->bind_param("i", $room_id);
        $stmt_delete_favorites->execute();
        
        // Xóa thông báo liên quan đến phòng
        $stmt_delete_notifications = $conn->prepare("DELETE FROM notifications WHERE related_id = ? AND (type = 'room_approved' OR type = 'room_rejected')");
        $stmt_delete_notifications->bind_param("i", $room_id);
        $stmt_delete_notifications->execute();
        
        // Xóa phòng
        $stmt_delete_room = $conn->prepare("DELETE FROM rooms WHERE id = ? AND user_id = ?");
        $stmt_delete_room->bind_param("ii", $room_id, $user_id);
        $stmt_delete_room->execute();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success_message'] = "Xóa phòng thành công!";
        header("Location: my_rooms.php");
        exit;
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $conn->rollback();
        $_SESSION['error_message'] = "Lỗi xóa phòng: " . $e->getMessage();
        header("Location: my_rooms.php");
        exit;
    }
}

// Include header
$page_title = "Xóa phòng";
include '../pages/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h2 class="h5 mb-0">Xóa phòng</h2>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Bạn có chắc chắn muốn xóa phòng:</strong>
                        <p class="fw-bold mt-2 mb-1"><?php echo htmlspecialchars($room['title']); ?></p>
                        <p class="mb-0 text-danger">Hành động này không thể hoàn tác!</p>
                    </div>
                    
                    <div class="mt-3">
                        <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($room['address'] . ', ' . $room['district'] . ', ' . $room['city']); ?></p>
                        <p><strong>Giá:</strong> <?php echo number_format($room['price']); ?> đ/tháng</p>
                        <p><strong>Trạng thái:</strong> 
                            <?php if ($room['status'] == 'approved'): ?>
                                <span class="badge bg-success">Đã duyệt</span>
                            <?php elseif ($room['status'] == 'pending'): ?>
                                <span class="badge bg-warning">Chờ duyệt</span>
                            <?php elseif ($room['status'] == 'rejected'): ?>
                                <span class="badge bg-danger">Từ chối</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <form method="post" class="mt-4">
                        <div class="d-flex justify-content-between">
                            <a href="my_rooms.php" class="btn btn-secondary">Hủy</a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash-alt me-2"></i>Xác nhận xóa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../pages/footer.php'; ?>