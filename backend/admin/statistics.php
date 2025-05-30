<?php
session_start();
$page_title = "Thống kê";
require_once __DIR__ . "/../../backend/config/config.php";

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: /frontend/auth/login.php");
    exit();
}

// Thống kê theo năm/tháng
$current_year = date('Y');
$current_month = date('m');

// Lấy năm được chọn từ form, nếu không có thì lấy năm hiện tại
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : $current_year;

// Tổng số phòng theo tháng trong năm đã chọn
$sql_room_stats = "SELECT 
                    MONTH(created_at) as month, 
                    COUNT(*) as total_rooms 
                   FROM rooms 
                   WHERE YEAR(created_at) = ? 
                   GROUP BY MONTH(created_at)";
$stmt_room_stats = $conn->prepare($sql_room_stats);
$stmt_room_stats->bind_param("i", $selected_year);
$stmt_room_stats->execute();
$result_room_stats = $stmt_room_stats->get_result();

// Chuyển đổi kết quả thành mảng để sử dụng trong biểu đồ
$room_stats = [];
while ($row = $result_room_stats->fetch_assoc()) {
    $room_stats[$row['month']] = $row['total_rooms'];
}

// Tổng số người dùng đăng ký theo tháng
$sql_user_stats = "SELECT 
                    MONTH(created_at) as month, 
                    COUNT(*) as total_users 
                   FROM user 
                   WHERE YEAR(created_at) = ? 
                   GROUP BY MONTH(created_at)";
$stmt_user_stats = $conn->prepare($sql_user_stats);
$stmt_user_stats->bind_param("i", $selected_year);
$stmt_user_stats->execute();
$result_user_stats = $stmt_user_stats->get_result();

// Chuyển đổi kết quả thành mảng
$user_stats = [];
while ($row = $result_user_stats->fetch_assoc()) {
    $user_stats[$row['month']] = $row['total_users'];
}

// Tổng số phòng theo trạng thái
$sql_status_stats = "SELECT 
                      status, 
                      COUNT(*) as count 
                     FROM rooms 
                     GROUP BY status";
$result_status_stats = $conn->query($sql_status_stats);
$status_stats = [];
while ($row = $result_status_stats->fetch_assoc()) {
    $status_stats[$row['status']] = $row['count'];
}

// Thống kê phòng theo loại
$sql_type_stats = "SELECT 
                    type, 
                    COUNT(*) as count 
                   FROM rooms 
                   GROUP BY type";
$result_type_stats = $conn->query($sql_type_stats);
$type_stats = [];
while ($row = $result_type_stats->fetch_assoc()) {
    $type_stats[$row['type']] = $row['count'];
}

// Include header
include __DIR__ . "/includes/header.php";
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Thống kê</h1>
</div>

<!-- Filter Form -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Lọc thống kê</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row">
            <div class="col-md-4">
                <label for="year" class="form-label">Năm</label>
                <select class="form-select" name="year" id="year">
                    <?php for ($year = $current_year; $year >= $current_year - 5; $year--): ?>
                        <option value="<?php echo $year; ?>" <?php echo $selected_year == $year ? 'selected' : ''; ?>>
                            <?php echo $year; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Áp dụng</button>
            </div>
        </form>
    </div>
</div>

<!-- Main Statistics -->
<div class="row">
    <!-- Annual Room Registration Chart -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Thống kê phòng đăng ký theo tháng (<?php echo $selected_year; ?>)</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="monthlyRoomChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Room Status Pie Chart -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Trạng thái phòng</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie">
                    <canvas id="roomStatusChart"></canvas>
                </div>
                <div class="mt-4 text-center small">
                    <span class="me-2">
                        <i class="fas fa-circle text-success"></i> Đã duyệt
                    </span>
                    <span class="me-2">
                        <i class="fas fa-circle text-warning"></i> Chờ duyệt
                    </span>
                    <span class="me-2">
                        <i class="fas fa-circle text-danger"></i> Từ chối
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Registration and Room Types -->
<div class="row">
    <!-- User Registration Chart -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Thống kê người dùng đăng ký theo tháng (<?php echo $selected_year; ?>)</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="monthlyUserChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Room Types Pie Chart -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Loại phòng</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie">
                    <canvas id="roomTypeChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Chuẩn bị dữ liệu cho biểu đồ phòng theo tháng
