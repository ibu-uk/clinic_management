<?php 
$page = 'clinic-records';
include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Add New Clinic Record</h4>
                    </div>
                    <div class="card-body">
                        <form id="clinicRecordForm" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Record Type</label>
                                    <select name="record_type" class="form-select" required>
                                        <option value="">Select Type</option>
                                        <option value="Rent">Rent</option>
                                        <option value="Insurance">Insurance</option>
                                        <option value="Clinic License">Clinic License</option>
                                        <option value="Fire Safety">Fire Safety</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Company Name</label>
                                    <input type="text" name="company_name" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contract Number</label>
                                    <input type="text" name="contract_number" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="contact_number" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contract Document</label>
                                    <input type="file" name="contract_document" class="form-control" accept=".pdf,.doc,.docx">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contract Start Date</label>
                                    <input type="date" name="contract_start_date" class="form-control" required onchange="updateEndDate()">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contract End Date</label>
                                    <input type="date" name="contract_end_date" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Payment Type</label>
                                    <select name="payment_type" class="form-select" required onchange="toggleInstallmentFields()">
                                        <option value="">Select Payment Type</option>
                                        <option value="one_time">One-time Payment</option>
                                        <option value="installment">Monthly Installment</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Total Cost (KWD)</label>
                                    <input type="number" name="total_cost" class="form-control" step="0.001" required onchange="calculateInstallment()">
                                </div>
                                <div id="installmentFields" style="display:none">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Down Payment (KWD)</label>
                                        <input type="number" name="down_payment" class="form-control" step="0.001" onchange="calculateInstallment()">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Number of Installments</label>
                                        <input type="number" name="number_of_installments" class="form-control" min="1" max="12" value="12" onchange="calculateInstallment()">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Monthly Payment (KWD)</label>
                                        <input type="number" name="monthly_payment" class="form-control" step="0.001" readonly>
                                    </div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">Save Record</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Clinic Records List</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="clinicRecordsList">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Company</th>
                                        <th>Contract #</th>
                                        <th>Contact #</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Total Cost</th>
                                        <th>Payment Details</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update end date based on start date
function updateEndDate() {
    const startDate = document.querySelector('input[name="contract_start_date"]').value;
    if (startDate) {
        const endDate = new Date(startDate);
        endDate.setFullYear(endDate.getFullYear() + 1); // Default 1 year contract
        document.querySelector('input[name="contract_end_date"]').value = endDate.toISOString().split('T')[0];
    }
}

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

// Format payment details
function formatPaymentDetails(record) {
    if (record.payment_type === 'one_time') {
        return `Full Payment: ${parseFloat(record.total_cost).toFixed(3)} KWD`;
    }
    
    const totalInstallments = parseInt(record.number_of_installments) || 12;
    const paidAmount = parseFloat(record.total_cost) - parseFloat(record.remaining_amount);
    const paidInstallments = Math.floor((paidAmount - parseFloat(record.down_payment)) / parseFloat(record.monthly_payment));
    
    return `
        Down Payment: ${parseFloat(record.down_payment).toFixed(3)} KWD<br>
        Installments: ${paidInstallments}/${totalInstallments} × ${parseFloat(record.monthly_payment).toFixed(3)} KWD<br>
        Remaining: ${parseFloat(record.remaining_amount).toFixed(3)} KWD
    `;
}

// Load records list
function loadClinicRecordsList() {
    console.log('Loading clinic records...');
    fetch('api/clinic-records/list.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Records loaded:', data);
            const tbody = document.querySelector('#clinicRecordsList tbody');
            tbody.innerHTML = data.map(record => `
                <tr>
                    <td>${record.record_type}</td>
                    <td>${record.company_name}</td>
                    <td>${record.contract_number}</td>
                    <td>${record.contact_number}</td>
                    <td>${record.contract_start_date}</td>
                    <td>${record.contract_end_date}</td>
                    <td>${parseFloat(record.total_cost).toFixed(3)} KWD</td>
                    <td>${formatPaymentDetails(record)}</td>
                    <td>
                        <span class="badge ${getBadgeClass(record)}">${getStatusText(record)}</span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info" onclick="viewRecord(${record.id})" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-primary" onclick="editRecord(${record.id})" title="Edit Record">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${record.contract_document ? `
                            <a href="uploads/clinic_records/${record.contract_document}" class="btn btn-sm btn-secondary" target="_blank" title="View Contract">
                                <i class="fas fa-file-alt"></i>
                            </a>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `).join('');
        })
        .catch(error => {
            console.error('Error loading records:', error);
            alert('Error loading records. Please check console for details.');
        });
}

// View record details
function viewRecord(id) {
    // Show loading spinner
    const loadingHtml = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    // Create modal
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">${loadingHtml}</div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    
    // Initialize and show modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Fetch record details
    fetch('api/clinic-records/view.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                modal.querySelector('.modal-body').innerHTML = data.html;
            } else {
                throw new Error(data.message || 'Error loading record details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modal.querySelector('.modal-body').innerHTML = `
                <div class="alert alert-danger">
                    ${error.message || 'Error loading record details. Please try again later.'}
                </div>
            `;
        });
    
    // Remove modal from DOM when hidden
    modal.addEventListener('hidden.bs.modal', () => {
        modal.remove();
    });
}

// Edit record
function editRecord(id) {
    window.location.href = 'edit-clinic-record.php?id=' + id;
}

// Helper functions
function getBadgeClass(record) {
    if (record.status === 'overdue') return 'bg-danger';
    if (record.status === 'pending') return 'bg-warning';
    return 'bg-success';
}

function getStatusText(record) {
    if (record.status === 'overdue') return 'Payment Overdue';
    if (record.status === 'pending') return 'Payment Pending';
    return 'Paid';
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadClinicRecordsList();
    toggleInstallmentFields();
    
    // Add event listeners
    document.querySelector('select[name="payment_type"]').addEventListener('change', toggleInstallmentFields);
    document.querySelector('input[name="total_cost"]').addEventListener('change', calculateInstallment);
    document.querySelector('input[name="down_payment"]').addEventListener('change', calculateInstallment);
    document.querySelector('input[name="number_of_installments"]').addEventListener('change', calculateInstallment);
});

// Form submission
document.getElementById('clinicRecordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('api/clinic-records/add.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            alert('Record added successfully');
            this.reset();
            loadClinicRecordsList();
        } else {
            alert(result.message || 'Error adding record');
        }
    } catch (error) {
        alert('Error adding record');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
