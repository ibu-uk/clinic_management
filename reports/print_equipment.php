<?php
require_once __DIR__ . '/../config/database.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Equipment ID is required');
    }

    $database = Database::getInstance();
    $db = $database->getConnection();

    // Get equipment details
    $query = "SELECT e.*, 
             (SELECT COALESCE(SUM(p.amount), 0) 
              FROM payments p 
              WHERE p.record_type = 'equipment'
              AND p.record_id = e.id
              AND p.status = 'completed') as total_paid
             FROM equipment e
             WHERE e.id = ?";

    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $equipment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$equipment) {
        throw new Exception('Equipment not found');
    }

    // Get payment history
    $query = "SELECT p.*, DATE_FORMAT(p.payment_date, '%Y-%m-%d') as formatted_date 
             FROM payments p 
             WHERE p.record_type = 'equipment' 
             AND p.record_id = ? 
             AND p.status = 'completed'
             ORDER BY p.payment_date DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate amounts
    $total_cost = floatval($equipment['total_cost']);
    $down_payment = floatval($equipment['down_payment']);
    $total_paid = floatval($equipment['total_paid']);
    
    if ($equipment['payment_type'] === 'one_time') {
        $remaining_amount = $total_cost - $total_paid;
    } else {
        $remaining_amount = $total_cost - $down_payment - $total_paid;
    }

    // Prepare details array
    $details = array(
        'Equipment Name' => $equipment['equipment_name'],
        'Model' => $equipment['equipment_model'],
        'Company' => $equipment['company_name'],
        'Contract Number' => $equipment['contract_number'],
        'Contract Type' => ucfirst($equipment['contract_type']),
        'Contract Period' => date('Y-m-d', strtotime($equipment['contract_start_date'])) . ' to ' . 
                            date('Y-m-d', strtotime($equipment['contract_end_date'])),
        'Payment Type' => ucfirst($equipment['payment_type']),
        'Total Cost' => number_format($total_cost, 3) . ' KWD'
    );

    if ($equipment['payment_type'] === 'installment') {
        $details['Down Payment'] = number_format($down_payment, 3) . ' KWD';
        $details['Monthly Installment'] = number_format(($total_cost - $down_payment) / $equipment['num_installments'], 3) . ' KWD';
    }

    $details['Total Paid'] = number_format($total_paid, 3) . ' KWD';
    $details['Remaining Amount'] = number_format($remaining_amount, 3) . ' KWD';
    $details['Status'] = ucfirst($equipment['status']);

    // Set the title
    $title = 'Equipment Details - ' . $equipment['equipment_name'];

    // Include the print template
    include 'print_template.php';

} catch (Exception $e) {
    echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
}
?>
