<?php
session_start();
require_once '../../backend/config/config.php';

// Kiểm tra quyền truy cập
if (!isset($_SESSION['reset_user_id'])) {
    header("Location: forgot_password.php");
    exit();
}

$new_password = $confirm_password = "";
$new_password_err = $confirm_password_err = "";

// Xử lý gửi lại OTP
if (isset($_POST['resend_otp'])) {
    header("Location: resend_otp.php?email=" . urlencode($_SESSION['reset_email']));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['resend_otp'])) {
    // Validate mật khẩu mới với nhiều điều kiện
    if (empty(trim($_POST["new_password"]))) {
        $new_password_err = "Vui lòng nhập mật khẩu mới.";
    } elseif (strlen(trim($_POST["new_password"])) < 8) {
        $new_password_err = "Mật khẩu phải có ít nhất 8 ký tự.";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", trim($_POST["new_password"]))) {
        $new_password_err = "Mật khẩu phải chứa ít nhất: 1 chữ hoa, 1 chữ thường, 1 số và 1 ký tự đặc biệt.";
    } else {
        $new_password = trim($_POST["new_password"]);
    }

    // Validate xác nhận mật khẩu
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Vui lòng xác nhận mật khẩu.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($new_password_err) && ($new_password != $confirm_password)) {
            $confirm_password_err = "Mật khẩu xác nhận không khớp.";
        }
    }

    // Kiểm tra lỗi trước khi cập nhật CSDL
    if (empty($new_password_err) && empty($confirm_password_err)) {
        $user_id = $_SESSION['reset_user_id'];
        
        // Kiểm tra mật khẩu mới không được trùng với mật khẩu cũ
        $check_sql = "SELECT password FROM user WHERE id = ?";
        if ($check_stmt = mysqli_prepare($conn, $check_sql)) {
            mysqli_stmt_bind_param($check_stmt, "i", $user_id);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_bind_result($check_stmt, $current_hashed_password);
            mysqli_stmt_fetch($check_stmt);
            mysqli_stmt_close($check_stmt);

            if (password_verify($new_password, $current_hashed_password)) {
                $new_password_err = "Mật khẩu mới không được trùng với mật khẩu cũ.";
            } else {
                // Cập nhật mật khẩu
                $sql = "UPDATE user SET password = ?, reset_otp = NULL, reset_otp_expiry = NULL, last_password_change = NOW() WHERE id = ?";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        // Đổi mật khẩu thành công
                        unset($_SESSION['reset_email']);
                        unset($_SESSION['reset_user_id']);
                        
                        // Chuyển hướng đến trang đăng nhập
                        $_SESSION['reset_success'] = "Mật khẩu đã được đặt lại thành công.";
                        header("Location: login.php");
                        exit();
                    } else {
                        $new_password_err = "Đã xảy ra lỗi. Vui lòng thử lại sau.";
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
    mysqli_close($conn);
}

// Thêm phần header
$page_title = "Đặt Lại Mật Khẩu";
include "../../frontend/pages/header.php";
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Đặt Lại Mật Khẩu</h4>
                </div>
                <div class="card-body">
                    <form action="reset_password.php" method="post">
                        <!-- Nút gửi lại OTP -->
                        <div class="mb-3 text-end">
                            <button class="btn btn-link p-0" type="submit" name="resend_otp" value="1">Gửi lại mã OTP qua email</button>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Mật Khẩu Mới</label>
                            <div class="input-group">
                                <input type="password" 
                                       name="new_password" 
                                       id="new_password"
                                       class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" 
                                       required 
                                       placeholder="Ít nhất 8 ký tự, có chữ hoa, chữ thường, số và ký tự đặc biệt">
                                <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                    <i class="far fa-eye"></i>
                                </button>
                                <?php if (!empty($new_password_err)): ?>
                                    <div class="invalid-feedback"><?php echo $new_password_err; ?></div>
                                <?php endif; ?>
                            </div>
                            <small class="form-text text-muted">
                                Mật khẩu phải chứa ít nhất: 1 chữ hoa, 1 chữ thường, 1 số và 1 ký tự đặc biệt
                            </small>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Xác Nhận Mật Khẩu Mới</label>
                            <div class="input-group">
                                <input type="password" 
                                       name="confirm_password" 
                                       id="confirm_password"
                                       class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" 
                                       required
                                       placeholder="Nhập lại mật khẩu mới">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="far fa-eye"></i>
                                </button>
                                <?php if (!empty($confirm_password_err)): ?>
                                    <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Đặt Lại Mật Khẩu</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Chức năng hiển thị/ẩn mật khẩu
    document.getElementById('toggleNewPassword')?.addEventListener('click', function() {
        const passwordInput = document.getElementById('new_password');
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });

    document.getElementById('toggleConfirmPassword')?.addEventListener('click', function() {
        const passwordInput = document.getElementById('confirm_password');
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
</script>

<?php
// Thêm phần footer
include "../../frontend/pages/footer.php";
?>