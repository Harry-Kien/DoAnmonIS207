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
                <div class="payment-methods">
                    <div class="payment-method active" data-method="banking">
                        <img src="../assets/images/mbbank.jpg" alt="Chuyển khoản ngân hàng">
                        Chuyển khoản ngân hàng
                    </div>
                    <div class="payment-method" data-method="momo">
                        <img src="../assets/images/momo.jpg" alt="Ví MoMo">
                        Ví MoMo
                    </div>
                    <div class="payment-method" data-method="vnpay">
                        <img src="../assets/images/vnpay.jpg" alt="VNPay">
                        VNPay
                    </div>
                </div>

                <!-- Banking -->
                <div id="banking-info" class="payment-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="qr-container">
                                <h6 class="mb-3">Quét mã QR để thanh toán</h6>
                                <img src="../assets/images/mbbank.jpg" alt="Mã QR thanh toán" style="max-width: 200px;" />
                                <p class="mt-2 text-muted small">Sử dụng ứng dụng ngân hàng hoặc ví điện tử để quét</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="payment-info">
                                <h6 class="mb-3">Thông tin chuyển khoản</h6>
                                <div class="row"><div class="col-5 fw-bold">Số tài khoản:</div><div class="col-7">3630112005</div></div>
                                <div class="row"><div class="col-5 fw-bold">Ngân hàng:</div><div class="col-7">MB Bank</div></div>
                                <div class="row"><div class="col-5 fw-bold">Chủ tài khoản:</div><div class="col-7">TRẦN TRUNG KIÊN</div></div>
                                <div class="row"><div class="col-5 fw-bold">Số tiền:</div><div class="col-7 fw-bold text-danger" id="amount-display"><?php echo number_format($plan_data['price']); ?> VNĐ</div></div>
                                <div class="row">
                                    <div class="col-5 fw-bold">Nội dung CK:</div>
                                    <div class="col-7">
                                        <span class="text-primary" id="payment-code"><?php echo $payment_code; ?></span>
                                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyPaymentCode()">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="mt-4 mb-3">Hướng dẫn thanh toán</h6>
                    <ol class="payment-steps">
                        <li>Mở ứng dụng ngân hàng hoặc ví điện tử trên điện thoại của bạn</li>
                        <li>Quét mã QR hoặc nhập thông tin chuyển khoản thủ công</li>
                        <li>Nhập chính xác số tiền và nội dung chuyển khoản như trên</li>
                        <li>Xác nhận giao dịch và hoàn tất thanh toán</li>
                        <li>Nhấn nút "Tôi đã thanh toán" bên dưới sau khi đã chuyển khoản thành công</li>
                    </ol>
                </div>

                <!-- MoMo -->
                <div id="momo-info" class="payment-content" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="qr-container">
                                <h6 class="mb-3">Quét mã QR MoMo</h6>
                                <img src="../assets/images/momo.jpg" alt="Mã QR MoMo" class="img-fluid" style="max-width: 200px;" />
                                <p class="mt-2 text-muted small">Sử dụng ứng dụng MoMo để quét</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="payment-info">
                                <h6 class="mb-3">Thông tin MoMo</h6>
                                <div class="row"><div class="col-5 fw-bold">Số điện thoại:</div><div class="col-7">0382140336</div></div>
                                <div class="row"><div class="col-5 fw-bold">Tên tài khoản:</div><div class="col-7">TRẦN TRUNG KIÊN</div></div>
                                <div class="row"><div class="col-5 fw-bold">Số tiền:</div><div class="col-7 fw-bold text-danger" id="momo-amount-display"><?php echo number_format($plan_data['price']); ?> VNĐ</div></div>
                                <div class="row"><div class="col-5 fw-bold">Nội dung CK:</div><div class="col-7"><span class="text-primary" id="momo-payment-code"><?php echo $payment_code; ?></span></div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VNPay -->
                <div id="vnpay-info" class="payment-content" style="display: none; min-height: 450px;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="qr-container d-flex flex-column align-items-center justify-content-center" style="height: 320px; background-color: #fff; border: 1px dashed #dee2e6;">
                                <img id="qr-vnpay-img"
                                     src="../assets/images/vnpay.jpg"
                                     alt="Mã QR VNPay"
                                     class="img-fluid"
                                     style="max-width: 200px;"
                                     onerror="document.getElementById('qr-vnpay-img').style.display='none';document.getElementById('qr-vnpay-fallback').style.display='block';" />
                                <div id="qr-vnpay-fallback" style="display: none; max-width: 200px;">
                                    <p style="color: red; text-align: center;">Không thể hiển thị mã QR. Vui lòng thử lại sau.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="payment-info">
                                <h6 class="mb-3">Thông tin VNPay</h6>
                                <div class="alert alert-warning">Quét mã QR bên cạnh bằng ứng dụng ngân hàng hỗ trợ VNPay hoặc ứng dụng VNPay QR để thanh toán.</div>
                                <div class="row"><div class="col-5 fw-bold">Số tiền:</div><div class="col-7 fw-bold text-danger" id="vnpay-amount-display"><?php echo number_format($plan_data['price']); ?> VNĐ</div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning mt-4">
                    <i class="fas fa-info-circle me-2"></i> Sau khi thanh toán thành công, hệ thống sẽ kích hoạt gói dịch vụ của bạn trong vòng 5-10 phút. Nếu sau 30 phút gói dịch vụ chưa được kích hoạt, vui lòng liên hệ hotline 0382140336 để được hỗ trợ.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="payment-completed-btn">
                    <i class="fas fa-check me-2"></i> Tôi đã thanh toán
                </button>
            </div>
        </div>
    </div>
</div>
