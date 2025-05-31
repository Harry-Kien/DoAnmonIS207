<?php
session_start();
require_once '../../backend/config/config.php';
require_once '../../backend/user/subscription_manager.php';
require_once '../../vendor/autoload.php'; // Thêm autoload cho thư viện QR

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Kiểm tra xem có phải là yêu cầu POST không
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

// Nhận dữ liệu JSON từ yêu cầu
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

// Lấy thông tin
$plan = isset($input['plan']) ? $input['plan'] : '';
$price = isset($input['price']) ? floatval($input['price']) : 0;
$paymentCode = isset($input['paymentCode']) ? $input['paymentCode'] : '';
$paymentMethod = isset($input['paymentMethod']) ? $input['paymentMethod'] : 'banking';

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thanh toán']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Xác định mã gói
$plan_code = 'basic';
if ($plan === 'Tiêu chuẩn' || strtolower($plan) === 'tiêu chuẩn' || $plan === 'standard') {
    $plan_code = 'standard';
} elseif ($plan === 'Cao cấp' || strtolower($plan) === 'cao cấp' || $plan === 'premium') {
    $plan_code = 'premium';
}

// Lấy thông tin gói từ bảng plans
$plan_sql = "SELECT price, duration FROM plans WHERE plan_code = ?";
$stmt = mysqli_prepare($conn, $plan_sql);
mysqli_stmt_bind_param($stmt, "s", $plan_code);
mysqli_stmt_execute($stmt);
$plan_result = mysqli_stmt_get_result($stmt);
$plan_data = mysqli_fetch_assoc($plan_result);

if (!$plan_data) {
    echo json_encode(['success' => false, 'message' => 'Gói không tồn tại']);
    exit;
}

// Xác minh giá
if ($price != $plan_data['price']) {
    echo json_encode(['success' => false, 'message' => 'Số tiền không khớp với gói']);
    exit;
}

// Tính thời gian hết hạn
$duration = $plan_data['duration'];
$expires_at = date('Y-m-d H:i:s', strtotime("+$duration days"));

// Bắt đầu giao dịch cơ sở dữ liệu
mysqli_begin_transaction($conn);

try {
    // Lưu thông tin thanh toán
    $insert_payment_sql = "INSERT INTO payments (user_id, subscription_id, amount, payment_code, payment_method, status, created_at, updated_at) 
                          VALUES (?, 0, ?, ?, ?, 'pending', NOW(), NOW())";
    $stmt = mysqli_prepare($conn, $insert_payment_sql);
    mysqli_stmt_bind_param($stmt, "idss", $user_id, $price, $paymentCode, $paymentMethod);
    $payment_result = mysqli_stmt_execute($stmt);

    if (!$payment_result) {
        throw new Exception('Lỗi khi lưu thông tin thanh toán');
    }

    $payment_id = mysqli_insert_id($conn);

    // Tạo đăng ký gói dịch vụ
    $subscriptionManager = new SubscriptionManager($conn);
    $subscription_id = $subscriptionManager->createSubscription($user_id, $plan_code, $payment_id, $expires_at);

    if (!$subscription_id) {
        throw new Exception('Lỗi khi tạo gói dịch vụ');
    }

    // Tạo mã QR dựa trên phương thức thanh toán
    function generateQRCode($paymentMethod, $amount, $paymentCode) {
        $qrData = '';
        switch ($paymentMethod) {
            case 'momo':
                $qrData = "2|99|0917123456|HOOMSEEKER|0|0|0|{$amount}|{$paymentCode}";
                break;
            case 'vnpay':
                $qrData = "vnpay|0|{$paymentCode}|{$amount}|HOOMSEEKER";
                break;
            case 'banking':
                $qrData = "BNK|0|19037123456789|HOOMSEEKER|{$amount}|{$paymentCode}";
                break;
        }

        $qrCode = new QrCode($qrData);
        $qrCode->setSize(300);
        $qrCode->setMargin(10);

        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        // Lưu QR code vào thư mục tạm
        $qrPath = "../../frontend/assets/images/qr/{$paymentCode}.png";
        $result->saveToFile($qrPath);
        
        return "/frontend/assets/images/qr/{$paymentCode}.png";
    }

    // Tạo mã QR
    $qrPath = generateQRCode($paymentMethod, $price, $paymentCode);

    // Trả về kết quả thành công với đường dẫn QR
    echo json_encode([
        'success' => true,
        'message' => 'Thanh toán đang chờ xử lý',
        'data' => [
            'payment_id' => $payment_id,
            'subscription_id' => $subscription_id,
            'plan' => $plan,
            'plan_code' => $plan_code,
            'payment_code' => $paymentCode,
            'qr_path' => $qrPath
        ]
    ]);

} catch (Exception $e) {
    // Hoàn tác giao dịch nếu có lỗi
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conn);
exit;