<?php
// Payment Modal Component for QR code payments
// This file is included in banggia.php

// Tạo payment code ngẫu nhiên
$payment_code = "HS" . mt_rand(1000000, 9999999);
?>

<!-- Modal Thanh toán -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Thanh toán gói <span id="plan-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm">
                    <!-- Hidden inputs -->
                    <input type="hidden" id="selected-plan" name="selected_plan" value="">
                    <input type="hidden" id="plan-price" name="selected_price" value="">
                    <input type="hidden" id="payment-code" name="payment_code" value="<?php echo $payment_code; ?>">
                    
                    <!-- Thông tin thanh toán -->
                    <div class="alert alert-info">
                        <strong>Thông tin gói:</strong> <span id="plan-name-display"></span> - 
                        <strong>Giá:</strong> <span id="price-display"></span><br>
                        <strong>Mã giao dịch:</strong> <span id="payment-code-display"><?php echo $payment_code; ?></span>
                    </div>
                    
                    <!-- Phương thức thanh toán -->
                    <div class="payment-methods mb-4">
                        <div class="form-check form-check-inline payment-method">
                            <input class="form-check-input" type="radio" name="payment_method" id="banking" value="banking" checked>
                            <label class="form-check-label" for="banking">
                                <img src="../assets/images/mbbank.jpg" alt="Banking" height="30">
                                Chuyển khoản
                            </label>
                        </div>
                        <div class="form-check form-check-inline payment-method">
                            <input class="form-check-input" type="radio" name="payment_method" id="momo" value="momo">
                            <label class="form-check-label" for="momo">
                                <img src="../assets/images/momo.jpg" alt="MoMo" height="30">
                                MoMo
                            </label>
                        </div>
                        <div class="form-check form-check-inline payment-method">
                            <input class="form-check-input" type="radio" name="payment_method" id="vnpay" value="vnpay">
                            <label class="form-check-label" for="vnpay">
                                <img src="../assets/images/vnpay.jpg" alt="VNPay" height="30">
                                VNPay
                            </label>
                        </div>
                    </div>
                    
                    <!-- Nội dung thanh toán -->
                    <div class="payment-content">
                        <!-- Banking -->
                        <div class="payment-info" id="banking-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="qr-container">
                                        <img src="../assets/images/mbbank.jpg" alt="QR Banking" class="img-fluid">
                                        <p class="mt-2">Quét mã QR để thanh toán</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5>Hướng dẫn thanh toán</h5>
                                    <ol class="payment-steps">
                                        <li>Mở ứng dụng ngân hàng và quét mã QR</li>
                                        <li>Nhập số tiền: <strong id="amount-display"></strong></li>
                                        <li>Nội dung chuyển khoản: <strong>HOMESEEKER <?php echo $payment_code; ?></strong></li>
                                        <li>Xác nhận và hoàn tất thanh toán</li>
                                    </ol>
                                    <div class="mt-3">
                                        <p><strong>Thông tin tài khoản:</strong></p>
                                        <p>Số tài khoản: <strong>3620112005</strong> <button class="btn btn-sm btn-outline-primary copy-btn" data-clipboard-text="3630112005"><i class="fas fa-copy"></i></button></p>
                                        <p>Chủ tài khoản: <strong>TRAN TRUNG KIEN</strong></p>
                                        <p>Ngân hàng: <strong>MB Bank</strong></p>
                                        <p>Nội dung CK: <strong>HOMESEEKER <?php echo $payment_code; ?></strong> <button class="btn btn-sm btn-outline-primary copy-btn" data-clipboard-text="HOMESEEKER <?php echo $payment_code; ?>"><i class="fas fa-copy"></i></button></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- MoMo -->
                        <div class="payment-info" id="momo-info" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="qr-container">
                                        <img src="../assets/images/momo.jpg" alt="QR MoMo" class="img-fluid">
                                        <p class="mt-2">Quét mã QR để thanh toán</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5>Hướng dẫn thanh toán MoMo</h5>
                                    <ol class="payment-steps">
                                        <li>Mở ứng dụng MoMo và quét mã QR</li>
                                        <li>Nhập số tiền: <strong id="momo-amount-display"></strong></li>
                                        <li>Nội dung chuyển khoản: <strong>HOMESEEKER <?php echo $payment_code; ?></strong></li>
                                        <li>Xác nhận và hoàn tất thanh toán</li>
                                    </ol>
                                    <div class="mt-3">
                                        <p><strong>Thông tin tài khoản MoMo:</strong></p>
                                        <p>Số điện thoại: <strong>0382140336</strong> <button class="btn btn-sm btn-outline-primary copy-btn" data-clipboard-text="0382140336"><i class="fas fa-copy"></i></button></p>
                                        <p>Chủ tài khoản: <strong>TRAN TRUNG KIEN</strong></p>
                                        <p>Nội dung CK: <strong>HOMESEEKER <?php echo $payment_code; ?></strong> <button class="btn btn-sm btn-outline-primary copy-btn" data-clipboard-text="HOMESEEKER <?php echo $payment_code; ?>"><i class="fas fa-copy"></i></button></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- VNPay -->
                        <div class="payment-info" id="vnpay-info" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="qr-container">
                                        <img src="../assets/images/vnpay.jpg" alt="QR VNPay" class="img-fluid">
                                        <p class="mt-2">Quét mã QR để thanh toán</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5>Hướng dẫn thanh toán VNPay</h5>
                                    <ol class="payment-steps">
                                        <li>Mở ứng dụng ngân hàng hỗ trợ VNPay và quét mã QR</li>
                                        <li>Nhập số tiền: <strong id="vnpay-amount-display"></strong></li>
                                        <li>Nội dung chuyển khoản: <strong>HOMESEEKER <?php echo $payment_code; ?></strong></li>
                                        <li>Xác nhận và hoàn tất thanh toán</li>
                                    </ol>
                                    <div class="mt-3">
                                        <p><strong>Thông tin VNPay:</strong></p>
                                        <p>Mã đơn hàng: <strong><?php echo $payment_code; ?></strong></p>
                                        <p>Nội dung CK: <strong>HOMESEEKER <?php echo $payment_code; ?></strong> <button class="btn btn-sm btn-outline-primary copy-btn" data-clipboard-text="HOMESEEKER <?php echo $payment_code; ?>"><i class="fas fa-copy"></i></button></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Xác nhận thanh toán</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal xác nhận thanh toán thành công -->
<div class="modal fade" id="confirmPaymentModal" tabindex="-1" aria-labelledby="confirmPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="confirmPaymentModalLabel">Thanh toán thành công</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                <h4 class="mt-3">Thanh toán đã được xác nhận!</h4>
                <p>Mã giao dịch: <strong id="transaction-code"></strong></p>
                <p>Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="payment-completed-btn" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Thêm thư viện Clipboard.js để hỗ trợ sao chép -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
<script>
    // Khởi tạo Clipboard.js
    document.addEventListener('DOMContentLoaded', function() {
        new ClipboardJS('.copy-btn');
        
        // Thêm hiệu ứng khi nhấn nút sao chép
        const copyButtons = document.querySelectorAll('.copy-btn');
        copyButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 1500);
            });
        });
    });
</script> 