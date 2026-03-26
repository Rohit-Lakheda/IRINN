@extends('user.layout')

@section('title', 'My Applications')

@push('styles')
<style>
    .applications-grid-thead th {
        background-color: var(--theme-blue, #2B2F6C) !important;
        color: #fff !important;
        border-bottom-color: rgba(255,255,255,0.2) !important;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="mb-1" style="color:#2c3e50;font-weight:600;">My Applications</h2>
        <p class="text-muted mb-0">View and manage all your applications.</p>
    </div>

    <!-- Filter and Search Section -->
    <div class="card shadow-sm mb-4" style="border-radius: 14px; border: 1px solid rgba(124, 58, 237, 0.12);">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('user.applications.index') }}" id="applicationFilterForm" class="theme-forms">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="search" class="form-label small mb-1">Search Applications</label>
                        <input type="text" 
                               name="search" 
                               id="searchInput"
                               class="form-control" 
                               placeholder="Application ID, Membership ID, IPv4/IPv6 prefix, Node, Location, IP, Stage, Status..."
                               value="{{ request('search') }}"
                               autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label for="live_filter" class="form-label small mb-1">Filter by Status</label>
                        <select name="live_filter" id="liveFilterSelect" class="form-select">
                            <option value="all" {{ $liveFilter === 'all' ? 'selected' : '' }}>All ({{ $totalCount ?? 0 }})</option>
                            <option value="live" {{ $liveFilter === 'live' ? 'selected' : '' }}>Live ({{ $liveCount ?? 0 }})</option>
                            <option value="not_live" {{ $liveFilter === 'not_live' ? 'selected' : '' }}>Not Live ({{ $notLiveCount ?? 0 }})</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <a href="{{ route('user.applications.index') }}" class="btn btn-danger">Clear</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Applications List -->
    <div class="card shadow-sm border-c-blue applications-list-card" style="border-radius: 16px;">
        <div class="card-header card-header-violet" style="border-radius: 16px 16px 0 0;">
            <h5 class="mb-0 text-white">Applications List</h5>
        </div>
        <div class="card-body">

                @if($applications->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover applications-grid-table">
                            <thead class="text-nowrap applications-grid-thead">
                                <tr>
                                    <th>Application ID</th>
                                    <th>Application Details</th>
                                    <th>Current Stage</th>
                                    <th>Status</th>
                                    <th>Submitted At</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($applications as $application)
                                @php
                                    $paymentData = $application->application_type === 'IX'
                                        ? ($application->application_data['payment'] ?? null)
                                        : ($application->application_data['part5'] ?? null);
                                    $isIxDraftAwaitingPayment = $application->application_type === 'IX'
                                        && $application->status === 'draft'
                                        && ($paymentData['status'] ?? null) === 'pending';
                                    $isIrinnDraftAwaitingPayment = $application->application_type === 'IRINN'
                                        && $application->status === 'draft'
                                        && ($paymentData['payment_status'] ?? null) === 'pending';
                                    $locationData = $application->application_data['location'] ?? null;
                                    $portSelection = $application->application_data['port_selection'] ?? null;
                                    $irinnResources = $application->application_type === 'IRINN' 
                                        ? ($application->application_data['part2'] ?? null) 
                                        : null;
                                @endphp
                                <tr class="align-middle">
                                    <td>
                                        @if($application->application_type === 'IRINN')
                                            <div><strong>App ID:</strong> {{ $application->application_id }}</div>
                                            @if($application->membership_id)
                                                <div class="small text-muted">Membership: {{ $application->membership_id }}</div>
                                            @endif
                                        @else
                                            @if($application->membership_id)
                                                <strong>{{ $application->membership_id }}</strong>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if($application->application_type === 'IRINN')
                                            @if($irinnResources)
                                                <div class="mb-1">
                                                    <strong>IP Resources:</strong>
                                                </div>
                                                <div class="small text-muted">
                                                    @if(!empty($irinnResources['ipv4_prefix']))
                                                        <div>IPv4: <strong class="text-dark">{{ $irinnResources['ipv4_prefix'] }}</strong></div>
                                                    @endif
                                                    @if(!empty($irinnResources['ipv6_prefix']))
                                                        <div>IPv6: <strong class="text-dark">{{ $irinnResources['ipv6_prefix'] }}</strong></div>
                                                    @endif
                                                    @if(!empty($irinnResources['asn_required']) && $irinnResources['asn_required'] === 'yes')
                                                        <div>ASN: <strong class="text-dark">Required</strong></div>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        @else
                                            @if($locationData)
                                                <div class="mb-1">
                                                    <strong>{{ $locationData['name'] ?? 'N/A' }}</strong>
                                                    @if(isset($locationData['state']))
                                                        <small class="text-muted">, {{ $locationData['state'] }}</small>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="mb-1"><span class="text-muted">N/A</span></div>
                                            @endif
                                            
                                            <div class="small text-muted">
                                                @if($application->assigned_ip)
                                                    <div>IP: <strong class="text-dark">{{ $application->assigned_ip }}</strong></div>
                                                @endif
                                                @if($application->assigned_port_number)
                                                    <div>Port: <strong class="text-dark">{{ $application->assigned_port_number }}</strong></div>
                                                @endif
                                                @if($application->assigned_port_capacity)
                                                    <div>Capacity: <strong class="text-dark">{{ $application->assigned_port_capacity }}</strong></div>
                                                @elseif($portSelection && isset($portSelection['capacity']))
                                                    <div>Capacity: <strong class="text-dark">{{ $portSelection['capacity'] }}</strong></div>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $stage = $application->current_stage ?? '';
                                            $stageClass = match(true) {
                                                $application->application_type === 'IRINN' && stripos($stage, 'billing') !== false => 'badge-stage-completed',
                                                $application->application_type === 'IRINN' && stripos($stage, 'helpdesk') !== false => 'badge-stage-processor',
                                                $application->application_type === 'IRINN' && stripos($stage, 'hostmaster') !== false => 'badge-stage-head',
                                                $application->application_type === 'IRINN' && stripos($stage, 'resubmission') !== false => 'badge-stage-rejected',
                                                $application->application_type === 'IRINN' && (stripos($stage, 'draft') !== false || stripos($stage, 'pending') !== false) => 'badge-stage-draft',
                                                stripos($stage, 'processor') !== false => 'badge-stage-processor',
                                                stripos($stage, 'legal') !== false => 'badge-stage-legal',
                                                stripos($stage, 'head') !== false => 'badge-stage-head',
                                                stripos($stage, 'ceo') !== false => 'badge-stage-ceo',
                                                stripos($stage, 'nodal') !== false => 'badge-stage-nodal',
                                                stripos($stage, 'tech') !== false => 'badge-stage-tech',
                                                stripos($stage, 'account') !== false => 'badge-stage-account',
                                                stripos($stage, 'draft') !== false => 'badge-stage-draft',
                                                stripos($stage, 'payment') !== false => 'badge-stage-payment',
                                                stripos($stage, 'completed') !== false || stripos($stage, 'approved') !== false => 'badge-stage-completed',
                                                stripos($stage, 'reject') !== false => 'badge-stage-rejected',
                                                default => 'badge-stage-default',
                                            };
                                        @endphp
                                        <span class="badge {{ $stageClass }}">{{ $application->current_stage }}</span>
                                    </td>
                                    <td>
                                        @if($application->application_type === 'IRINN')
                                            @if($application->status === 'billing')
                                                <span class="badge badge-status-live">LIVE</span>
                                            @else
                                                <span class="badge badge-status-notlive">NOT LIVE</span>
                                            @endif
                                        @else
                                            @if($application->is_active && $application->service_activation_date)
                                                <span class="badge badge-status-live">LIVE</span>
                                            @else
                                                <span class="badge badge-status-notlive">NOT LIVE</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>{{ $application->submitted_at ? $application->submitted_at->format('d M Y, h:i A') : 'N/A' }}</td>
                                    <td style="vertical-align: middle;">
                                        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end">
                                            <a href="{{ route('user.applications.show', $application->id) }}" class="btn btn-primary">
                                                View Details
                                            </a>
                                            <a href="{{ route('user.applications.gst.edit', $application->id) }}" class="btn btn-success">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                    <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0Zm3.5 7.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5Z"/>
                                                </svg>
                                                Update GST
                                            </a>

                                            @if($isIxDraftAwaitingPayment || $isIrinnDraftAwaitingPayment)
                                                @php
                                                    $user = \App\Models\Registration::find(session('user_id'));
                                                    $wallet = $user ? $user->wallet : null;
                                                    $walletBalance = $wallet && $wallet->status === 'active' ? (float) $wallet->balance : 0;
                                                    if ($application->application_type === 'IX') {
                                                        $applicationPricing = \App\Models\IxApplicationPricing::getActive();
                                                        $applicationAmount = $applicationPricing ? (float) $applicationPricing->total_amount : 1180.00;
                                                    } else {
                                                        // IRINN: Get amount from application data
                                                        $applicationAmount = (float) ($paymentData['total_amount'] ?? 1180.00);
                                                    }
                                                    $canPayWithWallet = $wallet && $wallet->status === 'active' && $walletBalance >= $applicationAmount;
                                                @endphp
                                                @if($canPayWithWallet)
                                                    <form action="{{ $application->application_type === 'IX' ? route('user.applications.ix.pay-now-with-wallet', $application->id) : route('user.applications.irin.pay-now-with-wallet', $application->id) }}" method="POST" class="d-inline me-2">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success" onclick="return confirm('Pay ₹{{ number_format($applicationAmount, 2) }} from advance amount?')">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                                <path d="M0 3a2 2 0 0 1 2-2h13.5a.5.5 0 0 1 0 1H15v2a1 1 0 0 1 1 1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1h-1a1 1 0 0 1-1-1V6h-2v1.5a.5.5 0 0 1-1 0V6H1V4.5a.5.5 0 0 1 1 0V3zm1 1.5V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V4.5A1.5 1.5 0 0 0 12.5 3h-9A1.5 1.5 0 0 0 1 4.5z"/>
                                                            </svg>
                                                            Pay with Advance
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($application->application_type === 'IX')
                                                    <a href="{{ route('user.applications.ix.pay-now', $application->id) }}" class="btn btn-primary">
                                                        Pay Now
                                                    </a>
                                                @else
                                                    @php
                                                        // For IRINN, we need to create payment transaction and redirect to PayU
                                                        $paymentData = $application->application_data['part5'] ?? null;
                                                        $totalAmount = (float) ($paymentData['total_amount'] ?? 1180.00);
                                                    @endphp
                                                    <form action="{{ route('user.applications.irin.store-new') }}" method="POST" class="d-inline" id="irinn-pay-form-{{ $application->id }}">
                                                        @csrf
                                                        <input type="hidden" name="action" value="submit">
                                                        <input type="hidden" name="application_id" value="{{ $application->id }}">
                                                        <button type="submit" class="btn btn-primary">
                                                            Pay Now
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif
                                            
                                            @php
                                                $pendingInvoices = $pendingInvoicesByApplication[$application->id] ?? collect();
                                            @endphp
                                            @if($pendingInvoices->count() > 0)
                                                <span class="badge bg-danger">{{ $pendingInvoices->count() }} Pending</span>
                                                <a href="{{ route('user.invoices.index', ['filter' => 'pending']) }}" class="btn btn-sm btn-danger">
                                                    View Invoices
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $applications->links('vendor.pagination.bootstrap-5') }}
                    </div>

                    <div class="mt-4 d-flex justify-content-end">
                        <a href="{{ route('user.irinn.create') }}" class="btn btn-primary btn-irinn-new">
                            <i class="bi bi-plus-circle me-1"></i> New IRINN Application
                        </a>
                    </div>
                @else
                    <div class="text-center py-5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" viewBox="0 0 16 16" class="text-muted mb-3">
                            <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                        </svg>
                        <p class="text-muted mb-4">No applications yet.</p>
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="{{ route('user.irinn.create') }}" class="btn btn-primary btn-lg">
                                <i class="bi bi-file-earmark-text"></i> IRINN Application
                            </a>
                        </div>
                    </div>
                @endif
                
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const liveFilterSelect = document.getElementById('liveFilterSelect');
    const filterForm = document.getElementById('applicationFilterForm');
    
    let searchTimeout;
    
    // Dynamic search - filters as user types (no button needed)
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            // Debounce search - wait 500ms after user stops typing
            searchTimeout = setTimeout(function() {
                filterForm.submit();
            }, 500);
        });
    }
    
    // Filter change - submit immediately
    if (liveFilterSelect) {
        liveFilterSelect.addEventListener('change', function() {
            filterForm.submit();
        });
    }

    // IRINN draft Pay Now: submit via AJAX then redirect to PayU form (avoid JSON + avoid action shadowing bug)
    document.querySelectorAll('form[id^="irinn-pay-form-"]').forEach(function(irinnPayForm) {
        irinnPayForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const btn = this.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Redirecting...';
            }

            const formData = new FormData(this);
            fetch(this.getAttribute('action'), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(r => r.json())
            .then(function(data) {
                if (data.success && data.payment_url && data.payment_data) {
                    const f = document.createElement('form');
                    f.method = 'POST';
                    f.action = data.payment_url;
                    Object.keys(data.payment_data).forEach(function(k) {
                        const i = document.createElement('input');
                        i.type = 'hidden';
                        i.name = k;
                        i.value = data.payment_data[k];
                        f.appendChild(i);
                    });
                    document.body.appendChild(f);
                    f.submit();
                } else {
                    alert(data.message || 'Unable to start payment.');
                    if (btn) { btn.disabled = false; btn.textContent = 'Pay Now'; }
                }
            })
            .catch(function(err) {
                console.error(err);
                alert('Payment request failed. Please try again.');
                if (btn) { btn.disabled = false; btn.textContent = 'Pay Now'; }
            });
        });
    });
});
</script>
@endpush
@endsection
