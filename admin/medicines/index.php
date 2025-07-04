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

// admin/medicines/index.php
require_once '../../includes/config.php';
require_once '../../includes/session.php';

// Kiểm tra đăng nhập và quyền truy cập
SessionManager::requireRole(['admin', 'admin']);

// Xử lý các hành động
$action = $_GET['action'] ?? '';
$message = '';
$message_type = '';

// Function to generate next medicine code
function generateMedicineCode($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT medicine_code 
            FROM medicines 
            WHERE medicine_code REGEXP '^MED[0-9]{4}$' 
            ORDER BY CAST(SUBSTRING(medicine_code, 4) AS UNSIGNED) DESC 
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
        
        return 'MED' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        error_log("Error generating medicine code: " . $e->getMessage());
        return 'MED' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

// Function to validate medicine code format
function validateMedicineCode($code) {
    return preg_match('/^MED\d{4}$/', $code);
}

// Function to check if medicine code or barcode exists
function medicineCodeExists($pdo, $code, $barcode, $excludeId = null) {
    try {
        $sql = "SELECT COUNT(*) FROM medicines WHERE (medicine_code = ? OR barcode = ?) ";
        if ($excludeId) {
            $sql .= "AND id != ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$code, $barcode, $excludeId]);
        } else {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$code, $barcode]);
        }
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking medicine code/barcode: " . $e->getMessage());
        return false;
    }
}

