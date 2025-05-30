<?php
// Kết nối database
require_once '../config/config.php';

// Bật hiển thị lỗi (chỉ dùng khi phát triển)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cho phép CORS để AJAX hoạt động
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

// Kiểm tra tham số city_id
if (isset($_GET['city_id']) && is_numeric($_GET['city_id'])) {
    $city_id = $_GET['city_id'];
    
    try {
        // Lấy danh sách quận/huyện (thêm DISTINCT để loại bỏ trùng lặp)
        $sql = "SELECT DISTINCT id, name FROM districts WHERE city_id = ? ORDER BY name ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $city_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Chuyển kết quả thành mảng
        $districts = [];
        while ($row = $result->fetch_assoc()) {
            $districts[] = $row;
        }
        
        // Trả về dữ liệu dạng JSON
        echo json_encode($districts);
    } catch (Exception $e) {
        // Ghi log lỗi
        error_log("Error in get_districts.php: " . $e->getMessage());
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    // Trả về mảng rỗng nếu không có city_id
    echo json_encode([]);
}
?>
<script>
// Ví dụ về việc gọi API từ phía client
var cityId = 123; // Thay bằng city_id thực tế
fetch('../../backend/api/get_districts.php?city_id=' + cityId)
    .then(response => response.json())
    .then(data => {
        console.log(data);
        // Xử lý dữ liệu quận/huyện ở đây
    })
    .catch(error => console.error('Error:', error));
</script>