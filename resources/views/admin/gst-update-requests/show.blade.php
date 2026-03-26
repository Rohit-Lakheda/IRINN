@extends('admin.layout')

@section('title', 'GST Update Request Details')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div>
                <h2 class="mb-1 fw-semibold">GST Update Request Details</h2>
                <p class="text-muted mb-0">Request ID: #{{ $request->id }}</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Application Summary Card -->
        <div class="col-md-6">
            <div class="card border-c-blue shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 12px 12px 0 0;">
                    <h6 class="mb-0 fw-semibold text-capitalize">Application Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row app-details">
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Application ID</label>
                            <div style="color: #2c3e50; font-weight: 500;">
                                <a href="{{ route('admin.applications.show', $request->application_id) }}" class="text-primary text-decoration-none">
                                    {{ $request->application->application_id }}
                                </a>
                            </div>
                        </div>
                        @if($request->application->membership_id)
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Membership ID</label>
                            <div style="color: #2c3e50; font-weight: 500;">{{ $request->application->membership_id }}</div>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Application Type</label>
                            <div style="color: #2c3e50;">{{ $request->application->application_type }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Current Stage</label>
                            <div><span class="badge bg-light text-dark">{{ $request->application->current_stage }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Summary Card -->
        <div class="col-md-6">
            <div class="card border-c-blue shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 12px 12px 0 0;">
                    <h6 class="mb-0 fw-semibold text-capitalize">User Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row app-details">
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Registration ID</label>
                            <div style="color: #2c3e50; font-weight: 500;">
                                <a href="{{ route('admin.users.show', $request->user_id) }}" class="text-primary text-decoration-none">
                                    {{ $request->user->registrationid }}
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Full Name</label>
                            <div style="color: #2c3e50; font-weight: 500;">{{ $request->user->fullname }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Email</label>
                            <div style="color: #2c3e50;">{{ $request->user->email }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Mobile</label>
                            <div style="color: #2c3e50;">{{ $request->user->mobile }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Request Status Card -->
        <div class="col-md-6">
            <div class="card border-info shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header bg-info text-white" style="border-radius: 12px 12px 0 0;">
                    <h6 class="mb-0 fw-semibold text-capitalize">Request Status</h6>
                </div>
                <div class="card-body">
                    <div class="row app-details">
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Status</label>
                            <div>
                                <span class="badge bg-{{ $request->status === 'pending' ? 'warning' : ($request->status === 'approved' ? 'success' : 'danger') }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Requested At</label>
                            <div style="color: #2c3e50; font-size: 0.875rem;">{{ $request->created_at->format('d M Y, h:i A') }}</div>
                        </div>
                        @if($request->reviewedBy)
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Reviewed By</label>
                            <div style="color: #2c3e50;">{{ $request->reviewedBy->name }}</div>
                        </div>
                        @endif
                        @if($request->reviewed_at)
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Reviewed At</label>
                            <div style="color: #2c3e50; font-size: 0.875rem;">{{ $request->reviewed_at->format('d M Y, h:i A') }}</div>
                        </div>
                        @endif
                        @if($request->similarity_score !== null)
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Company Name Similarity</label>
                            <div>
                                <span class="badge bg-{{ $request->similarity_score >= 70 ? 'success' : 'warning' }}">
                                    {{ number_format($request->similarity_score, 2) }}%
                                </span>
                                <small class="text-muted d-block mt-1">
                                    @if($request->similarity_score < 70)
                                        Requires admin approval (below 70%)
                                    @else
                                        Would have been auto-approved
                                    @endif
                                </small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- GST Details Comparison Card -->
        <div class="col-md-6">
            <div class="card border-c-blue shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 12px 12px 0 0;">
                    <h6 class="mb-0 fw-semibold text-capitalize">GST Details Comparison</h6>
                </div>
                <div class="card-body">
                    <div class="row app-details">
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Old GSTIN</label>
                            <div style="color: #2c3e50;">
                                <span class="badge bg-secondary">{{ $request->old_gstin ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">New GSTIN</label>
                            <div style="color: #2c3e50;">
                                <span class="badge bg-primary">{{ $request->new_gstin }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Old Company Name</label>
                            <div style="color: #2c3e50;">{{ $request->old_company_name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">New Company Name</label>
                            <div style="color: #2c3e50; font-weight: 500;">{{ $request->new_company_name }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Notes Card -->
        @if($request->admin_notes)
        <div class="col-md-12">
            <div class="card border-{{ $request->status === 'rejected' ? 'danger' : 'info' }} shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-{{ $request->status === 'rejected' ? 'danger' : 'info' }} text-white" style="border-radius: 12px 12px 0 0;">
                    <h6 class="mb-0 fw-semibold text-capitalize">Admin Notes</h6>
                </div>
                <div class="card-body">
                    <div style="color: #2c3e50; white-space: pre-wrap;">{{ $request->admin_notes }}</div>
                </div>
            </div>
        </div>
        @endif

        <!-- Actions Card -->
        @if($request->status === 'pending')
        <div class="col-md-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 12px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 12px 12px 0 0;">
                    <h6 class="mb-0 fw-semibold text-capitalize">Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('admin.gst-update-requests.approve', $request->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to approve this GST update request? This will update the application with new GST details and billing address.');">
                            @csrf
                            <div class="mb-3">
                                <label for="admin_notes_approve" class="form-label">Admin Notes (Optional)</label>
                                <textarea class="form-control" id="admin_notes_approve" name="admin_notes" rows="3" placeholder="Add any notes about this approval..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                    <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                </svg>
                                Approve Request
                            </button>
                        </form>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                            </svg>
                            Reject Request
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Reject Modal -->
@if($request->status === 'pending')
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.gst-update-requests.reject', $request->id) }}">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel">Reject GST Update Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please provide a reason for rejecting this GST update request:</p>
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="4" required minlength="10" placeholder="Enter reason for rejection (minimum 10 characters)..."></textarea>
                        <small class="text-muted">Minimum 10 characters required</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

