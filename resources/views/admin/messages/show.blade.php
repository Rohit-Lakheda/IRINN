@extends('admin.layout')

@section('title', 'Message Details')

@section('content')
<div class="py-4">
    <div class="row mb-md-4">
        <div class="col-12">
            <h2 class="mb-1 fw-semibold text-capitalize">Message Details</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.messages') }}">Messages</a></li>
                    <li class="breadcrumb-item active">Message #{{ $message->id }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row g-4">
        <!-- Message Details -->
        <div class="col-md-8">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold text-capitalize">Message Information</h5>
                </div>
                <div class="card-body">
                    <div class="row app-details">
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">To</label>
                            <div>
                                <a href="{{ route('admin.users.show', $message->user_id) }}" style="color: #0d6efd; text-decoration: none; font-weight: 500;">
                                    {{ $message->user->fullname }}
                                </a>
                            </div>
                            <div class="text-muted small">{{ $message->user->email }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">From</label>
                            <div style="color: #2c3e50; font-weight: 500;">
                                @if($adminAction && $adminAction->admin)
                                    {{ $adminAction->admin->name }}
                                @else
                                    You
                                @endif
                            </div>
                            <div class="text-muted small">Admin</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Subject</label>
                            <div style="color: #2c3e50; font-weight: 500; font-size: 1.1rem;">{{ $message->subject }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Status</label>
                            <div>
                                @if($message->admin_read)
                                    <span class="badge bg-success text-capitalize">Read</span>
                                @else
                                    <span class="badge bg-warning text-capitalize">Unread</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="text-muted small mb-2">Message</label>
                            <div class="p-3 bg-light rounded" style="color: #2c3e50; white-space: pre-wrap; line-height: 1.6;">{{ $message->message }}</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Sent At</label>
                            <div style="color: #2c3e50;">
                                {{ $message->created_at->format('d M Y, h:i A') }}
                                <br>
                                <small class="text-muted">{{ $message->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Read At (Admin)</label>
                            <div style="color: #2c3e50;">
                                @if($message->admin_read_at)
                                    {{ $message->admin_read_at->format('d M Y, h:i A') }}
                                    <br>
                                    <small class="text-muted">{{ $message->admin_read_at->diffForHumans() }}</small>
                                @else
                                    <span class="text-muted text-capitalize">Not read yet</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Reply -->
            @if($message->user_reply)
            <div class="card border-c-blue shadow-sm mt-4" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold text-capitalize">User Reply</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small mb-2">Reply Message</label>
                        <div class="p-3 bg-info bg-opacity-10 rounded" style="color: #2c3e50; white-space: pre-wrap; line-height: 1.6;">{{ $message->user_reply }}</div>
                    </div>
                    <div>
                        <label class="text-muted small mb-1">Replied At</label>
                        <div style="color: #2c3e50;">
                            @if($message->user_replied_at)
                                {{ $message->user_replied_at->format('d M Y, h:i A') }}
                                <br>
                                <small class="text-muted">{{ $message->user_replied_at->diffForHumans() }}</small>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="card border-c-blue shadow-sm mt-4" style="border-radius: 16px;">
                <div class="card-body p-4 text-center">
                    <p class="text-muted mb-0">No reply from user yet.</p>
                </div>
            </div>
            @endif
        </div>

        <!-- User Information -->
        <div class="col-md-4">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold text-capitalize">User Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Name</label>
                        <div>
                            <a href="{{ route('admin.users.show', $message->user_id) }}" style="color: #0d6efd; text-decoration: none; font-weight: 500;">
                                {{ $message->user->fullname }}
                            </a>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Email</label>
                        <div style="color: #2c3e50;">{{ $message->user->email }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Registration ID</label>
                        <div style="color: #2c3e50;">{{ $message->user->registrationid }}</div>
                    </div>
                    <div class="mb-0">
                        <label class="text-muted small mb-1">Mobile</label>
                        <div style="color: #2c3e50;">{{ $message->user->mobile }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Update notification count after viewing message
    document.addEventListener('DOMContentLoaded', function() {
        // Update the badge count by decrementing it
        const badge = document.getElementById('messageBadge');
        if (badge) {
            const currentCount = parseInt(badge.textContent) || 0;
            if (currentCount > 0) {
                const newCount = currentCount - 1;
                if (newCount > 0) {
                    badge.textContent = newCount;
                } else {
                    badge.remove();
                }
            }
        }
    });
</script>
@endpush
@endsection

