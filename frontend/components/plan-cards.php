<?php
// Lấy danh sách gói từ cơ sở dữ liệu
$plans_sql = "SELECT * FROM plans";
$plans_result = mysqli_query($conn, $plans_sql);
$plans = [];
while ($row = mysqli_fetch_assoc($plans_result)) {
    $row['features'] = json_decode($row['features'], true);
    $row['code'] = $row['plan_code'];
    $plans[] = $row;
}

// Kiểm tra trạng thái đăng nhập và gói hiện tại
$user_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$current_plan = null;
$previous_plan = null;

if ($user_logged_in && isset($_SESSION['user_id'])) {
    require_once(__DIR__ . '/../../backend/user/subscription_manager.php');
    $subscriptionManager = new SubscriptionManager($conn);
    $current_plan = $subscriptionManager->getUserSubscription($_SESSION['user_id']);
    
    // Nếu người dùng đang dùng gói cơ bản, kiểm tra xem có gói cũ còn hạn không
    if ($current_plan['plan_code'] === 'basic') {
        $previous_plan = $subscriptionManager->getPreviousValidSubscription($_SESSION['user_id']);
    }
}
?>

<section class="py-5">
    <div class="container">


        <div class="row g-4">
            <?php foreach ($plans as $key => $plan): ?>
                <div class="col-md-4">
                    <div class="card pricing-card h-100 <?php echo ($plan['code'] === 'standard') ? 'popular' : ''; ?>">
                        <?php if ($plan['code'] === 'standard'): ?>
                            <div class="popular-badge">Phổ biến</div>
                        <?php endif; ?>
                        <div class="card-body text-center p-4">
                            <h4 class="card-title"><?php echo htmlspecialchars($plan['name']); ?></h4>
                            <div class="price my-4">
                                <?php if ($plan['price'] > 0): ?>
                                    <?php echo number_format($plan['price']); ?>đ
                                <?php else: ?>
                                    Miễn phí
                                <?php endif; ?>
                                <span>/<?php echo $plan['duration']; ?> ngày</span>
                            </div>
                            <ul class="feature-list text-start mb-4">
                                <?php if ($plan['features']['search']): ?>
                                    <li><i class="fas fa-check text-success"></i> Tìm kiếm phòng trọ</li>
                                <?php endif; ?>
                                
                                <?php if ($plan['features']['view_details']): ?>
                                    <li><i class="fas fa-check text-success"></i> Xem thông tin chi tiết</li>
                                <?php endif; ?>
                                
                                <?php if ($plan['features']['contact_owner']): ?>
                                    <li><i class="fas fa-check text-success"></i> Liên hệ chủ nhà trọ</li>
                                <?php endif; ?>
                                
                                <?php if ($plan['features']['save_favorite']): ?>
                                    <li><i class="fas fa-check text-success"></i> Lưu phòng trọ yêu thích</li>
                                <?php endif; ?>
                                
                                <?php if (isset($plan['features']['post_room'])): ?>
                                    <?php if ($plan['features']['post_room'] || $plan['code'] === 'standard' || $plan['code'] === 'premium'): ?>
                                        <li><i class="fas fa-check text-success"></i> Đăng tin phòng trọ 
                                            <?php if ($plan['max_posts']): ?>
                                                (Tối đa <?php echo $plan['max_posts']; ?> tin)
                                            <?php else: ?>
                                                (Không giới hạn)
                                            <?php endif; ?>
                                        </li>
                                    <?php else: ?>
                                        <li><i class="fas fa-times text-danger"></i> Đăng tin phòng trọ</li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (isset($plan['features']['featured_post'])): ?>
                                    <?php if ($plan['features']['featured_post'] || $plan['code'] === 'premium'): ?>
                                        <li><i class="fas fa-check text-success"></i> Tin đăng được ưu tiên hiển thị</li>
                                    <?php else: ?>
                                        <li><i class="fas fa-times text-danger"></i> Tin đăng được ưu tiên</li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (isset($plan['features']['priority_support'])): ?>
                                    <?php if ($plan['features']['priority_support'] || $plan['code'] === 'standard' || $plan['code'] === 'premium'): ?>
                                        <li><i class="fas fa-check text-success"></i> Hỗ trợ khách hàng ưu tiên 24/7</li>
                                    <?php else: ?>
                                        <li><i class="fas fa-times text-danger"></i> Hỗ trợ khách hàng ưu tiên</li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (isset($plan['features']['free_consultation'])): ?>
                                    <?php if ($plan['features']['free_consultation'] || $plan['code'] === 'premium'): ?>
                                        <li><i class="fas fa-check text-success"></i> Tư vấn miễn phí</li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (isset($plan['features']['insurance'])): ?>
                                    <?php if ($plan['features']['insurance'] || $plan['code'] === 'premium'): ?>
                                        <li><i class="fas fa-check text-success"></i> Bảo hiểm thuê phòng</li>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </ul>

                            <?php
                            $plan_code = $plan['code'];
                            $plan_name_display = [
                                'basic' => 'Cơ bản',
                                'standard' => 'Tiêu chuẩn',
                                'premium' => 'Cao cấp'
                            ][$plan_code];

                            // Kiểm tra xem người dùng có đang sử dụng gói này không
                            $is_current_plan = $user_logged_in && $current_plan && $current_plan['plan_code'] === $plan_code;
                            
                            // Kiểm tra xem gói có phải là gói cao hơn không
                            $is_upgrade = $user_logged_in && $current_plan && 
                                        (($current_plan['plan_code'] === 'basic' && ($plan_code === 'standard' || $plan_code === 'premium')) ||
                                         ($current_plan['plan_code'] === 'standard' && $plan_code === 'premium'));

                            // Kiểm tra xem đây có phải là gói trước đó còn hạn không
                            $is_previous_plan = $previous_plan && $previous_plan['plan_code'] === $plan_code;

                            // Kiểm tra xem người dùng có đang sử dụng gói trả phí không
                            $has_paid_plan = $user_logged_in && $current_plan && 
                                           ($current_plan['plan_code'] === 'standard' || $current_plan['plan_code'] === 'premium');
                            ?>

                            <?php if ($user_logged_in): ?>
                                <?php if ($is_current_plan): ?>
                                    <div class="d-flex flex-column gap-2">
                                        <button class="btn btn-outline-success w-100" disabled>
                                            <i class="fas fa-check-circle me-2"></i>Gói hiện tại của bạn
                                        </button>
                                        <?php if ($current_plan['end_date']): ?>
                                            <small class="text-muted">
                                                Hết hạn: <?php echo date('d/m/Y', strtotime($current_plan['end_date'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($is_previous_plan): ?>
                                    <a href="../../backend/payment/reactivate_subscription.php?subscription_id=<?php echo $previous_plan['id']; ?>" 
                                       class="btn btn-success w-100">
                                        <i class="fas fa-sync-alt me-2"></i>Kích hoạt lại gói này
                                    </a>
                                <?php elseif ($is_upgrade): ?>
                                    <a href="#" 
                                       class="btn btn-<?php echo $plan_code === 'standard' ? 'warning' : 'primary'; ?> w-100 plan-select-btn"
                                       data-plan="<?php echo $plan_name_display; ?>" 
                                       data-price="<?php echo $plan['price']; ?>">
                                        <i class="fas fa-arrow-up me-2"></i>Nâng cấp lên gói <?php echo $plan_name_display; ?>
                                    </a>
                                <?php elseif ($plan_code === 'basic' && !$has_paid_plan): ?>
                                    <button class="btn btn-outline-secondary w-100" disabled>
                                        <i class="fas fa-check me-2"></i>Gói miễn phí
                                    </button>
                                <?php elseif ($plan_code !== 'basic'): ?>
                                    <a href="#" 
                                       class="btn btn-<?php echo $plan_code === 'standard' ? 'warning' : 'primary'; ?> w-100 plan-select-btn"
                                       data-plan="<?php echo $plan_name_display; ?>" 
                                       data-price="<?php echo $plan['price']; ?>">
                                        Đăng ký gói <?php echo $plan_name_display; ?>
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="../../frontend/auth/login.php?redirect=banggia.php<?php echo $plan_code !== 'basic' ? "&plan=$plan_code" : ''; ?>"
                                   class="btn btn-outline-<?php echo $plan_code === 'standard' ? 'warning' : ($plan_code === 'premium' ? 'primary' : 'secondary'); ?> w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập để đăng ký gói
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div> 
</section>