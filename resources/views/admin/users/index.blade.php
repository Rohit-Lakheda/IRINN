@extends('admin.layout')

@section('title', 'All Registration')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="flex-wrap">
                    <h2 class="mb-1 border-0">All Registration</h2>
                    <p class="mb-2 text-muted">Manage and view all user registrations</p>
                </div>
            </div>
            <div class="accent-line"></div>
        </div>
    </div>

    <!-- Search Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm users-search-card" style="border-radius: 16px;">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('admin.users') }}" class="d-flex gap-2 theme-forms">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search by name, email, mobile, PAN, registration ID, or status..."
                               value="{{ request('search') }}"
                               autocomplete="off">
                        <button type="submit" class="btn users-search-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                            </svg>
                            Search
                        </button>
                        <a href="{{ route('admin.users.export', request()->all()) }}" class="btn users-export-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8.5 6a.5.5 0 0 0-1 0v1.5H6a.5.5 0 0 0 0 1h1.5V10a.5.5 0 0 0 1 0V8.5H10a.5.5 0 0 0 0-1H8.5V6z"/>
                                <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2zm10-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1z"/>
                            </svg>
                            Export to Excel
                        </a>
                        @if(request('search'))
                            <a href="{{ route('admin.users') }}" class="btn users-clear-btn">Clear</a>
                        @endif
                    </form>
                    @if(request('search'))
                        <div class="mt-2">
                            <small class="text-muted">Showing results for: <strong>{{ request('search') }}</strong></small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header users-card-header d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 text-capitalize text-white">Registration List</h5>
                    <form method="GET" action="{{ route('admin.users') }}" class="d-inline">
                        @if(request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        <select name="per_page" class="form-select form-select-sm users-per-page-select" onchange="this.form.submit()" style="color: #1e3a8a !important;">
                            <option value="10" {{ (string)request('per_page', 20) === '10' ? 'selected' : '' }} style="color: #1e3a8a !important;">10</option>
                            <option value="20" {{ (string)request('per_page', 20) === '20' ? 'selected' : '' }} style="color: #1e3a8a !important;">20</option>
                            <option value="50" {{ (string)request('per_page', 20) === '50' ? 'selected' : '' }} style="color: #1e3a8a !important;">50</option>
                            <option value="100" {{ (string)request('per_page', 20) === '100' ? 'selected' : '' }} style="color: #1e3a8a !important;">100</option>
                        </select>
                    </form>
                </div>
                <div class="card-body">
                    @if($users->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="text-nowrap users-table-header">
                                    <tr>
                                        <th>Registration ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Status</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                    <tr class="align-middle">
                                        <td><strong style="color: #1e3a8a;">{{ $user->registrationid }}</strong></td>
                                        <td style="font-size: 0.875rem; color: #1e3a8a;">{{ $user->fullname }}</td>
                                        <td style="font-size: 0.875rem; word-break: break-all; color: #1e3a8a;">{{ $user->email }}</td>
                                        <td style="font-size: 0.875rem; color: #1e3a8a;">{{ $user->mobile }}</td>
                                        <td>
                                            @if($user->status === 'approved')
                                                <span class="badge rounded-pill px-2 py-1 bg-success" style="font-size: 0.75rem;">
                                                    Registered
                                                </span>
                                            @elseif($user->status === 'pending')
                                                <span class="badge rounded-pill px-2 py-1 bg-warning" style="font-size: 0.75rem;">
                                                    Pending
                                                </span>
                                            @else
                                                <span class="badge rounded-pill px-2 py-1 bg-secondary" style="font-size: 0.75rem;">
                                                    Rejected
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap" style="font-size: 0.875rem; color: #1e3a8a;">{{ $user->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <form method="POST" action="{{ route('admin.users.send-credentials', $user->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to send credentials to {{ $user->email }}?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm users-send-btn text-capitalize">Send Credentials</button>
                                                </form>
                                                <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-sm users-view-btn text-capitalize">View Details</a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap">
                            <div style="font-size: 0.875rem; color: #1e3a8a;">
                                Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} entries
                            </div>
                            <div>
                                {{ $users->links('vendor.pagination.bootstrap-5') }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <p class="text-muted mb-0">No registrations found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Search Card */
    .users-search-card {
        background: #f8f9fa;
        border: 1px solid #e5e7eb;
    }
    
    /* Buttons - Subtle Theme Colors */
    .users-search-btn {
        background: #667eea;
        color: #ffffff;
        border: none;
        font-weight: 500;
        padding: 0.5rem 1.25rem;
        border-radius: 10px;
        transition: all 0.25s ease;
    }
    
    .users-search-btn:hover {
        background: #5a67d8;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .users-export-btn {
        background: #10b981;
        color: #ffffff;
        border: none;
        font-weight: 500;
        padding: 0.5rem 1.25rem;
        border-radius: 10px;
        transition: all 0.25s ease;
    }
    
    .users-export-btn:hover {
        background: #059669;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .users-clear-btn {
        background: #ef4444;
        color: #ffffff;
        border: none;
        font-weight: 500;
        padding: 0.5rem 1.25rem;
        border-radius: 10px;
        transition: all 0.25s ease;
    }
    
    .users-clear-btn:hover {
        background: #dc2626;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
    
    /* Table Action Buttons */
    .users-send-btn {
        background: #10b981;
        color: #ffffff;
        border: none;
        font-weight: 500;
        font-size: 0.8rem;
        padding: 0.375rem 0.75rem;
        border-radius: 8px;
        transition: all 0.25s ease;
    }
    
    .users-send-btn:hover {
        background: #059669;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }
    
    .users-view-btn {
        background: #667eea;
        color: #ffffff;
        border: none;
        font-weight: 500;
        font-size: 0.8rem;
        padding: 0.375rem 0.75rem;
        border-radius: 8px;
        transition: all 0.25s ease;
    }
    
    .users-view-btn:hover {
        background: #5a67d8;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }
    
    /* Card Header - Subtle Solid Color */
    .users-card-header {
        background: #667eea;
        padding: 1rem 1.25rem;
    }
    
    /* Table Header - Subtle Solid Color */
    .users-table-header {
        background: #ede9fe;
    }
    
    .users-table-header th {
        background: #ede9fe;
        color: #1e3a8a;
        font-weight: 600;
        font-size: 0.875rem;
        padding: 0.75rem;
        border-bottom: 2px solid #c7d2fe;
    }
    
    .users-per-page-select {
        min-width: 80px;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: #1e3a8a !important;
        font-weight: 500;
        padding: 0.375rem 2rem 0.375rem 0.75rem;
        border-radius: 8px;
    }
    
    .users-per-page-select:focus {
        background: #ffffff;
        border-color: rgba(255, 255, 255, 0.5);
        color: #1e3a8a !important;
        box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
    }
    
    .users-per-page-select option {
        color: #1e3a8a;
        background: #ffffff;
        padding: 0.5rem;
    }
    
    .accent-line {
        height: 3px;
        width: 60px;
        background: #667eea;
        border-radius: 2px;
        margin-top: 0.5rem;
    }
    
    .table td {
        color: #1e3a8a;
    }
</style>
@endpush
@endsection

