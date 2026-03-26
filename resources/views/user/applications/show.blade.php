@extends('user.layout')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Application Details')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1" style="color:#2c3e50;font-weight:600;">Application Details</h2>
            <p class="text-muted mb-0">Application ID: <strong>{{ $application->application_id }}</strong></p>
            <p class="text-muted mb-0 small">Application Type: <strong>{{ $application->application_type ?? 'N/A' }}</strong></p>
        </div>
        @if($application->application_type === 'IRINN')
        <div>
            <a href="{{ route('user.irinn.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> New IRINN Application
            </a>
        </div>
        @endif
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
                    @if($application->application_type === 'IX')
                        @if($application->assigned_port_capacity)
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
                    @php
                        $appData = $application->application_data ?? [];
                        $documents = $appData['documents'] ?? [];
                        $pdfs = $appData['pdfs'] ?? [];
                        $allDocuments = array_merge($documents, $pdfs);
                    @endphp
                    @if(!empty($allDocuments))
                    <a href="javascript:;" class="list-group-item list-group-item-action toggle-nav-link" data-target="section-documents">
                        <i class="bi bi-file-earmark-pdf me-2"></i> Documents
                    </a>
                    @endif
                    <a href="javascript:;" class="list-group-item list-group-item-action toggle-nav-link" data-target="section-status">
                        <i class="bi bi-list-check me-2"></i> Application Status
                    </a>
                    <a href="javascript:;" class="list-group-item list-group-item-action toggle-nav-link" data-target="section-service-timeline">
                        <i class="bi bi-activity me-2"></i> Service Timeline
                    </a>
                    @if($application->statusHistory && $application->statusHistory->count() > 0)
                    <a href="javascript:;" class="list-group-item list-group-item-action toggle-nav-link" data-target="section-history">
                        <i class="bi bi-clock-history me-2"></i> Status History
                    </a>
                    @endif
                    @if($application->gstChangeHistory && $application->gstChangeHistory->count() > 0)
                    <a href="javascript:;" class="list-group-item list-group-item-action toggle-nav-link" data-target="section-gst-history">
                        <i class="bi bi-receipt me-2"></i> GST Change History
                    </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-8 col-lg-9">
            <!-- Pending GST Update Request Alert -->
            @if(isset($pendingGstUpdateRequest) && $pendingGstUpdateRequest)
            <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert" style="border-radius: 16px;">
                <div class="d-flex align-items-start">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading mb-2">GST Update Request Pending Approval</h6>
                        <p class="mb-2">
                            Your GST update request is pending admin approval. The company name similarity is 
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

            <!-- IRINN Resubmission requested: show admin message and Edit & Resubmit -->
            @if($application->application_type === 'IRINN' && $application->status === 'resubmission_requested')
            @php
                $appData = $application->application_data ?? [];
                $irinnResubmitReason = $appData['irinn_resubmission_reason'] ?? '';
            @endphp
            <div class="alert alert-warning border-warning mb-4" style="border-radius: 16px;">
                <div class="d-flex align-items-start">
                    <i class="bi bi-pencil-square me-2 fs-4"></i>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading mb-2">Resubmission requested</h5>
                        <p class="mb-2">Admin has requested changes to your IRINN application. Please edit the details and resubmit. <strong>No payment is required</strong> — payment was already received.</p>
                        @if($irinnResubmitReason)
                        <div class="mb-3 p-2 bg-white rounded small">
                            <strong>Admin message:</strong><br>{{ $irinnResubmitReason }}
                        </div>
                        @endif
                        <a href="{{ route('user.applications.irin.resubmit', $application->id) }}" class="btn btn-primary">
                            <i class="bi bi-pencil me-1"></i> Edit & Resubmit Application
                        </a>
                    </div>
                </div>
            </div>
            @endif

            <!-- Application Information -->
            <div id="section-application-info" class="card border-c-blue shadow-sm mb-3" style="border-radius: 16px;">
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
                                $location = $appData['location'] ?? null;
                                $portSelection = $appData['port_selection'] ?? [];
                            @endphp
                            @if($location)
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block">Node/Location</label>
                                <div class="fw-medium">{{ $location['name'] ?? 'N/A' }}</div>
                            </div>
                            @endif
                            @if($location && isset($location['node_type']))
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block">Node Type</label>
                                <div class="fw-medium">{{ ucfirst($location['node_type']) }}</div>
                            </div>
                            @endif
                            @if($location && isset($location['state']))
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block">State</label>
                                <div class="fw-medium">{{ $location['state'] }}</div>
                            </div>
                            @endif
                            @if($application->assigned_port_capacity)
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block">Port Capacity</label>
                                <div class="fw-medium">{{ $application->assigned_port_capacity }}</div>
                            </div>
                            @elseif($portSelection && isset($portSelection['capacity']) && !$application->assigned_port_capacity)
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block">Port Capacity</label>
                                <div class="fw-medium">{{ $portSelection['capacity'] }}</div>
                            </div>
                            @endif
                            @if($portSelection && isset($portSelection['billing_plan']))
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block">Billing Cycle</label>
                                <div class="fw-medium">{{ strtoupper($portSelection['billing_plan']) }}</div>
                            </div>
                            @endif
                        @endif
                        @php
                            $serviceStatus = $application->service_status ?? ($application->is_active ? 'live' : 'disconnected');
                        @endphp
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Service Status</label>
                            <div>
                                @if($serviceStatus === 'live')
                                    <span class="badge bg-success">LIVE</span>
                                    @if($application->service_activation_date)
                                    <small class="d-block text-muted mt-1">Activated: {{ \Carbon\Carbon::parse($application->service_activation_date)->format('d M Y') }}</small>
                                    @endif
                                @elseif($serviceStatus === 'suspended')
                                    <span class="badge bg-warning text-dark">SUSPENDED</span>
                                    @if($application->suspended_from)
                                        <small class="d-block text-muted mt-1">From: {{ \Carbon\Carbon::parse($application->suspended_from)->format('d M Y') }}</small>
                                    @endif
                                @else
                                    <span class="badge bg-danger">DISCONNECTED</span>
                                    @if($application->disconnected_at)
                                        <small class="d-block text-muted mt-1">Effective: {{ \Carbon\Carbon::parse($application->disconnected_at)->format('d M Y') }}</small>
                                    @endif
                                @endif
                            </div>
                        </div>
                        @if($serviceStatus === 'disconnected')
                            @php
                                $latestReactivationRequest = \App\Models\ApplicationReactivationRequest::where('application_id', $application->id)
                                    ->latest()
                                    ->first();
                            @endphp
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block">Reactivation</label>
                                <div>
                                    @if(! $latestReactivationRequest || in_array($latestReactivationRequest->status, ['rejected', 'completed'], true))
                                        <form method="POST" action="{{ route('user.applications.reactivation-request', $application->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Send reactivation request to admin?')">
                                                Request Reactivation
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-secondary">{{ strtoupper($latestReactivationRequest->status) }}</span>
                                        @if($latestReactivationRequest->status === 'invoiced')
                                            <small class="d-block text-muted mt-1">Invoice generated. Please pay from <a href="{{ route('user.invoices.index') }}">Invoices</a>.</small>
                                        @elseif($latestReactivationRequest->status === 'paid')
                                            <small class="d-block text-muted mt-1">Payment received. Admin will set your reactivation date.</small>
                                        @else
                                            <small class="d-block text-muted mt-1">Your request is under review.</small>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if($application->rejection_reason)
                        <div class="col-12">
                            <label class="small text-muted mb-1 d-block">Rejection Reason</label>
                            <div class="text-danger fw-medium">{{ $application->rejection_reason }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Registration Details - Always render for IRINN -->
            @if($application->application_type === 'IRINN' || !empty($application->registration_details))
            <div id="section-registration" class="card border-c-blue shadow-sm mb-3" style="border-radius: 16px; display: none;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">Registration Details</h6>
                    <button type="button" class="btn btn-sm toggle-section" data-target="registration-content">
                        <i class="bi bi-chevron-down text-white fs-5"></i>
                    </button>
                </div>
                <div id="registration-content" class="card-body p-3" style="display: block;">
                    @php
                        $regDetails = $application->registration_details ?? [];
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

            <!-- KYC Details - Always render for IRINN -->
            @if($application->application_type === 'IRINN' || !empty($application->kyc_details))
            <div id="section-kyc" class="card border-c-blue shadow-sm mb-3" style="border-radius: 16px; display: none;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">KYC Details</h6>
                    <button type="button" class="btn btn-sm toggle-section" data-target="kyc-content">
                        <i class="bi bi-chevron-down text-white fs-5"></i>
                    </button>
                </div>
                <div id="kyc-content" class="card-body p-3" style="display: block;">
                    @php
                        $kycDetails = $application->kyc_details ?? [];
                    @endphp
                    <div class="row g-2 app-details">
                        <!-- Organisation Details -->
                        @if(isset($kycDetails['organisation_type']))
                        <div class="col-12 mt-2">
                            <h6 class="mb-2 text-primary">Organisation Details</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Organisation Type</label>
                            <div class="fw-medium">{{ ucfirst(str_replace('_', ' ', $kycDetails['organisation_type'])) }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['organisation_type_other']) && $kycDetails['organisation_type_other'])
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Organisation Type (Other)</label>
                            <div class="fw-medium">{{ $kycDetails['organisation_type_other'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['affiliate_type']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Affiliate Type</label>
                            <div class="fw-medium">{{ ucfirst($kycDetails['affiliate_type']) }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['affiliate_verification_mode']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Affiliate Verification Mode</label>
                            <div class="fw-medium">{{ strtoupper($kycDetails['affiliate_verification_mode']) }}</div>
                        </div>
                        @endif

                        <!-- Management Representative -->
                        @if(isset($kycDetails['management_name']) || isset($kycDetails['management_pan']))
                        <div class="col-12 mt-3">
                            <h6 class="mb-2 text-primary">Management Representative</h6>
                        </div>
                        @endif
                        @if(isset($kycDetails['management_name']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Name</label>
                            <div class="fw-medium">{{ $kycDetails['management_name'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['management_dob']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Date of Birth</label>
                            <div class="fw-medium">{{ $kycDetails['management_dob'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['management_pan']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">PAN</label>
                            <div class="fw-medium">
                                {{ $kycDetails['management_pan'] }}
                                @if($kycDetails['management_pan_verified'] ?? false)
                                    <span class="badge bg-success ms-1">Verified</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if(isset($kycDetails['management_din']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">DIN</label>
                            <div class="fw-medium">{{ $kycDetails['management_din'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['management_email']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Email</label>
                            <div class="fw-medium">
                                {{ $kycDetails['management_email'] }}
                                @if($kycDetails['management_email_verified'] ?? false)
                                    <span class="badge bg-success ms-1">Verified</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if(isset($kycDetails['management_mobile']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Mobile</label>
                            <div class="fw-medium">
                                {{ $kycDetails['management_mobile'] }}
                                @if($kycDetails['management_mobile_verified'] ?? false)
                                    <span class="badge bg-success ms-1">Verified</span>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- Authorized Representative -->
                        @if(isset($kycDetails['authorized_name']) || isset($kycDetails['authorized_pan']))
                        <div class="col-12 mt-3">
                            <h6 class="mb-2 text-primary">Authorized Representative</h6>
                        </div>
                        @endif
                        @if(isset($kycDetails['authorized_name']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Name</label>
                            <div class="fw-medium">{{ $kycDetails['authorized_name'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['authorized_dob']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Date of Birth</label>
                            <div class="fw-medium">{{ $kycDetails['authorized_dob'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['authorized_pan']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">PAN</label>
                            <div class="fw-medium">
                                {{ $kycDetails['authorized_pan'] }}
                                @if($kycDetails['authorized_pan_verified'] ?? false)
                                    <span class="badge bg-success ms-1">Verified</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if(isset($kycDetails['authorized_email']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Email</label>
                            <div class="fw-medium">
                                {{ $kycDetails['authorized_email'] }}
                                @if($kycDetails['authorized_email_verified'] ?? false)
                                    <span class="badge bg-success ms-1">Verified</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if(isset($kycDetails['authorized_mobile']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Mobile</label>
                            <div class="fw-medium">
                                {{ $kycDetails['authorized_mobile'] }}
                                @if($kycDetails['authorized_mobile_verified'] ?? false)
                                    <span class="badge bg-success ms-1">Verified</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if(isset($kycDetails['whois_source']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Whois Details Source</label>
                            <div class="fw-medium">{{ ucfirst($kycDetails['whois_source']) }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['billing_person_name']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Billing Person Name</label>
                            <div class="fw-medium">{{ $kycDetails['billing_person_name'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['billing_person_email']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Billing Person Email</label>
                            <div class="fw-medium">{{ $kycDetails['billing_person_email'] }}</div>
                        </div>
                        @endif
                        @if(isset($kycDetails['billing_person_mobile']))
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Billing Person Mobile</label>
                            <div class="fw-medium">{{ $kycDetails['billing_person_mobile'] }}</div>
                        </div>
                        @endif

                        <!-- GST Information -->
                        @if(isset($kycDetails['gstin']))
                        <div class="col-12 mt-3">
                            <h6 class="mb-2 text-primary">GST Information</h6>
                        </div>
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
                        @if(isset($kycDetails['primary_address']))
                        <div class="col-12">
                            <label class="small text-muted mb-1 d-block">Primary Address</label>
                            <div class="fw-medium">{{ $kycDetails['primary_address'] }}</div>
                        </div>
                        @endif

                        <!-- UDYAM Information -->
                        @if(isset($kycDetails['udyam_number']))
                        <div class="col-12 mt-3">
                            <h6 class="mb-2 text-primary">UDYAM Information</h6>
                        </div>
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
                        <div class="col-12 mt-3">
                            <h6 class="mb-2 text-primary">MCA Information</h6>
                        </div>
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

                        <!-- Billing Address -->
                        @if(isset($kycDetails['billing_address']))
                        <div class="col-12 mt-3">
                            <h6 class="mb-2 text-primary">Billing Address</h6>
                            <div class="fw-medium">
                                @php
                                    $billingAddress = $kycDetails['billing_address'];
                                    if (is_string($billingAddress)) {
                                        $decoded = json_decode($billingAddress, true);
                                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                            $billingAddress = $decoded;
                                        }
                                    }
                                @endphp
                                @if(is_array($billingAddress))
                                    @if(isset($billingAddress['address']))
                                        <div>{{ $billingAddress['address'] }}</div>
                                    @endif
                                    @if(isset($billingAddress['state']))
                                        <div class="text-muted small">State: {{ $billingAddress['state'] }}</div>
                                    @endif
                                    @if(isset($billingAddress['pincode']))
                                        <div class="text-muted small">Pincode: {{ $billingAddress['pincode'] }}</div>
                                    @endif
                                @else
                                    <div>{{ $billingAddress }}</div>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- KYC Status -->
                        @if(isset($kycDetails['status']))
                        <div class="col-md-6 mt-3">
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
                        <div class="col-md-6 mt-3">
                            <label class="small text-muted mb-1 d-block">Completed At</label>
                            <div class="fw-medium">{{ $kycDetails['completed_at'] }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Authorized Representative Details -->
            @if($application->authorized_representative_details)
            <div id="section-representative" class="card border-c-blue shadow-sm mb-3" style="border-radius: 16px; display: none;">
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
                    </div>
                </div>
            </div>
            @endif

            <!-- IRINN Application Data -->
            @if($application->application_type === 'IRINN')
                @php
                    $appData = $application->application_data ?? [];
                @endphp
                <div id="section-irin-application-data" class="card border-c-blue shadow-sm mb-3" style="border-radius: 16px; display: none;">
                    <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                        <h6 class="mb-0">IRINN Application Data</h6>
                        <div class="d-flex gap-2 align-items-center">
                            <!-- <button type="button" class="btn btn-sm btn-light" onclick="showOnlySection('section-registration')">
                                <i class="bi bi-person-badge"></i> Registration Details
                            </button>
                            <button type="button" class="btn btn-sm btn-light" onclick="showOnlySection('section-kyc')">
                                <i class="bi bi-shield-check"></i> KYC Details
                            </button> -->
                            <button type="button" class="btn btn-sm toggle-section" data-target="irinn-application-data-content">
                                <i class="bi bi-chevron-down text-white fs-5"></i>
                            </button>
                        </div>
                    </div>
                    <div id="irinn-application-data-content" class="card-body p-3" style="display: block;">
                        @if(!empty($appData))
                            <div class="row g-2 app-details">
                            <!-- Part 1: Application [IRINN] -->
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

                            <!-- Part 2: New Resources -->
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

                            <!-- Part 3: IRINN Agreement and Documents -->
                            @if(isset($appData['part3']))
                            <div class="col-12 mt-3">
                                <h6 class="mb-2 text-primary">Part 3: IRINN Agreement and Documents</h6>
                                <div class="row g-2">
                                    @if(isset($appData['part3']['board_resolution_file']))
                                    <div class="col-md-6">
                                        <label class="small text-muted mb-1 d-block">Board Resolution</label>
                                        <div class="fw-medium">
                                            @if($appData['part3']['board_resolution_file'])
                                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'board_resolution_file']) }}" target="_blank" class="text-primary">View Document</a>
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
                                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'irinn_agreement_file']) }}" target="_blank" class="text-primary">View Document</a>
                                            @else
                                                <span class="text-muted">Not uploaded</span>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Part 4: Resource Justification Requirement -->
                            @if(isset($appData['part4']))
                            <div class="col-12 mt-3">
                                <h6 class="mb-2 text-primary">Part 4: Resource Justification Requirement</h6>
                                <div class="row g-2">
                                    @if(isset($appData['part4']['network_diagram_file']))
                                    <div class="col-md-6">
                                        <label class="small text-muted mb-1 d-block">Network Diagram</label>
                                        <div class="fw-medium">
                                            @if($appData['part4']['network_diagram_file'])
                                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'network_diagram_file']) }}" target="_blank" class="text-primary">View Document</a>
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
                                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'equipment_invoice_file']) }}" target="_blank" class="text-primary">View Document</a>
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
                                                    <div><a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'bandwidth_invoice_file', 'index' => $index]) }}" target="_blank" class="text-primary">View Document {{ $loop->iteration }}</a></div>
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
                                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'bandwidth_agreement_file']) }}" target="_blank" class="text-primary">View Document</a>
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

                            <!-- Part 5: Payment -->
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

                <!-- Payment Section for Draft IRINN Applications -->
                @if($application->status === 'draft')
                    @php
                        $paymentData = $application->application_data['part5'] ?? null;
                        $isAwaitingPayment = ($paymentData['payment_status'] ?? null) === 'pending';
                        $totalAmount = (float) ($paymentData['total_amount'] ?? 1180.00);
                        $user = \App\Models\Registration::find(session('user_id'));
                        $wallet = $user ? $user->wallet : null;
                        $walletBalance = $wallet && $wallet->status === 'active' ? (float) $wallet->balance : 0;
                        $canPayWithWallet = $wallet && $wallet->status === 'active' && $walletBalance >= $totalAmount;
                    @endphp
                    @if($isAwaitingPayment)
                    <div class="card border-warning shadow-sm mb-3" style="border-radius: 16px;">
                        <div class="card-header bg-warning d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                            <h6 class="mb-0 text-dark">Payment Required</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="alert alert-warning mb-3">
                                <strong>Your application is saved as draft.</strong> Please complete the payment to submit your application.
                                <div class="mt-2">
                                    <strong>Application Fee:</strong> ₹{{ number_format($totalAmount, 2) }}
                                </div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                @if($canPayWithWallet)
                                <form action="{{ route('user.applications.irin.pay-now-with-wallet', $application->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Pay ₹{{ number_format($totalAmount, 2) }} from advance amount?')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                            <path d="M0 3a2 2 0 0 1 2-2h13.5a.5.5 0 0 1 0 1H15v2a1 1 0 0 1 1 1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1h-1a1 1 0 0 1-1-1V6h-2v1.5a.5.5 0 0 1-1 0V6H1V4.5a.5.5 0 0 1 1 0V3zm1 1.5V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V4.5A1.5 1.5 0 0 0 12.5 3h-9A1.5 1.5 0 0 0 1 4.5z"/>
                                        </svg>
                                        Pay with Advance (₹{{ number_format($walletBalance, 2) }})
                                    </button>
                                </form>
                                @endif
                                <form action="{{ route('user.applications.irin.store-new') }}" method="POST" class="d-inline" id="irinn-pay-form">
                                    @csrf
                                    <input type="hidden" name="action" value="submit">
                                    <input type="hidden" name="application_id" value="{{ $application->id }}">
                                    <button type="submit" class="btn btn-primary" id="irinn-pay-payu-btn">
                                        Pay with PayU
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif
                @endif
            @endif

            <!-- Application Data (IX only) -->
            @if($application->application_type === 'IX' && $application->application_data)
            <div id="section-application-data" class="card border-c-blue shadow-sm mb-3" style="border-radius: 16px; display: none;">
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
                        @if(isset($appData['location']))
                        <div class="col-12">
                            <h6 class="mb-2 text-primary">Location Details</h6>
                            <div class="row g-2">
                                @if(isset($appData['location']['name']))
                                <div class="col-md-6">
                                    <label class="small text-muted mb-1 d-block">Location Name</label>
                                    <div class="fw-medium">{{ $appData['location']['name'] }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Plan Change Section (IX only) -->
            @if($application->application_type === 'IX' && $application->assigned_port_capacity)
            <div id="section-plan-change" class="card border-c-blue shadow-sm mb-3" style="border-radius: 16px; display: none;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">Plan Management</h6>
                    <button type="button" class="btn btn-sm toggle-section" data-target="plan-change-content">
                        <i class="bi bi-chevron-down text-white fs-5"></i>
                    </button>
                </div>
                <div id="plan-change-content" class="card-body p-3">
                    <div class="row g-2 app-details">
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Current Port Capacity</label>
                            <div class="fw-medium">{{ $application->assigned_port_capacity }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Uploaded Documents -->
            @php
                $appDataForDocs = $application->application_data ?? [];
                $documents = $appDataForDocs['documents'] ?? [];
                $pdfs = $appDataForDocs['pdfs'] ?? [];
                $allDocuments = array_merge($documents, $pdfs);
            @endphp
            @if(!empty($allDocuments))
            <div id="section-documents" class="card border-c-blue shadow-sm mb-3" style="border-radius: 16px; display: none;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">Uploaded Documents</h6>
                    <button type="button" class="btn btn-sm toggle-section" data-target="documents-content">
                        <i class="bi bi-chevron-down text-white fs-5"></i>
                    </button>
                </div>
                <div id="documents-content" class="card-body p-3">
                    <div class="row g-2 app-details">
                        @foreach($allDocuments as $key => $path)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small mb-1 d-block">{{ ucwords(str_replace(['_', 'file'], [' ', ''], $key)) }}</label>
                                <div>
                                    @if($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path))
                                        <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => $key]) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            View Document
                                        </a>
                                    @else
                                        <span class="small text-muted">File not found</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Application Status -->
            <div id="section-status" class="card border-c-blue shadow-sm mb-3" style="border-radius: 16px; display: none;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">Application Status</h6>
                    <button type="button" class="btn btn-sm toggle-section" data-target="status-content">
                        <i class="bi bi-chevron-down text-white fs-5"></i>
                    </button>
                </div>
                <div id="status-content" class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Status</label>
                            <div>
                                <span class="badge bg-info">{{ ucfirst($application->status) }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Current Stage</label>
                            <div>
                                <span class="badge bg-light text-dark">{{ $application->current_stage }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Service Status Timeline -->
            <div class="card border-c-blue shadow-sm mb-3" style="border-radius: 16px; display: none;" id="section-service-timeline">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">Service Status Timeline</h6>
                    <button type="button" class="btn btn-sm toggle-section" data-target="section-service-timeline-content">
                        <i class="bi bi-chevron-up text-white fs-5"></i>
                    </button>
                </div>
                <div id="section-service-timeline-content" class="card-body p-3" style="display: none;">
                    @if($application->serviceStatusHistories && $application->serviceStatusHistories->count() > 0)
                        <div class="d-flex flex-column gap-2">
                            @foreach($application->serviceStatusHistories->take(10) as $entry)
                                <div class="d-flex justify-content-between align-items-center border rounded px-3 py-2">
                                    <div class="d-flex align-items-center gap-2">
                                        @if($entry->status === 'live')
                                            <span class="badge bg-success">LIVE</span>
                                        @elseif($entry->status === 'suspended')
                                            <span class="badge bg-warning text-dark">SUSPENDED</span>
                                        @else
                                            <span class="badge bg-danger">DISCONNECTED</span>
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
                    @else
                        <div class="text-muted">No service timeline history available yet.</div>
                    @endif
                </div>
            </div>

            <!-- Status History -->
            @if($application->statusHistory && $application->statusHistory->count() > 0)
            <div id="section-history" class="card border-c-blue shadow-sm mb-3" style="border-radius: 16px; display: none;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">Status History</h6>
                    <button type="button" class="btn btn-sm toggle-section" data-target="history-content">
                        <i class="bi bi-chevron-down text-white fs-5"></i>
                    </button>
                </div>
                <div id="history-content" class="card-body p-3">
                    <div class="timeline">
                        @foreach($application->statusHistory->sortByDesc('created_at') as $history)
                        <div class="mb-2 pb-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong class="small">{{ $history->status_display ?? ucfirst($history->status) }}</strong>
                                    @if($history->notes)
                                        <p class="mb-0 text-muted small mt-1">{{ $history->notes }}</p>
                                    @endif
                                </div>
                                <small class="text-muted">{{ $history->created_at->format('d M Y, h:i A') }}</small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- GST Change History -->
            @if($application->gstChangeHistory && $application->gstChangeHistory->count() > 0)
            <div id="section-gst-history" class="card border-c-blue shadow-sm mb-3" style="border-radius: 16px; display: none;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">GST Change History</h6>
                    <button type="button" class="btn btn-sm toggle-section" data-target="gst-history-content">
                        <i class="bi bi-chevron-down text-white fs-5"></i>
                    </button>
                </div>
                <div id="gst-history-content" class="card-body p-3">
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
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const allSectionIds = [
        'section-application-info',
        'section-registration',
        'section-kyc',
        'section-representative',
        'section-plan-change',
        'section-application-data',
        'section-irin-application-data',
        'section-documents',
        'section-status',
        'section-service-timeline',
        'section-history',
        'section-gst-history'
    ];

    function showOnlySection(targetSectionId) {
        console.log('showOnlySection called with:', targetSectionId);
        allSectionIds.forEach(sectionId => {
            const section = document.getElementById(sectionId);
            if (section) {
                if (sectionId === targetSectionId) {
                    console.log('Showing section:', sectionId);
                    section.style.display = 'block';
                    const contentIdMap = {
                        'section-registration': 'registration-content',
                        'section-kyc': 'kyc-content',
                        'section-representative': 'representative-content',
                        'section-plan-change': 'plan-change-content',
                        'section-application-data': 'application-data-content',
                        'section-irin-application-data': 'irinn-application-data-content',
                        'section-documents': 'documents-content',
                        'section-status': 'status-content',
                        'section-service-timeline': 'section-service-timeline-content',
                        'section-history': 'history-content',
                        'section-gst-history': 'gst-history-content'
                    };
                    
                    const contentId = contentIdMap[sectionId] || (sectionId + '-content');
                    const content = document.getElementById(contentId);
                    if (content) {
                        content.style.display = 'block';
                        const toggleBtn = section.querySelector('.toggle-section');
                        if (toggleBtn) {
                            const icon = toggleBtn.querySelector('i');
                            if (icon) {
                                icon.classList.remove('bi-chevron-up');
                                icon.classList.add('bi-chevron-down');
                            }
                        }
                    }
                    setTimeout(() => {
                        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 100);
                } else {
                    section.style.display = 'none';
                }
            } else {
                console.log('Section not found:', sectionId);
            }
        });
        
        document.querySelectorAll('.toggle-nav-link').forEach(nav => {
            nav.classList.remove('active');
            if (nav.getAttribute('data-target') === targetSectionId) {
                nav.classList.add('active');
            }
        });
    }
    
    window.showOnlySection = showOnlySection;
    
    document.querySelectorAll('.toggle-nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            console.log('Navigation link clicked:', targetId);
            if (targetId) {
                const section = document.getElementById(targetId);
                console.log('Section element found:', section);
                if (section) {
                    showOnlySection(targetId);
                } else {
                    console.error('Section not found in DOM:', targetId);
                    alert('Section "' + targetId + '" not found. Please refresh the page.');
                }
            }
        });
    });
    
    console.log('Available sections on page load:');
    allSectionIds.forEach(id => {
        const el = document.getElementById(id);
        console.log('  -', id, ':', el ? 'EXISTS (display: ' + el.style.display + ')' : 'NOT FOUND');
    });

    document.querySelectorAll('.toggle-section').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const target = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (target.style.display === 'none') {
                target.style.display = 'block';
                icon.classList.remove('bi-chevron-up');
                icon.classList.add('bi-chevron-down');
            } else {
                target.style.display = 'none';
                icon.classList.remove('bi-chevron-down');
                icon.classList.add('bi-chevron-up');
            }
        });
    });

    // IRINN draft: Pay with PayU – submit via AJAX then redirect to PayU form (avoid showing JSON)
    const irinnPayForm = document.getElementById('irinn-pay-form');
    if (irinnPayForm) {
        irinnPayForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('irinn-pay-payu-btn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Redirecting...';
            }
            const formData = new FormData(this);
            // NOTE: form has an input named "action", which shadows HTMLFormElement.action in JS.
            // Always read the real URL from the attribute.
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
                    if (btn) { btn.disabled = false; btn.textContent = 'Pay with PayU'; }
                }
            })
            .catch(function(err) {
                console.error(err);
                alert('Payment request failed. Please try again.');
                if (btn) { btn.disabled = false; btn.textContent = 'Pay with PayU'; }
            });
        });
    }

});
</script>
@endpush
@endsection
