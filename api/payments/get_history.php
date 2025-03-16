<?php
require_once '../../config/config.php';
require_once '../../includes/auth_validate.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Get filters from query parameters
    $record_type = filter_input(INPUT_GET, 'record_type', FILTER_SANITIZE_STRING);
    $payment_method = filter_input(INPUT_GET, 'payment_method', FILTER_SANITIZE_STRING);
    $date_from = filter_input(INPUT_GET, 'date_from', FILTER_SANITIZE_STRING);
    $date_to = filter_input(INPUT_GET, 'date_to', FILTER_SANITIZE_STRING);
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    $items_per_page = filter_input(INPUT_GET, 'items_per_page', FILTER_VALIDATE_INT) ?: 10;

    // Build query conditions
    $conditions = [];
    $params = [];

    if ($record_type) {
        $conditions[] = "p.record_type = ?";
        $params[] = $record_type;
    }

    if ($payment_method) {
        $conditions[] = "p.payment_method = ?";
        $params[] = $payment_method;
    }

    if ($date_from) {
        $conditions[] = "p.payment_date >= ?";
        $params[] = $date_from;
    }

    if ($date_to) {
        $conditions[] = "p.payment_date <= ?";
        $params[] = $date_to;
    }

    $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    // Get total count
    $countQuery = "SELECT COUNT(*) FROM payments p $whereClause";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $total_records = $stmt->fetchColumn();

    // Calculate pagination
    $offset = ($page - 1) * $items_per_page;
    $total_pages = ceil($total_records / $items_per_page);

    // Get paginated results
    $query = "
        SELECT 
            p.*,
            GROUP_CONCAT(
                JSON_OBJECT(
                    'installment_number', mi.installment_number,
                    'due_date', mi.due_date,
                    'amount', mi.amount
                )
            ) as installments
        FROM payments p
        LEFT JOIN monthly_installments mi ON p.id = mi.payment_id
        $whereClause
        GROUP BY p.id
        ORDER BY p.payment_date DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $pdo->prepare($query);
    $params[] = $items_per_page;
    $params[] = $offset;
    $stmt->execute($params);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process installments JSON
    foreach ($payments as &$payment) {
        $payment['installments'] = $payment['installments'] ? json_decode('[' . $payment['installments'] . ']', true) : [];
    }

    echo json_encode([
        'success' => true,
        'payments' => $payments,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $page
    ]);

} catch (Exception $e) {
    error_log('Error in get_history.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch payment history: ' . $e->getMessage()
    ]);
}
