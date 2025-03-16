<?php
require_once 'config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();

try {
    // Get payments with record details
    $query = "SELECT p.*, 
              CASE 
                WHEN p.record_type = 'equipment' THEN e.equipment_name
                ELSE cr.company_name
              END as record_name,
              CASE 
                WHEN p.record_type = 'equipment' THEN e.contract_number
                ELSE cr.contract_number
              END as contract_number,
              CASE 
                WHEN p.record_type = 'equipment' THEN e.total_cost
                ELSE cr.total_amount
              END as total_amount,
              CASE 
                WHEN p.record_type = 'equipment' THEN e.remaining_amount
                ELSE cr.remaining_amount
              END as remaining_balance
              FROM payments p
              LEFT JOIN equipment e ON p.record_type = 'equipment' AND p.record_id = e.id
              LEFT JOIN clinic_records cr ON p.record_type = 'clinic_record' AND p.record_id = cr.id
              ORDER BY p.payment_date DESC, p.created_at DESC";
              
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $badge_class = $row['status'] === 'completed' ? 'bg-success' : 'bg-warning';
        
        echo "<tr>
                <td>" . date('Y-m-d', strtotime($row['payment_date'])) . "</td>
                <td>" . ucfirst($row['record_type']) . "</td>
                <td>{$row['record_name']}</td>
                <td>{$row['contract_number']}</td>
                <td>" . number_format($row['amount'], 3) . " KWD</td>
                <td>" . number_format($row['total_amount'], 3) . " KWD</td>
                <td>" . number_format($row['remaining_balance'], 3) . " KWD</td>
                <td>{$row['payment_method']}</td>
                <td>{$row['reference_number']}</td>
                <td><span class='badge {$badge_class}'>" . ucfirst($row['status']) . "</span></td>
                <td>
                    <div class='btn-group'>
                        <button class='btn btn-info btn-sm' onclick='viewPayment({$row['id']})'>View</button>
                    </div>
                </td>
            </tr>";
    }
} catch (Exception $e) {
    echo "<tr><td colspan='11' class='text-center text-danger'>Error loading payment records: " . $e->getMessage() . "</td></tr>";
}

function determinePaymentStatus($paymentDate) {
    $paymentDate = strtotime($paymentDate);
    $today = strtotime('today');
    
    if ($paymentDate > $today) {
        return 'pending';
    } else if ($paymentDate == $today) {
        return 'paid';
    } else {
        return 'overdue';
    }
}
?>
