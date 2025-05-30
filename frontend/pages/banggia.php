<?php
// Khởi động session
session_start();

// Tùy chỉnh tiêu đề trang
$page_title = "Bảng giá dịch vụ - Homeseeker";

// Include các file cần thiết
require_once(__DIR__ . '/../../backend/config/config.php');
require_once(__DIR__ . '/../../backend/user/subscription_manager.php');

// Khởi tạo subscription manager
$subscriptionManager = new SubscriptionManager($conn);

// Lấy danh sách gói dịch vụ
$plans = $subscriptionManager->getAllPlans();

// Lấy thông tin gói hiện tại nếu đã đăng nhập
$current_plan = null;
if (isset($_SESSION['user_id'])) {
    $current_plan = $subscriptionManager->getUserSubscription($_SESSION['user_id']);
}

// Include header
include '../../frontend/pages/header.php';
?>

<!-- Banner -->
<section class="page-title" style="position: relative; background-color: rgba(0,0,0,0.6); color: white; padding: 80px 0; text-align: center; background-image: url('../assets/images/anhbanner.jpg'); background-size: cover; background-position: center;">
    <div class="container position-relative z-1">
        <h1 class="display-4 mb-3">Bảng giá dịch vụ</h1>
        <p class="lead" style="color: rgba(255,255,255,0.7);">Lựa chọn mức giá phù hợp với nhu cầu của bạn</p>
    </div>
</section>

<!-- Hiển thị thông báo -->
<section class="py-2">
    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
    </div>
</section>

<!-- Danh sách các gói -->
<?php include __DIR__ . '/../components/plan-cards.php'; ?>

<!-- Câu hỏi thường gặp -->
<?php include __DIR__ . '/../components/faq-section.php'; ?>

<!-- Modal thanh toán -->
<?php include __DIR__ . '/../components/paymet-modal.php'; ?>



<!-- CSS riêng cho banggia -->
<link rel="stylesheet" href="../assets/css/banggia-payment.css">

<!-- Script riêng cho bảng giá -->
<script src="../assets/js/banggia-payment.js"></script>
<?php include __DIR__ . '/../components/support-box.php'; ?>

<?php
// Include footer
include '../../frontend/pages/footer.php';
?>
