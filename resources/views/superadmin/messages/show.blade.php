@extends('superadmin.layout')

@section('title', 'Message Details')

@section('content')
<div class="container-fluid px-2 py-0">
    <!-- Page Header -->
    <div class="mb-2">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2 class="mb-1 fw-semibold text-navy border-0">Message Details</h2>
            </div>
            <div>
                <a href="{{ route('superadmin.messages') }}" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 1 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                    </svg>
                    Back to Messages
                </a>
            </div>
        </div>
    </div>
    <div class="accent-line"></div>

    <div class="row mt-4">
        <div class="col-md-8">
            <!-- Message Card -->
            <div class="card border-c-blue shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Message</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="text-muted small mb-1">Subject</label>
                        <p class="mb-0 text-navy fw-semibold fs-6">{{ $message->subject }}</p>
                    </div>
                    <div class="mb-4">
                        <label class="text-muted small mb-1">Message</label>
                        <div class="p-3 bg-light rounded text-navy">{{ $message->message }}</div>
                    </div>
                    @if($message->user_reply)
                        <div class="mb-4">
                            <label class="text-muted small mb-1">User Reply</label>
                            <div class="p-3 bg-info bg-opacity-10 rounded text-navy">{{ $message->user_reply }}</div>
                            <small class="text-muted">Replied on: {{ $message->user_replied_at->format('M d, Y h:i A') }} ({{ $message->user_replied_at->diffForHumans() }})</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- User Information -->
            <div class="card border-success shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-header bg-success text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold">User Information</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Name</label>
                        <p class="mb-0">
                            <a href="{{ route('superadmin.users.show', $message->user_id) }}" class="text-decoration-none fw-semibold text-blue">
                                {{ $message->user->fullname }}
                            </a>
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Email</label>
                        <p class="mb-0 text-nevy fw-medium">{{ $message->user->email }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Mobile</label>
                        <p class="mb-0 text-nevy fw-medium">{{ $message->user->mobile }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Status</label>
                        <p class="mb-0">
                            <span class="badge rounded-pill px-3 py-1 
                                @if($message->user->status === 'approved' || $message->user->status === 'active') bg-success
                                @elseif($message->user->status === 'pending') bg-warning text-dark
                                @else bg-secondary @endif">
                                {{ ucfirst($message->user->status) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Message Metadata -->
            <div class="card border-info shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold">Message Details</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Sent By</label>
                        <p class="mb-0">
                            @if($message->sent_by === 'admin')
                                @if($adminAction && $adminAction->admin)
                                    <span class="badge rounded-pill px-3 py-1 bg-primary">
                                        {{ $adminAction->admin->name }}
                                    </span>
                                    <br>
                                    <small class="text-muted">{{ $adminAction->admin->email }}</small>
                                @else
                                    <span class="badge rounded-pill px-3 py-1 bg-primary">Admin</span>
                                @endif
                            @else
                                <span class="badge rounded-pill px-3 py-1 bg-info">{{ ucfirst($message->sent_by) }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Sent On</label>
                        <p class="mb-0 text-nevy fw-medium">
                            {{ $message->created_at->format('M d, Y h:i A') }}
                            <br>
                            <small class="text-muted">{{ $message->created_at->diffForHumans() }}</small>
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Read Status</label>
                        <p class="mb-0">
                            @if($message->is_read)
                                <span class="badge rounded-pill px-3 py-1 bg-success">Read</span>
                                @if($message->read_at)
                                    <br>
                                    <small class="text-muted">Read on: {{ $message->read_at->format('M d, Y h:i A') }}</small>
                                @endif
                            @else
                                <span class="badge rounded-pill px-3 py-1 bg-warning text-dark">Unread</span>
                            @endif
                        </p>
                    </div>
                    @if($message->user_reply)
                        <div class="mb-3">
                            <label class="text-muted small mb-1">User Replied</label>
                            <p class="mb-0">
                                <span class="badge rounded-pill px-3 py-1 bg-success">Yes</span>
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

