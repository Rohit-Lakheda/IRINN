@extends('user.layout')

@section('title', 'Pending Payments')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="mb-2">Pending Payments</h2>
        <p class="mb-0">Pay your outstanding invoices</p>
        <div class="accent-line"></div>
    </div>

    @php
        $wallet = $user->wallet;
        $walletBalance = $wallet ? (float) $wallet->balance : 0;
    @endphp

    <!-- Outstanding Amount Summary -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px; border-left: 4px solid #dc3545;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="mb-2">
                            <h6 class="text-muted mb-2" style="font-size: 0.875rem; font-weight: 500;">Total Outstanding Amount</h6>
                            <h2 class="mb-0" style="color: #dc3545; font-weight: 700;">₹{{ number_format($outstandingAmount, 2) }}</h2>
                            <p class="text-muted small mb-0 mt-1">{{ $pendingInvoices->count() }} {{ $pendingInvoices->count() == 1 ? 'invoice' : 'invoices' }} pending payment</p>
                        </div>
                        @if($pendingInvoices->count() > 0)
                        <div class="d-flex gap-2 flex-wrap">
                            @php
                                $canPayAllWithWallet = $wallet && $wallet->status === 'active' && $walletBalance >= $outstandingAmount;
                            @endphp
                            @if($canPayAllWithWallet)
                                <form action="{{ route('user.payments.pay-all-with-wallet') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Pay ₹{{ number_format($outstandingAmount, 2) }} from advance amount for all {{ $pendingInvoices->count() }} invoice(s)?')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                            <path d="M0 3a2 2 0 0 1 2-2h13.5a.5.5 0 0 1 0 1H15v2a1 1 0 0 1 1 1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1h-1a1 1 0 0 1-1-1V6h-2v1.5a.5.5 0 0 1-1 0V6H1V4.5a.5.5 0 0 1 1 0V3zm1 1.5V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V4.5A1.5 1.5 0 0 0 12.5 3h-9A1.5 1.5 0 0 0 1 4.5z"/>
                                        </svg>
                                        Pay All with Advance (₹{{ number_format($outstandingAmount, 2) }})
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('user.payments.pay-all') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                        <path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm.5-1.037a4.5 4.5 0 0 1-1.013-8.986A4.5 4.5 0 0 1 8.5 10.963z"/>
                                        <path d="M5.232 4.616a.5.5 0 0 1 .106.7L1.907 8l3.43 2.684a.5.5 0 1 1-.768.64L1.907 9l-3.43-2.684a.5.5 0 0 1 .768-.64zm10.536 0a.5.5 0 0 0-.106.7L14.093 8l-3.43 2.684a.5.5 0 1 0 .768.64L14.093 9l3.43-2.684a.5.5 0 0 0-.768-.64z"/>
                                    </svg>
                                    Pay All with PayU (₹{{ number_format($outstandingAmount, 2) }})
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-c-green shadow-sm" style="border-radius: 16px; border-left: 4px solid #28a745;">
                <div class="card-body p-4">
                    <h6 class="text-muted mb-2" style="font-size: 0.875rem; font-weight: 500;">Advance Amount</h6>
                    @if($wallet)
                        <h2 class="mb-0" style="color: #28a745; font-weight: 700;">₹{{ number_format($walletBalance, 2) }}</h2>
                        <p class="text-muted small mb-2 mt-1">Available for payments</p>
                        @if($walletBalance < $outstandingAmount)
                            <a href="{{ route('user.wallet.add-money') }}" class="btn btn-success btn-sm w-100">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                                </svg>
                                Top-up
                            </a>
                        @endif
                    @else
                        <h2 class="mb-0" style="color: #6c757d; font-weight: 700;">₹0.00</h2>
                        <p class="text-muted small mb-2 mt-1">No advance amount</p>
                        <a href="{{ route('user.wallet.create') }}" class="btn btn-success btn-sm w-100">
                            Create Wallet
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Invoices List -->
    @if($pendingInvoices->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Pending Invoices</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light text-nowrap">
                                <tr>
                                    <th style="padding: 1rem;">Invoice Number</th>
                                    <th style="padding: 1rem;">Application ID</th>
                                    <th style="padding: 1rem;">Invoice Date</th>
                                    <th style="padding: 1rem;">Due Date</th>
                                    <th style="padding: 1rem;">Billing Period</th>
                                    <th style="padding: 1rem;">Amount</th>
                                    <th style="padding: 1rem;" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingInvoices as $invoice)
                                <tr class="align-middle">
                                    <td style="padding: 1rem;">
                                        <strong>{{ $invoice->invoice_number }}</strong>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <a href="{{ route('user.applications.show', $invoice->application_id) }}" style="color: #0d6efd; text-decoration: none;">
                                            {{ $invoice->application->application_id }}
                                        </a>
                                    </td>
                                    <td style="padding: 1rem;">{{ $invoice->invoice_date->format('d M Y') }}</td>
                                    <td style="padding: 1rem;">
                                        {{ $invoice->due_date->format('d M Y') }}
                                        @if($invoice->due_date->isPast())
                                            <span class="badge bg-danger ms-1">Overdue</span>
                                        @endif
                                    </td>
                                    <td style="padding: 1rem;">
                                        @if($invoice->billing_period)
                                            {{ $invoice->billing_period }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td style="padding: 1rem;">
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
                                        @endphp
                                        
                                        <div class="invoice-amount-breakdown">
                                            @if($invoice->payment_status === 'partial')
                                                <strong>₹{{ number_format($invoice->balance_amount ?? $invoice->total_amount, 2) }}</strong>
                                                <br><small class="text-warning">(Partial: ₹{{ number_format($invoice->paid_amount, 2) }} paid of ₹{{ number_format($invoice->total_amount, 2) }})</small>
                                            @else
                                                <strong>₹{{ number_format($invoice->total_amount, 2) }}</strong>
                                            @endif
                                            
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
                                            
                                            @if($hasForwardedFrom && $forwardedAmount > 0)
                                                <div class="mt-1 p-2 bg-warning bg-opacity-10 rounded small">
                                                    <div class="d-flex justify-content-between">
                                                        <span class="text-muted">Carry Forward Amount:</span>
                                                        <strong class="text-warning">₹{{ number_format($forwardedAmount, 2) }}</strong>
                                                    </div>
                                                    @if($invoice->forwarded_to_invoice_date)
                                                        <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                                                            Forwarded on {{ is_string($invoice->forwarded_to_invoice_date) ? \Carbon\Carbon::parse($invoice->forwarded_to_invoice_date)->format('d M Y') : $invoice->forwarded_to_invoice_date->format('d M Y') }}
                                                        </small>
                                                    @endif
                                                </div>
                                            @endif
                                            
                                            @if($invoice->gst_amount > 0 && !$hasForwardedTo)
                                                <br><small class="text-muted">(Base: ₹{{ number_format($invoice->amount, 2) }} + GST: ₹{{ number_format($invoice->gst_amount, 2) }})</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="padding: 1rem;" class="text-end">
                                        @php
                                            $invoiceAmount = $invoice->balance_amount ?? $invoice->total_amount;
                                            $canPayWithWallet = $wallet && $wallet->status === 'active' && $walletBalance >= $invoiceAmount;
                                        @endphp
                                        <div class="d-flex flex-column gap-2 align-items-end">
                                            @if($canPayWithWallet)
                                                <form action="{{ route('user.payments.pay-with-wallet', $invoice->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Pay ₹{{ number_format($invoiceAmount, 2) }} from advance amount?')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                            <path d="M0 3a2 2 0 0 1 2-2h13.5a.5.5 0 0 1 0 1H15v2a1 1 0 0 1 1 1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1h-1a1 1 0 0 1-1-1V6h-2v1.5a.5.5 0 0 1-1 0V6H1V4.5a.5.5 0 0 1 1 0V3zm1 1.5V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V4.5A1.5 1.5 0 0 0 12.5 3h-9A1.5 1.5 0 0 0 1 4.5z"/>
                                                        </svg>
                                                        Pay with Advance
                                                    </button>
                                                </form>
                                            @endif
                                            <form action="{{ route('user.payments.pay-now', $invoice->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                        <path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm.5-1.037a4.5 4.5 0 0 1-1.013-8.986A4.5 4.5 0 0 1 8.5 10.963z"/>
                                                        <path d="M5.232 4.616a.5.5 0 0 1 .106.7L1.907 8l3.43 2.684a.5.5 0 1 1-.768.64L1.907 9l-3.43-2.684a.5.5 0 0 1 .768-.64zm10.536 0a.5.5 0 0 0-.106.7L14.093 8l-3.43 2.684a.5.5 0 1 0 .768.64L14.093 9l3.43-2.684a.5.5 0 0 0-.768-.64z"/>
                                                    </svg>
                                                    Pay with PayU
                                                </button>
                                            </form>
                                            <a href="{{ route('user.invoices.download', $invoice->id) }}" class="btn btn-outline-secondary btn-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                    <path d="M.5 9.9a.5.5 0 0 1 .5.5h13a.5.5 0 0 1 0-1H1a.5.5 0 0 1-.5.5zM.5 11.9a.5.5 0 0 1 .5.5h13a.5.5 0 0 1 0-1H1a.5.5 0 0 1-.5.5z"/>
                                                    <path d="M8 0a8 8 0 1 1 0 16A8 8 0 0 1 8 0zM1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8z"/>
                                                    <path d="M7.5 5.5a.5.5 0 0 1 1 0v5.793l2.146-2.147a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 1 1 .708-.708L7.5 11.293V5.5z"/>
                                                </svg>
                                                Download
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body text-center py-5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#198754" class="mb-3" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 4.384 6.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                    <h5 class="mb-2">No Pending Payments</h5>
                    <p class="text-muted mb-0">All your invoices have been paid.</p>
                    <a href="{{ route('user.dashboard') }}" class="btn btn-primary mt-3">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
