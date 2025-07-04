<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// manager/index.php
require_once '../includes/config.php';
require_once '../includes/session.php';

// Kiểm tra đăng nhập và quyền truy cập
SessionManager::requireRole(['admin', 'manager']);

// Đếm số lượng thuốc
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM medicines WHERE status = 'active'");
    $stmt->execute();
    $medicines = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $medicines = ['total' => 0];
    error_log("Error counting medicines: " . $e->getMessage());
}

// Đếm số lượng giao dịch bán hàng hôm nay
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM sales WHERE DATE(sale_date) = CURDATE() AND status = 'completed'");
    $stmt->execute();
    $sales = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sales = ['total' => 0];
    error_log("Error counting sales: " . $e->getMessage());
}

// Đếm số lượng đơn thuốc đang chờ xử lý
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM prescriptions WHERE status = 'pending'");
    $stmt->execute();
    $prescriptions = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $prescriptions = ['total' => 0];
    error_log("Error counting prescriptions: " . $e->getMessage());
}

// Đếm số lượng giao dịch thuốc kiểm soát đặc biệt hôm nay
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM controlled_drug_log WHERE DATE(sold_at) = CURDATE()");
    $stmt->execute();
    $controlled = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $controlled = ['total' => 0];
    error_log("Error counting controlled drugs: " . $e->getMessage());
}

