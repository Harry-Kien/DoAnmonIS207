<?php
// Kết nối database
require_once "../config/config.php";

// Đọc nội dung file SQL
$sql_content = file_get_contents(__DIR__ . "/../database/notifications.sql");

// Thực thi SQL
if ($conn->multi_query($sql_content)) {
    echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<h2 style='color: #4CAF50;'>Đã tạo bảng notifications thành công</h2>";
    echo "<p>Bảng notifications đã được tạo hoặc đã tồn tại trong cơ sở dữ liệu.</p>";
    echo "<p>Bạn có thể quay lại <a href='../../frontend/pages/welcome.php' style='color: #2196F3; text-decoration: none;'>trang cá nhân</a> để tiếp tục.</p>";
    echo "</div>";
} else {
    echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<h2 style='color: #F44336;'>Lỗi khi tạo bảng notifications</h2>";
    echo "<p>Đã xảy ra lỗi: " . $conn->error . "</p>";
    echo "<p>Vui lòng kiểm tra lại cấu hình cơ sở dữ liệu.</p>";
    echo "</div>";
}

// Đóng kết nối
$conn->close();
?> 