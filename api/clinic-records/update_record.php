<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['id'])) {
        throw new Exception('Record ID is required');
    }

    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    // Check if contract number is unique (excluding current record)
    $check_query = "SELECT id FROM clinic_records WHERE contract_number = ? AND id != ?";
    $stmt = $db->prepare($check_query);
    $stmt->execute([$_POST['contractNumber'], $_POST['id']]);
    
    if ($stmt->rowCount() > 0) {
        throw new Exception('Contract number already exists');
    }
    
    // Calculate expiry date
    $contract_date = new DateTime($_POST['contractDate']);
    $duration = intval($_POST['contractDuration']);
    $expiry_date = clone $contract_date;
    $expiry_date->modify("+{$duration} months");
    
    // Calculate payment details
    $total_cost = floatval($_POST['totalCost']);
    $payment_type = $_POST['paymentType'];
    $down_payment = $payment_type === 'installment' ? floatval($_POST['downPayment']) : $total_cost;
    $monthly_installment = $payment_type === 'installment' ? floatval($_POST['monthlyInstallment']) : 0;
    
    // Get current record to check if payment details changed
    $query = "SELECT * FROM clinic_records WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_POST['id']]);
    $current_record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get total paid amount
    $query = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments 
              WHERE record_type = 'clinic_record' AND record_id = ? AND status = 'completed'";
    $stmt = $db->prepare($query);
    $stmt->execute([$_POST['id']]);
    $total_paid = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total_paid']);
    
    // Calculate remaining amount
    $remaining_amount = $total_cost - $total_paid;
    
    // Update record
    $query = "UPDATE clinic_records SET 
        record_type = ?,
        company_name = ?,
        contract_number = ?,
        contract_date = ?,
        expiry_date = ?,
        total_cost = ?,
        payment_type = ?,
        down_payment = ?,
        monthly_installment = ?,
        remaining_amount = ?,
        payment_status = ?,
        renewal_status = ?,
        notes = ?,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        $_POST['recordType'],
        $_POST['companyName'],
        $_POST['contractNumber'],
        $contract_date->format('Y-m-d'),
        $expiry_date->format('Y-m-d'),
        $total_cost,
        $payment_type,
        $down_payment,
        $monthly_installment,
        $remaining_amount,
        $remaining_amount > 0 ? 'pending' : 'paid',
        'active',
        $_POST['notes'] ?? '',
        $_POST['id']
    ]);
    
    // If payment type or amount changed, we need to update the next payment date
    if ($payment_type === 'installment' && $remaining_amount > 0) {
        $next_payment = new DateTime();
        $next_payment->modify('+1 month');
        
        $update_query = "UPDATE clinic_records SET next_payment_date = ? WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $stmt->execute([$next_payment->format('Y-m-d'), $_POST['id']]);
    }
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Record updated successfully'
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
