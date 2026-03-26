@extends('admin.layout')

@section('title', 'Edit Invoice - ' . $invoice->invoice_number)

@section('content')
<div class="row mb-md-4">
    <div class="col-12">
        <h1>Edit Invoice</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.applications') }}">Applications</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.applications.show', $invoice->application_id) }}">{{ $invoice->application->application_id }}</a></li>
                <li class="breadcrumb-item active">Edit Invoice</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-c-blue shadow mb-4">
            <div class="card-header theme-bg-blue text-white">
                <h5 class="mb-0">Invoice: {{ $invoice->invoice_number }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.applications.invoice.update', $invoice->id) }}" id="invoiceForm" class="theme-forms">
                    @csrf
                    @method('PUT')

                    <div class="row mb-4 gy-3">
                        <div class="col-md-6">
                            <label for="invoice_date" class="form-label">Invoice Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="invoice_date" name="invoice_date" value="{{ $invoice->invoice_date->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="due_date" name="due_date" value="{{ $invoice->due_date->format('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="row mb-4 gy-3">
                        <div class="col-md-4">
                            <label for="billing_period" class="form-label">Billing Period</label>
                            <input type="text" class="form-control" id="billing_period" name="billing_period" value="{{ $invoice->billing_period }}" placeholder="e.g., 2025-01, 2025-Q1">
                        </div>
                        <div class="col-md-4">
                            <label for="billing_start_date" class="form-label">Billing Start Date</label>
                            <input type="date" class="form-control" id="billing_start_date" name="billing_start_date" value="{{ $invoice->billing_start_date ? $invoice->billing_start_date->format('Y-m-d') : '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="billing_end_date" class="form-label">Billing End Date</label>
                            <input type="date" class="form-control" id="billing_end_date" name="billing_end_date" value="{{ $invoice->billing_end_date ? $invoice->billing_end_date->format('Y-m-d') : '' }}">
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3">Line Items / Particulars</h5>
                    <div id="lineItemsContainer">
                        @php
                            // Use segments if provided, otherwise fall back to line_items
                            if (isset($segments) && !empty($segments)) {
                                $items = $segments;
                            } else {
                                $lineItems = $invoice->line_items ?? [];
                                // Handle both old format (segments array) and new format
                                if (isset($lineItems['segments']) && is_array($lineItems['segments'])) {
                                    $items = $lineItems['segments'];
                                } elseif (is_array($lineItems) && !isset($lineItems[0]['description'])) {
                                    // Convert old format to new format
                                    $items = [];
                                    foreach ($lineItems as $key => $value) {
                                        if ($key !== '_metadata' && is_array($value) && (isset($value['description']) || isset($value['start']))) {
                                            // Convert segment format to display format
                                            if (isset($value['start'])) {
                                                $items[] = [
                                                    'description' => $value['description'] ?? ($value['plan_label'] ?? '') . ' - ' . $value['capacity'] . ' Port Capacity (' . ($value['start'] ?? '') . ' to ' . ($value['end'] ?? '') . ')',
                                                    'quantity' => 1,
                                                    'rate' => $value['amount_full'] ?? $value['rate'] ?? 0,
                                                    'amount' => $value['amount_prorated'] ?? $value['amount'] ?? 0,
                                                ];
                                            } else {
                                                $items[] = $value;
                                            }
                                        }
                                    }
                                } else {
                                    $items = $lineItems;
                                }
                            }
                            if (empty($items)) {
                                $items = [['description' => '', 'quantity' => 1, 'rate' => 0, 'amount' => 0]];
                            }
                        @endphp
                        @foreach($items as $index => $item)
                        <div class="line-item-row mb-3 p-3 border rounded border-c-blue">
                            <div class="row g-2 gy-3">
                                <div class="col-md-6">
                                    <label class="form-label small">Description <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" name="line_items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Quantity</label>
                                    <input type="number" class="form-control form-control-sm quantity-input" name="line_items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" step="0.01" min="0">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Rate</label>
                                    <input type="number" class="form-control form-control-sm rate-input" name="line_items[{{ $index }}][rate]" value="{{ $item['rate'] ?? 0 }}" step="0.01" min="0">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Amount</label>
                                    <input type="number" class="form-control form-control-sm amount-input" name="line_items[{{ $index }}][amount]" value="{{ $item['amount'] ?? 0 }}" step="0.01" min="0">
                                </div>
                            </div>
                            @if($index > 0)
                            <button type="button" class="btn btn-sm btn-danger mt-2 remove-line-item">Remove</button>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm theme-bg-blue text-white mt-2" id="addLineItem">+ Add Line Item</button>

                    <hr class="my-4">

                    <div class="row gy-4 mb-4">
                        <div class="col-md-4">
                            <label for="amount" class="form-label">Base Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="amount" name="amount" value="{{ $invoice->amount }}" step="0.01" min="0" required>
                            <small class="text-muted">Amount before GST</small>
                        </div>
                        <div class="col-md-4">
                            <label for="tds_amount" class="form-label">TDS Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="tds_amount" name="tds_amount" value="{{ $invoice->tds_amount ?? 0 }}" step="0.01" min="0" required>
                            <small class="text-muted">TDS Amount (max 10% of base amount)</small>
                            <div class="invalid-feedback" id="tds_amount_error" style="display: none;"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="gst_amount" class="form-label">GST Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="gst_amount" name="gst_amount" value="{{ $invoice->gst_amount }}" step="0.01" min="0" required>
                            <small class="text-muted">GST/IGST amount</small>
                        </div>
                        <div class="col-md-4">
                            <label for="total_amount" class="form-label">Total Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="total_amount" name="total_amount" value="{{ $invoice->total_amount }}" step="0.01" min="0" required>
                            <small class="text-muted">Base + GST - TDS + Carry Forward</small>
                        </div>
                    </div>

                    <div class="row mb-4 gy-3">
                        <div class="col-md-4">
                            <label for="carry_forward_amount" class="form-label">Carry Forward Amount</label>
                            <input type="number" class="form-control" id="carry_forward_amount" name="carry_forward_amount" value="{{ $invoice->carry_forward_amount ?? 0 }}" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label for="has_carry_forward" class="form-label">Has Carry Forward</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="has_carry_forward" name="has_carry_forward" value="1" {{ $invoice->has_carry_forward ? 'checked' : '' }}>
                                <label class="form-check-label" for="has_carry_forward">
                                    Yes
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row mb-4 gy-3">
                        <div class="col-md-4">
                            <label for="paid_amount" class="form-label">Paid Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="paid_amount" name="paid_amount" value="{{ $invoice->paid_amount ?? 0 }}" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label for="balance_amount" class="form-label">Balance Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="balance_amount" name="balance_amount" value="{{ $invoice->balance_amount ?? $invoice->total_amount }}" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="row mb-4 gy-3">
                        <div class="col-md-6">
                            <label for="payment_status" class="form-label">Payment Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="payment_status" name="payment_status" required>
                                <option value="pending" {{ $invoice->payment_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="partial" {{ $invoice->payment_status === 'partial' ? 'selected' : '' }}>Partial</option>
                                <option value="paid" {{ $invoice->payment_status === 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="overdue" {{ $invoice->payment_status === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                <option value="cancelled" {{ $invoice->payment_status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending" {{ $invoice->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="paid" {{ $invoice->status === 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="overdue" {{ $invoice->status === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                <option value="cancelled" {{ $invoice->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-4 gy-3">
                        <div class="col-md-6">
                            <label for="manual_payment_id" class="form-label">Manual Payment ID</label>
                            <input type="text" class="form-control" id="manual_payment_id" name="manual_payment_id" value="{{ $invoice->manual_payment_id }}" placeholder="Payment reference/UTR">
                        </div>
                        <div class="col-md-6">
                            <label for="manual_payment_notes" class="form-label">Payment Notes</label>
                            <textarea class="form-control" id="manual_payment_notes" name="manual_payment_notes" rows="2">{{ $invoice->manual_payment_notes }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('admin.applications.show', $invoice->application_id) }}" class="btn btn-danger">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let lineItemIndex = {{ count($items) }};

    // Add line item
    document.getElementById('addLineItem').addEventListener('click', function() {
        const container = document.getElementById('lineItemsContainer');
        const newItem = document.createElement('div');
        newItem.className = 'line-item-row mb-3 p-3 border rounded';
        newItem.innerHTML = `
            <div class="row g-2 gy-3">
                <div class="col-md-6">
                    <label class="form-label small">Description <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" name="line_items[${lineItemIndex}][description]" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Quantity</label>
                    <input type="number" class="form-control form-control-sm quantity-input" name="line_items[${lineItemIndex}][quantity]" value="1" step="0.01" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Rate</label>
                    <input type="number" class="form-control form-control-sm rate-input" name="line_items[${lineItemIndex}][rate]" value="0" step="0.01" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Amount</label>
                    <input type="number" class="form-control form-control-sm amount-input" name="line_items[${lineItemIndex}][amount]" value="0" step="0.01" min="0">
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-danger mt-2 remove-line-item">Remove</button>
        `;
        container.appendChild(newItem);
        lineItemIndex++;
        
        // Attach event listeners to new inputs
        attachLineItemListeners(newItem);
        
        // Update base amount after adding new item
        updateBaseAmountFromLineItems();
    });

    // Remove line item
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-line-item')) {
            if (confirm('Are you sure you want to remove this line item?')) {
                e.target.closest('.line-item-row').remove();
                // Update base amount after removing item
                updateBaseAmountFromLineItems();
            }
        }
    });

    // Get form inputs first
    const amountInput = document.getElementById('amount');
    const gstInput = document.getElementById('gst_amount');
    const tdsAmountInput = document.getElementById('tds_amount');
    const totalInput = document.getElementById('total_amount');
    const tdsAmountError = document.getElementById('tds_amount_error');

    // Validate TDS amount (must be between 0 and 10% of base amount)
    function validateTdsAmount() {
        const amount = parseFloat(amountInput.value) || 0;
        const tdsAmount = parseFloat(tdsAmountInput.value) || 0;
        const maxTdsAmount = (amount * 10) / 100; // 10% of base amount
        
        // Update max attribute dynamically
        tdsAmountInput.setAttribute('max', maxTdsAmount.toFixed(2));
        
        if (tdsAmount < 0) {
            tdsAmountInput.classList.add('is-invalid');
            tdsAmountError.textContent = 'TDS amount cannot be less than 0';
            tdsAmountError.style.display = 'block';
            return false;
        } else if (tdsAmount > maxTdsAmount) {
            tdsAmountInput.classList.add('is-invalid');
            tdsAmountError.textContent = `TDS amount cannot exceed 10% of base amount (₹${maxTdsAmount.toFixed(2)})`;
            tdsAmountError.style.display = 'block';
            return false;
        } else {
            tdsAmountInput.classList.remove('is-invalid');
            tdsAmountError.style.display = 'none';
            return true;
        }
    }

    // Calculate total amount
    // Total = Base Amount + GST Amount - TDS Amount + Carry Forward (if any)
    function calculateTotal() {
        const amount = parseFloat(amountInput.value) || 0;
        const gst = parseFloat(gstInput.value) || 0;
        const tds = parseFloat(tdsAmountInput.value) || 0;
        // Check if there's a carry forward amount
        const carryForwardInput = document.getElementById('carry_forward_amount');
        const carryForward = carryForwardInput ? parseFloat(carryForwardInput.value) || 0 : 0;
        // Total = Base + GST - TDS + Carry Forward
        const total = amount + gst - tds + carryForward;
        totalInput.value = Math.max(0, total).toFixed(2); // Ensure total is not negative
        
        // Update balance amount if needed
        const paidAmount = parseFloat(document.getElementById('paid_amount').value) || 0;
        const balanceInput = document.getElementById('balance_amount');
        balanceInput.value = Math.max(0, (parseFloat(totalInput.value) - paidAmount)).toFixed(2);
    }
    
    // Update base amount from line items and recalculate total
    function updateBaseAmountFromLineItems() {
        let total = 0;
        document.querySelectorAll('.amount-input').forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        if (amountInput) {
            amountInput.value = total.toFixed(2);
            validateTdsAmount();
            calculateTotal();
        }
    }

    // Calculate amount from quantity * rate
    function attachLineItemListeners(itemRow) {
        const quantityInput = itemRow.querySelector('.quantity-input');
        const rateInput = itemRow.querySelector('.rate-input');
        const amountInput = itemRow.querySelector('.amount-input');

        function calculateAmount() {
            const quantity = parseFloat(quantityInput.value) || 0;
            const rate = parseFloat(rateInput.value) || 0;
            amountInput.value = (quantity * rate).toFixed(2);
            // Update base amount and recalculate total
            updateBaseAmountFromLineItems();
        }

        if (quantityInput) quantityInput.addEventListener('input', calculateAmount);
        if (rateInput) rateInput.addEventListener('input', calculateAmount);
        if (amountInput) amountInput.addEventListener('input', updateBaseAmountFromLineItems);
    }

    // Attach listeners to existing line items
    document.querySelectorAll('.line-item-row').forEach(item => {
        attachLineItemListeners(item);
    });
    
    // Initialize base amount from line items on page load
    if (amountInput) {
        updateBaseAmountFromLineItems();
    }

    // Validate TDS and recalculate total when base amount or TDS amount changes
    if (amountInput) {
        amountInput.addEventListener('input', function() {
            validateTdsAmount();
            calculateTotal();
        });
    }
    if (tdsAmountInput) {
        tdsAmountInput.addEventListener('input', function() {
            validateTdsAmount();
            calculateTotal();
        });
        tdsAmountInput.addEventListener('blur', validateTdsAmount);
    }
    if (gstInput) {
        gstInput.addEventListener('input', calculateTotal);
    }
    
    // Update total when carry forward changes
    const carryForwardInput = document.getElementById('carry_forward_amount');
    if (carryForwardInput) {
        carryForwardInput.addEventListener('input', calculateTotal);
    }
    
    // Validate TDS on page load
    if (tdsAmountInput) {
        validateTdsAmount();
    }
    
    // Validate before form submission
    const invoiceForm = document.getElementById('invoiceForm');
    if (invoiceForm) {
        invoiceForm.addEventListener('submit', function(e) {
            if (tdsAmountInput && !validateTdsAmount()) {
                e.preventDefault();
                e.stopPropagation();
                tdsAmountInput.focus();
                return false;
            }
        });
    }

    // Update balance when paid amount changes
    const paidAmountInput = document.getElementById('paid_amount');
    const balanceAmountInput = document.getElementById('balance_amount');
    
    if (paidAmountInput && balanceAmountInput && totalInput) {
        paidAmountInput.addEventListener('input', function() {
            const total = parseFloat(totalInput.value) || 0;
            const paid = parseFloat(paidAmountInput.value) || 0;
            balanceAmountInput.value = Math.max(0, (total - paid)).toFixed(2);
        });
    }
});
</script>
@endsection

