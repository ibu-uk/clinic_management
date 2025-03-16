<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // Get form data
    $data = $_POST;

    // Handle file upload
    $contract_file = null;
    if (isset($_FILES['contractFile']) && $_FILES['contractFile']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/clinic-records/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['contractFile']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['contractFile']['tmp_name'], $target_path)) {
            $contract_file = 'clinic-records/' . $file_name;
        }
    }

    // Handle payment logic
    $total_amount = $data['totalAmount'];
    if ($data['paymentType'] === 'one_time') {
        $down_payment = $total_amount;
        $num_installments = 1;
        $monthly_payment = $total_amount;
    } else {
        $down_payment = isset($data['downPayment']) ? $data['downPayment'] : 0;
        $num_installments = isset($data['numInstallments']) ? $data['numInstallments'] : 1;
        $remaining_amount = $total_amount - $down_payment;
        $monthly_payment = $remaining_amount / $num_installments;
    }

    // Calculate end date
    $start_date = new DateTime($data['contractDate']);
    $end_date = clone $start_date;
    $end_date->modify('+' . $num_installments . ' months');

    // Set next payment date for installment payments
    $next_payment_date = null;
    if ($data['paymentType'] === 'installment') {
        $next_payment_date = clone $start_date;
        $next_payment_date->modify('+1 month');
    }

    // Insert clinic record
    $query = "INSERT INTO clinic_records (
        record_type, company_name, contract_number, contract_date,
        expiry_date, payment_type, total_amount, down_payment,
        remaining_amount, monthly_payment, num_months,
        next_payment_date, contract_file, status
    ) VALUES (
        :record_type, :company_name, :contract_number, :contract_date,
        :expiry_date, :payment_type, :total_amount, :down_payment,
        :remaining_amount, :monthly_payment, :num_months,
        :next_payment_date, :contract_file, 'active'
    )";

    $stmt = $db->prepare($query);
    $stmt->execute([
        ':record_type' => $data['recordType'],
        ':company_name' => $data['companyName'],
        ':contract_number' => $data['contractNumber'],
        ':contract_date' => $start_date->format('Y-m-d'),
        ':expiry_date' => $end_date->format('Y-m-d'),
        ':payment_type' => $data['paymentType'],
        ':total_amount' => $total_amount,
        ':down_payment' => $down_payment,
        ':remaining_amount' => $data['paymentType'] === 'one_time' ? 0 : $total_amount - $down_payment,
        ':monthly_payment' => $monthly_payment,
        ':num_months' => $num_installments,
        ':next_payment_date' => $next_payment_date ? $next_payment_date->format('Y-m-d') : null,
        ':contract_file' => $contract_file
    ]);

    $record_id = $db->lastInsertId();

    // Generate installment records if payment type is installment
    if ($data['paymentType'] === 'installment') {
        $payment_date = clone $start_date;
        $payment_date->modify('+1 month'); // First payment after one month
        
        for ($i = 1; $i <= $num_installments; $i++) {
            $query = "INSERT INTO monthly_installments (
                record_type, record_id, installment_number, due_date, amount
            ) VALUES (
                'clinic_record', :record_id, :installment_number, :due_date, :amount
            )";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':record_id' => $record_id,
                ':installment_number' => $i,
                ':due_date' => $payment_date->format('Y-m-d'),
                ':amount' => $monthly_payment
            ]);
            
            // Create notification for payment
            $notification_date = clone $payment_date;
            $notification_date->modify('-10 days');
            
            $query = "INSERT INTO notifications (
                type, reference_id, reference_type, message, due_date, status
            ) VALUES (
                'payment', :reference_id, 'clinic_record',
                :message, :due_date, 'pending'
            )";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':reference_id' => $record_id,
                ':message' => "Payment of {$monthly_payment} KWD due for {$data['recordType']} record in 10 days",
                ':due_date' => $notification_date->format('Y-m-d')
            ]);
            
            $payment_date->modify('+1 month');
        }
    }

    // Create notification for contract expiry
    $expiry_notification_date = clone $end_date;
    $expiry_notification_date->modify('-15 days');
    
    $query = "INSERT INTO notifications (
        type, reference_id, reference_type, message, due_date, status
    ) VALUES (
        'expiry', :reference_id, 'clinic_record',
        :message, :due_date, 'pending'
    )";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':reference_id' => $record_id,
        ':message' => "Contract for {$data['recordType']} record will expire in 15 days",
        ':due_date' => $expiry_notification_date->format('Y-m-d')
    ]);

    $db->commit();
    echo json_encode(['status' => 'success', 'message' => 'Clinic record added successfully']);
} catch (Exception $e) {
    $db->rollBack();
    error_log($e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
