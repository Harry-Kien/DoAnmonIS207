<?php
require_once __DIR__ . '/../models/ChatbotModel.php';

class ChatbotController {
    private $model;
    
    public function __construct($conn) {
        $this->model = new ChatbotModel($conn);
    }
    
    /**
     * Xử lý tin nhắn từ người dùng và trả về câu trả lời
     */
    public function processMessage($message, $user_id = 0) {
        // Nếu không có tin nhắn, trả về lỗi
        if (empty($message)) {
            return ['error' => 'Không có tin nhắn'];
        }
        
        // Lưu tin nhắn của người dùng vào cơ sở dữ liệu
        $this->model->saveUserMessage($user_id, $message);
        
        // Tìm câu trả lời từ các câu hỏi thường gặp
        $response = $this->model->findFAQResponse($message);
        
        // Nếu không tìm thấy câu trả lời từ FAQ, sử dụng API LLM
        if ($response === null) {
            $response = $this->getResponseFromLLM($message);
        }
        
        // Lưu câu trả lời của bot vào cơ sở dữ liệu
        $this->model->saveBotResponse($user_id, $response);
        
        return ['response' => $response];
    }
    
    /**
     * Lấy danh sách tất cả các câu hỏi thường gặp
     */
    public function getAllFAQs() {
        return $this->model->getAllFAQs();
    }
    
    /**
     * Thêm câu hỏi mới
     */
    public function addFAQ($question, $answer) {
        if (empty($question) || empty($answer)) {
            return ['error' => 'Vui lòng điền đầy đủ thông tin!'];
        }
        
        $result = $this->model->addFAQ($question, $answer);
        
        if ($result) {
            return ['success' => 'Đã thêm câu hỏi thành công!'];
        } else {
            return ['error' => 'Có lỗi xảy ra khi thêm câu hỏi!'];
        }
    }
    
    /**
     * Cập nhật câu hỏi
     */
    public function updateFAQ($id, $question, $answer) {
        if ($id <= 0 || empty($question) || empty($answer)) {
            return ['error' => 'Vui lòng điền đầy đủ thông tin!'];
        }
        
        $result = $this->model->updateFAQ($id, $question, $answer);
        
        if ($result) {
            return ['success' => 'Đã cập nhật câu hỏi thành công!'];
        } else {
            return ['error' => 'Có lỗi xảy ra khi cập nhật câu hỏi!'];
        }
    }
    
    /**
     * Xóa câu hỏi
     */
    public function deleteFAQ($id) {
        if ($id <= 0) {
            return ['error' => 'Không tìm thấy câu hỏi!'];
        }
        
        $result = $this->model->deleteFAQ($id);
        
        if ($result) {
            return ['success' => 'Đã xóa câu hỏi thành công!'];
        } else {
            return ['error' => 'Có lỗi xảy ra khi xóa câu hỏi!'];
        }
    }
    
    /**
     * Lấy thống kê chat
     */
    public function getChatStats() {
        return $this->model->getChatStats();
    }
    
    /**
     * Lấy lịch sử chat của người dùng
     */
    public function getUserChatHistory($user_id, $limit = 50) {
        if ($user_id <= 0) {
            return ['error' => 'Không tìm thấy người dùng!'];
        }
        
        return $this->model->getUserChatHistory($user_id, $limit);
    }
    
    /**
     * Lấy câu trả lời từ LLM API (OpenAI GPT)
     */
    private function getResponseFromLLM($message) {
        // Thay thế API_KEY bằng khóa API thực của bạn
        $api_key = "YOUR_OPENAI_API_KEY";
        
        // Nếu không có API key, trả về câu trả lời mặc định
        if ($api_key === "YOUR_OPENAI_API_KEY") {
            return "Tôi không có câu trả lời cho câu hỏi này. Bạn có thể liên hệ với bộ phận hỗ trợ khách hàng qua email support@homseeker.com hoặc số điện thoại 1900 1234.";
        }
        
        // Chuẩn bị dữ liệu gửi đến API
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Bạn là trợ lý ảo của Homseeker, một nền tảng tìm kiếm và cho thuê phòng trọ. Hãy trả lời các câu hỏi liên quan đến việc tìm phòng trọ, đăng tin cho thuê, và các thông tin khác về dịch vụ của Homseeker. Trả lời ngắn gọn, rõ ràng và thân thiện.'
                ],
                [
                    'role' => 'user',
                    'content' => $message
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 150
        ];
        
        // Gửi yêu cầu đến API
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        // Xử lý phản hồi từ API
        if ($response) {
            $response_data = json_decode($response, true);
            if (isset($response_data['choices'][0]['message']['content'])) {
                return $response_data['choices'][0]['message']['content'];
            }
        }
        
        // Trả về câu trả lời mặc định nếu có lỗi
        return "Xin lỗi, tôi không thể trả lời câu hỏi này vào lúc này. Vui lòng thử lại sau hoặc liên hệ với bộ phận hỗ trợ khách hàng.";
    }
} 