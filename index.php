<?php
// index.php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'models/User.php';

// Nếu đã đăng nhập, chuyển hướng đến dashboard theo role
if (SessionManager::isLoggedIn()) {
    $user_data = SessionManager::getUserInfo();
    $user_role = $user_data && isset($user_data['role']) ? $user_data['role'] : 'user';
    
    // Redirect theo role
    switch ($user_role) {
        case 'admin':
            header("Location: admin/index.php");
            break;
        case 'manager':
            header("Location: manager/index.php");
            break;
        case 'pharmacist':
            header("Location: pharmacist/index.php");
            break;
        case 'cashier':
            header("Location: cashier/index.php");
            break;
        default:
            header("Location: user/index.php");
            break;
    }
    exit();
}

$error_message = '';
$success_message = '';

// Xử lý thông báo từ URL parameters
if (isset($_GET['logout'])) {
    $success_message = "Đăng xuất thành công!";
}

if (isset($_GET['timeout'])) {
    $error_message = "Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.";
}

if (isset($_GET['security'])) {
    $error_message = "Phát hiện bất thường trong phiên đăng nhập. Vui lòng đăng nhập lại.";
}

if (isset($_GET['access_denied'])) {
    $error_message = "Bạn không có quyền truy cập vào trang này.";
}

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);
    
    if (empty($username) || empty($password)) {
        $error_message = "Vui lòng nhập tên đăng nhập và mật khẩu.";
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            if ($db) {
                $user = new User($db);
                $user_data = $user->login($username, $password);
                
                if ($user_data) {
                    // Kiểm tra trạng thái tài khoản
                    if ($user_data['status'] !== 'active') {
                        $error_message = "Tài khoản của bạn đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.";
                    } else {
                        // Đăng nhập thành công
                        SessionManager::login($user_data);
                        
                        // Xử lý "Remember Me" (có thể implement sau)
                        if ($remember_me) {
                            $token = bin2hex(random_bytes(32));
                            setcookie('remember_token', $token, time() + (86400 * 30), "/", "", false, true); // 30 days
                            // TODO: Lưu token vào database (cần tạo bảng remember_tokens)
                        }
                        
                        // Chuyển hướng đến dashboard theo role
                        $user_role = $user_data['role'] ?? 'user';
                        
                        switch ($user_role) {
                            case 'admin':
                                header("Location: admin/index.php");
                                break;
                            case 'manager':
                                header("Location: manager/index.php");
                                break;
                            case 'pharmacist':
                                header("Location: pharmacist/index.php");
                                break;
                            case 'cashier':
                                header("Location: cashier/index.php");
                                break;
                            default:
                                header("Location: user/index.php");
                                break;
                        }
                        exit();
                    }
                } else {
                    $error_message = "Tên đăng nhập hoặc mật khẩu không đúng.";
                }
            } else {
                $error_message = "Không thể kết nối đến cơ sở dữ liệu.";
            }
        } catch (Exception $e) {
            error_log("Login exception: " . $e->getMessage());
            $error_message = "Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống quản lý nhà thuốc</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .login-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
            text-align: center;
        }
        
        .login-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .form-control {
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            background: rgba(108, 117, 125, 0.1);
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background: white;
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(52, 152, 219, 0.3);
        }
        
        .alert {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .input-group-text {
            background: rgba(108, 117, 125, 0.1);
            border: none;
            border-radius: 10px 0 0 10px;
        }
        
        .pharmacy-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 2rem;
            color: white;
            text-align: center;
        }
        
        .pharmacy-info h3 {
            margin-bottom: 1rem;
            font-weight: 300;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list i {
            margin-right: 0.5rem;
            color: #f39c12;
        }
        
        .debug-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9em;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="row login-container">
                    <!-- Form đăng nhập -->
                    <div class="col-md-6 p-0">
                        <div class="login-header">
                            <i class="fas fa-pills"></i>
                            <h2>Hệ thống Nhà thuốc</h2>
                            <p class="mb-0">Đăng nhập để tiếp tục</p>
                        </div>
                        
                        <div class="p-4">
                            <?php if (!empty($error_message)): ?>
                                <div class="alert alert-danger d-flex align-items-center" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php echo htmlspecialchars($error_message); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($success_message)): ?>
                                <div class="alert alert-success d-flex align-items-center" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?php echo htmlspecialchars($success_message); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-user me-1"></i>
                                        Tên đăng nhập hoặc Email
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="username" 
                                           name="username" 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                           required 
                                           autocomplete="username"
                                           placeholder="Nhập tên đăng nhập hoặc email">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-1"></i>
                                        Mật khẩu
                                    </label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control" 
                                               id="password" 
                                               name="password" 
                                               required 
                                               autocomplete="current-password"
                                               placeholder="Nhập mật khẩu">
                                        <button class="btn btn-outline-secondary" 
                                                type="button" 
                                                onclick="togglePassword()">
                                            <i class="fas fa-eye" id="password-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                                    <label class="form-check-label" for="remember_me">
                                        Ghi nhớ đăng nhập
                                    </label>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="login" class="btn btn-primary btn-login">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Đăng nhập
                                    </button>
                                </div>
                            </form>
                            
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Phiên đăng nhập được bảo mật SSL
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thông tin hệ thống -->
                    <div class="col-md-6 p-0 d-none d-md-block" style="background: linear-gradient(135deg, rgba(52, 152, 219, 0.8), rgba(44, 62, 80, 0.8)); border-radius: 0 20px 20px 0;">
                        <div class="pharmacy-info h-100 d-flex flex-column justify-content-center">
                            <h3>Hệ thống quản lý nhà thuốc hiện đại</h3>
                            <p class="mb-4">Giải pháp toàn diện cho việc quản lý nhà thuốc của bạn</p>
                            
                            <ul class="feature-list">
                                <li>
                                    <i class="fas fa-prescription-bottle-alt"></i>
                                    Quản lý thuốc và kho hàng
                                </li>
                                <li>
                                    <i class="fas fa-file-prescription"></i>
                                    Xử lý đơn thuốc điện tử
                                </li>
                                <li>
                                    <i class="fas fa-users"></i>
                                    Quản lý khách hàng
                                </li>
                                <li>
                                    <i class="fas fa-chart-line"></i>
                                    Báo cáo doanh thu chi tiết
                                </li>
                                <li>
                                    <i class="fas fa-shield-alt"></i>
                                    Bảo mật dữ liệu cao
                                </li>
                                <li>
                                    <i class="fas fa-mobile-alt"></i>
                                    Giao diện thân thiện
                                </li>
                            </ul>
                            
                            <!-- Debug info -->
                            
                            
                            <div class="mt-4">
                                <small>
                                    <i class="fas fa-clock me-1"></i>
                                    Hỗ trợ 24/7 | 
                                    <i class="fas fa-phone me-1"></i>
                                    Hotline: 1900-xxxx
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordEye = document.getElementById('password-eye');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordEye.classList.remove('fa-eye');
                passwordEye.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordEye.classList.remove('fa-eye-slash');
                passwordEye.classList.add('fa-eye');
            }
        }
        
        // Auto hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 300);
            });
        }, 5000);
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Vui lòng nhập đầy đủ thông tin đăng nhập.');
                return false;
            }
        });
    </script>
</body>
</html>