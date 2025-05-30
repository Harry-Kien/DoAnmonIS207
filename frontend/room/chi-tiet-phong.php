<?php
// Khởi động session
session_start();

// Kết nối database
require_once(__DIR__ . '/../../backend/config/config.php');

// Hàm PHP tính thời gian đăng tin
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    if ($diff < 60) return $diff . ' giây trước';
    if ($diff < 3600) return floor($diff / 60) . ' phút trước';
    if ($diff < 86400) return floor($diff / 3600) . ' giờ trước';
    if ($diff < 2592000) return floor($diff / 86400) . ' ngày trước';
    if ($diff < 31536000) return floor($diff / 2592000) . ' tháng trước';
    return floor($diff / 31536000) . ' năm trước';
}

// Lấy ID phòng từ URL
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Kiểm tra ID hợp lệ
if ($room_id <= 0) {
    header("Location: phong.php");
    exit();
}

// Truy vấn thông tin phòng
$sql = "SELECT r.*, 
            u.username as owner_username, 
            u.avatar as owner_avatar, 
            d.name as district_name, 
            c.name as city_name 
        FROM rooms r
        LEFT JOIN user u ON r.user_id = u.id
        LEFT JOIN districts d ON r.district_id = d.id
        LEFT JOIN cities c ON r.city_id = c.id
        WHERE r.id = ? AND (
            r.status = 'approved' OR  
            r.user_id = ?             
        )";

try {
    $stmt = $conn->prepare($sql);
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        header("Location: phong.php");
        exit();
    }

    $room = $result->fetch_assoc();

    // Lấy hình ảnh phòng
    $sql_images = "SELECT * FROM room_images WHERE room_id = ? ORDER BY is_primary DESC";
    $stmt_images = $conn->prepare($sql_images);
    $stmt_images->bind_param("i", $room_id);
    $stmt_images->execute();
    $images = $stmt_images->get_result()->fetch_all(MYSQLI_ASSOC);

    // Lấy danh sách phòng tương tự
    $sql_similar = "SELECT r.*, 
        (SELECT image_path FROM room_images WHERE room_id = r.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM rooms r
        WHERE r.id != ? AND r.district_id = ? AND r.status = 'approved'
        ORDER BY r.created_at DESC 
        LIMIT 3";
    $stmt_similar = $conn->prepare($sql_similar);
    $stmt_similar->bind_param("ii", $room_id, $room['district_id']);
    $stmt_similar->execute();
    $similar_rooms = $stmt_similar->get_result()->fetch_all(MYSQLI_ASSOC);

    // Kiểm tra xem phòng có được đánh dấu yêu thích không
    $is_favorited = false;
    if (isset($_SESSION['user_id'])) {
        $check_favorite = "SELECT * FROM favorites WHERE user_id = ? AND room_id = ?";
        $stmt_favorite = $conn->prepare($check_favorite);
        $stmt_favorite->bind_param("ii", $_SESSION['user_id'], $room_id);
        $stmt_favorite->execute();
        $favorite_result = $stmt_favorite->get_result();
        $is_favorited = $favorite_result->num_rows > 0;
    }

} catch (Exception $e) {
    error_log("Lỗi truy vấn: " . $e->getMessage());
    header("Location: phong.php");
    exit();
}

// Đặt tiêu đề trang
$page_title = $room['title'] . " - Homeseeker";

