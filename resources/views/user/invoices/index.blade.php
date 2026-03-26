@extends('user.layout')

@section('title', 'My Invoices')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="mb-1" style="color:#2c3e50;font-weight:600;">My Invoices</h2>
        <p class="text-muted mb-0">View and download all your invoices.</p>
    </div>

    <!-- Invoice Summary (Minimal) -->
    <div class="card  shadow-sm mb-4" style="border-radius: 16px;">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
            <h5 class="mb-0" style="font-weight: 600;">INVOICE SUMMARY</h5>
        </div>
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="d-flex align-items-center p-2 bg-light rounded cursor-pointer" 
                         onclick="filterInvoices('all')" 
                         style="cursor: pointer; transition: background-color 0.2s;"
                         onmouseover="this.style.backgroundColor='#e9ecef'" 
                         onmouseout="this.style.backgroundColor='#f8f9fa'">
                        <div class="bg-info bg-opacity-10 rounded-circle p-2 me-2" style="min-width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#ffffff" viewBox="0 0 16 16">
                                <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                            </svg>
                        </div>
                        <div class="flex-grow-1">
                            <small class="text-muted d-block" style="font-size: 0.75rem;">Total Invoices</small>
                            <strong class="filter-number" data-filter="all" style="color: #2c3e50; font-size: 1.25rem; font-weight: 700; cursor: pointer;">{{ $totalInvoices }}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center p-2 bg-light rounded cursor-pointer" 
                         onclick="filterInvoices('pending')" 
                         style="cursor: pointer; transition: background-color 0.2s;"
                         onmouseover="this.style.backgroundColor='#e9ecef'" 
                         onmouseout="this.style.backgroundColor='#f8f9fa'">
                        <div class="bg-warning bg-opacity-10 rounded-circle p-2 me-2" style="min-width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#2B2F6C" viewBox="0 0 16 16">
                                <path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022l-.074.997zm2.004.45a7.003 7.003 0 0 0-.985-.299l.219-.976c.383.086.76.2 1.126.342l-.36.933zm1.37.71a7.01 7.01 0 0 0-.439-.27l.493-.87a8.025 8.025 0 0 1 .979.654l-.615.789a6.996 6.996 0 0 0-.418-.302zm1.834 1.79a6.99 6.99 0 0 0-.653-.796l.724-.69c.27.285.52.59.747.91l-.818.576zm.744 1.352a7.08 7.08 0 0 0-.214-.468l.893-.45a7.976 7.976 0 0 1 .45 1.088l-.95.313a7.023 7.023 0 0 0-.179-.483zm.53 2.507a6.991 6.991 0 0 0-.1-1.025l.985-.17c.067.386.106.778.116 1.175l-.99-.13zm-.131 1.538c.033-.17.06-.339.081-.51l.993.123a7.957 7.957 0 0 1-.23 1.155l-.964-.267c.046-.165.086-.332.12-.501zm-.952 2.379c.184-.29.346-.594.486-.908l.914.405c-.236.36-.504.696-.796 1.007l-.844-.497zm-.964 1.205c.122-.122.239-.248.35-.378l.758.653a8.073 8.073 0 0 1-.401.432l-.707-.707z"/>
                                <path d="M8 1a7 7 0 1 0 4.95 11.95l.707.707A8.001 8.001 0 1 1 8 0v1z"/>
                                <path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5z"/>
                            </svg>
                        </div>
                        <div class="flex-grow-1">
                            <small class="text-muted d-block" style="font-size: 0.75rem;">Pending</small>
                            <strong class="filter-number" data-filter="pending" style="color: #2c3e50; font-size: 1.25rem; font-weight: 700; cursor: pointer;">{{ $pendingInvoices }}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center p-2 bg-light rounded cursor-pointer" 
                         onclick="filterInvoices('paid')" 
                         style="cursor: pointer; transition: background-color 0.2s;"
                         onmouseover="this.style.backgroundColor='#e9ecef'" 
                         onmouseout="this.style.backgroundColor='#f8f9fa'">
                        <div class="bg-success bg-opacity-10 rounded-circle p-2 me-2" style="min-width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#ffffff" viewBox="0 0 16 16">
                                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 4.384 6.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                            </svg>
                        </div>
                        <div class="flex-grow-1">
                            <small class="text-muted d-block" style="font-size: 0.75rem;">Paid</small>
                            <strong class="filter-number" data-filter="paid" style="color: #2c3e50; font-size: 1.25rem; font-weight: 700; cursor: pointer;">{{ $paidInvoices }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="card border-c-blue shadow-sm mb-4" style="border-radius: 16px;" id="invoiceFilterSection">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('user.invoices.index') }}" id="invoiceFilterForm">
                <input type="hidden" name="filter" id="filterInput" value="{{ $filterType }}">
                <div class="row g-2 align-items-end theme-forms">
                    <div class="col-md-4">
                        <label for="search" class="form-label small mb-1">Search by Invoice Number or Period</label>
                        <input type="text" 
                               name="search" 
                               id="searchInput"
                               class="form-control form-control-sm" 
                               placeholder="Invoice number or billing period..."
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('user.invoices.index') }}" class="btn btn-danger w-100">Clear</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
        <div class="card-body p-0">
            @if($invoices->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light text-nowrap">
                            <tr>
                                <th style="color: #2c3e50; font-weight: 600;">Invoice Number</th>
                                <th style="color: #2c3e50; font-weight: 600;">Application ID</th>
                                <th style="color: #2c3e50; font-weight: 600;">Invoice Date</th>
                                <th style="color: #2c3e50; font-weight: 600;">Due Date</th>
                                <th style="color: #2c3e50; font-weight: 600;">Billing Period</th>
                                <th style="color: #2c3e50; font-weight: 600;">TDS Amount</th>
                                <th style="color: #2c3e50; font-weight: 600;">Amount</th>
                                <th style="color: #2c3e50; font-weight: 600;">Status</th>
                                <th class="text-end pe-3" style="color: #2c3e50; font-weight: 600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                            <tr class="align-middle">
                                <td><strong>{{ $invoice->invoice_number }}</strong></td>
                                <td>
                                    <a href="{{ route('user.applications.show', $invoice->application_id) }}" style="color: #0d6efd; text-decoration: none;">
                                        {{ $invoice->application->application_id }}
                                    </a>
                                </td>
                                <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
                                <td>
                                    {{ $invoice->due_date->format('d M Y') }}
                                    @if($invoice->due_date->isPast() && $invoice->status === 'pending')
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    @endif
                                </td>
                                <td>
                                    @if($invoice->billing_period)
                                        {{ $invoice->billing_period }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                        @if($invoice->tds_amount > 0)
                                            <span class="">₹{{ number_format($invoice->tds_amount, 2) }}</span>
                                        @elseif($invoice->tds_amount < 0)
                                            <span class="text-danger">₹{{ number_format($invoice->tds_amount, 2) }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                <td>
                                    @php
                                        // Check if invoice has carry forward FROM it (forwarded to another invoice)
                                        $hasForwardedFrom = $invoice->has_carry_forward && $invoice->forwarded_amount > 0;
                                        $forwardedAmount = $invoice->forwarded_amount ?? 0;
                                        
                                        // Check if invoice has carry forward TO it (added from previous invoices)
                                        $hasForwardedTo = false;
                                        $forwardedToAmount = 0;
                                        if ($invoice->line_items && is_array($invoice->line_items)) {
                                            foreach ($invoice->line_items as $item) {
                                                if (isset($item['is_carry_forward']) && $item['is_carry_forward']) {
                                                    $hasForwardedTo = true;
                                                    $forwardedToAmount += (float) ($item['amount'] ?? 0);
                                                }
                                            }
                                        }
                                        
                                        $currentPeriodAmount = $invoice->total_amount - $forwardedToAmount;
                                        
                                        // Calculate actual amount paid by user (Total - Forwarded Amount)
                                        $actualPaidAmount = $invoice->total_amount - $forwardedAmount;
                                    @endphp

                                    
                                    <div class="invoice-amount-breakdown">
                                        <strong>₹{{ number_format($invoice->total_amount, 2) }}</strong>
                                        
                                        @if($hasForwardedTo && $forwardedToAmount > 0)
                                            <div class="mt-1 p-2 bg-info bg-opacity-10 rounded small">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-white">Current Period:</span>
                                                    <strong>₹{{ number_format($currentPeriodAmount, 2) }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-white">Added Forwarded Amount:</span>
                                                    <strong class="text-primary">₹{{ number_format($forwardedToAmount, 2) }}</strong>
                                                </div>
                                                <small class="text-white d-block mt-1" style="font-size: 0.7rem;">
                                                    <em>(Amount forwarded from previous invoice(s) - GST included)</em>
                                                </small>
                                            </div>
                                        @endif
                                        
                                        @if($invoice->payment_status === 'partial')
                                            <div class="mt-1 p-2 bg-warning bg-opacity-10 rounded small">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-muted">Amount Paid:</span>
                                                    <strong class="text-success">₹{{ number_format($invoice->paid_amount, 2) }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-muted">Balance:</span>
                                                    <strong class="text-warning">₹{{ number_format($invoice->balance_amount, 2) }}</strong>
                                                </div>
                                            </div>
                                        @endif
                                        
                                        @if($hasForwardedFrom && $forwardedAmount > 0)
                                            <div class="mt-1 p-2 bg-warning bg-opacity-10 rounded small" style="border-color: var(--border-c-yellow) !important;">
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-blue">Carry Forward Amount:</span>
                                                    <strong class="text-blue">₹{{ number_format($forwardedAmount, 2) }}</strong>
                                                </div>
                                                @if($invoice->forwarded_to_invoice_date)
                                                    <small class="text-blue d-block mt-1" style="font-size: 0.7rem;">
                                                        Forwarded on {{ is_string($invoice->forwarded_to_invoice_date) ? \Carbon\Carbon::parse($invoice->forwarded_to_invoice_date)->format('d M Y') : $invoice->forwarded_to_invoice_date->format('d M Y') }}
                                                    </small>
                                                @endif
                                            </div>
                                        @endif
                                        
                                        @if($invoice->payment_status === 'paid' || ($invoice->paid_amount > 0 && $invoice->payment_status !== 'partial'))
                                            <div class="mt-1 p-2 bg-success bg-opacity-10 rounded small">
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-white">Amount Paid:</span>
                                                    <strong class="text-white">₹{{ number_format($actualPaidAmount, 2) }}</strong>
                                                </div>
                                            </div>
                                        @endif
                                        
                                        @if($invoice->gst_amount > 0 && !$hasForwardedTo)
                                            <br><small class="text-muted">(Base: ₹{{ number_format($invoice->amount, 2) }} + GST: ₹{{ number_format($invoice->gst_amount, 2) }})</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($invoice->payment_status === 'partial')
                                        <span class="badge bg-warning">Partial</span>
                                        <br><small class="text-muted">Paid: ₹{{ number_format($invoice->paid_amount ?? 0, 2) }}</small>
                                        <br><small class="text-muted">Balance: ₹{{ number_format($invoice->balance_amount, 2) }}</small>
                                        @if($hasForwardedFrom && $forwardedAmount > 0)
                                            <br><small class="text-warning mt-1 d-block">Carry Forward: ₹{{ number_format($forwardedAmount, 2) }}</small>
                                        @endif
                                    @elseif($invoice->status === 'paid')
                                        <span class="badge bg-success mb-1">Paid</span>
                                        @php
                                            $actualPaidDisplay = $invoice->total_amount - $forwardedAmount;
                                        @endphp
                                        <br><small class="text-success">Paid: ₹{{ number_format($actualPaidDisplay, 2) }}</small>
                                        @if($invoice->paid_at)
                                            <br><small class="text-muted">{{ is_string($invoice->paid_at) ? \Carbon\Carbon::parse($invoice->paid_at)->format('d M Y') : $invoice->paid_at->format('d M Y') }}</small>
                                        @endif
                                        @if($hasForwardedFrom && $forwardedAmount > 0)
                                            <br><small class="text-blue mt-1 d-block">Carry Forward: ₹{{ number_format($forwardedAmount, 2) }}</small>
                                        @endif
                                    @elseif($invoice->status === 'overdue')
                                        <span class="badge bg-danger">Overdue</span>
                                        @if($invoice->paid_amount > 0)
                                            <br><small class="text-muted">Paid: ₹{{ number_format($invoice->paid_amount, 2) }}</small>
                                        @endif
                                    @elseif($invoice->hasCreditNote())
                                        <span class="badge bg-info">Credit Note</span>
                                        <br><small class="text-muted">Download credit note PDF</small>
                                    @elseif($invoice->status === 'cancelled')
                                        <span class="badge bg-secondary">Cancelled</span>
                                        @if($invoice->paid_amount > 0)
                                            <br><small class="text-muted">Paid: ₹{{ number_format($invoice->paid_amount, 2) }}</small>
                                        @endif
                                    @else
                                        <span class="badge theme-bg-yellow text-blue fw-normal border border-c-blue mb-2 text-capitalize">Pending</span>
                                        @if($invoice->paid_amount > 0)
                                            <br><small class="text-muted">Paid: ₹{{ number_format($invoice->paid_amount, 2) }}</small>
                                        @endif
                                    @endif
                                    
                                    @if($hasForwardedFrom && $forwardedAmount > 0)
                                        <br><span class="badge theme-bg-yellow text-blue fw-normal border border-c-blue mt-1 d-inline text-capitalize">Has Carry Forward</span>
                                    @endif
                                    @if($hasForwardedTo && $forwardedToAmount > 0)
                                        <br><span class="badge bg-info d-inline text-capitalize border border-c-blue">Has Added Forwarded Amount</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap justify-content-end gap-2">
                                        @if(!$invoice->isCancelledOrHasCreditNote() && (in_array($invoice->status, ['pending', 'overdue']) || $invoice->payment_status === 'partial'))
                                            @php
                                                $invoiceAmount = $invoice->balance_amount ?? $invoice->total_amount;
                                                $canPayWithWallet = $wallet && $wallet->status === 'active' && $walletBalance >= $invoiceAmount;
                                            @endphp
                                            @if($canPayWithWallet)
                                                <form action="{{ route('user.payments.pay-with-wallet', $invoice->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Pay ₹{{ number_format($invoiceAmount, 2) }} from advance amount?')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                            <path d="M0 3a2 2 0 0 1 2-2h13.5a.5.5 0 0 1 0 1H15v2a1 1 0 0 1 1 1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1h-1a1 1 0 0 1-1-1V6h-2v1.5a.5.5 0 0 1-1 0V6H1V4.5a.5.5 0 0 1 1 0V3zm1 1.5V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V4.5A1.5 1.5 0 0 0 12.5 3h-9A1.5 1.5 0 0 0 1 4.5z"/>
                                                        </svg>
                                                        Pay with Advance
                                                    </button>
                                                </form>
                                            @endif
                                            <form action="{{ route('user.payments.pay-now', $invoice->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                        <path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm.5-1.037a4.5 4.5 0 0 1-1.013-8.986A4.5 4.5 0 0 1 8.5 10.963z"/>
                                                        <path d="M5.232 4.616a.5.5 0 0 1 .106.7L1.907 8l3.43 2.684a.5.5 0 1 1-.768.64L1.907 9l-3.43-2.684a.5.5 0 0 1 .768-.64zm10.536 0a.5.5 0 0 0-.106.7L14.093 8l-3.43 2.684a.5.5 0 1 0 .768.64L14.093 9l3.43-2.684a.5.5 0 0 0-.768-.64z"/>
                                                    </svg>
                                                    Pay Now
                                                </button>
                                            </form>
                                        @endif
                                        @if(!$invoice->isCancelledOrHasCreditNote() && $invoice->payment_status === 'pending')
                                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#updateTdsAmountModal{{ $invoice->id }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                    <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793l.646.647a.5.5 0 0 0 .708 0l1.5-1.5a.5.5 0 0 0 0-.708l-1.5-1.5zm.646 6.708-3 3a.5.5 0 0 1-.708 0L7.5 7.207l-.646.647a.5.5 0 0 1-.708 0l-3-3a.5.5 0 1 1 .708-.708L7 7.293l2.146-2.147a.5.5 0 0 1 .708 0l2.5 2.5a.5.5 0 0 1 0 .708z"/>
                                                </svg>
                                                Update TDS Amount
                                            </button>
                                        @endif
                                        @if(!$invoice->tds_certificate_path)
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#uploadTdsCertificateModal{{ $invoice->id }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                                                </svg>
                                                Upload TDS Certificate
                                            </button>
                                        @else
                                            <a href="{{ route('user.invoices.view-tds-certificate', $invoice->id) }}" class="btn btn-sm btn-info" target="_blank">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                                                    <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1H2z"/>
                                                </svg>
                                                View Certificate
                                            </a>
                                        @endif
                                        @if($invoice->hasCreditNote())
                                            <a href="{{ route('user.invoices.download', ['id' => $invoice->id, 'type' => 'credit_note']) }}" class="btn btn-sm btn-info">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                                                </svg>
                                                Download Credit Note
                                            </a>
                                            <a href="{{ route('user.invoices.download', ['id' => $invoice->id, 'type' => 'invoice']) }}" class="btn btn-sm btn-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                                                </svg>
                                                Download Invoice
                                            </a>
                                        @else
                                            <a href="{{ route('user.invoices.download', $invoice->id) }}" class="btn btn-sm btn-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                                                </svg>
                                                Download
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                {{-- Update TDS Amount Modal --}}
                @foreach($invoices as $invoice)
                    @if($invoice->payment_status === 'pending')
                    <div class="modal fade" id="updateTdsAmountModal{{ $invoice->id }}" tabindex="-1" data-base-amount="{{ $invoice->amount }}">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" style="color:white !important;">Update TDS Amount</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('user.invoices.update-tds-amount', $invoice->id) }}" id="updateTdsAmountForm{{ $invoice->id }}">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="alert alert-info">
                                            <strong>Note:</strong> You can only update TDS amount when payment status is pending.
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">TDS Amount (₹) <span class="text-danger">*</span></label>
                                            <input type="number" name="tds_amount" id="tds_amount_{{ $invoice->id }}" class="form-control" step="0.01" min="0" value="{{ $invoice->tds_amount > 0 ? $invoice->tds_amount : 0 }}" required placeholder="0.00">
                                            <small class="text-muted">TDS Amount (max 10% of base amount: ₹{{ number_format(($invoice->amount * 10) / 100, 2) }})</small>
                                            <div class="invalid-feedback" id="tds_amount_error_{{ $invoice->id }}" style="display: none;"></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-warning">Update TDS Amount</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif
                @endforeach

                {{-- Upload TDS Certificate Modal --}}
                @foreach($invoices as $invoice)
                    @if(!$invoice->tds_certificate_path)
                    <div class="modal fade" id="uploadTdsCertificateModal{{ $invoice->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Upload TDS Certificate</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('user.invoices.upload-tds-certificate', $invoice->id) }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Upload TDS Certificate <span class="text-danger">*</span></label>
                                            <input type="file" name="tds_certificate" class="form-control" accept="application/pdf,.pdf" required>
                                            <small class="text-muted">Upload the TDS certificate (PDF only, max 10MB)</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-info">Upload Certificate</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif
                @endforeach
                <div class="card-footer">
                    <div class="d-flex justify-content-center">
                        {{ $invoices->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#6c757d" viewBox="0 0 16 16" class="mb-3">
                        <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                    </svg>
                    <p class="text-muted mb-0">No invoices found.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function filterInvoices(filterType) {
    // Update filter input
    document.getElementById('filterInput').value = filterType;
    
    // Highlight active filter
    document.querySelectorAll('.filter-number').forEach(function(el) {
        if (el.getAttribute('data-filter') === filterType) {
            el.style.color = '#0d6efd';
        } else {
            el.style.color = '#2c3e50';
        }
    });
    
    // Submit form to filter
    document.getElementById('invoiceFilterForm').submit();
}

// Highlight active filter on page load
document.addEventListener('DOMContentLoaded', function() {
    const filterType = '{{ $filterType }}';
    
    // Highlight active filter
    document.querySelectorAll('.filter-number').forEach(function(el) {
        if (el.getAttribute('data-filter') === filterType) {
            el.style.color = '#0d6efd';
        } else {
            el.style.color = '#2c3e50';
        }
    });
    
    // TDS Amount validation for update modals
    document.querySelectorAll('[id^="updateTdsAmountModal"]').forEach(modal => {
        const form = modal.querySelector('form');
        if (form) {
            const invoiceId = modal.id.replace('updateTdsAmountModal', '');
            const tdsAmountInput = form.querySelector('input[name="tds_amount"]');
            const tdsAmountError = form.querySelector('#tds_amount_error_' + invoiceId);
            const baseAmount = parseFloat(modal.getAttribute('data-base-amount')) || 0;

            if (tdsAmountInput) {
                function validateTdsAmount() {
                    const tdsAmount = parseFloat(tdsAmountInput.value) || 0;
                    const maxTdsAmount = (baseAmount * 10) / 100;

                    tdsAmountInput.setAttribute('max', maxTdsAmount.toFixed(2));

                    if (tdsAmount < 0) {
                        tdsAmountInput.classList.add('is-invalid');
                        if (tdsAmountError) {
                            tdsAmountError.textContent = 'TDS amount cannot be less than 0';
                            tdsAmountError.style.display = 'block';
                        }
                        return false;
                    } else if (tdsAmount > maxTdsAmount) {
                        tdsAmountInput.classList.add('is-invalid');
                        if (tdsAmountError) {
                            tdsAmountError.textContent = 'TDS amount cannot exceed 10% of base amount (₹' + maxTdsAmount.toFixed(2) + ')';
                            tdsAmountError.style.display = 'block';
                        }
                        return false;
                    } else {
                        tdsAmountInput.classList.remove('is-invalid');
                        if (tdsAmountError) {
                            tdsAmountError.style.display = 'none';
                        }
                        return true;
                    }
                }

                tdsAmountInput.addEventListener('input', validateTdsAmount);
                tdsAmountInput.addEventListener('blur', validateTdsAmount);

                form.addEventListener('submit', function(e) {
                    if (!validateTdsAmount()) {
                        e.preventDefault();
                        e.stopPropagation();
                        tdsAmountInput.focus();
                        return false;
                    }

                    // Show confirmation
                    if (!confirm('Are you sure you want to update TDS amount?')) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
        }
    });
});
</script>
@endpush
@endsection
