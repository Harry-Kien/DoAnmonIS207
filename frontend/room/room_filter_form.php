<?php
// room_filter_form.php
?>
<div class="filter-bar">
  <h5 class="mb-4">Lọc tìm kiếm</h5>
  <form action="../../frontend/room/filter_rooms.php" method="GET" id="filterForm">
    <!-- Khu vực -->
    <div class="filter-section">
      <div class="filter-heading">Khu vực</div>
      <select class="form-select mb-3" name="location" id="locationSelect" onchange="updateDistricts()">
        <option value="">Tất cả</option>
        <option value="TP. Hồ Chí Minh">TP. Hồ Chí Minh</option>
        <option value="Bình Dương">Bình Dương</option>
      </select>
      <select class="form-select" name="district" id="districtSelect">
        <option value="">Quận/Huyện</option>
      </select>
    </div>

    <!-- Loại phòng -->
    <div class="filter-section">
      <div class="filter-heading">Loại phòng</div>
      <select class="form-select" name="type">
        <option value="">Tất cả</option>
        <option value="Phòng trọ">Phòng trọ</option>
        <option value="Chung cư mini">Chung cư mini</option>
        <option value="Nhà nguyên căn">Nhà nguyên căn</option>
      </select>
    </div>

    <!-- Khoảng giá -->
    <div class="filter-section">
      <div class="filter-heading">Khoảng giá</div>
      <select class="form-select" name="price">
        <option value="">Tất cả</option>
        <option value="0-2000000">Dưới 2 triệu</option>
        <option value="2000000-4000000">2 - 4 triệu</option>
        <option value="4000000-6000000">4 - 6 triệu</option>
        <option value="6000000-10000000">Trên 6 triệu</option>
      </select>
    </div>

    <!-- Diện tích -->
    <div class="filter-section">
      <div class="filter-heading">Diện tích</div>
      <div class="row g-2">
        <div class="col-6">
          <input type="number" class="form-control" name="min_area" placeholder="Từ (m²)">
        </div>
        <div class="col-6">
          <input type="number" class="form-control" name="max_area" placeholder="Đến (m²)">
        </div>
      </div>
    </div>

    <button type="submit" class="btn btn-warning w-100">Tìm kiếm</button>
  </form>
</div>