// Include header
include '../pages/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <!-- Phần gallery ảnh -->
            <div class="room-gallery mb-4">
                <?php if (count($images) > 0): ?>
                    <div id="roomGallery" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php foreach ($images as $index => $image): ?>
                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                    <img src="<?php echo !empty($image['image_path']) ? '../assets/' . htmlspecialchars($image['image_path']) : 'https://via.placeholder.com/400x250'; ?>" class="d-block w-100 rounded" alt="<?php echo htmlspecialchars($room['title']); ?>" style="max-height: 500px; object-fit: contain;">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($images) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#roomGallery" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#roomGallery" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php if (count($images) > 1): ?>
                        <div class="d-flex mt-2 overflow-auto">
                            <?php foreach ($images as $index => $image): ?>
                                <div class="thumbnail mx-1" style="min-width: 80px;" data-bs-target="#roomGallery" data-bs-slide-to="<?php echo $index; ?>">
                                    <img src="<?php echo !empty($image['image_path']) ? '../assets/' . htmlspecialchars($image['image_path']) : 'https://via.placeholder.com/400x250'; ?>" class="img-thumbnail" alt="Thumbnail" style="width: 80px; height: 60px; object-fit: cover; cursor: pointer;">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center p-5 bg-light rounded">
                        <i class="fas fa-image fa-3x mb-3 text-muted"></i>
                        <p>Không có hình ảnh</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Thông tin chính -->
            <div class="room-info">
                <h1><?php echo htmlspecialchars($room['title']); ?></h1>
                <p class="text-muted"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($room['address'] . ', ' . $room['district'] . ', ' . $room['city']); ?></p>
                
                <div class="d-flex flex-wrap mb-4">
                    <div class="me-4 mb-2">
                        <span class="text-muted">Loại phòng:</span>
                        <span class="ms-2 badge bg-warning"><?php echo htmlspecialchars($room['type']); ?></span>
                    </div>
                    <div class="me-4 mb-2">
                        <span class="text-muted">Diện tích:</span>
                        <strong class="ms-2"><?php echo htmlspecialchars($room['area']); ?> m²</strong>
                    </div>
                    <div class="me-4 mb-2">
                        <span class="text-muted">Số người tối đa:</span>
                        <strong class="ms-2"><?php echo htmlspecialchars($room['max_occupants']); ?> người</strong>
                    </div>
                    <div class="me-4 mb-2">
                        <span class="text-muted">Ngày đăng:</span>
                        <span class="ms-2"><?php echo date('d/m/Y', strtotime($room['created_at'])); ?></span>
                    </div>
                </div>
                
                <div class="room-description mb-4">
                    <h4>Mô tả chi tiết</h4>
                    <div class="p-3 bg-light rounded">
                        <?php
                        // Sửa lỗi xuống dòng mô tả
                        echo nl2br(htmlspecialchars(str_replace(["\\r\\n", "\\n", "\\r"], "\n", $room['description'])));
                        ?>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h4>Tiện ích</h4>
                        <div class="p-3 bg-light rounded">
                            <?php if (!empty($room['amenities'])): ?>
                                <?php echo nl2br(htmlspecialchars($room['amenities'])); ?>
                            <?php else: ?>
                                <em class="text-muted">Không có thông tin</em>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h4>Cơ sở vật chất</h4>
                        <div class="p-3 bg-light rounded">
                            <?php if (!empty($room['facilities'])): ?>
                                <?php echo nl2br(htmlspecialchars($room['facilities'])); ?>
                            <?php else: ?>
                                <em class="text-muted">Không có thông tin</em>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($room['lat']) && !empty($room['lng'])): ?>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $room['lat'] . ',' . $room['lng']; ?>" target="_blank" class="btn btn-outline-primary">
                        <i class="fas fa-map-marker-alt"></i> Xem trên Google Maps
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Thông tin giá và liên hệ -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <h3 class="text-danger mb-4"><?php echo number_format($room['price']); ?> đ/tháng</h3>
                    
                    <div class="info-item mb-3 pb-3 border-bottom">
                        <h5>Thông tin liên hệ</h5>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-user text-warning me-2"></i>
                            <span><?php echo htmlspecialchars($room['contact_name']); ?></span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-phone-alt text-warning me-2"></i>
                            <a href="tel:<?php echo htmlspecialchars($room['contact_phone']); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($room['contact_phone']); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="tel:<?php echo htmlspecialchars($room['contact_phone']); ?>" class="btn btn-warning">
                            <i class="fas fa-phone-alt me-2"></i>Gọi ngay
                        </a>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button class="btn btn-outline-danger save-favorite" data-room-id="<?php echo $room['id']; ?>">
                                <i class="<?php echo $is_favorited ? 'fas' : 'far'; ?> fa-heart me-2"></i>
                                <?php echo $is_favorited ? 'Đã lưu' : 'Lưu tin'; ?>
                            </button>
                        <?php else: ?>
                            <a href="../auth/login.php" class="btn btn-outline-danger">
                                <i class="far fa-heart me-2"></i>Đăng nhập để lưu tin
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Thông tin người đăng -->
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="mb-3">Người đăng</h5>
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            <img src="<?php echo !empty($room['owner_avatar']) ? '../assets/avatars/' . htmlspecialchars($room['owner_avatar']) : 'https://via.placeholder.com/50'; ?>" class="rounded-circle" alt="Avatar" width="50" height="50">
                        </div>
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($room['owner_username']); ?></h6>
                            <span class="text-muted small">Thành viên từ <?php echo date('m/Y', strtotime($room['created_at'])); ?></span>
                        </div>
                    </div>
                    <a href="phong.php?user=<?php echo $room['user_id']; ?>" class="btn btn-outline-secondary w-100">
                        Xem tất cả tin đăng
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bản đồ vị trí -->
    <?php if (!empty($room['lat']) && !empty($room['lng'])): ?>
    <div class="mb-4">
        <h4>Bản đồ vị trí</h4>
        <div id="mapbox-room" style="height: 350px; border-radius: 10px; overflow: hidden; margin-bottom: 20px;"></div>
    </div>
    <!-- Mapbox GL JS -->
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet">
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script>
        mapboxgl.accessToken = 'pk.eyJ1IjoiaGVsbG90aGFuaDJrMyIsImEiOiJjbWF0aGRkeTUwMjJiMmxzNTFiNGdscXJmIn0.3WnATbNXHfKjjkbviTZhUQ';
        const map = new mapboxgl.Map({
            container: 'mapbox-room',
            style: 'mapbox://styles/mapbox/streets-v11',
            center: [<?php echo $room['lng']; ?>, <?php echo $room['lat']; ?>],
            zoom: 15
        });
        new mapboxgl.Marker()
            .setLngLat([<?php echo $room['lng']; ?>, <?php echo $room['lat']; ?>])
            .addTo(map);
    </script>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.save-favorite').forEach(function(favBtn) {
        favBtn.addEventListener('click', function() {
            const roomId = this.getAttribute('data-room-id');
            const icon = this.querySelector('i');
            fetch('../../backend/user/toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'room_id=' + roomId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.action === 'added') {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        this.innerHTML = '<i class="fas fa-heart me-2"></i>Đã lưu';
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        this.innerHTML = '<i class="far fa-heart me-2"></i>Lưu tin';
                    }
                }
            });
        });
    });
    // Xử lý thumbnails
    const thumbnails = document.querySelectorAll('.thumbnail');
    thumbnails.forEach((thumb, index) => {
        thumb.addEventListener('click', function() {
            const carousel = document.querySelector('#roomGallery');
            const carouselInstance = new bootstrap.Carousel(carousel);
            carouselInstance.to(index);
        });
    });
});
</script>

<!-- Footer -->
<?php include '../pages/footer.php'; ?>