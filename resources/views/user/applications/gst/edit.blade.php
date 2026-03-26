@extends('user.layout')

@section('title', 'Update GST for Application')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4" style="color: #2c3e50; font-weight: 600;">Update GST Details</h2>
            
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('user.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('user.applications.index') }}">Applications</a></li>
                    <li class="breadcrumb-item active">Update GST</li>
                </ol>
            </nav>

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

            <form method="POST" action="{{ route('user.applications.gst.update', $application->id) }}" id="applicationGstEditForm">
                @csrf

                <!-- Application Information (Read-only) -->
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
                            @if($application->membership_id)
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Membership ID</label>
                                <div style="color: #2c3e50; font-weight: 500;">{{ $application->membership_id }}</div>
                            </div>
                            @endif
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Application Type</label>
                                <div style="color: #2c3e50;">{{ $application->application_type }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Current Stage</label>
                                <div style="color: #2c3e50;">{{ $application->current_stage }}</div>
                            </div>
                            @php
                                $locationData = $application->application_data['location'] ?? null;
                            @endphp
                            @if($locationData)
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Location</label>
                                <div style="color: #2c3e50;">{{ $locationData['name'] ?? 'N/A' }}{!! isset($locationData['state']) ? ', '.$locationData['state'] : '' !!}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- GST Verification Details (Editable) -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                    <div class="card-header bg-success text-white" style="border-radius: 16px 16px 0 0;">
                        <h5 class="mb-0" style="font-weight: 600;">GST Verification Details</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-info mb-3">
                            <small><i class="fas fa-info-circle"></i> This GST update will only apply to this specific application. Other applications will not be affected.</small>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">GSTIN <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="gstin" id="gstin" class="form-control" value="{{ old('gstin', $gstin ?? '') }}" maxlength="15" placeholder="15 character GSTIN" required>
                                    <button type="button" class="btn btn-outline-primary" id="verifyGstBtn" style="display: none;">
                                        <span id="verifyGstBtnText">Verify</span>
                                        <span id="verifyGstBtnLoader" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                    </button>
                                </div>
                                <div id="gstVerificationStatus" class="mt-2" style="display: none;"></div>
                                <input type="hidden" id="gstVerificationId" name="gst_verification_id" value="{{ $gstVerification?->id ?? '' }}">
                                <input type="hidden" id="gstVerified" name="gst_verified" value="{{ $gstVerified ? '1' : '0' }}">
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Current GST Details -->
                @php
                    $kycDetails = is_array($application->kyc_details) ? $application->kyc_details : [];
                @endphp
                <div id="currentGstDetailsSection" class="card border-0 shadow-sm mb-4" style="border-radius: 16px; @if(empty($kycDetails['gstin']) && empty($kycDetails['legal_name'])) display: none; @endif">
                    <div class="card-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
                        <h5 class="mb-0" style="font-weight: 600;">Current GST Details</h5>
                    </div>
                    <div class="card-body p-4">
                        <div id="currentGstDetailsContent" class="row g-3">
                            @if(isset($kycDetails['gstin']))
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">GSTIN</label>
                                <div id="current-gstin" style="color: #2c3e50; font-weight: 500;">{{ $kycDetails['gstin'] }}</div>
                            </div>
                            @endif
                            @if(isset($kycDetails['legal_name']))
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Business Name</label>
                                <div id="current-legal-name" style="color: #2c3e50;">{{ $kycDetails['legal_name'] }}</div>
                            </div>
                            @endif
                            @if(isset($kycDetails['trade_name']))
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Trade Name</label>
                                <div id="current-trade-name" style="color: #2c3e50;">{{ $kycDetails['trade_name'] }}</div>
                            </div>
                            @endif
                            @if(isset($kycDetails['constitution_of_business']))
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Entity Type</label>
                                <div id="current-constitution" style="color: #2c3e50;">{{ $kycDetails['constitution_of_business'] }}</div>
                            </div>
                            @endif
                            @if(isset($kycDetails['registration_date']))
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Registration Date</label>
                                <div id="current-reg-date" style="color: #2c3e50;">
                                    @php
                                        $regDate = $kycDetails['registration_date'];
                                        if ($regDate && !is_string($regDate)) {
                                            $regDate = \Carbon\Carbon::parse($regDate)->format('d M Y');
                                        }
                                    @endphp
                                    {{ $regDate ?? 'N/A' }}
                                </div>
                            </div>
                            @endif
                            @if(isset($kycDetails['gst_type']))
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">GST Type</label>
                                <div id="current-gst-type" style="color: #2c3e50;">{{ $kycDetails['gst_type'] }}</div>
                            </div>
                            @endif
                            @if(isset($kycDetails['company_status']))
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Company Status</label>
                                <div id="current-company-status" style="color: #2c3e50;">
                                    {{ $kycDetails['company_status'] }}
                                    @if($kycDetails['gst_verified'] ?? false)
                                        <span class="badge bg-success ms-2">Verified</span>
                                    @endif
                                </div>
                            </div>
                            @endif
                            @if(isset($kycDetails['billing_address']))
                            <div class="col-12">
                                <label class="text-muted small mb-1">Billing Address</label>
                                <div id="current-billing-address" style="color: #2c3e50;">
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
                                        {{ $billingAddress['address'] ?? $billingAddress['label'] ?? $billingAddress }}
                                    @else
                                        {{ $billingAddress }}
                                    @endif
                                </div>
                            </div>
                            @endif
                            @if(isset($kycDetails['state']))
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">State</label>
                                <div id="current-state" style="color: #2c3e50;">{{ $kycDetails['state'] }}</div>
                            </div>
                            @endif
                            @if(isset($kycDetails['billing_pincode']))
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Pincode</label>
                                <div id="current-pincode" style="color: #2c3e50;">{{ $kycDetails['billing_pincode'] }}</div>
                            </div>
                            @elseif(isset($kycDetails['pincode']))
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Pincode</label>
                                <div id="current-pincode" style="color: #2c3e50;">{{ $kycDetails['pincode'] }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- New GST Details (Will be populated after verification) -->
                <div id="newGstDetailsSection" class="card border-0 shadow-sm mb-4" style="border-radius: 16px; display: none;">
                    <div class="card-header bg-warning text-dark" style="border-radius: 16px 16px 0 0;">
                        <h5 class="mb-0" style="font-weight: 600;">New GST Details (Will be Updated)</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-warning mb-3">
                            <small><i class="fas fa-exclamation-triangle"></i> The following details will replace the current GST details for this application.</small>
                        </div>
                        <div id="newGstDetailsContent" class="row g-3">
                            <!-- Content will be populated by JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('user.applications.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update GST</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
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

        // Call verify endpoint for application
        fetch('{{ route("user.applications.gst.verify-gst", $application->id) }}', {
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

            fetch('{{ route("user.profile.check-gst-status") }}', {
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
                        statusEl.innerHTML = '<div class="alert alert-success mb-0 py-2">' +
                            '<div class="d-flex align-items-center">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-2">' +
                            '<path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>' +
                            '</svg>' +
                            '<strong>GSTIN verified successfully!</strong>' +
                            '</div>' +
                            '<small>' + (data.message || 'GST verification completed. You can now update the application.') + '</small>' +
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

    function displayNewGstDetails(verificationData) {
        // Update current GST details section first
        updateCurrentGstDetails(verificationData);
        
        // Also show new GST details section
        const newGstSection = document.getElementById('newGstDetailsSection');
        const newGstContent = document.getElementById('newGstDetailsContent');
        
        if (!newGstSection || !newGstContent) {
            return;
        }

        // Format registration date
        let regDate = 'N/A';
        if (verificationData.registration_date) {
            const date = new Date(verificationData.registration_date);
            regDate = date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        }

        // Build HTML for new GST details
        let html = '';
        
        if (verificationData.gstin) {
            html += '<div class="col-md-6"><label class="text-muted small mb-1">GSTIN</label><div style="color: #2c3e50; font-weight: 500;">' + verificationData.gstin + '</div></div>';
        }
        if (verificationData.legal_name) {
            html += '<div class="col-md-6"><label class="text-muted small mb-1">Business Name</label><div style="color: #2c3e50;">' + verificationData.legal_name + '</div></div>';
        }
        if (verificationData.trade_name) {
            html += '<div class="col-md-6"><label class="text-muted small mb-1">Trade Name</label><div style="color: #2c3e50;">' + verificationData.trade_name + '</div></div>';
        }
        if (verificationData.constitution_of_business) {
            html += '<div class="col-md-6"><label class="text-muted small mb-1">Entity Type</label><div style="color: #2c3e50;">' + verificationData.constitution_of_business + '</div></div>';
        }
        if (verificationData.registration_date) {
            html += '<div class="col-md-6"><label class="text-muted small mb-1">Registration Date</label><div style="color: #2c3e50;">' + regDate + '</div></div>';
        }
        if (verificationData.gst_type) {
            html += '<div class="col-md-6"><label class="text-muted small mb-1">GST Type</label><div style="color: #2c3e50;">' + verificationData.gst_type + '</div></div>';
        }
        if (verificationData.company_status) {
            html += '<div class="col-md-6"><label class="text-muted small mb-1">Company Status</label><div style="color: #2c3e50;">' + verificationData.company_status + '</div></div>';
        }
        if (verificationData.primary_address) {
            html += '<div class="col-12"><label class="text-muted small mb-1">Billing Address</label><div style="color: #2c3e50;">' + verificationData.primary_address + '</div></div>';
        }
        if (verificationData.state) {
            html += '<div class="col-md-6"><label class="text-muted small mb-1">State</label><div style="color: #2c3e50;">' + verificationData.state + '</div></div>';
        }
        if (verificationData.pincode) {
            html += '<div class="col-md-6"><label class="text-muted small mb-1">Pincode</label><div style="color: #2c3e50;">' + verificationData.pincode + '</div></div>';
        }

        if (html) {
            newGstContent.innerHTML = html;
            newGstSection.style.display = 'block';
            // Scroll to new GST details section
            newGstSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function updateCurrentGstDetails(verificationData) {
        const currentSection = document.getElementById('currentGstDetailsSection');
        if (!currentSection) {
            return;
        }

        // Show the section if it was hidden
        currentSection.style.display = 'block';

        // Format registration date
        let regDate = 'N/A';
        if (verificationData.registration_date) {
            const date = new Date(verificationData.registration_date);
            regDate = date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        }

        // Update each field if element exists
        const updateField = (id, value) => {
            const element = document.getElementById(id);
            if (element && value) {
                element.textContent = value;
                // Show parent div if it was hidden
                const parentDiv = element.closest('.col-md-6, .col-12');
                if (parentDiv) {
                    parentDiv.style.display = 'block';
                }
            }
        };

        updateField('current-gstin', verificationData.gstin);
        updateField('current-legal-name', verificationData.legal_name);
        updateField('current-trade-name', verificationData.trade_name);
        updateField('current-constitution', verificationData.constitution_of_business);
        updateField('current-reg-date', regDate);
        updateField('current-gst-type', verificationData.gst_type);
        updateField('current-company-status', verificationData.company_status);
        updateField('current-billing-address', verificationData.primary_address);
        updateField('current-state', verificationData.state);
        updateField('current-pincode', verificationData.pincode);

        // Add verified badge to company status if not already present
        const companyStatusEl = document.getElementById('current-company-status');
        if (companyStatusEl && verificationData.company_status) {
            if (!companyStatusEl.querySelector('.badge')) {
                companyStatusEl.innerHTML = verificationData.company_status + ' <span class="badge bg-success ms-2">Verified</span>';
            }
        }
    }
});
</script>
@endsection

