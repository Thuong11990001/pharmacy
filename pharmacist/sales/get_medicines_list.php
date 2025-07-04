<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/session.php';

// Kiểm tra đăng nhập và quyền truy cập
try {
    SessionManager::requireRole(['admin', 'manager', 'pharmacist']);
} catch (Exception $e) {
    error_log("Session error in get_medicines_list.php: " . $e->getMessage());
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit();
}

try {
    // Lấy danh sách thuốc có tồn kho
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.name,
            m.medicine_code,
            m.selling_price,
            COALESCE(SUM(mb.current_quantity), 0) as available_quantity
        FROM medicines m
        LEFT JOIN medicine_batches mb ON m.id = mb.medicine_id 
            AND mb.status = 'active' 
            AND mb.current_quantity > 0 
            AND mb.expiry_date > CURRENT_DATE
        WHERE m.status = 'active'
        GROUP BY m.id, m.name, m.medicine_code, m.selling_price
        HAVING available_quantity > 0
        ORDER BY m.name
    ");
    $stmt->execute();
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dữ liệu
    $formatted_medicines = [];
    foreach ($medicines as $medicine) {
        $formatted_medicines[] = [
            'id' => $medicine['id'],
            'name' => $medicine['name'],
            'medicine_code' => $medicine['medicine_code'],
            'selling_price' => (float)$medicine['selling_price'],
            'available_quantity' => (int)$medicine['available_quantity']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'medicines' => $formatted_medicines
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_medicines_list.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu']);
} catch (Exception $e) {
    error_log("General error in get_medicines_list.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>