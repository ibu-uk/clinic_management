<?php
$page = 'clinic-records';
include 'includes/header.php';

// Get record ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get record details
require_once 'config/database.php';
$database = Database::getInstance();
$db = $database->getConnection();

$query = "SELECT * FROM clinic_records WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    echo '<script>alert("Record not found"); window.location.href = "clinic-records.php";</script>';
    exit;
}
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Edit Clinic Record</h4>
                        <a href="clinic-records.php" class="btn btn-secondary">Back to List</a>
                    </div>
                    <div class="card-body">
                        <form id="editClinicRecordForm" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Record Type</label>
                                    <select name="record_type" class="form-select" required>
                                        <option value="">Select Type</option>
                                        <?php
                                        $types = ['Rent', 'Insurance', 'Clinic License', 'Fire Safety'];
                                        foreach ($types as $type) {
                                            $selected = ($type === $record['record_type']) ? 'selected' : '';
                                            echo "<option value=\"$type\" $selected>$type</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Company Name</label>
                                    <input type="text" name="company_name" class="form-control" required value="<?php echo htmlspecialchars($record['company_name']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contract Number</label>
                                    <input type="text" name="contract_number" class="form-control" required value="<?php echo htmlspecialchars($record['contract_number']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="contact_number" class="form-control" required value="<?php echo htmlspecialchars($record['contact_number']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contract Start Date</label>
                                    <input type="date" name="contract_start_date" class="form-control" required value="<?php echo $record['contract_start_date']; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contract End Date</label>
                                    <input type="date" name="contract_end_date" class="form-control" required value="<?php echo $record['contract_end_date']; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Payment Type</label>
                                    <select name="payment_type" class="form-select" required onchange="toggleInstallmentFields()">
                                        <option value="">Select Payment Type</option>
                                        <option value="one_time" <?php echo $record['payment_type'] === 'one_time' ? 'selected' : ''; ?>>One-time Payment</option>
                                        <option value="installment" <?php echo $record['payment_type'] === 'installment' ? 'selected' : ''; ?>>Monthly Installment</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Total Cost (KWD)</label>
                                    <input type="number" name="total_cost" class="form-control" step="0.001" required value="<?php echo $record['total_cost']; ?>" onchange="calculateInstallment()">
                                </div>
                                <div id="installmentFields" style="display:<?php echo $record['payment_type'] === 'installment' ? 'block' : 'none'; ?>">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Down Payment (KWD)</label>
                                        <input type="number" name="down_payment" class="form-control" step="0.001" value="<?php echo $record['down_payment']; ?>" onchange="calculateInstallment()">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Number of Installments</label>
                                        <input type="number" name="number_of_installments" class="form-control" min="1" max="12" value="<?php echo $record['number_of_installments']; ?>" onchange="calculateInstallment()">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Monthly Payment (KWD)</label>
                                        <input type="number" name="monthly_payment" class="form-control" step="0.001" readonly value="<?php echo $record['monthly_payment']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Contract Document</label>
                                    <input type="file" name="contract_document" class="form-control" accept=".pdf,.doc,.docx">
                                    <?php if ($record['contract_document']): ?>
                                    <div class="mt-2">
                                        <a href="uploads/clinic_records/<?php echo $record['contract_document']; ?>" target="_blank" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-file-alt"></i> View Current Document
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($record['notes']); ?></textarea>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">Update Record</button>
                                <a href="clinic-records.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle installment fields visibility
function toggleInstallmentFields() {
    const paymentType = document.querySelector('select[name="payment_type"]').value;
    const installmentFields = document.getElementById('installmentFields');
    
    if (paymentType === 'installment') {
        installmentFields.style.display = 'block';
        calculateInstallment();
    } else {
        installmentFields.style.display = 'none';
    }
}

// Calculate installment amount
function calculateInstallment() {
    const paymentType = document.querySelector('select[name="payment_type"]').value;
    if (paymentType !== 'installment') return;

    const totalCost = parseFloat(document.querySelector('input[name="total_cost"]').value) || 0;
    const downPayment = parseFloat(document.querySelector('input[name="down_payment"]').value) || 0;
    const numberOfInstallments = parseInt(document.querySelector('input[name="number_of_installments"]').value) || 12;

    if (totalCost > 0 && numberOfInstallments > 0) {
        const remainingAmount = totalCost - downPayment;
        const monthlyPayment = remainingAmount / numberOfInstallments;
        document.querySelector('input[name="monthly_payment"]').value = monthlyPayment.toFixed(3);
    }
}

// Form submission
document.getElementById('editClinicRecordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('api/clinic-records/update.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            alert('Record updated successfully');
            window.location.href = 'clinic-records.php';
        } else {
            alert(result.message || 'Error updating record');
        }
    } catch (error) {
        alert('Error updating record');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
