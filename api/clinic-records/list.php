<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM clinic_records ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates and numbers
    foreach ($records as &$record) {
        $record['contract_start_date'] = date('Y-m-d', strtotime($record['contract_start_date']));
        $record['contract_end_date'] = date('Y-m-d', strtotime($record['contract_end_date']));
        if ($record['next_payment_date']) {
            $record['next_payment_date'] = date('Y-m-d', strtotime($record['next_payment_date']));
        }
        $record['total_cost'] = number_format($record['total_cost'], 3, '.', '');
        $record['remaining_amount'] = number_format($record['remaining_amount'], 3, '.', '');
    }
    
    echo json_encode($records);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
