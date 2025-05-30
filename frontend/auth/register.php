<?php
// Khởi động session
session_start();

// Nếu đã đăng nhập, chuyển hướng về trang chính
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: ../../frontend/pages/index.php");
    exit;
}

// Tùy chỉnh tiêu đề trang
$page_title = "Đăng ký - Homeseeker";

// Include header (đảm bảo đường dẫn đúng)
include '../../frontend/pages/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary">
                    <h4 class="mb-0 text-white">Đăng ký tài khoản</h4>
                </div>
                <div class="card-body">
                    <?php
                    if (isset($_GET['error'])) {
                        echo '<div class="alert alert-danger mb-3">' . htmlspecialchars($_GET['error']) . '</div>';
                    }
                    ?>
                    <form action="../../backend/auth/register_process.php" method="post" autocomplete="off">
                        <div class="mb-3">
                            <label for="username" class="form-label">Tên đăng nhập</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="fullname" class="form-label">Họ và tên</label>
                            <input type="text" class="form-control" id="fullname" name="fullname" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Số điện thoại</label>
                            <input type="tel" class="form-control" id="phone" name="phone" pattern="[0-9]{10,11}">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">Tôi đồng ý với các điều khoản dịch vụ</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Đăng ký</button>
                        </div>
                    </form>
                    <p class="mt-3 text-center">Đã có tài khoản? <a href="../../frontend/auth/login.php">Đăng nhập</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer (đảm bảo đường dẫn đúng)
include '../../frontend/pages/footer.php';
?>