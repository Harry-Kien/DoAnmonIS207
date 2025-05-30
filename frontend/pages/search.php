<?php
// Khởi động session
session_start();

// Tùy chỉnh tiêu đề trang
$page_title = "Kết quả tìm kiếm - Homeseeker";

// Include header
include 'header.php';

// Kết nối CSDL
require_once '../../backend/config/config.php';

// Lấy tham số tìm kiếm
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$price = isset($_GET['price']) ? $_GET['price'] : '';

// Xây dựng câu truy vấn
$query = "SELECT * FROM rooms WHERE status = 'approved'";
$params = [];

if (!empty($keyword)) {
    $query .= " AND (title LIKE ? OR description LIKE ? OR address LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

if (!empty($location)) {
    $query .= " AND (city = ? OR district = ?)";
    $params[] = $location;
    $params[] = $location;
}

if (!empty($type)) {
    $query .= " AND type = ?";
    $params[] = $type;
}

if (!empty($price)) {
    $price_range = explode('-', $price);
    if (count($price_range) == 2) {
        $min_price = (int)$price_range[0];
        $max_price = (int)$price_range[1];
        $query .= " AND price BETWEEN ? AND ?";
        $params[] = $min_price;
        $params[] = $max_price;
    }
}

// Thêm điều kiện sắp xếp
$query .= " ORDER BY created_at DESC";

// Thực hiện truy vấn
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$rooms = $result->fetch_all(MYSQLI_ASSOC);
?>

<style>
.feature-card {
    height: 100%;
    min-height: 350px;
    border-radius: 12px;
    box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    overflow: hidden;
    transition: box-shadow 0.2s;
    display: flex;
    flex-direction: column;
    background: #fff;
    padding: 0;
}
.feature-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.10);
}
.feature-card .card-img-top {
    height: 140px;
    object-fit: cover;
    width: 100%;
}
.feature-card .card-body {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 16px 14px 10px 14px;
}
.feature-card .card-title {
    font-size: 1.05rem;
    min-height: 40px;
    margin-bottom: 0.4rem;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
.feature-card .card-text {
    font-size: 0.93rem;
    color: #555;
    min-height: 20px;
    margin-bottom: 0.4rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.feature-card .d-flex.justify-content-between {
    margin-bottom: 0.3rem;
}
.row.g-4 {
    --bs-gutter-x: 18px;
    --bs-gutter-y: 18px;
}
.col-md-4 {
    flex: 0 0 auto;
    width: 32.5%;
    max-width: 32.5%;
}
@media (max-width: 991.98px) {
    .col-md-4 { width: 49%; max-width: 49%; }
}
@media (max-width: 767.98px) {
    .col-md-4 { width: 100%; max-width: 100%; }
}
</style>

<div class="container py-5">
    <!-- Tiêu đề và bộ lọc tìm kiếm -->
    <div class="mb-5">
        <h1 class="mb-4">Kết quả tìm kiếm</h1>
        
        <!-- Bộ lọc -->
        <div class="bg-light p-4 rounded">
            <form action="search.php" method="GET">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="keyword" placeholder="Từ khóa" value="<?php echo htmlspecialchars($keyword); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="location">
                            <option value="">Chọn khu vực</option>
                            <option value="TP. Hồ Chí Minh" <?php if($location == 'TP. Hồ Chí Minh') echo 'selected'; ?>>TP. Hồ Chí Minh</option>
        
                            <option value="Bình Dương" <?php if($location == 'Bình Dương') echo 'selected'; ?>>Bình Dương</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="type">
                            <option value="">Loại phòng</option>
                            <option value="Phòng trọ" <?php if($type == 'Phòng trọ') echo 'selected'; ?>>Phòng trọ</option>
                            <option value="Chung cư mini" <?php if($type == 'Chung cư mini') echo 'selected'; ?>>Chung cư mini</option>
                            <option value="Nhà nguyên căn" <?php if($type == 'Nhà nguyên căn') echo 'selected'; ?>>Nhà nguyên căn</option>
                         
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="price">
                            <option value="">Giá</option>
                            <option value="0-2000000" <?php if($price == '0-2000000') echo 'selected'; ?>>Dưới 2 triệu</option>
                            <option value="2000000-3000000" <?php if($price == '2000000-4000000') echo 'selected'; ?>>2 - 4 triệu</option>
                            <option value="4000000-6000000" <?php if($price == '44000000-6000000') echo 'selected'; ?>>4 - 6 triệu</option>
                    
                            <option value="6000000-999999999" <?php if($price == '6000000-999999999') echo 'selected'; ?>>Trên 6 triệu</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-warning w-100">Lọc kết quả</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Kết quả tìm kiếm -->
    <div class="row g-4">
        <?php if (count($rooms) > 0): ?>
            <?php foreach ($rooms as $room): ?>
                <div class="col-md-4 mb-3 d-flex">
                    <div class="card feature-card w-100">
                        <?php
                        // Lấy ảnh đầu tiên của phòng
                        $room_id = $room['id'];
                        $img_query = "SELECT image_path FROM room_images WHERE room_id = ? LIMIT 1";
                        $img_stmt = $conn->prepare($img_query);
                        $img_stmt->bind_param("i", $room_id);
                        $img_stmt->execute();
                        $img_result = $img_stmt->get_result();
                        $img_path = $img_result->num_rows > 0 
                            ? '../assets/' . $img_result->fetch_assoc()['image_path'] 
                            : 'https://via.placeholder.com/400x250';
                        ?>
                        <img src="<?php echo $img_path; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($room['title']); ?>">
                        <div class="card-body d-flex flex-column">
                            <div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="badge bg-warning"><?php echo htmlspecialchars($room['type']); ?></span>
                                    <span class="text-danger fw-bold"><?php echo number_format($room['price']); ?> đ/tháng</span>
                                </div>
                                <h5 class="card-title">
                                    <a href="../room/chi-tiet-phong.php?id=<?php echo $room['id']; ?>" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($room['title']); ?>
                                    </a>
                                </h5>
                                <p class="card-text">
                                    <i class="fas fa-map-marker-alt text-secondary me-2"></i>
                                    <?php echo htmlspecialchars($room['district'] . ', ' . $room['city']); ?>
                                </p>
                            </div>
                            <div class="d-flex justify-content-between text-secondary mt-auto">
                                <span><i class="fas fa-vector-square me-1"></i> <?php echo $room['area']; ?>m²</span>
                                <?php if (isset($room['max_occupants'])): ?>
                                    <span><i class="fas fa-users me-1"></i> <?php echo $room['max_occupants']; ?></span>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a href="javascript:void(0)" class="toggle-favorite" data-room-id="<?php echo $room['id']; ?>" title="Thêm vào yêu thích">
                                        <i class="far fa-heart text-danger"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center p-5">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <h4>Không tìm thấy kết quả phù hợp</h4>
                    <p>Vui lòng thử lại với các tiêu chí tìm kiếm khác.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Thêm Mapbox CSS và JS -->
<link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
<script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
<script src="https://unpkg.com/@mapbox/mapbox-sdk/umd/mapbox-sdk.min.js"></script>

<div id="map" style="width: 100%; height: 400px;"></div>

<script>
mapboxgl.accessToken = 'YOUR_MAPBOX_ACCESS_TOKENpk.eyJ1IjoiaGVsbG90aGFuaDJrMyIsImEiOiJjbWF0aGRkeTUwMjJiMmxzNTFiNGdscXJmIn0.3WnATbNXHfKjjkbviTZhUQ'; // Thay bằng token của bạn

var address = "<?php echo addslashes($room['address'] . ', ' . $room['district'] . ', ' . $room['city']); ?>";
var mapboxClient = mapboxSdk({ accessToken: mapboxgl.accessToken });

mapboxClient.geocoding
    .forwardGeocode({
        query: address,
        limit: 1
    })
    .send()
    .then(function(response) {
        if (
            response &&
            response.body &&
            response.body.features &&
            response.body.features.length
        ) {
            var feature = response.body.features[0];

            var map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: feature.center,
                zoom: 15
            });

            new mapboxgl.Marker().setLngLat(feature.center).addTo(map);
        } else {
            alert('Không tìm thấy vị trí!');
        }
    });
</script>

<?php
// Include footer
include 'footer.php';
?>