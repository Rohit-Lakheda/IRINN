@extends('admin.layout')

@section('title', 'Grievance Tickets')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="border-0">Assigned Grievance Tickets</h2>
            <div class="accent-line"></div>
        </div>
    </div>

    <!-- Filters and Search Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <form method="GET" action="{{ route('admin.grievance.index') }}" class="row g-3 theme-forms">
                        <!-- Search -->
                        <div class="col-md-12 mb-3">
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Search by ticket ID, subject, description, status, type, priority, or registration details..."
                                   value="{{ request('search') }}">
                        </div>
                        
                        <!-- Filters Row -->
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="">All Priorities</option>
                                <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                                <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Escalated</label>
                            <select name="escalated" class="form-select">
                                <option value="">All</option>
                                <option value="yes" {{ request('escalated') === 'yes' ? 'selected' : '' }}>Escalated</option>
                                <option value="no" {{ request('escalated') === 'no' ? 'selected' : '' }}>Not Escalated</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                                <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label small text-muted">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">Apply Filters</button>
                                <a href="{{ route('admin.grievance.index') }}" class="btn btn-danger">Clear</a>
                            </div>
                        </div>
                        
                        @if(request('search') || request('priority') || request('escalated') || request('status'))
                            <div class="col-12">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <small class="text-muted">Active filters:</small>
                                    @if(request('priority'))
                                        <span class="badge bg-info">Priority: {{ ucfirst(request('priority')) }}</span>
                                    @endif
                                    @if(request('escalated'))
                                        <span class="badge bg-warning">Escalated: {{ request('escalated') === 'yes' ? 'Yes' : 'No' }}</span>
                                    @endif
                                    @if(request('status'))
                                        <span class="badge bg-success">Status: {{ ucfirst(request('status')) }}</span>
                                    @endif
                                    @if(request('search'))
                                        <span class="badge bg-secondary">Search: {{ request('search') }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-c-blue shadow-sm">
        <div class="card-header theme-bg-blue">
            <h5 class="mb-0 text-capitalize">Tickets List</h5>
        </div>
        <div class="card-body">
            @if($tickets->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="text-nowrap">
                            <tr>
                                <th>Ticket ID</th>
                                <th>Registration</th>
                                <th>Type</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Created</th>
                                <th>Last Reply</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tickets as $ticket)
                            <tr class="align-middle">
                                <td><strong>{{ $ticket->ticket_id }}</strong></td>
                                <td>{{ $ticket->user->fullname ?? 'N/A' }}</td>
                                <td><span class="badge bg-info">{{ $ticket->type_display }}</span></td>
                                <td>{{ $ticket->subject ?? \Illuminate\Support\Str::limit($ticket->description, 40) }}</td>
                                <td>
                                    <span class="badge bg-{{ $ticket->status === 'closed' ? 'secondary' : ($ticket->status === 'resolved' ? 'success' : ($ticket->status === 'in_progress' ? 'warning' : 'dark theme-bg-blue text-white')) }}">
                                        {{ $ticket->status_display }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <span class="badge bg-{{ $ticket->priority_badge_color }} text-center justify-content-center">
                                            {{ ucfirst($ticket->priority) }}
                                        </span>
                                        @if($ticket->escalation_level !== 'none')
                                            <span class="badge bg-danger">
                                                @if($ticket->escalation_level === 'ix_head')
                                                    ⚠️ Escalated
                                                @elseif($ticket->escalation_level === 'ceo')
                                                    🔴 Escalated to CEO
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $ticket->created_at->format('d M Y') }}</td>
                                <td>
                                    @if($ticket->messages->count() > 0)
                                        {{ $ticket->messages->last()->created_at->format('d M Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.grievance.show', $ticket->id) }}" class="btn btn-sm btn-primary">View</a>
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
                    <p class="text-muted">No tickets assigned to you yet.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

