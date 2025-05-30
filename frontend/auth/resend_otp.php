<?php
session_start();
require_once "config.php";

// Kiểm tra xem người dùng có quyền truy cập trang này không
if (!isset($_SESSION['reset_email'])) {
    header("location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];

// Tìm user theo email
$sql = "SELECT id, username FROM user WHERE email = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $email);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) == 1) {
            mysqli_stmt_bind_result($stmt, $user_id, $username);
            mysqli_stmt_fetch($stmt);
            
            // Tạo OTP mới
            $otp = sprintf("%06d", mt_rand(1, 999999));
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            // Lưu OTP mới vào CSDL
            $update_sql = "UPDATE user SET reset_otp = ?, reset_otp_expiry = ? WHERE id = ?";
            if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                $hashed_otp = password_hash($otp, PASSWORD_DEFAULT);
                mysqli_stmt_bind_param($update_stmt, "ssi", $hashed_otp, $otp_expiry, $user_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    // Gửi OTP mới qua email
                    $subject = "Mã OTP Đặt Lại Mật Khẩu Mới - Homeseeker";
                    $message = "Xin chào $username,\n\n";
                    $message .= "Mã OTP mới để đặt lại mật khẩu của bạn là: $otp\n";
                    $message .= "Mã này sẽ hết hạn sau 15 phút.\n";
                    $message .= "Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.";
                    
                    $headers = "From: support@homeseeker.com\r\n";
                    
                    if (mail($email, $subject, $message, $headers)) {
                        // Chuyển hướng về trang xác nhận OTP với thông báo
                        $_SESSION['resend_message'] = "Mã OTP mới đã được gửi đến email của bạn.";
                        header("location: verify_otp.php");
                        exit();
                    } else {
                        $error_message = "Không thể gửi OTP. Vui lòng thử lại sau.";
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