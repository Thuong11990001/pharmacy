<?php
session_start();

// Database connection
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=improved_pharmacy;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check user session

// Set headers for CSV download
$filename = "bao_cao_don_thuoc_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Write UTF-8 BOM for proper encoding in Excel
fwrite($output, "\xEF\xBB\xBF");

// Write report header information
fputcsv($output, ['BÁO CÁO TÌNH TRẠNG ĐƠN THUỐC'], ';');
fputcsv($output, ['Ngày xuất báo cáo: ' . date('d/m/Y H:i:s')], ';');
fputcsv($output, ['Hệ thống quản lý nhà thuốc'], ';');
fputcsv($output, [''], ';'); // Empty row for spacing

// Get total prescriptions count and basic stats
try {
    $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM v_prescription_status");
    $totalPrescriptions = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get today's prescriptions
    $todayStmt = $pdo->query("
        SELECT COUNT(*) as today_count 
        FROM v_prescription_status 
        WHERE DATE(created_date) = CURDATE()
    ");
    $todayCount = $todayStmt->fetch(PDO::FETCH_ASSOC)['today_count'] ?? 0;
    
    fputcsv($output, ['TỔNG QUAN'], ';');
    fputcsv($output, ['Tổng số đơn thuốc:', number_format($totalPrescriptions, 0, ',', '.')], ';');
    fputcsv($output, ['Đơn thuốc hôm nay:', number_format($todayCount, 0, ',', '.')], ';');
    fputcsv($output, [''], ';'); // Empty row for spacing
} catch (PDOException $e) {
    fputcsv($output, ['Lỗi khi tải tổng quan: ' . $e->getMessage()], ';');
}

// Write CSV headers for prescription status
fputcsv($output, ['PHÂN TÍCH TÌNH TRẠNG ĐƠN THUỐC'], ';');
fputcsv($output, [
    'Trạng Thái',
    'Số Lượng Đơn Thuốc',
    'Tỷ Lệ (%)',
    'Mức Độ Ưu Tiên',
    'Ghi Chú'
], ';');

// Fetch prescription status data
try {
    $stmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count,
            (COUNT(*) * 100.0 / SUM(COUNT(*)) OVER ()) as percentage
        FROM v_prescription_status
        GROUP BY status
        ORDER BY 
            CASE status
                WHEN 'pending' THEN 1
                WHEN 'partial' THEN 2
                WHEN 'expired' THEN 3
                WHEN 'completed' THEN 4
                WHEN 'cancelled' THEN 5
                ELSE 6
            END
    ");
    
    $totalCount = 0;
    $pendingCount = 0;
    $expiredCount = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status_info = [
            'pending' => [
                'label' => 'Chờ xử lý',
                'priority' => 'Cao',
                'note' => 'Cần xử lý ngay'
            ],
            'partial' => [
                'label' => 'Một phần',
                'priority' => 'Trung bình',
                'note' => 'Đang thực hiện'
            ],
            'completed' => [
                'label' => 'Hoàn thành',
                'priority' => 'Bình thường',
                'note' => 'Đã hoàn tất'
            ],
            'cancelled' => [
                'label' => 'Hủy',
                'priority' => 'Thấp',
                'note' => 'Đã hủy bỏ'
            ],
            'expired' => [
                'label' => 'Hết hạn',
                'priority' => 'Cấp bách',
                'note' => 'Cần xem xét lại'
            ]
        ][$row['status']] ?? [
            'label' => $row['status'],
            'priority' => 'Không xác định',
            'note' => ''
        ];
        
        fputcsv($output, [
            $status_info['label'],
            number_format($row['count'], 0, ',', '.'),
            number_format($row['percentage'], 1, ',', '.') . '%',
            $status_info['priority'],
            $status_info['note']
        ], ';');
        
        $totalCount += $row['count'];
        
        // Track important counts for recommendations
        if ($row['status'] === 'pending') {
            $pendingCount = $row['count'];
        }
        if ($row['status'] === 'expired') {
            $expiredCount = $row['count'];
        }
    }
    
    // Add performance metrics
    fputcsv($output, [''], ';');
    fputcsv($output, ['HIỆU SUẤT XỬ LÝ'], ';');
    
    // Calculate completion rate
    $completedStmt = $pdo->query("
        SELECT COUNT(*) as completed_count 
        FROM v_prescription_status 
        WHERE status = 'completed'
    ");
    $completedCount = $completedStmt->fetch(PDO::FETCH_ASSOC)['completed_count'];
    $completionRate = $totalCount > 0 ? ($completedCount / $totalCount) * 100 : 0;
    
    fputcsv($output, ['Tỷ lệ hoàn thành:', number_format($completionRate, 1, ',', '.') . '%'], ';');
    
    // Add recommendations section
    fputcsv($output, [''], ';');
    fputcsv($output, ['KHUYẾN NGHỊ'], ';');
    
    if ($pendingCount > 0) {
        fputcsv($output, ['⚠️ Có ' . $pendingCount . ' đơn thuốc đang chờ xử lý'], ';');
    }
    
    if ($expiredCount > 0) {
        fputcsv($output, ['🔴 Có ' . $expiredCount . ' đơn thuốc đã hết hạn cần xem xét'], ';');
    }
    
    if ($completionRate < 80) {
        fputcsv($output, ['📊 Tỷ lệ hoàn thành thấp, cần cải thiện quy trình'], ';');
    } elseif ($completionRate >= 90) {
        fputcsv($output, ['✅ Tỷ lệ hoàn thành tốt, duy trì hiệu suất'], ';');
    }
    
    // Write summary footer
    fputcsv($output, [''], ';');
    fputcsv($output, ['Tổng số bản ghi:', number_format($totalCount, 0, ',', '.')], ';');
    fputcsv($output, ['Xuất lúc:', date('d/m/Y H:i:s')], ';');
    
} catch (PDOException $e) {
    fputcsv($output, ['Lỗi', 'Không thể tải dữ liệu: ' . $e->getMessage()], ';');
}

// Close the file pointer
fclose($output);
exit;
?>