<?php
session_start();
require_once '../../backend/config/config.php';

// Kiểm tra xem có phải là yêu cầu POST không
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

// Nhận dữ liệu JSON từ yêu cầu
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['payment_code'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$payment_code = $input['payment_code'];

// Kiểm tra trạng thái thanh toán trong cơ sở dữ liệu
$check_sql = "SELECT status FROM payments WHERE payment_code = ?";
$stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($stmt, "s", $payment_code);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$payment = mysqli_fetch_assoc($result);

if ($payment) {
    // Kiểm tra với cổng thanh toán (thay thế bằng API thực tế)
    $payment_status = checkPaymentWithGateway($payment_code);
    
    if ($payment_status === 'completed' && $payment['status'] !== 'completed') {
        // Cập nhật trạng thái trong cơ sở dữ liệu
        $update_sql = "UPDATE payments SET status = 'completed', updated_at = NOW() WHERE payment_code = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "s", $payment_code);
        mysqli_stmt_execute($stmt);
    }
    
    echo json_encode([
        'success' => true,
        'status' => $payment_status
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy giao dịch']);
}

// Hàm kiểm tra với cổng thanh toán (giả lập)
function checkPaymentWithGateway($payment_code) {
    // TODO: Thay thế bằng API thực tế của cổng thanh toán
    // Giả lập xác suất thanh toán thành công là 30%
    return (rand(1, 100) <= 30) ? 'completed' : 'pending';
}

mysqli_close($conn);
?> 