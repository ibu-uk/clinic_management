-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 15, 2025 at 09:28 AM
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
-- Table structure for table `clinic_installments`
--

CREATE TABLE `clinic_installments` (
  `id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(10,3) NOT NULL,
  `status` enum('paid','pending','overdue') DEFAULT 'pending',
  `payment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinic_records`
--

CREATE TABLE `clinic_records` (
  `id` int(11) NOT NULL,
  `record_type` enum('Rent','Insurance','Clinic License','Fire Safety') NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contract_number` varchar(50) NOT NULL,
  `contact_number` varchar(50) NOT NULL,
  `contract_document` varchar(255) DEFAULT NULL,
  `contract_start_date` date NOT NULL,
  `contract_end_date` date NOT NULL,
  `total_cost` decimal(10,3) NOT NULL,
  `payment_type` enum('one_time','installment') NOT NULL,
  `down_payment` decimal(10,3) NOT NULL DEFAULT 0.000,
  `number_of_installments` int(11) DEFAULT 12,
  `monthly_payment` decimal(10,3) NOT NULL DEFAULT 0.000,
  `remaining_amount` decimal(10,3) NOT NULL DEFAULT 0.000,
  `next_payment_date` date DEFAULT NULL,
  `status` enum('paid','pending','overdue') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_start_date` date DEFAULT curdate(),
  `payment_months` int(11) DEFAULT 12
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinic_records`
--

INSERT INTO `clinic_records` (`id`, `record_type`, `company_name`, `contract_number`, `contact_number`, `contract_document`, `contract_start_date`, `contract_end_date`, `total_cost`, `payment_type`, `down_payment`, `number_of_installments`, `monthly_payment`, `remaining_amount`, `next_payment_date`, `status`, `notes`, `created_at`, `updated_at`, `payment_start_date`, `payment_months`) VALUES
(3, 'Rent', 'shield prime', '101010', '65065000', '67b1ab759af96_NADEEM.pdf', '2025-02-01', '2026-02-01', 7500.000, 'one_time', 7500.000, 12, 0.000, 0.000, NULL, 'paid', '', '2025-02-16 09:10:13', '2025-02-16 09:10:13', '2025-02-16', 12),
(4, 'Insurance', 'shield primennnn', '121200', '55445544', '67b1abd2b4a63_NADEEM1.pdf', '2025-02-01', '2026-02-01', 7500.000, 'installment', 0.000, 12, 625.000, 6875.000, '2025-03-16', 'pending', '', '2025-02-16 09:11:46', '2025-02-16 09:12:30', '2025-02-16', 12);

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
  `downpayment` decimal(10,3) DEFAULT 0.000,
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
  `start_date` date DEFAULT curdate(),
  `installment_start_date` date DEFAULT curdate(),
  `installment_months` int(11) DEFAULT 12
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`id`, `equipment_name`, `equipment_model`, `company_name`, `contract_number`, `contact_number`, `contract_type`, `contract_start_date`, `contract_end_date`, `total_cost`, `downpayment`, `payment_type`, `down_payment`, `remaining_amount`, `monthly_installment`, `num_installments`, `next_payment_date`, `maintenance_schedule`, `contract_file`, `status`, `created_at`, `updated_at`, `start_date`, `installment_start_date`, `installment_months`) VALUES
(25, 'laser jasmine', '252525L', 'bato clinic', '75757575', '66680241', 'new', '2025-02-01', '2026-02-01', 8500.000, 0.000, 'installment', 2500.000, 5500.000, 500.000, 12, '2025-03-15', '3', '67b08746a760a_1739622214.pdf', 'active', '2025-02-15 12:23:34', '2025-02-15 12:25:44', '2025-02-15', '2025-02-15', 12),
(26, 'bato prime', '252525L', 'bato clinic', '101010', '55445544', 'upgrade', '2025-02-01', '2026-02-01', 8500.000, 0.000, 'one_time', 0.000, 0.000, 0.000, NULL, NULL, '3', '67b4328fa185f_1739862671.pdf', '', '2025-02-18 07:11:11', '2025-02-18 07:13:02', '2025-02-18', '2025-02-18', 12),
(27, 'tvs', 'tvs-1100', 'sild', '15151510', '80808080', 'new', '2025-03-01', '2026-03-01', 9500.000, 0.000, 'installment', 2000.000, 7500.000, 625.000, 12, NULL, '6', '67d533b640d00_1742025654.pdf', 'active', '2025-03-15 08:00:54', '2025-03-15 08:00:54', '2025-03-15', '2025-03-15', 12);

-- --------------------------------------------------------

--
-- Table structure for table `equipment_installments`
--

CREATE TABLE `equipment_installments` (
  `id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(10,3) NOT NULL,
  `status` enum('paid','pending','overdue') DEFAULT 'pending',
  `payment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `record_type` enum('clinic_record','equipment') NOT NULL,
  `record_id` int(11) NOT NULL,
  `payment_type` enum('one_time','installment') NOT NULL,
  `amount` decimal(10,3) NOT NULL,
  `payment_date` date NOT NULL,
  `reference_no` varchar(50) NOT NULL,
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `record_type`, `record_id`, `payment_type`, `amount`, `payment_date`, `reference_no`, `status`, `created_at`, `updated_at`) VALUES
(12, 'equipment', 25, 'one_time', 500.000, '2025-02-15', '101025', 'completed', '2025-02-15 12:25:44', '2025-02-15 12:25:44'),
(13, 'clinic_record', 3, 'one_time', 7500.000, '2025-02-16', 'CR-DP-000003', 'completed', '2025-02-16 09:10:13', '2025-02-16 09:10:13'),
(14, 'clinic_record', 4, 'one_time', 625.000, '2025-02-16', '1230', 'completed', '2025-02-16 09:12:30', '2025-02-16 09:12:30'),
(15, 'equipment', 26, 'one_time', 8500.000, '2025-02-20', '202020', 'completed', '2025-02-18 07:13:02', '2025-02-18 07:13:02');

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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clinic_installments`
--
ALTER TABLE `clinic_installments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `record_id` (`record_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `clinic_records`
--
ALTER TABLE `clinic_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contract_number` (`contract_number`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `equipment_installments`
--
ALTER TABLE `equipment_installments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_id` (`equipment_id`),
  ADD KEY `payment_id` (`payment_id`);

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
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `clinic_installments`
--
ALTER TABLE `clinic_installments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinic_records`
--
ALTER TABLE `clinic_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `equipment_installments`
--
ALTER TABLE `equipment_installments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `monthly_installments`
--
ALTER TABLE `monthly_installments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clinic_installments`
--
ALTER TABLE `clinic_installments`
  ADD CONSTRAINT `clinic_installments_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `clinic_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clinic_installments_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `equipment_installments`
--
ALTER TABLE `equipment_installments`
  ADD CONSTRAINT `equipment_installments_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `equipment_installments_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
