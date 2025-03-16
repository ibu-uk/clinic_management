<?php
require_once 'config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();

try {
    // Get equipment stats
    $query = "SELECT 
        COUNT(*) as total_equipment,
        SUM(CASE WHEN payment_type = 'installment' AND remaining_amount > 0 THEN 1 ELSE 0 END) as pending_payments,
        SUM(total_cost) as total_equipment_cost,
        SUM(remaining_amount) as total_remaining
    FROM equipment 
    WHERE status = 'active'";
    
    $stmt = $db->query($query);
    $equipment_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get maintenance stats
    $query = "SELECT 
        COUNT(*) as total_maintenance,
        SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_maintenance,
        SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_maintenance
    FROM maintenance 
    WHERE maintenance_date >= CURDATE()";
    
    $stmt = $db->query($query);
    $maintenance_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get payment stats for last 30 days
    $query = "SELECT 
        COUNT(*) as total_payments,
        SUM(amount) as total_paid,
        COUNT(DISTINCT record_id) as unique_records
    FROM payments 
    WHERE status = 'completed'
    AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    
    $stmt = $db->query($query);
    $payment_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get upcoming payments for next 30 days
    $query = "SELECT 
        e.equipment_name,
        e.contract_number,
        e.monthly_installment as amount,
        DATE_ADD(
            GREATEST(e.contract_start_date, CURDATE()),
            INTERVAL (
                FLOOR(
                    DATEDIFF(CURDATE(), e.contract_start_date) / 30
                ) + 1
            ) * 30 DAY
        ) as next_payment_date
    FROM equipment e
    WHERE e.payment_type = 'installment'
    AND e.status = 'active'
    AND e.remaining_amount > 0
    HAVING next_payment_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY next_payment_date ASC";
    
    $stmt = $db->query($query);
    $upcoming_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get contracts expiring in next 90 days
    $query = "SELECT 
        equipment_name,
        contract_number,
        contract_end_date,
        DATEDIFF(contract_end_date, CURDATE()) as days_remaining
    FROM equipment
    WHERE status = 'active'
    AND contract_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
    ORDER BY contract_end_date ASC";
    
    $stmt = $db->query($query);
    $expiring_contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Output stats cards
    ?>
    <div class="row">
        <!-- Equipment Stats -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-2"><?= number_format($equipment_stats['total_equipment']) ?></h4>
                    <div>Total Equipment</div>
                    <div class="small">Pending Payments: <?= number_format($equipment_stats['pending_payments']) ?></div>
                </div>
            </div>
        </div>

        <!-- Maintenance Stats -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-dark mb-4">
                <div class="card-body">
                    <h4 class="mb-2"><?= number_format($maintenance_stats['scheduled_maintenance']) ?></h4>
                    <div>Scheduled Maintenance</div>
                    <div class="small text-danger">Overdue: <?= number_format($maintenance_stats['overdue_maintenance']) ?></div>
                </div>
            </div>
        </div>

        <!-- Payment Stats -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-2"><?= number_format($payment_stats['total_payments']) ?></h4>
                    <div>Payments (30 days)</div>
                    <div class="small">Amount: <?= number_format($payment_stats['total_paid'], 3) ?> KWD</div>
                </div>
            </div>
        </div>

        <!-- Upcoming Payments -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-2"><?= count($upcoming_payments) ?></h4>
                    <div>Upcoming Payments</div>
                    <div class="small">Next 30 days</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Important Alerts</h5>
                    <div>
                        <button class="btn btn-sm btn-outline-primary me-2" onclick="filterAlerts('payments')">Payments</button>
                        <button class="btn btn-sm btn-outline-warning" onclick="filterAlerts('contracts')">Contracts</button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($upcoming_payments)): ?>
                        <div class="alert alert-warning payment-alert">
                            <h6 class="alert-heading"><i class="fas fa-money-bill me-2"></i>Upcoming Payments</h6>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Equipment</th>
                                            <th>Contract #</th>
                                            <th>Due Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcoming_payments as $payment): 
                                            $days_until = (strtotime($payment['next_payment_date']) - time()) / (60 * 60 * 24);
                                            $status_class = $days_until <= 7 ? 'bg-danger' : 'bg-warning';
                                            $status_text = $days_until <= 7 ? 'Due Soon' : 'Upcoming';
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($payment['equipment_name']) ?></td>
                                                <td><?= htmlspecialchars($payment['contract_number']) ?></td>
                                                <td><?= date('Y-m-d', strtotime($payment['next_payment_date'])) ?></td>
                                                <td><?= number_format($payment['amount'], 3) ?> KWD</td>
                                                <td><span class="badge <?= $status_class ?>"><?= $status_text ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($expiring_contracts)): ?>
                        <div class="alert alert-info contract-alert">
                            <h6 class="alert-heading"><i class="fas fa-file-contract me-2"></i>Contracts Expiring Soon</h6>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Equipment</th>
                                            <th>Contract #</th>
                                            <th>End Date</th>
                                            <th>Days Left</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($expiring_contracts as $contract): 
                                            $status_class = $contract['days_remaining'] <= 30 ? 'bg-danger' : 'bg-warning';
                                            $status_text = $contract['days_remaining'] <= 30 ? 'Critical' : 'Expiring Soon';
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($contract['equipment_name']) ?></td>
                                                <td><?= htmlspecialchars($contract['contract_number']) ?></td>
                                                <td><?= date('Y-m-d', strtotime($contract['contract_end_date'])) ?></td>
                                                <td><?= $contract['days_remaining'] ?> days</td>
                                                <td><span class="badge <?= $status_class ?>"><?= $status_text ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($upcoming_payments) && empty($expiring_contracts)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>No urgent alerts at this time
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    function filterAlerts(type) {
        const paymentAlerts = document.querySelectorAll('.payment-alert');
        const contractAlerts = document.querySelectorAll('.contract-alert');
        
        if (type === 'payments') {
            paymentAlerts.forEach(alert => alert.style.display = 'block');
            contractAlerts.forEach(alert => alert.style.display = 'none');
        } else if (type === 'contracts') {
            paymentAlerts.forEach(alert => alert.style.display = 'none');
            contractAlerts.forEach(alert => alert.style.display = 'block');
        }
    }
    </script>
    <?php
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error loading dashboard statistics: " . $e->getMessage() . "</div>";
}
?>
