<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// admin/prescriptions/index.php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../config/database.php';


// Kiểm tra đăng nhập và quyền truy cập
SessionManager::requireRole(['admin', 'admin', 'admin']);

// Xử lý các hành động
$action = $_GET['action'] ?? '';
$user_info = SessionManager::getUserInfo();

// Prepare data for charts
try {
    // Sales by Date
    $salesStmt = $pdo->query("
        SELECT DATE(sale_date) as sale_date, SUM(total_amount) as total_sales
        FROM sales 
        WHERE status = 'completed'
        GROUP BY DATE(sale_date)
        ORDER BY sale_date DESC
        LIMIT 30
    ");
    $salesData = $salesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prescription Status
    $prescriptionStmt = $pdo->query("
        SELECT status, COUNT(*) as count
        FROM v_prescription_status
        GROUP BY status
    ");
    $prescriptionData = $prescriptionStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Stock Status
    $stockStmt = $pdo->query("
        SELECT stock_status, COUNT(*) as count
        FROM v_current_stock
        GROUP BY stock_status
    ");
    $stockData = $stockStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error loading report data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Báo Cáo Hệ Thống - Pharmacy Management System</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- AdminLTE Theme style -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/overlayscrollbars/1.13.1/css/OverlayScrollbars.min.css">
    <!-- Chart.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.css">
    
    <style>
        .content-wrapper {
            margin-left: 250px;
        }
        @media (max-width: 767px) {
            .content-wrapper {
                margin-left: 0;
            }
        }
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }
        .export-btn {
            margin-bottom: 1rem;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Preloader -->
    <div class="preloader flex-column justify-content-center align-items-center">
        <img class="animation__shake" src="https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png" alt="AdminLTELogo" height="60" width="60">
    </div>

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#" class="nav-link">Trang chủ</a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <span class="navbar-text">Xin chào, <?php echo htmlspecialchars($user_info['full_name']); ?></span>
            </li>
            <li class="nav-item">
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="#" class="brand-link">
            <img src="https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png" alt="Pharmacy Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">Pharmacy Management</span>
        </a>
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="https://adminlte.io/themes/v3/dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="#" class="d-block"><?php echo htmlspecialchars($user_info['full_name']); ?></a>
                </div>
            </div>
            <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <!-- Dashboard -->
        <li class="nav-item">
          <a href="<?php echo BASE_URL; ?>admin/" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <!-- Quản lý -->
        <li class="nav-item has-treeview <?php echo in_array($current_page, ['medicines', 'categories', 'suppliers', 'users', 'inventory', 'prescriptions']) ? 'menu-open' : ''; ?>">
          <a href="#" class="nav-link <?php echo in_array($current_page, ['medicines', 'categories', 'suppliers', 'users', 'inventory', 'prescriptions']) ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-cogs"></i>
            <p>
              Quản lý
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>admin/medicines/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/medicines/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Thuốc</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>admin/categories/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/categories/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Danh mục</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>admin/suppliers/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/suppliers/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Nhà cung cấp</p>
              </a>
            </li>
            <?php if ($user_info['role'] === 'admin'): ?>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>admin/users/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/users/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Người dùng</p>
              </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>admin/inventory/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/inventory/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Lô hàng</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>admin/prescriptions/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/prescriptions/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Đơn thuốc</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>admin/patients/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/patients/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Bệnh nhân</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- Giao dịch -->
        <li class="nav-item has-treeview <?php echo in_array($current_page, ['sales', 'controlled-drugs', 'sale-details', 'prescription-details']) ? 'menu-open' : ''; ?>">
          <a href="#" class="nav-link <?php echo in_array($current_page, ['sales', 'controlled-drugs', 'sale-details', 'prescription-details']) ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-exchange-alt"></i>
            <p>
              Giao dịch
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>admin/sales/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/sales/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Giao dịch Bán hàng</p>
              </a>
            </li>
            <?php if ($user_info['can_sell_controlled']): ?>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>admin/controlled-drugs/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/controlled-drugs/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Nhật ký Thuốc Kiểm soát</p>
              </a>
            </li>
            <?php endif; ?>
           
          </ul>
        </li>

        <!-- Báo cáo -->
        <li class="nav-item">
          <a href="<?php echo BASE_URL; ?>admin/reports/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/reports/') !== false ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>Báo cáo</p>
          </a>
        </li>

        <!-- Phân cách -->
        <li class="nav-header">HỆ THỐNG</li>

        <!-- Cài đặt (chỉ admin) -->
        <?php if ($user_info['role'] === 'admin'): ?>
        <li class="nav-item">
          <a href="<?php echo BASE_URL; ?>admin/settings/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/settings/') !== false ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-tools"></i>
            <p>Cài đặt hệ thống</p>
          </a>
        </li>
        <?php endif; ?>

        <!-- Thông tin -->
        <li class="nav-item">
          <a href="#" class="nav-link" onclick="showSystemInfo()">
            <i class="nav-icon fas fa-info-circle"></i>
            <p>Thông tin hệ thống</p>
          </a>
        </li>

        <!-- Đăng xuất -->
        <li class="nav-item">
          <a href="<?php echo BASE_URL; ?>logout.php" class="nav-link" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất?')">
            <i class="nav-icon fas fa-sign-out-alt text-danger"></i>
            <p class="text-danger">Đăng xuất</p>
          </a>
        </li>
      </ul>
    </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Báo Cáo Hệ Thống</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                            <li class="breadcrumb-item active">Báo Cáo</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="icon fas fa-ban"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Sales Chart -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Doanh Thu Theo Ngày</h3>
                                <div class="card-tools">
                                    <button class="btn btn-primary btn-sm export-btn" data-report="sales">
                                        <i class="fas fa-download"></i> Xuất Báo Cáo
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="salesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Prescription Status Chart -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Trạng Thái Đơn Thuốc</h3>
                                <div class="card-tools">
                                    <button class="btn btn-primary btn-sm export-btn" data-report="prescriptions">
                                        <i class="fas fa-download"></i> Xuất Báo Cáo
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="prescriptionChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Status Chart -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Trạng Thái Tồn Kho</h3>
                                <div class="card-tools">
                                    <button class="btn btn-primary btn-sm export-btn" data-report="stock">
                                        <i class="fas fa-download"></i> Xuất Báo Cáo
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="stockChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Summary Table -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Tóm Tắt Doanh Thu</h3>
                                <div class="card-tools">
                                    <button class="btn btn-primary btn-sm export-btn" data-report="sales_summary">
                                        <i class="fas fa-download"></i> Xuất Báo Cáo
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="salesSummaryTable" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Mã Đơn</th>
                                                <th>Ngày Bán</th>
                                                <th>Khách Hàng</th>
                                                <th>Tổng Tiền</th>
                                                <th>Phương Thức</th>
                                                <th>Trạng Thái</th>
                                                <th>Nhân Viên</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            try {
                                                $stmt = $pdo->query("
                                                    SELECT sale_code, sale_date, customer_name, total_amount, 
                                                           payment_method, status, employee_name
                                                    FROM v_sales_summary 
                                                    WHERE status = 'completed'
                                                    ORDER BY sale_date DESC
                                                    LIMIT 10
                                                ");
                                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                    echo "<tr>";
                                                    echo "<td>" . htmlspecialchars($row['sale_code']) . "</td>";
                                                    echo "<td>" . htmlspecialchars(date('d/m/Y', strtotime($row['sale_date']))) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['customer_name'] ?? '-') . "</td>";
                                                    echo "<td>" . number_format($row['total_amount'], 2) . "</td>";
                                                    echo "<td>" . htmlspecialchars(['cash' => 'Tiền mặt', 'card' => 'Thẻ', 'bank_transfer' => 'Chuyển khoản', 'insurance' => 'Bảo hiểm', 'mixed' => 'Hỗn hợp'][$row['payment_method']] ?? '-') . "</td>";
                                                    echo "<td>" . htmlspecialchars(['draft' => 'Nháp', 'completed' => 'Hoàn thành', 'cancelled' => 'Hủy', 'returned' => 'Trả lại'][$row['status']] ?? '-') . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['employee_name'] ?? '-') . "</td>";
                                                    echo "</tr>";
                                                }
                                            } catch (PDOException $e) {
                                                echo "<tr><td colspan='7' class='text-center text-danger'>Lỗi khi tải dữ liệu: " . $e->getMessage() . "</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Main Footer -->
    <footer class="main-footer">
        <strong>Copyright &copy; 2025 <a href="#">Pharmacy Management</a>.</strong>
        All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> 1.0.0
        </div>
    </footer>
</div>

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.1/js/bootstrap.bundle.min.js"></script>
<!-- DataTables & Plugins -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<!-- overlayScrollbars -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/overlayscrollbars/1.13.1/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>
<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
// Chart data
const salesData = <?php echo json_encode($salesData); ?>;
const prescriptionData = <?php echo json_encode($prescriptionData); ?>;
const stockData = <?php echo json_encode($stockData); ?>;

// Initialize Sales Chart
const salesChartCtx = document.getElementById('salesChart').getContext('2d');
new Chart(salesChartCtx, {
    type: 'line',
    data: {
        labels: salesData.map(item => new Date(item.sale_date).toLocaleDateString('vi-VN')),
        datasets: [{
            label: 'Doanh Thu',
            data: salesData.map(item => item.total_sales),
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1,
            fill: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: { display: true, text: 'Doanh Thu (VND)' }
            },
            x: {
                title: { display: true, text: 'Ngày' }
            }
        }
    }
});

// Initialize Prescription Status Chart
const prescriptionChartCtx = document.getElementById('prescriptionChart').getContext('2d');
new Chart(prescriptionChartCtx, {
    type: 'pie',
    data: {
        labels: prescriptionData.map(item => ({
            pending: 'Chờ xử lý',
            partial: 'Một phần',
            completed: 'Hoàn thành',
            cancelled: 'Hủy',
            expired: 'Hết hạn'
        }[item.status] || item.status)),
        datasets: [{
            data: prescriptionData.map(item => item.count),
            backgroundColor: ['#ff6384', '#36a2eb', '#4bc0c0', '#ffcd56', '#9966ff']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Initialize Stock Status Chart
const stockChartCtx = document.getElementById('stockChart').getContext('2d');
new Chart(stockChartCtx, {
    type: 'bar',
    data: {
        labels: stockData.map(item => ({
            'Out of Stock': 'Hết hàng',
            'Low Stock': 'Sắp hết',
            'Near Expiry': 'Gần hết hạn',
            'In Stock': 'Còn hàng'
        }[item.stock_status] || item.stock_status)),
        datasets: [{
            label: 'Số lượng',
            data: stockData.map(item => item.count),
            backgroundColor: '#36a2eb'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: { display: true, text: 'Số lượng thuốc' }
            }
        }
    }
});

// Initialize DataTable
$(document).ready(function() {
    $('#salesSummaryTable').DataTable({
        responsive: true,
        lengthChange: false,
        autoWidth: false,
        pageLength: 10,
        language: {
            "processing": "Đang xử lý...",
            "lengthMenu": "Hiển thị _MENU_ mục",
            "zeroRecords": "Không tìm thấy dữ liệu",
            "info": "Hiển thị _START_ đến _END_ của _TOTAL_ mục",
            "infoEmpty": "Hiển thị 0 đến 0 của 0 mục",
            "infoFiltered": "(lọc từ _MAX_ mục)",
            "search": "Tìm kiếm:",
            "paginate": {
                "first": "Đầu",
                "last": "Cuối",
                "next": "Tiếp",
                "previous": "Trước"
            }
        },
        order: [[1, 'desc']]
    });

    // Export report handler
    $('.export-btn').on('click', function() {
        const reportType = $(this).data('report');
        let url = '';
        switch(reportType) {
            case 'sales':
                url = 'export_sales.php';
                break;
            case 'prescriptions':
                url = 'export_prescriptions.php';
                break;
            case 'stock':
                url = 'export_stock.php';
                break;
            case 'sales_summary':
                url = 'export_sales_summary.php';
                break;
        }
        window.location.href = url;
    });

    // Auto-dismiss alerts
    $('.alert').each(function() {
        var alert = $(this);
        setTimeout(function() {
            alert.fadeOut('slow');
        }, 5000);
    });

    // Handle sidebar menu toggle
    $('[data-widget="pushmenu"]').on('click', function() {
        $('body').toggleClass('sidebar-collapse');
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

</body>
</html>