const roomMonthlyData = {
    labels: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
    datasets: [{
        label: 'Số phòng đăng ký',
        lineTension: 0.3,
        backgroundColor: 'rgba(78, 115, 223, 0.05)',
        borderColor: 'rgba(78, 115, 223, 1)',
        pointRadius: 3,
        pointBackgroundColor: 'rgba(78, 115, 223, 1)',
        pointBorderColor: 'rgba(78, 115, 223, 1)',
        pointHoverRadius: 5,
        pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
        pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
        pointHitRadius: 10,
        pointBorderWidth: 2,
        data: [
            <?php
            for ($month = 1; $month <= 12; $month++) {
                echo isset($room_stats[$month]) ? $room_stats[$month] : 0;
                echo $month < 12 ? ', ' : '';
            }
            ?>
        ]
    }]
};

// Chuẩn bị dữ liệu cho biểu đồ người dùng theo tháng
const userMonthlyData = {
    labels: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
    datasets: [{
        label: 'Số người dùng đăng ký',
        lineTension: 0.3,
        backgroundColor: 'rgba(28, 200, 138, 0.05)',
        borderColor: 'rgba(28, 200, 138, 1)',
        pointRadius: 3,
        pointBackgroundColor: 'rgba(28, 200, 138, 1)',
        pointBorderColor: 'rgba(28, 200, 138, 1)',
        pointHoverRadius: 5,
        pointHoverBackgroundColor: 'rgba(28, 200, 138, 1)',
        pointHoverBorderColor: 'rgba(28, 200, 138, 1)',
        pointHitRadius: 10,
        pointBorderWidth: 2,
        data: [
            <?php
            for ($month = 1; $month <= 12; $month++) {
                echo isset($user_stats[$month]) ? $user_stats[$month] : 0;
                echo $month < 12 ? ', ' : '';
            }
            ?>
        ]
    }]
};

// Chuẩn bị dữ liệu cho biểu đồ trạng thái phòng
const roomStatusData = {
    labels: ['Đã duyệt', 'Chờ duyệt', 'Từ chối'],
    datasets: [{
        data: [
            <?php 
            echo isset($status_stats['approved']) ? $status_stats['approved'] : 0; 
            echo ', ';
            echo isset($status_stats['pending']) ? $status_stats['pending'] : 0;
            echo ', ';
            echo isset($status_stats['rejected']) ? $status_stats['rejected'] : 0;
            ?>
        ],
        backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b'],
        hoverBackgroundColor: ['#169b6b', '#dda20a', '#c72a1c'],
        hoverBorderColor: 'rgba(234, 236, 244, 1)',
    }]
};

// Chuẩn bị dữ liệu cho biểu đồ loại phòng
const roomTypeData = {
    labels: [
        <?php
        foreach ($type_stats as $type => $count) {
            echo "'".($type == 'boarding_house' ? 'Nhà trọ' : ($type == 'apartment' ? 'Căn hộ' : ($type == 'whole_house' ? 'Nhà nguyên căn' : $type)))."', ";
        }
        ?>
    ],
    datasets: [{
        data: [
            <?php
            foreach ($type_stats as $count) {
                echo $count . ', ';
            }
            ?>
        ],
        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
        hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a'],
        hoverBorderColor: 'rgba(234, 236, 244, 1)',
    }]
};

// Cấu hình biểu đồ
const chartOptions = {
    maintainAspectRatio: false,
    responsive: true,
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                precision: 0
            }
        }
    },
    plugins: {
        legend: {
            display: true,
            position: 'top'
        }
    }
};

// Khởi tạo các biểu đồ
window.addEventListener('DOMContentLoaded', (event) => {
    // Biểu đồ phòng theo tháng
    const roomCtx = document.getElementById('monthlyRoomChart').getContext('2d');
    new Chart(roomCtx, {
        type: 'line',
        data: roomMonthlyData,
        options: chartOptions
    });

    // Biểu đồ người dùng theo tháng
    const userCtx = document.getElementById('monthlyUserChart').getContext('2d');
    new Chart(userCtx, {
        type: 'line',
        data: userMonthlyData,
        options: chartOptions
    });

    // Biểu đồ trạng thái phòng
    const statusCtx = document.getElementById('roomStatusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: roomStatusData,
        options: {
            maintainAspectRatio: false,
            responsive: true,
            cutout: '70%',
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Biểu đồ loại phòng
    const typeCtx = document.getElementById('roomTypeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'doughnut',
        data: roomTypeData,
        options: {
            maintainAspectRatio: false,
            responsive: true,
            cutout: '70%',
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                }
            }
        }
    });
});
</script>

<?php
// Include footer
include __DIR__ . "/includes/footer.php";

// Close database connection
$stmt_room_stats->close();
$stmt_user_stats->close();
$conn->close();
?>