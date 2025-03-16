<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    // Get filters from request
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
    $offset = ($page - 1) * $per_page;

    $where_clauses = [];
    $params = [];

    // Apply filters
    if (!empty($_GET['record_type'])) {
        if ($_GET['record_type'] === 'equipment') {
            $where_clauses[] = "(p.record_type IN ('new', 'renew', 'upgrade', 'equipment'))";
        } else {
            $where_clauses[] = "(p.record_type IN ('rent', 'insurance', 'clinic_license', 'fire_safety', 'clinic_record'))";
        }
    }

    if (!empty($_GET['sub_type'])) {
        $where_clauses[] = "p.record_type = :sub_type";
        $params[':sub_type'] = $_GET['sub_type'];
    }

    if (!empty($_GET['payment_method'])) {
        $where_clauses[] = "p.payment_method = :payment_method";
        $params[':payment_method'] = $_GET['payment_method'];
    }

    if (!empty($_GET['date_from'])) {
        $where_clauses[] = "p.payment_date >= :date_from";
        $params[':date_from'] = $_GET['date_from'];
    }

    if (!empty($_GET['date_to'])) {
        $where_clauses[] = "p.payment_date <= :date_to";
        $params[':date_to'] = $_GET['date_to'];
    }

    $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM payments p $where_sql";
    $stmt = $db->prepare($count_query);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get payments with proper joins
    $query = "SELECT p.*, 
              COALESCE(e.equipment_name, cr.company_name) as name,
              COALESCE(e.contract_number, cr.contract_number) as contract_number,
              CASE 
                WHEN p.record_type IN ('new', 'renew', 'upgrade', 'equipment') THEN e.equipment_name
                ELSE cr.company_name
              END as record_name
              FROM payments p
              LEFT JOIN equipment e ON p.record_id = e.id 
                AND (p.record_type IN ('new', 'renew', 'upgrade', 'equipment') 
                     OR (p.record_type = 'equipment' AND e.contract_type = p.record_type))
              LEFT JOIN clinic_records cr ON p.record_id = cr.id 
                AND (p.record_type IN ('rent', 'insurance', 'clinic_license', 'fire_safety', 'clinic_record')
                     OR (p.record_type = 'clinic_record' AND cr.record_type = p.record_type))
              $where_sql
              ORDER BY p.payment_date DESC, p.id DESC
              LIMIT :offset, :per_page";

    $stmt = $db->prepare($query);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get summary statistics
    $summary_query = "SELECT 
        COUNT(*) as total_count,
        SUM(amount) as total_amount,
        SUM(CASE WHEN payment_date >= DATE_FORMAT(NOW() ,'%Y-%m-01') THEN amount ELSE 0 END) as monthly_amount,
        AVG(amount) as average_amount
        FROM payments p $where_sql";
    
    $stmt = $db->prepare($summary_query);
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'payments' => $payments,
        'total' => $total,
        'summary' => $summary
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
