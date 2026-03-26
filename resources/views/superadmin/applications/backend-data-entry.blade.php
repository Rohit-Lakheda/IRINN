@extends('superadmin.layout')

@section('title', 'Backend Data Entry Applications')

@section('content')
<div class="container-fluid px-2 py-0">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="mb-0 border-0">Bulk Approve Applications</h2>
        <p class="text-muted mb-1">Select and approve applications to IX Account stage for invoice generation</p>
        <div class="accent-line"></div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Search Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-body">
                    <form method="GET" action="{{ route('superadmin.applications.backend-data-entry') }}" class="row g-3 theme-forms">
                        <div class="col-md-9 col-lg-10">
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Search by application ID, applicant name, or email..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Approval Form -->
    <form method="POST" action="{{ route('superadmin.applications.backend-data-entry.bulk-approve') }}" id="bulkApproveForm" class="theme-fomrs">
        @csrf
        
        <!-- Applications Table -->
        <div class="row">
            <div class="col-12">
                <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-radius: 16px 16px 0 0;">
                        <h5 class="mb-0">All IX Applications</h5>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn border-white text-blue btn-sm" onclick="selectAll()">
                                <i class="bi bi-check-square"></i> Select All
                            </button>
                            <button type="button" class="btn btn-danger text-blue btn-sm" onclick="deselectAll()">
                                <i class="bi bi-square"></i> Deselect All
                            </button>
                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirmBulkApprove()">
                                <i class="bi bi-check-circle"></i> Bulk Approve Selected
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-2">
                        @if($applications->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 theme-forms">
                                    <thead class="table-light text-nowrap">
                                        <tr class="align-middle">
                                            <th>
                                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleAll(this)">
                                            </th>
                                            <th>Application ID</th>
                                            <th>Applicant Name</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Membership ID</th>
                                            <th>Created At</th>
                                            <th class="text-end p-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($applications as $application)
                                        @php
                                            $isAlreadyApproved = in_array($application->status, ['ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                                        @endphp
                                        <tr class="align-middle {{ $isAlreadyApproved ? 'table-secondary' : '' }}">
                                            <td>
                                                <input type="checkbox" 
                                                       name="application_ids[]" 
                                                       value="{{ $application->id }}" 
                                                       class="application-checkbox"
                                                       {{ $isAlreadyApproved ? 'disabled' : '' }}>
                                            </td>
                                            <td>
                                                <a href="{{ route('superadmin.applications.show', $application->id) }}" 
                                                   class="text-decoration-none fw-bold">
                                                    {{ $application->application_id }}
                                                </a>
                                            </td>
                                            <td>{{ $application->user->fullname ?? 'N/A' }}</td>
                                            <td>{{ $application->user->email ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-{{ in_array($application->status, ['ip_assigned', 'invoice_pending', 'payment_verified', 'approved']) ? 'success' : 'warning' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $application->status)) }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $application->membership_id ?? 'N/A' }}
                                            </td>
                                            <td>
                                                {{ $application->created_at->format('d M Y, h:i A') }}
                                            </td>
                                            <td>
                                                <a href="{{ route('superadmin.applications.show', $application->id) }}" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <div class="card-footer bg-white border-top-0" style="border-radius: 0 0 16px 16px;">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <small class="fw-semibold text-blue">
                                            Showing {{ $applications->firstItem() }} to {{ $applications->lastItem() }} of {{ $applications->total() }} results
                                        </small>
                                    </div>
                                    <div class="d-flex justify-content-center">
                                        {{ $applications->links('vendor.pagination.bootstrap-5') }}
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="text-muted mt-3">No applications found.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Info Alert -->
    <div class="alert alert-info mt-3">
        <h6 class="alert-heading text-blue d-flex"><i class="bi bi-info-circle me-2"></i> Bulk Approval Information</h6>
        <ul class="mb-0 ps-3">
            <li>Applications will be approved from <strong>IX Processor</strong> directly to <strong>IX Account</strong> stage</li>
            <li>Status will be changed to <strong>ip_assigned</strong> (ready for invoice generation)</li>
            <li>Service activation date will be set to <strong>2026-01-01</strong> for all approved applications</li>
            <li>Readonly details (IP, Customer ID, Membership ID, Port Capacity, etc.) will be saved from application data</li>
            <li>Applications will be marked as <strong>LIVE</strong> (is_active = true)</li>
            <li>Applications already at or beyond invoice stage will be skipped</li>
        </ul>
    </div>
</div>

@push('scripts')
<script>
function toggleAll(checkbox) {
    const checkboxes = document.querySelectorAll('.application-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.application-checkbox');
    checkboxes.forEach(cb => cb.checked = true);
    document.getElementById('selectAllCheckbox').checked = true;
}

function deselectAll() {
    const checkboxes = document.querySelectorAll('.application-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    document.getElementById('selectAllCheckbox').checked = false;
}

function confirmBulkApprove() {
    const checked = document.querySelectorAll('.application-checkbox:checked');
    if (checked.length === 0) {
        alert('Please select at least one application to approve.');
        return false;
    }
    
    return confirm(`Are you sure you want to bulk approve ${checked.length} application(s)?\n\nThis will:\n- Change status to ip_assigned\n- Set service activation date to 2026-01-01\n- Membership ID = Application ID\n- Customer ID = Registration ID\n- Port Capacity & Billing Cycle from application data\n- Mark applications as LIVE`);
}
</script>
@endpush
@endsection

