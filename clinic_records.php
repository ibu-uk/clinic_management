<?php
include 'includes/header.php';
include 'config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$payment_type = isset($_GET['payment_type']) ? $_GET['payment_type'] : 'all';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Clinic Records</h1>
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-table me-1"></i>
                        Clinic Records List
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClinicRecordModal">
                            <i class="fas fa-plus"></i> Add New Record
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-select" id="statusFilter">
                                <option value="all" <?= $status == 'all' ? 'selected' : '' ?>>All Status</option>
                                <option value="active" <?= $status == 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $status == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="paymentFilter">
                                <option value="all" <?= $payment_type == 'all' ? 'selected' : '' ?>>All Payment Types</option>
                                <option value="full" <?= $payment_type == 'full' ? 'selected' : '' ?>>Full Payment</option>
                                <option value="installment" <?= $payment_type == 'installment' ? 'selected' : '' ?>>Installment</option>
                            </select>
                        </div>
                    </div>

                    <!-- Records Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered" id="clinicRecordsTable">
                            <thead>
                                <tr>
                                    <th>Company Name</th>
                                    <th>Contract Number</th>
                                    <th>Contract Start</th>
                                    <th>Contract End</th>
                                    <th>Total Cost</th>
                                    <th>Payment Type</th>
                                    <th>Remaining Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Clinic Record Modal -->
<div class="modal fade" id="addClinicRecordModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Clinic Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addClinicRecordForm">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" name="company_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contract Number</label>
                            <input type="text" class="form-control" name="contract_number" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Contract Start Date</label>
                            <input type="date" class="form-control" name="contract_start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contract Duration (months)</label>
                            <input type="number" class="form-control" name="contract_duration" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Total Cost (KWD)</label>
                            <input type="number" step="0.001" class="form-control" name="total_cost" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Type</label>
                            <select class="form-select" name="payment_type" required>
                                <option value="full">Full Payment</option>
                                <option value="installment">Installment</option>
                            </select>
                        </div>
                    </div>
                    <div id="installmentDetails" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Down Payment (KWD)</label>
                                <input type="number" step="0.001" class="form-control" name="down_payment">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Monthly Installment (KWD)</label>
                                <input type="number" step="0.001" class="form-control" name="monthly_installment" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View/Edit Record Modal -->
<div class="modal fade" id="editRecordModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Clinic Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editRecordForm">
                <input type="hidden" name="record_id">
                <div class="modal-body">
                    <!-- Similar fields as add modal -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" name="company_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contract Number</label>
                            <input type="text" class="form-control" name="contract_number" required readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Contract Start Date</label>
                            <input type="date" class="form-control" name="contract_start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contract End Date</label>
                            <input type="date" class="form-control" name="contract_end_date" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Total Cost (KWD)</label>
                            <input type="number" step="0.001" class="form-control" name="total_cost" required readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Type</label>
                            <input type="text" class="form-control" name="payment_type" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Remaining Amount (KWD)</label>
                            <input type="number" step="0.001" class="form-control" name="remaining_amount" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment History Modal -->
<div class="modal fade" id="paymentHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount (KWD)</th>
                                <th>Type</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody id="paymentHistoryBody">
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#clinicRecordsTable').DataTable({
        ajax: {
            url: 'api/clinic_records/get_records.php',
            data: function(d) {
                d.status = $('#statusFilter').val();
                d.payment_type = $('#paymentFilter').val();
            }
        },
        columns: [
            { data: 'company_name' },
            { data: 'contract_number' },
            { data: 'contract_start_date' },
            { data: 'contract_end_date' },
            { 
                data: 'total_cost',
                render: function(data) {
                    return parseFloat(data).toFixed(3) + ' KWD';
                }
            },
            { data: 'payment_type' },
            { 
                data: 'remaining_amount',
                render: function(data) {
                    return parseFloat(data).toFixed(3) + ' KWD';
                }
            },
            { 
                data: 'status',
                render: function(data) {
                    const badge = data === 'active' ? 'bg-success' : 'bg-secondary';
                    return `<span class="badge ${badge}">${data}</span>`;
                }
            },
            {
                data: null,
                render: function(data) {
                    const buttons = `
                        <button class="btn btn-sm btn-info view-payments" data-id="${data.id}">
                            <i class="fas fa-money-bill"></i>
                        </button>
                        <button class="btn btn-sm btn-primary edit-record" data-id="${data.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                    `;
                    return buttons;
                }
            }
        ]
    });

    // Filter change handlers
    $('#statusFilter, #paymentFilter').change(function() {
        table.ajax.reload();
    });

    // Payment type change handler
    $('select[name="payment_type"]').change(function() {
        const isInstallment = $(this).val() === 'installment';
        $('#installmentDetails').toggle(isInstallment);
        $('input[name="down_payment"]').prop('required', isInstallment);
    });

    // Calculate monthly installment
    $('input[name="total_cost"], input[name="down_payment"], input[name="contract_duration"]').on('input', function() {
        const totalCost = parseFloat($('input[name="total_cost"]').val()) || 0;
        const downPayment = parseFloat($('input[name="down_payment"]').val()) || 0;
        const duration = parseInt($('input[name="contract_duration"]').val()) || 1;

        if (totalCost > 0 && duration > 0) {
            const monthlyAmount = (totalCost - downPayment) / duration;
            $('input[name="monthly_installment"]').val(monthlyAmount.toFixed(3));
        }
    });

    // Add Record Form Submit
    $('#addClinicRecordForm').submit(function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        $.ajax({
            url: 'api/clinic_records/add_record.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    $('#addClinicRecordModal').modal('hide');
                    table.ajax.reload();
                    showAlert('success', 'Record added successfully');
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'Error adding record');
            }
        });
    });

    // Edit Record Handler
    $(document).on('click', '.edit-record', function() {
        const recordId = $(this).data('id');
        
        $.get('api/clinic_records/get_record.php', { id: recordId }, function(response) {
            if (response.status === 'success') {
                const record = response.data;
                const form = $('#editRecordForm');
                
                // Populate form fields
                Object.keys(record).forEach(key => {
                    form.find(`[name="${key}"]`).val(record[key]);
                });
                
                $('#editRecordModal').modal('show');
            }
        });
    });

    // View Payments Handler
    $(document).on('click', '.view-payments', function() {
        const recordId = $(this).data('id');
        
        $.get('api/clinic_records/get_payments.php', { record_id: recordId }, function(response) {
            if (response.status === 'success') {
                const payments = response.data;
                let html = '';
                
                payments.forEach(payment => {
                    html += `
                        <tr>
                            <td>${payment.payment_date}</td>
                            <td>${parseFloat(payment.amount).toFixed(3)} KWD</td>
                            <td>${payment.payment_type}</td>
                            <td>${payment.reference_no}</td>
                        </tr>
                    `;
                });
                
                $('#paymentHistoryBody').html(html);
                $('#paymentHistoryModal').modal('show');
            }
        });
    });

    // Helper function to show alerts
    function showAlert(type, message) {
        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('.container-fluid').prepend(alert);
        setTimeout(() => $('.alert').alert('close'), 5000);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
