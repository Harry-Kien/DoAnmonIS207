<?php
// Khởi động session
session_start();

// Kiểm tra người dùng đã đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php?redirect=favorites.php");
    exit();
}

// Tùy chỉnh tiêu đề trang
$page_title = "Phòng trọ yêu thích - Homeseeker";

// Include header
include '../pages/header.php';

// Kết nối CSDL
require_once '../../backend/config/config.php';

// Lấy danh sách phòng yêu thích của người dùng
$user_id = $_SESSION['user_id'];
$query = "SELECT r.*, 
                 (SELECT image_path FROM room_images WHERE room_id = r.id AND is_primary = 1 LIMIT 1) as image_url,
                 d.name as district, c.name as city
          FROM rooms r
          JOIN room_favorites rf ON r.id = rf.room_id
          LEFT JOIN districts d ON r.district_id = d.id
          LEFT JOIN cities c ON r.city_id = c.id
          WHERE rf.user_id = ? AND r.status = 'approved'
          ORDER BY rf.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$favorite_rooms = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="container py-5" style="min-height: calc(100vh - 280px);">
    <h1 class="mb-4">Phòng trọ yêu thích</h1>
    
    <?php if (count($favorite_rooms) > 0): ?>
        <div class="row g-4">
            <?php foreach ($favorite_rooms as $room): ?>
                <div class="col-md-4 mb-4 room-card-container">
                    <div class="card feature-card h-100">
                        <img src="<?php echo !empty($room['image_url']) ? '../assets/' . $room['image_url'] : 'https://via.placeholder.com/400x250'; ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($room['title']); ?>" 
                             style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="badge bg-warning"><?php echo htmlspecialchars($room['type']); ?></span>
                                <span class="text-danger fw-bold"><?php echo number_format($room['price']); ?> đ/tháng</span>
                            </div>
                            <h5 class="card-title">
                                <a href="../room/chi-tiet-phong.php?id=<?php echo $room['id']; ?>" class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($room['title']); ?>
                                </a>
                            </h5>
                            <p class="card-text text-muted small mb-2">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?php echo htmlspecialchars($room['district'] . ', ' . $room['city']); ?>
                            </p>
                            <div class="d-flex justify-content-between text-secondary small">
                                <span><i class="fas fa-vector-square me-1"></i> <?php echo $room['area']; ?>m²</span>
                                <button class="btn btn-sm text-danger remove-favorite" data-room-id="<?php echo $room['id']; ?>" title="Xóa khỏi yêu thích">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info p-5 text-center">
            <i class="far fa-heart fa-3x mb-3"></i>
            <h4>Bạn chưa có phòng trọ yêu thích nào</h4>
            <p class="mb-4">Hãy khám phá các phòng trọ và thêm vào danh sách yêu thích của bạn.</p>
            <a href="../room/phong.php" class="btn btn-warning px-4">Xem phòng trọ</a>
        </div>
    <?php endif; ?>
</div>

<script>
// JavaScript để xử lý xóa khỏi yêu thích
document.addEventListener('DOMContentLoaded', function() {
    const favoriteButtons = document.querySelectorAll('.remove-favorite');
    
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const roomId = this.getAttribute('data-room-id');
            const card = this.closest('.room-card-container');
            
            // Gửi yêu cầu AJAX để xóa khỏi danh sách yêu thích
            fetch('../../backend/user/toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'room_id=' + roomId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.action === 'removed') {
                    // Xóa card khỏi danh sách với hiệu ứng
                    card.style.opacity = 0;
                    setTimeout(() => {
                        card.remove();
                        
                        // Kiểm tra nếu không còn phòng yêu thích
                        const remainingCards = document.querySelectorAll('.room-card-container');
                        if (remainingCards.length === 0) {
                            location.reload(); // Tải lại trang để hiển thị thông báo
                        }
                    }, 500);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });
});
</script>
<?php include '../pages/footer.php'; ?> 