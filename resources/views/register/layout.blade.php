<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Register')</title>
    
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
            --theme-primary: #6b46c1; /* Purple theme */
            --theme-primary-dark: #553c9a;
            --theme-primary-light: #8b5cf6;
            --theme-purple-soft: #ede9fe;
            --theme-purple-bg: #faf5ff;
            --black: #000000;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        .auth-page-wrapper {
            min-height: 100vh;
            max-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 1rem;
            overflow-y: auto;
        }

        .auth-main {
            width: 100%;
            max-width: 1400px;
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .auth-row {
            width: 100%;
            display: flex;
            align-items: flex-start;
            margin: 0;
        }

        .left-section {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            min-height: 100vh;
            max-height: 100vh;
            position: relative;
        }

        .left-section-logo {
            position: absolute;
            top: 0;
            left: 0;
            padding: 0.5rem 0.75rem;
            z-index: 10;
        }

        .left-section-logo .nixi-logo-link {
            display: inline-block;
        }

        .left-section-logo .nixi-logo {
            height: 35px;
            max-width: 100px;
            width: auto;
        }

        .left-section img {
            max-width: 100%;
            max-height: 100vh;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .right-section {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 1rem;
        }
        @media (max-width: 991.98px) {
            .auth-page-wrapper {
                padding: 0;
            }
            .auth-main {
                padding: 0;
            }
            .auth-row {
                flex-direction: column;
                align-items: center;
                position: relative;
            }
            .left-section {
                min-height: auto;
                max-height: 40vh;
                padding: 0.5rem;
                width: 100%;
            }
            .left-section img {
                max-height: 40vh;
            }
            .right-section {
                width: 100%;
                padding-top: 3.5rem;
            }
            .left-section-logo {
                position: fixed;
                top: 0.5rem;
                left: 0.5rem;
                padding: 0.4rem 0.6rem;
                z-index: 1000;
                background: rgba(255, 255, 255, 0.95);
                border-radius: 6px;
            }
            .left-section-logo .nixi-logo {
                height: 30px;
                max-width: 85px;
            }
        }
        @media (max-width: 575.98px) {
            .left-section {
                max-height: 30vh;
            }
            .left-section img {
                max-height: 30vh;
            }
            .left-section-logo {
                top: 0.4rem;
                left: 0.4rem;
                padding: 0.35rem 0.5rem;
            }
            .left-section-logo .nixi-logo {
                height: 28px;
                max-width: 75px;
            }
        }
        
        .consent-wrapper {
            position: relative;
        }

        .consent-wrapper label {
            position: relative;
            cursor: pointer;
            pointer-events: auto;
        }

        .consent-text {
            line-height: 1.5;
            font-size: 14px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Tooltip box */
        .tooltip {
            display: none;
            position: absolute;
            left: 0;
            top: -7px;
            margin-top: 8px;
            font-size: 12px;
            width: 100%;
            height: 77px;
            background: #fff;
            color: #000;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            overflow: scroll;
            z-index: 9999;
            opacity: 1;
        }

        /* Show on hover */
        .consent-wrapper label:hover .tooltip {
            display: block;
        }

        .text-success,
        .invalid-feedback,
        .alert-danger,
        .alert-danger ul li,
        .alert-success ul li {
            color: var(--theme-primary) !important;
        }

        .form-control.is-invalid,
        .form-check-input.is-invalid {
            border-color: var(--theme-primary) !important;
            border-bottom: 2px solid var(--theme-primary) !important;
        }
        
        .form-control:focus,
        .form-select:focus,
        textarea:focus {
            border-color: var(--theme-primary-light) !important;
            border-bottom: 2px solid var(--theme-primary-light) !important;
            box-shadow: 0 0 0 0.2rem rgba(107, 70, 193, 0.15) !important;
        }
        .form-control, .form-select {
            min-height: 36px;
            border-color: rgba(107, 70, 193, 0.3);
        }

        
        .input-group-text {
            border-color: rgba(107, 70, 193, 0.3) !important;
            border-bottom: 2px solid var(--theme-primary) !important;
            background-color: var(--theme-purple-soft);
        }
        
        .badge.bg-danger,
        .badge.bg-success,
        .badge.bg-warning,
        .badge.bg-primary {
            background-color: var(--theme-primary) !important;
            color: #ffffff !important;
            border: 1px solid var(--theme-primary-dark) !important;
        }

        .btn,
        .btn-primary,
        button.btn,
        button.btn-primary,
        input.btn,
        input.btn-primary {
            background-color: var(--theme-primary) !important;
            border-color: var(--theme-primary) !important;
            color: #ffffff !important;
            font-weight: 600;
            padding: 0.6rem 1.5rem;
            transition: all 0.2s ease;
        }

        .btn,
        .btn-primary,
        button.btn,
        button.btn-primary {
            color: #ffffff !important;
        }

        .btn *,
        .btn-primary *,
        button.btn *,
        button.btn-primary * {
            color: #ffffff !important;
        }

        .btn:hover,
        .btn:focus,
        .btn-primary:hover,
        .btn-primary:focus,
        .btn:focus-visible,
        button.btn:hover,
        button.btn-primary:hover {
            background-color: var(--theme-primary-dark) !important;
            border-color: var(--theme-primary-dark) !important;
            color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(107, 70, 193, 0.3);
        }

        .btn:hover,
        .btn-primary:hover,
        button.btn:hover,
        button.btn-primary:hover {
            color: #ffffff !important;
        }

        .btn:hover *,
        .btn:focus *,
        .btn-primary:hover *,
        .btn-primary:focus *,
        button.btn:hover *,
        button.btn-primary:hover * {
            color: #ffffff !important;
        }

        .btn-secondary {
            background-color: #6b7280 !important;
            border-color: #6b7280 !important;
            color: #ffffff !important;
        }

        .btn-secondary:hover {
            background-color: #4b5563 !important;
            border-color: #4b5563 !important;
            color: #ffffff !important;
        }

        .btn-success {
            background-color: #10b981 !important;
            border-color: #10b981 !important;
            color: #ffffff !important;
        }

        .btn-success:hover {
            background-color: #059669 !important;
            border-color: #059669 !important;
            color: #ffffff !important;
        }

        .btn-warning {
            background-color: #f59e0b !important;
            border-color: #f59e0b !important;
            color: #ffffff !important;
        }

        .btn-warning:hover {
            background-color: #d97706 !important;
            border-color: #d97706 !important;
            color: #ffffff !important;
        }

        .btn-outline-secondary,
        .btn-outline-primary {
            background-color: transparent !important;
            border-color: var(--theme-primary) !important;
            color: var(--theme-primary) !important;
        }

        .btn-outline-secondary:hover,
        .btn-outline-primary:hover {
            background-color: var(--theme-primary) !important;
            border-color: var(--theme-primary) !important;
            color: #ffffff !important;
        }

        a,
        .btn-link {
            color: var(--theme-primary) !important;
        }

        a:hover,
        .btn-link:hover {
            color: var(--theme-primary-dark) !important;
            text-decoration: underline;
        }

        .text-blue {
            color: var(--theme-primary) !important;
        }

        .before-login {
            box-shadow: 0 18px 40px rgba(107, 70, 193, 0.15);
            border-radius: 18px;
            border: 1px solid rgba(107, 70, 193, 0.2);
            background: #ffffff;
        }
        .before-login .card-header {
            text-transform: none;
            background: linear-gradient(135deg, var(--theme-primary), var(--theme-primary-dark));
            color: #ffffff !important;
        }
        .before-login .card-header h4 {
            color: #ffffff !important;
        }
        .form-label {
            color: #374151;
            font-weight: 500;
        }
        .card-body {
            color: #1f2937;
        }
        .card-body .lead {
            color: #4b5563;
        }
        .text-muted {
            color: #6b7280 !important;
        }
        .text-danger {
            color: #dc2626 !important;
        }
        .alert-danger {
            background-color: #fef2f2;
            border-color: #fecaca;
            color: #991b1b;
        }
        .alert-success {
            background-color: #f0fdf4;
            border-color: #bbf7d0;
            color: #166534;
        }
        .alert-info {
            background-color: #eff6ff;
            border-color: #bfdbfe;
            color: #1e40af;
        }
        .before-login .text-blue {
            font-weight: 600 !important;
        }
        .text-blue {
            color: var(--theme-primary) !important;
        }
        .text-gold {
            color: var(--theme-primary-light) !important;
        }
        .form-label {
            text-transform: capitalize;
        }

        .form-check-input {
            border-color: rgba(107, 70, 193, 0.5);
        }

        .form-check-input:checked {
            background-color: var(--theme-primary);
            border-color: var(--theme-primary);
        }

        .form-check-input:focus {
            border-color: var(--theme-primary-light);
            box-shadow: 0 0 0 0.2rem rgba(107, 70, 193, 0.15);
        }

        @media (max-width: 576px) {

        }
        .input-style input[type="radio"] {

        }
        .input-style input[type="checkbox"], .input-style input[type="radio"] {
            width: 20px;
            height: 20px;
            border: 1px solid var(--theme-primary);
            margin-right: 10px;
        }
        .input-style input[type="checkbox"]:focus, .input-style input[type="radio"]:focus {
            box-shadow: 0 0 0 0.2rem rgba(107, 70, 193, 0.15);
            border-color: var(--theme-primary-light);
        }
        .input-style input[type="checkbox"]:checked, .input-style input[type="radio"]:checked {
            background-color: var(--theme-primary);
            border-color: var(--theme-primary);
        }
    </style>
    
    <!-- Additional Styles -->
    @stack('styles')
</head>
<body>
    <div class="auth-page-wrapper">
        <div class="auth-main">
            @yield('content')
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Additional Scripts -->
    @stack('scripts')
</body>
</html>

