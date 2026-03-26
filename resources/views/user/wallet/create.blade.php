@extends('user.layout')

@section('title', 'Create Wallet')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="mb-1">Create Wallet</h2>
        <p class="mb-0">Create a new wallet to manage your payments</p>
        <div class="accent-line"></div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Wallet Creation</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.wallet.store') }}" class="theme-forms">
                        @csrf
                        <input type="hidden" name="wallet_type" value="closed_loop">

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-wallet2 me-1"></i> Create Wallet
                            </button>
                            <a href="{{ route('user.dashboard') }}" class="btn btn-danger">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

