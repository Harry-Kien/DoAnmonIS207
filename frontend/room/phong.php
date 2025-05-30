<?php
// Khởi động session
session_start();
require_once(__DIR__ . '/../../backend/config/config.php');
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Hàm tính thời gian đăng bài
function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $current = time();
    $diff = $current - $time;
    if ($diff < 60) return 'Vừa xong';
    if ($diff < 3600) return floor($diff / 60) . ' phút trước';
    if ($diff < 86400) return floor($diff / 3600) . ' giờ trước';
    if ($diff < 2592000) return floor($diff / 86400) . ' ngày trước';
    return date('d/m/Y', $time);
}

// Helper phân trang
function getPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return 'phong.php?' . http_build_query($params);
}

// Xử lý tham số filter (giữ nguyên logic cũ)
$location = $_GET['location'] ?? '';
$district = $_GET['district'] ?? '';
$type = $_GET['type'] ?? [];
if (!is_array($type) && !empty($type)) $type = [$type];
$amenities = $_GET['amenities'] ?? [];
if (!is_array($amenities) && !empty($amenities)) $amenities = [$amenities];
if (isset($_GET['price_range']) && !empty($_GET['price_range'])) {
    $price_range = explode('-', $_GET['price_range']);
    $min_price = isset($price_range[0]) ? (int)$price_range[0] : 0;
    $max_price = isset($price_range[1]) ? (int)$price_range[1] : 10000000;
} else {
    $min_price = $_GET['min_price'] ?? 0;
    $max_price = $_GET['max_price'] ?? 10000000;
}
if (isset($_GET['area_range']) && !empty($_GET['area_range'])) {
    $area_range = explode('-', $_GET['area_range']);
    $min_area = isset($area_range[0]) ? (int)$area_range[0] : 0;
    $max_area = isset($area_range[1]) ? (int)$area_range[1] : 1000;
} else {
    $min_area = $_GET['min_area'] ?? 0;
    $max_area = $_GET['max_area'] ?? 1000;
}
$sort = $_GET['sort'] ?? 'newest';
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$rooms_per_page = 9;

// Truy vấn SQL (giữ nguyên logic cũ)
$sql = "SELECT r.*, r.lat, r.lng, u.full_name, 
        (SELECT image_path FROM room_images WHERE room_id = r.id ORDER BY is_primary DESC, display_order ASC LIMIT 1) as main_image 
        FROM rooms r
        LEFT JOIN user u ON r.user_id = u.id
        WHERE r.status = 'approved'";
if (!empty($location)) $sql .= " AND r.address LIKE '%" . mysqli_real_escape_string($conn, $location) . "%'";
if (!empty($district)) $sql .= " AND r.address LIKE '%" . mysqli_real_escape_string($conn, $district) . "%'";
if (!empty($type)) {
    $type_conditions = [];
    foreach ($type as $t) $type_conditions[] = "r.type = '" . mysqli_real_escape_string($conn, $t) . "'";
    if (!empty($type_conditions)) $sql .= " AND (" . implode(" OR ", $type_conditions) . ")";
}
if ($min_price > 0) $sql .= " AND r.price >= " . (int)$min_price;
if ($max_price > 0 && $max_price < 10000000) $sql .= " AND r.price <= " . (int)$max_price;
if ($min_area > 0) $sql .= " AND r.area >= " . (int)$min_area;
if ($max_area > 0 && $max_area < 1000) $sql .= " AND r.area <= " . (int)$max_area;
if (!empty($amenities)) {
    foreach ($amenities as $amenity) {
        $sql .= " AND r.amenities LIKE '%" . mysqli_real_escape_string($conn, $amenity) . "%'";
    }
}
switch ($sort) {
    case 'price_asc': $sql .= " ORDER BY r.price ASC"; break;
    case 'price_desc': $sql .= " ORDER BY r.price DESC"; break;
    case 'area_asc': $sql .= " ORDER BY r.area ASC"; break;
    case 'area_desc': $sql .= " ORDER BY r.area DESC"; break;
    default: $sql .= " ORDER BY r.created_at DESC";
}
// Remove LIMIT clause for count query
$count_sql = preg_replace('/SELECT\s+r\.\*,.*?as main_image\s+FROM/is', 'SELECT COUNT(*) as total FROM', $sql);
$count_sql = preg_replace('/ORDER BY.+?(?=LIMIT|$)/is', '', $count_sql); // Remove ORDER BY for count
$count_sql = preg_replace('/LIMIT\s+\d+(,\s*\d+)?/i', '', $count_sql); // Remove LIMIT for count
$count_result = mysqli_query($conn, $count_sql);
$count_data = mysqli_fetch_assoc($count_result);
$total_rooms = $count_data['total'] ?? 0;
$total_pages = ceil($total_rooms / $rooms_per_page);
$offset = ($current_page - 1) * $rooms_per_page;
$sql_with_limit = $sql . " LIMIT $offset, $rooms_per_page";
$result = mysqli_query($conn, $sql_with_limit);
if (!$result) die("Lỗi truy vấn: " . mysqli_error($conn));


// Tiêu đề trang
$page_title = "Phòng trọ - Homeseeker";
include '../pages/header.php';
?>

<!-- Page Title -->
<section class="page-title" style="position: relative; background-color: rgba(0,0,0,0.6); color: white; padding: 80px 0; text-align: center; background-image: url('../../frontend/assets/images/anhbanner.jpg'); background-size: cover; background-position: center;">
    <div class="container position-relative z-1">
        <h1 class="display-4 mb-3">Tìm Phòng Trọ</h1>
        <p class="lead" style="color: rgba(255,255,255,0.7);">Lựa chọn phòng trọ phù hợp với nhu cầu của bạn</p>
    </div>
