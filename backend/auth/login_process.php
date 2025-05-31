<?php
// Khởi động session
session_start();

// Bao gồm file cấu hình kết nối CSDL
require_once __DIR__ . '/../../backend/config/config.php';

// Chỉ xử lý nếu là POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Nhận và lọc dữ liệu từ form
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $remember = isset($_POST["remember"]) ? true : false;

    // Nhận các tham số điều hướng nếu có
    $redirect = $_POST["redirect"] ?? "";
    $plan = $_POST["plan"] ?? "";

    // Tạo chuỗi tham số lỗi nếu cần quay lại login
    $error_params = "";
    if (!empty($redirect)) {
        $error_params .= "&redirect=" . urlencode($redirect);
        if (!empty($plan)) {
            $error_params .= "&plan=" . urlencode($plan);
        }
    }

    // Kiểm tra dữ liệu đầu vào
    if (empty($username)) {
        header("Location: ../../frontend/auth/login.php?login_error=Vui lòng nhập tên đăng nhập hoặc email." . $error_params);
        exit();
    }
    if (empty($password)) {
        header("Location: ../../frontend/auth/login.php?login_error=Vui lòng nhập mật khẩu." . $error_params);
        exit();
    }

    // Câu truy vấn: cho phép login bằng username hoặc email
    $sql = "SELECT id, username, email, password, is_admin FROM user WHERE username = ? OR email = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $username, $username);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) === 1) {
                mysqli_stmt_bind_result($stmt, $id, $username_db, $email, $hashed_password, $is_admin);

                if (mysqli_stmt_fetch($stmt)) {
                    if (password_verify($password, $hashed_password) || $password === $hashed_password) {
                        // Đăng nhập thành công - tạo session
                        $_SESSION["loggedin"] = true;
                        $_SESSION["user_id"] = $id;
                        $_SESSION["username"] = $username_db;
                        $_SESSION["is_admin"] = $is_admin;
                        $_SESSION["login_time"] = time();

                        // Lưu plan nếu có
                        if (!empty($plan)) {
                            $_SESSION["selected_plan"] = $plan;
                        }

                        // Xử lý cookie "ghi nhớ đăng nhập"
                        if ($remember) {
                            // Tạo token ngẫu nhiên
                            $token = bin2hex(random_bytes(32));
                            // Lưu token vào database (bạn cần thêm cột remember_token vào bảng user)
                            $update_sql = "UPDATE user SET remember_token = ? WHERE id = ?";
                            if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                                mysqli_stmt_bind_param($update_stmt, "si", $token, $id);
                                mysqli_stmt_execute($update_stmt);
                                mysqli_stmt_close($update_stmt);
                            }
                            // Lưu token và username vào cookie (30 ngày)
                            setcookie('remember_token', $token, time() + (86400 * 30), "/");
                            setcookie('remember_username', $username_db, time() + (86400 * 30), "/");
                            // Lưu thông tin đăng nhập để tự động điền vào form
                            setcookie('remember_login_username', $username, time() + (86400 * 30), "/");
                            setcookie('remember_login_password', base64_encode($password), time() + (86400 * 30), "/");
                        } else {
                            // Xóa cookie nếu không chọn "Ghi nhớ"
                            setcookie('remember_token', '', time() - 3600, "/");
                            setcookie('remember_username', '', time() - 3600, "/");
                            setcookie('remember_login_username', '', time() - 3600, "/");
                            setcookie('remember_login_password', '', time() - 3600, "/");
                        }

                        // Chuyển hướng
                        if (!empty($redirect)) {
                            header("Location: " . $redirect);
                        } else {
                            header("Location: ../../frontend/pages/index.php");
                        }
                        exit();
                    } else {
                        header("Location: ../../frontend/auth/login.php?login_error=Mật khẩu không đúng." . $error_params);
                        exit();
                    }
                }
            } else {
                header("Location: ../../frontend/auth/login.php?login_error=Tài khoản không tồn tại." . $error_params);
                exit();
            }
        } else {
            header("Location: ../../frontend/auth/login.php?login_error=Lỗi truy vấn. Vui lòng thử lại sau." . $error_params);
            exit();
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_close($conn);
} else {
    // Nếu không phải POST, quay lại login
    header("Location: ../../frontend/auth/login.php");
    exit();
}
?>