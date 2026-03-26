@extends('login.layout')

@section('title', 'Verify OTP')

@section('content')
    <div class="auth-row">
        <div class="col-md-6 col-lg-6 left-section">
            <div class="left-section-logo">
                @include('partials.logo')
            </div>
            <img src="{{ asset('images/IRINN.png') }}" alt="IRINN illustration" class="img-fluid d-none d-md-block" />
        </div>
        <div class="col-md-6 col-lg-5 col-xl-4 right-section">
        <div class="card before-login w-100">
            <div class="card-header text-white">
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
                <p>Please enter the OTP sent to your registered email to complete login.</p>
                
                <div class="alert alert-info">
                    <strong>Email:</strong> {{ $email }}
                </div>

                <form method="POST" action="{{ route('login.verify.otp') }}" id="verifyOtpForm">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="otp" class="form-label">Enter OTP  <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('otp') is-invalid @enderror" 
                               id="otp" 
                               name="otp" 
                               value="{{ old('otp') }}"
                               placeholder="Enter 6-digit OTP "
                               maxlength="6"
                               required
                               autofocus>
                        <small class="form-text text-muted">Enter the OTP sent to your email</small>
                        @error('otp')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3 d-flex gap-2">
                        <button type="button" class="btn btn-secondary flex-fill" onclick="resendOtp()">Resend OTP</button>
                        <button type="submit" class="btn btn-primary flex-fill">Verify & Continue</button>
                    </div>
                </form>

                <form id="resendOtpForm" method="POST" action="{{ route('login.resend.otp') }}" style="display: none;">
                    @csrf
                </form>
            </div>
            <div class="text-center mb-3">
                <a href="{{ route('login.index') }}" class="text-decoration-none text-blue">Back to Login</a>
            </div>
        </div>
    </div>

@push('scripts')
<script>
    // OTP input validation - only allow digits
    document.getElementById('otp').addEventListener('input', function(e) {
        let value = e.target.value;
        value = value.replace(/\D/g, '');
        e.target.value = value;
    });

    function resendOtp() {
        if (confirm('Are you sure you want to resend OTP?')) {
            document.getElementById('resendOtpForm').submit();
        }
    }
</script>
@endpush
@endsection

