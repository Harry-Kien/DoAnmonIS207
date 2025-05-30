document.addEventListener('DOMContentLoaded', function() {
    // Xử lý đánh dấu thông báo đã đọc
    const markAsReadButtons = document.querySelectorAll('.mark-as-read');
    markAsReadButtons.forEach(button => {
        button.addEventListener('click', function() {
            const notificationId = this.dataset.id;
            markNotificationAsRead(notificationId, this);
        });
    });
});

function markNotificationAsRead(notificationId, buttonElement) {
    fetch('../../backend/notifications/mark_as_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `notification_id=${notificationId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cập nhật giao diện
            const notificationItem = buttonElement.closest('.notification-item');
            notificationItem.classList.remove('bg-warning', 'bg-opacity-10');
            notificationItem.classList.add('bg-light');
            buttonElement.remove();
            
            // Cập nhật số thông báo chưa đọc
            updateUnreadCount();
        }
    })
    .catch(error => console.error('Error:', error));
}

function updateUnreadCount() {
    fetch('../../backend/notifications/get_unread_count.php')
    .then(response => response.json())
    .then(data => {
        const unreadBadge = document.querySelector('.notification-badge');
        if (unreadBadge) {
            if (data.count > 0) {
                unreadBadge.textContent = data.count;
                unreadBadge.style.display = 'inline';
            } else {
                unreadBadge.style.display = 'none';
            }
        }
    })
    .catch(error => console.error('Error:', error));
} 