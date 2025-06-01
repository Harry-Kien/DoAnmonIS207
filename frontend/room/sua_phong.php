<?php
require_once '../../backend/auth/session.php';
require_once '../../backend/config/config.php';

$room_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Lấy thông tin phòng
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $room_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();

if (!$room) {
    echo "<div class='alert alert-danger'>Không tìm thấy phòng hoặc bạn không có quyền sửa.</div>";
    exit;
}

// Hiển thị thông báo thành công nếu có
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Hiển thị lỗi nếu có
$errors = $_SESSION['room_errors'] ?? [];
unset($_SESSION['room_errors']);

// Include header
$page_title = "Sửa phòng";
include '../pages/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h2 class="h5 mb-0">Sửa thông tin phòng</h2>
                </div>
                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $err) echo "<div>$err</div>"; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="../../backend/rooms/xu_ly_sua_phong.php" method="post">
                        <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                        <input type="hidden" name="district_id" value="<?php echo $room['district_id']; ?>">
                        <input type="hidden" name="city_id" value="<?php echo $room['city_id']; ?>">
                        <input type="hidden" name="lat" value="<?php echo $room['lat']; ?>">
                        <input type="hidden" name="lng" value="<?php echo $room['lng']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($room['title']); ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Loại phòng <span class="text-danger">*</span></label>
                                <select name="type" class="form-select" required>
                                    <option value="Phòng trọ" <?php if($room['type']=='Phòng trọ') echo 'selected'; ?>>Phòng trọ</option>
                                    <option value="Chung cư mini" <?php if($room['type']=='Chung cư mini') echo 'selected'; ?>>Chung cư mini</option>
                                    <option value="Nhà nguyên căn" <?php if($room['type']=='Nhà nguyên căn') echo 'selected'; ?>>Nhà nguyên căn</option>
                                    <option value="Ở ghép" <?php if($room['type']=='Ở ghép') echo 'selected'; ?>>Ở ghép</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số người tối đa <span class="text-danger">*</span></label>
                                <input type="number" name="max_occupants" class="form-control" value="<?php echo $room['max_occupants']; ?>" min="1" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Diện tích (m²) <span class="text-danger">*</span></label>
                                <input type="number" name="area" class="form-control" value="<?php echo $room['area']; ?>" min="1" step="0.1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Giá (VNĐ/tháng) <span class="text-danger">*</span></label>
                                <input type="number" name="price" class="form-control" value="<?php echo floatval($room['price']); ?>" min="1" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Địa chỉ <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($room['address']); ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Quận/Huyện <span class="text-danger">*</span></label>
                                <input type="text" name="district" class="form-control" value="<?php echo htmlspecialchars($room['district']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Thành phố <span class="text-danger">*</span></label>
                                <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($room['city']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mô tả chi tiết</label>
                            <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($room['description']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tiện ích</label>
                            <textarea name="amenities" class="form-control" rows="3" placeholder="VD: Wifi, máy lạnh, tủ lạnh..."><?php echo htmlspecialchars($room['amenities']); ?></textarea>
                            <small class="text-muted">Liệt kê các tiện ích có sẵn, mỗi tiện ích cách nhau bằng dấu phẩy.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Cơ sở vật chất</label>
                            <textarea name="facilities" class="form-control" rows="3" placeholder="VD: Bãi đỗ xe, bảo vệ 24/7..."><?php echo htmlspecialchars($room['facilities']); ?></textarea>
                            <small class="text-muted">Liệt kê các cơ sở vật chất có sẵn, mỗi mục cách nhau bằng dấu phẩy.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tên người liên hệ <span class="text-danger">*</span></label>
                            <input type="text" name="contact_name" class="form-control" value="<?php echo htmlspecialchars($room['contact_name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại liên hệ <span class="text-danger">*</span></label>
                            <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($room['contact_phone']); ?>" required>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="my_rooms.php" class="btn btn-secondary">Quay lại</a>
                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../pages/footer.php'; ?>