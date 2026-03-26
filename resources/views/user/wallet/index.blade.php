@extends('user.layout')

@section('title', 'My Wallet')

@section('content')
<style>
.cards .btn-primary {
    color: #2B2F6C !important;
}
.cards .btn-primary:hover {
    color: #fff !important;
}
</style>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="mb-1">My Wallet</h2>
        <p class="mb-1">Manage your wallet balance and transactions</p>
        <div class="accent-line border-0"></div>
    </div>

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

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Advance Amount Card -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Advance Amount</h5>
                        <span class="badge bg-{{ $wallet->status === 'active' ? 'success' : 'secondary' }}">
                            {{ ucfirst($wallet->status) }}
                        </span>
                    </div>
                    <div class="mb-3">
                        <h2 class="mb-0" style="color: #2c3e50; font-weight: 700;">
                            ₹{{ number_format($wallet->balance, 2) }}
                        </h2>
                        <small class="text-muted">Available for invoice payments</small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('user.wallet.add-money') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Top-up
                        </a>
                        <a href="{{ route('user.wallet.index') }}" class="btn btn-success">
                            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Wallet Information</h5>
                    <div class="mb-2">
                        <span class="text-muted small">Wallet ID:</span>
                        <strong class="ms-2">{{ $wallet->wallet_id ?? 'N/A' }}</strong>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted small">Created:</span>
                        <strong class="ms-2">{{ $wallet->created_at->format('d M Y') }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="card shadow-sm cards">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-black" style="color: #000000 !important;">Recent Transactions</h5>
            <a href="{{ route('user.wallet.transactions') }}" class="btn btn-primary btn-sm">
                View All
            </a>
        </div>
        <div class="card-body">
            @if($recentTransactions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="text-nowrap">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentTransactions as $transaction)
                                <tr class="align-middle">
                                    <td class="text-nowrap">{{ $transaction->created_at->format('d M Y, h:i A') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->transaction_type === 'credit' ? 'success' : ($transaction->transaction_type === 'debit' ? 'danger' : 'info') }}">
                                            {{ ucfirst($transaction->transaction_type) }}
                                        </span>
                                    </td>
                                    <td>{{ $transaction->description ?? 'N/A' }}</td>
                                    <td>
                                        <strong class=" text-nowrap {{ $transaction->transaction_type === 'credit' ? 'text-success' : 'text-danger' }}">
                                            {{ $transaction->transaction_type === 'credit' ? '+' : '-' }}₹{{ number_format($transaction->amount, 2) }}
                                        </strong>
                                    </td>
                                    <td>₹{{ number_format($transaction->balance_after, 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->status === 'success' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <p class="text-muted mb-0">No transactions yet</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

