@extends('admin.layout')

@section('title', 'Profile Update Request Details')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div>
                <h2 class="mb-1 fw-semibold">Profile Update Request Details</h2>
                <p class="text-muted mb-0">Request ID: #{{ $request->id }}</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Registration Summary Card -->
        <div class="col-md-6">
            <div class="card border-c-blue shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 12px 12px 0 0;">
                    <h6 class="mb-0 fw-semibold text-capitalize">Registration Summary</h6>
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
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Status</label>
                            <div>
                                <span class="badge bg-{{ $request->user->status === 'approved' ? 'success' : ($request->user->status === 'active' ? 'info' : 'warning') }}">
                                    {{ ucfirst($request->user->status) }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Registered At</label>
                            <div style="color: #2c3e50; font-size: 0.875rem;">{{ $request->user->created_at->format('d M Y, h:i A') }}</div>
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
                        @if($request->approver)
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Approved/Rejected By</label>
                            <div style="color: #2c3e50;">
                                @if($request->approver instanceof \App\Models\Admin)
                                    {{ $request->approver->name }}
                                @else
                                    {{ $request->approver->fullname ?? 'Unknown' }}
                                @endif
                            </div>
                        </div>
                        @endif
                        @if($request->approved_at)
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Approved At</label>
                            <div style="color: #2c3e50; font-size: 0.875rem;">{{ $request->approved_at->format('d M Y, h:i A') }}</div>
                        </div>
                        @endif
                        @if($request->rejected_at)
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Rejected At</label>
                            <div style="color: #2c3e50; font-size: 0.875rem;">{{ $request->rejected_at->format('d M Y, h:i A') }}</div>
                        </div>
                        @endif
                        @if($request->update_approved)
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Update Applied</label>
                            <div>
                                <span class="badge bg-success">Yes</span>
                            </div>
                        </div>
                        @endif
                        @if($request->update_approved_at)
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Applied At</label>
                            <div style="color: #2c3e50; font-size: 0.875rem;">{{ $request->update_approved_at->format('d M Y, h:i A') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- User Message / Request Details Card -->
        <div class="col-md-6">
            <div class="card border-c-blue shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header theme-bg-blue" style="border-radius: 12px 12px 0 0;">
                    <h6 class="mb-0 fw-semibold text-capitalize">User Request Message</h6>
                </div>
                <div class="card-body">
                    @if($request->requested_changes)
                        @if(is_array($request->requested_changes))
                            @if(isset($request->requested_changes['message']))
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Message</label>
                                    <div style="color: #2c3e50; white-space: pre-wrap;">{{ $request->requested_changes['message'] }}</div>
                                </div>
                            @endif
                            @if(isset($request->requested_changes['changes']))
                                <div class="mb-2">
                                    <label class="text-muted small mb-1">Requested Changes</label>
                                    <div style="color: #2c3e50;">
                                        <ul class="mb-0">
                                            @foreach($request->requested_changes['changes'] as $field => $value)
                                                <li><strong>{{ ucwords(str_replace('_', ' ', $field)) }}:</strong> {{ $value }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        @else
                            <div style="color: #2c3e50; white-space: pre-wrap;">{{ $request->requested_changes }}</div>
                        @endif
                    @else
                        <div class="text-muted">No message provided</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Requested Changes Details Card -->
        @if($request->requested_changes && is_array($request->requested_changes))
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header theme-bg-secondary text-white" style="border-radius: 12px 12px 0 0;">
                    <h6 class="mb-0 fw-semibold">Requested Changes Details</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @foreach($request->requested_changes as $key => $value)
                            @if($key !== 'message' && $key !== 'changes')
                                <div class="col-md-6">
                                    <label class="text-muted small mb-1">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                    <div style="color: #2c3e50;">{{ is_array($value) ? json_encode($value) : $value }}</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Previous Details & New Details Comparison -->
        @if($request->submitted_data)
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header bg-danger text-white" style="border-radius: 12px 12px 0 0;">
                    <h6 class="mb-0" style="font-weight: 600;">Previous Details</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
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
                        @if($request->user->pancardno)
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">PAN Card</label>
                            <div style="color: #2c3e50;">{{ $request->user->pancardno }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header bg-success text-white" style="border-radius: 12px 12px 0 0;">
                    <h6 class="mb-0" style="font-weight: 600;">New Details (Submitted)</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @if(isset($request->submitted_data['fullname']))
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Full Name</label>
                            <div style="color: #2c3e50; font-weight: 500;">{{ $request->submitted_data['fullname'] }}</div>
                        </div>
                        @endif
                        @if(isset($request->submitted_data['email']))
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Email</label>
                            <div style="color: #2c3e50;">{{ $request->submitted_data['email'] }}</div>
                        </div>
                        @endif
                        @if(isset($request->submitted_data['mobile']))
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Mobile</label>
                            <div style="color: #2c3e50;">{{ $request->submitted_data['mobile'] }}</div>
                        </div>
                        @endif
                        @if(isset($request->submitted_data['pancardno']))
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">PAN Card</label>
                            <div style="color: #2c3e50;">{{ $request->submitted_data['pancardno'] }}</div>
                        </div>
                        @endif
                        @if($request->submitted_at)
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Submitted At</label>
                            <div style="color: #2c3e50; font-size: 0.875rem;">{{ $request->submitted_at->format('d M Y, h:i A') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Admin Notes / Rejection Reason -->
        @if($request->admin_notes)
        <div class="col-md-6">
            <div class="card border-c-blue shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 12px 12px 0 0;">
                    <h6 class="mb-0 fw-semibold text-capitalize">Admin Notes</h6>
                </div>
                <div class="card-body">
                    <div style="color: #2c3e50; white-space: pre-wrap;">{{ $request->admin_notes }}</div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Action Buttons -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('admin.users.show', $request->user_id) }}" class="btn btn-outline-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                            </svg>
                            View Full Registration
                        </a>
                        
                        @if($request->status === 'pending')
                            <form method="POST" action="{{ route('admin.profile-updates.approve', $request->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to approve this profile update request?');">
                                @csrf
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
                        @elseif($request->status === 'approved' && $request->submitted_data && !$request->update_approved)
                            <form method="POST" action="{{ route('admin.profile-updates.approve-submitted', $request->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to approve and apply this profile update? This will update the user\'s profile.');">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                        <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                    </svg>
                                    Approve & Apply Update
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    @if($request->status === 'pending')
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.profile-updates.reject', $request->id) }}">
                    @csrf
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="rejectModalLabel">Reject Profile Update Request</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Please provide a reason for rejecting this profile update request:</p>
                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="4" required minlength="10" placeholder="Enter reason for rejection (minimum 10 characters)..."></textarea>
                            <small class="text-muted">Minimum 10 characters required</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

