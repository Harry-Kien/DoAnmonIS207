<?php
session_start();
require_once "../config/config.php";
require_once "notification_handler.php";

// Kiểm tra nếu người dùng đã đăng nhập
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo "Bạn cần đăng nhập để sử dụng tính năng này.";
    exit;
}

$user_id = $_SESSION['user_id'];

// Khởi tạo notification handler
$notificationHandler = new NotificationHandler($conn);

// Lấy một phòng ngẫu nhiên của người dùng để làm related_id
$sql = "SELECT id FROM rooms WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$room_id = $result->num_rows > 0 ? $result->fetch_assoc()['id'] : null;

// Thêm các thông báo mẫu
$notifications = [
    [
        'type' => 'room_approved',
        'title' => 'Phòng đã được duyệt',
        'message' => 'Phòng trọ của bạn đã được quản trị viên phê duyệt và hiển thị công khai.',
        'related_id' => $room_id
    ],
    [
        'type' => 'comment_received',
        'title' => 'Có bình luận mới',
        'message' => 'Có người đã bình luận về phòng trọ của bạn.',
        'related_id' => $room_id
    ],
    [
        'type' => 'viewing_request',
        'title' => 'Yêu cầu xem phòng',
        'message' => 'Có người muốn đặt lịch xem phòng trọ của bạn.',
        'related_id' => $room_id
    ],
    [
        'type' => 'system_maintenance',
        'title' => 'Bảo trì hệ thống',
        'message' => 'Hệ thống sẽ bảo trì vào ngày 30/04/2025 từ 23:00 đến 01:00.',
        'related_id' => null
    ],
    [
        'type' => 'new_feature',
        'title' => 'Tính năng mới',
        'message' => 'Chúng tôi vừa cập nhật tính năng mới: Đặt lịch xem phòng tự động.',
        'related_id' => null
    ]
];

$success_count = 0;

// Thêm từng thông báo
foreach ($notifications as $notification) {
    $result = $notificationHandler->addNotification(
        $user_id,
        $notification['type'],
        $notification['title'],
        $notification['message'],
        $notification['related_id']
    );
    
    if ($result) {
        $success_count++;
    }
}

echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
echo "<h2 style='color: #4CAF50;'>Đã thêm thành công $success_count thông báo mẫu</h2>";
echo "<p>Các thông báo đã được thêm vào tài khoản của bạn. Hãy quay lại <a href='../../frontend/pages/welcome.php' style='color: #2196F3; text-decoration: none;'>trang cá nhân</a> để xem thông báo.</p>";
echo "</div>";
?> 