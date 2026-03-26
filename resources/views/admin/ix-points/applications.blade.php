@extends('admin.layout')

@section('title', 'Applications - ' . $location->name)

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div>
                <h2 class="mb-1">Applications - {{ $location->name }}</h2>
                <p class="mb-0 text-muted">All applications for this IX point location</p>
                <div class="accent-line"></div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <form method="GET" action="{{ route('admin.ix-points.applications', $location->id) }}" id="filterForm">
                        <div class="row g-3">
                            <!-- Search -->
                            <div class="col-md-4">
                                <label for="search" class="form-label small fw-semibold">Search</label>
                                <input type="text" class="form-control form-control-sm" id="search" name="search" 
                                    value="{{ request('search') }}" placeholder="Search by application ID, name, email...">
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
                                    <a href="{{ route('admin.ix-points.applications', $location->id) }}" class="btn btn-outline-secondary btn-sm">
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

    <!-- Applications Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    @if($applications->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Application ID</th>
                                        <th>Applicant Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Live Status</th>
                                        <th>Submitted At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($applications as $application)
                                        <tr>
                                            <td>{{ $application->application_id }}</td>
                                            <td>{{ $application->user->fullname ?? 'N/A' }}</td>
                                            <td>{{ $application->user->email ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $application->status === 'approved' || $application->status === 'payment_verified' ? 'success' : ($application->status === 'rejected' || $application->status === 'ceo_rejected' ? 'danger' : 'warning') }}">
                                                    {{ ucfirst(str_replace('_', ' ', $application->status)) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($application->is_active && $application->assigned_ip && $application->assigned_port_number)
                                                    <span class="badge bg-success">Live</span>
                                                @else
                                                    <span class="badge bg-secondary">Not Live</span>
                                                @endif
                                            </td>
                                            <td>{{ $application->created_at->format('d M Y') }}</td>
                                            <td>
                                                <a href="{{ route('admin.applications.show', $application->id) }}" class="btn btn-sm btn-primary">View Details</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-4 d-flex justify-content-center">
                            {{ $applications->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="text-muted mb-0 mt-3">No applications found for this location.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

