<?php
header('Content-Type: application/json');

// Get database connection
require_once '../../config/database.php';
$database = Database::getInstance();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

try {
    // Start transaction
    $db->beginTransaction();
    
    // Insert equipment record
    $query = "INSERT INTO equipment (
        equipment_name, equipment_model, company_name, contract_number,
        contact_number, contract_type, contract_start_date, contract_end_date,
        total_cost, payment_type, down_payment, remaining_amount,
        monthly_installment, num_installments, next_payment_date,
        maintenance_schedule, contract_file, status
    ) VALUES (
        :equipment_name, :equipment_model, :company_name, :contract_number,
        :contact_number, :contract_type, :contract_start_date, :contract_end_date,
        :total_cost, :payment_type, :down_payment, :remaining_amount,
        :monthly_installment, :num_installments, :next_payment_date,
        :maintenance_schedule, :contract_file, 'active'
    )";

    $stmt = $db->prepare($query);

    // Calculate payment details
    $down_payment = $data->payment_type === 'installment' ? $data->down_payment : 0;
    $remaining_amount = $data->payment_type === 'installment' ? 
        $data->total_cost - $down_payment : 0;
    $monthly_installment = $data->payment_type === 'installment' ? 
        $remaining_amount / $data->num_installments : 0;
    $next_payment_date = $data->payment_type === 'installment' ? 
        date('Y-m-d', strtotime($data->contract_start_date . ' +1 month')) : null;

    // Bind values
    $stmt->bindParam(':equipment_name', $data->equipment_name);
    $stmt->bindParam(':equipment_model', $data->equipment_model);
    $stmt->bindParam(':company_name', $data->company_name);
    $stmt->bindParam(':contract_number', $data->contract_number);
    $stmt->bindParam(':contact_number', $data->contact_number);
    $stmt->bindParam(':contract_type', $data->contract_type);
    $stmt->bindParam(':contract_start_date', $data->contract_start_date);
    $stmt->bindParam(':contract_end_date', $data->contract_end_date);
    $stmt->bindParam(':total_cost', $data->total_cost);
    $stmt->bindParam(':payment_type', $data->payment_type);
    $stmt->bindParam(':down_payment', $down_payment);
    $stmt->bindParam(':remaining_amount', $remaining_amount);
    $stmt->bindParam(':monthly_installment', $monthly_installment);
    $stmt->bindParam(':num_installments', $data->num_installments);
    $stmt->bindParam(':next_payment_date', $next_payment_date);
    $stmt->bindParam(':maintenance_schedule', $data->maintenance_schedule);
    $stmt->bindParam(':contract_file', $data->contract_file);

    if (!$stmt->execute()) {
        throw new Exception("Error saving equipment record");
    }

    $equipment_id = $db->lastInsertId();

    // If one-time payment, create payment record
    if ($data->payment_type === 'one_time') {
        $payment_query = "INSERT INTO payments (
            record_type, record_id, payment_date, amount,
            payment_method, reference_number, status
        ) VALUES (
            :record_type, :record_id, :payment_date, :amount,
            :payment_method, :reference_number, :status
        )";

        $payment_stmt = $db->prepare($payment_query);
        $payment_stmt->bindValue(':record_type', 'new');
        $payment_stmt->bindValue(':record_id', $equipment_id);
        $payment_stmt->bindValue(':payment_date', date('Y-m-d'));
        $payment_stmt->bindValue(':amount', $data->total_cost);
        $payment_stmt->bindValue(':payment_method', $data->payment_method);
        $payment_stmt->bindValue(':reference_number', $data->reference_number);
        $payment_stmt->bindValue(':status', 'completed');

        if (!$payment_stmt->execute()) {
            throw new Exception("Error saving payment record");
        }
    }

    // Commit transaction
    $db->commit();

    // Return success response
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "Equipment was saved successfully."
    ));
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollBack();
    
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => $e->getMessage()
    ));
}
?>
