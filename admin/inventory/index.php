<script type="text/javascript">
        var gk_isXlsx = false;
        var gk_xlsxFileLookup = {};
        var gk_fileData = {};
        function filledCell(cell) {
          return cell !== '' && cell != null;
        }
        function loadFileData(filename) {
        if (gk_isXlsx && gk_xlsxFileLookup[filename]) {
            try {
                var workbook = XLSX.read(gk_fileData[filename], { type: 'base64' });
                var firstSheetName = workbook.SheetNames[0];
                var worksheet = workbook.Sheets[firstSheetName];

                // Convert sheet to JSON to filter blank rows
                var jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, blankrows: false, defval: '' });
                // Filter out blank rows (rows where all cells are empty, null, or undefined)
                var filteredData = jsonData.filter(row => row.some(filledCell));

                // Heuristic to find the header row by ignoring rows with fewer filled cells than the next row
                var headerRowIndex = filteredData.findIndex((row, index) =>
                  row.filter(filledCell).length >= filteredData[index + 1]?.filter(filledCell).length
                );
                // Fallback
                if (headerRowIndex === -1 || headerRowIndex > 25) {
                  headerRowIndex = 0;
                }

                // Convert filtered JSON back to CSV
                var csv = XLSX.utils.aoa_to_sheet(filteredData.slice(headerRowIndex)); // Create a new sheet from filtered array of arrays
                csv = XLSX.utils.sheet_to_csv(csv, { header: 1 });
                return csv;
            } catch (e) {
                console.error(e);
                return "";
            }
        }
        return gk_fileData[filename] || "";
        }
        </script><?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// admin/inventory/index.php
require_once '../../includes/config.php';
require_once '../../includes/session.php';

// Kiểm tra đăng nhập và quyền truy cập
SessionManager::requireRole(['admin', 'admin']);

// Xử lý các hành động
$action = $_GET['action'] ?? '';
$message = '';
$message_type = '';

