document.addEventListener('DOMContentLoaded', function() {
    // Hàm helper để lấy base URL của website
    function getBaseUrl() {
        return window.location.protocol + '//' + window.location.host + '/DoAnmonIS207'; // Sửa tên thư mục dự án
    }

    // Hàm validate input
    function validateInput(value, type) {
        if (!value) return true; // Cho phép giá trị rỗng
        
        switch(type) {
            case 'area':
                return !isNaN(value) && value >= 0;
            case 'price':
                return !isNaN(value) && value >= 0;
            default:
                return true;
        }
    }

    // Xử lý form tìm kiếm nhanh
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            try {
                const location = document.getElementById('location')?.value || '';
                const type = document.getElementById('type')?.value || '';
                const price = document.getElementById('price')?.value || '';

                if (!validateInput(price, 'price')) {
                    alert('Giá trị giá không hợp lệ');
                    return;
                }

                const url = `${getBaseUrl()}/frontend/pages/search.php?location=${encodeURIComponent(location)}&type=${encodeURIComponent(type)}&price=${encodeURIComponent(price)}`; // Sửa cách ghép đường dẫn
                window.location.href = url;
            } catch (error) {
                console.error('Lỗi khi xử lý form tìm kiếm:', error);
                alert('Đã có lỗi xảy ra khi tìm kiếm. Vui lòng thử lại.');
            }
        });
    }

    // Xử lý nút lọc nâng cao
    const filterBtn = document.querySelector('.btn-filter');
    if (filterBtn) {
        filterBtn.addEventListener('click', function(e) {
            e.preventDefault();
            applyFilters();
        });
    }

    function applyFilters() {
        try {
            // Lấy và validate các giá trị
            const location = document.querySelector('select[name="location"]')?.value || '';
            
            // Xử lý type
            let type = '';
            const typeSelect = document.querySelector('select[name="type"]');
            if (typeSelect) {
                type = typeSelect.value;
            } else {
                const checkedType = document.querySelectorAll('input[name="type[]"]:checked');
                if (checkedType.length > 0) {
                    type = Array.from(checkedType).map(i => i.value).join(',');
                }
            }

            const price = document.querySelector('select[name="price"]')?.value || '';
            const district = document.querySelector('select[name="district"]')?.value || '';
            const minArea = document.getElementById('minArea')?.value || '';
            const maxArea = document.getElementById('maxArea')?.value || '';

            // Validate các giá trị
            if (!validateInput(minArea, 'area') || !validateInput(maxArea, 'area')) {
                alert('Giá trị diện tích không hợp lệ');
                return;
            }

            if (!validateInput(price, 'price')) {
                alert('Giá trị giá không hợp lệ');
                return;
            }

            // Tạo URL với base URL
            let url = `${getBaseUrl()}/frontend/pages/search.php?location=${encodeURIComponent(location)}&type=${encodeURIComponent(type)}&price=${encodeURIComponent(price)}`; // Sửa cách ghép đường dẫn
            
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
        } catch (error) {
            console.error('Lỗi khi áp dụng bộ lọc:', error);
            alert('Đã có lỗi xảy ra khi lọc. Vui lòng thử lại.');
        }
    }
});