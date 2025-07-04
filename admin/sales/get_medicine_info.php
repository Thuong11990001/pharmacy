<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/session.php';

// Kiểm tra đăng nhập và quyền truy cập
try {
    SessionManager::requireRole(['admin', 'manager', 'pharmacist']);
} catch (Exception $e) {
    error_log("Session error in get_medicine_info.php: " . $e->getMessage());
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit();
}

try {
    $medicine_id = isset($_GET['medicine_id']) ? (int)$_GET['medicine_id'] : 0;
    
    if ($medicine_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID thuốc không hợp lệ']);
        exit();
    }
    
    // Lấy thông tin thuốc và tồn kho
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.name,
            m.medicine_code,
            m.selling_price,
            COALESCE(SUM(mb.current_quantity), 0) as available_quantity,
            AVG(mb.import_price) as cost_price
        FROM medicines m
        LEFT JOIN medicine_batches mb ON m.id = mb.medicine_id 
            AND mb.status = 'active' 
            AND mb.current_quantity > 0 
            AND mb.expiry_date > CURRENT_DATE
        WHERE m.id = ? AND m.status = 'active'
        GROUP BY m.id, m.name, m.medicine_code, m.selling_price
    ");
    $stmt->execute([$medicine_id]);
    $medicine = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($medicine) {
        echo json_encode([
            'success' => true,
            'medicine_id' => $medicine['id'],
            'name' => $medicine['name'],
            'medicine_code' => $medicine['medicine_code'],
            'selling_price' => (float)$medicine['selling_price'],
            'available_quantity' => (int)$medicine['available_quantity'],
            'cost_price' => (float)($medicine['cost_price'] ?? 0)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy thông tin thuốc'
        ]);
    }
} catch (PDOException $e) {
    error_log("Database error in get_medicine_info.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu']);
} catch (Exception $e) {
    error_log("General error in get_medicine_info.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>