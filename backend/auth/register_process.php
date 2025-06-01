<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../backend/config/config.php';
require_once '../mail/send_mail.php';

// Kiểm tra xem có dữ liệu đăng ký trong session không
if (!isset($_SESSION['registration'])) {
    header("location: ../../frontend/auth/register.php?error=Phiên đăng ký đã hết hạn");
    exit;
}

// Lấy OTP từ form
$submitted_otp = $_POST['otp'] ?? '';

// Kiểm tra OTP có khớp không
if ($submitted_otp !== $_SESSION['registration']['otp']) {
    header("location: ../../frontend/auth/register.php?error=Mã OTP không chính xác");
    exit;
}

// Kiểm tra thời gian OTP (5 phút)
if (time() - $_SESSION['registration']['otp_time'] > 300) {
    header("location: ../../frontend/auth/register.php?error=Mã OTP đã hết hạn");
    exit;
}

try {
    // Bắt đầu transaction
    mysqli_begin_transaction($conn);

    // Lưu thông tin người dùng để sử dụng sau khi insert
    $username = $_SESSION['registration']['username'];
    $email = $_SESSION['registration']['email'];
    $fullname = $_SESSION['registration']['fullname'];
    $phone = $_SESSION['registration']['phone'];
    $password_hash = $_SESSION['registration']['password'];

    // Thêm user mới vào database với các trường chính xác
    $sql = "INSERT INTO user (username, email, full_name, phone, password, created_at, updated_at, status, is_admin) 
            VALUES (?, ?, ?, ?, ?, NOW(), NOW(), 1, 0)";
    $stmt = mysqli_prepare($conn, $sql);
    
    mysqli_stmt_bind_param($stmt, "sssss", 
        $username,
        $email,
        $fullname, // fullname được lưu vào full_name
        $phone,
        $password_hash
    );
    
    mysqli_stmt_execute($stmt);
    
    // Lấy ID của user vừa tạo
    $user_id = mysqli_insert_id($conn);

    // Commit transaction
    mysqli_commit($conn);

    // Xóa dữ liệu đăng ký khỏi session
    unset($_SESSION['registration']);

    // Thiết lập session đăng nhập
    $_SESSION['loggedin'] = true;
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['full_name'] = $fullname;
    $_SESSION['is_admin'] = 0; // Người dùng mới không phải admin

    // Chuyển hướng đến trang chính sau khi đăng nhập thành công
    header("location: ../../frontend/pages/index.php?welcome=1");
    exit;
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    mysqli_rollback($conn);
    
    $error_message = "Có lỗi xảy ra khi đăng ký: ";
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        if (strpos($e->getMessage(), 'user.username') !== false) {
            $error_message .= "Tên đăng nhập đã tồn tại";
        } elseif (strpos($e->getMessage(), 'user.email') !== false) {
            $error_message .= "Email đã được sử dụng";
        } else {
            $error_message .= "Thông tin đã tồn tại trong hệ thống";
        }
    } else {
        $error_message .= "Vui lòng thử lại sau";
    }
    
    header("location: ../../frontend/auth/register.php?error=" . urlencode($error_message));
    exit;
}

mysqli_close($conn);
?>