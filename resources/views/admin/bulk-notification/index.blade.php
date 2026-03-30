@extends('admin.layout')

@section('title', 'Bulk Notification')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1 fw-semibold border-0">Bulk Notification</h2>
            <p class="text-muted mb-0">Send notifications to multiple users based on various filters</p>
            <div class="accent-line"></div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold">Send Bulk Notification</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.bulk-notification.send') }}" id="bulkNotificationForm" class="theme-forms">
                        @csrf

                        <!-- Notification Content -->
                        <div class="mb-4">
                            <h6 class="mb-3 fw-semibold">Notification Content</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('subject') is-invalid @enderror" 
                                           id="subject" 
                                           name="subject" 
                                           value="{{ old('subject') }}" 
                                           required 
                                           placeholder="Enter notification subject">
                                    @error('subject')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('message') is-invalid @enderror" 
                                              id="message" 
                                              name="message" 
                                              rows="6" 
                                              required 
                                              placeholder="Enter notification message">{{ old('message') }}</textarea>
                                    @error('message')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Filter Options -->
                        <div class="mb-4">
                            <h6 class="mb-3 fw-semibold">Filter Recipients</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="filter_type" class="form-label">Filter Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('filter_type') is-invalid @enderror" 
                                            id="filter_type" 
                                            name="filter_type" 
                                            required>
                                        <option value="">Select filter type...</option>
                                        <option value="all" {{ old('filter_type') === 'all' ? 'selected' : '' }}>All IRINN members (with membership ID)</option>
                                        <option value="payment_status" {{ old('filter_type') === 'payment_status' ? 'selected' : '' }}>By Payment Status</option>
                                        <option value="application_status" {{ old('filter_type') === 'application_status' ? 'selected' : '' }}>By Application Status</option>
                                        <option value="user_wise" {{ old('filter_type') === 'user_wise' ? 'selected' : '' }}>Select Specific Users</option>
                                    </select>
                                    @error('filter_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Payment Status Filter -->
                                <div class="col-12" id="payment_status_filter" style="display: none;">
                                    <label for="payment_status" class="form-label">Payment Status <span class="text-danger">*</span></label>
                                    <select class="form-select @error('payment_status') is-invalid @enderror" 
                                            id="payment_status" 
                                            name="payment_status">
                                        <option value="">Select payment status...</option>
                                        <option value="paid" {{ old('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                        <option value="pending" {{ old('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="overdue" {{ old('payment_status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                    </select>
                                    @error('payment_status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Application Status Filter -->
                                <div class="col-12" id="application_status_filter" style="display: none;">
                                    <label for="application_status" class="form-label">Application Status <span class="text-danger">*</span></label>
                                    <select class="form-select @error('application_status') is-invalid @enderror" 
                                            id="application_status" 
                                            name="application_status">
                                        <option value="">Select application status...</option>
                                        <option value="live" {{ old('application_status') === 'live' ? 'selected' : '' }}>Live</option>
                                        <option value="not_live" {{ old('application_status') === 'not_live' ? 'selected' : '' }}>Not Live</option>
                                    </select>
                                    @error('application_status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- User-wise Filter -->
                                <div class="col-12" id="user_wise_filter" style="display: none;">
                                    <label class="form-label">Select Users <span class="text-danger">*</span></label>
                                    <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                        <div class="mb-2">
                                            <input type="text" 
                                                   id="user_search" 
                                                   class="form-control form-control-sm" 
                                                   placeholder="Search users...">
                                        </div>
                                        <div id="user_list">
                                            @foreach($users as $user)
                                                <div class="form-check user-item" data-name="{{ strtolower($user->fullname) }}" data-email="{{ strtolower($user->email) }}" data-regid="{{ strtolower($user->registrationid) }}">
                                                    <input class="form-check-input" 
                                                           type="checkbox" 
                                                           name="user_ids[]" 
                                                           value="{{ $user->id }}" 
                                                           id="user_{{ $user->id }}"
                                                           {{ in_array($user->id, old('user_ids', [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="user_{{ $user->id }}">
                                                        {{ $user->fullname }} ({{ $user->email }}) - {{ $user->registrationid }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @error('user_ids')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Submit Button -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-danger">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-send me-2"></i>Send Notification
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterType = document.getElementById('filter_type');
    const zoneFilter = document.getElementById('zone_filter');
    const locationFilter = document.getElementById('location_filter');
    const paymentStatusFilter = document.getElementById('payment_status_filter');
    const applicationStatusFilter = document.getElementById('application_status_filter');
    const userWiseFilter = document.getElementById('user_wise_filter');
    const userSearch = document.getElementById('user_search');
    const userList = document.getElementById('user_list');
    const submitBtn = document.getElementById('submitBtn');

    // Hide all filters initially
    function hideAllFilters() {
        paymentStatusFilter.style.display = 'none';
        applicationStatusFilter.style.display = 'none';
        userWiseFilter.style.display = 'none';
    }

    // Show/hide filters based on selection
    filterType.addEventListener('change', function() {
        hideAllFilters();
        
        switch(this.value) {
            case 'payment_status':
                paymentStatusFilter.style.display = 'block';
                break;
            case 'application_status':
                applicationStatusFilter.style.display = 'block';
                break;
            case 'user_wise':
                userWiseFilter.style.display = 'block';
                break;
        }
    });

    // Initialize on page load if old input exists
    if (filterType.value) {
        filterType.dispatchEvent(new Event('change'));
    }

    // User search functionality
    if (userSearch) {
        userSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const userItems = userList.querySelectorAll('.user-item');
            
            userItems.forEach(function(item) {
                const name = item.getAttribute('data-name');
                const email = item.getAttribute('data-email');
                const regid = item.getAttribute('data-regid');
                
                if (name.includes(searchTerm) || email.includes(searchTerm) || regid.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Form submission confirmation
    document.getElementById('bulkNotificationForm').addEventListener('submit', function(e) {
        const filterTypeValue = filterType.value;
        let message = 'Are you sure you want to send this notification?';
        
        if (filterTypeValue === 'all') {
            message += '\n\nThis will send notifications to all users with an IRINN membership.';
        }
        
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
        
        // Disable submit button to prevent double submission
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
    });
});
</script>
@endpush
@endsection

