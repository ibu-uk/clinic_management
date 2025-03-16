<?php
require_once '../config/database.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    // Updated query to correctly count maintenance records
    $query = "SELECT e.*, 
             (SELECT COUNT(*) FROM payments p 
              WHERE p.record_type = 'equipment'
              AND p.record_id = e.id 
              AND p.status = 'completed') as paid_installments,
             (SELECT COALESCE(SUM(p.amount), 0) 
              FROM payments p 
              WHERE p.record_type = 'equipment'
              AND p.record_id = e.id
              AND p.status = 'completed') as total_paid,
             (SELECT COUNT(*) FROM maintenance m 
              WHERE m.equipment_id = e.id 
              AND m.status = 'completed'
              AND m.maintenance_date <= CURRENT_DATE) as completed_maintenance,
             (SELECT COUNT(*) FROM maintenance m 
              WHERE m.equipment_id = e.id 
              AND m.status = 'scheduled'
              AND m.maintenance_date > CURRENT_DATE) as scheduled_maintenance
             FROM equipment e
             ORDER BY e.created_at DESC";

    $stmt = $db->query($query);
    $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($equipment) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Equipment</th>
                        <th>Model</th>
                        <th>Company</th>
                        <th>Contract #</th>
                        <th>Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Total Cost</th>
                        <th>Down Payment</th>
                        <th>Payment Status</th>
                        <th>Monthly Amount</th>
                        <th>Paid Amount</th>
                        <th>Remaining</th>
                        <th>Maintenance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipment as $item): 
                        // Calculate monthly installment
                        $total_after_down = floatval($item['total_cost']) - floatval($item['down_payment']);
                        $num_installments = intval($item['num_installments']) ?: 12;
                        $monthly_amount = $total_after_down / $num_installments;

                        // Calculate payment status
                        $total_cost = floatval($item['total_cost']);
                        $down_payment = floatval($item['down_payment']);
                        $total_paid = floatval($item['total_paid']);
                        
                        // Calculate remaining amount based on payment type
                        if ($item['payment_type'] === 'one_time') {
                            $remaining_amount = $total_cost - $total_paid;
                        } else {
                            $remaining_amount = $total_cost - $down_payment - $total_paid;
                        }

                        // Calculate payment status
                        if ($item['payment_type'] === 'one_time') {
                            if ($total_paid >= $total_cost) {
                                $payment_status = "<span class='badge bg-success'>Paid</span>";
                            } else {
                                $payment_status = "<span class='badge bg-warning'>Unpaid</span>";
                            }
                        } else {
                            // For installment
                            if ($total_paid >= ($total_cost - $down_payment)) {
                                $payment_status = "<span class='badge bg-success'>Paid</span>";
                            } else if ($total_paid > 0) {
                                $payment_status = "<span class='badge bg-info'>Partially Paid</span>";
                            } else if ($down_payment > 0) {
                                $payment_status = "<span class='badge bg-warning'>Down Payment Only</span>";
                            } else {
                                $payment_status = "<span class='badge bg-danger'>No Payment</span>";
                            }
                        }

                        // Maintenance status
                        $completed = intval($item['completed_maintenance']);
                        $scheduled = intval($item['scheduled_maintenance']);
                        $maintenance_html = "";
                        
                        // Get maintenance schedule intervals
                        $schedule_intervals = !empty($item['maintenance_schedule']) ? explode(',', $item['maintenance_schedule']) : [];
                        
                        if (!empty($schedule_intervals)) {
                            $maintenance_html .= "<div class='mb-1'>";
                            foreach ($schedule_intervals as $interval) {
                                $maintenance_html .= "<span class='badge bg-info me-1'>Every {$interval} Months</span>";
                            }
                            $maintenance_html .= "</div>";
                            
                            if ($completed > 0 || $scheduled > 0) {
                                if ($completed > 0) {
                                    $maintenance_html .= "<span class='badge bg-success me-1'>Completed: {$completed}</span>";
                                }
                                if ($scheduled > 0) {
                                    $maintenance_html .= "<span class='badge bg-warning me-1'>Scheduled: {$scheduled}</span>";
                                }
                            }
                        } else {
                            $maintenance_html = "<span class='badge bg-secondary'>No Maintenance</span>";
                        }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($item['equipment_name']) ?></td>
                            <td><?= htmlspecialchars($item['equipment_model']) ?></td>
                            <td><?= htmlspecialchars($item['company_name']) ?></td>
                            <td><?= htmlspecialchars($item['contract_number']) ?></td>
                            <td><span class="badge bg-info"><?= ucfirst(htmlspecialchars($item['contract_type'])) ?></span></td>
                            <td><?= date('Y-m-d', strtotime($item['contract_start_date'])) ?></td>
                            <td><?= date('Y-m-d', strtotime($item['contract_end_date'])) ?></td>
                            <td><?= number_format($total_cost, 3) ?> KWD</td>
                            <td><?= number_format($down_payment, 3) ?> KWD</td>
                            <td><?= $payment_status ?></td>
                            <td><?= $item['payment_type'] === 'installment' ? number_format($monthly_amount, 3) . ' KWD' : '-' ?></td>
                            <td><?= number_format($total_paid, 3) ?> KWD</td>
                            <td>
                                <span class="badge <?= $remaining_amount > 0 ? 'bg-warning' : 'bg-success' ?>">
                                    <?= number_format($remaining_amount, 3) ?> KWD
                                </span>
                            </td>
                            <td><?= $maintenance_html ?></td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewEquipment(<?= $item['id'] ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-primary" onclick="editEquipment(<?= $item['id'] ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No equipment records found.</div>
    <?php endif;

} catch (Exception $e) {
    error_log("Error in equipment list: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Error loading equipment list. Please try again later.</div>";
}
?>
