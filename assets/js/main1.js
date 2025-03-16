// Toggle form fields based on record type selection
function toggleRecordFields() {
    const recordType = document.getElementById('recordTypeSelect').value;
    document.getElementById('equipmentFields').style.display = recordType === 'Equipment' ? 'block' : 'none';
    document.getElementById('clinicRecordFields').style.display = recordType === 'Clinic Record' ? 'block' : 'none';
}

// Toggle clinic record form based on type selection
function toggleClinicRecordFields() {
    const recordType = document.getElementById('clinicRecordTypeSelect').value;
    const clinicRecordForm = document.getElementById('clinicRecordForm');
    document.getElementById('recordTypeInput').value = recordType;
    clinicRecordForm.style.display = recordType ? 'block' : 'none';
}

// Load dashboard data
async function loadDashboardData() {
    try {
        const response = await fetch('api/dashboard/get-data.php');
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            
            // Update statistics
            document.getElementById('equipmentCount').textContent = data.equipmentCount;
            document.getElementById('pendingPayments').textContent = data.pendingPayments;
            document.getElementById('overduePayments').textContent = data.overduePayments;
            document.getElementById('totalRecords').textContent = data.totalRecords;

            // Update recent payments table
            if (document.getElementById('recentPayments')) {
                document.getElementById('recentPayments').innerHTML = data.recentPayments.map(payment => `
                    <tr>
                        <td>${payment.payment_date}</td>
                        <td>${payment.record_type}</td>
                        <td>${payment.status}</td>
                        <td>${payment.amount}</td>
                    </tr>
                `).join('');
            }

            // Update upcoming payments table
            if (document.getElementById('upcomingPayments')) {
                document.getElementById('upcomingPayments').innerHTML = data.upcomingPayments.map(payment => `
                    <tr>
                        <td>${payment.due_date}</td>
                        <td>${payment.record_type}</td>
                        <td>${payment.amount}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="markAsPaid(${payment.id})">
                                Mark as Paid
                            </button>
                        </td>
                    </tr>
                `).join('');
            }

            // Update equipment list
            if (document.getElementById('equipmentList')) {
                document.getElementById('equipmentList').innerHTML = data.equipmentList.map(equipment => `
                    <tr>
                        <td>${equipment.equipment_name}</td>
                        <td>${equipment.equipment_model}</td>
                        <td>${equipment.company_name}</td>
                        <td>${equipment.contract_number}</td>
                        <td>${equipment.contract_start_date}</td>
                        <td>${equipment.total_cost}</td>
                        <td>${equipment.remaining_amount}</td>
                        <td>${equipment.status}</td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="viewDetails('equipment', ${equipment.id})">
                                View
                            </button>
                        </td>
                    </tr>
                `).join('');
            }

            // Update clinic records list
            if (document.getElementById('clinicRecordsList')) {
                document.getElementById('clinicRecordsList').innerHTML = data.clinicRecordsList.map(record => `
                    <tr>
                        <td>${record.record_type}</td>
                        <td>${record.company_name}</td>
                        <td>${record.contract_number}</td>
                        <td>${record.contract_date}</td>
                        <td>${record.expiry_date}</td>
                        <td>${record.total_amount}</td>
                        <td>${record.remaining_amount}</td>
                        <td>${record.status}</td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="viewDetails('clinic', ${record.id})">
                                View
                            </button>
                        </td>
                    </tr>
                `).join('');
            }
        } else {
            showNotification('Error loading dashboard data', false);
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error loading dashboard data', false);
    }
}

// Show notification
function showNotification(message, isSuccess = true) {
    const notificationBox = document.getElementById('notificationBox');
    notificationBox.className = `alert ${isSuccess ? 'alert-success' : 'alert-danger'}`;
    notificationBox.textContent = message;
    notificationBox.style.display = 'block';

    setTimeout(() => {
        notificationBox.style.display = 'none';
    }, 5000);
}

// Mark payment as paid
async function markAsPaid(paymentId) {
    try {
        const response = await fetch('api/payments/mark-paid.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ paymentId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message);
            loadDashboardData();
        } else {
            showNotification(result.message, false);
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred while marking payment as paid', false);
    }
}

// View details
function viewDetails(type, id) {
    window.location.href = `view-details.php?type=${type}&id=${id}`;
}

// Load data when page loads
document.addEventListener('DOMContentLoaded', () => {
    loadDashboardData();
});

// Handle equipment form submission
document.getElementById('equipmentForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(this);
        const response = await fetch('api/equipment/create.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message);
            this.reset();
            document.getElementById('recordTypeSelect').value = '';
            document.getElementById('equipmentFields').style.display = 'none';
            loadDashboardData();
        } else {
            showNotification(result.message, false);
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred while submitting the form', false);
    }
});

// Handle clinic record form submission
document.getElementById('clinicRecordForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(this);
        const response = await fetch('api/clinic-records/create.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message);
            this.reset();
            document.getElementById('recordTypeSelect').value = '';
            document.getElementById('clinicRecordFields').style.display = 'none';
            document.getElementById('clinicRecordTypeSelect').value = '';
            loadDashboardData();
        } else {
            showNotification(result.message, false);
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred while submitting the form', false);
    }
});

// Calculate expiry date based on start date and months
function calculateExpiryDate(startDateId, monthsId, expiryDateId) {
    const startDate = document.getElementById(startDateId).value;
    const months = document.getElementById(monthsId).value;
    
    if (startDate && months) {
        const expiryDate = new Date(startDate);
        expiryDate.setMonth(expiryDate.getMonth() + parseInt(months));
        document.getElementById(expiryDateId).value = expiryDate.toISOString().split('T')[0];
    }
}

// Calculate monthly payment based on total amount and months
function calculateMonthlyPayment(totalAmountId, monthsId, monthlyPaymentId) {
    const totalAmount = document.getElementById(totalAmountId).value;
    const months = document.getElementById(monthsId).value;
    
    if (totalAmount && months) {
        const monthlyPayment = (parseFloat(totalAmount) / parseInt(months)).toFixed(2);
        document.getElementById(monthlyPaymentId).value = monthlyPayment;
    }
}

// Delete record function
async function deleteRecord(type, id) {
    if (!confirm('Are you sure you want to delete this record?')) {
        return;
    }
    
    try {
        const response = await fetch(`api/${type}/delete.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id })
        });
        
        const result = await response.json();
        if (result.success) {
            showNotification(result.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(result.message, false);
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred while deleting the record', false);
    }
}

// View PDF contract
function viewContract(filepath) {
    window.open(`uploads/${filepath}`, '_blank');
}
