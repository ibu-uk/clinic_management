<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Record ID is required');
    }

    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get record details
    $query = "SELECT cr.*,
        COALESCE(SUM(p.amount), 0) as total_paid,
        CASE 
            WHEN cr.payment_type = 'full' AND cr.remaining_amount = 0 THEN 'paid'
            WHEN cr.next_payment_date < CURRENT_DATE THEN 'overdue'
            ELSE cr.payment_status 
        END as payment_status,
        CASE 
            WHEN cr.expiry_date < CURRENT_DATE THEN 'expired'
            WHEN cr.expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) THEN 'pending'
            ELSE cr.renewal_status 
        END as renewal_status
    FROM clinic_records cr
    LEFT JOIN payments p ON p.record_type = 'clinic_record' AND p.record_id = cr.id AND p.status = 'completed'
    WHERE cr.id = ?
    GROUP BY cr.id";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        throw new Exception('Record not found');
    }
    
    // Get payment history
    $query = "SELECT * FROM payments 
              WHERE record_type = 'clinic_record' 
              AND record_id = ? 
              ORDER BY payment_date DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates
    $record['contract_date'] = date('Y-m-d', strtotime($record['contract_date']));
    $record['expiry_date'] = date('Y-m-d', strtotime($record['expiry_date']));
    $record['next_payment_date'] = $record['next_payment_date'] ? date('Y-m-d', strtotime($record['next_payment_date'])) : null;
    
    echo json_encode([
        'status' => 'success',
        'record' => $record,
        'payments' => $payments
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
