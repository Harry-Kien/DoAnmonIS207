<?php
session_start();
require_once "../../backend/config/config.php";

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

// Kiểm tra ID phòng
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: rooms.php");
    exit();
}

$room_id = intval($_GET['id']);

// Lấy thông tin chi tiết phòng
$sql = "SELECT r.*, u.username, u.email, u.phone 
        FROM rooms r 
        JOIN user u ON r.user_id = u.id 
        WHERE r.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $room_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: rooms.php?error=Không tìm thấy phòng");
    exit();
}

$room = mysqli_fetch_assoc($result);

// Lấy danh sách hình ảnh của phòng
$sql_images = "SELECT image_path FROM room_images WHERE room_id = ?";
$stmt_images = mysqli_prepare($conn, $sql_images);
mysqli_stmt_bind_param($stmt_images, "i", $room_id);
mysqli_stmt_execute($stmt_images);
$images_result = mysqli_stmt_get_result($stmt_images);
$images = [];
while ($image = mysqli_fetch_assoc($images_result)) {
    // Lấy tên file từ đường dẫn đầy đủ
    $filename = basename($image['image_path']);
    $images[] = ['image_path' => $filename];
}

// Xử lý các hành động
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'approve':
            $sql = "UPDATE rooms SET status = 'approved' WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $room_id);
            mysqli_stmt_execute($stmt);
            header("Location: view_room.php?id=$room_id&success=Phòng đã được duyệt thành công");
            exit();
            break;
            
        case 'reject':
            $sql = "UPDATE rooms SET status = 'rejected' WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $room_id);
            mysqli_stmt_execute($stmt);
            header("Location: view_room.php?id=$room_id&success=Phòng đã bị từ chối");
            exit();
            break;
            
        case 'delete':
            // Xóa hình ảnh liên quan trước
            $sql_images = "SELECT image_path FROM room_images WHERE room_id = ?";
            $stmt_images = mysqli_prepare($conn, $sql_images);
            mysqli_stmt_bind_param($stmt_images, "i", $room_id);
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
                mysqli_stmt_bind_param($stmt_delete_images, "i", $room_id);
                mysqli_stmt_execute($stmt_delete_images);
                
                // Xóa phòng
                $sql_delete_room = "DELETE FROM rooms WHERE id = ?";
                $stmt_delete_room = mysqli_prepare($conn, $sql_delete_room);
                mysqli_stmt_bind_param($stmt_delete_room, "i", $room_id);
                mysqli_stmt_execute($stmt_delete_room);
                
                mysqli_commit($conn);
                header("Location: rooms.php?success=Phòng đã được xóa thành công");
                exit();
            } catch (Exception $e) {
                mysqli_rollback($conn);
                header("Location: view_room.php?id=$room_id&error=Lỗi khi xóa phòng: " . $e->getMessage());
                exit();
            }
            break;
    }
}

