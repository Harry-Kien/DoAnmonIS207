<?php
require_once '../../backend/config/config.php';

$plan_code = isset($_GET['plan_code']) ? $_GET['plan_code'] : 'standard';
$plan_sql = "SELECT name, price, duration FROM plans WHERE plan_code = ?";
$stmt = mysqli_prepare($conn, $plan_sql);
mysqli_stmt_bind_param($stmt, "s", $plan_code);
mysqli_stmt_execute($stmt);
$plan_result = mysqli_stmt_get_result($stmt);
$plan_data = mysqli_fetch_assoc($plan_result);

if (!$plan_data) {
    $plan_data = ['name' => 'Tiêu chuẩn', 'price' => 199000, 'duration' => 30];
}

$payment_code = "HS" . mt_rand(1000000, 9999999);
?>

<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content payment-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Thanh toán gói dịch vụ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="selected-plan" value="<?php echo htmlspecialchars($plan_data['name']); ?>">
                <input type="hidden" id="plan-price" value="<?php echo htmlspecialchars($plan_data['price']); ?>">

                <div class="alert alert-info">
                    Bạn đang thanh toán cho gói <strong id="plan-name"><?php echo htmlspecialchars($plan_data['name']); ?></strong> với giá <strong id="price-display"><?php echo number_format($plan_data['price']); ?>đ</strong>/<?php echo $plan_data['duration']; ?> ngày
                </div>

                <h5 class="mb-3">Chọn phương thức thanh toán</h5>
                <div class="payment-methods d-flex justify-content-center gap-3 mb-4">
                    <div class="payment-method-card" data-method="banking">
                        <div class="card cursor-pointer p-3 text-center" onclick="selectPaymentMethod('banking')">
                            <img src="../assets/images/payment/mbbank-logo.png" alt="Chuyển khoản ngân hàng" class="img-fluid mb-2" style="height: 40px; object-fit: contain;">
                            <div class="method-name">Chuyển khoản ngân hàng</div>
                        </div>
                    </div>
                    <div class="payment-method-card" data-method="momo">
                        <div class="card cursor-pointer p-3 text-center" onclick="selectPaymentMethod('momo')">
                            <img src="../assets/images/payment/momo-logo.png" alt="Ví MoMo" class="img-fluid mb-2" style="height: 40px; object-fit: contain;">
                            <div class="method-name">Ví MoMo</div>
                        </div>
                    </div>
                    <div class="payment-method-card" data-method="vnpay">
                        <div class="card cursor-pointer p-3 text-center" onclick="selectPaymentMethod('vnpay')">
                            <img src="../assets/images/payment/vnpay-logo.png" alt="VNPay" class="img-fluid mb-2" style="height: 40px; object-fit: contain;">
                            <div class="method-name">VNPay</div>
                        </div>
                    </div>
                </div>

                <!-- Banking -->
                <div id="banking-info" class="payment-content active">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="qr-container text-center p-4 bg-light rounded">
                                <h6 class="mb-3">Quét mã QR để thanh toán</h6>
                                <img src="../assets/images/payment/mbbank-qr.png" alt="Mã QR thanh toán" class="img-fluid mb-3" style="max-width: 200px;" />
                                <p class="text-muted small">Sử dụng ứng dụng ngân hàng hoặc ví điện tử để quét</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="payment-info">
                                <h6 class="mb-3">Thông tin chuyển khoản</h6>
                                <div class="info-item d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold">Số tài khoản:</span>
                                    <div class="d-flex align-items-center">
                                        <span>3630112005</span>
                                        <button class="btn btn-sm btn-link" onclick="copyToClipboard('3630112005')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="info-item d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold">Ngân hàng:</span>
                                    <span>MB Bank</span>
                                </div>
                                <div class="info-item d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold">Chủ tài khoản:</span>
                                    <span>TRẦN TRUNG KIÊN</span>
                                </div>
                                <div class="info-item d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold">Số tiền:</span>
                                    <span class="text-danger fw-bold"><?php echo number_format($plan_data['price']); ?> VNĐ</span>
                                </div>
                                <div class="info-item d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Nội dung CK:</span>
                                    <div class="d-flex align-items-center">
                                        <span class="text-primary" id="payment-code"><?php echo $payment_code; ?></span>
                                        <button class="btn btn-sm btn-link" onclick="copyToClipboard('<?php echo $payment_code; ?>')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MoMo -->
                <div id="momo-info" class="payment-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="qr-container text-center p-4 bg-light rounded">
                                <h6 class="mb-3">Quét mã QR MoMo</h6>
                                <img src="../assets/images/payment/momo-qr.png" alt="Mã QR MoMo" class="img-fluid mb-3" style="max-width: 200px;" />
                                <p class="text-muted small">Sử dụng ứng dụng MoMo để quét</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="payment-info">
                                <h6 class="mb-3">Thông tin MoMo</h6>
                                <div class="info-item d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold">Số điện thoại:</span>
                                    <div class="d-flex align-items-center">
                                        <span>0382140336</span>
                                        <button class="btn btn-sm btn-link" onclick="copyToClipboard('0382140336')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="info-item d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold">Tên tài khoản:</span>
                                    <span>TRẦN TRUNG KIÊN</span>
                                </div>
                                <div class="info-item d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold">Số tiền:</span>
                                    <span class="text-danger fw-bold"><?php echo number_format($plan_data['price']); ?> VNĐ</span>
                                </div>
                                <div class="info-item d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Nội dung CK:</span>
                                    <div class="d-flex align-items-center">
                                        <span class="text-primary"><?php echo $payment_code; ?></span>
                                        <button class="btn btn-sm btn-link" onclick="copyToClipboard('<?php echo $payment_code; ?>')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VNPay -->
                <div id="vnpay-info" class="payment-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="qr-container text-center p-4 bg-light rounded">
                                <h6 class="mb-3">Quét mã QR VNPay</h6>
                                <img src="../assets/images/payment/vnpay-qr.png" alt="Mã QR VNPay" class="img-fluid mb-3" style="max-width: 200px;" />
                                <p class="text-muted small">Sử dụng ứng dụng ngân hàng hỗ trợ VNPay để quét</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="payment-info">
                                <h6 class="mb-3">Thông tin VNPay</h6>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Quét mã QR bên cạnh bằng ứng dụng ngân hàng hỗ trợ VNPay hoặc ứng dụng VNPay QR để thanh toán.
                                </div>
                                <div class="info-item d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Số tiền:</span>
                                    <span class="text-danger fw-bold"><?php echo number_format($plan_data['price']); ?> VNĐ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning mt-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Sau khi thanh toán thành công, hệ thống sẽ kích hoạt gói dịch vụ của bạn trong vòng 5-10 phút.
                    Nếu sau 30 phút gói dịch vụ chưa được kích hoạt, vui lòng liên hệ hotline 0382140336 để được hỗ trợ.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="payment-completed-btn" onclick="confirmPayment()">
                    <i class="fas fa-check me-2"></i> Tôi đã thanh toán
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.payment-methods {
    margin-bottom: 2rem;
}

