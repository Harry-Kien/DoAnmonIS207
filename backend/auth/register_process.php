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
    $pdo->beginTransaction();

    // Thêm user mới vào database với các trường chính xác
    $sql = "INSERT INTO users (username, email, full_name, phone, password, created_at, updated_at, status, is_admin) 
            VALUES (?, ?, ?, ?, ?, NOW(), NOW(), 1, 0)";
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        $_SESSION['registration']['username'],
        $_SESSION['registration']['email'],
        $_SESSION['registration']['fullname'], // fullname được lưu vào full_name
        $_SESSION['registration']['phone'],
        $_SESSION['registration']['password']
    ]);

    // Commit transaction
    $pdo->commit();

    // Xóa dữ liệu đăng ký khỏi session
    unset($_SESSION['registration']);

    // Chuyển hướng đến trang đăng nhập với thông báo thành công
    header("location: ../../frontend/auth/login.php?success=Đăng ký thành công! Vui lòng đăng nhập.");
    exit;
} catch (PDOException $e) {
    // Rollback transaction nếu có lỗi
    $pdo->rollBack();
    
    $error_message = "Có lỗi xảy ra khi đăng ký: ";
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        if (strpos($e->getMessage(), 'users.username') !== false) {
            $error_message .= "Tên đăng nhập đã tồn tại";
        } elseif (strpos($e->getMessage(), 'users.email') !== false) {
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