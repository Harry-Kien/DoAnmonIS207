<?php
session_start();
require_once "../../backend/config/config.php";

$email = "";
$email_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Vui lòng nhập email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Kiểm tra email có tồn tại không
    if (empty($email_err)) {
        $sql = "SELECT id, username FROM user WHERE email = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $user_id, $username);
                    mysqli_stmt_fetch($stmt);
                    
                    // Tạo OTP
                    $otp = sprintf("%06d", mt_rand(1, 999999));
                    $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    
                    // Lưu OTP vào CSDL
                    $update_sql = "UPDATE user SET reset_otp = ?, reset_otp_expiry = ? WHERE id = ?";
                    if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                        mysqli_stmt_bind_param($update_stmt, "ssi", $otp, $otp_expiry, $user_id);
                        
                        if (mysqli_stmt_execute($update_stmt)) {
                            // Gửi OTP qua email
                            $subject = "Mã OTP Đặt Lại Mật Khẩu - Homeseeker";
                            $message = "Xin chào $username,\n\n";
                            $message .= "Mã OTP đặt lại mật khẩu của bạn là: $otp\n";
                            $message .= "Mã này sẽ hết hạn sau 15 phút.\n";
                            $message .= "Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.";
                            
                            $headers = "From: support@homeseeker.com\r\n";
                            
                            if (mail($email, $subject, $message, $headers)) {
                                // Chuyển hướng đến trang nhập OTP
                                $_SESSION['reset_email'] = $email;
                                $_SESSION['reset_user_id'] = $user_id;
                                header("Location: verify_otp.php");
                                exit();
                            } else {
                                $email_err = "Không thể gửi OTP. Vui lòng thử lại sau.";
                            }
                        }
                        mysqli_stmt_close($update_stmt);
                    }
                } else {
                    $email_err = "Không tìm thấy email này trong hệ thống.";
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);
}

// Thêm phần header
$page_title = "Quên Mật Khẩu";
include "../../frontend/pages/header.php";
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Quên Mật Khẩu</h4>
                </div>
                <div class="card-body">
                    <?php
                    if (!empty($email_err)) {
                        echo '<div class="alert alert-danger">' . $email_err . '</div>';
                    }
                    ?>
                    <form action="../../frontend/auth/forgot_password.php" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Nhập Email</label>
                            <input type="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" 
                                   id="email" name="email" value="<?php echo $email; ?>" required>
                            <?php if (!empty($email_err)): ?>
                                <div class="invalid-feedback"><?php echo $email_err; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Gửi Mã OTP</button>
                        </div>
                    </form>
                    <div class="mt-3 text-center">
                        <a href="../../frontend/auth/login.php" class="text-decoration-none">Quay lại đăng nhập</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Thêm phần footer
include "../../frontend/pages/footer.php";
?>