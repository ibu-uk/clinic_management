<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();

try {
    $type = $_GET['type'] ?? '';
    $records = [];

    if ($type === 'Equipment') {
        $query = "SELECT id, equipment_name as name, contract_number, remaining_amount 
                 FROM equipment 
                 WHERE remaining_amount > 0";
    } else {
        $query = "SELECT id, company_name as name, contract_number, remaining_amount 
                 FROM clinic_records 
                 WHERE record_type = :type AND remaining_amount > 0";
    }

    $stmt = $db->prepare($query);
    if ($type !== 'Equipment') {
        $stmt->bindParam(':type', $type);
    }
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $records[] = $row;
    }

    echo json_encode([
        'success' => true,
        'records' => $records
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
