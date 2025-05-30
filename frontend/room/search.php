<?php
require_once '../../backend/config/config.php';
$page_title = "Tìm kiếm phòng trọ";
include '../pages/header.php';

// Lấy dữ liệu từ form
$keyword = trim($_GET['keyword'] ?? '');
$location = trim($_GET['location'] ?? '');
$type = trim($_GET['type'] ?? '');
$price = trim($_GET['price'] ?? '');

$min_price = 0;
$max_price = 999999999;
if ($price) {
    $arr = explode('-', $price);
    $min_price = isset($arr[0]) ? (int)$arr[0] : 0;
    $max_price = isset($arr[1]) ? (int)$arr[1] : 999999999;
}

// Xây dựng câu truy vấn nếu có tìm kiếm
$rooms = [];
if ($_GET) {
    $sql = "SELECT * FROM rooms WHERE status = 'approved'";
    $params = [];
    $types = "";

    if ($keyword) {
        $sql .= " AND (title LIKE ? OR description LIKE ?)";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
        $types .= "ss";
    }
    if ($location) {
        $sql .= " AND address LIKE ?";
        $params[] = "%$location%";
        $types .= "s";
    }
    if ($type) {
        $sql .= " AND type = ?";
        $params[] = $type;
        $types .= "s";
    }
    if ($price) {
        $sql .= " AND price >= ? AND price <= ?";
        $params[] = $min_price;
        $params[] = $max_price;
        $types .= "ii";
    }
    $sql .= " ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rooms = $stmt->get_result();
}
?>

<div class="container py-5">
    <h2 class="mb-4 text-center">Tìm kiếm phòng trọ</h2>
    <form action="search.php" method="get" class="row g-2 align-items-center justify-content-center mb-4">
        <div class="col-md-3">
            <input type="text" name="keyword" class="form-control" placeholder="Nhập từ khóa tìm kiếm..." value="<?php echo htmlspecialchars($keyword); ?>">
        </div>
        <div class="col-md-3">
            <select name="location" class="form-select">
                <option value="">Chọn thành phố</option>
                <option value="TP. Hồ Chí Minh" <?php if($location=="TP. Hồ Chí Minh") echo "selected"; ?>>TP. Hồ Chí Minh</option>
                <option value="Hà Nội" <?php if($location=="Hà Nội") echo "selected"; ?>>Hà Nội</option>
                <!-- Thêm các tỉnh/thành khác nếu cần -->
            </select>
        </div>
        <div class="col-md-2">
            <select name="type" class="form-select">
                <option value="">Loại phòng</option>
                <option value="Phòng trọ" <?php if($type=="Phòng trọ") echo "selected"; ?>>Phòng trọ</option>
                <option value="Chung cư mini" <?php if($type=="Chung cư mini") echo "selected"; ?>>Chung cư mini</option>
                <option value="Nhà nguyên căn" <?php if($type=="Nhà nguyên căn") echo "selected"; ?>>Nhà nguyên căn</option>
                <option value="Ở ghép" <?php if($type=="Ở ghép") echo "selected"; ?>>Ở ghép</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="price" class="form-select">
                <option value="">Chọn mức giá</option>
                <option value="0-2000000" <?php if($price=="0-2000000") echo "selected"; ?>>Dưới 2 triệu</option>
                <option value="2000000-4000000" <?php if($price=="2000000-4000000") echo "selected"; ?>>2 - 4 triệu</option>
                <option value="4000000-6000000" <?php if($price=="4000000-6000000") echo "selected"; ?>>4 - 6 triệu</option>
                <option value="6000000-999999999" <?php if($price=="6000000-999999999") echo "selected"; ?>>Trên 6 triệu</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-warning w-100">Tìm kiếm</button>
        </div>
    </form>

    <?php if ($_GET): ?>
        <?php if ($rooms && $rooms->num_rows > 0): ?>
            <div class="row g-4">
                <?php while ($room = $rooms->fetch_assoc()): ?>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="chi-tiet-phong.php?id=<?php echo $room['id']; ?>">
                                        <?php echo htmlspecialchars($room['title']); ?>
                                    </a>
                                </h5>
                                <p class="card-text">
                                    <b>Giá:</b> <?php echo number_format($room['price']); ?> đ/tháng<br>
                                    <b>Địa chỉ:</b> <?php echo htmlspecialchars($room['address']); ?><br>
                                    <b>Loại:</b> <?php echo htmlspecialchars($room['type']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-4">Không tìm thấy phòng phù hợp với tiêu chí của bạn.</div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../pages/footer.php'; ?>