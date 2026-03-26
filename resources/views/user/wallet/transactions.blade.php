@extends('user.layout')

@section('title', 'Wallet Transactions')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="mb-1 border-bottom-0">Wallet Transactions</h2>
                <p class="mb-0">View all your wallet transactions</p>
            </div>
            <a href="{{ route('user.wallet.index') }}" class="btn btn-primary">
                <i class="bi bi-arrow-left me-1"></i> Back to Wallet
            </a>
        </div>
        <div class="accent-line"></div>
    </div>

    <!-- Wallet Balance Summary -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Current Balance</h6>
                    <p class="fs-4 mb-0 fw-bold border-bottom-0 text-black">
                        ₹{{ number_format($wallet->balance, 2) }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Total Credits</h6>
                    <p class="fs-4 mb-0 fw-bold border-bottom-0 text-success">
                        ₹{{ number_format($wallet->transactions()->where('transaction_type', 'credit')->where('status', 'success')->sum('amount'), 2) }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Total Debits</h6>
                    <p class="fs-4 mb-0 fw-bold border-bottom-0 text-danger">
                        ₹{{ number_format($wallet->transactions()->where('transaction_type', 'debit')->where('status', 'success')->sum('amount'), 2) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0" style="color: #000000 !important;">Transaction History</h5>
        </div>
        <div class="card-body">
            @if($transactions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="text-nowrap">
                            <tr>
                                <th>Date & Time</th>
                                <th>Type</th>
                                <th>Transaction ID</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Balance Before</th>
                                <th>Balance After</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $transaction)
                                <tr class="align-middle">
                                    <td class="text-nowrap">{{ $transaction->created_at->format('d M Y, h:i A') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->transaction_type === 'credit' ? 'success' : ($transaction->transaction_type === 'debit' ? 'danger' : ($transaction->transaction_type === 'refund' ? 'info' : 'secondary')) }}">
                                            {{ ucfirst($transaction->transaction_type) }}
                                        </span>
                                        @if($transaction->sync_source)
                                            <span class="badge bg-secondary ms-1" title="Synced from PayU">Sync</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $transaction->transaction_id ?? 'N/A' }}</small>
                                    </td>
                                    <td>{{ $transaction->description ?? 'N/A' }}</td>
                                    <td class="text-nowrap">
                                        <strong class="{{ $transaction->transaction_type === 'credit' || $transaction->transaction_type === 'refund' ? 'text-success' : 'text-danger' }}">
                                            {{ $transaction->transaction_type === 'credit' || $transaction->transaction_type === 'refund' ? '+' : '-' }}₹{{ number_format($transaction->amount, 2) }}
                                        </strong>
                                    </td>
                                    <td>₹{{ number_format($transaction->balance_before, 2) }}</td>
                                    <td>
                                        <strong>₹{{ number_format($transaction->balance_after, 2) }}</strong>
                                    </td>
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

                <!-- Pagination -->
                <div class="mt-3">
                    {{ $transactions->links() }}
                </div>
            @else
                <div class="text-center py-4">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="text-muted mt-3 mb-0">No transactions found</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

