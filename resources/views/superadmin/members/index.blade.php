@extends('superadmin.layout')

@section('title', 'Members')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0 border-0 text-nevy semibold">Members</h2>
            <p class="text-muted mb-0">Members are users who have at least one application with membership ID</p>
            <div class="accent-line"></div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-pills theme-nav-pills">
                <li class="nav-item">
                    <a class="nav-link {{ $filter === 'all' ? 'active' : '' }}" href="{{ route('superadmin.members', ['filter' => 'all']) }}">
                        All Members
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $filter === 'active' ? 'active' : '' }}" href="{{ route('superadmin.members', ['filter' => 'active']) }}">
                        Live Members
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $filter === 'disconnected' ? 'active' : '' }}" href="{{ route('superadmin.members', ['filter' => 'disconnected']) }}">
                        Not Live Members
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Search Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-4">
                    <form method="GET" action="{{ route('superadmin.members') }}" class="row g-3 theme-forms">
                        <input type="hidden" name="filter" value="{{ $filter }}">
                        <div class="col-md-9 col-lg-10">
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Search by name, email, registration ID, PAN..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <button type="submit" class="btn btn-primary fw-emdium">Search</button>
                        </div>
                        @if(request('search'))
                            <div class="col-12">
                                <a href="{{ route('superadmin.members', ['filter' => $filter]) }}" class="btn btn-sm btn-danger">Clear Search</a>
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
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold">Members List</h5>
                </div>
                <div class="card-body">
                    @if($members->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="text-nowrap">
                                    <tr>
                                        <th>Registration ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Membership ID</th>
                                        <th>Application Status</th>
                                        <th>Member Status</th>
                                        <th class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($members as $member)
                                    @php
                                        $memberApplication = $member->applications->whereNotNull('membership_id')->first();
                                        $membershipId = $memberApplication->membership_id ?? 'N/A';
                                        $isActive = $memberApplication->is_active ?? true;
                                    @endphp
                                    <tr class="align-middle">
                                        <td><strong>{{ $member->registrationid }}</strong></td>
                                        <td>{{ $member->fullname }}</td>
                                        <td>{{ $member->email }}</td>
                                        <td>{{ $member->mobile }}</td>
                                        <td><strong>{{ $membershipId }}</strong></td>
                                        <td class="text-center">
                                            @if($memberApplication)
                                                @if($isActive)
                                                    <span class="badge bg-success">LIVE</span>
                                                @else
                                                    <span class="badge bg-danger">NOT LIVE</span>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary">N/A</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($member->status === 'approved' || $member->status === 'active')
                                                <span class="badge bg-success">Active</span>
                                            @elseif($member->status === 'inactive')
                                                <span class="badge bg-danger">Deactivated</span>
                                            @elseif($member->status === 'pending')
                                                <span class="badge bg-warning">Pending</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($member->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('superadmin.members.show', $member->id) }}" class="btn btn-sm btn-primary text-nowrap">View Details</a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 d-flex justify-content-center">
                            {{ $members->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <p class="text-muted">No members found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

