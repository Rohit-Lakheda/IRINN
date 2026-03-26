@extends('admin.layout')

@section('title', 'Requests & Messages')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1>Requests & Messages</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <!-- Search Form for Requests -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.requests-messages') }}" class="row g-2">
                    <div class="col-md-8">
                        <input type="text" 
                               name="requests_search" 
                               class="form-control form-control-sm" 
                               placeholder="Search requests by registration name, email, or ID..."
                               value="{{ request('requests_search') }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Search</button>
                    </div>
                    @if(request('requests_search'))
                        <div class="col-12">
                            <a href="{{ route('admin.requests-messages', ['messages_page' => request('messages_page')]) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                        </div>
                    @endif
                    @if(request('messages_search'))
                        <input type="hidden" name="messages_search" value="{{ request('messages_search') }}">
                    @endif
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">Profile Update Requests ({{ $profileUpdateRequests->total() }})</h5>
            </div>
            <div class="card-body">
                @if($profileUpdateRequests->count() > 0)
                    <div class="list-group">
                        @foreach($profileUpdateRequests as $request)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <a href="{{ route('admin.users.show', $request->user_id) }}" class="text-decoration-none">
                                                {{ $request->user->fullname }}
                                            </a>
                                            <span class="badge bg-{{ $request->status === 'pending' ? 'warning' : ($request->status === 'approved' ? 'success' : 'danger') }} ms-2">
                                                {{ $request->status }}
                                            </span>
                                        </h6>
                                        <p class="mb-1 text-muted small">
                                            <strong>Email:</strong> {{ $request->user->email }} | 
                                            <strong>Mobile:</strong> {{ $request->user->mobile }}
                                        </p>
                                        @if($request->requested_changes)
                                            <p class="mb-1">{{ Str::limit(is_array($request->requested_changes) ? json_encode($request->requested_changes) : $request->requested_changes, 150) }}</p>
                                        @endif
                                        <small class="text-muted">{{ $request->created_at->format('M d, Y h:i A') }}</small>
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.users.show', $request->user_id) }}" class="btn btn-sm btn-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $profileUpdateRequests->links('vendor.pagination.bootstrap-5') }}
                    </div>
                @else
                    <p class="text-muted">No pending profile update requests.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Search Form for Messages -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.requests-messages') }}" class="row g-2">
                    <div class="col-md-8">
                        <input type="text" 
                               name="messages_search" 
                               class="form-control form-control-sm" 
                               placeholder="Search messages by subject, content, or registration..."
                               value="{{ request('messages_search') }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Search</button>
                    </div>
                    @if(request('messages_search'))
                        <div class="col-12">
                            <a href="{{ route('admin.requests-messages', ['requests_page' => request('requests_page')]) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                        </div>
                    @endif
                    @if(request('requests_search'))
                        <input type="hidden" name="requests_search" value="{{ request('requests_search') }}">
                    @endif
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Recent Messages ({{ $messages->total() }})</h5>
            </div>
            <div class="card-body">
                @if($messages->count() > 0)
                    <div class="list-group">
                        @foreach($messages as $message)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <a href="{{ route('admin.users.show', $message->user_id) }}" class="text-decoration-none">
                                                {{ $message->user->fullname }}
                                            </a>
                                        </h6>
                                        <p class="mb-1"><strong>{{ $message->subject }}</strong></p>
                                        <p class="mb-1 text-muted small">{{ Str::limit($message->message, 100) }}</p>
                                        <small class="text-muted">{{ $message->created_at->format('M d, Y h:i A') }}</small>
                                        @if($message->is_read)
                                            <span class="badge bg-success ms-2">Read</span>
                                        @else
                                            <span class="badge bg-warning ms-2">Unread</span>
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.users.show', $message->user_id) }}" class="btn btn-sm btn-primary">View Registration</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $messages->links('vendor.pagination.bootstrap-5') }}
                    </div>
                @else
                    <p class="text-muted">No messages sent yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

