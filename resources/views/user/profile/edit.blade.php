@extends('user.layout')

@section('title', 'Edit Profile')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4" style="color: #2c3e50; font-weight: 600;">Edit Profile</h2>
            
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('user.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('user.profile') }}">Profile</a></li>
                    <li class="breadcrumb-item active">Edit</li>
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

            <form method="POST" action="{{ route('user.profile.update') }}" id="profileEditForm">
                @csrf

                <!-- User Information (Read-only) -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                    <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                        <h5 class="mb-0" style="font-weight: 600;">User Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Registration ID</label>
                                <div style="color: #2c3e50; font-weight: 500;">{{ $user->registrationid }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Full Name</label>
                                <div style="color: #2c3e50; font-weight: 500;">{{ $user->fullname }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Email Address</label>
                                <div style="color: #2c3e50;">
                                    {{ $user->email }}
                                    @if($user->email_verified)
                                        <span class="badge bg-success ms-2">Verified</span>
                                    @else
                                        <span class="badge bg-danger ms-2">Not Verified</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Mobile Number</label>
                                <div style="color: #2c3e50;">
                                    {{ $user->mobile }}
                                    @if($user->mobile_verified)
                                        <span class="badge bg-success ms-2">Verified</span>
                                    @else
                                        <span class="badge bg-danger ms-2">Not Verified</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">PAN</label>
                                <div style="color: #2c3e50;">
                                    {{ $user->pancardno }}
                                    @if($user->pan_verified)
                                        <span class="badge bg-success ms-2">Verified</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GST Verification Details (Editable) -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                    <div class="card-header bg-success text-white" style="border-radius: 16px 16px 0 0;">
                        <h5 class="mb-0" style="font-weight: 600;">GST Verification Details</h5>
                    </div>
                    <div class="card-body p-4">
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

                        <!-- Display GST Verification Details (Read-only) -->
                        @if($gstVerification)
                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Business Name</label>
                                <div style="color: #2c3e50;">{{ $gstVerification->legal_name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Trade Name</label>
                                <div style="color: #2c3e50;">{{ $gstVerification->trade_name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Entity Type</label>
                                <div style="color: #2c3e50;">{{ $gstVerification->constitution_of_business ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Registration Date</label>
                                <div style="color: #2c3e50;">{{ $gstVerification->registration_date ? $gstVerification->registration_date->format('d M Y') : 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">GST Type</label>
                                <div style="color: #2c3e50;">{{ $gstVerification->gst_type ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Company Status</label>
                                <div style="color: #2c3e50;">
                                    {{ $gstVerification->company_status ?? 'N/A' }}
                                    @if($gstVerification->is_verified)
                                        <span class="badge bg-success ms-2">Verified</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small mb-1">Primary Address</label>
                                <div style="color: #2c3e50;">{{ $gstVerification->primary_address ?? 'N/A' }}</div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- KYC Details (Read-only) -->
                @php
                    $userKycProfile = \App\Models\UserKycProfile::where('user_id', $user->id)
                        ->where('status', 'completed')
                        ->latest()
                        ->first();
                @endphp
             

                <!-- Form Actions -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('user.profile') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Profile</button>
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

        // Call verify endpoint
        fetch('{{ route("user.profile.verify-gst") }}', {
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

        fetch('{{ route("user.profile.complete-kyc") }}', {
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
                    '<br><small>' + (data.message || 'KYC has been completed.') + '</small>' +
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
