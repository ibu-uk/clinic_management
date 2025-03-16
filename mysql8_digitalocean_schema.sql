-- MySQL 8.0.41 Schema for Clinic Management System (Digital Ocean)
-- Compatible with MySQL 8.0.41 on Digital Ocean Managed Databases

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `clinic_management`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `clinic_management`;

-- Enable MySQL 8.0 features and set recommended configurations
SET GLOBAL sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- Users table with role-based access control
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'doctor', 'staff', 'accountant') NOT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`),
    UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Patients table
CREATE TABLE IF NOT EXISTS `patients` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(255) NOT NULL,
    `date_of_birth` DATE NOT NULL,
    `gender` ENUM('male', 'female', 'other') NOT NULL,
    `contact_number` VARCHAR(20) NOT NULL,
    `email` VARCHAR(255),
    `address` TEXT,
    `medical_history` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `patients_name_index` (`full_name`),
    KEY `patients_dob_index` (`date_of_birth`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clinic Records table
CREATE TABLE IF NOT EXISTS `clinic_records` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `patient_id` BIGINT UNSIGNED NOT NULL,
    `doctor_id` BIGINT UNSIGNED NOT NULL,
    `appointment_date` DATETIME NOT NULL,
    `diagnosis` TEXT,
    `treatment` TEXT,
    `notes` TEXT,
    `status` ENUM('scheduled', 'in-progress', 'completed', 'cancelled') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `clinic_records_patient_index` (`patient_id`),
    KEY `clinic_records_doctor_index` (`doctor_id`),
    KEY `clinic_records_date_index` (`appointment_date`),
    CONSTRAINT `fk_records_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_records_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Equipment table
CREATE TABLE IF NOT EXISTS `equipment` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `purchase_date` DATE NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `status` ENUM('active', 'maintenance', 'retired') NOT NULL,
    `last_maintenance` DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `check_equipment_price_positive` CHECK (`price` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Maintenance table
CREATE TABLE IF NOT EXISTS `maintenance` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `equipment_id` BIGINT UNSIGNED NOT NULL,
    `maintenance_date` DATE NOT NULL,
    `description` TEXT NOT NULL,
    `cost` DECIMAL(10,2) NOT NULL,
    `performed_by` VARCHAR(255) NOT NULL,
    `next_maintenance_date` DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `maintenance_equipment_index` (`equipment_id`),
    CONSTRAINT `fk_maintenance_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `check_maintenance_cost_positive` CHECK (`cost` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments table
CREATE TABLE IF NOT EXISTS `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `clinic_record_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_date` DATETIME NOT NULL,
    `payment_method` ENUM('cash', 'card', 'bank_transfer', 'insurance') NOT NULL,
    `status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL,
    `transaction_reference` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `payments_record_index` (`clinic_record_id`),
    KEY `payments_date_index` (`payment_date`),
    CONSTRAINT `fk_payments_record` FOREIGN KEY (`clinic_record_id`) REFERENCES `clinic_records` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `check_payment_amount_positive` CHECK (`amount` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Installments table
CREATE TABLE IF NOT EXISTS `installments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `payment_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `due_date` DATE NOT NULL,
    `payment_date` DATETIME,
    `status` ENUM('pending', 'paid', 'overdue') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `installments_payment_index` (`payment_id`),
    KEY `installments_due_date_index` (`due_date`),
    CONSTRAINT `fk_installments_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `check_installment_amount_positive` CHECK (`amount` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create audit log table for tracking changes
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED,
    `table_name` VARCHAR(255) NOT NULL,
    `record_id` BIGINT UNSIGNED NOT NULL,
    `action` ENUM('insert', 'update', 'delete') NOT NULL,
    `old_values` JSON,
    `new_values` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `audit_logs_user_index` (`user_id`),
    KEY `audit_logs_table_record_index` (`table_name`, `record_id`),
    CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create settings table for system configuration
CREATE TABLE IF NOT EXISTS `settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(255) NOT NULL,
    `value` JSON NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enable binary logging for point-in-time recovery
SET GLOBAL log_bin = ON;

-- Set other recommended MySQL 8.0 configurations
SET GLOBAL innodb_buffer_pool_size = 1073741824; -- 1GB
SET GLOBAL max_connections = 200;
SET GLOBAL innodb_file_per_table = ON;
SET GLOBAL innodb_flush_method = 'O_DIRECT';
SET GLOBAL innodb_flush_log_at_trx_commit = 1;
SET GLOBAL sync_binlog = 1;

SET FOREIGN_KEY_CHECKS = 1;
