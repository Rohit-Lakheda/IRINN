@extends('user.layout')

@section('title', 'Redirecting to Payment')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h4 class="mb-3">Redirecting to Payment Gateway</h4>
                    <p class="text-muted mb-4">
                        Please wait while we securely redirect you to the PayU payment gateway to complete your payment.
                    </p>
                    <p class="text-muted">
                        If you are not redirected automatically within a few seconds, click the button below.
                    </p>
                    <form name="payu" id="payuRedirectForm" method="POST" action="{{ $paymentUrl }}">
                        @foreach($paymentForm as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <button type="submit" class="btn btn-primary mt-3">
                            Proceed to Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-submit form on page load as per PayU documentation
    // Reference: PayU Hosted Checkout API Documentation - Step 1.3
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('payuRedirectForm');
        if (form) {
            // Use setTimeout to ensure form is fully rendered
            setTimeout(function() {
                form.submit();
            }, 100);
        }
    });
    
    // Fallback: Also support onload event (as per PayU documentation example)
    window.onload = function() {
        const form = document.forms.payu || document.getElementById('payuRedirectForm');
        if (form && !form.dataset.submitted) {
            form.dataset.submitted = 'true';
            form.submit();
        }
    };
</script>
@endpush
@endsection


