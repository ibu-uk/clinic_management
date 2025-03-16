<?php 
$page = 'payment-history';
require_once 'config/config.php';
require_once 'includes/auth_validate.php';
include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Payment History</h5>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="recordType" class="form-label">Record Type</label>
                                <select id="recordType" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="clinic_record">Clinic Record</option>
                                    <option value="equipment">Equipment</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="paymentMethod" class="form-label">Payment Method</label>
                                <select id="paymentMethod" class="form-select">
                                    <option value="">All Methods</option>
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="dateFrom" class="form-label">Date From</label>
                                <input type="date" id="dateFrom" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label for="dateTo" class="form-label">Date To</label>
                                <input type="date" id="dateTo" class="form-control">
                            </div>
                        </div>
                        
                        <!-- Payments Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="paymentsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Record Type</th>
                                        <th>Record ID</th>
                                        <th>Payment Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="dataTables_info" id="pageInfo">
                                    Showing 1 to 10 of 0 entries
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="dataTables_paginate">
                                    <ul class="pagination justify-content-end" id="pagination">
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="paymentDetailsContent">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
const itemsPerPage = 10;

// Load payments on page load
document.addEventListener('DOMContentLoaded', function() {
    loadPayments();
    
    // Add event listeners to filters
    document.getElementById('recordType').addEventListener('change', loadPayments);
    document.getElementById('paymentMethod').addEventListener('change', loadPayments);
    document.getElementById('dateFrom').addEventListener('change', loadPayments);
    document.getElementById('dateTo').addEventListener('change', loadPayments);
});

function loadPayments() {
    const filters = {
        record_type: document.getElementById('recordType').value,
        payment_method: document.getElementById('paymentMethod').value,
        date_from: document.getElementById('dateFrom').value,
        date_to: document.getElementById('dateTo').value,
        page: currentPage,
        items_per_page: itemsPerPage
    };
    
    fetch('api/payments/get_history.php?' + new URLSearchParams(filters))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPayments(data.payments);
                updatePagination(data.total_pages);
                updatePageInfo(data.total_records);
            } else {
                console.error('Error loading payments:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function displayPayments(payments) {
    const tbody = document.querySelector('#paymentsTable tbody');
    tbody.innerHTML = '';
    
    payments.forEach(payment => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${payment.id}</td>
            <td>${payment.record_type}</td>
            <td>${payment.record_id}</td>
            <td>${payment.payment_date}</td>
            <td>${parseFloat(payment.amount).toFixed(3)} KWD</td>
            <td>${payment.payment_method}</td>
            <td>${payment.reference_number || '-'}</td>
            <td>${payment.notes || '-'}</td>
            <td>
                <button class="btn btn-sm btn-info" onclick="viewPaymentDetails(${payment.id})">
                    <i class="fas fa-eye"></i>
                </button>
                <a href="#" class="btn btn-sm btn-primary" onclick="printReceipt(${payment.id})">
                    <i class="fas fa-print"></i>
                </a>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function updatePagination(totalPages) {
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';
    
    // Previous button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `
        <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">
            Previous
        </a>
    `;
    pagination.appendChild(prevLi);
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${currentPage === i ? 'active' : ''}`;
        li.innerHTML = `
            <a class="page-link" href="#" onclick="changePage(${i})">
                ${i}
            </a>
        `;
        pagination.appendChild(li);
    }
    
    // Next button
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `
        <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">
            Next
        </a>
    `;
    pagination.appendChild(nextLi);
}

function updatePageInfo(totalRecords) {
    const start = ((currentPage - 1) * itemsPerPage) + 1;
    const end = Math.min(start + itemsPerPage - 1, totalRecords);
    document.getElementById('pageInfo').textContent = 
        `Showing ${start} to ${end} of ${totalRecords} entries`;
}

function changePage(page) {
    currentPage = page;
    loadPayments();
}

function viewPaymentDetails(paymentId) {
    fetch(`api/payments/get_details.php?id=${paymentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPaymentDetails(data.payment);
                new bootstrap.Modal(document.getElementById('paymentDetailsModal')).show();
            } else {
                console.error('Error loading payment details:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function displayPaymentDetails(payment) {
    const content = document.getElementById('paymentDetailsContent');
    content.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Payment ID:</strong> ${payment.id}</p>
                <p><strong>Record Type:</strong> ${payment.record_type}</p>
                <p><strong>Record ID:</strong> ${payment.record_id}</p>
                <p><strong>Payment Date:</strong> ${payment.payment_date}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Amount:</strong> ${parseFloat(payment.amount).toFixed(3)} KWD</p>
                <p><strong>Method:</strong> ${payment.payment_method}</p>
                <p><strong>Reference:</strong> ${payment.reference_number || '-'}</p>
                <p><strong>Notes:</strong> ${payment.notes || '-'}</p>
            </div>
        </div>
        <hr>
        <h6>Installments Paid</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Installment #</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    ${payment.installments.map(inst => `
                        <tr>
                            <td>${inst.installment_number}</td>
                            <td>${inst.due_date}</td>
                            <td>${parseFloat(inst.amount).toFixed(3)} KWD</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function printReceipt(paymentId) {
    window.open(`print_receipt.php?id=${paymentId}`, '_blank');
}
</script>

<?php include 'includes/footer.php'; ?>
