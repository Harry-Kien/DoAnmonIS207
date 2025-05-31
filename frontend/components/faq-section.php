<?php
// FAQ Section Component for Pricing Page
?>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center mb-5">
                <h2 class="fw-bold mb-3">Câu hỏi thường gặp</h2>
                <p class="text-muted">Những thắc mắc phổ biến về các gói dịch vụ của chúng tôi</p>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="accordion" id="faqAccordion">
                    <!-- Câu hỏi 1 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading1">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="true" aria-controls="collapse1">
                                Làm thế nào để nâng cấp hoặc hạ cấp gói dịch vụ?
                            </button>
                        </h2>
                        <div id="collapse1" class="accordion-collapse collapse show" aria-labelledby="heading1" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Để nâng cấp gói dịch vụ, bạn chỉ cần đăng nhập vào tài khoản và chọn gói dịch vụ mới từ trang Bảng giá. Sau khi thanh toán, gói mới sẽ được kích hoạt ngay lập tức. Để hạ cấp xuống gói thấp hơn, bạn có thể chọn gói Cơ bản (miễn phí) hoặc gói thấp hơn gói hiện tại của bạn.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Câu hỏi 2 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading2">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                                Các phương thức thanh toán nào được chấp nhận?
                            </button>
                        </h2>
                        <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="heading2" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Chúng tôi chấp nhận nhiều phương thức thanh toán khác nhau bao gồm: chuyển khoản ngân hàng, ví điện tử MoMo, và VNPay. Tất cả các giao dịch đều được bảo mật và xử lý nhanh chóng để đảm bảo trải nghiệm thanh toán thuận tiện cho người dùng.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Câu hỏi 3 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading3">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                                Tôi có thể hủy gói dịch vụ bất cứ lúc nào không?
                            </button>
                        </h2>
                        <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="heading3" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Có, bạn có thể hủy gói dịch vụ bất cứ lúc nào. Tuy nhiên, chúng tôi không hoàn tiền cho thời gian còn lại của gói dịch vụ. Sau khi hủy, bạn vẫn có thể sử dụng dịch vụ cho đến khi hết thời hạn đã thanh toán, sau đó tài khoản sẽ tự động chuyển về gói Cơ bản (miễn phí).
                            </div>
                        </div>
                    </div>
                    
                    <!-- Câu hỏi 4 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading4">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                                Làm thế nào để tôi biết gói dịch vụ nào phù hợp với nhu cầu của mình?
                            </button>
                        </h2>
                        <div id="collapse4" class="accordion-collapse collapse" aria-labelledby="heading4" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <p>Mỗi gói dịch vụ được thiết kế cho các nhu cầu khác nhau:</p>
                                <ul>
                                    <li><strong>Gói Cơ bản:</strong> Phù hợp cho người dùng mới, muốn trải nghiệm dịch vụ hoặc chỉ có nhu cầu đăng ít phòng.</li>
                                    <li><strong>Gói Tiêu chuẩn:</strong> Lý tưởng cho chủ nhà có 5-10 phòng cho thuê, cần quảng cáo và hỗ trợ ưu tiên.</li>
                                    <li><strong>Gói Cao cấp:</strong> Dành cho chủ nhà chuyên nghiệp với nhiều phòng cho thuê, cần khả năng quảng cáo nổi bật và hỗ trợ 24/7.</li>
                                </ul>
                                <p>Nếu bạn vẫn chưa chắc chắn, hãy liên hệ với đội ngũ hỗ trợ của chúng tôi để được tư vấn.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Câu hỏi 5 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading5">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse5" aria-expanded="false" aria-controls="collapse5">
                                Tôi có thể yêu cầu hóa đơn VAT cho việc thanh toán không?
                            </button>
                        </h2>
                        <div id="collapse5" class="accordion-collapse collapse" aria-labelledby="heading5" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Có, chúng tôi có thể cung cấp hóa đơn VAT cho các giao dịch thanh toán. Vui lòng cung cấp thông tin xuất hóa đơn (tên công ty, mã số thuế, địa chỉ) khi thanh toán hoặc liên hệ với bộ phận hỗ trợ khách hàng của chúng tôi trong vòng 7 ngày kể từ ngày thanh toán.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-5">
            <div class="col-12 text-center">
                <p class="mb-0">Bạn có câu hỏi khác? <a href="https://zalo.me/0382140336" class="text-primary">Liên hệ với chúng tôi</a></p>
            </div>
        </div>
    </div>
</section>
