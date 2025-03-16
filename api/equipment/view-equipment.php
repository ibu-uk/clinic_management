<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid equipment ID']));
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get equipment details
    $query = "SELECT e.*, 
             (SELECT COUNT(*) FROM payments p 
              WHERE p.record_type = 'equipment'
              AND p.record_id = e.id) as paid_installments,
             (SELECT COALESCE(SUM(p.amount), 0) 
              FROM payments p 
              WHERE p.record_type = 'equipment'
              AND p.record_id = e.id) as total_paid
             FROM equipment e 
             WHERE e.id = ?";
             
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$equipment) {
        die(json_encode(['status' => 'error', 'message' => 'Equipment not found']));
    }
    
    // Get maintenance records
    $maintenance_query = "SELECT * FROM maintenance 
                         WHERE equipment_id = ? 
                         ORDER BY maintenance_date DESC";
    $stmt = $db->prepare($maintenance_query);
    $stmt->execute([$_GET['id']]);
    $maintenance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payment records
    $payment_query = "SELECT 
                        payment_date,
                        amount,
                        reference_no,
                        status
                     FROM payments 
                     WHERE record_type = 'equipment'
                     AND record_id = ?
                     ORDER BY payment_date DESC";
    $stmt = $db->prepare($payment_query);
    $stmt->execute([$_GET['id']]);
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

    // Format numbers
    $total_cost = number_format($total_cost, 3);
    $down_payment = number_format($down_payment, 3);
    $total_paid = number_format($total_paid, 3);
    $remaining_amount = number_format($remaining_amount, 3);
    $monthly_amount = number_format($monthly_amount, 3);

    // Prepare HTML content
    ob_start();
?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12 text-end">
            <a href="/clinic_management/reports/equipment_report.php?id=<?= $_GET['id'] ?>" 
               class="btn btn-primary me-2" target="_blank">
                <i class="fas fa-file-pdf"></i> Download PDF
            </a>
            <button onclick="window.open('/clinic_management/reports/print_equipment.php?id=<?= $_GET['id'] ?>', '_blank', 'width=800,height=600')" 
                    class="btn btn-secondary">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
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
                    <td><?= $total_cost ?> KWD</td>
                </tr>
                <tr>
                    <th>Payment Type</th>
                    <td>
                        <span class="badge bg-primary"><?= ucfirst($equipment['payment_type']) ?></span>
                        <?php if ($equipment['payment_type'] === 'installment'): ?>
                            <br>Monthly: <?= $monthly_amount ?> KWD
                            <br>Progress: <?= $equipment['paid_installments'] ?>/<?= $num_installments ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Down Payment</th>
                    <td><?= $down_payment ?> KWD</td>
                </tr>
                <tr>
                    <th>Total Paid</th>
                    <td><?= $total_paid ?> KWD</td>
                </tr>
                <tr>
                    <th>Remaining Amount</th>
                    <td><span class="badge bg-warning"><?= $remaining_amount ?> KWD</span></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td><span class="badge bg-<?= $equipment['status'] === 'completed' ? 'success' : 'warning' ?>"><?= ucfirst($equipment['status']) ?></span></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <h5>Payment History</h5>
            <?php if (empty($payment_records)): ?>
                <div class="alert alert-info">No payment records found</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Reference</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment_records as $payment): ?>
                                <tr>
                                    <td><?= date('Y-m-d', strtotime($payment['payment_date'])) ?></td>
                                    <td><?= number_format($payment['amount'], 3) ?> KWD</td>
                                    <td><?= htmlspecialchars($payment['reference_no']) ?></td>
                                    <td><span class="badge bg-success"><?= ucfirst($payment['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <h5>Contract Document</h5>
            <?php if (!empty($equipment['contract_file'])): ?>
                <div class="mb-3">
                    <a href="uploads/contracts/<?= htmlspecialchars($equipment['contract_file']) ?>" 
                       class="btn btn-primary" target="_blank">
                        <i class="fas fa-file-pdf"></i> View Contract
                    </a>
                </div>
                <p class="text-muted">File: <?= htmlspecialchars($equipment['contract_file']) ?></p>
            <?php else: ?>
                <div class="alert alert-warning">No contract document uploaded</div>
            <?php endif; ?>
        </div>

        <div class="col-md-6">
            <h5>Maintenance History</h5>
            <?php if (empty($maintenance_records)): ?>
                <div class="alert alert-info">No maintenance records found</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($maintenance_records as $record): ?>
                                <tr>
                                    <td><?= date('Y-m-d', strtotime($record['maintenance_date'])) ?></td>
                                    <td><?= htmlspecialchars($record['maintenance_type']) ?></td>
                                    <td><?= htmlspecialchars($record['description']) ?></td>
                                    <td><?= number_format($record['cost'], 3) ?> KWD</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
    $html = ob_get_clean();
    echo json_encode([
        'status' => 'success',
        'html' => $html,
        'equipment' => [
            'id' => $equipment['id'],
            'name' => $equipment['equipment_name'],
            'contract_file' => $equipment['contract_file']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in view-equipment.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load equipment details: ' . $e->getMessage()
    ]);
}
