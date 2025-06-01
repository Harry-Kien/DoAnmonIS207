<?php
session_start();
require_once '../../backend/config/config.php';
require_once '../../backend/api/controllers/ChatbotController.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

// Khởi tạo controller
$chatbotController = new ChatbotController($conn);

// Xử lý thêm/sửa câu hỏi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            // Thêm câu hỏi mới
            $question = $_POST['question'] ?? '';
            $answer = $_POST['answer'] ?? '';
            
            $result = $chatbotController->addFAQ($question, $answer);
            
            if (isset($result['success'])) {
                $_SESSION['success_message'] = $result['success'];
            } else {
                $_SESSION['error_message'] = $result['error'] ?? 'Có lỗi xảy ra!';
            }
        } elseif ($_POST['action'] === 'edit') {
            // Cập nhật câu hỏi
            $id = $_POST['id'] ?? 0;
            $question = $_POST['question'] ?? '';
            $answer = $_POST['answer'] ?? '';
            
            $result = $chatbotController->updateFAQ($id, $question, $answer);
            
            if (isset($result['success'])) {
                $_SESSION['success_message'] = $result['success'];
            } else {
                $_SESSION['error_message'] = $result['error'] ?? 'Có lỗi xảy ra!';
            }
        } elseif ($_POST['action'] === 'delete') {
            // Xóa câu hỏi
            $id = $_POST['id'] ?? 0;
            
            $result = $chatbotController->deleteFAQ($id);
            
            if (isset($result['success'])) {
                $_SESSION['success_message'] = $result['success'];
            } else {
                $_SESSION['error_message'] = $result['error'] ?? 'Có lỗi xảy ra!';
            }
        }
    }
    
    // Chuyển hướng để tránh gửi lại form khi refresh
    header("Location: chatbot_management.php");
    exit();
}

// Lấy danh sách câu hỏi
$faqs = $chatbotController->getAllFAQs();

// Lấy thống kê chat
$stats = $chatbotController->getChatStats();
$total_chats = $stats['total_chats'];
$total_users = $stats['total_users'];

$page_title = "Quản lý Chatbot";
include '../pages/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Quản lý Chatbot</h1>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success_message']; 
            unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error_message']; 
            unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Thống kê</h5>
                    <p>Tổng số tin nhắn: <strong><?php echo $total_chats; ?></strong></p>
                    <p>Số người dùng đã sử dụng chatbot: <strong><?php echo $total_users; ?></strong></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Thêm câu hỏi mới</h5>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="question" class="form-label">Câu hỏi</label>
                            <input type="text" class="form-control" id="question" name="question" required>
                        </div>
                        <div class="mb-3">
                            <label for="answer" class="form-label">Câu trả lời</label>
                            <textarea class="form-control" id="answer" name="answer" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Thêm câu hỏi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Danh sách câu hỏi</h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Câu hỏi</th>
                            <th>Câu trả lời</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($faqs)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Không có câu hỏi nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($faqs as $faq): ?>
                                <tr>
                                    <td><?php echo $faq['id']; ?></td>
                                    <td><?php echo htmlspecialchars($faq['question']); ?></td>
                                    <td><?php echo htmlspecialchars($faq['answer']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($faq['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary edit-faq" 
                                                data-id="<?php echo $faq['id']; ?>"
                                                data-question="<?php echo htmlspecialchars($faq['question']); ?>"
                                                data-answer="<?php echo htmlspecialchars($faq['answer']); ?>">
                                            <i class="fas fa-edit"></i> Sửa
                                        </button>
                                        <form method="post" action="" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa câu hỏi này?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $faq['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> Xóa
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal sửa câu hỏi -->
<div class="modal fade" id="editFaqModal" tabindex="-1" aria-labelledby="editFaqModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editFaqModalLabel">Sửa câu hỏi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_question" class="form-label">Câu hỏi</label>
                        <input type="text" class="form-control" id="edit_question" name="question" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_answer" class="form-label">Câu trả lời</label>
                        <textarea class="form-control" id="edit_answer" name="answer" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý sự kiện click nút sửa
    const editButtons = document.querySelectorAll('.edit-faq');
    const editModal = new bootstrap.Modal(document.getElementById('editFaqModal'));
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const question = this.getAttribute('data-question');
            const answer = this.getAttribute('data-answer');
            
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_question').value = question;
            document.getElementById('edit_answer').value = answer;
            
            editModal.show();
        });
    });
});
</script>

<?php include '../pages/footer.php'; ?> 