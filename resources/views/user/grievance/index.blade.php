@extends('user.layout')

@section('title', 'My Grievances')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>My Grievances</h2>
                <div class="accent-line"></div>
            </div>
            <a href="{{ route('user.grievance.create') }}" class="btn btn-primary ms-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                </svg>
                Submit New Grievance
            </a>
        </div>

        <div class="card border-c-blue shadow-sm">
            <div class="card-header theme-bg-blue">
                <h5 class="mb-0">My Grievances / Tickets</h5>
            </div>
            <div class="card-body">
                @if($tickets->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr class="text-nowrap">
                                    <th>Ticket ID</th>
                                    <th>Type</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Assigned To</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tickets as $ticket)
                                <tr class="align-middle">
                                    <td><strong>{{ $ticket->ticket_id }}</strong></td>
                                    <td>
                                        <span class="badge bg-info">{{ $ticket->type_display }}</span>
                                    </td>
                                    <td>
                                        {{ $ticket->subject ?? \Illuminate\Support\Str::limit($ticket->description, 50) }}
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $ticket->status === 'closed' ? 'secondary' : ($ticket->status === 'resolved' ? 'success' : ($ticket->status === 'in_progress' ? 'warning' : 'primary')) }}">
                                            {{ $ticket->status_display }}
                                        </span>
                                    </td>
                                <td>
                                    <span class="badge bg-{{ $ticket->priority_badge_color }}">
                                        {{ ucfirst($ticket->priority) }}
                                    </span>
                                    @if($ticket->escalation_level !== 'none')
                                    <br>
                                    <span class="badge bg-danger mt-1">
                                        @if($ticket->escalation_level === 'ix_head')
                                            ⚠️ Escalated to IRINN Head
                                        @elseif($ticket->escalation_level === 'ceo')
                                            🔴 Escalated to CEO
                                        @endif
                                    </span>
                                    @endif
                                </td>
                                    <td>
                                        {{ $ticket->assignedAdmin ? $ticket->assignedAdmin->name : 'Not Assigned' }}
                                    </td>
                                    <td>{{ $ticket->created_at->format('d M Y, h:i A') }}</td>
                                    <td>
                                        <a href="{{ route('user.grievance.show', $ticket->id) }}" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $tickets->links('vendor.pagination.bootstrap-5') }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" viewBox="0 0 16 16" class="text-muted mb-3">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                        </svg>
                        <p class="text-muted">No grievances submitted yet.</p>
                        <a href="{{ route('user.grievance.create') }}" class="btn btn-primary mt-2">Submit Your First Grievance</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

