<?php
session_start();
require_once "../../backend/config/config.php"; // Add config file include
header('Content-Type: application/json');

// Nhận email từ request
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';

if (!isset($_SESSION['registration']) || $_SESSION['registration']['email'] !== $email) {
    echo json_encode([
        'success' => false,
        'message' => 'Không tìm thấy thông tin đăng ký'
    ]);
    exit;
}

// Tạo mã OTP mới
$otp = sprintf("%06d", mt_rand(0, 999999));

// Cập nhật OTP mới trong session
$_SESSION['registration']['otp'] = $otp;
$_SESSION['registration']['otp_time'] = time();

// Gửi email chứa mã OTP mới
require '../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Cấu hình server
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'kientrantrung3@gmail.com';
    $mail->Password = 'kjgr qnvy axtn iosd';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Người nhận
    $mail->setFrom('kientrantrung3@gmail.com', 'Homeseeker');
    $mail->addAddress($email, $_SESSION['registration']['fullname']);

    // Nội dung
    $mail->isHTML(true);
    $mail->Subject = 'Mã OTP mới - Homeseeker';
    $mail->Body = "
        <h2>Xin chào {$_SESSION['registration']['fullname']},</h2>
        <p>Đây là mã OTP mới của bạn để xác thực đăng ký tài khoản tại Homeseeker.</p>
        <p>Mã xác thực OTP mới của bạn là: <strong>$otp</strong></p>
        <p>Mã này sẽ hết hạn sau 5 phút.</p>
        <p>Vui lòng không chia sẻ mã này với bất kỳ ai.</p>
    ";

    $mail->send();
    echo json_encode([
        'success' => true,
        'message' => 'Mã OTP mới đã được gửi đến email của bạn'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể gửi email: ' . $mail->ErrorInfo
    ]);
}
?> 