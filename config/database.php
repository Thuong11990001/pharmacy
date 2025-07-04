<?php
// config/database.php

class Database {
    // Cấu hình database - THAY ĐỔI THEO THÔNG TIN CỦA BẠN
    private $host = "localhost";
    private $db_name = "improved_pharmacy";  // Tên database của bạn
    private $username = "root";        // Username MySQL của bạn
    private $password = "";            // Password MySQL của bạn (để trống nếu dùng XAMPP)
    private $charset = "utf8mb4";
    
    public $conn;

    // Kết nối database
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
            return null;
        }

        return $this->conn;
    }
    
    // Test connection
    public function testConnection() {
        $conn = $this->getConnection();
        if ($conn) {
            try {
                $stmt = $conn->query("SELECT 1");
                return true;
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }
}
?>