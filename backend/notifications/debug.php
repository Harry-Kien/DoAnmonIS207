<?php
// Kết nối database
require_once "../config/config.php";
require_once "notification_handler.php";

// Kiểm tra xem bảng notifications có tồn tại không
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($result->num_rows > 0) {
    $table_exists = true;
}

// Kiểm tra các thông báo trong hệ thống
$notifications_count = 0;
$unread_count = 0;
if ($table_exists) {
    $result = $conn->query("SELECT COUNT(*) as count FROM notifications");
    $notifications_count = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = FALSE");
    $unread_count = $result->fetch_assoc()['count'];
}

// Kiểm tra các thông báo theo loại
$notification_types = [];
if ($table_exists) {
    $result = $conn->query("SELECT type, COUNT(*) as count FROM notifications GROUP BY type");
    while ($row = $result->fetch_assoc()) {
        $notification_types[$row['type']] = $row['count'];
    }
}

// Kiểm tra thông báo phê duyệt phòng
$approve_notifications = [];
if ($table_exists) {
    $result = $conn->query("SELECT n.*, u.username, r.title as room_title 
                           FROM notifications n 
                           JOIN user u ON n.user_id = u.id 
                           LEFT JOIN rooms r ON n.related_id = r.id 
                           WHERE n.type = 'room_approved' OR n.type = 'room_rejected'
                           ORDER BY n.created_at DESC
                           LIMIT 10");
    while ($row = $result->fetch_assoc()) {
        $approve_notifications[] = $row;
    }
}

// Hiển thị thông tin debug
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Thông Báo - Homeseeker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            padding: 20px;
        }
        .debug-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card-header {
            font-weight: 600;
            background-color: #f8f9fa;
        }
        .status-ok {
            color: #28a745;
        }
        .status-error {
            color: #dc3545;
        }
        .badge {
            font-size: 85%;
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <h1 class="mb-4">Debug Hệ Thống Thông Báo</h1>
        
        <!-- Trạng thái bảng -->
        <div class="card">
            <div class="card-header">
                Trạng thái bảng notifications
            </div>
            <div class="card-body">
                <?php if ($table_exists): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        Bảng notifications đã tồn tại trong cơ sở dữ liệu.
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Bảng notifications chưa tồn tại trong cơ sở dữ liệu.
                        <div class="mt-2">
                            <a href="create_notifications_table.php" class="btn btn-primary btn-sm">Tạo bảng notifications</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <p><strong>Tổng số thông báo:</strong> <?php echo $notifications_count; ?></p>
                    <p><strong>Thông báo chưa đọc:</strong> <?php echo $unread_count; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Thống kê theo loại -->
        <div class="card">
            <div class="card-header">
                Thống kê thông báo theo loại
            </div>
            <div class="card-body">
                <?php if (empty($notification_types)): ?>
                    <p class="text-muted">Chưa có thông báo nào trong hệ thống.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Loại thông báo</th>
                                    <th>Số lượng</th>
                                    <th>Mô tả</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $type_descriptions = [
                                    'room_approved' => 'Phòng được duyệt',
                                    'room_rejected' => 'Phòng bị từ chối',
                                    'comment_received' => 'Nhận được bình luận mới',
                                    'viewing_request' => 'Có người đặt lịch xem phòng',
                                    'favorite_updated' => 'Phòng yêu thích có cập nhật',
                                    'listing_expiring' => 'Tin đăng sắp hết hạn',
                                    'system_maintenance' => 'Thông báo bảo trì',
                                    'new_feature' => 'Tính năng mới',
                                    'promotion' => 'Khuyến mãi'
                                ];
                                
                                foreach ($notification_types as $type => $count): 
                                    $description = isset($type_descriptions[$type]) ? $type_descriptions[$type] : 'Không xác định';
                                ?>
                                <tr>
                                    <td><code><?php echo $type; ?></code></td>
                                    <td><?php echo $count; ?></td>
                                    <td><?php echo $description; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Thông báo phê duyệt phòng -->
        <div class="card">
            <div class="card-header">
                Thông báo phê duyệt phòng gần đây
            </div>
            <div class="card-body">
                <?php if (empty($approve_notifications)): ?>
                    <p class="text-muted">Chưa có thông báo phê duyệt phòng nào.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Người dùng</th>
                                    <th>Loại</th>
                                    <th>Tiêu đề</th>
                                    <th>Phòng</th>
                                    <th>Thời gian</th>
                                    <th>Đã đọc</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($approve_notifications as $notification): ?>
                                <tr>
                                    <td><?php echo $notification['id']; ?></td>
                                    <td><?php echo htmlspecialchars($notification['username']); ?></td>
                                    <td>
                                        <?php if ($notification['type'] == 'room_approved'): ?>
                                            <span class="badge bg-success">Duyệt</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Từ chối</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($notification['title']); ?></td>
                                    <td><?php echo htmlspecialchars($notification['room_title'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?></td>
                                    <td>
                                        <?php if ($notification['is_read']): ?>
                                            <i class="fas fa-check-circle text-success"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-danger"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Các công cụ -->
        <div class="card">
            <div class="card-header">
                Công cụ
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <a href="add_demo_notifications.php" class="btn btn-primary w-100">
                            <i class="fas fa-plus-circle me-2"></i>Thêm thông báo mẫu
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="create_notifications_table.php" class="btn btn-info w-100">
                            <i class="fas fa-table me-2"></i>Tạo bảng notifications
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="../../frontend/notifications/all.php" class="btn btn-success w-100">
                            <i class="fas fa-bell me-2"></i>Xem tất cả thông báo
                        </a>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="../../frontend/pages/welcome.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Quay lại trang cá nhân
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 