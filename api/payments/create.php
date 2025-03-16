<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // Get form data
    $record_type = $_POST['record_type'] ?? '';
    $record_id = $_POST['record_id'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    $reference_number = $_POST['reference_number'] ?? '';
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    $installments = $_POST['installments'] ?? [];
    
    if (empty($record_type) || empty($record_id) || empty($payment_method)) {
        throw new Exception('Missing required fields');
    }
    
    if (empty($installments)) {
        throw new Exception('Please select at least one installment to pay');
    }
    
    // Calculate total payment amount
    $total_amount = 0;
    $installment_ids = [];
    
    // Get record details
    if ($record_type === 'equipment') {
        $query = "SELECT * FROM equipment WHERE id = :id AND status = 'active'";
    } else {
        $query = "SELECT * FROM clinic_records WHERE id = :id AND status = 'active'";
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $record_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        throw new Exception('Record not found');
    }
    
    // Get installments details
    $query = "SELECT * FROM monthly_installments 
              WHERE id IN (" . implode(',', array_map('intval', $installments)) . ")
              AND record_type = :type 
              AND record_id = :id
              AND status = 'pending'";
              
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':type' => $record_type,
        ':id' => $record_id
    ]);
    
    $installment_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($installment_details as $inst) {
        $total_amount += floatval($inst['amount']);
        $installment_ids[] = $inst['id'];
    }
    
    // Handle file upload
    $receipt_file = null;
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/receipts/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['receipt']['tmp_name'], $target_path)) {
            $receipt_file = 'receipts/' . $file_name;
        }
    }
    
    // Create payment record
    $query = "INSERT INTO payments (
        record_type, record_id, amount, payment_method,
        reference_number, payment_date, notes,
        status, created_at, updated_at
    ) VALUES (
        :record_type, :record_id, :amount, :payment_method,
        :reference_number, :payment_date, :notes,
        'completed', NOW(), NOW()
    )";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':record_type' => $record_type,
        ':record_id' => $record_id,
        ':amount' => $total_amount,
        ':payment_method' => $payment_method,
        ':reference_number' => $reference_number,
        ':payment_date' => $payment_date,
        ':notes' => $notes
    ]);
    
    $payment_id = $db->lastInsertId();
    
    // Update installments status
    $query = "UPDATE monthly_installments 
              SET status = 'paid', 
                  payment_id = :payment_id,
                  paid_date = :paid_date,
                  updated_at = NOW()
              WHERE id IN (" . implode(',', $installment_ids) . ")";
              
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':payment_id' => $payment_id,
        ':paid_date' => $payment_date
    ]);
    
    // Update record's remaining amount
    if ($record_type === 'equipment') {
        $query = "UPDATE equipment 
                  SET remaining_amount = remaining_amount - :amount,
                      updated_at = NOW()
                  WHERE id = :id";
    } else {
        $query = "UPDATE clinic_records 
                  SET remaining_amount = remaining_amount - :amount,
                      updated_at = NOW()
                  WHERE id = :id";
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':amount' => $total_amount,
        ':id' => $record_id
    ]);
    
    $db->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Payment recorded successfully',
        'payment_id' => $payment_id
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
