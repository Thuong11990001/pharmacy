<?php
// includes/session.php

class SessionManager {
    
    public static function startSecureSession() {
        // Chỉ cấu hình session nếu session chưa được khởi động
        if (session_status() == PHP_SESSION_NONE) {
            // Cấu hình session bảo mật TRƯỚC KHI khởi động session
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.cookie_lifetime', 0); // Session cookie expires when browser closes
            ini_set('session.gc_maxlifetime', 7200); // 2 hours
            
            // Khởi động session
            session_start();
        }
        
        // Regenerate session ID để tránh session fixation
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
    }
    
    public static function login($user_data) {
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['user_code'] = $user_data['user_code'];
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['full_name'] = $user_data['full_name'];
        $_SESSION['email'] = $user_data['email'];
        $_SESSION['role'] = $user_data['role'];
        $_SESSION['can_sell_controlled'] = $user_data['can_sell_controlled'];
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        
        // Regenerate session ID sau khi đăng nhập
        session_regenerate_id(true);
    }
    
    public static function logout() {
        // Xóa tất cả session variables
        $_SESSION = array();
        
        // Xóa session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header("Location: index.php");
            exit();
        }
        
        // Kiểm tra session timeout (2 giờ)
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 7200)) {
            self::logout();
            header("Location: index.php?timeout=1");
            exit();
        }
        
        // Kiểm tra IP và User Agent để tránh session hijacking (có thể tắt nếu cần)
        if (isset($_SESSION['ip_address']) && isset($_SESSION['user_agent'])) {
            if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR'] || 
                $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
                self::logout();
                header("Location: index.php?security=1");
                exit();
            }
        }
    }
    
    public static function hasRole($required_roles) {
        if (!is_array($required_roles)) {
            $required_roles = [$required_roles];
        }
        
        return isset($_SESSION['role']) && in_array($_SESSION['role'], $required_roles);
    }
    
    public static function requireRole($required_roles) {
        self::requireLogin();
        
        if (!self::hasRole($required_roles)) {
            header("Location: dashboard.php?access_denied=1");
            exit();
        }
    }
    
    public static function canSellControlled() {
        return isset($_SESSION['can_sell_controlled']) && $_SESSION['can_sell_controlled'] == 1;
    }
    
    public static function getUserInfo() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'user_code' => $_SESSION['user_code'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'can_sell_controlled' => $_SESSION['can_sell_controlled'] ?? null
        ];
    }
    
    public static function refreshSession() {
        // Cập nhật thời gian login để kéo dài session
        if (self::isLoggedIn()) {
            $_SESSION['login_time'] = time();
        }
    }
}

// Khởi động session bảo mật khi file được include
SessionManager::startSecureSession();
?>