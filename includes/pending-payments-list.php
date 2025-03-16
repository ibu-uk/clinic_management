<?php
require_once 'config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();

try {
    $query = "SELECT mi.*, 
              CASE 
                WHEN mi.record_type = 'equipment' THEN e.equipment_name
                ELSE cr.company_name
              END as record_name,
              CASE 
                WHEN mi.record_type = 'equipment' THEN e.contract_number
                ELSE cr.contract_number
              END as contract_number,
              CASE 
                WHEN mi.record_type = 'equipment' THEN e.contract_type
                ELSE cr.record_type
              END as actual_record_type
              FROM monthly_installments mi
              LEFT JOIN equipment e ON mi.record_id = e.id AND mi.record_type = 'equipment'
              LEFT JOIN clinic_records cr ON mi.record_id = cr.id AND mi.record_type = 'clinic_record'
              WHERE mi.status != 'paid'
              ORDER BY mi.due_date ASC";
              
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status_class = $row['status'] === 'overdue' ? 'status-overdue' : 'status-pending';
        $due_date = new DateTime($row['due_date']);
        $today = new DateTime();
        
        if ($row['status'] === 'pending' && $due_date < $today) {
            // Update status to overdue
            $update_query = "UPDATE monthly_installments SET status = 'overdue' WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([':id' => $row['id']]);
            $status_class = 'status-overdue';
            $row['status'] = 'overdue';
        }
        
        // Display the actual record type (subtype)
        $display_type = ucfirst($row['actual_record_type']);
        
        echo "<tr>
                <td>" . $row['due_date'] . "</td>
                <td>{$display_type}<br><small>{$row['record_name']}</small></td>
                <td>{$row['contract_number']}</td>
                <td>Installment #{$row['installment_number']}</td>
                <td>" . number_format($row['amount'], 3) . " KWD</td>
                <td><span class='status-badge {$status_class}'>" . ucfirst($row['status']) . "</span></td>
                <td>
                    <button class='btn btn-sm btn-primary' 
                            onclick='openPaymentModal({$row['id']}, \"{$row['actual_record_type']}\", {$row['record_id']}, {$row['amount']})'>
                        <i class='fas fa-money-bill'></i> Pay Now
                    </button>
                </td>
            </tr>";
    }
} catch (Exception $e) {
    echo "<tr><td colspan='7' class='text-center text-danger'>Error loading pending payments: " . $e->getMessage() . "</td></tr>";
}
?>
