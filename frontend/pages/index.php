<?php
session_start();
require_once "../../backend/config/config.php";

if (!isset($_SESSION["loggedin"]) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $sql = "SELECT id, username FROM user WHERE remember_token = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) === 1) {
            mysqli_stmt_bind_result($stmt, $id, $username_db);
            if (mysqli_stmt_fetch($stmt)) {
                $_SESSION["loggedin"] = true;
                $_SESSION["user_id"] = $id;
                $_SESSION["username"] = $username_db;
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Truy vấn lấy phòng trọ nổi bật (mới nhất)
$featured_rooms_sql = "SELECT r.*, 
                        u.full_name, 
                        d.name AS district_name, 
                        c.name AS city_name,
                        (SELECT image_path FROM room_images WHERE room_id = r.id ORDER BY is_primary DESC LIMIT 1) AS primary_image
                      FROM rooms r
                      LEFT JOIN user u ON r.user_id = u.id
                      LEFT JOIN districts d ON r.district_id = d.id
                      LEFT JOIN cities c ON r.city_id = c.id
                      WHERE r.status = 'approved'
                      ORDER BY r.created_at DESC
                      LIMIT 3";
$featured_rooms = mysqli_query($conn, $featured_rooms_sql);

// Đếm số lượng
$num_rooms = mysqli_num_rows($featured_rooms);

// Tiêu đề trang
$page_title = "Homeseeker - Tìm nhà trọ, phòng trọ uy tín";

// Hàm xử lý thời gian
function timeAgo($dateString) {
    $date = new DateTime($dateString);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->y > 0) return $diff->y . " năm trước";
    if ($diff->m > 0) return $diff->m . " tháng trước";
    if ($diff->d > 0) return $diff->d . " ngày trước";
    if ($diff->h > 0) return $diff->h . " giờ trước";
    if ($diff->i > 0) return $diff->i . " phút trước";
    return "Vừa xong";
}

// Include header
include 'header.php';
?>

<!-- Banner -->
<div class="hero-section" style="background-image:url('../assets/images/anhbanner.jpg');">
    <div class="container">
        <div class="text-center text-white py-5">
            <h1>Tìm nhà trọ, phòng trọ uy tín</h1>
            <p>Giải pháp tìm nhà trọ, phòng trọ nhanh chóng và hiệu quả nhất</p>

            <!-- Form tìm kiếm -->
            <div class="search-form bg-white p-4 rounded shadow mt-4">
                <form action="../room/filter_rooms.php" method="get" id="search-form">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <select class="form-select" name="location">
                                <option value="">Chọn khu vực</option>
                                <option value="TP. Hồ Chí Minh">TP. Hồ Chí Minh</option>
                                <option value="Bình Dương">Bình Dương</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="type">
                                <option value="">Loại phòng</option>
                                <option value="Phòng trọ">Phòng trọ</option>
                                <option value="Chung cư mini">Chung cư mini</option>
                                <option value="Nhà nguyên căn">Nhà nguyên căn</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="price">
                                <option value="">Giá</option>
                                <option value="0-2000000">Dưới 2 triệu</option>
                                <option value="2000000-4000000">2 - 4 triệu</option>
                                <option value="4000000-6000000">4 - 6 triệu</option>
                                <option value="6000000-100000000">Trên 6 triệu</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-warning w-100">Tìm kiếm</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Phòng trọ nổi bật -->
<div class="container py-5">
    <h2 class="text-center mb-4">Phòng trọ nổi bật</h2>

    <div class="row">
        <?php if ($num_rooms > 0): ?>
            <?php while ($room = mysqli_fetch_assoc($featured_rooms)): ?>
                <?php
                $price_formatted = number_format($room['price']) . ' đ/tháng';
                $room_type_class = 'bg-warning';
                $room_type = $room['type'] ?? 'Phòng trọ';

                if ($room_type === 'Chung cư mini') $room_type_class = 'bg-info';
                elseif ($room_type === 'Nhà nguyên căn') $room_type_class = 'bg-success';

                $default_image = '../assets/images/default-room.jpg';
                $image_path = $default_image;

                if (!empty($room['primary_image'])) {
                    $image_path = '../assets/uploads/rooms/' . basename($room['primary_image']);
                }
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card room-card h-100">
                        <div class="position-relative">
                            <img src="<?php echo $image_path; ?>" class="card-img-top"
                                 alt="<?php echo $room['title']; ?>"
                                 style="height: 200px; object-fit: cover;"
                                 onerror="this.src='<?php echo $default_image; ?>'">
                            <span class="position-absolute top-0 start-0 badge <?php echo $room_type_class; ?> m-2"><?php echo $room_type; ?></span>
                            <span class="position-absolute top-0 end-0 badge bg-danger m-2"><?php echo $price_formatted; ?></span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title text-dark"><?php echo $room['title']; ?></h5>
                            <p class="card-text text-muted">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?php
                                $location = $room['address'];
                                if (!empty($room['district_name'])) $location .= ', ' . $room['district_name'];
                                if (!empty($room['city_name'])) $location .= ', ' . $room['city_name'];
                                echo $location;
                                ?>
                            </p>
                            <div class="d-flex justify-content-between mt-3 small text-secondary">
                                <span><i class="fas fa-expand me-1"></i> <?php echo $room['area']; ?> m²</span>
                                <span><i class="fas fa-clock me-1"></i> <?php echo timeAgo($room['created_at']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <span class="text-muted"><i class="fas fa-user me-1"></i> <?php echo $room['full_name'] ?? 'Chủ trọ'; ?></span>
                            </div>
                        </div>
                        <a href="../room/chi-tiet-phong.php?id=<?php echo $room['id']; ?>" class="stretched-link"></a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <p>Chưa có phòng trọ nào được đăng.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="text-center mt-4">
        <a href="../../frontend/room/phong.php" class="btn btn-outline-warning">Xem thêm phòng trọ</a>
    </div>
</div>

<?php include 'footer.php'; ?>
