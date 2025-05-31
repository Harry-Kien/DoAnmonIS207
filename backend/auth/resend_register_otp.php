<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['temp_register_data'])) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin đăng ký']);
    exit();
}

require_once '../mail/send_mail.php';

// Tạo OTP mới
$new_otp = sprintf("%06d", mt_rand(1, 999999));
$_SESSION['register_otp'] = $new_otp;
$_SESSION['otp_expiry'] = time() + (15 * 60); // 15 phút

// Gửi email OTP
$email = $_SESSION['temp_register_data']['email'];
if (sendOTPEmail($email, $new_otp)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể gửi email']);
}
?> 