// Xử lý thêm/sửa thuốc
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medicine_id = $_POST['medicine_id'] ?? '';
    $medicine_code = trim($_POST['medicine_code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $generic_name = trim($_POST['generic_name'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '');
    $manufacturer = trim($_POST['manufacturer'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $strength = trim($_POST['strength'] ?? '');
    $dosage_form = trim($_POST['dosage_form'] ?? '');
    $selling_price = trim($_POST['selling_price'] ?? '');
    $min_stock_level = trim($_POST['min_stock_level'] ?? '0');
    $max_stock_level = trim($_POST['max_stock_level'] ?? '0');
    $is_controlled = isset($_POST['is_controlled']) ? 1 : 0;
    $requires_prescription = isset($_POST['requires_prescription']) ? 1 : 0;
    $description = trim($_POST['description'] ?? '');
    $storage_conditions = trim($_POST['storage_conditions'] ?? '');
    $contraindications = trim($_POST['contraindications'] ?? '');
    $side_effects = trim($_POST['side_effects'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $default_supplier_id = $_POST['default_supplier_id'] ?? '';
    $status = $_POST['status'] ?? 'active';
    
    // Validate required fields
    if (empty($name)) {
        $message = "Tên thuốc không được để trống!";
        $message_type = "danger";
    } elseif (empty($unit)) {
        $message = "Đơn vị thuốc không được để trống!";
        $message_type = "danger";
    } elseif ($selling_price === '' || !is_numeric($selling_price) || $selling_price < 0) {
        $message = "Giá bán không hợp lệ!";
        $message_type = "danger";
    } elseif ($min_stock_level === '' || !is_numeric($min_stock_level) || $min_stock_level < 0) {
        $message = "Mức tồn kho tối thiểu không hợp lệ!";
        $message_type = "danger";
    } elseif ($max_stock_level === '' || !is_numeric($max_stock_level) || $max_stock_level < $min_stock_level) {
        $message = "Mức tồn kho tối đa không hợp lệ!";
        $message_type = "danger";
    } else {
        try {
            $pdo->beginTransaction();
            
            if ($medicine_id) {
                // Cập nhật thuốc
                if (!empty($medicine_code) && !validateMedicineCode($medicine_code)) {
                    throw new Exception("Mã thuốc phải có định dạng MED + 4 chữ số (VD: MED0001)!");
                } elseif (medicineCodeExists($pdo, $medicine_code, $barcode, $medicine_id)) {
                    throw new Exception("Mã thuốc hoặc mã vạch đã tồn tại!");
                }
                
                if (empty($medicine_code)) {
                    $medicine_code = generateMedicineCode($pdo);
                }
                
                $stmt = $pdo->prepare("
                    UPDATE medicines 
                    SET medicine_code = ?, name = ?, generic_name = ?, barcode = ?, manufacturer = ?, 
                        unit = ?, strength = ?, dosage_form = ?, selling_price = ?, min_stock_level = ?, 
                        max_stock_level = ?, is_controlled = ?, requires_prescription = ?, description = ?, 
                        storage_conditions = ?, contraindications = ?, side_effects = ?, category_id = ?, 
                        default_supplier_id = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $result = $stmt->execute([
                    $medicine_code, $name, $generic_name, $barcode, $manufacturer, $unit, $strength, 
                    $dosage_form, $selling_price, $min_stock_level, $max_stock_level, $is_controlled, 
                    $requires_prescription, $description, $storage_conditions, $contraindications, 
                    $side_effects, $category_id ?: null, $default_supplier_id ?: null, $status, $medicine_id
                ]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $pdo->commit();
                    $message = "Cập nhật thuốc thành công! Mã thuốc: " . $medicine_code;
                    $message_type = "success";
                } else {
                    $pdo->rollback();
                    throw new Exception("Không có thay đổi nào được thực hiện hoặc thuốc không tồn tại!");
                }
            } else {
                // Thêm thuốc mới
                if (empty($medicine_code)) {
                    $medicine_code = generateMedicineCode($pdo);
                } elseif (!validateMedicineCode($medicine_code)) {
                    throw new Exception("Mã thuốc phải có định dạng MED + 4 chữ số (VD: MED0001)!");
                } elseif (medicineCodeExists($pdo, $medicine_code, $barcode)) {
                    $old_code = $medicine_code;
                    $medicine_code = generateMedicineCode($pdo);
                    $message = "Mã thuốc hoặc mã vạch đã tồn tại! Đã tự động tạo mã mới: " . $medicine_code;
                    $message_type = "warning";
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO medicines (
                        medicine_code, name, generic_name, barcode, manufacturer, unit, strength, dosage_form, 
                        selling_price, min_stock_level, max_stock_level, is_controlled, requires_prescription, 
                        description, storage_conditions, contraindications, side_effects, category_id, 
                        default_supplier_id, status, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $result = $stmt->execute([
                    $medicine_code, $name, $generic_name, $barcode, $manufacturer, $unit, $strength, 
                    $dosage_form, $selling_price, $min_stock_level, $max_stock_level, $is_controlled, 
                    $requires_prescription, $description, $storage_conditions, $contraindications, 
                    $side_effects, $category_id ?: null, $default_supplier_id ?: null, $status
                ]);
                
                if ($result) {
                    $pdo->commit();
                    $message = $message_type === 'warning' ? $message : "Thêm thuốc thành công! Mã thuốc: " . $medicine_code;
                    $message_type = "success";
                } else {
                    $pdo->rollback();
                    throw new Exception("Không thể thêm thuốc vào cơ sở dữ liệu!");
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
        header('Location: ' . BASE_URL . 'admin/medicines/');
        exit();
    }
}

// Check for flash messages
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Xử lý xóa thuốc
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $pdo->beginTransaction();
        
        // Kiểm tra thuốc có tồn tại
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM medicines WHERE id = ?");
        $stmt->execute([$id]);
        $medicine_exists = $stmt->fetchColumn();
        
        if ($medicine_exists == 0) {
            $message = "Không tìm thấy thuốc để xóa!";
            $message_type = "danger";
        } else {
            // Kiểm tra xem có lô thuốc nào liên quan không
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM medicine_batches WHERE medicine_id = ?");
            $stmt->execute([$id]);
            $batch_count = $stmt->fetchColumn();
            
            if ($batch_count > 0) {
                $message = "Không thể xóa thuốc này vì đang có {$batch_count} lô thuốc liên quan!";
                $message_type = "danger";
            } else {
                $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ?");
                $result = $stmt->execute([$id]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $pdo->commit();
                    $_SESSION['flash_message'] = "Xóa thuốc thành công!";
                    $_SESSION['flash_type'] = "success";
                } else {
                    $pdo->rollback();
                    $message = "Không thể xóa thuốc!";
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
        header('Location: ' . BASE_URL . 'admin/medicines/');
        exit();
    }
}

// Lấy danh sách thuốc
try {
    $stmt = $pdo->prepare("
        SELECT m.*, c.name AS category_name, s.name AS supplier_name,
               COALESCE(v.total_quantity, 0) AS total_quantity,
               COALESCE(v.active_batches, 0) AS active_batches
        FROM medicines m
        LEFT JOIN categories c ON m.category_id = c.id
        LEFT JOIN suppliers s ON m.default_supplier_id = s.id
        LEFT JOIN v_current_stock v ON m.id = v.medicine_id
        ORDER BY CAST(SUBSTRING(m.medicine_code, 4) AS UNSIGNED)
    ");
    $stmt->execute();
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $medicines = [];
    error_log("Error fetching medicines: " . $e->getMessage());
}

// Lấy danh sách danh mục và nhà cung cấp cho form
try {
    $stmt = $pdo->prepare("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT id, name FROM suppliers WHERE status = 'active' ORDER BY name");
    $stmt->execute();
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
    $suppliers = [];
    error_log("Error fetching categories/suppliers: " . $e->getMessage());
}

// Lấy thông tin thuốc để sửa
$edit_medicine = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM medicines WHERE id = ?");
        $stmt->execute([$id]);
        $edit_medicine = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$edit_medicine) {
            $_SESSION['flash_message'] = "Không tìm thấy thuốc để chỉnh sửa!";
            $_SESSION['flash_type'] = "danger";
            header('Location: ' . BASE_URL . 'admin/medicines/');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error fetching medicine for edit: " . $e->getMessage());
        $_SESSION['flash_message'] = "Lỗi khi tải thông tin thuốc!";
        $_SESSION['flash_type'] = "danger";
        header('Location: ' . BASE_URL . 'admin/medicines/');
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
    <title>Quản lý Thuốc - Pharmacy Management System</title>
    
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
                <a href="<?php echo BASE_URL; ?>admin/medicines/" class="nav-link">Thuốc</a>
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
                        <h1 class="m-0">Quản lý Thuốc</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>admin/">Trang chủ</a></li>
                            <li class="breadcrumb-item active">Thuốc</li>
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
                            <i class="fas fa-<?php echo $edit_medicine ? 'edit' : 'plus'; ?>"></i>
                            <?php echo $edit_medicine ? 'Sửa thuốc' : 'Thêm thuốc mới'; ?>
                        </h3>
                        <?php if ($edit_medicine): ?>
                            <div class="card-tools">
                                <a href="<?php echo BASE_URL; ?>admin/medicines/" class="btn btn-tool">
                                    <i class="fas fa-times"></i> Hủy
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" id="medicineForm">
                        <div class="card-body">
                            <?php if ($edit_medicine): ?>
                                <input type="hidden" name="medicine_id" value="<?php echo $edit_medicine['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="medicine_code">Mã thuốc</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="medicine_code" name="medicine_code" 
                                                   value="<?php echo htmlspecialchars($edit_medicine['medicine_code'] ?? ''); ?>" 
                                                   placeholder="Để trống để tự động tạo" maxlength="7">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" id="generateCode">
                                                    <i class="fas fa-magic"></i> Tự động
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">
                                            Mã thuốc tự động theo thứ tự MED0001, MED0002... Để trống để hệ thống tự tạo.
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Tên thuốc <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($edit_medicine['name'] ?? ''); ?>" 
                                               placeholder="Nhập tên thuốc" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="generic_name">Tên generic</label>
                                        <input type="text" class="form-control" id="generic_name" name="generic_name" 
                                               value="<?php echo htmlspecialchars($edit_medicine['generic_name'] ?? ''); ?>" 
                                               placeholder="Nhập tên generic">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="barcode">Mã vạch</label>
                                        <input type="text" class="form-control" id="barcode" name="barcode" 
                                               value="<?php echo htmlspecialchars($edit_medicine['barcode'] ?? ''); ?>" 
                                               placeholder="Nhập mã vạch">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="manufacturer">Nhà sản xuất</label>
                                        <input type="text" class="form-control" id="manufacturer" name="manufacturer" 
                                               value="<?php echo htmlspecialchars($edit_medicine['manufacturer'] ?? ''); ?>" 
                                               placeholder="Nhập nhà sản xuất">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="unit">Đơn vị <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="unit" name="unit" 
                                               value="<?php echo htmlspecialchars($edit_medicine['unit'] ?? ''); ?>" 
                                               placeholder="Ví dụ: Viên, Hộp, Lọ" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="strength">Hàm lượng</label>
                                        <input type="text" class="form-control" id="strength" name="strength" 
                                               value="<?php echo htmlspecialchars($edit_medicine['strength'] ?? ''); ?>" 
                                               placeholder="Ví dụ: 500mg">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="dosage_form">Dạng bào chế</label>
                                        <input type="text" class="form-control" id="dosage_form" name="dosage_form" 
                                               value="<?php echo htmlspecialchars($edit_medicine['dosage_form'] ?? ''); ?>" 
                                               placeholder="Ví dụ: Viên nén, Dung dịch">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="selling_price">Giá bán <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="selling_price" name="selling_price" 
                                               value="<?php echo htmlspecialchars($edit_medicine['selling_price'] ?? ''); ?>" 
                                               placeholder="Nhập giá bán" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="min_stock_level">Tồn kho tối thiểu</label>
                                        <input type="number" class="form-control" id="min_stock_level" name="min_stock_level" 
                                               value="<?php echo htmlspecialchars($edit_medicine['min_stock_level'] ?? '0'); ?>" 
                                               placeholder="Nhập mức tồn kho tối thiểu" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="max_stock_level">Tồn kho tối đa</label>
                                        <input type="number" class="form-control" id="max_stock_level" name="max_stock_level" 
                                               value="<?php echo htmlspecialchars($edit_medicine['max_stock_level'] ?? '0'); ?>" 
                                               placeholder="Nhập mức tồn kho tối đa" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category_id">Danh mục</label>
                                        <select class="form-control" id="category_id" name="category_id">
                                            <option value="">Chọn danh mục</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" 
                                                        <?php echo ($edit_medicine && $edit_medicine['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="default_supplier_id">Nhà cung cấp mặc định</label>
                                        <select class="form-control" id="default_supplier_id" name="default_supplier_id">
                                            <option value="">Chọn nhà cung cấp</option>
                                            <?php foreach ($suppliers as $supplier): ?>
                                                <option value="<?php echo $supplier['id']; ?>" 
                                                        <?php echo ($edit_medicine && $edit_medicine['default_supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($supplier['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_controlled" name="is_controlled" 
                                                   <?php echo ($edit_medicine && $edit_medicine['is_controlled']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_controlled">Thuốc kiểm soát đặc biệt</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="requires_prescription" name="requires_prescription" 
                                                   <?php echo ($edit_medicine && $edit_medicine['requires_prescription']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="requires_prescription">Yêu cầu kê đơn</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="description">Mô tả</label>
                                <textarea class="form-control" id="description" name="description" rows="3" placeholder="Nhập mô tả"><?php echo htmlspecialchars($edit_medicine['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="storage_conditions">Điều kiện bảo quản</label>
                                <textarea class="form-control" id="storage_conditions" name="storage_conditions" rows="3" placeholder="Nhập điều kiện bảo quản"><?php echo htmlspecialchars($edit_medicine['storage_conditions'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="contraindications">Chống chỉ định</label>
                                <textarea class="form-control" id="contraindications" name="contraindications" rows="3" placeholder="Nhập chống chỉ định"><?php echo htmlspecialchars($edit_medicine['contraindications'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="side_effects">Tác dụng phụ</label>
                                <textarea class="form-control" id="side_effects" name="side_effects" rows="3" placeholder="Nhập tác dụng phụ"><?php echo htmlspecialchars($edit_medicine['side_effects'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="status">Trạng thái</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active" <?php echo (!$edit_medicine || $edit_medicine['status'] === 'active') ? 'selected' : ''; ?>>Hoạt động</option>
                                    <option value="inactive" <?php echo ($edit_medicine && $edit_medicine['status'] === 'inactive') ? 'selected' : ''; ?>>Ngừng hoạt động</option>
                                    <option value="discontinued" <?php echo ($edit_medicine && $edit_medicine['status'] === 'discontinued') ? 'selected' : ''; ?>>Ngừng kinh doanh</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $edit_medicine ? 'Cập nhật' : 'Thêm mới'; ?>
                            </button>
                            <?php if ($edit_medicine): ?>
                                <a href="<?php echo BASE_URL; ?>admin/medicines/" class="btn btn-secondary">
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

                <!-- Medicines List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list"></i> Danh sách thuốc
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-info">Tổng: <?php echo count($medicines); ?> thuốc</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="medicinesTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Mã thuốc</th>
                                        <th>Tên thuốc</th>
                                        <th>Danh mục</th>
                                        <th>Nhà cung cấp</th>
                                        <th>Đơn vị</th>
                                        <th>Giá bán</th>
                                        <th>Tồn kho</th>
                                        <th>Lô hoạt động</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($medicines)): ?>
                                        <tr>
                                            <td colspan="12" class="text-center">
                                                <i class="fas fa-inbox"></i> Chưa có thuốc nào
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $stt = 1; foreach ($medicines as $medicine): ?>
                                            <tr>
                                                <td><?php echo $stt++; ?></td>
                                                <td>
                                                    <span class="badge badge-secondary"><?php echo htmlspecialchars($medicine['medicine_code']); ?></span>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($medicine['name']); ?></strong>
                                                    <?php if (!empty($medicine['generic_name'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($medicine['generic_name']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($medicine['category_name'] ?: 'Chưa có'); ?></td>
                                                <td><?php echo htmlspecialchars($medicine['supplier_name'] ?: 'Chưa có'); ?></td>
                                                <td><?php echo htmlspecialchars($medicine['unit']); ?></td>
                                                <td><?php echo number_format($medicine['selling_price'], 2); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $medicine['total_quantity'] <= $medicine['min_stock_level'] ? 'danger' : 'info'; ?>">
                                                        <?php echo $medicine['total_quantity']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $medicine['active_batches']; ?></td>
                                                <td>
                                                    <?php if ($medicine['status'] === 'active'): ?>
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-check-circle"></i> Hoạt động
                                                        </span>
                                                    <?php elseif ($medicine['status'] === 'inactive'): ?>
                                                        <span class="badge badge-danger">
                                                            <i class="fas fa-times-circle"></i> Ngừng hoạt động
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">
                                                            <i class="fas fa-ban"></i> Ngừng kinh doanh
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small>
                                                        <?php 
                                                        $created_date = new DateTime($medicine['created_at']);
                                                        echo $created_date->format('d/m/Y H:i'); 
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="<?php echo BASE_URL; ?>admin/medicines/?action=edit&id=<?php echo $medicine['id']; ?>" 
                                                           class="btn btn-sm btn-warning" title="Sửa">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($medicine['active_batches'] == 0): ?>
                                                            <button type="button" class="btn btn-sm btn-danger delete-medicine" 
                                                                    data-id="<?php echo $medicine['id']; ?>" 
                                                                    data-name="<?php echo htmlspecialchars($medicine['name']); ?>"
                                                                    title="Xóa">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-secondary" 
                                                                    title="Không thể xóa - có <?php echo $medicine['active_batches']; ?> lô thuốc" disabled>
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
                <p>Bạn có chắc chắn muốn xóa thuốc <strong id="medicineName"></strong>?</p>
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
    $('#medicinesTable').DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "pageLength": 25,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
        },
        "order": [[1, "asc"]], // Sort by medicine code
        "columnDefs": [
            { "orderable": false, "targets": [11] } // Disable sorting for action column
        ]
    });

    // Auto-generate medicine code
    $('#generateCode').click(function() {
        $('#medicine_code').val('');
        $('#medicine_code').attr('placeholder', 'Mã sẽ được tự động tạo khi lưu');
    });

    // Format medicine code input
    $('#medicine_code').on('input', function() {
        let value = $(this).val().toUpperCase();
        if (value.startsWith('MED')) {
            value = 'MED' + value.substring(3).replace(/[^0-9]/g, '');
        } else {
            value = value.replace(/[^A-Z0-9]/g, '');
        }
        if (value.length > 7) {
            value = value.substring(0, 7);
        }
        $(this).val(value);
    });

    // Form validation
    $('#medicineForm').submit(function(e) {
        let isValid = true;
        let errors = [];

        // Validate name
        if ($('#name').val().trim() === '') {
            errors.push('Tên thuốc không được để trống');
            $('#name').addClass('is-invalid');
            isValid = false;
        } else {
            $('#name').removeClass('is-invalid');
        }

        // Validate unit
        if ($('#unit').val().trim() === '') {
            errors.push('Đơn vị thuốc không được để trống');
            $('#unit').addClass('is-invalid');
            isValid = false;
        } else {
            $('#unit').removeClass('is-invalid');
        }

        // Validate selling price
        let sellingPrice = $('#selling_price').val().trim();
        if (sellingPrice === '' || isNaN(sellingPrice) || sellingPrice < 0) {
            errors.push('Giá bán không hợp lệ');
            $('#selling_price').addClass('is-invalid');
            isValid = false;
        } else {
            $('#selling_price').removeClass('is-invalid');
        }

        // Validate stock levels
        let minStock = $('#min_stock_level').val().trim();
        let maxStock = $('#max_stock_level').val().trim();
        if (minStock === '' || isNaN(minStock) || minStock < 0) {
            errors.push('Mức tồn kho tối thiểu không hợp lệ');
            $('#min_stock_level').addClass('is-invalid');
            isValid = false;
        } else {
            $('#min_stock_level').removeClass('is-invalid');
        }
        if (maxStock === '' || isNaN(maxStock) || maxStock < minStock) {
            errors.push('Mức tồn kho tối đa không hợp lệ');
            $('#max_stock_level').addClass('is-invalid');
            isValid = false;
        } else {
            $('#max_stock_level').removeClass('is-invalid');
        }

        // Validate medicine code format if provided
        let medicineCode = $('#medicine_code').val().trim();
        if (medicineCode !== '' && !/^MED\d{4}$/.test(medicineCode)) {
            errors.push('Mã thuốc phải có định dạng MED + 4 chữ số (VD: MED0001)');
            $('#medicine_code').addClass('is-invalid');
            isValid = false;
        } else {
            $('#medicine_code').removeClass('is-invalid');
        }

        if (!isValid) {
            e.preventDefault();
            let errorMessage = 'Vui lòng kiểm tra lại thông tin:\n• ' + errors.join('\n• ');
            alert(errorMessage);
        }
    });

    // Delete medicine
    $('.delete-medicine').click(function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        
        $('#medicineName').text(name);
        $('#confirmDelete').attr('href', '<?php echo BASE_URL; ?>admin/medicines/?action=delete&id=' + id);
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