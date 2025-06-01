<?php
header('Content-Type: application/json');
session_start();
require_once '../../config/config.php';
require_once '../controllers/ChatbotController.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Khởi tạo controller
$chatbotController = new ChatbotController($conn);

// Xử lý các yêu cầu API
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        // Lấy danh sách câu hỏi hoặc thống kê
        if ($action === 'faqs') {
            $faqs = $chatbotController->getAllFAQs();
            echo json_encode(['faqs' => $faqs]);
        } elseif ($action === 'stats') {
            $stats = $chatbotController->getChatStats();
            echo json_encode($stats);
        } elseif ($action === 'history' && isset($_GET['user_id'])) {
            $user_id = (int)$_GET['user_id'];
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $history = $chatbotController->getUserChatHistory($user_id, $limit);
            echo json_encode(['history' => $history]);
        } else {
            echo json_encode(['error' => 'Invalid action']);
        }
        break;
        
    case 'POST':
        // Thêm câu hỏi mới
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($action === 'add_faq') {
            $question = $data['question'] ?? '';
            $answer = $data['answer'] ?? '';
            
            $result = $chatbotController->addFAQ($question, $answer);
            echo json_encode($result);
        } else {
            echo json_encode(['error' => 'Invalid action']);
        }
        break;
        
    case 'PUT':
        // Cập nhật câu hỏi
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($action === 'update_faq') {
            $id = $data['id'] ?? 0;
            $question = $data['question'] ?? '';
            $answer = $data['answer'] ?? '';
            
            $result = $chatbotController->updateFAQ($id, $question, $answer);
            echo json_encode($result);
        } else {
            echo json_encode(['error' => 'Invalid action']);
        }
        break;
        
    case 'DELETE':
        // Xóa câu hỏi
        if ($action === 'delete_faq' && isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            
            $result = $chatbotController->deleteFAQ($id);
            echo json_encode($result);
        } else {
            echo json_encode(['error' => 'Invalid action']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?> 