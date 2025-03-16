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
    
    // Get JSON POST data
    $json = file_get_contents('php://input');
    error_log("Received payment request: " . $json); // Debug log
    
    $postData = json_decode($json, true);
    if (!$postData) {
        throw new Exception('Invalid request data: ' . json_last_error_msg());
    }
    
    // Get and validate input data
    $record_type = $postData['record_type'] ?? '';
    $record_id = $postData['record_id'] ?? '';
    $payment_date = $postData['payment_date'] ?? '';
    $payment_method = $postData['payment_method'] ?? '';
    $reference_number = $postData['reference_number'] ?? '';
    $description = $postData['description'] ?? '';
    $amount = floatval($postData['amount'] ?? 0);
    $installments = $postData['installments'] ?? [];
    $installment_start_date = $postData['installment_start_date'] ?? null;
    
    // Map record type
    if ($record_type === 'clinic') {
        $record_type = 'clinic_record';
    }
    
    // Debug log
    error_log("Processing payment: type={$record_type}, id={$record_id}, amount={$amount}");
    
    // Validate required fields
    if (empty($record_type) || empty($record_id) || empty($payment_date) || 
        empty($payment_method) || $amount <= 0) {
        throw new Exception('Missing required fields');
    }
    
    // Initialize database connection
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Get record details to verify payment amount
        if ($record_type === 'equipment') {
            $query = "SELECT 
                payment_type, 
                CASE 
                    WHEN payment_type = 'one_time' THEN 
                        total_cost - COALESCE((
                            SELECT SUM(amount) 
                            FROM payments 
                            WHERE record_type = 'equipment' 
                            AND record_id = equipment.id
                            AND status = 'completed'
                        ), 0)
                    ELSE remaining_amount
                END as remaining_amount,
                monthly_installment, 
                total_cost 
                FROM equipment 
                WHERE id = ? AND status != 'terminated'";
        } else {
            $query = "SELECT payment_type, remaining_amount, monthly_payment as monthly_installment, total_cost 
                     FROM clinic_records 
                     WHERE id = ? AND status != 'paid'";
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$record_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$record) {
            throw new Exception('Record not found or already completed');
        }
        
        // Debug log
        error_log("Record details: " . print_r($record, true));
        
        // Validate payment amount
        if ($record['payment_type'] === 'one_time') {
            $remaining = floatval($record['remaining_amount']);
            if ($amount > $remaining) {
                throw new Exception("Payment amount ({$amount}) exceeds remaining balance ({$remaining})");
            }
        } else {
            if (!empty($installments)) {
                $totalInstallmentAmount = array_reduce($installments, function($sum, $item) {
                    return $sum + floatval($item['amount']);
                }, 0);
                if ($totalInstallmentAmount != $record['monthly_installment']) {
                    throw new Exception('Invalid installment amount');
                }
            } elseif ($amount != $record['monthly_installment']) {
                throw new Exception('Invalid installment amount');
            }
        }
        
        // Create payment record
        $query = "INSERT INTO payments (
            record_type,
            record_id,
            amount,
            payment_date,
            reference_no,
            status,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, 'completed', NOW(), NOW())";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $record_type,
            $record_id,
            $amount,
            $payment_date,
            $reference_number
        ]);
        
        $payment_id = $pdo->lastInsertId();
        
        // Calculate new remaining amount
        $new_remaining = $record['remaining_amount'] - $amount;
        
        // Update record's remaining amount and status
        if ($record_type === 'equipment') {
            // For equipment records
            $query = "UPDATE equipment 
                     SET remaining_amount = CASE 
                            WHEN payment_type = 'one_time' THEN 
                                total_cost - COALESCE((
                                    SELECT SUM(amount) 
                                    FROM payments 
                                    WHERE record_type = 'equipment' 
                                    AND record_id = equipment.id 
                                    AND status = 'completed'
                                ), 0)
                            ELSE 
                                total_cost - down_payment - COALESCE((
                                    SELECT SUM(amount) 
                                    FROM payments 
                                    WHERE record_type = 'equipment' 
                                    AND record_id = equipment.id 
                                    AND status = 'completed'
                                ), 0)
                         END,
                         status = CASE 
                            WHEN payment_type = 'one_time' AND (
                                total_cost <= COALESCE((
                                    SELECT SUM(amount) 
                                    FROM payments 
                                    WHERE record_type = 'equipment' 
                                    AND record_id = equipment.id 
                                    AND status = 'completed'
                                ), 0)
                            ) THEN 'paid'
                            WHEN payment_type = 'installment' AND (
                                (total_cost - down_payment) <= COALESCE((
                                    SELECT SUM(amount) 
                                    FROM payments 
                                    WHERE record_type = 'equipment' 
                                    AND record_id = equipment.id 
                                    AND status = 'completed'
                                ), 0)
                            ) THEN 'paid'
                            ELSE status 
                         END,
                         next_payment_date = CASE 
                            WHEN payment_type = 'installment' AND (
                                total_cost - down_payment - COALESCE((
                                    SELECT SUM(amount) 
                                    FROM payments 
                                    WHERE record_type = 'equipment' 
                                    AND record_id = equipment.id 
                                    AND status = 'completed'
                                ), 0)
                            ) > 0 
                            THEN DATE_ADD(?, INTERVAL 1 MONTH)
                            ELSE NULL 
                         END,
                         updated_at = NOW()
                     WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$payment_date, $record_id]);
        } else {
            // For clinic records
            $query = "UPDATE clinic_records 
                     SET remaining_amount = CASE 
                            WHEN payment_type = 'one_time' THEN 
                                total_cost - COALESCE((
                                    SELECT SUM(amount) 
                                    FROM payments 
                                    WHERE record_type = :record_type 
                                    AND record_id = clinic_records.id 
                                    AND status = 'completed'
                                ), 0)
                            ELSE 
                                total_cost - down_payment - COALESCE((
                                    SELECT SUM(amount) 
                                    FROM payments 
                                    WHERE record_type = :record_type 
                                    AND record_id = clinic_records.id 
                                    AND status = 'completed'
                                ), 0)
                         END,
                         status = CASE 
                            WHEN payment_type = 'one_time' AND (
                                total_cost <= COALESCE((
                                    SELECT SUM(amount) 
                                    FROM payments 
                                    WHERE record_type = :record_type 
                                    AND record_id = clinic_records.id 
                                    AND status = 'completed'
                                ), 0)
                            ) THEN 'paid'
                            WHEN payment_type = 'installment' AND (
                                (total_cost - down_payment) <= COALESCE((
                                    SELECT SUM(amount) 
                                    FROM payments 
                                    WHERE record_type = :record_type 
                                    AND record_id = clinic_records.id 
                                    AND status = 'completed'
                                ), 0)
                            ) THEN 'paid'
                            ELSE status 
                         END,
                         next_payment_date = CASE 
                            WHEN payment_type = 'installment' AND (
                                total_cost - down_payment - COALESCE((
                                    SELECT SUM(amount) 
                                    FROM payments 
                                    WHERE record_type = :record_type 
                                    AND record_id = clinic_records.id 
                                    AND status = 'completed'
                                ), 0)
                            ) > 0 
                            THEN DATE_ADD(:payment_date, INTERVAL 1 MONTH)
                            ELSE NULL 
                         END,
                         updated_at = NOW()
                     WHERE id = :record_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'record_type' => $record_type,
                'payment_date' => $payment_date,
                'record_id' => $record_id
            ]);
        }
        
        // If this is an installment payment, create installment record
        if ($record['payment_type'] === 'installment') {
            if (!empty($installments)) {
                // Calculate the start date for installments
                $start_date = !empty($installment_start_date) ? $installment_start_date : date('Y-m-d');
                
                $query = "INSERT INTO monthly_installments (
                    record_type,
                    record_id,
                    installment_start_date,
                    due_date,
                    amount,
                    status,
                    description
                ) VALUES ";
                
                $values = [];
                $params = [];
                
                foreach ($installments as $i => $installment) {
                    // Calculate due date based on start date and installment number
                    $due_date = date('Y-m-d', strtotime($start_date . ' + ' . $i . ' months'));
                    
                    $values[] = "(?, ?, ?, ?, ?, 'pending', ?)";
                    $params = array_merge($params, [
                        $record_type,
                        $record_id,
                        $start_date,
                        $due_date,
                        $installment['amount'],
                        $installment['description'] ?? null
                    ]);
                }
                
                $query .= implode(', ', $values);
                
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Debug log
        error_log("Payment processed successfully: payment_id={$payment_id}");
        
        // Send success response
        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'data' => [
                'payment_id' => $payment_id,
                'amount' => number_format($amount, 3, '.', ''),
                'remaining_amount' => number_format($record['remaining_amount'] - $amount, 3, '.', '')
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in process_payment.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clear any output before sending JSON
    if (ob_get_length()) ob_clean();
    
    // Send error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process payment: ' . $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();
