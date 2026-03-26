@extends('user.layout')

@section('title', 'Notifications')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="mb-1" style="color:#2c3e50;font-weight:600;">Notifications</h2>
        <p class="text-muted mb-0">View and manage all your notifications.</p>
    </div>

    <!-- Filter and Search Section -->
    <div class="card border-c-blue shadow-sm mb-4" style="border-radius: 16px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('user.messages.index') }}" id="notificationFilterForm" class="theme-forms">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="search" class="form-label small mb-1">Search Notifications</label>
                        <input type="text" 
                               name="search" 
                               id="searchInput"
                               class="form-control form-control-sm" 
                               placeholder="Search in subject, message, or reply..."
                               value="{{ request('search') }}"
                               autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label for="filter" class="form-label small mb-1">Filter by Status</label>
                        <select name="filter" id="filterSelect" class="form-select form-select-sm">
                            <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>All ({{ $totalCount ?? 0 }})</option>
                            <option value="unread" {{ $filter === 'unread' ? 'selected' : '' }}>Unread ({{ $unreadCount ?? 0 }})</option>
                            <option value="read" {{ $filter === 'read' ? 'selected' : '' }}>Read ({{ $readCount ?? 0 }})</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <div class="d-flex gap-2">
                            <a href="{{ route('user.messages.index') }}" class="btn btn-danger">Clear</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
        <div class="card-body p-0">
            @if($messages->count() > 0)
                <div class="list-group list-group-flush flush-notification">
                    @foreach($messages as $message)
                        <a href="{{ route('user.messages.show', $message->id) }}" 
                           class="list-group-item list-group-item-action px-4 py-3 {{ !$message->is_read ? 'bg-light' : '' }}"
                           style="transition: background-color 0.2s;"
                           onmouseover="this.style.backgroundColor='{{ !$message->is_read ? '#e9ecef' : '#f8f9fa' }}'"
                           onmouseout="this.style.backgroundColor='{{ !$message->is_read ? '#f8f9fa' : 'transparent' }}'">
                            <div class="d-flex w-100 justify-content-between align-items-start flex-wrap">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        @if(!$message->is_read)
                                            <span class="badge bg-danger">New</span>
                                        @endif
                                        <h6 class="mb-0 fw-bold" style="color: #2c3e50;">{{ $message->subject }}</h6>
                                    </div>
                                    <p class="mb-2 text-muted small">{{ Str::limit($message->message, 150) }}</p>
                                    <small class="fw-bold text-blue mb-2 d-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#2B2F6C" class="me-1 fs-5" viewBox="0 0 16 16">
                                            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                                        </svg>
                                        From: {{ ucfirst($message->sent_by) }}
                                    </small>
                                </div>
                                <div class="text-xl-end ms-0 ms-xl-3">
                                    <small class="text-muted d-block">{{ $message->created_at->format('d M Y') }}</small>
                                    <small class="text-muted">{{ $message->created_at->format('h:i A') }}</small>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
                
                <div class="card-footer bg-transparent border-0">
                    <div class="d-flex justify-content-center">
                        {{ $messages->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#6c757d" viewBox="0 0 16 16" class="mb-3">
                        <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.597 2.32C2.653 13.08 2.814 14 3.443 14h9.114c.629 0 1.79-.92 1.499-1.938-.22-.753-.436-1.553-.597-2.32C13.134 8.197 13 6.628 13 6a4.002 4.002 0 0 0-3.203-3.92L9 1.917V.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.418ZM14 6c0 .711-.055 1.398-.156 2.044-.099.574-.236 1.118-.414 1.63C13.073 10.702 12.5 11 12 11H4c-.5 0-1.073-.298-1.43-.326-.178-.512-.315-1.056-.414-1.63C2.055 7.398 2 6.711 2 6a5 5 0 0 1 10 0Z"/>
                    </svg>
                    <p class="text-muted mb-0">No notifications found.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterSelect = document.getElementById('filterSelect');
    const filterForm = document.getElementById('notificationFilterForm');
    
    let searchTimeout;
    
    // Dynamic search - filters as user types (no button needed)
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            // Debounce search - wait 500ms after user stops typing
            searchTimeout = setTimeout(function() {
                filterForm.submit();
            }, 500);
        });
    }
    
    // Filter change - submit immediately
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            filterForm.submit();
        });
    }
});
</script>
@endpush
@endsection

