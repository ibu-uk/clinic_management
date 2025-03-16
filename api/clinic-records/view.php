<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Record ID is required');
    }

    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get record details
    $query = "SELECT * FROM clinic_records WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        throw new Exception('Record not found');
    }
    
    // Get payment history
    $payment_query = "SELECT * FROM payments WHERE record_type = 'clinic_record' AND record_id = ? AND status = 'completed' ORDER BY payment_date DESC";
    $stmt = $db->prepare($payment_query);
    $stmt->execute([$_GET['id']]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates and numbers
    $record['contract_start_date'] = date('Y-m-d', strtotime($record['contract_start_date']));
    $record['contract_end_date'] = date('Y-m-d', strtotime($record['contract_end_date']));
    
    // Check for overdue status
    $is_overdue = false;
    if ($record['payment_type'] === 'installment' && $record['next_payment_date']) {
        $next_payment = new DateTime($record['next_payment_date']);
        $today = new DateTime();
        $is_overdue = $next_payment < $today && $record['remaining_amount'] > 0;
    }
    
    // Calculate payment progress
    $total_paid = array_sum(array_column($payments, 'amount'));
    $progress_percentage = ($total_paid / $record['total_cost']) * 100;
    
    // Generate HTML
    $html = '
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-12 text-end">
                <a href="/clinic_management/reports/clinic_report.php?id=' . $record['id'] . '" 
                   class="btn btn-primary me-2" target="_blank">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </a>
                <button onclick="window.open(\'/clinic_management/reports/print_clinic.php?id=' . $record['id'] . '\', \'_blank\', \'width=800,height=600\')" 
                        class="btn btn-secondary">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <h6>Record Information</h6>
                <table class="table table-sm">
                    <tr>
                        <th>Record Type:</th>
                        <td>' . htmlspecialchars($record['record_type']) . '</td>
                    </tr>
                    <tr>
                        <th>Company Name:</th>
                        <td>' . htmlspecialchars($record['company_name']) . '</td>
                    </tr>
                    <tr>
                        <th>Contract Number:</th>
                        <td>' . htmlspecialchars($record['contract_number']) . '</td>
                    </tr>
                    <tr>
                        <th>Contact Number:</th>
                        <td>' . htmlspecialchars($record['contact_number']) . '</td>
                    </tr>
                    <tr>
                        <th>Contract Period:</th>
                        <td>' . $record['contract_start_date'] . ' to ' . $record['contract_end_date'] . '</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Payment Information</h6>
                <table class="table table-sm">
                    <tr>
                        <th>Total Cost:</th>
                        <td>' . number_format($record['total_cost'], 3) . ' KWD</td>
                    </tr>
                    <tr>
                        <th>Payment Type:</th>
                        <td>' . ucfirst($record['payment_type']) . '</td>
                    </tr>';
    
    if ($record['payment_type'] === 'installment') {
        $html .= '
                    <tr>
                        <th>Down Payment:</th>
                        <td>' . number_format($record['down_payment'], 3) . ' KWD</td>
                    </tr>
                    <tr>
                        <th>Monthly Payment:</th>
                        <td>' . number_format($record['monthly_payment'], 3) . ' KWD</td>
                    </tr>
                    <tr>
                        <th>Number of Installments:</th>
                        <td>' . $record['number_of_installments'] . '</td>
                    </tr>
                    <tr>
                        <th>Next Payment Date:</th>
                        <td>' . ($record['next_payment_date'] ?? 'N/A') . '</td>
                    </tr>';
    }
    
    $html .= '
                    <tr>
                        <th>Remaining Amount:</th>
                        <td>' . number_format($record['remaining_amount'], 3) . ' KWD</td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td><span class="badge ' . ($is_overdue ? 'bg-danger' : ($record['remaining_amount'] > 0 ? 'bg-warning' : 'bg-success')) . '">' . 
                            ($is_overdue ? 'Overdue' : ($record['remaining_amount'] > 0 ? 'Pending' : 'Paid')) . '</span></td>
                    </tr>
                </table>
            </div>
        </div>';
    
    if ($record['payment_type'] === 'installment') {
        $html .= '
        <div class="row mt-3">
            <div class="col-12">
                <h6>Payment Progress</h6>
                <div class="progress mb-2">
                    <div class="progress-bar ' . getProgressBarClass($progress_percentage) . '" 
                         role="progressbar" 
                         style="width: ' . $progress_percentage . '%" 
                         aria-valuenow="' . $progress_percentage . '" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        ' . number_format($progress_percentage, 1) . '%
                    </div>
                </div>
            </div>
        </div>';
        
        if ($payments) {
            $html .= '
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Payment History</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>';
            
            foreach ($payments as $payment) {
                $html .= '
                                <tr>
                                    <td>' . date('Y-m-d', strtotime($payment['payment_date'])) . '</td>
                                    <td>' . number_format($payment['amount'], 3) . ' KWD</td>
                                    <td>' . htmlspecialchars($payment['reference_no']) . '</td>
                                </tr>';
            }
            
            $html .= '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>';
        } else {
            $html .= '
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> No payments have been made yet. 
                        ' . ($is_overdue ? '<strong>Payment is overdue!</strong>' : '') . '
                    </div>
                </div>
            </div>';
        }
    }
    
    if ($record['notes']) {
        $html .= '
        <div class="row mt-3">
            <div class="col-12">
                <h6>Notes</h6>
                <p class="text-muted">' . nl2br(htmlspecialchars($record['notes'])) . '</p>
            </div>
        </div>';
    }
    
    $html .= '</div>';
    
    echo json_encode([
        'status' => 'success',
        'html' => $html
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

function getProgressBarClass($percentage) {
    if ($percentage < 25) return 'bg-danger';
    if ($percentage < 75) return 'bg-warning';
    return 'bg-success';
}
?>
