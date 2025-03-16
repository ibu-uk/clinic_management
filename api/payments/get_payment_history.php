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
    error_log("Loading payment history for type: {$type}, id: {$id}");
    
    if (!$type || !$id) {
        throw new Exception('Record type and ID are required');
    }
    
    // Adjust the query based on record type
    if ($type === 'equipment') {
        $query = "
            SELECT 
                p.payment_date,
                p.reference_no,
                COALESCE(p.amount, 0) as amount,
                p.status
            FROM payments p
            INNER JOIN equipment e ON p.equipment_id = e.id
            WHERE p.equipment_id = :id
            ORDER BY p.payment_date DESC
        ";
    } else {
        $query = "
            SELECT 
                p.payment_date,
                p.reference_no,
                COALESCE(p.amount, 0) as amount,
                p.status
            FROM payments p
            INNER JOIN clinic_records cr ON p.clinic_record_id = cr.id
            WHERE p.clinic_record_id = :id
            ORDER BY p.payment_date DESC
        ";
    }
    
    // Debug log
    error_log("Executing query: " . str_replace("\n", " ", $query));
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $id]);
    
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug log
    error_log("Found " . count($payments) . " payments");
    
    // Format numbers and dates
    foreach ($payments as &$payment) {
        $payment['amount'] = number_format((float)$payment['amount'], 3);
        $payment['payment_date'] = date('Y-m-d', strtotime($payment['payment_date']));
    }
    
    // Clear any output before sending JSON
    if (ob_get_length()) ob_clean();
    
    // Send success response
    echo json_encode([
        'success' => true,
        'payments' => $payments
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_payment_history.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clear any output before sending JSON
    if (ob_get_length()) ob_clean();
    
    // Send error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load payment history: ' . $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();
