<?php
$page_title = "Add Equipment";
include 'includes/header.php';
require_once 'config/database.php';
?>

<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12">
            <h2 class="mb-3">Add New Equipment</h2>
            
            <form id="addEquipmentForm" action="api/equipment/add-equipment.php" method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <!-- Equipment Details Card -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Equipment Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label for="contract_type" class="form-label">Contract Type</label>
                                        <select name="contract_type" id="contract_type" class="form-select form-select-sm" required>
                                            <option value="new">New</option>
                                            <option value="upgrade">Upgrade</option>
                                            <option value="renew">Renew</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="equipment_name" class="form-label">Equipment Name</label>
                                        <input type="text" class="form-control form-control-sm" id="equipment_name" name="equipment_name" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="equipment_model" class="form-label">Equipment Model</label>
                                        <input type="text" class="form-control form-control-sm" id="equipment_model" name="equipment_model" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="company_name" class="form-label">Company Name</label>
                                        <input type="text" class="form-control form-control-sm" id="company_name" name="company_name" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="contract_number" class="form-label">Contract Number</label>
                                        <input type="text" class="form-control form-control-sm" id="contract_number" name="contract_number" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="contact_number" class="form-label">Contact Number</label>
                                        <input type="text" class="form-control form-control-sm" id="contact_number" name="contact_number" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contract Details Card -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Contract Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label for="contract_start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control form-control-sm" id="contract_start_date" name="contract_start_date" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="contract_end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control form-control-sm" id="contract_end_date" name="contract_end_date" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="payment_type" class="form-label">Payment Type</label>
                                        <select name="payment_type" id="payment_type" class="form-select form-select-sm" required>
                                            <option value="one_time">One Time Payment</option>
                                            <option value="installment">Monthly Installment</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6" id="start_month_div" style="display: none;">
                                        <label for="start_month" class="form-label">Start After</label>
                                        <select class="form-select form-select-sm" id="start_month" name="start_month">
                                            <option value="1">1 Month</option>
                                            <option value="2">2 Months</option>
                                            <option value="3">3 Months</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="total_cost" class="form-label">Total Cost (KWD)</label>
                                        <input type="number" step="0.001" class="form-control form-control-sm" id="total_cost" name="total_cost" required>
                                    </div>

                                    <div class="col-md-6" id="down_payment_div" style="display: none;">
                                        <label for="down_payment" class="form-label">Down Payment (KWD)</label>
                                        <input type="number" step="0.001" class="form-control form-control-sm" id="down_payment" name="down_payment" value="0">
                                    </div>

                                    <div class="col-md-6" id="num_installments_div" style="display: none;">
                                        <label for="num_installments" class="form-label">Number of Installments</label>
                                        <input type="number" class="form-control form-control-sm" id="num_installments" name="num_installments" value="12">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Maintenance Schedule</label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="maintenance_3" name="maintenance[]" value="3">
                                                <label class="form-check-label" for="maintenance_3">3 Months</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="maintenance_6" name="maintenance[]" value="6">
                                                <label class="form-check-label" for="maintenance_6">6 Months</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="maintenance_12" name="maintenance[]" value="12">
                                                <label class="form-check-label" for="maintenance_12">12 Months</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label for="contract_file" class="form-label">Contract Document</label>
                                        <input type="file" class="form-control form-control-sm" id="contract_file" name="contract_file" accept=".pdf,.doc,.docx">
                                        <small class="text-muted">Accepted: PDF, DOC, DOCX</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Add Equipment</button>
                        <a href="equipment.php" class="btn btn-secondary ms-2">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentType = document.getElementById('payment_type');
    const startMonthDiv = document.getElementById('start_month_div');
    const downPaymentDiv = document.getElementById('down_payment_div');
    const numInstallmentsDiv = document.getElementById('num_installments_div');

    function toggleInstallmentFields() {
        const isInstallment = paymentType.value === 'installment';
        startMonthDiv.style.display = isInstallment ? 'block' : 'none';
        downPaymentDiv.style.display = isInstallment ? 'block' : 'none';
        numInstallmentsDiv.style.display = isInstallment ? 'block' : 'none';
    }

    // Initial setup
    toggleInstallmentFields();

    // Event listener for payment type change
    paymentType.addEventListener('change', toggleInstallmentFields);
});
</script>

<?php include 'includes/footer.php'; ?>
