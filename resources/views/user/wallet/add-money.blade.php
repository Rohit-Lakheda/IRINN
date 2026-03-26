@extends('user.layout')

@section('title', 'Add Money to Wallet')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="mb-1">Add Money to Wallet</h2>
        <p class="mb-0">Top up your wallet balance</p>
        <div class="accent-line"></div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0" style="color: #2c3e50 !important;">Top-up Wallet</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h6 class="text-muted mb-2">Current Advance Amount</h6>
                        <h2 class="mb-0 fw-bold text-success" style="color: #198754 !important;">
                            ₹{{ number_format($wallet->balance, 2) }}
                        </h2>
                    </div>

                    @if(isset($totalBillingCycleAmount) && $totalBillingCycleAmount > 0)
                        @if($currentBalance < $totalBillingCycleAmount)
                            @php
                                $shortfall = $totalBillingCycleAmount - $currentBalance;
                            @endphp
                            <div class="alert alert-warning mb-3">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Low Balance Alert:</strong>
                                <br>Your current balance (₹{{ number_format($currentBalance, 2) }}) is less than your total billing cycle amount (₹{{ number_format($totalBillingCycleAmount, 2) }}).
                                <br><strong>Shortfall: ₹{{ number_format($shortfall, 2) }}</strong>
                                <br><small class="text-muted">You need to add at least ₹{{ number_format($minimumAmountToAdd, 2) }} (equal to or more) to meet the requirement.</small>
                            </div>
                        @else
                            <div class="alert alert-success mb-3">
                                <i class="bi bi-check-circle me-2"></i>
                                <strong>Balance Sufficient:</strong>
                                <br>Your current balance (₹{{ number_format($currentBalance, 2) }}) is sufficient for your total billing cycle amount (₹{{ number_format($totalBillingCycleAmount, 2) }}).
                                <br><small class="text-muted">You can add any amount (minimum ₹1).</small>
                            </div>
                        @endif
                    @endif

                    <form method="POST" action="{{ route('user.wallet.process-add-money') }}" class="theme-forms">
                        @csrf

                        <div class="mb-3">
                            <label for="amount" class="form-label">Top-up Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control @error('amount') is-invalid @enderror" 
                                   id="amount" 
                                   name="amount" 
                                   min="{{ isset($minimumAmountToAdd) ? $minimumAmountToAdd : 1 }}" 
                                   max="100000" 
                                   step="0.01" 
                                   value="{{ old('amount') }}" 
                                   placeholder="Enter amount"
                                   required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Minimum: ₹{{ number_format(isset($minimumAmountToAdd) ? $minimumAmountToAdd : 1, 2) }} | 
                                Maximum: ₹1,00,000
                            </small>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            You will be redirected to PayU payment gateway to complete the payment.
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-credit-card me-1"></i> Pay Now
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

