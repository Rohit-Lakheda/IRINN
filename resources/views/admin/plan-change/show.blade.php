@extends('admin.layout')

@section('title', 'Plan Change Request Details')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="mb-1" style="color:#2c3e50;font-weight:600;">Plan Change Request Details</h2>
        <p class="text-muted mb-0">Application: {{ $request->application->application_id }}</p>
    </div>

    <!-- Request Information & Status -->
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card border-c-blue shadow-sm h-100" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0 fw-semibold text-capitalize">Request Information</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row app-details">
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Application ID</label>
                            <div>
                                <a href="{{ route('admin.applications.show', $request->application_id) }}" style="color: #0d6efd; text-decoration: none; font-weight: 500;">
                                    {{ $request->application->application_id }}
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">User</label>
                            <div>{{ $request->user->fullname ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Status</label>
                            <div>
                                @if($request->status === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($request->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($request->status === 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($request->status) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Change Type</label>
                            <div>
                                @if($request->change_type === 'upgrade')
                                    <span class="badge bg-success">Upgrade</span>
                                @else
                                    <span class="badge bg-info">Downgrade</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Requested</label>
                            <div>{{ $request->created_at->format('d M Y, h:i A') }}</div>
                        </div>
                        @if($request->reviewed_at)
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Reviewed</label>
                            <div>{{ $request->reviewed_at->format('d M Y, h:i A') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Reviewed By</label>
                            <div>{{ $request->reviewedBy->name ?? 'N/A' }}</div>
                        </div>
                        @endif
                        @if($request->effective_from)
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Effective From</label>
                            <div>{{ $request->effective_from->format('d M Y') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-info shadow-sm h-100" style="border-radius: 16px;">
                <div class="card-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0 fw-semibold text-capitalize">User's Reason</h6>
                </div>
                <div class="card-body p-4">
                    <p class="mb-0">{{ $request->reason ?? 'No reason provided.' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Plan & Requested Plan -->
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card border-c-green shadow-sm h-100" style="border-radius: 16px;">
                <div class="card-header theme-bg-green text-white" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0 fw-semibold text-capitalize">Current Plan</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row app-details">
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Port Capacity</label>
                            <div class="fw-bold">{{ $request->current_port_capacity ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Billing Plan</label>
                            <div>{{ strtoupper($request->current_billing_plan ?? 'N/A') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Amount</label>
                            <div>₹{{ number_format($request->current_amount ?? 0, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-c-gold shadow-sm h-100" style="border-radius: 16px;">
                <div class="card-header theme-bg-gold text-white" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0 fw-semibold text-capitalize">Requested Plan</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row app-details">
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Port Capacity</label>
                            <div class="fw-bold">{{ $request->new_port_capacity }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Billing Plan</label>
                            <div>{{ strtoupper($request->new_billing_plan) }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Amount</label>
                            <div>₹{{ number_format($request->new_amount, 2) }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Adjustment</label>
                            <div>
                                @if($request->adjustment_amount > 0)
                                    <span class="text-danger fw-bold">+₹{{ number_format($request->adjustment_amount, 2) }}</span>
                                    <small class="d-block text-muted">Additional payment required</small>
                                @elseif($request->adjustment_amount < 0)
                                    <span class="text-success fw-bold">₹{{ number_format(abs($request->adjustment_amount), 2) }}</span>
                                    <small class="d-block text-muted">Credit will be applied</small>
                                @else
                                    <span class="text-muted">₹0.00</span>
                                    <small class="d-block text-muted">No change</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($request->status === 'pending')
    <!-- Approval & Rejection Forms -->
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-success shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-success text-white" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0 fw-semibold text-capitalize">Approve Request</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.plan-change.approve', $request->id) }}" class="theme-forms">
                        @csrf
                        <div class="mb-3">
                            <label for="effective_from" class="form-label small">Effective From</label>
                            <input
                                type="date"
                                name="effective_from"
                                id="effective_from"
                                class="form-control form-control-sm"
                                value="{{ old('effective_from', date('Y-m-d')) }}">
                            <small class="form-text text-muted">When should this plan change take effect? (Past dates are allowed for admin adjustments.)</small>
                        </div>
                        <div class="mb-3">
                            <label for="admin_notes_approve" class="form-label small">Admin Notes (Optional)</label>
                            <textarea name="admin_notes" id="admin_notes_approve" rows="3" class="form-control form-control-sm" placeholder="Add any notes for the user..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to approve this plan change request?')">
                            Approve Request
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-danger shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-danger text-white" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0 fw-semibold text-capitalize">Reject Request</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.plan-change.reject', $request->id) }}" class="theme-forms">
                        @csrf
                        <div class="mb-3">
                            <label for="admin_notes_reject" class="form-label small">Rejection Reason <span class="text-danger">*</span></label>
                            <textarea name="admin_notes" id="admin_notes_reject" rows="3" class="form-control form-control-sm @error('admin_notes') is-invalid @enderror" required placeholder="Please provide a reason for rejection (minimum 10 characters)..."></textarea>
                            @error('admin_notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Minimum 10 characters required.</small>
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject this plan change request?')">
                            Reject Request
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @if(in_array(strtolower($request->status), ['pending', 'approved']))
        <div class="col-md-4">
            <div class="card border-danger shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-danger text-white" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0 fw-semibold text-capitalize">Delete Request</h6>
                </div>
                <div class="card-body p-4">
                    <p class="small text-muted mb-3">
                        @if($request->status === 'approved')
                            Delete this approved plan change request. If already applied, it will be reverted. This will allow the user to apply for a new plan change.
                        @else
                            Permanently delete this plan change request. This action cannot be undone.
                        @endif
                    </p>
                    <form method="POST" action="{{ route('admin.plan-change.destroy', $request->id) }}" id="deleteForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="event.preventDefault(); if(confirm('Are you sure you want to delete this plan change request? This will allow the user to apply for a new plan change. This action cannot be undone.')) { document.getElementById('deleteForm').submit(); }">
                            Delete Request
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>
    @else
    <!-- Admin Notes (if reviewed) -->
    @if($request->admin_notes)
    <div class="row g-3 mb-3">
        <div class="col-md-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0 fw-semibold text-capitalize">Admin Notes</h6>
                </div>
                <div class="card-body p-4">
                    <p class="mb-0">{{ $request->admin_notes }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif

    <!-- History -->
    @if($request->history->count() > 0)
    <div class="row g-3">
        <div class="col-12">
            <div class="card border-info shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0 fw-semibold text-capitalize">Change History</h6>
                </div>
                <div class="card-body p-3">
                    <div class="timeline">
                        @foreach($request->history as $history)
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>{{ ucfirst($history->action) }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $history->performed_by ?? 'System' }}</small>
                                    @if($history->notes)
                                    <br>
                                    <small class="text-muted">{{ $history->notes }}</small>
                                    @endif
                                </div>
                                <small class="text-muted">{{ $history->created_at->format('d M Y, h:i A') }}</small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