// Function to generate next batch code
function generateBatchCode($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT batch_code 
            FROM medicine_batches 
            WHERE batch_code REGEXP '^BAT[0-9]{4}$' 
            ORDER BY CAST(SUBSTRING(batch_code, 4) AS UNSIGNED) DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $lastCode = $stmt->fetchColumn();
        
        if ($lastCode) {
            $lastNumber = (int)substr($lastCode, 3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return 'BAT' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        error_log("Error generating batch code: " . $e->getMessage());
        return 'BAT' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

// Function to validate batch code format
function validateBatchCode($code) {
    return preg_match('/^BAT\d{4}$/', $code);
}

// Function to check if batch code exists
function batchCodeExists($pdo, $code, $excludeId = null) {
    try {
        if ($excludeId) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM medicine_batches WHERE batch_code = ? AND id != ?");
            $stmt->execute([$code, $excludeId]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM medicine_batches WHERE batch_code = ?");
            $stmt->execute([$code]);
        }
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking batch code: " . $e->getMessage());
        return false;
    }
}

// Xử lý thêm/sửa lô hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_id = $_POST['batch_id'] ?? '';
    $batch_code = trim($_POST['batch_code'] ?? '');
    $medicine_id = $_POST['medicine_id'] ?? '';
    $supplier_id = $_POST['supplier_id'] ?? '';
    $batch_number = trim($_POST['batch_number'] ?? '');
    $manufacturing_date = $_POST['manufacturing_date'] ?? '';
    $expiry_date = $_POST['expiry_date'] ?? '';
    $import_date = $_POST['import_date'] ?? '';
    $import_price = trim($_POST['import_price'] ?? '');
    $original_quantity = trim($_POST['original_quantity'] ?? '');
    $current_quantity = trim($_POST['current_quantity'] ?? '');
    $storage_location = trim($_POST['storage_location'] ?? '');
    $qr_code = trim($_POST['qr_code'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $notes = trim($_POST['notes'] ?? '');
    $imported_by = SessionManager::getUserInfo()['id'];
    
    // Validate required fields
    if (empty($medicine_id)) {
        $message = "Vui lòng chọn thuốc!";
        $message_type = "danger";
    } elseif (empty($supplier_id)) {
        $message = "Vui lòng chọn nhà cung cấp!";
        $message_type = "danger";
    } elseif (empty($batch_number)) {
        $message = "Số lô không được để trống!";
        $message_type = "danger";
    } elseif (empty($expiry_date)) {
        $message = "Ngày hết hạn không được để trống!";
        $message_type = "danger";
    } elseif (empty($import_date)) {
        $message = "Ngày nhập kho không được để trống!";
        $message_type = "danger";
    } elseif ($import_price === '' || !is_numeric($import_price) || $import_price < 0) {
        $message = "Giá nhập không hợp lệ!";
        $message_type = "danger";
    } elseif ($original_quantity === '' || !is_numeric($original_quantity) || $original_quantity <= 0) {
        $message = "Số lượng ban đầu không hợp lệ!";
        $message_type = "danger";
    } elseif ($current_quantity === '' || !is_numeric($current_quantity) || $current_quantity < 0) {
        $message = "Số lượng hiện tại không hợp lệ!";
        $message_type = "danger";
    } elseif ($current_quantity > $original_quantity) {
        $message = "Số lượng hiện tại không thể lớn hơn số lượng ban đầu!";
        $message_type = "danger";
    } else {
        try {
            $pdo->beginTransaction();
            
            if ($batch_id) {
                // Cập nhật lô hàng
                if (!empty($batch_code) && !validateBatchCode($batch_code)) {
                    throw new Exception("Mã lô phải có định dạng BAT + 4 chữ số (VD: BAT0001)!");
                } elseif (batchCodeExists($pdo, $batch_code, $batch_id)) {
                    throw new Exception("Mã lô đã tồn tại!");
                }
                
                if (empty($batch_code)) {
                    $batch_code = generateBatchCode($pdo);
                }
                
                $stmt = $pdo->prepare("
                    UPDATE medicine_batches 
                    SET batch_code = ?, medicine_id = ?, supplier_id = ?, batch_number = ?, 
                        manufacturing_date = ?, expiry_date = ?, import_date = ?, import_price = ?, 
                        original_quantity = ?, current_quantity = ?, storage_location = ?, qr_code = ?, 
                        status = ?, notes = ?, imported_by = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $result = $stmt->execute([
                    $batch_code, $medicine_id, $supplier_id, $batch_number, 
                    $manufacturing_date ?: null, $expiry_date, $import_date, $import_price, 
                    $original_quantity, $current_quantity, $storage_location, $qr_code, 
                    $status, $notes, $imported_by, $batch_id
                ]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $pdo->commit();
                    $message = "Cập nhật lô hàng thành công! Mã lô: " . $batch_code;
                    $message_type = "success";
                } else {
                    $pdo->rollback();
                    throw new Exception("Không có thay đổi nào được thực hiện hoặc lô hàng không tồn tại!");
                }
            } else {
                // Thêm lô hàng mới
                if (empty($batch_code)) {
                    $batch_code = generateBatchCode($pdo);
                } elseif (!validateBatchCode($batch_code)) {
                    throw new Exception("Mã lô phải có định dạng BAT + 4 chữ số (VD: BAT0001)!");
                } elseif (batchCodeExists($pdo, $batch_code)) {
                    $old_code = $batch_code;
                    $batch_code = generateBatchCode($pdo);
                    $message = "Mã lô đã tồn tại! Đã tự động tạo mã mới: " . $batch_code;
                    $message_type = "warning";
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO medicine_batches (
                        batch_code, medicine_id, supplier_id, batch_number, manufacturing_date, 
                        expiry_date, import_date, import_price, original_quantity, current_quantity, 
                        storage_location, qr_code, status, imported_by, notes, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $result = $stmt->execute([
                    $batch_code, $medicine_id, $supplier_id, $batch_number, 
                    $manufacturing_date ?: null, $expiry_date, $import_date, $import_price, 
                    $original_quantity, $current_quantity, $storage_location, $qr_code, 
                    $status, $imported_by, $notes
                ]);
                
                if ($result) {
                    $pdo->commit();
                    $message = $message_type === 'warning' ? $message : "Thêm lô hàng thành công! Mã lô: " . $batch_code;
                    $message_type = "success";
                } else {
                    $pdo->rollback();
                    throw new Exception("Không thể thêm lô hàng vào cơ sở dữ liệu!");
                }
            }
        } catch (PDOException $e) {
            $pdo->rollback();
            error_log("Database error: " . $e->getMessage());
            $message = "Lỗi cơ sở dữ liệu: " . $e->getMessage();
            $message_type = "danger";
        } catch (Exception $e) {
            $pdo->rollback();
            error_log("General error: " . $e->getMessage());
            $message = $e->getMessage();
            $message_type = "danger";
        }
    }
    
    // Redirect to prevent form resubmission
    if ($message_type === 'success') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $message_type;
        header('Location: ' . BASE_URL . 'admin/inventory/');
        exit();
    }
}

// Check for flash messages
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Xử lý xóa lô hàng
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $pdo->beginTransaction();
        
        // Kiểm tra lô hàng có tồn tại
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM medicine_batches WHERE id = ?");
        $stmt->execute([$id]);
        $batch_exists = $stmt->fetchColumn();
        
        if ($batch_exists == 0) {
            $message = "Không tìm thấy lô hàng để xóa!";
            $message_type = "danger";
        } else {
            // Kiểm tra xem có stock movements liên quan không
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_movements WHERE batch_id = ?");
            $stmt->execute([$id]);
            $movement_count = $stmt->fetchColumn();
            
            if ($movement_count > 0) {
                $message = "Không thể xóa lô hàng này vì đang có {$movement_count} giao dịch liên quan!";
                $message_type = "danger";
            } else {
                $stmt = $pdo->prepare("DELETE FROM medicine_batches WHERE id = ?");
                $result = $stmt->execute([$id]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $pdo->commit();
                    $_SESSION['flash_message'] = "Xóa lô hàng thành công!";
                    $_SESSION['flash_type'] = "success";
                } else {
                    $pdo->rollback();
                    $message = "Không thể xóa lô hàng!";
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
    
    if (!$message) {
        header('Location: ' . BASE_URL . 'admin/inventory/');
        exit();
    }
}

// Lấy danh sách lô hàng
try {
    $stmt = $pdo->prepare("
        SELECT mb.*, m.name AS medicine_name, s.name AS supplier_name, u.full_name AS imported_by_name
        FROM medicine_batches mb
        LEFT JOIN medicines m ON mb.medicine_id = m.id
        LEFT JOIN suppliers s ON mb.supplier_id = s.id
        LEFT JOIN users u ON mb.imported_by = u.id
        ORDER BY CAST(SUBSTRING(mb.batch_code, 4) AS UNSIGNED)
    ");
    $stmt->execute();
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $batches = [];
    error_log("Error fetching batches: " . $e->getMessage());
}

// Lấy danh sách thuốc và nhà cung cấp cho form
try {
    $stmt = $pdo->prepare("SELECT id, name FROM medicines WHERE status = 'active' ORDER BY name");
    $stmt->execute();
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT id, name FROM suppliers WHERE status = 'active' ORDER BY name");
    $stmt->execute();
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $medicines = [];
    $suppliers = [];
    error_log("Error fetching medicines/suppliers: " . $e->getMessage());
}

// Lấy thông tin lô hàng để sửa
$edit_batch = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM medicine_batches WHERE id = ?");
        $stmt->execute([$id]);
        $edit_batch = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$edit_batch) {
            $_SESSION['flash_message'] = "Không tìm thấy lô hàng để chỉnh sửa!";
            $_SESSION['flash_type'] = "danger";
            header('Location: ' . BASE_URL . 'admin/inventory/');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error fetching batch for edit: " . $e->getMessage());
        $_SESSION['flash_message'] = "Lỗi khi tải thông tin lô hàng!";
        $_SESSION['flash_type'] = "danger";
        header('Location: ' . BASE_URL . 'admin/inventory/');
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
    <title>Quản lý Lô hàng - Pharmacy Management System</title>
    
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
                <a href="<?php echo BASE_URL; ?>admin/" class="nav-link">Trang chủ</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?php echo BASE_URL; ?>admin/inventory/" class="nav-link">Lô hàng</a>
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
                        <h1 class="m-0">Quản lý Lô hàng</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>admin/">Trang chủ</a></li>
                            <li class="breadcrumb-item active">Lô hàng</li>
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
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-<?php echo $message_type == 'success' ? 'check' : ($message_type == 'danger' ? 'ban' : 'exclamation-triangle'); ?>"></i> 
                        <?php echo $message_type == 'success' ? 'Thành công!' : ($message_type == 'danger' ? 'Lỗi!' : 'Cảnh báo!'); ?></h5>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Add/Edit Form -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-<?php echo $edit_batch ? 'edit' : 'plus'; ?>"></i>
                            <?php echo $edit_batch ? 'Sửa lô hàng' : 'Thêm lô hàng mới'; ?>
                        </h3>
                        <?php if ($edit_batch): ?>
                            <div class="card-tools">
                                <a href="<?php echo BASE_URL; ?>admin/inventory/" class="btn btn-tool">
                                    <i class="fas fa-times"></i> Hủy
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" id="batchForm">
                        <div class="card-body">
                            <?php if ($edit_batch): ?>
                                <input type="hidden" name="batch_id" value="<?php echo $edit_batch['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="batch_code">Mã lô</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="batch_code" name="batch_code" 
                                                   value="<?php echo htmlspecialchars($edit_batch['batch_code'] ?? ''); ?>" 
                                                   placeholder="Để trống để tự động tạo" maxlength="7">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" id="generateCode">
                                                    <i class="fas fa-magic"></i> Tự động
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">
                                            Mã lô tự động theo thứ tự BAT0001, BAT0002... Để trống để hệ thống tự tạo.
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="medicine_id">Thuốc <span class="text-danger">*</span></label>
                                        <select class="form-control" id="medicine_id" name="medicine_id" required>
                                            <option value="">Chọn thuốc</option>
                                            <?php foreach ($medicines as $medicine): ?>
                                                <option value="<?php echo $medicine['id']; ?>" 
                                                        <?php echo ($edit_batch && $edit_batch['medicine_id'] == $medicine['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($medicine['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="supplier_id">Nhà cung cấp <span class="text-danger">*</span></label>
                                        <select class="form-control" id="supplier_id" name="supplier_id" required>
                                            <option value="">Chọn nhà cung cấp</option>
                                            <?php foreach ($suppliers as $supplier): ?>
                                                <option value="<?php echo $supplier['id']; ?>" 
                                                        <?php echo ($edit_batch && $edit_batch['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($supplier['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="batch_number">Số lô <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="batch_number" name="batch_number" 
                                               value="<?php echo htmlspecialchars($edit_batch['batch_number'] ?? ''); ?>" 
                                               placeholder="Nhập số lô" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="manufacturing_date">Ngày sản xuất</label>
                                        <input type="date" class="form-control" id="manufacturing_date" name="manufacturing_date" 
                                               value="<?php echo htmlspecialchars($edit_batch['manufacturing_date'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="expiry_date">Ngày hết hạn <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="expiry_date" name="expiry_date" 
                                               value="<?php echo htmlspecialchars($edit_batch['expiry_date'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="import_date">Ngày nhập kho <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="import_date" name="import_date" 
                                               value="<?php echo htmlspecialchars($edit_batch['import_date'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="import_price">Giá nhập <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="import_price" name="import_price" 
                                               value="<?php echo htmlspecialchars($edit_batch['import_price'] ?? ''); ?>" 
                                               placeholder="Nhập giá nhập" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="original_quantity">Số lượng ban đầu <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="original_quantity" name="original_quantity" 
                                               value="<?php echo htmlspecialchars($edit_batch['original_quantity'] ?? ''); ?>" 
                                               placeholder="Nhập số lượng ban đầu" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="current_quantity">Số lượng hiện tại <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="current_quantity" name="current_quantity" 
                                               value="<?php echo htmlspecialchars($edit_batch['current_quantity'] ?? ''); ?>" 
                                               placeholder="Nhập số lượng hiện tại" min="0" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="storage_location">Vị trí lưu trữ</label>
                                        <input type="text" class="form-control" id="storage_location" name="storage_location" 
                                               value="<?php echo htmlspecialchars($edit_batch['storage_location'] ?? ''); ?>" 
                                               placeholder="Nhập vị trí lưu trữ">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="qr_code">Mã QR</label>
                                        <input type="text" class="form-control" id="qr_code" name="qr_code" 
                                               value="<?php echo htmlspecialchars($edit_batch['qr_code'] ?? ''); ?>" 
                                               placeholder="Nhập mã QR">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="notes">Ghi chú</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Nhập ghi chú"><?php echo htmlspecialchars($edit_batch['notes'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="status">Trạng thái</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active" <?php echo (!$edit_batch || $edit_batch['status'] === 'active') ? 'selected' : ''; ?>>Hoạt động</option>
                                    <option value="expired" <?php echo ($edit_batch && $edit_batch['status'] === 'expired') ? 'selected' : ''; ?>>Hết hạn</option>
                                    <option value="recalled" <?php echo ($edit_batch && $edit_batch['status'] === 'recalled') ? 'selected' : ''; ?>>Thu hồi</option>
                                    <option value="depleted" <?php echo ($edit_batch && $edit_batch['status'] === 'depleted') ? 'selected' : ''; ?>>Hết hàng</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $edit_batch ? 'Cập nhật' : 'Thêm mới'; ?>
                            </button>
                            <?php if ($edit_batch): ?>
                                <a href="<?php echo BASE_URL; ?>admin/inventory/" class="btn btn-secondary">
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

                <!-- Batches List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list"></i> Danh sách lô hàng
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-info">Tổng: <?php echo count($batches); ?> lô hàng</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="batchesTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Mã lô</th>
                                        <th>Thuốc</th>
                                        <th>Nhà cung cấp</th>
                                        <th>Số lô</th>
                                        <th>Ngày hết hạn</th>
                                        <th>Số lượng</th>
                                        <th>Giá nhập</th>
                                        <th>Trạng thái</th>
                                        <th>Người nhập</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($batches)): ?>
                                        <tr>
                                            <td colspan="12" class="text-center">
                                                <i class="fas fa-inbox"></i> Chưa có lô hàng nào
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $stt = 1; foreach ($batches as $batch): ?>
                                            <tr>
                                                <td><?php echo $stt++; ?></td>
                                                <td>
                                                    <span class="badge badge-secondary"><?php echo htmlspecialchars($batch['batch_code']); ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($batch['medicine_name']); ?></td>
                                                <td><?php echo htmlspecialchars($batch['supplier_name']); ?></td>
                                                <td><?php echo htmlspecialchars($batch['batch_number']); ?></td>
                                                <td>
                                                    <?php 
                                                    $expiry_date = new DateTime($batch['expiry_date']);
                                                    echo $expiry_date->format('d/m/Y');
                                                    if ($batch['status'] === 'expired' || $expiry_date <= new DateTime()) {
                                                        echo ' <span class="badge badge-danger">Hết hạn</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $batch['current_quantity'] <= 0 ? 'danger' : 'info'; ?>">
                                                        <?php echo $batch['current_quantity'] . '/' . $batch['original_quantity']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($batch['import_price'], 2); ?></td>
                                                <td>
                                                    <?php if ($batch['status'] === 'active'): ?>
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-check-circle"></i> Hoạt động
                                                        </span>
                                                    <?php elseif ($batch['status'] === 'expired'): ?>
                                                        <span class="badge badge-danger">
                                                            <i class="fas fa-times-circle"></i> Hết hạn
                                                        </span>
                                                    <?php elseif ($batch['status'] === 'recalled'): ?>
                                                        <span class="badge badge-warning">
                                                            <i class="fas fa-exclamation-triangle"></i> Thu hồi
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">
                                                            <i class="fas fa-ban"></i> Hết hàng
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($batch['imported_by_name'] ?: 'Chưa có'); ?></td>
                                                <td>
                                                    <small>
                                                        <?php 
                                                        $created_date = new DateTime($batch['created_at']);
                                                        echo $created_date->format('d/m/Y H:i'); 
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="<?php echo BASE_URL; ?>admin/inventory/?action=edit&id=<?php echo $batch['id']; ?>" 
                                                           class="btn btn-sm btn-warning" title="Sửa">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php 
                                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_movements WHERE batch_id = ?");
                                                        $stmt->execute([$batch['id']]);
                                                        $movement_count = $stmt->fetchColumn();
                                                        if ($movement_count == 0): ?>
                                                            <button type="button" class="btn btn-sm btn-danger delete-batch" 
                                                                    data-id="<?php echo $batch['id']; ?>" 
                                                                    data-name="<?php echo htmlspecialchars($batch['batch_code']); ?>"
                                                                    title="Xóa">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-secondary" 
                                                                    title="Không thể xóa - có <?php echo $movement_count; ?> giao dịch" disabled>
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
                <p>Bạn có chắc chắn muốn xóa lô hàng <strong id="batchName"></strong>?</p>
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
    $('#batchesTable').DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "pageLength": 25,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
        },
        "order": [[1, "asc"]], // Sort by batch code
        "columnDefs": [
            { "orderable": false, "targets": [11] } // Disable sorting for action column
        ]
    });

    // Auto-generate batch code
    $('#generateCode').click(function() {
        $('#batch_code').val('');
        $('#batch_code').attr('placeholder', 'Mã sẽ được tự động tạo khi lưu');
    });

    // Format batch code input
    $('#batch_code').on('input', function() {
        let value = $(this).val().toUpperCase();
        if (value.startsWith('BAT')) {
            value = 'BAT' + value.substring(3).replace(/[^0-9]/g, '');
        } else {
            value = value.replace(/[^A-Z0-9]/g, '');
        }
        if (value.length > 7) {
            value = value.substring(0, 7);
        }
        $(this).val(value);
    });

    // Form validation
    $('#batchForm').submit(function(e) {
        let isValid = true;
        let errors = [];

        // Validate medicine
        if ($('#medicine_id').val() === '') {
            errors.push('Vui lòng chọn thuốc');
            $('#medicine_id').addClass('is-invalid');
            isValid = false;
        } else {
            $('#medicine_id').removeClass('is-invalid');
        }

        // Validate supplier
        if ($('#supplier_id').val() === '') {
            errors.push('Vui lòng chọn nhà cung cấp');
            $('#supplier_id').addClass('is-invalid');
            isValid = false;
        } else {
            $('#supplier_id').removeClass('is-invalid');
        }

        // Validate batch number
        if ($('#batch_number').val().trim() === '') {
            errors.push('Số lô không được để trống');
            $('#batch_number').addClass('is-invalid');
            isValid = false;
        } else {
            $('#batch_number').removeClass('is-invalid');
        }

        // Validate expiry date
        if ($('#expiry_date').val() === '') {
            errors.push('Ngày hết hạn không được để trống');
            $('#expiry_date').addClass('is-invalid');
            isValid = false;
        } else {
            $('#expiry_date').removeClass('is-invalid');
        }

        // Validate import date
        if ($('#import_date').val() === '') {
            errors.push('Ngày nhập kho không được để trống');
            $('#import_date').addClass('is-invalid');
            isValid = false;
        } else {
            $('#import_date').removeClass('is-invalid');
        }

        // Validate import price
        let importPrice = $('#import_price').val().trim();
        if (importPrice === '' || isNaN(importPrice) || importPrice < 0) {
            errors.push('Giá nhập không hợp lệ');
            $('#import_price').addClass('is-invalid');
            isValid = false;
        } else {
            $('#import_price').removeClass('is-invalid');
        }

        // Validate quantities
        let originalQuantity = $('#original_quantity').val().trim();
        let currentQuantity = $('#current_quantity').val().trim();
        if (originalQuantity === '' || isNaN(originalQuantity) || originalQuantity <= 0) {
            errors.push('Số lượng ban đầu không hợp lệ');
            $('#original_quantity').addClass('is-invalid');
            isValid = false;
        } else {
            $('#original_quantity').removeClass('is-invalid');
        }
        if (currentQuantity === '' || isNaN(currentQuantity) || currentQuantity < 0) {
            errors.push('Số lượng hiện tại không hợp lệ');
            $('#current_quantity').addClass('is-invalid');
            isValid = false;
        } else if (parseInt(currentQuantity) > parseInt(originalQuantity)) {
            errors.push('Số lượng hiện tại không thể lớn hơn số lượng ban đầu');
            $('#current_quantity').addClass('is-invalid');
            isValid = false;
        } else {
            $('#current_quantity').removeClass('is-invalid');
        }

        // Validate batch code format if provided
        let batchCode = $('#batch_code').val().trim();
        if (batchCode !== '' && !/^BAT\d{4}$/.test(batchCode)) {
            errors.push('Mã lô phải có định dạng BAT + 4 chữ số (VD: BAT0001)');
            $('#batch_code').addClass('is-invalid');
            isValid = false;
        } else {
            $('#batch_code').removeClass('is-invalid');
        }

        if (!isValid) {
            e.preventDefault();
            let errorMessage = 'Vui lòng kiểm tra lại thông tin:\n• ' + errors.join('\n• ');
            alert(errorMessage);
        }
    });

    // Delete batch
    $('.delete-batch').click(function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        
        $('#batchName').text(name);
        $('#confirmDelete').attr('href', '<?php echo BASE_URL; ?>admin/inventory/?action=delete&id=' + id);
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