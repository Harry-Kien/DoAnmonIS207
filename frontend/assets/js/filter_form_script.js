const districtsByLocation = {
    "TP. Hồ Chí Minh": [
        "Quận 1", "Quận 2", "Quận 3", "Quận 4", "Quận 5", "Quận 6", "Quận 7",
        "Quận 8", "Quận 9", "Quận 10", "Quận 11", "Quận 12", "Quận Bình Thạnh",
        "Quận Tân Bình", "Quận Tân Phú", "Quận Phú Nhuận", "Quận Gò Vấp",
        "Quận Bình Tân", "Huyện Củ Chi", "Huyện Hóc Môn", "Huyện Bình Chánh",
        "Huyện Nhà Bè", "Huyện Cần Giờ", "Thủ Đức"
    ],
    "Bình Dương": [
        "TP. Thủ Dầu Một", "TP. Dĩ An", "TP. Thuận An", "TP. Tân Uyên",
        "TP. Bến Cát", "Huyện Bàu Bàng", "Huyện Phú Giáo", "Huyện Dầu Tiếng", "Huyện Bắc Tân Uyên"
    ]
};

function updateDistricts(currentDistrict = '') {
    const location = document.getElementById('locationSelect')?.value;
    const districtSelect = document.getElementById('districtSelect');
    if (!districtSelect) {
        return;
    }

    districtSelect.innerHTML = '<option value="">Quận/Huyện</option>';
    if (districtsByLocation[location]) {
        districtsByLocation[location].forEach(d => {
            const option = document.createElement('option');
            option.value = d;
            option.textContent = d;
            if (d === currentDistrict) {
                option.selected = true;
            }
            districtSelect.appendChild(option);
        });
    }
}

function applySort() {
    const sortValue = document.getElementById('sortSelect')?.value;
    const url = new URL(window.location.href);
    url.searchParams.set('sort', sortValue);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', () => {
    const districtFromServer = window.currentDistrict || '';
    updateDistricts(districtFromServer);

    document.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach(input => {
        input.addEventListener('change', () => {
            if (input.type === 'radio') {
                document.querySelectorAll(`input[name="${input.name}"]`).forEach(i => i.closest('.btn')?.classList.remove('active'));
                input.closest('.btn')?.classList.add('active');
            } else {
                input.closest('.btn')?.classList.toggle('active', input.checked);
            }
        });
    });

    const form = document.getElementById('filterForm');
    if (form) {
        form.addEventListener('submit', e => {
            ['price_range', 'area_range'].forEach(rangeName => {
                const rangeInput = document.querySelector(`input[name="${rangeName}"]:checked`);
                if (rangeInput && rangeInput.value) {
                    const [min, max] = rangeInput.value.split('-').map(Number);
                    form.querySelectorAll(`input[name^="min_"], input[name^="max_"]`).forEach(i => i.remove());

                    const minInput = document.createElement('input');
                    minInput.type = 'hidden';
                    minInput.name = `min_${rangeName.split('_')[0]}`;
                    minInput.value = min;

                    const maxInput = document.createElement('input');
                    maxInput.type = 'hidden';
                    maxInput.name = `max_${rangeName.split('_')[0]}`;
                    maxInput.value = max;

                    form.appendChild(minInput);
                    form.appendChild(maxInput);
                }
            });
        });
    }

    document.querySelectorAll('.room-img').forEach(img => {
        img.addEventListener('error', () => {
            img.src = '../assets/images/default-room.jpg';
        });
    });

    document.querySelectorAll('.btn-outline-secondary input:checked').forEach(input => {
        input.closest('.btn')?.classList.add('active');
    });
});
