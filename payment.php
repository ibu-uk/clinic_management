<?php
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$page = 'payment';
include 'includes/header.php';
require_once 'config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();

// Get record types for dropdown
$record_types = [
    ['id' => 'equipment', 'name' => 'Equipment'],
    ['id' => 'clinic_record', 'name' => 'Clinic Record']
];

// Get sub types based on record type - match exactly with payments.php
$subtypes = [
    'equipment' => [
        ['id' => 'new', 'name' => 'New'],
        ['id' => 'renew', 'name' => 'Renew'],
        ['id' => 'upgrade', 'name' => 'Upgrade']
    ],
    'clinic_record' => [
        ['id' => 'rent', 'name' => 'Rent'],
        ['id' => 'insurance', 'name' => 'Insurance'],
        ['id' => 'clinic_license', 'name' => 'Clinic License'],
        ['id' => 'fire_safety', 'name' => 'Fire Safety']
    ]
];

// Payment methods
$payment_methods = [
    ['id' => 'cash', 'name' => 'Cash'],
    ['id' => 'bank_transfer', 'name' => 'Bank Transfer'],
    ['id' => 'cheque', 'name' => 'Cheque']
];
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Record Payment</h4>
            </div>
            <div class="card-body">
                <form id="paymentForm">
                    <div class="row">
                        <!-- Record Type -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Record Type</label>
                            <select id="recordType" class="form-select" required>
                                <option value="">Select Type</option>
                                <?php foreach ($record_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Sub Type -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Sub Type</label>
                            <select id="subType" class="form-select" required>
                                <option value="">Select Sub Type</option>
                            </select>
                        </div>

                        <!-- Select Record -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Select Record</label>
                            <select id="recordId" class="form-select" required>
                                <option value="">Select Record</option>
                            </select>
                        </div>

                        <!-- Payment Date -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Payment Date</label>
                            <input type="date" id="paymentDate" class="form-control" required 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Payment Method</label>
                            <select id="paymentMethod" class="form-select" required>
                                <option value="">Select Method</option>
                                <?php foreach ($payment_methods as $method): ?>
                                    <option value="<?php echo $method['id']; ?>"><?php echo $method['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Reference Number -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Reference Number</label>
                            <input type="text" id="referenceNumber" class="form-control" required>
                        </div>

                        <!-- Notes -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Notes</label>
                            <textarea id="notes" class="form-control" rows="1"></textarea>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Submit Payment</button>
                        <button type="button" id="viewHistory" class="btn btn-outline-primary">View Full History</button>
                    </div>
                </form>

                <!-- Recent Payments Table -->
                <div class="mt-4">
                    <h5>Recent Payments</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Record</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="recentPayments">
                                <!-- Will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Prevent any global modifications to these values
Object.freeze(<?php echo json_encode($subtypes); ?>);

// Update sub types based on record type
document.getElementById('recordType').addEventListener('change', function() {
    const subTypeSelect = document.getElementById('subType');
    const recordSelect = document.getElementById('recordId');
    const selectedType = this.value;
    
    // Reset dropdowns
    subTypeSelect.innerHTML = '<option value="">Select Sub Type</option>';
    recordSelect.innerHTML = '<option value="">Select Record</option>';
    
    // Get the subtypes directly from PHP to avoid any JavaScript modifications
    const subtypeData = <?php echo json_encode($subtypes); ?>;
    
    // Populate sub types with exact values from PHP
    if (selectedType && subtypeData[selectedType]) {
        subtypeData[selectedType].forEach(subtype => {
            subTypeSelect.innerHTML += `<option value="${subtype.id}">${subtype.name}</option>`;
        });
    }
});

// Load records based on type and sub-type
document.getElementById('subType').addEventListener('change', async function() {
    const recordType = document.getElementById('recordType').value;
    const subType = this.value;
    const recordSelect = document.getElementById('recordId');
    
    try {
        const response = await fetch(`api/payments/get_records.php?type=${recordType}&subtype=${subType}`);
        if (!response.ok) {
            throw new Error('Failed to fetch records');
        }
        
        const records = await response.json();
        if (records.error) {
            throw new Error(records.message || 'Failed to load records');
        }
        
        recordSelect.innerHTML = '<option value="">Select Record</option>';
        records.forEach(record => {
            recordSelect.innerHTML += `
                <option value="${record.id}">
                    ${record.name} (${record.contract_number}) - Remaining: ${record.remaining_amount} KWD
                </option>`;
        });
    } catch (error) {
        console.error('Error loading records:', error);
        alert(error.message);
    }
});

// Handle form submission
document.getElementById('paymentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = {
        record_type: document.getElementById('recordType').value,
        record_id: document.getElementById('recordId').value,
        payment_date: document.getElementById('paymentDate').value,
        payment_method: document.getElementById('paymentMethod').value,
        reference_number: document.getElementById('referenceNumber').value,
        description: document.getElementById('notes').value,
        installments: [] // Will be populated from the selected record
    };
    
    try {
        // First, get the pending installments for this record
        const installmentsResponse = await fetch(`api/payments/get_pending_installments.php?record_type=${formData.record_type}&record_id=${formData.record_id}`);
        if (!installmentsResponse.ok) {
            throw new Error('Failed to fetch installments');
        }
        const installmentsData = await installmentsResponse.json();
        if (installmentsData.error) {
            throw new Error(installmentsData.message || 'Failed to load installments');
        }
        
        // Add installment IDs to the form data
        formData.installments = installmentsData.map(inst => inst.id);
        
        // Process the payment
        const response = await fetch('api/payments/process_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const result = await response.json();
        
        if (result.error) {
            throw new Error(result.message || 'Error processing payment');
        }
        
        alert('Payment processed successfully');
        loadRecentPayments();
        this.reset();
        
    } catch (error) {
        console.error('Error:', error);
        alert(error.message);
    }
});

// Load recent payments
async function loadRecentPayments() {
    try {
        const response = await fetch('api/payments/recent_payments.php');
        if (!response.ok) {
            throw new Error('Failed to fetch recent payments');
        }
        
        const payments = await response.json();
        if (payments.error) {
            throw new Error(payments.message || 'Failed to load recent payments');
        }
        
        const tbody = document.getElementById('recentPayments');
        tbody.innerHTML = '';
        
        payments.forEach(payment => {
            tbody.innerHTML += `
                <tr>
                    <td>${payment.payment_date}</td>
                    <td>${payment.record_name}</td>
                    <td>${payment.payment_method}</td>
                    <td>${payment.reference_no}</td>
                    <td>${payment.amount} KWD</td>
                    <td><span class="badge bg-${payment.status === 'completed' ? 'success' : 'warning'}">${payment.status}</span></td>
                </tr>
            `;
        });
    } catch (error) {
        console.error('Error loading recent payments:', error);
        alert(error.message);
    }
}

// Load recent payments on page load
loadRecentPayments();

// View full history button
document.getElementById('viewHistory').addEventListener('click', function() {
    const recordId = document.getElementById('recordId').value;
    const recordType = document.getElementById('recordType').value;
    
    if (recordId && recordType) {
        window.location.href = `payment_history.php?type=${recordType}&id=${recordId}`;
    } else {
        alert('Please select a record first');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
