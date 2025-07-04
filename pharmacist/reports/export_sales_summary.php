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
$filename = "bao_cao_tom_tat_ban_hang_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Write UTF-8 BOM for proper encoding in Excel
fwrite($output, "\xEF\xBB\xBF");

// Write report header information
fputcsv($output, ['BÁO CÁO TÓM TẮT BÁN HÀNG'], ';');
fputcsv($output, ['Ngày xuất báo cáo: ' . date('d/m/Y H:i:s')], ';');
fputcsv($output, ['Hệ thống quản lý nhà thuốc'], ';');
fputcsv($output, [''], ';'); // Empty row for spacing

// Get total statistics
try {
    $statsStmt = $pdo->query("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value
        FROM v_sales_summary 
        WHERE status = 'completed'
    ");
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    fputcsv($output, ['THỐNG KÊ TỔNG QUAN'], ';');
    fputcsv($output, ['Tổng số đơn hàng:', number_format($stats['total_orders'], 0, ',', '.')], ';');
    fputcsv($output, ['Tổng doanh thu:', number_format($stats['total_revenue'], 0, ',', '.') . ' VND'], ';');
    fputcsv($output, ['Giá trị đơn hàng trung bình:', number_format($stats['avg_order_value'], 0, ',', '.') . ' VND'], ';');
    fputcsv($output, [''], ';'); // Empty row for spacing
} catch (PDOException $e) {
    fputcsv($output, ['Lỗi khi tải thống kê: ' . $e->getMessage()], ';');
}

// Write detailed data headers
fputcsv($output, ['CHI TIẾT CÁC ĐỠN HÀNG'], ';');
fputcsv($output, [
    'Mã Đơn',
    'Ngày Bán',
    'Khách Hàng',
    'Tổng Tiền (VND)',
    'Phương Thức Thanh Toán',
    'Trạng Thái',
    'Nhân Viên'
], ';');

// Fetch sales summary data
try {
    $stmt = $pdo->query("
        SELECT 
            sale_code,
            sale_date,
            customer_name,
            total_amount,
            payment_method,
            status,
            employee_name
        FROM v_sales_summary 
        WHERE status = 'completed'
        ORDER BY sale_date DESC
    ");
    
    $rowCount = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $payment_method = [
            'cash' => 'Tiền mặt',
            'card' => 'Thẻ',
            'bank_transfer' => 'Chuyển khoản',
            'insurance' => 'Bảo hiểm',
            'mixed' => 'Hỗn hợp'
        ][$row['payment_method']] ?? $row['payment_method'];
        
        $status = [
            'draft' => 'Nháp',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Hủy',
            'returned' => 'Trả lại'
        ][$row['status']] ?? $row['status'];
        
        fputcsv($output, [
            $row['sale_code'],
            date('d/m/Y H:i', strtotime($row['sale_date'])),
            $row['customer_name'] ?? 'Khách lẻ',
            number_format($row['total_amount'], 0, ',', '.'),
            $payment_method,
            $status,
            $row['employee_name'] ?? 'Không xác định'
        ], ';');
        
        $rowCount++;
    }
    
    // Write summary footer
    fputcsv($output, [''], ';');
    fputcsv($output, ['Tổng số bản ghi:', $rowCount], ';');
    fputcsv($output, ['Xuất lúc:', date('d/m/Y H:i:s')], ';');
    
} catch (PDOException $e) {
    fputcsv($output, ['Lỗi', 'Không thể tải dữ liệu: ' . $e->getMessage()], ';');
}

// Close the file pointer
fclose($output);
exit;
?>