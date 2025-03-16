<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid clinic record ID']));
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get clinic record details
    $query = "SELECT c.*, 
             (SELECT COUNT(*) FROM payments p 
              WHERE p.record_type = 'clinic_record'
              AND p.record_id = c.id) as paid_installments,
             (SELECT COALESCE(SUM(p.amount), 0) 
              FROM payments p 
              WHERE p.record_type = 'clinic_record'
              AND p.record_id = c.id) as total_paid
             FROM clinic_records c 
             WHERE c.id = ?";
             
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        die(json_encode(['status' => 'error', 'message' => 'Clinic record not found']));
    }
    
    // Get payment records
    $payment_query = "SELECT 
                        payment_date,
                        amount,
                        reference_no,
                        status
                     FROM payments 
                     WHERE record_type = 'clinic_record'
                     AND record_id = ?
                     ORDER BY payment_date DESC";
    $stmt = $db->prepare($payment_query);
    $stmt->execute([$_GET['id']]);
    $payment_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate amounts
    $total_cost = floatval($record['total_cost']);
    $down_payment = floatval($record['down_payment']);
    $total_paid = floatval($record['total_paid']);
    $remaining_amount = $total_cost - $down_payment - $total_paid;
    
    // Format numbers
    $total_cost = number_format($total_cost, 3);
    $down_payment = number_format($down_payment, 3);
    $total_paid = number_format($total_paid, 3);
    $remaining_amount = number_format($remaining_amount, 3);
    $monthly_payment = number_format(floatval($record['monthly_payment']), 3);

    // Prepare HTML content
    ob_start();
?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12 text-end">
            <a href="../reports/clinic_report.php?id=<?= $record['id'] ?>" 
               class="btn btn-primary me-2" target="_blank">
                <i class="fas fa-file-pdf"></i> Download PDF
            </a>
            <button onclick="window.open('../reports/print_clinic.php?id=<?= $record['id'] ?>', '_blank', 'width=800,height=600')" 
                    class="btn btn-secondary">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <h5>Record Details</h5>
            <table class="table table-bordered">
                <tr>
                    <th>Record Type</th>
                    <td><span class="badge bg-info"><?= ucfirst(htmlspecialchars($record['record_type'])) ?></span></td>
                </tr>
                <tr>
                    <th>Company Name</th>
                    <td><?= htmlspecialchars($record['company_name']) ?></td>
                </tr>
                <tr>
                    <th>Contract Number</th>
                    <td><?= htmlspecialchars($record['contract_number']) ?></td>
                </tr>
                <tr>
                    <th>Contact Number</th>
                    <td><?= htmlspecialchars($record['contact_number']) ?></td>
                </tr>
                <tr>
                    <th>Contract Period</th>
                    <td>
                        <?= date('Y-m-d', strtotime($record['contract_start_date'])) ?> to 
                        <?= date('Y-m-d', strtotime($record['contract_end_date'])) ?>
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
                        <span class="badge bg-primary"><?= ucfirst($record['payment_type']) ?></span>
                        <?php if ($record['payment_type'] === 'installment'): ?>
                            <br>Monthly: <?= $monthly_payment ?> KWD
                            <br>Progress: <?= $record['paid_installments'] ?>/<?= $record['number_of_installments'] ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($record['payment_type'] === 'installment'): ?>
                <tr>
                    <th>Down Payment</th>
                    <td><?= $down_payment ?> KWD</td>
                </tr>
                <?php endif; ?>
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
                    <td><span class="badge bg-<?= $record['status'] === 'paid' ? 'success' : 'warning' ?>"><?= ucfirst($record['status']) ?></span></td>
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

    <?php if (!empty($record['contract_document'])): ?>
    <div class="row mt-4">
        <div class="col-md-12">
            <h5>Contract Document</h5>
            <div class="mb-3">
                <a href="../../uploads/contracts/<?= htmlspecialchars($record['contract_document']) ?>" 
                   class="btn btn-primary" target="_blank">
                    <i class="fas fa-file-pdf"></i> View Contract
                </a>
            </div>
            <p class="text-muted">File: <?= htmlspecialchars($record['contract_document']) ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php
    $html = ob_get_clean();
    echo json_encode([
        'status' => 'success',
        'html' => $html
    ]);

} catch (Exception $e) {
    error_log("Error in view clinic record: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
