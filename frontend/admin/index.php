<?php
session_start();
require_once "../../backend/config/config.php";

// Tự động đăng nhập nếu có cookie remember_token
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

// Kiểm tra xem $conn có được định nghĩa không
if (!isset($conn)) {
    die("Lỗi: Biến \$conn không được định nghĩa. Vui lòng kiểm tra file config.php.");
}

// Truy vấn phòng trọ nổi bật (mới nhất)
$featured_sql = "SELECT r.*, u.full_name 
                FROM rooms r
                JOIN user u ON r.user_id = u.id
                WHERE r.status = 1
                ORDER BY r.created_at DESC
                LIMIT 3";
                
$featured_result = mysqli_query($conn, $featured_sql);

// Kiểm tra lỗi truy vấn
if (!$featured_result) {
    die("Lỗi truy vấn: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homeseeker - Tìm nhà trọ, phòng trọ uy tín</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <!-- Banner tìm kiếm -->
    <div class="hero-section">
        <div class="container">
            <div class="text-center text-white py-5">
                <h1>Tìm nhà trọ, phòng trọ uy tín</h1>
                <p>Giải pháp tìm nhà trọ, phòng trọ nhanh chóng và hiệu quả nhất</p>
                
                <!-- Form tìm kiếm -->
                <div class="search-form bg-white p-4 rounded shadow mt-4">
                    <form action="filter_rooms.php" method="get">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <select class="form-select" name="location" id="location">
                                    <option value="">Chọn khu vực</option>
                                    <option value="Hồ Chí Minh">Hồ Chí Minh</option>
                                    <option value="Hà Nội">Hà Nội</option>
                                    <option value="Đà Nẵng">Đà Nẵng</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="type" id="type">
                                    <option value="">Loại phòng</option>
                                    <option value="Phòng trọ">Phòng trọ</option>
                                    <option value="Chung cư mini">Chung cư mini</option>
                                    <option value="Nhà nguyên căn">Nhà nguyên căn</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="price" id="price">
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
            <?php 
            if (mysqli_num_rows($featured_result) > 0) {
                while ($room = mysqli_fetch_assoc($featured_result)) {
                    // Lấy ảnh đầu tiên của phòng
                    $image_sql = "SELECT image_path FROM room_images 
                                  WHERE room_id = {$room['id']} 
                                  ORDER BY display_order ASC LIMIT 1";
                    $image_result = mysqli_query($conn, $image_sql);
                    $image_path = "images/default-room.jpg"; // Ảnh mặc định
                    
                    if (mysqli_num_rows($image_result) > 0) {
                        $image = mysqli_fetch_assoc($image_result);
                        $image_path = $image['image_path'];
                    }
                    
                    // Xác định loại phòng để hiển thị nhãn
                    $room_type = '';
                    $room_type_class = '';
                    
                    // Phân tích JSON amenities để xác định loại phòng
                    $amenities = json_decode($room['amenities'], true);
                    
                    if (strpos(strtolower($room['title']), 'phòng trọ') !== false) {
                        $room_type = 'Phòng trọ';
                        $room_type_class = 'bg-warning';
                    } elseif (strpos(strtolower($room['title']), 'căn hộ mini') !== false || 
                              strpos(strtolower($room['title']), 'chung cư mini') !== false) {
                        $room_type = 'Chung cư mini';
                        $room_type_class = 'bg-info';
                    } elseif (strpos(strtolower($room['title']), 'nhà nguyên căn') !== false) {
                        $room_type = 'Nhà nguyên căn';
                        $room_type_class = 'bg-success';
                    } else {
                        $room_type = 'Phòng trọ';
                        $room_type_class = 'bg-warning';
                    }
                    
                    // Format giá
                    $price_formatted = number_format($room['price']) . '/tháng';
            ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card room-card h-100">
                            <div class="position-relative">
                                <img src="<?php echo $image_path; ?>" class="card-img-top" alt="<?php echo $room['title']; ?>" style="height: 200px; object-fit: cover;">
                                <span class="position-absolute top-0 start-0 badge <?php echo $room_type_class; ?> m-2"><?php echo $room_type; ?></span>
                                <span class="position-absolute top-0 end-0 badge bg-danger m-2"><?php echo $price_formatted; ?></span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><a href="chi-tiet-phong.php?id=<?php echo $room['id']; ?>" class="text-decoration-none"><?php echo $room['title']; ?></a></h5>
                                <p class="card-text text-muted"><i class="fas fa-map-marker-alt"></i> <?php echo $room['address']; ?></p>
                                <div class="d-flex justify-content-between mt-3">
                                    <span><i class="fas fa-expand"></i> <?php echo $room['area']; ?> m²</span>
                                    <span><i class="fas fa-user"></i> <?php echo $room['full_name']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
            <?php
                }
            } else {
                echo '<div class="col-12 text-center"><p>Chưa có phòng trọ nào được đăng.</p></div>';
            }
            ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="filter_rooms.php" class="btn btn-outline-warning">Xem thêm phòng trọ</a>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>