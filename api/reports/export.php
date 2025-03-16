<?php
require_once '../../config/database.php';
require_once '../../vendor/autoload.php'; // You'll need to install PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$database = Database::getInstance();
$db = $database->getConnection();

try {
    $conditions = [];
    $params = [];

    // Handle date range filter
    if (isset($_GET['startDate']) && $_GET['startDate']) {
        $conditions[] = "(start_date >= :start_date)";
        $params[':start_date'] = $_GET['startDate'];
    }
    if (isset($_GET['endDate']) && $_GET['endDate']) {
        $conditions[] = "(end_date <= :end_date)";
        $params[':end_date'] = $_GET['endDate'];
    }

    // Handle record type filter
    if (isset($_GET['recordType']) && $_GET['recordType']) {
        $conditions[] = "record_type = :record_type";
        $params[':record_type'] = $_GET['recordType'];
    }

    // Handle payment status filter
    if (isset($_GET['paymentStatus']) && $_GET['paymentStatus']) {
        if ($_GET['paymentStatus'] === 'overdue') {
            $conditions[] = "(payment_type = 'installment' AND next_payment_date < CURDATE())";
        } else {
            $conditions[] = "status = :payment_status";
            $params[':payment_status'] = $_GET['paymentStatus'];
        }
    }

    $query = "SELECT * FROM reports_view";
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    $query .= " ORDER BY start_date DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $headers = [
        'Type', 'Name/Record', 'Company', 'Contract Number', 
        'Start Date', 'End Date', 'Payment Type', 'Total Amount (KWD)',
        'Remaining Amount (KWD)', 'Monthly Amount (KWD)', 'Next Payment',
        'Status', 'Next Maintenance'
    ];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $col++;
    }

    // Add data
    $row = 2;
    foreach ($results as $item) {
        $sheet->setCellValue('A' . $row, $item['record_type']);
        $sheet->setCellValue('B' . $row, $item['name']);
        $sheet->setCellValue('C' . $row, $item['company_name']);
        $sheet->setCellValue('D' . $row, $item['contract_number']);
        $sheet->setCellValue('E' . $row, $item['start_date']);
        $sheet->setCellValue('F' . $row, $item['end_date']);
        $sheet->setCellValue('G' . $row, $item['payment_type']);
        $sheet->setCellValue('H' . $row, $item['total_amount']);
        $sheet->setCellValue('I' . $row, $item['remaining_amount']);
        $sheet->setCellValue('J' . $row, $item['monthly_amount']);
        $sheet->setCellValue('K' . $row, $item['next_payment_date']);
        $sheet->setCellValue('L' . $row, $item['status']);
        $sheet->setCellValue('M' . $row, $item['next_maintenance_date']);
        $row++;
    }

    // Auto-size columns
    foreach (range('A', 'M') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="clinic_management_report.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    error_log($e->getMessage());
    echo "Error generating report: " . $e->getMessage();
}
