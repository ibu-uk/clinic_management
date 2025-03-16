<?php
$page_title = "Maintenance Schedule";
include 'includes/header.php';
require_once 'config/database.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    // Handle maintenance status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maintenance_id'])) {
        $update_query = "UPDATE maintenance 
                        SET status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $stmt->execute([$_POST['status'], $_POST['notes'], $_POST['maintenance_id']]);
        
        echo "<div class='alert alert-success alert-dismissible fade show' style='margin: 1rem auto; max-width: 1200px;'>
                Maintenance status updated successfully
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }

    // Get filter values
    $status_filter = $_GET['status'] ?? 'all';
    $date_filter = $_GET['date'] ?? 'all';

    // Build query based on filters
    $query = "SELECT m.*, e.equipment_name, e.equipment_model, e.company_name, e.contract_number
              FROM maintenance m
              JOIN equipment e ON m.equipment_id = e.id
              WHERE 1=1";

    if ($status_filter !== 'all') {
        $query .= " AND m.status = '$status_filter'";
    }

    switch ($date_filter) {
        case 'upcoming':
            $query .= " AND m.maintenance_date > CURRENT_DATE";
            break;
        case 'today':
            $query .= " AND DATE(m.maintenance_date) = CURRENT_DATE";
            break;
        case 'past':
            $query .= " AND m.maintenance_date < CURRENT_DATE";
            break;
    }

    $query .= " ORDER BY m.maintenance_date ASC";
    $stmt = $db->query($query);
    $maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">
    <div class="container py-4" style="max-width: 1200px;">
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form class="row g-3">
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label">Status Filter</label>
                        <select name="status" class="form-select shadow-none" onchange="this.form.submit()">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                            <option value="scheduled" <?= $status_filter === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label">Date Filter</label>
                        <select name="date" class="form-select shadow-none" onchange="this.form.submit()">
                            <option value="all" <?= $date_filter === 'all' ? 'selected' : '' ?>>All Dates</option>
                            <option value="upcoming" <?= $date_filter === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            <option value="today" <?= $date_filter === 'today' ? 'selected' : '' ?>>Today</option>
                            <option value="past" <?= $date_filter === 'past' ? 'selected' : '' ?>>Past</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if (count($maintenance) > 0): ?>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4">Equipment</th>
                                    <th>Model</th>
                                    <th>Company</th>
                                    <th>Contract #</th>
                                    <th>Maintenance Date</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th class="text-end px-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($maintenance as $item): ?>
                                    <tr>
                                        <td class="px-4"><?= htmlspecialchars($item['equipment_name']) ?></td>
                                        <td><?= htmlspecialchars($item['equipment_model']) ?></td>
                                        <td><?= htmlspecialchars($item['company_name']) ?></td>
                                        <td><?= htmlspecialchars($item['contract_number']) ?></td>
                                        <td><?= date('Y-m-d', strtotime($item['maintenance_date'])) ?></td>
                                        <td>
                                            <?php
                                            $badge_class = 'bg-info';
                                            switch ($item['status']) {
                                                case 'completed':
                                                    $badge_class = 'bg-success';
                                                    break;
                                                case 'scheduled':
                                                    $badge_class = 'bg-warning text-dark';
                                                    break;
                                                case 'cancelled':
                                                    $badge_class = 'bg-danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?= $badge_class ?>">
                                                <?= ucfirst($item['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($item['notes'] ?? '') ?></td>
                                        <td class="text-end px-4">
                                            <button class="btn btn-sm btn-primary" onclick="updateMaintenance(<?= $item['id'] ?>, '<?= $item['status'] ?>', '<?= addslashes($item['notes'] ?? '') ?>')">
                                                <i class="fas fa-edit me-1"></i> Update
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No maintenance records found.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Update Maintenance Modal -->
<div class="modal fade" id="maintenanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Maintenance Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="maintenance_id" id="maintenance_id">
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select shadow-none" required>
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea name="notes" id="notes" class="form-control shadow-none" rows="3" placeholder="Enter maintenance notes, findings, or reasons for cancellation"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateMaintenance(id, currentStatus, notes) {
    document.getElementById('maintenance_id').value = id;
    document.getElementById('status').value = currentStatus;
    document.getElementById('notes').value = notes;
    const modal = new bootstrap.Modal(document.getElementById('maintenanceModal'));
    modal.show();
}
</script>

<?php
} catch (Exception $e) {
    error_log("Error in maintenance page: " . $e->getMessage());
    echo "<div class='alert alert-danger alert-dismissible fade show' style='margin: 1rem auto; max-width: 1200px;'>
            Error loading maintenance records. Please try again later.
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
}

include 'includes/footer.php';
?>
