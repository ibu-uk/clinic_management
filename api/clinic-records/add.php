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
    
    // Handle file upload
    $contract_document = null;
    if (isset($_FILES['contract_document']) && $_FILES['contract_document']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/clinic_records/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['contract_document']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'doc', 'docx'];
        
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception('Invalid file type. Only PDF, DOC, and DOCX files are allowed.');
        }
        
        $contract_document = uniqid() . '_' . $_FILES['contract_document']['name'];
        $target_file = $upload_dir . $contract_document;
        
        if (!move_uploaded_file($_FILES['contract_document']['tmp_name'], $target_file)) {
            throw new Exception('Failed to upload file');
        }
    }
    
    // Calculate payment details
    $total_cost = floatval($_POST['total_cost']);
    $payment_type = $_POST['payment_type'];
    $down_payment = 0;
    $monthly_payment = 0;
    $number_of_installments = 12;
    $remaining_amount = $total_cost;
    $next_payment_date = null;
    
    if ($payment_type === 'one_time') {
        $down_payment = $total_cost;
        $remaining_amount = 0;
    } else {
        $down_payment = floatval($_POST['down_payment']);
        $number_of_installments = intval($_POST['number_of_installments']);
        $monthly_payment = floatval($_POST['monthly_payment']);
        $remaining_amount = $total_cost - $down_payment;
        
        // Set next payment date to one month from start date
        $next_payment = new DateTime($_POST['contract_start_date']);
        $next_payment->modify('+1 month');
        $next_payment_date = $next_payment->format('Y-m-d');
    }
    
    // Insert record
    $query = "INSERT INTO clinic_records (
        record_type, company_name, contract_number, contact_number,
        contract_document, contract_start_date, contract_end_date,
        total_cost, payment_type, down_payment, number_of_installments,
        monthly_payment, remaining_amount, next_payment_date,
        status, notes, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        $_POST['record_type'],
        $_POST['company_name'],
        $_POST['contract_number'],
        $_POST['contact_number'],
        $contract_document,
        $_POST['contract_start_date'],
        $_POST['contract_end_date'],
        $total_cost,
        $payment_type,
        $down_payment,
        $number_of_installments,
        $monthly_payment,
        $remaining_amount,
        $next_payment_date,
        $remaining_amount > 0 ? 'pending' : 'paid',
        $_POST['notes'] ?? ''
    ]);
    
    $record_id = $db->lastInsertId();
    
    // Add initial payment record if down payment exists
    if ($down_payment > 0) {
        $payment_query = "INSERT INTO payments (
            record_type, record_id, payment_type, amount,
            payment_date, reference_no, status
        ) VALUES ('clinic_record', ?, ?, ?, CURRENT_DATE, ?, 'completed')";
        
        $reference_no = 'CR-DP-' . str_pad($record_id, 6, '0', STR_PAD_LEFT);
        
        $stmt = $db->prepare($payment_query);
        $stmt->execute([
            $record_id,
            $payment_type,
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
