<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASEPATH', dirname(__FILE__));

try {
    // Include database configuration
    require_once BASEPATH . '/config/database.php';
    
    // Test database connection
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    // Handle maintenance update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maintenance_id'])) {
        $update_query = "UPDATE maintenance 
                        SET status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->execute(['completed', $_POST['notes'] ?? '', $_POST['maintenance_id']]);
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // If connection is successful, include the rest of the application
    $page = 'dashboard';
    include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid px-4">
        <h1 class="mt-4">Dashboard</h1>
        
        <!-- Include Notifications -->
        <?php include 'includes/notifications.php'; ?>
        
        <!-- Include Dashboard Stats -->
        <?php include 'includes/dashboard-stats.php'; ?>
        
        <div class="row">
            <!-- Maintenance Table -->
            <div class="col-xl-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-tools me-1"></i>
                        Maintenance Schedule
                        <div class="float-end">
                            <select id="maintenanceFilter" class="form-select form-select-sm" style="width: auto; display: inline-block;">
                                <option value="all">All</option>
                                <option value="today">Today</option>
                                <option value="upcoming">Upcoming</option>
                                <option value="overdue">Overdue</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Equipment</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="maintenanceTableBody">
                                    <?php
                                    $query = "SELECT m.*, e.equipment_name 
                                             FROM maintenance m
                                             JOIN equipment e ON m.equipment_id = e.id
                                             WHERE m.status != 'completed'
                                             ORDER BY m.maintenance_date ASC 
                                             LIMIT 10";
                                    $stmt = $conn->query($query);
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $date = new DateTime($row['maintenance_date']);
                                        $now = new DateTime();
                                        $status = $row['status'];
                                        
                                        if ($status !== 'completed' && $date < $now) {
                                            $status = 'overdue';
                                        }
                                        
                                        $badge_class = match($status) {
                                            'completed' => 'bg-success',
                                            'scheduled' => 'bg-warning',
                                            'overdue' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        
                                        echo "<tr data-date='" . $date->format('Y-m-d') . "' data-status='$status' data-maintenance-id='{$row['id']}'>
                                                <td>" . htmlspecialchars($row['equipment_name']) . "</td>
                                                <td>" . $date->format('Y-m-d') . "</td>
                                                <td><span class='badge {$badge_class}'>" . ucfirst($status) . "</span></td>
                                                <td>
                                                    <button class='btn btn-sm btn-success' onclick='completeMaintenance({$row['id']})'>
                                                        Mark Complete
                                                    </button>
                                                </td>
                                              </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-2">
                            <a href="maintenance.php" class="btn btn-primary btn-sm">View All</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Equipment -->
            <div class="col-xl-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-tools me-1"></i>
                        Recent Equipment
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Equipment</th>
                                        <th>Contract #</th>
                                        <th>Total Cost</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT * FROM equipment 
                                             ORDER BY created_at DESC 
                                             LIMIT 5";
                                    $stmt = $conn->query($query);
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<tr>
                                                <td>{$row['equipment_name']}</td>
                                                <td>{$row['contract_number']}</td>
                                                <td>" . number_format($row['total_cost'], 3) . " KWD</td>
                                                <td>{$row['status']}</td>
                                              </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Maintenance Complete Modal -->
<div class="modal fade" id="maintenanceModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complete Maintenance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="maintenanceForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="maintenance_id" id="maintenance_id">
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Enter maintenance notes or findings"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Complete Maintenance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function completeMaintenance(id) {
    document.getElementById('maintenance_id').value = id;
    const modal = new bootstrap.Modal(document.getElementById('maintenanceModal'));
    modal.show();
}

document.getElementById('maintenanceFilter').addEventListener('change', function() {
    const filter = this.value;
    const rows = document.querySelectorAll('#maintenanceTableBody tr');
    const today = new Date().toISOString().split('T')[0];
    
    rows.forEach(row => {
        const date = row.dataset.date;
        const status = row.dataset.status;
        
        switch(filter) {
            case 'today':
                row.style.display = date === today ? '' : 'none';
                break;
            case 'upcoming':
                row.style.display = (date > today && status === 'scheduled') ? '' : 'none';
                break;
            case 'overdue':
                row.style.display = status === 'overdue' ? '' : 'none';
                break;
            default:
                row.style.display = '';
        }
    });
});

// Add form submit handler to use AJAX
document.getElementById('maintenanceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('api/maintenance/update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('maintenanceModal')).hide();
            
            // Remove the completed maintenance row
            const row = document.querySelector(`tr[data-maintenance-id="${formData.get('maintenance_id')}"]`);
            if (row) row.remove();
            
            // Refresh the stats
            fetch('api/dashboard/get-stats.php')
                .then(response => response.json())
                .then(stats => {
                    // Update maintenance stats
                    document.getElementById('scheduled_maintenance').textContent = stats.scheduled_maintenance;
                    document.getElementById('overdue_maintenance').textContent = stats.overdue_maintenance;
                    document.getElementById('completed_maintenance').textContent = stats.completed_maintenance;
                });
            
            // Show success message
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                Maintenance marked as completed
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.container-fluid').insertBefore(alert, document.querySelector('.row'));
            
            // Auto dismiss alert after 3 seconds
            setTimeout(() => {
                alert.remove();
            }, 3000);
        } else {
            alert('Error updating maintenance: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating maintenance. Please try again.');
    });
});
</script>

<?php include 'includes/footer.php'; 
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>