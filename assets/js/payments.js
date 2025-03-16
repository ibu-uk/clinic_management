$(document).ready(function() {
    // Initialize elements
    const $recordType = $('#recordType');
    const $subType = $('#subType');
    const $recordSelect = $('#recordSelect');
    const $paymentAmount = $('#paymentAmount');
    const $summaryCards = $('#summaryCards');
    const $monthlyPaymentCard = $('#monthlyPaymentCard');
    const $paymentHistory = $('#paymentHistory');
    const $paymentForm = $('#paymentForm');
    const $paymentMethod = $('#paymentMethod');
    const $referenceNumber = $('#referenceNumber');
    
    // Initialize date picker with today's date
    const today = new Date().toISOString().split('T')[0];
    $('#paymentDate').val(today);

    // Format currency function
    function formatCurrency(amount) {
        return parseFloat(amount).toFixed(3) + ' KWD';
    }

    // Format date function
    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    // Handle payment method change
    $paymentMethod.on('change', function() {
        const method = $(this).val();
        const $referenceLabel = $referenceNumber.prev('label');
        
        // Update reference number label based on payment method
        switch (method) {
            case 'KNET':
                $referenceLabel.text('KNET Reference Number');
                $referenceNumber.attr('placeholder', 'Enter KNET reference number');
                break;
            case 'credit_card':
                $referenceLabel.text('Transaction ID');
                $referenceNumber.attr('placeholder', 'Enter transaction ID');
                break;
            case 'cheque':
                $referenceLabel.text('Cheque Number');
                $referenceNumber.attr('placeholder', 'Enter cheque number');
                break;
            case 'link':
                $referenceLabel.text('Payment Link Reference');
                $referenceNumber.attr('placeholder', 'Enter payment link reference');
                break;
            default:
                $referenceLabel.text('Reference Number');
                $referenceNumber.attr('placeholder', '');
        }
    });

    // Handle record type change
    $recordType.on('change', function() {
        const type = $(this).val();
        
        // Reset sub type options
        $subType.html('<option value="">Select Sub Type</option>');
        
        // Define subtypes based on record type
        if (type === 'equipment') {
            $subType.append(`
                <option value="new">New</option>
                <option value="renew">Renew</option>
                <option value="upgrade">Upgrade</option>
            `);
        } else if (type === 'clinic') {
            $subType.append(`
                <option value="rent">Rent</option>
                <option value="insurance">Insurance</option>
                <option value="clinic_license">Clinic License</option>
                <option value="fire_safety">Fire Safety</option>
            `);
        }
        
        // Reset and hide elements
        $recordSelect.html('<option value="">Select Record</option>').parent().hide();
        $summaryCards.hide();
        $monthlyPaymentCard.hide();
        $paymentHistory.hide();
        
        // Clear form fields
        $paymentAmount.val('');
        $paymentMethod.val('');
        $referenceNumber.val('');
        $('#notes').val('');
    });

    // Handle sub type change
    $subType.on('change', async function() {
        const type = $recordType.val();
        const subType = $(this).val();
        
        if (!type || !subType) {
            $recordSelect.html('<option value="">Select Record</option>').parent().hide();
            return;
        }
        
        try {
            const response = await fetch(`api/payments/get_records.php?type=${type}&subtype=${subType}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to fetch records');
            }
            
            // Populate record select
            $recordSelect.html('<option value="">Select Record</option>');
            data.records.forEach(record => {
                $recordSelect.append(`
                    <option value="${record.id}">
                        ${record.name} - Remaining: ${formatCurrency(record.remaining_amount)}
                    </option>
                `);
            });
            
            // Show record select
            $recordSelect.parent().show();
            
        } catch (error) {
            console.error('Error:', error);
            alert(error.message);
        }
    });

    // Handle record selection
    $recordSelect.on('change', async function() {
        const recordId = $(this).val();
        const type = $recordType.val();
        
        // Reset and hide elements
        $summaryCards.hide();
        $monthlyPaymentCard.hide();
        $paymentHistory.hide();
        
        if (!recordId) return;
        
        try {
            const response = await fetch(`api/payments/get_record_details.php?type=${type}&id=${recordId}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to fetch record details');
            }
            
            // Update summary cards
            $('#totalCost').text(formatCurrency(data.total_cost));
            $('#remainingAmount').text(formatCurrency(data.remaining_amount));
            
            // Show monthly payment if available
            if (data.monthly_payment) {
                $('#monthlyPayment').text(formatCurrency(data.monthly_payment));
                $monthlyPaymentCard.show();
                $paymentAmount.val(data.monthly_payment);
            } else {
                $monthlyPaymentCard.hide();
                $paymentAmount.val(data.remaining_amount);
            }
            
            // Show summary cards
            $summaryCards.show();
            
            // Fetch and display payment history
            const historyResponse = await fetch(`api/payments/get_payment_history.php?type=${type}&id=${recordId}`);
            const historyData = await historyResponse.json();
            
            if (historyData.success && historyData.payments && historyData.payments.length > 0) {
                const $tbody = $paymentHistory.find('tbody').empty();
                
                historyData.payments.slice(0, 5).forEach(payment => {
                    $tbody.append(`
                        <tr>
                            <td>${formatDate(payment.payment_date)}</td>
                            <td>${payment.reference_no}</td>
                            <td>${formatCurrency(payment.amount)}</td>
                            <td>${payment.status}</td>
                        </tr>
                    `);
                });
                
                $paymentHistory.show();
            }
            
        } catch (error) {
            console.error('Error:', error);
            alert(error.message);
        }
    });

    // Handle form submission
    $paymentForm.on('submit', async function(e) {
        e.preventDefault();
        
        const formData = {
            record_type: $recordType.val(),
            record_id: $recordSelect.val(),
            payment_date: $('#paymentDate').val(),
            payment_method: $paymentMethod.val(),
            reference_number: $referenceNumber.val(),
            description: $('#notes').val(),
            amount: parseFloat($paymentAmount.val())
        };
        
        try {
            // Show loading state
            const $submitBtn = $('button[type="submit"]').prop('disabled', true).text('Processing...');
            
            // Process the payment
            const response = await fetch('api/payments/process_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Failed to process payment');
            }
            
            // Show success message and reset form
            alert('Payment processed successfully');
            window.location.reload();
            
        } catch (error) {
            console.error('Error:', error);
            alert(error.message);
            
            // Reset submit button
            $('button[type="submit"]').prop('disabled', false).text('Record Payment');
        }
    });
});
