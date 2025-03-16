<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get posted data
    $data = json_decode(file_get_contents("php://input"));
    
    // Start transaction
    $db->beginTransaction();
    
    // Insert payment record
    $payment_query = "INSERT INTO payments (
        record_type, record_id, payment_date, amount,
        payment_method, reference_number, notes, status
    ) VALUES (
        :record_type, :record_id, :payment_date, :amount,
        :payment_method, :reference_number, :notes, 'completed'
    )";
    
    $payment_stmt = $db->prepare($payment_query);
    $payment_stmt->bindParam(':record_type', $data->record_type);
    $payment_stmt->bindParam(':record_id', $data->record_id);
    $payment_stmt->bindParam(':payment_date', $data->payment_date);
    $payment_stmt->bindParam(':amount', $data->amount);
    $payment_stmt->bindParam(':payment_method', $data->payment_method);
    $payment_stmt->bindParam(':reference_number', $data->reference_number);
    $payment_stmt->bindParam(':notes', $data->notes);
    
    if (!$payment_stmt->execute()) {
        throw new Exception("Error saving payment record");
    }
    
    // Update remaining amount in the respective table
    if ($data->record_type === 'equipment') {
        $update_query = "UPDATE equipment SET 
            remaining_amount = remaining_amount - :amount,
            next_payment_date = DATE_ADD(:payment_date, INTERVAL 1 MONTH)
        WHERE id = :id";
    } else {
        $update_query = "UPDATE clinic_records SET 
            remaining_amount = remaining_amount - :amount,
            next_payment_date = DATE_ADD(:payment_date, INTERVAL 1 MONTH)
        WHERE id = :id";
    }
    
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':amount', $data->amount);
    $update_stmt->bindParam(':payment_date', $data->payment_date);
    $update_stmt->bindParam(':id', $data->record_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Error updating record");
    }
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment recorded successfully'
    ]);

} catch(Exception $e) {
    // Rollback transaction on error
    $db->rollBack();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
