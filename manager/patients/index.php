<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// manager/suppliers/index.php
require_once '../../includes/config.php';
require_once '../../includes/session.php';

// Kiểm tra đăng nhập và quyền truy cập
SessionManager::requireRole(['admin', 'manager']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản Lý Bệnh Nhân - Pharmacy Management System</title>
    
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
    
    <style>
        .content-wrapper {
            margin-left: 250px;
        }
        @media (max-width: 767px) {
            .content-wrapper {
                margin-left: 0;
            }
        }
        .table-responsive {
            overflow-x: auto;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: 0.2rem;
        }
        .modal-lg {
            max-width: 800px;
        }
        .required {
            color: #dc3545;
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
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#" class="nav-link">Trang chủ</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            
            <li class="nav-item">
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="#" class="brand-link">
            <img src="https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png" alt="Pharmacy Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">Pharmacy Management</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="https://adminlte.io/themes/v3/dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="#" class="d-block"><?php echo htmlspecialchars($user_info['full_name']); ?></a>
                </div>
            </div>

            <!-- Sidebar Menu -->
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
                        <h1 class="m-0">Quản Lý Bệnh Nhân</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                            <li class="breadcrumb-item active">Quản Lý Bệnh Nhân</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Alerts -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="icon fas fa-check"></i>
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="icon fas fa-ban"></i>
                        <?php echo nl2br($_SESSION['error']); unset($_SESSION['error']); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Main row -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Danh Sách Bệnh Nhân</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addPatientModal">
                                        <i class="fas fa-plus"></i> Thêm Bệnh Nhân
                                    </button>
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="patientsTable" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Mã BN</th>
                                                <th>Họ Tên</th>
                                                <th>CMND/CCCD</th>
                                                <th>Số ĐT</th>
                                                <th>Email</th>
                                                <th>Địa Chỉ</th>
                                                <th>Ngày Sinh</th>
                                                <th>Giới Tính</th>
                                                <th>Thao Tác</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            try {
                                                $stmt = $pdo->query("SELECT id, patient_code, full_name, id_number, phone, email, address, date_of_birth, gender, emergency_contact, allergies, medical_notes, status FROM patients ORDER BY created_at DESC");
                                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                    echo "<tr>";
                                                    echo "<td>" . htmlspecialchars($row['patient_code']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['id_number'] ?? '-') . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['phone'] ?? '-') . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['email'] ?? '-') . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['address'] ?? '-') . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['date_of_birth'] ? date('d/m/Y', strtotime($row['date_of_birth'])) : '-') . "</td>";
                                                    echo "<td>" . htmlspecialchars(['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'][$row['gender']] ?? '-') . "</td>";
                                                    echo "<td>
                                                        <button class='btn btn-sm btn-warning edit-patient' data-toggle='modal' data-target='#editPatientModal'
                                                            data-id='" . htmlspecialchars($row['id']) . "'
                                                            data-patient_code='" . htmlspecialchars($row['patient_code']) . "'
                                                            data-full_name='" . htmlspecialchars($row['full_name']) . "'
                                                            data-id_number='" . htmlspecialchars($row['id_number'] ?? '') . "'
                                                            data-phone='" . htmlspecialchars($row['phone'] ?? '') . "'
                                                            data-email='" . htmlspecialchars($row['email'] ?? '') . "'
                                                            data-address='" . htmlspecialchars($row['address'] ?? '') . "'
                                                            data-date_of_birth='" . htmlspecialchars($row['date_of_birth'] ?? '') . "'
                                                            data-gender='" . htmlspecialchars($row['gender'] ?? '') . "'
                                                            data-emergency_contact='" . htmlspecialchars($row['emergency_contact'] ?? '') . "'
                                                            data-allergies='" . htmlspecialchars($row['allergies'] ?? '') . "'
                                                            data-medical_notes='" . htmlspecialchars($row['medical_notes'] ?? '') . "'
                                                            data-status='" . htmlspecialchars($row['status'] ?? 'active') . "'>
                                                            <i class='fas fa-edit'></i>
                                                        </button>
                                                        <button class='btn btn-sm btn-danger delete-patient' data-id='" . htmlspecialchars($row['id']) . "' data-full_name='" . htmlspecialchars($row['full_name']) . "'>
                                                            <i class='fas fa-trash'></i>
                                                        </button>
                                                    </td>";
                                                    echo "</tr>";
                                                }
                                            } catch (PDOException $e) {
                                                echo "<tr><td colspan='9' class='text-center text-danger'>Lỗi khi tải danh sách bệnh nhân: " . $e->getMessage() . "</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
                </div>
                <!-- /.row -->
            </div>
            <!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Main Footer -->
    <footer class="main-footer">
        <strong>Copyright &copy; 2025 <a href="#">Pharmacy Management</a>.</strong>
        All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> 1.0.0
        </div>
    </footer>
</div>
<!-- ./wrapper -->

<!-- Add Patient Modal -->
<div class="modal fade" id="addPatientModal" tabindex="-1" role="dialog" aria-labelledby="addPatientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="addPatientForm" action="add_patient.php" method="POST">
                <div class="modal-header">
                    <h4 class="modal-title" id="addPatientModalLabel">
                        <i class="fas fa-user-plus"></i> Thêm Bệnh Nhân
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="full_name">Họ và Tên <span class="required">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="id_number">CMND/CCCD</label>
                                <input type="text" class="form-control" id="id_number" name="id_number">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">Số Điện Thoại</label>
                                <input type="text" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="address">Địa Chỉ</label>
                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date_of_birth">Ngày Sinh</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gender">Giới Tính</label>
                                <select class="form-control" id="gender" name="gender">
                                    <option value="">Chọn giới tính</option>
                                    <option value="male">Nam</option>
                                    <option value="female">Nữ</option>
                                    <option value="other">Khác</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="emergency_contact">Liên Hệ Khẩn Cấp</label>
                        <input type="text" class="form-control" id="emergency_contact" name="emergency_contact">
                    </div>
                    <div class="form-group">
                        <label for="allergies">Dị Ứng</label>
                        <textarea class="form-control" id="allergies" name="allergies" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="medical_notes">Ghi Chú Y Tế</label>
                        <textarea class="form-control" id="medical_notes" name="medical_notes" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="status">Trạng Thái</label>
                        <select class="form-control" id="status" name="status">
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Không hoạt động</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Thêm Bệnh Nhân
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Patient Modal -->
<div class="modal fade" id="editPatientModal" tabindex="-1" role="dialog" aria-labelledby="editPatientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="editPatientForm" action="edit_patient.php" method="POST">
                <div class="modal-header">
                    <h4 class="modal-title" id="editPatientModalLabel">
                        <i class="fas fa-user-edit"></i> Sửa Bệnh Nhân
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="form-group">
                        <label for="edit_patient_code">Mã Bệnh Nhân</label>
                        <input type="text" class="form-control" id="edit_patient_code" name="patient_code" readonly>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_full_name">Họ và Tên <span class="required">*</span></label>
                                <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_id_number">CMND/CCCD</label>
                                <input type="text" class="form-control" id="edit_id_number" name="id_number">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_phone">Số Điện Thoại</label>
                                <input type="text" class="form-control" id="edit_phone" name="phone">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_email">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_address">Địa Chỉ</label>
                        <textarea class="form-control" id="edit_address" name="address" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_date_of_birth">Ngày Sinh</label>
                                <input type="date" class="form-control" id="edit_date_of_birth" name="date_of_birth">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_gender">Giới Tính</label>
                                <select class="form-control" id="edit_gender" name="gender">
                                    <option value="">Chọn giới tính</option>
                                    <option value="male">Nam</option>
                                    <option value="female">Nữ</option>
                                    <option value="other">Khác</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_emergency_contact">Liên Hệ Khẩn Cấp</label>
                        <input type="text" class="form-control" id="edit_emergency_contact" name="emergency_contact">
                    </div>
                    <div class="form-group">
                        <label for="edit_allergies">Dị Ứng</label>
                        <textarea class="form-control" id="edit_allergies" name="allergies" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_medical_notes">Ghi Chú Y Tế</label>
                        <textarea class="form-control" id="edit_medical_notes" name="medical_notes" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Trạng Thái</label>
                        <select class="form-control" id="edit_status" name="status">
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Không hoạt động</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu Thay Đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">
                    <i class="fas fa-exclamation-triangle text-danger"></i> Xác Nhận Xóa
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa bệnh nhân <strong id="deletePatientName"></strong>?</p>
                <p class="text-danger"><small>Hành động này không thể hoàn tác!</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Xóa
                </button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.1/js/bootstrap.bundle.min.js"></script>
<!-- DataTables  & Plugins -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<!-- overlayScrollbars -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/overlayscrollbars/1.13.1/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#patientsTable').DataTable({
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
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [8] },
            { width: "120px", targets: [8] }
        ]
    });

    // Clear form when add modal is shown
    $('#addPatientModal').on('shown.bs.modal', function () {
        $('#addPatientForm')[0].reset();
        $('#full_name').focus();
    });

    // Populate edit modal
    $('.edit-patient').on('click', function() {
        $('#edit_id').val($(this).data('id'));
        $('#edit_patient_code').val($(this).data('patient_code'));
        $('#edit_full_name').val($(this).data('full_name'));
        $('#edit_id_number').val($(this).data('id_number') || '');
        $('#edit_phone').val($(this).data('phone') || '');
        $('#edit_email').val($(this).data('email') || '');
        $('#edit_address').val($(this).data('address') || '');
        $('#edit_date_of_birth').val($(this).data('date_of_birth') || '');
        $('#edit_gender').val($(this).data('gender') || '');
        $('#edit_emergency_contact').val($(this).data('emergency_contact') || '');
        $('#edit_allergies').val($(this).data('allergies') || '');
        $('#edit_medical_notes').val($(this).data('medical_notes') || '');
        $('#edit_status').val($(this).data('status') || 'active');
    });

    // Handle delete button click
    $('.delete-patient').on('click', function() {
        var patientId = $(this).data('id');
        var patientName = $(this).data('full_name');
        
        $('#deletePatientName').text(patientName);
        $('#deleteConfirmModal').modal('show');
        
        $('#confirmDeleteBtn').off('click').on('click', function() {
            // Create form to submit delete request
            var form = $('<form>', {
                'method': 'POST',
                'action': 'delete_patient.php'
            });
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'id',
                'value': patientId
            }));
            
            $('body').append(form);
            form.submit();
        });
    });

    // Form validation
    $('#addPatientForm').on('submit', function(e) {
        var fullName = $('#full_name').val().trim();
        if (!fullName) {
            e.preventDefault();
            alert('Vui lòng nhập họ và tên bệnh nhân.');
            $('#full_name').focus();
            return false;
        }
        
        // Validate email format if provided
        var email = $('#email').val().trim();
        if (email && !isValidEmail(email)) {
            e.preventDefault();
            alert('Vui lòng nhập email hợp lệ.');
            $('#email').focus();
            return false;
        }
        
        // Validate phone number if provided
        var phone = $('#phone').val().trim();
        if (phone && !isValidPhone(phone)) {
            e.preventDefault();
            alert('Vui lòng nhập số điện thoại hợp lệ.');
            $('#phone').focus();
            return false;
        }
    });

    $('#editPatientForm').on('submit', function(e) {
        var fullName = $('#edit_full_name').val().trim();
        if (!fullName) {
            e.preventDefault();
            alert('Vui lòng nhập họ và tên bệnh nhân.');
            $('#edit_full_name').focus();
            return false;
        }
        
        // Validate email format if provided
        var email = $('#edit_email').val().trim();
        if (email && !isValidEmail(email)) {
            e.preventDefault();
            alert('Vui lòng nhập email hợp lệ.');
            $('#edit_email').focus();
            return false;
        }
        
        // Validate phone number if provided
        var phone = $('#edit_phone').val().trim();
        if (phone && !isValidPhone(phone)) {
            e.preventDefault();
            alert('Vui lòng nhập số điện thoại hợp lệ.');
            $('#edit_phone').focus();
            return false;
        }
    });

    // Helper functions for validation
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function isValidPhone(phone) {
        var phoneRegex = /^[0-9\-\+\s\(\)]{10,15}$/;
        return phoneRegex.test(phone);
    }

    // Auto-dismiss alerts after 5 seconds
    $('.alert').each(function() {
        var alert = $(this);
        setTimeout(function() {
            alert.fadeOut('slow');
        }, 5000);
    });

    // Refresh DataTable when modal is closed after successful operation
    $('#addPatientModal, #editPatientModal').on('hidden.bs.modal', function() {
        // Check if there's a success message, then reload the page
        if ($('.alert-success').length > 0) {
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
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