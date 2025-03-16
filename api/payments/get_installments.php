<?php
// Start output buffering
ob_start();

// Disable error reporting for output
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Include required files
    require_once '../../config/config.php';
    require_once '../../includes/auth_validate.php';
    require_once '../../includes/Database.php';
    
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    // Get and validate parameters
    $record_type = filter_input(INPUT_GET, 'record_type', FILTER_SANITIZE_STRING);
    $record_id = filter_input(INPUT_GET, 'record_id', FILTER_VALIDATE_INT);
    
    if (!$record_type || !$record_id) {
        throw new Exception('Record type and ID are required');
    }
    
    // Debug log
    error_log("Loading installments for: record_type={$record_type}, record_id={$record_id}");
    
    // Initialize database connection
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get record details based on type
    if ($record_type === 'equipment') {
        $query = "
            SELECT 
                e.id,
                e.equipment_name as name,
                e.contract_number,
                e.total_cost as total_amount,
                COALESCE(e.down_payment, 0) as down_payment,
                COALESCE(e.remaining_amount, 0) as remaining_amount,
                e.status,
                e.contract_type as record_type
            FROM equipment e
            WHERE e.id = :id
            AND e.status != 'terminated'
        ";
    } else {
        $query = "
            SELECT 
                cr.id,
                cr.company_name as name,
                cr.contract_number,
                cr.total_cost as total_amount,
                COALESCE(cr.down_payment, 0) as down_payment,
                COALESCE(cr.remaining_amount, 0) as remaining_amount,
                cr.status,
                cr.record_type
            FROM clinic_records cr
            WHERE cr.id = :id
            AND cr.status != 'paid'
        ";
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $record_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        throw new Exception('Record not found or not active');
    }
    
    // Get all installments for the record
    $query = "
        SELECT 
            mi.id as installment_id,
            mi.due_date,
            mi.amount,
            mi.status,
            mi.paid_date as payment_date,
            COALESCE(p.payment_method, '-') as payment_method,
            COALESCE(p.reference_number, '-') as reference_number
        FROM monthly_installments mi
        LEFT JOIN payments p ON mi.payment_id = p.id
        WHERE mi.record_type = :record_type 
        AND mi.record_id = :record_id
        ORDER BY mi.due_date ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':record_type' => $record_type,
        ':record_id' => $record_id
    ]);
    $installments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug log
    error_log("SQL Query: " . preg_replace('/\s+/', ' ', $query));
    error_log("Found " . count($installments) . " installments");
    
    // Format installment amounts and dates
    foreach ($installments as &$installment) {
        $installment['amount'] = number_format(floatval($installment['amount']), 3, '.', '');
        $installment['due_date'] = date('Y-m-d', strtotime($installment['due_date']));
        $installment['payment_date'] = $installment['payment_date'] ? date('Y-m-d', strtotime($installment['payment_date'])) : null;
        $installment['installment_number'] = $installment['installment_id']; // Add for compatibility
    }
    
    // Clear any output before sending JSON
    if (ob_get_length()) ob_clean();
    
    // Send response
    $response = json_encode([
        'success' => true,
        'data' => $installments
    ]);
    
    if ($response === false) {
        throw new Exception('Failed to encode JSON response: ' . json_last_error_msg());
    }
    
    echo $response;
    
} catch (Exception $e) {
    error_log("Error in get_installments.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clear any output before sending JSON
    if (ob_get_length()) ob_clean();
    
    // Send error response
    $error_response = json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    if ($error_response === false) {
        error_log("Failed to encode error response: " . json_last_error_msg());
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error'
        ]);
    } else {
        echo $error_response;
    }
}

// End output buffering and send response
ob_end_flush();
