@extends('admin.layout')

@section('title', 'Messages')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1 fw-semibold border-0">Messages</h2>
            <p class="text-muted mb-0">Your messages and user replies</p>
            <div class="accent-line"></div>
        </div>
    </div>

    <!-- Search Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <form method="GET" action="{{ route('admin.messages') }}" class="row g-3 theme-forms">
                        <div class="col-md-10">
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Search by subject, message, user reply, registration name, email, or registration ID..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                        @if(request('search'))
                            <div class="col-12">
                                <a href="{{ route('admin.messages') }}" class="btn btn-sm btn-danger">Clear Search</a>
                                <small class="text-muted ms-2">Showing results for: <strong>{{ request('search') }}</strong></small>
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
                    <h5 class="mb-0 fw-semibold text-capitalize">Messages ({{ $messages->total() }})</h5>
                </div>
                <div class="card-body">
                    @if($messages->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="text-nowrap">
                                    <tr>
                                        <th style="color: #2c3e50; font-weight: 600;">User</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Subject</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Message Preview</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Status</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Sent At</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Read At</th>
                                        <th class="text-end pe-3" style="color: #2c3e50; font-weight: 600;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($messages as $message)
                                    <tr class="align-middle">
                                        <td>
                                            <div>
                                                <a href="{{ route('admin.users.show', $message->user_id) }}" style="color: #0d6efd; text-decoration: none; font-weight: 500;">
                                                    {{ $message->user->fullname }}
                                                </a>
                                            </div>
                                            <div class="text-muted small">{{ $message->user->email }}</div>
                                        </td>
                                        <td style="color: #2c3e50;">{{ $message->subject }}</td>
                                        <td>
                                            <div style="max-width: 300px; color: #2c3e50;">
                                                {{ \Illuminate\Support\Str::limit($message->message, 100) }}
                                            </div>
                                            @if($message->user_reply)
                                                <div class="mt-2 p-2 bg-info bg-opacity-10 rounded" style="max-width: 300px;">
                                                    <small class="text-muted d-block mb-1"><strong>User Reply:</strong></small>
                                                    <small style="color: #2c3e50;">{{ \Illuminate\Support\Str::limit($message->user_reply, 80) }}</small>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($message->admin_read)
                                                <span class="badge bg-success text-capitalize">Read</span>
                                            @else
                                                <span class="badge theme-bg-yellow text-blue text-capitalize">Unread</span>
                                            @endif
                                        </td>
                                        <td style="color: #2c3e50;">
                                            <div>{{ $message->created_at->format('d M Y') }}</div>
                                            <small class="text-muted">{{ $message->created_at->format('h:i A') }}</small>
                                        </td>
                                        <td style="color: #2c3e50;">
                                            @if($message->admin_read_at)
                                                <div>{{ $message->admin_read_at->format('d M Y') }}</div>
                                                <small class="text-muted">{{ $message->admin_read_at->format('h:i A') }}</small>
                                            @else
                                                <span class="text-muted">Not read</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.messages.show', $message->id) }}" class="btn btn-sm btn-primary">View Details</a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 d-flex justify-content-center">
                            {{ $messages->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" viewBox="0 0 16 16" class="text-muted mb-3">
                                <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                            </svg>
                            <p class="text-muted">No messages found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

