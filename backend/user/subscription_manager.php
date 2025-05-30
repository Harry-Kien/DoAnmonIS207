<?php
class SubscriptionManager {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Lấy thông tin gói dịch vụ hiện tại của người dùng
     */
    public function getUserSubscription($user_id) {
        $sql = "SELECT us.*, p.name as plan_name, p.plan_code, p.features, p.max_posts, p.price, p.duration 
                FROM user_subscriptions us
                JOIN plans p ON us.plan_id = p.id
                WHERE us.user_id = ? 
                AND us.is_active = 1 
                AND us.end_date > NOW()
                ORDER BY us.end_date DESC 
                LIMIT 1";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $subscription = mysqli_fetch_assoc($result);
            $subscription['features'] = json_decode($subscription['features'], true);
            return $subscription;
        }
        
        // Nếu không có gói đang hoạt động, trả về gói cơ bản
        return $this->getBasicPlan($user_id);
    }
    
    /**
     * Lấy thông tin gói "Cơ bản" và tạo nếu cần
     */
    public function getBasicPlan($user_id = null) {
        $sql = "SELECT * FROM plans WHERE plan_code = 'basic'";
        $result = mysqli_query($this->conn, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            $plan = mysqli_fetch_assoc($result);
            $features = json_decode($plan['features'], true);
            
            // Nếu có user_id và chưa có gói, tạo gói "Cơ bản"
            if ($user_id) {
                $start_date = date('Y-m-d H:i:s');
                $end_date = date('Y-m-d H:i:s', strtotime("+{$plan['duration']} days"));
                
                $insert_sql = "INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, is_active, created_at, updated_at) 
                               VALUES (?, ?, ?, ?, 1, NOW(), NOW())";
                $stmt = mysqli_prepare($this->conn, $insert_sql);
                mysqli_stmt_bind_param($stmt, "iiss", $user_id, $plan['id'], $start_date, $end_date);
                mysqli_stmt_execute($stmt);
                
                $subscription_id = mysqli_insert_id($this->conn);
                
                return [
                    'id' => $subscription_id,
                    'user_id' => $user_id,
                    'plan_id' => $plan['id'],
                    'plan_name' => $plan['name'],
                    'plan_code' => $plan['plan_code'],
                    'max_posts' => $plan['max_posts'],
                    'features' => $features,
                    'price' => $plan['price'],
                    'duration' => $plan['duration'],
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'is_active' => 1
                ];
            }
            
            return [
                'id' => null,
                'user_id' => null,
                'plan_id' => $plan['id'],
                'plan_name' => $plan['name'],
                'plan_code' => $plan['plan_code'],
                'max_posts' => $plan['max_posts'],
                'features' => $features,
                'price' => $plan['price'],
                'duration' => $plan['duration'],
                'start_date' => null,
                'end_date' => null,
                'is_active' => 1
            ];
        }
        
        // Fallback nếu không tìm thấy gói "Cơ bản"
        $features = [
            'search' => true,
            'view_details' => true,
            'contact_owner' => true,
            'save_favorite' => true,
            'post_room' => false,
            'featured_post' => false,
            'priority_support' => false,
            'free_consultation' => false,
            'insurance' => false
        ];
        
        return [
            'id' => null,
            'user_id' => null,
            'plan_id' => 1,
            'plan_name' => 'Cơ bản',
            'plan_code' => 'basic',
            'max_posts' => 0,
            'features' => $features,
            'price' => 0,
            'duration' => 30,
            'start_date' => null,
            'end_date' => null,
            'is_active' => 1
        ];
    }
    
    /**
     * Lấy tất cả các gói dịch vụ
     */
    public function getAllPlans() {
        $sql = "SELECT * FROM plans ORDER BY price ASC";
        $result = mysqli_query($this->conn, $sql);
        $plans = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $row['features'] = json_decode($row['features'], true);
            $plans[] = $row;
        }
        
        return $plans;
    }
    
    /**
     * Kiểm tra xem người dùng có thể đăng tin mới không
     */
    public function canPostNewRoom($user_id) {
        $subscription = $this->getUserSubscription($user_id);
        
        // Nếu là gói "Cơ bản", không được đăng tin
        if ($subscription['plan_code'] === 'basic') {
            return [
                'can_post' => false,
                'message' => 'Bạn cần nâng cấp lên gói Tiêu chuẩn hoặc Cao cấp để đăng tin.',
                'remaining_posts' => 0
            ];
        }
        
        // Nếu là gói không giới hạn
        if ($subscription['max_posts'] === null) {
            return [
                'can_post' => true,
                'message' => 'Bạn có thể đăng không giới hạn tin.',
                'remaining_posts' => 'Không giới hạn'
            ];
        }
        
        // Kiểm tra số lượng tin đã đăng
        $sql = "SELECT COUNT(*) as post_count FROM rooms WHERE user_id = ? AND created_at >= ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $user_id, $subscription['start_date']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        $posted_count = $row['post_count'];
        $remaining = $subscription['max_posts'] - $posted_count;
        
        if ($remaining <= 0) {
            return [
                'can_post' => false,
                'message' => 'Bạn đã sử dụng hết số lượng tin đăng trong gói dịch vụ.',
                'remaining_posts' => 0
            ];
        }
        
        return [
            'can_post' => true,
            'message' => 'Bạn còn ' . $remaining . ' lượt đăng tin.',
            'remaining_posts' => $remaining
        ];
    }
    
    /**
     * Cập nhật trạng thái tin đăng theo gói dịch vụ
     */
    public function updateRoomPriority($room_id, $user_id) {
        $subscription = $this->getUserSubscription($user_id);
        
        $is_premium = 0;
        $is_featured = 0;
        $priority_until = null;
        
        if ($subscription['plan_code'] === 'standard') {
            $is_premium = 1;
            $priority_until = date('Y-m-d H:i:s', strtotime($subscription['end_date']));
        } elseif ($subscription['plan_code'] === 'premium') {
            $is_premium = 1;
            $is_featured = 1;
            $priority_until = date('Y-m-d H:i:s', strtotime($subscription['end_date']));
        }
        
        $sql = "UPDATE rooms SET is_premium = ?, is_featured = ?, priority_until = ? WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "iisii", $is_premium, $is_featured, $priority_until, $room_id, $user_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Tạo đăng ký gói dịch vụ mới
     */
    public function createSubscription($user_id, $plan_code, $payment_id = null, $expires_at) {
        // Lấy thông tin gói dịch vụ
        $sql = "SELECT * FROM plans WHERE plan_code = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $plan_code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 0) {
            return false;
        }
        
        $plan = mysqli_fetch_assoc($result);
        $plan_id = $plan['id'];
        
        // Kiểm tra gói hiện tại
        $current = $this->getUserSubscription($user_id);
        $start_date = date('Y-m-d H:i:s');
        
        // Vô hiệu hóa gói cũ
        if ($current['id'] !== null) {
            $update_sql = "UPDATE user_subscriptions SET is_active = 0, updated_at = NOW() WHERE id = ?";
            $update_stmt = mysqli_prepare($this->conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "i", $current['id']);
            mysqli_stmt_execute($update_stmt);
        }
        
        // Tạo gói mới
        $insert_sql = "INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, is_active, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, 1, NOW(), NOW())";
        $insert_stmt = mysqli_prepare($this->conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "iiss", $user_id, $plan_id, $start_date, $expires_at);
        
        if (!mysqli_stmt_execute($insert_stmt)) {
            return false;
        }
        
        $subscription_id = mysqli_insert_id($this->conn);
        
        // Cập nhật thanh toán nếu có
        if ($payment_id) {
            $update_payment_sql = "UPDATE payments SET subscription_id = ?, status = 'completed', updated_at = NOW() WHERE id = ?";
            $update_payment_stmt = mysqli_prepare($this->conn, $update_payment_sql);
            mysqli_stmt_bind_param($update_payment_stmt, "ii", $subscription_id, $payment_id);
            mysqli_stmt_execute($update_payment_stmt);
        }
        
        return $subscription_id;
    }

    /**
     * Lấy thông tin gói dịch vụ trước đó của người dùng nếu còn hạn
     */
    public function getPreviousValidSubscription($user_id) {
        $sql = "SELECT us.*, p.name as plan_name, p.plan_code as plan_code, p.price, p.duration, p.features, p.max_posts
                FROM user_subscriptions us
                JOIN plans p ON us.plan_id = p.id
                WHERE us.user_id = ? 
                AND us.is_active = 0 
                AND us.end_date > NOW()
                AND p.plan_code != 'basic'
                ORDER BY us.end_date DESC 
                LIMIT 1";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $subscription = mysqli_fetch_assoc($result);
            $subscription['features'] = json_decode($subscription['features'], true);
            return $subscription;
        }
        
        return null;
    }

    /**
     * Kích hoạt lại gói dịch vụ trước đó
     */
    public function reactivatePreviousSubscription($user_id, $subscription_id) {
        mysqli_begin_transaction($this->conn);
        
        try {
            // Vô hiệu hóa gói hiện tại
            $deactivate_sql = "UPDATE user_subscriptions 
                             SET is_active = 0, updated_at = NOW() 
                             WHERE user_id = ? AND is_active = 1";
            $stmt = mysqli_prepare($this->conn, $deactivate_sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            
            // Kích hoạt lại gói cũ
            $reactivate_sql = "UPDATE user_subscriptions 
                             SET is_active = 1, updated_at = NOW() 
                             WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($this->conn, $reactivate_sql);
            mysqli_stmt_bind_param($stmt, "ii", $subscription_id, $user_id);
            $result = mysqli_stmt_execute($stmt);
            
            if (!$result) {
                throw new Exception("Không thể kích hoạt lại gói dịch vụ");
            }
            
            mysqli_commit($this->conn);
            return true;
        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            throw $e;
        }
    }

    /**
     * Kiểm tra quyền truy cập tính năng của người dùng
     */
    public function checkFeatureAccess($user_id, $feature) {
        // Lấy thông tin gói hiện tại của người dùng
        $subscription = $this->getUserSubscription($user_id);
        
        if (!$subscription) {
            // Nếu không có gói, trả về gói cơ bản
            $subscription = $this->getBasicPlan($user_id);
        }

        // Kiểm tra xem gói có hết hạn chưa
        if (isset($subscription['end_date']) && strtotime($subscription['end_date']) < time()) {
            // Nếu hết hạn, trả về gói cơ bản
            return $this->getBasicPlan($user_id)['features'][$feature] ?? false;
        }

        // Kiểm tra tính năng theo gói
        if ($subscription['plan_code'] === 'premium') {
            // Gói cao cấp có tất cả tính năng
            return true;
        }

        if ($subscription['plan_code'] === 'standard') {
            // Gói phổ biến có các tính năng cơ bản + đăng tin và hỗ trợ ưu tiên
            $allowed_features = [
                'search' => true,
                'view_details' => true,
                'contact_owner' => true,
                'save_favorite' => true,
                'post_room' => true,
                'priority_support' => true
            ];
            return isset($allowed_features[$feature]) && $allowed_features[$feature];
        }

        // Gói cơ bản chỉ có các tính năng cơ bản
        $basic_features = [
            'search' => true,
            'view_details' => true,
            'contact_owner' => true,
            'save_favorite' => true
        ];
        return isset($basic_features[$feature]) && $basic_features[$feature];
    }

    /**
     * Cập nhật tính năng cho các gói
     */
    public function updatePlanFeatures($plan_code) {
        $features = [];
        
        // Tính năng cơ bản cho tất cả các gói
        $basic_features = [
            'search' => true,
            'view_details' => true,
            'contact_owner' => true,
            'save_favorite' => true
        ];
        
        switch ($plan_code) {
            case 'premium':
                // Gói cao cấp có tất cả tính năng
                $features = [
                    'search' => true,
                    'view_details' => true,
                    'contact_owner' => true,
                    'save_favorite' => true,
                    'post_room' => true,
                    'featured_post' => true,
                    'priority_support' => true,
                    'free_consultation' => true,
                    'insurance' => true
                ];
                break;
                
            case 'standard':
                // Gói phổ biến có thêm đăng tin và hỗ trợ ưu tiên
                $features = array_merge($basic_features, [
                    'post_room' => true,
                    'priority_support' => true
                ]);
                break;
                
            case 'basic':
            default:
                $features = $basic_features;
                break;
        }
        
        // Cập nhật vào database
        $features_json = json_encode($features);
        $sql = "UPDATE plans SET features = ? WHERE plan_code = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $features_json, $plan_code);
        return mysqli_stmt_execute($stmt);
    }

    /**
     * Kiểm tra xem người dùng có thể sử dụng tính năng không
     */
    public function canUseFeature($user_id, $feature) {
        $subscription = $this->getUserSubscription($user_id);
        
        // Kiểm tra trạng thái subscription
        if (!$subscription || !$subscription['is_active']) {
            return false;
        }
        
        // Kiểm tra thời hạn
        if (strtotime($subscription['end_date']) < time()) {
            return false;
        }
        
        // Kiểm tra quyền truy cập tính năng
        return $this->checkFeatureAccess($user_id, $feature);
    }

    /**
     * Lấy số lượng tin đăng còn lại của người dùng
     */
    public function getRemainingPosts($user_id) {
        $subscription = $this->getUserSubscription($user_id);
        
        // Nếu không có gói hoặc gói hết hạn
        if (!$subscription || strtotime($subscription['end_date']) < time()) {
            return 0;
        }
        
        // Nếu là gói không giới hạn
        if ($subscription['max_posts'] === null) {
            return -1; // -1 đại diện cho không giới hạn
        }
        
        // Đếm số tin đã đăng trong thời gian subscription
        $sql = "SELECT COUNT(*) as posted_count 
                FROM rooms 
                WHERE user_id = ? 
                AND created_at >= ?";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $user_id, $subscription['start_date']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        return max(0, $subscription['max_posts'] - $row['posted_count']);
    }
}
?>