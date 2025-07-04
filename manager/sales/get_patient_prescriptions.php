<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/session.php';

// Kiểm tra đăng nhập và quyền truy cập
try {
    SessionManager::requireRole(['admin', 'manager', 'pharmacist']);
} catch (Exception $e) {
    error_log("Session error in get_patient_prescriptions.php: " . $e->getMessage());
    http_response_code(403);
    echo json_encode(['error' => 'Không có quyền truy cập']);
    exit();
}

try {
    $patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
    
    if ($patient_id <= 0) {
        error_log("Invalid patient_id: $patient_id");
        http_response_code(400);
        echo json_encode(['error' => 'ID bệnh nhân không hợp lệ']);
        exit();
    }
    
    $stmt = $pdo->prepare("
        SELECT id, prescription_code 
        FROM prescriptions 
        WHERE patient_id = ? 
        AND status IN ('pending', 'partial') 
        ORDER BY prescription_date DESC
    ");
    $stmt->execute([$patient_id]);
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($prescriptions);
} catch (PDOException $e) {
    error_log("Database error in get_patient_prescriptions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in get_patient_prescriptions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi: ' . $e->getMessage()]);
}
?>