</section>

<!-- Main Content -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Filter Sidebar -->
            <div class="col-lg-3 mb-4">
                <?php include 'room_filter_form.php'; ?>
            </div>
            <!-- Room Listings -->
            <div class="col-lg-9">
                <!-- Search and Sort Bar -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center">
                        <span class="me-2">Sắp xếp theo:</span>
                        <select class="form-select form-select-sm" style="width: auto;" id="sortSelect" onchange="applySort()">
                            <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Mới nhất</option>
                            <option value="price_asc" <?php echo ($sort == 'price_asc') ? 'selected' : ''; ?>>Giá thấp đến cao</option>
                            <option value="price_desc" <?php echo ($sort == 'price_desc') ? 'selected' : ''; ?>>Giá cao đến thấp</option>
                            <option value="area_asc" <?php echo ($sort == 'area_asc') ? 'selected' : ''; ?>>Diện tích nhỏ đến lớn</option>
                            <option value="area_desc" <?php echo ($sort == 'area_desc') ? 'selected' : ''; ?>>Diện tích lớn đến nhỏ</option>
                        </select>
                    </div>
                    <div>
                        <span class="text-muted">Tìm thấy <?php echo $total_rooms; ?> phòng</span>
                    </div>
                </div>
                <!-- Room Grid -->
                <div class="row g-4">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($room = mysqli_fetch_assoc($result)): ?>
                            <?php
                                $room_type = $room['type'] ?? 'Phòng trọ';
                                $room_type_class = 'bg-warning';
                                $price = $room['price'];
                                $price_formatted = ($price >= 1000000) ? number_format($price / 1000000, 1) . ' triệu/tháng' : number_format($price) . 'đ/tháng';
                                $time_ago = getTimeAgo($room['created_at']);
                                $default_image = '../assets/images/default-room.jpg';
                                $image_path = $default_image;
                                if (!empty($room['main_image'])) {
                                    if (strpos($room['main_image'], 'uploads/') === 0) {
                                        $image_path = '../assets/' . $room['main_image'];
                                    } else {
                                        $image_path = '../assets/uploads/rooms/' . $room['main_image'];
                                    }
                                }
                            ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card room-card h-100">
                                    <div class="position-relative">
                                        <img src="<?php echo htmlspecialchars($image_path); ?>"
                                             class="card-img-top room-img"
                                             alt="<?php echo htmlspecialchars($room['title']); ?>"
                                             style="height: 200px; object-fit: cover;"
                                             onerror="this.src='<?php echo $default_image; ?>'">
                                        <?php if ($room['created_at'] > date('Y-m-d H:i:s', strtotime('-2 days'))): ?>
                                            <span class="badge bg-success badge-vip">NEW</span>
                                        <?php endif; ?>
                                        
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="badge <?php echo $room_type_class; ?>"><?php echo htmlspecialchars($room_type); ?></span>
                                            <span class="text-danger fw-bold"><?php echo $price_formatted; ?></span>
                                        </div>
                                        <h5 class="card-title"><?php echo htmlspecialchars($room['title']); ?></h5>
                                        <p class="card-text small text-muted mb-1">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($room['address']); ?>
                                            <?php if (!empty($room['lat']) && !empty($room['lng'])): ?>
                                                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $room['lat'] . ',' . $room['lng']; ?>"
                                                   target="_blank" title="Xem trên Google Maps" style="margin-left:8px;">
                                                    <i class="fab fa-google fa-lg text-danger"></i>
                                                </a>
                                            <?php endif; ?>
                                        </p>
                                        <div class="d-flex justify-content-between text-secondary small">
                                            <span><i class="fas fa-vector-square me-1"></i> <?php echo htmlspecialchars($room['area']); ?>m²</span>
                                            <span><i class="fas fa-bath me-1"></i> 1</span>
                                            <span><i class="fas fa-clock me-1"></i> <?php echo $time_ago; ?></span>
                                        </div>
                                    </div>
                                    <a href="chi-tiet-phong.php?id=<?php echo $room['id']; ?>" class="stretched-link"></a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-search-minus fa-3x text-muted mb-3"></i>
                            <h4>Không tìm thấy phòng nào phù hợp</h4>
                            <p class="text-muted">Vui lòng thử lại với bộ lọc khác</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Phân trang -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Phân trang" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <!-- Nút Previous -->
                            <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo getPaginationUrl($current_page - 1); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>

                            <?php
                            // Hiển thị các nút số trang
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);

                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="' . getPaginationUrl(1) . '">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<li class="page-item ' . ($i == $current_page ? 'active' : '') . '">
                                    <a class="page-link" href="' . getPaginationUrl($i) . '">' . $i . '</a>
                                </li>';
                            }

                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="' . getPaginationUrl($total_pages) . '">' . $total_pages . '</a></li>';
                            }
                            ?>

                            <!-- Nút Next -->
                            <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo getPaginationUrl($current_page + 1); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Script filter -->
<script>
window.currentDistrict = '<?php echo addslashes($district); ?>';
</script>
<script src="../assets/js/filter_form_script.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<?php include '../pages/footer.php'; ?>

<script>
function applySort() {
    const sortSelect = document.getElementById('sortSelect');
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('sort', sortSelect.value);
    currentUrl.searchParams.delete('page'); // Reset về trang 1 khi thay đổi sort
    window.location.href = currentUrl.toString();
}
</script>