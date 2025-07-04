<?php
// models/User.php
require_once 'config/database.php';

class User {
    private $conn;
    private $table_name = "users";
    
    public $id;
    public $user_code;
    public $username;
    public $password;
    public $full_name;
    public $email;
    public $phone;
    public $role;
    public $can_sell_controlled;
    public $status;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function login($username, $password) {
        try {
            error_log("Login attempt - Username: $username");
            
            // Sửa lỗi: Đơn giản hóa query và sử dụng parameter đúng cách
            $query = "SELECT id, user_code, username, password, full_name, email, phone, role, 
                             can_sell_controlled, status, created_at, updated_at 
                      FROM " . $this->table_name . " 
                      WHERE (username = ? OR email = ?) AND status = 'active'
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters theo thứ tự
            $stmt->bindValue(1, $username, PDO::PARAM_STR);
            $stmt->bindValue(2, $username, PDO::PARAM_STR);
            
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("Found user: " . $row['username']);
                
                // Verify password
                if (password_verify($password, $row['password'])) {
                    error_log("Password verified successfully");
                    $this->updateLastLogin($row['id']);
                    $this->logUserActivity($row['id'], 'LOGIN_SUCCESS', 'User logged in successfully');
                    return $row;
                } else {
                    error_log("Password verification failed");
                    $this->logUserActivity($row['id'], 'LOGIN_FAILED', 'Invalid password');
                    return false;
                }
            } else {
                error_log("User not found: " . $username);
                $this->logUserActivity(null, 'LOGIN_FAILED', 'Username not found: ' . $username);
                return false;
            }
        } catch (PDOException $exception) {
            error_log("Login error: " . $exception->getMessage());
            return false;
        }
    }
    
    public function getUserById($id) {
        try {
            $query = "SELECT id, user_code, username, full_name, email, phone, role, 
                             can_sell_controlled, status, created_at, updated_at 
                      FROM " . $this->table_name . " 
                      WHERE id = ? AND status = 'active'
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return false;
        } catch (PDOException $exception) {
            error_log("Get user error: " . $exception->getMessage());
            return false;
        }
    }
    
    public function changePassword($user_id, $old_password, $new_password) {
        try {
            // Kiểm tra mật khẩu cũ
            $query = "SELECT password FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($old_password, $row['password'])) {
                    // Cập nhật mật khẩu mới
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $update_query = "UPDATE " . $this->table_name . " 
                                   SET password = ?, updated_at = CURRENT_TIMESTAMP 
                                   WHERE id = ?";
                    
                    $update_stmt = $this->conn->prepare($update_query);
                    $update_stmt->bindValue(1, $hashed_password, PDO::PARAM_STR);
                    $update_stmt->bindValue(2, $user_id, PDO::PARAM_INT);
                    
                    if ($update_stmt->execute()) {
                        $this->logUserActivity($user_id, 'PASSWORD_CHANGED', 'Password changed successfully');
                        return true;
                    }
                }
            }
            
            return false;
        } catch (PDOException $exception) {
            error_log("Change password error: " . $exception->getMessage());
            return false;
        }
    }
    
    private function updateLastLogin($user_id) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET updated_at = CURRENT_TIMESTAMP 
                     WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $exception) {
            error_log("Update last login error: " . $exception->getMessage());
        }
    }
    
    private function logUserActivity($user_id, $action, $description) {
        try {
            // Kiểm tra bảng audit_log có tồn tại không
            $check_table = "SHOW TABLES LIKE 'audit_log'";
            $check_stmt = $this->conn->prepare($check_table);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $query = "INSERT INTO audit_log (table_name, record_id, action, new_values, user_id, ip_address, user_agent) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindValue(1, 'users', PDO::PARAM_STR);
                $stmt->bindValue(2, $user_id, PDO::PARAM_INT);
                $stmt->bindValue(3, $action, PDO::PARAM_STR);
                $stmt->bindValue(4, json_encode(['description' => $description]), PDO::PARAM_STR);
                $stmt->bindValue(5, $user_id, PDO::PARAM_INT);
                $stmt->bindValue(6, $_SERVER['REMOTE_ADDR'] ?? 'unknown', PDO::PARAM_STR);
                $stmt->bindValue(7, $_SERVER['HTTP_USER_AGENT'] ?? 'unknown', PDO::PARAM_STR);
                $stmt->execute();
            }
        } catch (PDOException $exception) {
            error_log("Log user activity error: " . $exception->getMessage());
        }
    }
    
    public function validatePassword($password) {
        // Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số
        if (strlen($password) < 8) {
            return "Mật khẩu phải có ít nhất 8 ký tự";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            return "Mật khẩu phải có ít nhất một chữ cái viết hoa";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            return "Mật khẩu phải có ít nhất một chữ cái viết thường";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            return "Mật khẩu phải có ít nhất một chữ số";
        }
        
        return true;
    }
}
?>