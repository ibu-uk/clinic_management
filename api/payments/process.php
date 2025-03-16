<?php
// Define base path
define('BASEPATH', dirname(dirname(dirname(__FILE__))));

// Prevent any unwanted output
ob_start();

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once BASEPATH . '/config/database.php';

    // Get and validate POST data
    $record_type = filter_input(INPUT_POST, 'record_type', FILTER_SANITIZE_STRING);
    $record_id = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);
    $payment_date = filter_input(INPUT_POST, 'payment_date', FILTER_SANITIZE_STRING);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    $reference_number = filter_input(INPUT_POST, 'reference_number', FILTER_SANITIZE_STRING);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
    
    // Get and validate installment IDs
    $installment_ids_json = filter_input(INPUT_POST, 'installment_ids');
    if (!$installment_ids_json) {
        throw new Exception('No installments selected');
    }
    
    $installment_ids = json_decode($installment_ids_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid installment IDs format');
    }
    
    if (empty($installment_ids)) {
        throw new Exception('No installments selected');
    }
    
    // Validate required fields
    if (!$record_type || !$record_id || !$payment_date || !$payment_method) {
        throw new Exception('Missing required fields');
    }
    
    // Debug log
    error_log("Processing payment with data: " . json_encode([
        'record_type' => $record_type,
        'record_id' => $record_id,
        'payment_date' => $payment_date,
        'payment_method' => $payment_method,
        'reference_number' => $reference_number,
        'notes' => $notes,
        'installment_ids' => $installment_ids
    ]));

    // Initialize database connection
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Verify installments exist and are pending
        $placeholders = str_repeat('?,', count($installment_ids) - 1) . '?';
        $query = "
            SELECT id, amount, status 
            FROM monthly_installments 
            WHERE id IN ($placeholders)
            AND record_type = ?
            AND record_id = ?
            AND status = 'pending'
        ";
        
        $params = array_merge($installment_ids, [$record_type, $record_id]);
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $installments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($installments) !== count($installment_ids)) {
            throw new Exception('Some selected installments are not valid or already paid');
        }
        
        // Calculate total amount
        $total_amount = array_reduce($installments, function($sum, $item) {
            return $sum + floatval($item['amount']);
        }, 0);
        
        // Create payment record
        $query = "
            INSERT INTO payments (
                record_type, 
                record_id, 
                payment_date, 
                payment_method, 
                amount, 
                reference_number, 
                notes, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $record_type,
            $record_id,
            $payment_date,
            $payment_method,
            $total_amount,
            $reference_number,
            $notes
        ]);
        
        $payment_id = $pdo->lastInsertId();
        
        // Update installments
        $query = "
            UPDATE monthly_installments 
            SET 
                status = 'paid',
                payment_id = ?,
                payment_date = ?,
                payment_method = ?,
                updated_at = NOW()
            WHERE id IN ($placeholders)
        ";
        
        $params = array_merge([$payment_id, $payment_date, $payment_method], $installment_ids);
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'payment_id' => $payment_id
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log the error with full details
    error_log("Payment Processing Error: " . $e->getMessage());
    error_log("Error Trace: " . $e->getTraceAsString());
    
    // Clear any existing output
    if (ob_get_length()) ob_clean();
    
    // Send error response with more details
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process payment: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

// End output and exit
ob_end_flush();
exit;
