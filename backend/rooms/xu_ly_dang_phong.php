<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

// Kiểm tra form submit
function geocodeAddress($address) {
    $accessToken = 'YOUR_MAPBOX_ACCESS_TOKEN'; // 👉 Thay bằng token thật
    $encodedAddress = urlencode($address);
    $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/$encodedAddress.json?access_token=$accessToken&limit=1";

    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if (!empty($data['features']) && isset($data['features'][0]['center'])) {
        $lng = $data['features'][0]['center'][0]; // ⚠ Mapbox trả về [lng, lat]
        $lat = $data['features'][0]['center'][1];
        return [$lat, $lng];
    }

    return [null, null];
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once '../../backend/config/config.php';

    // Validate và lọc dữ liệu đầu vào
    $title = trim(mysqli_real_escape_string($conn, $_POST['title']));
    $type = trim(mysqli_real_escape_string($conn, $_POST['type']));
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $area = filter_var($_POST['area'], FILTER_VALIDATE_FLOAT);
    $address = trim(mysqli_real_escape_string($conn, $_POST['address']));
    $district_id = !empty($_POST['district_id']) ? (int)$_POST['district_id'] : null;
    $city_id = !empty($_POST['city_id']) ? (int)$_POST['city_id'] : null;

    // Lấy tên quận/huyện và tỉnh/thành phố
    $district = '';
    $city = '';
    if ($district_id && $city_id) {
        $sql_loc = "SELECT d.name AS district_name, c.name AS city_name 
                   FROM districts d 
                   JOIN cities c ON d.city_id = c.id 
                   WHERE d.id = ?";
        $stmt_loc = $conn->prepare($sql_loc);
        $stmt_loc->bind_param("i", $district_id);
        $stmt_loc->execute();
        $result_loc = $stmt_loc->get_result();
        $loc_data = $result_loc->fetch_assoc();
        if ($loc_data) {
            $district = $loc_data['district_name'];
            $city = $loc_data['city_name'];
        }
        $stmt_loc->close();
    }

    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
    $amenities = mysqli_real_escape_string($conn, $_POST['amenities'] ?? '');
    $facilities = mysqli_real_escape_string($conn, $_POST['facilities'] ?? '');
    $max_occupants = filter_var($_POST['max_occupants'] ?? 1, FILTER_VALIDATE_INT);
    $contact_name = trim(mysqli_real_escape_string($conn, $_POST['contact_name']));
    $contact_phone = trim(mysqli_real_escape_string($conn, $_POST['contact_phone']));

    // Validate các trường bắt buộc
    $errors = [];
    if (empty($title)) $errors[] = "Tiêu đề không được để trống";
    if (empty($price) || $price <= 0) $errors[] = "Giá phải là số dương";
    if (empty($area) || $area <= 0) $errors[] = "Diện tích phải là số dương";
    if (empty($address)) $errors[] = "Địa chỉ không được để trống";
    if (!preg_match('/^[0-9]{10,11}$/', $contact_phone)) $errors[] = "Số điện thoại không hợp lệ";

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['old_inputs'] = $_POST;
        header("Location: dang_phong.php");
        exit();
    }

    // Các trường mặc định
    $is_verified = 0;
    $status = 'pending';
    $is_premium = 0;
    $is_featured = 0;
    $priority_until = null;
    $created_at = date('Y-m-d H:i:s');
    $updated_at = $created_at;

    // Lấy lat/lng từ POST (ưu tiên client-side)
$lat = isset($_POST['lat']) && $_POST['lat'] !== '' ? floatval($_POST['lat']) : null;
$lng = isset($_POST['lng']) && $_POST['lng'] !== '' ? floatval($_POST['lng']) : null;

// Nếu client không gửi lat/lng, fallback sang geocode server-side (không khuyến khích)
if ($lat === null || $lng === null) {
    $full_address = "$address, $district, $city";
    list($lat, $lng) = geocodeAddress($full_address);
}

// Chuẩn bị truy vấn
$sql = "INSERT INTO rooms (
    user_id, title, type, price, area, address, district_id, city_id, district, city,
    description, amenities, facilities, max_occupants, contact_name, contact_phone,
    is_verified, status, created_at, updated_at, is_premium, is_featured, priority_until,
    lat, lng
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "issddiissssssisssssiissdd",
    $_SESSION['user_id'], // i
    $title,               // s
    $type,                // s
    $price,               // d
    $area,                // d
    $address,             // s
    $district_id,         // i
    $city_id,             // i
    $district,            // s
    $city,                // s
    $description,         // s
    $amenities,           // s
    $facilities,          // s
    $max_occupants,       // i
    $contact_name,        // s
    $contact_phone,       // s
    $is_verified,         // i
    $status,              // s
    $created_at,          // s
    $updated_at,          // s
    $is_premium,          // i
    $is_featured,         // i
    $priority_until,      // s
    $lat,                 // d
    $lng                  // d
);

    if ($stmt->execute()) {
        $room_id = $stmt->insert_id;

        // Xử lý upload hình ảnh
        if (!empty($_FILES['images']['name'][0])) {
            $upload_dir = "../../frontend/assets/uploads/rooms/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $max_files = 5;
            $file_count = count($_FILES['images']['name']);
            $files_to_upload = min($file_count, $max_files);

            for ($i = 0; $i < $files_to_upload; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['images']['tmp_name'][$i];
                    $name = basename($_FILES['images']['name'][$i]);
                    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    if (!in_array($extension, $allowed_types)) continue;
                    if ($_FILES['images']['size'][$i] > 2 * 1024 * 1024) continue;

                    $new_filename = $room_id . '_' . uniqid() . '.' . $extension;
                    $destination = $upload_dir . $new_filename;

                    if (move_uploaded_file($tmp_name, $destination)) {
                        // Lưu đường dẫn tương đối để frontend dễ truy cập
                        $relative_path = 'uploads/rooms/' . $new_filename;
                        $sql_image = "INSERT INTO room_images (
                            room_id, image_path, is_primary, display_order, created_at
                        ) VALUES (?, ?, ?, ?, NOW())";
                        $stmt_image = $conn->prepare($sql_image);
                        $is_primary = ($i === 0) ? 1 : 0;
                        $display_order = $i + 1;
                        $stmt_image->bind_param(
                            "isii",
                            $room_id, $relative_path, $is_primary, $display_order
                        );
                        $stmt_image->execute();
                        $stmt_image->close();
                    }
                }
            }
        }

        $_SESSION['success_message'] = "Đăng tin thành công! Tin của bạn đang chờ xét duyệt.";
        header("Location: ../../frontend/room/my_rooms.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Có lỗi xảy ra: " . $stmt->error;
        header("Location: dang_phong.php");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: dang_phong.php");
    exit();
}

$lat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;
?>