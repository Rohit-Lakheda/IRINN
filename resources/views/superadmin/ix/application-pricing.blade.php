@extends('superadmin.layout')

@section('title', 'IX Application Pricing')

@section('content')
<div class="container-fluid px-2 py-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
        <div>
            <h2 class="mb-0 border-0" style="color:#2c3e50;font-weight:600;">IX Application Pricing</h2>
            <p class="text-muted mb-1">Manage application fee and GST percentage for IX applications.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('superadmin.dashboard') }}" class="btn btn-primary">
                <i class="bi bi-arrow-left fs-6"></i> Back to Dashboard
            </a>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPricingModal">
                + Add Pricing
            </button>
        </div>
    </div>
    <div class="accent-line"></div>

    @if($activePricing)
        <div class="alert alert-info mb-4">
            <strong>Currently Active Pricing:</strong> Application Fee: ₹{{ number_format($activePricing->application_fee, 2) }}, GST: {{ $activePricing->gst_percentage }}%, Total: ₹{{ number_format($activePricing->total_amount, 2) }}
        </div>
    @endif

    <div class="card border-c-blue shadow-sm">
        <div class="card-header bg-primary">
            <h5 class="mb-0">Pricing History</h5>
        </div>
        <div class="card-body p-2">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light text-nowrap">
                        <tr>
                            <th>Application Fee (₹)</th>
                            <th>GST %</th>
                            <th>Total Amount (₹)</th>
                            <th>Effective From</th>
                            <th>Effective Until</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pricings as $pricing)
                        <tr>
                            <td>₹{{ number_format($pricing->application_fee, 2) }}</td>
                            <td>{{ $pricing->gst_percentage }}%</td>
                            <td>₹{{ number_format($pricing->total_amount, 2) }}</td>
                            <td>{{ $pricing->effective_from ? $pricing->effective_from->format('d M Y') : 'N/A' }}</td>
                            <td>{{ $pricing->effective_until ? $pricing->effective_until->format('d M Y') : 'No expiry' }}</td>
                            <td>
                                <span class="badge {{ $pricing->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $pricing->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm gap-2">
                                    <button class="btn rounded btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editPricingModal{{ $pricing->id }}">
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('superadmin.ix-application-pricing.toggle', $pricing) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn rounded btn-sm {{ $pricing->is_active ? 'theme-bg-yellow' : 'btn-success' }}">
                                            {{ $pricing->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('superadmin.ix-application-pricing.destroy', $pricing) }}" class="d-inline" onsubmit="return confirm('Remove this pricing entry?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn rounded btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <div class="modal fade" id="editPricingModal{{ $pricing->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content border-c-blue">
                                    <div class="modal-header theme-bg-blue text-white">
                                        <h5 class="modal-title text-white" style="color: #fff !important;">Update Pricing</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="{{ route('superadmin.ix-application-pricing.update', $pricing) }}" class="theme-forms">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-body theme-forms">
                                            <div class="mb-3">
                                                <label class="form-label">Application Fee (₹)</label>
                                                <input type="number" step="0.01" min="0" name="application_fee" class="form-control" value="{{ old('application_fee', $pricing->application_fee) }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">GST Percentage (%)</label>
                                                <input type="number" step="0.01" min="0" max="100" name="gst_percentage" class="form-control" value="{{ old('gst_percentage', $pricing->gst_percentage) }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Effective From</label>
                                                <input type="date" name="effective_from" class="form-control" value="{{ old('effective_from', $pricing->effective_from?->format('Y-m-d')) }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Effective Until</label>
                                                <input type="date" name="effective_until" class="form-control" value="{{ old('effective_until', $pricing->effective_until?->format('Y-m-d')) }}">
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="pricingActive{{ $pricing->id }}" @checked($pricing->is_active)>
                                                <label class="form-check-label" for="pricingActive{{ $pricing->id }}">Active</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No pricing entries found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createPricingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-c-blue">
            <div class="modal-header theme-bg-blue text-white">
                <h5 class="modal-title text-white" style="color: #fff !important;">Add Pricing Entry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('superadmin.ix-application-pricing.store') }}" class="theme-forms">
                @csrf
                <div class="modal-body theme-forms">
                    <div class="mb-3">
                        <label class="form-label">Application Fee (₹)</label>
                        <input type="number" step="0.01" min="0" name="application_fee" class="form-control" value="1000.00" required>
                        <small class="text-muted">Base application fee amount</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">GST Percentage (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="gst_percentage" class="form-control" value="18.00" required>
                        <small class="text-muted">GST percentage to be applied</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Effective From</label>
                        <input type="date" name="effective_from" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Effective Until</label>
                        <input type="date" name="effective_until" class="form-control">
                        <small class="text-muted">Leave empty for no expiry</small>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="createPricingActive" checked>
                        <label class="form-check-label" for="createPricingActive">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Pricing</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
