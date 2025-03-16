<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Admin privileges required.'
    ]);
    exit;
}

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    // Create equipment installments table
    $sql = "
    CREATE TABLE IF NOT EXISTS equipment_installments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        equipment_id INT NOT NULL,
        due_date DATE NOT NULL,
        amount DECIMAL(10,3) NOT NULL,
        status ENUM('paid', 'pending', 'overdue') DEFAULT 'pending',
        payment_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
        FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($sql);
    
    // Create clinic installments table
    $sql = "
    CREATE TABLE IF NOT EXISTS clinic_installments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        record_id INT NOT NULL,
        due_date DATE NOT NULL,
        amount DECIMAL(10,3) NOT NULL,
        status ENUM('paid', 'pending', 'overdue') DEFAULT 'pending',
        payment_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (record_id) REFERENCES clinic_records(id) ON DELETE CASCADE,
        FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($sql);
    
    // Add installment date columns
    $sql = "
    -- Add columns to equipment table if they don't exist
    ALTER TABLE equipment 
    ADD COLUMN IF NOT EXISTS installment_start_date DATE DEFAULT CURRENT_DATE,
    ADD COLUMN IF NOT EXISTS installment_months INT DEFAULT 12;

    -- Add columns to clinic_records table if they don't exist
    ALTER TABLE clinic_records 
    ADD COLUMN IF NOT EXISTS payment_start_date DATE DEFAULT CURRENT_DATE,
    ADD COLUMN IF NOT EXISTS payment_months INT DEFAULT 12;
    ";
    $pdo->exec($sql);
    
    echo json_encode([
        'success' => true,
        'message' => 'Database tables and columns created successfully'
    ]);
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Error executing migrations: ' . $e->getMessage()
    ]);
}
