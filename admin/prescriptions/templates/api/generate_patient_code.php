<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Function to generate next patient code
function generatePatientCode($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT patient_code 
            FROM patients 
            WHERE patient_code REGEXP '^PAT[0-9]{4}$' 
            ORDER BY CAST(SUBSTRING(patient_code, 2) AS UNSIGNED) DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $lastCode = $stmt->fetchColumn();
        
        if ($lastCode) {
            $lastNumber = (int)substr($lastCode, 1);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return 'PAT' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        error_log("Error generating patient code: " . $e->getMessage());
        return 'PAT' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

try {
    $code = generatePatientCode($pdo);
    
    echo json_encode([
        'success' => true,
        'code' => $code
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Không thể tạo mã bệnh nhân!'
    ]);
}
?>