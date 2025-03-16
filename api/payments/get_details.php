<?php
require_once '../../config/config.php';
require_once '../../includes/auth_validate.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Get payment ID
    $payment_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$payment_id) {
        throw new Exception('Invalid payment ID');
    }

    // Get payment details with related information
    $query = "
        SELECT 
            p.*,
            CASE 
                WHEN p.record_type = 'clinic_record' THEN cr.name
                WHEN p.record_type = 'equipment' THEN e.name
            END as record_name,
            GROUP_CONCAT(
                JSON_OBJECT(
                    'id', mi.id,
                    'installment_number', mi.installment_number,
                    'due_date', mi.due_date,
                    'amount', mi.amount,
                    'status', mi.status
                )
            ) as installments
        FROM payments p
        LEFT JOIN monthly_installments mi ON p.id = mi.payment_id
        LEFT JOIN clinic_records cr ON p.record_type = 'clinic_record' AND p.record_id = cr.id
        LEFT JOIN equipment e ON p.record_type = 'equipment' AND p.record_id = e.id
        WHERE p.id = ?
        GROUP BY p.id
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        throw new Exception('Payment not found');
    }

    // Process installments JSON
    $payment['installments'] = $payment['installments'] ? json_decode('[' . $payment['installments'] . ']', true) : [];

    // Add formatted amount
    $payment['formatted_amount'] = number_format($payment['amount'], 3) . ' KWD';

    // Format dates
    $payment['payment_date'] = date('Y-m-d', strtotime($payment['payment_date']));
    foreach ($payment['installments'] as &$installment) {
        $installment['due_date'] = date('Y-m-d', strtotime($installment['due_date']));
    }

    echo json_encode([
        'success' => true,
        'payment' => $payment
    ]);

} catch (Exception $e) {
    error_log('Error in get_details.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch payment details: ' . $e->getMessage()
    ]);
}
