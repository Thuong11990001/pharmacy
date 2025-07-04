<?php
// includes/config.php

// Định nghĩa BASE_URL (cần thiết cho SessionManager)
define('BASE_URL', 'http://localhost/new_pharma/');

// Các URL khác
define('BACKEND_URL', 'http://localhost/new_pharma/includes/');
define('PUBLIC_URL', 'http://localhost/new_pharma/public/');
define('ADMIN_URL', 'http://localhost/new_pharma/manager/');
define('PHARMACIST_URL', 'http://localhost/new_pharma/pharmacist/');

// Include database class
require_once __DIR__ . '/../config/database.php';

// Tạo kết nối database
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Không thể kết nối database");
    }
    
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// Set timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');
?>