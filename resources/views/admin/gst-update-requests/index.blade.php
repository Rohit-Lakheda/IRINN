@extends('admin.layout')

@section('title', 'GST Update Requests')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1 fw-semibold">GST Update Requests</h2>
            <p class="text-muted mb-0">Review and manage GST update requests from users</p>
        </div>
    </div>

    <!-- Search Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.gst-update-requests') }}" class="row g-3 theme-forms">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Search</label>
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Search by GSTIN, company name, application ID, membership ID, user name, email..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Search</button>
                            <a href="{{ route('admin.gst-update-requests') }}" class="btn btn-danger">Clear</a>
                        </div>
                        @if(request('search') || request('status'))
                            <div class="col-12">
                                <small class="text-muted">
                                    Active filters: 
                                    @if(request('search'))
                                        <span class="badge bg-info">{{ request('search') }}</span>
                                    @endif
                                    @if(request('status'))
                                        <span class="badge bg-info">{{ ucfirst(request('status')) }}</span>
                                    @endif
                                </small>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold text-capitalize">All GST Update Requests ({{ $requests->total() }})</h5>
                </div>
            <div class="card-body">
                @if($requests->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="text-nowrap">
                                <tr>
                                    <th>Application</th>
                                    <th>User</th>
                                    <th>GSTIN Change</th>
                                    <th>Company Name</th>
                                    <th>Similarity</th>
                                    <th>Status</th>
                                    <th>Requested At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requests as $gstRequest)
                                    <tr class="align-middle">
                                        <td>
                                            <a href="{{ route('admin.applications.show', $gstRequest->application_id) }}" class="text-decoration-none">
                                                <strong>{{ $gstRequest->application->application_id }}</strong><br>
                                                @if($gstRequest->application->membership_id)
                                                    <small class="text-muted">Membership: {{ $gstRequest->application->membership_id }}</small>
                                                @endif
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.users.show', $gstRequest->user_id) }}" class="text-decoration-none">
                                                <strong>{{ $gstRequest->user->fullname }}</strong><br>
                                                <small class="text-muted">{{ $gstRequest->user->email }}</small>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="mb-1">
                                                <small class="text-muted">Old:</small><br>
                                                <span class="badge bg-secondary">{{ $gstRequest->old_gstin ?? 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <small class="text-muted">New:</small><br>
                                                <span class="badge bg-primary">{{ $gstRequest->new_gstin }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="mb-1">
                                                <small class="text-muted">Old:</small><br>
                                                <span class="text-muted">{{ $gstRequest->old_company_name ?? 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <small class="text-muted">New:</small><br>
                                                <strong>{{ $gstRequest->new_company_name }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            @if($gstRequest->similarity_score !== null)
                                                <span class="badge bg-{{ $gstRequest->similarity_score >= 70 ? 'success' : 'warning' }}">
                                                    {{ number_format($gstRequest->similarity_score, 2) }}%
                                                </span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge text-capitalize bg-{{ $gstRequest->status === 'pending' ? 'warning' : ($gstRequest->status === 'approved' ? 'success' : 'danger') }}">
                                                {{ ucfirst($gstRequest->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $gstRequest->created_at->format('M d, Y h:i A') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.gst-update-requests.show', $gstRequest->id) }}" class="btn btn-md btn-outline-primary" title="View Details">
                                                    View Details
                                                </a>
                                                
                                                @if($gstRequest->status === 'pending')
                                                    <form method="POST" action="{{ route('admin.gst-update-requests.approve', $gstRequest->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to approve this GST update request? This will update the application with new GST details.');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" title="Approve Request">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                                <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $gstRequest->id }}" title="Reject Request">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                            
                                            <!-- Reject Modal -->
                                            @if($gstRequest->status === 'pending')
                                            <div class="modal fade" id="rejectModal{{ $gstRequest->id }}" tabindex="-1" aria-labelledby="rejectModalLabel{{ $gstRequest->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="{{ route('admin.gst-update-requests.reject', $gstRequest->id) }}">
                                                            @csrf
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title" id="rejectModalLabel{{ $gstRequest->id }}">Reject GST Update Request</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Please provide a reason for rejecting this GST update request:</p>
                                                                <div class="mb-3">
                                                                    <label for="admin_notes{{ $gstRequest->id }}" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                                                    <textarea class="form-control" id="admin_notes{{ $gstRequest->id }}" name="admin_notes" rows="4" required minlength="10" placeholder="Enter reason for rejection (minimum 10 characters)..."></textarea>
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
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-4">
                        {{ $requests->links('vendor.pagination.bootstrap-5') }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="text-muted mb-3" viewBox="0 0 16 16">
                            <path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm15 2h-4v3h4V4zm0 4h-4v3h4V8zm0 4h-4v3h3a1 1 0 0 0 1-1v-2zm-5 3v-3H6v3h4zm-5 0v-3H1v2a1 1 0 0 0 1 1h3zm-4-4h4V8H1v3zm0-4h4V4H1v3zm5-3v3h4V4H6zm4 4H6v3h4V8z"/>
                        </svg>
                        <h5 class="text-muted">No GST update requests found</h5>
                        <p class="text-muted">Try adjusting your search to see more results.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

