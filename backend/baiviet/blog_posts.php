<?php
session_start();
require_once __DIR__ . "/../config/config.php";

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

$page_title = "Quản lý bài viết blog";

// Xử lý xóa bài viết
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Lấy category_id trước khi xóa
    $sql_get_category = "SELECT category_id FROM blog_posts WHERE id = ?";
    $stmt_get_category = $conn->prepare($sql_get_category);
    $stmt_get_category->bind_param("i", $id);
    $stmt_get_category->execute();
    $result = $stmt_get_category->get_result();
    $category = $result->fetch_assoc();
    $category_id = $category['category_id'] ?? 0;
    $stmt_get_category->close();

    // Xóa bài viết
    $sql_delete = "DELETE FROM blog_posts WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id);
    if ($stmt_delete->execute()) {
        // Cập nhật số lượng bài viết trong danh mục
        if ($category_id) {
            $sql_update_category = "UPDATE blog_categories SET post_count = post_count - 1 WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update_category);
            $stmt_update->bind_param("i", $category_id);
            $stmt_update->execute();
            $stmt_update->close();
        }
        $_SESSION['success_message'] = "Xóa bài viết thành công!";
    } else {
        $_SESSION['error_message'] = "Lỗi khi xóa bài viết: " . $stmt_delete->error;
    }
    $stmt_delete->close();
    header("Location: ../../backend/baiviet/blog_posts.php");
    exit();
}

// Lấy danh sách bài viết
$posts_query = "SELECT bp.id, bp.title, bp.author, bp.created_at, bp.comments_count, bc.name AS category_name
                FROM blog_posts bp
                LEFT JOIN blog_categories bc ON bp.category_id = bc.id
                ORDER BY bp.created_at DESC";
$posts_result = mysqli_query($conn, $posts_query);

// Include header
include '../../frontend/pages/header.php';
?>

<!-- Tiêu đề trang -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Quản lý bài viết blog</h1>
    <a href="../../backend/baiviet/add_blog_post.php" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm bài viết</a>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Danh sách bài viết -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Danh sách bài viết</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered datatable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tiêu đề</th>
                        <th>Tác giả</th>
                        <th>Danh mục</th>
                        <th>Ngày đăng</th>
                        <th>Bình luận</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($posts_result) > 0): ?>
                        <?php while($post = mysqli_fetch_assoc($posts_result)): ?>
                            <tr>
                                <td><?php echo $post['id']; ?></td>
                                <td><?php echo htmlspecialchars($post['title']); ?></td>
                                <td><?php echo htmlspecialchars($post['author']); ?></td>
                                <td><?php echo htmlspecialchars($post['category_name'] ?? 'Không có danh mục'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($post['created_at'])); ?></td>
                                <td><?php echo $post['comments_count']; ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="../../backend/baiviet/edit_blog_post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-info" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="../../backend/baiviet/blog_posts.php?action=delete&id=<?php echo $post['id']; ?>" class="btn btn-sm btn-danger" title="Xóa" onclick="return confirm('Bạn có chắc muốn xóa bài viết này?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">Không có bài viết nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Include footer
include '../../frontend/pages/footer.php';
?>