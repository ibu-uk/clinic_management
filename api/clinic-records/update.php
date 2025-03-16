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
    
    // Get current record
    $query = "SELECT * FROM clinic_records WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_POST['id']]);
    $current_record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_record) {
        throw new Exception('Record not found');
    }
    
    // Check if contract number is changed and validate uniqueness
    if ($_POST['contract_number'] !== $current_record['contract_number']) {
        $check_query = "SELECT id FROM clinic_records WHERE contract_number = ? AND id != ?";
        $stmt = $db->prepare($check_query);
        $stmt->execute([$_POST['contract_number'], $_POST['id']]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception('Contract number already exists');
        }
    }
    
    // Handle file upload
    $contract_document = $current_record['contract_document'];
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
        
        // Delete old file if exists
        if ($contract_document && file_exists($upload_dir . $contract_document)) {
            unlink($upload_dir . $contract_document);
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
        
        // Set next payment date to one month from start date if it's a new installment setup
        if ($payment_type !== $current_record['payment_type'] || $total_cost !== floatval($current_record['total_cost'])) {
            $next_payment = new DateTime($_POST['contract_start_date']);
            $next_payment->modify('+1 month');
            $next_payment_date = $next_payment->format('Y-m-d');
        } else {
            $next_payment_date = $current_record['next_payment_date'];
        }
    }
    
    // Update record
    $query = "UPDATE clinic_records SET 
        record_type = ?,
        company_name = ?,
        contract_number = ?,
        contact_number = ?,
        contract_document = ?,
        contract_start_date = ?,
        contract_end_date = ?,
        total_cost = ?,
        payment_type = ?,
        down_payment = ?,
        number_of_installments = ?,
        monthly_payment = ?,
        remaining_amount = ?,
        next_payment_date = ?,
        status = ?,
        notes = ?
    WHERE id = ?";
    
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
        $_POST['notes'] ?? '',
        $_POST['id']
    ]);
    
    // If payment type or amounts changed, add a note in the payments table
    if ($payment_type !== $current_record['payment_type'] || 
        $total_cost !== floatval($current_record['total_cost']) || 
        $down_payment !== floatval($current_record['down_payment'])) {
        
        $note = "Contract terms updated: " . 
                "Payment type changed from {$current_record['payment_type']} to {$payment_type}, " .
                "Total cost changed from {$current_record['total_cost']} to {$total_cost}, " .
                "Down payment changed from {$current_record['down_payment']} to {$down_payment}";
        
        $payment_query = "INSERT INTO payments (
            record_type, record_id, payment_type, amount,
            payment_date, reference_no, status, notes
        ) VALUES ('clinic_record', ?, ?, 0, CURRENT_DATE, ?, 'completed', ?)";
        
        $reference_no = 'CR-UPDATE-' . str_pad($_POST['id'], 6, '0', STR_PAD_LEFT);
        
        $stmt = $db->prepare($payment_query);
        $stmt->execute([
            $_POST['id'],
            $payment_type,
            $reference_no,
            $note
        ]);
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
