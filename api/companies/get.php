<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$type = $_GET['type'] ?? '';
if (empty($type)) {
    echo json_encode(['success' => false, 'message' => 'Record type is required']);
    exit();
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    $query = "";
    if ($type === 'Equipment') {
        $query = "SELECT id, company_name FROM equipment WHERE status = 'active'";
    } else {
        $query = "SELECT id, company_name FROM clinic_records WHERE record_type = :type AND status = 'active'";
    }
    
    $stmt = $db->prepare($query);
    if ($type !== 'Equipment') {
        $stmt->bindParam(':type', $type);
    }
    $stmt->execute();
    
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'companies' => $companies
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching companies: ' . $e->getMessage()
    ]);
}
