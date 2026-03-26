@extends('superadmin.layout')

@section('title', 'Manage Admins')

@section('content')
<div class="container-fluid px-2 py-0">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
        <div>
            <h2 class="mb-0 border-0">Manage Admins</h2>
            <p class="text-muted mb-1">Manage all admin accounts and their roles</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('superadmin.dashboard') }}" class="btn btn-primary text-white">
                <i class="bi bi-arrow-left fs-6"></i> Back to Dashboard
            </a>
            <a href="{{ route('superadmin.admins.create') }}" class="btn btn-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                    <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0Zm-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    <path d="M2 13c0 1 1 1 1 1h5.256A4.493 4.493 0 0 1 8 12.5a4.49 4.49 0 0 1 1.544-3.393C9.077 9.038 8.564 9 8 9c-5 0-6 3-6 4Z"/>
                </svg>
                Add New Admin
            </a>
        </div>
    </div>
    <div class="accent-line"></div>

    <!-- Search Form -->
    <div class="card border-c-blue shadow-sm mb-4" style="border-radius: 16px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('superadmin.admins') }}" class="row g-3 theme-forms">
                <div class="col-md-10">
                    <input type="text" 
                           name="search" 
                           class="form-control form-control-lg" 
                           placeholder="Search by name, email, employee ID, or role..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100" style="border-radius: 10px; font-weight: 500;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                        Search
                    </button>
                </div>
                @if(request('search'))
                    <div class="col-12">
                        <a href="{{ route('superadmin.admins') }}" class="btn btn-sm btn-danger">
                            Clear Search
                        </a>
                        <small class="text-muted ms-2">Showing results for: <strong>{{ request('search') }}</strong></small>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold">Admins List ({{ $admins->total() }})</h5>
                </div>
                <div class="card-body">
                    @if($admins->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Employee ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Roles</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th class="text-end pe-3" width="270">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($admins as $admin)
                                    <tr class="align-middle">
                                        <td><strong class="text-navy">{{ $admin->admin_id }}</strong></td>
                                        <td class="text-nowrap">{{ $admin->name }}</td>
                                        <td>{{ $admin->email }}</td>
                                        <td>
                                            @if($admin->roles->count() > 0)
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($admin->roles as $role)
                                                        <span class="badge bg-info">{{ $role->name }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted small">No roles assigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill px-3 py-1 {{ $admin->is_active ? 'bg-success' : 'bg-danger' }}">
                                                {{ $admin->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                            @if($admin->is_super_admin)
                                                <span class="badge bg-warning text-dark ms-1">Super Admin</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">{{ $admin->created_at->format('M d, Y') }}</td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2 flex-wrap">
                                                <a href="{{ route('superadmin.admins.show', $admin->id) }}" class="btn btn-sm rounded btn-info">
                                                    View
                                                </a>
                                                <a href="{{ route('superadmin.admins.edit-details', $admin->id) }}" class="btn btn-sm rounded btn-primary">
                                                    Edit Details
                                                </a>
                                                <a href="{{ route('superadmin.admins.edit-type', $admin->id) }}" class="btn btn-sm rounded theme-bg-yellow">
                                                    Edit Type
                                                </a>
                                                <form method="POST" action="{{ route('superadmin.admins.toggle-status', $admin->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm rounded {{ $admin->is_active ? 'btn-danger' : 'btn-success' }}" onclick="return confirm('Are you sure you want to {{ $admin->is_active ? 'deactivate' : 'activate' }} this admin?')">
                                                        {{ $admin->is_active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 d-flex justify-content-center">
                            {{ $admins->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#6c757d" class="mb-3" viewBox="0 0 16 16">
                                <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                            </svg>
                            <p class="text-muted">No admins found. <a href="{{ route('superadmin.admins.create') }}" style="color: #2c3e50; font-weight: 500;">Create one now</a>.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
