<?php
// Kiểm tra xem có phải là admin không
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

// Kiểm tra xem người dùng đã đăng nhập chưa
$is_logged_in = isset($_SESSION['user_id']);

// Xác định gói hiện tại của người dùng
$current_plan_code = isset($current_plan) ? $current_plan['plan_code'] : 'none';

// Lấy thông tin các gói từ cơ sở dữ liệu
if (!isset($plans) || empty($plans)) {
    // Nếu không có kết nối đến cơ sở dữ liệu, sử dụng dữ liệu mẫu
    $plans_sql = "SELECT * FROM plans";
    $plans_result = mysqli_query($conn, $plans_sql);
    $plans = [];
    
    if ($plans_result) {
        while ($row = mysqli_fetch_assoc($plans_result)) {
            $row['features'] = json_decode($row['features'], true);
            $row['code'] = $row['plan_code'];
            $plans[] = $row;
        }
    } else {
        // Dữ liệu mẫu nếu không thể truy vấn database
        $plans = [
            [
                'plan_code' => 'basic',
                'name' => 'Cơ bản',
                'price' => 0,
                'duration' => 30,
                'description' => 'Gói miễn phí cơ bản cho người dùng mới',
                'code' => 'basic',
                'features' => [
                    'search' => true,
                    'view_details' => true,
                    'contact_owner' => true,
                    'save_favorite' => true,
                    'post_room' => false,
                    'featured_post' => false,
                    'priority_support' => false,
                    'free_consultation' => false,
                    'insurance' => false
                ],
                'max_posts' => 0,
                'is_popular' => false
            ],
            [
                'plan_code' => 'standard',
                'name' => 'Phổ biến',
                'price' => 199000,
                'duration' => 30,
                'description' => 'Gói phổ biến cho chủ nhà',
                'code' => 'standard',
                'features' => [
                    'search' => true,
                    'view_details' => true,
                    'contact_owner' => true,
                    'save_favorite' => true,
                    'post_room' => true,
                    'featured_post' => true,
                    'priority_support' => true,
                    'free_consultation' => false,
                    'insurance' => false
                ],
                'max_posts' => 10,
                'is_popular' => true
            ],
            [
                'plan_code' => 'premium',
                'name' => 'Cao cấp',
                'price' => 399000,
                'duration' => 30,
                'description' => 'Gói cao cấp cho chủ nhà chuyên nghiệp',
                'code' => 'premium',
                'features' => [
                    'search' => true,
                    'view_details' => true,
                    'contact_owner' => true,
                    'save_favorite' => true,
                    'post_room' => true,
                    'featured_post' => true,
                    'priority_support' => true,
                    'free_consultation' => true,
                    'insurance' => true
                ],
                'max_posts' => null,
                'is_popular' => false
            ]
        ];
    }
}

