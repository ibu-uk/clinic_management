<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    // Get the 10 most recent payments with record details
    $query = "SELECT 
        p.*,
        CASE 
            WHEN p.record_type = 'equipment' THEN e.equipment_name
            ELSE cr.company_name
        END as record_name,
        CASE 
            WHEN p.record_type = 'equipment' THEN e.contract_number
            ELSE cr.contract_number
        END as contract_number,
        CASE 
            WHEN p.record_type = 'equipment' THEN 'Equipment'
            ELSE 'Clinic Record'
        END as record_type_display
    FROM payments p
    LEFT JOIN equipment e ON p.record_id = e.id AND p.record_type = 'equipment'
    LEFT JOIN clinic_records cr ON p.record_id = cr.id AND p.record_type = 'clinic_record'
    ORDER BY p.payment_date DESC, p.id DESC
    LIMIT 10";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the payments
    $formatted_payments = array_map(function($payment) {
        return [
            'payment_date' => date('Y-m-d', strtotime($payment['payment_date'])),
            'record_name' => $payment['record_name'] . ' (' . $payment['contract_number'] . ')',
            'payment_method' => ucfirst($payment['payment_method']),
            'reference_no' => $payment['reference_no'],
            'amount' => number_format($payment['amount'], 3),
            'status' => $payment['status']
        ];
    }, $payments);

    echo json_encode($formatted_payments);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
