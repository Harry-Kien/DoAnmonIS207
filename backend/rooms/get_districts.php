<?php
require_once '../../backend/config/config.php'; // Đảm bảo đúng đường dẫn tới file config
header('Content-Type: application/json');

$city_id = isset($_GET['city_id']) ? intval($_GET['city_id']) : 0;
$result = [];

if ($city_id > 0) {
    $sql = "SELECT id, name FROM districts WHERE city_id = $city_id ORDER BY name ASC";
    $query = $conn->query($sql);
    while ($row = $query->fetch_assoc()) {
        $result[] = $row;
    }
}
echo json_encode($result);