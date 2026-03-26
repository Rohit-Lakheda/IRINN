@extends('admin.layout')

@section('title', 'Payment Allocation')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1" style="color: #2c3e50; font-weight: 600;">Payment Allocation</h2>
            <p class="text-muted mb-0">Allocate a single payment across multiple invoices for a user</p>
            <div class="accent-line"></div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Allocate Payment</h5>
                </div>
                <div class="card-body">
                    <form id="paymentAllocationForm" method="POST" action="{{ route('admin.applications.allocate-payment') }}">
                        @csrf
                        
                        <!-- User Selection -->
                        <div class="mb-4">
                            <label for="user_search" class="form-label fw-bold">Select User</label>
                            <input type="text" 
                                   id="user_search" 
                                   class="form-control" 
                                   placeholder="Search by name, email, registration ID, or mobile..."
                                   autocomplete="off">
                            <input type="hidden" name="user_id" id="user_id" required>
                            <div id="user_results" class="list-group mt-2" style="display: none; max-height: 300px; overflow-y: auto;"></div>
                            <div id="selected_user" class="mt-2" style="display: none;">
                                <div class="alert alert-info d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong id="selected_user_name"></strong><br>
                                        <small id="selected_user_details"></small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearUserSelection()">Clear</button>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Details -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="total_payment_amount" class="form-label fw-bold">Total Payment Amount (₹)</label>
                                <input type="number" 
                                       step="0.01" 
                                       min="0.01" 
                                       class="form-control" 
                                       id="total_payment_amount" 
                                       name="total_payment_amount" 
                                       required
                                       placeholder="Enter payment amount">
                            </div>
                            <div class="col-md-6">
                                <label for="payment_reference" class="form-label fw-bold">Payment Reference / UTR</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="payment_reference" 
                                       name="payment_reference" 
                                       required
                                       placeholder="Enter payment ID or reference">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="form-label fw-bold">Notes (Optional)</label>
                            <textarea class="form-control" 
                                      id="notes" 
                                      name="notes" 
                                      rows="3" 
                                      placeholder="Any additional notes about this payment..."></textarea>
                        </div>

                        <!-- Invoices List -->
                        <div id="invoices_section" style="display: none;">
                            <h5 class="mb-3">Pending Invoices</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Application ID</th>
                                            <th>Total Amount</th>
                                            <th>Paid Amount</th>
                                            <th>Balance</th>
                                            <th>Due Date</th>
                                            <th>Allocate Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="invoices_table_body">
                                        <!-- Invoices will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Total Allocated: ₹<span id="total_allocated">0.00</span></strong><br>
                                        <small class="text-muted">Remaining: ₹<span id="remaining_amount">0.00</span></small>
                                    </div>
                                    <button type="submit" class="btn btn-primary" id="submit_btn" disabled>
                                        Allocate Payment
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div id="no_invoices" class="alert alert-info" style="display: none;">
                            No pending invoices found for this user.
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedUserId = null;
let totalPaymentAmount = 0;
let allocations = {};

