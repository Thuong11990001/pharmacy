-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th7 04, 2025 lúc 08:54 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `improved_pharmacy`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` int(11) NOT NULL,
  `action` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `audit_log`
--

INSERT INTO `audit_log` (`id`, `table_name`, `record_id`, `action`, `old_values`, `new_values`, `user_id`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 'users', 6, '', NULL, '{\"description\":\"User logged in successfully\"}', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-27 12:05:50'),
(2, 'users', 6, '', NULL, '{\"description\":\"User logged in successfully\"}', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-27 12:06:15'),
(3, 'users', 6, '', NULL, '{\"description\":\"User logged in successfully\"}', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-27 16:59:35'),
(4, 'users', 6, '', NULL, '{\"description\":\"User logged in successfully\"}', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-28 05:41:41'),
(5, 'users', 6, '', NULL, '{\"description\":\"User logged in successfully\"}', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-28 12:47:02'),
(6, 'users', 6, '', NULL, '{\"description\":\"User logged in successfully\"}', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-29 05:27:08'),
(7, 'users', 6, '', NULL, '{\"description\":\"User logged in successfully\"}', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-29 05:29:15'),
(8, 'users', 6, '', NULL, '{\"description\":\"User logged in successfully\"}', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-29 09:59:18'),
(9, 'users', 6, '', NULL, '{\"description\":\"User logged in successfully\"}', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-29 12:17:40'),
(10, 'users', 6, '', NULL, '{\"description\":\"User logged in successfully\"}', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-30 06:03:26'),
(11, 'users', 6, '', NULL, '{\"description\":\"User logged in successfully\"}', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-07-01 06:26:04'),
(12, 'users', 6, '', NULL, '{\"description\":\"User logged in successfully\"}', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-07-02 05:50:06'),
(13, 'users', 6, '', NULL, '{\"description\":\"User logged in successfully\"}', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-07-03 07:04:16'),
(14, 'users', 2, '', NULL, '{\"description\":\"User logged in successfully\"}', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-07-03 09:51:43'),
(15, 'users', 6, '', NULL, '{\"description\":\"User logged in successfully\"}', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-07-03 10:02:49'),
(16, 'users', 2, '', NULL, '{\"description\":\"User logged in successfully\"}', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-07-03 10:03:02'),
(17, 'users', 6, '', NULL, '{\"description\":\"User logged in successfully\"}', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-07-04 05:27:41'),
(18, 'users', 2, '', NULL, '{\"description\":\"User logged in successfully\"}', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-07-04 06:16:14'),
(19, 'users', 1, '', NULL, '{\"description\":\"User logged in successfully\"}', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-07-04 06:26:32'),
(20, 'users', 6, '', NULL, '{\"description\":\"User logged in successfully\"}', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-07-04 06:27:45'),
(21, 'users', 2, '', NULL, '{\"description\":\"User logged in successfully\"}', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-07-04 06:27:58'),
(22, 'users', 1, '', NULL, '{\"description\":\"User logged in successfully\"}', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-07-04 06:28:25'),
(23, 'users', 8, '', NULL, '{\"description\":\"Invalid password\"}', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-07-04 06:50:06'),
(24, 'users', 8, '', NULL, '{\"description\":\"User logged in successfully\"}', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-07-04 06:50:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_code` varchar(7) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `requires_prescription` tinyint(1) DEFAULT 0,
  `is_controlled` tinyint(1) DEFAULT 0,
  `parent_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `category_code`, `name`, `description`, `requires_prescription`, `is_controlled`, `parent_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'CAT0001', 'Over-the-Counter', 'Non-prescription medicines', 0, 0, NULL, 'active', '2025-06-25 12:01:12', '2025-06-25 12:01:12'),
