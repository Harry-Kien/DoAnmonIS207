<?php
// Thông tin kết nối database
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'thongtinnguoidung');

// Kết nối đến MySQL
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kiểm tra kết nối
if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}

// Đặt charset là utf8mb4
mysqli_set_charset($conn, "utf8mb4");
?>