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
$subscription_id = isset($_GET['subscription_id']) ? intval($_GET['subscription_id']) : 0;

if (!$subscription_id) {
    $_SESSION['error'] = "Thông tin gói dịch vụ không hợp lệ";
    header("Location: ../../frontend/pages/banggia.php");
    exit();
}

// Khởi tạo subscription manager
$subscriptionManager = new SubscriptionManager($conn);

try {
    // Kiểm tra xem gói có tồn tại và còn hạn không
    $previous_subscription = $subscriptionManager->getPreviousValidSubscription($user_id);
    
    if (!$previous_subscription || $previous_subscription['id'] != $subscription_id) {
        throw new Exception("Gói dịch vụ không tồn tại hoặc đã hết hạn");
    }
    
    // Kích hoạt lại gói cũ
    $subscriptionManager->reactivatePreviousSubscription($user_id, $subscription_id);
    
    $_SESSION['success'] = "Đã kích hoạt lại gói " . $previous_subscription['plan_name'] . " thành công";
} catch (Exception $e) {
    $_SESSION['error'] = "Có lỗi xảy ra: " . $e->getMessage();
}

header("Location: ../../frontend/pages/banggia.php");
exit(); 