<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    $type = $_GET['type'] ?? '';
    
    if ($type === 'equipment') {
        $query = "SELECT 
            id,
            equipment_name as name,
            contract_number,
            total_cost as total_amount,
            remaining_amount
        FROM equipment 
        WHERE status = 'active' 
        AND (payment_type = 'installment' OR remaining_amount > 0)";
    } else {
        $query = "SELECT 
            id,
            company_name as name,
            contract_number,
            total_amount,
            remaining_amount
        FROM clinic_records 
        WHERE status = 'active' 
        AND (payment_type = 'installment' OR remaining_amount > 0)";
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
