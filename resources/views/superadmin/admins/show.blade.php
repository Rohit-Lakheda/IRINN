@extends('superadmin.layout')

@section('title', 'Admin Details')

@section('content')
<div class="container-fluid px-2 py-0">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
        <div>
            <h2 class="mb-1 border-0">Admin Details</h2>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('superadmin.admins') }}" class="btn btn-primary text-white"> <i class="bi bi-arrow-left fs-6"></i> Back to Admins</a>
            <a href="{{ route('superadmin.admins.edit', $admin->id) }}" class="btn theme-bg-yellow">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                    <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707 10.293 16l6.5-6.5-1.586-1.586z"/>
                </svg>
                Edit Admin
            </a>
        </div>
    </div>
    <div class="accent-line mb-4"></div>

    <div class="row g-4">
        <!-- Admin Details -->
        <div class="col-md-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold">Admin Information</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="text-muted small mb-1">Employee ID</label>
                                <p class="mb-0 fw-medium text-nevy fs-6">{{ $admin->admin_id }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="text-muted small mb-1">Name</label>
                                <p class="mb-0 fw-medium text-nevy fs-6">{{ $admin->name }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="text-muted small mb-1">Email</label>
                                <p class="mb-0 fw-medium text-nevy fs-6">{{ $admin->email }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="text-muted small mb-1">Status</label>
                                <p class="mb-0">
                                    <span class="badge rounded-pill px-3 py-1 {{ $admin->is_active ? 'bg-success' : 'bg-danger' }}">
                                        {{ $admin->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    @if($admin->is_super_admin)
                                        <span class="badge bg-warning text-dark ms-1">Super Admin</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="text-muted small mb-1">Type</label>
                                <p class="mb-0 mb-0 fw-medium text-nevy">
                                    @if($admin->is_super_admin)
                                        <span class="badge bg-warning text-dark">Super Admin</span>
                                    @else
                                        <span class="badge bg-info">Regular Admin</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="text-muted small mb-1">Created On</label>
                                <p class="mb-0 fw-medium text-nevy fs-6">{{ $admin->created_at->format('M d, Y h:i A') }}</p>
                                <small class="text-muted">{{ $admin->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="text-muted small mb-1">Last Updated</label>
                                <p class="mb-0 fw-medium text-nevy fs-6">{{ $admin->updated_at->format('M d, Y h:i A') }}</p>
                                <small class="text-muted">{{ $admin->updated_at->diffForHumans() }}</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="text-muted small mb-1">Total Actions</label>
                                <p class="mb-0 fw-medium text-nevy fs-6">{{ $totalActions }}</p>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="text-muted small mb-1">Roles</label>
                                <div class="d-flex flex-wrap gap-2">
                                    @if($admin->roles->count() > 0)
                                        @foreach($admin->roles as $role)
                                            <span class="badge bg-info px-3 py-2">{{ $role->name }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">No roles assigned</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Login/Logout Activities -->
        <div class="col-md-6">
            <div class="card border-success shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-success text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold">Recent Login/Logout Activities</h5>
                </div>
                <div class="card-body">
                    @if($recentActivities->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentActivities as $activity)
                            <div class="list-group-item px-0 py-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="badge rounded-pill px-2 py-1 me-2
                                                {{ $activity->action_type === 'admin_login' ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $activity->action_type === 'admin_login' ? 'Logged In' : 'Logged Out' }}
                                            </span>
                                        </div>
                                        @if($activity->description)
                                            <p class="mb-1 text-muted small">{{ $activity->description }}</p>
                                        @endif
                                        <small class="text-muted">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                            </svg>
                                            {{ $activity->created_at->format('M d, Y h:i A') }} ({{ $activity->created_at->diffForHumans() }})
                                        </small>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" class="mb-2" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                            </svg>
                            <p class="text-muted mb-0">No recent login/logout activities.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Messages Sent to Users -->
        <div class="col-md-6">
            <div class="card border-info shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold">Messages Sent to Users ({{ $messages->count() }})</h5>
                </div>
                <div class="card-body">
                    @if($messages->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($messages as $message)
                            <div class="list-group-item px-0 py-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-nevy fw-semibold">
                                            <a href="{{ route('superadmin.users.show', $message->user_id) }}" class="text-decoration-none text-nevy">
                                                {{ $message->user->fullname }}
                                            </a>
                                        </h6>
                                        <p class="mb-1 text-muted small">{{ $message->user->email }}</p>
                                        <p class="mb-1 text-nevy fw-medium">{{ $message->subject }}</p>
                                        <div style="max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($message->message, 80) }}</small>
                                        </div>
                                        @if($message->user_reply)
                                            <div class="mt-2 p-2 bg-light rounded">
                                                <small class="text-muted d-block mb-1"><strong>User Reply:</strong></small>
                                                <small>{{ \Illuminate\Support\Str::limit($message->user_reply, 60) }}</small>
                                            </div>
                                        @endif
                                        <small class="text-muted">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                            </svg>
                                            {{ $message->created_at->format('M d, Y h:i A') }} ({{ $message->created_at->diffForHumans() }})
                                        </small>
                                    </div>
                                    <div>
                                        <a href="{{ route('superadmin.messages.show', $message->id) }}" class="btn btn-sm btn-primary">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" class="mb-2" viewBox="0 0 16 16">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                            </svg>
                            <p class="text-muted mb-0">No messages sent yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

