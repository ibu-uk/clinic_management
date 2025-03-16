<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    $type = $_GET['type'] ?? '';
    $id = $_GET['id'] ?? '';
    
    if (!$type || !$id) {
        throw new Exception('Missing required parameters');
    }
    
    if ($type === 'equipment') {
        $query = "SELECT 
            id,
            equipment_name as name,
            contract_number,
            total_cost as total_amount,
            remaining_amount,
            monthly_installment,
            next_payment_date
        FROM equipment 
        WHERE id = :id";
    } else {
        $query = "SELECT 
            id,
            company_name as name,
            contract_number,
            total_amount,
            remaining_amount,
            monthly_payment as monthly_installment,
            next_payment_date
        FROM clinic_records 
        WHERE id = :id";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        throw new Exception('Record not found');
    }
    
    // Get payment history
    $payment_query = "SELECT 
        payment_date,
        amount,
        payment_method,
        reference_number,
        status
    FROM payments 
    WHERE record_type = :record_type 
    AND record_id = :record_id
    ORDER BY payment_date DESC";
    
    $payment_stmt = $db->prepare($payment_query);
    $payment_stmt->bindValue(':record_type', $type);
    $payment_stmt->bindValue(':record_id', $id);
    $payment_stmt->execute();
    
    $record['payment_history'] = $payment_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $record
    ]);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
