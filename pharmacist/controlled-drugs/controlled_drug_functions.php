<?php
// manager/controlled_drugs/controlled_drug_functions.php

function handleControlledDrugActions($action) {
    $message = '';
    $message_type = '';
    $logs = [];
    $medicines = [];
    $patients = [];
    $users = [];
    $user_info = [];
    $statistics = [];

    try {
        // Get user info
        $user_info = SessionManager::getUserInfo();
        
        // Handle actions
        switch ($action) {
            case 'approve':
                if (isset($_GET['id'])) {
                    $result = approveControlledDrugLog($_GET['id'], $user_info['id']);
                    if ($result) {
                        $message = 'Log thuốc kiểm soát đã được phê duyệt thành công.';
                        $message_type = 'success';
                    } else {
                        $message = 'Có lỗi xảy ra khi phê duyệt log.';
                        $message_type = 'danger';
                    }
                }
                break;
                
            case 'export':
                exportControlledDrugLogs();
                return [null, null, null, null, null, null, null, null]; // Exit after export
                
            case 'print':
                return printControlledDrugReport();
                
            case 'statistics':
                return getControlledDrugStatistics();
                
            case 'detail':
                if (isset($_GET['id'])) {
                    return getControlledDrugDetail($_GET['id']);
                }
                break;
        }

        // Get data for display
        $logs = getControlledDrugLogs();
        $medicines = getControlledMedicines();
        $patients = getPatients();
        $users = getUsers();
        $statistics = getControlledDrugStatistics();

    } catch (Exception $e) {
        $message = 'Có lỗi xảy ra: ' . $e->getMessage();
        $message_type = 'danger';
    }

    return [$message, $message_type, $logs, $medicines, $patients, $users, $user_info, $statistics];
}

function getControlledDrugLogs($filters = []) {
    global $pdo;
    
    $sql = "SELECT 
                cdl.*,
                m.name as medicine_name,
                m.medicine_code,
                p.full_name as patient_name,
                p.patient_code,
                u.full_name as sold_by_name,
                supervisor.full_name as supervisor_name
            FROM controlled_drug_log cdl
            JOIN medicines m ON cdl.medicine_id = m.id
            JOIN patients p ON cdl.patient_id = p.id
            JOIN users u ON cdl.sold_by = u.id
            LEFT JOIN users supervisor ON cdl.supervisor_approved_by = supervisor.id
            WHERE 1=1";
    
    $params = [];
    
    // Apply filters from GET parameters
    if (isset($_GET['filter_medicine']) && !empty($_GET['filter_medicine'])) {
        $sql .= " AND cdl.medicine_id = :medicine_id";
        $params['medicine_id'] = $_GET['filter_medicine'];
    }
    
    if (isset($_GET['filter_patient']) && !empty($_GET['filter_patient'])) {
        $sql .= " AND cdl.patient_id = :patient_id";
        $params['patient_id'] = $_GET['filter_patient'];
    }
    
    if (isset($_GET['filter_employee']) && !empty($_GET['filter_employee'])) {
        $sql .= " AND cdl.sold_by = :sold_by";
        $params['sold_by'] = $_GET['filter_employee'];
    }
    
    if (isset($_GET['filter_doctor']) && !empty($_GET['filter_doctor'])) {
        $sql .= " AND cdl.doctor_name LIKE :doctor_name";
        $params['doctor_name'] = '%' . $_GET['filter_doctor'] . '%';
    }
    
    if (isset($_GET['filter_date_from']) && !empty($_GET['filter_date_from'])) {
        $sql .= " AND DATE(cdl.sold_at) >= :date_from";
        $params['date_from'] = $_GET['filter_date_from'];
    }
    
    if (isset($_GET['filter_date_to']) && !empty($_GET['filter_date_to'])) {
        $sql .= " AND DATE(cdl.sold_at) <= :date_to";
        $params['date_to'] = $_GET['filter_date_to'];
    }
    
    if (isset($_GET['filter_approval_status']) && !empty($_GET['filter_approval_status'])) {
        if ($_GET['filter_approval_status'] == 'approved') {
            $sql .= " AND cdl.supervisor_approved_by IS NOT NULL";
        } elseif ($_GET['filter_approval_status'] == 'pending') {
            $sql .= " AND cdl.supervisor_approved_by IS NULL";
        }
    }
    
    $sql .= " ORDER BY cdl.sold_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getControlledMedicines() {
    global $pdo;
    
    $sql = "SELECT id, medicine_code, name 
            FROM medicines 
            WHERE is_controlled = 1 AND status = 'active'
            ORDER BY name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPatients() {
    global $pdo;
    
    $sql = "SELECT id, patient_code, full_name 
            FROM patients 
            WHERE status = 'active'
            ORDER BY full_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUsers() {
    global $pdo;
    
    $sql = "SELECT id, full_name 
            FROM users 
            WHERE status = 'active' AND role IN ('pharmacist', 'cashier', 'manager')
            ORDER BY full_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getControlledDrugStatistics() {
    global $pdo;
    
    // Total logs
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM controlled_drug_log");
    $stmt->execute();
    $total_logs = $stmt->fetchColumn();
    
    // Today's logs
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM controlled_drug_log WHERE DATE(sold_at) = CURDATE()");
    $stmt->execute();
    $today_logs = $stmt->fetchColumn();
    
    // Controlled medicines count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM medicines WHERE is_controlled = 1 AND status = 'active'");
    $stmt->execute();
    $controlled_medicines = $stmt->fetchColumn();
    
    // Pending approval
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM controlled_drug_log WHERE supervisor_approved_by IS NULL");
    $stmt->execute();
    $pending_approval = $stmt->fetchColumn();
    
    return [
        'total_logs' => $total_logs,
        'today_logs' => $today_logs,
        'controlled_medicines' => $controlled_medicines,
        'pending_approval' => $pending_approval
    ];
}

function approveControlledDrugLog($log_id, $supervisor_id) {
    global $pdo;
    
    try {
        $sql = "UPDATE controlled_drug_log 
                SET supervisor_approved_by = :supervisor_id 
                WHERE id = :log_id AND supervisor_approved_by IS NULL";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            'supervisor_id' => $supervisor_id,
            'log_id' => $log_id
        ]);
        
        return $result && $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error approving controlled drug log: " . $e->getMessage());
        return false;
    }
}

function getControlledDrugDetail($log_id) {
    global $pdo;
    
    $sql = "SELECT 
                cdl.*,
                m.name as medicine_name,
                m.medicine_code,
                m.strength,
                m.dosage_form,
                m.generic_name,
                p.full_name as patient_name,
                p.patient_code,
                p.phone as patient_phone,
                p.address as patient_address,
                u.full_name as sold_by_name,
                supervisor.full_name as supervisor_name,
                s.sale_code,
                s.total_amount as sale_total,
                pr.prescription_code,
                pr.diagnosis,
                pr.hospital_clinic,
                mb.batch_number,
                mb.expiry_date
            FROM controlled_drug_log cdl
            JOIN medicines m ON cdl.medicine_id = m.id
            JOIN patients p ON cdl.patient_id = p.id
            JOIN users u ON cdl.sold_by = u.id
            JOIN sales s ON cdl.sale_id = s.id
            JOIN prescriptions pr ON cdl.prescription_id = pr.id
            JOIN medicine_batches mb ON cdl.batch_id = mb.id
            LEFT JOIN users supervisor ON cdl.supervisor_approved_by = supervisor.id
            WHERE cdl.id = :log_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['log_id' => $log_id]);
    
    $detail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($detail) {
        // Format data for display
        $detail['formatted_sold_at'] = date('d/m/Y H:i:s', strtotime($detail['sold_at']));
        $detail['formatted_expiry_date'] = date('d/m/Y', strtotime($detail['expiry_date']));
        $detail['total_price'] = $detail['quantity'] * $detail['unit_price'];
        
        // Return as JSON for AJAX
        header('Content-Type: application/json');
        echo json_encode($detail);
        exit;
    }
    
    return null;
}

