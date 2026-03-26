@extends('admin.layout')

@section('title', 'Profile Update Requests')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1 fw-semibold">Profile Update Requests</h2>
            <p class="text-muted mb-0">Review and manage profile update requests from users</p>
        </div>
    </div>

    <!-- Search Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.profile-update-requests') }}" class="row g-3 theme-forms">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Search</label>
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Search by status, registration name, email, registration ID, or mobile..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Search</button>
                            <a href="{{ route('admin.profile-update-requests') }}" class="btn btn-danger">Clear</a>
                        </div>
                        @if(request('search'))
                            <div class="col-12">
                                <small class="text-muted">
                                    Active filter: <span class="badge bg-info">{{ request('search') }}</span>
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
                    <h5 class="mb-0 fw-semibold text-capitalize">All Profile Update Requests ({{ $requests->total() }})</h5>
                </div>
            <div class="card-body">
                @if($requests->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="text-nowrap">
                                <tr>
                                    <th>Registration</th>
                                    <th>Requested Changes</th>
                                    <th>Status</th>
                                    <th>Requested At</th>
                                    <th>Approved/Rejected By</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requests as $request)
                                    <tr class="align-middle">
                                        <td>
                                            <a href="{{ route('admin.users.show', $request->user_id) }}" class="text-decoration-none">
                                                <strong>{{ $request->user->fullname }}</strong><br>
                                                <small class="text-muted">{{ $request->user->email }}</small><br>
                                                <small class="text-muted">{{ $request->user->mobile }}</small>
                                            </a>
                                        </td>
                                        <td>
                                            @if($request->requested_changes)
                                                <div class="mb-2">
                                                    <strong>Request:</strong><br>
                                                    {{ Str::limit(is_array($request->requested_changes) ? json_encode($request->requested_changes) : $request->requested_changes, 150) }}
                                                </div>
                                            @else
                                                <span class="text-muted">No details provided</span>
                                            @endif
                                            
                                            @if($request->submitted_data)
                                                <div class="mt-2 p-2 bg-success rounded text-white">
                                                    <strong>Submitted Data:</strong><br>
                                                    @if(isset($request->submitted_data['email']))
                                                        <strong>Email:</strong> {{ $request->submitted_data['email'] }}<br>
                                                    @endif
                                                    @if(isset($request->submitted_data['mobile']))
                                                        <strong>Mobile:</strong> {{ $request->submitted_data['mobile'] }}
                                                    @endif
                                                    @if($request->submitted_at)
                                                        <br><small class="text-white">Submitted: {{ $request->submitted_at->format('M d, Y h:i A') }}</small>
                                                    @endif
                                                </div>
                                            @endif
                                            
                                            @if($request->admin_notes && $request->status === 'rejected')
                                                <div class="mt-2 p-2 bg-danger bg-opacity-10 rounded">
                                                    <strong>Rejection Reason:</strong><br>
                                                    {{ $request->admin_notes }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge text-capitalize bg-{{ $request->status === 'pending' ? 'warning' : ($request->status === 'approved' ? 'success' : 'danger') }}">
                                                {{ ucfirst($request->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $request->created_at->format('M d, Y h:i A') }}</td>
                                        <td>
                                            @if($request->approver)
                                                @if($request->approver instanceof \App\Models\Admin)
                                                    {{ $request->approver->name }}
                                                @else
                                                    {{ $request->approver->fullname ?? 'Unknown' }}
                                                @endif
                                                @if($request->approved_at)
                                                    <br><small class="text-muted">{{ $request->approved_at->format('M d, Y h:i A') }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group gap-2" role="group">
                                                <a href="{{ route('admin.profile-update-requests.show', $request->id) }}" class="btn btn-sm btn-primary rounded" title="View Details">
                                                    <i class="bi bi-eye fs-6"></i>
                                                </a>
                                                
                                                @if($request->status === 'pending')
                                                    <form method="POST" action="{{ route('admin.profile-updates.approve', $request->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to approve this profile update request?');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" title="Approve Request">
                                                            <i class="bi bi-check2 fs-6"></i>
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-sm btn-danger rounded" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $request->id }}" title="Reject Request">
                                                        <i class="bi bi-x-lg fs-6"></i>
                                                    </button>
                                                @elseif($request->status === 'approved' && $request->submitted_data && !$request->update_approved)
                                                    <form method="POST" action="{{ route('admin.profile-updates.approve-submitted', $request->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to approve and apply this profile update? This will update the user\'s profile.');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success rounded" title="Approve & Apply Update">
                                                            <i class="bi bi-check2 fs-6"></i>
                                                            Apply
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                            
                                            <!-- Reject Modal -->
                                            @if($request->status === 'pending')
                                            <div class="modal fade" id="rejectModal{{ $request->id }}" tabindex="-1" aria-labelledby="rejectModalLabel{{ $request->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="{{ route('admin.profile-updates.reject', $request->id) }}">
                                                            @csrf
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title" id="rejectModalLabel{{ $request->id }}">Reject Profile Update Request</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Please provide a reason for rejecting this profile update request:</p>
                                                                <div class="mb-3">
                                                                    <label for="admin_notes{{ $request->id }}" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                                                    <textarea class="form-control" id="admin_notes{{ $request->id }}" name="admin_notes" rows="4" required minlength="10" placeholder="Enter reason for rejection (minimum 10 characters)..."></textarea>
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
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                        </svg>
                        <h5 class="text-muted">No profile update requests found</h5>
                        <p class="text-muted">Try adjusting your search to see more results.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

