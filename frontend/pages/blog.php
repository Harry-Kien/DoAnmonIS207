<?php
// Khởi động phiên làm việc
session_start();

// Bao gồm file cấu hình kết nối CSDL
require_once __DIR__ . '/../../backend/config/config.php';

// Tùy chỉnh tiêu đề trang
$page_title = "Phòng trọ - Homeseeker";

// Lấy danh sách bài viết từ cơ sở dữ liệu
$posts_query = "SELECT bp.id, bp.title, bp.excerpt, bp.image_url, bp.author, bp.created_at, bp.comments_count, bc.name AS category_name
                FROM blog_posts bp
                LEFT JOIN blog_categories bc ON bp.category_id = bc.id
                ORDER BY bp.created_at DESC
                LIMIT 3"; // Giới hạn 3 bài để hiển thị trên trang đầu
$posts_result = mysqli_query($conn, $posts_query);

// Lấy danh sách bài viết gần đây
$recent_posts_query = "SELECT id, title, created_at, image_url
                       FROM blog_posts
                       ORDER BY created_at DESC
                       LIMIT 4"; // Giới hạn 4 bài gần đây
$recent_posts_result = mysqli_query($conn, $recent_posts_query);

// Lấy danh sách danh mục và số bài viết
$categories_query = "SELECT name, post_count
                     FROM blog_categories
                     ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);

// Lấy danh sách phòng trọ mới đăng, không cần lấy ảnh
$rooms_query = "SELECT id, title, price
                FROM rooms
                WHERE status = 'approved'
                ORDER BY created_at DESC
                LIMIT 4"; // Giới hạn 4 phòng
$rooms_result = mysqli_query($conn, $rooms_query);

// Include header
include './header.php';
?>

<section class="page-title" style="position: relative; background-color: rgba(0,0,0,0.6); color: white; padding: 80px 0; text-align: center; background-image: url('../../frontend/assets/images/anhbanner.jpg'); background-size: cover; background-position: center;">
    <div class="container position-relative z-1">
        <h1 class="display-4 mb-3">Blog</h1>
        <p class="lead" style="color: rgba(255,255,255,0.7);">Cẩm nang phòng trọ - Tất cả kinh nghiệm, mẹo vặt và thông tin hữu ích</p>
    </div>
</section>

<!-- Nội dung Blog -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Nội dung chính -->
            <div class="col-lg-8">
                <?php if (mysqli_num_rows($posts_result) > 0): ?>
                    <?php while ($post = mysqli_fetch_assoc($posts_result)): ?>
                        <!-- Bài viết Blog -->
                        <div class="card blog-card">
                            <img src="<?php echo htmlspecialchars($post['image_url'] ?? '../assets/images/placeholder/800x400.jpg'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($post['title']); ?>">
                            <div class="card-body p-4">
                                <div class="blog-meta">
                                    <span><i class="far fa-user"></i> <?php echo htmlspecialchars($post['author']); ?></span>
                                    <span><i class="far fa-calendar"></i> <?php echo date('d/m/Y', strtotime($post['created_at'])); ?></span>
                                    <span><i class="far fa-comments"></i> <?php echo $post['comments_count']; ?> Bình luận</span>
                                </div>
                                <h3 class="blog-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                <p class="blog-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                                <a href="./blog_detail.php?id=<?php echo $post['id']; ?>" class="read-more">Đọc thêm</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center">Chưa có bài viết nào.</p>
                <?php endif; ?>

                <!-- Phân trang (tạm thời giữ tĩnh, có thể làm động sau) -->
                <nav aria-label="Phân trang Blog">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" aria-label="Trang trước">
                                <span aria-hidden="true">«</span>
                            </a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#" aria-label="Trang sau">
                                <span aria-hidden="true">»</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Thanh bên -->
            <div class="col-lg-4">
                <!-- Tìm kiếm -->
                <div class="blog-sidebar">
                    <form action="./blog_search.php" method="GET">
                        <div class="input-group">
                            <input type="text" class="form-control" name="keyword" placeholder="Tìm kiếm bài viết...">
                            <button class="btn btn-warning" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Bài viết gần đây -->
                <div class="blog-sidebar">
                    <h5 class="sidebar-title">Bài viết gần đây</h5>
                    <?php while ($recent_post = mysqli_fetch_assoc($recent_posts_result)): ?>
                        <!-- Bài viết gần đây -->
                        <div class="recent-post">
                            <img src="<?php echo htmlspecialchars($recent_post['image_url'] ?? '../assets/images/placeholder/80x80.jpg'); ?>" alt="<?php echo htmlspecialchars($recent_post['title']); ?>">
                            <div class="recent-post-content">
                                <h6 class="recent-post-title">
                                    <a href="./blog_detail.php?id=<?php echo $recent_post['id']; ?>"><?php echo htmlspecialchars($recent_post['title']); ?></a>
                                </h6>
                                <div class="recent-post-date">
                                    <i class="far fa-calendar-alt me-1"></i> <?php echo date('d/m/Y', strtotime($recent_post['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Phòng trọ nổi bật -->
                <div class="blog-sidebar">
                    <h5 class="sidebar-title">Trọ mới đăng</h5>
                    <?php while ($room = mysqli_fetch_assoc($rooms_result)): ?>
                        <!-- Phòng trọ -->
                        <div class="room-item">
                            <div class="room-item-content">
                                <h6 class="room-item-title">
                                    <a href="../room/chi-tiet-phong.php?id=<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['title']); ?></a>
                                </h6>
                                <div class="room-price"><?php echo number_format($room['price']); ?> đ/tháng</div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Danh mục -->
                <div class="blog-sidebar">
                    <h5 class="sidebar-title">Danh mục</h5>
                    <ul class="list-group list-group-flush">
                        <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="./blog_category.php?name=<?php echo urlencode($category['name']); ?>" class="text-dark text-decoration-none"><?php echo htmlspecialchars($category['name']); ?></a>
                                <span class="badge bg-warning rounded-pill"><?php echo $category['post_count']; ?></span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Phần lợi ích -->
<section class="benefits-section">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="benefit-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <h4>Uy tín cao</h4>
                <p class="text-muted">Homeseeker - Top thị trường về nền tảng tìm phòng trọ uy tín</p>
            </div>
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="benefit-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h4>Bảo hành đảm bảo</h4>
                <p class="text-muted">Cam kết phòng trọ chất lượng, đúng như thông tin đăng tải</p>
            </div>
            <div class="col-md-4">
                <div class="benefit-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h4>Hỗ trợ 24/7</h4>
                <p class="text-muted">Đội ngũ nhân viên hỗ trợ tận tâm, sẵn sàng phục vụ mọi lúc</p>
            </div>
        </div>
    </div>
</section>

<?php
// Đóng kết nối CSDL
mysqli_close($conn);

// Include footer
include './footer.php';
?>