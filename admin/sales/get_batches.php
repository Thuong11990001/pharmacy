<?php
// manager/sales/get_batches.php
ob_start(); // Start output buffering
require_once '../../includes/config.php';
require_once '../../includes/session.php';

// Kiểm tra đăng nhập và quyền truy cập
SessionManager::requireRole(['admin', 'manager', 'pharmacist']);

// Debug: Log request
error_log("get_batches.php called with medicine_id: " . ($_GET['medicine_id'] ?? 'not set'));

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
    
    // Debug: Kiểm tra xem medicine có tồn tại không
    $checkStmt = $pdo->prepare("SELECT id, name FROM medicines WHERE id = ?");
    $checkStmt->execute([$medicine_id]);
    $medicine = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$medicine) {
        ob_clean();
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Medicine not found']);
        exit;
    }
    
    // Debug: Log medicine info
    error_log("Medicine found: " . $medicine['name']);
    
    // Query để lấy batches - có thể cần điều chỉnh điều kiện
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            batch_number, 
            current_quantity, 
            expiry_date,
            DATE_FORMAT(expiry_date, '%d/%m/%Y') as formatted_expiry_date
        FROM medicine_batches
        WHERE medicine_id = ? 
        AND status = 'active' 
        AND current_quantity > 0
        AND expiry_date > CURRENT_DATE
        ORDER BY expiry_date ASC
    ");
    $stmt->execute([$medicine_id]);
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log số lượng batches tìm thấy
    error_log("Found " . count($batches) . " batches for medicine_id: " . $medicine_id);
    
    // Nếu không có batch nào thỏa mãn điều kiện, thử query rộng hơn để debug
    if (empty($batches)) {
        $debugStmt = $pdo->prepare("
            SELECT 
                id, 
                batch_number, 
                current_quantity, 
                expiry_date,
                status,
                DATE_FORMAT(expiry_date, '%d/%m/%Y') as formatted_expiry_date
            FROM medicine_batches
            WHERE medicine_id = ?
            ORDER BY expiry_date ASC
        ");
        $debugStmt->execute([$medicine_id]);
        $allBatches = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("All batches for medicine_id " . $medicine_id . ": " . json_encode($allBatches));
        
        // Trả về thông tin debug nếu không có batch hợp lệ
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'batches' => [],
            'debug_info' => [
                'medicine_name' => $medicine['name'],
                'total_batches' => count($allBatches),
                'all_batches' => $allBatches,
                'message' => 'No valid batches found. Check expiry dates, quantities, and status.'
            ]
        ]);
        exit;
    }
    
    // Clear buffer and send JSON response
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'batches' => $batches,
        'medicine_name' => $medicine['name']
    ]);
    exit;
    
} catch (PDOException $e) {
    error_log("Error fetching batches: " . $e->getMessage());
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