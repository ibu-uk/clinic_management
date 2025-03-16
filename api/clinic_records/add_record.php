<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    // Start transaction
    $db->beginTransaction();

    // Validate contract number uniqueness
    $check_query = "SELECT id FROM clinic_records WHERE contract_number = ?";
    $stmt = $db->prepare($check_query);
    $stmt->execute([$_POST['contract_number']]);
    
    if ($stmt->rowCount() > 0) {
        throw new Exception('Contract number already exists');
    }

    // Calculate contract end date
    $start_date = new DateTime($_POST['contract_start_date']);
    $duration = intval($_POST['contract_duration']);
    $end_date = clone $start_date;
    $end_date->modify("+{$duration} months");

    // Insert clinic record
    $query = "INSERT INTO clinic_records (
        company_name, contract_number, contract_start_date, contract_end_date,
        total_cost, payment_type, down_payment, monthly_installment, remaining_amount,
        status, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', CURRENT_TIMESTAMP)";

    $total_cost = floatval($_POST['total_cost']);
    $payment_type = $_POST['payment_type'];
    $down_payment = $payment_type === 'installment' ? floatval($_POST['down_payment']) : $total_cost;
    $monthly_installment = $payment_type === 'installment' ? floatval($_POST['monthly_installment']) : 0;
    $remaining_amount = $total_cost - $down_payment;

    $stmt = $db->prepare($query);
    $stmt->execute([
        $_POST['company_name'],
        $_POST['contract_number'],
        $start_date->format('Y-m-d'),
        $end_date->format('Y-m-d'),
        $total_cost,
        $payment_type,
        $down_payment,
        $monthly_installment,
        $remaining_amount
    ]);

    $record_id = $db->lastInsertId();

    // Add initial payment record if down payment exists
    if ($down_payment > 0) {
        $payment_query = "INSERT INTO payments (
            record_type, record_id, payment_type, amount, payment_date, reference_no, status
        ) VALUES ('clinic_record', ?, ?, ?, CURRENT_DATE, ?, 'completed')";

        $reference_no = 'DP-' . str_pad($record_id, 6, '0', STR_PAD_LEFT);
        
        $stmt = $db->prepare($payment_query);
        $stmt->execute([
            $record_id,
            'down_payment',
            $down_payment,
            $reference_no
        ]);
    }

    // Commit transaction
    $db->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Record added successfully'
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
