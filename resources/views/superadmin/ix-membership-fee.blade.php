@extends('superadmin.layout')

@section('title', 'IX Membership Fee')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1" style="color: #2c3e50; font-weight: 600;">IX Membership Fee</h2>
            <p class="text-muted mb-0">Manage the annual IX membership fee. This fee is invoiced per customer (when they have at least one live application), generated with the first service invoice of a billing cycle or on 1st April each year. GST (CGST/SGST or IGST) is applied based on same-state/different-state logic.</p>
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

                    <form method="POST" action="{{ route('superadmin.ix-membership-fee.update') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Fee Amount (INR)</label>
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
                            <small class="text-muted">CGST/SGST (same state) or IGST (different state) is calculated from this rate.</small>
                        </div>
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Update IX membership fee?')">
                            Update Fee
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0 fw-semibold">Generate Membership Invoices</h6>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small">Eligible customers: those with at least one <strong>live</strong> IX application (not suspended/disconnected). Membership invoices are generated per customer for the current financial year ({{ $fy['start']->format('d M Y') }} to {{ $fy['end']->format('d M Y') }}).</p>
                    <p class="mb-3"><strong>{{ count($eligibleUserIds) }}</strong> eligible customer(s).</p>

                    <form method="POST" action="{{ route('superadmin.ix-membership-fee.generate') }}" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Generate membership invoices for all eligible customers who do not already have one for this FY?');">
                            Generate for all eligible (current FY)
                        </button>
                    </form>

                    <form method="POST" action="{{ route('superadmin.ix-membership-fee.generate') }}">
                        @csrf
                        <label class="form-label fw-semibold">Or generate for one customer (User ID)</label>
                        <div class="input-group">
                            <input type="number" name="user_id" class="form-control" placeholder="User ID" min="1">
                            <button type="submit" class="btn btn-outline-secondary">Generate for this user</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
