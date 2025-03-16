<?php
require_once '../../config/database.php';

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

    header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Equipment Details - <?= htmlspecialchars($equipment['equipment_name']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            border-bottom: 2px solid #333;
        }
        .section {
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            color: white;
        }
        .badge-success { background-color: #28a745; }
        .badge-warning { background-color: #ffc107; color: #000; }
        .badge-info { background-color: #17a2b8; }
        @media print {
            body { margin: 0; }
            .header { border-bottom-color: #000; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Equipment Details</h1>
        <p>Generated on: <?= date('Y-m-d H:i:s') ?></p>
    </div>

    <div class="section">
        <h2>Equipment Information</h2>
        <table>
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
                <td><span class="badge badge-info"><?= ucfirst(htmlspecialchars($equipment['contract_type'])) ?></span></td>
            </tr>
            <tr>
                <th>Contract Period</th>
                <td><?= date('Y-m-d', strtotime($equipment['contract_start_date'])) ?> to 
                    <?= date('Y-m-d', strtotime($equipment['contract_end_date'])) ?></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Payment Details</h2>
        <table>
            <tr>
                <th>Total Cost</th>
                <td><?= number_format($total_cost, 3) ?> KWD</td>
            </tr>
            <tr>
                <th>Payment Type</th>
                <td><?= ucfirst($equipment['payment_type']) ?></td>
            </tr>
            <?php if ($equipment['payment_type'] === 'installment'): ?>
            <tr>
                <th>Down Payment</th>
                <td><?= number_format($down_payment, 3) ?> KWD</td>
            </tr>
            <tr>
                <th>Monthly Installment</th>
                <td><?= number_format(($total_cost - $down_payment) / $equipment['num_installments'], 3) ?> KWD</td>
            </tr>
            <?php endif; ?>
            <tr>
                <th>Total Paid</th>
                <td><?= number_format($total_paid, 3) ?> KWD</td>
            </tr>
            <tr>
                <th>Remaining Amount</th>
                <td><?= number_format($remaining_amount, 3) ?> KWD</td>
            </tr>
            <tr>
                <th>Status</th>
                <td><span class="badge badge-<?= $equipment['status'] === 'paid' ? 'success' : 'warning' ?>">
                    <?= ucfirst($equipment['status']) ?></span></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Payment History</h2>
        <?php if (empty($payments)): ?>
            <p>No payment records found</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Reference No</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= $payment['formatted_date'] ?></td>
                            <td><?= number_format($payment['amount'], 3) ?> KWD</td>
                            <td><?= htmlspecialchars($payment['reference_no']) ?></td>
                            <td><span class="badge badge-success"><?= ucfirst($payment['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
<?php
} catch (Exception $e) {
    echo '<div style="color: red; padding: 20px;">' . $e->getMessage() . '</div>';
}
?>
