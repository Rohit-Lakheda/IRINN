@extends('admin.layout')

@section('title', 'Application Details')

@section('content')
@if($application->application_type === 'IRINN')
<div class="row mb-md-4">
    <div class="col-12">
        <h1>Application Details</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.applications') }}">Applications</a></li>
                <li class="breadcrumb-item active">{{ $application->application_id }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-lg-6">
        <div class="card border-c-blue shadow mb-4 irinn-app-info-card">
            <div class="card-header theme-bg-blue text-white">
                <h5 class="mb-0 text-capitalize">Application Information</h5>
            </div>
            <div class="card-body p-2">
                    @php
                        $data = $application->application_data ?? [];
                        $part2 = $data['part2'] ?? [];
                        $ipv4 = $part2['ipv4_prefix'] ?? null;
                        $ipv6 = $part2['ipv6_prefix'] ?? null;
                        $asnRequired = $part2['asn_required'] ?? null;
                        $totalFee = $data['total_fee'] ?? ($data['part5']['total_amount'] ?? null);
                        $isLive = $application->status === 'billing';
                    @endphp
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Application ID</label>
                            <div class="fw-semibold" style="color:#1e3a8a;">{{ $application->application_id }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Current Stage</label>
                            <span class="badge theme-bg-blue bg-opacity-75 text-white text-capitalize px-3 py-2" style="font-size:0.8rem;">
                                {{ $application->current_stage ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Status</label>
                            <span class="badge px-3 py-2 text-capitalize" style="font-size:0.8rem; background:#fef3c7; color:#92400e;">
                                {{ $application->status_display }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Live / Not Live</label>
                            @if($isLive)
                                <span class="badge px-3 py-2" style="font-size:0.8rem; background:#dcfce7; color:#166534;">Live (Billing)</span>
                            @else
                                <span class="badge px-3 py-2" style="font-size:0.8rem; background:#fee2e2; color:#991b1b;">Not Live</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Applicant</label>
                            <div class="fw-semibold" style="color:#1e3a8a;">{{ $application->user->fullname ?? 'N/A' }}</div>
                            <small class="text-muted">{{ $application->user->email ?? 'N/A' }}</small>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Submitted At</label>
                            <div class="fw-semibold" style="color:#1e3a8a;">
                                {{ $application->submitted_at ? $application->submitted_at->format('d M Y, h:i A') : 'N/A' }}
                            </div>
                        </div>
                    </div>
                    <div class="bg-light p-3 rounded">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block">IPv4 Prefix</label>
                                <div class="fw-semibold" style="color:#1e3a8a;">{{ $ipv4 ?? 'Not requested' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block">IPv6 Prefix</label>
                                <div class="fw-semibold" style="color:#1e3a8a;">{{ $ipv6 ?? 'Not requested' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block">ASN Required</label>
                                <div class="fw-semibold" style="color:#1e3a8a;">
                                    {{ ($asnRequired === 'yes') ? 'Yes' : 'No' }}
                                </div>
                            </div>
                            @if($totalFee)
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block">Application Fee (incl. GST)</label>
                                <div class="fw-semibold" style="color:#1e3a8a;">₹{{ number_format($totalFee, 2) }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                @else
                <table class="table table-bordered rounded-0">
                    <tr class="align-middle">
                        <th width="200">Application ID:</th>
                        <td><strong>{{ $application->application_id }}</strong></td>
                    </tr>
                    @if($application->membership_id)
                    @php
                        $serviceStatus = $application->service_status ?? ($application->is_active ? 'live' : 'disconnected');
                    @endphp
                    <tr class="align-middle">
                        <th>Service Status:</th>
                        <td>
                            @if($serviceStatus === 'live')
                                <span class="badge bg-success text-capitalize">Live</span>
                                @if($application->service_activation_date)
                                    <span class="text-muted ms-2 small">Activated: {{ $application->service_activation_date->format('d M Y') }}</span>
                                @endif
                            @elseif($serviceStatus === 'suspended')
                                <span class="badge bg-warning text-dark text-capitalize">Suspended</span>
                                @if($application->suspended_from)
                                    <span class="text-muted ms-2 small">From: {{ \Carbon\Carbon::parse($application->suspended_from)->format('d M Y') }}</span>
                                @endif
                            @else
                                <span class="badge bg-danger text-capitalize">Disconnected</span>
                                @if($application->disconnected_at)
                                    <span class="text-muted ms-2 small">Effective: {{ \Carbon\Carbon::parse($application->disconnected_at)->format('d M Y') }}</span>
                                @endif
                            @endif

                            @php
                                $pendingStopDate = null;
                                $pendingInvoice = null;

                                if (isset($selectedRole) && $selectedRole === 'ix_account') {
                                    if ($serviceStatus === 'suspended' && $application->suspended_from) {
                                        $pendingStopDate = \Carbon\Carbon::parse($application->suspended_from)->subDay()->format('Y-m-d');
                                    } elseif ($serviceStatus === 'disconnected' && $application->disconnected_at) {
                                        $pendingStopDate = \Carbon\Carbon::parse($application->disconnected_at)->format('Y-m-d');
                                    }

                                    if ($pendingStopDate) {
                                        $pendingInvoice = \App\Models\Invoice::query()
                                            ->where('application_id', $application->id)
                                            ->where('status', '!=', 'cancelled')
                                            ->where('invoice_purpose', 'service')
                                            ->whereNotNull('billing_start_date')
                                            ->whereNotNull('billing_end_date')
                                            ->whereDate('billing_start_date', '<=', $pendingStopDate)
                                            ->whereDate('billing_end_date', '>=', $pendingStopDate)
                                            ->latest('invoice_date')
                                            ->first();
                                    }
                                }
                            @endphp

                            @if($serviceStatus !== 'live' && isset($selectedRole) && $selectedRole === 'ix_account')
                                <div class="mt-2">
                                    @if($pendingInvoice)
                                        <div class="small text-muted">Final/Pending Invoice:</div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap mt-1">
                                            @if($pendingInvoice->hasCreditNote())
                                                <a class="btn btn-sm btn-outline-info" href="{{ route('admin.applications.invoice.download', ['invoice' => $pendingInvoice->id, 'type' => 'credit_note']) }}">
                                                    Download Credit Note
                                                </a>
                                                <a class="btn btn-sm btn-outline-success" href="{{ route('admin.applications.invoice.download', ['invoice' => $pendingInvoice->id, 'type' => 'invoice']) }}">
                                                    Download Invoice
                                                </a>
                                            @else
                                                <a class="btn btn-sm btn-outline-success" href="{{ route('admin.applications.invoice.download', $pendingInvoice->id) }}">
                                                    Download {{ $pendingInvoice->invoice_number }}
                                                </a>
                                            @endif
                                            @php
                                                $invStatus = strtoupper($pendingInvoice->payment_status ?? $pendingInvoice->status ?? 'PENDING');
                                                $invBadge = in_array(strtolower($invStatus), ['paid'], true) ? 'success' : (in_array(strtolower($invStatus), ['partial'], true) ? 'warning text-dark' : 'secondary');
                                            @endphp
                                            <span class="badge bg-{{ $invBadge }}">{{ $invStatus }}</span>
                                        </div>
                                    @else
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.applications.ix-account.generate-pending-invoice', $application->id) }}">
                                            Generate Pending Invoice
                                        </a>
                                    @endif
                                </div>
                            @endif

                            @if($serviceStatus === 'live' && isset($selectedRole) && $selectedRole === 'ix_account')
                                @php
                                    $lastDisconnected = \App\Models\ApplicationServiceStatusHistory::query()
                                        ->where('application_id', $application->id)
                                        ->where('status', 'disconnected')
                                        ->whereNotNull('effective_from')
                                        ->latest('effective_from')
                                        ->first();

                                    $finalStopDate = $lastDisconnected ? \Carbon\Carbon::parse($lastDisconnected->effective_from)->format('Y-m-d') : null;

                                    $finalInvoice = null;
                                    if ($finalStopDate) {
                                        $finalInvoice = \App\Models\Invoice::query()
                                            ->where('application_id', $application->id)
                                            ->where('status', '!=', 'cancelled')
                                            ->where('invoice_purpose', 'service')
                                            ->whereNotNull('billing_start_date')
                                            ->whereNotNull('billing_end_date')
                                            ->whereDate('billing_start_date', '<=', $finalStopDate)
                                            ->whereDate('billing_end_date', '>=', $finalStopDate)
                                            ->latest('invoice_date')
                                            ->first();
                                    }
                                @endphp

                                @if($lastDisconnected && ! $finalInvoice)
                                    <div class="mt-2">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.applications.ix-account.generate-pending-invoice', $application->id) }}">
                                            Generate Final Invoice (before Disconnection)
                                        </a>
                                    </div>
                                @elseif($lastDisconnected && $finalInvoice)
                                    <div class="mt-2">
                                        <div class="small text-muted">Final Invoice (before Disconnection):</div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap mt-1">
                                            @if($finalInvoice->hasCreditNote())
                                                <a class="btn btn-sm btn-outline-info" href="{{ route('admin.applications.invoice.download', ['invoice' => $finalInvoice->id, 'type' => 'credit_note']) }}">
                                                    Download Credit Note
                                                </a>
                                                <a class="btn btn-sm btn-outline-success" href="{{ route('admin.applications.invoice.download', ['invoice' => $finalInvoice->id, 'type' => 'invoice']) }}">
                                                    Download Invoice
                                                </a>
                                            @else
                                                <a class="btn btn-sm btn-outline-success" href="{{ route('admin.applications.invoice.download', $finalInvoice->id) }}">
                                                    Download {{ $finalInvoice->invoice_number }}
                                                </a>
                                            @endif
                                            @php
                                                $invStatus = strtoupper($finalInvoice->payment_status ?? $finalInvoice->status ?? 'PENDING');
                                                $invBadge = in_array(strtolower($invStatus), ['paid'], true) ? 'success' : (in_array(strtolower($invStatus), ['partial'], true) ? 'warning text-dark' : 'secondary');
                                            @endphp
                                            <span class="badge bg-{{ $invBadge }}">{{ $invStatus }}</span>
                                        </div>
                                    </div>
                                @endif
                            @endif

                            @if($serviceStatus === 'disconnected' && isset($selectedRole) && $selectedRole === 'ix_account')
                                @php
                                    $latestReactivationRequest = \App\Models\ApplicationReactivationRequest::where('application_id', $application->id)
                                        ->latest()
                                        ->first();
                                @endphp
                                <div class="mt-2">
                                    <div class="small text-muted">Reactivation Request:</div>
                                    @if(! $latestReactivationRequest)
                                        <div class="small text-muted">No request submitted yet.</div>
                                    @else
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="badge bg-secondary">{{ strtoupper($latestReactivationRequest->status) }}</span>
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.reactivation-requests.index') }}">Open Requests</a>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                    <tr class="align-middle">
                        <th>Seller GST (Invoice):</th>
                        <td>
                            @php
                                $assignedSellerStateCode = $application->seller_state_code ? (string) $application->seller_state_code : null;
                            @endphp
                            <form method="POST" action="{{ route('admin.applications.seller-gst.update', $application->id) }}" class="d-flex flex-column gap-2">
                                @csrf
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">Assigned Seller GST (optional)</label>
                                        <select name="seller_state_code" id="seller_state_code_select" class="form-select form-select-sm">
                                            <option value="">Auto (Current Flow)</option>
                                            @foreach(($sellerGstOptions ?? []) as $stateCode => $label)
                                                <option value="{{ $stateCode }}" {{ ($assignedSellerStateCode !== null && (string) $stateCode === $assignedSellerStateCode) ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                    </div>
                                </div>
                                <small class="text-muted">If not set, seller GST will be determined automatically based on buyer's state code.</small>
                            </form>
                        </td>
                    </tr>
                    <tr class="align-middle">
                        <th>Update Service Status:</th>
                        <td>
                            <form method="POST" action="{{ route('admin.applications.service-status', $application->id) }}" class="d-flex flex-column gap-2">
                                @csrf
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <select name="service_status" id="service_status" class="form-select form-select-sm">
                                            <option value="live" {{ $serviceStatus === 'live' ? 'selected' : '' }}>Live</option>
                                            <option value="suspended" {{ $serviceStatus === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                            <option value="disconnected" {{ $serviceStatus === 'disconnected' ? 'selected' : '' }}>Disconnected</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4" id="activation_date_wrap">
                                        <input type="date" name="activation_date" class="form-control form-control-sm" value="{{ old('activation_date', now()->format('Y-m-d')) }}" placeholder="Activation date">
                                    </div>
                                    <div class="col-md-4 d-none" id="suspension_date_wrap">
                                        <input type="date" name="suspension_date" class="form-control form-control-sm" value="{{ old('suspension_date', now()->format('Y-m-d')) }}" placeholder="Suspension date">
                                    </div>
                                    <div class="col-md-4 d-none" id="disconnection_date_wrap">
                                        <input type="date" name="disconnection_date" class="form-control form-control-sm" value="{{ old('disconnection_date', now()->format('Y-m-d')) }}" placeholder="Disconnection date">
                                    </div>
                                </div>
                                <div>
                                    <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Notes (optional)">{{ old('notes') }}</textarea>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Are you sure you want to update the service status?')">
                                        Update
                                    </button>
                                </div>
                            </form>

                            <script>
                                (function () {
                                    const statusSelect = document.getElementById('service_status');
                                    const activationWrap = document.getElementById('activation_date_wrap');
                                    const suspensionWrap = document.getElementById('suspension_date_wrap');
                                    const disconnectionWrap = document.getElementById('disconnection_date_wrap');

                                    function refresh() {
                                        const val = statusSelect.value;
                                        activationWrap.classList.toggle('d-none', val !== 'live');
                                        suspensionWrap.classList.toggle('d-none', val !== 'suspended');
                                        disconnectionWrap.classList.toggle('d-none', val !== 'disconnected');
                                    }

                                    statusSelect.addEventListener('change', refresh);
                                    refresh();
                                })();
                            </script>
                        </td>
                    </tr>
                    @if($application->serviceStatusHistories && $application->serviceStatusHistories->count() > 0)
                    <tr class="align-middle">
                        <th>Service Timeline:</th>
                        <td>
                            @if(isset($selectedRole) && $selectedRole === 'ix_account')
                                <form method="POST" action="{{ route('admin.applications.service-timeline.reset', $application->id) }}" class="mb-2 d-flex align-items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="reset_fields" value="1">
                                    <input type="hidden" name="reset_reactivation" value="1">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reset service timeline + reactivation requests + reactivation/final invoices for this application? This is for testing only.')">
                                        Reset Timeline + Reactivation (Testing)
                                    </button>
                                </form>
                            @endif
                            <div class="d-flex flex-column gap-1">
                                @foreach($application->serviceStatusHistories->take(8) as $entry)
                                    <div class="d-flex align-items-center justify-content-between border rounded px-2 py-1">
                                        <div class="d-flex align-items-center gap-2">
                                            @if($entry->status === 'live')
                                                <span class="badge bg-success text-capitalize">Live</span>
                                            @elseif($entry->status === 'suspended')
                                                <span class="badge bg-warning text-dark text-capitalize">Suspended</span>
                                            @else
                                                <span class="badge bg-danger text-capitalize">Disconnected</span>
                                            @endif
                                            <span class="small text-muted">
                                                {{ $entry->effective_from ? \Carbon\Carbon::parse($entry->effective_from)->format('d M Y') : '-' }}
                                            </span>
                                            @if($entry->notes)
                                                <span class="small">{{ $entry->notes }}</span>
                                            @endif
                                        </div>
                                        <div class="small text-muted">
                                            {{ $entry->created_at ? $entry->created_at->format('d M Y, h:i A') : '' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endif
                    @endif
                    <tr class="align-middle">
                        <th>Status:</th>
                        <td>
                            @if($application->application_type === 'IX')
                                {{-- New IX Workflow Statuses --}}
                                @if($application->status === 'approved' || $application->status === 'payment_verified')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($application->status === 'rejected' || $application->status === 'ceo_rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @elseif(in_array($application->status, ['submitted', 'resubmitted', 'processor_resubmission', 'legal_sent_back', 'head_sent_back']))
                                    <span class="badge bg-warning">IX Processor Review</span>
                                @elseif($application->status === 'processor_forwarded_legal')
                                    <span class="badge bg-info">IX Legal Review</span>
                                @elseif($application->status === 'legal_forwarded_head')
                                    <span class="badge bg-primary">IX Head Review</span>
                                @elseif($application->status === 'head_forwarded_ceo')
                                    <span class="badge" style="background-color: #6f42c1; color: white;">CEO Review</span>
                                @elseif($application->status === 'ceo_approved')
                                    <span class="badge bg-info">Nodal Officer Review</span>
                                @elseif($application->status === 'port_assigned')
                                    <span class="badge bg-primary">IX Tech Team Review</span>
                                @elseif(in_array($application->status, ['ip_assigned', 'invoice_pending']))
                                    <span class="badge bg-warning">IX Account Review</span>
                                @else
                                    <span class="badge bg-secondary">{{ $application->status_display }}</span>
                                @endif
                            @else
                                {{-- Legacy Statuses --}}
                            @if($application->status === 'approved')
                                <span class="badge bg-success">Approved</span>
                            @elseif($application->status === 'rejected')
                                <span class="badge bg-danger">Rejected</span>
                            @elseif(in_array($application->status, ['pending', 'processor_review']))
                                <span class="badge bg-warning">Processor Review</span>
                            @elseif(in_array($application->status, ['processor_approved', 'finance_review']))
                                <span class="badge bg-info">Finance Review</span>
                            @elseif($application->status === 'finance_approved')
                                <span class="badge bg-primary">Technical Review</span>
                            @else
                                    <span class="badge bg-secondary">{{ $application->status_display }}</span>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @if($application->application_type === 'IX')
                        @php
                            $appData = $application->application_data ?? [];
                            $location = $appData['location'] ?? null;
                            $portSelection = $appData['port_selection'] ?? [];
                        @endphp
                        @if($location)
                        <tr class="align-middle">
                            <th>Node/Location:</th>
                            <td><strong>{{ $location['name'] ?? 'N/A' }}</strong></td>
                        </tr>
                        @endif
                        @if($location && isset($location['node_type']))
                        <tr class="align-middle">
                            <th>Node Type:</th>
                            <td><strong>{{ ucfirst($location['node_type']) }}</strong></td>
                        </tr>
                        @endif
                        @if($location && isset($location['state']))
                        <tr class="align-middle">
                            <th>State:</th>
                            <td><strong>{{ $location['state'] }}</strong></td>
                        </tr>
                        @endif
                        @php
                            $displayPortCapacity = $application->getEffectivePortCapacity();
                        @endphp
                        @if($displayPortCapacity)
                        <tr class="align-middle">
                            <th>Port Capacity:</th>
                            <td><strong>{{ $displayPortCapacity }}</strong></td>
                        </tr>
                        @endif
                        @if($application->assigned_port_number)
                        <tr class="align-middle">
                            <th>Assigned Port Number:</th>
                            <td><strong>{{ $application->assigned_port_number }}</strong></td>
                        </tr>
                        @endif
                        @if($application->customer_id)
                        <tr class="align-middle">
                            <th>Customer ID:</th>
                            <td><strong>{{ $application->customer_id }}</strong></td>
                        </tr>
                        @endif
                        @if($application->membership_id)
                        <tr class="align-middle">
                            <th>Membership ID:</th>
                            <td><strong>{{ $application->membership_id }}</strong></td>
                        </tr>
                        @endif
                        @if($application->assigned_ip)
                        <tr class="align-middle">
                            <th>Assigned IP:</th>
                            <td><strong>{{ $application->assigned_ip }}</strong></td>
                        </tr>
                        @endif
                        @if($application->service_activation_date)
                        <tr class="align-middle">
                            <th>Service Activation Date:</th>
                            <td><strong>{{ \Carbon\Carbon::parse($application->service_activation_date)->format('d M Y') }}</strong></td>
                        </tr>
                        @endif
                        @if($application->billing_cycle)
                        <tr class="align-middle">
                            <th>Billing Cycle:</th>
                            <td><strong>
                                @php
                                    $billingCycle = strtolower(trim($application->billing_cycle));
                                    if (in_array($billingCycle, ['mrc', 'monthly'])) {
                                        echo 'Monthly (MRC)';
                                    } elseif (in_array($billingCycle, ['arc', 'annual'])) {
                                        echo 'Annual (ARC)';
                                    } elseif ($billingCycle === 'quarterly') {
                                        echo 'Quarterly';
                                    } else {
                                        echo ucfirst($application->billing_cycle);
                                    }
                                @endphp
                            </strong></td>
                        </tr>
                        @endif
                        @if($application->resubmission_query)
                        <tr class="align-middle">
                            <th>Resubmission Query:</th>
                            <td class="text-warning">{{ $application->resubmission_query }}</td>
                        </tr>
                        @endif
                    @endif
                    <tr class="align-middle">
                        <th>Current Stage:</th>
                        <td><span class="badge bg-warning">{{ $application->current_stage }}</span></td>
                    </tr>
                    <tr class="align-middle">
                        <th>Submitted At:</th>
                        <td><strong>{{ $application->submitted_at ? $application->submitted_at->format('d M Y, h:i A') : 'N/A' }}</strong></td>
                    </tr>
                    @if($application->approved_at)
                    <tr class="align-middle">
                        <th>Approved At:</th>
                        <td>{{ $application->approved_at->format('d M Y, h:i A') }}</td>
                    </tr>
                    @endif
                    @if($application->rejection_reason)
                    <tr class="align-middle">
                        <th>Rejection Reason:</th>
                        <td class="text-danger">{{ $application->rejection_reason }}</td>
                    </tr>
                    @endif
                </table>

                <div class="m-2">
                    <a href="{{ route('admin.applications.show-comprehensive', $application->id) }}" class="btn btn-success text-white">
                        <i class="bi bi-eye me-1"></i> View Comprehensive Details
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-6">
        @if($application->application_type === 'IRINN')
            {{-- IRINN Workflow Actions (shown in right column) --}}
            @php
                $data = $application->application_data ?? [];
                $irinnStatus = $application->status ?? 'helpdesk';
                $irinnPreviousStage = $data['irinn_previous_stage'] ?? null;
            @endphp
            <div class="card border-c-blue shadow mb-4 irinn-workflow-card">
                <div class="card-header theme-bg-blue text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-capitalize">IRINN Workflow Actions</h5>
                    <span class="badge bg-light text-dark text-capitalize">Current Stage: {{ $application->current_stage }}</span>
                </div>
                <div class="card-body">
                    @if(session('admin_selected_role') === 'helpdesk' && $irinnStatus === 'helpdesk')
                        <div class="mb-3">
                            <form method="POST" action="{{ route('admin.applications.irinn.change-stage', $application->id) }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="target_stage" value="hostmaster">
                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Move application to Hostmaster stage?');">
                                    Move to Hostmaster
                                </button>
                            </form>
                        </div>
                    @elseif(session('admin_selected_role') === 'hostmaster' && $irinnStatus === 'hostmaster')
                        <div class="mb-3">
                            <form method="POST" action="{{ route('admin.applications.irinn.change-stage', $application->id) }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="target_stage" value="billing">
                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Move application to Billing stage?');">
                                    Move to Billing
                                </button>
                            </form>
                        </div>
                    @endif

                    @if(in_array(session('admin_selected_role'), ['helpdesk', 'hostmaster'], true) && $irinnStatus !== 'billing')
                        <hr>
                        <form method="POST" action="{{ route('admin.applications.irinn.request-resubmission', $application->id) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Request Resubmission (message to user)</label>
                                <textarea name="resubmission_reason" rows="3" class="form-control" placeholder="Explain what needs to be corrected or updated">{{ old('resubmission_reason') }}</textarea>
                                @error('resubmission_reason')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Ask user to resubmit this IRINN application?');">
                                Request Resubmission
                            </button>
                            @if($irinnPreviousStage)
                                <small class="text-muted ms-2">Last stage before resubmission: {{ ucfirst($irinnPreviousStage) }}</small>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
        @else
        <!-- Action Panel for IX and legacy applications -->
        <div class="card border-info shadow mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-2 text-capitalize fw-semibold">Actions</h5>
                <small class="text-white-50 text-capitalize">Actions are only available for applications in your stage</small>
            </div>
            <div class="card-body overflow-y-auto app-recent-invoices">
                @php
                    // Determine which role to use for actions
                    $roleToUse = $selectedRole ?? null;
                    if ($admin->roles->count() === 1) {
                        $roleToUse = $admin->roles->first()->slug;
                    }
                @endphp
                
                @if(!$roleToUse || ($application->application_type === 'IX' && !$application->isVisibleToIxProcessor() && !$application->isVisibleToIxLegal() && !$application->isVisibleToIxHead() && !$application->isVisibleToCeo() && !$application->isVisibleToNodalOfficer() && !$application->isVisibleToIxTechTeam() && !$application->isVisibleToIxAccount()))
                    <div class="alert alert-info">
                        <small>This application is not in your action stage. You can view all details but cannot perform actions.</small>
                    </div>
                @endif

                @if($roleToUse === 'processor' && $application->isVisibleToProcessor())
                    <form method="POST" action="{{ route('admin.applications.approve-to-finance', $application->id) }}" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to approve this application and forward it to Finance?')">
                            Approve to Finance
                        </button>
                    </form>
                @endif

                @if($roleToUse === 'finance' && $application->isVisibleToFinance())
                    <form method="POST" action="{{ route('admin.applications.approve-to-technical', $application->id) }}" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to approve this application and forward it to Technical?')">
                            Approve to Technical
                        </button>
                    </form>

                    <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#rejectToProcessorModal">
                        Send Back to Processor
                    </button>

                    <!-- Reject to Processor Modal -->
                    <div class="modal fade" id="rejectToProcessorModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.applications.send-back-to-processor', $application->id) }}">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">Send Back to Processor</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10" placeholder="Please provide a detailed reason for sending this application back to Processor..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-warning">Send Back</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                @if($roleToUse === 'technical' && $application->isVisibleToTechnical())
                    <form method="POST" action="{{ route('admin.applications.approve', $application->id) }}" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to approve this application? This is the final approval.')">
                            Approve Application
                        </button>
                    </form>

                    <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#rejectToFinanceModal">
                        Send Back to Finance
                    </button>

                    <!-- Reject to Finance Modal -->
                    <div class="modal fade" id="rejectToFinanceModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.applications.send-back-to-finance', $application->id) }}">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">Send Back to Finance</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10" placeholder="Please provide a detailed reason for sending this application back to Finance..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-warning">Send Back</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- New IX Workflow Actions --}}
                @if($application->application_type === 'IX')
                    {{-- IX Processor Actions --}}
                    @if($roleToUse === 'ix_processor' && $application->isVisibleToIxProcessor())
                        <form method="POST" action="{{ route('admin.applications.ix-processor.forward-to-legal', $application->id) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Forward this application to IX Legal?')">
                                Forward to Legal
                            </button>
                        </form>
                        <button type="button" class="btn theme-bg-yellow theme-bg-yellow w-100 mb-3" data-bs-toggle="modal" data-bs-target="#requestResubmissionModal">
                            Request Resubmission
                        </button>
                        <!-- Request Resubmission Modal -->
                        <div class="modal fade" id="requestResubmissionModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.ix-processor.request-resubmission', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Request Resubmission</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="resubmission_query" class="form-label">Query/Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="resubmission_query" name="resubmission_query" rows="4" required minlength="10" placeholder="Please provide details about what needs to be resubmitted..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Request Resubmission</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- IX Legal Actions --}}
                    @if($roleToUse === 'ix_legal' && $application->isVisibleToIxLegal())
                        <form method="POST" action="{{ route('admin.applications.ix-legal.forward-to-head', $application->id) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Forward this application to IX Head?')">
                                Forward to IX Head
                            </button>
                        </form>
                        <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#legalSendBackModal">
                            Send Back to Processor
                        </button>
                        <!-- Legal Send Back Modal -->
                        <div class="modal fade" id="legalSendBackModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.ix-legal.send-back-to-processor', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Send Back to Processor</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="rejection_reason" class="form-label">Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Send Back</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- IX Head Actions --}}
                    @if($roleToUse === 'ix_head' && $application->isVisibleToIxHead())
                        <form method="POST" action="{{ route('admin.applications.ix-head.forward-to-ceo', $application->id) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Forward this application to CEO?')">
                                Forward to CEO
                            </button>
                        </form>
                        <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#headSendBackModal">
                            Send Back to Processor
                        </button>
                        <!-- Head Send Back Modal -->
                        <div class="modal fade" id="headSendBackModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.ix-head.send-back-to-processor', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Send Back to Processor</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="rejection_reason" class="form-label">Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Send Back</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- CEO Actions --}}
                    @if($roleToUse === 'ceo' && $application->isVisibleToCeo())
                        <form method="POST" action="{{ route('admin.applications.ceo.approve', $application->id) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Approve this application and forward to Nodal Officer?')">
                                Approve (Forward to Nodal Officer)
                            </button>
                        </form>
                        <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#ceoSendBackModal">
                            Send Back to IX Head
                        </button>
                        <button type="button" class="btn btn-danger w-100 mb-3" data-bs-toggle="modal" data-bs-target="#ceoRejectModal">
                            Reject
                        </button>
                        <!-- CEO Send Back Modal -->
                        <div class="modal fade" id="ceoSendBackModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.ceo.send-back-to-head', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Send Back to IX Head</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="send_back_reason" class="form-label">Reason (Optional)</label>
                                                <textarea class="form-control" id="send_back_reason" name="send_back_reason" rows="4" maxlength="1000" placeholder="Enter reason for sending back to IX Head (optional)"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Send Back to IX Head</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- CEO Reject Modal -->
                        <div class="modal fade" id="ceoRejectModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.ceo.reject', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Reject Application</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">Reject</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Nodal Officer Actions --}}
                    @if($roleToUse === 'nodal_officer' && $application->isVisibleToNodalOfficer())
                        <button type="button" class="btn btn-success w-100 mb-3" data-bs-toggle="modal" data-bs-target="#assignPortModal">
                            Assign Port (Forward to Tech Team)
                        </button>
                        <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#holdModal">
                            Hold
                        </button>
                        <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#notFeasibleModal">
                            Not Feasible
                        </button>
                        <form method="POST" action="{{ route('admin.applications.nodal-officer.customer-denied', $application->id) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-secondary w-100" onclick="return confirm('Mark as Customer Denied?')">
                                Customer Denied
                            </button>
                        </form>
                        <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#nodalForwardToProcessorModal">
                            Forward to Processor
                        </button>
                        <!-- Assign Port Modal -->
                        <div class="modal fade" id="assignPortModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.nodal-officer.assign-port', $application->id) }}">
                                        @csrf
                                        @php
                                            $appData = $application->application_data ?? [];
                                            $portSelection = $appData['port_selection'] ?? [];
                                            $userSelectedPortCapacity = $application->getEffectivePortCapacity() ?? ($portSelection['capacity'] ?? '');
                                        @endphp
                                        <div class="modal-header">
                                            <h5 class="modal-title">Assign Port</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="assigned_port_capacity" class="form-label">Port Capacity <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="assigned_port_capacity" name="assigned_port_capacity" value="{{ $userSelectedPortCapacity }}" required readonly style="background-color: #e9ecef;">
                                                @if($userSelectedPortCapacity)
                                                    <small class="text-muted">Pre-filled from user's application</small>
                                                @endif
                                            </div>
                                            <div class="mb-3">
                                                <label for="assigned_port_number" class="form-label">Port Number</label>
                                                <input type="text" class="form-control" id="assigned_port_number" name="assigned_port_number" placeholder="Enter port number">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success">Assign Port</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- Hold Modal -->
                        <div class="modal fade" id="holdModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.nodal-officer.hold', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Hold Application</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="rejection_reason" class="form-label">Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Hold</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- Not Feasible Modal -->
                        <div class="modal fade" id="notFeasibleModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.nodal-officer.not-feasible', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Mark as Not Feasible</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="rejection_reason" class="form-label">Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Mark as Not Feasible</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- Forward to Processor Modal -->
                        <div class="modal fade" id="nodalForwardToProcessorModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.nodal-officer.forward-to-processor', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Forward to Processor</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="rejection_reason" class="form-label">Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Forward to Processor</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- IX Tech Team Actions --}}
                    @if($roleToUse === 'ix_tech_team' && $application->isVisibleToIxTechTeam())
                        <button type="button" class="btn btn-success w-100 mb-3" data-bs-toggle="modal" data-bs-target="#assignIpModal">
                            Assign IP (Make Live)
                        </button>
                        <!-- Assign IP Modal -->
                        <div class="modal fade" id="assignIpModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.ix-tech-team.assign-ip', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Assign IP and Make Live</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="assigned_ip" class="form-label">Assigned IP <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="assigned_ip" name="assigned_ip" placeholder="Enter IP address" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="customer_id" class="form-label">Customer ID <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="customer_id" name="customer_id" value="{{ $application->user->registrationid ?? '' }}" required readonly style="background-color: #e9ecef;">
                                                <small class="text-muted">Auto-filled from user's registration ID</small>
                                            </div>
                                            <div class="mb-3">
                                                <label for="membership_id" class="form-label">Membership ID <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="membership_id" name="membership_id" value="{{ $application->application_id }}" required readonly style="background-color: #e9ecef;">
                                                <small class="text-muted">Auto-filled from application ID</small>
                                            </div>
                                            <div class="mb-3">
                                                <label for="service_activation_date" class="form-label">Service Activation Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="service_activation_date" name="service_activation_date" value="{{ old('service_activation_date', date('Y-m-d')) }}" required>
                                                <small class="text-muted">This date will be used to calculate billing cycle and payment reminders</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success">Assign IP & Make Live</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Invoices in Actions tab: show to all admins for IX applications; only ix_account sees Generate / Edit / Mark Paid / Delete --}}
                    @if($application->application_type === 'IX')
                        {{-- Generate Invoice: only for ix_account --}}
                        @if(isset($canManageInvoices) && $canManageInvoices && $roleToUse === 'ix_account' && $application->isVisibleToIxAccount() && $application->is_active)
                            @if(isset($canGenerateInvoice) && $canGenerateInvoice)
                                <a href="{{ route('admin.applications.ix-account.generate-invoice', $application->id) }}" class="btn btn-primary w-100 mb-3">
                                    <i class="bi bi-receipt"></i> Generate Invoice
                                </a>
                            @elseif(isset($invoiceGenerationMessage))
                                <div class="alert alert-info mb-3 text-blue">
                                    <small><i class="bi bi-info-circle"></i> {{ $invoiceGenerationMessage }}</small>
                                </div>
                            @endif
                        @endif

                        {{-- Payment Summary: for all admins (only active invoices: exclude cancelled and credit note) --}}
                            @php
                                $allInvoices = $application->invoices;
                                $activeInvoices = $allInvoices->filter(fn($inv) => !$inv->isCancelledOrHasCreditNote());
                                // Calculate total paid: sum of actual paid amounts from active invoices only
                                $totalPaid = $activeInvoices->sum(function($inv) {
                                    return $inv->paid_amount ?? 0;
                                });
                                // Calculate total carry forward: sum of forwarded amounts from active invoices only
                                $totalCarryForward = $activeInvoices->sum(function($inv) {
                                    return $inv->forwarded_amount ?? 0;
                                });
                                // Total balance (remaining): sum of current balance from active invoices only
                                $totalBalance = $activeInvoices->sum(function($inv) {
                                    return $inv->balance_amount ?? ($inv->total_amount - ($inv->paid_amount ?? 0));
                                });
                                // Total invoice amount from active invoices only (excluding carry forward amounts that were added)
                                $totalInvoiceAmount = $activeInvoices->sum(function($inv) {
                                    $invoiceTotal = $inv->total_amount ?? 0;
                                    $carryForwardAdded = 0;
                                    if ($inv->line_items && is_array($inv->line_items)) {
                                        foreach ($inv->line_items as $item) {
                                            if (isset($item['is_carry_forward']) && $item['is_carry_forward']) {
                                                $carryForwardAdded += (float) ($item['amount'] ?? 0);
                                            }
                                        }
                                    }
                                    return $invoiceTotal - $carryForwardAdded;
                                });
                            @endphp
                            <div class="card border-info shadow-sm mb-3" style="border-width: 2px;">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0 text-capitalize fw-semibold"><i class="bi bi-calculator"></i> Payment Summary</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Total Invoice Amount</small>
                                            <strong class="text-dark">₹{{ number_format($totalInvoiceAmount, 2) }}</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Total Paid</small>
                                            <strong class="text-success">₹{{ number_format($totalPaid, 2) }}</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Total Balance</small>
                                            <strong class="text-danger">₹{{ number_format($totalBalance, 2) }}</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Total Carry Forward</small>
                                            <strong class="text-warning">₹{{ number_format($totalCarryForward, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Invoices History --}}
                            @php
                                $allInvoices = $application->invoices()->with('generatedBy')->latest()->get();
                                $latestInvoice = $allInvoices->first();
                                $totalInvoices = $allInvoices->count();
                            @endphp
                            
                            {{-- Recent Invoice Section --}}
                            @if($latestInvoice)
                                <div class="mt-3">
                                    <h6 class="mb-2">Recent Invoice:</h6>
                                    <div class="list-group list-group-flush">
                                        @php
                                            $invoice = $latestInvoice;
                                        @endphp
                                        <div class="list-group-item px-0 py-2 small">
                                            <div class="d-flex justify-content-between align-items-start w-100">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span>
                                                            {{-- Invoice Number as Clickable Link --}}
                                                            <a href="{{ route('admin.applications.invoice.download', $invoice->id) }}" 
                                                               class="text-decoration-none fw-bold text-primary" 
                                                               target="_blank"
                                                               title="Download Invoice PDF">
                                                                <i class="bi bi-file-earmark-pdf"></i> {{ $invoice->invoice_number }}
                                                            </a>
                                                            @if($invoice->billing_period)
                                                                <span class="text-muted"> - {{ $invoice->billing_period }}</span>
                                                            @endif
                                                        </span>
                                                        <span class="badge bg-{{ $invoice->isCancelled() ? 'secondary' : ($invoice->hasCreditNote() ? 'info' : ($invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : 'warning'))) }}">
                                                            {{ $invoice->isCancelled() ? 'Cancelled' : ($invoice->hasCreditNote() ? 'Credit Note' : ucfirst($invoice->status)) }}
                                                        </span>
                                                    </div>
                                                    <div class="text-muted mb-2">
                                                        <div class="row g-2 mb-2">
                                                            <div class="col-12">
                                                                <strong>Invoice Amount:</strong> ₹{{ number_format($invoice->total_amount, 2) }}
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="d-block text-muted">Paid Amount</small>
                                                                <strong class="text-success">₹{{ number_format($invoice->paid_amount ?? 0, 2) }}</strong>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="d-block text-muted">Balance Amount</small>
                                                                <strong class="text-danger">₹{{ number_format($invoice->balance_amount ?? $invoice->total_amount, 2) }}</strong>
                                                            </div>
                                                            @php
                                                                // Check if this invoice has carry forward FROM it (forwarded to another invoice)
                                                                $hasForwardedFrom = !$invoice->isCancelledOrHasCreditNote() && $invoice->has_carry_forward && $invoice->forwarded_amount > 0;
                                                                
                                                                // Check if this invoice has carry forward TO it (added from previous invoices)
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
                                                            @endphp
                                                            @if($hasForwardedFrom)
                                                                <div class="col-12">
                                                                    <small class="d-block text-muted">Carry Forward Amount</small>
                                                                    <strong class="text-warning">₹{{ number_format($invoice->forwarded_amount, 2) }}</strong>
                                                                    @if($invoice->forwarded_to_invoice_date)
                                                                        <br><small class="text-muted">Forwarded on: {{ $invoice->forwarded_to_invoice_date->format('d M Y') }}</small>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                            @if($hasForwardedTo)
                                                                <div class="col-12">
                                                                    <small class="d-block text-muted">Added Forwarded Amount</small>
                                                                    <strong class="text-info">₹{{ number_format($forwardedToAmount, 2) }}</strong>
                                                                    <br><small class="text-muted">(Amount forwarded from previous invoice(s))</small>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <hr class="my-2">
                                                        <div>
                                                            <strong>Due Date:</strong> {{ $invoice->due_date->format('d M Y') }} <br>
                                                            @if($invoice->generatedBy)
                                                                <strong>Generated by:</strong> {{ $invoice->generatedBy->name }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <hr class="my-3">
                                                    {{-- Download for all; Cancel/Credit Note only for ix_account when allowed; no Edit/Delete --}}
                                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                                        @if($invoice->hasCreditNote())
                                                            <a href="{{ route('admin.applications.invoice.download', ['invoice' => $invoice->id, 'type' => 'credit_note']) }}" 
                                                               class="btn btn-sm btn-info" 
                                                               target="_blank"
                                                               title="Download Credit Note PDF">
                                                                <i class="bi bi-download"></i> Download Credit Note PDF
                                                            </a>
                                                            <a href="{{ route('admin.applications.invoice.download', ['invoice' => $invoice->id, 'type' => 'invoice']) }}" 
                                                               class="btn btn-sm btn-success" 
                                                               target="_blank"
                                                               title="Download Original Invoice PDF">
                                                                <i class="bi bi-download"></i> Download Invoice PDF
                                                            </a>
                                                        @else
                                                            <a href="{{ route('admin.applications.invoice.download', $invoice->id) }}" 
                                                               class="btn btn-sm btn-success" 
                                                               target="_blank"
                                                               title="Download Invoice PDF">
                                                                <i class="bi bi-download"></i> Download PDF
                                                            </a>
                                                        @endif
                                                        @if(isset($canManageInvoices) && $canManageInvoices && $roleToUse === 'ix_account' && !$invoice->isCancelledOrHasCreditNote())
                                                            @if($invoice->status === 'paid')
                                                                <button type="button" class="btn btn-sm btn-warning" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#markUnpaidModal{{ $invoice->id }}" 
                                                                        title="Mark Invoice as Unpaid">
                                                                    <i class="bi bi-x-circle"></i> Mark Unpaid
                                                                </button>
                                                            @else
                                                                <button type="button" class="btn btn-sm btn-success" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#markPaidModal{{ $invoice->id }}" 
                                                                        title="Mark Invoice as Paid">
                                                                    <i class="bi bi-check-circle"></i> Mark Paid
                                                                </button>
                                                            @endif
                                                            @if($invoice->canBeCancelled())
                                                                <button type="button" class="btn btn-sm btn-warning" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#cancelInvoiceModal{{ $invoice->id }}" 
                                                                        title="Cancel Invoice (within 24 hours)">
                                                                    <i class="bi bi-x-octagon"></i> Cancel Invoice
                                                                </button>
                                                            @endif
                                                            @if($invoice->canGenerateCreditNote())
                                                                <button type="button" class="btn btn-sm btn-info" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#creditNoteInvoiceModal{{ $invoice->id }}" 
                                                                        title="Generate Credit Note (after 24 hours)">
                                                                    <i class="bi bi-file-earmark-text"></i> Generate Credit Note
                                                                </button>
                                                            @endif
                                                        @endif
                                                    </div>
                                                    
                                                    @if(isset($canManageInvoices) && $canManageInvoices && $roleToUse === 'ix_account')
                                                    {{-- Cancel Invoice Modal (within 24h) --}}
                                                    @if($invoice->canBeCancelled())
                                                    <div class="modal fade" id="cancelInvoiceModal{{ $invoice->id }}" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-warning text-dark">
                                                                    <h5 class="modal-title">Cancel Invoice</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form method="POST" action="{{ route('admin.applications.invoice.cancel', $invoice->id) }}">
                                                                    @csrf
                                                                    <div class="modal-body">
                                                                        <p>Cancel invoice <strong>{{ $invoice->invoice_number }}</strong>? This is only allowed within 24 hours of generation. The invoice PDF will be renamed to a cancelled copy and the user will receive a cancellation email.</p>
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Cancellation Reason Code <span class="text-danger">*</span></label>
                                                                            <select name="cnl_rsn" class="form-select" required>
                                                                                <option value="1">1 - Duplicate</option>
                                                                                <option value="2">2 - Order cancelled</option>
                                                                                <option value="3">3 - Data entry mistake</option>
                                                                                <option value="4">4 - Others</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Remarks <span class="text-danger">*</span></label>
                                                                            <textarea name="cnl_rem" class="form-control" rows="2" required placeholder="Brief reason for cancellation"></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                        <button type="submit" class="btn btn-warning">
                                                                            <i class="bi bi-x-octagon"></i> Cancel Invoice
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endif
                                                    {{-- Generate Credit Note Modal (after 24h) --}}
                                                    @if($invoice->canGenerateCreditNote())
                                                    <div class="modal fade" id="creditNoteInvoiceModal{{ $invoice->id }}" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-info text-white">
                                                                    <h5 class="modal-title">Generate Credit Note</h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form method="POST" action="{{ route('admin.applications.invoice.credit-note', $invoice->id) }}">
                                                                    @csrf
                                                                    <div class="modal-body">
                                                                        <p>Generate a credit note for invoice <strong>{{ $invoice->invoice_number }}</strong>? This will create a credit note with the same invoice number and send the credit note PDF to the user. A new invoice can be generated for the same period after this.</p>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                        <button type="submit" class="btn btn-info">
                                                                            <i class="bi bi-file-earmark-text"></i> Generate Credit Note
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endif
                                                    
                                                    @if($invoice->status === 'paid' && $invoice->manual_payment_id)
                                                        <div class="mt-2 text-success small">
                                                            <i class="bi bi-check-circle"></i> Payment ID: {{ $invoice->manual_payment_id }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            {{-- Mark Paid Modal with Amount and Carry Forward Options --}}
                                            <div class="modal fade" id="markPaidModal{{ $invoice->id }}" tabindex="-1" data-base-amount="{{ $invoice->amount }}">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content border-c-blue">
                                                        <div class="modal-header theme-bg-blue">
                                                            <h5 class="modal-title text-white" style="color: #ffffff !important;">Mark Invoice as Paid</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST" action="{{ route('admin.applications.invoice.mark-paid', $invoice->id) }}" class="theme-forms" enctype="multipart/form-data">
                                                            @csrf
                                                            <div class="modal-body">
                                                                <div class="alert alert-info mb-3">
                                                                    <strong>Invoice Amount:</strong> ₹{{ number_format($invoice->total_amount, 2) }}
                                                                    @if($invoice->balance_amount > 0)
                                                                        <br><strong>Balance Amount:</strong> ₹{{ number_format($invoice->balance_amount, 2) }}
                                                                    @endif
                                                                    <br><strong>Base Amount:</strong> ₹{{ number_format($invoice->amount, 2) }}
                                                                </div>
                                                                <div class="row g-3">
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Payment ID <span class="text-danger">*</span></label>
                                                                        <input type="text" name="payment_id" class="form-control" required placeholder="Reference / UTR / Transaction ID">
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">TDS Amount (₹) <span class="text-danger">*</span></label>
                                                                        <input type="number" name="tds_amount" id="tds_amount_mark_paid_{{ $invoice->id }}" class="form-control" step="0.01" min="0" value="{{ $invoice->tds_amount ?? 0 }}" required placeholder="0.00">
                                                                        <small class="text-muted">TDS Amount (max 10% of base amount: ₹{{ number_format(($invoice->amount * 10) / 100, 2) }})</small>
                                                                        <div class="invalid-feedback" id="tds_amount_error_mark_paid_{{ $invoice->id }}" style="display: none;"></div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Amount Paid (₹) <span class="text-danger">*</span></label>
                                                                        <input type="number" name="amount" class="form-control" step="0.01" min="0" max="{{ $invoice->total_amount }}" value="{{ $invoice->balance_amount > 0 ? $invoice->balance_amount : $invoice->total_amount }}" required placeholder="0.00">
                                                                        <small class="text-muted">Enter the amount received (can be partial or full)</small>
                                                                    </div>
                                                                    <div class="col-md-12">
                                                                        <label class="form-label">Carry Forward to Next Invoice</label>
                                                                        <select name="carry_forward" class="form-select">
                                                                            <option value="0">No - Do not carry forward</option>
                                                                            <option value="1">Yes - Carry forward remaining amount</option>
                                                                        </select>
                                                                        <small class="text-muted">If partial payment, choose whether to carry forward balance</small>
                                                                    </div>

                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Upload Payment Receipt</label>
                                                                        <input type="file" name="payment_receipt" class="form-control" accept="application/pdf,.pdf">
                                                                        <small class="text-muted">Upload the payment receipt (PDF only)</small>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Upload TDS Certificate</label>
                                                                        <input type="file" name="tds_certificate" class="form-control" accept="application/pdf,.pdf">
                                                                        <small class="text-muted">Upload the TDS certificate (PDF only)</small>
                                                                    </div>
                                                                    <div class="col-12">
                                                                        <label class="form-label">Notes (Optional)</label>
                                                                        <textarea name="notes" class="form-control" rows="3" placeholder="Any remarks or additional information about this payment..."></textarea>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-success">
                                                                    <i class="bi bi-check-circle"></i> Mark as Paid
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Mark Unpaid Modal --}}
                                            <div class="modal fade" id="markUnpaidModal{{ $invoice->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Mark Invoice as Unpaid</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST" action="{{ route('admin.applications.invoice.mark-unpaid', $invoice->id) }}">
                                                            @csrf
                                                            <div class="modal-body">
                                                                <div class="alert alert-warning">
                                                                    <strong>Warning:</strong> This will reset all payment information for this invoice. Are you sure you want to continue?
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-warning">
                                                                    <i class="bi bi-x-circle"></i> Mark as Unpaid
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                                    @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- All Invoices Section (Hidden by Default) --}}
                            @if($totalInvoices > 0)
                            <div class="mt-3 text-center">
                                <button type="button" class="btn btn-link text-primary p-0" id="toggleAllInvoicesBtn" onclick="toggleAllInvoices()" data-total="{{ $totalInvoices }}">
                                    <i class="bi bi-chevron-down" id="allInvoicesIcon"></i> View All Invoices ({{ $totalInvoices }} total)
                                </button>
                            </div>
                            
                            <div class="mt-3" id="allInvoicesSection" style="display: none;">
                                <h6 class="mb-2">All Invoices:</h6>
                                @if($totalInvoices > 0)
                                <div class="list-group list-group-flush">
                                    @foreach($allInvoices as $invoice)
                                    <div class="list-group-item px-0 py-2 small">
                                                <div class="d-flex justify-content-between align-items-start w-100">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <span>
                                                                {{-- Invoice Number as Clickable Link --}}
                                                                <a href="{{ route('admin.applications.invoice.download', $invoice->id) }}" 
                                                                   class="text-decoration-none fw-bold text-primary" 
                                                                   target="_blank"
                                                                   title="Download Invoice PDF">
                                                                    <i class="bi bi-file-earmark-pdf"></i> {{ $invoice->invoice_number }}
                                                                </a>
                                                                @if($invoice->billing_period)
                                                                    <span class="text-muted"> - {{ $invoice->billing_period }}</span>
                                                                @endif
                                                            </span>
                                                            <span class="badge bg-{{ $invoice->isCancelled() ? 'secondary' : ($invoice->hasCreditNote() ? 'info' : ($invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : 'warning'))) }}">
                                                                {{ $invoice->isCancelled() ? 'Cancelled' : ($invoice->hasCreditNote() ? 'Credit Note' : ucfirst($invoice->status)) }}
                                                            </span>
                                                        </div>
                                                        <div class="text-muted mb-2">
                                                            <div class="row g-2 mb-2">
                                                                <div class="col-12">
                                                                    <strong>Invoice Amount:</strong> ₹{{ number_format($invoice->total_amount, 2) }}
                                                                </div>
                                                                <div class="col-6">
                                                                    <small class="d-block text-muted">Paid Amount</small>
                                                                    <strong class="text-success">₹{{ number_format($invoice->paid_amount ?? 0, 2) }}</strong>
                                                                </div>
                                                                <div class="col-6">
                                                                    <small class="d-block text-muted">Balance Amount</small>
                                                                    <strong class="text-danger">₹{{ number_format($invoice->balance_amount ?? $invoice->total_amount, 2) }}</strong>
                                                                </div>
                                                                @php
                                                                    // Check if this invoice has carry forward FROM it (forwarded to another invoice) - only for active invoices
                                                                    $hasForwardedFrom = !$invoice->isCancelledOrHasCreditNote() && $invoice->has_carry_forward && $invoice->forwarded_amount > 0;
                                                                    
                                                                    // Check if this invoice has carry forward TO it (added from previous invoices)
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
                                                                @endphp
                                                                @if($hasForwardedFrom)
                                                                    <div class="col-12">
                                                                        <small class="d-block text-muted">Carry Forward Amount</small>
                                                                        <strong class="text-warning">₹{{ number_format($invoice->forwarded_amount, 2) }}</strong>
                                                                        @if($invoice->forwarded_to_invoice_date)
                                                                            <br><small class="text-muted">Forwarded on: {{ $invoice->forwarded_to_invoice_date->format('d M Y') }}</small>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                                @if($hasForwardedTo)
                                                                    <div class="col-12">
                                                                        <small class="d-block text-muted">Added Forwarded Amount</small>
                                                                        <strong class="text-info">₹{{ number_format($forwardedToAmount, 2) }}</strong>
                                                                        <br><small class="text-muted">(Amount forwarded from previous invoice(s))</small>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <hr class="my-2">
                                                            <div>
                                                                <strong>Due Date:</strong> {{ $invoice->due_date->format('d M Y') }} <br>
                                                                @if($invoice->generatedBy)
                                                                    <strong>Generated by:</strong> {{ $invoice->generatedBy->name }}
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <hr class="my-3">
                                                        {{-- Download for all; Cancel/Credit Note only for ix_account when allowed; no Edit/Delete --}}
                                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                                            @if($invoice->hasCreditNote())
                                                                <a href="{{ route('admin.applications.invoice.download', ['invoice' => $invoice->id, 'type' => 'credit_note']) }}" 
                                                                   class="btn btn-sm btn-info" 
                                                                   target="_blank"
                                                                   title="Download Credit Note PDF">
                                                                    <i class="bi bi-download"></i> Download Credit Note PDF
                                                                </a>
                                                                <a href="{{ route('admin.applications.invoice.download', ['invoice' => $invoice->id, 'type' => 'invoice']) }}" 
                                                                   class="btn btn-sm btn-success" 
                                                                   target="_blank"
                                                                   title="Download Original Invoice PDF">
                                                                    <i class="bi bi-download"></i> Download Invoice PDF
                                                                </a>
                                                            @else
                                                                <a href="{{ route('admin.applications.invoice.download', $invoice->id) }}" 
                                                                   class="btn btn-sm btn-success" 
                                                                   target="_blank"
                                                                   title="Download Invoice PDF">
                                                                    <i class="bi bi-download"></i> Download PDF
                                                                </a>
                                                            @endif
                                                            @if(isset($canManageInvoices) && $canManageInvoices && $roleToUse === 'ix_account' && !$invoice->isCancelledOrHasCreditNote())
                                                                @php $isLatestInvoice = $invoice->id === $latestInvoice->id; @endphp
                                                                @if($invoice->status === 'paid')
                                                                    @if($isLatestInvoice)
                                                                        <button type="button" class="btn btn-sm btn-warning" 
                                                                                data-bs-toggle="modal" 
                                                                                data-bs-target="#markUnpaidModalAll{{ $invoice->id }}" 
                                                                                title="Mark Invoice as Unpaid">
                                                                            <i class="bi bi-x-circle"></i> Mark Unpaid
                                                                        </button>
                                                                    @endif
                                                                @else
                                                                    <button type="button" class="btn btn-sm btn-success" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#markPaidModalAll{{ $invoice->id }}" 
                                                                            title="Mark Invoice as Paid">
                                                                        <i class="bi bi-check-circle"></i> Mark Paid
                                                                    </button>
                                                                @endif
                                                                @if($invoice->canBeCancelled())
                                                                    <button type="button" class="btn btn-sm btn-warning" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#cancelInvoiceModalAll{{ $invoice->id }}" 
                                                                            title="Cancel Invoice (within 24 hours)">
                                                                        <i class="bi bi-x-octagon"></i> Cancel Invoice
                                                                    </button>
                                                                @endif
                                                                @if($invoice->canGenerateCreditNote())
                                                                    <button type="button" class="btn btn-sm btn-info" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#creditNoteInvoiceModalAll{{ $invoice->id }}" 
                                                                            title="Generate Credit Note (after 24 hours)">
                                                                        <i class="bi bi-file-earmark-text"></i> Generate Credit Note
                                                                    </button>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            @if(isset($canManageInvoices) && $canManageInvoices && $roleToUse === 'ix_account')
                                            {{-- Cancel Invoice Modal (All Invoices section) --}}
                                            @if($invoice->canBeCancelled())
                                            <div class="modal fade" id="cancelInvoiceModalAll{{ $invoice->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-warning text-dark">
                                                            <h5 class="modal-title">Cancel Invoice</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST" action="{{ route('admin.applications.invoice.cancel', $invoice->id) }}">
                                                            @csrf
                                                            <div class="modal-body">
                                                                <p>Cancel invoice <strong>{{ $invoice->invoice_number }}</strong>? This is only allowed within 24 hours of generation.</p>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Cancellation Reason Code <span class="text-danger">*</span></label>
                                                                    <select name="cnl_rsn" class="form-select" required>
                                                                        <option value="1">1 - Duplicate</option>
                                                                        <option value="2">2 - Order cancelled</option>
                                                                        <option value="3">3 - Data entry mistake</option>
                                                                        <option value="4">4 - Others</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Remarks <span class="text-danger">*</span></label>
                                                                    <textarea name="cnl_rem" class="form-control" rows="2" required placeholder="Brief reason for cancellation"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-warning">
                                                                    <i class="bi bi-x-octagon"></i> Cancel Invoice
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                            {{-- Generate Credit Note Modal (All Invoices section) --}}
                                            @if($invoice->canGenerateCreditNote())
                                            <div class="modal fade" id="creditNoteInvoiceModalAll{{ $invoice->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-info text-white">
                                                            <h5 class="modal-title">Generate Credit Note</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST" action="{{ route('admin.applications.invoice.credit-note', $invoice->id) }}">
                                                            @csrf
                                                            <div class="modal-body">
                                                                <p>Generate a credit note for invoice <strong>{{ $invoice->invoice_number }}</strong>?</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-info">
                                                                    <i class="bi bi-file-earmark-text"></i> Generate Credit Note
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                            
                                            {{-- Mark Paid Modal (All Invoices section) --}}
                                            <div class="modal fade" id="markPaidModalAll{{ $invoice->id }}" tabindex="-1" data-base-amount="{{ $invoice->amount }}">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content border-c-blue">
                                                        <div class="modal-header theme-bg-blue">
                                                            <h5 class="modal-title text-white" style="color: #ffffff !important;">Mark Invoice as Paid</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST" action="{{ route('admin.applications.invoice.mark-paid', $invoice->id) }}" class="theme-forms" enctype="multipart/form-data">
                                                            @csrf
                                                            <div class="modal-body">
                                                                <div class="alert alert-info mb-3">
                                                                    <strong>Invoice Amount:</strong> ₹{{ number_format($invoice->total_amount, 2) }}
                                                                    @if($invoice->balance_amount > 0)
                                                                        <br><strong>Balance Amount:</strong> ₹{{ number_format($invoice->balance_amount, 2) }}
                                                                    @endif
                                                                    <br><strong>Base Amount:</strong> ₹{{ number_format($invoice->amount, 2) }}
                                                                </div>
                                                                <div class="row g-3">
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Payment ID <span class="text-danger">*</span></label>
                                                                        <input type="text" name="payment_id" class="form-control" required placeholder="Reference / UTR / Transaction ID">
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">TDS Amount (₹) <span class="text-danger">*</span></label>
                                                                        <input type="number" name="tds_amount" id="tds_amount_mark_paid_all_{{ $invoice->id }}" class="form-control" step="0.01" min="0" value="{{ $invoice->tds_amount ?? 0 }}" required placeholder="0.00">
                                                                        <small class="text-muted">TDS Amount (max 10% of base amount: ₹{{ number_format(($invoice->amount * 10) / 100, 2) }})</small>
                                                                        <div class="invalid-feedback" id="tds_amount_error_mark_paid_all_{{ $invoice->id }}" style="display: none;"></div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Amount Paid (₹) <span class="text-danger">*</span></label>
                                                                        <input type="number" name="amount" class="form-control" step="0.01" min="0" max="{{ $invoice->total_amount }}" value="{{ $invoice->balance_amount > 0 ? $invoice->balance_amount : $invoice->total_amount }}" required placeholder="0.00">
                                                                        <small class="text-muted">Enter the amount received (can be partial or full)</small>
                                                                    </div>
                                                                    <div class="col-md-12">
                                                                        <label class="form-label">Carry Forward to Next Invoice</label>
                                                                        <select name="carry_forward" class="form-select">
                                                                            <option value="0">No - Do not carry forward</option>
                                                                            <option value="1">Yes - Carry forward remaining amount</option>
                                                                        </select>
                                                                        <small class="text-muted">If partial payment, choose whether to carry forward balance</small>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Upload Payment Receipt</label>
                                                                        <input type="file" name="payment_receipt" class="form-control" accept="application/pdf,.pdf">
                                                                        <small class="text-muted">Upload the payment receipt (PDF only)</small>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Upload TDS Certificate</label>
                                                                        <input type="file" name="tds_certificate" class="form-control" accept="application/pdf,.pdf">
                                                                        <small class="text-muted">Upload the TDS certificate (PDF only)</small>
                                                                    </div>
                                                                    <div class="col-12">
                                                                        <label class="form-label">Notes (Optional)</label>
                                                                        <textarea name="notes" class="form-control" rows="3" placeholder="Any remarks or additional information about this payment..."></textarea>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-success">
                                                                    <i class="bi bi-check-circle"></i> Mark as Paid
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            {{-- Mark Unpaid Modal (All Invoices section) --}}
                                            <div class="modal fade" id="markUnpaidModalAll{{ $invoice->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Mark Invoice as Unpaid</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST" action="{{ route('admin.applications.invoice.mark-unpaid', $invoice->id) }}">
                                                            @csrf
                                                            <div class="modal-body">
                                                                <div class="alert alert-warning">
                                                                    <strong>Warning:</strong> This will reset all payment information for this invoice. Are you sure you want to continue?
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-warning">
                                                                    <i class="bi bi-x-circle"></i> Mark as Unpaid
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                    @endforeach
                                </div>
                                @else
                                <div class="alert alert-info mb-0">
                                    <small>No invoices found for this application.</small>
                                </div>
                                @endif
                            </div>
                            @endif
                            @endif
                    @endif

                {{-- Legacy Workflow Actions (for backward compatibility) --}}
                @if($application->application_type !== 'IX')
                    @if($roleToUse === 'processor' && $application->isVisibleToProcessor())
                        <form method="POST" action="{{ route('admin.applications.approve-to-finance', $application->id) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to approve this application and forward it to Finance?')">
                                Approve to Finance
                            </button>
                        </form>
                    @endif

                    @if($roleToUse === 'finance' && $application->isVisibleToFinance())
                        <form method="POST" action="{{ route('admin.applications.approve-to-technical', $application->id) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to approve this application and forward it to Technical?')">
                                Approve to Technical
                            </button>
                        </form>

                        <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#rejectToProcessorModal">
                            Send Back to Processor
                        </button>

                        <!-- Reject to Processor Modal -->
                        <div class="modal fade" id="rejectToProcessorModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.send-back-to-processor', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Send Back to Processor</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10" placeholder="Please provide a detailed reason for sending this application back to Processor..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Send Back</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($roleToUse === 'technical' && $application->isVisibleToTechnical())
                        <form method="POST" action="{{ route('admin.applications.approve', $application->id) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to approve this application? This is the final approval.')">
                                Approve Application
                            </button>
                        </form>

                        <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#rejectToFinanceModal">
                            Send Back to Finance
                        </button>

                        <!-- Reject to Finance Modal -->
                        <div class="modal fade" id="rejectToFinanceModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.send-back-to-finance', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Send Back to Finance</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10" placeholder="Please provide a detailed reason for sending this application back to Finance..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Send Back</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                @if($application->application_type === 'IX' && !in_array($roleToUse, ['ix_processor', 'ix_legal', 'ix_head', 'ceo', 'nodal_officer', 'ix_tech_team', 'ix_account']))
                    <div class="alert alert-info mb-3">
                        <small>Please select an IX workflow role from the dropdown to take actions on this application.</small>
                    </div>
                @elseif($application->application_type !== 'IX' && !in_array($roleToUse, ['processor', 'finance', 'technical']))
                    <div class="alert alert-info mb-3">
                        <small>Please select a role from the dropdown to take actions on this application.</small>
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

{{-- New row: col-md-12 with three cards in a row (Registration, Authorized Rep, KYC) - same for account and other admins --}}
                                                                </div><div class="row mb-4">
    <div class="col-md-12">
        <div class="row g-4">
            @if($application->application_type === 'IX' && $application->registration_details)
            <div class="col-md-4">
                <div class="card border-c-blue shadow-sm h-100" style="border-radius: 16px;">
                    <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                        <h5 class="mb-0 text-capitalize fw-semibold">Registration Details</h5>
                    </div>
                    <div class="card-body p-3">
                        @php $regDetails = $application->registration_details; @endphp
                        <div class="row app-details g-2">
                            <div class="col-6"><label class="text-muted small mb-1">Registration ID</label><div style="color: #2c3e50; font-weight: 500;">{{ $regDetails['registration_id'] ?? 'N/A' }}</div></div>
                            <div class="col-6"><label class="text-muted small mb-1">Registration Type</label><div style="color: #2c3e50;">{{ ucfirst($regDetails['registration_type'] ?? 'N/A') }}</div></div>
                            <div class="col-6"><label class="text-muted small mb-1">Full Name</label><div style="color: #2c3e50;">{{ $regDetails['fullname'] ?? 'N/A' }}</div></div>
                            <div class="col-6"><label class="text-muted small mb-1">PAN Card</label><div style="color: #2c3e50;">{{ $regDetails['pancardno'] ?? 'N/A' }}</div></div>
                            <div class="col-6"><label class="text-muted small mb-1">Email</label><div style="color: #2c3e50;">{{ $regDetails['email'] ?? 'N/A' }}</div></div>
                            <div class="col-6"><label class="text-muted small mb-1">Mobile</label><div style="color: #2c3e50;">{{ $regDetails['mobile'] ?? 'N/A' }}</div></div>
                            <div class="col-6"><label class="text-muted small mb-1">Date of Birth</label><div style="color: #2c3e50;">{{ $regDetails['dateofbirth'] ?? 'N/A' }}</div></div>
                            <div class="col-6"><label class="text-muted small mb-1">Registration Date</label><div style="color: #2c3e50;">{{ $regDetails['registrationdate'] ?? 'N/A' }}</div></div>
                            <div class="col-12"><div class="d-flex gap-2 flex-wrap"><span class="badge bg-{{ ($regDetails['pan_verified'] ?? false) ? 'success' : 'danger' }}">PAN {{ ($regDetails['pan_verified'] ?? false) ? 'Verified' : 'Not Verified' }}</span><span class="badge bg-{{ ($regDetails['email_verified'] ?? false) ? 'success' : 'danger' }}">Email {{ ($regDetails['email_verified'] ?? false) ? 'Verified' : 'Not Verified' }}</span><span class="badge bg-{{ ($regDetails['mobile_verified'] ?? false) ? 'success' : 'danger' }}">Mobile {{ ($regDetails['mobile_verified'] ?? false) ? 'Verified' : 'Not Verified' }}</span></div></div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($application->application_type === 'IX' && $application->authorized_representative_details)
            <div class="col-md-4">
                <div class="card border-c-blue shadow-sm h-100" style="border-radius: 16px;">
                    <div class="card-header theme-bg-blue text-dark" style="border-radius: 16px 16px 0 0;">
                        <h5 class="mb-0 text-capitalize fw-semibold">Authorized Representative</h5>
                    </div>
                    <div class="card-body p-3">
                        @php $repDetails = $application->authorized_representative_details; @endphp
                        <div class="row app-details g-2">
                            <div class="col-6"><label class="text-muted small mb-1">Name</label><div style="color: #2c3e50; font-weight: 500;">{{ $repDetails['name'] ?? 'N/A' }}</div></div>
                            <div class="col-6"><label class="text-muted small mb-1">PAN Card</label><div style="color: #2c3e50;">{{ $repDetails['pan'] ?? 'N/A' }}</div></div>
                            <div class="col-6"><label class="text-muted small mb-1">Date of Birth</label><div style="color: #2c3e50;">{{ $repDetails['dob'] ?? 'N/A' }}</div></div>
                            <div class="col-6"><label class="text-muted small mb-1">Email</label><div style="color: #2c3e50;">{{ $repDetails['email'] ?? 'N/A' }}</div></div>
                            <div class="col-6"><label class="text-muted small mb-1">Mobile</label><div style="color: #2c3e50;">{{ $repDetails['mobile'] ?? 'N/A' }}</div></div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($application->kyc_details)
            <div class="col-md-4">
                <div class="card border-info shadow-sm h-100" style="border-radius: 16px;">
                    <div class="card-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
                        <h5 class="mb-0 text-capitalize fw-semibold">KYC Details</h5>
                    </div>
                    <div class="card-body p-3">
                        @php $kycDetails = $application->kyc_details; @endphp
                        <div class="row g-2 small">
                            <div class="col-12"><label class="text-muted small mb-1">GSTIN</label><div style="color: #2c3e50; font-weight: 500;">{{ $kycDetails['gstin'] ?? 'N/A' }}</div>@if(isset($kycDetails['gst_verified']))<span class="badge bg-{{ $kycDetails['gst_verified'] ? 'success' : 'danger' }} mt-1">{{ $kycDetails['gst_verified'] ? 'Verified' : 'Not Verified' }}</span>@endif</div>
                            <div class="col-6"><label class="text-muted small mb-1">Is MSME</label><div><span class="badge bg-{{ ($kycDetails['is_msme'] ?? false) ? 'success' : 'secondary' }}">{{ ($kycDetails['is_msme'] ?? false) ? 'Yes' : 'No' }}</span></div></div>
                            @if($kycDetails['udyam_number'] ?? null)<div class="col-6"><label class="text-muted small mb-1">UDYAM</label><div style="color: #2c3e50;">{{ $kycDetails['udyam_number'] }}</div>@if(isset($kycDetails['udyam_verified']))<span class="badge bg-{{ $kycDetails['udyam_verified'] ? 'success' : 'danger' }} mt-1">{{ $kycDetails['udyam_verified'] ? 'Verified' : 'Not Verified' }}</span>@endif</div>@endif
                            @if($kycDetails['cin'] ?? null)<div class="col-6"><label class="text-muted small mb-1">CIN</label><div style="color: #2c3e50;">{{ $kycDetails['cin'] }}</div>@if(isset($kycDetails['mca_verified']))<span class="badge bg-{{ $kycDetails['mca_verified'] ? 'success' : 'danger' }} mt-1">{{ $kycDetails['mca_verified'] ? 'Verified' : 'Not Verified' }}</span>@endif</div>@endif
                            <div class="col-6"><label class="text-muted small mb-1">Contact Name</label><div style="color: #2c3e50;">{{ $kycDetails['contact_name'] ?? 'N/A' }}</div></div>
                            <div class="col-6"><label class="text-muted small mb-1">Contact PAN</label><div style="color: #2c3e50;">{{ $kycDetails['contact_pan'] ?? 'N/A' }}</div></div>
                            <div class="col-6"><label class="text-muted small mb-1">Contact Email</label><div style="color: #2c3e50;">{{ $kycDetails['contact_email'] ?? 'N/A' }}</div></div>
                            <div class="col-6"><label class="text-muted small mb-1">Contact Mobile</label><div style="color: #2c3e50;">{{ $kycDetails['contact_mobile'] ?? 'N/A' }}</div></div>
                            @if($kycDetails['billing_address'] ?? null)
                            <div class="col-12"><label class="text-muted small mb-1">Billing Address</label><div style="color: #2c3e50; font-size: 0.8rem;">
                                @php
                                    $ba = $kycDetails['billing_address'];
                                    if (is_string($ba)) { $d = json_decode($ba, true); if (json_last_error() === JSON_ERROR_NONE && is_array($d)) $ba = $d; }
                                    if (is_array($ba)) {
                                        if (isset($ba['address']) && !empty($ba['address'])) { echo e($ba['address']); }
                                        elseif (isset($ba['label']) && !empty($ba['label'])) { echo e($ba['label']); }
                                        else { $parts = []; foreach (['address','street','city','state','pincode','country'] as $f) { if (!empty($ba[$f])) $parts[] = $ba[$f]; } echo !empty($parts) ? e(implode(', ', $parts)) : 'N/A'; }
                                    } else { echo e($ba); }
                                @endphp
                            </div></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- GST Change History -->
@if($application->gstChangeHistory && $application->gstChangeHistory->count() > 0)
<div class="card border-info shadow mt-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0 text-capitalize fw-semibold">GST Change History</h5>
    </div>
    <div class="card-body">
        <div class="timeline">
            @foreach($application->gstChangeHistory as $history)
            <div class="mb-3 pb-3 border-bottom">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <strong>GST Changed</strong>
                        <div class="mt-2">
                            <span class="badge bg-secondary">{{ $history->old_gstin ?? 'N/A' }}</span>
                            <i class="bi bi-arrow-right mx-2"></i>
                            <span class="badge bg-primary">{{ $history->new_gstin }}</span>
                        </div>
                    </div>
                    <small class="text-muted">{{ $history->created_at->format('d M Y, h:i A') }}</small>
                </div>
                @if($history->notes)
                    <p class="mb-0 mt-2"><small>{{ $history->notes }}</small></p>
                @endif
                @if($history->changedBy)
                <p class="mb-0 mt-1">
                    <small class="text-muted">
                        Changed by: 
                        @if($history->changed_by_type === 'admin')
                            {{ $history->changedBy->name ?? 'Admin' }}
                        @elseif($history->changed_by_type === 'superadmin')
                            {{ $history->changedBy->name ?? 'SuperAdmin' }}
                        @elseif($history->changed_by_type === 'user')
                            {{ $history->changedBy->fullname ?? 'User' }}
                        @endif
                    </small>
                </p>
                @elseif($history->changed_by_type === 'user')
                <p class="mb-0 mt-1">
                    <small class="text-muted">Changed by: User</small>
                </p>
                @endif
                @if($history->ip_address)
                <p class="mb-0 mt-1">
                    <small class="text-muted">IP: {{ $history->ip_address }}</small>
                </p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@else
{{-- Legacy / IX layout (existing content) --}}
<div class="row mb-md-4">
    <div class="col-12">
        <h1>Application Details</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.applications') }}">Applications</a></li>
                <li class="breadcrumb-item active">{{ $application->application_id }}</li>
            </ol>
        </nav>
    </div>
</div>

{{-- Existing non-IRINN content --}}
@include('admin.applications.show-comprehensive', ['application' => $application])
@endif

@if($application->application_type === 'IRINN')
@php
    $data = $application->application_data ?? [];
    $gstData = $data['gst_data'] ?? [];
    $companyDetails = $gstData['company_details'] ?? [];
    $files = $data['files'] ?? [];
    $irinnStatus = $application->status ?? 'helpdesk';
    $irinnPreviousStage = $data['irinn_previous_stage'] ?? null;
@endphp

{{-- Status History below main info + actions (single full-width card) --}}
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-info shadow irinn-status-card">
            <div class="card-header theme-bg-blue text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-capitalize fw-semibold">Status History</h5>
            </div>
            <div class="card-body">
                @if($application->statusHistory && $application->statusHistory->count() > 0)
                    <div class="timeline">
                        @foreach($application->statusHistory->sortBy('created_at') as $history)
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>{{ $history->status_display }}</strong>
                                </div>
                                <small class="text-muted">{{ $history->created_at->format('d M Y, h:i A') }}</small>
                            </div>
                            @if($history->notes)
                            <p class="mb-0 mt-2"><small>{{ $history->notes }}</small></p>
                            @endif
                            @php
                                $changedBy = $history->changedBy();
                            @endphp
                            @if($changedBy)
                            <p class="mb-0 mt-1">
                                <small class="text-muted">
                                    Changed by: 
                                    @if($history->changed_by_type === 'admin')
                                        {{ $changedBy->name ?? 'Admin' }}
                                    @elseif($history->changed_by_type === 'superadmin')
                                        {{ $changedBy->name ?? 'SuperAdmin' }}
                                    @elseif($history->changed_by_type === 'user')
                                        User
                                    @endif
                                </small>
                            </p>
                            @elseif($history->changed_by_type === 'user')
                            <p class="mb-0 mt-1">
                                <small class="text-muted">Changed by: User</small>
                            </p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted">No status history available.</p>
                @endif
            </div>
        </div>
    </div>
</div>
</div>

<!-- Application Details Modal -->
<div class="modal fade" id="applicationDetailsModal" tabindex="-1" aria-labelledby="applicationDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="applicationDetailsModalLabel">
                    IRINN Application Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if(empty($data))
                    <div class="alert alert-warning">
                        <p>No application data available.</p>
                    </div>
                @else
                    <!-- Company Information -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Company Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>GSTIN:</strong> {{ $data['gstin'] ?? 'N/A' }}</p>
                                    <p><strong>Legal Name:</strong> {{ $companyDetails['legal_name'] ?? 'N/A' }}</p>
                                    <p><strong>Trade Name:</strong> {{ $companyDetails['trade_name'] ?? 'N/A' }}</p>
                                    <p><strong>PAN:</strong> {{ $companyDetails['pan'] ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>State:</strong> {{ $companyDetails['state'] ?? 'N/A' }}</p>
                                    <p><strong>Registration Date:</strong> {{ $companyDetails['registration_date'] ?? 'N/A' }}</p>
                                    <p><strong>GST Type:</strong> {{ $companyDetails['gst_type'] ?? 'N/A' }}</p>
                                    <p><strong>Company Status:</strong> {{ $companyDetails['company_status'] ?? 'N/A' }}</p>
                                </div>
                            </div>
                            @if(!empty($companyDetails['pradr']))
                            <div class="mt-3">
                                <strong>Principal Address:</strong>
                                <p class="mb-0">{{ $companyDetails['pradr']['addr'] ?? 'N/A' }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Technical Person / MR Details -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Technical Person Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> {{ $data['mr_name'] ?? 'N/A' }}</p>
                                    <p><strong>Email:</strong> {{ $data['mr_email'] ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Mobile:</strong> {{ $data['mr_mobile'] ?? 'N/A' }}</p>
                                    <p><strong>Designation:</strong> {{ $data['mr_designation'] ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- IRINN Specific Details -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">IRINN Specific Details</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Account Name:</strong> {{ $data['account_name'] ?? 'N/A' }}</p>
                            <p><strong>Dot in Domain Required:</strong> {{ isset($data['dot_in_domain_required']) && $data['dot_in_domain_required'] ? 'Yes' : 'No' }}</p>
                        </div>
                    </div>

                    <!-- IP Address Requirements -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">IP Address Requirements</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    @if(isset($data['ipv4_selected']) && $data['ipv4_selected'])
                                        <p><strong>IPv4:</strong> Selected</p>
                                        <p><strong>IPv4 Size:</strong> {{ $data['ipv4_size'] ?? 'N/A' }}</p>
                                        <p><strong>IPv4 Fee:</strong> ₹ {{ number_format($data['ipv4_fee'] ?? 0, 2) }}</p>
                                    @else
                                        <p><strong>IPv4:</strong> Not Selected</p>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    @if(isset($data['ipv6_selected']) && $data['ipv6_selected'])
                                        <p><strong>IPv6:</strong> Selected</p>
                                        <p><strong>IPv6 Size:</strong> {{ $data['ipv6_size'] ?? 'N/A' }}</p>
                                        <p><strong>IPv6 Fee:</strong> ₹ {{ number_format($data['ipv6_fee'] ?? 0, 2) }}</p>
                                    @else
                                        <p><strong>IPv6:</strong> Not Selected</p>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-3">
                                <p class="mb-0"><strong>Total Fee:</strong> ₹ {{ number_format($data['total_fee'] ?? 0, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Business & Network Details -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Business & Network Details</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Nature of Business:</strong> {{ $data['nature_of_business'] ?? 'N/A' }}</p>
                            <p><strong>Industry Type:</strong> {{ $data['industry_type'] ?? 'N/A' }}</p>
                            @if(!empty($data['udyam_number']))
                            <p><strong>UDYAM Number:</strong> {{ $data['udyam_number'] }}</p>
                            @endif
                            @if(!empty($data['mca_tan']))
                            <p><strong>MCA TAN:</strong> {{ $data['mca_tan'] }}</p>
                            @endif
                            
                            @if(isset($data['as_number_required']) && $data['as_number_required'])
                                <div class="mt-3">
                                    <p><strong>ASN Required:</strong> Yes</p>
                                    @if(!empty($data['upstream_name']))
                                    <p><strong>Upstream Provider Name:</strong> {{ $data['upstream_name'] }}</p>
                                    <p><strong>Upstream Provider Mobile:</strong> {{ $data['upstream_mobile'] ?? 'N/A' }}</p>
                                    <p><strong>Upstream Provider Email:</strong> {{ $data['upstream_email'] ?? 'N/A' }}</p>
                                    <p><strong>Upstream ASN:</strong> {{ $data['upstream_asn'] ?? 'N/A' }}</p>
                                    @endif
                                </div>
                            @else
                                <div class="mt-3">
                                    <p><strong>ASN Required:</strong> No</p>
                                    @if(!empty($data['company_asn']))
                                    <p><strong>Company ASN:</strong> {{ $data['company_asn'] }}</p>
                                    @endif
                                    @if(!empty($data['isp_company_name']))
                                    <p><strong>ISP Company Name:</strong> {{ $data['isp_company_name'] }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Billing Details -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Billing Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Affiliate Name:</strong> {{ $data['billing_affiliate_name'] ?? 'N/A' }}</p>
                                    <p><strong>Email:</strong> {{ $data['billing_email'] ?? 'N/A' }}</p>
                                    <p><strong>Mobile:</strong> {{ $data['billing_mobile'] ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Address:</strong> {{ $data['billing_address'] ?? 'N/A' }}</p>
                                    <p><strong>City:</strong> {{ $data['billing_city'] ?? 'N/A' }}</p>
                                    <p><strong>State:</strong> {{ $data['billing_state'] ?? 'N/A' }}</p>
                                    <p><strong>Postal Code:</strong> {{ $data['billing_postal_code'] ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Uploaded Documents -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Uploaded Documents</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @if(!empty($files['network_plan_file']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['network_plan_file']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Network Plan:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'network_plan_file']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Network Plan
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['payment_receipts_file']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['payment_receipts_file']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Payment Receipts:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'payment_receipts_file']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Payment Receipts
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['equipment_details_file']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['equipment_details_file']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Equipment Details:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'equipment_details_file']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Equipment Details
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_business_address_proof']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_business_address_proof']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Business Address Proof:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_business_address_proof']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_authorization_doc']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_authorization_doc']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Authorization Document:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_authorization_doc']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_signature_proof']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_signature_proof']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Signature Proof:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_signature_proof']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_gst_certificate']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_gst_certificate']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>GST Certificate:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_gst_certificate']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_partnership_deed']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_partnership_deed']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Partnership Deed:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_partnership_deed']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_partnership_entity_doc']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_partnership_entity_doc']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Partnership Entity Document:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_partnership_entity_doc']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_incorporation_cert']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_incorporation_cert']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Certificate of Incorporation:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_incorporation_cert']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_company_pan_gstin']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_company_pan_gstin']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Company PAN/GSTIN:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_company_pan_gstin']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_sole_proprietorship_doc']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_sole_proprietorship_doc']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Sole Proprietorship Document:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_sole_proprietorship_doc']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_udyam_cert']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_udyam_cert']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>UDYAM Certificate:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_udyam_cert']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_establishment_reg']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_establishment_reg']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Establishment Registration:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_establishment_reg']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_school_pan_gstin']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_school_pan_gstin']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>School PAN/GSTIN:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_school_pan_gstin']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_rbi_license']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_rbi_license']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>RBI License:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_rbi_license']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_bank_pan_gstin']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_bank_pan_gstin']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Bank PAN/GSTIN:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_bank_pan_gstin']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(empty($files))
                                <div class="col-12">
                                    <p class="text-muted">No documents uploaded.</p>
                                </div>
                                @endif
                            </div>
        </div>
    </div>
@endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@elseif($application->application_type === 'IX')
@php
    $ixData = $application->application_data ?? [];
    $ixDocuments = $ixData['documents'] ?? [];
    $locationInfo = $ixData['location'] ?? [];
    $portInfo = $ixData['port_selection'] ?? [];
    $ipInfo = $ixData['ip_prefix'] ?? [];
    $routerInfo = $ixData['router_details'] ?? [];
    $peeringInfo = $ixData['peering'] ?? [];
    $paymentInfo = $ixData['payment'] ?? [];
@endphp
<!-- IX Application Details Modal -->
<div class="modal fade" id="ixApplicationDetailsModal" tabindex="-1" aria-labelledby="ixApplicationDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-c-blue">
            <div class="modal-header theme-bg-blue text-white d-flex justify-content-between align-items-center">
                <h5 class="modal-title mb-0 fw-semibold text-white" id="ixApplicationDetailsModalLabel" style="color: #fff !important;">
                    IX Application Details - {{ $application->application_id }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if(empty($ixData))
                    <div class="alert alert-warning">
                        <p>No application data available.</p>
                    </div>
                @else
                    <div class="row g-4">
                        <!-- Member Type & Location -->
                        <div class="col-md-6">
                            <div class="card border-c-blue shadow-sm h-100" style="border-radius: 12px;">
                                <div class="card-header theme-bg-blue text-white" style="border-radius: 12px 12px 0 0;">
                                    <h6 class="mb-0 fw-semibold text-capitalize">Member Type & Location</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row app-details">
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Member Type</label>
                                            <div style="color: #2c3e50; font-weight: 500;">{{ $ixData['member_type'] ?? 'N/A' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">NIXI Location</label>
                                            <div style="color: #2c3e50; font-weight: 500;">{{ $locationInfo['name'] ?? 'N/A' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Node Type</label>
                                            <div>
                                                <span class="badge bg-info">{{ ucfirst($locationInfo['node_type'] ?? 'N/A') }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">State</label>
                                            <div style="color: #2c3e50;">{{ $locationInfo['state'] ?? 'N/A' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Switch Details</label>
                                            <div style="color: #2c3e50;">{{ $locationInfo['switch_details'] ?? 'N/A' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Address</label>
                                            <div style="color: #2c3e50; font-size: 0.875rem;">{{ $locationInfo['address'] ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Port Selection -->
                        <div class="col-md-6">
                            <div class="card border-c-blue shadow-sm h-100" style="border-radius: 12px;">
                                <div class="card-header theme-bg-blue text-white" style="border-radius: 12px 12px 0 0;">
                                    <h6 class="mb-0 fw-semibold text-capitalize">Port Selection</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row app-details">
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Port Capacity</label>
                                            <div style="color: #2c3e50; font-weight: 500;">{{ $application->getEffectivePortCapacity() ?? 'N/A' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Billing Plan</label>
                                            <div>
                                                <span class="badge theme-bg-blue">{{ strtoupper($portInfo['billing_plan'] ?? 'N/A') }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Estimated Amount</label>
                                            <div style="color: #2c3e50; font-weight: 600; font-size: 1.1rem;">₹{{ number_format($portInfo['amount'] ?? 0, 2) }} {{ $portInfo['currency'] ?? 'INR' }}</div>
                                        </div>
                                        @if($application->getEffectivePortCapacity())
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Current Port Capacity</label>
                                            <div><span class="badge bg-success" style="font-size: 0.875rem;">{{ $application->getEffectivePortCapacity() }}</span></div>
                                        </div>
                                        @endif
                                        @if($application->assigned_port_number)
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Assigned Port Number</label>
                                            <div><span class="badge bg-info" style="font-size: 0.875rem;">{{ $application->assigned_port_number }}</span></div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- IP Prefix -->
                        <div class="col-md-6">
                            <div class="card border-c-blue shadow-sm h-100" style="border-radius: 12px;">
                                <div class="card-header theme-bg-blue text-white" style="border-radius: 12px 12px 0 0;">
                                    <h6 class="mb-0 fw-semibold text-capitalize">IP Prefix Details</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row app-details">
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Number of Prefixes</label>
                                            <div style="color: #2c3e50; font-weight: 500;">{{ $ipInfo['count'] ?? 'N/A' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Source</label>
                                            <div>
                                                <span class="badge bg-info">{{ strtoupper($ipInfo['source'] ?? 'N/A') }}</span>
                                            </div>
                                        </div>
                                        @if($application->assigned_ip)
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Assigned IP</label>
                                            <div><span class="badge bg-success" style="font-size: 0.875rem;">{{ $application->assigned_ip }}</span></div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Peering Details -->
                        <div class="col-md-6">
                            <div class="card border-c-blue shadow-sm h-100" style="border-radius: 12px;">
                                <div class="card-header theme-bg-blue text-dark" style="border-radius: 12px 12px 0 0;">
                                    <h6 class="mb-0 fw-semibold text-capitalize">Peering Details</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row app-details">
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">ASN Number</label>
                                            <div style="color: #2c3e50; font-weight: 500;">{{ $peeringInfo['asn_number'] ?? 'N/A' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Pre-NIXI Connectivity</label>
                                            <div>
                                                <span class="badge bg-info">{{ ucfirst($peeringInfo['pre_nixi_connectivity'] ?? 'N/A') }}</span>
                                            </div>
                                        </div>
                                        @if($application->customer_id)
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Customer ID</label>
                                            <div><span class="badge theme-bg-blue" style="font-size: 0.875rem;">{{ $application->customer_id }}</span></div>
                                        </div>
                                        @endif
                                        @if($application->membership_id)
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Membership ID</label>
                                            <div><span class="badge theme-bg-blue" style="font-size: 0.875rem;">{{ $application->membership_id }}</span></div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Router Details -->
                        <div class="col-md-6">
                            <div class="card border-c-blue shadow-sm h-100" style="border-radius: 12px;">
                                <div class="card-header theme-bg-blue text-white" style="border-radius: 12px 12px 0 0;">
                                    <h6 class="mb-0 fw-semibold text-capitalize">Router Details</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row app-details">
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Height in U</label>
                                            <div style="color: #2c3e50; font-weight: 500;">{{ $routerInfo['height_u'] ?? 'N/A' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Make & Model</label>
                                            <div style="color: #2c3e50;">{{ $routerInfo['make_model'] ?? 'N/A' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Serial Number</label>
                                            <div style="color: #2c3e50;">{{ $routerInfo['serial_number'] ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Information -->
                        @if($paymentInfo)
                        <div class="col-md-6">
                            <div class="card border-c-blue shadow-sm h-100" style="border-radius: 12px;">
                                <div class="card-header theme-bg-blue text-white" style="border-radius: 12px 12px 0 0;">
                                    <h6 class="mb-0 fw-semibold text-capitalize">Payment Information</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row app-details">
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Application Fee</label>
                                            <div style="color: #2c3e50; font-weight: 500;">₹{{ number_format($paymentInfo['application_fee'] ?? 0, 2) }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">GST ({{ $paymentInfo['gst_percentage'] ?? 0 }}%)</label>
                                            <div style="color: #2c3e50;">₹{{ number_format(($paymentInfo['application_fee'] ?? 0) * ($paymentInfo['gst_percentage'] ?? 0) / 100, 2) }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Total Amount</label>
                                            <div style="color: #2c3e50; font-weight: 600; font-size: 1.1rem;">₹{{ number_format($paymentInfo['total_amount'] ?? 0, 2) }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Payment Status</label>
                                            <div>
                                                <span class="badge {{ ($paymentInfo['status'] ?? 'pending') === 'success' ? 'bg-success' : 'bg-warning' }}" style="font-size: 0.875rem;">
                                                    {{ ucfirst($paymentInfo['status'] ?? 'pending') }}
                                                </span>
                                            </div>
                                        </div>
                                        @if(isset($paymentInfo['paid_at']))
                                        <div class="col-md-6">
                                            <label class="text-muted small mb-1">Paid At</label>
                                            <div style="color: #2c3e50; font-size: 0.875rem;">{{ \Carbon\Carbon::parse($paymentInfo['paid_at'])->format('d M Y, h:i A') }}</div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Uploaded Documents -->
                    <div class="row g-4 mt-2">
                        <div class="col-12">
                            <div class="card border-c-blue shadow-sm" style="border-radius: 12px;">
                                <div class="card-header theme-bg-blue text-white" style="border-radius: 12px 12px 0 0;">
                                    <h6 class="mb-0 fw-semibold text-capitalize">Uploaded Documents</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row app-details">
                                        @if(!empty($ixDocuments))
                                            @foreach($ixDocuments as $key => $path)
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="text-muted small mb-1">{{ ucwords(str_replace(['_', 'file'], [' ', ''], $key)) }}</label>
                                                    <div>
                                                        @if($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path))
                                                            <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => $key]) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                                                    <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                                                                </svg>
                                                                View Document
                                                            </a>
                                                        @else
                                                            <span class="small">File not found</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        @else
                                            <div class="col-12">
                                                <p class="text-muted mb-0">No documents uploaded.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Application PDF -->
                    @if(isset($ixData['pdfs']['application_pdf']))
                    <div class="card border-c-blue shadow-sm">
                        <div class="card-header theme-bg-blue">
                            <h6 class="mb-0 fw-semibold text-capitalize">Application PDF</h6>
                        </div>
                        <div class="card-body">
                            <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'application_pdf']) }}" target="_blank" class="btn btn-primary">
                                <i class="bi bi-file-pdf"></i> Download Application PDF
                            </a>
                        </div>
                    </div>
                    @endif
                @endif
            </div>
            <div class="modal-footer">
                <a href="{{ route('admin.applications.edit', $application->id) }}" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                        <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793l1.646 1.647a.5.5 0 0 0 .708-.708L11.207 1.086l1.647-1.647a.5.5 0 0 0 0-.708zm.646 6.036L9.793 2.206 6.5 5.5V18h11V5.5l-3.646-3.647zM.5 2a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1H1v13h14V3h-3.5a.5.5 0 0 1 0-1H15a.5.5 0 0 1 .5.5v14a.5.5 0 0 1-.5.5H.5a.5.5 0 0 1-.5-.5V2z"/>
                    </svg>
                    Update Details
                </a>
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
function toggleAllInvoices() {
    const section = document.getElementById('allInvoicesSection');
    const icon = document.getElementById('allInvoicesIcon');
    const btn = document.getElementById('toggleAllInvoicesBtn');
    const totalInvoices = btn.getAttribute('data-total');
    
    if (section.style.display === 'none') {
        section.style.display = 'block';
        icon.classList.remove('bi-chevron-down');
        icon.classList.add('bi-chevron-up');
        btn.innerHTML = '<i class="bi bi-chevron-up" id="allInvoicesIcon"></i> Hide All Invoices';
    } else {
        section.style.display = 'none';
        icon.classList.remove('bi-chevron-up');
        icon.classList.add('bi-chevron-down');
        btn.innerHTML = '<i class="bi bi-chevron-down" id="allInvoicesIcon"></i> View All Invoices (' + totalInvoices + ' total)';
    }
}
</script>
<script>
function openIxApplicationModal() {
    const modalElement = document.getElementById('ixApplicationDetailsModal');
    if (modalElement) {
        if (typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            modal.show();
        } else {
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
            document.body.classList.add('modal-open');
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'ixModalBackdrop';
            document.body.appendChild(backdrop);
        }
    } else {
        console.error('IX Application modal element not found');
        alert('Unable to load application details. Please refresh the page.');
    }
}

function openApplicationModal() {
    const modalElement = document.getElementById('applicationDetailsModal');
    if (modalElement) {
        // Try Bootstrap 5 modal
        if (typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else {
            // Fallback: manually show modal
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
            document.body.classList.add('modal-open');
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'modalBackdrop';
            document.body.appendChild(backdrop);
        }
    } else {
        console.error('Modal element not found');
        alert('Unable to load application details. Please refresh the page.');
    }
}

// Function to remove all modal backdrops and restore body
function cleanupModalBackdrop() {
    // Remove all modal backdrops (Bootstrap creates them without IDs sometimes)
    const allBackdrops = document.querySelectorAll('.modal-backdrop');
    allBackdrops.forEach(backdrop => {
        backdrop.remove();
    });
    
    // Remove specific backdrop IDs if they exist
    const modalBackdrop = document.getElementById('modalBackdrop');
    if (modalBackdrop) {
        modalBackdrop.remove();
    }
    
    const ixModalBackdrop = document.getElementById('ixModalBackdrop');
    if (ixModalBackdrop) {
        ixModalBackdrop.remove();
    }
    
    // Remove modal-open class from body
    document.body.classList.remove('modal-open');
    
    // Remove padding-right that Bootstrap adds
    document.body.style.paddingRight = '';
    document.body.style.overflow = '';
}

// Close modal handler for fallback
function closeApplicationModal() {
    const modalElement = document.getElementById('applicationDetailsModal');
    if (modalElement) {
        modalElement.style.display = 'none';
        modalElement.classList.remove('show');
        cleanupModalBackdrop();
    }
}

// Close IX modal handler for fallback
function closeIxApplicationModal() {
    const modalElement = document.getElementById('ixApplicationDetailsModal');
    if (modalElement) {
        modalElement.style.display = 'none';
        modalElement.classList.remove('show');
        cleanupModalBackdrop();
    }
}

// Close modal handler
document.addEventListener('DOMContentLoaded', function() {
    // Handle applicationDetailsModal
    const modal = document.getElementById('applicationDetailsModal');
    if (modal) {
        // Cleanup when Bootstrap modal is hidden
        modal.addEventListener('hidden.bs.modal', function() {
            cleanupModalBackdrop();
        });
        
        // Also cleanup on hide event (before hidden)
        modal.addEventListener('hide.bs.modal', function() {
            // Ensure cleanup happens
            setTimeout(cleanupModalBackdrop, 100);
        });
    }
    
    // Handle ixApplicationDetailsModal
    const ixModal = document.getElementById('ixApplicationDetailsModal');
    if (ixModal) {
        // Cleanup when Bootstrap modal is hidden
        ixModal.addEventListener('hidden.bs.modal', function() {
            cleanupModalBackdrop();
        });
        
        // Also cleanup on hide event (before hidden)
        ixModal.addEventListener('hide.bs.modal', function() {
            // Ensure cleanup happens
            setTimeout(cleanupModalBackdrop, 100);
        });
    }
    
    const btn = document.getElementById('viewDetailsBtn');
    if (btn) {
        btn.addEventListener('click', function(e) {
            // Let Bootstrap handle it if available, otherwise use our function
            if (typeof bootstrap === 'undefined') {
                e.preventDefault();
                openApplicationModal();
            }
        });
    }
    
    // Handle close button clicks for applicationDetailsModal
    const closeButtons = document.querySelectorAll('#applicationDetailsModal [data-bs-dismiss="modal"], #applicationDetailsModal .btn-close');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (typeof bootstrap === 'undefined') {
                closeApplicationModal();
            } else {
                // Even with Bootstrap, ensure cleanup
                setTimeout(cleanupModalBackdrop, 200);
            }
        });
    });
    
    // Handle close button clicks for ixApplicationDetailsModal
    const ixCloseButtons = document.querySelectorAll('#ixApplicationDetailsModal [data-bs-dismiss="modal"], #ixApplicationDetailsModal .btn-close');
    ixCloseButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (typeof bootstrap === 'undefined') {
                closeIxApplicationModal();
            } else {
                // Even with Bootstrap, ensure cleanup
                setTimeout(cleanupModalBackdrop, 200);
            }
        });
    });
    
    // Close on backdrop click for applicationDetailsModal
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal && typeof bootstrap === 'undefined') {
                closeApplicationModal();
            }
        });
    }
    
    // Close on backdrop click for ixApplicationDetailsModal
    if (ixModal) {
        ixModal.addEventListener('click', function(e) {
            if (e.target === ixModal && typeof bootstrap === 'undefined') {
                closeIxApplicationModal();
            }
        });
    }
    
    // Global cleanup - remove any leftover backdrops on page load
    cleanupModalBackdrop();
    
    // TDS Amount validation for mark-paid modals
    document.querySelectorAll('[id^="markPaidModal"]').forEach(modal => {
        const form = modal.querySelector('form');
        if (form) {
            const tdsAmountInput = form.querySelector('input[name="tds_amount"]');
            const tdsAmountError = form.querySelector('[id^="tds_amount_error_mark_paid"]');
            
            if (tdsAmountInput) {
                // Get base amount from modal data attribute
                const baseAmount = parseFloat(modal.getAttribute('data-base-amount')) || 0;
                
                function validateTdsAmountInModal() {
                    if (!tdsAmountInput) return true;
                    
                    const tdsAmount = parseFloat(tdsAmountInput.value) || 0;
                    const maxTdsAmount = baseAmount > 0 ? (baseAmount * 10) / 100 : 0;
                    
                    // Update max attribute dynamically
                    tdsAmountInput.setAttribute('max', maxTdsAmount.toFixed(2));
                    
                    if (tdsAmount < 0) {
                        tdsAmountInput.classList.add('is-invalid');
                        if (tdsAmountError) {
                            tdsAmountError.textContent = 'TDS amount cannot be less than 0';
                            tdsAmountError.style.display = 'block';
                        }
                        return false;
                    } else if (baseAmount > 0 && tdsAmount > maxTdsAmount) {
                        tdsAmountInput.classList.add('is-invalid');
                        if (tdsAmountError) {
                            tdsAmountError.textContent = `TDS amount cannot exceed 10% of base amount (₹${maxTdsAmount.toFixed(2)})`;
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
                
                // Validate on input and blur
                tdsAmountInput.addEventListener('input', validateTdsAmountInModal);
                tdsAmountInput.addEventListener('blur', validateTdsAmountInModal);
                
                // Validate before form submission
                form.addEventListener('submit', function(e) {
                    if (!validateTdsAmountInModal()) {
                        e.preventDefault();
                        e.stopPropagation();
                        tdsAmountInput.focus();
                        return false;
                    }
                });
                
                // Validate on modal show
                modal.addEventListener('shown.bs.modal', function() {
                    validateTdsAmountInModal();
                });
            }
        }
    });
});
</script>
@endpush
@endsection

@push('styles')
<style>
    /* IRINN application details layout - elegant colorful theme */
    .irinn-app-info-card,
    .irinn-workflow-card,
    .irinn-status-card {
        border-radius: 16px;
        overflow: hidden;
    }

    .irinn-app-info-card .card-header,
    .irinn-workflow-card .card-header,
    .irinn-status-card .card-header {
        background: linear-gradient(90deg, var(--theme-blue, #2B2F6C), #4b4fd1);
        color: #ffffff;
    }

    .irinn-app-info-card .card-body {
        padding: 1.25rem 1.5rem;
        background: #ffffff;
    }

    .irinn-workflow-card .card-body {
        padding: 1.25rem 1.5rem;
        background: #f9fafb;
    }

    .irinn-status-card {
        border-color: var(--theme-blue, #2B2F6C);
    }

    .irinn-status-card .card-body {
        padding: 1.25rem 1.5rem;
        background: #ffffff;
    }

    .irinn-app-info-card label {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .irinn-workflow-card .btn-success {
        background-color: #22c55e;
        border-color: #16a34a;
        font-weight: 600;
        padding-inline: 1.25rem;
        border-radius: 999px;
    }

    .irinn-workflow-card .btn-outline-danger {
        border-radius: 999px;
        font-weight: 600;
    }
</style>
@endpush
