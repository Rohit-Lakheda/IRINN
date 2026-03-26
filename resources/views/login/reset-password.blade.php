@extends('login.layout')

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
                    <h4 class="mb-0">Reset Password for IRINN Portal</h4>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <p class="lead">Enter your new password</p>
                    <p class="text-muted">Please enter a strong password with at least 8 characters.</p>
                    
                    <form method="POST" action="{{ route('login.reset-password.submit') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="email" value="{{ $email }}">
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your new password"
                                   required
                                   autofocus>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" 
                                   class="form-control @error('password_confirmation') is-invalid @enderror" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   placeholder="Confirm your new password"
                                   required>
                            @error('password_confirmation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <p><a href="{{ route('login.index') }}" class="text-blue">Back to Sign In</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

