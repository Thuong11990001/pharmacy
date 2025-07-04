<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/session.php';

// Kiểm tra đăng nhập và quyền truy cập
try {
    SessionManager::requireRole(['admin', 'manager', 'pharmacist']);
} catch (Exception $e) {
    error_log("Session error in get_prescription_details.php: " . $e->getMessage());
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit();
}

try {
    $prescription_id = isset($_GET['prescription_id']) ? (int)$_GET['prescription_id'] : 0;
    
    if ($prescription_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID đơn thuốc không hợp lệ']);
        exit();
    }
    
    // Lấy chi tiết đơn thuốc với thông tin thuốc và tồn kho
    // Sửa tên cột từ pd.quantity thành pd.quantity_prescribed
    $stmt = $pdo->prepare("
        SELECT 
            pd.id as prescription_detail_id,
            pd.medicine_id,
            pd.quantity_prescribed as prescribed_quantity,
            pd.dosage_instructions,
            pd.frequency,
            pd.duration_days,
            pd.unit_price,
            pd.total_price,
            pd.notes,
            m.name as medicine_name,
            m.medicine_code,
            m.selling_price,
            COALESCE(SUM(mb.current_quantity), 0) as available_quantity,
            AVG(mb.import_price) as cost_price
        FROM prescription_details pd
        JOIN medicines m ON pd.medicine_id = m.id
        LEFT JOIN medicine_batches mb ON m.id = mb.medicine_id 
            AND mb.status = 'active' 
            AND mb.current_quantity > 0 
            AND mb.expiry_date > CURRENT_DATE
        WHERE pd.prescription_id = ? 
        AND m.status = 'active'
        GROUP BY pd.id, pd.medicine_id, pd.quantity_prescribed, pd.dosage_instructions, 
                 pd.frequency, pd.duration_days, pd.unit_price, pd.total_price, pd.notes,
                 m.name, m.medicine_code, m.selling_price
        ORDER BY pd.id
    ");
    $stmt->execute([$prescription_id]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($details) {
        // Format dữ liệu để dễ sử dụng trong JavaScript
        $formatted_details = [];
        foreach ($details as $detail) {
            $formatted_details[] = [
                'prescription_detail_id' => $detail['prescription_detail_id'],
                'medicine_id' => $detail['medicine_id'],
                'medicine_name' => $detail['medicine_name'],
                'medicine_code' => $detail['medicine_code'],
                'prescribed_quantity' => (int)$detail['prescribed_quantity'],
                'selling_price' => (float)($detail['unit_price'] ?? $detail['selling_price']), // Ưu tiên unit_price từ đơn thuốc
                'available_quantity' => (int)$detail['available_quantity'],
                'cost_price' => (float)($detail['cost_price'] ?? 0),
                'dosage' => $detail['dosage_instructions'],
                'frequency' => $detail['frequency'],
                'duration' => $detail['duration_days'],
                'instructions' => $detail['notes'],
                'unit_price' => (float)($detail['unit_price'] ?? 0),
                'total_price' => (float)($detail['total_price'] ?? 0)
            ];
        }
        
        echo json_encode([
            'success' => true,
            'details' => $formatted_details
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy chi tiết đơn thuốc'
        ]);
    }
} catch (PDOException $e) {
    error_log("Database error in get_prescription_details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu']);
} catch (Exception $e) {
    error_log("General error in get_prescription_details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>