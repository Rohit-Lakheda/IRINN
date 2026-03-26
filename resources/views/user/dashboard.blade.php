@extends('user.layout')

@section('title', 'Applicant Dashboard')

@section('content')
<div class="container-fluid py-2 px-0">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="mb-2 text-blue fw-bold" style="font-size: 1.5rem; letter-spacing: -0.02em;">Applicant Dashboard</h2>
        <p class="mb-0 text-muted">Welcome to NIXI-IRINN Portal, <strong class="text-dark">{{ $user->fullname }}</strong></p>
        <div class="accent-line"></div>
    </div>

    <!-- User Details Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-c-blue">
                <div class="card-header card-header-violet border-0">
                    <h5 class="mb-0">User Profile</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle theme-bg-blue bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="#ffffff" viewBox="0 0 16 16">
                                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="mb-1" style="color: #2c3e50; font-weight: 600;">{{ $user->fullname }}</h4>
                                    <p class="text-muted mb-0 small">{{ $user->email }}</p>
                                </div>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted small me-2">Registration ID:</span>
                                        <strong style="color: #2c3e50;">{{ $user->registrationid }}</strong>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted small me-2">Status:</span>
                                        <span class="badge 
                                            @if($user->status === 'approved' || $user->status === 'active') bg-success
                                            @elseif($user->status === 'pending') bg-warning
                                            @else bg-secondary @endif"
                                            @if($user->status === 'pending')
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top" 
                                                title="Once approved you will be able to fill application"
                                            @endif>
                                            {{ ucfirst($user->status) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted small me-2">Mobile:</span>
                                        <strong style="color: #2c3e50;">{{ $user->mobile }}</strong>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted small me-2">PAN:</span>
                                        <strong style="color: #2c3e50;">{{ $user->pancardno }}</strong>
                                        @if($user->pan_verified)
                                            <span class="badge bg-success ms-2">Verified</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted small me-2">GSTIN:</span>
                                        <strong style="color: #2c3e50;">{{ $gstin ?? 'N/A' }}</strong>
                                        @if($gstVerified)
                                            <span class="badge bg-success ms-2">Verified</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <a href="{{ route('user.profile') }}" class="btn btn-primary btn-profile">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="btn-profile-icon" viewBox="0 0 16 16">
                                    <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                    <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                                </svg>
                                <span class="btn-profile-text">View Full Profile</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Outstanding Amount Summary -->
    @if($user->status === 'approved' || $user->status === 'active')
    @if(isset($outstandingAmount) && $outstandingAmount > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px; border-left: 4px solid #dc3545;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="mb-3 mb-md-0">
                            <h6 class="text-muted mb-2" style="font-size: 0.875rem; font-weight: 500;">Outstanding Amount</h6>
                            <a href="{{ route('user.payments.pending') }}" style="text-decoration: none; color: inherit;">
                                <h2 class="mb-0" style="color: #dc3545; font-weight: 700; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">₹{{ number_format($outstandingAmount, 2) }}</h2>
                            </a>
                            <p class="text-muted small mb-0 mt-1">{{ $pendingInvoices ?? 0 }} {{ ($pendingInvoices ?? 0) == 1 ? 'invoice' : 'invoices' }} pending payment</p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('user.invoices.index', ['filter' => 'pending']) }}" class="btn btn-outline-danger">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                                </svg>
                                View Pending Payments
                            </a>
                            @if(isset($pendingInvoicesList) && $pendingInvoicesList->count() > 0)
                            @php
                                $canPayAllWithWallet = $wallet && $wallet->status === 'active' && $walletBalance >= $outstandingAmount;
                            @endphp
                            @if($canPayAllWithWallet)
                                <form action="{{ route('user.payments.pay-all-with-wallet') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Pay ₹{{ number_format($outstandingAmount, 2) }} from advance amount for all {{ $pendingInvoicesList->count() }} invoice(s)?')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                            <path d="M0 3a2 2 0 0 1 2-2h13.5a.5.5 0 0 1 0 1H15v2a1 1 0 0 1 1 1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1h-1a1 1 0 0 1-1-1V6h-2v1.5a.5.5 0 0 1-1 0V6H1V4.5a.5.5 0 0 1 1 0V3zm1 1.5V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V4.5A1.5 1.5 0 0 0 12.5 3h-9A1.5 1.5 0 0 0 1 4.5z"/>
                                        </svg>
                                        Pay All with Advance
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('user.payments.pay-all') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                        <path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm.5-1.037a4.5 4.5 0 0 1-1.013-8.986A4.5 4.5 0 0 1 8.5 10.963z"/>
                                        <path d="M5.232 4.616a.5.5 0 0 1 .106.7L1.907 8l3.43 2.684a.5.5 0 1 1-.768.64L1.907 9l-3.43-2.684a.5.5 0 0 1 .768-.64zm10.536 0a.5.5 0 0 0-.106.7L14.093 8l-3.43 2.684a.5.5 0 1 0 .768.64L14.093 9l3.43-2.684a.5.5 0 0 0-.768-.64z"/>
                                    </svg>
                                    Pay All Now
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif

    <!-- Advance Amount Section -->
    @if($user->status === 'approved' || $user->status === 'active')
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-c-green shadow-sm" style="border-radius: 16px; border-left: 4px solid #28a745;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="mb-3 mb-md-0">
                            <h6 class="text-muted mb-2" style="font-size: 0.875rem; font-weight: 500;">Advance Amount</h6>
                            @if($wallet)
                                <h2 class="mb-0" style="color: #28a745; font-weight: 700;">₹{{ number_format($walletBalance, 2) }}</h2>
                                <p class="text-muted small mb-0 mt-1">Available for invoice payments</p>
                            @else
                                <h2 class="mb-0" style="color: #6c757d; font-weight: 700;">₹0.00</h2>
                                <p class="text-muted small mb-0 mt-1">No advance amount</p>
                            @endif
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            @if($wallet)
                                <a href="{{ route('user.wallet.add-money') }}" class="btn btn-success">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                                    </svg>
                                    Top-up
                                </a>
                            @else
                                <a href="{{ route('user.wallet.create') }}" class="btn btn-success">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                                    </svg>
                                    Create Wallet
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Live Applications -->
    @if($user->status === 'approved' || $user->status === 'active')
    @if(isset($liveApplications) && $liveApplications->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-c-green shadow-sm" style="border-radius: 16px;">
                <div class="card-header theme-bg-green border-0 text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Live Applications</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        @foreach($liveApplications as $application)
                        @php
                            // Get invoices for this application
                            $appInvoices = \App\Models\Invoice::where('application_id', $application->id)
                                ->where('status', '!=', 'cancelled')
                                ->orderBy('invoice_date', 'desc')
                                ->get();
                            
                            $appPendingInvoices = $appInvoices->whereIn('payment_status', ['pending', 'partial']);
                            $appPaidInvoices = $appInvoices->where('payment_status', 'paid');
                            $latestInvoice = $appInvoices->first();
                            
                            $appOutstandingAmount = $appPendingInvoices->sum(function ($invoice) {
                                return (float)($invoice->balance_amount ?? $invoice->total_amount ?? 0);
                            });
                            
                            $appPaidAmount = $appPaidInvoices->sum(function ($invoice) {
                                $forwarded = (float)($invoice->forwarded_amount ?? 0);
                                $total = (float)($invoice->total_amount ?? 0);
                                return $total - $forwarded;
                            });
                            
                            $appData = $application->application_data ?? [];
                            $portSelection = $appData['port_selection'] ?? [];
                        @endphp
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-c-green shadow-sm h-100" style="border-radius: 16px; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.1)'">
                                <!-- Card Header -->
                                <div class="card-header theme-bg-green border-0 text-white d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                                    <div class="d-flex align-items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                            <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                        </svg>
                                        <span class="fw-bold">LIVE</span>
                                    </div>
                                    <button type="button" 
                                            class="btn btn-sm toggle-summary" 
                                            data-target="summary-{{ $application->id }}"
                                            style="border-radius: 8px; min-width: 32px; padding: 4px 8px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#ffffff" viewBox="0 0 16 16" class="toggle-icon">
                                            <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                        </svg>
                                    </button>
                                </div>
                                
                                <!-- Card Body -->
                                <div class="card-body p-3 pb-0">
                                    <div class="mb-3">
                                        <h6 class="mb-1 fw-bold" style="color: #2c3e50;">{{ $application->application_id }}</h6>
                                        <small class="text-muted">{{ $application->application_type }}</small>
                                    </div>
                                    
                                    <!-- Quick Info (Always Visible) -->
                                    <div class="mb-3">
                                        @if($application->assigned_ip)
                                        <div class="d-flex align-items-center mb-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#6c757d" class="me-2" viewBox="0 0 16 16">
                                                <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0ZM1.5 8a6.5 6.5 0 1 1 13 0 6.5 6.5 0 0 1-13 0Z"/>
                                                <path d="M8 4.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7ZM5 8a3 3 0 1 1 6 0 3 3 0 0 1-6 0Z"/>
                                            </svg>
                                            <small class="text-muted">IP:</small>
                                            <strong class="ms-1" style="color: #2c3e50;">{{ $application->assigned_ip }}</strong>
                                        </div>
                                        @endif
                                        
                                        @if($application->assigned_port_capacity)
                                        <div class="d-flex align-items-center mb-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#6c757d" class="me-2" viewBox="0 0 16 16">
                                                <path d="M8 4a.5.5 0 0 1 .5.5V6h2.5a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V7H5a.5.5 0 0 1 0-1h2.5V4.5A.5.5 0 0 1 8 4z"/>
                                                <path d="M3.794 1.343a1 1 0 0 0-.844.445L1.993 3.5H1a.5.5 0 0 0 0 1h.993l.95 1.712a1 1 0 0 0 .844.445h8.312a1 1 0 0 0 .844-.445L14.007 4.5H15a.5.5 0 0 0 0-1h-.993l-.95-1.712a1 1 0 0 0-.844-.445H3.794zM2.5 5.5l1.5 2.7v5.8a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 .5-.5V8.2l1.5-2.7H2.5z"/>
                                            </svg>
                                            <small class="text-muted">Capacity:</small>
                                            <strong class="ms-1" style="color: #2c3e50;">{{ $application->assigned_port_capacity }}</strong>
                                        </div>
                                        @endif

                                        @if($application->application_data['location']['name'])
                                        <div class="d-flex align-items-center mb-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#6c757d" class="me-2" viewBox="0 0 16 16">
                                                <path d="M8 4a.5.5 0 0 1 .5.5V6h2.5a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V7H5a.5.5 0 0 1 0-1h2.5V4.5A.5.5 0 0 1 8 4z"/>
                                                <path d="M3.794 1.343a1 1 0 0 0-.844.445L1.993 3.5H1a.5.5 0 0 0 0 1h.993l.95 1.712a1 1 0 0 0 .844.445h8.312a1 1 0 0 0 .844-.445L14.007 4.5H15a.5.5 0 0 0 0-1h-.993l-.95-1.712a1 1 0 0 0-.844-.445H3.794zM2.5 5.5l1.5 2.7v5.8a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 .5-.5V8.2l1.5-2.7H2.5z"/>
                                            </svg>
                                            <small class="text-muted">Node:</small>
                                            <strong class="ms-1" style="color: #2c3e50;">{{ $application->application_data['location']['name'] }}</strong>
                                        </div>
                                        @endif
                                        
                                        @if($appOutstandingAmount > 0)
                                        <div class="d-flex align-items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#dc3545" class="me-2" viewBox="0 0 16 16">
                                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                                <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                                            </svg>
                                            <small class="text-muted">Outstanding:</small>
                                            <strong class="ms-1 text-danger">₹{{ number_format($appOutstandingAmount, 2) }}</strong>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Collapsible Summary -->
                                    <div id="summary-{{ $application->id }}" class="application-summary" style="display: none;">
                                        <hr class="my-3">
                                        
                                        <!-- Connection Details -->
                                        <div class="mb-3">
                                            <h6 class="small fw-bold mb-2" style="color: #2c3e50;">Connection Details</h6>
                                            <div class="row g-2">
                                                @if($application->assigned_ip)
                                                <div class="col-12">
                                                    <div class="d-flex justify-content-between p-2 bg-light rounded">
                                                        <small class="text-muted">IP Address:</small>
                                                        <strong style="color: #2c3e50;">{{ $application->assigned_ip }}</strong>
                                                    </div>
                                                </div>
                                                @endif
                                                
                                                @if($application->assigned_port_capacity)
                                                <div class="col-6">
                                                    <div class="d-flex justify-content-between p-2 bg-light rounded">
                                                        <small class="text-muted">Capacity:</small>
                                                        <strong style="color: #2c3e50;">{{ $application->assigned_port_capacity }}</strong>
                                                    </div>
                                                </div>
                                                @endif
                                                
                                                @if($application->assigned_port_number)
                                                <div class="col-6">
                                                    <div class="d-flex justify-content-between p-2 bg-light rounded">
                                                        <small class="text-muted">Port No:</small>
                                                        <strong style="color: #2c3e50;">{{ $application->assigned_port_number }}</strong>
                                                    </div>
                                                </div>
                                                @endif
                                                
                                                @if($application->service_activation_date)
                                                <div class="col-12">
                                                    <div class="d-flex justify-content-between p-2 bg-light rounded">
                                                        <small class="text-muted">Live Since:</small>
                                                        <strong style="color: #2c3e50;">{{ \Carbon\Carbon::parse($application->service_activation_date)->format('d M Y') }}</strong>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <!-- Account Details -->
                                        @if($application->membership_id || $application->customer_id)
                                        <div class="mb-3">
                                            <h6 class="small fw-bold mb-2" style="color: #2c3e50;">Account Details</h6>
                                            <div class="row g-2">
                                                @if($application->membership_id)
                                                <div class="col-12">
                                                    <div class="d-flex justify-content-between p-2 bg-light rounded">
                                                        <small class="text-muted">Membership ID:</small>
                                                        <strong style="color: #2c3e50;">{{ $application->membership_id }}</strong>
                                                    </div>
                                                </div>
                                                @endif
                                                
                                                @if($application->customer_id)
                                                <div class="col-12">
                                                    <div class="d-flex justify-content-between p-2 bg-light rounded">
                                                        <small class="text-muted">Customer ID:</small>
                                                        <strong style="color: #2c3e50;">{{ $application->customer_id }}</strong>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                        @endif
                                        
                                        <!-- Payment Summary -->
                                        <div class="mb-3">
                                            <h6 class="small fw-bold mb-2" style="color: #2c3e50;">Payment Summary</h6>
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <div class="p-2 bg-warning bg-opacity-10 rounded text-center">
                                                        <small class="text-muted d-block text-blue">Outstanding</small>
                                                        <strong class="text-blue">₹{{ number_format($appOutstandingAmount, 2) }}</strong>
                                                        <br><small class="text-blue">{{ $appPendingInvoices->count() }} pending</small>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="p-2 theme-bg-green bg-opacity-10 rounded text-center">
                                                        <small class="text-muted d-block text-white">Total Paid</small>
                                                        <strong class="text-white">₹{{ number_format($appPaidAmount, 2) }}</strong>
                                                        <br><small class="text-white">{{ $appPaidInvoices->count() }} paid</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Recent Invoice -->
                                        @if($latestInvoice)
                                        <div class="mb-3">
                                            <h6 class="small fw-bold mb-2" style="color: #2c3e50;">Recent Invoice</h6>
                                            <div class="p-2 bg-light rounded">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div>
                                                        <strong style="color: #2c3e50;">{{ $latestInvoice->invoice_number }}</strong>
                                                        <br><small class="text-muted">{{ $latestInvoice->invoice_date->format('d M Y') }}</small>
                                                    </div>
                                                    <span class="badge bg-{{ $latestInvoice->payment_status === 'paid' ? 'success' : ($latestInvoice->payment_status === 'partial' ? 'warning' : 'danger') }}">
                                                        {{ strtoupper($latestInvoice->payment_status) }}
                                                    </span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <small class="text-muted">Amount:</small>
                                                    <strong>₹{{ number_format($latestInvoice->total_amount, 2) }}</strong>
                                                </div>
                                                <a href="{{ route('user.invoices.download', $latestInvoice->id) }}" 
                                                   class="btn btn-primary w-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                        <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                                        <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                                                    </svg>
                                                    Download Invoice
                                                </a>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Card Footer -->
                                <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                                    <a href="{{ route('user.applications.show', $application->id) }}" class="btn btn-primary w-100" style="border-radius: 8px;">
                                        View Full Details
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    @if($liveApplications->count() === 0)
                    <div class="text-center py-5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#6c757d" viewBox="0 0 16 16" class="mb-3">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                        </svg>
                        <p class="text-muted mb-0">No live applications found.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif
</div>

@push('scripts')
<script>
    // Ensure alert close button works
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    alert.classList.remove('show');
                    setTimeout(function() {
                        alert.remove();
                    }, 150);
                });
            }
        });

        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle application summary
    document.querySelectorAll('.toggle-summary').forEach(function(button) {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const summary = document.getElementById(targetId);
            const icon = this.querySelector('.toggle-icon');
            
            if (summary.style.display === 'none') {
                summary.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            } else {
                summary.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        });
        });
    });
</script>
@endpush
@endsection
