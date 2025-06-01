<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/controllers/ChatbotController.php';

// Khởi tạo controller
$chatbotController = new ChatbotController($conn);

// Nhận dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'] ?? '';
$user_id = $data['user_id'] ?? 0;

// Xử lý tin nhắn và trả về kết quả
$result = $chatbotController->processMessage($message, $user_id);

// Trả về kết quả dưới dạng JSON
echo json_encode($result);
exit;

/**
 * Lấy câu trả lời từ LLM API (OpenAI GPT)
 */
function getResponseFromLLM($message) {
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
?> 