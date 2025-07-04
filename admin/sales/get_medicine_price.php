<?php
// manager/sales/get_medicine_price.php
ob_start(); // Start output buffering
require_once '../../includes/config.php';
require_once '../../includes/session.php';

// Kiểm tra đăng nhập và quyền truy cập
SessionManager::requireRole(['admin', 'manager', 'pharmacist']);

// Kiểm tra medicine_id được gửi qua GET
if (!isset($_GET['medicine_id']) || !is_numeric($_GET['medicine_id'])) {
    ob_clean(); // Clear buffer before sending response
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid or missing medicine_id']);
    exit;
}

$medicine_id = (int)$_GET['medicine_id'];

try {
    // Sử dụng biến $pdo được định nghĩa trong config.php
    global $pdo;
    if (!$pdo) {
        throw new Exception("Database connection not available");
    }
    
    $stmt = $pdo->prepare("
        SELECT selling_price
        FROM medicines
        WHERE id = ? 
        AND status = 'active'
    ");
    $stmt->execute([$medicine_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Clear buffer and send JSON response
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['selling_price' => $result['selling_price']]);
    } else {
        ob_clean();
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Medicine not found or inactive']);
    }
    exit;
} catch (PDOException $e) {
    error_log("Error fetching medicine price: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

ob_end_flush(); // End output buffering
?>