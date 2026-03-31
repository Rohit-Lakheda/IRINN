@extends('user.layout')

@section('title', 'Ticket Details')

@section('content')
<div class="container-fluid view-grievances">
    <!-- Ticket Header -->
    <div class="card mb-3 border-c-blue shadow-sm">
        <div class="card-header theme-bg-blue">
            <div class="d-flex justify-content-between align-items-center ticket-status">
                <h5 class="mb-0">Ticket: {{ $ticket->ticket_id }}</h5>
                <span class="badge bg-{{ $ticket->status === 'closed' ? 'secondary' : ($ticket->status === 'resolved' ? 'success' : ($ticket->status === 'in_progress' ? 'warning' : 'light text-dark')) }}">
                    {{ $ticket->status_display }}
                </span>
            </div>
        </div>
    </div>

    <!-- Ticket Information Cards -->
    <div class="row mb-3">
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-c-blue h-100">
                <div class="card-header theme-bg-blue">
                    <h6 class="mb-0">Ticket Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 app-details">
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Type</label>
                            <div>
                                <span class="badge bg-info text-capitalize">{{ $ticket->type_display }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Priority</label>
                            <div>
                                <span class="badge bg-{{ $ticket->priority_badge_color }} text-capitalize">{{ ucfirst($ticket->priority) }}</span>
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
                        @if($ticket->category)
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Category</label>
                            <div>
                                <span class="badge bg-info text-capitalize">{{ ucfirst(str_replace('_', ' ', $ticket->category)) }}</span>
                            </div>
                        </div>
                        @endif
                        @if($ticket->sub_category)
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Sub Category</label>
                            <div>
                                <span class="badge bg-info text-capitalize">{{ ucfirst(str_replace('_', ' ', $ticket->sub_category)) }}</span>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Created Date</label>
                            <div>{{ $ticket->created_at->format('d M Y, h:i A') }}</div>
                        </div>
                        @if($ticket->assigned_at)
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Assigned Date</label>
                            <div>{{ $ticket->assigned_at->format('d M Y, h:i A') }}</div>
                        </div>
                        @endif
                        @if($ticket->subject)
                        <div class="col-md-12">
                            <label class="small text-muted mb-1 d-block">Subject</label>
                            <div class="fw-medium">{{ $ticket->subject }}</div>
                        </div>
                        @endif
                        <div class="col-md-12 mb-0">
                            <label class="small text-muted mb-1 d-block">Message</label>
                            <div class="text-break">{{ nl2br(e($ticket->description)) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignment & Status Card -->
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-c-blue h-100">
                <div class="card-header theme-bg-blue">
                    <h6 class="mb-0">Assignment & Status</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 app-details">
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Assigned To</label>
                            <div>
                                @if($ticket->assigned_role)
                                    <span class="badge theme-bg-green text-capitalize">{{ ucfirst(str_replace('_', ' ', $ticket->assigned_role)) }}</span>
                                @else
                                    <span class="text-muted">Not Assigned</span>
                                @endif
                            </div>
                        </div>
                        @if($ticket->assigned_role)
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Assigned Role</label>
                            <div>
                                <span class="badge theme-bg-green text-capitalize">{{ ucfirst(str_replace('_', ' ', $ticket->assigned_role)) }}</span>
                            </div>
                        </div>
                        @endif
                        @if($ticket->escalated_at)
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Escalated Date</label>
                            <div>{{ $ticket->escalated_at->format('d M Y, h:i A') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Escalated To</label>
                            <div>{{ $ticket->escalatedTo->name ?? 'N/A' }}</div>
                        </div>
                        @endif
                        @if($ticket->resolved_at)
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Resolved Date</label>
                            <div>{{ $ticket->resolved_at->format('d M Y, h:i A') }}</div>
                        </div>
                        @endif
                        @if($ticket->closed_at)
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">Closed Date</label>
                            <div>{{ $ticket->closed_at->format('d M Y, h:i A') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Conversation Thread -->
    <div class="card mb-3 border-c-blue shadow-sm">
        <div class="card-header theme-bg-blue">
            <h5 class="mb-0">Conversation</h5>
        </div>
        <div class="card-body">
            <div class="conversation-thread">
                @php
                    $visibleMessages = $ticket->messages->reject(fn ($m) => (bool) $m->is_internal)->values();
                @endphp

                @forelse($visibleMessages as $message)
                <div class="message-item mb-3 p-3 rounded {{ $message->sender_type === 'user' ? 'alternative-light' : 'alter-light bg-opacity-10' }}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            @if($message->sender_type === 'user')
                                <strong>{{ $message->sender_name }}</strong>
                            @else
                                <strong>
                                    {{ $ticket->assigned_role ? ucfirst(str_replace('_', ' ', $ticket->assigned_role)) : 'Assigned Admin' }}
                                </strong>
                            @endif
                            <span class="badge bg-secondary ms-2">{{ ucfirst($message->sender_type) }}</span>
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
                                <a href="{{ route('user.grievance.attachments.download', $attachment->id) }}" target="_blank" class="btn btn-sm theme-bg-blue text-white">
                                    @if($attachment->is_image)
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                            <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                            <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
                                        </svg>
                                    @elseif($attachment->is_pdf)
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                            <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                                        </svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                            <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z"/>
                                        </svg>
                                    @endif
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

    <!-- Reply Form -->
    @if($ticket->status !== 'closed')
    <div class="card border-c-blue shadow-sm">
        <div class="card-header theme-bg-blue">
            <h6 class="mb-0">Reply</h6>
        </div>
        <div class="card-body theme-forms">
            <form method="POST" action="{{ route('user.grievance.reply', $ticket->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="message" class="form-label small">Your Message <span class="text-danger">*</span></label>
                    <textarea name="message" id="message" rows="4" class="form-control @error('message') is-invalid @enderror" required></textarea>
                    @error('message')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="attachments" class="form-label small">Attachments</label>
                    <input type="file" name="attachments[]" id="attachments" class="form-control form-control-sm" multiple accept="image/*,.pdf,.doc,.docx">
                    <small class="form-text text-muted">Maximum file size: 10MB per file.</small>
                </div>
                <button type="submit" class="btn btn-primary">Send Reply</button>
            </form>
        </div>
    </div>
    @else
    <div class="alert alert-info">
        <strong>This ticket is closed.</strong> You cannot reply to closed tickets. Please create a new ticket if you need further assistance.
    </div>
    @endif
</div>
@endsection