(2, 'CAT0002', 'Prescription Only', 'Prescription required medicines', 1, 0, NULL, 'active', '2025-06-25 12:01:12', '2025-06-25 12:01:12'),
(3, 'CAT0003', 'Controlled Substances', 'Controlled/narcotic medicines', 1, 1, NULL, 'active', '2025-06-25 12:01:12', '2025-06-25 12:01:12'),
(4, 'CAT0004', 'Thuốc giảm đau', 'Thuốc giảm đau, hạ sốt', 0, 0, 1, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(5, 'CAT0005', 'Thuốc kháng sinh', 'Thuốc kháng sinh điều trị nhiễm khuẩn', 1, 0, 2, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(6, 'CAT0006', 'Thuốc tim mạch', 'Thuốc điều trị bệnh tim mạch', 1, 0, 2, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(7, 'CAT0007', 'Thuốc tiểu đường', 'Thuốc điều trị bệnh tiểu đường', 1, 0, 2, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(8, 'CAT0008', 'Thuốc an thần', 'Thuốc an thần, chống trầm cảm', 1, 1, 3, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(9, 'CAT0009', 'Thuốc dạ dày', 'Thuốc điều trị bệnh dạ dày', 0, 0, 1, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(10, 'CAT0010', 'Vitamin & Thực phẩm chức năng', 'Vitamin và thực phẩm bổ sung', 0, 0, 1, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(11, 'CAT0011', 'Dụng cụ Y tế', 'Dụng cụ y tế (găng tay, ống tiêm)', 0, 0, NULL, 'inactive', '2025-06-28 06:16:25', '2025-06-28 06:17:18'),
(13, 'CAT0012', 'Testing', 'testing', 0, 1, 6, 'active', '2025-06-28 13:21:20', '2025-07-03 09:59:48');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `controlled_drug_log`
--

CREATE TABLE `controlled_drug_log` (
  `id` int(11) NOT NULL,
  `log_code` varchar(10) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `doctor_name` varchar(255) NOT NULL,
  `doctor_license` varchar(100) DEFAULT NULL,
  `sold_by` int(11) NOT NULL,
  `supervisor_approved_by` int(11) DEFAULT NULL,
  `sold_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `compliance_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `controlled_drug_log`
--

INSERT INTO `controlled_drug_log` (`id`, `log_code`, `sale_id`, `medicine_id`, `batch_id`, `patient_id`, `prescription_id`, `quantity`, `unit_price`, `doctor_name`, `doctor_license`, `sold_by`, `supervisor_approved_by`, `sold_at`, `compliance_notes`) VALUES
(1, 'CDL0000001', 4, 11, 18, 4, 4, 30, 2500.00, 'BS. Phạm Thị Hạnh', 'BS456789', 3, 2, '2025-06-25 12:15:14', NULL),
(2, 'CDL0000002', 18, 12, 19, 7, 5, 14, 3200.00, 'BS. Hoàng Văn Nam', 'BS567890', 6, 2, '2025-06-30 06:30:34', NULL),
(3, 'CDL0000003', 19, 13, 20, 6, 8, 30, 4500.00, 'BS. Trần Hoài Thương', '1121515165', 6, 2, '2025-06-30 06:31:09', NULL),
(4, 'CDL0000004', 21, 12, 19, 2, 11, 5, 3200.00, 'BS. Trần Hoài Thương', '1121515165', 6, 2, '2025-07-04 05:31:22', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `medicine_code` varchar(7) NOT NULL,
  `name` varchar(255) NOT NULL,
  `generic_name` varchar(255) DEFAULT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `unit` varchar(50) NOT NULL,
  `strength` varchar(100) DEFAULT NULL,
  `dosage_form` varchar(100) DEFAULT NULL,
  `selling_price` decimal(12,2) NOT NULL,
  `min_stock_level` int(11) DEFAULT 0,
  `max_stock_level` int(11) DEFAULT 0,
  `is_controlled` tinyint(1) DEFAULT 0,
  `requires_prescription` tinyint(1) DEFAULT 0,
  `description` text DEFAULT NULL,
  `storage_conditions` text DEFAULT NULL,
  `contraindications` text DEFAULT NULL,
  `side_effects` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `default_supplier_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive','discontinued') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Đang đổ dữ liệu cho bảng `medicines`
--

INSERT INTO `medicines` (`id`, `medicine_code`, `name`, `generic_name`, `barcode`, `manufacturer`, `unit`, `strength`, `dosage_form`, `selling_price`, `min_stock_level`, `max_stock_level`, `is_controlled`, `requires_prescription`, `description`, `storage_conditions`, `contraindications`, `side_effects`, `category_id`, `default_supplier_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'MED0001', 'Panadol Extra', 'Paracetamol + Caffeine', '8935049003014', 'GlaxoSmithKline', 'viên', '500mg + 65mg', 'Viên nén', 2500.00, 200, 1000, 0, 0, 'Thuốc giảm đau, hạ sốt', 'Bảo quản nơi khô ráo, tránh ánh sáng', NULL, NULL, 4, 1, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(2, 'MED0002', 'Efferalgan', 'Paracetamol', '3400930301234', 'Sanofi', 'viên', '500mg', 'Viên sủi', 3200.00, 150, 800, 0, 0, 'Thuốc giảm đau, hạ sốt dạng sủi', 'Bảo quản nơi khô ráo', NULL, NULL, 4, 3, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(3, 'MED0003', 'Gaviscon', 'Sodium Alginate', '5012616987654', 'Reckitt Benckiser', 'gói', '10ml', 'Hỗn dịch uống', 15000.00, 100, 500, 0, 0, 'Thuốc điều trị trào ngược dạ dày', 'Bảo quản nhiệt độ phòng', NULL, NULL, 9, 1, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(4, 'MED0004', 'Vitamin C 1000mg', 'Ascorbic Acid', '8936036005012', 'Pymepharco', 'viên', '1000mg', 'Viên sủi', 1800.00, 300, 1500, 0, 0, 'Bổ sung Vitamin C', 'Bảo quản nơi khô ráo', NULL, NULL, 10, 5, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(5, 'MED0005', 'Lacteol Fort', 'Lactobacillus', '3400938756432', 'Sanofi', 'gói', '340mg', 'Bột uống', 12000.00, 80, 400, 0, 0, 'Men vi sinh điều trị tiêu chảy', 'Bảo quản tủ lạnh 2-8°C', NULL, NULL, 9, 3, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(6, 'MED0006', 'Augmentin 625mg', 'Amoxicillin + Clavulanic Acid', '8935001234567', 'GlaxoSmithKline', 'viên', '500mg + 125mg', 'Viên nén bao phim', 8500.00, 100, 600, 0, 1, 'Kháng sinh phổ rộng', 'Bảo quản nơi khô ráo, dưới 25°C', NULL, NULL, 5, 1, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(7, 'MED0007', 'Cefixime 200mg', 'Cefixime', '8936789012345', 'Hậu Giang Pharma', 'viên', '200mg', 'Viên nang', 12000.00, 80, 400, 0, 1, 'Kháng sinh Cephalosporin thế hệ 3', 'Bảo quản nơi khô ráo', NULL, NULL, 5, 2, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(8, 'MED0008', 'Amlodipine 5mg', 'Amlodipine Besylate', '8935049876543', 'Abbott', 'viên', '5mg', 'Viên nén', 1200.00, 200, 1000, 0, 1, 'Thuốc điều trị tăng huyết áp', 'Bảo quản dưới 30°C', NULL, NULL, 6, 4, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(9, 'MED0009', 'Metformin 500mg', 'Metformin HCl', '8936123456789', 'Pymepharco', 'viên', '500mg', 'Viên nén bao phim', 800.00, 300, 1500, 0, 1, 'Thuốc điều trị tiểu đường typ 2', 'Bảo quản nơi khô ráo', NULL, NULL, 7, 5, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(10, 'MED0010', 'Insulin Lantus', 'Insulin Glargine', '3400956789012', 'Sanofi', 'ống tiêm', '100 IU/ml', 'Dung dịch tiêm', 280000.00, 20, 100, 0, 1, 'Insulin tác dụng dài', 'Bảo quản tủ lạnh 2-8°C', NULL, NULL, 7, 3, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(11, 'MED0011', 'Alprazolam 0.25mg', 'Alprazolam', '8935012345678', 'Abbott', 'viên', '0.25mg', 'Viên nén', 2500.00, 50, 200, 1, 1, 'Thuốc an thần, chống lo âu', 'Bảo quản nơi khô ráo, tránh ánh sáng', NULL, NULL, 8, 4, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(12, 'MED0012', 'Tramadol 50mg', 'Tramadol HCl', '8936987654321', 'Hậu Giang Pharma', 'viên', '50mg', 'Viên nang', 3200.00, 30, 150, 1, 1, 'Thuốc giảm đau mạnh', 'Bảo quản dưới 25°C', NULL, NULL, 8, 2, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(13, 'MED0013', 'Codeine 30mg', 'Codeine Phosphate', '8935098765432', 'Pymepharco', 'viên', '30mg', 'Viên nén', 4500.00, 25, 100, 1, 1, 'Thuốc giảm đau, chống ho có đờm', 'Bảo quản nơi khô ráo', NULL, NULL, 8, 5, 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(14, 'MED0014', 'Phasphalugel', 'Phas', '377449', 'THTPharma', 'Hộp', '150mg', 'Dung dịch', 20000.00, 20, 50, 0, 0, 'Thuốc dạ day', 'Nơi khô ráo thoáng mát', 'Không', 'Không', 1, 2, 'active', '2025-06-28 13:23:19', '2025-06-28 13:23:19'),
(16, 'MED0015', 'Pseudopherin', 'Pseudo', '312213123', 'THTPharma', 'Hộp', '500mg', 'viên nén', 200000.00, 10, 100, 1, 0, 'adasdasdasd', 'dấdadass', 'adasdsadasd', 'dấdasdasd', 3, 4, 'active', '2025-07-03 10:10:05', '2025-07-03 10:10:05');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `medicine_batches`
--

CREATE TABLE `medicine_batches` (
  `id` int(11) NOT NULL,
  `batch_code` varchar(7) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `batch_number` varchar(255) NOT NULL,
  `manufacturing_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `import_date` date NOT NULL,
  `import_price` decimal(12,2) NOT NULL,
  `original_quantity` int(11) NOT NULL,
  `current_quantity` int(11) NOT NULL,
  `storage_location` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `status` enum('active','expired','recalled','depleted') DEFAULT 'active',
  `imported_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Đang đổ dữ liệu cho bảng `medicine_batches`
--

INSERT INTO `medicine_batches` (`id`, `batch_code`, `medicine_id`, `supplier_id`, `batch_number`, `manufacturing_date`, `expiry_date`, `import_date`, `import_price`, `original_quantity`, `current_quantity`, `storage_location`, `qr_code`, `status`, `imported_by`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'BAT0001', 1, 1, 'PN240101', '2024-01-15', '2026-01-15', '2024-02-01', 1800.00, 500, 320, 'Kệ A1', NULL, 'active', 2, NULL, '2025-06-25 12:09:37', '2025-06-25 12:15:14'),
(2, 'BAT0002', 1, 1, 'PN240201', '2024-02-15', '2026-02-15', '2024-03-01', 1750.00, 300, 246, 'Kệ A1', NULL, 'active', 2, NULL, '2025-06-25 12:09:37', '2025-07-02 06:09:38'),
(3, 'BAT0003', 2, 3, 'EF240301', '2024-03-01', '2026-03-01', '2024-03-15', 2400.00, 200, 142, 'Kệ A2', NULL, 'active', 3, NULL, '2025-06-25 12:09:37', '2025-06-25 12:15:14'),
(4, 'BAT0004', 2, 3, 'EF240401', '2024-04-01', '2026-04-01', '2024-04-15', 2350.00, 250, 200, 'Kệ A2', NULL, 'active', 3, NULL, '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(5, 'BAT0005', 3, 1, 'GV240201', '2024-02-01', '2025-08-01', '2024-02-20', 12000.00, 100, 73, 'Kệ B1', NULL, 'active', 2, NULL, '2025-06-25 12:09:37', '2025-07-04 05:31:22'),
(6, 'BAT0006', 3, 1, 'GV240501', '2024-05-01', '2025-11-01', '2024-05-20', 11800.00, 150, 118, 'Kệ B1', NULL, 'active', 2, NULL, '2025-06-25 12:09:37', '2025-06-25 12:15:14'),
(7, 'BAT0007', 4, 5, 'VC240301', '2024-03-01', '2026-03-01', '2024-03-20', 1200.00, 400, 295, 'Kệ C1', NULL, 'active', 1, NULL, '2025-06-25 12:09:37', '2025-06-25 12:15:14'),
(8, 'BAT0008', 4, 5, 'VC240601', '2024-06-01', '2026-06-01', '2024-06-20', 1150.00, 300, 251, 'Kệ C1', NULL, 'active', 1, NULL, '2025-06-25 12:09:37', '2025-06-25 12:15:14'),
(9, 'BAT0009', 5, 3, 'LF240401', '2024-04-01', '2025-10-01', '2024-04-25', 9000.00, 120, 89, 'Tủ lạnh A', NULL, 'active', 3, NULL, '2025-06-25 12:09:37', '2025-06-25 12:15:14'),
(10, 'BAT0010', 6, 1, 'AU240201', '2024-02-01', '2026-02-01', '2024-02-15', 6500.00, 200, 115, 'Kệ D1', NULL, 'active', 2, NULL, '2025-06-25 12:09:37', '2025-06-30 06:30:34'),
(11, 'BAT0011', 6, 1, 'AU240501', '2024-05-01', '2026-05-01', '2024-05-15', 6400.00, 180, 160, 'Kệ D1', NULL, 'active', 2, NULL, '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(12, 'BAT0012', 7, 2, 'CF240301', '2024-03-01', '2026-03-01', '2024-03-20', 9500.00, 100, 70, 'Kệ D2', NULL, 'active', 3, NULL, '2025-06-25 12:09:37', '2025-06-25 12:15:14'),
(13, 'BAT0013', 8, 4, 'AM240401', '2024-04-01', '2027-04-01', '2024-04-20', 900.00, 300, 250, 'Kệ E1', NULL, 'active', 1, NULL, '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(14, 'BAT0014', 8, 4, 'AM240601', '2024-06-01', '2027-06-01', '2024-06-20', 850.00, 250, 230, 'Kệ E1', NULL, 'active', 1, NULL, '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(15, 'BAT0015', 9, 5, 'MF240501', '2024-05-01', '2027-05-01', '2024-05-25', 600.00, 500, 355, 'Kệ E2', NULL, 'active', 2, NULL, '2025-06-25 12:09:37', '2025-06-30 06:31:09'),
(16, 'BAT0016', 10, 3, 'IN240301', '2024-03-01', '2025-09-01', '2024-03-15', 220000.00, 30, 25, 'Tủ lạnh B', NULL, 'active', 3, NULL, '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(17, 'BAT0017', 10, 3, 'IN240601', '2024-06-01', '2025-12-01', '2024-06-15', 215000.00, 25, 22, 'Tủ lạnh B', NULL, 'active', 3, NULL, '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(18, 'BAT0018', 11, 4, 'AL240401', '2024-04-01', '2026-04-01', '2024-04-20', 1800.00, 80, 30, 'Tủ khóa A', NULL, 'depleted', 2, NULL, '2025-06-25 12:09:37', '2025-06-25 12:15:14'),
(19, 'BAT0019', 12, 2, 'TR240501', '2024-05-01', '2026-05-01', '2024-05-20', 2400.00, 60, 26, 'Tủ khóa A', NULL, 'active', 3, NULL, '2025-06-25 12:09:37', '2025-07-04 05:31:22'),
(20, 'BAT0020', 13, 5, 'CD240601', '2024-06-01', '2026-06-01', '2024-06-20', 3200.00, 40, 0, 'Tủ khóa B', NULL, 'active', 2, NULL, '2025-06-25 12:09:37', '2025-06-30 06:31:09'),
(21, 'BAT0021', 14, 2, 'PH280625', '2025-06-10', '2026-10-02', '2025-06-28', 15000.00, 40, 38, 'Kệ A1', 'QRRRRRRR', 'active', 6, 'Testing', '2025-06-28 13:31:33', '2025-07-02 06:09:38'),
(22, 'BAT0022', 11, 2, '1', '2025-01-29', '2027-06-16', '2025-07-03', 2000000.00, 200, 200, 'Kệ A1', 'QRRRRRRR', 'active', 2, '', '2025-07-03 10:06:23', '2025-07-03 10:06:23'),
(23, 'BAT0023', 16, 4, 'PS37205', '2025-07-02', '2026-07-03', '2025-07-03', 190000.00, 80, 80, 'Két sắt', 'qeqweqwe2313', 'active', 2, '', '2025-07-03 10:11:19', '2025-07-03 10:11:19');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `patient_code` varchar(7) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `id_number` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `emergency_contact` varchar(255) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `medical_notes` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `patients`
--

INSERT INTO `patients` (`id`, `patient_code`, `full_name`, `id_number`, `phone`, `email`, `address`, `date_of_birth`, `gender`, `emergency_contact`, `allergies`, `medical_notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 'PAT0001', 'Nguyễn Văn Nam', '123456789012', '0912345678', 'nam.nguyen@email.com', '123 Đường ABC, Quận 1, TP.HCM', '1985-03-15', 'male', 'Nguyễn Thị Lan - 0912345679', 'Dị ứng Penicillin', 'Tiền sử cao huyết áp', 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(2, 'PAT0002', 'Trần Thị Hương', '234567890123', '0923456789', 'huong.tran@email.com', '456 Đường DEF, Quận 3, TP.HCM', '1992-07-22', 'female', 'Trần Văn Minh - 0923456790', 'Không có', 'Đang mang thai tháng thứ 6', 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(3, 'PAT0003', 'Lê Minh Tuấn', '345678901234', '0934567890', 'tuan.le@email.com', '789 Đường GHI, Quận 7, TP.HCM', '1978-11-08', 'male', 'Lê Thị Mai - 0934567891', 'Dị ứng Aspirin', 'Bệnh tiểu đường typ 2', 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(4, 'PAT0004', 'Phạm Thị Lan', '456789012345', '0945678901', 'lan.pham@email.com', '321 Đường JKL, Quận 5, TP.HCM', '1988-12-12', 'female', 'Phạm Văn Long - 0945678902', 'Dị ứng hải sản', 'Rối loạn lo âu', 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(5, 'PAT0005', 'Võ Thanh Hải', '567890123456', '0956789012', 'hai.vo@email.com', '654 Đường MNO, Quận 10, TP.HCM', '1995-05-30', 'male', 'Võ Thị Nga - 0956789013', 'Không có', 'Khỏe mạnh', 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(6, 'PAT0006', 'Đỗ Thị Minh', '678901234567', '0967890123', 'minh.do@email.com', '987 Đường PQR, Quận Bình Thạnh, TP.HCM', '1982-09-18', 'female', 'Đỗ Văn Kiên - 0967890124', 'Dị ứng thuốc nhuộm', 'Bệnh dạ dày', 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(7, 'PAT0007', 'Hoàng Văn Đức', '789012345678', '0978901234', 'duc.hoang@email.com', '147 Đường STU, Quận Tân Bình, TP.HCM', '1975-01-25', 'male', 'Hoàng Thị Yến - 0978901235', 'Dị ứng bụi', 'Bệnh phổi tắc nghẽn mạn tính', 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(8, 'PAT0008', 'Bùi Thị Thu', '890123456789', '0989012345', 'thu.bui@email.com', '258 Đường VWX, Quận Phú Nhuận, TP.HCM', '1990-04-07', 'female', 'Bùi Văn Tâm - 0989012346', 'Paracetamol', 'Đang điều trị trầm cảm nhẹ', 'active', '2025-06-25 12:09:37', '2025-07-03 07:11:54'),
(9, 'PAT0009', 'Đinh Văn Hùng', '901234567890', '0990123456', 'hung.dinh@email.com', '369 Đường YZ, Quận Gò Vấp, TP.HCM', '1983-08-14', 'male', 'Đinh Thị Xuân - 0990123457', 'Dị ứng Ibuprofen', 'Viêm khớp mãn tính', 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(10, 'PAT0010', 'Lý Thị Oanh', '012345678901', '0901234567', 'oanh.ly@email.com', '741 Đường ABC, Quận 12, TP.HCM', '1987-06-03', 'female', 'Lý Văn Phúc - 0901234568', 'Dị ứng latex', 'Tim mạch bình thường', 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(13, 'PAT0011', 'thuong tran', '0111222544', '0362117720', 'thuongpmpt@gmail.com', 'Can Tho', '1999-02-03', 'male', '4423434324324', 'đasda', 'dấdsd', 'active', '2025-07-03 07:18:26', '2025-07-03 07:18:26');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `prescription_code` varchar(20) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_name` varchar(255) NOT NULL,
  `doctor_license` varchar(100) DEFAULT NULL,
  `hospital_clinic` varchar(255) DEFAULT NULL,
  `prescription_date` date NOT NULL,
  `diagnosis` text DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `status` enum('pending','partial','completed','cancelled','expired') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `prescription_code`, `patient_id`, `doctor_name`, `doctor_license`, `hospital_clinic`, `prescription_date`, `diagnosis`, `total_amount`, `status`, `notes`, `processed_by`, `processed_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'PRES000002', 1, 'BS. Nguyễn Văn Khoa', 'BS123456', 'Bệnh viện Chợ Rẫy', '2024-06-20', 'Nhiễm khuẩn đường hô hấp trên', 0.00, 'completed', 'Uống sau ăn', 6, NULL, NULL, '2025-06-25 12:09:37', '2025-07-01 06:59:38'),
(2, 'PRES000005', 2, 'BS. Trần Thị Linh', 'BS234567', 'Bệnh viện Từ Dũ', '2024-06-21', 'Viêm họng cấp', 0.00, 'completed', 'Uống đủ liều', 6, NULL, NULL, '2025-06-25 12:09:37', '2025-07-01 07:00:02'),
(3, 'PRES000004', 3, 'BS. Lê Minh Đức', 'BS345678', 'Bệnh viện Thống Nhất', '2024-06-22', 'Tiểu đường typ 2', 0.00, 'completed', 'Kiểm soát đường huyết', 6, NULL, NULL, '2025-06-25 12:09:37', '2025-07-01 06:59:55'),
(4, 'RX240004', 4, 'BS. Phạm Thị Hạnh', 'BS456789', 'Phòng khám Tâm lý ABC', '2024-06-23', 'Rối loạn lo âu', 0.00, 'completed', 'Theo dõi tâm trạng', 3, NULL, NULL, '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(5, 'PRES000006', 7, 'BS. Hoàng Văn Nam', 'BS567890', 'Bệnh viện Phổi Trung ương', '2024-06-24', 'Viêm phế quản mãn tính', 0.00, 'completed', 'Cần theo dõi sát', 6, NULL, NULL, '2025-06-25 12:09:37', '2025-07-01 07:00:15'),
(6, 'PRES000007', 8, 'BS. Bùi Thị Mai', 'BS678901', 'Bệnh viện Tâm thần TP.HCM', '2024-06-25', 'Trầm cảm nhẹ', 0.00, 'completed', 'Cần tư vấn tâm lý', 6, NULL, NULL, '2025-06-25 12:09:37', '2025-07-01 07:00:32'),
(8, 'PRES000001', 6, 'BS. Trần Hoài Thương', '1121515165', 'Phòng khám chui', '2025-06-29', 'Tâm thần nặng', 0.00, 'completed', '', 6, NULL, NULL, '2025-06-29 05:58:21', '2025-07-01 06:59:26'),
(9, 'PRES000003', 10, 'BS. Trần Hoài Thương', '1121515165', 'Phòng khám chui', '2025-06-29', 'Đau đầu', 16000.00, 'completed', 'ddd', 6, NULL, NULL, '2025-06-29 06:10:50', '2025-07-01 08:12:48'),
(10, 'PRES000008', 7, 'BS. Trần Hoài Thương', '1121515165', 'Phòng khám chui', '2025-07-01', 'ffff', 12500.00, 'completed', 'fffff', 6, NULL, NULL, '2025-07-01 08:06:48', '2025-07-01 08:06:48'),
(11, 'PRES000009', 2, 'BS. Trần Hoài Thương', '1121515165', 'Phòng khám chui', '2025-07-04', 'dâdad', 91000.00, 'completed', 'dâdadad', 6, NULL, NULL, '2025-07-04 05:30:47', '2025-07-04 05:32:16');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `prescription_details`
--

CREATE TABLE `prescription_details` (
  `id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity_prescribed` int(11) NOT NULL,
  `quantity_dispensed` int(11) DEFAULT 0,
  `dosage_instructions` text DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `duration_days` int(11) DEFAULT NULL,
  `unit_price` decimal(12,2) DEFAULT NULL,
  `total_price` decimal(12,2) DEFAULT NULL,
  `status` enum('pending','partial','completed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Đang đổ dữ liệu cho bảng `prescription_details`
--

INSERT INTO `prescription_details` (`id`, `prescription_id`, `medicine_id`, `quantity_prescribed`, `quantity_dispensed`, `dosage_instructions`, `frequency`, `duration_days`, `unit_price`, `total_price`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 6, 14, 14, 'Uống 1 viên mỗi 12 giờ', '2 lần/ngày', 7, 8500.00, 119000.00, 'completed', NULL, '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(2, 1, 1, 20, 20, 'Uống khi sốt, tối đa 4 viên/ngày', 'Khi cần', 5, 2500.00, 50000.00, 'completed', NULL, '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(3, 2, 7, 10, 10, 'Uống 1 viên mỗi 12 giờ', '2 lần/ngày', 5, 12000.00, 120000.00, 'completed', NULL, '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(6, 4, 11, 30, 30, 'Uống 1 viên trước khi đi ngủ', '1 lần/ngày', 30, 2500.00, 75000.00, 'completed', NULL, '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(7, 5, 6, 21, 0, 'Uống 1 viên mỗi 8 giờ', '3 lần/ngày', 7, 8500.00, 178500.00, 'pending', NULL, '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(8, 5, 12, 14, 0, 'Uống 1 viên khi đau, tối đa 4 viên/ngày', 'Khi cần', 7, 3200.00, 44800.00, 'pending', NULL, '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(9, 6, 11, 15, 0, 'Uống 1/2 viên sáng và tối', '2 lần/ngày', 30, 2500.00, 37500.00, 'pending', NULL, '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(13, 8, 13, 30, 0, '1 viên/ buổi', NULL, NULL, NULL, NULL, 'pending', 'Uống sau ăn', '2025-06-30 06:43:53', '2025-06-30 06:43:53'),
(14, 8, 9, 15, 0, '1 viên / ngày', NULL, NULL, NULL, NULL, 'pending', 'Uống sau ăn', '2025-06-30 06:43:53', '2025-06-30 06:43:53'),
(15, 3, 9, 60, 0, 'Uống 1 viên mỗi 12 giờ sau ăn', NULL, NULL, NULL, NULL, 'pending', '', '2025-06-30 06:44:23', '2025-06-30 06:44:23'),
(16, 3, 10, 5, 0, 'Tiêm dưới da mỗi tối', NULL, NULL, NULL, NULL, 'pending', '', '2025-06-30 06:44:23', '2025-06-30 06:44:23'),
(17, 10, 1, 5, 5, 'Uống 1 viên trước khi đi ngủ', '1 lần/ngày', 5, 2500.00, 12500.00, 'completed', NULL, '2025-07-01 08:06:48', '2025-07-01 08:06:48'),
(19, 9, 2, 5, 5, '1 viên/ buổi', '1 lần/ngày', 5, 3200.00, 16000.00, 'completed', NULL, '2025-07-01 08:12:48', '2025-07-01 08:12:48'),
(22, 11, 3, 5, 0, '1 viên/ buổi', '1 lần/ngày', 5, 15000.00, 75000.00, 'pending', NULL, '2025-07-04 05:32:16', '2025-07-04 05:32:16'),
(23, 11, 12, 5, 0, 'Uống 1 viên trước khi đi ngủ', '1 lần/ngày', 5, 3200.00, 16000.00, 'pending', NULL, '2025-07-04 05:32:16', '2025-07-04 05:32:16');

--
-- Bẫy `prescription_details`
--
DELIMITER $$
CREATE TRIGGER `tr_update_prescription_status` AFTER UPDATE ON `prescription_details` FOR EACH ROW BEGIN
    DECLARE total_items INT;
    DECLARE completed_items INT;
    
    SELECT COUNT(*), SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END)
    INTO total_items, completed_items
    FROM prescription_details 
    WHERE prescription_id = NEW.prescription_id;
    
    UPDATE prescriptions 
    SET status = CASE 
        WHEN completed_items = 0 THEN 'pending'
        WHEN completed_items < total_items THEN 'partial'
        ELSE 'completed'
    END,
    processed_at = CASE WHEN completed_items = total_items THEN NOW() ELSE processed_at END
    WHERE id = NEW.prescription_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `sale_code` varchar(10) NOT NULL,
  `sale_date` date NOT NULL,
  `sale_time` time NOT NULL,
  `prescription_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','card','bank_transfer','insurance','mixed') DEFAULT 'cash',
  `payment_status` enum('pending','paid','partial','refunded') DEFAULT 'pending',
  `status` enum('draft','completed','cancelled','returned') DEFAULT 'draft',
  `employee_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Đang đổ dữ liệu cho bảng `sales`
--

INSERT INTO `sales` (`id`, `sale_code`, `sale_date`, `sale_time`, `prescription_id`, `patient_id`, `customer_name`, `customer_phone`, `subtotal`, `discount_amount`, `tax_amount`, `total_amount`, `payment_method`, `payment_status`, `status`, `employee_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'SA24060001', '2024-06-20', '14:30:00', 1, 1, NULL, NULL, 169000.00, 0.00, 0.00, 169000.00, 'cash', 'paid', 'completed', 2, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(2, 'SA24060002', '2024-06-21', '09:15:00', 2, 2, NULL, NULL, 120000.00, 0.00, 0.00, 120000.00, 'card', 'paid', 'completed', 3, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(3, 'SA24060003', '2024-06-22', '11:45:00', 3, 3, NULL, NULL, 24000.00, 0.00, 0.00, 24000.00, 'cash', 'paid', 'completed', 2, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(4, 'SA24060004', '2024-06-23', '16:20:00', 4, 4, NULL, NULL, 75000.00, 0.00, 0.00, 75000.00, 'cash', 'paid', 'completed', 3, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(5, 'SA24060005', '2024-06-24', '10:30:00', NULL, NULL, 'Nguyễn Thị Hạnh', '0987654321', 50000.00, 2500.00, 0.00, 47500.00, 'cash', 'paid', 'completed', 4, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(6, 'SA24060006', '2024-06-24', '14:15:00', NULL, NULL, 'Trần Văn Bình', '0976543210', 36000.00, 0.00, 0.00, 36000.00, 'cash', 'paid', 'completed', 4, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(7, 'SA24060007', '2024-06-25', '08:45:00', NULL, NULL, 'Lê Thị Cúc', '0965432109', 27200.00, 1200.00, 0.00, 26000.00, 'card', 'paid', 'completed', 5, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(8, 'SA24060008', '2024-06-25', '13:20:00', NULL, NULL, 'Phạm Văn Dũng', '0954321098', 84000.00, 4000.00, 0.00, 80000.00, 'cash', 'paid', 'completed', 5, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(9, 'SA24060009', '2024-06-25', '15:45:00', NULL, NULL, 'Hoàng Thị Mai', '0943210987', 15000.00, 0.00, 0.00, 15000.00, 'card', 'paid', 'completed', 4, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(10, 'SA24060010', '2024-06-25', '17:30:00', NULL, NULL, 'Đặng Văn Hùng', '0932109876', 96000.00, 0.00, 0.00, 96000.00, 'cash', 'paid', 'completed', 5, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(17, 'SALE000001', '2025-06-29', '20:06:00', NULL, NULL, 'Trần Hoài Thương', '123456', 12500.00, 0.00, 0.00, 12500.00, 'cash', 'paid', 'completed', 6, '', '2025-06-29 13:07:23', '2025-06-29 13:07:46'),
(18, 'SALE000002', '2025-06-30', '13:30:00', 5, 7, '', '', 223300.00, 0.00, 0.00, 223300.00, 'cash', 'paid', 'completed', 6, '', '2025-06-30 06:30:34', '2025-06-30 06:30:34'),
(19, 'SALE000003', '2025-06-30', '13:30:00', 8, 6, '', '', 147000.00, 0.00, 0.00, 147000.00, 'cash', 'pending', 'draft', 6, '', '2025-06-30 06:31:09', '2025-06-30 06:31:09'),
(20, 'SALE000004', '2025-07-02', '13:08:00', NULL, NULL, 'Trần Hoài Thương', '123456', 50000.00, 0.00, 0.00, 50000.00, 'cash', 'paid', 'completed', 6, '', '2025-07-02 06:09:38', '2025-07-02 06:09:38'),
(21, 'SALE000005', '2025-07-04', '12:31:00', 11, 2, '', '', 91000.00, 0.00, 0.00, 91000.00, 'cash', 'paid', 'completed', 6, '', '2025-07-04 05:31:22', '2025-07-04 05:31:22');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sale_details`
--

CREATE TABLE `sale_details` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `cost_price` decimal(12,2) NOT NULL,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `total_price` decimal(12,2) NOT NULL,
  `profit_amount` decimal(12,2) GENERATED ALWAYS AS (`total_price` - `cost_price` * `quantity`) STORED,
  `prescription_detail_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Đang đổ dữ liệu cho bảng `sale_details`
--

INSERT INTO `sale_details` (`id`, `sale_id`, `medicine_id`, `batch_id`, `quantity`, `unit_price`, `cost_price`, `discount_amount`, `total_price`, `prescription_detail_id`, `created_at`, `updated_at`) VALUES
(1, 1, 6, 10, 14, 8500.00, 6500.00, 0.00, 119000.00, 1, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(2, 1, 1, 1, 20, 2500.00, 1800.00, 0.00, 50000.00, 2, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(3, 2, 7, 12, 10, 12000.00, 9500.00, 0.00, 120000.00, 3, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(4, 3, 9, 15, 30, 800.00, 600.00, 0.00, 24000.00, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(5, 4, 11, 18, 30, 2500.00, 1800.00, 0.00, 75000.00, 7, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(6, 5, 1, 1, 10, 2500.00, 1800.00, 1250.00, 23750.00, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(7, 5, 4, 7, 5, 1800.00, 1200.00, 450.00, 8550.00, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(8, 5, 3, 5, 1, 15000.00, 12000.00, 750.00, 14250.00, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(9, 6, 4, 7, 20, 1800.00, 1200.00, 0.00, 36000.00, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(10, 7, 2, 3, 8, 3200.00, 2400.00, 1200.00, 24400.00, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(11, 7, 5, 9, 1, 12000.00, 9000.00, 0.00, 12000.00, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(12, 8, 1, 2, 20, 2500.00, 1750.00, 2500.00, 47500.00, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(13, 8, 3, 6, 2, 15000.00, 11800.00, 1500.00, 28500.00, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(14, 8, 4, 8, 5, 1800.00, 1150.00, 0.00, 9000.00, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(15, 9, 3, 5, 1, 15000.00, 12000.00, 0.00, 15000.00, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(16, 10, 4, 7, 30, 1800.00, 1200.00, 0.00, 54000.00, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(17, 10, 4, 8, 24, 1800.00, 1150.00, 0.00, 43200.00, NULL, '2025-06-25 12:15:14', '2025-06-25 12:15:14'),
(22, 17, 1, 2, 5, 2500.00, 1750.00, 0.00, 12500.00, NULL, '2025-06-29 13:07:46', '2025-06-29 13:07:46'),
(23, 18, 6, 10, 21, 8500.00, 6500.00, 0.00, 178500.00, 7, '2025-06-30 06:30:34', '2025-06-30 06:30:34'),
(24, 18, 12, 19, 14, 3200.00, 2400.00, 0.00, 44800.00, 8, '2025-06-30 06:30:34', '2025-06-30 06:30:34'),
(25, 19, 13, 20, 30, 4500.00, 3200.00, 0.00, 135000.00, NULL, '2025-06-30 06:31:09', '2025-06-30 06:31:09'),
(26, 19, 9, 15, 15, 800.00, 600.00, 0.00, 12000.00, NULL, '2025-06-30 06:31:09', '2025-06-30 06:31:09'),
(27, 20, 14, 21, 2, 20000.00, 15000.00, 0.00, 40000.00, NULL, '2025-07-02 06:09:38', '2025-07-02 06:09:38'),
(28, 20, 1, 2, 4, 2500.00, 1750.00, 0.00, 10000.00, NULL, '2025-07-02 06:09:38', '2025-07-02 06:09:38'),
(29, 21, 3, 5, 5, 15000.00, 12000.00, 0.00, 75000.00, NULL, '2025-07-04 05:31:22', '2025-07-04 05:31:22'),
(30, 21, 12, 19, 5, 3200.00, 2400.00, 0.00, 16000.00, NULL, '2025-07-04 05:31:22', '2025-07-04 05:31:22');

--
-- Bẫy `sale_details`
--
DELIMITER $$
CREATE TRIGGER `tr_log_controlled_drug_sale` AFTER INSERT ON `sale_details` FOR EACH ROW BEGIN
    DECLARE is_controlled BOOLEAN DEFAULT FALSE;
    DECLARE patient_id_val INT;
    DECLARE prescription_id_val INT;
    
    -- Check if medicine is controlled
    SELECT m.is_controlled INTO is_controlled
    FROM medicines m 
    WHERE m.id = NEW.medicine_id;
    
    IF is_controlled THEN
        -- Get patient and prescription info
        SELECT s.patient_id, s.prescription_id 
        INTO patient_id_val, prescription_id_val
        FROM sales s 
        WHERE s.id = NEW.sale_id;
        
        -- Insert into controlled drug log
        INSERT INTO controlled_drug_log (
            log_code, sale_id, medicine_id, batch_id, patient_id, 
            prescription_id, quantity, unit_price, doctor_name, 
            doctor_license, sold_by
        )
        SELECT 
            CONCAT('CDL', LPAD(COALESCE(MAX(CAST(SUBSTRING(log_code, 4) AS UNSIGNED)), 0) + 1, 7, '0')),
            NEW.sale_id,
            NEW.medicine_id,
            NEW.batch_id,
            patient_id_val,
            prescription_id_val,
            NEW.quantity,
            NEW.unit_price,
            pr.doctor_name,
            pr.doctor_license,
            s.employee_id
        FROM sales s
        JOIN prescriptions pr ON s.prescription_id = pr.id
        LEFT JOIN controlled_drug_log cdl ON 1=1
        WHERE s.id = NEW.sale_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_update_stock_on_sale` AFTER INSERT ON `sale_details` FOR EACH ROW BEGIN
    -- Log stock movement
    INSERT INTO stock_movements (
        movement_code, batch_id, movement_type, quantity, 
        remaining_quantity, reference_id, reference_type, 
        cost_price, unit_price, performed_by, performed_at
    )
    SELECT 
        CONCAT('SM', LPAD(COALESCE(MAX(CAST(SUBSTRING(movement_code, 3) AS UNSIGNED)), 0) + 1, 5, '0')),
        NEW.batch_id,
        'sale',
        NEW.quantity,
        (SELECT current_quantity FROM medicine_batches WHERE id = NEW.batch_id),
        NEW.sale_id,
        'sale',
        NEW.cost_price,
        NEW.unit_price,
        (SELECT employee_id FROM sales WHERE id = NEW.sale_id),
        NOW()
    FROM medicine_batches mb
    LEFT JOIN stock_movements sm ON 1=1
    WHERE mb.id = NEW.batch_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `movement_code` varchar(7) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `movement_type` enum('import','sale','adjustment','expiry','return','transfer') NOT NULL,
  `quantity` int(11) NOT NULL,
  `remaining_quantity` int(11) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `cost_price` decimal(12,2) DEFAULT NULL,
  `unit_price` decimal(12,2) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `movement_code`, `batch_id`, `movement_type`, `quantity`, `remaining_quantity`, `reference_id`, `reference_type`, `cost_price`, `unit_price`, `reason`, `performed_by`, `performed_at`) VALUES
(1, 'SM00001', 10, 'sale', 14, 136, 1, 'sale', 6500.00, 8500.00, NULL, 2, '2025-06-25 12:15:14'),
(2, 'SM00002', 1, 'sale', 20, 330, 1, 'sale', 1800.00, 2500.00, NULL, 2, '2025-06-25 12:15:14'),
(3, 'SM00003', 12, 'sale', 10, 70, 2, 'sale', 9500.00, 12000.00, NULL, 3, '2025-06-25 12:15:14'),
(4, 'SM00004', 15, 'sale', 30, 370, 3, 'sale', 600.00, 800.00, NULL, 2, '2025-06-25 12:15:14'),
(5, 'SM00005', 18, 'sale', 30, 30, 4, 'sale', 1800.00, 2500.00, NULL, 3, '2025-06-25 12:15:14'),
(6, 'SM00006', 1, 'sale', 10, 320, 5, 'sale', 1800.00, 2500.00, NULL, 4, '2025-06-25 12:15:14'),
(7, 'SM00007', 7, 'sale', 5, 345, 5, 'sale', 1200.00, 1800.00, NULL, 4, '2025-06-25 12:15:14'),
(8, 'SM00008', 5, 'sale', 1, 79, 5, 'sale', 12000.00, 15000.00, NULL, 4, '2025-06-25 12:15:14'),
(9, 'SM00009', 7, 'sale', 20, 325, 6, 'sale', 1200.00, 1800.00, NULL, 4, '2025-06-25 12:15:14'),
(10, 'SM00010', 3, 'sale', 8, 142, 7, 'sale', 2400.00, 3200.00, NULL, 5, '2025-06-25 12:15:14'),
(11, 'SM00011', 9, 'sale', 1, 89, 7, 'sale', 9000.00, 12000.00, NULL, 5, '2025-06-25 12:15:14'),
(12, 'SM00012', 2, 'sale', 20, 260, 8, 'sale', 1750.00, 2500.00, NULL, 5, '2025-06-25 12:15:14'),
(13, 'SM00013', 6, 'sale', 2, 118, 8, 'sale', 11800.00, 15000.00, NULL, 5, '2025-06-25 12:15:14'),
(14, 'SM00014', 8, 'sale', 5, 275, 8, 'sale', 1150.00, 1800.00, NULL, 5, '2025-06-25 12:15:14'),
(15, 'SM00015', 5, 'sale', 1, 78, 9, 'sale', 12000.00, 15000.00, NULL, 4, '2025-06-25 12:15:14'),
(16, 'SM00016', 7, 'sale', 30, 295, 10, 'sale', 1200.00, 1800.00, NULL, 5, '2025-06-25 12:15:14'),
(17, 'SM00017', 8, 'sale', 24, 251, 10, 'sale', 1150.00, 1800.00, NULL, 5, '2025-06-25 12:15:14'),
(19, 'SM00018', 2, 'sale', 5, 260, 17, 'sale', 1750.00, 2500.00, NULL, 6, '2025-06-29 13:07:23'),
(20, 'SM00019', 2, 'sale', 5, 255, 17, 'sale', 1750.00, 2500.00, NULL, 6, '2025-06-29 13:07:46'),
(21, 'SM00020', 10, 'sale', 21, 136, 18, 'sale', 6500.00, 8500.00, NULL, 6, '2025-06-30 06:30:34'),
(22, 'SM00021', 19, 'sale', 14, 45, 18, 'sale', 2400.00, 3200.00, NULL, 6, '2025-06-30 06:30:34'),
(23, 'SM00022', 20, 'sale', 30, 30, 19, 'sale', 3200.00, 4500.00, NULL, 6, '2025-06-30 06:31:09'),
(24, 'SM00023', 15, 'sale', 15, 370, 19, 'sale', 600.00, 800.00, NULL, 6, '2025-06-30 06:31:09'),
(25, 'SM00024', 21, 'sale', 2, 40, 20, 'sale', 15000.00, 20000.00, NULL, 6, '2025-07-02 06:09:38'),
(26, 'SM00025', 2, 'sale', 4, 250, 20, 'sale', 1750.00, 2500.00, NULL, 6, '2025-07-02 06:09:38'),
(27, 'SM00026', 5, 'sale', 5, 78, 21, 'sale', 12000.00, 15000.00, NULL, 6, '2025-07-04 05:31:22'),
(28, 'SM00027', 19, 'sale', 5, 31, 21, 'sale', 2400.00, 3200.00, NULL, 6, '2025-07-04 05:31:22');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `supplier_code` varchar(7) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tax_code` varchar(50) DEFAULT NULL,
  `payment_terms` text DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `suppliers`
--

INSERT INTO `suppliers` (`id`, `supplier_code`, `name`, `contact_person`, `phone`, `email`, `address`, `tax_code`, `payment_terms`, `status`, `created_at`, `updated_at`) VALUES
(1, 'SUP0001', 'Công ty TNHH Dược phẩm Hà Tây', 'Nguyễn Văn A', '0241234567', 'info@hatay-pharma.com', '123 Đường Láng, Ba Đình, Hà Nội', '0123456789', 'Net 30', 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(2, 'SUP0002', 'Công ty Cổ phần Dược Hậu Giang', 'Lê Thị B', '0292345678', 'sales@dhg.com.vn', '288 Bis Nguyễn Văn Cừ, Cần Thơ', '0234567890', 'Net 15', 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(3, 'SUP0003', 'Sanofi Vietnam', 'John Smith', '0283456789', 'vietnam@sanofi.com', 'Tầng 8, Tòa nhà Mapletree, TP.HCM', '0345678901', 'Net 45', 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(4, 'SUP0004', 'Abbott Laboratories Vietnam', 'Mary Johnson', '0284567890', 'abbott.vietnam@abbott.com', 'Tầng 10, Lotte Center, TP.HCM', '0456789012', 'Net 30', 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37'),
(5, 'SUP0005', 'Công ty TNHH Pymepharco', 'Trần Văn C', '0285678901', 'info@pymepharco.com', '449 Hoàng Văn Thụ, Tân Bình, TP.HCM', '0567890123', 'Net 20', 'active', '2025-06-25 12:09:37', '2025-06-25 12:09:37');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_code` varchar(7) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','pharmacist','cashier','manager') NOT NULL,
  `can_sell_controlled` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `user_code`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `can_sell_controlled`, `status`, `created_at`, `updated_at`) VALUES
(1, 'USR0001', 'admin', '$2y$10$ToP1kL9i5Fzn6ozRUF2ZIugWGyWItKeAvL6II4hWGHnp.rZURBEam', 'System Administrator', NULL, NULL, 'admin', 1, 'active', '2025-06-25 12:01:12', '2025-07-04 06:28:25'),
(2, 'USR0002', 'pharmacist1', '$2y$10$ToP1kL9i5Fzn6ozRUF2ZIugWGyWItKeAvL6II4hWGHnp.rZURBEam', 'Dr. Nguyễn Thị Mai', 'mai.nguyen@pharmacy.com', '0901234567', 'pharmacist', 1, 'active', '2025-06-25 12:09:37', '2025-07-04 06:27:58'),
(3, 'USR0003', 'pharmacist2', '$2y$10$ToP1kL9i5Fzn6ozRUF2ZIugWGyWItKeAvL6II4hWGHnp.rZURBEam', 'Dr. Trần Văn Hùng', 'hung.tran@pharmacy.com', '0901234568', 'pharmacist', 1, 'active', '2025-06-25 12:09:37', '2025-06-27 06:17:09'),
(4, 'USR0004', 'cashier1', '$2y$10$ToP1kL9i5Fzn6ozRUF2ZIugWGyWItKeAvL6II4hWGHnp.rZURBEam', 'Lê Thị Hoa', 'hoa.le@pharmacy.com', '0901234569', 'cashier', 0, 'active', '2025-06-25 12:09:37', '2025-06-27 06:17:09'),
(5, 'USR0005', 'cashier2', '$2y$10$ToP1kL9i5Fzn6ozRUF2ZIugWGyWItKeAvL6II4hWGHnp.rZURBEam', 'Phạm Minh Tuấn', 'tuan.pham@pharmacy.com', '0901234570', 'cashier', 0, 'active', '2025-06-25 12:09:37', '2025-06-27 06:17:09'),
(6, 'USR0006', 'manager1', '$2y$10$ToP1kL9i5Fzn6ozRUF2ZIugWGyWItKeAvL6II4hWGHnp.rZURBEam', 'Võ Thị Lan', 'lan.vo@pharmacy.com', '0901234571', 'manager', 1, 'active', '2025-06-25 12:09:37', '2025-07-04 06:27:45'),
(8, 'USR0007', 'thuong1100', '$2y$10$qJsvq7HMVVvO4Po85CDrPuuHYgRTzf9v8R20zzBxLz2kPU/qC7JPS', 'thuong tran', 'thuongpmpt@gmail.com', '0362117720', 'pharmacist', 1, 'active', '2025-07-04 06:49:56', '2025-07-04 06:50:23');

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_current_stock`
-- (See below for the actual view)
--
CREATE TABLE `v_current_stock` (
`medicine_id` int(11)
,`medicine_code` varchar(7)
,`medicine_name` varchar(255)
,`unit` varchar(50)
,`selling_price` decimal(12,2)
,`min_stock_level` int(11)
,`max_stock_level` int(11)
,`total_quantity` decimal(32,0)
,`active_batches` bigint(21)
,`nearest_expiry` date
,`avg_cost_price` decimal(16,6)
,`stock_status` varchar(12)
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_prescription_status`
-- (See below for the actual view)
--
CREATE TABLE `v_prescription_status` (
`id` int(11)
,`prescription_code` varchar(20)
,`prescription_date` date
,`patient_name` varchar(255)
,`doctor_name` varchar(255)
,`status` enum('pending','partial','completed','cancelled','expired')
,`total_items` bigint(21)
,`completed_items` decimal(22,0)
,`total_quantity_prescribed` decimal(32,0)
,`total_quantity_dispensed` decimal(32,0)
,`total_amount` decimal(12,2)
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_sales_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_sales_summary` (
`sale_id` int(11)
,`sale_code` varchar(10)
,`sale_date` date
,`sale_time` time
,`customer_name` varchar(255)
,`total_amount` decimal(12,2)
,`payment_method` enum('cash','card','bank_transfer','insurance','mixed')
,`status` enum('draft','completed','cancelled','returned')
,`employee_name` varchar(255)
,`item_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_current_stock`
--
DROP TABLE IF EXISTS `v_current_stock`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_current_stock`  AS SELECT `m`.`id` AS `medicine_id`, `m`.`medicine_code` AS `medicine_code`, `m`.`name` AS `medicine_name`, `m`.`unit` AS `unit`, `m`.`selling_price` AS `selling_price`, `m`.`min_stock_level` AS `min_stock_level`, `m`.`max_stock_level` AS `max_stock_level`, coalesce(sum(`mb`.`current_quantity`),0) AS `total_quantity`, count(`mb`.`id`) AS `active_batches`, min(`mb`.`expiry_date`) AS `nearest_expiry`, avg(`mb`.`import_price`) AS `avg_cost_price`, CASE WHEN coalesce(sum(`mb`.`current_quantity`),0) = 0 THEN 'Out of Stock' WHEN coalesce(sum(`mb`.`current_quantity`),0) <= `m`.`min_stock_level` THEN 'Low Stock' WHEN min(`mb`.`expiry_date`) <= curdate() + interval 30 day THEN 'Near Expiry' ELSE 'In Stock' END AS `stock_status` FROM (`medicines` `m` left join `medicine_batches` `mb` on(`m`.`id` = `mb`.`medicine_id` and `mb`.`status` = 'active')) WHERE `m`.`status` = 'active' GROUP BY `m`.`id`, `m`.`medicine_code`, `m`.`name`, `m`.`unit`, `m`.`selling_price`, `m`.`min_stock_level`, `m`.`max_stock_level` ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_prescription_status`
--
DROP TABLE IF EXISTS `v_prescription_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_prescription_status`  AS SELECT `p`.`id` AS `id`, `p`.`prescription_code` AS `prescription_code`, `p`.`prescription_date` AS `prescription_date`, `pat`.`full_name` AS `patient_name`, `p`.`doctor_name` AS `doctor_name`, `p`.`status` AS `status`, count(`pd`.`id`) AS `total_items`, sum(case when `pd`.`status` = 'completed' then 1 else 0 end) AS `completed_items`, sum(`pd`.`quantity_prescribed`) AS `total_quantity_prescribed`, sum(`pd`.`quantity_dispensed`) AS `total_quantity_dispensed`, `p`.`total_amount` AS `total_amount` FROM ((`prescriptions` `p` join `patients` `pat` on(`p`.`patient_id` = `pat`.`id`)) left join `prescription_details` `pd` on(`p`.`id` = `pd`.`prescription_id`)) GROUP BY `p`.`id` ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_sales_summary`
--
DROP TABLE IF EXISTS `v_sales_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_sales_summary`  AS SELECT `s`.`id` AS `sale_id`, `s`.`sale_code` AS `sale_code`, `s`.`sale_date` AS `sale_date`, `s`.`sale_time` AS `sale_time`, concat(coalesce(`p`.`full_name`,`s`.`customer_name`),'') AS `customer_name`, `s`.`total_amount` AS `total_amount`, `s`.`payment_method` AS `payment_method`, `s`.`status` AS `status`, `u`.`full_name` AS `employee_name`, count(`sd`.`id`) AS `item_count` FROM (((`sales` `s` left join `patients` `p` on(`s`.`patient_id` = `p`.`id`)) left join `users` `u` on(`s`.`employee_id` = `u`.`id`)) left join `sale_details` `sd` on(`s`.`id` = `sd`.`sale_id`)) GROUP BY `s`.`id` ;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_table_record` (`table_name`,`record_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_code` (`category_code`),
  ADD KEY `idx_category_code` (`category_code`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_parent` (`parent_id`);

--
-- Chỉ mục cho bảng `controlled_drug_log`
--
ALTER TABLE `controlled_drug_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `log_code` (`log_code`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `prescription_id` (`prescription_id`),
  ADD KEY `sold_by` (`sold_by`),
  ADD KEY `supervisor_approved_by` (`supervisor_approved_by`),
  ADD KEY `idx_log_code` (`log_code`),
  ADD KEY `idx_sale` (`sale_id`),
  ADD KEY `idx_medicine` (`medicine_id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_sold_at` (`sold_at`),
  ADD KEY `idx_doctor` (`doctor_name`),
  ADD KEY `idx_controlled_compliance` (`sold_at`,`medicine_id`);

--
-- Chỉ mục cho bảng `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medicine_code` (`medicine_code`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `default_supplier_id` (`default_supplier_id`),
  ADD KEY `idx_medicine_code` (`medicine_code`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_barcode` (`barcode`),
  ADD KEY `idx_generic_name` (`generic_name`),
  ADD KEY `idx_is_controlled` (`is_controlled`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_medicines_search` (`name`,`generic_name`,`barcode`);

--
-- Chỉ mục cho bảng `medicine_batches`
--
ALTER TABLE `medicine_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `batch_code` (`batch_code`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `imported_by` (`imported_by`),
  ADD KEY `idx_batch_code` (`batch_code`),
  ADD KEY `idx_medicine` (`medicine_id`),
  ADD KEY `idx_expiry_date` (`expiry_date`),
  ADD KEY `idx_batch_number` (`batch_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_batch_expiry_alert` (`expiry_date`,`status`,`current_quantity`);

--
-- Chỉ mục cho bảng `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patient_code` (`patient_code`),
  ADD UNIQUE KEY `id_number` (`id_number`),
  ADD KEY `idx_patient_code` (`patient_code`),
  ADD KEY `idx_id_number` (`id_number`),
  ADD KEY `idx_phone` (`phone`);

--
-- Chỉ mục cho bảng `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `prescription_code` (`prescription_code`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_prescription_code` (`prescription_code`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_prescription_date` (`prescription_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_doctor` (`doctor_name`);

--
-- Chỉ mục cho bảng `prescription_details`
--
ALTER TABLE `prescription_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prescription` (`prescription_id`),
  ADD KEY `idx_medicine` (`medicine_id`);

--
-- Chỉ mục cho bảng `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sale_code` (`sale_code`),
  ADD KEY `idx_sale_code` (`sale_code`),
  ADD KEY `idx_sale_date` (`sale_date`),
  ADD KEY `idx_prescription` (`prescription_id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sales_reporting` (`sale_date`,`status`,`total_amount`);

--
-- Chỉ mục cho bảng `sale_details`
--
ALTER TABLE `sale_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prescription_detail_id` (`prescription_detail_id`),
  ADD KEY `idx_sale` (`sale_id`),
  ADD KEY `idx_medicine` (`medicine_id`),
  ADD KEY `idx_batch` (`batch_id`);

--
-- Chỉ mục cho bảng `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `movement_code` (`movement_code`),
  ADD KEY `performed_by` (`performed_by`),
  ADD KEY `idx_movement_code` (`movement_code`),
  ADD KEY `idx_batch` (`batch_id`),
  ADD KEY `idx_movement_type` (`movement_type`),
  ADD KEY `idx_performed_at` (`performed_at`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`),
  ADD KEY `idx_stock_movements_reporting` (`performed_at`,`movement_type`);

--
-- Chỉ mục cho bảng `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `supplier_code` (`supplier_code`),
  ADD KEY `idx_supplier_code` (`supplier_code`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_status` (`status`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_code` (`user_code`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_user_code` (`user_code`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT cho bảng `controlled_drug_log`
--
ALTER TABLE `controlled_drug_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `medicine_batches`
--
ALTER TABLE `medicine_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT cho bảng `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `prescription_details`
--
ALTER TABLE `prescription_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `sale_details`
--
ALTER TABLE `sale_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT cho bảng `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `controlled_drug_log`
--
ALTER TABLE `controlled_drug_log`
  ADD CONSTRAINT `controlled_drug_log_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `controlled_drug_log_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`),
  ADD CONSTRAINT `controlled_drug_log_ibfk_3` FOREIGN KEY (`batch_id`) REFERENCES `medicine_batches` (`id`),
  ADD CONSTRAINT `controlled_drug_log_ibfk_4` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `controlled_drug_log_ibfk_5` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`),
  ADD CONSTRAINT `controlled_drug_log_ibfk_6` FOREIGN KEY (`sold_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `controlled_drug_log_ibfk_7` FOREIGN KEY (`supervisor_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `medicines_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `medicines_ibfk_2` FOREIGN KEY (`default_supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `medicine_batches`
--
ALTER TABLE `medicine_batches`
  ADD CONSTRAINT `medicine_batches_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medicine_batches_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `medicine_batches_ibfk_3` FOREIGN KEY (`imported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `prescription_details`
--
ALTER TABLE `prescription_details`
  ADD CONSTRAINT `prescription_details_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescription_details_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_ibfk_3` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `sale_details`
--
ALTER TABLE `sale_details`
  ADD CONSTRAINT `sale_details_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_details_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_details_ibfk_3` FOREIGN KEY (`batch_id`) REFERENCES `medicine_batches` (`id`),
  ADD CONSTRAINT `sale_details_ibfk_4` FOREIGN KEY (`prescription_detail_id`) REFERENCES `prescription_details` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `medicine_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
