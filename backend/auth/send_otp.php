<?php
session_start();
require_once "../../backend/config/config.php"; // Updated path to the correct config file
header('Content-Type: application/json');

// Nhận dữ liệu từ form
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$fullname = $_POST['full_name'] ?? '';
$phone = $_POST['phone'] ?? '';
$password = $_POST['password'] ?? '';

// Kiểm tra username và email đã tồn tại chưa
$stmt = mysqli_prepare($conn, "SELECT id FROM user WHERE username = ? OR email = ?");
mysqli_stmt_bind_param($stmt, "ss", $username, $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Tên đăng nhập hoặc email đã được sử dụng'
    ]);
    exit;
}

// Tạo mã OTP ngẫu nhiên 6 số
$otp = sprintf("%06d", mt_rand(0, 999999));

// Lưu thông tin đăng ký và OTP vào session
$_SESSION['registration'] = [
    'username' => $username,
    'email' => $email,
    'fullname' => $fullname, // Sẽ được lưu vào trường full_name trong DB
    'phone' => $phone,
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'otp' => $otp,
    'otp_time' => time()
];

// Gửi email chứa mã OTP
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
    $mail->CharSet = 'UTF-8';

    // Người nhận
    $mail->setFrom('kientrantrung3@gmail.com', 'Homeseeker');
    $mail->addAddress($email, $fullname);

    // Nội dung
    $mail->isHTML(true);
    $mail->Subject = 'Xác thực đăng ký tài khoản Homeseeker';
    $mail->Body = "
        <h2>Xin chào $fullname,</h2>
        <p>Cảm ơn bạn đã đăng ký tài khoản tại Homeseeker.</p>
        <p>Mã xác thực OTP của bạn là: <strong>$otp</strong></p>
        <p>Mã này sẽ hết hạn sau 5 phút.</p>
        <p>Vui lòng không chia sẻ mã này với bất kỳ ai.</p>
    ";

    $mail->send();
    echo json_encode([
        'success' => true,
        'message' => 'Mã OTP đã được gửi đến email của bạn'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể gửi email: ' . $mail->ErrorInfo
    ]);
}
?> 