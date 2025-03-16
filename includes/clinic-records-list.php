<?php
require_once 'config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();

try {
    // Modified query to correctly show payment information for all record types
    $query = "SELECT 
        cr.*,
        (SELECT COUNT(*) FROM monthly_installments mi 
         WHERE mi.record_type = 'clinic_record' 
         AND mi.record_id = cr.id 
         AND mi.status = 'paid') as paid_installments,
        (SELECT COUNT(*) FROM monthly_installments mi 
         WHERE mi.record_type = 'clinic_record' 
         AND mi.record_id = cr.id) as total_installments,
        (SELECT COALESCE(SUM(p.amount), 0) 
         FROM payments p 
         WHERE p.record_type = cr.record_type 
         AND p.record_id = cr.id) as paid_amount,
        cr.monthly_payment as installment_amount
    FROM clinic_records cr 
    ORDER BY cr.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Calculate installment info
        $installment_text = $row['paid_installments'] . "/" . $row['total_installments'];
        $monthly_text = "Monthly: " . number_format($row['monthly_payment'], 3) . " KWD";
        $paid_amount = number_format($row['paid_amount'], 3);
        $total_amount = number_format($row['total_amount'], 3);
        
        echo "<tr>
                <td>{$row['record_type']}</td>
                <td>{$row['company_name']}</td>
                <td>{$row['contract_number']}</td>
                <td>" . date('n/j/Y', strtotime($row['contract_date'])) . "</td>
                <td>" . date('n/j/Y', strtotime($row['expiry_date'])) . "</td>
                <td>{$total_amount} KWD</td>
                <td>
                    {$installment_text}<br>
                    {$monthly_text}
                </td>
                <td>{$paid_amount} KWD</td>
                <td><span class='badge bg-" . ($row['status'] == 'active' ? 'success' : 'secondary') . "'>{$row['status']}</span></td>
                <td>
                    <div class='btn-group'>
                        <button class='btn btn-primary btn-sm' onclick='viewRecord({$row['id']})'>View</button>
                        <button class='btn btn-warning btn-sm' onclick='editRecord({$row['id']})'>Edit</button>
                    </div>
                </td>
            </tr>";
    }
} catch (Exception $e) {
    echo "<tr><td colspan='10' class='text-center text-danger'>Error loading clinic records: " . $e->getMessage() . "</td></tr>";
}
?>
