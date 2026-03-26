<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Super Admin OTP Verification | {{ config('app.name', 'Laravel') }}</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}?v={{ time() }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ time() }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}?v={{ time() }}">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    
    <!-- Custom Theme CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <style>
        :root {
            /*--theme-primary: #10b981;*/ /* Green for superadmin */
            /*--theme-primary-dark: #059669;*/
            --black: #000000;

            --theme-primary: #FFD700; /* Yellow */
            --theme-primary-dark: #E6B800;
            --theme-primary-light: #FFE066;
        }
        
        html, body {
            background-image: url(../../images/Nixi-right.png);
            background-repeat: no-repeat;
            background-size: cover;
            background-position: top;
            background-attachment: fixed;
            height: 100%;
        }

        .left-section img {
            width: 64%;    
        }
        @media (min-width: 1400px) {
            .left-section img {
                width: 68%;
            }   
        }
        @media (max-width: 1199.98px) {
           .left-section img {
                width: 78%;    
            }
        }
        @media (max-width: 991.98px) {
           .left-section img {
                width: 90%;
            }
        }
        @media (max-width: 767.98px) {
            .left-section img {
                width: 70%;    
            }
        }
        .card-header.bg-primary {
            background-color: var(--theme-primary) !important;
            color: var(--white) !important;
            border-bottom: 2px solid var(--black) !important;
        }

        .card-header.bg-primary * {
            color: var(--white) !important;
        }

        .btn-primary {
            background-color: var(--theme-primary) !important;
            border-color: var(--theme-primary) !important;
            border-bottom: 2px solid var(--black) !important;
            color: var(--white) !important;
        }

        .btn-primary:hover {
            background-color: var(--theme-primary-dark) !important;
            border-color: var(--theme-primary-dark) !important;
            color: var(--white) !important;
        }

        .btn,
        .btn-primary,
        .btn-warning,
        .btn-danger,
        .btn-success,
        .btn-outline-secondary,
        .btn-outline-primary {
            background-color: var(--theme-primary) !important;
            border-color: var(--theme-primary) !important;
            border-bottom: 2px solid var(--black) !important;
            color: #000 !important;
        }

        .btn:hover,
        .btn:focus,
        .btn-primary:hover,
        .btn-warning:hover,
        .btn-danger:hover,
        .btn-success:hover,
        .btn-outline-secondary:hover,
        .btn-outline-primary:hover,
        .btn:focus-visible {
            background-color: var(--theme-primary-dark) !important;
            border-color: var(--theme-primary-dark) !important;
            border-bottom: 2px solid var(--black) !important;
            color: #000 !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--black) !important;
            border-bottom: 2px solid var(--theme-primary) !important;
            box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.2) !important;
        }

        .form-control:focus,
        .form-select:focus,
        textarea:focus {
            border-color: #E89C48 !important;
            border-bottom: 2px solid #E89C48 !important;
            /*box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.2) !important;*/
            box-shadow: none !important;
        }
        .form-control, .form-select {
            min-height: 45px;
        }
        .input-group-text {
            border-color: var(--black) !important;
            border-bottom: 2px solid var(--theme-primary) !important;
        }
        .before-login {
            box-shadow: 0 10px 30px rgba(43, 47, 108, 0.15);
        }
        .before-login .card-header {
            text-transform: capitalize;
            background-color: #282568;
        }
        .before-login .text-blue {
            font-weight: 600 !important;
        }
        .text-blue {
            color: var(--theme-blue) !important;
        }
        .text-gold {
            color: #E89C48 !important;
        }
        .form-label {
            text-transform: capitalize;
        }
    </style>
</head>
<body>
    <div class="navbar-wrapper">
        <div class="container">
            <div class="nixi-logo-fixed">
                @include('partials.logo')
            </div>
        </div>
    </div>
    <div class="container mt-4">
        <div class="row justify-content-center align-items-center mt-md-5">
            <div class="col-md-6 col-lg-6 left-section">
                <img src="{{ asset('images/Nixi-left-img.png') }}" alt="Sign In" class="img-fluid d-none d-md-block" />
            </div>
            <div class="col-md-6 col-lg-5 col-xl-4">
                <div class="card before-login">
                    <div class="card-header text-white">
                        <h4 class="mb-0 text-capitalize">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16" class="me-2">
                                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                            </svg>
                            Super Admin OTP Verification
                        </h4>
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
                        
                        <form method="POST" action="{{ route('superadmin.login.verify.otp') }}" id="otpForm">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="otp" class="form-label">Enter OTP <span class="text-danger">*</span></label>
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

                            <div class="mb-3">
                                <label for="master_otp" class="form-label">Or Use Master OTP (Optional)</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="master_otp" 
                                       name="master_otp" 
                                       placeholder="Enter master OTP"
                                       maxlength="6">
                                <small class="form-text text-muted">You can use the master OTP instead</small>
                            </div>
                            

                            <div class="mb-3 d-flex gap-2">
                                <form method="POST" action="{{ route('superadmin.login.resend-otp') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary flex-fill">Resend OTP</button>
                                </form>
                                <button type="submit" class="btn btn-secondary flex-fill">Verify & Continue</button>
                            </div>

                            <!-- <div class="mb-3">
                                <button type="submit" class="btn btn-danger w-100">Verify OTP</button>
                            </div> -->

                            <div class="text-center">
                                {{--<form method="POST" action="{{ route('superadmin.login.resend-otp') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-link">Resend OTP</button>
                                </form>
                                <span class="mx-2">|</span>--}}
                                <a href="{{ route('superadmin.login') }}" class="text-blue">Back to Login</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- OTP Input Validation -->
    <script>
        document.getElementById('otp').addEventListener('input', function(e) {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        document.getElementById('master_otp').addEventListener('input', function(e) {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Prevent back button navigation
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
    </script>
</body>
</html>

