-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 09, 2025 at 03:42 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `clinic_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `clinic_records`
--

CREATE TABLE `clinic_records` (
  `id` int(11) NOT NULL,
  `record_type` enum('Rent','Insurance','Clinic License','Fire Safety') NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contract_number` varchar(100) NOT NULL,
  `contract_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `total_amount` decimal(10,3) NOT NULL,
  `payment_type` enum('one_time','installment') NOT NULL,
  `down_payment` decimal(10,3) DEFAULT NULL,
  `remaining_amount` decimal(10,3) DEFAULT NULL,
  `monthly_payment` decimal(10,3) DEFAULT NULL,
  `num_installments` int(11) DEFAULT NULL,
  `next_payment_date` date DEFAULT NULL,
  `contract_file` varchar(255) DEFAULT NULL,
  `status` enum('active','completed','terminated') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `monthly_installment` decimal(10,3) DEFAULT NULL,
  `total_cost` decimal(10,3) DEFAULT NULL,
  `start_date` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinic_records`
--

INSERT INTO `clinic_records` (`id`, `record_type`, `company_name`, `contract_number`, `contract_date`, `expiry_date`, `total_amount`, `payment_type`, `down_payment`, `remaining_amount`, `monthly_payment`, `num_installments`, `next_payment_date`, `contract_file`, `status`, `created_at`, `updated_at`, `monthly_installment`, `total_cost`, `start_date`) VALUES
(6, 'Rent', 'bato', '123-2024', '2025-01-01', '2026-01-01', 5000.000, 'installment', 1000.000, 4000.000, 333.333, 12, '2025-02-01', '677fcb36dfcd9_RCD Replying on Tones8-1-2025.pdf', 'active', '2025-01-09 13:12:22', '2025-01-09 13:12:22', NULL, NULL, '2025-01-09'),
(7, 'Insurance', 'bato clinic123', '222-2024', '2025-01-09', '2026-01-09', 6550.000, 'installment', 1500.000, 5050.000, 420.833, 12, '2025-02-09', '', 'active', '2025-01-09 13:31:13', '2025-01-09 13:31:13', NULL, NULL, '2025-01-09');

-- --------------------------------------------------------

--
-- Stand-in structure for view `clinic_records_report`
-- (See below for the actual view)
--
CREATE TABLE `clinic_records_report` (
`id` int(11)
,`record_type` enum('Rent','Insurance','Clinic License','Fire Safety')
,`company_name` varchar(255)
,`contract_number` varchar(100)
,`contract_date` date
,`expiry_date` date
,`total_amount` decimal(10,3)
,`payment_type` enum('one_time','installment')
,`down_payment` decimal(10,3)
,`remaining_amount` decimal(10,3)
,`monthly_payment` decimal(10,3)
,`num_installments` int(11)
,`next_payment_date` date
,`contract_file` varchar(255)
,`status` enum('active','completed','terminated')
,`created_at` timestamp
,`updated_at` timestamp
,`payment_date` date
,`payment_method` enum('cash','cheque','bank','link')
,`payment_amount` decimal(10,3)
,`payment_status` enum('pending','completed')
);

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int(11) NOT NULL,
  `equipment_name` varchar(255) NOT NULL,
  `equipment_model` varchar(255) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contract_number` varchar(100) NOT NULL,
  `contact_number` varchar(50) NOT NULL,
  `contract_type` enum('new','upgrade','renew') NOT NULL,
  `contract_start_date` date NOT NULL,
  `contract_end_date` date NOT NULL,
  `total_cost` decimal(10,3) NOT NULL,
  `payment_type` enum('one_time','installment') NOT NULL,
  `down_payment` decimal(10,3) DEFAULT NULL,
  `remaining_amount` decimal(10,3) DEFAULT NULL,
  `monthly_installment` decimal(10,3) DEFAULT NULL,
  `num_installments` int(11) DEFAULT NULL,
  `next_payment_date` date DEFAULT NULL,
  `maintenance_schedule` text DEFAULT NULL,
  `contract_file` varchar(255) DEFAULT NULL,
  `status` enum('active','completed','terminated') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `start_date` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `equipment_report`
-- (See below for the actual view)
--
CREATE TABLE `equipment_report` (
`id` int(11)
,`equipment_name` varchar(255)
,`equipment_model` varchar(255)
,`company_name` varchar(255)
,`contract_number` varchar(100)
,`contact_number` varchar(50)
,`contract_type` enum('new','upgrade','renew')
,`contract_start_date` date
,`contract_end_date` date
,`total_cost` decimal(10,3)
,`payment_type` enum('one_time','installment')
,`down_payment` decimal(10,3)
,`remaining_amount` decimal(10,3)
,`monthly_installment` decimal(10,3)
,`num_installments` int(11)
,`next_payment_date` date
,`maintenance_schedule` text
,`contract_file` varchar(255)
,`status` enum('active','completed','terminated')
,`created_at` timestamp
,`updated_at` timestamp
,`payment_date` date
,`payment_method` enum('cash','cheque','bank','link')
,`payment_amount` decimal(10,3)
,`payment_status` enum('pending','completed')
);

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `maintenance_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monthly_installments`
--

CREATE TABLE `monthly_installments` (
  `id` int(11) NOT NULL,
  `record_type` enum('equipment','clinic_record') NOT NULL,
  `record_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(10,3) NOT NULL,
  `status` enum('pending','paid','overdue') NOT NULL DEFAULT 'pending',
  `payment_id` int(11) DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `monthly_installments`
