@extends('admin.layout')

@section('title', 'Update KYC Details')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1>Update KYC Details</h2>
            <p class="text-muted mb-0">Update missing kyc_details for applications by fetching data from GST API</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('errors') && is_array(session('errors')))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Some applications failed to update:</strong>
            <ul class="mb-0 mt-2">
                @foreach(session('errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Search Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.applications.update-kyc-details') }}" class="row g-3 theme-forms">
                        <div class="col-md-10">
                            <label class="form-label fw-bold text-capitalize">Search</label>
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Search by application ID, membership ID, applicant name, email, or registration ID..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                        @if(request('search'))
                            <div class="col-12">
                                <a href="{{ route('admin.applications.update-kyc-details') }}" class="btn btn-danger">Clear</a>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Applications List -->
    <div class="card border-c-blue shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0 text-capitalize" style="color: #000 !important;">Applications</h4>
            @if($applications->count() > 0)
                <form method="POST" action="{{ route('admin.applications.process-update-kyc-details') }}" id="updateKycForm">
                    @csrf
                    <button type="submit" class="btn btn-primary" id="updateBtn" disabled>
                        <i class="bi bi-arrow-clockwise me-1"></i> Update Selected
                    </button>
                </form>
            @endif
        </div>
        <div class="card-body">
            @if($applications->count() > 0)
                <form id="selectAllForm" class="theme-forms">
                    <div class="mb-3">
                        <input type="checkbox" id="selectAll" class="form-check-input mt-0 me-0">
                        <label for="selectAll" class="form-check-label ms-2">Select All</label>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="text-nowrap">
                            <tr>
                                <th width="40" class="theme-forms">
                                    <input type="checkbox" id="selectAllTable" class="form-check-input">
                                </th>
                                <th>Application ID</th>
                                <th>Membership ID</th>
                                <th>Applicant Name</th>
                                <th>Email</th>
                                <th>GSTIN</th>
                                <th>KYC Status</th>
                                <th>GST Verification</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($applications as $application)
                                @php
                                    $applicationData = $application->application_data ?? [];
                                    $gstin = strtoupper($applicationData['gstin'] ?? 'N/A');
                                    $kycDetails = $application->kyc_details ?? [];
                                    $hasKycDetails = !empty($kycDetails) && isset($kycDetails['gstin']);
                                    $gstVerification = $application->gstVerification;
                                    $gstVerified = $gstVerification && $gstVerification->is_verified;
                                @endphp
                                <tr class="align-middle">
                                    <td class="theme-forms">
                                        <input type="checkbox" 
                                               form="updateKycForm"
                                               name="application_ids[]" 
                                               value="{{ $application->id }}" 
                                               class="form-check-input application-checkbox"
                                               data-application-id="{{ $application->id }}">
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.applications.show', $application->id) }}" target="_blank">
                                            {{ $application->application_id ?? 'N/A' }}
                                        </a>
                                    </td>
                                    <td>{{ $application->membership_id ?? 'N/A' }}</td>
                                    <td>{{ $application->user->fullname ?? 'N/A' }}</td>
                                    <td>{{ $application->user->email ?? 'N/A' }}</td>
                                    <td>
                                        @if($gstin !== 'N/A')
                                            <code>{{ $gstin }}</code>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($hasKycDetails)
                                            <span class="badge bg-success">Complete</span>
                                        @else
                                            <span class="badge bg-warning">Missing</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($gstVerified)
                                            <span class="badge bg-success">Verified</span>
                                        @elseif($gstVerification)
                                            <span class="badge bg-warning">{{ ucfirst($gstVerification->status) }}</span>
                                        @else
                                            <span class="badge bg-secondary">Not Verified</span>
                                        @endif
                                    </td>
                                    <td>{{ $application->created_at->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3 d-flex justify-content-center">
                    {{ $applications->links('vendor.pagination.bootstrap-5') }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="text-muted mt-3 mb-0">No applications found with missing KYC details</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const selectAllTableCheckbox = document.getElementById('selectAllTable');
    const applicationCheckboxes = document.querySelectorAll('.application-checkbox');
    const updateBtn = document.getElementById('updateBtn');
    const updateKycForm = document.getElementById('updateKycForm');

    function updateSelectedIds() {
        const selected = Array.from(document.querySelectorAll('.application-checkbox:checked'));
        updateBtn.disabled = selected.length === 0;
    }

    function toggleAll(checked) {
        applicationCheckboxes.forEach(cb => {
            cb.checked = checked;
        });
        selectAllCheckbox.checked = checked;
        selectAllTableCheckbox.checked = checked;
        updateSelectedIds();
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            toggleAll(this.checked);
        });
    }

    if (selectAllTableCheckbox) {
        selectAllTableCheckbox.addEventListener('change', function() {
            toggleAll(this.checked);
        });
    }

    applicationCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            updateSelectedIds();
            const allChecked = Array.from(applicationCheckboxes).every(c => c.checked);
            selectAllCheckbox.checked = allChecked;
            selectAllTableCheckbox.checked = allChecked;
        });
    });

    if (updateKycForm) {
        updateKycForm.addEventListener('submit', function(e) {
            const selected = Array.from(document.querySelectorAll('.application-checkbox:checked'));
            if (selected.length === 0) {
                e.preventDefault();
                alert('Please select at least one application to update.');
                return false;
            }

            // Remove any existing hidden inputs from previous submissions
            updateKycForm.querySelectorAll('input[type="hidden"][name="application_ids[]"]').forEach(input => {
                input.remove();
            });

            // Add hidden inputs for all selected checkboxes to ensure they're submitted
            selected.forEach(checkbox => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'application_ids[]';
                hiddenInput.value = checkbox.value;
                updateKycForm.appendChild(hiddenInput);
            });

            if (!confirm(`Are you sure you want to update KYC details for ${selected.length} application(s)? This will fetch data from GST API.`)) {
                e.preventDefault();
                // Remove hidden inputs we just added
                updateKycForm.querySelectorAll('input[type="hidden"][name="application_ids[]"]').forEach(input => {
                    input.remove();
                });
                return false;
            }

            updateBtn.disabled = true;
            updateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Updating...';
        });
    }

    updateSelectedIds();
});
</script>
@endpush
@endsection

