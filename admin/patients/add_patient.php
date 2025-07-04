<?php
session_start();

$host = '127.0.0.1';
$dbname = 'improved_pharmacy';
$username = 'root';
$password = '';

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

    // Validation
    if (empty($data['full_name'])) {
        $errors[] = "Tên bệnh nhân không được để trống";
    }
    if (!empty($data['id_number'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE id_number = :id_number");
        $stmt->execute(['id_number' => $data['id_number']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Số CMND/CCCD đã tồn tại";
        }
    }
    if (!empty($data['phone'])) {
        if (!preg_match('/^[0-9]{10,12}$/', $data['phone'])) {
            $errors[] = "Số điện thoại phải có 10-12 chữ số";
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE phone = :phone");
            $stmt->execute(['phone' => $data['phone']]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Số điện thoại đã tồn tại";
            }
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
            // Generate patient_code
            $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(patient_code, 4) AS UNSIGNED)) as max_code FROM patients WHERE patient_code LIKE 'PAT%'");
            $result = $stmt->fetch();
            $next_number = ($result['max_code'] ?? 0) + 1;
            $patient_code = 'PAT' . str_pad($next_number, 4, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare("
                INSERT INTO patients (
                    patient_code, full_name, id_number, phone, email, address, 
                    date_of_birth, gender, emergency_contact, allergies, medical_notes, 
                    status, created_at, updated_at
                ) VALUES (
                    :patient_code, :full_name, :id_number, :phone, :email, :address,
                    :date_of_birth, :gender, :emergency_contact, :allergies, :medical_notes,
                    :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )
            ");
            
            $stmt->execute([
                'patient_code' => $patient_code,
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
            
            $_SESSION['success'] = "Thêm bệnh nhân thành công!";
            header('Location: index.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Lỗi khi thêm bệnh nhân: " . $e->getMessage();
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