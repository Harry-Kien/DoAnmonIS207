<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Kiểm tra đăng nhập admin (cho giao diện thủ công)
$is_admin_request = !isset($_SERVER['HTTP_X_N8N_TOKEN']);
if ($is_admin_request) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        header("Location: ../../frontend/auth/login.php");
        exit();
    }
}

// Xác thực token cho yêu cầu từ n8n
if (!$is_admin_request) {
    $n8n_token = $_SERVER['HTTP_X_N8N_TOKEN'] ?? '';
    $expected_token = 'your-n8n-token-here'; // Thay bằng token bảo mật của bạn
    if ($n8n_token !== $expected_token) {
        http_response_code(403);
        echo json_encode(['error' => 'Xác thực không hợp lệ']);
        exit();
    }
}

$page_title = "Thêm bài viết blog";

// Lấy danh sách danh mục
$categories_query = "SELECT id, name FROM blog_categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);

// Xử lý yêu cầu thêm bài viết
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form hoặc API
    $title = trim($_POST['title'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $created_at = date('Y-m-d H:i:s');

    // Kiểm tra dữ liệu
    if (empty($title) || empty($excerpt) || empty($content) || empty($author) || empty($category_id)) {
        $error_message = "Vui lòng điền đầy đủ thông tin.";
        if (!$is_admin_request) {
            http_response_code(400);
            echo json_encode(['error' => $error_message]);
            exit();
        }
        $_SESSION['error_message'] = $error_message;
    } else {
        // Xử lý upload ảnh
        $image_url = '';
        $upload_dir = __DIR__ . '/../../frontend/assets/images/uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if ($is_admin_request && !empty($_FILES['image']['name'])) {
            // Xử lý upload ảnh từ giao diện admin
            $upload_name = time() . '-' . basename($_FILES['image']['name']);
            $upload_path = $upload_dir . $upload_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_url = '/frontend/assets/images/uploads/' . $upload_name;
            } else {
                $error_message = "Lỗi khi upload hình ảnh.";
                if (!$is_admin_request) {
                    http_response_code(500);
                    echo json_encode(['error' => $error_message]);
                    exit();
                }
                $_SESSION['error_message'] = $error_message;
                header("Location: ../../backend/baiviet/add_blog_post.php");
                exit();
            }
        } elseif (!$is_admin_request && !empty($_FILES['image']['tmp_name'])) {
            // Xử lý upload ảnh từ n8n
            $upload_name = time() . '-' . basename($_FILES['image']['name']);
            $upload_path = $upload_dir . $upload_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_url = '/frontend/assets/images/uploads/' . $upload_name;
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Lỗi khi upload hình ảnh từ n8n']);
                exit();
            }
        }

        // Thêm bài viết vào cơ sở dữ liệu
        $sql = "INSERT INTO blog_posts (title, excerpt, content, image_url, author, category_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssis", $title, $excerpt, $content, $image_url, $author, $category_id, $created_at);

        if ($stmt->execute()) {
            // Cập nhật số lượng bài viết trong danh mục
            $update_sql = "UPDATE blog_categories SET post_count = post_count + 1 WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $category_id);
            $update_stmt->execute();
            $update_stmt->close();

            if ($is_admin_request) {
                $_SESSION['success_message'] = "Thêm bài viết thành công!";
                header("Location: ../../backend/baiviet/blog_posts.php");
            } else {
                http_response_code(201);
                echo json_encode(['message' => 'Thêm bài viết thành công!', 'post_id' => $stmt->insert_id]);
            }
            exit();
        } else {
            $error_message = "Lỗi khi thêm bài viết: " . $stmt->error;
            if ($is_admin_request) {
                $_SESSION['error_message'] = $error_message;
            } else {
                http_response_code(500);
                echo json_encode(['error' => $error_message]);
            }
        }
        $stmt->close();
    }
}

// Giao diện (chỉ hiển thị cho yêu cầu từ admin)
if ($is_admin_request) {
    include '../../frontend/pages/header.php';
?>

<!-- Tiêu đề trang -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Thêm bài viết blog</h1>
</div>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Form thêm bài viết -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Thêm bài viết mới</h6>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="title" class="form-label">Tiêu đề</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="mb-3">
                <label for="excerpt" class="form-label">Đoạn trích</label>
                <textarea class="form-control" id="excerpt" name="excerpt" rows="3" required></textarea>
            </div>
            <div class="mb-3">
                <label for="content" class="form-label">Nội dung</label>
                <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Hình ảnh minh họa</label>
                <input type="file" class="form-control" id="image" name="image" accept="image/*">
            </div>
            <div class="mb-3">
                <label for="author" class="form-label">Tác giả</label>
                <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="category_id" class="form-label">Danh mục</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">-- Chọn danh mục --</option>
                    <?php while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Thêm bài viết</button>
            <a href="../../backend/baiviet/blog_posts.php" class="btn btn-secondary">Hủy</a>
        </form>
    </div>
</div>

<?php
    include '../../frontend/pages/footer.php';
}
?>