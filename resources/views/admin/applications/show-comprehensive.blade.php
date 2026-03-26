@extends('admin.layout')

@section('title', 'Application Details - Comprehensive View')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1" style="color:#2c3e50;font-weight:600;">Application Details (Comprehensive)</h2>
            <p class="text-muted mb-0">Application ID:
                <strong>{{ $application->application_id }}</strong>
            </p>
            <p class="text-muted mb-0 small">Application Type:
                <strong>{{ $application->application_type ?? 'N/A' }}</strong>
            </p>
        </div>
        <div>
            <a href="{{ route('admin.applications.show', $application->id) }}" class="btn btn-outline-primary">
                Back to Summary View
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Left navigation --}}
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm" style="border-radius:16px;">
                <div class="card-header theme-bg-blue" style="border-radius:16px 16px 0 0;">
                    <h6 class="mb-0 text-white">Sections</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="javascript:void(0)" class="list-group-item list-group-item-action comp-nav-link active"
                       data-target="comp-section-application">
                        Application Information
                    </a>
                    <a href="javascript:void(0)" class="list-group-item list-group-item-action comp-nav-link"
                       data-target="comp-section-registration">
                        Registration Details
                    </a>
                    <a href="javascript:void(0)" class="list-group-item list-group-item-action comp-nav-link"
                       data-target="comp-section-kyc">
                        KYC Details
                    </a>
                    <a href="javascript:void(0)" class="list-group-item list-group-item-action comp-nav-link"
                       data-target="comp-section-irin-data">
                        IRINN Application Data
                    </a>
                    <a href="javascript:void(0)" class="list-group-item list-group-item-action comp-nav-link"
                       data-target="comp-section-status">
                        Application Status
                    </a>
                    <a href="javascript:void(0)" class="list-group-item list-group-item-action comp-nav-link"
                       data-target="comp-section-gst-history">
                        GST Change History
                    </a>
                </div>
            </div>
        </div>

        {{-- Main content --}}
        <div class="col-md-9">
            {{-- Application Information --}}
            <div id="comp-section-application" class="card shadow-sm mb-3 comp-section" style="border-radius:16px;">
                <div class="card-header theme-bg-blue" style="border-radius:16px 16px 0 0;">
                    <h6 class="mb-0 text-white">Application Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Application ID</div>
                            <div class="fw-semibold">{{ $application->application_id }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Type</div>
                            <div class="fw-semibold">{{ $application->application_type }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Current Stage</div>
                            <div class="fw-semibold">{{ $application->current_stage_display ?? $application->status_display }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Created At</div>
                            <div class="fw-semibold">
                                {{ optional($application->created_at)->format('d M Y, h:i A') }}
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="text-muted small">Registered User</div>
                            @if($application->registration)
                                <a href="{{ route('admin.users.show', $application->registration->id) }}"
                                   class="fw-semibold text-decoration-none">
                                    {{ $application->registration->name }}
                                </a>
                            @else
                                <span class="fw-semibold">N/A</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Registration Details --}}
            <div id="comp-section-registration" class="card shadow-sm mb-3 comp-section" style="border-radius:16px; display:none;">
                <div class="card-header theme-bg-blue" style="border-radius:16px 16px 0 0;">
                    <h6 class="mb-0 text-white">Registration Details</h6>
                </div>
                <div class="card-body">
                    @php
                        $reg = $application->registration_details ?? ($application->registration ? $application->registration->toArray() : []);
                    @endphp
                    @if(!empty($reg))
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted small">Name</div>
                                <div class="fw-semibold">{{ $reg['name'] ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Email</div>
                                <div class="fw-semibold">{{ $reg['email'] ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Mobile</div>
                                <div class="fw-semibold">{{ $reg['mobile'] ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">PAN</div>
                                <div class="fw-semibold">{{ $reg['pan_no'] ?? 'N/A' }}</div>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">No registration snapshot available.</p>
                    @endif
                </div>
            </div>

            {{-- KYC Details --}}
            <div id="comp-section-kyc" class="card shadow-sm mb-3 comp-section" style="border-radius:16px; display:none;">
                <div class="card-header theme-bg-blue" style="border-radius:16px 16px 0 0;">
                    <h6 class="mb-0 text-white">KYC Details</h6>
                </div>
                <div class="card-body">
                    @php
                        $kyc = $application->kyc_details ?? [];
                    @endphp
                    @if(!empty($kyc))
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted small">GSTIN</div>
                                <div class="fw-semibold">{{ $kyc['gstin'] ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Legal / Trade Name</div>
                                <div class="fw-semibold">{{ $kyc['legal_name'] ?? $kyc['trade_name'] ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-12">
                                <div class="text-muted small">Billing Address</div>
                                <div class="fw-semibold">
                                    {{ $kyc['billing_address']['address'] ?? $kyc['billing_address'] ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">No KYC snapshot available.</p>
                    @endif
                </div>
            </div>

            {{-- IRINN Application Data (simplified snapshot) --}}
            <div id="comp-section-irin-data" class="card shadow-sm mb-3 comp-section" style="border-radius:16px; display:none;">
                <div class="card-header theme-bg-blue" style="border-radius:16px 16px 0 0;">
                    <h6 class="mb-0 text-white">IRINN Application Data</h6>
                </div>
                <div class="card-body">
                    @php
                        $data = $application->application_data ?? [];
                    @endphp
                    @if(!empty($data))
                        {{-- Part 1 --}}
                        <h6 class="fw-semibold mb-2" style="color:#2c3e50;">Part 1: Application</h6>
                        <div class="mb-3">
                            <div class="text-muted small">Affiliate Type</div>
                            <div class="fw-semibold">{{ $data['part1']['affiliate_type'] ?? 'N/A' }}</div>
                        </div>

                        {{-- Part 2 --}}
                        <h6 class="fw-semibold mb-2" style="color:#2c3e50;">Part 2: New Resources</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="text-muted small">IPv4 Prefix</div>
                                <div class="fw-semibold">{{ $data['part2']['ipv4_prefix'] ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">IPv6 Prefix</div>
                                <div class="fw-semibold">{{ $data['part2']['ipv6_prefix'] ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">ASN Required</div>
                                <div class="fw-semibold">
                                    @if(isset($data['part2']['asn_required']))
                                        {{ $data['part2']['asn_required'] ? 'Yes' : 'No' }}
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Part 5 --}}
                        <h6 class="fw-semibold mb-2" style="color:#2c3e50;">Part 5: Payment</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="text-muted small">Application Fee</div>
                                <div class="fw-semibold">
                                    {{ $data['part5']['application_fee'] ?? 'N/A' }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Total Amount</div>
                                <div class="fw-semibold">
                                    {{ $data['part5']['total_amount'] ?? 'N/A' }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Payment Status</div>
                                <div class="fw-semibold">
                                    {{ $data['part5']['payment_status'] ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">No IRINN application data available.</p>
                    @endif
                </div>
            </div>

            {{-- Application Status --}}
            <div id="comp-section-status" class="card shadow-sm mb-3 comp-section" style="border-radius:16px; display:none;">
                <div class="card-header theme-bg-blue" style="border-radius:16px 16px 0 0;">
                    <h6 class="mb-0 text-white">Application Status</h6>
                </div>
                <div class="card-body">
                    {{-- Simple IRINN workflow bar --}}
                    @php
                        $statusSlug = strtolower(trim((string) $application->status));
                    @endphp
                    <div class="mb-3 d-flex align-items-center justify-content-between flex-wrap">
                        @foreach(['helpdesk', 'hostmaster', 'billing'] as $step)
                            @php
                                $index = array_search($step, ['helpdesk', 'hostmaster', 'billing']);
                                $currentIndex = array_search($statusSlug, ['helpdesk', 'hostmaster', 'billing']);
                                $completed = $currentIndex !== false && $currentIndex > $index;
                                $active = $currentIndex === $index;
                            @endphp
                            <div class="d-flex align-items-center flex-grow-1 mb-2">
                                <div class="rounded-circle text-center"
                                     style="width:32px;height:32px;
                                            @if($completed || $active)
                                                background:#4caf50;color:#fff;
                                            @else
                                                background:#e0e0e0;color:#777;
                                            @endif
                                            font-size:14px;line-height:32px;">
                                    {{ strtoupper(substr($step,0,1)) }}
                                </div>
                                <div class="ms-2 fw-semibold"
                                     style="@if($completed || $active)color:#2c3e50;@else color:#9e9e9e;@endif">
                                    {{ ucfirst($step) }}
                                </div>
                                @if(!$loop->last)
                                    <div class="flex-grow-1 mx-2"
                                         style="height:3px;
                                                background:linear-gradient(to right,#cfd8dc,#cfd8dc);">
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3">
                        <div class="text-muted small mb-2">Status History</div>
                        <div class="border rounded-3 p-2" style="background:#fafafa;">
                            @forelse($statusHistory as $entry)
                                <div class="border-bottom pb-2 mb-2">
                                    <div class="d-flex justify-content-between">
                                        <div class="fw-semibold">
                                            {{ $entry->status_display }}
                                            @if($entry->remarks)
                                                - {{ $entry->remarks }}
                                            @endif
                                        </div>
                                        <div class="text-muted small">
                                            {{ optional($entry->created_at)->format('d M Y, h:i A') }}
                                        </div>
                                    </div>
                                    <div class="small text-muted">
                                        By: {{ $entry->changed_by ?? 'System' }}
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted mb-0">No status history available.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- GST Change History --}}
            <div id="comp-section-gst-history" class="card shadow-sm mb-3 comp-section" style="border-radius:16px; display:none;">
                <div class="card-header theme-bg-blue" style="border-radius:16px 16px 0 0;">
                    <h6 class="mb-0 text-white">GST Change History</h6>
                </div>
                <div class="card-body">
                    @if(isset($gstChangeHistory) && $gstChangeHistory->count())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Old GSTIN</th>
                                        <th>New GSTIN</th>
                                        <th>Changed At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($gstChangeHistory as $gst)
                                        <tr>
                                            <td>{{ $gst->old_gstin ?? 'N/A' }}</td>
                                            <td>{{ $gst->new_gstin ?? 'N/A' }}</td>
                                            <td>{{ optional($gst->created_at)->format('d M Y, h:i A') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No GST change history available.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .comp-nav-link.active {
        background-color: #e8f0fe;
        color: #1a237e;
        font-weight: 600;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const navLinks = document.querySelectorAll('.comp-nav-link');
    const sections = document.querySelectorAll('.comp-section');

    function showSection(id) {
        sections.forEach(function (sec) {
            sec.style.display = (sec.id === id) ? 'block' : 'none';
        });
    }

    navLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            const target = this.getAttribute('data-target');

            navLinks.forEach(function (l) { l.classList.remove('active'); });
            this.classList.add('active');

            showSection(target);
        });
    });

    // initial
    showSection('comp-section-application');
});
</script>
@endpush

@extends('admin.layout')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Application Details - Comprehensive View')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1" style="color:#2c3e50;font-weight:600;">Application Details</h2>
            <p class="text-muted mb-0">Application ID: <strong>{{ $application->application_id }}</strong></p>
            <p class="text-muted mb-0 small">Application Type: <strong>{{ $application->application_type ?? 'N/A' }}</strong></p>
        </div>
        <div>
            <a href="{{ route('admin.applications.show', $application->id) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Summary View
            </a>
            <button type="button" class="btn btn-warning text-white" data-bs-toggle="modal" data-bs-target="#updateApplicationModal">
                <i class="bi bi-pencil me-1"></i> Edit Application Details
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Side Navigation -->
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="card border-c-blue shadow-sm sticky-top1" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue" style="border-radius: 16px 16px 0 0; padding: 19px;">
                    <h6 class="mb-0">Quick Navigation</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="javascript:;" class="list-group-item list-group-item-action toggle-nav-link" data-target="section-application-info">
                        <i class="bi bi-info-circle me-2"></i> Application Information
                    </a>
                    @if($application->registration_details || $application->application_type === 'IRINN')
                    <a href="javascript:;" class="list-group-item list-group-item-action toggle-nav-link" data-target="section-registration">
                        <i class="bi bi-person-badge me-2"></i> Registration Details
                    </a>
                    @endif
                    @if($application->kyc_details || $application->application_type === 'IRINN')
                    <a href="javascript:;" class="list-group-item list-group-item-action toggle-nav-link" data-target="section-kyc">
                        <i class="bi bi-shield-check me-2"></i> KYC Details
                    </a>
                    @endif
                    @if($application->authorized_representative_details)
                    <a href="javascript:;" class="list-group-item list-group-item-action toggle-nav-link" data-target="section-representative">
                        <i class="bi bi-person-vcard me-2"></i> Authorized Representative
                    </a>
                    @endif
                    @if(strtoupper((string) $application->application_type) === 'IX')
                        @if($application->getEffectivePortCapacity())
                        <a href="javascript:;" class="list-group-item list-group-item-action toggle-nav-link" data-target="section-plan-change">
                            <i class="bi bi-arrow-repeat me-2"></i> Plan Management
                        </a>
                        @endif
                    @endif
                    @if($application->application_type === 'IRINN')
                    <a href="javascript:;" class="list-group-item list-group-item-action toggle-nav-link" data-target="section-irin-application-data">
                        <i class="bi bi-file-earmark-text me-2"></i> IRINN Application Data
                    </a>
                    @elseif($application->application_data)
                    <a href="javascript:;" class="list-group-item list-group-item-action toggle-nav-link" data-target="section-application-data">
                        <i class="bi bi-file-earmark-text me-2"></i> Application Data
                    </a>
                    @endif
                    <a href="javascript:;" class="list-group-item list-group-item-action toggle-nav-link" data-target="section-status">
                        <i class="bi bi-list-check me-2"></i> Application Status
                    </a>
                    <a href="javascript:;" class="list-group-item list-group-item-action toggle-nav-link" data-target="section-gst-history">
                        <i class="bi bi-receipt me-2"></i> GST Change History
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-8 col-lg-9">
            <!-- Pending GST Update Request Alert -->
            @php
                $pendingGstUpdateRequest = $application->gstUpdateRequests->where('status', 'pending')->first();
            @endphp
            @if($pendingGstUpdateRequest)
            <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert" style="border-radius: 16px;">
                <div class="d-flex align-items-start">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading mb-2">GST Update Request Pending Approval</h6>
                        <p class="mb-2">
                            GST update request is pending admin approval. The company name similarity is 
                            <strong>{{ number_format($pendingGstUpdateRequest->similarity_score ?? 0, 2) }}%</strong> 
                            (requires 70% or more for automatic approval).
                        </p>
                        <div class="small">
                            <strong>Old GSTIN:</strong> {{ $pendingGstUpdateRequest->old_gstin ?? 'N/A' }}<br>
                            <strong>New GSTIN:</strong> {{ $pendingGstUpdateRequest->new_gstin }}<br>
                            <strong>Old Company Name:</strong> {{ $pendingGstUpdateRequest->old_company_name ?? 'N/A' }}<br>
                            <strong>New Company Name:</strong> {{ $pendingGstUpdateRequest->new_company_name }}<br>
                            <strong>Submitted:</strong> {{ $pendingGstUpdateRequest->created_at->format('d M Y, h:i A') }}
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Application Information -->
            <div id="section-application-info" class="card border-c-blue shadow-sm mb-3 comp-section active-section" style="border-radius: 16px; display: block;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">Application Information</h6>
                    <button type="button" class="btn btn-sm toggle-section" data-target="application-info-content">
                        <i class="bi bi-chevron-down text-white fs-5"></i>
                    </button>
                </div>
                <div id="application-info-content" class="card-body p-3">
                    <div class="row g-2 app-details">
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Application ID</label>
                            <div class="fw-medium">{{ $application->application_id }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Application Type</label>
                            <div class="fw-medium">{{ $application->application_type }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Current Stage</label>
                            <div><span class="badge bg-light text-dark">{{ $application->current_stage }}</span></div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Submitted At</label>
                            <div class="fw-medium">{{ $application->submitted_at ? $application->submitted_at->format('d M Y, h:i A') : 'N/A' }}</div>
                        </div>
                        @if($application->approved_at)
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Approved At</label>
                            <div class="fw-medium">{{ $application->approved_at->format('d M Y, h:i A') }}</div>
                        </div>
                        @endif
                        @if($application->pan_card_no)
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">PAN Card Number</label>
                            <div class="fw-medium">{{ $application->pan_card_no }}</div>
                        </div>
                        @endif
                        @if($application->billing_cycle)
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Billing Cycle</label>
                            <div class="fw-medium">{{ strtoupper($application->billing_cycle) }}</div>
                        </div>
                        @endif
                        @if($application->service_activation_date)
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Service Activation Date</label>
                            <div class="fw-medium">{{ $application->service_activation_date->format('d M Y') }}</div>
                        </div>
                        @endif
                        @if($application->deactivated_at)
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Deactivated At</label>
                            <div class="fw-medium">{{ $application->deactivated_at->format('d M Y, h:i A') }}</div>
                        </div>
                        @endif
                        @if($application->resubmission_query)
                        <div class="col-12">
                            <label class="small text-muted mb-1 d-block">Resubmission Query</label>
                            <div class="fw-medium">{{ $application->resubmission_query }}</div>
                        </div>
                        @endif
                        @if($application->application_type === 'IX')
                            @if($application->membership_id)
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block">Membership ID</label>
                                <div class="fw-medium">{{ $application->membership_id }}</div>
                            </div>
                            @endif
                            @if($application->customer_id)
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block">Customer ID</label>
                                <div class="fw-medium">{{ $application->customer_id }}</div>
                            </div>
                            @endif
                            @if($application->assigned_ip)
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block">Assigned IP</label>
                                <div class="fw-medium">{{ $application->assigned_ip }}</div>
                            </div>
                            @endif
                            @if($application->assigned_port_number)
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block">Port Number</label>
                                <div class="fw-medium">{{ $application->assigned_port_number }}</div>
                            </div>
                            @endif
                            @php
                                $appData = $application->application_data ?? [];
                                $portSelection = $appData['port_selection'] ?? [];
                                $displayPortCapacity = $application->getEffectivePortCapacity();
                            @endphp
                            @if($displayPortCapacity)
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block">Port Capacity</label>
                                <div class="fw-medium">{{ $displayPortCapacity }}</div>
                            </div>
                            @endif
                            @if($portSelection && isset($portSelection['billing_plan']))
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block">Billing Cycle</label>
                                <div class="fw-medium">{{ strtoupper($portSelection['billing_plan']) }}</div>
                            </div>
                            @endif
                        @endif
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Live Status</label>
                            <div>
                                @if($application->is_active && $application->service_activation_date)
                                    <span class="badge bg-success">LIVE</span>
                                    <small class="d-block text-muted mt-1">Activated: {{ \Carbon\Carbon::parse($application->service_activation_date)->format('d M Y') }}</small>
                                @elseif($application->deactivated_at)
                                    <span class="badge bg-danger">NOT LIVE</span>
                                    <small class="d-block text-muted mt-1">Closed: {{ \Carbon\Carbon::parse($application->deactivated_at)->format('d M Y') }}</small>
                                @else
                                    <span class="badge bg-secondary">NOT LIVE</span>
                                @endif
                            </div>
                        </div>
                        @if($application->rejection_reason)
                        <div class="col-12">
                            <label class="small text-muted mb-1 d-block">Rejection Reason</label>
                            <div class="text-danger fw-medium">{{ $application->rejection_reason }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            @if(in_array($application->application_type, ['IX', 'IRINN']))
            <!-- Registration Details -->
            @if($application->registration_details || $application->application_type === 'IRINN')
            <div id="section-registration" class="card border-c-blue shadow-sm mb-3 comp-section" style="border-radius: 16px; display: none;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">Registration Details</h6>
                    <button type="button" class="btn btn-sm toggle-section" data-target="registration-content">
                        <i class="bi bi-chevron-down text-white fs-5"></i>
                    </button>
                </div>
                <div id="registration-content" class="card-body p-3">
                    @php
                        $regDetails = $application->registration_details;
                    @endphp
                    <div class="row g-2 app-details">
                        @if(isset($regDetails['registration_id']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Registration ID</label>
                            <div class="fw-medium">{{ $regDetails['registration_id'] }}</div>
                        </div>
                        @endif
                        @if(isset($regDetails['registration_type']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Registration Type</label>
                            <div class="fw-medium">{{ ucfirst($regDetails['registration_type']) }}</div>
                        </div>
                        @endif
                        @if(isset($regDetails['fullname']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Full Name</label>
                            <div class="fw-medium">{{ $regDetails['fullname'] }}</div>
                        </div>
                        @endif
                        @if(isset($regDetails['pancardno']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">PAN Card</label>
                            <div class="fw-medium">{{ $regDetails['pancardno'] }}</div>
                        </div>
                        @endif
                        @if(isset($regDetails['email']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Email</label>
                            <div class="fw-medium">
                                {{ $regDetails['email'] }}
                                @if($regDetails['email_verified'] ?? false)
                                    <span class="badge bg-success ms-1">Verified</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if(isset($regDetails['mobile']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Mobile</label>
                            <div class="fw-medium">
                                {{ $regDetails['mobile'] }}
                                @if($regDetails['mobile_verified'] ?? false)
                                    <span class="badge bg-success ms-1">Verified</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if(isset($regDetails['dateofbirth']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Date of Birth</label>
                            <div class="fw-medium">{{ $regDetails['dateofbirth'] }}</div>
                        </div>
                        @endif
                        @if(isset($regDetails['registrationdate']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Registration Date</label>
                            <div class="fw-medium">{{ $regDetails['registrationdate'] }}</div>
                        </div>
                        @endif
                        @if(isset($regDetails['address']))
                        <div class="col-12">
                            <label class="small text-muted mb-1 d-block">Address</label>
                            <div class="fw-medium">{{ $regDetails['address'] }}</div>
                        </div>
                        @endif
                        @if(isset($regDetails['city']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">City</label>
                            <div class="fw-medium">{{ $regDetails['city'] }}</div>
                        </div>
                        @endif
                        @if(isset($regDetails['state']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">State</label>
                            <div class="fw-medium">{{ $regDetails['state'] }}</div>
                        </div>
                        @endif
                        @if(isset($regDetails['pincode']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Pincode</label>
                            <div class="fw-medium">{{ $regDetails['pincode'] }}</div>
                        </div>
                        @endif
                        @if(isset($regDetails['country']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Country</label>
                            <div class="fw-medium">{{ $regDetails['country'] }}</div>
                        </div>
                        @endif
                        @if(isset($regDetails['status']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Registration Status</label>
                            <div>
                                @if($regDetails['status'] === 'approved' || $regDetails['status'] === 'active')
                                    <span class="badge bg-success">{{ ucfirst($regDetails['status']) }}</span>
                                @else
                                    <span class="badge bg-warning">{{ ucfirst($regDetails['status']) }}</span>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- KYC Details -->
            @if($application->kyc_details || $application->application_type === 'IRINN')
            <div id="section-kyc" class="card border-c-blue shadow-sm mb-3 comp-section" style="border-radius: 16px; display: none;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">KYC Details</h6>
                    <button type="button" class="btn btn-sm toggle-section" data-target="kyc-content">
                        <i class="bi bi-chevron-down text-white fs-5"></i>
                    </button>
                </div>
                <div id="kyc-content" class="card-body p-3">
                    @php
                        $kycDetails = $application->kyc_details;
                        $formatKycDate = function ($value) {
                            if (empty($value)) {
                                return null;
                            }
                            if ($value instanceof \Carbon\Carbon) {
                                return $value->format('d M Y');
                            }
                            try {
                                return \Carbon\Carbon::parse($value)->format('d M Y');
                            } catch (\Exception $e) {
                                return null;
                            }
                        };
                        $formatKycDateTime = function ($value) {
                            if (empty($value)) {
                                return null;
                            }
                            if ($value instanceof \DateTimeInterface) {
                                return \Carbon\Carbon::instance($value)->format('d M Y, h:i A');
                            }
                            if ($value instanceof \Carbon\Carbon) {
                                return $value->format('d M Y, h:i A');
                            }
                            if (is_array($value)) {
                                $value = $value['date'] ?? $value['completed_at'] ?? null;
                                if (empty($value)) {
                                    return null;
                                }
                            }
                            if (is_numeric($value)) {
                                try {
                                    return \Carbon\Carbon::createFromTimestamp((int) $value)->format('d M Y, h:i A');
                                } catch (\Exception $e) {
                                    return null;
                                }
                            }
                            try {
                                return \Carbon\Carbon::parse($value)->format('d M Y, h:i A');
                            } catch (\Exception $e) {
                                if (is_string($value)) {
                                    $candidate = trim($value);
                                    // Try to extract a datetime substring from noisy text.
                                    if (preg_match('/\d{4}-\d{2}-\d{2}[T\s]\d{2}:\d{2}(?::\d{2})?(?:\.\d+)?(?:Z|[+\-]\d{2}:\d{2})?/', $candidate, $match)) {
                                        try {
                                            return \Carbon\Carbon::parse($match[0])->format('d M Y, h:i A');
                                        } catch (\Exception $e2) {
                                        }
                                    }
                                    if (preg_match('/\d{4}-\d{2}-\d{2}/', $candidate, $matchDateOnly)) {
                                        try {
                                            return \Carbon\Carbon::parse($matchDateOnly[0])->format('d M Y');
                                        } catch (\Exception $e3) {
                                        }
                                    }
                                }
                                return null;
                            }
                        };
                    @endphp
                    <div class="row g-2 app-details">
                        <!-- GST Information -->
                        @if(isset($kycDetails['gstin']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">GSTIN</label>
                            <div class="fw-medium">
                                {{ $kycDetails['gstin'] }}
                                @if($kycDetails['gst_verified'] ?? false)
                                    <span class="badge bg-success ms-1">Verified</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if(isset($kycDetails['legal_name']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Legal Name</label>
                            <div class="fw-medium">{{ $kycDetails['legal_name'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['trade_name']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Trade Name</label>
                            <div class="fw-medium">{{ $kycDetails['trade_name'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['taxpayer_type']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Taxpayer Type</label>
                            <div class="fw-medium">{{ $kycDetails['taxpayer_type'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['gst_type']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">GST Type</label>
                            <div class="fw-medium">{{ $kycDetails['gst_type'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['gstin_status']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">GSTIN Status</label>
                            <div class="fw-medium">{{ $kycDetails['gstin_status'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['company_status']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Company Status</label>
                            <div class="fw-medium">{{ $kycDetails['company_status'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['registration_date']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Registration Date</label>
                            <div class="fw-medium">{{ $formatKycDate($kycDetails['registration_date']) }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['constitution_of_business']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Constitution of Business</label>
                            <div class="fw-medium">{{ $kycDetails['constitution_of_business'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['state']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">State</label>
                            <div class="fw-medium">{{ $kycDetails['state'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['pincode']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Pincode</label>
                            <div class="fw-medium">{{ $kycDetails['pincode'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['primary_address']))
                        <div class="col-12">
                            <label class="small text-muted mb-1 d-block">Primary Address</label>
                            <div class="fw-medium">{{ $kycDetails['primary_address'] }}</div>
                        </div>
                        @endif

                        <!-- MSME Information -->
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Is MSME</label>
                            <div>
                                @if($kycDetails['is_msme'] ?? false)
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </div>
                        </div>
                        @if(isset($kycDetails['udyam_number']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">UDYAM Number</label>
                            <div class="fw-medium">
                                {{ $kycDetails['udyam_number'] }}
                                @if($kycDetails['udyam_verified'] ?? false)
                                    <span class="badge bg-success ms-1">Verified</span>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- MCA Information -->
                        @if(isset($kycDetails['cin']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">CIN</label>
                            <div class="fw-medium">
                                {{ $kycDetails['cin'] }}
                                @if($kycDetails['mca_verified'] ?? false)
                                    <span class="badge bg-success ms-1">Verified</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if(isset($kycDetails['roc_iec_number']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">ROC IEC Number</label>
                            <div class="fw-medium">
                                {{ $kycDetails['roc_iec_number'] }}
                                @if($kycDetails['roc_iec_verified'] ?? false)
                                    <span class="badge bg-success ms-1">Verified</span>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- Contact Information -->
                        @if(isset($kycDetails['contact_name']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Contact Name</label>
                            <div class="fw-medium">{{ $kycDetails['contact_name'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['contact_pan']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Contact PAN</label>
                            <div class="fw-medium">{{ $kycDetails['contact_pan'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['contact_dob']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Contact Date of Birth</label>
                            <div class="fw-medium">{{ $formatKycDate($kycDetails['contact_dob']) }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['contact_email']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Contact Email</label>
                            <div class="fw-medium">
                                {{ $kycDetails['contact_email'] }}
                                @if($kycDetails['contact_email_verified'] ?? false)
                                    <span class="badge bg-success ms-1">Verified</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if(isset($kycDetails['contact_mobile']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Contact Mobile</label>
                            <div class="fw-medium">
                                {{ $kycDetails['contact_mobile'] }}
                                @if($kycDetails['contact_mobile_verified'] ?? false)
                                    <span class="badge bg-success ms-1">Verified</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if(isset($kycDetails['contact_name_pan_dob_verified']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Contact Name/PAN/DOB Verified</label>
                            <div>
                                @if($kycDetails['contact_name_pan_dob_verified'])
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- Billing Address -->
                        @if(isset($kycDetails['billing_address']))
                        <div class="col-12">
                            <label class="small text-muted mb-1 d-block">Billing Address</label>
                            <div>
                                @php
                                    $billingAddress = $kycDetails['billing_address'];
                                    // Check if it's a JSON string
                                    if (is_string($billingAddress)) {
                                        $decoded = json_decode($billingAddress, true);
                                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                            $billingAddress = $decoded;
                                        }
                                    }
                                @endphp
                                @if(is_array($billingAddress))
                                    @if(isset($billingAddress['label']))
                                        <div class="fw-medium">{{ $billingAddress['label'] }}</div>
                                    @endif
                                    @if(isset($billingAddress['address']))
                                        <div class="fw-medium mt-1">{{ $billingAddress['address'] }}</div>
                                    @elseif(isset($billingAddress['source']))
                                        <div class="text-muted small mt-1">Source: {{ ucfirst($billingAddress['source']) }}</div>
                                    @endif
                                    @if(isset($billingAddress['state']))
                                        <div class="text-muted small">State: {{ $billingAddress['state'] }}</div>
                                    @endif
                                    @if(isset($billingAddress['pincode']))
                                        <div class="text-muted small">Pincode: {{ $billingAddress['pincode'] }}</div>
                                    @endif
                                @else
                                    {{-- Display billing address as plain text if it's not JSON --}}
                                    <div class="fw-medium">{{ $billingAddress }}</div>
                                    @if(isset($kycDetails['billing_pincode']))
                                        <div class="text-muted small mt-1">Pincode: {{ $kycDetails['billing_pincode'] }}</div>
                                    @endif
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- User Information from KYC -->
                        @if(isset($kycDetails['user_name']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">User Name</label>
                            <div class="fw-medium">{{ $kycDetails['user_name'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['user_email']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">User Email</label>
                            <div class="fw-medium">{{ $kycDetails['user_email'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['user_mobile']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">User Mobile</label>
                            <div class="fw-medium">{{ $kycDetails['user_mobile'] }}</div>
                        </div>
                        @endif

                        <!-- KYC Status -->
                        @if(isset($kycDetails['status']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">KYC Status</label>
                            <div>
                                @if($kycDetails['status'] === 'completed')
                                    <span class="badge bg-success">Completed</span>
                                @else
                                    <span class="badge bg-warning">{{ ucfirst($kycDetails['status']) }}</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if(isset($kycDetails['completed_at']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Completed At</label>
                            <div class="fw-medium">{{ $formatKycDateTime($kycDetails['completed_at']) ?? 'N/A' }}</div>
                        </div>
                        @endif

                        @if($application->application_type === 'IRINN')
                        <!-- Management Representative (IRINN) -->
                        <div class="col-12 mt-3">
                            <h6 class="mb-2 text-primary">Management Representative</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted mb-1 d-block">Name</label>
                            <div class="fw-medium">{{ $kycDetails['management_name'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted mb-1 d-block">Mobile</label>
                            <div class="fw-medium">{{ $kycDetails['management_mobile'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted mb-1 d-block">Email</label>
                            <div class="fw-medium">{{ $kycDetails['management_email'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted mb-1 d-block">PAN</label>
                            <div class="fw-medium">{{ $kycDetails['management_pan'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted mb-1 d-block">DIN</label>
                            <div class="fw-medium">{{ $kycDetails['management_din'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted mb-1 d-block">Date of Birth</label>
                            <div class="fw-medium">{{ $formatKycDate($kycDetails['management_dob'] ?? null) ?? 'N/A' }}</div>
                        </div>

                        <!-- Authorized Representative / WHOIS Contact (IRINN) -->
                        <div class="col-12 mt-3">
                            <h6 class="mb-2 text-primary">Authorized Representative / WHOIS Contact</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted mb-1 d-block">Name</label>
                            <div class="fw-medium">{{ $kycDetails['authorized_name'] ?? $kycDetails['contact_name'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted mb-1 d-block">Mobile</label>
                            <div class="fw-medium">{{ $kycDetails['authorized_mobile'] ?? $kycDetails['contact_mobile'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted mb-1 d-block">Email</label>
                            <div class="fw-medium">{{ $kycDetails['authorized_email'] ?? $kycDetails['contact_email'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted mb-1 d-block">PAN</label>
                            <div class="fw-medium">{{ $kycDetails['authorized_pan'] ?? $kycDetails['contact_pan'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted mb-1 d-block">Date of Birth</label>
                            <div class="fw-medium">{{ $formatKycDate($kycDetails['authorized_dob'] ?? $kycDetails['contact_dob'] ?? null) ?? 'N/A' }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Authorized Representative Details -->
            @if($application->authorized_representative_details)
            <div id="section-representative" class="card border-c-blue shadow-sm mb-3 comp-section" style="border-radius: 16px; display: none;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">Authorized Representative Details</h6>
                    <button type="button" class="btn btn-sm toggle-section" data-target="representative-content">
                        <i class="bi bi-chevron-down text-white fs-5"></i>
                    </button>
                </div>
                <div id="representative-content" class="card-body p-3">
                    @php
                        $repDetails = $application->authorized_representative_details;
                    @endphp
                    <div class="row g-2 app-details">
                        @if(isset($repDetails['name']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Name</label>
                            <div class="fw-medium">{{ $repDetails['name'] }}</div>
                        </div>
                        @endif
                        @if(isset($repDetails['pan']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">PAN Card</label>
                            <div class="fw-medium">{{ $repDetails['pan'] }}</div>
                        </div>
                        @endif
                        @if(isset($repDetails['dob']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Date of Birth</label>
                            <div class="fw-medium">{{ $repDetails['dob'] }}</div>
                        </div>
                        @endif
                        @if(isset($repDetails['email']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Email</label>
                            <div class="fw-medium">{{ $repDetails['email'] }}</div>
                        </div>
                        @endif
                        @if(isset($repDetails['mobile']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Mobile</label>
                            <div class="fw-medium">{{ $repDetails['mobile'] }}</div>
                        </div>
                        @endif
                        @if(isset($repDetails['address']))
                        <div class="col-12">
                            <label class="small text-muted mb-1 d-block">Address</label>
                            <div class="fw-medium">{{ $repDetails['address'] }}</div>
                        </div>
                        @endif
                        @if(isset($repDetails['city']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">City</label>
                            <div class="fw-medium">{{ $repDetails['city'] }}</div>
                        </div>
                        @endif
                        @if(isset($repDetails['state']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">State</label>
                            <div class="fw-medium">{{ $repDetails['state'] }}</div>
                        </div>
                        @endif
                        @if(isset($repDetails['pincode']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Pincode</label>
                            <div class="fw-medium">{{ $repDetails['pincode'] }}</div>
                        </div>
                        @endif
                        @if(isset($repDetails['designation']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Designation</label>
                            <div class="fw-medium">{{ $repDetails['designation'] }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            @if($application->getEffectivePortCapacity())
            <!-- Plan Change Section -->
            <div id="section-plan-change" class="card border-c-blue shadow-sm mb-3 comp-section" style="border-radius: 16px; display: none;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">Plan Management</h6>
                    <button type="button" class="btn btn-sm toggle-section" data-target="plan-change-content">
                        <i class="bi bi-chevron-down text-white fs-5"></i>
                    </button>
                </div>
                <div id="plan-change-content" class="card-body p-3">
                    @php
                        $appData = $application->application_data ?? [];
                        $portSelection = $appData['port_selection'] ?? [];
                        $currentCapacity = $application->getEffectivePortCapacity() ?? 'N/A';
                        $currentBillingPlan = $portSelection['billing_plan'] ?? 'N/A';
                        $currentAmount = $portSelection['amount'] ?? 0;
                    @endphp
                    
                    <div class="row g-2 app-details">
                        <div class="col-md-6">
                            <div class="card border-c-blue shadow-sm h-100" style="border-radius: 12px;">
                                <div class="card-header theme-bg-blue" style="border-radius: 12px 12px 0 0;">
                                    <h6 class="mb-0 small">Current Plan</h6>
                                </div>
                                <div class="card-body p-2">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="small text-muted mb-1 d-block">Port Capacity</label>
                                            <div class="fw-bold small">{{ $currentCapacity }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="small text-muted mb-1 d-block">Billing Cycle</label>
                                            <div class="small">{{ strtoupper($currentBillingPlan) }}</div>
                                        </div>
                                        <div class="col-md-6 mb-0">
                                            <label class="small text-muted mb-1 d-block">Amount</label>
                                            <div class="small">₹{{ number_format($currentAmount, 2) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-c-blue shadow-sm h-100" style="border-radius: 12px;">
                                <div class="card-header bg-{{ $approvedPlanChange ? 'warning' : ($pendingPlanChange ? 'info' : 'light') }}" style="border-radius: 12px 12px 0 0;">
                                    <h6 class="mb-0 small text-blue" style="color: var(--theme-blue) !important;">Plan Change Status</h6>
                                </div>
                                <div class="card-body p-2">
                                    @if($approvedPlanChange)
                                        <div class="alert alert-warning mb-0 py-2 mt-2">
                                            <strong class="small">Approved Plan Change</strong>
                                            <p class="mb-1 small">
                                                <strong>{{ $approvedPlanChange->current_port_capacity }}</strong> → <strong>{{ $approvedPlanChange->new_port_capacity }}</strong> 
                                                ({{ strtoupper($approvedPlanChange->new_billing_plan) }})
                                            </p>
                                            <p class="mb-0 small">
                                                <strong>Effective From:</strong> {{ \Carbon\Carbon::parse($approvedPlanChange->effective_from)->format('d M Y') }}
                                            </p>
                                            <p class="mb-0 mt-1 small text-muted">
                                                Cannot request new change until effective date.
                                            </p>
                                        </div>
                                    @elseif($pendingPlanChange)
                                        <div class="alert alert-info mb-0 py-2">
                                            <strong class="small">Pending Request</strong>
                                            <p class="mb-1 small">
                                                <strong>{{ $pendingPlanChange->current_port_capacity }}</strong> → <strong>{{ $pendingPlanChange->new_port_capacity }}</strong> 
                                                ({{ strtoupper($pendingPlanChange->new_billing_plan) }})
                                            </p>
                                            <p class="mb-0 small">
                                                <strong>Requested:</strong> {{ $pendingPlanChange->created_at->format('d M Y') }}
                                            </p>
                                        </div>
                                    @else
                                        <div class="text-center">
                                            <p class="text-muted mb-2 small">No active plan change request</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Plan Change History -->
                    @if($application->planChangeRequests && $application->planChangeRequests->count() > 0)
                    <div class="mt-4">
                        <h6 class="mb-3 text-primary">Plan Change History</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th class="small">Request Date</th>
                                        <th class="small">From</th>
                                        <th class="small">To</th>
                                        <th class="small">Billing Plan</th>
                                        <th class="small">Amount</th>
                                        <th class="small">Status</th>
                                        <th class="small">Reviewed By</th>
                                        <th class="small">Reviewed At</th>
                                        <th class="small">Effective From</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($application->planChangeRequests->sortByDesc('created_at') as $planChange)
                                    <tr>
                                        <td class="small">{{ $planChange->created_at->format('d M Y, h:i A') }}</td>
                                        <td class="small">{{ $planChange->current_port_capacity }}</td>
                                        <td class="small">{{ $planChange->new_port_capacity }}</td>
                                        <td class="small">{{ strtoupper($planChange->new_billing_plan) }}</td>
                                        <td class="small">
                                            @if($planChange->new_amount)
                                                ₹{{ number_format($planChange->new_amount, 2) }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="small">
                                            @if($planChange->status === 'approved')
                                                <span class="badge bg-success">Approved</span>
                                            @elseif($planChange->status === 'rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                            @elseif($planChange->status === 'pending')
                                                <span class="badge bg-warning">Pending</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($planChange->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="small">
                                            @if($planChange->reviewedBy)
                                                {{ $planChange->reviewedBy->name }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="small">
                                            @if($planChange->reviewed_at)
                                                {{ $planChange->reviewed_at->format('d M Y, h:i A') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="small">
                                            @if($planChange->effective_from)
                                                {{ \Carbon\Carbon::parse($planChange->effective_from)->format('d M Y') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if($planChange->reason || $planChange->admin_notes)
                                    <tr>
                                        <td colspan="9" class="small text-muted">
                                            @if($planChange->reason)
                                                <strong>Reason:</strong> {{ $planChange->reason }}
                                            @endif
                                            @if($planChange->admin_notes)
                                                @if($planChange->reason) | @endif
                                                <strong>Admin Notes:</strong> {{ $planChange->admin_notes }}
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @else
                    <div class="mt-4">
                        <p class="text-muted small mb-0">No plan change history available.</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- IRINN Application Data -->
            @if($application->application_type === 'IRINN')
                @php
                    $appData = $application->application_data ?? [];
                @endphp
                <div id="section-irin-application-data" class="card border-c-blue shadow-sm mb-3 comp-section" style="border-radius: 16px; display: none;">
                    <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                        <h6 class="mb-0">IRINN Application Data</h6>
                        <button type="button" class="btn btn-sm toggle-section" data-target="irinn-application-data-content">
                            <i class="bi bi-chevron-down text-white fs-5"></i>
                        </button>
                    </div>
                    <div id="irinn-application-data-content" class="card-body p-3" style="display: block;">
                        @if(!empty($appData))
                            <div class="row g-2 app-details">
                                @if(isset($appData['part1']))
                                <div class="col-12 mt-2">
                                    <h6 class="mb-2 text-primary">Part 1: Application [IRINN]</h6>
                                    <div class="row g-2">
                                        @if(isset($appData['part1']['affiliate_type']))
                                        <div class="col-md-6">
                                            <label class="small text-muted mb-1 d-block">Affiliate Type</label>
                                            <div class="fw-medium">{{ ucfirst($appData['part1']['affiliate_type']) }}</div>
                                        </div>
                                        @endif
                                        @if(isset($appData['part1']['domain_required']))
                                        <div class="col-md-6">
                                            <label class="small text-muted mb-1 d-block">.IN Domain Required</label>
                                            <div class="fw-medium">{{ ucfirst($appData['part1']['domain_required']) }}</div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                @if(isset($appData['part2']))
                                <div class="col-12 mt-3">
                                    <h6 class="mb-2 text-primary">Part 2: New Resources</h6>
                                    <div class="row g-2">
                                        @if(isset($appData['part2']['ipv4_prefix']))
                                        <div class="col-md-4">
                                            <label class="small text-muted mb-1 d-block">IPv4 Prefix</label>
                                            <div class="fw-medium">{{ $appData['part2']['ipv4_prefix'] }}</div>
                                        </div>
                                        @endif
                                        @if(isset($appData['part2']['ipv6_prefix']))
                                        <div class="col-md-4">
                                            <label class="small text-muted mb-1 d-block">IPv6 Prefix</label>
                                            <div class="fw-medium">{{ $appData['part2']['ipv6_prefix'] }}</div>
                                        </div>
                                        @endif
                                        @if(isset($appData['part2']['asn_required']))
                                        <div class="col-md-4">
                                            <label class="small text-muted mb-1 d-block">ASN Required</label>
                                            <div class="fw-medium">{{ ucfirst($appData['part2']['asn_required']) }}</div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                @if(isset($appData['part3']))
                                <div class="col-12 mt-3">
                                    <h6 class="mb-2 text-primary">Part 3: IRINN Agreement and Documents</h6>
                                    <div class="row g-2">
                                        @if(isset($appData['part3']['board_resolution_file']))
                                        <div class="col-md-6">
                                            <label class="small text-muted mb-1 d-block">Board Resolution</label>
                                            <div class="fw-medium">
                                                @if($appData['part3']['board_resolution_file'])
                                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'board_resolution_file']) }}" target="_blank" class="text-primary">View Document</a>
                                                @else
                                                    <span class="text-muted">Not uploaded</span>
                                                @endif
                                            </div>
                                        </div>
                                        @endif
                                        @if(isset($appData['part3']['irinn_agreement_file']))
                                        <div class="col-md-6">
                                            <label class="small text-muted mb-1 d-block">IRINN Agreement</label>
                                            <div class="fw-medium">
                                                @if($appData['part3']['irinn_agreement_file'])
                                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'irinn_agreement_file']) }}" target="_blank" class="text-primary">View Document</a>
                                                @else
                                                    <span class="text-muted">Not uploaded</span>
                                                @endif
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                @if(isset($appData['part4']))
                                <div class="col-12 mt-3">
                                    <h6 class="mb-2 text-primary">Part 4: Resource Justification Requirement</h6>
                                    <div class="row g-2">
                                        @if(isset($appData['part4']['network_diagram_file']))
                                        <div class="col-md-6">
                                            <label class="small text-muted mb-1 d-block">Network Diagram</label>
                                            <div class="fw-medium">
                                                @if($appData['part4']['network_diagram_file'])
                                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'network_diagram_file']) }}" target="_blank" class="text-primary">View Document</a>
                                                @else
                                                    <span class="text-muted">Not uploaded</span>
                                                @endif
                                            </div>
                                        </div>
                                        @endif
                                        @if(isset($appData['part4']['equipment_invoice_file']))
                                        <div class="col-md-6">
                                            <label class="small text-muted mb-1 d-block">Equipment Invoice</label>
                                            <div class="fw-medium">
                                                @if($appData['part4']['equipment_invoice_file'])
                                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'equipment_invoice_file']) }}" target="_blank" class="text-primary">View Document</a>
                                                @else
                                                    <span class="text-muted">Not uploaded</span>
                                                @endif
                                            </div>
                                        </div>
                                        @endif
                                        @if(isset($appData['part4']['bandwidth_invoice_file']))
                                        <div class="col-md-6">
                                            <label class="small text-muted mb-1 d-block">Bandwidth Invoices</label>
                                            <div class="fw-medium">
                                                @if(!empty($appData['part4']['bandwidth_invoice_file']) && is_array($appData['part4']['bandwidth_invoice_file']))
                                                    @foreach($appData['part4']['bandwidth_invoice_file'] as $index => $file)
                                                        <div><a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'bandwidth_invoice_file', 'index' => $index]) }}" target="_blank" class="text-primary">View Document {{ $loop->iteration }}</a></div>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">Not uploaded</span>
                                                @endif
                                            </div>
                                        </div>
                                        @endif
                                        @if(isset($appData['part4']['bandwidth_agreement_file']))
                                        <div class="col-md-6">
                                            <label class="small text-muted mb-1 d-block">Bandwidth Agreement</label>
                                            <div class="fw-medium">
                                                @if($appData['part4']['bandwidth_agreement_file'])
                                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'bandwidth_agreement_file']) }}" target="_blank" class="text-primary">View Document</a>
                                                @else
                                                    <span class="text-muted">Not uploaded</span>
                                                @endif
                                            </div>
                                        </div>
                                        @endif
                                        @if(isset($appData['part4']['upstream_provider']))
                                        <div class="col-12 mt-2">
                                            <h6 class="mb-2">Upstream Provider Details</h6>
                                            <div class="row g-2">
                                                @if(isset($appData['part4']['upstream_provider']['name']))
                                                <div class="col-md-6">
                                                    <label class="small text-muted mb-1 d-block">Name</label>
                                                    <div class="fw-medium">{{ $appData['part4']['upstream_provider']['name'] }}</div>
                                                </div>
                                                @endif
                                                @if(isset($appData['part4']['upstream_provider']['mobile']))
                                                <div class="col-md-6">
                                                    <label class="small text-muted mb-1 d-block">Mobile</label>
                                                    <div class="fw-medium">{{ $appData['part4']['upstream_provider']['mobile'] }}</div>
                                                </div>
                                                @endif
                                                @if(isset($appData['part4']['upstream_provider']['email']))
                                                <div class="col-md-6">
                                                    <label class="small text-muted mb-1 d-block">Email</label>
                                                    <div class="fw-medium">{{ $appData['part4']['upstream_provider']['email'] }}</div>
                                                </div>
                                                @endif
                                                @if(isset($appData['part4']['upstream_provider']['org_name']))
                                                <div class="col-md-6">
                                                    <label class="small text-muted mb-1 d-block">Organization Name</label>
                                                    <div class="fw-medium">{{ $appData['part4']['upstream_provider']['org_name'] }}</div>
                                                </div>
                                                @endif
                                                @if(isset($appData['part4']['upstream_provider']['asn_details']))
                                                <div class="col-md-12">
                                                    <label class="small text-muted mb-1 d-block">ASN Details</label>
                                                    <div class="fw-medium">{{ $appData['part4']['upstream_provider']['asn_details'] }}</div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                @if(isset($appData['part5']))
                                <div class="col-12 mt-3">
                                    <h6 class="mb-2 text-primary">Part 5: Payment</h6>
                                    <div class="row g-2">
                                        @if(isset($appData['part5']['application_fee']))
                                        <div class="col-md-6">
                                            <label class="small text-muted mb-1 d-block">Application Fee</label>
                                            <div class="fw-medium">₹{{ number_format($appData['part5']['application_fee'], 2) }}</div>
                                        </div>
                                        @endif
                                        @if(isset($appData['part5']['gst_amount']))
                                        <div class="col-md-6">
                                            <label class="small text-muted mb-1 d-block">GST Amount</label>
                                            <div class="fw-medium">₹{{ number_format($appData['part5']['gst_amount'], 2) }}</div>
                                        </div>
                                        @endif
                                        @if(isset($appData['part5']['total_amount']))
                                        <div class="col-md-6">
                                            <label class="small text-muted mb-1 d-block">Total Amount</label>
                                            <div class="fw-medium">₹{{ number_format($appData['part5']['total_amount'], 2) }}</div>
                                        </div>
                                        @endif
                                        @if(isset($appData['part5']['payment_status']))
                                        <div class="col-md-6">
                                            <label class="small text-muted mb-1 d-block">Payment Status</label>
                                            <div>
                                                @if($appData['part5']['payment_status'] === 'success')
                                                    <span class="badge bg-success">Paid</span>
                                                @elseif($appData['part5']['payment_status'] === 'pending')
                                                    <span class="badge bg-warning">Pending</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($appData['part5']['payment_status']) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        @else
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle me-2"></i> No application data available yet. Please submit your application.
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Application Data -->
            @if($application->application_type === 'IX' && $application->application_data)
            <div id="section-application-data" class="card border-c-blue shadow-sm mb-3 comp-section" style="border-radius: 16px; display: none;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">Application Data</h6>
                    <button type="button" class="btn btn-sm toggle-section" data-target="application-data-content">
                        <i class="bi bi-chevron-down text-white fs-5"></i>
                    </button>
                </div>
                <div id="application-data-content" class="card-body p-3">
                    @php
                        $appData = $application->application_data ?? [];
                    @endphp
                    <div class="row g-2 app-details">
                        {{-- IX Application specific data --}}
                        @if($application->application_type === 'IX' && isset($appData['location']))
                        <div class="col-12">
                            <h6 class="mb-2 text-primary">Location Details</h6>
                            <div class="row g-2">
                                @if(isset($appData['location']['name']))
                                <div class="col-md-6">
                                    <label class="small text-muted mb-1 d-block">Location Name</label>
                                    <div class="fw-medium">{{ $appData['location']['name'] }}</div>
                                </div>
                                @endif
                                @if(isset($appData['location']['state']))
                                <div class="col-md-6">
                                    <label class="small text-muted mb-1 d-block">State</label>
                                    <div class="fw-medium">{{ $appData['location']['state'] }}</div>
                                </div>
                                @endif
                                @if(isset($appData['location']['city']))
                                <div class="col-md-6">
                                    <label class="small text-muted mb-1 d-block">City</label>
                                    <div class="fw-medium">{{ $appData['location']['city'] }}</div>
                                </div>
                                @endif
                                @if(isset($appData['location']['address']))
                                <div class="col-12">
                                    <label class="small text-muted mb-1 d-block">Address</label>
                                    <div class="fw-medium">{{ $appData['location']['address'] }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        @if($application->application_type === 'IX' && isset($appData['port_selection']))
                        <div class="col-12 mt-3">
                            <h6 class="mb-2 text-primary">Port Selection</h6>
                            <div class="row g-2">
                                @if($application->getEffectivePortCapacity())
                                <div class="col-md-6">
                                    <label class="small text-muted mb-1 d-block">Capacity</label>
                                    <div class="fw-medium">{{ $application->getEffectivePortCapacity() }}</div>
                                </div>
                                @endif
                                @if(isset($appData['port_selection']['billing_plan']))
                                <div class="col-md-6">
                                    <label class="small text-muted mb-1 d-block">Billing Plan</label>
                                    <div class="fw-medium">{{ strtoupper($appData['port_selection']['billing_plan']) }}</div>
                                </div>
                                @endif
                                @if(isset($appData['port_selection']['amount']))
                                <div class="col-md-6">
                                    <label class="small text-muted mb-1 d-block">Amount</label>
                                    <div class="fw-medium">₹{{ number_format($appData['port_selection']['amount'], 2) }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        @if($application->application_type === 'IX' && isset($appData['gstin']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">GSTIN (from Application Data)</label>
                            <div class="fw-medium">{{ $appData['gstin'] }}</div>
                        </div>
                        @endif

                        @if($application->application_type === 'IX' && isset($appData['payment']))
                        <div class="col-12 mt-3">
                            <h6 class="mb-2 text-primary">Payment Information</h6>
                            <div class="row g-2">
                                @if(isset($appData['payment']['status']))
                                <div class="col-md-6">
                                    <label class="small text-muted mb-1 d-block">Payment Status</label>
                                    <div>
                                        @if($appData['payment']['status'] === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-warning">{{ ucfirst($appData['payment']['status']) }}</span>
                                        @endif
                                    </div>
                                </div>
                                @endif
                                @if(isset($appData['payment']['amount']))
                                <div class="col-md-6">
                                    <label class="small text-muted mb-1 d-block">Payment Amount</label>
                                    <div class="fw-medium">₹{{ number_format($appData['payment']['amount'], 2) }}</div>
                                </div>
                                @endif
                                @if(isset($appData['payment']['transaction_id']))
                                <div class="col-md-6">
                                    <label class="small text-muted mb-1 d-block">Transaction ID</label>
                                    <div class="fw-medium">{{ $appData['payment']['transaction_id'] }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        {{-- IRINN Application specific data (new workflow) --}}
                        @if($application->application_type === 'IRINN')
                            @php
                                $part1 = $appData['part1'] ?? [];
                                $part2 = $appData['part2'] ?? [];
                                $part4 = $appData['part4'] ?? [];
                                $part5 = $appData['part5'] ?? [];
                                $upstream = $part4['upstream_provider'] ?? [];
                            @endphp

                            <div class="col-12">
                                <h6 class="mb-2 text-primary">Step 1 – Application</h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="small text-muted mb-1 d-block">Affiliate Type</label>
                                        <div class="fw-medium">
                                            @php
                                                $affiliate = $part1['affiliate_type'] ?? null;
                                            @endphp
                                            @if($affiliate === 'new')
                                                Affiliate – New
                                            @elseif($affiliate === 'transfer')
                                                Affiliate – Transfer from APNIC
                                            @else
                                                —
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small text-muted mb-1 d-block">.IN Domain Required</label>
                                        <div class="fw-medium">
                                            @php
                                                $domainReq = $part1['domain_required'] ?? null;
                                            @endphp
                                            @if($domainReq === 'yes')
                                                Yes
                                            @elseif($domainReq === 'no')
                                                No
                                            @else
                                                —
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted mb-1 d-block">IPv4 Prefix</label>
                                        <div class="fw-medium">{{ $part2['ipv4_prefix'] ?? '—' }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted mb-1 d-block">IPv6 Prefix</label>
                                        <div class="fw-medium">{{ $part2['ipv6_prefix'] ?? '—' }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted mb-1 d-block">ASN Required</label>
                                        <div class="fw-medium">
                                            @php
                                                $asnReq = $part2['asn_required'] ?? null;
                                            @endphp
                                            @if($asnReq === 'yes' || $asnReq === '1')
                                                Yes
                                            @elseif($asnReq === 'no' || $asnReq === '0')
                                                No
                                            @else
                                                —
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-3">
                                <h6 class="mb-2 text-primary">Step 4 – Upstream Provider Details</h6>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="small text-muted mb-1 d-block">Provider Name</label>
                                        <div class="fw-medium">{{ $upstream['name'] ?? '—' }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted mb-1 d-block">Mobile</label>
                                        <div class="fw-medium">{{ $upstream['mobile'] ?? '—' }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted mb-1 d-block">Email</label>
                                        <div class="fw-medium">{{ $upstream['email'] ?? '—' }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted mb-1 d-block">Organization Name</label>
                                        <div class="fw-medium">{{ $upstream['org_name'] ?? '—' }}</div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="small text-muted mb-1 d-block">Peering ASN Details</label>
                                        <div class="fw-medium">{{ $upstream['asn_details'] ?? '—' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-3">
                                <h6 class="mb-2 text-primary">Step 5 – IRINN Fee & Payment</h6>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="small text-muted mb-1 d-block">Application Fee (Base)</label>
                                        <div class="fw-medium">
                                            @php $fee = $part5['application_fee'] ?? null; @endphp
                                            {{ $fee !== null ? '₹'.number_format((float) $fee, 2) : '—' }}
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted mb-1 d-block">GST %</label>
                                        <div class="fw-medium">
                                            @php $gstPct = $part5['gst_percentage'] ?? null; @endphp
                                            {{ $gstPct !== null ? $gstPct.'%' : '—' }}
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted mb-1 d-block">GST Amount</label>
                                        <div class="fw-medium">
                                            @php $gstAmt = $part5['gst_amount'] ?? null; @endphp
                                            {{ $gstAmt !== null ? '₹'.number_format((float) $gstAmt, 2) : '—' }}
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted mb-1 d-block">Total Amount (Incl. GST)</label>
                                        <div class="fw-medium">
                                            @php $totalAmt = $part5['total_amount'] ?? ($part5['total_payable'] ?? null); @endphp
                                            {{ $totalAmt !== null ? '₹'.number_format((float) $totalAmt, 2) : '—' }}
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted mb-1 d-block">Payment Status</label>
                                        <div class="fw-medium">
                                            @php $payStatus = $part5['payment_status'] ?? null; @endphp
                                            @if($payStatus)
                                                <span class="badge bg-{{ $payStatus === 'completed' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($payStatus) }}
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Application Status -->
            <div id="section-status" class="card border-c-blue shadow-sm mb-3 comp-section" style="border-radius: 16px; display: none;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">Application Status</h6>
                    <button type="button" class="btn btn-sm toggle-section" data-target="status-content">
                        <i class="bi bi-chevron-down text-white fs-5"></i>
                    </button>
                </div>
                <div id="status-content" class="card-body p-3">
                    @php
                        if($application->application_type === 'IX') {
                            $stages = ['IX Processor', 'IX Legal', 'IX Head', 'CEO', 'Nodal Officer', 'IX Tech Team', 'IX Account', 'Completed'];
                            $isCompleted = ($application->is_active && $application->service_activation_date) || in_array($application->status, ['payment_verified', 'approved']);
                            
                            $processorCompleted = in_array($application->status, ['processor_forwarded_legal', 'legal_forwarded_head', 'head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                            $legalCompleted = in_array($application->status, ['legal_forwarded_head', 'head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                            $headCompleted = in_array($application->status, ['head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                            $ceoCompleted = in_array($application->status, ['ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                            $nodalCompleted = in_array($application->status, ['port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                            $techCompleted = in_array($application->status, ['ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                            $accountCompleted = ($application->is_active && $application->service_activation_date) || in_array($application->status, ['payment_verified', 'approved', 'ip_assigned', 'invoice_pending']);
                            $completedCompleted = $isCompleted;
                        }
                    @endphp
                    @if($application->application_type === 'IX')
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    @if($processorCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2"></i>
                                    @endif
                                    <span class="small fw-medium">IX Processor</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    @if($legalCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2"></i>
                                    @endif
                                    <span class="small fw-medium">IX Legal</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    @if($headCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2"></i>
                                    @endif
                                    <span class="small fw-medium">IX Head</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    @if($ceoCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2"></i>
                                    @endif
                                    <span class="small fw-medium">CEO</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    @if($nodalCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2"></i>
                                    @endif
                                    <span class="small fw-medium">Nodal Officer</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    @if($techCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2"></i>
                                    @endif
                                    <span class="small fw-medium">IX Tech Team</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    @if($accountCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2"></i>
                                    @endif
                                    <span class="small fw-medium">IX Account</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    @if($completedCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2"></i>
                                    @endif
                                    <span class="small fw-medium">Completed</span>
                                </div>
                            </div>
                        </div>
                    @else
                    @php
                        $statusSlug = strtolower(trim((string) $application->status));
                        $flowSteps = [
                            'helpdesk' => 'Helpdesk',
                            'hostmaster' => 'Hostmaster',
                            'billing' => 'Billing',
                        ];
                    @endphp
                    <div class="rounded border px-3 py-2 mb-3" style="background:#f8fafc;">
                        <div class="small text-muted mb-2">IRINN Workflow</div>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                        @foreach($flowSteps as $slug => $label)
                            @php
                                $isCurrent = $statusSlug === $slug;
                                $isCompleted = match($slug) {
                                    'helpdesk' => in_array($statusSlug, ['hostmaster','billing']),
                                    'hostmaster' => $statusSlug === 'billing',
                                    'billing' => $statusSlug === 'billing',
                                    default => false,
                                };
                            @endphp
                            <div class="d-flex align-items-center">
                                <div class="d-flex align-items-center px-2 py-1 rounded-pill"
                                     style="background: {{ $isCurrent ? '#ffffff' : 'rgba(148,163,184,0.15)' }};">
                                    <span class="me-2">
                                        @if($isCompleted || $isCurrent)
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                        @else
                                            <i class="bi bi-circle text-muted"></i>
                                        @endif
                                    </span>
                                    <span class="small fw-semibold"
                                          style="color: {{ $isCurrent ? 'var(--theme-blue, #2B2F6C)' : '#4b5563' }};">
                                        {{ $label }}
                                    </span>
                                </div>
                                @if(! $loop->last)
                                    <span class="mx-1 text-muted">
                                        <i class="bi bi-arrow-right"></i>
                                    </span>
                                @endif
                            </div>
                        @endforeach
                        </div>
                        @if(!array_key_exists($statusSlug, $flowSteps))
                            <div class="small text-muted mt-2">
                                Current status: <strong>{{ ucwords(str_replace('_', ' ', $statusSlug)) }}</strong>
                            </div>
                        @endif
                    </div>
                    @endif

                    @if($application->statusHistory && $application->statusHistory->count() > 0)
                    <hr class="my-3">
                    <h6 class="mb-2 text-primary">Application History</h6>
                    <div class="timeline">
                        @foreach($application->statusHistory->sortByDesc('created_at') as $history)
                        <div class="mb-2 pb-2 border-bottom px-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="pe-3">
                                    <strong class="small">{{ $history->status_display ?? ucfirst($history->status) }}</strong>
                                    @if($history->notes)
                                        <p class="mb-0 text-muted small mt-1">{{ $history->notes }}</p>
                                    @endif
                                </div>
                                <small class="text-muted text-nowrap ms-2">{{ $history->created_at->format('d M Y, h:i A') }}</small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            <!-- GST Change History -->
            <div id="section-gst-history" class="card border-c-blue shadow-sm mb-3 comp-section" style="border-radius: 16px; display: none;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">GST Change History</h6>
                    <button type="button" class="btn btn-sm toggle-section" data-target="gst-history-content">
                        <i class="bi bi-chevron-down text-white fs-5"></i>
                    </button>
                </div>
                <div id="gst-history-content" class="card-body p-3">
                    @if($application->gstChangeHistory && $application->gstChangeHistory->count() > 0)
                    <div class="timeline">
                        @foreach($application->gstChangeHistory as $history)
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong class="small">GST Changed</strong>
                                    <div class="text-muted small mt-1">
                                        <span class="badge bg-secondary">{{ $history->old_gstin ?? 'N/A' }}</span>
                                        <i class="bi bi-arrow-right mx-2"></i>
                                        <span class="badge bg-primary">{{ $history->new_gstin }}</span>
                                    </div>
                                </div>
                                <small class="text-muted">{{ $history->created_at->format('d M Y, h:i A') }}</small>
                            </div>
                            @if($history->notes)
                                <p class="mb-0 text-muted small">{{ $history->notes }}</p>
                            @endif
                            @if($history->changedBy)
                                <p class="mb-0 text-muted small mt-1">
                                    <strong>Changed by:</strong> {{ $history->changedBy->name ?? 'System' }}
                                    @if($history->changed_by_type)
                                        <span class="badge bg-info ms-2">{{ ucfirst($history->changed_by_type) }}</span>
                                    @endif
                                </p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">No GST change history available for this application.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Application Modal -->
<div class="modal fade" id="updateApplicationModal" tabindex="-1" aria-labelledby="updateApplicationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateApplicationModalLabel">Update Application - {{ $application->application_id }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="updateApplicationForm" action="{{ route('admin.applications.update-comprehensive', $application->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div class="accordion" id="updateAccordion">
                        <!-- Application Information -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAppInfo">
                                    Application Information
                                </button>
                            </h2>
                            <div id="collapseAppInfo" class="accordion-collapse collapse show" data-bs-parent="#updateAccordion">
                                <div class="accordion-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Application ID</label>
                                            <input type="text" name="application_id" class="form-control" value="{{ $application->application_id }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Status</label>
                                            <input type="text" name="status" class="form-control" value="{{ $application->status }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Current Stage</label>
                                            <input type="text" name="current_stage" class="form-control" value="{{ $application->current_stage }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">PAN Card Number</label>
                                            <input type="text" name="pan_card_no" class="form-control" value="{{ $application->pan_card_no }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Submitted At</label>
                                            <input type="datetime-local" name="submitted_at" class="form-control" value="{{ $application->submitted_at ? $application->submitted_at->format('Y-m-d\TH:i') : '' }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Approved At</label>
                                            <input type="datetime-local" name="approved_at" class="form-control" value="{{ $application->approved_at ? $application->approved_at->format('Y-m-d\TH:i') : '' }}">
                                        </div>
                                        @if($application->application_type === 'IX')
                                        <div class="col-md-6">
                                            <label class="form-label">Membership ID</label>
                                            <input type="text" name="membership_id" class="form-control" value="{{ $application->membership_id }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Customer ID</label>
                                            <input type="text" name="customer_id" class="form-control" value="{{ $application->customer_id }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Assigned IP</label>
                                            <input type="text" name="assigned_ip" class="form-control" value="{{ $application->assigned_ip }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Port Number</label>
                                            <input type="text" name="assigned_port_number" class="form-control" value="{{ $application->assigned_port_number }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Port Capacity</label>
                                            <input type="text" name="assigned_port_capacity" class="form-control" value="{{ $application->assigned_port_capacity }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Service Activation Date</label>
                                            <input type="date" name="service_activation_date" class="form-control" value="{{ $application->service_activation_date ? $application->service_activation_date->format('Y-m-d') : '' }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Billing Cycle</label>
                                            <input type="text" name="billing_cycle" class="form-control" value="{{ $application->billing_cycle }}">
                                        </div>
                                        @endif
                                        <div class="col-md-6">
                                            <label class="form-label">Is Active</label>
                                            <select name="is_active" class="form-control">
                                                <option value="1" {{ $application->is_active ? 'selected' : '' }}>Yes</option>
                                                <option value="0" {{ !$application->is_active ? 'selected' : '' }}>No</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Rejection Reason</label>
                                            <textarea name="rejection_reason" class="form-control" rows="2">{{ $application->rejection_reason }}</textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Resubmission Query</label>
                                            <textarea name="resubmission_query" class="form-control" rows="2">{{ $application->resubmission_query }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Registration Details -->
                        @if($application->registration_details)
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRegDetails">
                                    Registration Details
                                </button>
                            </h2>
                            <div id="collapseRegDetails" class="accordion-collapse collapse" data-bs-parent="#updateAccordion">
                                <div class="accordion-body">
                                    <div class="row g-3">
                                        @php
                                            $regDetails = $application->registration_details;
                                        @endphp
                                        @foreach($regDetails as $key => $value)
                                        <div class="col-md-6">
                                            <label class="form-label">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                            @if(is_array($value))
                                                @foreach($value as $subKey => $subValue)
                                                <div class="mb-2">
                                                    <label class="small text-muted">{{ ucwords(str_replace('_', ' ', $subKey)) }}</label>
                                                    <input type="text" name="registration_details[{{ $key }}][{{ $subKey }}]" class="form-control form-control-sm" value="{{ $subValue ?? '' }}">
                                                </div>
                                                @endforeach
                                            @else
                                                <input type="text" name="registration_details[{{ $key }}]" class="form-control" value="{{ $value ?? '' }}">
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- KYC Details -->
                        @if($application->kyc_details)
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseKycDetails">
                                    KYC Details
                                </button>
                            </h2>
                            <div id="collapseKycDetails" class="accordion-collapse collapse" data-bs-parent="#updateAccordion">
                                <div class="accordion-body">
                                    <div class="row g-3">
                                        @php
                                            $kycDetails = $application->kyc_details;
                                        @endphp
                                        @foreach($kycDetails as $key => $value)
                                            @if($key === 'billing_address')
                                                {{-- Billing Address as textarea --}}
                                                <div class="col-12">
                                                    <label class="form-label">Billing Address</label>
                                                    <textarea name="kyc_details[{{ $key }}]" class="form-control" rows="3">{{ is_array($value) ? (isset($value['address']) ? $value['address'] : json_encode($value)) : $value }}</textarea>
                                                </div>
                                            @elseif($key === 'billing_pincode')
                                                {{-- Billing Pincode as text input --}}
                                                <div class="col-md-6">
                                                    <label class="form-label">Billing Pincode</label>
                                                    <input type="text" name="kyc_details[{{ $key }}]" class="form-control" value="{{ $value ?? '' }}">
                                                </div>
                                            @elseif(is_bool($value))
                                                <div class="col-md-6">
                                                    <label class="form-label">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                                    <select name="kyc_details[{{ $key }}]" class="form-control">
                                                        <option value="1" {{ $value ? 'selected' : '' }}>Yes</option>
                                                        <option value="0" {{ !$value ? 'selected' : '' }}>No</option>
                                                    </select>
                                                </div>
                                            @elseif(is_array($value))
                                                <div class="col-12">
                                                    <label class="form-label">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                                    @foreach($value as $subKey => $subValue)
                                                    <div class="mb-2">
                                                        <label class="small text-muted">{{ ucwords(str_replace('_', ' ', $subKey)) }}</label>
                                                        @if(is_array($subValue))
                                                            {{-- If subValue is also an array, convert to JSON --}}
                                                            <input type="text" name="kyc_details[{{ $key }}][{{ $subKey }}]" class="form-control form-control-sm" value="{{ json_encode($subValue) }}">
                                                        @else
                                                            <input type="text" name="kyc_details[{{ $key }}][{{ $subKey }}]" class="form-control form-control-sm" value="{{ $subValue ?? '' }}">
                                                        @endif
                                                    </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="col-md-6">
                                                    <label class="form-label">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                                    @if($key === 'contact_dob' || $key === 'completed_at')
                                                        <input type="date" name="kyc_details[{{ $key }}]" class="form-control" value="{{ $value ? (is_string($value) ? $value : \Carbon\Carbon::parse($value)->format('Y-m-d')) : '' }}">
                                                    @elseif(str_contains($key, 'date') || str_contains($key, 'dob'))
                                                        <input type="date" name="kyc_details[{{ $key }}]" class="form-control" value="{{ $value ? (is_string($value) ? $value : \Carbon\Carbon::parse($value)->format('Y-m-d')) : '' }}">
                                                    @else
                                                        <input type="text" name="kyc_details[{{ $key }}]" class="form-control" value="{{ $value ?? '' }}">
                                                    @endif
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Authorized Representative Details -->
                        @if($application->authorized_representative_details)
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRepDetails">
                                    Authorized Representative Details
                                </button>
                            </h2>
                            <div id="collapseRepDetails" class="accordion-collapse collapse" data-bs-parent="#updateAccordion">
                                <div class="accordion-body">
                                    <div class="row g-3">
                                        @php
                                            $repDetails = $application->authorized_representative_details;
                                        @endphp
                                        @foreach($repDetails as $key => $value)
                                        <div class="col-md-6">
                                            <label class="form-label">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                            @if(is_array($value))
                                                {{-- If value is an array, convert to JSON string or handle nested --}}
                                                <input type="text" name="authorized_representative_details[{{ $key }}]" class="form-control" value="{{ json_encode($value) }}">
                                            @else
                                                <input type="text" name="authorized_representative_details[{{ $key }}]" class="form-control" value="{{ $value ?? '' }}">
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Application Data -->
                        @if($application->application_data)
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAppData">
                                    Application Data
                                </button>
                            </h2>
                            <div id="collapseAppData" class="accordion-collapse collapse" data-bs-parent="#updateAccordion">
                                <div class="accordion-body">
                                    @php
                                        $appData = $application->application_data;
                                    @endphp
                                    
                                    <!-- Location Details -->
                                    @if(isset($appData['location']))
                                    <div class="mb-4">
                                        <h6 class="mb-3 text-primary">Location Details</h6>
                                        <div class="row g-3">
                                            @foreach($appData['location'] as $key => $value)
                                            <div class="col-md-6">
                                                <label class="form-label">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                                <input type="text" name="application_data[location][{{ $key }}]" class="form-control" value="{{ $value ?? '' }}">
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    <!-- Port Selection -->
                                    @if(isset($appData['port_selection']))
                                    <div class="mb-4">
                                        <h6 class="mb-3 text-primary">Port Selection</h6>
                                        <div class="row g-3">
                                            @foreach($appData['port_selection'] as $key => $value)
                                            <div class="col-md-6">
                                                <label class="form-label">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                                <input type="text" name="application_data[port_selection][{{ $key }}]" class="form-control" value="{{ $value ?? '' }}">
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    <!-- IP Prefix -->
                                    @if(isset($appData['ip_prefix']))
                                    <div class="mb-4">
                                        <h6 class="mb-3 text-primary">IP Prefix</h6>
                                        <div class="row g-3">
                                            @foreach($appData['ip_prefix'] as $key => $value)
                                            <div class="col-md-6">
                                                <label class="form-label">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                                <input type="text" name="application_data[ip_prefix][{{ $key }}]" class="form-control" value="{{ $value ?? '' }}">
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    <!-- Peering -->
                                    @if(isset($appData['peering']))
                                    <div class="mb-4">
                                        <h6 class="mb-3 text-primary">Peering</h6>
                                        <div class="row g-3">
                                            @foreach($appData['peering'] as $key => $value)
                                            <div class="col-md-6">
                                                <label class="form-label">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                                <input type="text" name="application_data[peering][{{ $key }}]" class="form-control" value="{{ $value ?? '' }}">
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    <!-- Router Details -->
                                    @if(isset($appData['router_details']))
                                    <div class="mb-4">
                                        <h6 class="mb-3 text-primary">Router Details</h6>
                                        <div class="row g-3">
                                            @foreach($appData['router_details'] as $key => $value)
                                            <div class="col-md-6">
                                                <label class="form-label">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                                <input type="text" name="application_data[router_details][{{ $key }}]" class="form-control" value="{{ $value ?? '' }}">
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    <!-- Member Type -->
                                    @if(isset($appData['member_type']))
                                    <div class="mb-4">
                                        <label class="form-label">Member Type</label>
                                        <input type="text" name="application_data[member_type]" class="form-control" value="{{ $appData['member_type'] }}">
                                    </div>
                                    @endif

                                    <!-- GSTIN -->
                                    @if(isset($appData['gstin']))
                                    <div class="mb-4">
                                        <label class="form-label">GSTIN</label>
                                        <input type="text" name="application_data[gstin]" class="form-control" value="{{ $appData['gstin'] }}">
                                    </div>
                                    @endif

                                    <!-- Payment Information -->
                                    @if(isset($appData['payment']))
                                    <div class="mb-4">
                                        <h6 class="mb-3 text-primary">Payment Information</h6>
                                        <div class="row g-3">
                                            @foreach($appData['payment'] as $key => $value)
                                            <div class="col-md-6">
                                                <label class="form-label">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                                @if($key === 'paid_at' || $key === 'declaration_confirmed_at')
                                                    <input type="datetime-local" name="application_data[payment][{{ $key }}]" class="form-control" value="{{ $value ? \Carbon\Carbon::parse($value)->format('Y-m-d\TH:i') : '' }}">
                                                @else
                                                    <input type="text" name="application_data[payment][{{ $key }}]" class="form-control" value="{{ $value ?? '' }}">
                                                @endif
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Documents Upload -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDocuments">
                                    Documents Upload
                                </button>
                            </h2>
                            <div id="collapseDocuments" class="accordion-collapse collapse" data-bs-parent="#updateAccordion">
                                <div class="accordion-body">
                                    @php
                                        $documentNames = [
                                            'agreement_file' => 'Signed Agreement',
                                            'license_isp_file' => 'ISP License',
                                            'license_vno_file' => 'VNO License',
                                            'cdn_declaration_file' => 'CDN Declaration',
                                            'general_declaration_file' => 'General Declaration',
                                            'board_resolution_file' => 'Board Resolution',
                                            'whois_details_file' => 'Whois Details',
                                            'pan_document_file' => 'PAN Document',
                                            'gstin_document_file' => 'GSTIN Document',
                                            'msme_document_file' => 'MSME Certificate',
                                            'incorporation_document_file' => 'Certificate of Incorporation',
                                            'authorized_rep_document_file' => 'Authorized Representative Document',
                                            'new_gst_document' => 'New GST Document',
                                        ];
                                        $existingDocs = $appData['documents'] ?? [];
                                    @endphp
                                    <div class="row g-3">
                                        @foreach($documentNames as $key => $label)
                                        <div class="col-md-6">
                                            <label class="form-label">{{ $label }}</label>
                                            @if(isset($existingDocs[$key]))
                                            <div class="mb-2">
                                                <small class="text-muted">Current: 
                                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => $key]) }}" target="_blank" class="text-primary">
                                                        View Document
                                                    </a>
                                                </small>
                                            </div>
                                            @endif
                                            <input type="file" name="documents[{{ $key }}]" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                            <small class="text-muted">Leave empty to keep existing document</small>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-white">
                        <i class="bi bi-save me-1"></i> Update Application
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sections = Array.from(document.querySelectorAll('.comp-section'));
    const mainContentColumn = document.querySelector('.col-md-8.col-lg-9');
    if (mainContentColumn) {
        // Ensure all section cards stay in the right content column.
        sections.forEach(section => {
            if (section.parentElement !== mainContentColumn) {
                mainContentColumn.appendChild(section);
            }
        });
    }
    // Same behavior as user-side details page: show one section at a time.
    function showOnlySection(targetSectionId) {
        sections.forEach(section => {
            section.style.display = (section.id === targetSectionId) ? 'block' : 'none';
        });

        // Keep selected card body expanded so content is always visible
        const selected = document.getElementById(targetSectionId);
        if (selected) {
            selected.querySelectorAll('[id$="-content"]').forEach(content => {
                content.style.display = 'block';
            });
            selected.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // Expose for fallback/debug use
    window.showCompSection = showOnlySection;

    // On initial load, force-hide all and show only Application Information
    sections.forEach(section => {
        section.style.display = 'none';
    });
    showOnlySection('section-application-info');
    const firstNav = document.querySelector('.toggle-nav-link[data-target="section-application-info"]');
    if (firstNav) {
        firstNav.classList.add('active');
    }

    // Toggle sections via navigation links - show only selected section
    document.querySelectorAll('.toggle-nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            
            if (targetId) {
                // Hide all sections and show only the selected one
                showOnlySection(targetId);
                
                // 🔥 Remove active from all nav links
                document.querySelectorAll('.toggle-nav-link').forEach(nav => {
                    nav.classList.remove('active');
                });

                // 🔥 Add active to clicked link
                this.classList.add('active');
                
            }
        });
    });
});
</script>
@endpush
@endsection

