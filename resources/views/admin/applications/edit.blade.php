@extends('admin.layout')

@section('title', 'Update Application')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1" style="color: #2c3e50; font-weight: 600;">Update Application</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.applications') }}">Applications</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.applications.show', $application->id) }}">{{ $application->application_id }}</a></li>
                    <li class="breadcrumb-item active">Update</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.applications.update', $application->id) }}" enctype="multipart/form-data">
        @csrf

        <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
            <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                <h5 class="mb-0" style="font-weight: 600;">Application Information</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Application ID</label>
                        <div style="color: #2c3e50; font-weight: 500;">{{ $application->application_id }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">User</label>
                        <div style="color: #2c3e50;">
                            <a href="{{ route('admin.users.show', $application->user_id) }}" style="color: #0d6efd; text-decoration: none;">
                                {{ $application->user->fullname }}
                            </a>
                            <br>
                            <small class="text-muted">{{ $application->user->email }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($application->application_type === 'IX')
        @php
            $representative = $applicationData['representative'] ?? [];
            $portSelection = $applicationData['port_selection'] ?? [];
            $ipPrefix = $applicationData['ip_prefix'] ?? [];
            $peering = $applicationData['peering'] ?? [];
            $routerDetails = $applicationData['router_details'] ?? [];
            $location = $applicationData['location'] ?? [];
        @endphp

        <!-- Representative & Contact Details -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
            <div class="card-header bg-success text-white" style="border-radius: 16px 16px 0 0;">
                <h5 class="mb-0" style="font-weight: 600;">Representative & Contact Details</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Representative Name</label>
                        <input type="text" name="representative_name" class="form-control" value="{{ old('representative_name', $representative['name'] ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Representative Email</label>
                        <input type="email" name="representative_email" class="form-control" value="{{ old('representative_email', $representative['email'] ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Representative Mobile</label>
                        <input type="tel" name="representative_mobile" class="form-control" value="{{ old('representative_mobile', $representative['mobile'] ?? '') }}" maxlength="10" pattern="[0-9]{10}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">GSTIN</label>
                        <div class="input-group">
                            <input type="text" name="gstin" id="gstin" class="form-control" value="{{ old('gstin', $applicationData['gstin'] ?? '') }}" maxlength="15" placeholder="15 character GSTIN">
                            <button type="button" class="btn btn-outline-primary" id="verifyGstBtn" style="display: none;">
                                <span id="verifyGstBtnText">Verify</span>
                                <span id="verifyGstBtnLoader" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                        <div id="gstVerificationStatus" class="mt-2" style="display: none;"></div>
                        <input type="hidden" id="gstVerificationId" name="gst_verification_id" value="">
                        <input type="hidden" id="gstVerified" name="gst_verified" value="0">
                    </div>
                </div>
            </div>
        </div>

        <!-- Port & Billing Details -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
            <div class="card-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
                <h5 class="mb-0" style="font-weight: 600;">Port & Billing Details</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Port Capacity</label>
                        <input type="text" name="port_capacity" class="form-control" value="{{ old('port_capacity', $portSelection['capacity'] ?? '') }}" placeholder="e.g., 1G, 10G">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Billing Plan</label>
                        <select name="billing_plan" class="form-select">
                            <option value="">Select Plan</option>
                            <option value="arc" {{ old('billing_plan', $portSelection['billing_plan'] ?? '') === 'arc' ? 'selected' : '' }}>Annual (ARC)</option>
                            <option value="mrc" {{ old('billing_plan', $portSelection['billing_plan'] ?? '') === 'mrc' ? 'selected' : '' }}>Monthly (MRC)</option>
                            <option value="quarterly" {{ old('billing_plan', $portSelection['billing_plan'] ?? '') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">IP Prefix Count</label>
                        <input type="number" name="ip_prefix_count" class="form-control" value="{{ old('ip_prefix_count', $ipPrefix['count'] ?? '') }}" min="1" max="500" placeholder="Number of prefixes">
                    </div>
                </div>
            </div>
        </div>

        <!-- Peering & Router Details -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
            <div class="card-header bg-warning text-dark" style="border-radius: 16px 16px 0 0;">
                <h5 class="mb-0" style="font-weight: 600;">Peering & Router Details</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">ASN Number</label>
                        <input type="text" name="asn_number" class="form-control" value="{{ old('asn_number', $peering['asn_number'] ?? '') }}" placeholder="ASN Number">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pre-NIXI Connectivity</label>
                        <select name="pre_peering_connectivity" class="form-select">
                            <option value="">Select</option>
                            <option value="yes" {{ old('pre_peering_connectivity', $peering['pre_nixi_connectivity'] ?? '') === 'yes' ? 'selected' : '' }}>Yes</option>
                            <option value="no" {{ old('pre_peering_connectivity', $peering['pre_nixi_connectivity'] ?? '') === 'no' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Router Height (U)</label>
                        <input type="text" name="router_height_u" class="form-control" value="{{ old('router_height_u', $routerDetails['height_u'] ?? '') }}" placeholder="Height in U">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Router Make & Model</label>
                        <input type="text" name="router_make_model" class="form-control" value="{{ old('router_make_model', $routerDetails['make_model'] ?? '') }}" placeholder="Make & Model">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Router Serial Number</label>
                        <input type="text" name="router_serial_number" class="form-control" value="{{ old('router_serial_number', $routerDetails['serial_number'] ?? '') }}" placeholder="Serial Number">
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Documents -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
            <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                <h5 class="mb-0" style="font-weight: 600;">Update Documents</h5>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-4">Upload new documents to replace existing ones or add missing documents. Maximum size per document is 10 MB. Only PDF files are allowed.</p>
                
                <div class="row g-3">
                    @php
                        $documentNames = [
                            'agreement_file' => 'Signed Agreement with NIXI',
                            'license_isp_file' => 'ISP License',
                            'license_vno_file' => 'VNO License',
                            'cdn_declaration_file' => 'CDN Declaration',
                            'general_declaration_file' => 'General Declaration',
                            'whois_details_file' => 'Whois Details',
                            'pan_document_file' => 'PAN Document',
                            'gstin_document_file' => 'GSTIN Document',
                            'msme_document_file' => 'MSME (Udyog/Udyam) Certificate',
                            'incorporation_document_file' => 'Certificate of Incorporation',
                            'authorized_rep_document_file' => 'Authorized Representative Document',
                        ];
                    @endphp

                    @foreach($documentNames as $key => $label)
                    <div class="col-md-6">
                        <label class="form-label">{{ $label }}</label>
                        <input type="file" name="{{ $key }}" class="form-control" accept="application/pdf">
                        @if(isset($documents[$key]) && \Illuminate\Support\Facades\Storage::disk('public')->exists($documents[$key]))
                            <div class="mt-2">
                                <small class="text-success d-flex align-items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                    </svg>
                                    Current document exists
                                </small>
                                <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => $key]) }}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                        <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                                    </svg>
                                    View Current
                                </a>
                            </div>
                        @else
                            <small class="text-muted d-block mt-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                </svg>
                                No document uploaded
                            </small>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Live Application Details (for live applications only) --}}
        @if($application->is_active && $application->application_type === 'IX')
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
            <div class="card-header bg-success text-white" style="border-radius: 16px 16px 0 0;">
                <h5 class="mb-0" style="font-weight: 600;">Live Application Details</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Service Activation Date <span class="text-danger">*</span></label>
                        <input type="date" 
                               name="service_activation_date" 
                               class="form-control" 
                               value="{{ old('service_activation_date', $application->service_activation_date ? $application->service_activation_date->format('Y-m-d') : '') }}"
                               required>
                        <small class="text-muted">Date when service was activated</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Billing Cycle</label>
                        <select name="billing_cycle" class="form-select">
                            <option value="">Select Billing Cycle</option>
                            <option value="monthly" {{ old('billing_cycle', $application->billing_cycle) === 'monthly' ? 'selected' : '' }}>Monthly (MRC)</option>
                            <option value="quarterly" {{ old('billing_cycle', $application->billing_cycle) === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                            <option value="annual" {{ old('billing_cycle', $application->billing_cycle) === 'annual' ? 'selected' : '' }}>Annual (ARC)</option>
                        </select>
                        <small class="text-muted">Billing cycle for recurring invoices</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Assigned Port Capacity</label>
                        <input type="text" 
                               name="assigned_port_capacity" 
                               class="form-control" 
                               value="{{ old('assigned_port_capacity', $application->assigned_port_capacity ?? '') }}"
                               placeholder="e.g., 1Gig, 10Gig">
                        <small class="text-muted">Port capacity assigned to this application</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Assigned IP Address</label>
                        <input type="text" 
                               name="assigned_ip" 
                               class="form-control" 
                               value="{{ old('assigned_ip', $application->assigned_ip ?? '') }}"
                               placeholder="e.g., 192.168.1.1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Customer ID</label>
                        <input type="text" 
                               name="customer_id" 
                               class="form-control" 
                               value="{{ old('customer_id', $application->customer_id ?? '') }}"
                               placeholder="Customer ID">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Membership ID</label>
                        <input type="text" 
                               name="membership_id" 
                               class="form-control" 
                               value="{{ old('membership_id', $application->membership_id ?? '') }}"
                               placeholder="Membership ID">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Assigned Port Number</label>
                        <input type="text" 
                               name="assigned_port_number" 
                               class="form-control" 
                               value="{{ old('assigned_port_number', $application->assigned_port_number ?? '') }}"
                               placeholder="Port number">
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="d-flex justify-content-between gap-2">
            <a href="{{ route('admin.applications.show', $application->id) }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                    <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                </svg>
                Update Application
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const gstinInput = document.getElementById('gstin');
    const verifyBtn = document.getElementById('verifyGstBtn');
    const verifyBtnText = document.getElementById('verifyGstBtnText');
    const verifyBtnLoader = document.getElementById('verifyGstBtnLoader');
    const statusEl = document.getElementById('gstVerificationStatus');
    const gstVerificationIdInput = document.getElementById('gstVerificationId');
    const gstVerifiedInput = document.getElementById('gstVerified');
    let verificationRequestId = null;
    let pollingInterval = null;

    // Get initial GSTIN value
    const originalGstin = gstinInput.value.trim().toUpperCase();

    // Check if GSTIN changes - show/hide verify button accordingly
    gstinInput.addEventListener('input', function() {
        const currentGstin = this.value.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
        const normalizedOriginal = originalGstin.replace(/[^A-Z0-9]/g, '');
        
        if (currentGstin !== normalizedOriginal && currentGstin.length === 15) {
            // GSTIN has changed - show verify button
            verifyBtn.style.display = 'inline-block';
            // Reset verification status when GSTIN changes
            gstVerifiedInput.value = '0';
            gstVerificationIdInput.value = '';
            statusEl.style.display = 'none';
            verifyBtn.disabled = false;
            verifyBtnText.textContent = 'Verify';
            verifyBtnLoader.classList.add('d-none');
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
        } else if (currentGstin === normalizedOriginal || currentGstin === '') {
            // GSTIN is same as original or empty - hide verify button
            verifyBtn.style.display = 'none';
            statusEl.style.display = 'none';
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
        } else if (currentGstin.length < 15) {
            // Still typing - hide button until 15 characters
            verifyBtn.style.display = 'none';
            statusEl.style.display = 'none';
        }
    });

    // Verify GST button click handler
    verifyBtn.addEventListener('click', function() {
        const gstin = gstinInput.value.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
        
        if (!gstin || gstin.length !== 15) {
            alert('Please enter a valid 15-character GSTIN.');
            return;
        }

        // Update input with normalized value
        gstinInput.value = gstin;

        // Disable button and show loading
        verifyBtn.disabled = true;
        verifyBtnText.textContent = 'Verifying...';
        verifyBtnLoader.classList.remove('d-none');
        statusEl.style.display = 'block';
        statusEl.className = 'mt-2';
        statusEl.innerHTML = '<div class="alert alert-info mb-0 py-2"><small><i class="fas fa-spinner fa-spin"></i> Initiating GST verification...</small></div>';

        // Call verify endpoint
        fetch('{{ route("admin.applications.verify-gst", $application->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ gstin: gstin })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                verificationRequestId = data.request_id;
                gstVerificationIdInput.value = data.verification_id;
                statusEl.innerHTML = '<div class="alert alert-info mb-0 py-2"><small><i class="fas fa-spinner fa-spin"></i> GST verification in progress...</small></div>';
                
                // Start polling for status
                pollVerificationStatus(data.verification_id);
            } else {
                throw new Error(data.message || 'Failed to initiate GST verification');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            statusEl.innerHTML = '<div class="alert alert-danger mb-0 py-2"><small>' + (error.message || 'Error verifying GSTIN. Please try again.') + '</small></div>';
            verifyBtn.disabled = false;
            verifyBtnText.textContent = 'Verify';
            verifyBtnLoader.classList.add('d-none');
        });
    });

    function pollVerificationStatus(verificationId) {
        let retryCount = 0;
        const maxRetries = 10;

        pollingInterval = setInterval(function() {
            if (retryCount >= maxRetries) {
                clearInterval(pollingInterval);
                pollingInterval = null;
                statusEl.innerHTML = '<div class="alert alert-warning mb-0 py-2"><small>Verification is taking longer than expected. Please check again later.</small></div>';
                verifyBtn.disabled = false;
                verifyBtnText.textContent = 'Verify';
                verifyBtnLoader.classList.add('d-none');
                return;
            }

            retryCount++;

            fetch('{{ route("admin.applications.check-gst-status", $application->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ verification_id: verificationId })
            })
            .then(response => response.json())
            .then(data => {
                    if (data.status === 'completed') {
                        clearInterval(pollingInterval);
                        pollingInterval = null;

                        if (data.is_verified) {
                            statusEl.innerHTML = '<div class="alert alert-success mb-2 py-2">' +
                                '<div class="d-flex align-items-center mb-2">' +
                                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-2">' +
                                '<path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>' +
                                '</svg>' +
                                '<strong>GSTIN verified successfully!</strong>' +
                                '</div>' +
                                '<small>' + (data.message || 'GST verification completed. Please complete the KYC process.') + '</small>' +
                                '</div>' +
                                '<div class="mt-2">' +
                                '<button type="button" id="completeKycBtn" class="btn btn-success btn-sm">' +
                                '<span id="completeKycBtnText"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/></svg>Complete KYC</span>' +
                                '<span id="completeKycBtnLoader" class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true"></span>' +
                                '</button>' +
                                '</div>';
                            gstVerifiedInput.value = '1';
                            verifyBtn.disabled = true;
                            verifyBtnText.textContent = 'Verified';
                            verifyBtnLoader.classList.add('d-none');
                        } else {
                            statusEl.innerHTML = '<div class="alert alert-danger mb-0 py-2"><small><i class="fas fa-times-circle"></i> ' + (data.message || 'GSTIN verification failed') + '</small></div>';
                            verifyBtn.disabled = false;
                            verifyBtnText.textContent = 'Verify';
                            verifyBtnLoader.classList.add('d-none');
                        }
                    } else if (data.status === 'failed') {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                    statusEl.innerHTML = '<div class="alert alert-danger mb-0 py-2"><small><i class="fas fa-times-circle"></i> ' + (data.message || 'GSTIN verification failed') + '</small></div>';
                    verifyBtn.disabled = false;
                    verifyBtnText.textContent = 'Verify';
                    verifyBtnLoader.classList.add('d-none');
                }
                // If status is still 'in_progress', continue polling
            })
            .catch(error => {
                console.error('Error checking status:', error);
                // Continue polling on error
            });
        }, 2000); // Poll every 2 seconds
    }

    // Handle Complete KYC button click (dynamically added, so use event delegation)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('#completeKycBtn');
        if (btn) {
            completeKyc(btn);
        }
    });

    function completeKyc(btn) {
        const btnText = document.getElementById('completeKycBtnText');
        const btnLoader = document.getElementById('completeKycBtnLoader');
        
        // Disable button and show loading
        btn.disabled = true;
        if (btnText) btnText.style.display = 'none';
        if (btnLoader) {
            btnLoader.classList.remove('d-none');
        }

        fetch('{{ route("admin.applications.complete-kyc", $application->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update status message
                const statusEl = document.getElementById('gstVerificationStatus');
                statusEl.innerHTML = '<div class="alert alert-success mb-0 py-2">' +
                    '<div class="d-flex align-items-center">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-2">' +
                    '<path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>' +
                    '</svg>' +
                    '<div>' +
                    '<strong>KYC Completed Successfully!</strong>' +
                    '<br><small>' + (data.message || 'KYC has been completed for this application.') + '</small>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                
                // Update button to show completed state
                if (btnText) {
                    btnText.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/></svg>KYC Completed';
                    btnText.style.display = 'inline-block';
                }
                if (btnLoader) {
                    btnLoader.classList.add('d-none');
                }
                btn.classList.remove('btn-success');
                btn.classList.add('btn-secondary');
            } else {
                throw new Error(data.message || 'Failed to complete KYC');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const statusEl = document.getElementById('gstVerificationStatus');
            const existingAlert = statusEl.querySelector('.alert-success');
            if (existingAlert) {
                existingAlert.innerHTML = '<div class="alert alert-danger mb-0 py-2"><small><i class="fas fa-times-circle"></i> ' + (error.message || 'Failed to complete KYC. Please try again.') + '</small></div>';
            } else {
                statusEl.innerHTML = '<div class="alert alert-danger mb-0 py-2"><small><i class="fas fa-times-circle"></i> ' + (error.message || 'Failed to complete KYC. Please try again.') + '</small></div>';
            }
            
            // Re-enable button on error
            btn.disabled = false;
            if (btnText) btnText.style.display = 'inline-block';
            if (btnLoader) {
                btnLoader.classList.add('d-none');
            }
        });
    }
});
</script>
@endsection
