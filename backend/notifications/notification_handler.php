<?php
require_once __DIR__ . "/../../backend/config/config.php";

class NotificationHandler {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    // Thêm thông báo mới
    public function addNotification($user_id, $type, $title, $message, $related_id = null) {
        $sql = "INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isssi", $user_id, $type, $title, $message, $related_id);
        return $stmt->execute();
    }
    
    // Lấy danh sách thông báo của user
    public function getUserNotifications($user_id, $limit = 10) {
        $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Đánh dấu thông báo đã đọc
    public function markAsRead($notification_id, $user_id) {
        $sql = "UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $notification_id, $user_id);
        return $stmt->execute();
    }
    
    // Đếm số thông báo chưa đọc
    public function getUnreadCount($user_id) {
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'];
    }
    
    // Xóa thông báo
    public function deleteNotification($notification_id, $user_id) {
        $sql = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $notification_id, $user_id);
        return $stmt->execute();
    }
}
?> 