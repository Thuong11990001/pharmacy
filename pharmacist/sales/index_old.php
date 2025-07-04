<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// manager/sales/index.php
require_once '../../includes/config.php';
require_once '../../includes/session.php';

// Kiểm tra đăng nhập và quyền truy cập
SessionManager::requireRole(['admin', 'manager', 'pharmacist']);

// Xử lý các hành động
$action = $_GET['action'] ?? '';
$message = '';
$message_type = '';

// Function to generate next sale code
function generateSaleCode($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT sale_code 
            FROM sales 
            WHERE sale_code REGEXP '^SALE[0-9]{6}$' 
            ORDER BY CAST(SUBSTRING(sale_code, 5) AS UNSIGNED) DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $lastCode = $stmt->fetchColumn();
        
        if ($lastCode) {
            $lastNumber = (int)substr($lastCode, 4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return 'SALE' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        error_log("Error generating sale code: " . $e->getMessage());
        return 'SALE' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}

// Function to validate sale code format
function validateSaleCode($code) {
    return preg_match('/^SALE\d{6}$/', $code);
}

// Function to check if sale code exists
function saleCodeExists($pdo, $code, $excludeId = null) {
    try {
        if ($excludeId) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE sale_code = ? AND id != ?");
            $stmt->execute([$code, $excludeId]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE sale_code = ?");
            $stmt->execute([$code]);
        }
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking sale code: " . $e->getMessage());
        return false;
    }
}

// Xử lý thêm/sửa sale
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST Data: " . print_r($_POST, true));
    
    $sale_code = trim($_POST['sale_code'] ?? '');
    $sale_date = $_POST['sale_date'] ?? date('Y-m-d');
    $sale_time = $_POST['sale_time'] ?? date('H:i:s');
    $prescription_id = !empty($_POST['prescription_id']) ? (int)$_POST['prescription_id'] : null;
    $patient_id = !empty($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $subtotal = (float)($_POST['subtotal'] ?? 0.00);
    $discount_amount = (float)($_POST['discount_amount'] ?? 0.00);
    $tax_amount = (float)($_POST['tax_amount'] ?? 0.00);
    $total_amount = (float)($_POST['total_amount'] ?? 0.00);
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $payment_status = $_POST['payment_status'] ?? 'pending';
    $status = $_POST['status'] ?? 'draft';
    $employee_id = SessionManager::getUserInfo()['id'];
    $notes = trim($_POST['notes'] ?? '');
    $sale_id = $_POST['sale_id'] ?? '';
    $sale_details = $_POST['sale_details'] ?? [];

    // Validate required fields
    if (empty($sale_date) || empty($sale_time) || ($patient_id === null && empty($customer_name)) || empty($sale_details)) {
        $message = "Vui lòng nhập đầy đủ thông tin bán hàng!";
        $message_type = "danger";
        error_log("Validation failed: Missing required fields");
    } else {
        try {
            $pdo->beginTransaction();
            
            if ($sale_id) {
                // Cập nhật sale
                error_log("Updating sale ID: $sale_id");
                
                if (!empty($sale_code)) {
                    if (!validateSaleCode($sale_code)) {
                        throw new Exception("Mã giao dịch phải có định dạng SALE + 6 chữ số (VD: SALE000001)!");
                    } elseif (saleCodeExists($pdo, $sale_code, $sale_id)) {
                        throw new Exception("Mã giao dịch đã tồn tại!");
                    }
                } else {
                    $sale_code = generateSaleCode($pdo);
                    error_log("Generated new code for update: $sale_code");
                }
                
                $stmt = $pdo->prepare("
                    UPDATE sales 
                    SET sale_code = ?, sale_date = ?, sale_time = ?, prescription_id = ?, 
                        patient_id = ?, customer_name = ?, customer_phone = ?, 
                        subtotal = ?, discount_amount = ?, tax_amount = ?, total_amount = ?, 
                        payment_method = ?, payment_status = ?, status = ?, notes = ?, 
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $result = $stmt->execute([
                    $sale_code, $sale_date, $sale_time, $prescription_id, $patient_id,
                    $customer_name, $customer_phone, $subtotal, $discount_amount,
                    $tax_amount, $total_amount, $payment_method, $payment_status,
                    $status, $notes, $sale_id
                ]);
                
                // Xóa sale_details cũ
                $stmt = $pdo->prepare("DELETE FROM sale_details WHERE sale_id = ?");
                $stmt->execute([$sale_id]);
                
                // Thêm sale_details mới
                foreach ($sale_details as $detail) {
                    $medicine_id = (int)($detail['medicine_id'] ?? 0);
                    $quantity = (int)($detail['quantity'] ?? 0);
                    $unit_price = (float)($detail['unit_price'] ?? 0.00);
                    $discount = (float)($detail['discount_amount'] ?? 0.00);
                    $total_price = (float)($detail['total_price'] ?? 0.00);
                    $prescription_detail_id = !empty($detail['prescription_detail_id']) ? (int)$detail['prescription_detail_id'] : null;
                    
                    if ($medicine_id && $quantity > 0) {
                        // Lấy danh sách lô thuốc hợp lệ, ưu tiên số lượng thấp最も
                        $stmt = $pdo->prepare("
                            SELECT id, current_quantity, import_price AS cost_price
                            FROM medicine_batches
                            WHERE medicine_id = ?
                            AND status = 'active'
                            AND current_quantity > 0
                            AND expiry_date > CURRENT_DATE
                            ORDER BY current_quantity ASC, expiry_date ASC
                            LIMIT 1
                        ");
                        $stmt->execute([$medicine_id]);
                        $batch = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$batch) {
                            throw new Exception("Không có lô thuốc hợp lệ cho thuốc ID: $medicine_id");
                        }
                        
                        $batch_id = $batch['id'];
                        $available_quantity = $batch['current_quantity'];
                        $cost_price = $batch['cost_price'];
                        
                        if ($quantity > $available_quantity) {
                            throw new Exception("Số lượng tồn kho không đủ cho thuốc ID: $medicine_id");
                        }
                        
                        // Thêm vào sale_details
                        $stmt = $pdo->prepare("
                            INSERT INTO sale_details (sale_id, medicine_id, batch_id, quantity, unit_price, 
                                                    cost_price, discount_amount, total_price, prescription_detail_id)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $sale_id, $medicine_id, $batch_id, $quantity, $unit_price,
                            $cost_price, $discount, $total_price, $prescription_detail_id
                        ]);
                        
                        // Cập nhật tồn kho
                        $stmt = $pdo->prepare("UPDATE medicine_batches SET current_quantity = current_quantity - ? WHERE id = ?");
                        $stmt->execute([$quantity, $batch_id]);
                    }
                }
                
                if ($result) {
                    $pdo->commit();
                    $message = "Cập nhật giao dịch bán hàng thành công! Mã giao dịch: " . $sale_code;
                    $message_type = "success";
                    error_log("Sale updated successfully");
                } else {
                    $pdo->rollback();
                    throw new Exception("Không có thay đổi nào được thực hiện hoặc giao dịch không tồn tại!");
                }
            } else {
                // Thêm sale mới
                error_log("Adding new sale");
                
                if (empty($sale_code)) {
                    $sale_code = generateSaleCode($pdo);
                    error_log("Generated new code: $sale_code");
                } else {
                    if (!validateSaleCode($sale_code)) {
                        throw new Exception("Mã giao dịch phải có định dạng SALE + 6 chữ số (VD: SALE000001)!");
                    } elseif (saleCodeExists($pdo, $sale_code)) {
                        $old_code = $sale_code;
                        $sale_code = generateSaleCode($pdo);
                        error_log("Code $old_code exists, generated new: $sale_code");
                        $message = "Mã giao dịch đã tồn tại! Đã tự động tạo mã mới: " . $sale_code;
                        $message_type = "warning";
                    }
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO sales (sale_code, sale_date, sale_time, prescription_id, patient_id,
                                     customer_name, customer_phone, subtotal, discount_amount,
                                     tax_amount, total_amount, payment_method, payment_status,
                                     status, employee_id, notes, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $result = $stmt->execute([
                    $sale_code, $sale_date, $sale_time, $prescription_id, $patient_id,
                    $customer_name, $customer_phone, $subtotal, $discount_amount,
                    $tax_amount, $total_amount, $payment_method, $payment_status,
                    $status, $employee_id, $notes
                ]);
                
                if ($result) {
                    $new_id = $pdo->lastInsertId();
                    
                    // Thêm sale_details
                    foreach ($sale_details as $detail) {
                        
                        $medicine_id = (int)($detail['medicine_id'] ?? 0);
                        $quantity = (int)($detail['quantity'] ?? 0);
                        $unit_price = (float)($detail['unit_price'] ?? 0.00);
                        $discount = (float)($detail['discount_amount'] ?? 0.00);
                        $total_price = (float)($detail['total_price'] ?? 0.00);
                        $prescription_detail_id = !empty($detail['prescription_detail_id']) ? (int)$detail['prescription_detail_id'] : null;
                        
                        if ($medicine_id && $quantity > 0) {
                            // Lấy danh sách lô thuốc hợp lệ, ưu tiên số lượng thấp nhất
                            $stmt = $pdo->prepare("
                                SELECT id, current_quantity, import_price AS cost_price
                                FROM medicine_batches
                                WHERE medicine_id = ?
                                AND status = 'active'
                                AND current_quantity > 0
                                AND expiry_date > CURRENT_DATE
                                ORDER BY current_quantity ASC, expiry_date ASC
                                LIMIT 1
                            ");
                            $stmt->execute([$medicine_id]);
                            $batch = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if (!$batch) {
                                throw new Exception("Không có lô thuốc hợp lệ cho thuốc ID: $medicine_id");
                            }
                            
                            $batch_id = $batch['id'];
                            $available_quantity = $batch['current_quantity'];
                            $cost_price = $batch['cost_price'];
                            
                            if ($quantity > $available_quantity) {
                                throw new Exception("Số lượng tồn kho không đủ cho thuốc ID: $medicine_id");
                            }
                            
                            // Thêm vào sale_details
                            $stmt = $pdo->prepare("
                                INSERT INTO sale_details (sale_id, medicine_id, batch_id, quantity, unit_price, 
                                                        cost_price, discount_amount, total_price, prescription_detail_id)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $new_id, $medicine_id, $batch_id, $quantity, $unit_price,
                                $cost_price, $discount, $total_price, $prescription_detail_id
                            ]);
                            
                            // Cập nhật tồn kho
                            $stmt = $pdo->prepare("UPDATE medicine_batches SET current_quantity = current_quantity - ? WHERE id = ?");
                            $stmt->execute([$quantity, $batch_id]);
                        }
                    }
                    
                    $pdo->commit();
                    $message = "Thêm giao dịch bán hàng thành công! Mã giao dịch: " . $sale_code;
                    $message_type = "success";
                    error_log("Sale added successfully with ID: $new_id");
                } else {
                    $pdo->rollback();
                    throw new Exception("Không thể thêm giao dịch vào cơ sở dữ liệu!");
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
    
    if ($message_type === 'success') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $message_type;
        header('Location: ' . BASE_URL . 'manager/sales/');
        exit();
    }
}

// Check for flash messages
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Xử lý xóa sale
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $pdo->beginTransaction();
        
        // Kiểm tra trạng thái giao dịch
        $stmt = $pdo->prepare("SELECT status FROM sales WHERE id = ?");
        $stmt->execute([$id]);
        $sale_status = $stmt->fetchColumn();
        
        if ($sale_status !== 'draft') {
            $message = "Chỉ có thể xóa giao dịch ở trạng thái nháp!";
            $message_type = "danger";
        } else {
            // Xóa sale_details
            $stmt = $pdo->prepare("DELETE FROM sale_details WHERE sale_id = ?");
            $stmt->execute([$id]);
            
            // Xóa sale
            $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                $pdo->commit();
                $_SESSION['flash_message'] = "Xóa giao dịch thành công!";
                $_SESSION['flash_type'] = "success";
            } else {
                $pdo->rollback();
                $message = "Không tìm thấy giao dịch để xóa!";
                $message_type = "danger";
            }
        }
    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Delete error: " . $e->getMessage());
        $message = "Lỗi: " . $e->getMessage();
        $message_type = "danger";
    }
    
    if (!$message) {
        header('Location: ' . BASE_URL . 'manager/sales/');
        exit();
    }
}

// Lấy danh sách sales từ view
try {
    $stmt = $pdo->prepare("SELECT * FROM v_sales_summary ORDER BY sale_date DESC, sale_time DESC");
    $stmt->execute();
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sales = [];
    error_log("Error fetching sales: " . $e->getMessage());
}

// Lấy danh sách patients, prescriptions, medicines cho dropdown
try {
    $stmt = $pdo->prepare("SELECT id, full_name FROM patients WHERE status = 'active' ORDER BY full_name");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Load all prescriptions initially (will be filtered by JavaScript)
    $stmt = $pdo->prepare("SELECT id, prescription_code, patient_id FROM prescriptions WHERE status IN ('pending', 'partial') ORDER BY prescription_date DESC");
    $stmt->execute();
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT id, name, medicine_code FROM medicines WHERE status = 'active' ORDER BY name");
    $stmt->execute();
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $patients = $prescriptions = $medicines = [];
    error_log("Error fetching dropdown data: " . $e->getMessage());
}

// Lấy thông tin sale để edit
$edit_sale = null;
$edit_sale_details = [];
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
        $stmt->execute([$id]);
        $edit_sale = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($edit_sale) {
            $stmt = $pdo->prepare("SELECT * FROM sale_details WHERE sale_id = ?");
            $stmt->execute([$id]);
            $edit_sale_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $_SESSION['flash_message'] = "Không tìm thấy giao dịch để chỉnh sửa!";
            $_SESSION['flash_type'] = "danger";
            header('Location: ' . BASE_URL . 'manager/sales/');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error fetching sale for edit: " . $e->getMessage());
        $_SESSION['flash_message'] = "Lỗi khi tải thông tin giao dịch!";
        $_SESSION['flash_type'] = "danger";
        header('Location: ' . BASE_URL . 'manager/sales/');
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
    <title>Quản lý Giao dịch Bán hàng - Pharmacy Management System</title>
    
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
                    <img src="https://adminlte.io/themes/v3/dist/img/user2-160x-Netflix-Style-Avatar" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="#" class="d-block"><?php echo htmlspecialchars($user_info['full_name'] ?? 'User'); ?></a>
                </div>
            </div>
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item">
                        <a href="<?php echo BASE_URL; ?>manager/" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo BASE_URL; ?>manager/sales/" class="nav-link active">
                            <i class="nav-icon fas fa-shopping-cart"></i>
                            <p>Giao dịch bán hàng</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo BASE_URL; ?>logout.php" class="nav-link">
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
                    <form method="POST" id="saleForm">
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
                                                // Filter prescriptions for the selected patient when editing
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

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
<!-- DataTables  & Plugins -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.responsive.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>

<script>
$(function () {
    // Initialize DataTable
    $("#salesTable").DataTable({
        "responsive": true, 
        "lengthChange": false, 
        "autoWidth": false,
        "pageLength": 25,
        "order": [[ 2, "desc" ]], // Sort by date and time
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
        },
        "columnDefs": [
            { "orderable": false, "targets": [9] }, // Disable sorting for action column
            { "width": "5%", "targets": [0] }, // STT column
            { "width": "10%", "targets": [1] }, // Code column
            { "width": "15%", "targets": [2] }, // Date time column
            { "width": "15%", "targets": [3] }, // Customer column
            { "width": "10%", "targets": [4] }, // Total amount column
            { "width": "10%", "targets": [5] }, // Payment method column
            { "width": "10%", "targets": [6] }, // Status column
            { "width": "15%", "targets": [7] }, // Employee column
            { "width": "5%", "targets": [8] }, // Item count column
            { "width": "10%", "targets": [9] }  // Actions column
        ]
    });

    // Generate sale code button
    $("#generateCode").click(function() {
        $("#sale_code").val('');
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Đang tạo...');
        setTimeout(() => {
            $(this).html('<i class="fas fa-magic"></i> Tự động');
            $("#sale_code").attr('placeholder', 'Mã sẽ được tự động tạo khi lưu');
        }, 500);
    });

    // Sale code format validation
    $("#sale_code").on('input', function() {
        let value = $(this).val().toUpperCase();
        let isValid = /^SALE\d{6}$/.test(value) || value === '';
        
        if (value && !isValid) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback"> Mã giao dịch phải có định dạng SALE + 6 chữ số (VD: SALE000001)</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
        
        if (value !== $(this).val()) {
            $(this).val(value);
        }
    });

    // Update prescriptions when patient changes
$("#patient_id").on('change', function() {
    const patientId = $(this).val();
    const prescriptionSelect = $("#prescription_id");
    
    // Clear current prescriptions
    prescriptionSelect.html('<option value="">-- Chọn đơn thuốc --</option>');
    
    if (patientId) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>manager/sales/get_patient_prescriptions.php',
            method: 'GET',
            data: { patient_id: patientId },
            success: function(data) {
                console.log('Raw response:', data);
                try {
                    const prescriptions = JSON.parse(data);
                    if (prescriptions.error) {
                        console.error('Server error:', prescriptions.error);
                        alert('Lỗi: ' + prescriptions.error);
                        return;
                    }
                    if (Array.isArray(prescriptions) && prescriptions.length === 0) {
                        console.log('No prescriptions found for patient_id:', patientId);
                        alert('Không có đơn thuốc nào cho bệnh nhân này!');
                        return;
                    }
                    prescriptions.forEach(function(prescription) {
                        prescriptionSelect.append(
                            `<option value="${prescription.id}">${prescription.prescription_code}</option>`
                        );
                    });
                } catch (e) {
                    console.error('Error parsing prescriptions:', e, 'Raw data:', data);
                    alert('Lỗi khi phân tích dữ liệu đơn thuốc! Kiểm tra console để biết thêm chi tiết.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error, 'Status code:', xhr.status);
                let errorMsg = 'Lỗi khi tải danh sách đơn thuốc!';
                if (xhr.status === 404) {
                    errorMsg = 'Không tìm thấy tệp get_patient_prescriptions.php!';
                } else if (xhr.status === 403) {
                    errorMsg = 'Bạn không có quyền truy cập!';
                } else if (xhr.status === 500) {
                    errorMsg = 'Lỗi máy chủ khi tải danh sách đơn thuốc!';
                }
                alert(errorMsg);
            }
        });
    }
});
    // Dynamic sale details
    let detailIndex = <?php echo count($edit_sale_details) ?: 1; ?>;
    $("#addSaleDetail").click(function() {
        const newRow = `
            <div class="sale-detail-row mb-3 p-3 border rounded">
                <div class="row">
                    <div class="col-md-4">
                        <label>Thuốc</label>
                        <select class="form-control medicine-select" name="sale_details[${detailIndex}][medicine_id]">
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
                               name="sale_details[${detailIndex}][quantity]" min="1">
                    </div>
                    <div class="col-md-3">
                        <label>Đơn giá</label>
                        <input type="number" class="form-control unit-price-input" 
                               name="sale_details[${detailIndex}][unit_price]" step="0.01">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm remove-detail mt-4">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <input type="hidden" name="sale_details[${detailIndex}][cost_price]" value="0">
                <input type="hidden" name="sale_details[${detailIndex}][discount_amount]" value="0">
                <input type="hidden" name="sale_details[${detailIndex}][total_price]" class="total-price-input" value="0">
                <input type="hidden" name="sale_details[${detailIndex}][prescription_detail_id]" value="">
            </div>`;
        $("#sale_details_container").append(newRow);
        detailIndex++;
    });

    // Remove sale detail
    $(document).on('click', '.remove-detail', function() {
        if ($('.sale-detail-row').length > 1) {
            $(this).closest('.sale-detail-row').remove();
            updateTotal();
        } else {
            alert('Phải có ít nhất một chi tiết giao dịch!');
        }
    });

    // Update unit price when medicine changes
    $(document).on('change', '.medicine-select', function() {
        const row = $(this).closest('.sale-detail-row');
        const medicineId = $(this).val();
        
        if (medicineId) {
            $.ajax({
                url: '<?php echo BASE_URL; ?>manager/sales/get_medicine_price.php',
                method: 'GET',
                data: { medicine_id: medicineId },
                success: function(data) {
                    try {
                        const result = JSON.parse(data);
                        if (result.selling_price) {
                            row.find('.unit-price-input').val(result.selling_price);
                            updateTotal();
                        } else {
                            console.warn('No price found for medicine:', medicineId);
                            row.find('.unit-price-input').val('0.00');
                        }
                    } catch (e) {
                        console.error('Error parsing price:', e);
                        row.find('.unit-price-input').val('0.00');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    row.find('.unit-price-input').val('0.00');
                }
            });
        } else {
            row.find('.unit-price-input').val('0.00');
        }
        updateTotal();
    });

    // Update total when quantity or unit price changes
    $(document).on('input', '.quantity-input, .unit-price-input', function() {
        updateTotal();
    });

    // Update subtotal and total amount
    function updateTotal() {
        let subtotal = 0;
        $('.sale-detail-row').each(function() {
            const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
            const unitPrice = parseFloat($(this).find('.unit-price-input').val()) || 0;
            const total = quantity * unitPrice;
            $(this).find('.total-price-input').val(total.toFixed(2));
            subtotal += total;
        });
        
        $('#subtotal').val(subtotal.toFixed(2));
        const discount = parseFloat($('#discount_amount').val()) || 0;
        const tax = parseFloat($('#tax_amount').val()) || 0;
        const total = subtotal - discount + tax;
        $('#total_amount').val(total.toFixed(2));
    }

    // Form validation before submit
    $("#saleForm").submit(function(e) {
        const saleCode = $("#sale_code").val();
        const saleDate = $("#sale_date").val();
        const saleTime = $("#sale_time").val();
        const patientId = $("#patient_id").val();
        const customerName = $("#customer_name").val().trim();
        const details = $('.sale-detail-row');
        
        if (!saleDate || !saleTime) {
            alert('Vui lòng nhập ngày và giờ bán!');
            e.preventDefault();
            return false;
        }
        
        if (!patientId && !customerName) {
            alert('Vui lòng chọn bệnh nhân hoặc nhập tên khách hàng!');
            e.preventDefault();
            return false;
        }
        
        if (saleCode && !/^SALE\d{6}$/.test(saleCode)) {
            alert('Mã giao dịch phải có định dạng SALE + 6 chữ số (VD: SALE000001)!');
            $("#sale_code").focus();
            e.preventDefault();
            return false;
        }
        
        let validDetails = false;
        details.each(function() {
            const medicineId = $(this).find('.medicine-select').val();
            const quantity = $(this).find('.quantity-input').val();
            if (medicineId && quantity > 0) {
                validDetails = true;
            }
        });
        
        if (!validDetails) {
            alert('Vui lòng thêm ít nhất một chi tiết giao dịch hợp lệ!');
            e.preventDefault();
            return false;
        }
        
        return true;
    });

    // Auto-dismiss alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Tooltip initialization
    $('[title]').tooltip();
});

// Auto-refresh page every 5 minutes
setInterval(function() {
    if (!document.hidden) {
        location.reload();
    }
}, 300000);
</script>

</body>
</html>