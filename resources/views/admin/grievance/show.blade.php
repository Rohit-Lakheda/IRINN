@extends('admin.layout')

@section('title', 'Ticket Details')

@section('content')
<div class="container-fluid">
    <!-- Ticket Header -->
    <div class="card mb-3 border-c-blue shadow-sm">
        <div class="card-header theme-bg-blue text-white">
            <div class="d-flex justify-content-between align-items-center" id="ui-ticket-status">
                <h5 class="mb-0">Ticket: {{ $ticket->ticket_id }}</h5>
                <span class="badge text-capitalize bg-{{ $ticket->status === 'closed' ? 'secondary' : ($ticket->status === 'resolved' ? 'success' : ($ticket->status === 'in_progress' ? 'warning' : 'light')) }}">
                    {{ $ticket->status_display }}
                </span>
            </div>
        </div>
    </div>

    <!-- User Information Cards -->
    <div class="row mb-3">
        <div class="col-md-6 mb-3">
            <div class="card border-c-blue shadow-sm h-100">
                <div class="card-header theme-bg-blue">
                    <h6 class="mb-0 fw-semibold text-capitalize">User Information</h6>
                </div>
                <div class="card-body">
                    <div class="row app-details">
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">User Name</label>
                            <div>
                                <a href="{{ route('admin.users.show', $ticket->user_id) }}" class="text-decoration-none fw-bold">
                                    {{ $ticket->user->fullname ?? 'N/A' }}
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Email</label>
                            <div>{{ $ticket->user->email ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Mobile</label>
                            <div>{{ $ticket->user->mobile ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Create Date</label>
                            <div>{{ $ticket->created_at->format('d M Y, h:i A') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Assigned Date</label>
                            <div>{{ $ticket->assigned_at ? $ticket->assigned_at->format('d M Y, h:i A') : 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ticket Details Card -->
        <div class="col-md-6 mb-3">
            <div class="card border-c-blue shadow-sm h-100">
                <div class="card-header theme-bg-blue">
                    <h6 class="mb-0 fw-semibold text-capitalize">Ticket Details</h6>
                </div>
                <div class="card-body">
                    <div class="row app-details">
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Category</label>
                            <div>
                                @if($ticket->category)
                                    <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $ticket->category)) }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ $ticket->type_display }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Sub Category</label>
                            <div>
                                @if($ticket->sub_category)
                                    <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $ticket->sub_category)) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Priority</label>
                            <div>
                                <span class="badge bg-{{ $ticket->priority_badge_color }}">{{ ucfirst($ticket->priority) }}</span>
                                @if($ticket->escalation_level !== 'none')
                                    <span class="badge bg-danger ms-1">
                                        @if($ticket->escalation_level === 'ix_head')
                                            Escalated
                                        @elseif($ticket->escalation_level === 'ceo')
                                            Escalated to CEO
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Assigned Role</label>
                            <div>
                                @if($ticket->assigned_role)
                                    <span class="badge theme-bg-blue text-white">{{ ucfirst(str_replace('_', ' ', $ticket->assigned_role)) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                        </div>
                        @if($ticket->subject)
                        <div class="col-md-12">
                            <label class="small text-muted mb-1 d-block">Subject</label>
                            <div class="fw-medium">{{ $ticket->subject }}</div>
                        </div>
                        @endif
                        <div class="col-md-12 mb-0 pb-0">
                            <label class="small text-muted mb-1 d-block">Message</label>
                            <div class="text-break">{{ nl2br(e($ticket->description)) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Ticket Information -->
    @if($ticket->forwarded_at || $ticket->escalated_at || $ticket->closed_at)
    <div class="row mb-3">
        <div class="col-md-6 mb-3">
            <div class="card border-c-blue shadow-sm">
                <div class="card-header theme-bg-blue">
                    <h6 class="mb-0 fw-semibold text-capitalize">Ticket History</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if($ticket->forwarded_at)
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Forwarded</label>
                            <div>{{ $ticket->forwarded_at->format('d M Y, h:i A') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Forwarded By</label>
                            <div>{{ $ticket->forwardedBy->name ?? 'N/A' }}</div>
                        </div>
                        @endif
                        @if($ticket->escalated_at)
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Escalated</label>
                            <div>{{ $ticket->escalated_at->format('d M Y, h:i A') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Escalated To</label>
                            <div>{{ $ticket->escalatedTo->name ?? 'N/A' }}</div>
                        </div>
                        @endif
                        @if($ticket->closed_at)
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Closed</label>
                            <div>{{ $ticket->closed_at->format('d M Y, h:i A') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Conversation Thread -->
    <div class="card border-c-blue mb-3 shadow-sm">
        <div class="card-header theme-bg-blue text-white">
            <h5 class="mb-0 fw-semibold text-capitalize">Conversation</h5>
        </div>
        <div class="card-body">
            <div class="conversation-thread">
                @forelse($ticket->messages as $message)
                <div class="message-item mb-3 p-3 rounded {{ $message->sender_type === 'user' ? 'bg-light' : ($message->is_internal ? 'bg-warning bg-opacity-10' : 'bg-primary bg-opacity-10') }}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <strong class="me-2">{{ $message->sender_name }}</strong>
                            <span class="badge bg-secondary">{{ ucfirst($message->sender_type) }}</span>
                            @if($message->is_internal)
                            <span class="badge bg-warning ms-1">Internal Note</span>
                            @endif
                        </div>
                        <small class="text-muted">{{ $message->created_at->format('d M Y, h:i A') }}</small>
                    </div>
                    <div class="message-content">
                        <p class="mb-2">{{ nl2br(e($message->message)) }}</p>
                        
                        @if($message->attachments->count() > 0)
                        <div class="attachments mt-2">
                            <strong>Attachments:</strong>
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                @foreach($message->attachments as $attachment)
                                <a href="{{ route('admin.grievance.attachments.download', $attachment->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    {{ $attachment->file_name }}
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <p>No messages yet.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Forward Ticket Form -->
    @if($ticket->status !== 'closed' && !empty($forwardableRoles))
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="card border-c-blue shadow-sm">
                <div class="card-header theme-bg-blue">
                    <h6 class="mb-0 fw-semibold text-capitalize">Forward Ticket</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.grievance.forward', $ticket->id) }}" class="theme-forms">
                        @csrf
                        <div class="mb-3">
                            <label for="target_role" class="form-label small">Forward To Role <span class="text-danger">*</span></label>
                            <select name="target_role" id="target_role" class="form-select @error('target_role') is-invalid @enderror" required>
                                <option value="">Select Role</option>
                                @foreach($forwardableRoles as $roleSlug => $roleName)
                                    <option value="{{ $roleSlug }}">{{ $roleName }}</option>
                                @endforeach
                            </select>
                            @error('target_role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="forwarding_notes" class="form-label small">Forwarding Notes (Optional)</label>
                            <textarea name="forwarding_notes" id="forwarding_notes" rows="3" class="form-control @error('forwarding_notes') is-invalid @enderror" placeholder="Add any notes about why you are forwarding this ticket..."></textarea>
                            @error('forwarding_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn theme-bg-blue text-white" onclick="return confirm('Are you sure you want to forward this ticket?')">Forward Ticket</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reply Form -->
        <div class="col-md-6">
            <div class="card border-c-blue shadow-sm">
                <div class="card-header theme-bg-blue">
                    <h6 class="mb-0 fw-semibold text-capitalize">Reply</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.grievance.reply', $ticket->id) }}" enctype="multipart/form-data" class="theme-forms">
                        @csrf
                        <div class="mb-3">
                            <label for="message" class="form-label small">Your Message <span class="text-danger">*</span></label>
                            <textarea name="message" id="message" rows="4" class="form-control @error('message') is-invalid @enderror" required></textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_internal" id="is_internal" value="1">
                                <label class="form-check-label small" for="is_internal">
                                    Internal Note (visible only to admins)
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="attachments" class="form-label small">Attachments</label>
                            <input type="file" name="attachments[]" id="attachments" class="form-control form-control-sm" multiple accept="image/*,.pdf,.doc,.docx">
                            <small class="form-text text-muted">Maximum file size: 10MB per file.</small>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary btn-sm">Send Reply</button>
                            @if($ticket->status !== 'resolved' && $ticket->status !== 'closed')
                            <form method="POST" action="{{ route('admin.grievance.resolve', $ticket->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Mark this ticket as resolved?')">Mark as Resolved</button>
                            </form>
                            @endif
                            @if($ticket->status !== 'closed')
                            <form method="POST" action="{{ route('admin.grievance.close', $ticket->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to close this ticket? This action cannot be undone.')">Close Ticket</button>
                            </form>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @elseif($ticket->status !== 'closed')
    <!-- Reply Form Only (when forward is not available) -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Reply</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.grievance.reply', $ticket->id) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="message" class="form-label small">Your Message <span class="text-danger">*</span></label>
                            <textarea name="message" id="message" rows="4" class="form-control @error('message') is-invalid @enderror" required></textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_internal" id="is_internal" value="1">
                                <label class="form-check-label small" for="is_internal">
                                    Internal Note (visible only to admins)
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="attachments" class="form-label small">Attachments</label>
                            <input type="file" name="attachments[]" id="attachments" class="form-control form-control-sm" multiple accept="image/*,.pdf,.doc,.docx">
                            <small class="form-text text-muted">Maximum file size: 10MB per file.</small>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary btn-sm">Send Reply</button>
                            @if($ticket->status !== 'resolved' && $ticket->status !== 'closed')
                            <form method="POST" action="{{ route('admin.grievance.resolve', $ticket->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Mark this ticket as resolved?')">Mark as Resolved</button>
                            </form>
                            @endif
                            @if($ticket->status !== 'closed')
                            <form method="POST" action="{{ route('admin.grievance.close', $ticket->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Are you sure you want to close this ticket? This action cannot be undone.')">Close Ticket</button>
                            </form>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="alert alert-info">
        <strong>This ticket is closed.</strong> You cannot reply to closed tickets.
    </div>
    @endif
</div>
@endsection
