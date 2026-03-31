@extends('superadmin.layout')

@section('title', 'All Applications')

@section('content')
<div class="container-fluid px-2 py-0">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="mb-1 fw-semibold text-navy border-0">All Applications</h2>
        <p class="text-muted mb-0">View and manage all IRINN applications</p>
        <div class="accent-line"></div>
    </div>
    <div class="accent-line"></div>

    <!-- Search Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <form method="GET" action="{{ route('superadmin.applications.index') }}" class="row g-3 theme-forms">
                        <div class="col-md-9 col-lg-10">
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Search by application ID, membership ID, applicant name, email, or status..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <button type="submit" class="btn btn-primary fw-emdium">Search</button>
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
                <div class="card-body p-0">
                    @if($applications->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="text-nowrap">
                                    <tr>
                                        <th>Application ID</th>
                                        <th>Member</th>
                                        <th>Membership ID</th>
                                        <th>Status</th>
                                        <th>Live Status</th>
                                        <th>Created</th>
                                        <th class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($applications as $application)
                                    <tr class="align-middle">
                                        <td>
                                            <strong>{{ $application->application_id }}</strong>
                                        </td>
                                        <td>
                                            {{ $application->user->fullname ?? 'N/A' }}
                                            <br><small class="text-muted">{{ $application->user->email ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <strong>{{ $application->membership_id ?? 'N/A' }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill px-3 py-1
                                                @if($application->status === 'approved' || $application->status === 'payment_verified') bg-success
                                                @elseif(in_array($application->status, ['ip_assigned', 'invoice_pending'])) bg-info
                                                @elseif($application->status === 'rejected' || $application->status === 'ceo_rejected') bg-danger
                                                @else bg-secondary @endif">
                                                {{ $application->status_display }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($application->is_active)
                                                <span class="badge bg-success">Live</span>
                                            @else
                                                <span class="badge bg-secondary">Not Live</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $application->created_at->format('d M Y') }}
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('superadmin.applications.show', $application->id) }}" class="btn btn-sm btn-primary">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-light border-top mt-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="text-nevy fw-semibold">
                                    Showing {{ $applications->firstItem() }} to {{ $applications->lastItem() }} of {{ $applications->total() }} applications
                                </div>
                                <div>
                                    {{ $applications->links('vendor.pagination.bootstrap-5') }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <p class="text-muted mb-0">No applications found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