// Xác định trang hiện tại
$current_page = 'rooms';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết phòng - Homeseeker Admin</title>
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
        .detail-card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .room-images {
            position: relative;
            height: 400px;
            overflow: hidden;
            border-radius: 5px;
        }
        .room-images img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .room-thumbnail {
            width: 100px;
            height: 70px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 3px;
            transition: all 0.2s;
        }
        .room-thumbnail:hover, .room-thumbnail.active {
            border-color: #0d6efd;
        }
        .detail-list {
            list-style: none;
            padding-left: 0;
        }
        .detail-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            display: flex;
        }
        .detail-list li:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            width: 150px;
            color: #495057;
        }
        .detail-value {
            flex: 1;
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
        .action-btn {
            padding: .5rem 1rem;
            font-size: .875rem;
        }
        .logout-btn {
            color: #dc3545;
        }
        .logout-btn:hover {
            background: #dc3545;
            color: #fff;
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
            .room-images {
                height: 250px;
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
                <a href="rooms.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
                <h4 class="mb-0 d-inline-block">Chi tiết phòng #<?php echo $room['id']; ?></h4>
            </div>
            <div>
                <span>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_GET['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_GET['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Room Details -->
        <div class="row">
            <!-- Room Images -->
            <div class="col-md-7 mb-4">
                <div class="detail-card p-3">
                    <h5 class="mb-3">Hình ảnh</h5>
                    <div class="room-images mb-3">
                        <?php if (count($images) > 0): ?>
                            <img src="../../frontend/assets/uploads/rooms/<?php echo htmlspecialchars($images[0]['image_path']); ?>" alt="Ảnh phòng" id="mainImage" class="img-fluid">
                        <?php else: ?>
                            <div class="d-flex justify-content-center align-items-center h-100 bg-light">
                                <p class="text-muted">Không có hình ảnh</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($images) > 1): ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($images as $index => $image): ?>
                            <img src="../../frontend/assets/uploads/rooms/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                 alt="Thumbnail <?php echo $index + 1; ?>" 
                                 class="room-thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                 onclick="changeMainImage('../../frontend/assets/uploads/rooms/<?php echo htmlspecialchars($image['image_path']); ?>', this)">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Room Info -->
            <div class="col-md-5 mb-4">
                <div class="detail-card p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Thông tin phòng</h5>
                        <?php if ($room['status'] == 'pending'): ?>
                            <span class="badge badge-pending">Chờ duyệt</span>
                        <?php elseif ($room['status'] == 'approved'): ?>
                            <span class="badge badge-approved">Đã duyệt</span>
                        <?php else: ?>
                            <span class="badge badge-rejected">Từ chối</span>
                        <?php endif; ?>
                    </div>
                    
                    <h4 class="mb-3"><?php echo htmlspecialchars($room['title']); ?></h4>
                    
                    <ul class="detail-list">
                        <li>
                            <span class="detail-label">Giá thuê:</span>
                            <span class="detail-value"><?php echo number_format($room['price']); ?> đ/tháng</span>
                        </li>
                        <li>
                            <span class="detail-label">Loại phòng:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($room['type'] ?? 'Không xác định'); ?></span>
                        </li>
                        <li>
                            <span class="detail-label">Diện tích:</span>
                            <span class="detail-value"><?php echo $room['area']; ?> m²</span>
                        </li>
                        <li>
                            <span class="detail-label">Địa chỉ:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($room['address'] ?? 'Không xác định'); ?></span>
                        </li>
                        <li>
                            <span class="detail-label">Người đăng:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($room['username']); ?></span>
                        </li>
                        <li>
                            <span class="detail-label">Liên hệ:</span>
                            <span class="detail-value">
                                <?php echo htmlspecialchars($room['phone'] ?? 'Không có'); ?> | 
                                <?php echo htmlspecialchars($room['email']); ?>
                            </span>
                        </li>
                        <li>
                            <span class="detail-label">Ngày đăng:</span>
                            <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($room['created_at'])); ?></span>
                        </li>
                    </ul>
                    
                    <div class="mt-4">
                        <form method="post" class="d-flex gap-2">
                            <?php if ($room['status'] != 'approved'): ?>
                                <button type="submit" name="action" value="approve" class="btn btn-success action-btn flex-grow-1">
                                    <i class="fas fa-check me-1"></i> Duyệt phòng
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($room['status'] != 'rejected'): ?>
                                <button type="submit" name="action" value="reject" class="btn btn-warning action-btn flex-grow-1">
                                    <i class="fas fa-times me-1"></i> Từ chối
                                </button>
                            <?php endif; ?>
                            
                            <button type="submit" name="action" value="delete" class="btn btn-danger action-btn flex-grow-1" onclick="return confirm('Bạn có chắc muốn xóa phòng này? Thao tác này không thể hoàn tác.');">
                                <i class="fas fa-trash me-1"></i> Xóa
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Room Description -->
            <div class="col-12 mb-4">
                <div class="detail-card p-3">
                    <h5 class="mb-3">Mô tả chi tiết</h5>
                    <div class="room-description">
                        <?php echo nl2br(htmlspecialchars($room['description'] ?? 'Không có mô tả')); ?>
                    </div>
                </div>
            </div>
            
            <!-- Room Amenities -->
            <div class="col-12 mb-4">
                <div class="detail-card p-3">
                    <h5 class="mb-3">Tiện ích</h5>
                    <div class="row">
                        <?php
                        $amenities = [
                            'wifi' => 'Wi-Fi',
                            'air_conditioner' => 'Điều hòa',
                            'refrigerator' => 'Tủ lạnh',
                            'washing_machine' => 'Máy giặt',
                            'parking' => 'Chỗ để xe',
                            'security' => 'An ninh 24/7',
                            'private_bathroom' => 'Phòng tắm riêng',
                            'kitchen' => 'Nhà bếp'
                        ];
                        
                        foreach ($amenities as $key => $name):
                            $has_amenity = isset($room[$key]) && $room[$key] == 1;
                        ?>
                            <div class="col-md-3 col-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-<?php echo $has_amenity ? 'check text-success' : 'times text-danger'; ?> me-2"></i>
                                    <span><?php echo $name; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
<script>
    function changeMainImage(src, thumbnail) {
        document.getElementById('mainImage').src = src;
        
        // Remove active class from all thumbnails
        document.querySelectorAll('.room-thumbnail').forEach(thumb => {
            thumb.classList.remove('active');
        });
        
        // Add active class to clicked thumbnail
        thumbnail.classList.add('active');
    }
</script>
</body>
</html> 