<?php
// Khởi động session
session_start();

// Kiểm tra nếu người dùng chưa đăng nhập, chuyển hướng về trang đăng nhập
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/login.php");
    exit;
}

// Bao gồm file cấu hình và notification handler
require_once "../../backend/config/config.php";
require_once "../../backend/notifications/notification_handler.php";

// Khởi tạo notification handler
$notificationHandler = new NotificationHandler($conn);

// Xử lý đánh dấu tất cả là đã đọc
if (isset($_POST['mark_all_read'])) {
    $sql = "UPDATE notifications SET is_read = TRUE WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    
    // Chuyển hướng để tránh gửi lại form khi refresh
    header("Location: all.php");
    exit;
}

// Xử lý xóa thông báo
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notification_id = intval($_GET['delete']);
    $notificationHandler->deleteNotification($notification_id, $_SESSION['user_id']);
    
    // Chuyển hướng để tránh gửi lại form khi refresh
    header("Location: all.php");
    exit;
}

// Lấy tất cả thông báo của người dùng
$notifications = $notificationHandler->getUserNotifications($_SESSION['user_id'], 100); // Lấy tối đa 100 thông báo
$unreadCount = $notificationHandler->getUnreadCount($_SESSION['user_id']);

// Tùy chỉnh tiêu đề trang
$page_title = "Tất cả thông báo - Homeseeker";

// Include header
include '../pages/header.php';
?>

<div class="container my-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0">Tất cả thông báo</h5>
                    <div>
                        <?php if ($unreadCount > 0): ?>
                            <form method="post" class="d-inline">
                                <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-check-double me-1"></i>Đánh dấu tất cả đã đọc
                                </button>
                            </form>
                        <?php endif; ?>
                        <a href="../pages/welcome.php" class="btn btn-sm btn-outline-secondary ms-2">
                            <i class="fas fa-arrow-left me-1"></i>Quay lại
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (count($notifications) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notification): ?>
                                <?php 
                                $icon = 'bell';
                                $color = 'primary';
                                
                                // Xác định icon và màu sắc dựa trên loại thông báo
                                switch ($notification['type']) {
                                    case 'room_approved':
                                        $icon = 'check-circle';
                                        $color = 'success';
                                        break;
                                    case 'room_rejected':
                                        $icon = 'times-circle';
                                        $color = 'danger';
                                        break;
                                    case 'comment_received':
                                        $icon = 'comment';
                                        $color = 'info';
                                        break;
                                    case 'viewing_request':
                                        $icon = 'calendar-check';
                                        $color = 'warning';
                                        break;
                                    case 'system_maintenance':
                                        $icon = 'wrench';
                                        $color = 'secondary';
                                        break;
                                    case 'new_feature':
                                        $icon = 'star';
                                        $color = 'warning';
                                        break;
                                    case 'promotion':
                                        $icon = 'gift';
                                        $color = 'danger';
                                        break;
                                }
                                
                                // Xác định URL khi click vào thông báo
                                $url = "#";
                                if (!empty($notification['related_id'])) {
                                    if ($notification['type'] == 'room_approved' || $notification['type'] == 'room_rejected') {
                                        $url = "../room/chi-tiet-phong.php?id=" . $notification['related_id'];
                                    } elseif ($notification['type'] == 'comment_received') {
                                        $url = "../room/chi-tiet-phong.php?id=" . $notification['related_id'] . "#comments";
                                    } elseif ($notification['type'] == 'viewing_request') {
                                        $url = "../room/my_rooms.php";
                                    }
                                }
                                ?>
                                <div class="list-group-item notification-item <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>" 
                                     data-id="<?php echo $notification['id']; ?>"
                                     data-type="<?php echo $notification['type']; ?>"
                                     data-related-id="<?php echo $notification['related_id']; ?>">
                                    <div class="d-flex">
                                        <div class="me-3 pt-1">
                                            <i class="fas fa-<?php echo $icon; ?> text-<?php echo $color; ?> fa-lg"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                                <div>
                                                    <?php if (!$notification['is_read']): ?>
                                                        <span class="badge bg-primary me-2">Mới</span>
                                                    <?php endif; ?>
                                                    <small class="text-muted">
                                                        <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <a href="<?php echo $url; ?>" class="btn btn-sm btn-link p-0 mark-as-read" data-id="<?php echo $notification['id']; ?>">
                                                    Xem chi tiết
                                                </a>
                                                <a href="all.php?delete=<?php echo $notification['id']; ?>" class="text-danger" onclick="return confirm('Bạn có chắc muốn xóa thông báo này?');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-bell text-muted mb-3" style="font-size: 3rem;"></i>
                            <p class="text-muted">Bạn không có thông báo nào</p>
                            <a href="../pages/welcome.php" class="btn btn-outline-primary">Quay lại trang cá nhân</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Đánh dấu thông báo đã đọc khi click vào nút "Xem chi tiết"
    document.querySelectorAll('.mark-as-read').forEach(item => {
        item.addEventListener('click', function(e) {
            const notificationId = this.getAttribute('data-id');
            
            fetch('../../backend/notifications/mark_as_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'notification_id=' + notificationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Xóa badge "Mới"
                    const badge = this.closest('.notification-item').querySelector('.badge');
                    if (badge) {
                        badge.remove();
                    }
                    
                    // Xóa highlight
                    this.closest('.notification-item').classList.remove('bg-light');
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
});
</script>

<?php
// Include footer
include '../pages/footer.php';
?> 