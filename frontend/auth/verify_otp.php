<?php
session_start();
require_once "../../backend/config/config.php";

// Kiểm tra xem người dùng có quyền truy cập trang này không
if (!isset($_SESSION['reset_email'])) {
    header("location: forgot_password.php");
    exit();
}

$otp = "";
$otp_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate OTP
    if (empty(trim($_POST["otp"]))) {
        $otp_err = "Vui lòng nhập mã OTP.";
    } elseif (strlen(trim($_POST["otp"])) != 6) {
        $otp_err = "Mã OTP phải có 6 chữ số.";
    } else {
        $otp = trim($_POST["otp"]);
    }

    // Kiểm tra OTP
    if (empty($otp_err)) {
        $email = $_SESSION['reset_email'];
        $sql = "SELECT id, reset_otp, reset_otp_expiry FROM user WHERE email = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $user_id, $db_otp, $otp_expiry);
                    mysqli_stmt_fetch($stmt);
                    
                    // Kiểm tra OTP còn hiệu lực không
                    if (strtotime($otp_expiry) > time()) {
                        // Kiểm tra OTP có khớp không (so sánh trực tiếp)
                        if ($otp == $db_otp) {
                            // OTP đúng, chuyển đến trang đặt lại mật khẩu
                            $_SESSION['reset_user_id'] = $user_id;
                            header("location: reset_password.php");
                            exit();
                        } else {
                            $otp_err = "Mã OTP không chính xác.";
                        }
                    } else {
                        $otp_err = "Mã OTP đã hết hạn. Vui lòng yêu cầu mã mới.";
                    }
                } else {
                    $otp_err = "Không tìm thấy tài khoản.";
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);
}

// Thêm phần header
$page_title = "Xác Nhận OTP";
include "../../frontend/pages/header.php";
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Xác Nhận Mã OTP</h4>
                </div>
                <div class="card-body">
                    <?php
                    if (!empty($otp_err)) {
                        echo '<div class="alert alert-danger">' . $otp_err . '</div>';
                    }
                    ?>
                    <p class="text-center">Mã OTP đã được gửi đến email: <?php echo $_SESSION['reset_email']; ?></p>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="mb-3">
                            <label for="otp" class="form-label">Nhập Mã OTP</label>
                            <input type="text" 
                                   class="form-control <?php echo (!empty($otp_err)) ? 'is-invalid' : ''; ?>" 
                                   id="otp" 
                                   name="otp" 
                                   maxlength="6" 
                                   pattern="\d{6}" 
                                   required 
                                   placeholder="Nhập 6 chữ số">
                            <?php if (!empty($otp_err)): ?>
                                <div class="invalid-feedback"><?php echo $otp_err; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Xác Nhận</button>
                        </div>
                    </form>
                    <div class="mt-3 text-center">
                        <form action="forgot_password.php" method="post">
                            <input type="hidden" name="email" value="<?php echo $_SESSION['reset_email']; ?>">
                            <button type="submit" class="btn btn-link p-0">Gửi lại mã OTP</button>
                        </form>
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