<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// manager/sales/index.php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once 'sales_functions.php';

// Kiểm tra đăng nhập và quyền truy cập
SessionManager::requireRole(['admin', 'manager', 'pharmacist']);

// Xử lý các hành động từ sales_functions.php
$action = $_GET['action'] ?? '';
list($message, $message_type, $edit_sale, $edit_sale_details, $sales, $patients, $prescriptions, $medicines, $user_info) = handleSalesActions($action);

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý Giao dịch Bán hàng - Pharmacy Management System</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/dataTables.bootstrap4.min.css">
    <!-- AdminLTE Theme style -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
    <!-- Custom Sales CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>manager/sales/css/sales.css">
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
                <a href="<?php echo BASE_URL; ?>manager/" class="nav-link">Trang chủ</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?php echo BASE_URL; ?>manager/sales/" class="nav-link">Giao dịch bán hàng</a>
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
        <a href="<?php echo BASE_URL; ?>manager/" class="brand-link">
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
          <a href="<?php echo BASE_URL; ?>manager/" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
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
              <a href="<?php echo BASE_URL; ?>manager/medicines/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/medicines/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Thuốc</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>manager/categories/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/categories/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Danh mục</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>manager/suppliers/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/suppliers/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Nhà cung cấp</p>
              </a>
            </li>
            <?php if ($user_info['role'] === 'admin'): ?>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>manager/users/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/users/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Người dùng</p>
              </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>manager/inventory/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/inventory/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Lô hàng</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>manager/prescriptions/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/prescriptions/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Đơn thuốc</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>manager/patients/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/patients/') !== false ? 'active' : ''; ?>">
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
              <a href="<?php echo BASE_URL; ?>manager/sales/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/sales/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Giao dịch Bán hàng</p>
              </a>
            </li>
            <?php if ($user_info['can_sell_controlled']): ?>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>manager/controlled-drugs/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/controlled-drugs/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Nhật ký Thuốc Kiểm soát</p>
              </a>
            </li>
            <?php endif; ?>
           
          </ul>
        </li>

        <!-- Báo cáo -->
        <li class="nav-item">
          <a href="<?php echo BASE_URL; ?>manager/reports/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/reports/') !== false ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>Báo cáo</p>
          </a>
        </li>

        <!-- Phân cách -->
        <li class="nav-header">HỆ THỐNG</li>

        <!-- Cài đặt (chỉ admin) -->
        <?php if ($user_info['role'] === 'admin'): ?>
        <li class="nav-item">
          <a href="<?php echo BASE_URL; ?>manager/settings/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/settings/') !== false ? 'active' : ''; ?>">
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
                        <h1 class="m-0">Quản lý Giao dịch Bán hàng</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>manager/">Trang chủ</a></li>
                            <li class="breadcrumb-item active">Giao dịch bán hàng</li>
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

                <!-- Add/Edit Form -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-<?php echo $edit_sale ? 'edit' : 'plus'; ?>"></i>
                            <?php echo $edit_sale ? 'Sửa giao dịch' : 'Thêm giao dịch mới'; ?>
                        </h3>
                        <?php if ($edit_sale): ?>
                            <div class="card-tools">
                                <a href="<?php echo BASE_URL; ?>manager/sales/" class="btn btn-tool">
                                    <i class="fas fa-times"></i> Hủy
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <form id="saleForm" method="POST" novalidate>
                        <div class="card-body">
                            <?php if ($edit_sale): ?>
                                <input type="hidden" name="sale_id" value="<?php echo $edit_sale['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="sale_code">Mã giao dịch</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="sale_code" name="sale_code" 
                                                   value="<?php echo htmlspecialchars($edit_sale['sale_code'] ?? ''); ?>" 
                                                   placeholder="Để trống để tự động tạo" maxlength="10">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" id="generateCode">
                                                    <i class="fas fa-magic"></i> Tự động
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">
                                            Mã giao dịch tự động theo thứ tự SALE000001, SALE000002... Để trống để hệ thống tự tạo.
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="sale_date">Ngày bán <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="sale_date" name="sale_date" 
                                               value="<?php echo htmlspecialchars($edit_sale['sale_date'] ?? date('Y-m-d')); ?>" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="sale_time">Giờ bán <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" id="sale_time" name="sale_time" 
                                               value="<?php echo htmlspecialchars($edit_sale['sale_time'] ?? date('H:i')); ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="patient_id">Khách hàng (Bệnh nhân)</label>
                                        <select class="form-control" id="patient_id" name="patient_id">
                                            <option value="">-- Chọn bệnh nhân --</option>
                                            <?php foreach ($patients as $patient): ?>
                                                <option value="<?php echo $patient['id']; ?>" 
                                                        <?php echo ($edit_sale && $edit_sale['patient_id'] == $patient['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($patient['full_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="prescription_id">Đơn thuốc</label>
                                        <select class="form-control" id="prescription_id" name="prescription_id">
                                            <option value="">-- Chọn đơn thuốc --</option>
                                            <?php if ($edit_sale && $edit_sale['patient_id']): ?>
                                                <?php
                                                $filtered_prescriptions = array_filter($prescriptions, function($prescription) use ($edit_sale) {
                                                    return $prescription['patient_id'] == $edit_sale['patient_id'];
                                                });
                                                ?>
                                                <?php foreach ($filtered_prescriptions as $prescription): ?>
                                                    <option value="<?php echo $prescription['id']; ?>" 
                                                            <?php echo ($edit_sale && $edit_sale['prescription_id'] == $prescription['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($prescription['prescription_code']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="customer_name">Tên khách hàng (Nếu không chọn bệnh nhân)</label>
                                        <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                               value="<?php echo htmlspecialchars($edit_sale['customer_name'] ?? ''); ?>" 
                                               placeholder="Nhập tên khách hàng">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="customer_phone">Số điện thoại khách hàng</label>
                                        <input type="text" class="form-control" id="customer_phone" name="customer_phone" 
                                               value="<?php echo htmlspecialchars($edit_sale['customer_phone'] ?? ''); ?>" 
                                               placeholder="Nhập số điện thoại">
                                    </div>
                                </div>
                            </div>

                            <!-- Sale Details -->
                            <div class="form-group">
                                <label>Chi tiết giao dịch</label>
                                <div id="sale_details_container">
                                    <?php if ($edit_sale && $edit_sale_details): ?>
                                        <?php foreach ($edit_sale_details as $index => $detail): ?>
                                            <div class="sale-detail-row mb-3 p-3 border rounded">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <label>Thuốc</label>
                                                        <select class="form-control medicine-select" name="sale_details[<?php echo $index; ?>][medicine_id]">
                                                            <option value="">-- Chọn thuốc --</option>
                                                            <?php foreach ($medicines as $medicine): ?>
                                                                <option value="<?php echo $medicine['id']; ?>" 
                                                                        <?php echo $detail['medicine_id'] == $medicine['id'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($medicine['medicine_code'] . ' - ' . $medicine['name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label>Số lượng</label>
                                                        <input type="number" class="form-control quantity-input" 
                                                               name="sale_details[<?php echo $index; ?>][quantity]" 
                                                               value="<?php echo $detail['quantity']; ?>" min="1">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label>Đơn giá</label>
                                                        <input type="number" class="form-control unit-price-input" 
                                                               name="sale_details[<?php echo $index; ?>][unit_price]" 
                                                               value="<?php echo $detail['unit_price']; ?>" step="0.01">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="button" class="btn btn-danger btn-sm remove-detail mt-4">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="sale_details[<?php echo $index; ?>][cost_price]" 
                                                       value="<?php echo $detail['cost_price']; ?>">
                                                <input type="hidden" name="sale_details[<?php echo $index; ?>][discount_amount]" 
                                                       value="<?php echo $detail['discount_amount']; ?>">
                                                <input type="hidden" name="sale_details[<?php echo $index; ?>][total_price]" 
                                                       class="total-price-input" value="<?php echo $detail['total_price']; ?>">
                                                <input type="hidden" name="sale_details[<?php echo $index; ?>][prescription_detail_id]" 
                                                       value="<?php echo $detail['prescription_detail_id']; ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="sale-detail-row mb-3 p-3 border rounded">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label>Thuốc</label>
                                                    <select class="form-control medicine-select" name="sale_details[0][medicine_id]">
                                                        <option value="">-- Chọn thuốc --</option>
                                                        <?php foreach ($medicines as $medicine): ?>
                                                            <option value="<?php echo $medicine['id']; ?>">
                                                                <?php echo htmlspecialchars($medicine['medicine_code'] . ' - ' . $medicine['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label>Số lượng</label>
                                                    <input type="number" class="form-control quantity-input" 
                                                           name="sale_details[0][quantity]" min="1">
                                                </div>
                                                <div class="col-md-3">
                                                    <label>Đơn giá</label>
                                                    <input type="number" class="form-control unit-price-input" 
                                                           name="sale_details[0][unit_price]" step="0.01">
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-danger btn-sm remove-detail mt-4">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <input type="hidden" name="sale_details[0][cost_price]" value="0">
                                            <input type="hidden" name="sale_details[0][discount_amount]" value="0">
                                            <input type="hidden" name="sale_details[0][total_price]" class="total-price-input" value="0">
                                            <input type="hidden" name="sale_details[0][prescription_detail_id]" value="">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-outline-primary mt-2" id="addSaleDetail">
                                    <i class="fas fa-plus"></i> Thêm thuốc
                                </button>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="subtotal">Tổng phụ</label>
                                        <input type="number" class="form-control" id="subtotal" name="subtotal" 
                                               value="<?php echo htmlspecialchars($edit_sale['subtotal'] ?? '0.00'); ?>" 
                                               readonly step="0.01">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="discount_amount">Giảm giá</label>
                                        <input type="number" class="form-control" id="discount_amount" name="discount_amount" 
                                               value="<?php echo htmlspecialchars($edit_sale['discount_amount'] ?? '0.00'); ?>" 
                                               step="0.01">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="tax_amount">Thuế</label>
                                        <input type="number" class="form-control" id="tax_amount" name="tax_amount" 
                                               value="<?php echo htmlspecialchars($edit_sale['tax_amount'] ?? '0.00'); ?>" 
                                               step="0.01">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="total_amount">Tổng cộng</label>
                                        <input type="number" class="form-control" id="total_amount" name="total_amount" 
                                               value="<?php echo htmlspecialchars($edit_sale['total_amount'] ?? '0.00'); ?>" 
                                               readonly step="0.01">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_method">Phương thức thanh toán</label>
                                        <select class="form-control" id="payment_method" name="payment_method">
                                            <option value="cash" <?php echo (!$edit_sale || $edit_sale['payment_method'] == 'cash') ? 'selected' : ''; ?>>Tiền mặt</option>
                                            <option value="card" <?php echo ($edit_sale && $edit_sale['payment_method'] == 'card') ? 'selected' : ''; ?>>Thẻ</option>
                                            <option value="bank_transfer" <?php echo ($edit_sale && $edit_sale['payment_method'] == 'bank_transfer') ? 'selected' : ''; ?>>Chuyển khoản</option>
                                            <option value="insurance" <?php echo ($edit_sale && $edit_sale['payment_method'] == 'insurance') ? 'selected' : ''; ?>>Bảo hiểm</option>
                                            <option value="mixed" <?php echo ($edit_sale && $edit_sale['payment_method'] == 'mixed') ? 'selected' : ''; ?>>Kết hợp</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_status">Trạng thái thanh toán</label>
                                        <select class="form-control" id="payment_status" name="payment_status">
                                            <option value="pending" <?php echo (!$edit_sale || $edit_sale['payment_status'] == 'pending') ? 'selected' : ''; ?>>Chưa thanh toán</option>
                                            <option value="paid" <?php echo ($edit_sale && $edit_sale['payment_status'] == 'paid') ? 'selected' : ''; ?>>Đã thanh toán</option>
                                            <option value="partial" <?php echo ($edit_sale && $edit_sale['payment_status'] == 'partial') ? 'selected' : ''; ?>>Thanh toán một phần</option>
                                            <option value="refunded" <?php echo ($edit_sale && $edit_sale['payment_status'] == 'refunded') ? 'selected' : ''; ?>>Đã hoàn tiền</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status">Trạng thái giao dịch</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="draft" <?php echo (!$edit_sale || $edit_sale['status'] == 'draft') ? 'selected' : ''; ?>>Nháp</option>
                                            <option value="completed" <?php echo ($edit_sale && $edit_sale['status'] == 'completed') ? 'selected' : ''; ?>>Hoàn thành</option>
                                            <option value="cancelled" <?php echo ($edit_sale && $edit_sale['status'] == 'cancelled') ? 'selected' : ''; ?>>Hủy</option>
                                            <option value="returned" <?php echo ($edit_sale && $edit_sale['status'] == 'returned') ? 'selected' : ''; ?>>Trả lại</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="notes">Ghi chú</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($edit_sale['notes'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 
                                <?php echo $edit_sale ? 'Cập nhật' : 'Thêm mới'; ?>
                            </button>
                            <?php if ($edit_sale): ?>
                                <a href="<?php echo BASE_URL; ?>manager/sales/" class="btn btn-secondary ml-2">
                                    <i class="fas fa-times"></i> Hủy
                                </a>
                            <?php else: ?>
                                <button type="reset" class="btn btn-secondary ml-2">
                                    <i class="fas fa-undo"></i> Đặt lại
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Sales List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> Danh sách giao dịch</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="salesTable" class="table table-bordered table-striped">
                                <thead>
    <tr>
        <th>STT</th>
        <th>Mã giao dịch</th>
        <th>Ngày giờ</th>
        <th>Khách hàng</th>
        <th>Tổng tiền</th>
        <th>Phương thức</th>
        <th>Trạng thái</th>
        <th>Nhân viên</th>
        <th>Số mặt hàng</th>
        <th>In hóa đơn</th>
        <th>Thao tác</th>
    </tr>
</thead>
                                <tbody>
    <?php foreach ($sales as $index => $sale): ?>
        <tr>
            <td><?php echo $index + 1; ?></td>
            <td>
                <code><?php echo htmlspecialchars($sale['sale_code']); ?></code>
            </td>
            <td><?php echo htmlspecialchars($sale['sale_date'] . ' ' . $sale['sale_time']); ?></td>
            <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
            <td><?php echo number_format($sale['total_amount'], 2); ?></td>
            <td>
                <span class="badge badge-<?php echo $sale['payment_method'] == 'cash' ? 'success' : ($sale['payment_method'] == 'card' ? 'info' : 'secondary'); ?>">
                    <?php echo htmlspecialchars($sale['payment_method']); ?>
                </span>
            </td>
            <td>
                <span class="badge badge-<?php echo $sale['status'] == 'completed' ? 'success' : ($sale['status'] == 'draft' ? 'warning' : 'danger'); ?>">
                    <?php echo htmlspecialchars($sale['status']); ?>
                </span>
            </td>
            <td><?php echo htmlspecialchars($sale['employee_name']); ?></td>
            <td><?php echo $sale['item_count']; ?></td>
            <!-- THÊM CỘT NÀY -->
            <td>
                <?php if ($sale['status'] == 'completed'): ?>
                    <a href="<?php echo BASE_URL; ?>manager/sales/print_invoice.php?id=<?php echo $sale['sale_id']; ?>" 
                       class="btn btn-info btn-sm" title="In hóa đơn" target="_blank">
                        <i class="fas fa-print"></i>
                    </a>
                <?php else: ?>
                    <button class="btn btn-secondary btn-sm" disabled title="Chỉ có thể in hóa đơn đã hoàn thành">
                        <i class="fas fa-print"></i>
                    </button>
                <?php endif; ?>
            </td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="<?php echo BASE_URL; ?>manager/sales/?action=edit&id=<?php echo $sale['sale_id']; ?>" 
                       class="btn btn-warning" title="Sửa">
                        <i class="fas fa-edit"></i>
                    </a>
                    <?php if ($sale['status'] == 'draft'): ?>
                        <a href="<?php echo BASE_URL; ?>manager/sales/?action=delete&id=<?php echo $sale['sale_id']; ?>" 
                           class="btn btn-danger" title="Xóa"
                           onclick="return confirm('Bạn có chắc chắn muốn xóa giao dịch \'<?php echo htmlspecialchars($sale['sale_code']); ?>\' không?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    <?php else: ?>
                        <button class="btn btn-danger" disabled title="Chỉ có thể xóa giao dịch nháp">
                            <i class="fas fa-trash"></i>
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
<script>
$(document).ready(function() {
    // Xử lý khi click nút in hóa đơn
    $('.btn-print').on('click', function(e) {
        // Có thể thêm loading spinner hoặc confirmation
        var saleCode = $(this).closest('tr').find('code').text();
        console.log('Đang in hóa đơn: ' + saleCode);
    });
});
</script>
<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
<!-- DataTables & Plugins -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.responsive.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>
<!-- Custom Sales JS -->
<script src="<?php echo BASE_URL; ?>manager/sales/js/sales.js"></script>

</body>
</html>