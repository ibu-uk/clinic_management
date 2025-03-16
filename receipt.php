<?php
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    die('Payment ID is required');
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    $query = "SELECT p.*, 
              CASE 
                WHEN p.record_type IN ('new', 'renew', 'upgrade', 'equipment') THEN e.equipment_name
                ELSE cr.company_name
              END as name,
              CASE 
                WHEN p.record_type IN ('new', 'renew', 'upgrade', 'equipment') THEN e.contract_number
                ELSE cr.contract_number
              END as contract_number
              FROM payments p
              LEFT JOIN equipment e ON p.record_id = e.id 
                AND p.record_type IN ('new', 'renew', 'upgrade', 'equipment')
              LEFT JOIN clinic_records cr ON p.record_id = cr.id 
                AND p.record_type IN ('rent', 'insurance', 'clinic_license', 'fire_safety', 'clinic_record')
              WHERE p.id = :id";

    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $_GET['id']]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        die('Payment not found');
    }
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .receipt {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 200px;
            margin-bottom: 20px;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .details {
            margin-bottom: 30px;
        }
        .row {
            display: flex;
            margin-bottom: 10px;
        }
        .label {
            font-weight: bold;
            width: 150px;
        }
        .value {
            flex: 1;
        }
        .amount {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background: #f8f9fa;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
        @media print {
            body {
                padding: 0;
            }
            .receipt {
                border: none;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <img src="assets/images/logo.png" alt="Logo" class="logo">
            <div class="title">Payment Receipt</div>
            <div>Receipt #: <?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></div>
        </div>

        <div class="details">
            <div class="row">
                <div class="label">Date:</div>
                <div class="value"><?php echo $payment['payment_date']; ?></div>
            </div>
            <div class="row">
                <div class="label">Name/Company:</div>
                <div class="value"><?php echo htmlspecialchars($payment['name']); ?></div>
            </div>
            <div class="row">
                <div class="label">Contract Number:</div>
                <div class="value"><?php echo htmlspecialchars($payment['contract_number']); ?></div>
            </div>
            <div class="row">
                <div class="label">Payment Type:</div>
                <div class="value"><?php echo ucfirst($payment['record_type']); ?></div>
            </div>
            <div class="row">
                <div class="label">Payment Method:</div>
                <div class="value"><?php echo ucfirst($payment['payment_method']); ?></div>
            </div>
            <div class="row">
                <div class="label">Reference Number:</div>
                <div class="value"><?php echo $payment['reference_number'] ?: '-'; ?></div>
            </div>
        </div>

        <div class="amount">
            Amount Paid: <?php echo number_format($payment['amount'], 3); ?> KWD
        </div>

        <div class="footer">
            Thank you for your payment. This is a computer-generated receipt and does not require a signature.
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()">Print Receipt</button>
    </div>
</body>
</html>
