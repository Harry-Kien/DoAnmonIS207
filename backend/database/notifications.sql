CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

-- Các loại thông báo (type):
-- comment_received: Nhận được bình luận mới
-- room_approved: Phòng được duyệt
-- room_rejected: Phòng bị từ chối
-- viewing_request: Có người đặt lịch xem phòng
-- favorite_updated: Phòng yêu thích có cập nhật
-- listing_expiring: Tin đăng sắp hết hạn
-- system_maintenance: Thông báo bảo trì
-- new_feature: Tính năng mới
-- promotion: Khuyến mãi 