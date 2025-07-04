<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once 'sales_functions.php';

// Kiểm tra đăng nhập và quyền truy cập
try {
    SessionManager::requireRole(['admin', 'manager', 'pharmacist']);
} catch (Exception $e) {
    error_log("Session error in generate_sale_code.php: " . $e->getMessage());
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit();
}

try {
    $sale_code = generateSaleCode($pdo);
    
    if ($sale_code) {
        echo json_encode([
            'success' => true,
            'sale_code' => $sale_code
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể tạo mã giao dịch'
        ]);
    }
} catch (Exception $e) {
    error_log("Error in generate_sale_code.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>