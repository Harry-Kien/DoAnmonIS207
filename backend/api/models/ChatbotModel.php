<?php
class ChatbotModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Lưu tin nhắn của người dùng vào cơ sở dữ liệu
     */
    public function saveUserMessage($user_id, $message) {
        if ($user_id <= 0) return true;
        
        $stmt = $this->conn->prepare("INSERT INTO chatbot_messages (user_id, message, is_bot, created_at) VALUES (?, ?, 0, NOW())");
        $stmt->bind_param("is", $user_id, $message);
        return $stmt->execute();
    }
    
    /**
     * Lưu câu trả lời của bot vào cơ sở dữ liệu
     */
    public function saveBotResponse($user_id, $response) {
        if ($user_id <= 0) return true;
        
        $stmt = $this->conn->prepare("INSERT INTO chatbot_messages (user_id, message, is_bot, created_at) VALUES (?, ?, 1, NOW())");
        $stmt->bind_param("is", $user_id, $response);
        return $stmt->execute();
    }
    
    /**
     * Tìm câu trả lời từ các câu hỏi thường gặp
     */
    public function findFAQResponse($question) {
        // Chuẩn bị câu hỏi để tìm kiếm
        $question = strtolower(trim($question));
        
        // Kiểm tra xem có bảng chatbot_faqs chưa
        $result = $this->conn->query("SHOW TABLES LIKE 'chatbot_faqs'");
        if ($result->num_rows == 0) {
            // Tạo bảng nếu chưa có
            $this->createFAQTable();
            // Thêm một số câu hỏi và câu trả lời mẫu
            $this->insertSampleFAQs();
        }
        
        // Tìm kiếm câu hỏi tương tự trong cơ sở dữ liệu
        $stmt = $this->conn->prepare("SELECT answer FROM chatbot_faqs WHERE MATCH(question) AGAINST(? IN NATURAL LANGUAGE MODE) LIMIT 1");
        if (!$stmt) {
            // Nếu không hỗ trợ FULLTEXT search, sử dụng LIKE
            $stmt = $this->conn->prepare("SELECT answer FROM chatbot_faqs WHERE question LIKE CONCAT('%', ?, '%') LIMIT 1");
        }
        
        $stmt->bind_param("s", $question);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['answer'];
        }
        
        // Kiểm tra các từ khóa phổ biến
        $keywords = $this->getKeywords();
        
        foreach ($keywords as $key => $value) {
            if (strpos($question, $key) !== false) {
                return $value;
            }
        }
        
        return null;
    }
    
    /**
     * Lấy danh sách từ khóa và câu trả lời
     */
    private function getKeywords() {
        return [
            'giá' => 'Giá phòng trọ trên Homseeker dao động từ 1.5 triệu đến 10 triệu đồng tùy khu vực và tiện nghi.',
            'đặt cọc' => 'Thông thường, bạn cần đặt cọc 1-2 tháng tiền phòng khi thuê phòng trọ.',
            'đăng ký' => 'Bạn có thể đăng ký tài khoản Homseeker bằng cách nhấn vào nút "Đăng ký" ở góc phải trên cùng của trang web.',
            'đăng nhập' => 'Bạn có thể đăng nhập vào tài khoản Homseeker bằng cách nhấn vào nút "Đăng nhập" ở góc phải trên cùng của trang web.',
            'đăng tin' => 'Để đăng tin cho thuê phòng, bạn cần đăng nhập và chọn "Đăng tin" từ menu chính.',
            'tìm phòng' => 'Bạn có thể tìm phòng bằng cách sử dụng công cụ tìm kiếm ở trang chủ hoặc vào mục "Phòng trọ".',
            'liên hệ' => 'Bạn có thể liên hệ với chủ phòng thông qua thông tin liên hệ được hiển thị trong chi tiết phòng trọ.',
            'thanh toán' => 'Homseeker hỗ trợ nhiều phương thức thanh toán như chuyển khoản ngân hàng, ví điện tử, và thanh toán trực tiếp.',
            'xin chào' => 'Xin chào! Tôi là trợ lý ảo của Homseeker. Tôi có thể giúp gì cho bạn?',
            'hello' => 'Xin chào! Tôi là trợ lý ảo của Homseeker. Tôi có thể giúp gì cho bạn?',
            'hi' => 'Xin chào! Tôi là trợ lý ảo của Homseeker. Tôi có thể giúp gì cho bạn?',
            'còn trống' => 'Bạn vui lòng bấm vào từng phòng để xem trạng thái còn trống. Hệ thống luôn cập nhật theo thời gian thực.',
            'máy lạnh' => 'Hầu hết các phòng đều có máy lạnh. Bạn có thể kiểm tra trong chi tiết phòng để chắc chắn.',
            'vệ sinh riêng' => 'Rất nhiều phòng có vệ sinh riêng. Bạn vui lòng lọc theo tiêu chí "Vệ sinh riêng" để tìm nhanh hơn.',
            'nấu ăn' => 'Có, nhiều phòng cho phép nấu ăn. Bạn hãy xem mô tả chi tiết hoặc chọn bộ lọc "Được nấu ăn".',
            'chỗ để xe' => 'Phòng có chỗ để xe máy và một số nơi có bãi giữ xe ô tô. Thông tin cụ thể hiển thị trên mỗi phòng.',
            'tiền điện' => 'Thông thường từ 3.500đ đến 5.000đ/kWh. Tuy nhiên, mức giá có thể khác tùy từng phòng cụ thể.',
            'đại học' => 'Bạn có thể chọn khu vực gần trường đại học hoặc dùng bản đồ để lọc các phòng gần trường của bạn.',
            'đặt phòng' => 'Bạn cần đăng nhập và nhấn nút "Đặt phòng" trong trang chi tiết. Hệ thống sẽ giữ phòng cho bạn trong 24h.',
            'miễn phí' => 'Việc tìm kiếm và xem phòng là hoàn toàn miễn phí. Chỉ khi bạn đăng phòng mới có một số gói tính phí.',
            'ngắn hạn' => 'Một số phòng có hỗ trợ thuê ngắn hạn (theo tuần hoặc vài ngày). Bạn có thể lọc theo mục "Ngắn hạn".',
            'wifi' => 'Đa số các phòng đều có sẵn wifi. Nếu không, bạn có thể tự đăng ký mạng riêng.',
            'uy tín' => 'Chúng tôi kiểm duyệt kỹ tin đăng, có đánh giá người dùng và cơ chế báo cáo tin giả để đảm bảo an toàn cho bạn.',
            'báo cáo' => 'Bạn có thể bấm nút "Báo cáo tin" trong trang chi tiết phòng. Admin sẽ xử lý trong 24h.',
            'hỗ trợ' => 'Chúng tôi có chatbot 24/7 và hỗ trợ qua email hoặc hotline trong giờ hành chính.',
            'đối tượng' => 'Một số phòng chỉ dành cho sinh viên hoặc nữ. Bạn hãy đọc kỹ phần mô tả hoặc hỏi chủ phòng.'
        ];
    }
    
    /**
     * Tạo bảng chatbot_faqs nếu chưa tồn tại
     */
    public function createFAQTable() {
        $sql = "CREATE TABLE IF NOT EXISTS chatbot_faqs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question TEXT NOT NULL,
            answer TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FULLTEXT KEY (question)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->query($sql);
        
        // Tạo bảng lưu lịch sử chat
        $sql = "CREATE TABLE IF NOT EXISTS chatbot_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            is_bot TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->query($sql);
    }
    
    /**
     * Thêm một số câu hỏi và câu trả lời mẫu vào cơ sở dữ liệu
     */
    public function insertSampleFAQs() {
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
            ]
        ];
        
        $stmt = $this->conn->prepare("INSERT INTO chatbot_faqs (question, answer) VALUES (?, ?)");
        
        foreach ($faqs as $faq) {
            $stmt->bind_param("ss", $faq['question'], $faq['answer']);
            $stmt->execute();
        }
    }
    
    /**
     * Lấy danh sách tất cả các câu hỏi thường gặp
     */
    public function getAllFAQs() {
        $faqs = [];
        $result = $this->conn->query("SELECT * FROM chatbot_faqs ORDER BY id DESC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $faqs[] = $row;
            }
        }
        return $faqs;
    }
    
    /**
     * Thêm câu hỏi mới
     */
    public function addFAQ($question, $answer) {
        $stmt = $this->conn->prepare("INSERT INTO chatbot_faqs (question, answer) VALUES (?, ?)");
        $stmt->bind_param("ss", $question, $answer);
        return $stmt->execute();
    }
    
    /**
     * Cập nhật câu hỏi
     */
    public function updateFAQ($id, $question, $answer) {
        $stmt = $this->conn->prepare("UPDATE chatbot_faqs SET question = ?, answer = ? WHERE id = ?");
        $stmt->bind_param("ssi", $question, $answer, $id);
        return $stmt->execute();
    }
    
    /**
     * Xóa câu hỏi
     */
    public function deleteFAQ($id) {
        $stmt = $this->conn->prepare("DELETE FROM chatbot_faqs WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    /**
     * Lấy thống kê chat
     */
    public function getChatStats() {
        $stats = [
            'total_chats' => 0,
            'total_users' => 0
        ];
        
        $result = $this->conn->query("SELECT COUNT(*) as total FROM chatbot_messages");
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_chats'] = $row['total'];
        }
        
        $result = $this->conn->query("SELECT COUNT(DISTINCT user_id) as total FROM chatbot_messages WHERE user_id > 0");
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_users'] = $row['total'];
        }
        
        return $stats;
    }
    
    /**
     * Lấy lịch sử chat của người dùng
     */
    public function getUserChatHistory($user_id, $limit = 50) {
        $history = [];
        
        $stmt = $this->conn->prepare("SELECT * FROM chatbot_messages WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
        }
        
        return array_reverse($history);
    }
}
?> 