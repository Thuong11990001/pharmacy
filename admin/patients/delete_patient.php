<?php
session_start();

$host = '127.0.0.1';
$dbname = 'improved_pharmacy';
$username = 'root'; // Adjust as needed
$password = ''; // Adjust as needed

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => "Kết nối cơ sở dữ liệu thất bại: " . $e->getMessage()]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        $id = trim($_POST['id']);
        if (!is_numeric($id)) {
            throw new Exception("ID bệnh nhân không hợp lệ");
        }
        $stmt = $pdo->prepare("DELETE FROM patients WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $_SESSION['success'] = "Xóa bệnh nhân thành công!";
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => "Lỗi khi xóa bệnh nhân: " . $e->getMessage()]);
        exit();
    }
}

header('Content-Type: application/json');
http_response_code(400);
echo json_encode(['error' => "Yêu cầu không hợp lệ"]);
exit();
?>