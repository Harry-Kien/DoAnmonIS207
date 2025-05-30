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
                                <?php if ($plan['features']['search']) echo "<li><i class='fas fa-check'></i> Tìm kiếm phòng trọ</li>"; ?>
                                <?php if ($plan['features']['view_details']) echo "<li><i class='fas fa-check'></i> Xem thông tin chi tiết</li>"; ?>
                                <?php if ($plan['features']['contact_owner']) echo "<li><i class='fas fa-check'></i> Liên hệ chủ nhà trọ</li>"; ?>
                                <?php if ($plan['features']['save_favorite']) echo "<li><i class='fas fa-check'></i> Lưu phòng trọ yêu thích</li>"; ?>
                                <?php if ($plan['features']['post_room']): ?>
                                    <li><i class="fas fa-check"></i> Đăng tối đa 
                                        <?php echo $plan['max_posts'] === null ? 'không giới hạn' : $plan['max_posts']; ?> tin
                                    </li>
                                <?php else: ?>
                                    <li class="text-muted"><i class="fas fa-times text-danger"></i> Đăng tin phòng trọ</li>
                                <?php endif; ?>
                                <?php if ($plan['features']['featured_post']): ?>
                                    <li><i class="fas fa-check"></i> Tin đăng 
                                        <?php echo $plan['code'] === 'premium' ? 'được đẩy top ưu tiên' : 'hiển thị ưu tiên'; ?>
                                    </li>
                                <?php else: ?>
                                    <li class="text-muted"><i class="fas fa-times text-danger"></i> Tin đăng được đẩy top</li>
                                <?php endif; ?>
                                <?php if ($plan['features']['priority_support']): ?>
                                    <li><i class="fas fa-check"></i> Hỗ trợ ưu tiên 24/7</li>
                                <?php else: ?>
                                    <li class="text-muted"><i class="fas fa-times text-danger"></i> Hỗ trợ ưu tiên</li>
                                <?php endif; ?>
                                <?php if ($plan['features']['free_consultation']) echo "<li><i class='fas fa-check'></i> Tư vấn miễn phí</li>"; ?>
                                <?php if ($plan['features']['insurance']) echo "<li><i class='fas fa-check'></i> Bảo hiểm thuê phòng</li>"; ?>
                            </ul>

                            <?php
                            $plan_code = $plan['code'];
                            $user_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
                            $user_has_plan = isset($current_plan['plan_code']);
                            $is_current = $user_has_plan && $current_plan['plan_code'] === $plan_code;
                            $plan_name_display = [
                                'basic' => 'Cơ bản',
                                'standard' => 'Tiêu chuẩn',
                                'premium' => 'Cao cấp'
                            ][$plan_code];
                            ?>

                            <?php if ($user_logged_in): ?>
                                <?php if ($user_has_plan): ?>
                                    <?php if ($is_current): ?>
                                        <button class="btn btn-outline-<?php echo $plan_code === 'standard' ? 'warning' : ($plan_code === 'premium' ? 'primary' : 'warning'); ?> w-100" disabled>Gói Phổ Biến</button>
                                    <?php elseif ($plan_code !== 'basic'): ?>
                                        <a href="#"
                                           class="btn btn-<?php echo $plan_code === 'standard' ? 'warning' : 'primary'; ?> w-100 plan-select-btn"
                                           data-plan="<?php echo $plan_name_display; ?>" data-price="<?php echo $plan['price']; ?>">
                                           Chọn gói <?php echo $plan_name_display; ?>
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="#" class="btn btn-outline-secondary w-100 disabled" disabled>Bạn chưa chọn gói</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="login.php?redirect=banggia.php<?php echo $plan_code !== 'basic' ? "&plan=$plan_code" : ''; ?>"
                                   class="btn btn-outline-<?php echo $plan_code === 'standard' ? 'warning' : ($plan_code === 'premium' ? 'primary' : 'warning'); ?> w-100">
                                   Đăng nhập để đăng ký
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div> 
</section>