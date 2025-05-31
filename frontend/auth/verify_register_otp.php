<?php
session_start();

// Kiểm tra nếu không có thông tin đăng ký trong session
if (!isset($_SESSION['temp_register_data'])) {
    header("Location: register.php");
    exit();
}

$page_title = "Xác thực OTP - Homeseeker";
include '../pages/header.php';

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once '../../backend/config/config.php';
    
    $otp = trim($_POST["otp"]);
    $user_data = $_SESSION['temp_register_data'];
    
    // Kiểm tra OTP
    if (empty($otp)) {
        $error = "Vui lòng nhập mã OTP";
    } elseif ($otp != $_SESSION['register_otp']) {
        $error = "Mã OTP không chính xác";
    } elseif (time() > $_SESSION['otp_expiry']) {
        $error = "Mã OTP đã hết hạn";
    } else {
        // OTP hợp lệ, tiến hành đăng ký tài khoản
        $hashed_password = password_hash($user_data['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO user (username, email, full_name, phone, password, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssss", 
                $user_data['username'],
                $user_data['email'],
                $user_data['fullname'],
                $user_data['phone'],
                $hashed_password
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $user_id = mysqli_insert_id($conn);
                
                // Thiết lập session đăng nhập
                $_SESSION["loggedin"] = true;
                $_SESSION["user_id"] = $user_id;
                $_SESSION["username"] = $user_data['username'];
                
                // Xóa dữ liệu tạm
                unset($_SESSION['temp_register_data']);
                unset($_SESSION['register_otp']);
                unset($_SESSION['otp_expiry']);
                
                // Chuyển hướng đến trang chủ
                header("Location: /IS207-hoomseeker/frontend/pages/index.php");
                exit();
            } else {
                $error = "Có lỗi xảy ra. Vui lòng thử lại sau.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary">
                    <h4 class="mb-0 text-white">Xác thực OTP</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <p class="text-center mb-4">
                        Chúng tôi đã gửi mã OTP đến email: <strong><?php echo $_SESSION['temp_register_data']['email']; ?></strong>
                    </p>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="otp" class="form-label">Nhập mã OTP</label>
                            <input type="text" class="form-control form-control-lg text-center" 
                                   id="otp" name="otp" maxlength="6" required
                                   pattern="\d{6}" title="Vui lòng nhập 6 chữ số">
                            <div class="form-text">
                                Mã OTP gồm 6 chữ số và có hiệu lực trong 15 phút
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Xác nhận</button>
                            <button type="button" class="btn btn-outline-secondary" id="resendOTP">
                                Gửi lại mã OTP
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('resendOTP').addEventListener('click', function() {
    this.disabled = true;
    this.innerHTML = 'Đang gửi...';
    
    fetch('/IS207-hoomseeker/backend/auth/resend_register_otp.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Mã OTP mới đã được gửi đến email của bạn');
        } else {
            alert('Có lỗi xảy ra khi gửi lại mã OTP. Vui lòng thử lại sau.');
        }
    })
    .catch(error => {
        alert('Có lỗi xảy ra. Vui lòng thử lại sau.');
    })
    .finally(() => {
        this.disabled = false;
        this.innerHTML = 'Gửi lại mã OTP';
    });
});

// Format input OTP
document.getElementById('otp').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>

<?php include '../pages/footer.php'; ?> 