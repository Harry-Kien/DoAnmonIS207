<?php
session_start();
require_once "../../backend/config/config.php";

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

// Tạo bảng settings nếu chưa tồn tại
$check_table_sql = "SHOW TABLES LIKE 'settings'";
$check_table_result = mysqli_query($conn, $check_table_sql);

if (mysqli_num_rows($check_table_result) == 0) {
    $create_table_sql = "CREATE TABLE settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT NOT NULL,
        setting_group VARCHAR(50) NOT NULL DEFAULT 'general',
        setting_description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $create_table_sql)) {
        // Thêm dữ liệu mẫu
        $sample_data_sql = "INSERT INTO settings (setting_key, setting_value, setting_group, setting_description) VALUES 
            ('site_name', 'Homeseeker', 'general', 'Tên website'),
            ('site_description', 'Nền tảng tìm kiếm phòng trọ hàng đầu', 'general', 'Mô tả ngắn về website'),
            ('contact_email', 'contact@homeseeker.com', 'contact', 'Email liên hệ'),
            ('contact_phone', '0123456789', 'contact', 'Số điện thoại liên hệ'),
            ('contact_address', 'Thành phố Hồ Chí Minh, Việt Nam', 'contact', 'Địa chỉ liên hệ'),
            ('facebook_url', 'https://facebook.com/homeseeker', 'social', 'URL Facebook'),
            ('instagram_url', 'https://instagram.com/homeseeker', 'social', 'URL Instagram'),
            ('subscription_price', '200000', 'payment', 'Giá gói đăng ký (VND/tháng)'),
            ('promotion_price', '100000', 'payment', 'Giá quảng cáo (VND/tuần)'),
            ('max_images', '10', 'rooms', 'Số lượng hình ảnh tối đa cho mỗi phòng'),
            ('auto_approve', '0', 'rooms', 'Tự động duyệt phòng (1: Có, 0: Không)'),
            ('version', '1.0.0', 'system', 'Phiên bản hệ thống')";
        mysqli_query($conn, $sample_data_sql);
    }
}

// Hàm lấy giá trị cài đặt
function get_setting($conn, $key, $default = '') {
    $sql = "SELECT setting_value FROM settings WHERE setting_key = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['setting_value'];
    }
    
    return $default;
}

// Lấy tên database từ kết nối
$db_name = '';
if ($result = mysqli_query($conn, 'SELECT DATABASE()')) {
    $row = mysqli_fetch_row($result);
    $db_name = $row[0];
    mysqli_free_result($result);
}

// Xử lý cập nhật cài đặt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    // Lấy tất cả các cài đặt từ form
    $settings = $_POST['settings'];
    
    // Cập nhật từng cài đặt
    foreach ($settings as $key => $value) {
        $key = mysqli_real_escape_string($conn, $key);
        $value = mysqli_real_escape_string($conn, $value);
        
        // Kiểm tra xem cài đặt đã tồn tại chưa
        $check_sql = "SELECT id FROM settings WHERE setting_key = '$key'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Cập nhật nếu đã tồn tại
            $update_sql = "UPDATE settings SET setting_value = '$value', updated_at = NOW() WHERE setting_key = '$key'";
            mysqli_query($conn, $update_sql);
        } else {
            // Thêm mới nếu chưa tồn tại
            $insert_sql = "INSERT INTO settings (setting_key, setting_value, setting_group) VALUES ('$key', '$value', 'general')";
            mysqli_query($conn, $insert_sql);
        }
    }
    
    $message = "Cài đặt đã được cập nhật thành công!";
    $message_type = "success";
}

// Lấy danh sách cài đặt theo nhóm
$settings = [];
$groups = ['general', 'contact', 'social', 'payment', 'rooms', 'system'];

foreach ($groups as $group) {
    $sql = "SELECT * FROM settings WHERE setting_group = '$group' ORDER BY id";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $settings[$group][] = $row;
        }
    }
}

