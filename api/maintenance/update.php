<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_POST['maintenance_id'])) {
        throw new Exception('Maintenance ID is required');
    }

    $database = Database::getInstance();
    $db = $database->getConnection();

    // Update maintenance status
    $update_query = "UPDATE maintenance 
                    SET status = 'completed', 
                        notes = ?, 
                        updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?";
    $stmt = $db->prepare($update_query);
    $stmt->execute([$_POST['notes'] ?? '', $_POST['maintenance_id']]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Maintenance updated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
