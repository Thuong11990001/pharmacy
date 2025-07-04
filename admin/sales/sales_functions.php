<?php
// manager/sales/sales_functions.php

// Function to generate next sale code
function generateSaleCode($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT sale_code 
            FROM sales 
            WHERE sale_code REGEXP '^SALE[0-9]{6}$' 
            ORDER BY CAST(SUBSTRING(sale_code, 5) AS UNSIGNED) DESC 
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
        
        return 'SALE' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        error_log("Error generating sale code: " . $e->getMessage());
        return 'SALE' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}

// Function to validate sale code format
function validateSaleCode($code) {
    return preg_match('/^SALE\d{6}$/', $code);
}

// Function to check if sale code exists
function saleCodeExists($pdo, $code, $excludeId = null) {
    try {
        if ($excludeId) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE sale_code = ? AND id != ?");
            $stmt->execute([$code, $excludeId]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE sale_code = ?");
            $stmt->execute([$code]);
        }
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking sale code: " . $e->getMessage());
        return false;
    }
}

// Main function to handle sales actions
function handleSalesActions($action) {
    global $pdo;
    $message = '';
    $message_type = '';
    $edit_sale = null;
    $edit_sale_details = [];
    $sales = [];
    $patients = [];
    $prescriptions = [];
    $medicines = [];
    $user_info = SessionManager::getUserInfo();

    // Xử lý thêm/sửa sale
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        error_log("POST Data: " . print_r($_POST, true));
        
        $sale_code = trim($_POST['sale_code'] ?? '');
        $sale_date = $_POST['sale_date'] ?? date('Y-m-d');
        $sale_time = $_POST['sale_time'] ?? date('H:i:s');
        $prescription_id = !empty($_POST['prescription_id']) ? (int)$_POST['prescription_id'] : null;
        $patient_id = !empty($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;
        $customer_name = trim($_POST['customer_name'] ?? '');
        $customer_phone = trim($_POST['customer_phone'] ?? '');
        $subtotal = (float)($_POST['subtotal'] ?? 0.00);
        $discount_amount = (float)($_POST['discount_amount'] ?? 0.00);
        $tax_amount = (float)($_POST['tax_amount'] ?? 0.00);
        $total_amount = (float)($_POST['total_amount'] ?? 0.00);
        $payment_method = $_POST['payment_method'] ?? 'cash';
        $payment_status = $_POST['payment_status'] ?? 'pending';
        $status = $_POST['status'] ?? 'draft';
        $employee_id = SessionManager::getUserInfo()['id'];
        $notes = trim($_POST['notes'] ?? '');
        $sale_id = $_POST['sale_id'] ?? '';
        $sale_details = $_POST['sale_details'] ?? [];

        // Validate required fields
        if (empty($sale_date) || empty($sale_time) || ($patient_id === null && empty($customer_name)) || empty($sale_details)) {
            $message = "Vui lòng nhập đầy đủ thông tin bán hàng!";
            $message_type = "danger";
            error_log("Validation failed: Missing required fields");
        } else {
            try {
                $pdo->beginTransaction();
                
                if ($sale_id) {
                    // Cập nhật sale
                    error_log("Updating sale ID: $sale_id");
                    
                    if (!empty($sale_code)) {
                        if (!validateSaleCode($sale_code)) {
                            throw new Exception("Mã giao dịch phải có định dạng SALE + 6 chữ số (VD: SALE000001)!");
                        } elseif (saleCodeExists($pdo, $sale_code, $sale_id)) {
                            throw new Exception("Mã giao dịch đã tồn tại!");
                        }
                    } else {
                        $sale_code = generateSaleCode($pdo);
                        error_log("Generated new code for update: $sale_code");
                    }
                    
                    $stmt = $pdo->prepare("
                        UPDATE sales 
                        SET sale_code = ?, sale_date = ?, sale_time = ?, prescription_id = ?, 
                            patient_id = ?, customer_name = ?, customer_phone = ?, 
                            subtotal = ?, discount_amount = ?, tax_amount = ?, total_amount = ?, 
                            payment_method = ?, payment_status = ?, status = ?, notes = ?, 
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");
                    $result = $stmt->execute([
                        $sale_code, $sale_date, $sale_time, $prescription_id, $patient_id,
                        $customer_name, $customer_phone, $subtotal, $discount_amount,
                        $tax_amount, $total_amount, $payment_method, $payment_status,
                        $status, $notes, $sale_id
                    ]);
                    
                    // Xóa sale_details cũ
                    $stmt = $pdo->prepare("DELETE FROM sale_details WHERE sale_id = ?");
                    $stmt->execute([$sale_id]);
                    
                    // Thêm sale_details mới
                    foreach ($sale_details as $detail) {
                        $medicine_id = (int)($detail['medicine_id'] ?? 0);
                        $quantity = (int)($detail['quantity'] ?? 0);
                        $unit_price = (float)($detail['unit_price'] ?? 0.00);
                        $discount = (float)($detail['discount_amount'] ?? 0.00);
                        $total_price = (float)($detail['total_price'] ?? 0.00);
                        $prescription_detail_id = !empty($detail['prescription_detail_id']) ? (int)$detail['prescription_detail_id'] : null;
                        
                        if ($medicine_id && $quantity > 0) {
                            // Lấy danh sách lô thuốc hợp lệ
                            $stmt = $pdo->prepare("
                                SELECT id, current_quantity, import_price AS cost_price
                                FROM medicine_batches
                                WHERE medicine_id = ?
                                AND status = 'active'
                                AND current_quantity > 0
                                AND expiry_date > CURRENT_DATE
                                ORDER BY current_quantity ASC, expiry_date ASC
                                LIMIT 1
                            ");
                            $stmt->execute([$medicine_id]);
                            $batch = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if (!$batch) {
                                throw new Exception("Không có lô thuốc hợp lệ cho thuốc ID: $medicine_id");
                            }
                            
                            $batch_id = $batch['id'];
                            $available_quantity = $batch['current_quantity'];
                            $cost_price = $batch['cost_price'];
                            
                            if ($quantity > $available_quantity) {
                                throw new Exception("Số lượng tồn kho không đủ cho thuốc ID: $medicine_id");
                            }
                            
                            // Thêm vào sale_details
                            $stmt = $pdo->prepare("
                                INSERT INTO sale_details (sale_id, medicine_id, batch_id, quantity, unit_price, 
                                                        cost_price, discount_amount, total_price, prescription_detail_id)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $sale_id, $medicine_id, $batch_id, $quantity, $unit_price,
                                $cost_price, $discount, $total_price, $prescription_detail_id
                            ]);
                            
                            // Cập nhật tồn kho
                            $stmt = $pdo->prepare("UPDATE medicine_batches SET current_quantity = current_quantity - ? WHERE id = ?");
                            $stmt->execute([$quantity, $batch_id]);
                        }
                    }
                    
                    if ($result) {
                        $pdo->commit();
                        $message = "Cập nhật giao dịch bán hàng thành công! Mã giao dịch: " . $sale_code;
                        $message_type = "success";
                        error_log("Sale updated successfully");
                    } else {
                        $pdo->rollback();
                        throw new Exception("Không có thay đổi nào được thực hiện hoặc giao dịch không tồn tại!");
                    }
                } else {
                    // Thêm sale mới
                    error_log("Adding new sale");
                    
                    if (empty($sale_code)) {
                        $sale_code = generateSaleCode($pdo);
                        error_log("Generated new code: $sale_code");
                    } else {
                        if (!validateSaleCode($sale_code)) {
                            throw new Exception("Mã giao dịch phải có định dạng SALE + 6 chữ số (VD: SALE000001)!");
                        } elseif (saleCodeExists($pdo, $sale_code)) {
                            $old_code = $sale_code;
                            $sale_code = generateSaleCode($pdo);
                            error_log("Code $old_code exists, generated new: $sale_code");
                            $message = "Mã giao dịch đã tồn tại! Đã tự động tạo mã mới: " . $sale_code;
                            $message_type = "warning";
                        }
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO sales (sale_code, sale_date, sale_time, prescription_id, patient_id,
                                         customer_name, customer_phone, subtotal, discount_amount,
                                         tax_amount, total_amount, payment_method, payment_status,
                                         status, employee_id, notes, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                    ");
                    $result = $stmt->execute([
                        $sale_code, $sale_date, $sale_time, $prescription_id, $patient_id,
                        $customer_name, $customer_phone, $subtotal, $discount_amount,
                        $tax_amount, $total_amount, $payment_method, $payment_status,
                        $status, $employee_id, $notes
                    ]);
                    
                    if ($result) {
                        $new_id = $pdo->lastInsertId();
                        
                        // Thêm sale_details
                        foreach ($sale_details as $detail) {
                            $medicine_id = (int)($detail['medicine_id'] ?? 0);
                            $quantity = (int)($detail['quantity'] ?? 0);
                            $unit_price = (float)($detail['unit_price'] ?? 0.00);
                            $discount = (float)($detail['discount_amount'] ?? 0.00);
                            $total_price = (float)($detail['total_price'] ?? 0.00);
                            $prescription_detail_id = !empty($detail['prescription_detail_id']) ? (int)$detail['prescription_detail_id'] : null;
                            
                            if ($medicine_id && $quantity > 0) {
                                $stmt = $pdo->prepare("
                                    SELECT id, current_quantity, import_price AS cost_price
                                    FROM medicine_batches
                                    WHERE medicine_id = ?
                                    AND status = 'active'
                                    AND current_quantity > 0
                                    AND expiry_date > CURRENT_DATE
                                    ORDER BY current_quantity ASC, expiry_date ASC
                                    LIMIT 1
                                ");
                                $stmt->execute([$medicine_id]);
                                $batch = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if (!$batch) {
                                    throw new Exception("Không có lô thuốc hợp lệ cho thuốc ID: $medicine_id");
                                }
                                
                                $batch_id = $batch['id'];
                                $available_quantity = $batch['current_quantity'];
                                $cost_price = $batch['cost_price'];
                                
                                if ($quantity > $available_quantity) {
                                    throw new Exception("Số lượng tồn kho không đủ cho thuốc ID: $medicine_id");
                                }
                                
                                // Thêm vào sale_details
                                $stmt = $pdo->prepare("
                                    INSERT INTO sale_details (sale_id, medicine_id, batch_id, quantity, unit_price, 
                                                            cost_price, discount_amount, total_price, prescription_detail_id)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                                ");
                                $stmt->execute([
                                    $new_id, $medicine_id, $batch_id, $quantity, $unit_price,
                                    $cost_price, $discount, $total_price, $prescription_detail_id
                                ]);
                                
                                // Cập nhật tồn kho
                                $stmt = $pdo->prepare("UPDATE medicine_batches SET current_quantity = current_quantity - ? WHERE id = ?");
                                $stmt->execute([$quantity, $batch_id]);
                            }
                        }
                        
                        $pdo->commit();
                        $message = "Thêm giao dịch bán hàng thành công! Mã giao dịch: " . $sale_code;
                        $message_type = "success";
                        error_log("Sale added successfully with ID: $new_id");
                    } else {
                        $pdo->rollback();
                        throw new Exception("Không thể thêm giao dịch vào cơ sở dữ liệu!");
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
        
        if ($message_type === 'success') {
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = $message_type;
            header('Location: ' . BASE_URL . 'manager/sales/');
            exit();
        }
    }

    // Check for flash messages
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $message_type = $_SESSION['flash_type'];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    }

    // Xử lý xóa sale
    if ($action === 'delete' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        try {
            $pdo->beginTransaction();
            
            // Kiểm tra trạng thái giao dịch
            $stmt = $pdo->prepare("SELECT status FROM sales WHERE id = ?");
            $stmt->execute([$id]);
            $sale_status = $stmt->fetchColumn();
            
            if ($sale_status !== 'draft') {
                $message = "Chỉ có thể xóa giao dịch ở trạng thái nháp!";
                $message_type = "danger";
            } else {
                // Xóa sale_details
                $stmt = $pdo->prepare("DELETE FROM sale_details WHERE sale_id = ?");
                $stmt->execute([$id]);
                
                // Xóa sale
                $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
                $result = $stmt->execute([$id]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $pdo->commit();
                    $_SESSION['flash_message'] = "Xóa giao dịch thành công!";
                    $_SESSION['flash_type'] = "success";
                } else {
                    $pdo->rollback();
                    $message = "Không tìm thấy giao dịch để xóa!";
                    $message_type = "danger";
                }
            }
        } catch (PDOException $e) {
            $pdo->rollback();
            error_log("Delete error: " . $e->getMessage());
            $message = "Lỗi: " . $e->getMessage();
            $message_type = "danger";
        }
        
        if (!$message) {
            header('Location: ' . BASE_URL . 'manager/sales/');
            exit();
        }
    }

    // Lấy danh sách sales từ view
    try {
        $stmt = $pdo->prepare("SELECT * FROM v_sales_summary ORDER BY sale_date DESC, sale_time DESC");
        $stmt->execute();
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $sales = [];
        error_log("Error fetching sales: " . $e->getMessage());
    }

    // Lấy danh sách patients, prescriptions, medicines cho dropdown
    try {
        $stmt = $pdo->prepare("SELECT id, full_name FROM patients WHERE status = 'active' ORDER BY full_name");
        $stmt->execute();
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT id, prescription_code, patient_id FROM prescriptions WHERE status IN ('pending', 'partial') ORDER BY prescription_date DESC");
        $stmt->execute();
        $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT id, name, medicine_code FROM medicines WHERE status = 'active' ORDER BY name");
        $stmt->execute();
        $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $patients = $prescriptions = $medicines = [];
        error_log("Error fetching dropdown data: " . $e->getMessage());
    }

    // Lấy thông tin sale để edit
    if ($action === 'edit' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        try {
            $stmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
            $stmt->execute([$id]);
            $edit_sale = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($edit_sale) {
                $stmt = $pdo->prepare("SELECT * FROM sale_details WHERE sale_id = ?");
                $stmt->execute([$id]);
                $edit_sale_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $_SESSION['flash_message'] = "Không tìm thấy giao dịch để chỉnh sửa!";
                $_SESSION['flash_type'] = "danger";
                header('Location: ' . BASE_URL . 'manager/sales/');
                exit();
            }
        } catch (PDOException $e) {
            error_log("Error fetching sale for edit: " . $e->getMessage());
            $_SESSION['flash_message'] = "Lỗi khi tải thông tin giao dịch!";
            $_SESSION['flash_type'] = "danger";
            header('Location: ' . BASE_URL . 'manager/sales/');
            exit();
        }
    }

    return [$message, $message_type, $edit_sale, $edit_sale_details, $sales, $patients, $prescriptions, $medicines, $user_info];
}
?>