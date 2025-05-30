<?php
// Khởi động session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lưu lại URL redirect và plan (nếu có)
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';
$plan = isset($_GET['plan']) ? $_GET['plan'] : '';

// Nếu người dùng đã đăng nhập, chuyển hướng đến trang chủ hoặc trang redirect
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if(!empty($redirect)) {
        // Lưu thông tin gói dịch vụ đã chọn (nếu có)
        if(!empty($plan)) {
            $_SESSION['selected_plan'] = $plan;
        }
        header("location: $redirect");
    } else {
        header("location: ../../frontend/pages/index.php");
    }
    exit;
}

// Tự động điền username nếu có cookie
$username_cookie = isset($_COOKIE['remember_username']) ? $_COOKIE['remember_username'] : '';

// Tùy chỉnh tiêu đề trang
$page_title = "Đăng nhập - Homeseeker";

// Include header
include '../pages/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Đăng nhập</h2>
                    
                    <?php 
                    if(isset($_GET["login_error"])){
                        echo '<div class="alert alert-danger">' . htmlspecialchars($_GET["login_error"]) . '</div>';
                    }
                    ?>
                    
                    <!-- Thêm các hidden field để lưu thông tin redirect và plan -->
                    <form action="../../backend/auth/login_process.php" method="post">
                        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                        <input type="hidden" name="plan" value="<?php echo htmlspecialchars($plan); ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Email hoặc Username</label>
                            <input type="text" class="form-control" id="username" name="username" required value="<?php echo htmlspecialchars($username_cookie); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe" name="remember" <?php if($username_cookie) echo 'checked'; ?>>
                            <label class="form-check-label" for="rememberMe">Ghi nhớ đăng nhập</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning">Đăng nhập</button>
                        </div>
                        <div class="mt-3 text-center">
                            <a href="forgot_password.php" class="text-decoration-none">Quên mật khẩu?</a>
                            <hr>
                            <p class="mb-0">Chưa có tài khoản? 
                                <a href="register.php<?php echo !empty($redirect) ? '?redirect='.htmlspecialchars($redirect).((!empty($plan)) ? '&plan='.htmlspecialchars($plan) : '') : ''; ?>" class="text-decoration-none">Đăng ký</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../pages/footer.php';
?>