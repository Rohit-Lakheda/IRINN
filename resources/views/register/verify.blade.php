@extends('register.layout')

@section('title', 'Verify OTP')

@section('content')
<div class="auth-row">
        <div class="col-md-5 col-lg-4 left-section">
            <div class="left-section-logo">
                @include('partials.logo')
            </div>
            <img src="{{ asset('images/IRINN.png') }}" alt="IRINN illustration" class="img-fluid d-none d-md-block" />
        </div>
        <div class="col-md-6 col-lg-5 col-xl-4 right-section">
        <div class="card shadow w-100">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Verify OTP</h4>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
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

                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <p class="lead">OTP Verification Required</p>
                <p>Please enter the OTPs sent to your email and mobile number to complete registration.</p>
                
                <div class="alert alert-info">
                    <strong>Registration ID:</strong> {{ $registration->registrationid }}<br>
                    <strong>Email:</strong> {{ $registration->email }}<br>
                    <strong>Mobile:</strong> {{ $registration->mobile }}
                </div>

                @if (config('app.debug'))
                <div class="alert alert-warning">
                    <strong>Development Mode:</strong> Check the logs for OTPs<br>
                    Email OTP: <strong>{{ $registration->email_otp }}</strong><br>
                    Mobile OTP: <strong>{{ $registration->mobile_otp }}</strong>
                </div>
                @endif

                <form method="POST" action="{{ route('register.verify.otp') }}" id="verifyOtpForm">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="email_otp" class="form-label">Email OTP <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('email_otp') is-invalid @enderror" 
                               id="email_otp" 
                               name="email_otp" 
                               value="{{ old('email_otp') }}"
                               placeholder="Enter 6-digit OTP"
                               maxlength="6"
                               required>
                        <small class="form-text text-muted">Check your email for the OTP</small>
                        @error('email_otp')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="mobile_otp" class="form-label">Mobile OTP <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('mobile_otp') is-invalid @enderror" 
                               id="mobile_otp" 
                               name="mobile_otp" 
                               value="{{ old('mobile_otp') }}"
                               placeholder="Enter 6-digit OTP"
                               maxlength="6"
                               required>
                        <small class="form-text text-muted">Check your mobile for the OTP</small>
                        @error('mobile_otp')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="master_otp" class="form-label">Master OTP (Optional)</label>
                        <input type="text" 
                               class="form-control @error('master_otp') is-invalid @enderror" 
                               id="master_otp" 
                               name="master_otp" 
                               value="{{ old('master_otp') }}"
                               placeholder="Enter master OTP (optional)"
                               maxlength="6">
                        <small class="form-text text-muted">If you have a master OTP, you can use it to verify both email and mobile</small>
                        @error('master_otp')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">Verify & Complete Registration</button>
                        <button type="button" class="btn btn-secondary" onclick="resendOtp()">Resend OTP</button>
                        <a href="{{ route('register.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>

                <form id="resendOtpForm" method="POST" action="{{ route('register.resend.otp') }}" style="display: none;">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // OTP input validation - only allow digits
    document.getElementById('email_otp').addEventListener('input', function(e) {
        let value = e.target.value;
        value = value.replace(/\D/g, '');
        e.target.value = value;
    });

    document.getElementById('mobile_otp').addEventListener('input', function(e) {
        let value = e.target.value;
        value = value.replace(/\D/g, '');
        e.target.value = value;
    });

    document.getElementById('master_otp').addEventListener('input', function(e) {
        let value = e.target.value;
        value = value.replace(/\D/g, '');
        e.target.value = value;
    });

    function resendOtp() {
        if (confirm('Are you sure you want to resend OTPs?')) {
            document.getElementById('resendOtpForm').submit();
        }
    }
</script>
@endpush
@endsection

