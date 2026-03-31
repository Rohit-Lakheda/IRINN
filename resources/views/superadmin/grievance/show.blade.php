@extends('superadmin.layout')

@section('title', 'Ticket Details')

@section('content')
<div class="container-fluid px-2 py-0">
    <div class="row mb-3">
        <div class="col-12 text-end">
            <a href="{{ route('superadmin.grievance.index') }}" class="btn btn-primary"><i class="bi bi-arrow-left fs-6"></i> Back to Tickets</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-primary">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="mb-0">Ticket: {{ $ticket->ticket_id }}</h5>
                <span class="badge bg-{{ $ticket->status === 'closed' ? 'secondary' : ($ticket->status === 'resolved' ? 'success' : ($ticket->status === 'in_progress' ? 'warning' : 'primary')) }}">
                    {{ $ticket->status_display }}
                </span>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-4 col-lg-4">
                            <p class="mb-1"><strong>Registration:</strong></p>
                        </div>
                        <div class="col-md-8 col-lg-8">
                            <p class="mb-2">{{ $ticket->user->fullname ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 col-lg-4">
                            <p class="mb-1"><strong>Email:</strong></p>
                        </div>
                        <div class="col-md-8 col-lg-8">
                            <p class="mb-2">{{ $ticket->user->email ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 col-lg-4">
                            <p class="mb-1"><strong>Type:</strong></p>
                        </div>
                        <div class="col-md-8 col-lg-8">
                            <p class="mb-2"><span class="badge bg-info">{{ $ticket->type_display }}</span></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 col-lg-4">
                            <p class="mb-1"><strong>Priority:</strong></p>
                        </div>
                        <div class="col-md-8 col-lg-8">
                            <p class="mb-2"><span class="badge bg-{{ $ticket->priority_badge_color }}">{{ ucfirst($ticket->priority) }}</span></p>
                        </div>
                    </div>
                    @if($ticket->escalation_level !== 'none')
                    <div class="row">
                        <div class="col-md-4 col-lg-4">
                            <p class="mb-1"><strong>Escalation:</strong></p>
                        </div>
                        <div class="col-md-8 col-lg-8">
                            <p class="mb-2">
                                <span class="badge bg-danger">
                                    @if($ticket->escalation_level === 'ix_head')
                                        ⚠️ Escalated to Level 1 role
                                    @elseif($ticket->escalation_level === 'ceo')
                                        🔴 Escalated to Level 2 role
                                    @endif
                                </span>
                            </p>
                        </div>
                    </div>
                    @endif
                </div>
                
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-4 col-lg-4">
                            <p class="mb-1"><strong>Created:</strong></p>
                        </div>
                        <div class="col-md-8 col-lg-8">
                            <p class="mb-2">{{ $ticket->created_at->format('d M Y, h:i A') }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 col-lg-4">
                            <p class="mb-1"><strong>Assigned To:</strong></p>
                        </div>
                        <div class="col-md-8 col-lg-8">
                            <p class="mb-2">{{ $ticket->assignedAdmin ? $ticket->assignedAdmin->name : 'Not Assigned' }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 col-lg-4">
                            <p class="mb-1"><strong>Assigned By:</strong></p>
                        </div>
                        <div class="col-md-8 col-lg-8">
                            <p class="mb-2">{{ $ticket->assignedBy ? $ticket->assignedBy->name : 'N/A' }}</p>
                        </div>
                    </div>
                    @if($ticket->escalated_at)
                        <div class="row">
                            <div class="col-md-4 col-lg-4">
                                <p class="mb-1"><strong>Escalated:</strong></p>
                            </div>
                            <div class="col-md-8 col-lg-8">
                                <p class="mb-2">{{ $ticket->escalated_at->format('d M Y, h:i A') }}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 col-lg-4">
                                <p class="mb-1"><strong>Escalated To:</strong></p>
                            </div>
                            <div class="col-md-8 col-lg-8">
                                <p class="mb-2">{{ $ticket->escalatedTo->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        @if($ticket->escalation_notes)
                            <div class="row">
                                <div class="col-md-4 col-lg-4">
                                    <p class="mb-1"><strong>Escalation Notes:</strong></p>
                                </div>
                                <div class="col-md-8 col-lg-8">
                                    <p class="mb-2">{{ $ticket->escalation_notes }}</p>
                                </div>
                            </div>
                        @endif
                    @endif
                    @if($ticket->subject)
                    <div class="row">
                        <div class="col-md-4 col-lg-4">
                            <p class="mb-1"><strong>Subject:</strong></p>
                        </div>
                        <div class="col-md-8 col-lg-8">
                            <p class="mb-2">{{ $ticket->subject }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            <hr>
            <div class="mb-3">
                <strong>Initial Description:</strong>
                <p class="mt-2">{{ nl2br(e($ticket->description)) }}</p>
            </div>
        </div>
    </div>

    <!-- Assignment Form -->
    @if($ticket->status !== 'closed')
    <div class="card mb-3">
        <div class="card-header bg-primary">
            <h5 class="mb-0">Assign Ticket</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('superadmin.grievance.assign', $ticket->id) }}" class="theme-forms">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="assigned_to" class="form-label">Assign To Admin <span class="text-danger">*</span></label>
                        <select name="assigned_to" id="assigned_to" class="form-select pe-5 @error('assigned_to') is-invalid @enderror" required>
                            <option value="">Select Admin</option>
                            @foreach($admins as $admin)
                                <option value="{{ $admin->id }}" {{ $ticket->assigned_to == $admin->id ? 'selected' : '' }}>
                                    {{ $admin->name }} 
                                    @if($admin->roles->count() > 0)
                                        ({{ $admin->roles->pluck('name')->join(', ') }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="priority" class="form-label">Priority</label>
                        <select name="priority" id="priority" class="form-select">
                            <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ $ticket->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{ $ticket->priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Assign</button>
                    </div>
                </div>
            </form>
            @if($ticket->assigned_to)
            <form method="POST" action="{{ route('superadmin.grievance.unassign', $ticket->id) }}" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-danger" onclick="return confirm('Unassign this ticket?')">Unassign</button>
            </form>
            @endif
        </div>
    </div>
    @endif

    <!-- Conversation Thread -->
    <div class="card mb-3">
        <div class="card-header bg-primary">
            <h5 class="mb-0">Conversation</h5>
        </div>
        <div class="card-body">
            <div class="conversation-thread super-conversation-thread">
                @foreach($ticket->messages as $message)
                <div class="message-item mb-4 p-3 rounded {{ $message->sender_type === 'user' ? 'bg-light' : ($message->is_internal ? 'bg-warning bg-opacity-10' : 'bg-primary bg-opacity-10') }}">
                    <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap">
                        <div>
                            <strong class="me-2">{{ $message->sender_name }}</strong>
                            <span class="badge bg-secondary me-2">{{ ucfirst($message->sender_type) }}</span>
                            @if($message->is_internal)
                            <span class="badge bg-warning ps-0">Internal Note</span>
                            @endif
                        </div>
                        <small class="text-muted text-white">{{ $message->created_at->format('d M Y, h:i A') }}</small>
                    </div>
                    <div class="message-content">
                        <p class="mb-2 messg">{{ nl2br(e($message->message)) }}</p>
                        
                        @if($message->attachments->count() > 0)
                        <div class="attachments mt-2">
                            <strong>Attachments:</strong>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @foreach($message->attachments as $attachment)
                                <a href="{{ route('superadmin.grievance.attachments.download', $attachment->id) }}" target="_blank" class="btn btn-sm border-white text-white text-capitalize">
                                    {{ $attachment->file_name }}
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

