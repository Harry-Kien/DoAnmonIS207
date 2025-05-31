<?php
session_start();
require_once "../../config/db.php";

header('Content-Type: application/json');

// Nhận dữ liệu từ form
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$fullname = $_POST['fullname'] ?? '';
$phone = $_POST['phone'] ?? '';
$password = $_POST['password'] ?? '';

// Kiểm tra username và email đã tồn tại chưa
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $email]);
if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch();
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
    $mail->Username = 'your-email@gmail.com'; // Thay bằng email của bạn
    $mail->Password = 'your-app-password'; // Thay bằng mật khẩu ứng dụng
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Người nhận
    $mail->setFrom('your-email@gmail.com', 'Homeseeker');
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