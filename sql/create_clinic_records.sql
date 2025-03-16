-- Create clinic_records table
CREATE TABLE IF NOT EXISTS clinic_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    record_type ENUM('Rent', 'Insurance', 'Clinic License', 'Fire Safety') NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    contract_number VARCHAR(50) UNIQUE NOT NULL,
    contact_number VARCHAR(50) NOT NULL,
    contract_document VARCHAR(255),
    contract_start_date DATE NOT NULL,
    contract_end_date DATE NOT NULL,
    total_cost DECIMAL(10,3) NOT NULL,
    payment_type ENUM('one_time', 'installment') NOT NULL,
    down_payment DECIMAL(10,3) NOT NULL DEFAULT 0,
    number_of_installments INT DEFAULT 12,
    monthly_payment DECIMAL(10,3) NOT NULL DEFAULT 0,
    remaining_amount DECIMAL(10,3) NOT NULL DEFAULT 0,
    next_payment_date DATE,
    status ENUM('paid', 'pending', 'overdue') NOT NULL DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create payments table if not exists
CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    record_type ENUM('clinic_record', 'equipment') NOT NULL,
    record_id INT NOT NULL,
    payment_type ENUM('one_time', 'installment') NOT NULL,
    amount DECIMAL(10,3) NOT NULL,
    payment_date DATE NOT NULL,
    reference_no VARCHAR(50) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);