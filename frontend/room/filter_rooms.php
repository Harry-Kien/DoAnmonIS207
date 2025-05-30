<?php
session_start();
require_once '../../backend/config/config.php';

// Lấy dữ liệu từ form GET
$location = $_GET['location'] ?? '';
$district = $_GET['district'] ?? '';
$type = $_GET['type'] ?? '';
$price_range = $_GET['price'] ?? '';
$min_area = isset($_GET['min_area']) ? (int)$_GET['min_area'] : 0;
$max_area = isset($_GET['max_area']) ? (int)$_GET['max_area'] : 0;
$amenities = $_GET['amenities'] ?? [];
if (!is_array($amenities)) $amenities = [$amenities];

// Validate and sanitize price_range
$min_price = 0;
$max_price = 10000000;
if (!empty($price_range) && preg_match('/^\d+-\d+$/', $price_range)) {
    [$min_price, $max_price] = array_map('intval', explode('-', $price_range));
}

// Truy vấn chính
$sql = "SELECT r.*, 
               u.full_name, 
               d.name as district_name, 
               c.name as city_name,
               (SELECT image_path FROM room_images WHERE room_id = r.id ORDER BY is_primary DESC LIMIT 1) as primary_image
        FROM rooms r
        LEFT JOIN user u ON r.user_id = u.id
        LEFT JOIN districts d ON r.district_id = d.id
        LEFT JOIN cities c ON r.city_id = c.id
        WHERE r.status = 'approved'";

$params = [];
$types = '';

if (!empty($location)) {
    $sql .= " AND c.name = ?";
    $params[] = $location;
    $types .= 's';
}
if (!empty($district)) {
    $sql .= " AND d.name = ?";
    $params[] = $district;
    $types .= 's';
}
if (!empty($type)) {
    $sql .= " AND r.type = ?";
    $params[] = $type;
    $types .= 's';
}
$sql .= " AND r.price BETWEEN ? AND ?";
$params[] = $min_price;
$params[] = $max_price;
$types .= 'ii';

if ($min_area > 0) {
    $sql .= " AND r.area >= ?";
    $params[] = $min_area;
    $types .= 'i';
}
if ($max_area > 0) {
    $sql .= " AND r.area <= ?";
    $params[] = $max_area;
    $types .= 'i';
}

foreach ($amenities as $a) {
    $sql .= " AND r.amenities LIKE ?";
    $params[] = "%$a%";
    $types .= 's';
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($types && $params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$page_title = "Kết quả tìm kiếm phòng trọ";
include '../../frontend/pages/header.php';
?>

<div class="container py-5">
    <h2 class="text-center mb-4">Kết quả tìm kiếm</h2>
    <div class="row">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($room = mysqli_fetch_assoc($result)): ?>
                <?php
                $price_formatted = number_format($room['price']) . ' đ/tháng';
                $room_type = $room['type'] ?? 'Phòng trọ';
                $room_type_class = 'bg-warning';
                if ($room_type === 'Chung cư mini') $room_type_class = 'bg-info';
                elseif ($room_type === 'Nhà nguyên căn') $room_type_class = 'bg-success';
                
                $default_image = '../assets/images/default-room.jpg';
                $image_path = $default_image;
                if (!empty($room['primary_image']) && strtolower($room['primary_image']) !== 'null') {
                    $image_path_candidate = '../assets/' . ltrim($room['primary_image'], '/');
                    $real_path = $_SERVER['DOCUMENT_ROOT'] . '/IS207-hoomseeker/frontend/assets/' . ltrim($room['primary_image'], '/');
                    if (file_exists($real_path)) {
                        $image_path = $image_path_candidate;
                    }
                }

                $address = $room['address'];
                if (!empty($room['district_name'])) $address .= ', ' . $room['district_name'];
                if (!empty($room['city_name'])) $address .= ', ' . $room['city_name'];
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card room-card h-100">
                        <div class="position-relative">
                            <img src="<?= htmlspecialchars($image_path) ?>" class="card-img-top" alt="<?= htmlspecialchars($room['title']) ?>" style="height: 200px; object-fit: cover;" onerror="this.src='<?= $default_image ?>'">
                            <span class="position-absolute top-0 start-0 badge <?= $room_type_class ?> m-2"><?= htmlspecialchars($room_type) ?></span>
                            <span class="position-absolute top-0 end-0 badge bg-danger m-2"><?= $price_formatted ?></span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title text-dark"><?= htmlspecialchars($room['title']) ?></h5>
                            <p class="card-text text-muted"><i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($address) ?></p>
                            <div class="d-flex justify-content-between mt-3 small text-secondary">
                                <span><i class="fas fa-expand me-1"></i> <?= $room['area'] ?> m²</span>
                                <span><i class="fas fa-clock me-1"></i> <?= date('d/m/Y', strtotime($room['created_at'])) ?></span>
                            </div>
                        </div>
                        <a href="../../frontend/room/chi-tiet-phong.php?id=<?= $room['id'] ?>" class="stretched-link"></a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <p>Không tìm thấy phòng trọ phù hợp.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../frontend/pages/footer.php'; ?>
