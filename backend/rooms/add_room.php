<?php
session_start();
require_once 'config.php';
require_once 'subscription_manager.php'; // Thêm vào để sử dụng SubscriptionManager

// Kiểm tra đăng nhập và phân quyền
if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'moderator')) {
    header("Location: login.php");
    exit();
}

// Khởi tạo SubscriptionManager để kiểm tra quyền đăng tin
$subscriptionManager = new SubscriptionManager($conn);
$user_id = $_SESSION['id'];

// Kiểm tra quyền đăng tin theo gói dịch vụ
$postCheck = $subscriptionManager->canPostNewRoom($user_id);

// Nếu không có quyền đăng tin, chuyển đến trang bảng giá
if (!$postCheck['can_post']) {
    $_SESSION['error'] = $postCheck['message'];
    header("Location: banggia.php");
    exit();
}

$errors = [];
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $type = $_POST['type'];
    $area = floatval($_POST['area']);
    $price = floatval($_POST['price']);
    $address = trim($_POST['address']);
    $district = trim($_POST['district']);
    $city = trim($_POST['city']);
    $max_occupants = intval($_POST['max_occupants']);
    $amenities = isset($_POST['amenities']) ? implode(',', $_POST['amenities']) : '';
    $facilities = isset($_POST['facilities']) ? implode(',', $_POST['facilities']) : '';

    // Validate dữ liệu
    if (empty($title)) {
        $errors[] = "Tiêu đề không được để trống";
    }
    if (empty($address)) {
        $errors[] = "Địa chỉ không được để trống";
    }
    if ($price <= 0) {
        $errors[] = "Giá phòng phải lớn hơn 0";
    }

    // Xử lý upload ảnh
    $image_paths = [];
    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = 'uploads/rooms/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $filename = uniqid() . '_' . basename($_FILES['images']['name'][$key]);
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($tmp_name, $target_path)) {
                $image_paths[] = $target_path;
            } else {
                $errors[] = "Lỗi upload ảnh: " . $_FILES['images']['name'][$key];
            }
        }
    }

    // Nếu không có lỗi, thêm phòng vào CSDL
    if (empty($errors)) {
        $sql = "INSERT INTO rooms (
            title, description, type, area, price, address, 
            district, city, amenities, facilities, 
            max_occupants, user_id, created_at, updated_at, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)";
        
        $status = ($_SESSION['role'] === 'admin') ? 'approved' : 'pending';
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param(
            $stmt, 
            "sssddsssssiss", 
            $title, $description, $type, $area, $price, 
            $address, $district, $city, $amenities, 
            $facilities, $max_occupants, $user_id, $status
        );

        if (mysqli_stmt_execute($stmt)) {
            $room_id = mysqli_insert_id($conn);

            if (!empty($image_paths)) {
                $image_sql = "INSERT INTO room_images (room_id, image_path, is_primary, created_at) VALUES (?, ?, ?, NOW())";
                $image_stmt = mysqli_prepare($conn, $image_sql);
                
                foreach ($image_paths as $index => $path) {
                    $is_primary = ($index === 0) ? 1 : 0;
                    mysqli_stmt_bind_param($image_stmt, "isi", $room_id, $path, $is_primary);
                    mysqli_stmt_execute($image_stmt);
                }
            }
            
            // Cập nhật trạng thái ưu tiên theo gói dịch vụ
            $subscriptionManager->updateRoomPriority($room_id, $user_id);

            $success_message = "Thêm phòng thành công!";
            // Reset form
            $_POST = [];
        } else {
            $errors[] = "Lỗi: " . mysqli_error($conn);
        }
    }
}

// Thêm thông tin gói dịch vụ hiện tại
$current_plan = $subscriptionManager->getUserSubscription($user_id);

// Thêm header
$page_title = "Thêm Phòng Trọ Mới";
include 'header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if ($current_plan): ?>
            <div class="alert alert-info mb-4">
                <h5>Thông tin gói dịch vụ của bạn</h5>
                <p><strong>Gói hiện tại:</strong> <?php echo $current_plan['plan_name']; ?></p>
                <?php if ($current_plan['max_posts'] !== null): ?>
                    <p><strong>Số tin đăng còn lại:</strong> <?php echo $postCheck['remaining_posts']; ?> tin</p>
                <?php else: ?>
                    <p><strong>Số tin đăng còn lại:</strong> Không giới hạn</p>
                <?php endif; ?>
                <?php if ($current_plan['end_date']): ?>
                    <p><strong>Ngày hết hạn:</strong> <?php echo date('d/m/Y', strtotime($current_plan['end_date'])); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Thêm Phòng Trọ Mới</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo $error; ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề phòng</label>
                            <input type="text" class="form-control" name="title" 
                                   value="<?php echo $_POST['title'] ?? ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea class="form-control" name="description" rows="3"><?php echo $_POST['description'] ?? ''; ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Loại phòng</label>
                                <select class="form-select" name="type">
                                    <option value="Phòng trọ">Phòng trọ</option>
                                    <option value="Chung cư mini">Chung cư mini</option>
                                    <option value="Studio">Studio</option>
                                    <option value="Nhà nguyên căn">Nhà nguyên căn</option>
                                    <option value="Ở ghép">Ở ghép</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Diện tích (m²)</label>
                                <input type="number" step="0.1" class="form-control" name="area" 
                                       value="<?php echo $_POST['area'] ?? ''; ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Giá thuê (VNĐ)</label>
                                <input type="number" class="form-control" name="price" 
                                       value="<?php echo $_POST['price'] ?? ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số người ở tối đa</label>
                                <input type="number" class="form-control" name="max_occupants" 
                                       value="<?php echo $_POST['max_occupants'] ?? 2; ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Địa chỉ</label>
                                <input type="text" class="form-control" name="address" 
                                       value="<?php echo $_POST['address'] ?? ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Quận/Huyện</label>
                                <input type="text" class="form-control" name="district" 
                                       value="<?php echo $_POST['district'] ?? ''; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Thành phố</label>
                            <input type="text" class="form-control" name="city" 
                                   value="<?php echo $_POST['city'] ?? 'TP. Hồ Chí Minh'; ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tiện ích</label>
                                <div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="Wifi miễn phí">
                                        <label class="form-check-label">Wifi miễn phí</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="Máy lạnh">
                                        <label class="form-check-label">Máy lạnh</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="Nhà vệ sinh riêng">
                                        <label class="form-check-label">Nhà vệ sinh riêng</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cơ sở vật chất</label>
                                <div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="facilities[]" value="Tự do giờ giấc">
                                        <label class="form-check-label">Tự do giờ giấc</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="facilities[]" value="Bảo vệ 24/7">
                                        <label class="form-check-label">Bảo vệ 24/7</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="facilities[]" value="Để xe miễn phí">
                                        <label class="form-check-label">Để xe miễn phí</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Hình ảnh phòng</label>
                            <input type="file" class="form-control" name="images[]" multiple accept="image/*">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Thêm Phòng</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>