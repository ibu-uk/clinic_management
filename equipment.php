<?php
$page = 'equipment';
include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Add Equipment</h4>
                    </div>
                    <div class="card-body">
                        <form id="equipmentForm" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contract Type</label>
                                    <select name="contract_type" class="form-select" required>
                                        <option value="">Select Type</option>
                                        <option value="new">New</option>
                                        <option value="upgrade">Upgrade</option>
                                        <option value="renew">Renew</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Equipment Name</label>
                                    <input type="text" name="equipment_name" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Equipment Model</label>
                                    <input type="text" name="equipment_model" class="form-control" required>
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
                                        <input type="number" name="num_installments" class="form-control" min="1" max="12" value="12" onchange="calculateInstallment()">
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
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="maintenance[]" value="3">
                                            <label class="form-check-label">Every 3 Months</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="maintenance[]" value="6">
                                            <label class="form-check-label">Every 6 Months</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="maintenance[]" value="12">
                                            <label class="form-check-label">Every 12 Months</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Contract Document</label>
                                    <input type="file" name="contract_file" class="form-control" accept=".pdf,.doc,.docx" required>
                                    <small class="text-muted">Accepted formats: PDF, DOC, DOCX</small>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                    <button type="reset" class="btn btn-secondary">Reset</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Add notification div -->
                <div id="notificationBox" class="alert" style="display: none;"></div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Equipment List</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Equipment Name</th>
                                        <th>Model</th>
                                        <th>Company</th>
                                        <th>Contract Number</th>
                                        <th>Type</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Total Cost</th>
                                        <th>Payment Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="equipmentList">
                                    <!-- Will be populated by AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .maintenance-item {
        padding: 2px 5px;
        margin: 2px 0;
        border-radius: 3px;
        font-size: 0.9em;
    }
    .maintenance-item.scheduled {
        background-color: #e3f2fd;
        color: #1976d2;
    }
    .maintenance-item.completed {
        background-color: #e8f5e9;
        color: #388e3c;
    }
    .maintenance-item.cancelled {
        background-color: #ffebee;
        color: #d32f2f;
    }
</style>

<script>
let isSubmitting = false;

// Generate a unique token for form submission
function generateFormToken() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

// Record payment function
async function recordPayment(equipmentId, amount, dueDate) {
    if (!confirm('Record payment of ' + amount + ' KWD for this equipment?')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('equipment_id', equipmentId);
        formData.append('amount', amount);
        formData.append('payment_date', dueDate);
        
        const response = await fetch('api/equipment/record-payment.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Payment recorded successfully', 'success');
            // Reload equipment list to show updated status
            loadEquipmentList();
        } else {
            showNotification(result.message || 'Error recording payment', 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred while recording payment', 'danger');
    }
}

// Load equipment list
function loadEquipmentList() {
    fetch('includes/equipment-list.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('equipmentList').innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading equipment list', 'danger');
        });
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

// Format currency
function formatCurrency(amount) {
    return parseFloat(amount).toFixed(3);
}

// Format payment type
function formatPaymentType(equipment) {
    if (equipment.payment_type === 'one_time') {
        return `One-time Payment<br>${equipment.payment_status}<br>${formatCurrency(equipment.total_cost)} KWD`;
    } else {
        return `Installment<br>${equipment.payment_status}<br>Monthly: ${formatCurrency(equipment.monthly_amount)} KWD`;
    }
}

// Format status
function formatStatus(status) {
    const statusClasses = {
        'active': 'success',
        'completed': 'info',
        'expired': 'danger'
    };
    return `<span class="badge bg-${statusClasses[status] || 'secondary'}">${status}</span>`;
}

