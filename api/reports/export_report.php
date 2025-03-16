<?php
require_once '../../config/database.php';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="clinic_report.xls"');
header('Cache-Control: max-age=0');

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();

    // Get parameters
    $type = isset($_GET['type']) ? $_GET['type'] : 'all';
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';

    // Start Excel content
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\">\n";
    echo "<Worksheet ss:Name=\"Report\">\n";
    echo "<Table>\n";

    // Headers
    echo "<Row>\n";
    echo "<Cell><Data ss:Type=\"String\">Type</Data></Cell>\n";
    echo "<Cell><Data ss:Type=\"String\">Name/Record</Data></Cell>\n";
    echo "<Cell><Data ss:Type=\"String\">Company</Data></Cell>\n";
    echo "<Cell><Data ss:Type=\"String\">Contract/Record #</Data></Cell>\n";
    echo "<Cell><Data ss:Type=\"String\">Start Date</Data></Cell>\n";
    echo "<Cell><Data ss:Type=\"String\">End Date</Data></Cell>\n";
    echo "<Cell><Data ss:Type=\"String\">Payment Type</Data></Cell>\n";
    echo "<Cell><Data ss:Type=\"String\">Payment Method</Data></Cell>\n";
    echo "<Cell><Data ss:Type=\"String\">Total Amount (KWD)</Data></Cell>\n";
    echo "<Cell><Data ss:Type=\"String\">Status</Data></Cell>\n";
    echo "</Row>\n";

    // Equipment Data
    if ($type == 'all' || $type == 'equipment') {
        $equipmentQuery = "
            SELECT 
                e.*,
                m.maintenance_date,
                m.status as maintenance_status,
                c.company_name
            FROM equipment e
            LEFT JOIN maintenance m ON e.id = m.equipment_id
            LEFT JOIN companies c ON e.company_id = c.id
            WHERE 1=1";
        
        if ($startDate && $endDate) {
            $equipmentQuery .= " AND (e.purchase_date BETWEEN :start_date AND :end_date 
                                OR m.maintenance_date BETWEEN :start_date AND :end_date)";
        }
        
        $stmt = $conn->prepare($equipmentQuery);
        if ($startDate && $endDate) {
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
        }
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<Row>\n";
            echo "<Cell><Data ss:Type=\"String\">Equipment</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($row['equipment_name']) . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($row['company_name']) . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($row['serial_number']) . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . $row['purchase_date'] . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . ($row['maintenance_date'] ?? '') . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">One-time</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">-</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"Number\">" . number_format($row['price'], 3) . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . ($row['maintenance_status'] ?? 'Active') . "</Data></Cell>\n";
            echo "</Row>\n";
        }
    }

    // Clinic Records Data
    if ($type == 'all' || $type == 'clinic') {
        $clinicQuery = "
            SELECT 
                cr.*,
                p.amount as payment_amount,
                p.payment_date,
                p.payment_type,
                p.payment_method
            FROM clinic_records cr
            LEFT JOIN payments p ON cr.id = p.record_id
            WHERE 1=1";
        
        if ($startDate && $endDate) {
            $clinicQuery .= " AND cr.record_date BETWEEN :start_date AND :end_date";
        }
        
        $stmt = $conn->prepare($clinicQuery);
        if ($startDate && $endDate) {
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
        }
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<Row>\n";
            echo "<Cell><Data ss:Type=\"String\">Clinic Record</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">Record #" . htmlspecialchars($row['record_number']) . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">-</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($row['record_number']) . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . $row['record_date'] . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . ($row['payment_date'] ?? '') . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . ($row['payment_type'] ?? '-') . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . ($row['payment_method'] ?? '-') . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"Number\">" . number_format($row['payment_amount'] ?? 0, 3) . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . $row['status'] . "</Data></Cell>\n";
            echo "</Row>\n";
        }
    }

    // Payments Data
    if ($type == 'all' || $type == 'payments') {
        $paymentsQuery = "
            SELECT 
                p.*,
                cr.record_number,
                cr.record_date
            FROM payments p
            LEFT JOIN clinic_records cr ON p.record_id = cr.id
            WHERE 1=1";
        
        if ($startDate && $endDate) {
            $paymentsQuery .= " AND p.payment_date BETWEEN :start_date AND :end_date";
        }
        
        $stmt = $conn->prepare($paymentsQuery);
        if ($startDate && $endDate) {
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
        }
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<Row>\n";
            echo "<Cell><Data ss:Type=\"String\">Payment</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">Payment for Record #" . htmlspecialchars($row['record_number']) . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">-</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($row['record_number']) . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . $row['payment_date'] . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">-</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . $row['payment_type'] . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . $row['payment_method'] . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"Number\">" . number_format($row['amount'], 3) . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">Completed</Data></Cell>\n";
            echo "</Row>\n";
        }
    }

    // Close Excel tags
    echo "</Table>\n";
    echo "</Worksheet>\n";
    echo "</Workbook>";

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
