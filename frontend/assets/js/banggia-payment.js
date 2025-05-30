document.addEventListener('DOMContentLoaded', function() {
    // Cấu hình các đường dẫn hình ảnh mã QR
    const qrImagePaths = {
        'banking': 'images/mbbank.jpg',
        'momo': 'images/momo.jpg',
        'vnpay': 'images/vnpay.jpg'
    };
    
    // Cấu hình các đường dẫn biểu tượng
    const iconPaths = {
        'banking': 'images/bank-transfer.png',
        'momo': 'images/momo.png',
        'vnpay': 'images/vnpay.png'
    };

    // 1. Tối ưu hiệu suất hiển thị modal
    function optimizeModalPerformance() {
        // Thêm CSS để tối ưu hóa modal
        const modalStyle = document.createElement('style');
        modalStyle.textContent = `
            /* Loại bỏ hiệu ứng animation */
            .modal, .modal-backdrop, .modal-dialog, .modal.fade .modal-dialog {
                transition: none !important;
                transform: none !important;
                animation: none !important;
            }
            
            /* Cố định kích thước modal */
            .modal-dialog {
                margin: 50px auto !important;
                max-width: 800px;
            }
            
            /* Cố định hiển thị backdrop */
            .modal-backdrop {
                opacity: 0.5 !important;
            }
            
            /* Cải thiện hiển thị modal content */
            .modal-content {
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }
            
            /* Ngăn chặn padding-right khi mở modal */
            .modal-open {
                padding-right: 0 !important;
                overflow: hidden !important;
            }
            
            /* Cấu hình container QR */
            .qr-container {
                height: 320px !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                margin-bottom: 20px !important;
                background-color: #f8f9fa !important;
                border: 1px dashed #dee2e6 !important;
                border-radius: 10px !important;
                padding: 20px !important;
            }
            
            /* Cấu hình hình ảnh QR */
            .qr-container img {
                width: 200px !important;
                height: 200px !important;
                object-fit: contain !important;
                background-color: white !important;
                border: 1px solid #eee !important;
            }
            
            /* Cấu hình thông tin thanh toán */
            .payment-info {
                height: 320px !important;
                overflow-y: auto !important;
            }
            
            /* Cấu hình khối nội dung thanh toán */
            .payment-content {
                min-height: 450px !important;
            }
            
            /* Cấu hình tab phương thức thanh toán */
            .payment-methods {
                min-height: 60px !important;
            }
            
            /* Loại bỏ hiệu ứng fade */
            .fade {
                transition: none !important;
            }
        `;
        document.head.appendChild(modalStyle);
    }

    // 2. Tải trước tất cả hình ảnh
    function preloadImages() {
        // Danh sách hình ảnh cần tải
        const imagesToPreload = [
            'images/mbbank.jpg',
            'images/momo.jpg',
            'images/vnpay.jpg',
            'images/bank-transfer.png',
            'images/momo.png',
            'images/vnpay.png'
        ];
        
        // Tạo các đối tượng Image để tải trước
        imagesToPreload.forEach(src => {
            const img = new Image();
            img.src = src;
        });
    }

    // 3. Thiết lập kích thước cố định cho các container
    function setupFixedContainers() {
        // Thiết lập container QR
        const qrContainers = document.querySelectorAll('.qr-container');
        qrContainers.forEach(container => {
            container.style.height = '320px';
            container.style.display = 'flex';
            container.style.flexDirection = 'column';
            container.style.alignItems = 'center';
            container.style.justifyContent = 'center';
            container.style.backgroundColor = '#f8f9fa';
            container.style.border = '1px dashed #dee2e6';
        });
        
        // Thiết lập hình ảnh QR
        const qrImages = document.querySelectorAll('.qr-container img');
        qrImages.forEach(img => {
            img.style.width = '200px';
            img.style.height = '200px';
            img.style.objectFit = 'contain';
            img.style.backgroundColor = 'white';
            img.style.border = '1px solid #eee';
            
            // Placeholder khi ảnh chưa tải được
            img.onerror = function() {
                this.src = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1%20text%20%7B%20fill%3A%23AAAAAA%3Bfont-weight%3Abold%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23EEEEEE%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2250.5%22%20y%3D%22100%22%3EMã%20QR%20đang%20tải...%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
            };
        });
        
        // Thiết lập thông tin thanh toán
        const paymentInfos = document.querySelectorAll('.payment-info');
        paymentInfos.forEach(info => {
            info.style.height = '320px';
            info.style.overflowY = 'auto';
        });
        
        // Thiết lập các tab phương thức thanh toán
        const paymentContents = document.querySelectorAll('.payment-content');
        paymentContents.forEach(content => {
            content.style.minHeight = '450px';
        });
    }

    // Cập nhật hình ảnh QR cho các phương thức thanh toán
    function updateQRImage(qrPath, method) {
        const container = document.getElementById(`${method}-info`);
        if (container) {
            const img = container.querySelector('.qr-container img');
            if (img) {
                img.src = qrPath;
                img.onerror = function() {
                    this.src = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1%20text%20%7B%20fill%3A%23AAAAAA%3Bfont-weight%3Abold%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23EEEEEE%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2250.5%22%20y%3D%22100%22%3EMã%20QR%20không%20khả%20dụng%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
                };
            }
        }
    }

    // Kiểm tra trạng thái thanh toán
    function checkPaymentStatus(paymentCode) {
        fetch('/backend/payment/check_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ payment_code: paymentCode })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'completed') {
                // Hiển thị modal thành công
                const successModal = new bootstrap.Modal(document.getElementById('confirmPaymentModal'));
                document.getElementById('transaction-code').textContent = paymentCode;
                successModal.show();
                
                // Reload trang sau 3 giây
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // 5. Xử lý sự kiện modal
    function setupModalEvents() {
        const paymentModal = document.getElementById('paymentModal');
        if (paymentModal) {
            // Loại bỏ class fade để tránh animation
            paymentModal.classList.remove('fade');
            
            // Xử lý khi mở modal
            paymentModal.addEventListener('show.bs.modal', function() {
                // Ngăn scroll
                document.body.style.overflow = 'hidden';
                document.body.style.paddingRight = '0px';
                
                // Đảm bảo tất cả container đã được thiết lập
                setupFixedContainers();
                
                // Cập nhật hình ảnh QR
                updateQRImage(qrImagePaths['banking'], 'banking');
            });
            
            // Xử lý khi đóng modal
            paymentModal.addEventListener('hidden.bs.modal', function() {
                // Khôi phục scroll
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            });
        }
    }

    // Xử lý sự kiện khi chọn phương thức thanh toán
    function setupPaymentMethodEvents() {
        const paymentMethods = document.querySelectorAll('[name="payment_method"]');
        paymentMethods.forEach(method => {
            method.addEventListener('change', function() {
                const selectedMethod = this.value;
                // Ẩn tất cả các thông tin thanh toán
                document.querySelectorAll('.payment-info').forEach(info => {
                    info.style.display = 'none';
                });
                // Hiển thị thông tin của phương thức được chọn
                const selectedInfo = document.getElementById(`${selectedMethod}-info`);
                if (selectedInfo) {
                    selectedInfo.style.display = 'block';
                }
            });
        });
    }

    // Xử lý sự kiện khi nhấn nút thanh toán
    function setupPaymentButton() {
        const paymentForm = document.getElementById('paymentForm');
        if (paymentForm) {
            paymentForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const selectedMethod = document.querySelector('[name="payment_method"]:checked').value;
                const planName = document.getElementById('selected_plan').value;
                const planPrice = document.getElementById('selected_price').value;
                
                // Tạo mã giao dịch ngẫu nhiên
                const paymentCode = 'PAY' + Date.now();
                
                // Gửi yêu cầu thanh toán
                fetch('/backend/payment/process_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        plan: planName,
                        price: planPrice,
                        paymentMethod: selectedMethod,
                        paymentCode: paymentCode
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Cập nhật mã QR
                        updateQRImage(data.data.qr_path, selectedMethod);
                        
                        // Bắt đầu kiểm tra trạng thái thanh toán
                        const checkInterval = setInterval(() => {
                            checkPaymentStatus(data.data.payment_code);
                        }, 5000); // Kiểm tra mỗi 5 giây
                        
                        // Lưu interval ID để có thể dừng khi cần
                        window.paymentCheckInterval = checkInterval;
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi xử lý thanh toán');
                });
            });
        }
    }

    // 7. Xử lý sự kiện khi nhấn nút đăng ký gói
    function setupPlanButtons() {
        const planButtons = document.querySelectorAll('.plan-select-btn');
        planButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const plan = this.getAttribute('data-plan');
                const price = this.getAttribute('data-price');
                
                // Xử lý gói Cơ bản
                if (plan === 'Cơ bản' && price === '0') {
                    window.location.href = 'downgrade_plan.php?plan=basic';
                    return;
                }
                
                // Chuẩn bị thông tin cho modal
                document.getElementById('selected-plan').value = plan;
                document.getElementById('plan-price').value = price;
                document.getElementById('plan-name').textContent = plan;
                
                // Format giá
                const formattedPrice = parseInt(price).toLocaleString('vi-VN') + 'đ';
                document.getElementById('price-display').textContent = formattedPrice;
                document.getElementById('amount-display').textContent = formattedPrice + ' VNĐ';
                document.getElementById('momo-amount-display').textContent = formattedPrice + ' VNĐ';
                document.getElementById('vnpay-amount-display').textContent = formattedPrice + ' VNĐ';
                
                // Chuẩn bị modal trước khi hiển thị
                setupFixedContainers();
                updateQRImage(qrImagePaths['banking'], 'banking');
                
                // Hiển thị modal
                const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'), {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });
                
                // Sử dụng setTimeout để tránh hiệu ứng giật
                setTimeout(function() {
                    paymentModal.show();
                }, 50);
            });
        });
    }

    // 10. Xử lý gói được chọn từ redirect
    function handleSelectedPlanFromRedirect() {
        // Được thực hiện thông qua PHP. Kiểm tra trong file banggia.php
        // JavaScript ở đây sẽ được gọi từ PHP nếu có selected_plan
    }

    // Khởi tạo tất cả các chức năng
    function initializeAll() {
        optimizeModalPerformance();
        preloadImages();
        setupFixedContainers();
        setupPaymentMethodEvents();
        setupPaymentButton();
        setupModalEvents();
        setupPlanButtons();
        handleSelectedPlanFromRedirect();
    }

    initializeAll();
});