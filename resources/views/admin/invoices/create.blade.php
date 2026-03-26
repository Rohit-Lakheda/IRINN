@extends('admin.layout')

@section('title', 'Generate Invoice - ' . $application->application_id)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1>Generate Invoice</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.applications') }}">Applications</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.applications.show', $application->id) }}">{{ $application->application_id }}</a></li>
                <li class="breadcrumb-item active">Generate Invoice</li>
            </ol>
        </nav>
    </div>
</div>

@if(isset($invoiceData['error']))
<div class="alert alert-danger">
    {{ $invoiceData['error'] }}
</div>
@else
<div class="row">
    <div class="col-12">
        <div class="card border-c-blue shadow mb-4">
            <div class="card-header theme-bg-blue text-white">
                <h5 class="mb-0">Invoice for: {{ $application->application_id }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.applications.ix-account.generate-invoice.store', $application->id) }}" id="invoiceForm" class="theme-forms">
                    @csrf
                    @if(!empty($invoiceData['pending_invoice']))
                        <input type="hidden" name="pending_invoice" value="1">
                    @endif

                    <div class="row gy-3 mb-4">
                        <div class="col-md-4">
                            <label for="billing_start_date" class="form-label">Billing Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="billing_start_date" name="billing_start_date" value="{{ $invoiceData['billing_start_date'] }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="billing_end_date" class="form-label">Billing End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="billing_end_date" name="billing_end_date" value="{{ $invoiceData['billing_end_date'] }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="due_date" name="due_date" value="{{ $invoiceData['due_date'] }}" required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="billing_period" class="form-label">Billing Period</label>
                            <input type="text" class="form-control" id="billing_period" name="billing_period" value="{{ $invoiceData['billing_period'] }}" placeholder="e.g., 2025-01, 2025-Q1">
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3">Line Items / Particulars</h5>
                    <div id="lineItemsContainer">
                        @foreach($invoiceData['segments'] as $index => $segment)
                        <div class="line-item-row mb-3 p-3 border rounded border-c-blue {{ isset($segment['is_adjustment']) && $segment['is_adjustment'] ? 'bg-light' : '' }}">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Description <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="line_items[{{ $index }}][description]" value="{{ $segment['description'] ?? ('IX Service - ' . ($segment['capacity'] ?? '') . ' Port Capacity (' . ($segment['plan_label'] ?? $segment['plan'] ?? '') . ')') }}" required>
                                    @php
                                        $periodStart = $segment['start'] ?? $invoiceData['billing_start_date'] ?? null;
                                        $periodEnd = $segment['end'] ?? $invoiceData['billing_end_date'] ?? null;
                                        $days = $segment['days'] ?? null;
                                        if (!$days && $periodStart && $periodEnd) {
                                            try {
                                                $start = new \DateTime($periodStart);
                                                $end = new \DateTime($periodEnd);
                                                $days = $start->diff($end)->days + 1;
                                            } catch (\Exception $e) {
                                                $days = null;
                                            }
                                        }
                                        $showPeriod = isset($segment['show_period']) ? (bool)$segment['show_period'] : true;
                                    @endphp
                                    <input type="hidden" name="line_items[{{ $index }}][show_period]" value="0">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input show-period-checkbox" type="checkbox" name="line_items[{{ $index }}][show_period]" id="show_period_{{ $index }}" value="1" {{ $showPeriod ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="show_period_{{ $index }}">
                                            Show Period
                                        </label>
                                    </div>
                                    <small class="text-muted period-display" style="display: {{ $showPeriod && $periodStart && $periodEnd ? 'block' : 'none' }};">
                                        Period: {{ $periodStart && $periodEnd ? date('Y-m-d', strtotime($periodStart)) . ' to ' . date('Y-m-d', strtotime($periodEnd)) . ($days ? ' (' . $days . ' days)' : '') : '' }}
                                    </small>
                                    @if(isset($segment['is_adjustment']) && $segment['is_adjustment'])
                                    <input type="hidden" name="line_items[{{ $index }}][is_adjustment]" value="1">
                                    <input type="hidden" name="line_items[{{ $index }}][adjustment_type]" value="{{ $segment['adjustment_type'] ?? 'adjustment' }}">
                                    @if(!empty($segment['plan_change_id']))
                                    <input type="hidden" name="line_items[{{ $index }}][plan_change_id]" value="{{ $segment['plan_change_id'] }}">
                                    @endif
                                    @endif
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" class="form-control quantity-input" name="line_items[{{ $index }}][quantity]" value="{{ $segment['quantity'] ?? 1 }}" step="0.01" min="0" {{ (isset($segment['is_adjustment']) && $segment['is_adjustment']) ? 'readonly' : '' }}>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Rate (₹)</label>
                                    <input type="number" class="form-control rate-input" name="line_items[{{ $index }}][rate]" value="{{ $segment['rate'] ?? $segment['amount_full'] ?? 0 }}" step="0.01" min="0" {{ (isset($segment['is_adjustment']) && $segment['is_adjustment']) ? 'readonly' : '' }}>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Amount (₹)</label>
                                    <input type="number" class="form-control amount-input" name="line_items[{{ $index }}][amount]" value="{{ $segment['amount'] ?? $segment['amount_prorated'] ?? 0 }}" step="0.01" {{ (isset($segment['is_adjustment']) && $segment['is_adjustment']) ? 'readonly' : 'min="0"' }}>
                                </div>
                            </div>
                            @if($index > 0 && !(isset($segment['is_adjustment']) && $segment['is_adjustment']))
                            <button type="button" class="btn btn-sm btn-danger mt-2 remove-line-item">
                                <i class="bi bi-trash"></i> Remove Line Item
                            </button>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm theme-bg-blue mt-2 text-white" id="addLineItem">
                        <i class="bi bi-plus-circle"></i> Add Line Item
                    </button>

                    <hr class="my-4">

                    <div class="row gy-3 mb-4">
                        <div class="col-md-4">
                            <label for="amount" class="form-label">Base Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="amount" name="amount" value="{{ $invoiceData['amount'] }}" step="0.01" min="0" required>
                            <small class="text-muted">Amount before GST</small>
                        </div>
                        <!-- <div class="col-md-4">
                            <label for="tds_percentage" class="form-label">TDS % <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="tds_percentage" name="tds_percentage" value="{{ $invoiceData['tds_percentage'] ?? 0 }}" step="0.01" min="0" required>
                            <small class="text-muted">TDS %</small>
                        </div> -->
                        <input type="hidden" id="tds_amount" name="tds_amount" value="0">
                        <div class="col-md-4">
                            <label for="gst_amount" class="form-label">GST Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="gst_amount" name="gst_amount" value="{{ $invoiceData['gst_amount'] }}" step="0.01" min="0" required>
                            <small class="text-muted">GST/IGST amount</small>
                        </div>
                        <div class="col-md-4">
                            <label for="total_amount" class="form-label">Total Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="total_amount" name="total_amount" value="{{ $invoiceData['final_total_amount'] }}" step="0.01" min="0" required>
                            <small class="text-muted">Base + GST - TDS + Carry Forward</small>
                        </div>
                    </div>

                    @if($invoiceData['has_carry_forward'])
                    <div class="alert alert-info mb-4">
                        <strong>Carry Forward Amount:</strong> ₹{{ number_format($invoiceData['carry_forward_amount'], 2) }}
                        <input type="hidden" name="carry_forward_amount" value="{{ $invoiceData['carry_forward_amount'] }}">
                        <input type="hidden" name="has_carry_forward" value="1">
                    </div>
                    @endif

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('admin.applications.show', $application->id) }}" class="btn btn-danger">Cancel</a>
                        <button type="submit" class="btn theme-bg-blue text-white">Generate Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let lineItemIndex = {{ count($invoiceData['segments']) }};

    // Add line item
    document.getElementById('addLineItem').addEventListener('click', function() {
        const container = document.getElementById('lineItemsContainer');
        const billingStartDate = document.getElementById('billing_start_date').value;
        const billingEndDate = document.getElementById('billing_end_date').value;
        
        // Calculate days between dates
        let daysText = '';
        if (billingStartDate && billingEndDate) {
            const start = new Date(billingStartDate);
            const end = new Date(billingEndDate);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            daysText = ` (${diffDays} days)`;
        }
        
        const periodText = (billingStartDate && billingEndDate) ? 
            `Period: ${billingStartDate} to ${billingEndDate}${daysText}` : '';
        
        const newItem = document.createElement('div');
        newItem.className = 'line-item-row mb-3 p-3 border rounded';
        newItem.innerHTML = `
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Description <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="line_items[${lineItemIndex}][description]" required>
                    <input type="hidden" name="line_items[${lineItemIndex}][show_period]" value="0">
                    <div class="form-check mt-2">
                        <input class="form-check-input show-period-checkbox" type="checkbox" name="line_items[${lineItemIndex}][show_period]" id="show_period_${lineItemIndex}" value="1" checked>
                        <label class="form-check-label small" for="show_period_${lineItemIndex}">
                            Show Period
                        </label>
                    </div>
                    ${periodText ? '<small class="text-muted period-display d-block" style="display: block;">' + periodText + '</small>' : ''}
                </div>
                <div class="col-md-2">
                    <label class="form-label">Quantity</label>
                    <input type="number" class="form-control quantity-input" name="line_items[${lineItemIndex}][quantity]" value="1" step="0.01" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Rate (₹)</label>
                    <input type="number" class="form-control rate-input" name="line_items[${lineItemIndex}][rate]" value="0" step="0.01" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Amount (₹)</label>
                    <input type="number" class="form-control amount-input" name="line_items[${lineItemIndex}][amount]" value="0" step="0.01" min="0">
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-danger mt-2 remove-line-item">
                <i class="bi bi-trash"></i> Remove Line Item
            </button>
        `;
        container.appendChild(newItem);
        lineItemIndex++;
        attachLineItemListeners(newItem);
    });

    // Remove line item
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-line-item')) {
            if (confirm('Are you sure you want to remove this line item?')) {
                e.target.closest('.line-item-row').remove();
            }
        }
    });

    // Calculate amount from quantity * rate
    function attachLineItemListeners(itemRow) {
        const quantityInput = itemRow.querySelector('.quantity-input');
        const rateInput = itemRow.querySelector('.rate-input');
        const amountInput = itemRow.querySelector('.amount-input');

        function calculateAmount() {
            const quantity = parseFloat(quantityInput.value) || 0;
            const rate = parseFloat(rateInput.value) || 0;
            amountInput.value = (quantity * rate).toFixed(2);
            updateTotal();
        }

        if (quantityInput) quantityInput.addEventListener('input', calculateAmount);
        if (rateInput) rateInput.addEventListener('input', calculateAmount);
    }

    // Attach listeners to existing line items
    document.querySelectorAll('.line-item-row').forEach(item => {
        attachLineItemListeners(item);
    });
    
    // Handle show/hide period checkbox for all line items
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('show-period-checkbox')) {
            const row = e.target.closest('.line-item-row');
            const periodDisplay = row.querySelector('.period-display');
            if (periodDisplay) {
                periodDisplay.style.display = e.target.checked ? 'block' : 'none';
            }
        }
    });

    // Auto-calculate total amount
    function updateTotal() {
        let total = 0;
        document.querySelectorAll('.amount-input').forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        document.getElementById('amount').value = total.toFixed(2);
        const gst = parseFloat(document.getElementById('gst_amount').value) || 0;
        const tds = parseFloat(document.getElementById('tds_amount').value) || 0;
        // Total = Base Amount + GST Amount - TDS Amount
        const finalTotal = total + gst - tds;
        document.getElementById('total_amount').value = Math.max(0, finalTotal).toFixed(2);
    }

    const amountInput = document.getElementById('amount');
    const gstInput = document.getElementById('gst_amount');
    const totalInput = document.getElementById('total_amount');
    const tdsAmountInput = document.getElementById('tds_amount');

    // Calculate total amount (TDS is not editable on generate invoice; submitted as 0)
    // Total = Base Amount + GST Amount - TDS Amount + Carry Forward (if any)
    function calculateTotal() {
        const amount = parseFloat(amountInput.value) || 0;
        const gst = parseFloat(gstInput.value) || 0;
        const tds = parseFloat(tdsAmountInput && tdsAmountInput.value ? tdsAmountInput.value : 0) || 0;
        const carryForwardInput = document.querySelector('input[name="carry_forward_amount"]');
        const carryForward = carryForwardInput ? parseFloat(carryForwardInput.value) || 0 : 0;
        const total = amount + gst - tds + carryForward;
        totalInput.value = Math.max(0, total).toFixed(2);
    }

    if (amountInput) {
        amountInput.addEventListener('input', calculateTotal);
    }
    if (gstInput) {
        gstInput.addEventListener('input', calculateTotal);
    }

    // Update total when line item amounts change
    document.querySelectorAll('.amount-input').forEach(input => {
        input.addEventListener('input', updateTotal);
    });
    
    // Update Period display when billing dates change
    function updateAllLineItemPeriods() {
        const billingStartDate = document.getElementById('billing_start_date').value;
        const billingEndDate = document.getElementById('billing_end_date').value;
        
        let daysText = '';
        if (billingStartDate && billingEndDate) {
            const start = new Date(billingStartDate);
            const end = new Date(billingEndDate);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            daysText = ` (${diffDays} days)`;
        }
        
        const periodText = (billingStartDate && billingEndDate) ? 
            `Period: ${billingStartDate} to ${billingEndDate}${daysText}` : '';
        
        // Update all line items - add or update period text
        document.querySelectorAll('.line-item-row').forEach(row => {
            const descriptionDiv = row.querySelector('.col-md-4');
            if (descriptionDiv) {
                const checkbox = row.querySelector('.show-period-checkbox');
                const isChecked = checkbox ? checkbox.checked : true;
                let existingPeriod = descriptionDiv.querySelector('small.period-display');
                
                if (periodText) {
                    if (existingPeriod) {
                        // Update existing period text
                        existingPeriod.textContent = periodText;
                        // Show/hide based on checkbox
                        existingPeriod.style.display = isChecked ? 'block' : 'none';
                    } else {
                        // Add new period after checkbox
                        const periodElement = document.createElement('small');
                        periodElement.className = 'text-muted period-display d-block';
                        periodElement.textContent = periodText;
                        periodElement.style.display = isChecked ? 'block' : 'none';
                        
                        // Insert after checkbox (which is inside form-check div)
                        const checkboxContainer = descriptionDiv.querySelector('.form-check');
                        if (checkboxContainer) {
                            checkboxContainer.insertAdjacentElement('afterend', periodElement);
                        } else {
                            descriptionDiv.appendChild(periodElement);
                        }
                    }
                } else if (existingPeriod) {
                    // Remove period if dates are cleared
                    existingPeriod.remove();
                }
            }
        });
    }
    
    // Listen for billing date changes
    const billingStartInput = document.getElementById('billing_start_date');
    const billingEndInput = document.getElementById('billing_end_date');
    if (billingStartInput) billingStartInput.addEventListener('change', updateAllLineItemPeriods);
    if (billingEndInput) billingEndInput.addEventListener('change', updateAllLineItemPeriods);
});
</script>
@endif
@endsection

