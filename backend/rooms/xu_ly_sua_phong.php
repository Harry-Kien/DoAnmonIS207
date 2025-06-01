<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form
    $room_id = intval($_POST['room_id'] ?? 0);
    $user_id = $_SESSION['user_id']; // Sửa thành user_id
    $title = $_POST['title'] ?? '';
    $type = $_POST['type'] ?? '';
    $area = floatval($_POST['area'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $description = $_POST['description'] ?? '';
    $address = $_POST['address'] ?? '';
    $district = $_POST['district'] ?? '';
    $city = $_POST['city'] ?? '';
    $district_id = intval($_POST['district_id'] ?? 0);
    $city_id = intval($_POST['city_id'] ?? 0);
    $max_occupants = intval($_POST['max_occupants'] ?? 1);
    $amenities = $_POST['amenities'] ?? '';
    $facilities = $_POST['facilities'] ?? '';
    $contact_name = $_POST['contact_name'] ?? '';
    $contact_phone = $_POST['contact_phone'] ?? '';
    
    // Lấy lat và lng từ form nếu có, nếu không thì giữ nguyên giá trị cũ
    $lat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
    $lng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;
    
    // Nếu lat và lng không được cung cấp, lấy từ database
    if ($lat === null || $lng === null) {
        $stmt_coords = $conn->prepare("SELECT lat, lng FROM rooms WHERE id = ?");
        $stmt_coords->bind_param("i", $room_id);
        $stmt_coords->execute();
        $result_coords = $stmt_coords->get_result();
        if ($row_coords = $result_coords->fetch_assoc()) {
            if ($lat === null) $lat = $row_coords['lat'];
            if ($lng === null) $lng = $row_coords['lng'];
        }
        $stmt_coords->close();
    }

    // Validate dữ liệu
    $errors = [];
    if (empty($title)) $errors[] = "Tiêu đề không được để trống";
    if ($area <= 0) $errors[] = "Diện tích phải lớn hơn 0";
    if ($price <= 0) $errors[] = "Giá phải lớn hơn 0";
    if (empty($address)) $errors[] = "Địa chỉ không được để trống";
    if (empty($district)) $errors[] = "Quận/Huyện không được để trống";
    if (empty($city)) $errors[] = "Thành phố không được để trống";
    if (empty($contact_name)) $errors[] = "Tên liên hệ không được để trống";
    if (empty($contact_phone)) $errors[] = "Số điện thoại liên hệ không được để trống";

    // Nếu không có lỗi, cập nhật CSDL
    if (empty($errors)) {
        try {
            // Lấy thời gian hiện tại
            $updated_at = date("Y-m-d H:i:s");
            
            $sql = "UPDATE rooms SET 
                title = ?,
                type = ?,
                area = ?,
                price = ?,
                description = ?,
                address = ?,
                district = ?,
                city = ?,
                district_id = ?,
                city_id = ?,
                max_occupants = ?,
                amenities = ?,
                facilities = ?,
                contact_name = ?,
                contact_phone = ?,
                lat = ?,
                lng = ?,
                updated_at = ?
                WHERE id = ? AND user_id = ?";
                
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Lỗi chuẩn bị câu lệnh: " . $conn->error);
            }
            
            // Đếm số lượng tham số trong câu lệnh SQL
            $param_count = substr_count($sql, "?");
            
            // Tạo chuỗi định nghĩa kiểu dữ liệu dựa trên số lượng tham số
            $param_types = str_repeat("s", $param_count); // Mặc định tất cả là string
            
            // Cập nhật kiểu dữ liệu cho các tham số cụ thể
            $param_types_array = str_split($param_types);
            $param_types_array[2] = "d"; // area
            $param_types_array[3] = "d"; // price
            $param_types_array[8] = "i"; // district_id
            $param_types_array[9] = "i"; // city_id
            $param_types_array[10] = "i"; // max_occupants
            $param_types_array[15] = "d"; // lat
            $param_types_array[16] = "d"; // lng
            $param_types_array[18] = "i"; // room_id
            $param_types_array[19] = "i"; // user_id
            
            $param_types = implode("", $param_types_array);
            
            $params = [
                $title,
                $type,
                $area,
                $price,
                $description,
                $address,
                $district,
                $city,
                $district_id,
                $city_id,
                $max_occupants,
                $amenities,
                $facilities,
                $contact_name,
                $contact_phone,
                $lat,
                $lng,
                $updated_at,
                $room_id,
                $user_id
            ];
            
            // Thực hiện bind_param
            $stmt->bind_param($param_types, ...$params);

            if (!$stmt->execute()) {
                throw new Exception("Lỗi thực thi câu lệnh: " . $stmt->error);
            }
            
            $_SESSION['success_message'] = "Cập nhật phòng thành công!";
            header("Location: ../../frontend/room/my_rooms.php");
            exit();
        } catch (Exception $e) {
            $errors[] = "Lỗi: " . $e->getMessage();
        }
    }

    // Nếu có lỗi, quay lại trang sửa
    if (!empty($errors)) {
        $_SESSION['room_errors'] = $errors;
        header("Location: ../../frontend/room/sua_phong.php?id=" . urlencode($room_id));
        exit();
    }
} else {
    // Nếu không phải POST, chuyển về danh sách phòng
    header("Location: ../../frontend/room/my_rooms.php");
    exit();
}
?>