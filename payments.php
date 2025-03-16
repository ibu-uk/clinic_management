<?php
$page = 'payments';
include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <!-- Summary Cards -->
        <div id="summaryCards" class="row mb-4" style="display: none;">
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Total Cost</h6>
                        <h3 class="card-text" id="totalCost">0.000 KWD</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Remaining Amount</h6>
                        <h3 class="card-text" id="remainingAmount">0.000 KWD</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6" id="monthlyPaymentCard" style="display: none;">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Monthly Payment</h6>
                        <h3 class="card-text" id="monthlyPayment">0.000 KWD</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6" id="nextPaymentDateCard" style="display: none;">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Next Payment Date</h6>
                        <h3 class="card-text" id="nextPaymentDate">-</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Record Payment Form -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Record Payment</h5>
                    </div>
                    <div class="card-body">
                        <form id="paymentForm">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="recordType" class="form-label">Record Type</label>
                                    <select class="form-select" id="recordType" required>
                                        <option value="">Select Type</option>
                                        <option value="equipment">Equipment</option>
                                        <option value="clinic">Clinic</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="subType" class="form-label">Sub Type</label>
                                    <select class="form-select" id="subType" required>
                                        <option value="">Select Sub Type</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3" style="display: none;">
                                    <label for="recordSelect" class="form-label">Record</label>
                                    <select class="form-select" id="recordSelect" required>
                                        <option value="">Select Record</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="paymentAmount" class="form-label">Payment Amount (KWD)</label>
                                    <input type="number" class="form-control" id="paymentAmount" step="0.001" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="paymentDate" class="form-label">Payment Date</label>
                                    <input type="date" class="form-control" id="paymentDate" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="paymentMethod" class="form-label">Payment Method</label>
                                    <select class="form-select" id="paymentMethod" required>
                                        <option value="">Select Method</option>
                                        <option value="KNET">KNET</option>
                                        <option value="credit_card">Credit Card</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="link">Payment Link</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="referenceNumber" class="form-label">Reference Number</label>
                                    <input type="text" class="form-control" id="referenceNumber" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" rows="2"></textarea>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-secondary me-2" onclick="window.location.reload()">Reset</button>
                                <button type="submit" class="btn btn-primary">Record Payment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div id="paymentHistory" class="card mb-4" style="display: none;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Payments</h5>
                <a href="#" id="viewFullHistory" class="btn btn-primary btn-sm">View Full History</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Payment Date</th>
                                <th>Reference Number</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include payments.js -->
<script src="assets/js/payments.js"></script>

<?php include 'includes/footer.php'; ?>