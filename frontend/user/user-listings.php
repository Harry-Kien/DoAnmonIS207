<?php
// Khởi động session
session_start();

// Kết nối database
require_once(__DIR__ . '/../../backend/config/config.php');

// Lấy user_id từ URL
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Kiểm tra user_id hợp lệ
if ($user_id <= 0) {
    header("Location: ../pages/welcome.php");
    exit();
}

// Thiết lập phân trang
$items_per_page = 9; // Số phòng hiển thị trên mỗi trang
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$current_page = max(1, $current_page); // Đảm bảo page không nhỏ hơn 1

// Lấy thông tin người dùng
$sql_user = "SELECT username, avatar, created_at FROM user WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();

if ($user_result->num_rows == 0) {
    header("Location: ../pages/welcome.php");
    exit();
}

$user_info = $user_result->fetch_assoc();

// Đếm tổng số phòng
$sql_count = "SELECT COUNT(*) as total FROM rooms WHERE user_id = ? AND status = 'approved'";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("i", $user_id);
$stmt_count->execute();
$total_rooms = $stmt_count->get_result()->fetch_assoc()['total'];

// Tính toán phân trang
$total_pages = ceil($total_rooms / $items_per_page);
$current_page = min($current_page, $total_pages); // Đảm bảo page không vượt quá tổng số trang
$offset = ($current_page - 1) * $items_per_page;

// Lấy danh sách phòng của người dùng với phân trang
$sql_rooms = "SELECT r.*, 
    (SELECT image_path FROM room_images WHERE room_id = r.id AND is_primary = 1 LIMIT 1) as primary_image,
    d.name as district_name, 
    c.name as city_name
    FROM rooms r
    LEFT JOIN districts d ON r.district_id = d.id
    LEFT JOIN cities c ON r.city_id = c.id
    WHERE r.user_id = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC
    LIMIT ? OFFSET ?";

$stmt_rooms = $conn->prepare($sql_rooms);
$stmt_rooms->bind_param("iii", $user_id, $items_per_page, $offset);
$stmt_rooms->execute();
$rooms = $stmt_rooms->get_result()->fetch_all(MYSQLI_ASSOC);

// Đặt tiêu đề trang
$page_title = "Tin đăng của " . $user_info['username'] . " - Homeseeker";

// Include header
include '../pages/header.php';
?>

<div class="container py-4">
    <!-- Thông tin người dùng -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <img src="<?php echo !empty($user_info['avatar']) ? '../assets/avatars/' . htmlspecialchars($user_info['avatar']) : 'https://via.placeholder.com/80'; ?>" 
                     alt="Avatar" class="rounded-circle me-4" width="80" height="80">
                <div>
                    <h4 class="mb-2"><?php echo htmlspecialchars($user_info['username']); ?></h4>
                    <p class="text-muted mb-0">
                        <i class="fas fa-user-clock me-2"></i>
                        Thành viên từ <?php echo date('m/Y', strtotime($user_info['created_at'])); ?>
                    </p>
                    <p class="text-muted mb-0">
                        <i class="fas fa-home me-2"></i>
                        <?php echo $total_rooms; ?> tin đăng
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Danh sách tin đăng -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Tin đăng của <?php echo htmlspecialchars($user_info['username']); ?></h4>
        <?php if ($total_rooms > 0): ?>
            <p class="text-muted mb-0">Hiển thị <?php echo ($offset + 1); ?>-<?php echo min($offset + $items_per_page, $total_rooms); ?> của <?php echo $total_rooms; ?> tin</p>
        <?php endif; ?>
    </div>
    
    <?php if ($total_rooms > 0): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-4">
            <?php foreach ($rooms as $room): ?>
                <div class="col">
                    <div class="card h-100">
                        <img src="<?php echo !empty($room['primary_image']) ? '../assets/' . htmlspecialchars($room['primary_image']) : 'https://via.placeholder.com/300x200'; ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($room['title']); ?>"
                             style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <a href="../room/chi-tiet-phong.php?id=<?php echo $room['id']; ?>" class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($room['title']); ?>
                                </a>
                            </h5>
                            <p class="card-text text-danger fw-bold mb-2"><?php echo number_format($room['price']); ?> đ/tháng</p>
                            <p class="card-text text-muted small mb-2">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?php echo htmlspecialchars($room['district_name'] . ', ' . $room['city_name']); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-warning"><?php echo htmlspecialchars($room['type']); ?></span>
                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($room['created_at'])); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Phân trang -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Phân trang">
                <ul class="pagination justify-content-center">
                    <!-- Nút Previous -->
                    <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?user_id=<?php echo $user_id; ?>&page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>

                    <?php
                    // Hiển thị các nút số trang
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);

                    if ($start_page > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?user_id=' . $user_id . '&page=1">1</a></li>';
                        if ($start_page > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }

                    for ($i = $start_page; $i <= $end_page; $i++) {
                        echo '<li class="page-item ' . ($i == $current_page ? 'active' : '') . '">
                                <a class="page-link" href="?user_id=' . $user_id . '&page=' . $i . '">' . $i . '</a>
                              </li>';
                    }

                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="?user_id=' . $user_id . '&page=' . $total_pages . '">' . $total_pages . '</a></li>';
                    }
                    ?>

                    <!-- Nút Next -->
                    <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?user_id=<?php echo $user_id; ?>&page=<?php echo $current_page + 1; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-home fa-3x text-muted mb-3"></i>
            <p class="text-muted">Chưa có tin đăng nào</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../pages/footer.php'; ?> 