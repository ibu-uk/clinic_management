<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();

try {
    $type = $_GET['type'] ?? '';
    
    if (empty($type)) {
        throw new Exception('Record type is required');
    }
    
    if ($type === 'equipment') {
        $query = "SELECT id, equipment_name, equipment_model, company_name, contract_number, 
                        total_cost, remaining_amount, payment_type, monthly_installment,
                        contract_start_date, contract_end_date, status
                 FROM equipment 
                 WHERE status = 'active'
                 ORDER BY created_at DESC";
    } else {
        $query = "SELECT id, record_type, company_name, contract_number, 
                        total_amount as total_cost, remaining_amount, payment_type,
                        monthly_payment as monthly_installment,
                        contract_date as contract_start_date, expiry_date as contract_end_date,
                        status
                 FROM clinic_records 
                 WHERE status = 'active'
                 ORDER BY created_at DESC";
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'records' => $records
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
