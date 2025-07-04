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
    $_SESSION['error'] = "Kết nối cơ sở dữ liệu thất bại: " . $e->getMessage();
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $data = [];

    $data['id'] = trim($_POST['id'] ?? '');
    $data['full_name'] = trim($_POST['full_name'] ?? '');
    $data['id_number'] = trim($_POST['id_number'] ?? '');
    $data['phone'] = trim($_POST['phone'] ?? '');
    $data['email'] = trim($_POST['email'] ?? '');
    $data['address'] = trim($_POST['address'] ?? '');
    $data['date_of_birth'] = trim($_POST['date_of_birth'] ?? '');
    $data['gender'] = trim($_POST['gender'] ?? '');
    $data['emergency_contact'] = trim($_POST['emergency_contact'] ?? '');
    $data['allergies'] = trim($_POST['allergies'] ?? '');
    $data['medical_notes'] = trim($_POST['medical_notes'] ?? '');
    $data['status'] = trim($_POST['status'] ?? 'active');

    if (empty($data['id']) || !is_numeric($data['id'])) {
        $errors[] = "ID bệnh nhân không hợp lệ";
    }
    if (empty($data['full_name'])) {
        $errors[] = "Tên bệnh nhân không được để trống";
    }
    if (!empty($data['id_number'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE id_number = :id_number AND id != :id");
        $stmt->execute(['id_number' => $data['id_number'], 'id' => $data['id']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Số CMND/CCCD đã tồn tại";
        }
    }
    if (!empty($data['phone']) && !preg_match('/^[0-9]{10,12}$/', $data['phone'])) {
        $errors[] = "Số điện thoại phải có 10-12 chữ số";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE phone = :phone AND id != :id");
        $stmt->execute(['phone' => $data['phone'], 'id' => $data['id']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Số điện thoại đã tồn tại";
        }
    }
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không hợp lệ";
    }
    if (!empty($data['date_of_birth']) && !DateTime::createFromFormat('Y-m-d', $data['date_of_birth'])) {
        $errors[] = "Ngày sinh không hợp lệ (định dạng: YYYY-MM-DD)";
    }
    if (!in_array($data['gender'], ['male', 'female', 'other', ''])) {
        $errors[] = "Giới tính không hợp lệ";
    }
    if (!in_array($data['status'], ['active', 'inactive'])) {
        $errors[] = "Trạng thái không hợp lệ";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE patients SET
                    full_name = :full_name,
                    id_number = :id_number,
                    phone = :phone,
                    email = :email,
                    address = :address,
                    date_of_birth = :date_of_birth,
                    gender = :gender,
                    emergency_contact = :emergency_contact,
                    allergies = :allergies,
                    medical_notes = :medical_notes,
                    status = :status,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $data['id'],
                'full_name' => $data['full_name'],
                'id_number' => $data['id_number'] ?: null,
                'phone' => $data['phone'] ?: null,
                'email' => $data['email'] ?: null,
                'address' => $data['address'] ?: null,
                'date_of_birth' => $data['date_of_birth'] ?: null,
                'gender' => $data['gender'] ?: null,
                'emergency_contact' => $data['emergency_contact'] ?: null,
                'allergies' => $data['allergies'] ?: null,
                'medical_notes' => $data['medical_notes'] ?: null,
                'status' => $data['status']
            ]);
            $_SESSION['success'] = "Cập nhật bệnh nhân thành công!";
            header('Location: index.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Lỗi khi cập nhật bệnh nhân: " . $e->getMessage();
            header('Location: index.php');
            exit();
        }
    } else {
        $_SESSION['error'] = "Vui lòng kiểm tra lại thông tin:\n• " . implode("\n• ", $errors);
        header('Location: index.php');
        exit();
    }
}

header('Location: index.php');
exit();
?>