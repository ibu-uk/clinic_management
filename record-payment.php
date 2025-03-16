<?php
$page = 'record-payment';
include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Record Payment</h5>
            </div>
            <div class="card-body">
                <form id="paymentForm" action="api/payments/process.php" method="POST">
                    <div class="row">
                        <!-- Record Type Selection -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Record Type</label>
                            <select class="form-select" id="recordType" name="record_type" required>
                                <option value="">Select Type</option>
                                <option value="equipment">Equipment</option>
                                <option value="clinic_record">Clinic Record</option>
                            </select>
                        </div>

                        <!-- Sub Type Selection -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Sub Type</label>
                            <select class="form-select" id="subType" name="sub_type" required disabled>
                                <option value="">Select Sub Type</option>
                            </select>
                        </div>

                        <!-- Record Selection -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Select Record</label>
                            <select class="form-select" id="recordId" name="record_id" required disabled>
                                <option value="">Select Record</option>
                            </select>
                        </div>
                    </div>

                    <!-- Installment Details Card - Initially Hidden -->
                    <div id="installmentDetails" class="card mt-4" style="display: none;">
                        <div class="card-header bg-light">
                            <h6 class="card-title mb-0">Installment Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center">
                                        <div class="text-muted mb-1">Total Amount</div>
                                        <div class="h5 mb-0" id="totalAmount">0.000</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center">
                                        <div class="text-muted mb-1">Paid Amount</div>
                                        <div class="h5 mb-0" id="paidAmount">0.000</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center">
                                        <div class="text-muted mb-1">Remaining Amount</div>
                                        <div class="h5 mb-0" id="remainingAmount">0.000</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center">
                                        <div class="text-muted mb-1">Monthly Installment</div>
                                        <div class="h5 mb-0" id="monthlyInstallment">0.000</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="multipleInstallments">
                                        <label class="form-check-label" for="multipleInstallments">
                                            Pay Multiple Installments
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div id="installmentsList" class="mb-4">
                                <!-- Installment rows will be added here dynamically -->
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Payment Date</label>
                                    <input type="date" class="form-control" name="payment_date" required>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <select class="form-select" name="payment_method" required>
                                        <option value="cash">Cash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="credit_card">Credit Card</option>
                                        <option value="check">Check</option>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Reference Number</label>
                                    <input type="text" class="form-control" name="reference_number">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Total Payment Amount</label>
                                    <input type="number" class="form-control" id="totalPaymentAmount" name="total_amount" step="0.001" required readonly>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control" name="notes" rows="1"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Save Payment</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Payments Table -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Payments</h5>
                <a href="payment-history.php" class="btn btn-sm btn-info">View Full History</a>
            </div>
            <div class="card-body">
                <?php include 'includes/recent-payments-list.php'; ?>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    let currentRecord = null;

    // Record Type Change Handler
    $('#recordType').change(function() {
        const recordType = $(this).val();
        const subTypeSelect = $('#subType');
        const recordSelect = $('#recordId');
        
        // Reset and disable dependent dropdowns
        subTypeSelect.html('<option value="">Select Sub Type</option>').prop('disabled', true);
        recordSelect.html('<option value="">Select Record</option>').prop('disabled', true);
        $('#installmentDetails').hide();
        currentRecord = null;
        
        if (recordType) {
            subTypeSelect.prop('disabled', false);
            
            if (recordType === 'equipment') {
                subTypeSelect.append(`
                    <option value="new">New</option>
                    <option value="renew">Renew</option>
                    <option value="upgrade">Upgrade</option>
                `);
            } else if (recordType === 'clinic_record') {
                subTypeSelect.append(`
                    <option value="rent">Rent</option>
                    <option value="insurance">Insurance</option>
                    <option value="clinic_license">Clinic License</option>
                    <option value="fire_safety">Fire Safety</option>
                `);
            }
        }
    });

    // Sub Type Change Handler
    $('#subType').change(function() {
        const recordType = $('#recordType').val();
        const subType = $(this).val();
        const recordSelect = $('#recordId');
        
        recordSelect.html('<option value="">Select Record</option>').prop('disabled', true);
        $('#installmentDetails').hide();
        currentRecord = null;
        
        if (subType) {
            recordSelect.html('<option value="">Loading records...</option>');
            
            $.ajax({
                url: 'api/payments/get_records.php',
                method: 'GET',
                data: { 
                    type: recordType,
                    subtype: subType.charAt(0).toUpperCase() + subType.slice(1).toLowerCase()
                },
                success: function(response) {
                    console.log('API Response:', response); // Debug log
                    
                    if (response.success && response.records.length > 0) {
                        recordSelect.prop('disabled', false);
                        recordSelect.html('<option value="">Select Record</option>');
                        
                        response.records.forEach(function(record) {
                            console.log('Processing record:', record); // Debug log
                            const name = record.equipment_name || record.company_name;
                            const monthlyAmount = record.monthly_installment || (record.remaining_amount / 12);
                            
                            recordSelect.append(`<option value="${record.id}" 
                                data-amount="${monthlyAmount.toFixed(3)}"
                                data-total="${record.total_amount || record.total_cost}"
                                data-remaining="${record.remaining_amount}"
                                data-paid="${record.total_paid}">
                                ${name} - ${record.contract_number} (Remaining: ${record.remaining_amount.toFixed(3)})
                            </option>`);
                        });
                    } else {
                        recordSelect.html('<option value="">No records found</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading records:', error);
                    recordSelect.html('<option value="">Error loading records</option>');
                }
            });
        }
    });

    // Record Selection Handler
    $('#recordId').change(function() {
        const selectedOption = $(this).find('option:selected');
        console.log('Selected option:', selectedOption.val()); // Debug log
        
        if (selectedOption.val()) {
            console.log('Selected data:', { // Debug log
                amount: selectedOption.data('amount'),
                total: selectedOption.data('total'),
                remaining: selectedOption.data('remaining'),
                paid: selectedOption.data('paid')
            });
            
            currentRecord = {
                id: selectedOption.val(),
                monthlyAmount: parseFloat(selectedOption.data('amount')),
                totalAmount: parseFloat(selectedOption.data('total')),
                remainingAmount: parseFloat(selectedOption.data('remaining')),
                paidAmount: parseFloat(selectedOption.data('paid'))
            };
            
            console.log('Current record:', currentRecord); // Debug log
            
            // Update installment details
            $('#totalAmount').text(currentRecord.totalAmount.toFixed(3));
            $('#paidAmount').text(currentRecord.paidAmount.toFixed(3));
            $('#remainingAmount').text(currentRecord.remainingAmount.toFixed(3));
            $('#monthlyInstallment').text(currentRecord.monthlyAmount.toFixed(3));
            
            // Show installment details card
            $('#installmentDetails').show();
            
            // Reset and update payment fields
            $('#multipleInstallments').prop('checked', false);
            updateInstallmentsList();
        } else {
            $('#installmentDetails').hide();
            currentRecord = null;
        }
    });

    // Multiple Installments Handler
    $('#multipleInstallments').change(function() {
        updateInstallmentsList();
    });

    // Update installments list based on selection
    function updateInstallmentsList() {
        if (!currentRecord) return;

        const installmentsList = $('#installmentsList');
        installmentsList.empty();

        if ($('#multipleInstallments').is(':checked')) {
            // Calculate maximum possible installments
            const maxInstallments = Math.floor(currentRecord.remainingAmount / currentRecord.monthlyAmount);
            const maxSelectable = Math.min(maxInstallments, 12); // Limit to 12 installments at once

            installmentsList.append(`
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Number of Installments</label>
                        <select class="form-select" id="numInstallments" name="installments">
                            ${Array.from({length: maxSelectable}, (_, i) => i + 1).map(num => 
                                `<option value="${num}">${num} installment${num > 1 ? 's' : ''}</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Amount per Installment</label>
                        <input type="text" class="form-control" readonly value="${currentRecord.monthlyAmount.toFixed(3)}">
                    </div>
                </div>
            `);

            // Add event listener for installment number change
            $('#numInstallments').change(function() {
                const numInstallments = parseInt($(this).val());
                const totalAmount = (currentRecord.monthlyAmount * numInstallments).toFixed(3);
                $('#totalPaymentAmount').val(totalAmount);
            }).trigger('change');
        } else {
            // Single installment
            installmentsList.append(`
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Payment Amount</label>
                        <input type="text" class="form-control" readonly value="${currentRecord.monthlyAmount.toFixed(3)}">
                    </div>
                </div>
            `);
            $('#totalPaymentAmount').val(currentRecord.monthlyAmount.toFixed(3));
        }
    }

    // Form submission handler
    $('#paymentForm').submit(function(e) {
        e.preventDefault();
        
        if (!currentRecord) {
            alert('Please select a record first');
            return;
        }

        const formData = new FormData(this);
        formData.append('installments', $('#multipleInstallments').is(':checked') ? 
            $('#numInstallments').val() : '1');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Payment recorded successfully');
                    window.location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error processing payment: ' + error);
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>