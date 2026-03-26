<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Super Admin Login | {{ config('app.name', 'Laravel') }}</title>
    
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
            --theme-primary: #FFD700; /* Yellow */
            --theme-primary-dark: #E6B800;
            --theme-primary-light: #FFE066;
            --theme-primary-y: #FFD700;
            --theme-primary-dark-y: #E6B800;
            --theme-primary-light-y: #FFE066;
        }
        
        html, body {
            background-image: url(../images/Nixi-right.png);
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
        .form-select:focus,
        textarea:focus {
            border-color: #E89C48 !important;
            border-bottom: 2px solid #E89C48 !important;
            box-shadow: none;
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
                        <h4 class="mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16" class="me-2">
                                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                            </svg>
                            Super Admin Login
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

                        <p class="lead fs-6">Please enter your credentials to access the Super Admin&nbsp;panel.</p>
                        
                        <form method="POST" action="{{ route('superadmin.login.submit') }}" id="superadminLoginForm" class="theme-forms">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}"
                                       placeholder="Enter your Super Admin email"
                                       required
                                       autofocus>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Enter your password"
                                           required>
                                    <button type="button" 
                                            class="btn btn-outline-secondary" 
                                            id="togglePassword"
                                            onclick="togglePasswordVisibility()">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" id="eyeIcon">
                                            <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                            <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                                        </svg>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" id="eyeSlashIcon" style="display: none;">
                                            <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5a7.028 7.028 0 0 1-2.79-.588l-.77.772A7.028 7.028 0 0 0 8 13.5c2.12 0 3.879-1.168 5.168-2.457A13.134 13.134 0 0 0 14.828 8z"/>
                                            <path d="M11.297 9.176a3.5 3.5 0 0 1-4.474-4.474l4.474 4.474z"/>
                                            <path d="M4.703 6.824a3.5 3.5 0 0 1 4.474 4.474L4.703 6.824z"/>
                                            <path d="M.5 1.5a.5.5 0 0 1 .5-.5h14a.5.5 0 0 1 0 1H1a.5.5 0 0 1-.5-.5z"/>
                                        </svg>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" class="btn btn-danger w-100">Login</button>
                            </div>
                            
                            <div class="text-center mt-3">
                                <p><a href="{{ url('/') }}" class="text-blue text-decoration-none fw-semibold">Back to Home</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Prevent back button after logout -->
    <script>
        // Prevent back button navigation
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
        
        // Clear cache on page load
        if (performance.navigation.type === 1) {
            // Page was reloaded
            window.location.replace(window.location.href);
        }
        
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeSlashIcon = document.getElementById('eyeSlashIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.style.display = 'none';
                eyeSlashIcon.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                eyeIcon.style.display = 'block';
                eyeSlashIcon.style.display = 'none';
            }
        }
    </script>
</body>
</html>

