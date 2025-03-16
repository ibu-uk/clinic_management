<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$companyId = $_GET['company_id'] ?? '';
if (empty($companyId)) {
    echo json_encode(['success' => false, 'message' => 'Company ID is required']);
    exit();
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // First check if this is equipment or clinic record
    $query = "SELECT id FROM equipment WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $companyId);
    $stmt->execute();
    
    $isEquipment = $stmt->rowCount() > 0;
    
    // Get installments
    $query = "SELECT 
        i.id,
        i.installment_number,
        i.due_date,
        i.amount,
        i.status
    FROM monthly_installments i
    WHERE i.record_id = :record_id 
    AND i.record_type = :record_type
    AND i.status IN ('pending', 'overdue')
    ORDER BY i.due_date ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':record_id', $companyId);
    $recordType = $isEquipment ? 'equipment' : 'clinic_record';
    $stmt->bindParam(':record_type', $recordType);
    $stmt->execute();
    
    $installments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'installments' => $installments
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching installments: ' . $e->getMessage()
    ]);
}
