-- Create database
CREATE DATABASE IF NOT EXISTS clinic_management;
USE clinic_management;

-- Equipment table
CREATE TABLE IF NOT EXISTS equipment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_name VARCHAR(255) NOT NULL,
    equipment_model VARCHAR(255) NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    contract_number VARCHAR(100) NOT NULL,
    contact_number VARCHAR(50) NOT NULL,
    contract_type ENUM('new', 'upgrade', 'renew') NOT NULL,
    contract_start_date DATE NOT NULL,
    contract_end_date DATE NOT NULL,
    total_cost DECIMAL(10, 3) NOT NULL,
    payment_type ENUM('one_time', 'installment') NOT NULL,
    down_payment DECIMAL(10, 3),
    remaining_amount DECIMAL(10, 3),
    monthly_installment DECIMAL(10, 3),
    num_installments INT,
    next_payment_date DATE,
    maintenance_schedule TEXT,
    contract_file VARCHAR(255),
    status ENUM('active', 'completed', 'terminated') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Clinic Records table
CREATE TABLE IF NOT EXISTS clinic_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    record_type ENUM('Rent', 'Insurance', 'Clinic License', 'Fire Safety') NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    contract_number VARCHAR(100) NOT NULL,
    contract_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    total_amount DECIMAL(10, 3) NOT NULL,
    payment_type ENUM('one_time', 'installment') NOT NULL,
    down_payment DECIMAL(10, 3),
    remaining_amount DECIMAL(10, 3),
    monthly_payment DECIMAL(10, 3),
    num_installments INT,
    next_payment_date DATE,
    contract_file VARCHAR(255),
    status ENUM('active', 'completed', 'terminated') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    record_type ENUM('new', 'renew', 'upgrade', 'rent', 'insurance', 'clinic_license', 'fire_safety') NOT NULL,
    record_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(10, 3) NOT NULL,
    payment_method ENUM('cash', 'cheque', 'bank', 'link') NOT NULL,
    reference_number VARCHAR(100),
    notes TEXT,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY record_type_id (record_type, record_id)
);

-- Maintenance table
CREATE TABLE IF NOT EXISTS maintenance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_id INT NOT NULL,
    maintenance_date DATE NOT NULL,
    description TEXT,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id)
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('maintenance', 'payment', 'expiry') NOT NULL,
    reference_type ENUM('equipment', 'clinic_record') NOT NULL,
    reference_id INT NOT NULL,
    message TEXT NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('pending', 'sent', 'read') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create views for reports
CREATE OR REPLACE VIEW equipment_report AS
SELECT 
    e.*,
    p.payment_date,
    p.payment_method,
    p.amount as payment_amount,
    p.status as payment_status
FROM equipment e
LEFT JOIN payments p ON e.id = p.record_id 
WHERE p.record_type IN ('new', 'renew', 'upgrade');

CREATE OR REPLACE VIEW clinic_records_report AS
SELECT 
    cr.*,
    p.payment_date,
    p.payment_method,
    p.amount as payment_amount,
    p.status as payment_status
FROM clinic_records cr
LEFT JOIN payments p ON cr.id = p.record_id 
WHERE p.record_type IN ('rent', 'insurance', 'clinic_license', 'fire_safety');

-- Insert default admin user (password: password)
INSERT INTO users (username, password) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
