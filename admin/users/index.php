<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// admin/users/index.php
require_once '../../includes/config.php';
require_once '../../includes/session.php';

// Kiểm tra đăng nhập và quyền truy cập
SessionManager::requireRole(['admin']);

// Xử lý các hành động
$action = $_GET['action'] ?? '';
$message = '';
$message_type = '';

// Function to generate next user code
function generateUserCode($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT user_code 
            FROM users 
            WHERE user_code REGEXP '^USR[0-9]{4}$' 
            ORDER BY CAST(SUBSTRING(user_code, 4) AS UNSIGNED) DESC 
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
        
        return 'USR' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        error_log("Error generating user code: " . $e->getMessage());
        return 'USR' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

// Function to validate user code format
function validateUserCode($code) {
    return preg_match('/^USR\d{4}$/', $code);
}

// Function to check if user code or username exists
function userCodeOrUsernameExists($pdo, $user_code, $username, $excludeId = null) {
    try {
        if ($excludeId) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (user_code = ? OR username = ?) AND id != ?");
            $stmt->execute([$user_code, $username, $excludeId]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_code = ? OR username = ?");
            $stmt->execute([$user_code, $username]);
        }
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking user code/username: " . $e->getMessage());
        return false;
    }
}

// Function to validate email format
function validateEmail($email) {
    return empty($email) || filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate phone format
function validatePhone($phone) {
    return empty($phone) || preg_match('/^[0-9]{10,12}$/', $phone);
}

// Xử lý thêm/sửa user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_code = trim($_POST['user_code'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? '';
    $can_sell_controlled = isset($_POST['can_sell_controlled']) ? 1 : 0;
    $status = $_POST['status'] ?? 'active';
    $user_id = $_POST['user_id'] ?? '';

    // Validate required fields
    if (empty($username) || empty($full_name) || empty($role)) {
    $message = "Tên đăng nhập, họ tên và vai trò không được để trống!";
    $message_type = "danger";
    error_log("Validation failed: Required fields missing");
} elseif (!$user_id && empty($password)) {
    // Chỉ kiểm tra mật khẩu khi thêm mới (không có user_id)
    $message = "Mật khẩu không được để trống khi thêm người dùng mới!";
    $message_type = "danger";
    error_log("Validation failed: Password required for new user");
} elseif (!validateEmail($email)) {
    $message = "Định dạng email không hợp lệ!";
    $message_type = "danger";
    error_log("Validation failed: Invalid email format");
} elseif (!validatePhone($phone)) {
    $message = "Số điện thoại phải chứa 10-12 chữ số!";
    $message_type = "danger";
    error_log("Validation failed: Invalid phone format");
} else {
        try {
            $pdo->beginTransaction();

            if ($user_id) {
                // Cập nhật user
                if (!empty($user_code) && !validateUserCode($user_code)) {
                    throw new Exception("Mã người dùng phải có định dạng USR + 4 chữ số (VD: USR0001)!");
                } elseif (userCodeOrUsernameExists($pdo, $user_code, $username, $user_id)) {
                    throw new Exception("Mã người dùng hoặc tên đăng nhập đã tồn tại!");
                }

                if (empty($user_code)) {
                    $user_code = generateUserCode($pdo);
                }

                $sql = "UPDATE users SET user_code = ?, username = ?, full_name = ?, email = ?, phone = ?, role = ?, can_sell_controlled = ?, status = ?, updated_at = CURRENT_TIMESTAMP";
                $params = [$user_code, $username, $full_name, $email ?: null, $phone ?: null, $role, $can_sell_controlled, $status];

                if (!empty($password)) {
                    $sql .= ", password = ?";
                    $params[] = password_hash($password, PASSWORD_BCRYPT);
                }
                $sql .= " WHERE id = ?";
                $params[] = $user_id;

                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute($params);

                if ($result && $stmt->rowCount() > 0) {
                    $pdo->commit();
                    $message = "Cập nhật người dùng thành công! Mã người dùng: " . $user_code;
                    $message_type = "success";
                    error_log("User updated successfully");
                } else {
                    $pdo->rollback();
                    throw new Exception("Không có thay đổi nào được thực hiện hoặc người dùng không tồn tại!");
                }
            } else {
    // Thêm user mới
    if (empty($user_code)) {
        $user_code = generateUserCode($pdo);
    } elseif (!validateUserCode($user_code)) {
        throw new Exception("Mã người dùng phải có định dạng USR + 4 chữ số (VD: USR0001)!");
    } elseif (userCodeOrUsernameExists($pdo, $user_code, $username)) {
        $user_code = generateUserCode($pdo);
        $message = "Mã người dùng hoặc tên đăng nhập đã tồn tại! Đã tự động tạo mã mới: " . $user_code;
        $message_type = "warning";
    }

                $stmt = $pdo->prepare("
                    INSERT INTO users (user_code, username, password, full_name, email, phone, role, can_sell_controlled, status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $result = $stmt->execute([
                    $user_code,
                    $username,
                    password_hash($password, PASSWORD_BCRYPT),
                    $full_name,
                    $email ?: null,
                    $phone ?: null,
                    $role,
                    $can_sell_controlled,
                    $status
                ]);

                if ($result) {
                    $pdo->commit();
                    $message = $message_type === 'warning' ? $message : "Thêm người dùng thành công! Mã người dùng: " . $user_code;
                    $message_type = "success";
                    error_log("User added successfully");
                } else {
                    $pdo->rollback();
                    throw new Exception("Không thể thêm người dùng vào cơ sở dữ liệu!");
                }
            }
        } catch (PDOException $e) {
            $pdo->rollback();
            error_log("Database error: " . $e->getMessage());
            $message = "Lỗi cơ sở dữ liệu: " . $e->getMessage();
            $message_type = "danger";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollback();
            }
            error_log("General error: " . $e->getMessage());
            $message = $e->getMessage();
            $message_type = "danger";
        }
    }

    if ($message_type === 'success') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $message_type;
        header('Location: ' . BASE_URL . 'admin/users/');
        exit();
    }
}

// Check for flash messages
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Xử lý xóa user
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $result = $stmt->execute([$id]);

        if ($result && $stmt->rowCount() > 0) {
            $pdo->commit();
            $_SESSION['flash_message'] = "Xóa người dùng thành công!";
            $_SESSION['flash_type'] = "success";
        } else {
            $pdo->rollback();
            $message = "Không tìm thấy người dùng để xóa!";
            $message_type = "danger";
        }
    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Delete error: " . $e->getMessage());
        $message = "Lỗi: " . $e->getMessage();
        $message_type = "danger";
    }

    if (!$message) {
        header('Location: ' . BASE_URL . 'admin/users/');
        exit();
    }
}