--

INSERT INTO `monthly_installments` (`id`, `record_type`, `record_id`, `due_date`, `amount`, `status`, `payment_id`, `paid_date`, `description`, `created_at`, `updated_at`) VALUES
(1, 'equipment', 3, '2025-01-09', 500.000, 'paid', 3, '2025-01-09', NULL, '2025-01-09 09:48:27', '2025-01-09 11:28:58'),
(2, 'equipment', 3, '2025-02-09', 500.000, 'paid', 6, '2025-01-09', NULL, '2025-01-09 09:48:27', '2025-01-09 11:59:23'),
(3, 'equipment', 3, '2025-03-09', 500.000, 'paid', 7, '2025-01-09', NULL, '2025-01-09 09:48:27', '2025-01-09 12:08:39'),
(4, 'equipment', 3, '2025-04-09', 500.000, 'paid', 8, '2025-01-09', NULL, '2025-01-09 09:48:27', '2025-01-09 12:09:39'),
(5, 'equipment', 3, '2025-05-09', 500.000, 'paid', 9, '2025-01-09', NULL, '2025-01-09 09:48:27', '2025-01-09 12:31:05'),
(6, 'equipment', 3, '2025-06-09', 500.000, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:27', '2025-01-09 09:48:27'),
(7, 'equipment', 3, '2025-07-09', 500.000, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:27', '2025-01-09 09:48:27'),
(8, 'equipment', 3, '2025-08-09', 500.000, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:27', '2025-01-09 09:48:27'),
(9, 'equipment', 3, '2025-09-09', 500.000, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:27', '2025-01-09 09:48:27'),
(10, 'equipment', 3, '2025-10-09', 500.000, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:27', '2025-01-09 09:48:27'),
(11, 'equipment', 3, '2025-11-09', 500.000, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:27', '2025-01-09 09:48:27'),
(12, 'equipment', 3, '2025-12-09', 500.000, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:27', '2025-01-09 09:48:27'),
(13, 'clinic_record', 3, '2025-01-09', 541.667, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:37', '2025-01-09 09:48:37'),
(14, 'clinic_record', 3, '2025-02-09', 541.667, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:37', '2025-01-09 09:48:37'),
(15, 'clinic_record', 3, '2025-03-09', 541.667, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:37', '2025-01-09 09:48:37'),
(16, 'clinic_record', 3, '2025-04-09', 541.667, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:37', '2025-01-09 09:48:37'),
(17, 'clinic_record', 3, '2025-05-09', 541.667, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:37', '2025-01-09 09:48:37'),
(18, 'clinic_record', 3, '2025-06-09', 541.667, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:37', '2025-01-09 09:48:37'),
(19, 'clinic_record', 3, '2025-07-09', 541.667, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:37', '2025-01-09 09:48:37'),
(20, 'clinic_record', 3, '2025-08-09', 541.667, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:37', '2025-01-09 09:48:37'),
(21, 'clinic_record', 3, '2025-09-09', 541.667, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:37', '2025-01-09 09:48:37'),
(22, 'clinic_record', 3, '2025-10-09', 541.667, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:37', '2025-01-09 09:48:37'),
(23, 'clinic_record', 3, '2025-11-09', 541.667, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:37', '2025-01-09 09:48:37'),
(24, 'clinic_record', 3, '2025-12-09', 541.663, 'pending', NULL, NULL, NULL, '2025-01-09 09:48:37', '2025-01-09 09:48:37'),
(25, 'equipment', 1, '2025-01-09', 500.000, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:18', '2025-01-09 09:49:18'),
(26, 'equipment', 1, '2025-02-09', 500.000, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:18', '2025-01-09 09:49:18'),
(27, 'equipment', 1, '2025-03-09', 500.000, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:18', '2025-01-09 09:49:18'),
(28, 'equipment', 1, '2025-04-09', 500.000, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:18', '2025-01-09 09:49:18'),
(29, 'equipment', 1, '2025-05-09', 500.000, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:18', '2025-01-09 09:49:18'),
(30, 'equipment', 1, '2025-06-09', 500.000, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:18', '2025-01-09 09:49:18'),
(31, 'equipment', 1, '2025-07-09', 500.000, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:18', '2025-01-09 09:49:18'),
(32, 'equipment', 1, '2025-08-09', 500.000, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:18', '2025-01-09 09:49:18'),
(33, 'equipment', 1, '2025-09-09', 500.000, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:18', '2025-01-09 09:49:18'),
(34, 'equipment', 1, '2025-10-09', 500.000, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:18', '2025-01-09 09:49:18'),
(35, 'equipment', 1, '2025-11-09', 500.000, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:18', '2025-01-09 09:49:18'),
(36, 'clinic_record', 2, '2025-01-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:23', '2025-01-09 09:49:23'),
(37, 'clinic_record', 2, '2025-02-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:23', '2025-01-09 09:49:23'),
(38, 'clinic_record', 2, '2025-03-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:23', '2025-01-09 09:49:23'),
(39, 'clinic_record', 2, '2025-04-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:23', '2025-01-09 09:49:23'),
(40, 'clinic_record', 2, '2025-05-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:23', '2025-01-09 09:49:23'),
(41, 'clinic_record', 2, '2025-06-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:23', '2025-01-09 09:49:23'),
(42, 'clinic_record', 2, '2025-07-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:23', '2025-01-09 09:49:23'),
(43, 'clinic_record', 2, '2025-08-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:23', '2025-01-09 09:49:23'),
(44, 'clinic_record', 2, '2025-09-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:23', '2025-01-09 09:49:23'),
(45, 'clinic_record', 2, '2025-10-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:23', '2025-01-09 09:49:23'),
(46, 'clinic_record', 2, '2025-11-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:23', '2025-01-09 09:49:23'),
(47, 'clinic_record', 2, '2025-12-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:23', '2025-01-09 09:49:23'),
(48, 'clinic_record', 2, '2026-01-09', 0.004, 'pending', NULL, NULL, NULL, '2025-01-09 09:49:23', '2025-01-09 09:49:23'),
(49, 'clinic_record', 1, '2025-01-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 10:02:37', '2025-01-09 10:02:37'),
(50, 'clinic_record', 1, '2025-02-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 10:02:37', '2025-01-09 10:02:37'),
(51, 'clinic_record', 1, '2025-03-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 10:02:37', '2025-01-09 10:02:37'),
(52, 'clinic_record', 1, '2025-04-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 10:02:37', '2025-01-09 10:02:37'),
(53, 'clinic_record', 1, '2025-05-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 10:02:37', '2025-01-09 10:02:37'),
(54, 'clinic_record', 1, '2025-06-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 10:02:37', '2025-01-09 10:02:37'),
(55, 'clinic_record', 1, '2025-07-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 10:02:37', '2025-01-09 10:02:37'),
(56, 'clinic_record', 1, '2025-08-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 10:02:37', '2025-01-09 10:02:37'),
(57, 'clinic_record', 1, '2025-09-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 10:02:37', '2025-01-09 10:02:37'),
(58, 'clinic_record', 1, '2025-10-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 10:02:37', '2025-01-09 10:02:37'),
(59, 'clinic_record', 1, '2025-11-09', 333.333, 'pending', NULL, NULL, NULL, '2025-01-09 10:02:37', '2025-01-09 10:02:37'),
(60, 'clinic_record', 1, '2025-12-09', 0.004, 'pending', NULL, NULL, NULL, '2025-01-09 10:02:37', '2025-01-09 10:02:37'),
(61, 'clinic_record', 4, '2025-01-09', 208.333, 'paid', 4, '2025-01-09', NULL, '2025-01-09 11:36:26', '2025-01-09 11:37:33'),
(62, 'clinic_record', 4, '2025-02-09', 208.333, 'paid', 5, '2025-01-09', NULL, '2025-01-09 11:36:26', '2025-01-09 11:47:35'),
(63, 'clinic_record', 4, '2025-03-09', 208.333, 'pending', NULL, NULL, NULL, '2025-01-09 11:36:26', '2025-01-09 11:36:26'),
(64, 'clinic_record', 4, '2025-04-09', 208.333, 'pending', NULL, NULL, NULL, '2025-01-09 11:36:26', '2025-01-09 11:36:26'),
(65, 'clinic_record', 4, '2025-05-09', 208.333, 'pending', NULL, NULL, NULL, '2025-01-09 11:36:26', '2025-01-09 11:36:26'),
(66, 'clinic_record', 4, '2025-06-09', 208.333, 'pending', NULL, NULL, NULL, '2025-01-09 11:36:26', '2025-01-09 11:36:26'),
(67, 'clinic_record', 4, '2025-07-09', 208.333, 'pending', NULL, NULL, NULL, '2025-01-09 11:36:26', '2025-01-09 11:36:26'),
(68, 'clinic_record', 4, '2025-08-09', 208.333, 'pending', NULL, NULL, NULL, '2025-01-09 11:36:26', '2025-01-09 11:36:26'),
(69, 'clinic_record', 4, '2025-09-09', 208.333, 'pending', NULL, NULL, NULL, '2025-01-09 11:36:26', '2025-01-09 11:36:26'),
(70, 'clinic_record', 4, '2025-10-09', 208.333, 'pending', NULL, NULL, NULL, '2025-01-09 11:36:26', '2025-01-09 11:36:26'),
(71, 'clinic_record', 4, '2025-11-09', 208.333, 'pending', NULL, NULL, NULL, '2025-01-09 11:36:26', '2025-01-09 11:36:26'),
(72, 'clinic_record', 4, '2025-12-09', 208.333, 'pending', NULL, NULL, NULL, '2025-01-09 11:36:26', '2025-01-09 11:36:26'),
(73, 'clinic_record', 4, '2026-01-09', 0.004, 'pending', NULL, NULL, NULL, '2025-01-09 11:36:26', '2025-01-09 11:36:26'),
(74, 'clinic_record', 5, '2025-01-31', 458.333, 'pending', NULL, NULL, NULL, '2025-01-09 12:20:16', '2025-01-09 12:20:16'),
(75, 'clinic_record', 5, '2025-03-03', 458.333, 'pending', NULL, NULL, NULL, '2025-01-09 12:20:16', '2025-01-09 12:20:16'),
(76, 'clinic_record', 5, '2025-04-03', 458.333, 'pending', NULL, NULL, NULL, '2025-01-09 12:20:16', '2025-01-09 12:20:16'),
(77, 'clinic_record', 5, '2025-05-03', 458.333, 'pending', NULL, NULL, NULL, '2025-01-09 12:20:16', '2025-01-09 12:20:16'),
(78, 'clinic_record', 5, '2025-06-03', 458.333, 'pending', NULL, NULL, NULL, '2025-01-09 12:20:16', '2025-01-09 12:20:16'),
(79, 'clinic_record', 5, '2025-07-03', 458.333, 'pending', NULL, NULL, NULL, '2025-01-09 12:20:16', '2025-01-09 12:20:16'),
(80, 'clinic_record', 5, '2025-08-03', 458.333, 'pending', NULL, NULL, NULL, '2025-01-09 12:20:16', '2025-01-09 12:20:16'),
(81, 'clinic_record', 5, '2025-09-03', 458.333, 'pending', NULL, NULL, NULL, '2025-01-09 12:20:16', '2025-01-09 12:20:16'),
(82, 'clinic_record', 5, '2025-10-03', 458.333, 'pending', NULL, NULL, NULL, '2025-01-09 12:20:16', '2025-01-09 12:20:16'),
(83, 'clinic_record', 5, '2025-11-03', 458.333, 'pending', NULL, NULL, NULL, '2025-01-09 12:20:16', '2025-01-09 12:20:16'),
(84, 'clinic_record', 5, '2025-12-03', 458.333, 'pending', NULL, NULL, NULL, '2025-01-09 12:20:16', '2025-01-09 12:20:16'),
(85, 'clinic_record', 5, '2026-01-03', 458.333, 'pending', NULL, NULL, NULL, '2025-01-09 12:20:16', '2025-01-09 12:20:16');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `type` enum('maintenance','payment','expiry') NOT NULL,
  `reference_type` enum('equipment','clinic_record') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','sent','read') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `record_type` enum('new','renew','upgrade','rent','insurance','clinic_license','fire_safety') NOT NULL,
  `record_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(10,3) NOT NULL,
  `payment_method` enum('cash','cheque','bank','link') NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '', '2025-01-08 09:07:33');

-- --------------------------------------------------------

--
-- Structure for view `clinic_records_report`
--
DROP TABLE IF EXISTS `clinic_records_report`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `clinic_records_report`  AS SELECT `cr`.`id` AS `id`, `cr`.`record_type` AS `record_type`, `cr`.`company_name` AS `company_name`, `cr`.`contract_number` AS `contract_number`, `cr`.`contract_date` AS `contract_date`, `cr`.`expiry_date` AS `expiry_date`, `cr`.`total_amount` AS `total_amount`, `cr`.`payment_type` AS `payment_type`, `cr`.`down_payment` AS `down_payment`, `cr`.`remaining_amount` AS `remaining_amount`, `cr`.`monthly_payment` AS `monthly_payment`, `cr`.`num_installments` AS `num_installments`, `cr`.`next_payment_date` AS `next_payment_date`, `cr`.`contract_file` AS `contract_file`, `cr`.`status` AS `status`, `cr`.`created_at` AS `created_at`, `cr`.`updated_at` AS `updated_at`, `p`.`payment_date` AS `payment_date`, `p`.`payment_method` AS `payment_method`, `p`.`amount` AS `payment_amount`, `p`.`status` AS `payment_status` FROM (`clinic_records` `cr` left join `payments` `p` on(`cr`.`id` = `p`.`record_id`)) WHERE `p`.`record_type` in ('rent','insurance','clinic_license','fire_safety') ;

-- --------------------------------------------------------

--
-- Structure for view `equipment_report`
--
DROP TABLE IF EXISTS `equipment_report`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `equipment_report`  AS SELECT `e`.`id` AS `id`, `e`.`equipment_name` AS `equipment_name`, `e`.`equipment_model` AS `equipment_model`, `e`.`company_name` AS `company_name`, `e`.`contract_number` AS `contract_number`, `e`.`contact_number` AS `contact_number`, `e`.`contract_type` AS `contract_type`, `e`.`contract_start_date` AS `contract_start_date`, `e`.`contract_end_date` AS `contract_end_date`, `e`.`total_cost` AS `total_cost`, `e`.`payment_type` AS `payment_type`, `e`.`down_payment` AS `down_payment`, `e`.`remaining_amount` AS `remaining_amount`, `e`.`monthly_installment` AS `monthly_installment`, `e`.`num_installments` AS `num_installments`, `e`.`next_payment_date` AS `next_payment_date`, `e`.`maintenance_schedule` AS `maintenance_schedule`, `e`.`contract_file` AS `contract_file`, `e`.`status` AS `status`, `e`.`created_at` AS `created_at`, `e`.`updated_at` AS `updated_at`, `p`.`payment_date` AS `payment_date`, `p`.`payment_method` AS `payment_method`, `p`.`amount` AS `payment_amount`, `p`.`status` AS `payment_status` FROM (`equipment` `e` left join `payments` `p` on(`e`.`id` = `p`.`record_id`)) WHERE `p`.`record_type` in ('new','renew','upgrade') ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clinic_records`
--
ALTER TABLE `clinic_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `monthly_installments`
--
ALTER TABLE `monthly_installments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `record_type_id` (`record_type`,`record_id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `status_due_date` (`status`,`due_date`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `record_type_id` (`record_type`,`record_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clinic_records`
--
ALTER TABLE `clinic_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `monthly_installments`
--
ALTER TABLE `monthly_installments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
