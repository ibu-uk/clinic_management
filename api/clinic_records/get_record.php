<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Record ID is required');
    }

    $database = Database::getInstance();
    $db = $database->getConnection();

    $query = "SELECT * FROM clinic_records WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        throw new Exception('Record not found');
    }

    echo json_encode([
        'status' => 'success',
        'data' => $record
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
