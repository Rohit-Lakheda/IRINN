@php
    $applicationDocumentRoute = $applicationDocumentRoute ?? 'user.applications.document';
    $hideUserOnlyActions = $hideUserOnlyActions ?? false;
@endphp
<div class="container-fluid py-4">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1" style="color:#2c3e50;font-weight:600;">Application Details</h2>
            <p class="text-muted mb-0">Application ID: <strong>{{ $application->application_id }}</strong></p>
            <p class="text-muted mb-0 small">Application Type: <strong>{{ $application->application_type ?? 'N/A' }}</strong></p>
        </div>
    </div>

    <div class="row">
        <!-- Side Navigation -->
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="card border-c-blue shadow-sm sticky-top1" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue" style="border-radius: 16px 16px 0 0; padding: 19px;">
                    <h6 class="mb-0">On this page</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="javascript:;" class="list-group-item list-group-item-action toggle-nav-link" data-target="section-application-info">
                        <i class="bi bi-info-circle me-2"></i> Application summary
                    </a>
                    @if(!empty($application->registration_details) && $application->application_type !== 'IRINN')
                    <a href="javascript:;" class="list-group-item list-group-item-action toggle-nav-link" data-target="section-registration">
                        <i class="bi bi-person-badge me-2"></i> Registration details
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
                    @if($application->application_type === 'IRINN' && $application->hasIrinnNormalizedData())
                        <div class="list-group-item py-2 small text-muted fw-semibold border-0 bg-light">IRINN application &mdash; by step</div>
                        <a href="javascript:;" class="list-group-item list-group-item-action py-2 ps-4 irinn-step-nav" data-irinn-step="1">
                            <span class="me-1 text-muted">1.</span> Organisation &amp; billing
                        </a>
                        <a href="javascript:;" class="list-group-item list-group-item-action py-2 ps-4 irinn-step-nav" data-irinn-step="2">
                            <span class="me-1 text-muted">2.</span> Management representative
                        </a>
                        <a href="javascript:;" class="list-group-item list-group-item-action py-2 ps-4 irinn-step-nav" data-irinn-step="3">
                            <span class="me-1 text-muted">3.</span> Technical &amp; abuse contact
                        </a>
                        <a href="javascript:;" class="list-group-item list-group-item-action py-2 ps-4 irinn-step-nav" data-irinn-step="4">
                            <span class="me-1 text-muted">4.</span> Billing representative
                        </a>
                        <a href="javascript:;" class="list-group-item list-group-item-action py-2 ps-4 irinn-step-nav" data-irinn-step="5">
                            <span class="me-1 text-muted">5.</span> Network resources
                        </a>
                        <a href="javascript:;" class="list-group-item list-group-item-action py-2 ps-4 irinn-step-nav" data-irinn-step="6">
                            <span class="me-1 text-muted">6.</span> Upstream &amp; signatory
                        </a>
                        {{-- KYC documents step removed (no longer collected on this portal) --}}
                    @endif
                    @if($application->application_type !== 'IRINN' && $application->application_data)
                        <a href="javascript:;" class="list-group-item list-group-item-action toggle-nav-link" data-target="section-application-data">
                            <i class="bi bi-file-earmark-text me-2"></i> Application data
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
                    <a href="javascript:;" class="list-group-item list-group-item-action toggle-nav-link" data-target="section-history">
                        <i class="bi bi-journal-text me-2"></i> Activity log (status history)
                    </a>
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

            @if($application->application_type === 'IRINN' && isset($approvedProfileUpdateRequest) && $approvedProfileUpdateRequest)
                <div class="alert alert-info border-0 mb-3" style="border-radius: 16px;">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-person-check me-2 fs-4"></i>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading mb-2">Email & mobile update approved</h6>
                            <p class="mb-3">Admin approved your profile update request. Submit the update (OTP verification) to apply it on your account.</p>
                            <a href="{{ route('user.profile-update.edit') }}" class="btn btn-primary">
                                Update Email & Mobile
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            @if($application->application_type === 'IRINN' && !($pendingGstUpdateRequest ?? null) && ! $hideUserOnlyActions)
                <div class="mb-3">
                    <a href="{{ route('user.applications.gst.edit', $application->id) }}" class="btn btn-primary" style="border-radius: 12px;">
                        <i class="bi bi-receipt me-2"></i> Update GST
                    </a>
                </div>
            @endif

            <!-- IRINN Resubmission requested: show admin message and Edit & Resubmit -->
            @if(! $hideUserOnlyActions && $application->application_type === 'IRINN' && $application->status === 'resubmission_requested')
            @php
                $appData = $application->application_data ?? [];
                $irinnResubmitReason = $appData['irinn_resubmission_reason'] ?? '';
            @endphp
            <div class="alert alert-warning border-warning mb-4" style="border-radius: 16px;">
                <div class="d-flex align-items-start">
                    <i class="bi bi-pencil-square me-2 fs-4"></i>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading mb-2">Resubmission requested</h5>
                        <p class="mb-2">Admin has requested changes to your IRINN application. Please edit the details and resubmit. <strong>No payment is required</strong> &mdash; payment was already received.</p>
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

            <!-- Application summary -->
            <div id="section-application-info" class="card border-c-blue shadow-sm mb-3" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">Application summary</h6>
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
                            <label class="small text-muted mb-1 d-block">Status</label>
                            <div><span class="badge bg-primary">{{ $application->status_display }}</span></div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Current processing stage</label>
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
                        @if(! $hideUserOnlyActions && $serviceStatus === 'disconnected')
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

            <!-- Registration Details (non-IRINN only) -->
            @if(!empty($application->registration_details) && $application->application_type !== 'IRINN')
            <div id="section-registration" class="card border-c-blue shadow-sm mb-3" style="border-radius: 16px; display: none;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">Registration details</h6>
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

            <!-- IRINN normalized submitted form (create-new flow) -->
            @if($application->application_type === 'IRINN' && $application->hasIrinnNormalizedData())
                <div id="section-irinn-normalized" class="card border-c-blue shadow-sm mb-3" style="border-radius: 16px; display: none;">
                    <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                        <h6 class="mb-0">IRINN application &mdash; step details</h6>
                        <button type="button" class="btn btn-sm toggle-section" data-target="irinn-normalized-content">
                            <i class="bi bi-chevron-down text-white fs-5"></i>
                        </button>
                    </div>
                    <div id="irinn-normalized-content" class="card-body p-3" style="display: block;">
                        <p class="text-muted small mb-3">Choose a step on the left to view only that section.</p>
                        @include('partials.irinn-normalized-application-details', [
                            'application' => $application,
                            'documentRouteName' => 'user.applications.document',
                        ])
                    </div>
                </div>
            @endif

            @if(! $hideUserOnlyActions && $application->application_type === 'IRINN')
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
                                    <strong>Application Fee:</strong> &#8377;{{ number_format($totalAmount, 2) }}
                                </div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                @if($canPayWithWallet)
                                <form action="{{ route('user.applications.irin.pay-now-with-wallet', $application->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Pay &#8377;{{ number_format($totalAmount, 2) }} from advance amount?')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                            <path d="M0 3a2 2 0 0 1 2-2h13.5a.5.5 0 0 1 0 1H15v2a1 1 0 0 1 1 1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1h-1a1 1 0 0 1-1-1V6h-2v1.5a.5.5 0 0 1-1 0V6H1V4.5a.5.5 0 0 1 1 0V3zm1 1.5V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V4.5A1.5 1.5 0 0 0 12.5 3h-9A1.5 1.5 0 0 0 1 4.5z"/>
                                        </svg>
                                        Pay with Advance (&#8377;{{ number_format($walletBalance, 2) }})
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
                                        <a href="{{ route($applicationDocumentRoute, ['id' => $application->id, 'doc' => $key]) }}" target="_blank" class="btn btn-sm btn-outline-primary">
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

            <!-- Activity log (application_status_history) -->
            <div id="section-history" class="card border-c-blue shadow-sm mb-3" style="border-radius: 16px; display: none;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0">Activity log</h6>
                    <button type="button" class="btn btn-sm toggle-section" data-target="history-content">
                        <i class="bi bi-chevron-down text-white fs-5"></i>
                    </button>
                </div>
                <div id="history-content" class="card-body p-3">
                    <p class="text-muted small mb-3">Chronological log of status updates and actions recorded for this application (for example: submitted, payment, resubmission, invoice generated). Oldest events appear first.</p>
                    @if($application->statusHistory && $application->statusHistory->count() > 0)
                        <div class="timeline">
                            @foreach($application->statusHistory as $index => $history)
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start">
                                    <div class="flex-grow-1" style="min-width: 200px;">
                                        <span class="badge bg-secondary me-1">{{ $index + 1 }}</span>
                                        <strong>{{ $history->status_display }}</strong>
                                        @if($history->notes)
                                            <p class="mb-0 text-muted small mt-2">{{ $history->notes }}</p>
                                        @endif
                                        <p class="mb-0 small text-muted mt-2">
                                            <span class="fw-semibold">Recorded by:</span> {{ $history->actorDescription() }}
                                        </p>
                                    </div>
                                    <div class="text-md-end">
                                        <div class="fw-semibold small">{{ $history->created_at->format('d M Y') }}</div>
                                        <div class="small text-muted">{{ $history->created_at->format('h:i A') }}</div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">No activity has been logged yet. After you submit, or when staff update your application, entries will appear here.</p>
                    @endif
                </div>
            </div>

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

