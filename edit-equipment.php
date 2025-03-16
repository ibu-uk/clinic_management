<?php
$page = 'equipment';
include 'includes/header.php';
require_once 'config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: equipment.php');
    exit;
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get equipment details
    $query = "SELECT * FROM equipment WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$equipment) {
        header('Location: equipment.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Error loading equipment: " . $e->getMessage());
    header('Location: equipment.php');
    exit;
}
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Edit Equipment</h4>
                    </div>
                    <div class="card-body">
                        <form id="editEquipmentForm" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?= $equipment['id'] ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contract Type</label>
                                    <select name="contract_type" class="form-select" required>
                                        <option value="">Select Type</option>
                                        <option value="new" <?= $equipment['contract_type'] === 'new' ? 'selected' : '' ?>>New</option>
                                        <option value="upgrade" <?= $equipment['contract_type'] === 'upgrade' ? 'selected' : '' ?>>Upgrade</option>
                                        <option value="renew" <?= $equipment['contract_type'] === 'renew' ? 'selected' : '' ?>>Renew</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Equipment Name</label>
                                    <input type="text" name="equipment_name" class="form-control" required value="<?= htmlspecialchars($equipment['equipment_name']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Equipment Model</label>
                                    <input type="text" name="equipment_model" class="form-control" required value="<?= htmlspecialchars($equipment['equipment_model']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Company Name</label>
                                    <input type="text" name="company_name" class="form-control" required value="<?= htmlspecialchars($equipment['company_name']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contract Number</label>
                                    <input type="text" name="contract_number" class="form-control" required value="<?= htmlspecialchars($equipment['contract_number']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="contact_number" class="form-control" required value="<?= htmlspecialchars($equipment['contact_number']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contract Start Date</label>
                                    <input type="date" name="contract_start_date" class="form-control" required value="<?= date('Y-m-d', strtotime($equipment['contract_start_date'])) ?>" onchange="updateEndDate()">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contract End Date</label>
                                    <input type="date" name="contract_end_date" class="form-control" required value="<?= date('Y-m-d', strtotime($equipment['contract_end_date'])) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Payment Type</label>
                                    <select name="payment_type" class="form-select" required onchange="toggleInstallmentFields()">
                                        <option value="">Select Payment Type</option>
                                        <option value="one_time" <?= $equipment['payment_type'] === 'one_time' ? 'selected' : '' ?>>One-time Payment</option>
                                        <option value="installment" <?= $equipment['payment_type'] === 'installment' ? 'selected' : '' ?>>Monthly Installment</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Total Cost (KWD)</label>
                                    <input type="number" name="total_cost" class="form-control" step="0.001" required value="<?= $equipment['total_cost'] ?>" onchange="calculateInstallment()">
                                </div>
                                <div id="installmentFields" style="display:none">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Down Payment (KWD)</label>
                                        <input type="number" name="down_payment" class="form-control" step="0.001" value="<?= $equipment['down_payment'] ?>" onchange="calculateInstallment()">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Number of Installments</label>
                                        <input type="number" name="num_installments" class="form-control" min="1" max="12" value="<?= $equipment['num_installments'] ?? 12 ?>" onchange="calculateInstallment()">
                                    </div>
                                    <div class="col-md-12">
                                        <div id="installmentCalculation" class="alert alert-info" style="display:none">
                                            <p><strong>Total Cost:</strong> <span id="totalCost">0.000</span> KWD</p>
                                            <p><strong>Down Payment:</strong> <span id="downPayment">0.000</span> KWD</p>
                                            <p><strong>Remaining Amount:</strong> <span id="remainingAmount">0.000</span> KWD</p>
                                            <p><strong>Monthly Installment:</strong> <span id="monthlyInstallmentAmount">0.000</span> KWD</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Maintenance Schedule</label>
                                    <div class="maintenance-schedule">
                                        <?php
                                        $maintenance_schedule = explode(',', $equipment['maintenance_schedule'] ?? '');
                                        ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="maintenance[]" value="3" <?= in_array('3', $maintenance_schedule) ? 'checked' : '' ?>>
                                            <label class="form-check-label">Every 3 Months</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="maintenance[]" value="6" <?= in_array('6', $maintenance_schedule) ? 'checked' : '' ?>>
                                            <label class="form-check-label">Every 6 Months</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="maintenance[]" value="12" <?= in_array('12', $maintenance_schedule) ? 'checked' : '' ?>>
                                            <label class="form-check-label">Every 12 Months</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Contract Document</label>
                                    <input type="file" name="contract_file" class="form-control" accept=".pdf,.doc,.docx">
                                    <small class="text-muted">Leave empty to keep existing document. Accepted formats: PDF, DOC, DOCX</small>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <button type="submit" class="btn btn-primary">Update Equipment</button>
                                    <a href="equipment.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle installment fields
function toggleInstallmentFields() {
    const paymentType = document.querySelector('select[name="payment_type"]').value;
    const installmentFields = document.getElementById('installmentFields');
    const installmentCalculation = document.getElementById('installmentCalculation');
    
    if (paymentType === 'installment') {
        installmentFields.style.display = 'block';
        calculateInstallment();
    } else {
        installmentFields.style.display = 'none';
        installmentCalculation.style.display = 'none';
    }
}

// Calculate installment
function calculateInstallment() {
    const paymentType = document.querySelector('select[name="payment_type"]').value;
    if (paymentType !== 'installment') {
        return;
    }

    const totalCost = parseFloat(document.querySelector('input[name="total_cost"]').value) || 0;
    const downPayment = parseFloat(document.querySelector('input[name="down_payment"]').value) || 0;
    const numInstallments = parseInt(document.querySelector('input[name="num_installments"]').value) || 12;

    const remainingAmount = totalCost - downPayment;
    const monthlyInstallment = remainingAmount / numInstallments;

    document.getElementById('totalCost').textContent = totalCost.toFixed(3);
    document.getElementById('downPayment').textContent = downPayment.toFixed(3);
    document.getElementById('remainingAmount').textContent = remainingAmount.toFixed(3);
    document.getElementById('monthlyInstallmentAmount').textContent = monthlyInstallment.toFixed(3);
    document.getElementById('installmentCalculation').style.display = 'block';
}

// Update end date
function updateEndDate() {
    const startDate = document.querySelector('input[name="contract_start_date"]').value;
    if (startDate) {
        const endDate = new Date(startDate);
        endDate.setFullYear(endDate.getFullYear() + 1);
        document.querySelector('input[name="contract_end_date"]').value = endDate.toISOString().split('T')[0];
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleInstallmentFields();
    calculateInstallment();
});
</script>

<?php include 'includes/footer.php'; ?>
