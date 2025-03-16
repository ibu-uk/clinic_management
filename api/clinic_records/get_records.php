<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    // Build query with filters
    $query = "SELECT * FROM clinic_records WHERE 1=1";
    $params = [];

    if (isset($_GET['status']) && $_GET['status'] !== 'all') {
        $query .= " AND status = ?";
        $params[] = $_GET['status'];
    }

    if (isset($_GET['payment_type']) && $_GET['payment_type'] !== 'all') {
        $query .= " AND payment_type = ?";
        $params[] = $_GET['payment_type'];
    }

    $query .= " ORDER BY created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $records
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
