<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../config/database.php';
include 'generate_patient_code.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Function to generate next patient code




// Function to check if patient code exists
function patientCodeExists($pdo, $code) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE patient_code = ?");
        $stmt->execute([$code]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking patient code: " . $e->getMessage());
        return false;
    }
}

try {
    // Get form data
    $patient_code = trim($_POST['patient_code'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $allergies = trim($_POST['allergies'] ?? '');
    $medical_notes = trim($_POST['medical_notes'] ?? '');
    
    // Validate required fields
    if (empty($full_name)) {
        throw new Exception('Họ và tên không được để trống!');
    }
    
    // Generate patient code if not provided
    if (empty($patient_code)) {
        $patient_code = generatePatientCode($pdo);
    } else {
        // Validate patient code format
        if (!preg_match('/^PAT\d{4}$/', $patient_code)) {
    throw new Exception('Mã bệnh nhân không đúng định dạng (PAT0001)!');
}
        
        // Check if code already exists
        if (patientCodeExists($pdo, $patient_code)) {
            throw new Exception('Mã bệnh nhân đã tồn tại!');
        }
    }
    
    // Validate email format if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Địa chỉ email không hợp lệ!');
    }
    
    // Validate phone number if provided
    if (!empty($phone) && !preg_match('/^[0-9+\-\s()]+$/', $phone)) {
        throw new Exception('Số điện thoại không hợp lệ!');
    }
    
    // Validate ID number if provided
    if (!empty($id_number) && !preg_match('/^[0-9]{9,12}$/', $id_number)) {
        throw new Exception('Số CCCD/CMND không hợp lệ!');
    }
    
    // Check for duplicate phone or email
    if (!empty($phone)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Số điện thoại đã được sử dụng!');
        }
    }
    
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Email đã được sử dụng!');
        }
    }
    
    if (!empty($id_number)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE id_number = ?");
        $stmt->execute([$id_number]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Số CCCD/CMND đã được sử dụng!');
        }
    }
    
    // Insert new patient
    $stmt = $pdo->prepare("
    INSERT INTO patients (
        patient_code, full_name, id_number, phone, date_of_birth, gender, 
        email, address, emergency_contact, allergies, medical_notes, 
        status, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
");

$result = $stmt->execute([
    $patient_code, $full_name, $id_number, $phone, $date_of_birth, 
    $gender, $email, $address, $emergency_contact, $allergies, 
    $medical_notes
]);
    
    if ($result) {
        $patient_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Thêm bệnh nhân thành công!',
            'patient' => [
                'id' => $patient_id,
                'patient_code' => $patient_code,
                'full_name' => $full_name
            ]
        ]);
    } else {
        throw new Exception('Không thể thêm bệnh nhân!');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()
    ]);
}



?>