// Lấy thông tin thuốc sắp hết hạn (trong 30 ngày)
try {
    $stmt = $pdo->prepare("
        SELECT m.name, mb.batch_number, mb.expiry_date, mb.current_quantity
        FROM medicine_batches mb
        JOIN medicines m ON mb.medicine_id = m.id
        WHERE mb.status = 'active' 
        AND mb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND mb.current_quantity > 0
        ORDER BY mb.expiry_date ASC
        LIMIT 5
    ");
    $stmt->execute();
    $expiring_medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $expiring_medicines = [];
    error_log("Error fetching expiring medicines: " . $e->getMessage());
}

// Lấy thông tin thuốc sắp hết (low stock)
try {
    $stmt = $pdo->prepare("
        SELECT m.name, m.min_stock_level, 
        COALESCE(SUM(mb.current_quantity), 0) as total_quantity
        FROM medicines m
        LEFT JOIN medicine_batches mb ON m.id = mb.medicine_id AND mb.status = 'active'
        WHERE m.status = 'active'
        GROUP BY m.id, m.name, m.min_stock_level
        HAVING total_quantity <= m.min_stock_level AND m.min_stock_level > 0
        ORDER BY total_quantity ASC
        LIMIT 5
    ");
    $stmt->execute();
    $low_stock_medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $low_stock_medicines = [];
    error_log("Error fetching low stock medicines: " . $e->getMessage());
}

// Lấy thông tin doanh thu tuần này
try {
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(total_amount), 0) as weekly_revenue,
            COUNT(*) as weekly_sales_count
        FROM sales 
        WHERE YEARWEEK(sale_date, 1) = YEARWEEK(CURDATE(), 1) 
        AND status = 'completed'
    ");
    $stmt->execute();
    $weekly_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $weekly_stats = ['weekly_revenue' => 0, 'weekly_sales_count' => 0];
    error_log("Error fetching weekly stats: " . $e->getMessage());
}

$user_info = SessionManager::getUserInfo();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Pharmacy Management System</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- AdminLTE Theme style -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/overlayscrollbars/1.13.1/css/OverlayScrollbars.min.css">
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Preloader -->
    <div class="preloader flex-column justify-content-center align-items-center">
        <img class="animation__shake" src="https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png" alt="AdminLTELogo" height="60" width="60">
    </div>

    <?php 
    // Include header and sidebar if they exist, otherwise create inline
    if (file_exists('share/header.php')) {
        require_once 'share/header.php'; 
    } else {
        // Inline header
        echo '<nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="' . BASE_URL . 'manager/" class="nav-link">Trang chủ</a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <span class="navbar-text">Xin chào, ' . htmlspecialchars($user_info['full_name'] ?? 'User') . '</span>
                </li>
                <li class="nav-item">
                    <a href="' . BASE_URL . 'logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </a>
                </li>
            </ul>
        </nav>';
    }
    
    if (file_exists('share/sidebar.php')) {
        require_once 'share/sidebar.php'; 
    } else {
        // Inline sidebar
        echo '<aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="' . BASE_URL . 'manager/" class="brand-link">
                <img src="https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png" alt="Pharmacy Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light">Pharmacy Management</span>
            </a>
            <div class="sidebar">
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <img src="https://adminlte.io/themes/v3/dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
                    </div>
                    <div class="info">
                        <a href="#" class="d-block">' . htmlspecialchars($user_info['full_name'] ?? 'User') . '</a>
                    </div>
                </div>
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item">
                            <a href="' . BASE_URL . 'manager/" class="nav-link active">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="' . BASE_URL . 'logout.php" class="nav-link">
                                <i class="nav-icon fas fa-sign-out-alt text-danger"></i>
                                <p class="text-danger">Đăng xuất</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>';
    }
    ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Dashboard</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Trang chủ</a></li>
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Small boxes (Stat box) -->
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?php echo number_format($medicines['total']); ?></h3>
                                <p>Thuốc đang hoạt động</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-medkit"></i>
                            </div>
                            <a href="<?php echo BASE_URL; ?>manager/medicines/" class="small-box-footer">
                                Xem chi tiết <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?php echo number_format($sales['total']); ?></h3>
                                <p>Giao dịch hôm nay</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-stats-bars"></i>
                            </div>
                            <a href="<?php echo BASE_URL; ?>manager/sales/" class="small-box-footer">
                                Xem chi tiết <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3><?php echo number_format($prescriptions['total']); ?></h3>
                                <p>Đơn thuốc chờ xử lý</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-clipboard"></i>
                            </div>
                            <a href="<?php echo BASE_URL; ?>manager/prescriptions/" class="small-box-footer">
                                Xem chi tiết <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3><?php echo number_format($controlled['total']); ?></h3>
                                <p>Thuốc kiểm soát hôm nay</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-alert-circled"></i>
                            </div>
                            <a href="<?php echo BASE_URL; ?>manager/controlled-drugs/" class="small-box-footer">
                                Xem chi tiết <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Revenue Stats -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Thống kê tuần này</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="description-block border-right">
                                            <span class="description-percentage text-success">
                                                <i class="fas fa-caret-up"></i>
                                            </span>
                                            <h5 class="description-header"><?php echo number_format($weekly_stats['weekly_revenue'], 0, ',', '.'); ?> VND</h5>
                                            <span class="description-text">Doanh thu tuần</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="description-block">
                                            <span class="description-percentage text-info">
                                                <i class="fas fa-caret-up"></i>
                                            </span>
                                            <h5 class="description-header"><?php echo number_format($weekly_stats['weekly_sales_count']); ?></h5>
                                            <span class="description-text">Giao dịch tuần</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Thông tin người dùng</h3>
                            </div>
                            <div class="card-body">
                                <p><strong>Tên:</strong> <?php echo htmlspecialchars($user_info['full_name']); ?></p>
                                <p><strong>Vai trò:</strong> 
                                    <span class="badge badge-<?php echo $user_info['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo ucfirst($user_info['role']); ?>
                                    </span>
                                </p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($user_info['email'] ?? 'Chưa cập nhật'); ?></p>
                                <p><strong>Quyền bán thuốc kiểm soát:</strong> 
                                    <span class="badge badge-<?php echo $user_info['can_sell_controlled'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $user_info['can_sell_controlled'] ? 'Có' : 'Không'; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts Row -->
                <div class="row">
                    <!-- Expiring Medicines -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                    Thuốc sắp hết hạn (30 ngày)
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <?php if (!empty($expiring_medicines)): ?>
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tên thuốc</th>
                                                <th>Lô</th>
                                                <th>Hết hạn</th>
                                                <th>Số lượng</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($expiring_medicines as $medicine): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($medicine['batch_number']); ?></td>
                                                    <td class="text-danger">
                                                        <?php echo date('d/m/Y', strtotime($medicine['expiry_date'])); ?>
                                                    </td>
                                                    <td><?php echo number_format($medicine['current_quantity']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="p-3 text-center text-muted">
                                        <i class="fas fa-check-circle text-success"></i>
                                        Không có thuốc nào sắp hết hạn
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Low Stock Medicines -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-box text-danger"></i>
                                    Thuốc sắp hết (dưới mức tối thiểu)
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <?php if (!empty($low_stock_medicines)): ?>
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tên thuốc</th>
                                                <th>Hiện có</th>
                                                <th>Tối thiểu</th>
                                                <th>Trạng thái</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($low_stock_medicines as $medicine): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                                    <td class="text-danger">
                                                        <?php echo number_format($medicine['total_quantity']); ?>
                                                    </td>
                                                    <td><?php echo number_format($medicine['min_stock_level']); ?></td>
                                                    <td>
                                                        <span class="badge badge-danger">Thiếu hàng</span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="p-3 text-center text-muted">
                                        <i class="fas fa-check-circle text-success"></i>
                                        Tất cả thuốc đều đủ tồn kho
                                    </div>
                                <?php endif; ?>
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
<!-- jQuery UI -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.1/js/bootstrap.bundle.min.js"></script>
<!-- overlayScrollbars -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/overlayscrollbars/1.13.1/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>

<script>
$(document).ready(function() {
    // Auto refresh session every 5 minutes
    setInterval(function() {
        $.ajax({
            url: '<?php echo BASE_URL; ?>ajax/refresh_session.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (!response.success) {
                    window.location.href = '<?php echo BASE_URL; ?>login.php';
                }
            }
        });
    }, 300000); // 5 minutes
});
</script>

</body>
</html>