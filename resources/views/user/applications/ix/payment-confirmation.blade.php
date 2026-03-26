@php
    $isLoggedIn = session()->has('user_id');
    $showLoginLink = $showLoginLink ?? false;
@endphp

@if($isLoggedIn)
    @extends('user.layout')
@else
    @extends('layouts.app')
@endif

@section('title', 'Payment Confirmation')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    @if(isset($infoMessage) && $infoMessage)
                        <div class="mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="#3b82f6" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                            </svg>
                        </div>
                        <h2 class="mb-3" style="color:#1f2937;">Payment Processing</h2>
                        <div class="alert alert-info">
                            <p class="mb-0">{{ $infoMessage }}</p>
                        </div>
                    @elseif(isset($paymentTransaction) && $paymentTransaction)
                        @if(isset($pollingEnabled) && $pollingEnabled && $paymentTransaction->payment_status === 'pending')
                            <div class="mb-4" id="loadingIcon">
                                <div class="spinner-border text-primary" role="status" style="width: 80px; height: 80px;">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            <div id="successIcon" style="display: none;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="#10b981" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 4.384 6.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                                </svg>
                            </div>
                            <h2 class="mb-3" style="color:#1f2937;" id="statusTitle">Verifying Payment...</h2>
                            <p class="text-muted mb-4" id="statusMessage">Please wait while we verify your payment status. This may take a few moments.</p>
                        @else
                            <div class="mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="#10b981" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 4.384 6.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                                </svg>
                            </div>
                            <h2 class="mb-3" style="color:#1f2937;">Payment Successful!</h2>
                            <p class="text-muted mb-4">Your payment has been processed successfully.</p>
                        @endif

                        <div class="card border mb-4" id="paymentDetailsCard" style="{{ (isset($pollingEnabled) && $pollingEnabled && $paymentTransaction->payment_status === 'pending') ? 'display: none;' : '' }}">
                            <div class="card-body">
                                <h5 class="mb-3" style="color:#1e40af;">Payment Details</h5>
                                <dl class="row mb-0 text-start">
                                    <dt class="col-sm-5">Transaction ID:</dt>
                                    <dd class="col-sm-7" id="transactionId">{{ $paymentTransaction->transaction_id }}</dd>

                                    <dt class="col-sm-5">Payment ID:</dt>
                                    <dd class="col-sm-7" id="paymentId">{{ $paymentTransaction->payment_id ?? '—' }}</dd>

                                    <dt class="col-sm-5">Amount Paid:</dt>
                                    <dd class="col-sm-7">₹{{ number_format($paymentTransaction->amount, 2) }}</dd>

                                    <dt class="col-sm-5">Payment Status:</dt>
                                    <dd class="col-sm-7" id="paymentStatus">
                                        @if($paymentTransaction->payment_status === 'success')
                                            <span class="badge bg-success">Success</span>
                                        @elseif($paymentTransaction->payment_status === 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @else
                                            <span class="badge bg-danger">Failed</span>
                                        @endif
                                    </dd>

                                    @if(isset($application) && $application)
                                    <dt class="col-sm-5">Application ID:</dt>
                                    <dd class="col-sm-7" id="applicationId">{{ $application->application_id }}</dd>
                                    @endif

                                    <dt class="col-sm-5">Payment Date:</dt>
                                    <dd class="col-sm-7">{{ $paymentTransaction->created_at->format('d M Y, h:i A') }}</dd>
                                </dl>
                            </div>
                        </div>

                        <div id="applicationStatusAlert" style="{{ (isset($pollingEnabled) && $pollingEnabled && $paymentTransaction->payment_status === 'pending') ? 'display: none;' : '' }}">
                            @if(isset($application) && $application)
                            <div class="alert alert-info">
                                <p class="mb-0">
                                    <strong>Application Status:</strong> Your IRINN application has been submitted successfully and is now under review.
                                </p>
                            </div>
                            @endif
                        </div>
                    @else
                        <div class="mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="#3b82f6" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                            </svg>
                        </div>
                        <h2 class="mb-3" style="color:#1f2937;">Payment Information</h2>
                        <div class="alert alert-info">
                            <p class="mb-0">Your payment is being processed. Please login to check your application status.</p>
                        </div>
                    @endif

                    <div class="d-flex gap-2 justify-content-center mt-4" id="actionButtons" style="{{ (isset($pollingEnabled) && $pollingEnabled && isset($paymentTransaction) && $paymentTransaction->payment_status === 'pending') ? 'display: none;' : '' }}">
                        @if($isLoggedIn)
                            <a href="{{ route('user.applications.index') }}" class="btn btn-primary">View Applications</a>
                            @if(isset($application) && $application)
                            <a href="{{ route('user.applications.ix.download-application-pdf', $application->id) }}" class="btn btn-outline-secondary">Download Application PDF</a>
                            @endif
                        @elseif($showLoginLink)
                            <a href="{{ route('login.index') }}" class="btn btn-primary">Login to View Applications</a>
                            <a href="{{ route('welcome') }}" class="btn btn-outline-secondary">Back to Home</a>
                        @else
                            <a href="{{ route('login.index') }}" class="btn btn-primary">Login</a>
                            <a href="{{ route('welcome') }}" class="btn btn-outline-secondary">Back to Home</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(isset($pollingEnabled) && $pollingEnabled && isset($transactionId) && $paymentTransaction && $paymentTransaction->payment_status === 'pending')
@push('scripts')
<script>
(function() {
    const transactionId = {{ $transactionId }};
    const statusUrl = '{{ route("user.applications.ix.payment-status", ":id") }}'.replace(':id', transactionId);
    let pollCount = 0;
    const maxPolls = 60; // Poll for up to 5 minutes (60 * 5 seconds)
    let pollInterval;
    
    function checkPaymentStatus() {
        pollCount++;
        
        if (pollCount > maxPolls) {
            clearInterval(pollInterval);
            document.getElementById('statusTitle').textContent = 'Payment Verification Timeout';
            document.getElementById('statusMessage').textContent = 'We are still verifying your payment. Please check back in a few minutes or contact support.';
            return;
        }
        
        fetch(statusUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.transaction) {
                    const status = data.transaction.payment_status;
                    
                    if (status === 'success') {
                        // Payment successful - update UI
                        clearInterval(pollInterval);
                        
                        // Update UI elements
                        document.getElementById('loadingIcon').style.display = 'none';
                        document.getElementById('successIcon').style.display = 'block';
                        document.getElementById('statusTitle').textContent = 'Payment Successful!';
                        document.getElementById('statusMessage').textContent = 'Your payment has been processed successfully.';
                        document.getElementById('paymentDetailsCard').style.display = 'block';
                        document.getElementById('applicationStatusAlert').style.display = 'block';
                        
                        // Update payment details
                        if (data.transaction.payment_id) {
                            document.getElementById('paymentId').textContent = data.transaction.payment_id;
                        }
                        document.getElementById('paymentStatus').innerHTML = '<span class="badge bg-success">Success</span>';
                        
                        if (data.application && data.application.application_id) {
                            document.getElementById('applicationId').textContent = data.application.application_id;
                        }
                        
                        // Show action buttons
                        const actionButtons = document.getElementById('actionButtons');
                        if (actionButtons) {
                            actionButtons.style.display = 'flex';
                        }
                    } else if (status === 'failed') {
                        // Payment failed
                        clearInterval(pollInterval);
                        document.getElementById('loadingIcon').style.display = 'none';
                        document.getElementById('statusTitle').textContent = 'Payment Failed';
                        document.getElementById('statusMessage').textContent = 'Your payment could not be processed. Please try again.';
                        document.getElementById('paymentStatus').innerHTML = '<span class="badge bg-danger">Failed</span>';
                    }
                    // If still pending, continue polling
                }
            })
            .catch(error => {
                console.error('Error checking payment status:', error);
                // Continue polling on error
            });
    }
    
    // Start polling immediately, then every 5 seconds
    checkPaymentStatus();
    pollInterval = setInterval(checkPaymentStatus, 5000);
})();
</script>
@endpush
@endif
@endsection

