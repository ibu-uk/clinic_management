<?php
$page = 'reports';
$page_title = 'Reports';
require_once 'includes/header.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<style>
    @media print {
        .no-print {
            display: none !important;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        body {
            padding: 20px;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 4px;
        }
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        .badge-warning {
            background-color: #ffc107;
            color: black;
        }
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Reports</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" id="startDate" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" id="endDate" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Report Type</label>
                            <select id="reportType" class="form-select">
                                <option value="all">All Reports</option>
                                <option value="equipment">Equipment</option>
                                <option value="clinic">Clinic Records</option>
                                <option value="payments">Payments</option>
                            </select>
                        </div>
                        <div class="col-12 mt-3 no-print">
                            <button class="btn btn-primary" onclick="loadReport()">Generate Report</button>
                            <button class="btn btn-success ms-2" onclick="exportReport()">Export to Excel</button>
                            <button class="btn btn-info ms-2" onclick="window.print()">Print Report</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Results -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Report Results</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Name/Record</th>
                                    <th>Company</th>
                                    <th>Contract #</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Payment Type</th>
                                    <th>Payment Method</th>
                                    <th>Total (KWD)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="reportTableBody">
                                <tr>
                                    <td colspan="10" class="text-center">No data available</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadReport() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const type = document.getElementById('reportType').value;

    // Show loading state
    document.getElementById('reportTableBody').innerHTML = '<tr><td colspan="10" class="text-center">Loading...</td></tr>';

    try {
        console.log('Fetching report data...');
        console.log(`Parameters: startDate=${startDate}, endDate=${endDate}, type=${type}`);

        const response = await fetch(`api/reports/get_report.php?startDate=${startDate}&endDate=${endDate}&type=${type}`);
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Server response:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Report data:', data);

        const tbody = document.getElementById('reportTableBody');

        if (data.status === 'success' && Array.isArray(data.data)) {
            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center">No records found for the selected criteria</td></tr>';
                return;
            }

            tbody.innerHTML = '';
            data.data.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${escapeHtml(item.record_type) || '-'}</td>
                    <td>${escapeHtml(item.name) || '-'}</td>
                    <td>${escapeHtml(item.company_name) || '-'}</td>
                    <td>${escapeHtml(item.contract_number) || '-'}</td>
                    <td>${formatDate(item.start_date) || '-'}</td>
                    <td>${formatDate(item.end_date) || '-'}</td>
                    <td>${escapeHtml(item.payment_type) || '-'}</td>
                    <td>${escapeHtml(item.payment_method) || '-'}</td>
                    <td>${formatAmount(item.total_amount)}</td>
                    <td><span class="badge ${getBadgeClass(item.status)}">${escapeHtml(item.status) || '-'}</span></td>
                `;
                tbody.appendChild(row);
            });
        } else {
            console.error('Invalid data format:', data);
            tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger">Invalid data format received from server</td></tr>';
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('reportTableBody').innerHTML = 
            `<tr><td colspan="10" class="text-center text-danger">Error: ${escapeHtml(error.message)}</td></tr>`;
    }
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    try {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    } catch (e) {
        console.error('Date formatting error:', e);
        return dateStr;
    }
}

function formatAmount(amount) {
    if (!amount) return '0.000';
    try {
        return parseFloat(amount).toFixed(3);
    } catch (e) {
        console.error('Amount formatting error:', e);
        return '0.000';
    }
}

function getBadgeClass(status) {
    if (!status) return 'bg-secondary';
    
    switch(status.toLowerCase()) {
        case 'active':
        case 'completed':
            return 'bg-success';
        case 'pending':
            return 'bg-warning';
        case 'overdue':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

function exportReport() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const type = document.getElementById('reportType').value;

    console.log('Exporting report...');
    console.log(`Parameters: startDate=${startDate}, endDate=${endDate}, type=${type}`);

    window.location.href = `api/reports/export_report.php?startDate=${startDate}&endDate=${endDate}&type=${type}`;
}

// Load initial report
document.addEventListener('DOMContentLoaded', function() {
    // Set default dates
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    document.getElementById('startDate').value = thirtyDaysAgo.toISOString().split('T')[0];
    document.getElementById('endDate').value = today.toISOString().split('T')[0];
    
    loadReport();
});
</script>

<?php require_once 'includes/footer.php'; ?>
