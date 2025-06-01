<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Script để tạo các bảng cần thiết cho chatbot
 */

// Tạo bảng chatbot_faqs nếu chưa tồn tại
$sql = "CREATE TABLE IF NOT EXISTS chatbot_faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FULLTEXT KEY (question)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "Bảng chatbot_faqs đã được tạo hoặc đã tồn tại.<br>";
} else {
    echo "Lỗi khi tạo bảng chatbot_faqs: " . $conn->error . "<br>";
}

// Tạo bảng chatbot_messages nếu chưa tồn tại
$sql = "CREATE TABLE IF NOT EXISTS chatbot_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_bot TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "Bảng chatbot_messages đã được tạo hoặc đã tồn tại.<br>";
} else {
    echo "Lỗi khi tạo bảng chatbot_messages: " . $conn->error . "<br>";
}

// Kiểm tra xem đã có dữ liệu trong bảng chatbot_faqs chưa
$result = $conn->query("SELECT COUNT(*) as count FROM chatbot_faqs");
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // Thêm một số câu hỏi và câu trả lời mẫu
    $faqs = [
        [
            'question' => 'làm thế nào để đăng ký tài khoản',
            'answer' => 'Để đăng ký tài khoản trên Homseeker, bạn có thể nhấn vào nút "Đăng ký" ở góc phải trên cùng của trang web, sau đó điền đầy đủ thông tin vào biểu mẫu đăng ký.'
        ],
        [
            'question' => 'làm sao để đăng tin cho thuê phòng',
            'answer' => 'Để đăng tin cho thuê phòng, bạn cần đăng nhập vào tài khoản Homseeker, sau đó nhấn vào nút "Đăng tin" từ menu chính. Điền đầy đủ thông tin về phòng trọ của bạn và nhấn "Đăng tin".'
        ],
        [
            'question' => 'làm thế nào để tìm phòng trọ',
            'answer' => 'Bạn có thể tìm phòng trọ bằng cách sử dụng công cụ tìm kiếm ở trang chủ, chọn khu vực, mức giá và các tiện ích bạn mong muốn, sau đó nhấn "Tìm kiếm".'
        ],
        [
            'question' => 'giá phòng trọ như thế nào',
            'answer' => 'Giá phòng trọ trên Homseeker dao động từ 1.5 triệu đến 10 triệu đồng tùy thuộc vào khu vực, diện tích và tiện nghi của phòng.'
        ],
        [
            'question' => 'làm thế nào để liên hệ với chủ phòng',
            'answer' => 'Bạn có thể liên hệ với chủ phòng thông qua số điện thoại hoặc email được hiển thị trong chi tiết phòng trọ. Nếu bạn đã đăng nhập, bạn cũng có thể sử dụng tính năng nhắn tin trực tiếp.'
        ],
        [
            'question' => 'homseeker là gì',
            'answer' => 'Homseeker là nền tảng kết nối giữa người tìm phòng trọ và chủ phòng cho thuê. Chúng tôi cung cấp thông tin chi tiết về các phòng trọ, căn hộ, nhà nguyên căn để giúp bạn tìm được nơi ở phù hợp nhất.'
        ],
        [
            'question' => 'phòng còn trống không',
            'answer' => 'Bạn vui lòng bấm vào từng phòng để xem trạng thái còn trống. Hệ thống luôn cập nhật theo thời gian thực.'
        ],
        [
            'question' => 'giá thuê phòng là bao nhiêu',
            'answer' => 'Giá thuê phòng dao động từ 1 triệu đến 3 triệu/tháng tùy khu vực và diện tích.'
        ],
        [
            'question' => 'phòng có máy lạnh không',
            'answer' => 'Hầu hết các phòng đều có máy lạnh. Bạn có thể kiểm tra trong chi tiết phòng để chắc chắn.'
        ],
        [
            'question' => 'phòng có vệ sinh riêng không',
            'answer' => 'Rất nhiều phòng có vệ sinh riêng. Bạn vui lòng lọc theo tiêu chí "Vệ sinh riêng" để tìm nhanh hơn.'
        ],
        [
            'question' => 'làm thế nào để đặt cọc phòng',
            'answer' => 'Để đặt cọc phòng, bạn cần liên hệ trực tiếp với chủ phòng qua thông tin liên hệ được cung cấp. Thông thường, bạn sẽ cần đặt cọc 1-2 tháng tiền phòng và ký hợp đồng thuê phòng.'
        ],
        [
            'question' => 'có phải trả phí để đăng tin không',
            'answer' => 'Hiện tại, Homseeker cho phép người dùng đăng tin miễn phí với số lượng tin nhất định. Nếu bạn muốn đăng nhiều tin hơn hoặc muốn tin của mình được hiển thị nổi bật, bạn có thể sử dụng các gói dịch vụ trả phí của chúng tôi.'
        ],
        [
            'question' => 'làm thế nào để báo cáo tin đăng không chính xác',
            'answer' => 'Nếu bạn phát hiện tin đăng không chính xác hoặc lừa đảo, bạn có thể nhấn vào nút "Báo cáo" trong trang chi tiết phòng trọ và cung cấp lý do báo cáo. Đội ngũ quản trị viên của chúng tôi sẽ xem xét và xử lý báo cáo của bạn.'
        ],
        [
            'question' => 'làm thế nào để thay đổi thông tin cá nhân',
            'answer' => 'Để thay đổi thông tin cá nhân, bạn cần đăng nhập vào tài khoản Homseeker, sau đó vào "Tài khoản" > "Thông tin cá nhân" và cập nhật thông tin mong muốn.'
        ],
        [
            'question' => 'có thể thuê phòng ngắn hạn không',
            'answer' => 'Có, một số phòng trên Homseeker có hỗ trợ cho thuê ngắn hạn. Bạn có thể sử dụng bộ lọc "Cho thuê ngắn hạn" để tìm các phòng phù hợp.'
        ]
    ];
    
    $stmt = $conn->prepare("INSERT INTO chatbot_faqs (question, answer) VALUES (?, ?)");
    
    foreach ($faqs as $faq) {
        $stmt->bind_param("ss", $faq['question'], $faq['answer']);
        if ($stmt->execute()) {
            echo "Đã thêm câu hỏi: " . htmlspecialchars($faq['question']) . "<br>";
        } else {
            echo "Lỗi khi thêm câu hỏi: " . $stmt->error . "<br>";
        }
    }
    
    echo "Đã thêm các câu hỏi mẫu vào cơ sở dữ liệu.<br>";
} else {
    echo "Đã có dữ liệu trong bảng chatbot_faqs.<br>";
}

echo "Quá trình cài đặt cơ sở dữ liệu cho chatbot đã hoàn tất.";
?> 