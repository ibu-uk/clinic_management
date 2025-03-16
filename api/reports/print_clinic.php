<?php
require_once '../../config/database.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Clinic Record ID is required');
    }

    $database = Database::getInstance();
    $db = $database->getConnection();

    // Get clinic record details
    $query = "SELECT c.*, 
             (SELECT COALESCE(SUM(p.amount), 0) 
              FROM payments p 
              WHERE p.record_type = 'clinic_record'
              AND p.record_id = c.id
              AND p.status = 'completed') as total_paid
             FROM clinic_records c
             WHERE c.id = ?";

    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $clinic = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$clinic) {
        throw new Exception('Clinic record not found');
    }

    // Get payment history
    $query = "SELECT p.*, DATE_FORMAT(p.payment_date, '%Y-%m-%d') as formatted_date 
             FROM payments p 
             WHERE p.record_type = 'clinic_record' 
             AND p.record_id = ? 
             AND p.status = 'completed'
             ORDER BY p.payment_date DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate amounts
    $total_cost = floatval($clinic['total_cost']);
    $down_payment = floatval($clinic['down_payment']);
    $total_paid = floatval($clinic['total_paid']);
    
    if ($clinic['payment_type'] === 'one_time') {
        $remaining_amount = $total_cost - $total_paid;
    } else {
        $remaining_amount = $total_cost - $down_payment - $total_paid;
    }

    // Prepare details array
    $details = array(
        'Patient Name' => $clinic['patient_name'],
        'Civil ID' => $clinic['civil_id'],
        'Phone' => $clinic['phone'],
        'Record Type' => ucfirst($clinic['record_type']),
        'Payment Type' => ucfirst($clinic['payment_type']),
        'Total Cost' => number_format($total_cost, 3) . ' KWD'
    );

    if ($clinic['payment_type'] === 'installment') {
        $details['Down Payment'] = number_format($down_payment, 3) . ' KWD';
        $details['Monthly Payment'] = number_format($clinic['monthly_payment'], 3) . ' KWD';
    }

    $details['Total Paid'] = number_format($total_paid, 3) . ' KWD';
    $details['Remaining Amount'] = number_format($remaining_amount, 3) . ' KWD';
    $details['Status'] = ucfirst($clinic['status']);

    // Set the title
    $title = 'Clinic Record Details - ' . $clinic['patient_name'];

    // Include the print template
    include 'print_template.php';

} catch (Exception $e) {
    echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
}
?>
