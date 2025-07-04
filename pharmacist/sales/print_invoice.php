<?php
// manager/sales/print_invoice.php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../config/database.php';

// Kiểm tra đăng nhập và quyền truy cập
SessionManager::requireRole(['admin', 'manager', 'pharmacist', 'cashier']);

// Lấy sale ID từ URL
$sale_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($sale_id <= 0) {
    die('Lỗi: Không tìm thấy mã giao dịch.');
}

// Lấy kết nối database
$db = new Database();
$conn = $db->getConnection();
if (!$conn) {
    die('Lỗi: Không thể kết nối đến cơ sở dữ liệu.');
}

// Lấy thông tin sale từ view v_sales_summary
try {
    $stmt = $conn->prepare("
        SELECT s.sale_id, s.sale_code, s.sale_date, s.sale_time, s.customer_name, s.total_amount, 
               s.payment_method, s.status, s.employee_name, s.item_count,
               p.full_name AS patient_name, p.phone AS patient_phone
        FROM v_sales_summary s
        LEFT JOIN patients p ON s.customer_name = p.full_name
        WHERE s.sale_id = :sale_id
    ");
    $stmt->execute(['sale_id' => $sale_id]);
    $sale = $stmt->fetch();

    if (!$sale) {
        die('Lỗi: Không tìm thấy giao dịch với mã này.');
    }

    // Lấy chi tiết giao dịch từ sale_details và medicines
    $stmt = $conn->prepare("
        SELECT sd.id, sd.quantity, sd.unit_price, sd.discount_amount, sd.total_price,
               m.medicine_code, m.name AS medicine_name, m.unit
        FROM sale_details sd
        JOIN medicines m ON sd.medicine_id = m.id
        WHERE sd.sale_id = :sale_id
    ");
    $stmt->execute(['sale_id' => $sale_id]);
    $sale_details = $stmt->fetchAll();
} catch (PDOException $e) {
    die('Lỗi truy vấn cơ sở dữ liệu: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hóa đơn #<?php echo htmlspecialchars($sale['sale_code']); ?> - Pharmacy Management System</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom Print CSS -->
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                font-size: 12pt;
            }
            .invoice-box {
                border: 1px solid #000;
                padding: 20px;
            }
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            font-size: 16px;
            line-height: 24px;
            font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif;
            color: #555;
        }
        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
        }
        .invoice-box table td {
            padding: 5px;
            vertical-align: top;
        }
        .invoice-box table tr td:nth-child(2) {
            text-align: right;
        }
        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }
        .invoice-box table tr.top table td.title {
            font-size: 45px;
            line-height: 45px;
            color: #333;
        }
        .invoice-box table tr.information table td {
            padding-bottom: 40px;
        }
        .invoice-box table tr.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }
        .invoice-box table tr.item td {
            border-bottom: 1px solid #eee;
        }
        .invoice-box table tr.item.last td {
            border-bottom: none;
        }
        .invoice-box table tr.total td:nth-child(2) {
            border-top: 2px solid #eee;
            font-weight: bold;
        }
        @media only screen and (max-width: 600px) {
            .invoice-box table tr.top table td {
                width: 100%;
                display: block;
                text-align: center;
            }
            .invoice-box table tr.information table td {
                width: 100%;
                display: block;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="title">
                                <img src="https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png" style="width:100px; max-width:100px;">
                            </td>
                            <td>
                                Hóa đơn #<?php echo htmlspecialchars($sale['sale_code']); ?><br>
                                Ngày: <?php echo htmlspecialchars($sale['sale_date']); ?><br>
                                Giờ: <?php echo htmlspecialchars($sale['sale_time']); ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                            <td>
                                Pharmacy Management System<br>
                                123 Đường ABC, Quận 1, TP.HCM<br>
                                Email: info@pharmacy.com
                            </td>
                            <td>
                                Khách hàng: <?php echo htmlspecialchars($sale['customer_name']); ?><br>
                                <?php if ($sale['patient_phone']): ?>
                                    Số điện thoại: <?php echo htmlspecialchars($sale['patient_phone']); ?><br>
                                <?php endif; ?>
                                Nhân viên: <?php echo htmlspecialchars($sale['employee_name']); ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="heading">
                <td>Sản phẩm</td>
                <td>Số tiền</td>
            </tr>
            <?php foreach ($sale_details as $detail): ?>
                <tr class="item">
                    <td>
                        <?php echo htmlspecialchars($detail['medicine_code'] . ' - ' . $detail['medicine_name']); ?><br>
                        Số lượng: <?php echo $detail['quantity']; ?> <?php echo htmlspecialchars($detail['unit']); ?><br>
                        Đơn giá: <?php echo number_format($detail['unit_price'], 2); ?> VNĐ
                        <?php if ($detail['discount_amount'] > 0): ?>
                            <br>Giảm giá: <?php echo number_format($detail['discount_amount'], 2); ?> VNĐ
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo number_format($detail['total_price'], 2); ?> VNĐ
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td></td>
                <td>
                    Tổng cộng: <?php echo number_format($sale['total_amount'], 2); ?> VNĐ
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    Phương thức thanh toán: <?php echo htmlspecialchars($sale['payment_method']); ?><br>
                    Trạng thái: <?php echo htmlspecialchars($sale['status']); ?>
                </td>
            </tr>
        </table>
        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> In hóa đơn</button>
            <a href="<?php echo BASE_URL; ?>manager/sales/" class="btn btn-secondary ml-2"><i class="fas fa-arrow-left"></i> Quay lại</a>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>