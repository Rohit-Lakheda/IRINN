@extends('user.layout')

@section('title', 'Request Profile Update')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow border-c-blue">
            <div class="card-header theme-bg-blue">
                <h4 class="mb-0">Request Profile Update</h4>
            </div>
            <div class="card-body">
                <p class="alert alert-info small mb-4">
                    <strong>Note:</strong> Only your <strong>registered email</strong> and <strong>registered mobile number</strong> can be updated (after admin approval, with OTP verification).
                </p>
                <p class="lead fs-6">
                    Please describe the changes you want to make to your profile. Once submitted, an admin will review your request and approve it if appropriate.
                </p>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('user.profile-update.store') }}">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="requested_changes" class="form-label">Describe the changes you want to make <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('requested_changes') is-invalid @enderror" 
                                  id="requested_changes" 
                                  name="requested_changes" 
                                  rows="6" 
                                  placeholder="Example: I want to update my mobile number from 9876543210 to 9876543211 because I changed my number..."
                                  required>{{ old('requested_changes') }}</textarea>
                        <small class="form-text text-muted">Please provide detailed information about what you want to change and why.</small>
                        @error('requested_changes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                        <a href="{{ route('user.profile') }}" class="btn bg-danger">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

