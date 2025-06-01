<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php';

function sendOTPEmail($to_email, $otp) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'kientrantrung3@gmail.com';
        $mail->Password = 'kjgr qnvy axtn iosd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom('kientrantrung3@gmail.com', 'Homeseeker');
        $mail->addAddress($to_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Xác thực tài khoản Homeseeker';
        $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2 style="color: #333;">Xác thực tài khoản Homeseeker</h2>
                <p>Cảm ơn bạn đã đăng ký tài khoản tại Homeseeker. Để hoàn tất quá trình đăng ký, vui lòng nhập mã OTP sau:</p>
                <div style="background-color: #f4f4f4; padding: 15px; text-align: center; margin: 20px 0;">
                    <h1 style="color: #007bff; margin: 0; letter-spacing: 5px;">' . $otp . '</h1>
                </div>
                <p>Mã OTP này sẽ hết hạn sau 15 phút.</p>
                <p>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email.</p>
                <hr style="border: 1px solid #eee; margin: 20px 0;">
                <p style="color: #666; font-size: 12px;">Email này được gửi tự động, vui lòng không trả lời.</p>
            </div>';

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?> 