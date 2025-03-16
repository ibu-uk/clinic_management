<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    $query = "SELECT 
        e.*,
        CASE 
            WHEN e.payment_type = 'one_time' THEN 
                CONCAT(
                    (CASE WHEN COALESCE((
                        SELECT COUNT(*) 
                        FROM payments p 
                        WHERE p.record_id = e.id 
                        AND p.record_type = 'equipment'
                        AND p.status = 'completed'
                    ), 0) > 0 THEN '1' ELSE '0' END),
                    '/1'
                )
            WHEN e.payment_type = 'installment' THEN 
                CONCAT(
                    (SELECT COUNT(*) 
                    FROM payments p 
                    WHERE p.record_id = e.id 
                    AND p.record_type = 'equipment'
                    AND p.status = 'completed'),
                    '/',
                    e.num_installments
                )
        END as payment_status,
        CASE 
            WHEN e.payment_type = 'one_time' THEN 
                CASE WHEN COALESCE((
                    SELECT SUM(amount) 
                    FROM payments p 
                    WHERE p.record_id = e.id 
                    AND p.record_type = 'equipment'
                    AND p.status = 'completed'
                ), 0) >= e.total_cost THEN 
                    NULL 
                ELSE 
                    e.total_cost 
                END
            ELSE 
                e.monthly_installment
        END as monthly_amount,
        COALESCE((
            SELECT SUM(amount) 
            FROM payments p 
            WHERE p.record_id = e.id 
            AND p.record_type = 'equipment'
            AND p.status = 'completed'
        ), 0) as paid_amount,
        (e.total_cost - e.down_payment - COALESCE((
            SELECT SUM(amount) 
            FROM payments p 
            WHERE p.record_id = e.id 
            AND p.record_type = 'equipment'
            AND p.status = 'completed'
        ), 0)) as remaining_amount,
        CASE 
            WHEN e.payment_type = 'one_time' AND COALESCE((
                SELECT SUM(amount) 
                FROM payments p 
                WHERE p.record_id = e.id 
                AND p.record_type = 'equipment'
                AND p.status = 'completed'
            ), 0) >= e.total_cost THEN 'completed'
            WHEN CURRENT_DATE > e.contract_end_date THEN 'expired'
            ELSE 'active'
        END as current_status
    FROM equipment e
    ORDER BY e.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format amounts
    foreach ($equipment as &$item) {
        $item['total_cost'] = number_format($item['total_cost'], 3);
        $item['down_payment'] = number_format($item['down_payment'], 3);
        $item['paid_amount'] = number_format($item['paid_amount'], 3);
        $item['remaining_amount'] = number_format($item['remaining_amount'], 3);
        if (!empty($item['monthly_amount'])) {
            $item['monthly_amount'] = number_format($item['monthly_amount'], 3);
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $equipment
    ]);

} catch(Exception $e) {
    error_log("Error in equipment list: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading equipment list'
    ]);
}
