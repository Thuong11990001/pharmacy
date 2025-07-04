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
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sales_report_' . date('Ymd_His') . '.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Write UTF-8 BOM for proper encoding in Excel
fwrite($output, "\xEF\xBB\xBF");

// Write CSV headers
fputcsv($output, [
    'Ngày Bán',
    'Tổng Doanh Thu (VND)',
    'Số Đơn Hàng'
], ';');

// Fetch sales data
try {
    $stmt = $pdo->query("
        SELECT 
            DATE(sale_date) as sale_date,
            SUM(total_amount) as total_sales,
            COUNT(id) as order_count
        FROM sales 
        WHERE status = 'completed'
        GROUP BY DATE(sale_date)
        ORDER BY sale_date DESC
        LIMIT 30
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            date('d/m/Y', strtotime($row['sale_date'])),
            number_format($row['total_sales'], 2, ',', '.'),
            $row['order_count']
        ], ';');
    }
} catch (PDOException $e) {
    fputcsv($output, ['Lỗi', 'Không thể tải dữ liệu: ' . $e->getMessage()], ';');
}

// Close the file pointer
fclose($output);
exit;
?>