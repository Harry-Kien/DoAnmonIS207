<?php
// File này hiển thị thông tin gói đăng ký của người dùng
if (!isset($_SESSION['user_id'])) {
    header('Location: /frontend/auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin gói đăng ký hiện tại của người dùng
$subscription_sql = "SELECT us.*, p.name as plan_name, p.description, p.features
                    FROM user_subscriptions us 
                    LEFT JOIN plans p ON us.plan_id = p.id
                    WHERE us.user_id = ? 
                    ORDER BY us.end_date DESC 
                    LIMIT 1";

// Nếu không có bảng plans hoặc join không thành công
if (!$stmt = mysqli_prepare($conn, $subscription_sql)) {
    $subscription_sql = "SELECT * FROM user_subscriptions 
                        WHERE user_id = ? 
                        ORDER BY end_date DESC 
                        LIMIT 1";
    $stmt = mysqli_prepare($conn, $subscription_sql);
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Mặc định sẽ là gói basic nếu không tìm thấy gói nào
$current_plan = [
    'name' => 'Cơ bản',
    'plan_id' => 1,
    'is_active' => 1,
    'end_date' => null
];

$has_subscription = false;

if ($subscription = mysqli_fetch_assoc($result)) {
    $has_subscription = true;
    
    // Kiểm tra ngày hết hạn để xác định trạng thái thực tế
    $is_expired = strtotime($subscription['end_date']) < time();
    
    // Nếu trạng thái trong DB không khớp với thực tế, cập nhật DB
    if (($subscription['is_active'] == 1 && $is_expired) || ($subscription['is_active'] == 0 && !$is_expired)) {
        $new_active_status = $is_expired ? 0 : 1;
        $update_status_sql = "UPDATE user_subscriptions SET is_active = ? WHERE id = ?";
        $status_stmt = mysqli_prepare($conn, $update_status_sql);
        mysqli_stmt_bind_param($status_stmt, "ii", $new_active_status, $subscription['id']);
        mysqli_stmt_execute($status_stmt);
        
        // Cập nhật lại giá trị is_active
        $subscription['is_active'] = $new_active_status;
    }
    
    // Gán tên gói dựa trên plan_id nếu không có thông tin từ bảng plans
    if (!isset($subscription['plan_name'])) {
        switch ($subscription['plan_id']) {
            case 1:
                $subscription['plan_name'] = 'Cơ bản';
                break;
            case 2:
                $subscription['plan_name'] = 'Gói phổ biến';
                break;
            case 3:
                $subscription['plan_name'] = 'Gói cao cấp';
                break;
            default:
                $subscription['plan_name'] = 'Không xác định';
        }
    }
    
    $current_plan = $subscription;
}
?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-star text-warning me-2"></i> Thông tin gói đăng ký</h5>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">
                <?php echo htmlspecialchars($current_plan['plan_name']); ?>
                <span class="badge <?php echo !$is_expired ? 'bg-success' : 'bg-danger'; ?>">
                    <?php echo !$is_expired ? 'Đang hoạt động' : 'Hết hạn'; ?>
                </span>
            </h5>
            <?php if ($has_subscription && $current_plan['plan_id'] > 1): ?>
                <a href="/frontend/user/upgrade.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-up"></i> Nâng cấp
                </a>
            <?php else: ?>
                <a href="/frontend/banggia.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-arrow-up"></i> Nâng cấp gói
                </a>
            <?php endif; ?>
        </div>
        
        <?php if ($has_subscription): ?>
            <div class="row mb-3">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Ngày bắt đầu:</strong></p>
                    <p><?php echo date('d/m/Y', strtotime($current_plan['start_date'])); ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Ngày kết thúc:</strong></p>
                    <p><?php echo date('d/m/Y', strtotime($current_plan['end_date'])); ?></p>
                </div>
            </div>
            
            <?php if ($is_expired): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i> Gói đăng ký của bạn đã hết hạn. Vui lòng gia hạn để tiếp tục sử dụng các tính năng cao cấp.
                </div>
                <a href="/frontend/banggia.php" class="btn btn-primary">
                    <i class="fas fa-sync"></i> Gia hạn ngay
                </a>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> Gói đăng ký của bạn còn 
                    <strong>
                        <?php 
                        $days_left = ceil((strtotime($current_plan['end_date']) - time()) / (60 * 60 * 24));
                        echo $days_left;
                        ?> ngày
                    </strong> 
                    trước khi hết hạn.
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Bạn đang sử dụng gói cơ bản miễn phí. Hãy nâng cấp để sử dụng các tính năng cao cấp.
            </div>
            <a href="/frontend/banggia.php" class="btn btn-primary">
                <i class="fas fa-arrow-up"></i> Xem các gói nâng cấp
            </a>
        <?php endif; ?>
    </div>
</div> 