// Lấy danh sách users
try {
    $stmt = $pdo->prepare("SELECT * FROM users ORDER BY user_code ASC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    error_log("Error fetching users: " . $e->getMessage());
}

// Lấy thông tin user để edit
$edit_user = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$edit_user) {
            $_SESSION['flash_message'] = "Không tìm thấy người dùng để chỉnh sửa!";
            $_SESSION['flash_type'] = "danger";
            header('Location: ' . BASE_URL . 'admin/users/');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error fetching user for edit: " . $e->getMessage());
        $_SESSION['flash_message'] = "Lỗi khi tải thông tin người dùng!";
        $_SESSION['flash_type'] = "danger";
        header('Location: ' . BASE_URL . 'admin/users/');
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
    <title>Quản lý Người dùng - Pharmacy Management System</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <div class="preloader flex-column justify-content-center align-items-center">
        <img class="animation__shake" src="https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png" alt="AdminLTELogo" height="60" width="60">
    </div>

    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?php echo BASE_URL; ?>admin/" class="nav-link">Trang chủ</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?php echo BASE_URL; ?>admin/users/" class="nav-link">Người dùng</a>
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

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="<?php echo BASE_URL; ?>admin/" class="brand-link">
            <img src="https://руса

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
                    <li class="nav-item">
                        <a href="<?php echo BASE_URL; ?>admin/" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
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
                    <li class="nav-item">
                        <a href="<?php echo BASE_URL; ?>admin/reports/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/reports/') !== false ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-chart-pie"></i>
                            <p>Báo cáo</p>
                        </a>
                    </li>
                    <li class="nav-header">HỆ THỐNG</li>
                    <?php if ($user_info['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a href="<?php echo BASE_URL; ?>admin/settings/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/settings/') !== false ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tools"></i>
                            <p>Cài đặt hệ thống</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a href="#" class="nav-link" onclick="showSystemInfo()">
                            <i class="nav-icon fas fa-info-circle"></i>
                            <p>Thông tin hệ thống</p>
                        </a>
                    </li>
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

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Quản lý Người dùng</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>admin/">Trang chủ</a></li>
                            <li class="breadcrumb-item active">Người dùng</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

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

                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-<?php echo $edit_user ? 'edit' : 'plus'; ?>"></i>
                            <?php echo $edit_user ? 'Sửa người dùng' : 'Thêm người dùng mới'; ?>
                        </h3>
                        <?php if ($edit_user): ?>
                            <div class="card-tools">
                                <a href="<?php echo BASE_URL; ?>admin/users/" class="btn btn-tool">
                                    <i class="fas fa-times"></i> Hủy
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" id="userForm">
                        <div class="card-body">
                            <?php if ($edit_user): ?>
                                <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="user_code">Mã người dùng</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="user_code" name="user_code" 
                                                   value="<?php echo htmlspecialchars($edit_user['user_code'] ?? ''); ?>" 
                                                   placeholder="Để trống để tự động tạo" maxlength="7">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" id="generateCode">
                                                    <i class="fas fa-magic"></i> Tự động
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">
                                            Mã người dùng tự động theo thứ tự USR0001, USR0002... Để trống để hệ thống tự tạo.
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="username">Tên đăng nhập <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo htmlspecialchars($edit_user['username'] ?? ''); ?>" 
                                               placeholder="Nhập tên đăng nhập" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">Mật khẩu <?php echo $edit_user ? '' : '<span class="text-danger">*</span>'; ?></label>
                                        <input type="password" class="form-control" id="password" name="password" 
                                               placeholder="Nhập mật khẩu <?php echo $edit_user ? '(Để trống nếu không thay đổi)' : ''; ?>" 
                                               <?php echo $edit_user ? '' : 'required'; ?>>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="full_name">Họ và tên <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo htmlspecialchars($edit_user['full_name'] ?? ''); ?>" 
                                               placeholder="Nhập họ và tên" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>" 
                                               placeholder="Nhập email">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Số điện thoại</label>
                                        <input type="text" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($edit_user['phone'] ?? ''); ?>" 
                                               placeholder="Nhập số điện thoại">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="role">Vai trò <span class="text-danger">*</span></label>
                                        <select class="form-control" id="role" name="role" required>
                                            <option value="admin" <?php echo ($edit_user && $edit_user['role'] == 'admin') ? 'selected' : ''; ?>>Quản trị viên</option>
                                            <option value="pharmacist" <?php echo ($edit_user && $edit_user['role'] == 'pharmacist') ? 'selected' : ''; ?>>Dược sĩ</option>
                                            <option value="cashier" <?php echo ($edit_user && $edit_user['role'] == 'cashier') ? 'selected' : ''; ?>>Thu ngân</option>
                                            <option value="manager" <?php echo ($edit_user && $edit_user['role'] == 'manager') ? 'selected' : ''; ?>>Quản lý</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status">Trạng thái</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="active" <?php echo (!$edit_user || $edit_user['status'] == 'active') ? 'selected' : ''; ?>>Hoạt động</option>
                                            <option value="inactive" <?php echo ($edit_user && $edit_user['status'] == 'inactive') ? 'selected' : ''; ?>>Không hoạt động</option>
                                            <option value="suspended" <?php echo ($edit_user && $edit_user['status'] == 'suspended') ? 'selected' : ''; ?>>Tạm đình chỉ</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="can_sell_controlled" 
                                           name="can_sell_controlled" value="1"
                                           <?php echo ($edit_user && $edit_user['can_sell_controlled']) ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="can_sell_controlled">Có quyền bán thuốc kiểm soát</label>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 
                                <?php echo $edit_user ? 'Cập nhật' : 'Thêm mới'; ?>
                            </button>
                            <?php if ($edit_user): ?>
                                <a href="<?php echo BASE_URL; ?>admin/users/" class="btn btn-secondary ml-2">
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

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> Danh sách người dùng</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="usersTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Mã người dùng</th>
                                        <th>Tên đăng nhập</th>
                                        <th>Họ và tên</th>
                                        <th>Email</th>
                                        <th>Số điện thoại</th>
                                        <th>Vai trò</th>
                                        <th>Quyền bán thuốc kiểm soát</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $index => $user): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <code><?php echo htmlspecialchars($user['user_code']); ?></code>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email'] ?? '--'); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone'] ?? '--'); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $user['role'] == 'admin' ? 'primary' : ($user['role'] == 'pharmacist' ? 'success' : ($user['role'] == 'cashier' ? 'warning' : 'info')); ?>">
                                                    <?php echo $user['role'] == 'admin' ? 'Quản trị viên' : ($user['role'] == 'pharmacist' ? 'Dược sĩ' : ($user['role'] == 'cashier' ? 'Thu ngân' : 'Quản lý')); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($user['can_sell_controlled']): ?>
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-exclamation-triangle"></i> Có
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Không</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $user['status'] == 'active' ? 'success' : ($user['status'] == 'inactive' ? 'secondary' : 'warning'); ?>">
                                                    <?php echo $user['status'] == 'active' ? 'Hoạt động' : ($user['status'] == 'inactive' ? 'Không hoạt động' : 'Tạm đình chỉ'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="<?php echo BASE_URL; ?>admin/users/?action=edit&id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-warning" title="Sửa">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?php echo BASE_URL; ?>admin/users/?action=delete&id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-danger" title="Xóa"
                                                       onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng \'<?php echo htmlspecialchars($user['full_name']); ?>\' không?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
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

    <aside class="control-sidebar control-sidebar-dark">
    </aside>

    <footer class="main-footer">
        <strong>Copyright &copy; 2024 <a href="#">Pharmacy Management System</a>.</strong>
        All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> 1.0.0
        </div>
    </footer>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.responsive.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/responsive.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>

<script>
$(function () {
    $("#usersTable").DataTable({
        "responsive": true, 
        "lengthChange": false, 
        "autoWidth": false,
        "pageLength": 25,
        "order": [[ 1, "asc" ]],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
        },
        "columnDefs": [
            { "orderable": false, "targets": [9] },
            { "width": "5%", "targets": [0] },
            { "width": "10%", "targets": [1] },
            { "width": "10%", "targets": [2] },
            { "width": "15%", "targets": [3] },
            { "width": "15%", "targets": [4] },
            { "width": "10%", "targets": [5] },
            { "width": "10%", "targets": [6] },
            { "width": "10%", "targets": [7] },
            { "width": "10%", "targets": [8] },
            { "width": "6%", "targets": [9] }
        ]
    });

    $("#generateCode").click(function() {
        $("#user_code").val('');
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Đang tạo...');
        setTimeout(() => {
            $(this).html('<i class="fas fa-magic"></i> Tự động');
            $("#user_code").attr('placeholder', 'Mã sẽ được tự động tạo khi lưu');
        }, 500);
    });

    $("#user_code").on('input', function() {
        let value = $(this).val().toUpperCase();
        let isValid = /^USR\d{4}$/.test(value) || value === '';
        
        if (value && !isValid) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Mã người dùng phải có định dạng USR + 4 chữ số (VD: USR0001)</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
        
        if (value !== $(this).val()) {
            $(this).val(value);
        }
    });

    $("#userForm").submit(function(e) {
        let userCode = $("#user_code").val();
        let username = $("#username").val().trim();
        let fullName = $("#full_name").val().trim();
        let password = $("#password").val();
        let role = $("#role").val();
        let email = $("#email").val();
        let phone = $("#phone").val();
        
        if (!username || !fullName || !role) {
            alert('Vui lòng nhập tên đăng nhập, họ tên và vai trò!');
            e.preventDefault();
            return false;
        }

        <?php if (!$edit_user): ?>
if (!password) {
    alert('Vui lòng nhập mật khẩu!');
    $("#password").focus();
    e.preventDefault();
    return false;
}
<?php endif; ?>
        
        if (userCode && !/^USR\d{4}$/.test(userCode)) {
            alert('Mã người dùng phải có định dạng USR + 4 chữ số (VD: USR0001)!');
            $("#user_code").focus();
            e.preventDefault();
            return false;
        }

        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            alert('Định dạng email không hợp lệ!');
            $("#email").focus();
            e.preventDefault();
            return false;
        }

        if (phone && !/^[0-9]{10,12}$/.test(phone)) {
            alert('Số điện thoại phải chứa 10-12 chữ số!');
            $("#phone").focus();
            e.preventDefault();
            return false;
        }
        
        return true;
    });

    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    $('[title]').tooltip();
});

setInterval(function() {
    if (!document.hidden) {
        location.reload();
    }
}, 300000);
</script>

</body>
</html>