// Đảm bảo tất cả các gói đều có key 'code'
foreach ($plans as $key => $plan) {
    if (!isset($plan['code']) && isset($plan['plan_code'])) {
        $plans[$key]['code'] = $plan['plan_code'];
    } elseif (!isset($plan['code'])) {
        $plans[$key]['code'] = 'unknown';
    }
}
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center mb-5">
                <h2 class="fw-bold mb-3">Chọn gói phù hợp với bạn</h2>
                <p class="text-muted">Chúng tôi cung cấp nhiều gói dịch vụ khác nhau để đáp ứng nhu cầu của bạn. Hãy chọn gói phù hợp nhất.</p>
            </div>
        </div>
        
        <div class="row g-4 justify-content-center">
            <?php foreach ($plans as $key => $plan): ?>
                <?php 
                // Đảm bảo plan có key 'code'
                $plan_code = isset($plan['code']) ? $plan['code'] : (isset($plan['plan_code']) ? $plan['plan_code'] : 'unknown');
                $is_popular = $plan_code === 'standard';
                ?>
                <div class="col-md-4">
                    <div class="pricing-card card h-100 <?php echo $is_popular ? 'popular' : ''; ?>">
                        <?php if ($is_popular): ?>
                            <div class="popular-badge">Phổ biến</div>
                        <?php endif; ?>
                        
                        <div class="card-body text-center p-4">
                            <h4 class="card-title fw-bold"><?php echo htmlspecialchars($plan['name']); ?></h4>
                            <p class="text-muted"><?php echo isset($plan['description']) ? htmlspecialchars($plan['description']) : ''; ?></p>
                            
                            <div class="price my-4">
                                <?php if ($plan['price'] > 0): ?>
                                    <?php echo number_format($plan['price'], 0, ',', '.'); ?>đ
                                    <span>/<?php echo $plan['duration']; ?> ngày</span>
                                <?php else: ?>
                                    Miễn phí
                                <?php endif; ?>
                            </div>
                            
                            <ul class="feature-list text-start mb-4">
                                <?php if (isset($plan['features']['search']) && $plan['features']['search']): ?>
                                    <li><i class="fas fa-check text-success"></i> Tìm kiếm phòng trọ</li>
                                <?php endif; ?>
                                
                                <?php if (isset($plan['features']['view_details']) && $plan['features']['view_details']): ?>
                                    <li><i class="fas fa-check text-success"></i> Xem thông tin chi tiết</li>
                                <?php endif; ?>
                                
                                <?php if (isset($plan['features']['contact_owner']) && $plan['features']['contact_owner']): ?>
                                    <li><i class="fas fa-check text-success"></i> Liên hệ chủ nhà trọ</li>
                                <?php endif; ?>
                                
                                <?php if (isset($plan['features']['save_favorite']) && $plan['features']['save_favorite']): ?>
                                    <li><i class="fas fa-check text-success"></i> Lưu phòng trọ yêu thích</li>
                                <?php endif; ?>
                                
                                <?php if (isset($plan['features']['post_room'])): ?>
                                    <?php if ($plan['features']['post_room']): ?>
                                        <li><i class="fas fa-check text-success"></i> Đăng tin phòng trọ 
                                            <?php if (isset($plan['max_posts'])): ?>
                                                <?php if ($plan['max_posts'] === null): ?>
                                                    (Không giới hạn)
                                                <?php else: ?>
                                                    (Tối đa <?php echo $plan['max_posts']; ?> tin)
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </li>
                                    <?php else: ?>
                                        <li><i class="fas fa-times text-danger"></i> Đăng tin phòng trọ</li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (isset($plan['features']['featured_post'])): ?>
                                    <?php if ($plan['features']['featured_post']): ?>
                                        <li><i class="fas fa-check text-success"></i> Tin đăng 
                                            <?php echo $plan_code === 'premium' ? 'được đẩy top ưu tiên' : 'hiển thị ưu tiên'; ?>
                                        </li>
                                    <?php else: ?>
                                        <li><i class="fas fa-times text-danger"></i> Tin đăng được ưu tiên</li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (isset($plan['features']['priority_support'])): ?>
                                    <?php if ($plan['features']['priority_support']): ?>
                                        <li><i class="fas fa-check text-success"></i> Hỗ trợ ưu tiên 24/7</li>
                                    <?php else: ?>
                                        <li><i class="fas fa-times text-danger"></i> Hỗ trợ ưu tiên</li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (isset($plan['features']['free_consultation']) && $plan['features']['free_consultation']): ?>
                                    <li><i class="fas fa-check text-success"></i> Tư vấn miễn phí</li>
                                <?php endif; ?>
                                
                                <?php if (isset($plan['features']['insurance']) && $plan['features']['insurance']): ?>
                                    <li><i class="fas fa-check text-success"></i> Bảo hiểm thuê phòng</li>
                                <?php endif; ?>
                            </ul>
                            
                            <?php
                            // Kiểm tra xem người dùng có đang sử dụng gói này không
                            $is_current_plan = $is_logged_in && $current_plan_code === $plan_code;
                            ?>
                            
                            <?php if ($is_logged_in): ?>
                                <?php if ($is_current_plan): ?>
                                    <button class="btn btn-outline-success w-100" disabled>
                                        <i class="fas fa-check-circle"></i> Gói hiện tại
                                    </button>
                                <?php elseif ($plan_code === 'basic'): ?>
                                    <button class="btn btn-outline-secondary w-100" disabled>
                                        <i class="fas fa-info-circle"></i> Gói mặc định
                                    </button>
                                <?php else: ?>
                                    <a href="#" class="btn btn-primary w-100 plan-select-btn" 
                                       data-plan="<?php echo htmlspecialchars($plan['name']); ?>" 
                                       data-price="<?php echo $plan['price']; ?>">
                                        <i class="fas fa-shopping-cart"></i> Đăng ký ngay
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="/frontend/auth/login.php?redirect=banggia.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-sign-in-alt"></i> Đăng nhập để đăng ký
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="row mt-4">
            <div class="col-12 text-center">
                <p class="text-muted">Tất cả các gói đều bao gồm hỗ trợ kỹ thuật và cập nhật thường xuyên.</p>
                <?php if (!$is_logged_in): ?>
                    <p><a href="/frontend/auth/login.php" class="text-primary">Đăng nhập</a> hoặc <a href="/frontend/auth/register.php" class="text-primary">Đăng ký</a> để bắt đầu sử dụng dịch vụ.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>