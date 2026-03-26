@extends('user.layout')

@section('title', 'Update Profile')

@section('content')
<div class="row justify-content-center update-form">
    <div class="col-md-8">
        <div class="card shadow border-c-blue" style="border-radius: 16px;">
            <div class="card-header theme-bg-blue" style="border-radius: 16px 16px 0 0;">
                <h4 class="mb-0">Update Profile</h4>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-success border-0" style="border-radius: 12px;">
                    <strong>Your profile update request has been approved!</strong><br>
                    You can now update your email and mobile number. Both will be verified through OTP.
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger border-0" style="border-radius: 12px;">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="profileUpdateForm" method="POST" action="{{ route('user.profile-update.update') }}">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $user->email) }}"
                                   required>
                            <button type="button" 
                                    class="btn theme-bg-yellow" 
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
                                       placeholder="Enter 6-digit OTP or Master OTP"
                                       maxlength="6">
                                <button type="button" 
                                        class="btn theme-bg-green text-white" 
                                        onclick="verifyEmailOtp()">
                                    Verify
                                </button>
                            </div>
                            <small class="form-text text-muted" id="emailOtpStatus"></small>
                        </div>
                        <div id="emailVerificationStatus" class="mt-2" style="display: none;"></div>
                        <input type="hidden" id="email_verified" name="email_verified" value="0">
                        @error('email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="tel" 
                                   class="form-control @error('mobile') is-invalid @enderror" 
                                   id="mobile" 
                                   name="mobile" 
                                   value="{{ old('mobile', $user->mobile) }}"
                                   placeholder="10-digit mobile number"
                                   maxlength="10"
                                   required>
                            <button type="button" 
                                    class="btn theme-bg-yellow" 
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
                                       placeholder="Enter 6-digit OTP or Master OTP"
                                       maxlength="6">
                                <button type="button" 
                                        class="btn theme-bg-green text-white" 
                                        onclick="verifyMobileOtp()">
                                    Verify
                                </button>
                            </div>
                            <small class="form-text text-muted" id="mobileOtpStatus"></small>
                        </div>
                        <div id="mobileVerificationStatus" class="mt-2" style="display: none;"></div>
                        <input type="hidden" id="mobile_verified" name="mobile_verified" value="0">
                        @error('mobile')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">PAN Card Number</label>
                        <input type="text" 
                               class="form-control" 
                               value="{{ $user->pancardno }}"
                               disabled>
                        <small class="form-text text-muted">PAN Card Number cannot be changed</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" 
                               class="form-control" 
                               value="{{ $user->fullname }}"
                               disabled>
                        <small class="form-text text-muted">Full Name cannot be changed</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" 
                               class="form-control" 
                               value="{{ $user->dateofbirth->format('Y-m-d') }}"
                               disabled>
                        <small class="form-text text-muted">Date of Birth cannot be changed</small>
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn btn-success px-4" style="border-radius: 8px; font-weight: 600;">Submit Update</button>
                        <a href="{{ route('user.profile') }}" class="btn bg-danger px-4" style="border-radius: 8px; font-weight: 600;">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let emailVerified = false;
let mobileVerified = false;

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

    fetch('{{ route("user.profile-update.send-email-otp") }}', {
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
        } else {
            alert(data.message || 'Failed to send OTP. Please try again.');
            btn.disabled = false;
            btn.textContent = 'Get OTP';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        btn.disabled = false;
        btn.textContent = 'Get OTP';
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

    fetch('{{ route("user.profile-update.send-mobile-otp") }}', {
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
        } else {
            alert(data.message || 'Failed to send OTP. Please try again.');
            btn.disabled = false;
            btn.textContent = 'Get OTP';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        btn.disabled = false;
        btn.textContent = 'Get OTP';
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

    fetch('{{ route("user.profile-update.verify-email-otp") }}', {
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
        } else {
            document.getElementById('emailOtpStatus').textContent = data.message || 'Invalid OTP. Please try again.';
            document.getElementById('emailOtpStatus').className = 'form-text text-danger';
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

    fetch('{{ route("user.profile-update.verify-mobile-otp") }}', {
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
        } else {
            document.getElementById('mobileOtpStatus').textContent = data.message || 'Invalid OTP. Please try again.';
            document.getElementById('mobileOtpStatus').className = 'form-text text-danger';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

// Form submission validation
document.getElementById('profileUpdateForm').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value;
    const mobile = document.getElementById('mobile').value;
    
    // Check if email and mobile are verified
    const emailVerifiedSession = sessionStorage.getItem('email_verified_' + btoa(email)) || document.getElementById('email_verified').value === '1';
    const mobileVerifiedSession = sessionStorage.getItem('mobile_verified_' + btoa(mobile)) || document.getElementById('mobile_verified').value === '1';
    
    if (!emailVerified && !emailVerifiedSession) {
        e.preventDefault();
        alert('Please verify your email address before submitting.');
        return false;
    }
    
    if (!mobileVerified && !mobileVerifiedSession) {
        e.preventDefault();
        alert('Please verify your mobile number before submitting.');
        return false;
    }
});
</script>
@endpush
@endsection
