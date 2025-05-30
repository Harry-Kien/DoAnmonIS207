<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['PHP_SELF'];
    header("Location: ../../frontend/auth/login.php");
    exit();
}

// Kết nối database
require_once '../../backend/config/config.php';

// Lấy danh sách tỉnh/thành phố
$sql_cities = "SELECT id, name FROM cities ORDER BY name ASC";
$result_cities = $conn->query($sql_cities);

// Tùy chỉnh tiêu đề trang
$page_title = "Đăng tin phòng trọ - Homeseeker";

// Include header (nên dùng header của frontend)
include '../../frontend/pages/header.php';
?>

<link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet">
<script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
<script src="https://unpkg.com/@mapbox/mapbox-sdk/umd/mapbox-sdk.min.js"></script>


<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h1 class="text-center mb-4">Đăng tin phòng trọ</h1>
            
            <?php 
            // Hiển thị thông báo lỗi
            if (isset($_SESSION['form_errors'])) {
                echo '<div class="alert alert-danger">';
                foreach ($_SESSION['form_errors'] as $error) {
                    echo "<p>$error</p>";
                }
                echo '</div>';
                unset($_SESSION['form_errors']);
            }
            ?>
            
            <form action="xu_ly_dang_phong.php" method="POST" enctype="multipart/form-data" id="roomPostForm" novalidate>
                <div class="card shadow-lg">
                    <div class="card-body p-4">
                        <!-- Thông tin cơ bản -->
                        <div class="row">
                            <div class="col-12 mb-4">
                                <h5 class="border-bottom pb-2">
                                    <i class="fas fa-home me-2"></i>Thông tin cơ bản
                                </h5>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="title" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title"
                                       value="<?php echo isset($old['title']) ? htmlspecialchars($old['title']) : ''; ?>"
                                       placeholder="VD: Phòng trọ cao cấp, thoáng mát, gần trung tâm" 
                                       required minlength="10" maxlength="100">
                                <div class="invalid-feedback">Tiêu đề từ 10-100 ký tự</div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="type" class="form-label">Loại phòng <span class="text-danger">*</span></label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Chọn loại phòng</option>
                                    <option value="Phòng trọ">Phòng trọ</option>
                                    <option value="Chung cư mini">Chung cư mini</option>
                                    <option value="Nhà nguyên căn">Nhà nguyên căn</option>
                                    <option value="Ở ghép">Ở ghép</option>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn loại phòng</div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="price" class="form-label">Giá phòng (VNĐ) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="price" name="price" 
                                           placeholder="1.000.000" required min="500000" max="100000000">
                                    <span class="input-group-text">đ/tháng</span>
                                    <div class="invalid-feedback">Giá từ 500.000 - 100.000.000 đồng</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="area" class="form-label">Diện tích (m²) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="area" name="area" 
                                       placeholder="20" required step="0.1" min="10" max="200">
                                <div class="invalid-feedback">Diện tích từ 10-200 m²</div>
                            </div>
                        </div>
                        
                        <!-- Địa chỉ -->
                        <div class="row mt-4">
                            <div class="col-12 mb-3">
                                <h5 class="border-bottom pb-2">
                                    <i class="fas fa-map-marker-alt me-2"></i>Địa chỉ chi tiết
                                </h5>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="address" class="form-label">Địa chỉ cụ thể <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="address" name="address" 
                                       placeholder="Số nhà, đường, phường/xã" required minlength="10">
                                <div class="invalid-feedback">Địa chỉ ít nhất 10 ký tự</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="city_id" class="form-label">Tỉnh/Thành phố <span class="text-danger">*</span></label>
                                <select class="form-select" id="city_id" name="city_id" required>
                                    <option value="">Chọn tỉnh/thành phố</option>
                                    <?php while($city = $result_cities->fetch_assoc()): ?>
                                        <option value="<?php echo $city['id']; ?>"
                                            <?php if(isset($old['city_id']) && $old['city_id'] == $city['id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($city['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback">Chọn tỉnh/thành phố</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="district_id" class="form-label">Quận/Huyện <span class="text-danger">*</span></label>
                                <select class="form-select" id="district_id" name="district_id" required>
                                    <option value="">Chọn quận/huyện</option>
                                </select>
                                <div class="invalid-feedback">Chọn quận/huyện</div>
                            </div>
                        </div>
                        
                        <!-- Chi tiết phòng -->
                        <div class="row mt-4">
                            <div class="col-12 mb-3">
                                <h5 class="border-bottom pb-2">
                                    <i class="fas fa-info-circle me-2"></i>Chi tiết phòng
                                </h5>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Mô tả chi tiết <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description"
                                          rows="5" required minlength="50" maxlength="1000"><?php echo isset($old['description']) ? htmlspecialchars($old['description']) : ''; ?></textarea>
                                <div class="invalid-feedback">Mô tả từ 50-1000 ký tự</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="amenities" class="form-label">Tiện ích</label>
                                <textarea class="form-control" id="amenities" name="amenities" 
                                          rows="3" placeholder="Điều hòa, nóng lạnh, wifi..."></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="facilities" class="form-label">Cơ sở vật chất</label>
                                <textarea class="form-control" id="facilities" name="facilities" 
                                          rows="3" placeholder="Giường, tủ, bàn ghế..."></textarea>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="max_occupants" class="form-label">Số người ở tối đa</label>
                                <input type="number" class="form-control" id="max_occupants" 
                                       name="max_occupants" value="1" min="1" max="10">
                            </div>
                        </div>
                        
                        <!-- Hình ảnh -->
                        <div class="row mt-4">
                            <div class="col-12 mb-3">
                                <h5 class="border-bottom pb-2">
                                    <i class="fas fa-image me-2"></i>Hình ảnh phòng
                                </h5>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="images" class="form-label">Chọn hình ảnh <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="images" name="images[]" 
                                       multiple accept="image/*" required>
                                <div class="form-text text-muted">
                                    Chọn tối đa 5 ảnh, mỗi ảnh không quá 2MB
                                </div>
                                <div class="invalid-feedback">Vui lòng chọn ít nhất 1 ảnh</div>
                            </div>
                        </div>
                        
                        <!-- Thông tin liên hệ -->
                        <div class="row mt-4">
                            <div class="col-12 mb-3">
                                <h5 class="border-bottom pb-2">
                                    <i class="fas fa-phone me-2"></i>Thông tin liên hệ
                                </h5>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="contact_name" class="form-label">Tên liên hệ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="contact_name" name="contact_name" 
                                       required minlength="2" maxlength="50">
                                <div class="invalid-feedback">Tên từ 2-50 ký tự</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="contact_phone" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                                       required pattern="^(0[3|5|7|8|9])+([0-9]{8})\b$">
                                <div class="invalid-feedback">Số điện thoại không hợp lệ</div>
                            </div>
                        </div>
                        
                        <!-- Nút đăng tin -->
                        <div class="row mt-4">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-warning btn-lg px-5">
                                    <i class="fas fa-paper-plane me-2"></i>Đăng tin
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    mapboxgl.accessToken = 'pk.eyJ1IjoiaGVsbG90aGFuaDJrMyIsImEiOiJjbWF0aGRkeTUwMjJiMmxzNTFiNGdscXJmIn0.3WnATbNXHfKjjkbviTZhUQ';
    const mapboxClient = mapboxSdk({ accessToken: mapboxgl.accessToken });

    var addressInput = document.getElementById('address');
    var citySelect = document.getElementById('city_id');
    var districtSelect = document.getElementById('district_id');
    var latInput = document.getElementById('lat');
    var lngInput = document.getElementById('lng');
    var mapDiv = document.getElementById('map');
    var map, marker;

    // Khởi tạo map
    var defaultLat = 10.762622, defaultLng = 106.660172;
    map = new mapboxgl.Map({
        container: mapDiv,
        style: 'mapbox://styles/mapbox/streets-v11',
        center: [defaultLng, defaultLat],
        zoom: 13
    });
    marker = new mapboxgl.Marker({ draggable: true })
        .setLngLat([defaultLng, defaultLat])
        .addTo(map);

    // Cập nhật input khi kéo marker
    marker.on('dragend', function () {
        var lngLat = marker.getLngLat();
        latInput.value = lngLat.lat;
        lngInput.value = lngLat.lng;
    });

    // Hàm lấy tọa độ từ địa chỉ
    function updateMapByAddress(callback) {
        var address = addressInput.value;
        var city = citySelect.options[citySelect.selectedIndex]?.text || '';
        var district = districtSelect.options[districtSelect.selectedIndex]?.text || '';
        var fullAddress = `${address}, ${district}, ${city}, Việt Nam`;

        if (address.length > 5 && city && district) {
            mapboxClient.geocoding
                .forwardGeocode({
                    query: fullAddress,
                    limit: 1
                })
                .send()
                .then(function (response) {
                    if (
                        response &&
                        response.body &&
                        response.body.features &&
                        response.body.features.length
                    ) {
                        var feature = response.body.features[0];
                        var lng = feature.center[0];
                        var lat = feature.center[1];
                        map.setCenter([lng, lat]);
                        marker.setLngLat([lng, lat]);
                        latInput.value = lat;
                        lngInput.value = lng;
                        if (typeof callback === "function") callback(true);
                    } else {
                        alert('Không tìm thấy tọa độ cho địa chỉ này!');
                        if (typeof callback === "function") callback(false);
                    }
                });
        } else {
            if (typeof callback === "function") callback(false);
        }
    }

    addressInput.addEventListener('blur', function() { updateMapByAddress(); });
    citySelect.addEventListener('change', function () {
        var cityId = this.value;
        districtSelect.innerHTML = '<option value="">Đang tải...</option>';
        if (cityId) {
            fetch('get_districts.php?city_id=' + cityId)
                .then(response => response.json())
                .then(data => {
                    let html = '<option value="">Chọn quận/huyện</option>';
                    data.forEach(function (district) {
                        html += `<option value="${district.id}">${district.name}</option>`;
                    });
                    districtSelect.innerHTML = html;
                })
                .catch(() => {
                    districtSelect.innerHTML = '<option value="">Không tải được quận/huyện</option>';
                });
        } else {
            districtSelect.innerHTML = '<option value="">Chọn quận/huyện</option>';
        }
    });
    districtSelect.addEventListener('change', function() { updateMapByAddress(); });

    // Nút xem Mapbox
    const mapBtn = document.createElement('button');
    mapBtn.type = 'button';
    mapBtn.className = 'btn btn-outline-primary ms-2';
    mapBtn.innerHTML = '<i class="fas fa-map-marker-alt"></i> Xem trên Mapbox';
    addressInput.parentNode.insertBefore(mapBtn, addressInput.nextSibling);

    mapBtn.onclick = function () {
        var address = addressInput.value;
        var city = citySelect.options[citySelect.selectedIndex]?.text || '';
        var district = districtSelect.options[districtSelect.selectedIndex]?.text || '';
        var fullAddress = `${address}, ${district}, ${city}, Việt Nam`;
        if (address) {
            var url = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(fullAddress)}`;
            window.open(url, '_blank');
        } else {
            alert('Vui lòng nhập địa chỉ trước!');
        }
    };

    // Đảm bảo luôn có lat/lng trước khi submit
    document.getElementById('roomPostForm').addEventListener('submit', function(e) {
        if (!latInput.value || !lngInput.value) {
            e.preventDefault();
            updateMapByAddress(function(success) {
                if (success && latInput.value && lngInput.value) {
                    this.submit();
                } else {
                    alert('Không thể lấy tọa độ từ địa chỉ. Vui lòng kiểm tra lại địa chỉ hoặc chọn vị trí trên bản đồ!');
                }
            }.bind(this));
        }
    });
});
</script>


<!-- Thêm sau phần nhập địa chỉ -->
<div class="col-12 mb-3">
    <div id="map" style="width: 100%; height: 300px; border-radius: 8px;"></div>
    <input type="hidden" name="lat" id="lat">
    <input type="hidden" name="lng" id="lng">
</div>

<?php
// Include footer (nên dùng footer của frontend)
include '../../frontend/pages/footer.php';
?>

<?php
$old = isset($_SESSION['old_inputs']) ? $_SESSION['old_inputs'] : [];
unset($_SESSION['old_inputs']);
?>

<script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
<script>
mapboxgl.accessToken = 'pk.eyJ1IjoiaGVsbG90aGFuaDJrMyIsImEiOiJjbWF0aGRkeTUwMjJiMmxzNTFiNGdscXJmIn0.3WnATbNXHfKjjkbviTZhUQ';

document.getElementById('roomPostForm').addEventListener('submit', async function(e) {
    const address = document.getElementById('address').value;
    const city = document.getElementById('city_id');
    const district = document.getElementById('district_id');
    const fullAddress = address + ', ' +
        (district.options[district.selectedIndex]?.text || '') + ', ' +
        (city.options[city.selectedIndex]?.text || '') + ', Việt Nam';

    // Gọi Mapbox API lấy tọa độ
    const res = await fetch(`https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(fullAddress)}.json?access_token=${mapboxgl.accessToken}`);
    const data = await res.json();

    if (data.features && data.features.length > 0) {
        const [lng, lat] = data.features[0].geometry.coordinates;
        document.getElementById('lat').value = lat;
        document.getElementById('lng').value = lng;
        // Cho phép submit tiếp
    } else {
        alert("Không tìm thấy tọa độ cho địa chỉ đã nhập. Vui lòng kiểm tra lại!");
        e.preventDefault(); // Ngăn submit
    }
});
</script>