<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();

try {
    $conditions = [];
    $params = [];

    // Handle date range filter
    if (isset($_GET['startDate']) && $_GET['startDate']) {
        $conditions[] = "(start_date >= :start_date)";
        $params[':start_date'] = $_GET['startDate'];
    }
    if (isset($_GET['endDate']) && $_GET['endDate']) {
        $conditions[] = "(end_date <= :end_date)";
        $params[':end_date'] = $_GET['endDate'];
    }

    // Handle record type filter
    if (isset($_GET['recordType']) && $_GET['recordType']) {
        $conditions[] = "record_type = :record_type";
        $params[':record_type'] = $_GET['recordType'];
    }

    // Handle payment status filter
    if (isset($_GET['paymentStatus']) && $_GET['paymentStatus']) {
        if ($_GET['paymentStatus'] === 'overdue') {
            $conditions[] = "(payment_type = 'installment' AND next_payment_date < CURDATE())";
        } else {
            $conditions[] = "status = :payment_status";
            $params[':payment_status'] = $_GET['paymentStatus'];
        }
    }

    $query = "SELECT * FROM reports_view";
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    $query .= " ORDER BY start_date DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Error retrieving reports'
    ]);
}
