<!-- Footer -->
<footer class="bg-dark text-white py-5">
    <div class="container">
        <div class="row g-4">
            <!-- Column 1: About -->
            <div class="col-md-4">
                <h5 class="mb-3">Về Homeseeker</h5>
                <p class="mb-4">Homeseeker là nền tảng kết nối người thuê và chủ nhà trọ, giúp việc tìm kiếm và cho thuê phòng trọ trở nên đơn giản và hiệu quả hơn.</p>
                <div class="social-icons d-flex gap-3">
                    <a href="https://www.facebook.com/profile.php?id=100047048344920" 
                    class="text-white fs-5" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    aria-label="Facebook Homeseeker">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://x.com/home?lang=vi" 
                    class="text-white fs-5" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    aria-label="Twitter Homeseeker">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://www.instagram.com/_harrykien_/" 
                    class="text-white fs-5" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    aria-label="Instagram Homeseeker">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://www.youtube.com/@trantrungkien8871" 
                    class="text-white fs-5" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    aria-label="YouTube Homeseeker">
                        <i class="fab fa-youtube"></i>
                    </a>
                </div>
            </div>

            <!-- Column 2: Quick Links -->
            <div class="col-md-2">
                <h5 class="mb-3">Liên kết nhanh</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="../../frontend/pages/index.php" class="text-white text-decoration-none hover-text-warning">Trang chủ</a>
                    </li>
                    <li class="mb-2">
                        <a href="../../frontend/room/phong.php" class="text-white text-decoration-none hover-text-warning">Phòng trọ</a>
                    </li>
                    <li class="mb-2">
                        <a href="../../frontend/pages/banggia.php" class="text-white text-decoration-none hover-text-warning">Bảng giá</a>
                    </li>
                    <li class="mb-2">
                        <a href="../../frontend/pages/blog.php" class="text-white text-decoration-none hover-text-warning">Blog</a>
                    </li>
                </ul>
            </div>

            <!-- Column 3: Contact -->
            <div class="col-md-3">
                <h5 class="mb-3">Liên hệ</h5>
                <ul class="list-unstyled contact-info">
                    <li class="mb-3 d-flex align-items-start">
                        <i class="fas fa-map-marker-alt me-2 mt-1"></i>
                        <span>90 Cây Da Xề, Đông Hòa, TP. Dĩ An, tỉnh Bình Dương</span>
                    </li>
                    <li class="mb-3 d-flex align-items-center">
                        <i class="fas fa-phone me-2"></i>
                        <a href="tel:0382140336" class="text-white text-decoration-none">0382140336</a>
                    </li>
                    <li class="mb-3 d-flex align-items-center">
                        <i class="fas fa-envelope me-2"></i>
                        <a href="mailto:homeseeker@gmail.com" class="text-white text-decoration-none">homeseeker@gmail.com</a>
                    </li>
                </ul>
            </div>

            <!-- Column 4: Newsletter -->
            <div class="col-md-3">
                <h5 class="mb-3">Đăng ký nhận tin</h5>
                <p class="mb-3">Đăng ký để nhận thông tin mới nhất về phòng trọ và các ưu đãi đặc biệt.</p>
                
                <!-- Form đăng ký với modal trigger -->
                <div class="newsletter-form">
                    <div class="input-group mb-3">
                        <input type="email" 
                            class="form-control" 
                            placeholder="Email của bạn"
                            aria-label="Email đăng ký"
                            id="newsletterEmail"
                            data-bs-toggle="modal" 
                            data-bs-target="#registrationModal"
                            readonly
                            style="cursor: pointer; background-color: white;">
                        <button class="btn btn-warning" 
                                type="button"
                                data-bs-toggle="modal" 
                                data-bs-target="#registrationModal">Đăng ký</button>
                    </div>
                </div>
                <small class="text-muted">Chúng tôi cam kết không spam email của bạn.</small>
            </div>
        </div>

        <!-- Modal đăng ký tài khoản -->
        <div class="modal fade" id="registrationModal" tabindex="-1" aria-labelledby="registrationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content" style="background-color: white;">
                    <div class="modal-header" style="background-color: #ffc107; color: #212529;">
                        <h5 class="modal-title" id="registrationModalLabel">Đăng ký tài khoản</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="color: #212529;">
                        <form id="registrationForm" action="register_process.php" method="post">
                            <div class="mb-3">
                                <label for="registerUsername" class="form-label" style="color: #212529;">Tên đăng nhập</label>
                                <input type="text" class="form-control" id="registerUsername" name="username" required style="color: #212529;">
                            </div>
                            <div class="mb-3">
                                <label for="registerName" class="form-label" style="color: #212529;">Họ và tên</label>
                                <input type="text" class="form-control" id="registerName" name="fullname" required style="color: #212529;">
                            </div>
                            <div class="mb-3">
                                <label for="registerEmail" class="form-label" style="color: #212529;">Email</label>
                                <input type="email" class="form-control" id="registerEmail" name="email" required style="color: #212529;">
                            </div>
                            <div class="mb-3">
                                <label for="registerPhone" class="form-label" style="color: #212529;">Số điện thoại</label>
                                <input type="tel" class="form-control" id="registerPhone" name="phone" style="color: #212529;">
                            </div>
                            <div class="mb-3">
                                <label for="registerPassword" class="form-label" style="color: #212529;">Mật khẩu</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="registerPassword" name="password" required 
                                           style="color: #212529;" 
                                           pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                                           title="Mật khẩu phải có ít nhất 8 ký tự, bao gồm 1 chữ hoa, 1 chữ thường, 1 số và 1 ký tự đặc biệt">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleRegisterPassword">
                                        <i class="far fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text" style="color: #6c757d;">
                                    Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="registerConfirmPassword" class="form-label" style="color: #212529;">Xác nhận mật khẩu</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="registerConfirmPassword" name="confirm_password" required style="color: #212529;">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleRegisterConfirmPassword">
                                        <i class="far fa-eye"></i>
                                    </button>
                                </div>
                                <div id="passwordMismatch" class="invalid-feedback">
                                    Mật khẩu xác nhận không khớp
                                </div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="agreeTerms" name="terms" required>
                                <label class="form-check-label" for="agreeTerms" style="color: #212529;">Tôi đồng ý với các điều khoản dịch vụ</label>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-warning">Đăng ký</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Copyright -->
        <hr class="mt-4">
        <div class="text-center pt-3">
            <p class="mb-0">© 2025 Homeseeker. Tất cả các quyền được bảo lưu.</p>
        </div>
    </div>
