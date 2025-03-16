<?php
$page = 'payment_history';
include 'includes/header.php';
require_once 'config/database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    $type = $_GET['type'] ?? '';
    $id = $_GET['id'] ?? '';
    
    if (!$type || !$id) {
        throw new Exception('Record type and ID are required');
    }
    
    // Get record details
    if ($type === 'equipment') {
        $query = "
            SELECT 
                equipment_name as name,
                contract_number,
                COALESCE(total_cost, 0) as total_cost,
                COALESCE(remaining_amount, 0) as remaining_amount,
                payment_type,
                CASE 
                    WHEN payment_type = 'installment' THEN COALESCE(monthly_payment, 0)
                    ELSE NULL 
                END as monthly_payment,
                CASE 
                    WHEN payment_type = 'installment' THEN next_payment_date 
                    ELSE NULL 
                END as next_payment_date,
                status
            FROM equipment
            WHERE id = :id
        ";
    } else {
        $query = "
            SELECT 
                company_name as name,
                contract_number,
                COALESCE(total_cost, 0) as total_cost,
                COALESCE(remaining_amount, 0) as remaining_amount,
                payment_type,
                CASE 
                    WHEN payment_type = 'installment' THEN COALESCE(monthly_payment, 0)
                    ELSE NULL 
                END as monthly_payment,
                CASE 
                    WHEN payment_type = 'installment' THEN next_payment_date 
                    ELSE NULL 
                END as next_payment_date,
                status
            FROM clinic_records
            WHERE id = :id
        ";
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        throw new Exception('Record not found');
    }
    
    // Get payment history
    $query = "
        SELECT 
            payment_date,
            reference_no,
            COALESCE(amount, 0) as amount,
            status,
            created_at
        FROM payments
        WHERE record_type = :type 
        AND record_id = :id
        ORDER BY payment_date DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':type' => $type,
        ':id' => $id
    ]);
    
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error in payment_history.php: " . $e->getMessage());
}
?>

<div class="main-content">
    <div class="container-fluid">
        <!-- Back Button -->
        <div class="mb-3">
            <a href="payments.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Payments
            </a>
        </div>
        
        <!-- Record Details -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Record Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label text-muted">Name</label>
                            <div class="fw-bold"><?php echo htmlspecialchars($record['name']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label text-muted">Contract Number</label>
                            <div class="fw-bold"><?php echo htmlspecialchars($record['contract_number']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label text-muted">Total Cost</label>
                            <div class="fw-bold"><?php echo number_format($record['total_cost'], 3); ?> KWD</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label text-muted">Remaining Amount</label>
                            <div class="fw-bold"><?php echo number_format($record['remaining_amount'], 3); ?> KWD</div>
                        </div>
                    </div>
                </div>
                
                <?php if ($record['payment_type'] === 'installment'): ?>
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label text-muted">Monthly Payment</label>
                            <div class="fw-bold"><?php echo number_format($record['monthly_payment'], 3); ?> KWD</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label text-muted">Next Payment Date</label>
                            <div class="fw-bold"><?php echo $record['next_payment_date'] ?: '-'; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label text-muted">Payment Type</label>
                            <div class="fw-bold">Installment</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label text-muted">Status</label>
                            <div>
                                <span class="badge bg-<?php echo $record['status'] === 'paid' ? 'success' : 
                                                            ($record['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                    <?php echo ucfirst($record['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Payment History -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Payment History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Payment Date</th>
                                <th>Reference Number</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No payment history found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d', strtotime($payment['payment_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($payment['reference_no']); ?></td>
                                    <td><?php echo number_format($payment['amount'], 3); ?> KWD</td>
                                    <td>
                                        <span class="badge bg-<?php echo $payment['status'] === 'completed' ? 'success' : 
                                                                    ($payment['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($payment['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
