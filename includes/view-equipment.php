<?php
require_once '../config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid equipment ID');
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get equipment details
    $query = "SELECT e.*, 
             (SELECT COUNT(*) FROM payments p 
              WHERE p.record_type = e.contract_type 
              AND p.record_id = e.id) as paid_installments,
             (SELECT COALESCE(SUM(p.amount), 0) 
              FROM payments p 
              WHERE p.record_type = e.contract_type 
              AND p.record_id = e.id) as total_paid
             FROM equipment e 
             WHERE e.id = ?";
             
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$equipment) {
        die('Equipment not found');
    }
    
    // Get maintenance records
    $maintenance_query = "SELECT * FROM maintenance 
                         WHERE equipment_id = ? 
                         ORDER BY maintenance_date DESC";
    $stmt = $db->prepare($maintenance_query);
    $stmt->execute([$_GET['id']]);
    $maintenance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payment records
    $payment_query = "SELECT * FROM payments 
                     WHERE record_type = ? 
                     AND record_id = ? 
                     ORDER BY payment_date DESC";
    $stmt = $db->prepare($payment_query);
    $stmt->execute([$equipment['contract_type'], $_GET['id']]);
    $payment_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate remaining amount
    $total_cost = floatval($equipment['total_cost']);
    $down_payment = floatval($equipment['down_payment']);
    $total_paid = floatval($equipment['total_paid']);
    $remaining_amount = $total_cost - $down_payment - $total_paid;
    
    // Calculate monthly installment
    $total_after_down = $total_cost - $down_payment;
    $num_installments = intval($equipment['num_installments']) ?: 12;
    $monthly_amount = $total_after_down / $num_installments;
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <h5>Equipment Details</h5>
            <table class="table table-bordered">
                <tr>
                    <th>Equipment Name</th>
                    <td><?= htmlspecialchars($equipment['equipment_name']) ?></td>
                </tr>
                <tr>
                    <th>Model</th>
                    <td><?= htmlspecialchars($equipment['equipment_model']) ?></td>
                </tr>
                <tr>
                    <th>Company</th>
                    <td><?= htmlspecialchars($equipment['company_name']) ?></td>
                </tr>
                <tr>
                    <th>Contract Number</th>
                    <td><?= htmlspecialchars($equipment['contract_number']) ?></td>
                </tr>
                <tr>
                    <th>Contract Type</th>
                    <td><span class="badge bg-info"><?= ucfirst(htmlspecialchars($equipment['contract_type'])) ?></span></td>
                </tr>
                <tr>
                    <th>Contract Period</th>
                    <td>
                        <?= date('Y-m-d', strtotime($equipment['contract_start_date'])) ?> to 
                        <?= date('Y-m-d', strtotime($equipment['contract_end_date'])) ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="col-md-6">
            <h5>Payment Details</h5>
            <table class="table table-bordered">
                <tr>
                    <th>Total Cost</th>
                    <td><?= number_format($total_cost, 3) ?> KWD</td>
                </tr>
                <tr>
                    <th>Down Payment</th>
                    <td><?= number_format($down_payment, 3) ?> KWD</td>
                </tr>
                <tr>
                    <th>Payment Type</th>
                    <td>
                        <?php if ($equipment['payment_type'] === 'installment'): ?>
                            <span class="badge bg-primary">Installment</span>
                            <br>
                            <small>Monthly: <?= number_format($monthly_amount, 3) ?> KWD</small>
                            <br>
                            <small>Progress: <?= $equipment['paid_installments'] ?>/<?= $num_installments ?></small>
                        <?php else: ?>
                            <span class="badge bg-success">Full Payment</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Total Paid</th>
                    <td><?= number_format($total_paid, 3) ?> KWD</td>
                </tr>
                <tr>
                    <th>Remaining Amount</th>
                    <td>
                        <span class="badge <?= $remaining_amount > 0 ? 'bg-warning' : 'bg-success' ?>">
                            <?= number_format($remaining_amount, 3) ?> KWD
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <h5>Payment History</h5>
            <?php if (count($payment_records) > 0): ?>
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payment_records as $payment): ?>
                            <tr>
                                <td><?= date('Y-m-d', strtotime($payment['payment_date'])) ?></td>
                                <td><?= number_format($payment['amount'], 3) ?> KWD</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No payment records found</div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-6">
            <h5>Maintenance History</h5>
            <?php if (count($maintenance_records) > 0): ?>
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maintenance_records as $maintenance): ?>
                            <tr>
                                <td><?= date('Y-m-d', strtotime($maintenance['maintenance_date'])) ?></td>
                                <td>
                                    <?php
                                    $badge_class = 'bg-info';
                                    switch ($maintenance['status']) {
                                        case 'completed':
                                            $badge_class = 'bg-success';
                                            break;
                                        case 'scheduled':
                                            $badge_class = 'bg-warning';
                                            break;
                                        case 'cancelled':
                                            $badge_class = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?= $badge_class ?>"><?= ucfirst($maintenance['status']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($maintenance['notes'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No maintenance records found</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
} catch (Exception $e) {
    error_log("Error in view equipment: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Error loading equipment details. Please try again later.</div>";
}
?>
