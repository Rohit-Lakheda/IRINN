@extends('admin.layout')

@section('title', 'Members')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1" style="color: #2c3e50; font-weight: 600;">Members</h2>
            <p class="text-muted mb-0">IRINN members are applications with a membership ID</p>
            <div class="accent-line"></div>
        </div>
    </div>

    <!-- Payment summary for billing admins -->
    @if(isset($showBillingPaymentSummary) && $showBillingPaymentSummary && $paymentStats)
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <a href="{{ route('admin.members', ['filter' => 'active', 'payment_filter' => 'generated']) }}" class="text-decoration-none">
                <div class="card border-c-blue shadow-sm {{ isset($paymentFilter) && $paymentFilter === 'generated' ? 'border-primary border-2' : '' }}" style="border-radius: 16px; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" 
                     onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                    <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                        <h6 class="mb-0 fw-semibold text-capitalize">Payments Generated</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="mb-0" style="color: #2c3e50; font-weight: 700;">{{ $paymentStats['total_generated'] }}</h3>
                                <small class="text-muted">Total Invoices</small>
                            </div>
                            <div class="theme-bg-blue bg-opacity-10 rounded-circle p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#ffffff" viewBox="0 0 16 16">
                                    <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.members', ['filter' => 'active', 'payment_filter' => 'received']) }}" class="text-decoration-none">
                <div class="card border-c-green shadow-sm {{ isset($paymentFilter) && $paymentFilter === 'received' ? 'border-success border-2' : '' }}" style="border-radius: 16px; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" 
                     onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                    <div class="card-header theme-bg-green text-white" style="border-radius: 16px 16px 0 0;">
                        <h6 class="mb-0 fw-semibold text-capitalize">Amount Received</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="mb-0" style="color: #2c3e50; font-weight: 700;">₹{{ number_format($paymentStats['total_received'], 2) }}</h3>
                                <small class="text-muted">{{ $paymentStats['paid_count'] }} Paid Invoices</small>
                            </div>
                            <div class="theme-bg-green bg-opacity-10 rounded-circle p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#ffffff" viewBox="0 0 16 16">
                                    <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.members', ['filter' => 'active', 'payment_filter' => 'pending']) }}" class="text-decoration-none">
                <div class="card border-c-gold shadow-sm {{ isset($paymentFilter) && $paymentFilter === 'pending' ? 'border-warning border-2' : '' }}" style="border-radius: 16px; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" 
                     onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                    <div class="card-header theme-bg-gold text-dark" style="border-radius: 16px 16px 0 0; border-bottom: none;">
                        <h6 class="mb-0 fw-semibold text-capitalize">Amount Pending</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="mb-0" style="color: #2c3e50; font-weight: 700;">₹{{ number_format($paymentStats['total_pending'], 2) }}</h3>
                                <small class="text-muted">{{ $paymentStats['pending_count'] }} Pending Invoices</small>
                            </div>
                            <div class="theme-bg-gold bg-opacity-10 rounded-circle p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#ffffff" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    @endif

    <!-- Current month invoice summary for billing admins -->
    @if(isset($showBillingPaymentSummary) && $showBillingPaymentSummary && isset($currentMonthStats))
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card shadow-sm" style="border-radius: 16px; border: 2px solid #6f42c1;">
                <div class="card-header text-white" style="border-radius: 16px 16px 0 0; background-color: #6f42c1;">
                    <h6 class="mb-0 fw-semibold">Current Month Invoice Summary - {{ now()->format('F Y') }}</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <div class="theme-bg-blue bg-opacity-10 rounded-circle p-3 me-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#ffffff" viewBox="0 0 16 16">
                                        <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="mb-0" style="color: #2c3e50; font-weight: 700;">{{ $currentMonthStats['generated_count'] }}</h4>
                                    <small class="text-muted">Payments Generated</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <div class="theme-bg-green bg-opacity-10 rounded-circle p-3 me-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#ffffff" viewBox="0 0 16 16">
                                        <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="mb-0" style="color: #2c3e50; font-weight: 700;">₹{{ number_format($currentMonthStats['received_amount'], 2) }}</h4>
                                    <small class="text-muted">Amount Received</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <div class="theme-bg-gold bg-opacity-10 rounded-circle p-3 me-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#ffffff" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                        <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="mb-0" style="color: #2c3e50; font-weight: 700;">₹{{ number_format($currentMonthStats['pending_amount'], 2) }}</h4>
                                    <small class="text-muted">Amount Pending</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Filter Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-pills theme-nav-pills">
                <li class="nav-item">
                    <a class="nav-link {{ $filter === 'all' && !isset($paymentFilter) ? 'active' : '' }}" href="{{ route('admin.members', ['filter' => 'all']) }}">
                        All Members
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ in_array($filter, ['active', 'live']) && !isset($paymentFilter) ? 'active' : '' }}" href="{{ route('admin.members', ['filter' => 'live']) }}">
                        Live Members
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $filter === 'suspended' && !isset($paymentFilter) ? 'active' : '' }}" href="{{ route('admin.members', ['filter' => 'suspended']) }}">
                        Suspended Members
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $filter === 'disconnected' && !isset($paymentFilter) ? 'active' : '' }}" href="{{ route('admin.members', ['filter' => 'disconnected']) }}">
                        Disconnected Members
                    </a>
                </li>
                @if(isset($paymentFilter) && $paymentFilter)
                <li class="nav-item">
                    <a class="nav-link active bg-info text-white" href="{{ route('admin.members', ['filter' => $filter, 'payment_filter' => $paymentFilter]) }}" style="color: #fff !important;">
                        @if($paymentFilter === 'generated')
                            Payments Generated
                        @elseif($paymentFilter === 'received')
                            Amount Received
                        @elseif($paymentFilter === 'pending')
                            Amount Pending
                        @endif
                        <span class="badge bg-light text-dark ms-2">Active</span>
                    </a>
                </li>
                @endif
                @if(isset($gstVerificationFilter) && $gstVerificationFilter && $gstVerificationFilter !== 'all')
                <li class="nav-item">
                    <a class="nav-link active text-white" href="{{ route('admin.members', ['filter' => $filter, 'gst_verification_filter' => $gstVerificationFilter]) }}">
                        @if($gstVerificationFilter === 'verified')
                            GST Verified
                        @elseif($gstVerificationFilter === 'unverified')
                            GST Unverified
                        @endif
                        <span class="badge bg-light text-capitalize text-dark ms-2">Active</span>
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </div>

    <!-- Search Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.members') }}" class="row g-3 theme-forms">
                        <input type="hidden" name="filter" value="{{ $filter }}">
                        @if(isset($paymentFilter) && $paymentFilter)
                            <input type="hidden" name="payment_filter" value="{{ $paymentFilter }}">
                        @endif
                        <div class="col-md-4">
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Search by name, email, registration ID, PAN..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="gst_verification_filter" class="form-select">
                                <option value="all" {{ (!isset($gstVerificationFilter) || $gstVerificationFilter === 'all') ? 'selected' : '' }}>All GST Status</option>
                                <option value="verified" {{ (isset($gstVerificationFilter) && $gstVerificationFilter === 'verified') ? 'selected' : '' }}>GST Verified</option>
                                <option value="unverified" {{ (isset($gstVerificationFilter) && $gstVerificationFilter === 'unverified') ? 'selected' : '' }}>GST Unverified</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="zone" class="form-select">
                                <option value="">All Zones</option>
                                @if(isset($zones) && $zones->count() > 0)
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone }}" {{ request('zone') == $zone ? 'selected' : '' }}>{{ $zone }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="nodal_officer" class="form-select">
                                <option value="">All Nodal Officers</option>
                                @if(isset($nodalOfficers) && $nodalOfficers->count() > 0)
                                    @foreach($nodalOfficers as $officer)
                                        <option value="{{ $officer }}" {{ request('nodal_officer') == $officer ? 'selected' : '' }}>{{ $officer }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn theme-bg-blue text-white w-100">Search</button>
                        </div>
                        @if(request('search') || request('zone') || request('nodal_officer'))
                            <div class="col-12">
                                <a href="{{ route('admin.members', array_filter(['filter' => $filter, 'payment_filter' => $paymentFilter ?? null, 'gst_verification_filter' => $gstVerificationFilter ?? null])) }}" class="btn btn-sm btn-danger">Clear Filters</a>
                                <small class="text-muted ms-2">
                                    @if(request('search'))
                                        Search: <strong>{{ request('search') }}</strong>
                                    @endif
                                    @if(request('zone'))
                                        | Zone: <strong>{{ request('zone') }}</strong>
                                    @endif
                                    @if(request('nodal_officer'))
                                        | Nodal Officer: <strong>{{ request('nodal_officer') }}</strong>
                                    @endif
                                </small>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Reports: visible only when current role is IX Account -->
    @if(isset($showExportReports) && $showExportReports)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">Export Reports</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <button type="button" class="btn theme-bg-blue text-white w-100" data-bs-toggle="modal" data-bs-target="#exportModal">
                                <i class="bi bi-file-earmark-excel"></i> Export to Excel
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn theme-bg-green text-white w-100" id="exportInvoiceAmountsBtn" onclick="exportInvoiceAmounts()">
                                <i class="bi bi-calculator"></i> Export Invoice Amounts
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn theme-bg-gold text-white w-100" id="exportGstVerificationBtn" data-bs-toggle="modal" data-bs-target="#gstVerificationModal">
                                <i class="bi bi-file-earmark-check"></i> Export GST Verification Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- GST Verification Selection Modal -->
    @if(isset($showBillingPaymentSummary) && $showBillingPaymentSummary)
    <div class="modal fade" id="gstVerificationModal" tabindex="-1" aria-labelledby="gstVerificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-c-blue">
                <div class="modal-header theme-bg-blue text-white d-flex justify-content-between align-items-center">
                    <h5 class="modal-title text-white" id="gstVerificationModalLabel" style="color: #fff !important;">Select Members for GST Verification Report</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="gstVerificationForm">
                    <div class="modal-body">
                        <p class="lead fs-6">Select members from the list below. Only live members (with active membership status) are shown.</p>
                        
                        <!-- Search Box -->
                        <div class="mb-3 theme-forms">
                            <input type="text" id="memberSearchInput" class="form-control form-control-sm" placeholder="Search by name, email, or membership ID...">
                        </div>
                        
                        <div class="table-responsive theme-forms" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-hover">
                                <thead class="sticky-top bg-light text-nowrap">
                                    <tr>
                                        <th style="width: 40px;">
                                            <input type="checkbox" id="selectAllMembersModal" class="form-check-input">
                                        </th>
                                        <th>Membership ID</th>
                                        <th>User Name</th>
                                        <th style="min-width: 200px;">Email</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="membersTableBody">
                                    @if(isset($allLiveMembers) && $allLiveMembers->count() > 0)
                                        @foreach($allLiveMembers as $application)
                                        @php
                                            // $application is now the member (application with membership_id)
                                            $membershipId = $application->membership_id ?? 'N/A';
                                            $user = $application->user;
                                        @endphp
                                        <tr class="member-row align-middle" data-name="{{ $user ? strtolower($user->fullname) : '' }}" data-email="{{ $user ? strtolower($user->email) : '' }}" data-membership="{{ strtolower($membershipId) }}">
                                            <td>
                                                <input type="checkbox" name="member_ids[]" value="{{ $application->id }}" class="form-check-input member-checkbox-modal" data-member-id="{{ $application->id }}">
                                            </td>
                                            <td><strong>{{ $membershipId }}</strong></td>
                                            <td>{{ $user ? $user->fullname : 'N/A' }}</td>
                                            <td class="text-break">{{ $user ? $user->email : 'N/A' }}</td>
                                            <td><span class="badge bg-success">Live</span></td>
                                        </tr>
                                        @endforeach
                                    @else
                                        <tr class="align-middle">
                                            <td colspan="5" class="text-center text-muted py-3">No live members found.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <small class="lead fs-6">
                                <span id="selectedCount">0</span> member(s) selected
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-file-earmark-check"></i> Export Selected Members
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-c-blue">
                <div class="modal-header theme-bg-blue text-white d-flex justify-content-between align-items-center">
                    <h5 class="modal-title text-white" id="exportModalLabel" style="color: #fff !important;">Export Members Invoice Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="GET" action="{{ route('admin.members.export') }}" id="exportForm" class="theme-forms">
                    <div class="modal-body">
                        <input type="hidden" name="filter" value="{{ $filter }}">
                        @if(isset($paymentFilter) && $paymentFilter)
                            <input type="hidden" name="payment_filter" value="{{ $paymentFilter }}">
                        @endif
                        @if(request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        @if(request('zone'))
                            <input type="hidden" name="zone" value="{{ request('zone') }}">
                        @endif
                        @if(request('nodal_officer'))
                            <input type="hidden" name="nodal_officer" value="{{ request('nodal_officer') }}">
                        @endif
                        @if(isset($gstVerificationFilter) && $gstVerificationFilter)
                            <input type="hidden" name="gst_verification_filter" value="{{ $gstVerificationFilter }}">
                        @endif
                        
                        <div class="mb-3">
                            <label for="zone" class="form-label small fw-semibold">Zone (Optional)</label>
                            <select class="form-select form-select-sm" id="zone" name="zone">
                                <option value="">All Zones</option>
                                @if(isset($zones) && $zones->count() > 0)
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone }}" {{ request('zone') == $zone ? 'selected' : '' }}>{{ $zone }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="nodal_officer" class="form-label small fw-semibold">Nodal Officer (Optional)</label>
                            <select class="form-select form-select-sm" id="nodal_officer" name="nodal_officer">
                                <option value="">All Nodal Officers</option>
                                @if(isset($nodalOfficers) && $nodalOfficers->count() > 0)
                                    @foreach($nodalOfficers as $officer)
                                        <option value="{{ $officer }}" {{ request('nodal_officer') == $officer ? 'selected' : '' }}>{{ $officer }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="user_id" class="form-label small fw-semibold">Filter by User (Optional)</label>
                            <input type="text" class="form-control form-control-sm" id="user_id" name="user_id" 
                                placeholder="Enter Registration ID or leave blank for all users"
                                value="{{ request('user_id') }}">
                            <small class="text-muted">Leave blank to export all users</small>
                        </div>

                        <div class="mb-3">
                            <label for="invoice_status" class="form-label small fw-semibold">Invoice Status</label>
                            <select class="form-select form-select-sm" id="invoice_status" name="invoice_status">
                                <option value="all" {{ request('invoice_status', 'all') == 'all' ? 'selected' : '' }}>All Invoices</option>
                                <option value="paid" {{ request('invoice_status') == 'paid' ? 'selected' : '' }}>Paid Invoices Only</option>
                                <option value="unpaid" {{ request('invoice_status') == 'unpaid' ? 'selected' : '' }}>Unpaid Invoices Only</option>
                            </select>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="date_from" class="form-label small fw-semibold">From Date (Optional)</label>
                                <input type="date" class="form-control form-control-sm" id="date_from" name="date_from">
                            </div>
                            <div class="col-md-6">
                                <label for="date_to" class="form-label small fw-semibold">To Date (Optional)</label>
                                <input type="date" class="form-control form-control-sm" id="date_to" name="date_to">
                            </div>
                        </div>
                        <small class="text-muted">Leave blank to export all invoices regardless of date</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel"></i> Export to Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold text-capitalize">Members List</h5>
                </div>
                <div class="card-body">
                    @if($members->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="text-nowrap">
                                    <tr>
                                        <th style="color: #2c3e50; font-weight: 600;">Membership ID</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Node & Port Details</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Registered User</th>
                                        <th style="color: #2c3e50; font-weight: 600;">GST Verification</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Membership Status</th>
                                        <th class="text-end pe-3" style="color: #2c3e50; font-weight: 600;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($members as $application)
                                    @php
                                        // $application is now the member (application with membership_id)
                                        $membershipId = $application->membership_id ?? 'N/A';
                                        $isActive = $application->is_active ?? false;
                                        
                                        // Get GST verification status
                                        $gstVerification = $application->gstVerification ?? null;
                                        $gstVerified = $gstVerification && $gstVerification->is_verified;
                                        $gstin = null;
                                        if ($gstVerification) {
                                            $gstin = $gstVerification->gstin;
                                        } elseif ($application->application_data) {
                                            $appData = $application->application_data;
                                            $gstin = $appData['gstin'] ?? null;
                                        }
                                        
                                        // Get location and port details from application
                                        $locationInfo = null;
                                        $portInfo = null;
                                        if ($application->application_data) {
                                            $appData = $application->application_data;
                                            $locationInfo = $appData['location'] ?? null;
                                            $portInfo = $appData['port_selection'] ?? null;
                                        }
                                        
                                        // Get effective port capacity (updated by assign port or approved plan change)
                                        $portCapacity = $application->getEffectivePortCapacity();
                                        $portNumber = $application->assigned_port_number ?? null;
                                        
                                        // Get user from application
                                        $user = $application->user;
                                    @endphp
                                    <tr class="align-middle">
                                        <td><strong style="color: #2c3e50;">{{ $membershipId }}</strong></td>
                                        <td>
                                            @if($locationInfo || $portCapacity || $portNumber)
                                                <div style="font-size: 0.875rem;">
                                                    @if($locationInfo && isset($locationInfo['name']))
                                                        <div><strong>Node:</strong> {{ $locationInfo['name'] }}</div>
                                                        @if(isset($locationInfo['node_type']))
                                                            <div class="text-muted small">{{ ucfirst($locationInfo['node_type']) }}</div>
                                                        @endif
                                                    @else
                                                        <div class="text-muted">Node: N/A</div>
                                                    @endif
                                                    
                                                    @if($portNumber)
                                                        <div class="mt-1"><strong>Port Number:</strong> {{ $portNumber }}</div>
                                                    @endif
                                                    
                                                    @if($portCapacity)
                                                        <div class="mt-1"><strong>Port Capacity:</strong> {{ $portCapacity }}</div>
                                                    @else
                                                        <div class="text-muted mt-1">Port Capacity: N/A</div>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($user)
                                                <div>
                                                    <a href="{{ route('admin.users.show', $user->id) }}" style="color: #0d6efd; text-decoration: none; font-weight: 500;">
                                                        {{ $user->fullname }}
                                                    </a>
                                                </div>
                                                <div class="text-muted small">{{ $user->email }}</div>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($gstin)
                                                @if($gstVerified)
                                                    <span class="badge bg-success">Verified</span>
                                                    <div class="text-muted small mt-1">{{ $gstin }}</div>
                                                @else
                                                    <span class="badge bg-warning text-dark">Unverified</span>
                                                    <div class="text-muted small mt-1">{{ $gstin }}</div>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary">No GST</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $serviceStatus = $application->service_status ?? ($isActive ? 'live' : 'disconnected');
                                            @endphp
                                            @if($serviceStatus === 'live')
                                                <span class="badge bg-success">LIVE</span>
                                            @elseif($serviceStatus === 'suspended')
                                                <span class="badge bg-warning text-dark">SUSPENDED</span>
                                            @else
                                                <span class="badge bg-danger">DISCONNECTED</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 justify-content-end">
                                                <a href="{{ route('admin.applications.show', $application->id) }}" class="btn btn-sm btn-primary text-nowrap">View Details</a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 d-flex justify-content-center">
                            {{ $members->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <p class="text-muted">No members found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal for Export -->
<div class="modal fade" id="exportLoadingModal" tabindex="-1" aria-labelledby="exportLoadingModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="mb-2">Generating Export...</h5>
                <p class="text-muted mb-0">Please wait while we calculate invoice amounts for all members. This may take a few moments.</p>
            </div>
        </div>
    </div>
</div>

<script>
function exportInvoiceAmounts() {
    // Show loading modal
    const loadingModalElement = document.getElementById('exportLoadingModal');
    const loadingModal = new bootstrap.Modal(loadingModalElement);
    loadingModal.show();
    
    // Build URL with current filters
    let url = '{{ route("admin.members.export-invoice-amounts") }}';
    const params = new URLSearchParams(window.location.search);
    if (params.toString()) {
        url += '?' + params.toString();
    }
    
    // Use fetch to wait for the complete response before triggering download
    // This ensures the modal stays visible until the file is actually ready
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Export failed');
            }
            
            // Get the blob from the response (this waits for the entire file)
            return response.blob();
        })
        .then(blob => {
            // Create a temporary URL for the blob
            const blobUrl = window.URL.createObjectURL(blob);
            
            // Create a temporary anchor element to trigger download
            const link = document.createElement('a');
            link.href = blobUrl;
            link.download = 'members_invoice_amounts_export_' + new Date().toISOString().slice(0, 10) + '.csv';
            document.body.appendChild(link);
            
            // Trigger the download
            link.click();
            
            // Clean up after download starts
            setTimeout(() => {
                if (document.body.contains(link)) {
                    document.body.removeChild(link);
                }
                window.URL.revokeObjectURL(blobUrl);
                
                // Hide modal after download is triggered
                if (loadingModalElement && loadingModalElement.classList.contains('show')) {
                    loadingModal.hide();
                }
            }, 500);
        })
        .catch(error => {
            console.error('Export error:', error);
            
            // Hide modal on error
            if (loadingModalElement && loadingModalElement.classList.contains('show')) {
                loadingModal.hide();
            }
            
            alert('Failed to export invoice amounts. Please try again.');
        });
}

// GST Verification Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const selectAllModal = document.getElementById('selectAllMembersModal');
    let memberCheckboxesModal = document.querySelectorAll('.member-checkbox-modal');
    const selectedCountEl = document.getElementById('selectedCount');
    const gstVerificationForm = document.getElementById('gstVerificationForm');
    const memberSearchInput = document.getElementById('memberSearchInput');
    const membersTableBody = document.getElementById('membersTableBody');
    
    // Search functionality
    if (memberSearchInput && membersTableBody) {
        memberSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const rows = membersTableBody.querySelectorAll('.member-row');
            
            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const email = row.getAttribute('data-email') || '';
                const membership = row.getAttribute('data-membership') || '';
                
                if (name.includes(searchTerm) || email.includes(searchTerm) || membership.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update select all checkbox state based on visible checkboxes
            updateSelectAllState();
            updateSelectedCount();
        });
    }
    
    // Update selected count (only counts visible checkboxes)
    function updateSelectedCount() {
        const visibleCheckboxes = Array.from(memberCheckboxesModal).filter(cb => {
            const row = cb.closest('tr');
            return row && row.style.display !== 'none';
        });
        const selected = visibleCheckboxes.filter(cb => cb.checked).length;
        if (selectedCountEl) {
            selectedCountEl.textContent = selected;
        }
    }
    
    // Update select all checkbox state
    function updateSelectAllState() {
        if (!selectAllModal) return;
        
        const visibleCheckboxes = Array.from(memberCheckboxesModal).filter(cb => {
            const row = cb.closest('tr');
            return row && row.style.display !== 'none';
        });
        
        if (visibleCheckboxes.length === 0) {
            selectAllModal.checked = false;
            selectAllModal.indeterminate = false;
            return;
        }
        
        const allChecked = visibleCheckboxes.every(cb => cb.checked);
        const someChecked = visibleCheckboxes.some(cb => cb.checked);
        
        selectAllModal.checked = allChecked;
        selectAllModal.indeterminate = someChecked && !allChecked;
    }
    
    // Select all functionality (only selects visible members)
    if (selectAllModal) {
        selectAllModal.addEventListener('change', function() {
            const visibleCheckboxes = Array.from(memberCheckboxesModal).filter(cb => {
                const row = cb.closest('tr');
                return row && row.style.display !== 'none';
            });
            
            visibleCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllModal.checked;
            });
            updateSelectedCount();
        });
    }
    
    // Update count when individual checkboxes change
    memberCheckboxesModal.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectAllState();
            updateSelectedCount();
        });
    });
    
    // Handle form submission
    if (gstVerificationForm) {
        gstVerificationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get selected member IDs
            const selectedMembers = Array.from(memberCheckboxesModal).filter(cb => cb.checked);
            if (selectedMembers.length === 0) {
                alert('Please select at least one member to export.');
                return;
            }
            
            // Show loading modal
            const loadingModalElement = document.getElementById('exportLoadingModal');
            const loadingModal = new bootstrap.Modal(loadingModalElement);
            loadingModal.show();
            
            // Update modal message for GST verification
            const modalBody = loadingModalElement.querySelector('.modal-body');
            const originalMessage = modalBody.innerHTML;
            modalBody.innerHTML = `
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="mb-2">Generating GST Verification Report...</h5>
                <p class="text-muted mb-0">Please wait while we verify GST numbers for ${selectedMembers.length} selected member(s). This may take several minutes.</p>
            `;
            
            // Close the selection modal
            const gstModal = bootstrap.Modal.getInstance(document.getElementById('gstVerificationModal'));
            if (gstModal) {
                gstModal.hide();
            }
            
            // Build URL with selected member IDs
            const params = new URLSearchParams();
            selectedMembers.forEach(checkbox => {
                params.append('member_ids[]', checkbox.value);
            });
            
            let url = '{{ route("admin.members.export-gst-verification") }}?' + params.toString();
            
            // Use fetch to wait for the complete response before triggering download
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Export failed');
                    }
                    
                    // Get the blob from the response (this waits for the entire file)
                    return response.blob();
                })
                .then(blob => {
                    // Create a temporary URL for the blob
                    const blobUrl = window.URL.createObjectURL(blob);
                    
                    // Create a temporary anchor element to trigger download
                    const link = document.createElement('a');
                    link.href = blobUrl;
                    link.download = 'gst_verification_report_' + new Date().toISOString().slice(0, 10) + '.csv';
                    document.body.appendChild(link);
                    
                    // Trigger the download
                    link.click();
                    
                    // Clean up after download starts
                    setTimeout(() => {
                        if (document.body.contains(link)) {
                            document.body.removeChild(link);
                        }
                        window.URL.revokeObjectURL(blobUrl);
                        
                        // Restore original message and hide modal
                        modalBody.innerHTML = originalMessage;
                        if (loadingModalElement && loadingModalElement.classList.contains('show')) {
                            loadingModal.hide();
                        }
                    }, 500);
                })
                .catch(error => {
                    console.error('Export error:', error);
                    
                    // Restore original message and hide modal on error
                    modalBody.innerHTML = originalMessage;
                    if (loadingModalElement && loadingModalElement.classList.contains('show')) {
                        loadingModal.hide();
                    }
                    
                    alert('Failed to export GST verification report. Please try again.');
                });
        });
    }
    
    // Reset form when modal is closed
    const gstModalElement = document.getElementById('gstVerificationModal');
    if (gstModalElement) {
        gstModalElement.addEventListener('hidden.bs.modal', function() {
            if (gstVerificationForm) {
                gstVerificationForm.reset();
            }
            memberCheckboxesModal.forEach(checkbox => {
                checkbox.checked = false;
            });
            if (selectAllModal) {
                selectAllModal.checked = false;
            }
            updateSelectedCount();
        });
    }
});
</script>
@endsection
