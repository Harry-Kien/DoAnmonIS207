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
                    if (isset($_GET['success'])) {
                        echo '<div class="alert alert-success mb-3">' . htmlspecialchars($_GET['success']) . '</div>';
                    }
                    ?>
                    <form action="../../backend/auth/register_process.php" method="post" autocomplete="off" id="registerForm">
                        <div id="registration-form">
                            <div class="mb-3">
                                <label for="register_username" class="form-label">Tên đăng nhập</label>
                                <input type="text" class="form-control" id="register_username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Họ và tên</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Số điện thoại</label>
                                <input type="tel" class="form-control" id="phone" name="phone" pattern="[0-9]{10,11}" required>
                            </div>
                            <div class="mb-3">
                                <label for="register_password" class="form-label">Mật khẩu</label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="register_password" 
                                           name="password" 
                                           required 
                                           pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                                           title="Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt">
                                    <span class="input-group-text" style="cursor: pointer;" onclick="togglePasswordVisibility('register_password', this)">
                                        <i class="far fa-eye"></i>
                                    </span>
                                </div>
                                <div class="form-text">
                                    Mật khẩu phải có ít nhất:
                                    <ul class="mb-0">
                                        <li>8 ký tự</li>
                                        <li>1 chữ hoa</li>
                                        <li>1 chữ thường</li>
                                        <li>1 số</li>
                                        <li>1 ký tự đặc biệt (@$!%*?&)</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    Tôi đồng ý với <a href="/IS207-hoomseeker/frontend/pages/terms.php" target="_blank">điều khoản dịch vụ</a>
                                </label>
                            </div>
                            <div class="d-grid">
                                <button type="button" class="btn btn-primary" onclick="sendOTP()">Đăng ký</button>
                            </div>
                        </div>

                        <!-- Form xác thực OTP (ẩn ban đầu) -->
                        <div id="otp-form" style="display: none;">
                            <div class="mb-3">
                                <label for="otp" class="form-label">Nhập mã OTP</label>
                                <input type="text" class="form-control" id="otp" name="otp" required maxlength="6">
                                <div class="form-text">
                                    Mã OTP đã được gửi đến email của bạn
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Xác nhận</button>
                                <button type="button" class="btn btn-secondary" onclick="resendOTP()">Gửi lại mã OTP</button>
                            </div>
                        </div>
                    </form>
                    <p class="mt-3 text-center">Đã có tài khoản? <a href="../../frontend/auth/login.php">Đăng nhập</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const password = document.getElementById('register_password');

    // Hàm kiểm tra mật khẩu hợp lệ
    function isValidPassword(pwd) {
        const hasMinLength = pwd.length >= 8;
        const hasUpperCase = /[A-Z]/.test(pwd);
        const hasLowerCase = /[a-z]/.test(pwd);
        const hasNumbers = /\d/.test(pwd);
        const hasSpecialChar = /[@$!%*?&]/.test(pwd);
        
        return hasMinLength && hasUpperCase && hasLowerCase && hasNumbers && hasSpecialChar;
    }

    // Hàm kiểm tra form hợp lệ
    function validateForm() {
        const username = document.getElementById('register_username').value.trim();
        const full_name = document.getElementById('full_name').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        
        // Reset trạng thái lỗi
        document.getElementById('register_username').style.border = '';

        // Validate từng trường
        if (!username) {
            document.getElementById('register_username').style.border = '1px solid red';
            document.getElementById('register_username').focus();
            alert('Vui lòng nhập tên đăng nhập');
            return false;
        }
        
        if (!full_name) {
            alert('Vui lòng nhập họ và tên');
            return false;
        }
        
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            alert('Vui lòng nhập email hợp lệ');
            return false;
        }
        
        if (!phone || !/^[0-9]{10,11}$/.test(phone)) {
            alert('Vui lòng nhập số điện thoại hợp lệ (10-11 số)');
            return false;
        }
        
        if (!isValidPassword(password.value)) {
            alert('Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt (@$!%*?&)');
            return false;
        }
        
        if (!document.getElementById('terms').checked) {
            alert('Vui lòng đồng ý với điều khoản dịch vụ');
            return false;
        }
        
        return true;
    }

    // Hàm gửi OTP
    window.sendOTP = function() {
        if (!validateForm()) return;

        const formData = new FormData(form);
        fetch('../../backend/auth/send_otp.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
        // Check if the response is OK and not empty
        if (!response.ok || response.headers.get('content-length') === '0') {
            throw new Error('Empty or invalid response from server');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            document.getElementById('registration-form').style.display = 'none';
            document.getElementById('otp-form').style.display = 'block';
            alert('Mã OTP đã được gửi đến email của bạn');
        } else {
            alert(data.message || 'Có lỗi xảy ra khi gửi OTP');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi gửi OTP');
});
    }

    // Hàm gửi lại OTP
    window.resendOTP = function() {
        const email = document.getElementById('email').value;
        fetch('../../backend/auth/resend_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email: email })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Mã OTP mới đã được gửi đến email của bạn');
            } else {
                alert(data.message || 'Có lỗi xảy ra khi gửi lại OTP');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi gửi lại OTP');
        });
    }

    // Hàm toggle hiển thị mật khẩu
    window.togglePasswordVisibility = function(inputId, toggleButton) {
        const input = document.getElementById(inputId);
        const icon = toggleButton.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
});
</script>

<?php
// Include footer (đảm bảo đường dẫn đúng)
include '../../frontend/pages/footer.php';
?>