// Xác định trang hiện tại
$current_page = 'settings';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt hệ thống - Homeseeker Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-container {
            display: flex;
        }
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background-color: #343a40;
            color: #fff;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            transition: all 0.3s;
        }
        .sidebar-header {
            padding: 20px;
            background: #212529;
        }
        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
        }
        .sidebar-menu {
            padding: 0;
            list-style: none;
        }
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        .sidebar-menu a {
            padding: 12px 20px;
            color: #adb5bd;
            display: block;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            color: #fff;
            background: #2c3136;
            border-left-color: #0d6efd;
        }
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .content {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 20px;
            transition: all 0.3s;
        }
        .navbar-admin {
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 10px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .settings-card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .settings-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }
        .settings-body {
            padding: 20px;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            font-weight: 500;
        }
        .form-text {
            color: #6c757d;
            font-size: 0.875rem;
        }
        .nav-pills .nav-link.active {
            background-color: #343a40;
        }
        .nav-pills .nav-link {
            color: #343a40;
        }
        .nav-pills .nav-link:hover:not(.active) {
            background-color: #f8f9fa;
        }
        .logout-btn {
            color: #dc3545;
        }
        .logout-btn:hover {
            background: #dc3545;
            color: #fff;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
            }
            .sidebar-brand, .menu-text {
                display: none;
            }
            .content {
                margin-left: 80px;
                width: calc(100% - 80px);
            }
            .sidebar-menu a {
                text-align: center;
                padding: 15px;
            }
            .sidebar-menu i {
                margin-right: 0;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
<div class="admin-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="home.php" class="sidebar-brand">
                <i class="fas fa-home"></i> Homeseeker
            </a>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="home.php" class="<?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="rooms.php" class="<?php echo $current_page == 'rooms' ? 'active' : ''; ?>">
                    <i class="fas fa-building"></i> <span class="menu-text">Quản lý phòng</span>
                </a>
            </li>
            <li>
                <a href="users.php" class="<?php echo $current_page == 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> <span class="menu-text">Quản lý người dùng</span>
                </a>
            </li>
            <li>
                <a href="reports.php" class="<?php echo $current_page == 'reports' ? 'active' : ''; ?>">
                    <i class="fas fa-flag"></i> <span class="menu-text">Báo cáo vi phạm</span>
                </a>
            </li>
            <li>
                <a href="payments.php" class="<?php echo $current_page == 'payments' ? 'active' : ''; ?>">
                    <i class="fas fa-credit-card"></i> <span class="menu-text">Thanh toán</span>
                </a>
            </li>
            <li>
                <a href="settings.php" class="<?php echo $current_page == 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> <span class="menu-text">Cài đặt</span>
                </a>
            </li>
            <li class="mt-5">
                <a href="../../frontend/auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> <span class="menu-text">Đăng xuất</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="navbar-admin d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Cài đặt hệ thống</h4>
            <div>
                <span>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            </div>
        </div>

        <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="settings-card">
                    <div class="settings-header">
                        <i class="fas fa-list"></i> Danh mục cài đặt
                    </div>
                    <div class="settings-body p-0">
                        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                            <button class="nav-link active" id="v-pills-general-tab" data-bs-toggle="pill" data-bs-target="#v-pills-general" type="button" role="tab" aria-controls="v-pills-general" aria-selected="true">
                                <i class="fas fa-globe"></i> Cài đặt chung
                            </button>
                            <button class="nav-link" id="v-pills-contact-tab" data-bs-toggle="pill" data-bs-target="#v-pills-contact" type="button" role="tab" aria-controls="v-pills-contact" aria-selected="false">
                                <i class="fas fa-address-card"></i> Thông tin liên hệ
                            </button>
                            <button class="nav-link" id="v-pills-social-tab" data-bs-toggle="pill" data-bs-target="#v-pills-social" type="button" role="tab" aria-controls="v-pills-social" aria-selected="false">
                                <i class="fas fa-share-alt"></i> Mạng xã hội
                            </button>
                            <button class="nav-link" id="v-pills-payment-tab" data-bs-toggle="pill" data-bs-target="#v-pills-payment" type="button" role="tab" aria-controls="v-pills-payment" aria-selected="false">
                                <i class="fas fa-money-bill"></i> Cài đặt thanh toán
                            </button>
                            <button class="nav-link" id="v-pills-rooms-tab" data-bs-toggle="pill" data-bs-target="#v-pills-rooms" type="button" role="tab" aria-controls="v-pills-rooms" aria-selected="false">
                                <i class="fas fa-building"></i> Cài đặt phòng trọ
                            </button>
                            <button class="nav-link" id="v-pills-system-tab" data-bs-toggle="pill" data-bs-target="#v-pills-system" type="button" role="tab" aria-controls="v-pills-system" aria-selected="false">
                                <i class="fas fa-info-circle"></i> Thông tin hệ thống
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <form method="POST" action="">
                    <div class="tab-content" id="v-pills-tabContent">
                        <!-- Cài đặt chung -->
                        <div class="tab-pane fade show active" id="v-pills-general" role="tabpanel" aria-labelledby="v-pills-general-tab">
                            <div class="settings-card">
                                <div class="settings-header">
                                    <i class="fas fa-globe"></i> Cài đặt chung
                                </div>
                                <div class="settings-body">
                                    <?php if (isset($settings['general'])): ?>
                                        <?php foreach ($settings['general'] as $setting): ?>
                                            <div class="form-group">
                                                <label for="<?php echo $setting['setting_key']; ?>" class="form-label">
                                                    <?php echo htmlspecialchars($setting['setting_description']); ?>
                                                </label>
                                                <input type="text" class="form-control" id="<?php echo $setting['setting_key']; ?>" name="settings[<?php echo $setting['setting_key']; ?>]" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">Không có cài đặt nào.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Thông tin liên hệ -->
                        <div class="tab-pane fade" id="v-pills-contact" role="tabpanel" aria-labelledby="v-pills-contact-tab">
                            <div class="settings-card">
                                <div class="settings-header">
                                    <i class="fas fa-address-card"></i> Thông tin liên hệ
                                </div>
                                <div class="settings-body">
                                    <?php if (isset($settings['contact'])): ?>
                                        <?php foreach ($settings['contact'] as $setting): ?>
                                            <div class="form-group">
                                                <label for="<?php echo $setting['setting_key']; ?>" class="form-label">
                                                    <?php echo htmlspecialchars($setting['setting_description']); ?>
                                                </label>
                                                <input type="text" class="form-control" id="<?php echo $setting['setting_key']; ?>" name="settings[<?php echo $setting['setting_key']; ?>]" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">Không có cài đặt nào.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mạng xã hội -->
                        <div class="tab-pane fade" id="v-pills-social" role="tabpanel" aria-labelledby="v-pills-social-tab">
                            <div class="settings-card">
                                <div class="settings-header">
                                    <i class="fas fa-share-alt"></i> Mạng xã hội
                                </div>
                                <div class="settings-body">
                                    <?php if (isset($settings['social'])): ?>
                                        <?php foreach ($settings['social'] as $setting): ?>
                                            <div class="form-group">
                                                <label for="<?php echo $setting['setting_key']; ?>" class="form-label">
                                                    <?php echo htmlspecialchars($setting['setting_description']); ?>
                                                </label>
                                                <input type="text" class="form-control" id="<?php echo $setting['setting_key']; ?>" name="settings[<?php echo $setting['setting_key']; ?>]" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">Không có cài đặt nào.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Cài đặt thanh toán -->
                        <div class="tab-pane fade" id="v-pills-payment" role="tabpanel" aria-labelledby="v-pills-payment-tab">
                            <div class="settings-card">
                                <div class="settings-header">
                                    <i class="fas fa-money-bill"></i> Cài đặt thanh toán
                                </div>
                                <div class="settings-body">
                                    <?php if (isset($settings['payment'])): ?>
                                        <?php foreach ($settings['payment'] as $setting): ?>
                                            <div class="form-group">
                                                <label for="<?php echo $setting['setting_key']; ?>" class="form-label">
                                                    <?php echo htmlspecialchars($setting['setting_description']); ?>
                                                </label>
                                                <input type="text" class="form-control" id="<?php echo $setting['setting_key']; ?>" name="settings[<?php echo $setting['setting_key']; ?>]" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                                <?php if (strpos($setting['setting_key'], 'price') !== false): ?>
                                                    <small class="form-text">Đơn vị: VNĐ</small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">Không có cài đặt nào.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Cài đặt phòng trọ -->
                        <div class="tab-pane fade" id="v-pills-rooms" role="tabpanel" aria-labelledby="v-pills-rooms-tab">
                            <div class="settings-card">
                                <div class="settings-header">
                                    <i class="fas fa-building"></i> Cài đặt phòng trọ
                                </div>
                                <div class="settings-body">
                                    <?php if (isset($settings['rooms'])): ?>
                                        <?php foreach ($settings['rooms'] as $setting): ?>
                                            <div class="form-group">
                                                <?php if ($setting['setting_key'] == 'auto_approve'): ?>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="<?php echo $setting['setting_key']; ?>" name="settings[<?php echo $setting['setting_key']; ?>]" value="1" <?php echo $setting['setting_value'] == '1' ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="<?php echo $setting['setting_key']; ?>">
                                                            <?php echo htmlspecialchars($setting['setting_description']); ?>
                                                        </label>
                                                    </div>
                                                    <small class="form-text">Khi bật, phòng mới đăng sẽ được tự động duyệt mà không cần kiểm duyệt.</small>
                                                <?php else: ?>
                                                    <label for="<?php echo $setting['setting_key']; ?>" class="form-label">
                                                        <?php echo htmlspecialchars($setting['setting_description']); ?>
                                                    </label>
                                                    <input type="number" class="form-control" id="<?php echo $setting['setting_key']; ?>" name="settings[<?php echo $setting['setting_key']; ?>]" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">Không có cài đặt nào.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Thông tin hệ thống -->
                        <div class="tab-pane fade" id="v-pills-system" role="tabpanel" aria-labelledby="v-pills-system-tab">
                            <div class="settings-card">
                                <div class="settings-header">
                                    <i class="fas fa-info-circle"></i> Thông tin hệ thống
                                </div>
                                <div class="settings-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Phiên bản PHP</span>
                                            <span class="badge bg-primary"><?php echo phpversion(); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Phiên bản MySQL</span>
                                            <span class="badge bg-primary"><?php echo mysqli_get_server_info($conn); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Phiên bản Homeseeker</span>
                                            <span class="badge bg-primary"><?php echo get_setting($conn, 'version', '1.0.0'); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Tên database</span>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($db_name); ?></span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" name="update_settings" class="btn btn-primary">
                            <i class="fas fa-save"></i> Lưu cài đặt
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-4 text-center text-muted">
            <p>&copy; <?php echo date('Y'); ?> Homeseeker Admin Panel</p>
        </footer>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 