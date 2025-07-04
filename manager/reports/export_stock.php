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
$filename = "bao_cao_ton_kho_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Write UTF-8 BOM for proper encoding in Excel
fwrite($output, "\xEF\xBB\xBF");

// Write report header information
fputcsv($output, ['BÁO CÁO TÌNH TRẠNG TỒN KHO'], ';');
fputcsv($output, ['Ngày xuất báo cáo: ' . date('d/m/Y H:i:s')], ';');
fputcsv($output, ['Hệ thống quản lý nhà thuốc'], ';');
fputcsv($output, [''], ';'); // Empty row for spacing

// Get total products count
try {
    $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM v_current_stock");
    $totalProducts = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    fputcsv($output, ['TỔNG QUAN'], ';');
    fputcsv($output, ['Tổng số sản phẩm trong kho:', number_format($totalProducts, 0, ',', '.')], ';');
    fputcsv($output, [''], ';'); // Empty row for spacing
} catch (PDOException $e) {
    fputcsv($output, ['Lỗi khi tải tổng quan: ' . $e->getMessage()], ';');
}

// Write CSV headers for stock status
fputcsv($output, ['PHÂN TÍCH TÌNH TRẠNG TỒN KHO'], ';');
fputcsv($output, [
    'Trạng Thái Tồn Kho',
    'Số Lượng Sản Phẩm',
    'Tỷ Lệ (%)',
    'Mức Độ Ưu Tiên'
], ';');

// Fetch stock status data
try {
    $stmt = $pdo->query("
        SELECT 
            stock_status,
            COUNT(*) as count,
            (COUNT(*) * 100.0 / SUM(COUNT(*)) OVER ()) as percentage
        FROM v_current_stock
        GROUP BY stock_status
        ORDER BY 
            CASE stock_status
                WHEN 'Out of Stock' THEN 1
                WHEN 'Low Stock' THEN 2
                WHEN 'Near Expiry' THEN 3
                WHEN 'In Stock' THEN 4
                ELSE 5
            END
    ");
    
    $totalCount = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status_info = [
            'Out of Stock' => ['label' => 'Hết hàng', 'priority' => 'Cấp bách'],
            'Low Stock' => ['label' => 'Sắp hết', 'priority' => 'Cao'],
            'Near Expiry' => ['label' => 'Gần hết hạn', 'priority' => 'Trung bình'],
            'In Stock' => ['label' => 'Còn hàng', 'priority' => 'Bình thường']
        ][$row['stock_status']] ?? ['label' => $row['stock_status'], 'priority' => 'Không xác định'];
        
        fputcsv($output, [
            $status_info['label'],
            number_format($row['count'], 0, ',', '.'),
            number_format($row['percentage'], 1, ',', '.') . '%',
            $status_info['priority']
        ], ';');
        
        $totalCount += $row['count'];
    }
    
    // Add summary section
    fputcsv($output, [''], ';');
    fputcsv($output, ['KHUYẾN NGHỊ'], ';');
    
    // Get critical stock items
    $criticalStmt = $pdo->query("
        SELECT COUNT(*) as critical_count 
        FROM v_current_stock 
        WHERE stock_status IN ('Out of Stock', 'Low Stock')
    ");
    $criticalCount = $criticalStmt->fetch(PDO::FETCH_ASSOC)['critical_count'];
    
    if ($criticalCount > 0) {
        fputcsv($output, ['⚠️ Có ' . $criticalCount . ' sản phẩm cần nhập kho khẩn cấp'], ';');
    }
    
    // Get near expiry items
    $expiryStmt = $pdo->query("
        SELECT COUNT(*) as expiry_count 
        FROM v_current_stock 
        WHERE stock_status = 'Near Expiry'
    ");
    $expiryCount = $expiryStmt->fetch(PDO::FETCH_ASSOC)['expiry_count'];
    
    if ($expiryCount > 0) {
        fputcsv($output, ['⏰ Có ' . $expiryCount . ' sản phẩm gần hết hạn cần xử lý'], ';');
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