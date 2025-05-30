<?php
session_start();
require_once __DIR__ . "/../config/config.php";

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

// Lấy ID bài viết từ query string
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Lấy thông tin bài viết
$sql = "SELECT title, excerpt, content, image_url, author, category_id FROM blog_posts WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $post_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$post = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$post) {
    $_SESSION['error_message'] = "Bài viết không tồn tại.";
    header("Location: ../../backend/baiviet/blog_posts.php");
    exit();
}

$page_title = "Sửa bài viết blog";

// Lấy danh sách danh mục
$categories_query = "SELECT id, name FROM blog_categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);

// Xử lý form sửa bài viết
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $excerpt = trim($_POST['excerpt']);
    $content = trim($_POST['content']);
    $author = trim($_POST['author']);
    $category_id = intval($_POST['category_id']);

    if (empty($title) || empty($excerpt) || empty($content) || empty($author) || empty($category_id)) {
        $_SESSION['error_message'] = "Vui lòng điền đầy đủ thông tin.";
    } else {
        // Xử lý upload hình ảnh mới (nếu có)
        $image_url = $post['image_url'];
        if (!empty($_FILES['image']['name'])) {
            $target_dir = __DIR__ . "/../../frontend/assets/uploads/";
            $target_file = $target_dir . basename($_FILES["image"]["name"]);
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_url = "../../frontend/assets/uploads/" . basename($_FILES["image"]["name"]);
            } else {
                $_SESSION['error_message'] = "Lỗi khi upload hình ảnh.";
                header("Location: ../../backend/baiviet/edit_blog_post.php?id=$post_id");
                exit();
            }
        }

        // Cập nhật bài viết
        $sql = "UPDATE blog_posts SET title = ?, excerpt = ?, content = ?, image_url = ?, author = ?, category_id = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssii", $title, $excerpt, $content, $image_url, $author, $category_id, $post_id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Cập nhật bài viết thành công!";
            header("Location: ../../backend/baiviet/blog_posts.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Lỗi khi cập nhật bài viết: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

// Include header
include '../../frontend/pages/footer.php';

?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Sửa bài viết blog</h1>
</div>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Edit Blog Post Form -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Sửa bài viết</h6>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="title" class="form-label">Tiêu đề</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="excerpt" class="form-label">Đoạn trích</label>
                <textarea class="form-control" id="excerpt" name="excerpt" rows="3" required><?php echo htmlspecialchars($post['excerpt']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="content" class="form-label">Nội dung</label>
                <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($post['content']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Hình ảnh minh họa</label>
                <?php if ($post['image_url']): ?>
                    <div class="mb-2">
                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="Current Image" style="max-width: 200px;">
                    </div>
                <?php endif; ?>
                <input type="file" class="form-control" id="image" name="image" accept="image/*">
            </div>
            <div class="mb-3">
                <label for="author" class="form-label">Tác giả</label>
                <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($post['author']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="category_id" class="form-label">Danh mục</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">Chọn danh mục</option>
                    <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $post['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Cập nhật bài viết</button>
            <a href="../../backend/baiviet/blog_posts.php" class="btn btn-secondary">Hủy</a>
        </form>
    </div>
</div>

<?php
// Include footer
include '../../frontend/pages/footer.php';

?>
