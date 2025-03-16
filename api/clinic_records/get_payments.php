<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['record_id'])) {
        throw new Exception('Record ID is required');
    }

    $database = Database::getInstance();
    $db = $database->getConnection();

    $query = "SELECT 
        payment_date,
        amount,
        payment_type,
        reference_no,
        status
    FROM payments 
    WHERE record_type = 'clinic_record' 
    AND record_id = ?
    ORDER BY payment_date DESC";

    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['record_id']]);
    
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $payments
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
