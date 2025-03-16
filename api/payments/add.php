<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required_fields = ['record_type', 'record_id', 'payment_date', 'payment_method', 'reference_no'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("$field is required");
        }
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Get record details
    $table = $data['record_type'] === 'equipment' ? 'equipment' : 'clinic_records';
    $record_query = "SELECT * FROM $table WHERE id = ?";
    $stmt = $db->prepare($record_query);
    $stmt->execute([$data['record_id']]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        throw new Exception('Record not found');
    }
    
    // Calculate payment amount based on remaining amount
    $payment_amount = $record['remaining_amount'];
    
    // Insert payment record
    $payment_query = "INSERT INTO payments (
        record_type,
        record_id,
        payment_method,
        amount,
        payment_date,
        reference_no,
        notes,
        status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'completed')";
    
    $stmt = $db->prepare($payment_query);
    $stmt->execute([
        $data['record_type'],
        $data['record_id'],
        $data['payment_method'],
        $payment_amount,
        $data['payment_date'],
        $data['reference_no'],
        $data['notes'] ?? null
    ]);
    
    // Update record's remaining amount
    $update_query = "UPDATE $table SET 
        remaining_amount = 0,
        status = 'paid',
        next_payment_date = NULL
    WHERE id = ?";
    
    $stmt = $db->prepare($update_query);
    $stmt->execute([$data['record_id']]);
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment recorded successfully'
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
