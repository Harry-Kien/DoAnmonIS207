<?php
// Tùy chỉnh tiêu đề trang
$page_title = "Điều khoản dịch vụ - Homeseeker";

// Include header
include 'header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Điều khoản dịch vụ</h1>
            
            <div class="card shadow">
                <div class="card-body">
                    <h2 class="h4 mb-3">1. Điều khoản sử dụng</h2>
                    <p>Bằng cách truy cập và sử dụng website Homeseeker, bạn đồng ý tuân thủ và chịu ràng buộc bởi các điều khoản và điều kiện sau đây.</p>

                    <h2 class="h4 mb-3 mt-4">2. Tài khoản người dùng</h2>
                    <ul>
                        <li>Bạn phải cung cấp thông tin chính xác và đầy đủ khi đăng ký tài khoản</li>
                        <li>Bạn chịu trách nhiệm bảo mật thông tin tài khoản của mình</li>
                        <li>Không được chia sẻ tài khoản cho người khác sử dụng</li>
                    </ul>

                    <h2 class="h4 mb-3 mt-4">3. Đăng tin phòng trọ</h2>
                    <ul>
                        <li>Thông tin đăng tin phải chính xác và trung thực</li>
                        <li>Hình ảnh phải là hình thực tế của phòng trọ</li>
                        <li>Không đăng tin trùng lặp hoặc spam</li>
                        <li>Giá phòng phải được niêm yết rõ ràng</li>
                    </ul>

                    <h2 class="h4 mb-3 mt-4">4. Quy định về nội dung</h2>
                    <ul>
                        <li>Không đăng nội dung vi phạm pháp luật</li>
                        <li>Không đăng nội dung quấy rối, xúc phạm</li>
                        <li>Không đăng thông tin cá nhân của người khác</li>
                    </ul>

                    <h2 class="h4 mb-3 mt-4">5. Thanh toán và phí dịch vụ</h2>
                    <ul>
                        <li>Các gói dịch vụ được niêm yết công khai</li>
                        <li>Phí dịch vụ không được hoàn lại sau khi đã sử dụng</li>
                        <li>Thanh toán phải được thực hiện trước khi sử dụng dịch vụ cao cấp</li>
                    </ul>

                    <h2 class="h4 mb-3 mt-4">6. Quyền sở hữu trí tuệ</h2>
                    <p>Tất cả nội dung trên website Homeseeker đều thuộc quyền sở hữu của chúng tôi. Nghiêm cấm sao chép, phân phối mà không được sự cho phép.</p>

                    <h2 class="h4 mb-3 mt-4">7. Thay đổi điều khoản</h2>
                    <p>Chúng tôi có quyền thay đổi điều khoản dịch vụ mà không cần thông báo trước. Việc tiếp tục sử dụng website sau khi thay đổi đồng nghĩa với việc bạn chấp nhận những thay đổi đó.</p>

                    <div class="mt-4">
                        <p class="text-muted">Cập nhật lần cuối: <?php echo date('d/m/Y'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'footer.php';
?> 