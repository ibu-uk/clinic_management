<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

try {
    // Get query parameters
    $record_type = $_GET['record_type'] ?? '';
    $record_id = $_GET['record_id'] ?? '';
    
    // Validate required fields
    if (empty($record_type) || empty($record_id)) {
        throw new Exception('Missing required parameters');
    }
    
    $pdo = getPDO();
    
    // Get pending installments for the record
    $query = "SELECT id, amount, due_date, status 
             FROM installments 
             WHERE record_type = ? 
             AND record_id = ? 
             AND status = 'pending' 
             ORDER BY due_date ASC";
             
    $stmt = $pdo->prepare($query);
    $stmt->execute([$record_type, $record_id]);
    $installments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($installments);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
