@extends('superadmin.layout')

@section('title', 'Reactivation Fee')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1" style="color: #2c3e50; font-weight: 600;">Reactivation Fee</h2>
            <p class="text-muted mb-0">Manage the fixed reactivation fee for disconnected members.</p>
            <div class="accent-line"></div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow-sm" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0 fw-semibold">Current Fee</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <div class="text-muted small">Current Fee Amount</div>
                        <div class="fs-4 fw-bold">₹{{ number_format((float) $setting->fee_amount, 2) }}</div>
                    </div>

                    <form method="POST" action="{{ route('superadmin.reactivation-fee.update') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Update Fee Amount (INR)</label>
                            <input type="number" step="0.01" min="0" name="fee_amount" class="form-control" value="{{ old('fee_amount', $setting->fee_amount) }}" required>
                            @error('fee_amount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">GST Percentage (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="gst_percentage" class="form-control" value="{{ old('gst_percentage', $setting->gst_percentage ?? 18) }}" required>
                            @error('gst_percentage')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Used for reactivation invoices.</small>
                        </div>
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Update reactivation fee?')">
                            Update Fee
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

