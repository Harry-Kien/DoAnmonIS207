document.addEventListener('DOMContentLoaded', function() {
    // Cấu hình các đường dẫn hình ảnh mã QR
    const qrImagePaths = {
        'banking': '../../frontend/assets/images/mbbank.jpg',
        'momo': '../../frontend/assets/images/momo.jpg',
        'vnpay': '../../frontend/assets/images/vnpay.jpg'
    };
    
    // Cấu hình các đường dẫn biểu tượng
    const iconPaths = {
        'banking': '../assets/images/mbbank.jpg',
        'momo': '../assets/images/momo.jpg',
        'vnpay': '../assets/images/vnpay.jpg'
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
            '../assets/images/mbbank.jpg',
            '../assets/images/momo.jpg',
            '../assets/images/vnpay.jpg'
        ];
        
        // Tạo các đối tượng Image để tải trước
        imagesToPreload.forEach(src => {
            const img = new Image();
            img.src = src;
            img.onerror = function() {
                console.error('Failed to load image:', src);
            };
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
        });
    }

    // Cập nhật hình ảnh QR cho các phương thức thanh toán
    function updateQRImage(method) {
        const container = document.getElementById(`${method}-info`);
        if (container) {
            const img = container.querySelector('.qr-container img');
            if (img) {
                img.src = iconPaths[method];
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
                updateQRImage('banking');
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
                const planName = document.getElementById('selected-plan').value;
                const planPrice = document.getElementById('plan-price').value;
                
                // Lấy payment code từ input hidden
                const paymentCode = document.getElementById('payment-code').value;
                
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
                        updateQRImage(selectedMethod);
                        
                        // Bắt đầu kiểm tra trạng thái thanh toán
                        const checkInterval = setInterval(() => {
                            checkPaymentStatus(paymentCode);
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
                if ((plan === 'Cơ bản' || plan.toLowerCase() === 'cơ bản') && price === '0') {
                    window.location.href = 'downgrade_plan.php?plan=basic';
                    return;
                }
                
                // Chuẩn bị thông tin cho modal
                document.getElementById('selected-plan').value = plan;
                document.getElementById('plan-price').value = price;
                document.getElementById('plan-name-display').textContent = plan;
                
                // Hiển thị tên gói trong tiêu đề modal
                const planNameElement = document.getElementById('plan-name');
                if (planNameElement) {
                    planNameElement.textContent = plan;
                }
                
                // Format giá
                const formattedPrice = parseInt(price).toLocaleString('vi-VN') + 'đ';
                document.getElementById('price-display').textContent = formattedPrice;
                document.getElementById('amount-display').textContent = formattedPrice + ' VNĐ';
                document.getElementById('momo-amount-display').textContent = formattedPrice + ' VNĐ';
                document.getElementById('vnpay-amount-display').textContent = formattedPrice + ' VNĐ';
                
                // Hiển thị payment code trong modal thành công
                const paymentCodeElement = document.getElementById('payment-code-display');
                if (paymentCodeElement) {
                    const paymentCode = document.getElementById('payment-code').value;
                    document.getElementById('transaction-code').textContent = paymentCode;
                }
                
                // Chuẩn bị modal trước khi hiển thị
                setupFixedContainers();
                updateQRImage('banking');
                
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