</footer>

<!-- Chatbot -->
<?php include_once __DIR__ . '/../components/chatbot.php'; ?>

<!-- Add CSS to ensure proper styling -->
<style>
    .modal-content {
        background-color: white !important;
    }
    
    .modal-body {
        color: #212529 !important;
    }
    
    .modal-body .form-label {
        color: #212529 !important;
    }
    
    .modal-body .form-control {
        color: #212529 !important;
        background-color: white !important;
    }
    
    .modal-body .form-check-label {
        color: #212529 !important;
    }
    
    .password-requirements {
        color: #6c757d;
        font-size: 0.875rem;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Function to set active navigation link based on current page
    document.addEventListener('DOMContentLoaded', function() {
        // Get the current page filename
        const currentPage = window.location.pathname.split('/').pop();
        
        // Select all nav links
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
        
        // Check each link
        navLinks.forEach(link => {
            // Get the href attribute and compare with current page
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        // Toggle password visibility
        function setupPasswordToggle(toggleButtonId, passwordFieldId) {
            const toggleButton = document.getElementById(toggleButtonId);
            if (toggleButton) {
                toggleButton.addEventListener('click', function() {
                    const passwordField = document.getElementById(passwordFieldId);
                    const icon = toggleButton.querySelector('i');
                    
                    // Toggle password visibility
                    if (passwordField.type === 'password') {
                        passwordField.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        passwordField.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            }
        }

        // Setup password toggles
        setupPasswordToggle('toggleRegisterPassword', 'registerPassword');
        setupPasswordToggle('toggleRegisterConfirmPassword', 'registerConfirmPassword');
        setupPasswordToggle('toggleCurrentPassword', 'current_password');
        setupPasswordToggle('toggleNewPassword', 'new_password');
        setupPasswordToggle('toggleConfirmPassword', 'confirm_password');
        setupPasswordToggle('togglePassword', 'password');

        // Password validation and confirmation check
        const registerForm = document.getElementById('registrationForm');
        const passwordInput = document.getElementById('registerPassword');
        const confirmPasswordInput = document.getElementById('registerConfirmPassword');
        const passwordMismatch = document.getElementById('passwordMismatch');

        // Validate password format
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

        // Check password confirmation
        function checkPasswordMatch() {
            if (confirmPasswordInput.value !== passwordInput.value) {
                confirmPasswordInput.classList.add('is-invalid');
                passwordMismatch.style.display = 'block';
                return false;
            } else {
                confirmPasswordInput.classList.remove('is-invalid');
                passwordMismatch.style.display = 'none';
                return true;
            }
        }

        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        passwordInput.addEventListener('input', checkPasswordMatch);

        // Form submission validation
        registerForm.addEventListener('submit', function(e) {
            if (!passwordRegex.test(passwordInput.value)) {
                e.preventDefault();
                alert('Mật khẩu phải có ít nhất 8 ký tự, bao gồm 1 chữ hoa, 1 chữ thường, 1 số và 1 ký tự đặc biệt');
                return;
            }

            if (!checkPasswordMatch()) {
                e.preventDefault();
                alert('Mật khẩu xác nhận không khớp!');
                return;
            }
        });
    });
</script>
</body>
</html>