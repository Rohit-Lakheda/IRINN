@extends('register.layout')

@section('title', 'Registration')

@section('content')
<div class="auth-row">
        <div class="col-md-5 col-lg-4 left-section">
            <div class="left-section-logo">
                @include('partials.logo')
            </div>
            <img src="{{ asset('images/IRINN.png') }}" alt="IRINN illustration" class="img-fluid d-none d-lg-block" />
        </div>
        <div class="col-md-12 col-lg-7 col-xl-7 right-section">
        <div class="card before-login w-100">
            <div class="card-header">
                <h4 class="mb-0">Registration for IRINN Portal</h4>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger" style="border-left: 4px solid #4169E1; color: #4169E1;">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <p class="lead fs-6">Please fill out the form below to register for an account.</p>
                <form method="POST" action="{{ route('register.store') }}" id="registrationForm">
                    @csrf
                    
                    <!-- Registration Type Selection -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Registration Type <span class="text-danger">*</span></label>
                        <div class="d-flex gap-4">
                        <div class="form-check d-flex align-items-center input-style">
                                <input class="form-check-input" type="radio" name="registration_type" id="registration_type_entity" value="entity" {{ old('registration_type' , 'entity') === 'entity' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="registration_type_entity">
                                    Entity
                                </label>
                            </div>
                            <div class="form-check d-flex align-items-center input-style">
                                <input class="form-check-input" type="radio" name="registration_type" id="registration_type_individual" value="individual" {{ old('registration_type') === 'individual' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="registration_type_individual">
                                    Individual
                                </label>
                            </div>
                           
                        </div>
                        @error('registration_type')
                            <div class="small mt-1" style="color: #4169E1;">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="row gy-3 mb-3">
                        <div class="col-md-12 col-lg-12">
                            <label for="fullname" class="form-label" id="fullnameLabel">Full Name (As per PAN) <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('fullname') is-invalid @enderror" 
                                   id="fullname" 
                                   name="fullname" 
                                   value="{{ old('fullname') }}"
                                   placeholder="Enter full name or affiliate name"
                                   required>
                            @error('fullname')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row gy-3">
                        <div class="col-md-12 col-lg-6">
                            <label for="dateofbirth" class="form-label" id="dateofbirthLabel">Date of Birth (As per PAN)<span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control @error('dateofbirth') is-invalid @enderror" 
                                   id="dateofbirth" 
                                   name="dateofbirth" 
                                   value="{{ old('dateofbirth') }}"
                                   required>
                            @error('dateofbirth')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-12 col-lg-6 mb-3 mt-3">
                            <label for="pancardno" class="form-label">PAN Number <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" 
                                    class="form-control @error('pancardno') is-invalid @enderror" 
                                    id="pancardno" 
                                    name="pancardno" 
                                    value="{{ old('pancardno') }}"
                                    placeholder="ABCDE1234F"
                                    maxlength="10"
                                    required>
                                <button type="button" 
                                        class="btn btn-outline-primary btn-sm" 
                                        id="verifyPanBtn"
                                        onclick="verifyPan()">
                                    Verify PAN
                                </button>
                            </div>
                            <div id="panVerificationStatus" class="mt-2" style="display: none;"></div>
                            @error('pancardno')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row gy-3 mb-3">
                        <div class="col-md-12 col-lg-6">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}"
                                       required>
                                <button type="button" 
                                        class="btn btn-outline-primary btn-sm" 
                                        id="getEmailOtpBtn"
                                        onclick="getEmailOtp()">
                                    Get OTP
                                </button>
                            </div>
                            <div id="emailOtpSection" style="display: none;" class="mt-2">
                                <div class="input-group">
                                    <input type="text" 
                                           class="form-control" 
                                           id="email_otp" 
                                           name="email_otp" 
                                           placeholder="Enter 6-digit OTP"
                                           maxlength="6">
                                    <button type="button" 
                                            class="btn btn-success" 
                                            onclick="verifyEmailOtp()">
                                        Verify
                                    </button>
                                </div>
                                <small class="form-text text-muted" id="emailOtpStatus"></small>
                            </div>
                            <div id="emailVerificationStatus" class="mt-2" style="display: none;"></div>
                            <input type="hidden" id="email_verified" name="email_verified" value="0">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 col-lg-6">
                            <label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="tel" 
                                       class="form-control @error('mobile') is-invalid @enderror" 
                                       id="mobile" 
                                       name="mobile" 
                                       value="{{ old('mobile') }}"
                                       placeholder="10-digit mobile number"
                                       maxlength="10"
                                       required>
                                <button type="button" 
                                        class="btn btn-outline-primary btn-sm" 
                                        id="getMobileOtpBtn"
                                        onclick="getMobileOtp()">
                                    Get OTP
                                </button>
                            </div>
                            <div id="mobileOtpSection" style="display: none;" class="mt-2">
                                <div class="input-group">
                                    <input type="text" 
                                           class="form-control" 
                                           id="mobile_otp" 
                                           name="mobile_otp" 
                                           placeholder="Enter 6-digit OTP"
                                           maxlength="6">
                                    <button type="button" 
                                            class="btn btn-success" 
                                            onclick="verifyMobileOtp()">
                                        Verify
                                    </button>
                                </div>
                                <small class="form-text text-muted" id="mobileOtpStatus"></small>
                            </div>
                            <div id="mobileVerificationStatus" class="mt-2" style="display: none;"></div>
                            <input type="hidden" id="mobile_verified" name="mobile_verified" value="0">
                            @error('mobile')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- Disclaimer/Declaration -->
                    <div class="row gy-3">
                        <div class="col-md-12 col-lg-12">
                            <div class="form-check d-flex input-style consent-wrapper">
                                <input class="form-check-input @error('declaration') is-invalid @enderror" 
                                    type="checkbox" 
                                    id="declaration" 
                                    name="declaration" 
                                    value="1" 
                                    required>
                                <label class="form-check-label" for="declaration">
                                    <span class="consent-text">
                                        I hereby authorize <strong>IRINN, NIXI</strong> to use this information for internet purposes and LEA desired requests. <span class="text-danger">*</span>
                                    </span>
                                    <span class="tooltip">
                                        I hereby authorize <strong>IRINN, NIXI</strong> to use this information for internet purposes and LEA desired requests.
                                    </span>
                                </label>
                            </div>
                            @error('declaration')
                                <div class="invalid-feedback d-block" style="color: #4169E1;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>  
                    
                    
                    <div class="mb-3 mt-3 text-center">
                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Register</button>
                    </div>
                    <div id="verificationWarning" class="alert alert-warning" style="display: none !important; border-left: 4px solid #4169E1; background-color: rgba(65, 105, 225, 0.2);">
                        Please complete all required fields and verifications before submitting the form.
                    </div>
                    
                    <div class="text-center">
                        <p>Already registered? <a href="{{ route('login.index') }}" class="text-blue">Sign in here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Override Bootstrap invalid-feedback color to royal blue */
    .invalid-feedback {
        color: #4169E1 !important;
    }
    
    /* Override Bootstrap is-invalid border color to royal blue */
    .form-control.is-invalid,
    .form-check-input.is-invalid {
        border-color: #4169E1 !important;
    }
    
    /* Override alert-danger text color to royal blue */
    .alert-danger {
        color: #4169E1 !important;
    }
    
    /* Override alert-warning border and text to royal blue accents */
    .alert-warning {
        border-left-color: #4169E1 !important;
    }
</style>
@endpush

@push('scripts')
<script>
    let emailVerified = false;
    let mobileVerified = false;
    let panVerified = false;
    let registrationType = 'individual'; // Default to individual

    // Registration Type Change Handler
    document.querySelectorAll('input[name="registration_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            registrationType = this.value;
            updateLabelsBasedOnRegistrationType();
        });
    });

    // Update labels based on registration type
    function updateLabelsBasedOnRegistrationType() {
        const fullnameLabel = document.getElementById('fullnameLabel');
        const fullnameInput = document.getElementById('fullname');
        const dateofbirthLabel = document.getElementById('dateofbirthLabel');
        
        if (registrationType === 'individual') {
            if (fullnameLabel) {
                fullnameLabel.innerHTML = 'Full Name (As per PAN) <span style="color: #4169E1;">*</span>';
            }
            if (fullnameInput) {
                fullnameInput.placeholder = 'Enter your full name';
            }
            if (dateofbirthLabel) {
                dateofbirthLabel.innerHTML = 'Date of Birth (As per PAN) <span style="color: #4169E1;">*</span>';
            }
        } else {
            if (fullnameLabel) {
                fullnameLabel.innerHTML = 'Entity Name (As per PAN) <span style="color: #4169E1;">*</span>';
            }
            if (fullnameInput) {
                fullnameInput.placeholder = 'Enter entity/company name';
            }
            if (dateofbirthLabel) {
                dateofbirthLabel.innerHTML = 'Date of Incorporation (As per PAN) <span style="color: #4169E1;">*</span>';
            }
        }
    }

    // Initialize registration type on page load
    const selectedType = document.querySelector('input[name="registration_type"]:checked');
    if (selectedType) {
        registrationType = selectedType.value;
        updateLabelsBasedOnRegistrationType();
    }

    // PAN Card format validation (client-side)
    document.getElementById('pancardno').addEventListener('input', function(e) {
        let value = e.target.value.toUpperCase();
        // Remove any non-alphanumeric characters
        value = value.replace(/[^A-Z0-9]/g, '');
        e.target.value = value;
    });
    

    // Verify PAN Card
    function verifyPan() {
        const panNo = document.getElementById('pancardno').value;
        const fullName = document.getElementById('fullname').value;
        const dob = document.getElementById('dateofbirth').value;
        
        if (!fullName) {
            const fieldName = registrationType === 'individual' ? 'Full Name' : 'Entity Name';
            alert(`Please enter your ${fieldName} first.`);
            return;
        }

        if (!dob) {
            const fieldName = registrationType === 'individual' ? 'Date of Birth' : 'Date of Incorporation';
            alert(`Please enter your ${fieldName} first.`);
            return;
        }
        
        if (!panNo) {
            alert('Please enter your PAN Number first.');
            return;
        }

        // Validate PAN format
        const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
        if (!panRegex.test(panNo)) {
            alert('Please enter a valid PAN Number. Format: ABCDE1234F');
            return;
        }

        const btn = document.getElementById('verifyPanBtn');
        const statusDiv = document.getElementById('panVerificationStatus');
        
        btn.disabled = true;
        btn.textContent = 'Verifying...';
        statusDiv.style.display = 'block';
        statusDiv.innerHTML = '<small class="text-info">Creating verification task...</small>';

        // Create verification task
        fetch('{{ route("register.verify.pan") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ 
                pancardno: panNo,
                fullname: fullName,
                dateofbirth: dob
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.request_id) {
                // Start polling for status
                statusDiv.innerHTML = '<small class="text-info">Verifying PAN... Please wait...</small>';
                pollPanStatus(data.request_id);
            } else {
                statusDiv.innerHTML = '<small style="color: #4169E1;">' + (data.message || 'PAN verification failed. Please check and try again.') + '</small>';
                btn.disabled = false;
                btn.textContent = 'Verify PAN';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            statusDiv.innerHTML = '<small style="color: #4169E1;">An error occurred. Please try again.</small>';
            btn.disabled = false;
            btn.textContent = 'Verify PAN';
        });
    }

    // Poll PAN verification status
    function pollPanStatus(requestId, retries = 0, maxRetries = 15) {
        const btn = document.getElementById('verifyPanBtn');
        const statusDiv = document.getElementById('panVerificationStatus');

        if (retries >= maxRetries) {
            statusDiv.innerHTML = '<small style="color: #4169E1;">Verification timeout. Please try again.</small>';
            btn.disabled = false;
            btn.textContent = 'Verify PAN';
            return;
        }

        fetch('{{ route("register.check.pan.status") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ request_id: requestId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'completed') {
                if (data.success) {
                    // Verification successful
                    panVerified = true;
                    const panInput = document.getElementById('pancardno');
                    const fullnameInput = document.getElementById('fullname');
                    const dobInput = document.getElementById('dateofbirth');
                    
                    // Make fields readonly
                    panInput.readOnly = true;
                    panInput.style.backgroundColor = '#d4edda';
                    panInput.style.borderColor = '#28a745';
                    panInput.style.cursor = 'not-allowed';
                    
                    fullnameInput.readOnly = true;
                    fullnameInput.style.backgroundColor = '#d4edda';
                    fullnameInput.style.borderColor = '#28a745';
                    fullnameInput.style.cursor = 'not-allowed';
                    
                    dobInput.readOnly = true;
                    dobInput.style.backgroundColor = '#d4edda';
                    dobInput.style.borderColor = '#28a745';
                    dobInput.style.cursor = 'not-allowed';
                    
                    btn.disabled = true;
                    btn.textContent = 'Verified';
                    btn.classList.remove('btn-outline-primary');
                    btn.classList.add('btn-success');
                    statusDiv.innerHTML = '<small class="text-success"><strong>✓ PAN verified successfully!</strong></small>';
                    checkAllValidations();
                } else {
                    // Verification failed
                    statusDiv.innerHTML = '<small style="color: #4169E1;"><strong>✗ ' + (data.message || 'PAN verification failed. Please check and try again.') + '</strong></small>';
                    btn.disabled = false;
                    btn.textContent = 'Verify PAN';
                }
            } else if (data.status === 'failed') {
                statusDiv.innerHTML = '<small style="color: #4169E1;"><strong>✗ ' + (data.message || 'PAN verification failed.') + '</strong></small>';
                btn.disabled = false;
                btn.textContent = 'Verify PAN';
            } else {
                // Still processing, poll again after 2 seconds
                setTimeout(() => {
                    pollPanStatus(requestId, retries + 1, maxRetries);
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            statusDiv.innerHTML = '<small style="color: #4169E1;">An error occurred while checking status. Please try again.</small>';
            btn.disabled = false;
            btn.textContent = 'Verify PAN';
        });
    }

    // Initialize labels on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateLabelsBasedOnRegistrationType();
    });

    // Full name validation (client-side) - allow letters, spaces, and apostrophes
    document.getElementById('fullname').addEventListener('input', function(e) {
        let value = e.target.value;
        // Allow letters, spaces, apostrophes, and hyphens
        value = value.replace(/[^a-zA-Z\s'-]/g, '');
        e.target.value = value;
    });

    // Mobile number validation (client-side) - only digits
    document.getElementById('mobile').addEventListener('input', function(e) {
        let value = e.target.value;
        // Only allow digits
        value = value.replace(/\D/g, '');
        e.target.value = value;
    });

    // OTP input validation - only allow digits
    document.getElementById('email_otp')?.addEventListener('input', function(e) {
        let value = e.target.value;
        value = value.replace(/\D/g, '');
        e.target.value = value;
    });

    document.getElementById('mobile_otp')?.addEventListener('input', function(e) {
        let value = e.target.value;
        value = value.replace(/\D/g, '');
        e.target.value = value;
    });

    // Get Email OTP
    function getEmailOtp() {
        const email = document.getElementById('email').value;
        
        if (!email) {
            alert('Please enter your email address first.');
            return;
        }

        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Please enter a valid email address.');
            return;
        }

        const btn = document.getElementById('getEmailOtpBtn');
        btn.disabled = true;
        btn.textContent = 'Sending...';

        fetch('{{ route("register.send.email.otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ email: email })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('emailOtpSection').style.display = 'block';
                document.getElementById('emailOtpStatus').textContent = 'OTP sent to your email. Please check and enter it.';
                document.getElementById('emailOtpStatus').className = 'form-text text-success';
                if (data.otp) {
                    document.getElementById('emailOtpStatus').textContent += ' (Dev: ' + data.otp + ')';
                }
                btn.disabled = false;
                btn.textContent = 'Resend OTP';
            } else {
                alert(data.message || 'Failed to send OTP. Please try again.');
                btn.disabled = false;
                const otpSection = document.getElementById('emailOtpSection');
                btn.textContent = otpSection && otpSection.style.display !== 'none' ? 'Resend OTP' : 'Get OTP';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            btn.disabled = false;
            const otpSection = document.getElementById('emailOtpSection');
            btn.textContent = otpSection && otpSection.style.display !== 'none' ? 'Resend OTP' : 'Get OTP';
        });
    }

    // Get Mobile OTP
    function getMobileOtp() {
        const mobile = document.getElementById('mobile').value;
        
        if (!mobile) {
            alert('Please enter your mobile number first.');
            return;
        }

        if (mobile.length !== 10) {
            alert('Please enter a valid 10-digit mobile number.');
            return;
        }

        const btn = document.getElementById('getMobileOtpBtn');
        btn.disabled = true;
        btn.textContent = 'Sending...';

        fetch('{{ route("register.send.mobile.otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ mobile: mobile })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('mobileOtpSection').style.display = 'block';
                document.getElementById('mobileOtpStatus').textContent = 'OTP sent to your mobile. Please check and enter it.';
                document.getElementById('mobileOtpStatus').className = 'form-text text-success';
                if (data.otp) {
                    document.getElementById('mobileOtpStatus').textContent += ' (Dev: ' + data.otp + ')';
                }
                btn.disabled = false;
                btn.textContent = 'Resend OTP';
            } else {
                alert(data.message || 'Failed to send OTP. Please try again.');
                btn.disabled = false;
                const otpSection = document.getElementById('mobileOtpSection');
                btn.textContent = otpSection && otpSection.style.display !== 'none' ? 'Resend OTP' : 'Get OTP';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            btn.disabled = false;
            const otpSection = document.getElementById('mobileOtpSection');
            btn.textContent = otpSection && otpSection.style.display !== 'none' ? 'Resend OTP' : 'Get OTP';
        });
    }

    // Verify Email OTP
    function verifyEmailOtp() {
        const email = document.getElementById('email').value;
        const otp = document.getElementById('email_otp').value;

        if (!otp || otp.length !== 6) {
            alert('Please enter a valid 6-digit OTP.');
            return;
        }

        fetch('{{ route("register.verify.email.otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ email: email, otp: otp })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                emailVerified = true;
                document.getElementById('email_verified').value = '1';
                const emailInput = document.getElementById('email');
                emailInput.readOnly = true;
                emailInput.style.backgroundColor = '#d4edda';
                emailInput.style.borderColor = '#28a745';
                document.getElementById('getEmailOtpBtn').disabled = true;
                document.getElementById('getEmailOtpBtn').textContent = 'Verified';
                document.getElementById('getEmailOtpBtn').classList.remove('btn-outline-primary');
                document.getElementById('getEmailOtpBtn').classList.add('btn-success');
                document.getElementById('emailOtpSection').style.display = 'none';
                const statusDiv = document.getElementById('emailVerificationStatus');
                statusDiv.style.display = 'block';
                statusDiv.innerHTML = '<small class="text-success"><strong>✓ Email verified successfully!</strong></small>';
                checkAllValidations();
            } else {
                document.getElementById('emailOtpStatus').textContent = data.message || 'Invalid OTP. Please try again.';
                document.getElementById('emailOtpStatus').className = 'form-text';
                document.getElementById('emailOtpStatus').style.color = '#4169E1';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }

    // Verify Mobile OTP
    function verifyMobileOtp() {
        const mobile = document.getElementById('mobile').value;
        const otp = document.getElementById('mobile_otp').value;

        if (!otp || otp.length !== 6) {
            alert('Please enter a valid 6-digit OTP.');
            return;
        }

        fetch('{{ route("register.verify.mobile.otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ mobile: mobile, otp: otp })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mobileVerified = true;
                document.getElementById('mobile_verified').value = '1';
                const mobileInput = document.getElementById('mobile');
                mobileInput.readOnly = true;
                mobileInput.style.backgroundColor = '#d4edda';
                mobileInput.style.borderColor = '#28a745';
                document.getElementById('getMobileOtpBtn').disabled = true;
                document.getElementById('getMobileOtpBtn').textContent = 'Verified';
                document.getElementById('getMobileOtpBtn').classList.remove('btn-outline-primary');
                document.getElementById('getMobileOtpBtn').classList.add('btn-success');
                document.getElementById('mobileOtpSection').style.display = 'none';
                const statusDiv = document.getElementById('mobileVerificationStatus');
                statusDiv.style.display = 'block';
                statusDiv.innerHTML = '<small class="text-success"><strong>✓ Mobile verified successfully!</strong></small>';
                checkAllValidations();
            } else {
                document.getElementById('mobileOtpStatus').textContent = data.message || 'Invalid OTP. Please try again.';
                document.getElementById('mobileOtpStatus').className = 'form-text';
                document.getElementById('mobileOtpStatus').style.color = '#4169E1';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }

    // Check all validations before enabling submit
    function checkAllValidations() {
        const panInput = document.getElementById('pancardno');
        const fullnameInput = document.getElementById('fullname');
        const emailInput = document.getElementById('email');
        const mobileInput = document.getElementById('mobile');
        const dobInput = document.getElementById('dateofbirth');
        const declarationCheckbox = document.getElementById('declaration');
        
        // Validate PAN format
        const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
        const panValid = panVerified && panRegex.test(panInput.value);
        
        // Validate fullname
        const nameRegex = /^[a-zA-Z\s'-]+$/;
        const nameValid = fullnameInput.value.trim().length > 0 && nameRegex.test(fullnameInput.value);
        
        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const emailValid = emailVerified && emailRegex.test(emailInput.value);
        
        // Validate mobile
        const mobileRegex = /^[0-9]{10}$/;
        const mobileValid = mobileVerified && mobileRegex.test(mobileInput.value);
        
        // Validate date of birth
        const dobValid = dobInput.value && new Date(dobInput.value) < new Date();
        
        // Validate declaration checkbox
        const declarationValid = declarationCheckbox && declarationCheckbox.checked;
        
        // Check all validations
        const allValid = panValid && nameValid && emailValid && mobileValid && dobValid && declarationValid;
        
        if (allValid) {
            document.getElementById('submitBtn').disabled = false;
            document.getElementById('verificationWarning').style.display = 'none';
        } else {
            document.getElementById('submitBtn').disabled = true;
            let warningMsg = '<b>Please complete all required fields:</b>';
            if (!panValid) warningMsg += '<br>- Verify PAN';
            if (!nameValid) warningMsg += '<br>- Enter valid Full Name / Entity Name';
            if (!emailValid) warningMsg += '<br>- Verify Email';
            if (!mobileValid) warningMsg += '<br>- Verify Mobile';
            if (!dobValid) warningMsg += '<br>- Enter Date of Birth / Date of Incorporation';
            if (!declarationValid) warningMsg += '<br>- Accept the declaration and authorization';
            document.getElementById('verificationWarning').innerHTML = warningMsg;
            document.getElementById('verificationWarning').style.display = 'hide';
        }
    }

    // Check verification status (legacy function for compatibility)
    function checkVerificationStatus() {
        checkAllValidations();
    }
    
    // Add real-time validation for other fields
    document.getElementById('fullname').addEventListener('input', checkAllValidations);
    document.getElementById('dateofbirth').addEventListener('change', checkAllValidations);
    
    // Add event listener for declaration checkbox
    const declarationCheckbox = document.getElementById('declaration');
    if (declarationCheckbox) {
        declarationCheckbox.addEventListener('change', checkAllValidations);
    }

    // Prevent form submission if not all validations pass
    document.getElementById('registrationForm').addEventListener('submit', function(e) {
        const declarationCheckbox = document.getElementById('declaration');
        if (!panVerified || !emailVerified || !mobileVerified || !declarationCheckbox.checked) {
            e.preventDefault();
            alert('Please complete all required fields, verifications, and accept the declaration before submitting.');
            checkAllValidations();
            return false;
        }
    });
    
    // Initial validation check
    checkAllValidations();
</script>
@endpush
@endsection

