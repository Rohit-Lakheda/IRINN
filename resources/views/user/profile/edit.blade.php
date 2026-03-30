@extends('user.layout')

@section('title', 'Update email & mobile')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <h2 class="mb-3" style="color: #2c3e50; font-weight: 600;">Update registered contact</h2>

            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('user.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('user.profile') }}">Profile</a></li>
                    <li class="breadcrumb-item active">Update email & mobile</li>
                </ol>
            </nav>

            <div class="alert alert-info border-0 mb-4" style="border-radius: 12px;">
                Only your <strong>registered email</strong> and <strong>registered mobile number</strong> can be changed here.
                GST and billing details shown on your profile come from your IRINN application; use the application workflow or support for those changes.
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Account details (read-only)</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Registration ID</span>
                            <span class="fw-medium">{{ $user->registrationid }}</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Full name</span>
                            <span class="fw-medium">{{ $user->fullname }}</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block">PAN</span>
                            <span class="fw-medium">{{ $user->pancardno }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($irinnApplication)
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                    <div class="card-header bg-secondary text-white" style="border-radius: 16px 16px 0 0;">
                        <h5 class="mb-0" style="font-weight: 600;">GST / billing on IRINN application (read-only)</h5>
                    </div>
                    <div class="card-body p-4 small">
                        <p class="text-muted mb-2">Application ID: <strong>{{ $irinnApplication->application_id }}</strong></p>
                        <table class="table table-sm table-borderless mb-0">
                            <tr><th class="w-25 text-muted">GST registered</th><td>{{ $irinnApplication->irinn_has_gst_number ? 'Yes' : 'No' }}</td></tr>
                            <tr><th class="text-muted">Billing GSTIN</th><td>{{ $irinnApplication->irinn_billing_gstin ? strtoupper($irinnApplication->irinn_billing_gstin) : '—' }}</td></tr>
                            <tr><th class="text-muted">Legal name</th><td>{{ $irinnApplication->irinn_billing_legal_name ?: '—' }}</td></tr>
                            <tr><th class="text-muted">Billing PAN</th><td>{{ $irinnApplication->irinn_billing_pan ? strtoupper($irinnApplication->irinn_billing_pan) : '—' }}</td></tr>
                            <tr><th class="text-muted">Billing address</th><td>{{ trim(implode(', ', array_filter([$irinnApplication->irinn_billing_address, $irinnApplication->irinn_billing_postcode]))) ?: '—' }}</td></tr>
                        </table>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('user.profile.update') }}" class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                @csrf
                <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Registered email & mobile</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}" required autocomplete="email">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-0">
                        <label for="mobile" class="form-label">Mobile number <span class="text-danger">*</span></label>
                        <input type="text" name="mobile" id="mobile" class="form-control @error('mobile') is-invalid @enderror"
                               value="{{ old('mobile', $user->mobile) }}" required autocomplete="tel" maxlength="15">
                        @error('mobile')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between align-items-center py-3" style="border-radius: 0 0 16px 16px;">
                    <a href="{{ route('user.profile') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
