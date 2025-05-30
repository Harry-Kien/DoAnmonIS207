<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../backend/config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['fullname'] ?? '');  // Thay đổi thành full_name
    $phone = trim($_POST['phone'] ?? '');       
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];

    // Validation
    if (empty($username)) {
        $errors[] = "Vui lòng nhập tên đăng nhập";
    }

    if (empty($full_name)) {
        $errors[] = "Vui lòng nhập họ và tên";
    }

    if (empty($email)) {
        $errors[] = "Vui lòng nhập email";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Địa chỉ email không hợp lệ";
    }

    // Nâng cao yêu cầu mật khẩu
    if (empty($password)) {
        $errors[] = "Vui lòng nhập mật khẩu";
    } elseif (strlen($password) < 8) {
        $errors[] = "Mật khẩu phải có ít nhất 8 ký tự";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $password)) {
        $errors[] = "Mật khẩu phải chứa ít nhất: 1 chữ hoa, 1 chữ thường, 1 số và 1 ký tự đặc biệt";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Mật khẩu xác nhận không khớp";
    }

    // Kiểm tra username đã tồn tại
    $check_username_sql = "SELECT id FROM user WHERE username = ?";
    $stmt = mysqli_prepare($conn, $check_username_sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $errors[] = "Tên đăng nhập đã được sử dụng";
    }
    mysqli_stmt_close($stmt);

    // Kiểm tra email đã tồn tại
    $check_email_sql = "SELECT id FROM user WHERE email = ?";
    $stmt = mysqli_prepare($conn, $check_email_sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $errors[] = "Email đã được sử dụng";
    }
    mysqli_stmt_close($stmt);

    // Nếu không có lỗi, tiến hành đăng ký
    if (empty($errors)) {
        // Mã hóa mật khẩu
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Câu truy vấn thêm user - chú ý thay đổi tên cột
        $insert_sql = "INSERT INTO user (username, email, full_name, phone, password, last_password_change) VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = mysqli_prepare($conn, $insert_sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssss", $username, $email, $full_name, $phone, $hashed_password);
            
            if (mysqli_stmt_execute($stmt)) {
                // Lấy ID của user vừa tạo
                $user_id = mysqli_insert_id($conn);
                
                // Đăng ký thành công, thiết lập session
                $_SESSION["loggedin"] = true;
                $_SESSION["id"] = $user_id;
                $_SESSION["username"] = $username;
                $_SESSION["email"] = $email;
                $_SESSION["full_name"] = $full_name;

                // Ghi log đăng ký
                $log_sql = "INSERT INTO user_activity_log (user_id, activity_type) VALUES (?, 'register')";
                $log_stmt = mysqli_prepare($conn, $log_sql);
                mysqli_stmt_bind_param($log_stmt, "i", $user_id);
                mysqli_stmt_execute($log_stmt);

                // Chuyển hướng đến trang welcome
                header("Location: ../../frontend/pages/welcome.php");
                exit();
            } else {
                header("Location: ../../frontend/auth/register.php?error=Có lỗi xảy ra. Vui lòng thử lại.");
                exit();
            }
            
            mysqli_stmt_close($stmt);
        } else {
            header("Location: ../../frontend/auth/register.php?error=Lỗi hệ thống: " . mysqli_error($conn));
            exit();
        }
    } else {
        // Chuyển hướng về trang đăng ký với thông báo lỗi
        $error_message = urlencode(implode("<br>", $errors));
        header("Location: ../../frontend/auth/register.php?error=$error_message");
        exit();
    }

    mysqli_close($conn);
}
?>