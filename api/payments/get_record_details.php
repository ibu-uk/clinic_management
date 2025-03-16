<?php
// Start output buffering
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    require_once '../../config/database.php';
    
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    $type = strtolower($_GET['type'] ?? '');
    $id = $_GET['id'] ?? '';
    
    // Debug log
    error_log("Loading record details for type: {$type}, id: {$id}");
    
    if (!$type || !$id) {
        throw new Exception('Record type and ID are required');
    }
    
    // Get record details
    if ($type === 'equipment') {
        $query = "
            SELECT 
                e.id,
                e.equipment_name as name,
                e.contract_number,
                e.contract_type,
                COALESCE(e.total_cost, 0) as total_cost,
                COALESCE(e.remaining_amount, 0) as remaining_amount,
                e.payment_type,
                COALESCE(e.downpayment, 0) as down_payment,
                CASE 
                    WHEN e.payment_type = 'installment' THEN COALESCE(e.monthly_installment, 0)
                    ELSE NULL 
                END as monthly_payment,
                CASE 
                    WHEN e.payment_type = 'installment' THEN e.next_payment_date 
                    ELSE NULL 
                END as next_payment_date,
                e.status
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
                cr.record_type,
                COALESCE(cr.total_cost, 0) as total_cost,
                COALESCE(cr.remaining_amount, 0) as remaining_amount,
                cr.payment_type,
                COALESCE(cr.down_payment, 0) as down_payment,
                CASE 
                    WHEN cr.payment_type = 'installment' THEN COALESCE(cr.monthly_payment, 0)
                    ELSE NULL 
                END as monthly_payment,
                CASE 
                    WHEN cr.payment_type = 'installment' THEN cr.next_payment_date 
                    ELSE NULL 
                END as next_payment_date,
                cr.status
            FROM clinic_records cr
            WHERE cr.id = :id
            AND cr.status != 'paid'
        ";
    }
    
    // Get record details
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        throw new Exception('Record not found');
    }
    
    // Format numbers
    foreach (['total_cost', 'remaining_amount', 'monthly_payment', 'down_payment'] as $field) {
        if (isset($record[$field])) {
            if (!is_numeric($record[$field])) {
                $record[$field] = '0.000';
            } else {
                $record[$field] = number_format((float)$record[$field], 3, '.', '');
            }
        }
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'total_cost' => $record['total_cost'],
        'remaining_amount' => $record['remaining_amount'],
        'monthly_payment' => $record['monthly_payment'],
        'next_payment_date' => $record['next_payment_date']
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_record_details.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clear any output before sending error
    if (ob_get_length()) ob_clean();
    
    // Send error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();
