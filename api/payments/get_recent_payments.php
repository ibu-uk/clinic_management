<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

try {
    $pdo = getPDO();
    
    // Get recent payments with record details
    $query = "SELECT p.*,
                     CASE 
                         WHEN p.record_type = 'equipment' THEN e.equipment_name
                         ELSE cr.company_name
                     END as name,
                     CASE 
                         WHEN p.record_type = 'equipment' THEN e.contract_number
                         ELSE cr.contract_number
                     END as contract_number
              FROM payments p
              LEFT JOIN equipment e ON p.record_type = 'equipment' AND p.record_id = e.id
              LEFT JOIN clinic_records cr ON p.record_type = 'clinic_record' AND p.record_id = cr.id
              ORDER BY p.payment_date DESC, p.created_at DESC
              LIMIT 10";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'payments' => $payments
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
