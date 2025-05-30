<?php
session_start();
require_once '../../backend/config/config.php';
require_once '../../backend/user/subscription_manager.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vui lòng đăng nhập để thực hiện thao tác này";
    header("Location: ../../frontend/auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$plan = isset($_GET['plan']) ? $_GET['plan'] : 'basic';

// Chỉ cho phép hạ xuống gói "Cơ bản"
if ($plan !== 'basic') {
    $_SESSION['error'] = "Chỉ hỗ trợ chuyển về gói Cơ bản";
    header("Location: ../../frontend/pages/banggia.php");
    exit();
}

// Khởi tạo subscription manager
$subscriptionManager = new SubscriptionManager($conn);

// Lấy thông tin gói hiện tại
$current_plan = $subscriptionManager->getUserSubscription($user_id);

// Nếu đã là gói "Cơ bản", không làm gì cả
if ($current_plan['plan_code'] === 'basic') {
    $_SESSION['info'] = "Bạn đang sử dụng gói Cơ bản";
    header("Location: ../../frontend/pages/banggia.php");
    exit();
}

// Lấy thông tin gói "Cơ bản"
$plan_sql = "SELECT duration FROM plans WHERE plan_code = 'basic'";
$stmt = mysqli_prepare($conn, $plan_sql);
mysqli_stmt_execute($stmt);
$plan_result = mysqli_stmt_get_result($stmt);
$plan_data = mysqli_fetch_assoc($plan_result);

if (!$plan_data) {
    $_SESSION['error'] = "Không tìm thấy thông tin gói Cơ bản";
    header("Location: ../../frontend/pages/banggia.php");
    exit();
}

// Tính thời gian hết hạn
$duration = $plan_data['duration'];
$expires_at = date('Y-m-d H:i:s', strtotime("+$duration days"));

// Bắt đầu giao dịch
mysqli_begin_transaction($conn);

try {
    // Vô hiệu hóa gói hiện tại
    $sql = "UPDATE user_subscriptions SET is_active = 0, updated_at = NOW() WHERE user_id = ? AND is_active = 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    $result = mysqli_stmt_execute($stmt);

    if (!$result) {
        throw new Exception('Lỗi khi vô hiệu hóa gói hiện tại');
    }

    // Tạo gói mới
    $subscription_id = $subscriptionManager->createSubscription($user_id, 'basic', null, $expires_at);

    if (!$subscription_id) {
        throw new Exception('Lỗi khi tạo gói mới');
    }

    // Cam kết giao dịch
    mysqli_commit($conn);

    $_SESSION['success'] = "Đã chuyển sang gói Cơ bản thành công";
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = "Có lỗi xảy ra khi chuyển gói dịch vụ: " . $e->getMessage();
}

header("Location: ../../frontend/pages/banggia.php");
exit();
?>