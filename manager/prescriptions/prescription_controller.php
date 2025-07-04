<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../config/database.php';

// Function to generate next prescription code
function generatePrescriptionCode($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT prescription_code 
            FROM prescriptions 
            WHERE prescription_code REGEXP '^PRES[0-9]{6}$' 
            ORDER BY CAST(SUBSTRING(prescription_code, 5) AS UNSIGNED) DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $lastCode = $stmt->fetchColumn();
        
        if ($lastCode) {
            $lastNumber = (int)substr($lastCode, 4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return 'PRES' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        error_log("Error generating prescription code: " . $e->getMessage());
        return 'PRES' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}

// Function to validate prescription code format
function validatePrescriptionCode($code) {
    return preg_match('/^PRES\d{6}$/', $code);
}

// Function to check if prescription code exists
function prescriptionCodeExists($pdo, $code, $excludeId = null) {
    try {
        if ($excludeId) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE prescription_code = ? AND id != ?");
            $stmt->execute([$code, $excludeId]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE prescription_code = ?");
            $stmt->execute([$code]);
        }
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking prescription code: " . $e->getMessage());
        return false;
    }
}

// Process prescription logic
function processPrescription($pdo, $action) {
    $message = '';
    $message_type = '';
    $prescriptions = [];
    $patients = [];
    $users = [];
    $medicines = [];
    $edit_prescription = null;
    $edit_prescription_details = [];
    $prescription_details = [];

    // Initialize prescription details array
    if (isset($_POST['medicine_ids']) && is_array($_POST['medicine_ids'])) {
        for ($i = 0; $i < count($_POST['medicine_ids']); $i++) {
            if (!empty($_POST['medicine_ids'][$i]) && !empty($_POST['quantities_prescribed'][$i])) {
                $prescription_details[] = [
                    'medicine_id' => (int)$_POST['medicine_ids'][$i],
                    'quantity_prescribed' => (int)$_POST['quantities_prescribed'][$i],
                    'quantity_dispensed' => (int)($_POST['quantities_dispensed'][$i] ?? 0),
                    'dosage_instructions' => trim($_POST['dosage_instructions'][$i] ?? ''),
                    'frequency' => trim($_POST['frequencies'][$i] ?? ''),
                    'duration_days' => (int)($_POST['duration_days'][$i] ?? 0),
                    'unit_price' => (float)($_POST['unit_prices'][$i] ?? 0),
                    'total_price' => (float)($_POST['unit_prices'][$i] ?? 0) * (int)$_POST['quantities_prescribed'][$i],
                    'status' => $_POST['detail_status'][$i] ?? 'pending'
                ];
            }
        }
    }

    // Calculate total amount
    $total_amount = array_sum(array_column($prescription_details, 'total_price'));

    // Xử lý thêm/sửa đơn thuốc
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $prescription_id = $_POST['prescription_id'] ?? '';
        $prescription_code = trim($_POST['prescription_code'] ?? '');
        $patient_id = $_POST['patient_id'] ?? '';
        $doctor_name = trim($_POST['doctor_name'] ?? '');
        $doctor_license = trim($_POST['doctor_license'] ?? '');
        $hospital_clinic = trim($_POST['hospital_clinic'] ?? '');
        $prescription_date = $_POST['prescription_date'] ?? '';
        $diagnosis = trim($_POST['diagnosis'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $status = $_POST['status'] ?? 'pending';
        $processed_by = SessionManager::getUserInfo()['id'];
        
        // Validate required fields
        if (empty($patient_id)) {
            $message = "Vui lòng chọn bệnh nhân!";
            $message_type = "danger";
        } elseif (empty($doctor_name)) {
            $message = "Tên bác sĩ không được để trống!";
            $message_type = "danger";
        } elseif (empty($prescription_date)) {
            $message = "Ngày kê đơn không được để trống!";
            $message_type = "danger";
        } else {
            try {
                $pdo->beginTransaction();
                
                if ($prescription_id) {
                    // Update prescription
                    $stmt = $pdo->prepare("
                        UPDATE prescriptions 
                        SET prescription_code = ?, patient_id = ?, doctor_name = ?, doctor_license = ?, 
                            hospital_clinic = ?, prescription_date = ?, diagnosis = ?, status = ?, 
                            notes = ?, total_amount = ?, processed_by = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");
                    $result = $stmt->execute([
                        $prescription_code, $patient_id, $doctor_name, $doctor_license, 
                        $hospital_clinic, $prescription_date, $diagnosis, $status, 
                        $notes, $total_amount, $processed_by, $prescription_id
                    ]);
                    
                    if ($result && $stmt->rowCount() > 0) {
                        // Delete old details
                        $stmt = $pdo->prepare("DELETE FROM prescription_details WHERE prescription_id = ?");
                        $stmt->execute([$prescription_id]);
                        
                        // Insert new details
                        foreach ($prescription_details as $detail) {
                            $stmt = $pdo->prepare("
                                INSERT INTO prescription_details (
                                    prescription_id, medicine_id, quantity_prescribed, quantity_dispensed,
                                    dosage_instructions, frequency, duration_days, unit_price, total_price, status
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $prescription_id, $detail['medicine_id'], $detail['quantity_prescribed'], 
                                $detail['quantity_dispensed'], $detail['dosage_instructions'], 
                                $detail['frequency'], $detail['duration_days'], $detail['unit_price'], 
                                $detail['total_price'], $detail['status']
                            ]);
                        }
                        
                        $pdo->commit();
                        $message = "Cập nhật đơn thuốc thành công! Mã đơn: " . $prescription_code;
                        $message_type = "success";
                    }
                } else {
                    // Insert new prescription
                    $stmt = $pdo->prepare("
                        INSERT INTO prescriptions (
                            prescription_code, patient_id, doctor_name, doctor_license, hospital_clinic, 
                            prescription_date, diagnosis, status, notes, total_amount, processed_by, 
                            created_at, updated_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                    ");
                    $result = $stmt->execute([
                        $prescription_code, $patient_id, $doctor_name, $doctor_license, 
                        $hospital_clinic, $prescription_date, $diagnosis, $status, 
                        $notes, $total_amount, $processed_by
                    ]);
                    
                    if ($result) {
                        $new_prescription_id = $pdo->lastInsertId();
                        
                        // Insert prescription details
                        foreach ($prescription_details as $detail) {
                            $stmt = $pdo->prepare("
                                INSERT INTO prescription_details (
                                    prescription_id, medicine_id, quantity_prescribed, quantity_dispensed,
                                    dosage_instructions, frequency, duration_days, unit_price, total_price, status
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $new_prescription_id, $detail['medicine_id'], $detail['quantity_prescribed'], 
                                $detail['quantity_dispensed'], $detail['dosage_instructions'], 
                                $detail['frequency'], $detail['duration_days'], $detail['unit_price'], 
                                $detail['total_price'], $detail['status']
                            ]);
                        }
                        
                        $pdo->commit();
                        $message = $message_type === 'warning' ? $message : "Thêm đơn thuốc thành công! Mã đơn: " . $prescription_code;
                        $message_type = "success";
                    }
                }
            } catch (PDOException $e) {
                $pdo->rollback();
                error_log("Database error: " . $e->getMessage());
                $message = "Lỗi cơ sở dữ liệu: " . $e->getMessage();
                $message_type = "danger";
            } catch (Exception $e) {
                $pdo->rollback();
                error_log("General error: " . $e->getMessage());
                $message = $e->getMessage();
                $message_type = "danger";
            }
        }
        
        // Redirect to prevent form resubmission
        if ($message_type === 'success') {
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = $message_type;
            header('Location: ' . BASE_URL . 'manager/prescriptions/');
            exit();
        }
    }

    // Check for flash messages
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $message_type = $_SESSION['flash_type'];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    }

    // Fetch prescriptions list
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, pat.full_name AS patient_name, u.full_name AS processed_by_name
            FROM prescriptions p
            LEFT JOIN patients pat ON p.patient_id = pat.id
            LEFT JOIN users u ON p.processed_by = u.id
            ORDER BY CAST(SUBSTRING(p.prescription_code, 5) AS UNSIGNED) DESC
        ");
        $stmt->execute();
        $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Total prescriptions found: " . count($prescriptions));
        if (count($prescriptions) > 0) {
            error_log("First prescription: " . print_r($prescriptions[0], true));
        }
    } catch (PDOException $e) {
        $prescriptions = [];
        error_log("Error fetching prescriptions: " . $e->getMessage());
    }

    // Fetch patients and users for form
    try {
        $stmt = $pdo->prepare("SELECT id, full_name FROM patients WHERE status = 'active' ORDER BY full_name");
        $stmt->execute();
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE status = 'active' ORDER BY full_name");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $patients = [];
        $users = [];
        error_log("Error fetching patients/users: " . $e->getMessage());
    }

    // Fetch medicines for form
    try {
        $stmt = $pdo->prepare("
            SELECT m.id, m.name, m.unit, m.selling_price, 
                   COALESCE(SUM(mb.current_quantity), 0) as available_quantity
            FROM medicines m
            LEFT JOIN medicine_batches mb ON m.id = mb.medicine_id 
                AND mb.status = 'active' 
                AND mb.expiry_date > CURDATE()
            WHERE m.status = 'active'
            GROUP BY m.id, m.name, m.unit, m.selling_price
            ORDER BY m.name
        ");
        $stmt->execute();
        $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $medicines = [];
        error_log("Error fetching medicines: " . $e->getMessage());
    }

    // Fetch prescription details for edit
    if ($action === 'edit' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        try {
            $stmt = $pdo->prepare("SELECT * FROM prescriptions WHERE id = ?");
            $stmt->execute([$id]);
            $edit_prescription = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$edit_prescription) {
                $_SESSION['flash_message'] = "Không tìm thấy đơn thuốc để chỉnh sửa!";
                $_SESSION['flash_type'] = "danger";
                header('Location: ' . BASE_URL . 'manager/prescriptions/');
                exit();
            }
            
            // Fetch prescription details
            $stmt = $pdo->prepare("
                SELECT pd.*, m.name AS medicine_name, m.unit, m.selling_price
                FROM prescription_details pd
                LEFT JOIN medicines m ON pd.medicine_id = m.id
                WHERE pd.prescription_id = ?
            ");
            $stmt->execute([$id]);
            $edit_prescription_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching prescription for edit: " . $e->getMessage());
            $_SESSION['flash_message'] = "Lỗi khi tải thông tin đơn thuốc!";
            $_SESSION['flash_type'] = "danger";
            header('Location: ' . BASE_URL . 'manager/prescriptions/');
            exit();
        }
    }

    // Fetch prescription details for view
    if ($action === 'view' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        try {
            $stmt = $pdo->prepare("
                SELECT pd.*, m.name AS medicine_name
                FROM prescription_details pd
                LEFT JOIN medicines m ON pd.medicine_id = m.id
                WHERE pd.prescription_id = ?
            ");
            $stmt->execute([$id]);
            $prescription_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching prescription details: " . $e->getMessage());
        }
    }

    return [
        $message,
        $message_type,
        $prescriptions,
        $patients,
        $users,
        $medicines,
        $edit_prescription,
        $edit_prescription_details,
        $prescription_details
    ];
}
?>