// Toggle installment fields
function toggleInstallmentFields() {
    const paymentType = document.querySelector('select[name="payment_type"]').value;
    const installmentFields = document.getElementById('installmentFields');
    const installmentCalculation = document.getElementById('installmentCalculation');
    
    if (paymentType === 'one_time') {
        installmentFields.style.display = 'none';
        if (installmentCalculation) {
            installmentCalculation.style.display = 'none';
        }
        // Clear installment fields
        document.querySelector('input[name="down_payment"]').value = '';
        document.querySelector('input[name="num_installments"]').value = '';
    } else if (paymentType === 'installment') {
        installmentFields.style.display = 'block';
        // Set default number of installments
        if (!document.querySelector('input[name="num_installments"]').value) {
            document.querySelector('input[name="num_installments"]').value = '12';
        }
        calculateInstallment();
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
    const numberOfInstallments = parseInt(document.querySelector('input[name="num_installments"]').value) || 12;

    if (totalCost > 0 && numberOfInstallments > 0) {
        const remainingAmount = totalCost - downPayment;
        const monthlyInstallment = remainingAmount / numberOfInstallments;

        const installmentCalculation = document.getElementById('installmentCalculation');
        if (installmentCalculation) {
            installmentCalculation.innerHTML = `
                <p><strong>Total Cost:</strong> ${totalCost.toFixed(3)} KWD</p>
                <p><strong>Down Payment:</strong> ${downPayment.toFixed(3)} KWD</p>
                <p><strong>Remaining Amount:</strong> ${remainingAmount.toFixed(3)} KWD</p>
                <p><strong>Monthly Installment:</strong> ${monthlyInstallment.toFixed(3)} KWD</p>
            `;
            installmentCalculation.style.display = 'block';
        }
    }
}

// Update end date based on start date
function updateEndDate() {
    const startDate = document.querySelector('input[name="contract_start_date"]').value;
    if (startDate) {
        const endDate = new Date(startDate);
        endDate.setFullYear(endDate.getFullYear() + 1);
        document.querySelector('input[name="contract_end_date"]').value = endDate.toISOString().split('T')[0];
    }
}

// Show notification with optional auto-hide
function showNotification(message, type = 'info', autoHide = true) {
    const notificationBox = document.getElementById('notificationBox');
    if (notificationBox) {
        notificationBox.className = `alert alert-${type}`;
        notificationBox.innerHTML = message;
        notificationBox.style.display = 'block';

        if (autoHide) {
            setTimeout(() => {
                notificationBox.style.display = 'none';
            }, 5000);
        }
    }
}

// Form submission
document.getElementById('equipmentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Prevent double submission
    if (isSubmitting) {
        return;
    }
    
    isSubmitting = true;
    const submitButton = this.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    
    try {
        const formData = new FormData(this);
        // Add unique form token
        formData.append('formToken', generateFormToken());
        
        // Add maintenance schedule
        const maintenanceInputs = document.querySelectorAll('input[name="maintenance[]"]:checked');
        formData.delete('maintenance[]');
        maintenanceInputs.forEach(input => {
            formData.append('maintenance[]', input.value);
        });

        const response = await fetch('api/equipment/create.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            this.reset();
            // Reset maintenance checkboxes
            document.querySelectorAll('input[name="maintenance[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            // Hide installment fields
            document.getElementById('installmentFields').style.display = 'none';
            // Reset installment calculation
            document.getElementById('installmentCalculation').style.display = 'none';
            loadEquipmentList();
        } else {
            if (result.message.includes('Contract number already exists')) {
                showNotification('This contract number is already in use. Please enter a different contract number.', 'warning', false);
            } else {
                showNotification(result.message || 'Error saving equipment record', 'danger');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred while submitting the form: ' + error.message, 'danger');
    } finally {
        isSubmitting = false;
        submitButton.disabled = false;
    }
});

// Add this to prevent accidental form submission
window.addEventListener('beforeunload', function (e) {
    if (isSubmitting) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// View equipment details
function viewEquipment(id) {
    // Show loading spinner
    const loadingHtml = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    // Create modal
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Equipment Details</h5>
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
    
    // Fetch equipment details
    fetch('api/equipment/view-equipment.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                modal.querySelector('.modal-body').innerHTML = data.html;
            } else {
                throw new Error(data.message || 'Error loading equipment details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modal.querySelector('.modal-body').innerHTML = `
                <div class="alert alert-danger">
                    ${error.message || 'Error loading equipment details. Please try again later.'}
                </div>
            `;
        });
    
    // Remove modal from DOM when hidden
    modal.addEventListener('hidden.bs.modal', () => {
        modal.remove();
    });
}

// Edit equipment
function editEquipment(id) {
    window.location.href = 'edit-equipment.php?id=' + id;
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadEquipmentList();
    toggleInstallmentFields();
    
    // Add event listeners
    document.querySelector('select[name="payment_type"]').addEventListener('change', toggleInstallmentFields);
    document.querySelector('input[name="total_cost"]').addEventListener('change', calculateInstallment);
    document.querySelector('input[name="down_payment"]').addEventListener('change', calculateInstallment);
    document.querySelector('input[name="num_installments"]').addEventListener('change', calculateInstallment);
});
</script>

<?php include 'includes/footer.php'; ?>
