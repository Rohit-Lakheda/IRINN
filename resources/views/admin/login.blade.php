@extends('login.layout')

@section('title', 'Admin Login')

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
                <h4 class="mb-0">Admin Login</h4>
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

                <p class="lead fs-6">Please enter your credentials to access the Admin&nbsp;panel.</p>
                <form method="POST" action="{{ route('admin.login.submit') }}" id="adminLoginForm" class="theme-forms">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email"
                               class="form-control @error('email') is-invalid @enderror"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               placeholder="Enter your admin email"
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
                                    class="btn btn-outline-secondary btn-sm"
                                    id="togglePassword"
                                    onclick="togglePasswordVisibility()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" id="eyeIcon">
                                    <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                    <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" id="eyeSlashIcon" style="display: none;">
                                    <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
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

                    <div class="mb-3 mt-4 text-center">
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </div>

                    <div class="text-center mt-3">
                        <p><a href="{{ url('/') }}" class="text-blue fw-medium text-decoration-underline">Back to Home</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
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
@endpush
@endsection

