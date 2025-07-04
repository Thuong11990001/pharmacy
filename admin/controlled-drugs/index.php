<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// admin/controlled_drugs/index.php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once 'controlled_drug_functions.php';

// Kiểm tra đăng nhập và quyền truy cập
SessionManager::requireRole(['admin', 'admin', 'admin']);

// Xử lý các hành động từ controlled_drug_functions.php
$action = $_GET['action'] ?? '';
list($message, $message_type, $logs, $medicines, $patients, $users, $user_info, $statistics) = handleControlledDrugActions($action);

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý Log Thuốc Kiểm soát - Pharmacy Management System</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/dataTables.bootstrap4.min.css">
    <!-- Chart.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.css">
    <!-- AdminLTE Theme style -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
    <!-- Custom Controlled Drug CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>admin/controlled-drugs/css/controlled_drug.css">
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
                <a href="<?php echo BASE_URL; ?>admin/" class="nav-link">Trang chủ</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?php echo BASE_URL; ?>admin/controlled_drugs/" class="nav-link">Log thuốc kiểm soát</a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <span class="navbar-text">Xin chào, <?php echo htmlspecialchars($user_info['full_name'] ?? 'User'); ?></span>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="<?php echo BASE_URL; ?>admin/" class="brand-link">
            <img src="https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png" alt="Pharmacy Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">Pharmacy Management</span>
        </a>
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="https://adminlte.io/themes/v3/dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="#" class="d-block"><?php echo htmlspecialchars($user_info['full_name'] ?? 'User'); ?></a>
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

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            Quản lý Log Thuốc Kiểm soát
                        </h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>admin/">Trang chủ</a></li>
                            <li class="breadcrumb-item active">Log thuốc kiểm soát</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-<?php echo $message_type == 'success' ? 'check' : ($message_type == 'danger' ? 'ban' : 'exclamation-triangle'); ?>"></i> 
                        <?php echo $message_type == 'success' ? 'Thành công!' : ($message_type == 'danger' ? 'Lỗi!' : 'Cảnh báo!'); ?></h5>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?php echo $statistics['total_logs']; ?></h3>
                                <p>Tổng số log</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-list"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?php echo $statistics['today_logs']; ?></h3>
                                <p>Log hôm nay</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3><?php echo $statistics['controlled_medicines']; ?></h3>
                                <p>Thuốc kiểm soát</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-pills"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3><?php echo $statistics['pending_approval']; ?></h3>
                                <p>Chờ phê duyệt</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Panel -->
                <div class="card card-secondary collapsed-card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-filter"></i> Bộ lọc</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="filterForm" method="GET">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_medicine">Thuốc</label>
                                        <select class="form-control" id="filter_medicine" name="filter_medicine">
                                            <option value="">-- Tất cả thuốc --</option>
                                            <?php foreach ($medicines as $medicine): ?>
                                                <option value="<?php echo $medicine['id']; ?>" 
                                                        <?php echo ($_GET['filter_medicine'] ?? '') == $medicine['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($medicine['medicine_code'] . ' - ' . $medicine['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_patient">Bệnh nhân</label>
                                        <select class="form-control" id="filter_patient" name="filter_patient">
                                            <option value="">-- Tất cả bệnh nhân --</option>
                                            <?php foreach ($patients as $patient): ?>
                                                <option value="<?php echo $patient['id']; ?>"
                                                        <?php echo ($_GET['filter_patient'] ?? '') == $patient['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($patient['patient_code'] . ' - ' . $patient['full_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="filter_date_from">Từ ngày</label>
                                        <input type="date" class="form-control" id="filter_date_from" name="filter_date_from"
                                               value="<?php echo $_GET['filter_date_from'] ?? ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="filter_date_to">Đến ngày</label>
                                        <input type="date" class="form-control" id="filter_date_to" name="filter_date_to"
                                               value="<?php echo $_GET['filter_date_to'] ?? ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary btn-block">
                                                <i class="fas fa-search"></i> Lọc
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_doctor">Bác sĩ</label>
                                        <input type="text" class="form-control" id="filter_doctor" name="filter_doctor"
                                               value="<?php echo htmlspecialchars($_GET['filter_doctor'] ?? ''); ?>"
                                               placeholder="Tên bác sĩ">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_employee">Nhân viên bán</label>
                                        <select class="form-control" id="filter_employee" name="filter_employee">
                                            <option value="">-- Tất cả nhân viên --</option>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>"
                                                        <?php echo ($_GET['filter_employee'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_approval_status">Trạng thái phê duyệt</label>
                                        <select class="form-control" id="filter_approval_status" name="filter_approval_status">
                                            <option value="">-- Tất cả trạng thái --</option>
                                            <option value="approved" <?php echo ($_GET['filter_approval_status'] ?? '') == 'approved' ? 'selected' : ''; ?>>Đã phê duyệt</option>
                                            <option value="pending" <?php echo ($_GET['filter_approval_status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Chờ phê duyệt</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <a href="<?php echo BASE_URL; ?>admin/controlled_drugs/" class="btn btn-secondary btn-block">
                                                <i class="fas fa-times"></i> Xóa bộ lọc
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-info" id="exportExcel">
                                <i class="fas fa-file-excel"></i> Xuất Excel
                            </button>
                            <button type="button" class="btn btn-success" id="printReport">
                                <i class="fas fa-print"></i> In báo cáo
                            </button>
                            <button type="button" class="btn btn-warning" id="showStatistics">
                                <i class="fas fa-chart-bar"></i> Thống kê
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Controlled Drug Logs List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list"></i> Danh sách Log Thuốc Kiểm soát
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-info">Tổng: <?php echo count($logs); ?> bản ghi</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="controlledDrugTable" class="table table-bordered table-striped table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th width="5%">STT</th>
                                        <th width="10%">Mã log</th>
                                        <th width="12%">Thuốc</th>
                                        <th width="10%">Bệnh nhân</th>
                                        <th width="8%">Số lượng</th>
                                        <th width="10%">Đơn giá</th>
                                        <th width="12%">Bác sĩ</th>
                                        <th width="10%">Nhân viên</th>
                                        <th width="10%">Thời gian</th>
                                        <th width="8%">Phê duyệt</th>
                                        <th width="5%">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $index => $log): ?>
                                        <tr class="<?php echo !$log['supervisor_approved_by'] ? 'table-warning' : ''; ?>">
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <code class="controlled-log-code"><?php echo htmlspecialchars($log['log_code']); ?></code>
                                            </td>
                                            <td>
                                                <div class="medicine-info">
                                                    <strong><?php echo htmlspecialchars($log['medicine_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($log['medicine_code']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="patient-info">
                                                    <strong><?php echo htmlspecialchars($log['patient_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($log['patient_code']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary quantity-badge">
                                                    <?php echo number_format($log['quantity']); ?>
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <strong><?php echo number_format($log['unit_price'], 2); ?>đ</strong>
                                                <br><small class="text-muted">Tổng: <?php echo number_format($log['unit_price'] * $log['quantity'], 2); ?>đ</small>
                                            </td>
                                            <td>
                                                <div class="doctor-info">
                                                    <strong><?php echo htmlspecialchars($log['doctor_name']); ?></strong>
                                                    <?php if ($log['doctor_license']): ?>
                                                        <br><small class="text-muted">GP: <?php echo htmlspecialchars($log['doctor_license']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="employee-name"><?php echo htmlspecialchars($log['sold_by_name']); ?></span>
                                            </td>
                                            <td>
                                                <div class="datetime-info">
                                                    <strong><?php echo date('d/m/Y', strtotime($log['sold_at'])); ?></strong>
                                                    <br><small class="text-muted"><?php echo date('H:i:s', strtotime($log['sold_at'])); ?></small>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($log['supervisor_approved_by']): ?>
                                                    <span class="badge badge-success" title="Đã phê duyệt bởi <?php echo htmlspecialchars($log['supervisor_name']); ?>">
                                                        <i class="fas fa-check"></i> Đã duyệt
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-hourglass-half"></i> Chờ duyệt
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-info view-detail" 
                                                            data-log-id="<?php echo $log['id']; ?>" title="Xem chi tiết">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if (!$log['supervisor_approved_by'] && in_array($user_info['role'], ['admin', 'admin'])): ?>
                                                        <button type="button" class="btn btn-success approve-log" 
                                                                data-log-id="<?php echo $log['id']; ?>" title="Phê duyệt">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </div>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
    </aside>

    <!-- Main Footer -->
    <footer class="main-footer">
        <strong>Copyright &copy; 2024 <a href="#">Pharmacy Management System</a>.</strong>
        All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> 1.0.0
        </div>
    </footer>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">Chi tiết Log Thuốc Kiểm soát</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Modal -->
<div class="modal fade" id="statisticsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Thống kê Thuốc Kiểm soát</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="statisticsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
<!-- DataTables & Plugins -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.responsive.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/buttons.html5.min.js"></script>
<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>
<!-- Custom Controlled Drug JS -->
<script src="<?php echo BASE_URL; ?>admin/controlled-drugs/js/controlled_drug.js"></script>

</body>
</html>