@extends('superadmin.layout')

@section('title', 'Messages')

@section('content')
<div class="container-fluid px-2 py-0">
    <!-- Page Header -->
    <div class="mb-2">
        <h2 class="mb-1 border-0">All Messages</h2>
        <p class="text-muted mb-0">View and search all messages between admins and registrations</p>
    </div>
    <div class="accent-line"></div>

    <!-- Search and Filter -->
    <div class="card border-c-blue shadow-sm mb-4 mt-4" style="border-radius: 16px;">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('superadmin.messages') }}" class="row g-3 theme-forms">
                <div class="col-md-8">
                    <input type="text" 
                           name="search" 
                           class="form-control" 
                           placeholder="Search by registration name, email, admin name, subject, or message content..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="sent_by" class="form-select">
                        <option value="">All Senders</option>
                        <option value="admin" {{ request('sent_by') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="user" {{ request('sent_by') === 'user' ? 'selected' : '' }}>Registration</option>
                        <option value="system" {{ request('sent_by') === 'system' ? 'selected' : '' }}>System</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Messages Table -->
    <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
        <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
            <h5 class="mb-0 fw-semibold">Messages ({{ $messages->total() }})</h5>
        </div>
        <div class="card-body">
            @if($messages->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="text-nowrap">
                            <tr>
                                <th>Registration</th>
                                <th>Subject</th>
                                <th>Message Preview</th>
                                <th>From</th>
                                <th>Time</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($messages as $message)
                            <tr class="align-middle">
                                <td>
                                    <a href="{{ route('superadmin.users.show', $message->user_id) }}" class="text-decoration-none" style="color: #2c3e50; font-weight: 500;">
                                        {{ $message->user->fullname }}
                                    </a>
                                    <br>
                                    <small class="text-muted">{{ $message->user->email }}</small>
                                </td>
                                <td>{{ $message->subject }}</td>
                                <td>
                                    <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ \Illuminate\Support\Str::limit($message->message, 100) }}
                                    </div>
                                    @if($message->user_reply)
                                        <div class="mt-2 p-2 bg-light rounded" style="max-width: 300px;">
                                            <small class="text-muted d-block mb-1"><strong>Registration Reply:</strong></small>
                                            <small>{{ \Illuminate\Support\Str::limit($message->user_reply, 80) }}</small>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($message->sent_by === 'admin')
                                        @php
                                            $adminAction = $adminActions[$message->id] ?? null;
                                        @endphp
                                        @if($adminAction && $adminAction->admin)
                                            <span class="badge rounded-pill px-3 py-1 bg-primary">
                                                {{ $adminAction->admin->name }}
                                            </span>
                                        @else
                                            <span class="badge rounded-pill px-3 py-1 bg-primary">
                                                Admin
                                            </span>
                                        @endif
                                    @else
                                        <span class="badge rounded-pill px-3 py-1 bg-info">
                                            {{ ucfirst($message->sent_by) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $message->created_at->format('M d, Y h:i A') }}</small>
                                    <br>
                                    <small class="text-muted">{{ $message->created_at->diffForHumans() }}</small>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('superadmin.messages.show', $message->id) }}" class="btn btn-sm btn-primary">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-top-0" style="border-radius: 0 0 16px 16px;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <small class="text-muted text-blue fw-semibold">
                                Showing {{ $messages->firstItem() }} to {{ $messages->lastItem() }} of {{ $messages->total() }} messages
                            </small>
                        </div>
                        <div class="d-flex justify-content-center">
                            {{ $messages->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" class="mb-2" viewBox="0 0 16 16">
                        <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                    </svg>
                    <p class="text-muted mb-0">No messages found.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

