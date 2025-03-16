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
    require_once '../../config/database.php';
    
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    // Initialize database connection
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    // Get and validate parameters
    $type = strtolower($_GET['type'] ?? '');
    $subtype = strtolower($_GET['subtype'] ?? '');
    
    if (!$type || !$subtype) {
        throw new Exception('Record type and subtype are required');
    }
    
    // Debug log
    error_log("Loading records for type: {$type}, subtype: {$subtype}");
    
    // Prepare query based on record type
    if ($type === 'equipment') {
        // For equipment records, use contract_type directly
        $query = "
            SELECT 
                e.id,
                e.equipment_name as name,
                e.equipment_model,
                e.company_name,
                e.contract_number,
                COALESCE(e.total_cost, 0) as total_cost,
                CASE 
                    WHEN e.payment_type = 'one_time' THEN 
                        COALESCE(e.total_cost - (
                            SELECT COALESCE(SUM(p.amount), 0) 
                            FROM payments p 
                            WHERE p.record_id = e.id 
                            AND p.record_type = 'equipment'
                        ), e.total_cost)
                    ELSE 
                        COALESCE(e.remaining_amount, 0)
                END as remaining_amount,
                e.payment_type,
                e.status,
                e.contract_type
            FROM equipment e
            WHERE e.contract_type = :subtype
            AND e.status != 'terminated'
            AND (
                (e.payment_type = 'installment' AND e.remaining_amount > 0)
                OR 
                (e.payment_type = 'one_time')
            )
            ORDER BY e.id DESC
        ";
        
        $params = ['subtype' => $subtype];
        
    } else if ($type === 'clinic') {
        // For clinic records, match the exact record types
        $record_type_map = [
            'rent' => 'Rent',
            'insurance' => 'Insurance',
            'clinic_license' => 'Clinic License',
            'fire_safety' => 'Fire Safety'
        ];
        
        if (!isset($record_type_map[$subtype])) {
            throw new Exception('Invalid clinic record subtype');
        }
        
        $query = "
            SELECT 
                cr.id,
                CONCAT(cr.record_type, ' - ', cr.company_name) as name,
                cr.company_name,
                cr.contract_number,
                COALESCE(cr.total_cost, 0) as total_cost,
                CASE 
                    WHEN cr.payment_type = 'one_time' THEN 
                        COALESCE(cr.total_cost - (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE record_id = cr.id AND record_type = :subtype), cr.total_cost)
                    ELSE 
                        COALESCE(cr.remaining_amount, 0)
                END as remaining_amount,
                cr.payment_type,
                cr.status
            FROM clinic_records cr
            WHERE cr.record_type = :subtype
            AND cr.status != 'paid'
            AND (
                (cr.payment_type = 'installment' AND cr.remaining_amount > 0)
                OR 
                (cr.payment_type = 'one_time' AND (
                    SELECT COALESCE(SUM(amount), 0) 
                    FROM payments 
                    WHERE record_id = cr.id 
                    AND record_type = :subtype
                ) < cr.total_cost)
            )
            ORDER BY cr.id DESC
        ";
        
        $params = ['subtype' => $record_type_map[$subtype]];
        
    } else {
        throw new Exception('Invalid record type');
    }
    
    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format currency values
    foreach ($records as &$record) {
        $record['total_cost'] = number_format((float)$record['total_cost'], 3, '.', '');
        $record['remaining_amount'] = number_format((float)$record['remaining_amount'], 3, '.', '');
    }
    
    // Debug log
    error_log("Found " . count($records) . " records");
    
    // Return success response
    echo json_encode([
        'success' => true,
        'records' => $records
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_records.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clear any output before sending error
    if (ob_get_length()) ob_clean();
    
    // Send error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load records: ' . $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();
