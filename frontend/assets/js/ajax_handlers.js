document.addEventListener('DOMContentLoaded', function() {
    // Xử lý form tìm kiếm nhanh (nếu có)
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const location = document.getElementById('location')?.value || '';
            const type = document.getElementById('type')?.value || '';
            const price = document.getElementById('price')?.value || '';
            const url = `../../../frontend/room/filter_rooms.php?location=${encodeURIComponent(location)}&type=${encodeURIComponent(type)}&price=${encodeURIComponent(price)}`;
            window.location.href = url;
        });
    }

    // Xử lý nút lọc nâng cao (nếu có)
    const filterBtn = document.querySelector('.btn-filter');
    if (filterBtn) {
        filterBtn.addEventListener('click', function(e) {
            e.preventDefault();
            applyFilters();
        });
    }

    function applyFilters() {
        // Lấy giá trị location
        let location = document.querySelector('select[name="location"]')?.value || '';
        // Lấy giá trị type (có thể là select, radio, hoặc checkbox)
        let type = '';
        const typeSelect = document.querySelector('select[name="type"]');
        if (typeSelect) {
            type = typeSelect.value;
        } else {
            // Nếu là radio/checkbox
            const checkedType = document.querySelectorAll('input[name="type[]"]:checked');
            if (checkedType.length > 0) {
                type = Array.from(checkedType).map(i => i.value).join(',');
            }
        }
        // Giá trị price
        let price = document.querySelector('select[name="price"]')?.value || '';
        // Giá trị district
        let district = document.querySelector('select[name="district"]')?.value || '';
        // Giá trị min/max area
        let minArea = document.getElementById('minArea')?.value || '';
        let maxArea = document.getElementById('maxArea')?.value || '';

        // Tạo URL với các tham số
        let url = `../../../frontend/room/filter_rooms.php?location=${encodeURIComponent(location)}&type=${encodeURIComponent(type)}&price=${encodeURIComponent(price)}`;
        if (district) {
            url += `&district=${encodeURIComponent(district)}`;
        }
        if (minArea) {
            url += `&min_area=${encodeURIComponent(minArea)}`;
        }
        if (maxArea) {
            url += `&max_area=${encodeURIComponent(maxArea)}`;
        }

        window.location.href = url;
    }
});