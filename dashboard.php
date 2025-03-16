<?php
$page = 'dashboard';
include 'includes/header.php';
require_once 'config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get clinic records summary with correct field names and status
    $clinic_summary_query = "SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN status = 'pending' AND contract_end_date >= CURRENT_DATE THEN 1 ELSE 0 END) as active_contracts,
        SUM(total_cost) as total_value,
        COUNT(CASE WHEN contract_end_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) THEN 1 END) as upcoming_renewals,
        SUM(CASE WHEN status = 'pending' THEN remaining_amount ELSE 0 END) as total_pending_amount
    FROM clinic_records";
    
    $stmt = $db->prepare($clinic_summary_query);
    $stmt->execute();
    $clinic_summary = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get recent clinic records
    $recent_clinic_query = "SELECT * FROM clinic_records 
        ORDER BY created_at DESC 
        LIMIT 5";
    $stmt = $db->prepare($recent_clinic_query);
    $stmt->execute();
    $recent_clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<div class="main-content">
    <div class="container-fluid">
        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <!-- Total Records -->
            <div class="col-md-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body p-3">
                        <h2 class="fs-2"><?php echo $clinic_summary['total_records'] ?? '0'; ?></h2>
                        <p class="mb-0">Total Records</p>
                    </div>
                </div>
            </div>

            <!-- Active Contracts -->
            <div class="col-md-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body p-3">
                        <h2 class="fs-2"><?php echo $clinic_summary['active_contracts'] ?? '0'; ?></h2>
                        <p class="mb-0">Active Contracts</p>
                    </div>
                </div>
            </div>

            <!-- Total Contract Value -->
            <div class="col-md-3">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body p-3">
                        <h2 class="fs-2"><?php echo number_format($clinic_summary['total_value'] ?? 0, 3); ?> KWD</h2>
                        <p class="mb-0">Total Contract Value</p>
                        <p class="small mb-0">Pending: <?php echo number_format($clinic_summary['total_pending_amount'] ?? 0, 3); ?> KWD</p>
                    </div>
                </div>
            </div>

            <!-- Upcoming Renewals -->
            <div class="col-md-3">
                <div class="card bg-info text-white h-100">
                    <div class="card-body p-3">
                        <h2 class="fs-2"><?php echo $clinic_summary['upcoming_renewals'] ?? '0'; ?></h2>
                        <p class="mb-0">Upcoming Renewals</p>
                        <p class="small mb-0">Next 30 days</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Records Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Recent Clinic Records</h6>
                        <a href="clinic-records.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_clinics)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Record Type</th>
                                            <th>Company</th>
                                            <th>Contract #</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Total Cost</th>
                                            <th>Remaining</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_clinics as $record): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($record['record_type']); ?></td>
                                                <td><?php echo htmlspecialchars($record['company_name']); ?></td>
                                                <td><?php echo htmlspecialchars($record['contract_number']); ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($record['contract_start_date'])); ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($record['contract_end_date'])); ?></td>
                                                <td><?php echo number_format($record['total_cost'], 3); ?> KWD</td>
                                                <td><?php echo number_format($record['remaining_amount'], 3); ?> KWD</td>
                                                <td>
                                                    <?php 
                                                    $status_class = 'secondary';
                                                    if ($record['status'] == 'paid') {
                                                        $status_class = 'success';
                                                    } elseif ($record['status'] == 'pending') {
                                                        $status_class = 'warning';
                                                    } elseif ($record['status'] == 'overdue') {
                                                        $status_class = 'danger';
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo ucfirst($record['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center mb-0">No clinic records found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