// User search functionality
let searchTimeout;
document.getElementById('user_search').addEventListener('input', function(e) {
    const query = e.target.value.trim();
    
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        document.getElementById('user_results').style.display = 'none';
        return;
    }

    searchTimeout = setTimeout(() => {
        fetch(`{{ route('admin.applications.search-users') }}?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                const users = data.users || [];
                const resultsDiv = document.getElementById('user_results');
                resultsDiv.innerHTML = '';
                
                if (users.length === 0) {
                    resultsDiv.innerHTML = '<div class="list-group-item">No users found</div>';
                } else {
                    users.forEach(user => {
                        const item = document.createElement('a');
                        item.href = '#';
                        item.className = 'list-group-item list-group-item-action';
                        item.innerHTML = `
                            <strong>${user.name}</strong><br>
                            <small>${user.email}</small><br>
                            <small class="text-muted">Reg ID: ${user.registration_id} | Mobile: ${user.mobile || 'N/A'}</small>
                        `;
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            selectUser(user.id, user.name, user.email, user.registration_id);
                        });
                        resultsDiv.appendChild(item);
                    });
                }
                
                resultsDiv.style.display = 'block';
            })
            .catch(error => {
                console.error('Error searching users:', error);
                document.getElementById('user_results').innerHTML = '<div class="list-group-item text-danger">Error searching users. Please try again.</div>';
            });
    }, 300); // Debounce for 300ms
});

function selectUser(userId, name, email, regId) {
    selectedUserId = userId;
    document.getElementById('user_id').value = userId;
    document.getElementById('selected_user_name').textContent = name;
    document.getElementById('selected_user_details').textContent = `${email} | Reg ID: ${regId}`;
    document.getElementById('selected_user').style.display = 'block';
    document.getElementById('user_results').style.display = 'none';
    document.getElementById('user_search').value = '';
    
    // Load invoices for this user
    loadUserInvoices(userId);
}

function clearUserSelection() {
    selectedUserId = null;
    document.getElementById('user_id').value = '';
    document.getElementById('selected_user').style.display = 'none';
    document.getElementById('invoices_section').style.display = 'none';
    document.getElementById('no_invoices').style.display = 'none';
    allocations = {};
    updateTotals();
}

function loadUserInvoices(userId) {
    fetch(`{{ route('admin.applications.user.invoices', ':userId') }}`.replace(':userId', userId))
        .then(response => response.json())
        .then(data => {
            const invoices = data.invoices || [];
            const tbody = document.getElementById('invoices_table_body');
            tbody.innerHTML = '';
            allocations = {};
            
            if (invoices.length === 0) {
                document.getElementById('invoices_section').style.display = 'none';
                document.getElementById('no_invoices').style.display = 'block';
                return;
            }
            
            document.getElementById('invoices_section').style.display = 'block';
            document.getElementById('no_invoices').style.display = 'none';
            
            invoices.forEach(invoice => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${invoice.invoice_number}</td>
                    <td>${invoice.application_id}</td>
                    <td>₹${parseFloat(invoice.total_amount).toFixed(2)}</td>
                    <td>₹${parseFloat(invoice.paid_amount).toFixed(2)}</td>
                    <td>₹${parseFloat(invoice.balance_amount).toFixed(2)}</td>
                    <td>${invoice.due_date}</td>
                    <td>
                        <input type="number" 
                               step="0.01" 
                               min="0" 
                               max="${invoice.balance_amount}" 
                               class="form-control allocation-input" 
                               data-invoice-id="${invoice.id}"
                               data-balance="${invoice.balance_amount}"
                               placeholder="0.00"
                               onchange="updateAllocation(${invoice.id}, this.value)">
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error loading invoices:', error);
            alert('Error loading invoices. Please try again.');
        });
}

function updateAllocation(invoiceId, amount) {
    const amountNum = parseFloat(amount) || 0;
    allocations[invoiceId] = amountNum;
    updateTotals();
}

function updateTotals() {
    const totalAllocated = Object.values(allocations).reduce((sum, val) => sum + val, 0);
    totalPaymentAmount = parseFloat(document.getElementById('total_payment_amount').value) || 0;
    
    document.getElementById('total_allocated').textContent = totalAllocated.toFixed(2);
    document.getElementById('remaining_amount').textContent = (totalPaymentAmount - totalAllocated).toFixed(2);
    
    // Validate allocation
    const difference = Math.abs(totalAllocated - totalPaymentAmount);
    const submitBtn = document.getElementById('submit_btn');
    
    if (totalAllocated > 0 && difference < 0.01) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('btn-secondary');
        submitBtn.classList.add('btn-primary');
    } else {
        submitBtn.disabled = true;
        submitBtn.classList.remove('btn-primary');
        submitBtn.classList.add('btn-secondary');
    }
}

// Update totals when payment amount changes
document.getElementById('total_payment_amount').addEventListener('input', updateTotals);

// Form submission
document.getElementById('paymentAllocationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const allocationsArray = Object.keys(allocations).map(invoiceId => ({
        invoice_id: invoiceId,
        amount: allocations[invoiceId]
    })).filter(item => item.amount > 0);
    
    if (allocationsArray.length === 0) {
        alert('Please allocate at least some amount to an invoice.');
        return;
    }
    
    // Add allocations as hidden inputs
    allocationsArray.forEach((allocation, index) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = `allocations[${index}][invoice_id]`;
        input.value = allocation.invoice_id;
        this.appendChild(input);
        
        const amountInput = document.createElement('input');
        amountInput.type = 'hidden';
        amountInput.name = `allocations[${index}][amount]`;
        amountInput.value = allocation.amount;
        this.appendChild(amountInput);
    });
    
    if (confirm('Are you sure you want to allocate this payment?')) {
        this.submit();
    }
});

// Close user results when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#user_search') && !e.target.closest('#user_results')) {
        document.getElementById('user_results').style.display = 'none';
    }
});
</script>
@endsection

