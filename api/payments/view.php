<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Payment ID is required');
    }

    $database = Database::getInstance();
    $db = $database->getConnection();

    $query = "SELECT p.*, 
              CASE 
                WHEN p.record_type IN ('new', 'renew', 'upgrade', 'equipment') THEN e.equipment_name
                ELSE cr.company_name
              END as name,
              CASE 
                WHEN p.record_type IN ('new', 'renew', 'upgrade', 'equipment') THEN e.contract_number
                ELSE cr.contract_number
              END as contract_number
              FROM payments p
              LEFT JOIN equipment e ON p.record_id = e.id 
                AND p.record_type IN ('new', 'renew', 'upgrade', 'equipment')
              LEFT JOIN clinic_records cr ON p.record_id = cr.id 
                AND p.record_type IN ('rent', 'insurance', 'clinic_license', 'fire_safety', 'clinic_record')
              WHERE p.id = :id";

    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $_GET['id']]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        throw new Exception('Payment not found');
    }

    echo json_encode([
        'success' => true,
        'payment' => $payment
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
