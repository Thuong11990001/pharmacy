<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// manager/categories/index.php
require_once '../../includes/config.php';
require_once '../../includes/session.php';

// Kiểm tra đăng nhập và quyền truy cập
SessionManager::requireRole(['admin', 'manager']);

// Xử lý các hành động
$action = $_GET['action'] ?? '';
$message = '';
$message_type = '';

// Function to generate next category code
function generateCategoryCode($pdo) {
    try {
        // Lấy mã danh mục có số lớn nhất hiện tại
        $stmt = $pdo->prepare("
            SELECT category_code 
            FROM categories 
            WHERE category_code REGEXP '^CAT[0-9]{4}$' 
            ORDER BY CAST(SUBSTRING(category_code, 4) AS UNSIGNED) DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $lastCode = $stmt->fetchColumn();
        
        if ($lastCode) {
            // Tách số từ mã cuối cùng (CAT0001 -> 1)
            $lastNumber = (int)substr($lastCode, 3);
            $nextNumber = $lastNumber + 1;
        } else {
            // Nếu chưa có mã nào, bắt đầu từ 1
            $nextNumber = 1;
        }
        
        // Tạo mã mới với format CAT + 4 chữ số
        return 'CAT' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        
    } catch (PDOException $e) {
        error_log("Error generating category code: " . $e->getMessage());
        
        // Fallback: tìm số lớn nhất bằng cách khác
        try {
            $stmt = $pdo->prepare("SELECT category_code FROM categories WHERE category_code LIKE 'CAT%' ORDER BY category_code DESC");
            $stmt->execute();
            $codes = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $maxNumber = 0;
            foreach ($codes as $code) {
                if (preg_match('/^CAT(\d{4})$/', $code, $matches)) {
                    $number = (int)$matches[1];
                    if ($number > $maxNumber) {
                        $maxNumber = $number;
                    }
                }
            }
            
            return 'CAT' . str_pad($maxNumber + 1, 4, '0', STR_PAD_LEFT);
            
        } catch (PDOException $e2) {
            error_log("Fallback error: " . $e2->getMessage());
            // Last resort: random number
            return 'CAT' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }
}

// Function to validate category code format
function validateCategoryCode($code) {
    return preg_match('/^CAT\d{4}$/', $code);
}

// Function to check if category code exists
function categoryCodeExists($pdo, $code, $excludeId = null) {
    try {
        if ($excludeId) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE category_code = ? AND id != ?");
            $stmt->execute([$code, $excludeId]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE category_code = ?");
            $stmt->execute([$code]);
        }
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking category code: " . $e->getMessage());
        return false;
    }
}

// Xử lý thêm/sửa category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log all POST data
    error_log("POST Data: " . print_r($_POST, true));
    
    $category_code = trim($_POST['category_code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $requires_prescription = isset($_POST['requires_prescription']) ? 1 : 0;
    $is_controlled = isset($_POST['is_controlled']) ? 1 : 0;
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $status = $_POST['status'] ?? 'active';
    $category_id = $_POST['category_id'] ?? '';
    
    // Debug: Log processed values
    error_log("Processed values - Name: $name, Code: $category_code, Status: $status");
    
    // Validate required fields
    if (empty($name)) {
        $message = "Tên danh mục không được để trống!";
        $message_type = "danger";
        error_log("Validation failed: Name is empty");
    } else {
        try {
            // Start transaction for data consistency
            $pdo->beginTransaction();
            
            if ($category_id) {
                // Cập nhật category
                error_log("Updating category ID: $category_id");
                
                if (!empty($category_code)) {
                    // Validate format mã danh mục nếu có thay đổi
                    if (!validateCategoryCode($category_code)) {
                        throw new Exception("Mã danh mục phải có định dạng CAT + 4 chữ số (VD: CAT0001)!");
                    } elseif (categoryCodeExists($pdo, $category_code, $category_id)) {
                        throw new Exception("Mã danh mục đã tồn tại!");
                    }
                } else {
                    // Nếu để trống mã, tự động tạo mã mới
                    $category_code = generateCategoryCode($pdo);
                    error_log("Generated new code for update: $category_code");
                }
                
                $stmt = $pdo->prepare("
                    UPDATE categories 
                    SET category_code = ?, name = ?, description = ?, requires_prescription = ?, 
                        is_controlled = ?, parent_id = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $result = $stmt->execute([$category_code, $name, $description, $requires_prescription, 
                               $is_controlled, $parent_id, $status, $category_id]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $pdo->commit();
                    $message = "Cập nhật danh mục thành công! Mã danh mục: " . $category_code;
                    $message_type = "success";
                    error_log("Category updated successfully");
                } else {
                    $pdo->rollback();
                    throw new Exception("Không có thay đổi nào được thực hiện hoặc category không tồn tại!");
                }
                
            } else {
                // Thêm category mới
                error_log("Adding new category");
                
                if (empty($category_code)) {
                    // Tự động tạo mã danh mục
                    $category_code = generateCategoryCode($pdo);
                    error_log("Generated new code: $category_code");
                } else {
                    // Validate format mã danh mục nhập vào
                    if (!validateCategoryCode($category_code)) {
                        throw new Exception("Mã danh mục phải có định dạng CAT + 4 chữ số (VD: CAT0001)!");
                    } elseif (categoryCodeExists($pdo, $category_code)) {
                        // Tự động tạo mã mới thay vì báo lỗi
                        $old_code = $category_code;
                        $category_code = generateCategoryCode($pdo);
                        error_log("Code $old_code exists, generated new: $category_code");
                        $message = "Mã danh mục đã tồn tại! Đã tự động tạo mã mới: " . $category_code;
                        $message_type = "warning";
                    }
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO categories (category_code, name, description, requires_prescription, 
                                          is_controlled, parent_id, status, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $result = $stmt->execute([$category_code, $name, $description, $requires_prescription, 
                               $is_controlled, $parent_id, $status]);
                
                if ($result) {
                    $new_id = $pdo->lastInsertId();
                    $pdo->commit();
                    
                    if ($message_type === 'warning') {
                        $message = "Mã danh mục đã tồn tại! Đã tự động tạo mã mới: " . $category_code;
                    } else {
                        $message = "Thêm danh mục thành công! Mã danh mục: " . $category_code;
                    }
                    $message_type = "success";
                    error_log("Category added successfully with ID: $new_id");
                } else {
                    $pdo->rollback();
                    throw new Exception("Không thể thêm danh mục vào cơ sở dữ liệu!");
                }
            }
            
        } catch (PDOException $e) {
            $pdo->rollback();
            error_log("Database error: " . $e->getMessage());
            
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                // Nếu vẫn bị trùng (race condition), tạo mã mới và thử lại
                try {
                    $pdo->beginTransaction();
                    $category_code = generateCategoryCode($pdo);
                    error_log("Retry with new code: $category_code");
                    
                    if ($category_id) {
                        $stmt = $pdo->prepare("
                            UPDATE categories 
                            SET category_code = ?, name = ?, description = ?, requires_prescription = ?, 
                                is_controlled = ?, parent_id = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                            WHERE id = ?
                        ");
                        $stmt->execute([$category_code, $name, $description, $requires_prescription, 
                                       $is_controlled, $parent_id, $status, $category_id]);
                        $message = "Cập nhật danh mục thành công! Mã danh mục: " . $category_code;
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO categories (category_code, name, description, requires_prescription, 
                                                  is_controlled, parent_id, status, created_at, updated_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                        ");
                        $stmt->execute([$category_code, $name, $description, $requires_prescription, 
                                       $is_controlled, $parent_id, $status]);
                        $message = "Thêm danh mục thành công! Mã danh mục: " . $category_code;
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
        header('Location: ' . BASE_URL . 'manager/categories/');
        exit();
    }
}

// Check for flash messages
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Xử lý xóa category
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $pdo->beginTransaction();
        
        // Kiểm tra xem có category con không
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $stmt->execute([$id]);
        $child_count = $stmt->fetchColumn();
        
        if ($child_count > 0) {
            $message = "Không thể xóa danh mục có danh mục con!";
            $message_type = "danger";
        } else {
            // Kiểm tra xem có thuốc nào sử dụng category này không
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM medicines WHERE category_id = ?");
            $stmt->execute([$id]);
            $medicine_count = $stmt->fetchColumn();
            
            if ($medicine_count > 0) {
                $message = "Không thể xóa danh mục đang được sử dụng bởi thuốc!";
                $message_type = "danger";
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $result = $stmt->execute([$id]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $pdo->commit();
                    $_SESSION['flash_message'] = "Xóa danh mục thành công!";
                    $_SESSION['flash_type'] = "success";
                } else {
                    $pdo->rollback();
                    $message = "Không tìm thấy danh mục để xóa!";
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
        header('Location: ' . BASE_URL . 'manager/categories/');
        exit();
    }
}

// Lấy danh sách categories với thông tin parent
try {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               p.name as parent_name,
               (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as child_count
        FROM categories c
        LEFT JOIN categories p ON c.parent_id = p.id
        ORDER BY 
            CASE WHEN c.parent_id IS NULL THEN 
                CAST(SUBSTRING(c.category_code, 4) AS UNSIGNED) 
            ELSE 
                CAST(SUBSTRING(COALESCE(p.category_code, c.category_code), 4) AS UNSIGNED) 
            END,
            CASE WHEN c.parent_id IS NULL THEN 0 ELSE 1 END,
            CAST(SUBSTRING(c.category_code, 4) AS UNSIGNED)
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
    error_log("Error fetching categories: " . $e->getMessage());
}

// Lấy danh sách parent categories cho dropdown
try {
    $stmt = $pdo->prepare("
        SELECT id, name, category_code
        FROM categories 
        WHERE status = 'active' 
        ORDER BY CAST(SUBSTRING(category_code, 4) AS UNSIGNED)
    ");
    $stmt->execute();
    $parent_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $parent_categories = [];
    error_log("Error fetching parent categories: " . $e->getMessage());
}

// Lấy thông tin category để edit
$edit_category = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $edit_category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$edit_category) {
            $_SESSION['flash_message'] = "Không tìm thấy danh mục để chỉnh sửa!";
            $_SESSION['flash_type'] = "danger";
            header('Location: ' . BASE_URL . 'manager/categories/');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error fetching category for edit: " . $e->getMessage());
        $_SESSION['flash_message'] = "Lỗi khi tải thông tin danh mục!";
        $_SESSION['flash_type'] = "danger";
        header('Location: ' . BASE_URL . 'manager/categories/');
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
    <title>Quản lý Danh mục Thuốc - Pharmacy Management System</title>
    
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
                <a href="<?php echo BASE_URL; ?>manager/" class="nav-link">Trang chủ</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?php echo BASE_URL; ?>manager/categories/" class="nav-link">Danh mục thuốc</a>
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
                        <h1 class="m-0">Quản lý Danh mục Thuốc</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>manager/">Trang chủ</a></li>
                            <li class="breadcrumb-item active">Danh mục thuốc</li>
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
                            <i class="fas fa-<?php echo $edit_category ? 'edit' : 'plus'; ?>"></i>
                            <?php echo $edit_category ? 'Sửa danh mục' : 'Thêm danh mục mới'; ?>
                        </h3>
                        <?php if ($edit_category): ?>
                            <div class="card-tools">
                                <a href="<?php echo BASE_URL; ?>manager/categories/" class="btn btn-tool">
                                    <i class="fas fa-times"></i> Hủy
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" id="categoryForm">
                        <div class="card-body">
                            <?php if ($edit_category): ?>
                                <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category_code">Mã danh mục</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="category_code" name="category_code" 
                                                   value="<?php echo htmlspecialchars($edit_category['category_code'] ?? ''); ?>" 
                                                   placeholder="Để trống để tự động tạo" maxlength="7">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" id="generateCode">
                                                    <i class="fas fa-magic"></i> Tự động
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">
                                            Mã danh mục tự động theo thứ tự CAT0001, CAT0002... Để trống để hệ thống tự tạo.
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Tên danh mục <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($edit_category['name'] ?? ''); ?>" 
                                               placeholder="Nhập tên danh mục" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="description">Mô tả</label>
                                <textarea class="form-control" id="description" name="description" rows="3" 
                                          placeholder="Nhập mô tả danh mục"><?php echo htmlspecialchars($edit_category['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="parent_id">Danh mục cha</label>
                                        <select class="form-control" id="parent_id" name="parent_id">
                                            <option value="">-- Không có danh mục cha --</option>
                                            <?php foreach ($parent_categories as $parent): ?>
                                                <?php if (!$edit_category || $parent['id'] != $edit_category['id']): ?>
                                                    <option value="<?php echo $parent['id']; ?>" 
                                                            <?php echo ($edit_category && $edit_category['parent_id'] == $parent['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($parent['category_code'] . ' - ' . $parent['name']); ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status">Trạng thái</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="active" <?php echo (!$edit_category || $edit_category['status'] == 'active') ? 'selected' : ''; ?>>Hoạt động</option>
                                            <option value="inactive" <?php echo ($edit_category && $edit_category['status'] == 'inactive') ? 'selected' : ''; ?>>Không hoạt động</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="requires_prescription" 
                                                   name="requires_prescription" value="1"
                                                   <?php echo ($edit_category && $edit_category['requires_prescription']) ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="requires_prescription">Yêu cầu đơn thuốc</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="is_controlled" 
                                                   name="is_controlled" value="1"
                                                   <?php echo ($edit_category && $edit_category['is_controlled']) ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="is_controlled">Thuốc kiểm soát đặc biệt</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 
                                <?php echo $edit_category ? 'Cập nhật' : 'Thêm mới'; ?>
                            </button>
                            <?php if ($edit_category): ?>
                                <a href="<?php echo BASE_URL; ?>manager/categories/" class="btn btn-secondary ml-2">
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

                <!-- Categories List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> Danh sách danh mục</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="categoriesTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Mã danh mục</th>
                                        <th>Tên danh mục</th>
                                        <th>Danh mục cha</th>
                                        <th>Mô tả</th>
                                        <th>Đơn thuốc</th>
                                        <th>Kiểm soát</th>
                                        <th>Trạng thái</th>
                                        <th>Danh mục con</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $index => $category): ?>
                                        <tr class="<?php echo $category['parent_id'] ? 'table-info' : ''; ?>">
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <code><?php echo htmlspecialchars($category['category_code']); ?></code>
                                            </td>
                                            <td>
                                                <?php if ($category['parent_id']): ?>
                                                    <i class="fas fa-level-up-alt fa-rotate-90 text-muted mr-1"></i>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </td>
                                            <td>
                                                <?php if ($category['parent_name']): ?>
                                                    <span class="badge badge-secondary">
                                                        <?php echo htmlspecialchars($category['parent_name']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">--</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($category['description']): ?>
                                                    <span title="<?php echo htmlspecialchars($category['description']); ?>">
                                                        <?php echo htmlspecialchars(mb_substr($category['description'], 0, 50)); ?>
                                                        <?php if (mb_strlen($category['description']) > 50): ?>...<?php endif; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">--</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($category['requires_prescription']): ?>
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-prescription"></i> Có
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Không</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($category['is_controlled']): ?>
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-exclamation-triangle"></i> Có
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Không</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($category['status'] == 'active'): ?>
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check"></i> Hoạt động
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">
                                                        <i class="fas fa-pause"></i> Không hoạt động
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($category['child_count'] > 0): ?>
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-sitemap"></i> <?php echo $category['child_count']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="<?php echo BASE_URL; ?>manager/categories/?action=edit&id=<?php echo $category['id']; ?>" 
                                                       class="btn btn-warning" title="Sửa">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($category['child_count'] == 0): ?>
                                                        <a href="<?php echo BASE_URL; ?>manager/categories/?action=delete&id=<?php echo $category['id']; ?>" 
                                                           class="btn btn-danger" title="Xóa"
                                                           onclick="return confirm('Bạn có chắc chắn muốn xóa danh mục \'<?php echo htmlspecialchars($category['name']); ?>\' không?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn btn-danger" disabled title="Không thể xóa danh mục có danh mục con">
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

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
<!-- DataTables  & Plugins -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.responsive.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/responsive.bootstrap4.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>

<script>
$(function () {
    // Initialize DataTable
    $("#categoriesTable").DataTable({
        "responsive": true, 
        "lengthChange": false, 
        "autoWidth": false,
        "pageLength": 25,
        "order": [[ 1, "asc" ]], // Sort by category code
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
        },
        "columnDefs": [
            { "orderable": false, "targets": [9] }, // Disable sorting for action column
            { "width": "5%", "targets": [0] }, // STT column
            { "width": "10%", "targets": [1] }, // Code column
            { "width": "15%", "targets": [2] }, // Name column
            { "width": "10%", "targets": [3] }, // Parent column
            { "width": "20%", "targets": [4] }, // Description column
            { "width": "8%", "targets": [5] }, // Prescription column
            { "width": "8%", "targets": [6] }, // Controlled column
            { "width": "10%", "targets": [7] }, // Status column
            { "width": "8%", "targets": [8] }, // Child count column
            { "width": "6%", "targets": [9] }  // Actions column
        ]
    });

    // Generate category code button
    $("#generateCode").click(function() {
        // Clear the input field to trigger auto-generation
        $("#category_code").val('');
        // Show feedback
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Đang tạo...');
        setTimeout(() => {
            $(this).html('<i class="fas fa-magic"></i> Tự động');
            $("#category_code").attr('placeholder', 'Mã sẽ được tự động tạo khi lưu');
        }, 500);
    });

    // Category code format validation
    $("#category_code").on('input', function() {
        let value = $(this).val().toUpperCase();
        let isValid = /^CAT\d{4}$/.test(value) || value === '';
        
        if (value && !isValid) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Mã danh mục phải có định dạng CAT + 4 chữ số (VD: CAT0001)</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
        
        // Update the input value to uppercase
        if (value !== $(this).val()) {
            $(this).val(value);
        }
    });

    // Form validation before submit
    $("#categoryForm").submit(function(e) {
        let categoryCode = $("#category_code").val();
        let categoryName = $("#name").val().trim();
        
        // Check required fields
        if (!categoryName) {
            alert('Vui lòng nhập tên danh mục!');
            $("#name").focus();
            e.preventDefault();
            return false;
        }
        
        // Validate category code format if provided
        if (categoryCode && !/^CAT\d{4}$/.test(categoryCode)) {
            alert('Mã danh mục phải có định dạng CAT + 4 chữ số (VD: CAT0001)!');
            $("#category_code").focus();
            e.preventDefault();
            return false;
        }
        
        return true;
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Tooltip initialization
    $('[title]').tooltip();

    // Confirm delete for categories with children
    $('.btn-danger[disabled]').click(function(e) {
        e.preventDefault();
        alert('Không thể xóa danh mục có danh mục con. Vui lòng xóa các danh mục con trước.');
    });
});

// Auto-refresh page every 5 minutes to get latest data
setInterval(function() {
    if (!document.hidden) {
        location.reload();
    }
}, 300000); // 5 minutes
</script>

</body>
</html>