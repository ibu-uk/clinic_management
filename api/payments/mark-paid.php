<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();

try {
    // Get input data
    $data = json_decode(file_get_contents('php://input'), true);
    $payment_id = $data['paymentId'] ?? null;

    if (!$payment_id) {
        throw new Exception('Payment ID is required');
    }

    // Start transaction
    $db->beginTransaction();

    // Mark payment as paid
    $query = "UPDATE monthly_installments SET 
                payment_date = CURDATE(),
                status = 'Paid'
              WHERE id = :payment_id";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':payment_id' => $payment_id]);

    // Update parent record's remaining amount and next payment date
    $query = "SELECT * FROM monthly_installments WHERE id = :payment_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':payment_id' => $payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($payment) {
        $table = $payment['record_type'] === 'Equipment' ? 'equipment' : 'clinic_records';
        $amount_field = $payment['record_type'] === 'Equipment' ? 'remaining_amount' : 'remaining_amount';

        // Update remaining amount
        $query = "UPDATE $table SET 
                    $amount_field = $amount_field - :amount,
                    next_payment_date = (
                        SELECT MIN(due_date) 
                        FROM monthly_installments 
                        WHERE record_type = :record_type 
                        AND record_id = :record_id 
                        AND payment_date IS NULL
                    )
                  WHERE id = :record_id";

        $stmt = $db->prepare($query);
        $stmt->execute([
            ':amount' => $payment['amount'],
            ':record_type' => $payment['record_type'],
            ':record_id' => $payment['record_id']
        ]);
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Payment marked as paid successfully'
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error marking payment as paid: ' . $e->getMessage()
    ]);
}
