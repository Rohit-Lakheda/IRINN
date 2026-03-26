@extends('admin.layout')

@section('title', 'Members - ' . $location->name)

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div>
                <h2 class="mb-1">Members - {{ $location->name }}</h2>
                <p class="mb-0 text-muted">All members for this IX point location</p>
                <div class="accent-line"></div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <form method="GET" action="{{ route('admin.ix-points.members', $location->id) }}" id="filterForm">
                        <div class="row g-3">
                            <!-- Search -->
                            <div class="col-md-4">
                                <label for="search" class="form-label small fw-semibold">Search</label>
                                <input type="text" class="form-control form-control-sm" id="search" name="search" 
                                    value="{{ request('search') }}" placeholder="Search by name, email, registration ID...">
                            </div>

                            <!-- Live Filter -->
                            <div class="col-md-2">
                                <label for="live" class="form-label small fw-semibold">Live Status</label>
                                <select class="form-select form-select-sm" id="live" name="live">
                                    <option value="">All</option>
                                    <option value="true" {{ request('live') === 'true' ? 'selected' : '' }}>Live</option>
                                    <option value="false" {{ request('live') === 'false' ? 'selected' : '' }}>Not Live</option>
                                </select>
                            </div>

                            <!-- Action Buttons -->
                            <div class="col-md-2 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                    <i class="bi bi-funnel"></i> Apply
                                </button>
                                @if(request()->anyFilled(['search', 'live']))
                                    <a href="{{ route('admin.ix-points.members', $location->id) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-x-circle"></i> Clear
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Members Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    @if($members->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Membership ID</th>
                                        <th>Name & Email</th>
                                        <th>Registration ID</th>
                                        <th>Live Status</th>
                                        <th>Registered At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($members as $member)
                                        @php
                                            $application = $member->applications->first();
                                            $isLive = $application && $application->is_active;
                                        @endphp
                                        <tr>
                                            <td>{{ $application->membership_id ?? 'N/A' }}</td>
                                            <td>
                                                <a href="{{ route('admin.users.show', $member->id) }}" class="text-decoration-none">
                                                    <strong>{{ $member->fullname }}</strong>
                                                </a><br>
                                                <small class="text-muted">{{ $member->email }}</small>
                                            </td>
                                            <td>{{ $member->registrationid }}</td>
                                            <td>
                                                @if($isLive)
                                                    <span class="badge bg-success">Live</span>
                                                @else
                                                    <span class="badge bg-secondary">Not Live</span>
                                                @endif
                                            </td>
                                            <td>{{ $member->created_at->format('d M Y') }}</td>
                                            <td>
                                                @if($application)
                                                    <a href="{{ route('admin.applications.show', $application->id) }}" class="btn btn-sm btn-primary">View Details</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-4 d-flex justify-content-center">
                            {{ $members->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="text-muted mb-0 mt-3">No members found for this location.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

