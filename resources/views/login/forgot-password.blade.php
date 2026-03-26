@extends('login.layout')

@section('title', 'Forgot Password')

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
            <div class="card-header">
                <h4 class="mb-0">Forgot Password for IRINN Portal</h4>
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
                        @if (session('email_not_registered'))
                            <div class="mt-2">
                                <a href="{{ route('register.index') }}" class="alert-link">Click here to register</a>
                            </div>
                        @endif
                    </div>
                @endif

                <p class="lead fs-6">Enter your registered email address and we'll send you instructions to Forgot your password.</p>
                <form method="POST" action="{{ route('login.forgot-password.submit') }}">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email') }}"
                               placeholder="Enter your registered email"
                               required
                               autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3 text-center">
                        <button type="submit" class="btn btn-primary">Send Reset Link</button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <p><a href="{{ route('login.index') }}" class="text-blue">Back to Sign In</a></p>
                        <p>Don't have an account? <a href="{{ route('register.index') }}" class="text-blue">Register here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

