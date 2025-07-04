<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// pharmacist/suppliers/index.php
require_once '../../includes/config.php';
require_once '../../includes/session.php';

// Kiểm tra đăng nhập và quyền truy cập
SessionManager::requireRole(['admin', 'pharmacist']);

// Xử lý các hành động
$action = $_GET['action'] ?? '';
$message = '';
$message_type = '';

// Function to generate next supplier code
function generateSupplierCode($pdo) {
    try {
        // Lấy mã nhà cung cấp có số lớn nhất hiện tại
        $stmt = $pdo->prepare("
            SELECT supplier_code 
            FROM suppliers 
            WHERE supplier_code REGEXP '^SUP[0-9]{4}$' 
            ORDER BY CAST(SUBSTRING(supplier_code, 4) AS UNSIGNED) DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $lastCode = $stmt->fetchColumn();
        
        if ($lastCode) {
            // Tách số từ mã cuối cùng (SUP0001 -> 1)
            $lastNumber = (int)substr($lastCode, 3);
            $nextNumber = $lastNumber + 1;
        } else {
            // Nếu chưa có mã nào, bắt đầu từ 1
            $nextNumber = 1;
        }
        
        // Tạo mã mới với format SUP + 4 chữ số
        return 'SUP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        
    } catch (PDOException $e) {
        error_log("Error generating supplier code: " . $e->getMessage());
        
        // Fallback: tìm số lớn nhất bằng cách khác
        try {
            $stmt = $pdo->prepare("SELECT supplier_code FROM suppliers WHERE supplier_code LIKE 'SUP%' ORDER BY supplier_code DESC");
            $stmt->execute();
            $codes = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $maxNumber = 0;
            foreach ($codes as $code) {
                if (preg_match('/^SUP(\d{4})$/', $code, $matches)) {
                    $number = (int)$matches[1];
                    if ($number > $maxNumber) {
                        $maxNumber = $number;
                    }
                }
            }
            
            return 'SUP' . str_pad($maxNumber + 1, 4, '0', STR_PAD_LEFT);
            
        } catch (PDOException $e2) {
            error_log("Fallback error: " . $e2->getMessage());
            // Last resort: random number
            return 'SUP' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }
}

// Function to validate supplier code format
function validateSupplierCode($code) {
    return preg_match('/^SUP\d{4}$/', $code);
}

// Function to check if supplier code exists
function supplierCodeExists($pdo, $code, $excludeId = null) {
    try {
        if ($excludeId) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM suppliers WHERE supplier_code = ? AND id != ?");
            $stmt->execute([$code, $excludeId]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM suppliers WHERE supplier_code = ?");
            $stmt->execute([$code]);
        }
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking supplier code: " . $e->getMessage());
        return false;
    }
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate phone number (Vietnam format)
function validatePhone($phone) {
    // Remove spaces and special characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    // Check Vietnam phone number format (10-11 digits starting with 0)
    return preg_match('/^0[0-9]{8,10}$/', $phone);
}

// Xử lý thêm/sửa supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log all POST data
    error_log("POST Data: " . print_r($_POST, true));
    
    $supplier_code = trim($_POST['supplier_code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $tax_code = trim($_POST['tax_code'] ?? '');
    $payment_terms = trim($_POST['payment_terms'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $supplier_id = $_POST['supplier_id'] ?? '';
    
    // Debug: Log processed values
    error_log("Processed values - Name: $name, Code: $supplier_code, Status: $status");
    
    // Validate required fields
    if (empty($name)) {
        $message = "Tên nhà cung cấp không được để trống!";
        $message_type = "danger";
        error_log("Validation failed: Name is empty");
    } elseif (!empty($email) && !validateEmail($email)) {
        $message = "Email không hợp lệ!";
        $message_type = "danger";
        error_log("Validation failed: Invalid email");
    } elseif (!empty($phone) && !validatePhone($phone)) {
        $message = "Số điện thoại không hợp lệ! (Định dạng: 0xxxxxxxxx)";
        $message_type = "danger";
        error_log("Validation failed: Invalid phone");
    } else {
        try {
            // Start transaction for data consistency
            $pdo->beginTransaction();
            
            if ($supplier_id) {
                // Cập nhật supplier
                error_log("Updating supplier ID: $supplier_id");
                
                if (!empty($supplier_code)) {
                    // Validate format mã nhà cung cấp nếu có thay đổi
                    if (!validateSupplierCode($supplier_code)) {
                        throw new Exception("Mã nhà cung cấp phải có định dạng SUP + 4 chữ số (VD: SUP0001)!");
                    } elseif (supplierCodeExists($pdo, $supplier_code, $supplier_id)) {
                        throw new Exception("Mã nhà cung cấp đã tồn tại!");
                    }
                } else {
                    // Nếu để trống mã, tự động tạo mã mới
                    $supplier_code = generateSupplierCode($pdo);
                    error_log("Generated new code for update: $supplier_code");
                }
                
                $stmt = $pdo->prepare("
                    UPDATE suppliers 
                    SET supplier_code = ?, name = ?, contact_person = ?, phone = ?, email = ?, 
                        address = ?, tax_code = ?, payment_terms = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $result = $stmt->execute([$supplier_code, $name, $contact_person, $phone, $email, 
                               $address, $tax_code, $payment_terms, $status, $supplier_id]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $pdo->commit();
                    $message = "Cập nhật nhà cung cấp thành công! Mã nhà cung cấp: " . $supplier_code;
                    $message_type = "success";
                    error_log("Supplier updated successfully");
                } else {
                    $pdo->rollback();
                    throw new Exception("Không có thay đổi nào được thực hiện hoặc nhà cung cấp không tồn tại!");
                }
                
            } else {
                // Thêm supplier mới
                error_log("Adding new supplier");
                
                if (empty($supplier_code)) {
                    // Tự động tạo mã nhà cung cấp
                    $supplier_code = generateSupplierCode($pdo);
                    error_log("Generated new code: $supplier_code");
                } else {
                    // Validate format mã nhà cung cấp nhập vào
                    if (!validateSupplierCode($supplier_code)) {
                        throw new Exception("Mã nhà cung cấp phải có định dạng SUP + 4 chữ số (VD: SUP0001)!");
                    } elseif (supplierCodeExists($pdo, $supplier_code)) {
                        // Tự động tạo mã mới thay vì báo lỗi
                        $old_code = $supplier_code;
                        $supplier_code = generateSupplierCode($pdo);
                        error_log("Code $old_code exists, generated new: $supplier_code");
                        $message = "Mã nhà cung cấp đã tồn tại! Đã tự động tạo mã mới: " . $supplier_code;
                        $message_type = "warning";
                    }
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO suppliers (supplier_code, name, contact_person, phone, email, 
                                         address, tax_code, payment_terms, status, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $result = $stmt->execute([$supplier_code, $name, $contact_person, $phone, $email, 
                               $address, $tax_code, $payment_terms, $status]);
                
                if ($result) {
                    $new_id = $pdo->lastInsertId();
                    $pdo->commit();
                    
                    if ($message_type === 'warning') {
                        $message = "Mã nhà cung cấp đã tồn tại! Đã tự động tạo mã mới: " . $supplier_code;
                    } else {
                        $message = "Thêm nhà cung cấp thành công! Mã nhà cung cấp: " . $supplier_code;
                    }
                    $message_type = "success";
                    error_log("Supplier added successfully with ID: $new_id");
                } else {
                    $pdo->rollback();
                    throw new Exception("Không thể thêm nhà cung cấp vào cơ sở dữ liệu!");
                }
            }
            
        } catch (PDOException $e) {
            $pdo->rollback();
            error_log("Database error: " . $e->getMessage());
            
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                // Nếu vẫn bị trùng (race condition), tạo mã mới và thử lại
                try {
                    $pdo->beginTransaction();
                    $supplier_code = generateSupplierCode($pdo);
                    error_log("Retry with new code: $supplier_code");
                    
                    if ($supplier_id) {
                        $stmt = $pdo->prepare("
                            UPDATE suppliers 
                            SET supplier_code = ?, name = ?, contact_person = ?, phone = ?, email = ?, 
                                address = ?, tax_code = ?, payment_terms = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                            WHERE id = ?
                        ");
                        $stmt->execute([$supplier_code, $name, $contact_person, $phone, $email, 
                                       $address, $tax_code, $payment_terms, $status, $supplier_id]);
                        $message = "Cập nhật nhà cung cấp thành công! Mã nhà cung cấp: " . $supplier_code;
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO suppliers (supplier_code, name, contact_person, phone, email, 
                                                 address, tax_code, payment_terms, status, created_at, updated_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                        ");
                        $stmt->execute([$supplier_code, $name, $contact_person, $phone, $email, 
                                       $address, $tax_code, $payment_terms, $status]);
                        $message = "Thêm nhà cung cấp thành công! Mã nhà cung cấp: " . $supplier_code;
                    }
                    $pdo->commit();
                    $message_type = "success";
                } catch (PDOException $e2) {
                    $pdo->rollback();
                    error_log("Retry failed: " . $e2->getMessage());
                    $message = "Lỗi hệ thống: " . $e2->getMessage();
                    $message_type = "danger";
                }
            } else {
                $message = "Lỗi cơ sở dữ liệu: " . $e->getMessage();
                $message_type = "danger";
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollback();
            }
            error_log("General error: " . $e->getMessage());
            $message = $e->getMessage();
            $message_type = "danger";
        }
    }
    
    // Redirect to prevent form resubmission
    if ($message_type === 'success') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $message_type;
        header('Location: ' . BASE_URL . 'pharmacist/suppliers/');
        exit();
    }
}

// Check for flash messages
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Xử lý xóa supplier
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $pdo->beginTransaction();
        
        // Kiểm tra supplier có tồn tại không
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        $supplier_exists = $stmt->fetchColumn();
        
        if ($supplier_exists == 0) {
            $message = "Không tìm thấy nhà cung cấp để xóa!";
            $message_type = "danger";
        } else {
            // Kiểm tra xem có thuốc nào sử dụng supplier này không
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM medicines WHERE default_supplier_id = ?");
            $stmt->execute([$id]);
            $medicine_count = $stmt->fetchColumn();
            
            if ($medicine_count > 0) {
                $message = "Không thể xóa nhà cung cấp này vì đang có {$medicine_count} thuốc sử dụng nhà cung cấp này!";
                $message_type = "danger";
            } else {
                // Thực hiện xóa supplier
                $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
                $result = $stmt->execute([$id]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $pdo->commit();
                    $_SESSION['flash_message'] = "Xóa nhà cung cấp thành công!";
                    $_SESSION['flash_type'] = "success";
                } else {
                    $pdo->rollback();
                    $message = "Không thể xóa nhà cung cấp!";
                    $message_type = "danger";
                }
            }
        }
    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Delete error: " . $e->getMessage());
        $message = "Lỗi: " . $e->getMessage();
        $message_type = "danger";
    }
    
    // Redirect after delete
    if (!$message) {
        header('Location: ' . BASE_URL . 'pharmacist/suppliers/');
        exit();
    }
}

// Lấy danh sách suppliers với số lượng thuốc
try {
    $stmt = $pdo->prepare("
        SELECT s.*, 
               COALESCE(COUNT(m.id), 0) as medicine_count
        FROM suppliers s
        LEFT JOIN medicines m ON s.id = m.default_supplier_id
        GROUP BY s.id
        ORDER BY CAST(SUBSTRING(s.supplier_code, 4) AS UNSIGNED)
    ");
    $stmt->execute();
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $suppliers = [];
    error_log("Error fetching suppliers: " . $e->getMessage());
}

// Lấy thông tin supplier để edit
$edit_supplier = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        $edit_supplier = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$edit_supplier) {
            $_SESSION['flash_message'] = "Không tìm thấy nhà cung cấp để chỉnh sửa!";
            $_SESSION['flash_type'] = "danger";
            header('Location: ' . BASE_URL . 'pharmacist/suppliers/');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error fetching supplier for edit: " . $e->getMessage());
        $_SESSION['flash_message'] = "Lỗi khi tải thông tin nhà cung cấp!";
        $_SESSION['flash_type'] = "danger";
        header('Location: ' . BASE_URL . 'pharmacist/suppliers/');
        exit();
    }
}

$user_info = SessionManager::getUserInfo();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý Nhà cung cấp - Pharmacy Management System</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/dataTables.bootstrap4.min.css">
    <!-- AdminLTE Theme style -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
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
                <a href="<?php echo BASE_URL; ?>pharmacist/" class="nav-link">Trang chủ</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?php echo BASE_URL; ?>pharmacist/suppliers/" class="nav-link">Nhà cung cấp</a>
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
        <a href="<?php echo BASE_URL; ?>pharmacist/" class="brand-link">
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
          <a href="<?php echo BASE_URL; ?>pharmacist/" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
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
              <a href="<?php echo BASE_URL; ?>pharmacist/medicines/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/medicines/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Thuốc</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>pharmacist/categories/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/categories/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Danh mục</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>pharmacist/suppliers/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/suppliers/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Nhà cung cấp</p>
              </a>
            </li>
            <?php if ($user_info['role'] === 'admin'): ?>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>pharmacist/users/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/users/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Người dùng</p>
              </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>pharmacist/inventory/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/inventory/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Lô hàng</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>pharmacist/prescriptions/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/prescriptions/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Đơn thuốc</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>pharmacist/patients/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/patients/') !== false ? 'active' : ''; ?>">
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
              <a href="<?php echo BASE_URL; ?>pharmacist/sales/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/sales/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Giao dịch Bán hàng</p>
              </a>
            </li>
            <?php if ($user_info['can_sell_controlled']): ?>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>pharmacist/controlled-drugs/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/controlled-drugs/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Nhật ký Thuốc Kiểm soát</p>
              </a>
            </li>
            <?php endif; ?>
           
          </ul>
        </li>

        <!-- Báo cáo -->
        <li class="nav-item">
          <a href="<?php echo BASE_URL; ?>pharmacist/reports/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/reports/') !== false ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>Báo cáo</p>
          </a>
        </li>

        <!-- Phân cách -->
        <li class="nav-header">HỆ THỐNG</li>

        <!-- Cài đặt (chỉ admin) -->
        <?php if ($user_info['role'] === 'admin'): ?>
        <li class="nav-item">
          <a href="<?php echo BASE_URL; ?>pharmacist/settings/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/settings/') !== false ? 'active' : ''; ?>">
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
                        <h1 class="m-0">Quản lý Nhà cung cấp</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>pharmacist/">Trang chủ</a></li>
                            <li class="breadcrumb-item active">Nhà cung cấp</li>
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
                            <i class="fas fa-<?php echo $edit_supplier ? 'edit' : 'plus'; ?>"></i>
                            <?php echo $edit_supplier ? 'Sửa nhà cung cấp' : 'Thêm nhà cung cấp mới'; ?>
                        </h3>
                        <?php if ($edit_supplier): ?>
                            <div class="card-tools">
                                <a href="<?php echo BASE_URL; ?>pharmacist/suppliers/" class="btn btn-tool">
                                    <i class="fas fa-times"></i> Hủy
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" id="supplierForm">
                        <div class="card-body">
                            <?php if ($edit_supplier): ?>
                                <input type="hidden" name="supplier_id" value="<?php echo $edit_supplier['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="supplier_code">Mã nhà cung cấp</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="supplier_code" name="supplier_code" 
                                                   value="<?php echo htmlspecialchars($edit_supplier['supplier_code'] ?? ''); ?>" 
                                                   placeholder="Để trống để tự động tạo" maxlength="7">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" id="generateCode">
                                                    <i class="fas fa-magic"></i> Tự động
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">
                                            Mã nhà cung cấp tự động theo thứ tự SUP0001, SUP0002... Để trống để hệ thống tự tạo.
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Tên nhà cung cấp <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($edit_supplier['name'] ?? ''); ?>" 
                                               placeholder="Nhập tên nhà cung cấp" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="contact_person">Người liên hệ</label>
                                        <input type="text" class="form-control" id="contact_person" name="contact_person" 
                                               value="<?php echo htmlspecialchars($edit_supplier['contact_person'] ?? ''); ?>" 
                                               placeholder="Nhập tên người liên hệ">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Số điện thoại</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($edit_supplier['phone'] ?? ''); ?>" 
                                               placeholder="Ví dụ: 0123456789">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($edit_supplier['email'] ?? ''); ?>" 
                                               placeholder="Nhập email">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tax_code">Mã số thuế</label>
                                        <input type="text" class="form-control" id="tax_code" name="tax_code" 
                                               value="<?php echo htmlspecialchars($edit_supplier['tax_code'] ?? ''); ?>" 
                                               placeholder="Nhập mã số thuế">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address">Địa chỉ</label>
                                <textarea class="form-control" id="address" name="address" rows="3" placeholder="Nhập địa chỉ"><?php echo htmlspecialchars($edit_supplier['address'] ?? ''); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_terms">Điều khoản thanh toán</label>
                                        <input type="text" class="form-control" id="payment_terms" name="payment_terms" 
                                               value="<?php echo htmlspecialchars($edit_supplier['payment_terms'] ?? ''); ?>" 
                                               placeholder="Ví dụ: 30 ngày">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status">Trạng thái</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="active" <?php echo (!$edit_supplier || $edit_supplier['status'] === 'active') ? 'selected' : ''; ?>>Hoạt động</option>
                                            <option value="inactive" <?php echo ($edit_supplier && $edit_supplier['status'] === 'inactive') ? 'selected' : ''; ?>>Ngừng hoạt động</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $edit_supplier ? 'Cập nhật' : 'Thêm mới'; ?>
                            </button>
                            <?php if ($edit_supplier): ?>
                                <a href="<?php echo BASE_URL; ?>pharmacist/suppliers/" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Hủy
                                </a>
                            <?php else: ?>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i> Làm mới
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Suppliers List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list"></i> Danh sách nhà cung cấp
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-info">Tổng: <?php echo count($suppliers); ?> nhà cung cấp</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="suppliersTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Mã NCC</th>
                                        <th>Tên nhà cung cấp</th>
                                        <th>Người liên hệ</th>
                                        <th>Điện thoại</th>
                                        <th>Email</th>
                                        <th>Trạng thái</th>
                                        <th>Số thuốc</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($suppliers)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center">
                                                <i class="fas fa-inbox"></i> Chưa có nhà cung cấp nào
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $stt = 1; foreach ($suppliers as $supplier): ?>
                                            <tr>
                                                <td><?php echo $stt++; ?></td>
                                                <td>
                                                    <span class="badge badge-secondary"><?php echo htmlspecialchars($supplier['supplier_code']); ?></span>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($supplier['name']); ?></strong>
                                                    <?php if (!empty($supplier['address'])): ?>
                                                        <br><small class="text-muted">
                                                            <i class="fas fa-map-marker-alt"></i> 
                                                            <?php echo htmlspecialchars(mb_substr($supplier['address'], 0, 50)); ?>
                                                            <?php echo mb_strlen($supplier['address']) > 50 ? '...' : ''; ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($supplier['contact_person'] ?: 'Chưa có'); ?></td>
                                                <td>
                                                    <?php if (!empty($supplier['phone'])): ?>
                                                        <a href="tel:<?php echo $supplier['phone']; ?>">
                                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($supplier['phone']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">Chưa có</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($supplier['email'])): ?>
                                                        <a href="mailto:<?php echo $supplier['email']; ?>">
                                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($supplier['email']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">Chưa có</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($supplier['status'] === 'active'): ?>
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-check-circle"></i> Hoạt động
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">
                                                            <i class="fas fa-times-circle"></i> Ngừng hoạt động
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($supplier['medicine_count'] > 0): ?>
                                                        <span class="badge badge-info">
                                                            <i class="fas fa-pills"></i> <?php echo $supplier['medicine_count']; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">0</span>
                                                    <?php endif; ?>
                                                </td>
                                                        <?php if ($supplier['medicine_count'] == 0): ?>
                                                            <button type="button" class="btn btn-sm btn-danger delete-supplier" 
                                                                    data-id="<?php echo $supplier['id']; ?>" 
                                                                    data-name="<?php echo htmlspecialchars($supplier['name']); ?>"
                                                                    title="Xóa">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-secondary" 
                                                                    title="Không thể xóa - có <?php echo $supplier['medicine_count']; ?> thuốc" disabled>
                                                                <i class="fas fa-lock"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                <td>
                                                    <small>
                                                        <?php 
                                                        $created_date = new DateTime($supplier['created_at']);
                                                        echo $created_date->format('d/m/Y H:i'); 
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="<?php echo BASE_URL; ?>pharmacist/suppliers/?action=edit&id=<?php echo $supplier['id']; ?>" 
                                                           class="btn btn-sm btn-warning" title="Sửa">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($supplier['medicine_count'] == 0): ?>
                                                            <button type="button" class="btn btn-sm btn-danger delete-supplier" 
                                                                    data-id="<?php echo $supplier['id']; ?>" 
                                                                    data-name="<?php echo htmlspecialchars($supplier['name']); ?>"
                                                                    title="Xóa">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-secondary" 
                                                                    title="Không thể xóa - có đơn hàng" disabled>
                                                                <i class="fas fa-lock"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <strong>Copyright &copy; 2024 <a href="#">Pharmacy Management System</a>.</strong>
        All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> 1.0.0
        </div>
    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark"></aside>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning"></i> Xác nhận xóa
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa nhà cung cấp <strong id="supplierName"></strong>?</p>
                <p class="text-danger"><small><i class="fas fa-warning"></i> Hành động này không thể hoàn tác!</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <a href="#" id="confirmDelete" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Xóa
                </a>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.bootstrap4.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>

<script>
$(function () {
    // Initialize DataTable
    $('#suppliersTable').DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "pageLength": 25,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
        },
        "order": [[1, "asc"]], // Sort by supplier code
        "columnDefs": [
            { "orderable": false, "targets": [9] } // Disable sorting for action column
        ]
    });

    // Auto-generate supplier code
    $('#generateCode').click(function() {
        $('#supplier_code').val('');
        $('#supplier_code').attr('placeholder', 'Mã sẽ được tự động tạo khi lưu');
    });

    // Format supplier code input
    $('#supplier_code').on('input', function() {
        let value = $(this).val().toUpperCase();
        // Remove non-alphanumeric characters except SUP prefix
        if (value.startsWith('SUP')) {
            value = 'SUP' + value.substring(3).replace(/[^0-9]/g, '');
        } else {
            value = value.replace(/[^A-Z0-9]/g, '');
        }
        // Limit to 7 characters (SUP + 4 digits)
        if (value.length > 7) {
            value = value.substring(0, 7);
        }
        $(this).val(value);
    });

    // Phone number formatting
    $('#phone').on('input', function() {
        let value = $(this).val().replace(/[^0-9]/g, '');
        if (value.length > 11) {
            value = value.substring(0, 11);
        }
        $(this).val(value);
    });

    // Form validation
    $('#supplierForm').submit(function(e) {
        let isValid = true;
        let errors = [];

        // Validate name
        if ($('#name').val().trim() === '') {
            errors.push('Tên nhà cung cấp không được để trống');
            $('#name').addClass('is-invalid');
            isValid = false;
        } else {
            $('#name').removeClass('is-invalid');
        }

        // Validate supplier code format if provided
        let supplierCode = $('#supplier_code').val().trim();
        if (supplierCode !== '' && !/^SUP\d{4}$/.test(supplierCode)) {
            errors.push('Mã nhà cung cấp phải có định dạng SUP + 4 chữ số (VD: SUP0001)');
            $('#supplier_code').addClass('is-invalid');
            isValid = false;
        } else {
            $('#supplier_code').removeClass('is-invalid');
        }

        // Validate email if provided
        let email = $('#email').val().trim();
        if (email !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            errors.push('Email không hợp lệ');
            $('#email').addClass('is-invalid');
            isValid = false;
        } else {
            $('#email').removeClass('is-invalid');
        }

        // Validate phone if provided
        let phone = $('#phone').val().trim();
        if (phone !== '' && !/^0[0-9]{8,10}$/.test(phone)) {
            errors.push('Số điện thoại không hợp lệ (định dạng: 0xxxxxxxxx)');
            $('#phone').addClass('is-invalid');
            isValid = false;
        } else {
            $('#phone').removeClass('is-invalid');
        }

        if (!isValid) {
            e.preventDefault();
            let errorMessage = 'Vui lòng kiểm tra lại thông tin:\n• ' + errors.join('\n• ');
            alert(errorMessage);
        }
    });

    // Delete supplier
    $('.delete-supplier').click(function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        
        $('#supplierName').text(name);
        $('#confirmDelete').attr('href', '<?php echo BASE_URL; ?>pharmacist/suppliers/?action=delete&id=' + id);
        $('#deleteModal').modal('show');
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Tooltip initialization
    $('[title]').tooltip();
});
</script>

</body>
</html>
                