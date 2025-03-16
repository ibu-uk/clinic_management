-- MySQL 8.0.41 Schema for Clinic Management System (Digital Ocean)
-- Compatible with MySQL 8.0.41 on Digital Ocean Managed Databases

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Create database if not exists with optimal settings
CREATE DATABASE IF NOT EXISTS `clinic_management`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `clinic_management`;

-- Set optimal MySQL 8.0 configurations for performance
SET SESSION sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- Performance and optimization settings (non read-only variables)
SET SESSION innodb_lock_wait_timeout = 50;
SET SESSION interactive_timeout = 28800;
SET SESSION wait_timeout = 28800;
SET SESSION net_write_timeout = 600;
SET SESSION net_read_timeout = 600;
SET SESSION max_allowed_packet = 67108864; -- 64MB

-- Optimizer settings for better query performance
SET SESSION optimizer_switch = 'index_merge=on,index_merge_union=on,index_merge_sort_union=on,index_merge_intersection=on,engine_condition_pushdown=on,index_condition_pushdown=on,mrr=on,mrr_cost_based=on,block_nested_loop=on,batched_key_access=off,materialization=on,semijoin=on,loosescan=on,firstmatch=on,duplicateweedout=on,subquery_materialization_cost_based=on,use_index_extensions=on,condition_fanout_filter=on,derived_merge=on';

-- Enable performance schema for monitoring (if available)
SET SESSION performance_schema = 1;

-- Create users table with optimized settings
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
    UNIQUE KEY `users_email_unique` (`email`) USING BTREE,
    UNIQUE KEY `users_username_unique` (`username`) USING BTREE
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC
  KEY_BLOCK_SIZE=8;

-- Patients table with optimized settings
CREATE TABLE IF NOT EXISTS `patients` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(255) NOT NULL,
    `date_of_birth` DATE NOT NULL,
    `gender` ENUM('male', 'female', 'other') NOT NULL,
    `contact_number` VARCHAR(20) NOT NULL,
    `email` VARCHAR(255),
    `address` TEXT,
    `medical_history` JSON,
    `blood_type` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'),
    `allergies` JSON,
    `emergency_contact` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `patients_name_index` (`full_name`) USING BTREE,
    KEY `patients_dob_index` (`date_of_birth`) USING BTREE,
    KEY `patients_email_index` (`email`) USING BTREE,
    FULLTEXT KEY `patients_fulltext_search` (`full_name`, `address`)
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC
  KEY_BLOCK_SIZE=8;

