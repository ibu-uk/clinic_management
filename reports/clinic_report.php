<?php
require_once __DIR__ . '/../config/database.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Clinic Record ID is required');
    }

    $database = Database::getInstance();
    $db = $database->getConnection();

    // Get clinic record details
    $query = "SELECT c.*, 
             (SELECT COALESCE(SUM(p.amount), 0) 
              FROM payments p 
              WHERE p.record_type = 'clinic_record'
              AND p.record_id = c.id
              AND p.status = 'completed') as total_paid
             FROM clinic_records c
             WHERE c.id = ?";

    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $clinic = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$clinic) {
        throw new Exception('Clinic record not found');
    }

    // Get payment history
    $query = "SELECT p.*, DATE_FORMAT(p.payment_date, '%Y-%m-%d') as formatted_date 
             FROM payments p 
             WHERE p.record_type = 'clinic_record' 
             AND p.record_id = ? 
             AND p.status = 'completed'
             ORDER BY p.payment_date DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate amounts
    $total_cost = floatval($clinic['total_cost']);
    $down_payment = floatval($clinic['down_payment']);
    $total_paid = floatval($clinic['total_paid']);
    
    if ($clinic['payment_type'] === 'one_time') {
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
    <title>Clinic Record Details - <?= htmlspecialchars($clinic['record_type']) ?></title>
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
        <h1>Clinic Record Details</h1>
        <p>Generated on: <?= date('Y-m-d H:i:s') ?></p>
    </div>

    <div class="section">
        <h2>Record Information</h2>
        <table>
            <tr>
                <th>Record Type</th>
                <td><span class="badge badge-info"><?= ucfirst(htmlspecialchars($clinic['record_type'])) ?></span></td>
            </tr>
            <tr>
                <th>Company Name</th>
                <td><?= htmlspecialchars($clinic['company_name']) ?></td>
            </tr>
            <tr>
                <th>Contract Number</th>
                <td><?= htmlspecialchars($clinic['contract_number']) ?></td>
            </tr>
            <tr>
                <th>Contact Number</th>
                <td><?= htmlspecialchars($clinic['contact_number']) ?></td>
            </tr>
            <tr>
                <th>Contract Period</th>
                <td><?= date('Y-m-d', strtotime($clinic['contract_start_date'])) ?> to 
                    <?= date('Y-m-d', strtotime($clinic['contract_end_date'])) ?></td>
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
                <td><?= ucfirst($clinic['payment_type']) ?></td>
            </tr>
            <?php if ($clinic['payment_type'] === 'installment'): ?>
            <tr>
                <th>Down Payment</th>
                <td><?= number_format($down_payment, 3) ?> KWD</td>
            </tr>
            <tr>
                <th>Monthly Payment</th>
                <td><?= number_format($clinic['monthly_payment'], 3) ?> KWD</td>
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
                <td><span class="badge badge-<?= $clinic['status'] === 'paid' ? 'success' : 'warning' ?>">
                    <?= ucfirst($clinic['status']) ?></span></td>
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

    <?php if (!empty($clinic['contract_document'])): ?>
    <div class="section">
        <h2>Contract Document</h2>
        <p>Document: <?= htmlspecialchars($clinic['contract_document']) ?></p>
    </div>
    <?php endif; ?>

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
