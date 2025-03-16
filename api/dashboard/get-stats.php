<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    // Get maintenance stats
    $query = "SELECT 
        SUM(CASE WHEN status = 'scheduled' AND maintenance_date >= CURRENT_DATE THEN 1 ELSE 0 END) as scheduled_maintenance,
        SUM(CASE WHEN (status = 'scheduled' OR status IS NULL) AND maintenance_date < CURRENT_DATE THEN 1 ELSE 0 END) as overdue_maintenance,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_maintenance
    FROM maintenance";
    
    $stmt = $db->query($query);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($stats);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
