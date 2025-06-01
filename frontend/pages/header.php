<?php
// Đầu file header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Homeseeker - Tìm nhà trọ, phòng trọ uy tín'; ?></title>
    <!-- Favicon -->
    <link rel="icon" href="../../frontend/assets/images/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../../frontend/assets/images/favicon.ico" type="image/x-icon">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../frontend/assets/css/style.css">
    <?php if (isset($additional_css)): echo $additional_css; endif; ?>
    <script src="../../frontend/assets/js/ajax_handlers.js"></script>
</head>
<body class="<?php echo $body_class ?? 'bg-light'; ?>">
    <!-- Header -->
    <header class="navbar-homeseeker sticky-top">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center w-100">
                <!-- Logo và Menu chính -->
                <div class="d-flex align-items-center">
                    <a href="../../frontend/pages/index.php" class="d-flex align-items-center text-decoration-none me-4">
                        <span class="text-warning">
                            <i class="fas fa-home fa-lg"></i>
                        </span>
                        <span class="logo-text ms-2">Homeseeker</span>
                    </a>
                    
                    <div class="d-none d-md-flex">
                        <a href="../../frontend/room/phong.php" class="nav-link">Phòng</a>
                        <a href="../../frontend/pages/banggia.php" class="nav-link">Bảng giá</a>
                        <a href="../../frontend/pages/blog.php" class="nav-link">Blog</a>
                    </div>
                </div>
                
                <!-- Các nút tương tác -->
<div class="d-flex align-items-center">
    <!-- Nút đăng tin và quản lý phòng (hiển thị cho mọi người) -->
    <a href="../../backend/rooms/dang_phong.php" class="btn-post d-inline-block">
        <i class="fas fa-plus-circle me-1"></i>Đăng tin
    </a>
    
    <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
        <a href="../../frontend/room/my_rooms.php" class="btn-manage d-none d-md-inline-block">
            <i class="fas fa-list-alt me-1"></i>Quản lý
        </a>
    <?php endif; ?>
    
    <!-- Icon tìm kiếm -->
    <span class="search-icon" id="searchIcon">
        <i class="fas fa-search"></i>
    </span>
    
    <!-- Phần còn lại giữ nguyên -->
                    
                    <!-- Icon yêu thích -->
                    <a href="../../frontend/user/favorites.php" class="heart-icon">
                        <i class="far fa-heart"></i>
                    </a>
                    
                    <!-- Dropdown người dùng -->
                    <div class="dropdown">
                        <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                            <span class="user-icon dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i>
                            </span>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="../../frontend/pages/welcome.php">Trang cá nhân</a></li>
                                <li><a class="dropdown-item d-md-none" href="../../backend/rooms/dang_phong.php">Đăng tin phòng</a></li>
                                <li><a class="dropdown-item d-md-none" href="../../frontend/room/my_rooms.php">Quản lý phòng</a></li>
                                <li><a class="dropdown-item" href="../../frontend/auth/reset_password.php">Đổi mật khẩu</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../../frontend/auth/logout.php">Đăng xuất</a></li>
                            </ul>
                        <?php else: ?>
                            <span class="user-icon dropdown-toggle" id="loginDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i>
                            </span>
                            <div class="dropdown-menu dropdown-menu-end p-3" style="width: 300px;">
                                <form action="../../backend/auth/login_process.php" method="post">
                                    <h5 class="mb-3">Đăng nhập</h5>
                                    <?php
                                    if (isset($_GET['login_error'])) {
                                        echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['login_error']) . '</div>';
                                    }
                                    ?>
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Email hoặc Username</label>
                                        <input type="text" class="form-control" id="username" name="username" placeholder="Nhập email hoặc username" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Mật khẩu</label>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu" required>
                                    </div>
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
                                        <label class="form-check-label" for="rememberMe">Ghi nhớ đăng nhập</label>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-warning">Đăng nhập</button>
                                    </div>
                                    <div class="mt-3 text-center">
                                        <a href="../../frontend/auth/forgot_password.php" class="text-decoration-none">Quên mật khẩu?</a>
                                        <hr>
                                        <p class="mb-0">Chưa có tài khoản? <a href="../../frontend/auth/register.php" class="text-decoration-none">Đăng ký</a></p>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Nút menu cho mobile -->
                    <button class="navbar-toggler ms-3 d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenu">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>
            </div>
            
            <!-- Menu mobile -->
            <div class="collapse navbar-collapse mt-3 d-md-none" id="mobileMenu">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../../frontend/room/phong.php">Phòng</a></li>
                    <li class="nav-item"><a class="nav-link" href="../../frontend/pages/banggia.php">Bảng giá</a></li>
                    <li class="nav-item"><a class="nav-link" href="../../frontend/pages/blog.php">Blog</a></li>
                    <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                        <li class="nav-item"><a class="nav-link" href="../../backend/rooms/dang_phong.php"><i class="fas fa-plus-circle me-1"></i>Đăng tin phòng</a></li>
                        <li class="nav-item"><a class="nav-link" href="../../backend/rooms/my_rooms.php"><i class="fas fa-list-alt me-1"></i>Quản lý phòng</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </header>
    
    <!-- Popup tìm kiếm -->
    <div class="search-popup" id="searchPopup" style="display:none;">
        <span class="close-search" id="closeSearch">
            <i class="fas fa-times"></i>
        </span>
        <div class="search-container">
            <form action="../../frontend/pages/search.php" method="GET">
                <h4 class="text-center mb-4">Tìm kiếm phòng trọ</h4>
                <div class="row g-3">
                    <div class="col-md-12 mb-3">
                        <input type="text" class="form-control" name="keyword" placeholder="Nhập từ khóa tìm kiếm..." autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="location">
                            <option value="">Chọn khu vực</option>
                            <option value="TP. Hồ Chí Minh">TP. Hồ Chí Minh</option>
                
                            <option value="Bình Dương">Bình Dương</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="type">
                            <option value="">Loại phòng</option>
                            <option value="Phòng trọ">Phòng trọ</option>
                            <option value="Chung cư mini">Chung cư mini</option>
                            <option value="Nhà nguyên căn">Nhà nguyên căn</option>
                            <option value="Ở ghép">Ở ghép</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="price">
                            <option value="">Giá</option>
                            <option value="0-2000000">Dưới 2 triệu</option>
                            <option value="2000000-4000000">2 - 4 triệu</option>
                            <option value="4000000-6000000">4 - 6 triệu</option>
                       
                            <option value="66000000-999999999">Trên 6 triệu</option>
                        </select>
                    </div>
                    <div class="col-md-12 mt-3">
                        <button type="submit" class="btn btn-warning w-100">Tìm kiếm</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- JavaScript cho chức năng tìm kiếm -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchIcon = document.getElementById('searchIcon');
            const searchPopup = document.getElementById('searchPopup');
            const closeSearch = document.getElementById('closeSearch');
            const keywordInput = searchPopup.querySelector('input[name="keyword"]');

            searchIcon.addEventListener('click', function() {
                searchPopup.style.display = 'flex';
                setTimeout(() => {
                    keywordInput.focus();
                }, 100);
            });

            closeSearch.addEventListener('click', function() {
                searchPopup.style.display = 'none';
            });

            // Đóng popup khi nhấn Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && searchPopup.style.display === 'flex') {
                    searchPopup.style.display = 'none';
                }
            });

            // Đóng popup khi click ra ngoài form
            searchPopup.addEventListener('click', function(e) {
                if (e.target === searchPopup) {
                    searchPopup.style.display = 'none';
                }
            });
        });
    </script>