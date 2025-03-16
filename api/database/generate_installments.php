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
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Generate equipment installments
    $sql = "SELECT 
                id, 
                total_cost,
                down_payment,
                monthly_installment,
                COALESCE(installment_start_date, contract_date, CURRENT_DATE) as start_date,
                COALESCE(installment_months, 12) as num_months
            FROM equipment 
            WHERE payment_type = 'installment' 
            AND status != 'terminated'";
            
    $stmt = $pdo->query($sql);
    $equipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($equipments as $equipment) {
        // Calculate number of installments
        $totalAmount = $equipment['total_cost'] - $equipment['down_payment'];
        $monthlyAmount = $equipment['monthly_installment'];
        $startDate = new DateTime($equipment['start_date']);
        $numInstallments = $equipment['num_months'];
        
        // Delete existing installments
        $pdo->prepare("DELETE FROM equipment_installments WHERE equipment_id = ?")->execute([$equipment['id']]);
        
        // Generate installments
        $insertStmt = $pdo->prepare("
            INSERT INTO equipment_installments (equipment_id, due_date, amount, status) 
            VALUES (?, ?, ?, ?)
        ");
        
        for ($i = 0; $i < $numInstallments; $i++) {
            $dueDate = clone $startDate;
            $dueDate->modify("+$i months");
            $status = $dueDate < new DateTime() ? 'overdue' : 'pending';
            
            $insertStmt->execute([
                $equipment['id'],
                $dueDate->format('Y-m-d'),
                $monthlyAmount,
                $status
            ]);
        }
    }
    
    // Generate clinic installments
    $sql = "SELECT 
                id, 
                total_cost,
                down_payment,
                monthly_payment,
                COALESCE(payment_start_date, contract_date, CURRENT_DATE) as start_date,
                COALESCE(payment_months, 12) as num_months
            FROM clinic_records 
            WHERE payment_type = 'installment' 
            AND status != 'paid'";
            
    $stmt = $pdo->query($sql);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($records as $record) {
        // Calculate number of installments
        $totalAmount = $record['total_cost'] - $record['down_payment'];
        $monthlyAmount = $record['monthly_payment'];
        $startDate = new DateTime($record['start_date']);
        $numInstallments = $record['num_months'];
        
        // Delete existing installments
        $pdo->prepare("DELETE FROM clinic_installments WHERE record_id = ?")->execute([$record['id']]);
        
        // Generate installments
        $insertStmt = $pdo->prepare("
            INSERT INTO clinic_installments (record_id, due_date, amount, status) 
            VALUES (?, ?, ?, ?)
        ");
        
        for ($i = 0; $i < $numInstallments; $i++) {
            $dueDate = clone $startDate;
            $dueDate->modify("+$i months");
            $status = $dueDate < new DateTime() ? 'overdue' : 'pending';
            
            $insertStmt->execute([
                $record['id'],
                $dueDate->format('Y-m-d'),
                $monthlyAmount,
                $status
            ]);
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Installments generated successfully'
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Error generating installments: ' . $e->getMessage()
    ]);
}
