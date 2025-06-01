<?php
session_start();
require_once "../../backend/config/config.php";
require_once '../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Kiểm tra xem người dùng có quyền truy cập trang này không
if (!isset($_SESSION['reset_email'])) {
    header("location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];

// Tìm user theo email
$sql = "SELECT id, username, full_name FROM user WHERE email = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $email);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) == 1) {
            mysqli_stmt_bind_result($stmt, $user_id, $username, $fullname);
            mysqli_stmt_fetch($stmt);
            
            // Tạo OTP mới
            $otp = sprintf("%06d", mt_rand(1, 999999));
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            // Lưu OTP mới vào CSDL (lưu dạng text thường)
            $update_sql = "UPDATE user SET reset_otp = ?, reset_otp_expiry = ? WHERE id = ?";
            if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                mysqli_stmt_bind_param($update_stmt, "ssi", $otp, $otp_expiry, $user_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    // Gửi OTP mới qua email sử dụng PHPMailer
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
                        $mail->addAddress($email, $fullname ?? $username);

                        // Nội dung
                        $mail->isHTML(true);
                        $mail->Subject = 'Mã OTP Đặt Lại Mật Khẩu Mới - Homeseeker';
                        $mail->Body = "
                            <h2>Xin chào " . ($fullname ?? $username) . ",</h2>
                            <p>Mã OTP mới để đặt lại mật khẩu của bạn là: <strong>$otp</strong></p>
                            <p>Mã này sẽ hết hạn sau 15 phút.</p>
                            <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
                        ";

                        $mail->send();
                        
                        // Chuyển hướng về trang xác nhận OTP với thông báo
                        $_SESSION['resend_message'] = "Mã OTP mới đã được gửi đến email của bạn.";
                        header("location: verify_otp.php");
                        exit();
                    } catch (Exception $e) {
                        $error_message = "Không thể gửi OTP: " . $mail->ErrorInfo;
                    }
                }
                mysqli_stmt_close($update_stmt);
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
}

// Nếu có lỗi, chuyển về trang quên mật khẩu
if (isset($error_message)) {
    $_SESSION['error_message'] = $error_message;
    header("location: forgot_password.php");
    exit();
}
?>