-- Clinic Records table with optimized settings
CREATE TABLE IF NOT EXISTS `clinic_records` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `patient_id` BIGINT UNSIGNED NOT NULL,
    `doctor_id` BIGINT UNSIGNED NOT NULL,
    `appointment_date` DATETIME NOT NULL,
    `diagnosis` TEXT,
    `treatment` TEXT,
    `notes` TEXT,
    `status` ENUM('scheduled', 'in-progress', 'completed', 'cancelled') NOT NULL,
    `vital_signs` JSON,
    `prescription` JSON,
    `lab_results` JSON,
    `follow_up_date` DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `clinic_records_patient_index` (`patient_id`) USING BTREE,
    KEY `clinic_records_doctor_index` (`doctor_id`) USING BTREE,
    KEY `clinic_records_date_index` (`appointment_date`) USING BTREE,
    KEY `clinic_records_status_index` (`status`) USING BTREE,
    KEY `clinic_records_followup_index` (`follow_up_date`) USING BTREE,
    CONSTRAINT `fk_records_patient` FOREIGN KEY (`patient_id`) 
        REFERENCES `patients` (`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_records_doctor` FOREIGN KEY (`doctor_id`) 
        REFERENCES `users` (`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC
  KEY_BLOCK_SIZE=8;

-- Equipment table with optimized settings
CREATE TABLE IF NOT EXISTS `equipment` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `purchase_date` DATE NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `status` ENUM('active', 'maintenance', 'retired') NOT NULL,
    `last_maintenance` DATE,
    `warranty_info` JSON,
    `specifications` JSON,
    `location` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `equipment_status_index` (`status`) USING BTREE,
    KEY `equipment_location_index` (`location`) USING BTREE,
    CONSTRAINT `check_equipment_price_positive` CHECK (`price` > 0)
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC
  KEY_BLOCK_SIZE=8;

-- Maintenance table with optimized settings
CREATE TABLE IF NOT EXISTS `maintenance` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `equipment_id` BIGINT UNSIGNED NOT NULL,
    `maintenance_date` DATE NOT NULL,
    `description` TEXT NOT NULL,
    `cost` DECIMAL(10,2) NOT NULL,
    `performed_by` VARCHAR(255) NOT NULL,
    `next_maintenance_date` DATE,
    `maintenance_type` ENUM('preventive', 'corrective', 'predictive') NOT NULL,
    `parts_replaced` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `maintenance_equipment_index` (`equipment_id`) USING BTREE,
    KEY `maintenance_date_index` (`maintenance_date`) USING BTREE,
    KEY `maintenance_next_date_index` (`next_maintenance_date`) USING BTREE,
    KEY `maintenance_type_index` (`maintenance_type`) USING BTREE,
    CONSTRAINT `fk_maintenance_equipment` FOREIGN KEY (`equipment_id`) 
        REFERENCES `equipment` (`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    CONSTRAINT `check_maintenance_cost_positive` CHECK (`cost` > 0)
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC
  KEY_BLOCK_SIZE=8;

-- Payments table with optimized settings
CREATE TABLE IF NOT EXISTS `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `clinic_record_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_date` DATETIME NOT NULL,
    `payment_method` ENUM('cash', 'card', 'bank_transfer', 'insurance') NOT NULL,
    `status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL,
    `transaction_reference` VARCHAR(255),
    `payment_details` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `payments_record_index` (`clinic_record_id`) USING BTREE,
    KEY `payments_date_index` (`payment_date`) USING BTREE,
    KEY `payments_status_index` (`status`) USING BTREE,
    KEY `payments_method_index` (`payment_method`) USING BTREE,
    CONSTRAINT `fk_payments_record` FOREIGN KEY (`clinic_record_id`) 
        REFERENCES `clinic_records` (`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    CONSTRAINT `check_payment_amount_positive` CHECK (`amount` > 0)
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC
  KEY_BLOCK_SIZE=8;

-- Installments table with optimized settings
CREATE TABLE IF NOT EXISTS `installments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `payment_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `due_date` DATE NOT NULL,
    `payment_date` DATETIME,
    `status` ENUM('pending', 'paid', 'overdue') NOT NULL,
    `reminder_sent` BOOLEAN DEFAULT FALSE,
    `payment_details` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `installments_payment_index` (`payment_id`) USING BTREE,
    KEY `installments_due_date_index` (`due_date`) USING BTREE,
    KEY `installments_status_index` (`status`) USING BTREE,
    CONSTRAINT `fk_installments_payment` FOREIGN KEY (`payment_id`) 
        REFERENCES `payments` (`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    CONSTRAINT `check_installment_amount_positive` CHECK (`amount` > 0)
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC
  KEY_BLOCK_SIZE=8;

-- Audit logs table with optimized settings
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED,
    `table_name` VARCHAR(255) NOT NULL,
    `record_id` BIGINT UNSIGNED NOT NULL,
    `action` ENUM('insert', 'update', 'delete') NOT NULL,
    `old_values` JSON,
    `new_values` JSON,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `audit_logs_user_index` (`user_id`) USING BTREE,
    KEY `audit_logs_table_record_index` (`table_name`, `record_id`) USING BTREE,
    KEY `audit_logs_action_index` (`action`) USING BTREE,
    KEY `audit_logs_created_index` (`created_at`) USING BTREE,
    CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC
  KEY_BLOCK_SIZE=8;

-- Settings table with optimized settings
CREATE TABLE IF NOT EXISTS `settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(255) NOT NULL,
    `value` JSON NOT NULL,
    `description` TEXT,
    `category` VARCHAR(255),
    `is_public` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `settings_key_unique` (`key`) USING BTREE,
    KEY `settings_category_index` (`category`) USING BTREE,
    KEY `settings_public_index` (`is_public`) USING BTREE
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC
  KEY_BLOCK_SIZE=8;

-- Create stored procedures for common operations

DELIMITER //

-- Procedure to add new patient with basic validation
CREATE PROCEDURE `sp_add_patient`(
    IN p_full_name VARCHAR(255),
    IN p_date_of_birth DATE,
    IN p_gender ENUM('male', 'female', 'other'),
    IN p_contact_number VARCHAR(20),
    IN p_email VARCHAR(255),
    IN p_address TEXT
)
BEGIN
    INSERT INTO `patients` (
        full_name, 
        date_of_birth, 
        gender, 
        contact_number, 
        email, 
        address
    ) VALUES (
        p_full_name,
        p_date_of_birth,
        p_gender,
        p_contact_number,
        p_email,
        p_address
    );
END //

-- Procedure to schedule appointment
CREATE PROCEDURE `sp_schedule_appointment`(
    IN p_patient_id BIGINT UNSIGNED,
    IN p_doctor_id BIGINT UNSIGNED,
    IN p_appointment_date DATETIME
)
BEGIN
    INSERT INTO `clinic_records` (
        patient_id,
        doctor_id,
        appointment_date,
        status
    ) VALUES (
        p_patient_id,
        p_doctor_id,
        p_appointment_date,
        'scheduled'
    );
END //

-- Procedure to process payment
CREATE PROCEDURE `sp_process_payment`(
    IN p_clinic_record_id BIGINT UNSIGNED,
    IN p_amount DECIMAL(10,2),
    IN p_payment_method ENUM('cash', 'card', 'bank_transfer', 'insurance')
)
BEGIN
    INSERT INTO `payments` (
        clinic_record_id,
        amount,
        payment_date,
        payment_method,
        status
    ) VALUES (
        p_clinic_record_id,
        p_amount,
        NOW(),
        p_payment_method,
        'completed'
    );
END //

DELIMITER ;

-- Create views for common queries

-- View for patient appointments
CREATE OR REPLACE VIEW `v_patient_appointments` AS
SELECT 
    p.id AS patient_id,
    p.full_name AS patient_name,
    u.username AS doctor_name,
    cr.appointment_date,
    cr.status
FROM patients p
JOIN clinic_records cr ON p.id = cr.patient_id
JOIN users u ON cr.doctor_id = u.id;

-- View for equipment maintenance schedule
CREATE OR REPLACE VIEW `v_equipment_maintenance` AS
SELECT 
    e.id AS equipment_id,
    e.name AS equipment_name,
    e.status,
    m.maintenance_date AS last_maintenance_date,
    m.next_maintenance_date,
    m.maintenance_type,
    m.cost AS maintenance_cost
FROM equipment e
LEFT JOIN maintenance m ON e.id = m.equipment_id
WHERE m.maintenance_date = (
    SELECT MAX(maintenance_date)
    FROM maintenance
    WHERE equipment_id = e.id
);

-- View for payment statistics
CREATE OR REPLACE VIEW `v_payment_statistics` AS
SELECT 
    DATE(payment_date) AS payment_day,
    payment_method,
    COUNT(*) AS transaction_count,
    SUM(amount) AS total_amount,
    AVG(amount) AS average_amount
FROM payments
GROUP BY DATE(payment_date), payment_method;

-- Create events for automated tasks

DELIMITER //

-- Event to mark overdue installments
CREATE EVENT `e_mark_overdue_installments`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    UPDATE installments 
    SET status = 'overdue'
    WHERE due_date < CURDATE() 
    AND status = 'pending';
END //

-- Event to archive old audit logs
CREATE EVENT `e_archive_old_audit_logs`
ON SCHEDULE EVERY 1 MONTH
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    -- Archive logs older than 6 months
    -- Implementation depends on your archiving strategy
    -- This is a placeholder for the actual archiving logic
    DELETE FROM audit_logs
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
END //

DELIMITER ;

-- Create triggers for data integrity and audit logging

DELIMITER //

-- Trigger to log changes in patients table
CREATE TRIGGER `tr_patients_after_update`
AFTER UPDATE ON `patients`
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (
        table_name,
        record_id,
        action,
        old_values,
        new_values
    ) VALUES (
        'patients',
        NEW.id,
        'update',
        JSON_OBJECT(
            'full_name', OLD.full_name,
            'email', OLD.email,
            'contact_number', OLD.contact_number
        ),
        JSON_OBJECT(
            'full_name', NEW.full_name,
            'email', NEW.email,
            'contact_number', NEW.contact_number
        )
    );
END //

-- Trigger to validate payment amounts
CREATE TRIGGER `tr_payments_before_insert`
BEFORE INSERT ON `payments`
FOR EACH ROW
BEGIN
    IF NEW.amount <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Payment amount must be greater than zero';
    END IF;
END //

DELIMITER ;

-- Set session variables back to default
SET SESSION sql_mode = DEFAULT;
SET FOREIGN_KEY_CHECKS = 1;