function exportControlledDrugLogs() {
    global $pdo;
    
    $logs = getControlledDrugLogs();
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="controlled_drug_logs_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output Excel content
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>STT</th>';
    echo '<th>Mã log</th>';
    echo '<th>Mã thuốc</th>';
    echo '<th>Tên thuốc</th>';
    echo '<th>Mã bệnh nhân</th>';
    echo '<th>Tên bệnh nhân</th>';
    echo '<th>Số lượng</th>';
    echo '<th>Đơn giá</th>';
    echo '<th>Thành tiền</th>';
    echo '<th>Bác sĩ</th>';
    echo '<th>Giấy phép hành nghề</th>';
    echo '<th>Nhân viên bán</th>';
    echo '<th>Thời gian bán</th>';
    echo '<th>Trạng thái phê duyệt</th>';
    echo '<th>Người phê duyệt</th>';
    echo '</tr>';
    
    foreach ($logs as $index => $log) {
        echo '<tr>';
        echo '<td>' . ($index + 1) . '</td>';
        echo '<td>' . htmlspecialchars($log['log_code']) . '</td>';
        echo '<td>' . htmlspecialchars($log['medicine_code']) . '</td>';
        echo '<td>' . htmlspecialchars($log['medicine_name']) . '</td>';
        echo '<td>' . htmlspecialchars($log['patient_code']) . '</td>';
        echo '<td>' . htmlspecialchars($log['patient_name']) . '</td>';
        echo '<td>' . number_format($log['quantity']) . '</td>';
        echo '<td>' . number_format($log['unit_price'], 2) . '</td>';
        echo '<td>' . number_format($log['unit_price'] * $log['quantity'], 2) . '</td>';
        echo '<td>' . htmlspecialchars($log['doctor_name']) . '</td>';
        echo '<td>' . htmlspecialchars($log['doctor_license'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($log['sold_by_name']) . '</td>';
        echo '<td>' . date('d/m/Y H:i:s', strtotime($log['sold_at'])) . '</td>';
        echo '<td>' . ($log['supervisor_approved_by'] ? 'Đã phê duyệt' : 'Chờ phê duyệt') . '</td>';
        echo '<td>' . htmlspecialchars($log['supervisor_name'] ?? '') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    exit;
}

function printControlledDrugReport() {
    $logs = getControlledDrugLogs();
    $statistics = getControlledDrugStatistics();
    
    // Return HTML for printing
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Báo cáo Log Thuốc Kiểm soát</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            .header { text-align: center; margin-bottom: 20px; }
            .statistics { margin-bottom: 20px; }
            .statistics table { width: 100%; border-collapse: collapse; }
            .statistics th, .statistics td { border: 1px solid #ddd; padding: 8px; text-align: center; }
            .data-table { width: 100%; border-collapse: collapse; font-size: 10px; }
            .data-table th, .data-table td { border: 1px solid #ddd; padding: 4px; }
            .data-table th { background-color: #f5f5f5; }
            @media print {
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h2>BÁO CÁO LOG THUỐC KIỂM SOÁT</h2>
            <p>Ngày in: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
        
        <div class="statistics">
            <h3>THỐNG KÊ TỔNG QUAN</h3>
            <table>
                <tr>
                    <th>Tổng số log</th>
                    <th>Log hôm nay</th>
                    <th>Thuốc kiểm soát</th>
                    <th>Chờ phê duyệt</th>
                </tr>
                <tr>
                    <td><?php echo $statistics['total_logs']; ?></td>
                    <td><?php echo $statistics['today_logs']; ?></td>
                    <td><?php echo $statistics['controlled_medicines']; ?></td>
                    <td><?php echo $statistics['pending_approval']; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="data">
            <h3>CHI TIẾT LOG THUỐC KIỂM SOÁT</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã log</th>
                        <th>Thuốc</th>
                        <th>Bệnh nhân</th>
                        <th>SL</th>
                        <th>Đơn giá</th>
                        <th>Bác sĩ</th>
                        <th>NV bán</th>
                        <th>Thời gian</th>
                        <th>Phê duyệt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $index => $log): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($log['log_code']); ?></td>
                        <td><?php echo htmlspecialchars($log['medicine_name']); ?></td>
                        <td><?php echo htmlspecialchars($log['patient_name']); ?></td>
                        <td><?php echo number_format($log['quantity']); ?></td>
                        <td><?php echo number_format($log['unit_price'], 0); ?>đ</td>
                        <td><?php echo htmlspecialchars($log['doctor_name']); ?></td>
                        <td><?php echo htmlspecialchars($log['sold_by_name']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($log['sold_at'])); ?></td>
                        <td><?php echo $log['supervisor_approved_by'] ? 'Đã duyệt' : 'Chờ duyệt'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <script>
            window.onload = function() {
                window.print();
            }
        </script>
    </body>
    </html>
    <?php
    $content = ob_get_clean();
    echo $content;
    exit;
}

// Additional utility functions
function getControlledDrugsByDate($date_from, $date_to) {
    global $pdo;
    
    $sql = "SELECT 
                DATE(cdl.sold_at) as sale_date,
                COUNT(*) as total_logs,
                SUM(cdl.quantity * cdl.unit_price) as total_amount,
                COUNT(CASE WHEN cdl.supervisor_approved_by IS NULL THEN 1 END) as pending_approval
            FROM controlled_drug_log cdl
            WHERE DATE(cdl.sold_at) BETWEEN :date_from AND :date_to
            GROUP BY DATE(cdl.sold_at)
            ORDER BY sale_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'date_from' => $date_from,
        'date_to' => $date_to
    ]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTopControlledMedicines($limit = 10) {
    global $pdo;
    
    $sql = "SELECT 
                m.name as medicine_name,
                m.medicine_code,
                COUNT(cdl.id) as total_sales,
                SUM(cdl.quantity) as total_quantity,
                SUM(cdl.quantity * cdl.unit_price) as total_amount
            FROM controlled_drug_log cdl
            JOIN medicines m ON cdl.medicine_id = m.id
            GROUP BY cdl.medicine_id
            ORDER BY total_sales DESC
            LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getControlledDrugAlerts() {
    global $pdo;
    
    $alerts = [];
    
    // Check for unapproved logs older than 24 hours
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM controlled_drug_log 
        WHERE supervisor_approved_by IS NULL 
        AND sold_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $overdue = $stmt->fetchColumn();
    
    if ($overdue > 0) {
        $alerts[] = [
            'type' => 'warning',
            'message' => "Có {$overdue} log thuốc kiểm soát chưa được phê duyệt quá 24 giờ"
        ];
    }
    
    // Check for high volume sales today
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM controlled_drug_log 
        WHERE DATE(sold_at) = CURDATE()
    ");
    $stmt->execute();
    $today_count = $stmt->fetchColumn();
    
    if ($today_count > 50) { // Threshold for high volume
        $alerts[] = [
            'type' => 'info',
            'message' => "Hôm nay có {$today_count} giao dịch thuốc kiểm soát"
        ];
    }
    
    return $alerts;
}

?>