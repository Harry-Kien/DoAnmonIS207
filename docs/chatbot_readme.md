# Hệ thống Chatbot cho Homseeker

Hệ thống chatbot cho website Homseeker được phát triển theo mô hình MVC (Model-View-Controller) để hỗ trợ người dùng tìm kiếm thông tin về phòng trọ, căn hộ và các dịch vụ liên quan.

## Cấu trúc thư mục

```
├── backend/
│   ├── api/
│   │   ├── chatbot.php                    # API endpoint chính cho chatbot
│   │   ├── controllers/
│   │   │   └── ChatbotController.php      # Controller xử lý logic chatbot
│   │   ├── models/
│   │   │   └── ChatbotModel.php           # Model tương tác với cơ sở dữ liệu
│   │   └── admin/
│   │       └── chatbot_management_api.php # API quản lý chatbot cho admin
│   └── database/
│       └── chatbot_setup.php              # Script cài đặt cơ sở dữ liệu cho chatbot
├── frontend/
│   ├── admin/
│   │   └── chatbot_management.php         # Giao diện quản lý chatbot cho admin
│   ├── components/
│   │   └── chatbot.php                    # Component chatbot để hiển thị trên trang
│   └── includes/
│       └── chatbot.php                    # File include để thêm chatbot vào các trang
└── docs/
    └── chatbot_readme.md                  # Tài liệu hướng dẫn về chatbot (file này)
```

## Cơ sở dữ liệu

Hệ thống chatbot sử dụng hai bảng trong cơ sở dữ liệu:

1. **chatbot_faqs**: Lưu trữ các câu hỏi thường gặp và câu trả lời
   - `id`: ID của câu hỏi
   - `question`: Nội dung câu hỏi
   - `answer`: Câu trả lời
   - `created_at`: Thời gian tạo
   - `updated_at`: Thời gian cập nhật

2. **chatbot_messages**: Lưu trữ lịch sử chat của người dùng
   - `id`: ID của tin nhắn
   - `user_id`: ID của người dùng
   - `message`: Nội dung tin nhắn
   - `is_bot`: Cờ đánh dấu tin nhắn từ bot (1) hoặc từ người dùng (0)
   - `created_at`: Thời gian tạo tin nhắn

## Cài đặt

1. Import cơ sở dữ liệu bằng cách chạy script `backend/database/chatbot_setup.php`
2. Thêm chatbot vào trang web bằng cách thêm dòng sau vào footer hoặc các trang cần hiển thị chatbot:
   ```php
   include_once 'frontend/includes/chatbot.php';
   ```

## Tính năng chính

1. **Trả lời tự động**: Chatbot có thể tự động trả lời các câu hỏi thường gặp dựa trên cơ sở dữ liệu FAQ.
2. **Tìm kiếm ngữ nghĩa**: Sử dụng FULLTEXT search để tìm kiếm câu trả lời phù hợp nhất.
3. **Fallback sang API LLM**: Nếu không tìm thấy câu trả lời trong cơ sở dữ liệu, chatbot sẽ sử dụng API LLM (như OpenAI GPT) để trả lời.
4. **Lưu lịch sử chat**: Lưu lại lịch sử chat của người dùng để phân tích và cải thiện chatbot.
5. **Quản lý FAQ**: Admin có thể thêm, sửa, xóa các câu hỏi và câu trả lời thông qua giao diện quản lý.

## Cấu hình API LLM

Để sử dụng API LLM (như OpenAI GPT), bạn cần cập nhật API key trong file `backend/api/controllers/ChatbotController.php`:

```php
private function getResponseFromLLM($message) {
    // Thay thế API_KEY bằng khóa API thực của bạn
    $api_key = "YOUR_OPENAI_API_KEY";
    // ...
}
```

## Quản lý Chatbot

Admin có thể quản lý chatbot thông qua giao diện tại `frontend/admin/chatbot_management.php`. Tại đây, admin có thể:

1. Xem thống kê sử dụng chatbot
2. Thêm câu hỏi và câu trả lời mới
3. Chỉnh sửa câu hỏi và câu trả lời hiện có
4. Xóa câu hỏi không cần thiết

## API Endpoints

1. **Endpoint chính**: `backend/api/chatbot.php`
   - Method: POST
   - Tham số: `message` (nội dung tin nhắn), `user_id` (ID người dùng, tùy chọn)
   - Phản hồi: JSON chứa câu trả lời của chatbot

2. **API quản lý cho admin**: `backend/api/admin/chatbot_management_api.php`
   - GET `?action=faqs`: Lấy danh sách câu hỏi thường gặp
   - GET `?action=stats`: Lấy thống kê sử dụng chatbot
   - GET `?action=history&user_id=X`: Lấy lịch sử chat của người dùng
   - POST `?action=add_faq`: Thêm câu hỏi mới
   - PUT `?action=update_faq`: Cập nhật câu hỏi
   - DELETE `?action=delete_faq&id=X`: Xóa câu hỏi

## Tùy chỉnh giao diện

Bạn có thể tùy chỉnh giao diện chatbot bằng cách chỉnh sửa CSS trong file `frontend/components/chatbot.php`. Các lớp CSS chính:

- `.chatbot-container`: Container chính của chatbot
- `.chatbot-header`: Phần header của chatbot
- `.chatbot-messages`: Phần hiển thị tin nhắn
- `.chatbot-input`: Phần nhập tin nhắn
- `.chatbot-trigger`: Nút kích hoạt chatbot

## Mở rộng

Để mở rộng chức năng của chatbot, bạn có thể:

1. Thêm các từ khóa và câu trả lời mới vào phương thức `getKeywords()` trong `ChatbotModel.php`
2. Tích hợp với các API NLP khác như Dialogflow, Rasa, v.v.
3. Thêm tính năng phân tích cảm xúc để hiểu tâm trạng người dùng
4. Tích hợp với hệ thống đặt phòng để cho phép người dùng đặt phòng trực tiếp qua chatbot 