.payment-method-card {
    flex: 1;
    max-width: 200px;
}

.payment-method-card .card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
    cursor: pointer;
}

.payment-method-card .card:hover {
    border-color: #ffc107;
    transform: translateY(-2px);
}

.payment-method-card.active .card {
    border-color: #ffc107;
    background-color: #fff8e5;
}

.payment-content {
    display: none;
}

.payment-content.active {
    display: block;
}

.qr-container {
    border: 1px solid #dee2e6;
    border-radius: 8px;
}

.info-item {
    padding: 8px;
    border-bottom: 1px solid #dee2e6;
}

.info-item:last-child {
    border-bottom: none;
}

.cursor-pointer {
    cursor: pointer;
}
</style>

<script>
function selectPaymentMethod(method) {
    // Remove active class from all payment methods
    document.querySelectorAll('.payment-method-card').forEach(card => {
        card.classList.remove('active');
    });
    
    // Add active class to selected method
    document.querySelector(`.payment-method-card[data-method="${method}"]`).classList.add('active');
    
    // Hide all payment content
    document.querySelectorAll('.payment-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Show selected payment content
    document.getElementById(`${method}-info`).classList.add('active');
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show toast or notification
        alert('Đã sao chép vào clipboard!');
    }).catch(err => {
        console.error('Không thể sao chép: ', err);
    });
}

function confirmPayment() {
    // Get the active payment method
    const activeMethod = document.querySelector('.payment-method-card.active').dataset.method;
    const planName = document.getElementById('selected-plan').value;
    const planPrice = document.getElementById('plan-price').value;
    const paymentCode = document.getElementById('payment-code').textContent;
    
    // Send confirmation to server
    fetch('/backend/payment/confirm_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            method: activeMethod,
            plan_name: planName,
            amount: planPrice,
            payment_code: paymentCode
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Cảm ơn bạn đã thanh toán! Chúng tôi sẽ xác nhận và kích hoạt gói dịch vụ của bạn trong thời gian sớm nhất.');
            $('#paymentModal').modal('hide');
        } else {
            alert('Có lỗi xảy ra: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi xác nhận thanh toán. Vui lòng thử lại sau.');
    });
}

// Set banking as default payment method when modal opens
document.addEventListener('DOMContentLoaded', function() {
    selectPaymentMethod('banking');
});
</script>
