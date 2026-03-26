@extends('admin.layout')

@section('title', 'Registration Details')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="flex-wrap">
                    <h2 class="mb-1 border-0">{{ $fromMembersPage ? 'Member Details' : 'Registration Details' }}</h2>
                    <p class="mb-2 text-muted">View and manage user registration information</p>
                </div>
                <div>
                    @if(!$fromMembersPage)
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.users') }}" class="btn users-back-btn">Back to Registrations</a>
                        <button type="button" class="btn users-delete-btn" data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                            Delete User
                        </button>
                    </div>
                    @else
                    <a href="{{ route('admin.members') }}" class="btn users-back-btn">Back to Members</a>
                    @endif
                </div>
            </div>
            <div class="accent-line"></div>
        </div>
    </div>

@if(!$fromMembersPage)
<!-- Delete User Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title text-white text-capitalize" id="deleteUserModalLabel" style="color: #fff !important;">Delete User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <strong>⚠️ Warning: This action cannot be undone!</strong>
                </div>
                <p>You are about to delete the following user and <strong>ALL</strong> their related data:</p>
                <ul>
                    <li><strong>User:</strong> {{ $user->fullname }} ({{ $user->registrationid }})</li>
                    <li>All applications and application history</li>
                    <li>All messages</li>
                    <li>All profile update requests</li>
                    <li>All KYC profiles</li>
                    <li>All payment transactions</li>
                    <li>All verifications (PAN, GST, UDYAM, MCA, ROC IEC)</li>
                    <li>All tickets and ticket messages</li>
                    <li>All sessions</li>
                    <li>All admin actions related to this user</li>
                </ul>
                <p class="mb-0"><strong>Are you absolutely sure you want to proceed?</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ route('admin.users.delete', $user->id) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Yes, Delete User</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Registration Details Card -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
            <div class="card-header users-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-capitalize text-white" style="font-weight: 600;">Registration Details</h5>
                @if(!$fromMembersPage)
                <button type="button" class="btn btn-sm users-message-btn" data-bs-toggle="modal" data-bs-target="#messageUserModal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                        <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                    </svg>
                    Message User
                </button>
                @endif
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Registration ID</label>
                            <div class="fw-bold fs-6" style="color: #1e3a8a; font-size: 1.1rem;">{{ $user->registrationid }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Full Name</label>
                            <div style="color: #1e3a8a;">{{ $user->fullname }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Email</label>
                            <div class="d-flex align-items-center gap-2 text-break flex-wrap">
                                <span style="color: #1e3a8a;">{{ $user->email }}</span>
                                @if($user->email_verified)
                                    <span class="badge bg-success">Verified</span>
                                @endif
                                <button type="button" class="btn btn-sm users-update-email-btn" data-bs-toggle="modal" data-bs-target="#updateEmailModal">
                                    Update Email
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Mobile</label>
                            <div class="d-flex align-items-center gap-2">
                                <span style="color: #1e3a8a;">{{ $user->mobile }}</span>
                                @if($user->mobile_verified)
                                    <span class="badge bg-success">Verified</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="text-muted small mb-1">PAN Card</label>
                            <div class="d-flex align-items-center gap-2">
                                <span style="color: #1e3a8a;">{{ $user->pancardno }}</span>
                                @if($user->pan_verified)
                                    <span class="badge bg-success">Verified</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Date of Birth</label>
                            <div style="color: #1e3a8a;">{{ $user->dateofbirth ? $user->dateofbirth->format('d M Y') : 'N/A' }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Registration Date</label>
                            <div style="color: #1e3a8a;">{{ $user->registrationdate ? $user->registrationdate->format('d M Y') : 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Message User Modal -->
@if(!$fromMembersPage)
<div class="modal fade" id="messageUserModal" tabindex="-1" aria-labelledby="messageUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-c-blue">
            <div class="modal-header users-modal-header">
                <h5 class="modal-title text-white" id="messageUserModalLabel">Send Message to {{ $user->fullname }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.users.send-message', $user->id) }}" class="theme-forms">
                @csrf
                <div class="modal-body pb-0">
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="message" name="message" rows="5" required placeholder="Type your message here..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn users-cancel-btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn users-save-btn">Send Message</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Update Email Modal -->
<div class="modal fade" id="updateEmailModal" tabindex="-1" aria-labelledby="updateEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-c-blue">
            <div class="modal-header users-modal-header">
                <h5 class="modal-title text-white" id="updateEmailModalLabel">Update Email</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.users.update-email', $user->id) }}" class="theme-forms">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_email" class="form-label">Current Email</label>
                        <input type="text" id="current_email" class="form-control" value="{{ $user->email }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">New Email <span class="text-danger">*</span></label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}"
                            required
                        >
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn users-cancel-btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn users-save-btn">Update Email</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Application Details Section -->
@if($user->applications->count() > 0)
<div class="row mb-3">
    <div class="col-12">
        <div class="card shadow-sm" style="border-radius: 16px; background: #f8f9fa; border: 1px solid #e5e7eb;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <h4 class="mb-0" style="color: #1e3a8a; font-weight: 600;">Applications ({{ $applications->count() }})</h4>
                    <form method="GET" action="{{ route('admin.users.show', $user->id) }}" class="d-flex gap-2 align-items-center">
                        @if(request('from'))
                            <input type="hidden" name="from" value="{{ request('from') }}">
                        @endif
                        <select name="stage" class="form-select form-select-sm users-stage-filter" onchange="this.form.submit()">
                            <option value="">All Stages</option>
                            <option value="helpdesk" {{ $stageFilter === 'helpdesk' ? 'selected' : '' }}>Helpdesk</option>
                            <option value="hostmaster" {{ $stageFilter === 'hostmaster' ? 'selected' : '' }}>Hostmaster</option>
                            <option value="billing" {{ $stageFilter === 'billing' ? 'selected' : '' }}>Billing</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4 g-3">
    @foreach($applications as $application)
        @php
            $invoices = $application->invoices ?? collect();
            $latestInvoice = $invoices->first();
            $outstandingAmount = $invoices->whereIn('payment_status', ['pending', 'partial'])->sum('balance_amount');
            $lastPayment = $invoices->where('payment_status', 'paid')->sortByDesc('paid_at')->first();
            $totalInvoiced = $invoices->sum('total_amount');
            $totalPaid = $invoices->sum('paid_amount');
            $isIrinn = $application->application_type === 'IRINN';
            // For IRINN, consider application live when it reaches Billing stage
            $isLive = $isIrinn ? $application->status === 'billing' : ($application->is_active && $application->assigned_ip && $application->assigned_port_number);
            $appData = $application->application_data ?? [];
            $irinnPart2 = $appData['part2'] ?? [];
            $irinnTotalFee = $appData['total_fee'] ?? ($appData['part5']['total_amount'] ?? null);
        @endphp
        <div class="col-md-6 col-lg-4 col-xl-4">
            <div class="card shadow-sm h-100 users-application-card" style="border-radius: 16px;">
                <div class="card-header users-app-card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 text-white" style="font-weight: 600;">{{ $application->application_id }}</h6>
                        @if($application->membership_id)
                            <small class="text-white-50">Membership ID: <strong>{{ $application->membership_id }}</strong></small>
                        @endif
                    </div>
                    <div>
                        @if($isLive)
                            <span class="badge bg-success text-capitalize">Live</span>
                        @else
                            <span class="badge bg-secondary text-capitalize">Not Live</span>
                        @endif
                    </div>
                </div>
                <div class="card-body p-4 pb-0">
                    @if($isIrinn)
                        <!-- IRINN Application Details -->
                        <div class="mb-3">
                            <h6 class="text-muted mb-2" style="font-size: 1rem; font-weight: 600;">IRINN Application Details</h6>
                            <div class="bg-light p-3 rounded">
                                <div class="mb-2">
                                    <span class="text-muted small">Current Stage:</span>
                                    <strong class="d-block" style="color: #1e3a8a;">{{ $application->current_stage ?? 'N/A' }}</strong>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <span class="text-muted small">IPv4 Prefix:</span>
                                        <strong class="d-block" style="color: #1e3a8a;">{{ $irinnPart2['ipv4_prefix'] ?? '—' }}</strong>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted small">IPv6 Prefix:</span>
                                        <strong class="d-block" style="color: #1e3a8a;">{{ $irinnPart2['ipv6_prefix'] ?? '—' }}</strong>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted small">ASN Required:</span>
                                        <strong class="d-block" style="color: #1e3a8a;">{{ ($irinnPart2['asn_required'] ?? 'no') === 'yes' ? 'Yes' : 'No' }}</strong>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted small">Total Fee (incl. GST):</span>
                                        <strong class="d-block" style="color: #1e3a8a;">{{ $irinnTotalFee ? '₹'.number_format($irinnTotalFee, 2) : 'N/A' }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        @if($isLive)
                            <!-- Live IX Application Details -->
                            <div class="mb-3">
                                <h6 class="text-muted mb-2" style="font-size: 1rem; font-weight: 600;">IP & Port Details</h6>
                                <div class="bg-light p-3 rounded">
                                    @if($application->assigned_ip)
                                    <div class="mb-2">
                                        <span class="text-muted small">IP Address:</span>
                                        <strong class="d-block" style="color: #1e3a8a;">{{ $application->assigned_ip }}</strong>
                                    </div>
                                    @endif
                                    @if($application->assigned_port_number)
                                    <div class="mb-2">
                                        <span class="text-muted small">Port Number:</span>
                                        <strong class="d-block" style="color: #1e3a8a;">{{ $application->assigned_port_number }}</strong>
                                    </div>
                                    @endif
                                    @if($application->getEffectivePortCapacity())
                                    <div class="mb-2">
                                        <span class="text-muted small">Port Capacity:</span>
                                        <strong class="d-block" style="color: #1e3a8a;">{{ $application->getEffectivePortCapacity() }}</strong>
                                    </div>
                                    @endif
                                    @if($application->customer_id)
                                    <div class="mb-2">
                                        <span class="text-muted small">Customer ID:</span>
                                        <strong class="d-block" style="color: #1e3a8a;">{{ $application->customer_id }}</strong>
                                    </div>
                                    @endif
                                    @if($application->service_activation_date)
                                    <div>
                                        <span class="text-muted small">Service Activation:</span>
                                        <strong class="d-block" style="color: #1e3a8a;">{{ $application->service_activation_date->format('d M Y') }}</strong>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <!-- Not Live IX Application Details -->
                            <div class="alert theme-bg-yellow mb-3 border-c-blue">
                                <div class="d-flex align-items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" class="me-2 mt-1">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                        <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                                    </svg>
                                    <div>
                                        <strong>This application is not live.</strong>
                                        <p class="mb-0 mt-1" style="font-size: 0.875rem;">IP and Port are not assigned yet.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small mb-1">Current Stage</label>
                                <div>
                                    <span class="badge bg-info p-2" style="font-size: 0.775rem;">{{ $application->current_stage ?? 'N/A' }}</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small mb-1">Status</label>
                                <div>
                                    <span class="badge p-2 bg-{{ $application->status === 'approved' ? 'success' : ($application->status === 'rejected' ? 'danger' : 'warning') }}" style="font-size: 0.775rem;">
                                        {{ $application->status_display }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    @endif

                    <!-- Invoice Summary -->
                    <div class="mb-3">
                        <h6 class="text-muted mb-2" style="font-size: 1rem; font-weight: 600;">Invoice Summary</h6>
                        <div class="bg-light p-3 rounded">
                            <div class="row g-2">
                                <div class="col-6">
                                    <span class="text-muted small">Total Invoiced:</span>
                                    <strong class="d-block" style="color: #1e3a8a;">₹{{ number_format($totalInvoiced, 2) }}</strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted small">Total Paid:</span>
                                    <strong class="d-block" style="color: #1e3a8a;">₹{{ number_format($totalPaid, 2) }}</strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted small">Outstanding:</span>
                                    <strong class="d-block text-{{ $outstandingAmount > 0 ? 'danger' : 'success' }}">₹{{ number_format($outstandingAmount, 2) }}</strong>
                                </div>
                                @if($latestInvoice)
                                <div class="col-6">
                                    <span class="text-muted small">Recent Invoice:</span>
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <strong class="d-block" style="color: #1e3a8a;">{{ $latestInvoice->invoice_number }}</strong>
                                        <a href="{{ route('admin.applications.invoice.download', $latestInvoice->id) }}" 
                                           class="btn btn-sm theme-bg-green p-2 text-white text-capitalize"
                                           title="Download" >
                                           Download
                                           <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-arrow-down" viewBox="0 0 16 16">
                                                <path fill-rule="evenodd" d="M7.646 10.854a.5.5 0 0 0 .708 0l2-2a.5.5 0 0 0-.708-.708L8.5 9.293V5.5a.5.5 0 0 0-1 0v3.793L6.354 8.146a.5.5 0 1 0-.708.708z"/>
                                                <path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383m.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z"/>
                                            </svg>
                                        </a>
                                    </div>
                                    <span class="badge mt-2 text-capitalize bg-{{ $latestInvoice->payment_status === 'paid' ? 'success' : ($latestInvoice->payment_status === 'partial' ? 'warning' : 'danger') }}">
                                        {{ strtoupper($latestInvoice->payment_status) }}
                                    </span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('admin.applications.show', $application->id) }}" class="btn users-view-details-btn flex-fill">
                            View Details
                        </a>
                        @if($application->membership_id)
                        <a href="{{ route('admin.applications.show', $application->id) }}" class="btn users-manage-btn w-100">
                            Manage Service Status
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@else
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info">
            <strong>No Applications Found:</strong> This user does not have any applications.
        </div>
    </div>
</div>
@endif

<!-- Transaction Details -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card shadow-sm" style="border-radius: 16px;">
            <div class="card-header users-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold text-capitalize text-white">Transaction Details</h5>
                <div class="d-flex align-items-center gap-3">
                    <form method="GET" action="{{ route('admin.users.show', $user->id) }}" class="d-inline">
                        @if(request('from'))
                            <input type="hidden" name="from" value="{{ request('from') }}">
                        @endif
                        @if(request('stage'))
                            <input type="hidden" name="stage" value="{{ request('stage') }}">
                        @endif
                        @if(request('per_page_actions'))
                            <input type="hidden" name="per_page_actions" value="{{ request('per_page_actions') }}">
                        @endif
                        <select name="per_page_transactions" class="form-select form-select-sm users-per-page-select" onchange="this.form.submit()" style="color: #1e3a8a !important;">
                            <option value="10" {{ request('per_page_transactions', 10) == 10 ? 'selected' : '' }} style="color: #1e3a8a !important;">10</option>
                            <option value="20" {{ request('per_page_transactions', 10) == 20 ? 'selected' : '' }} style="color: #1e3a8a !important;">20</option>
                            <option value="50" {{ request('per_page_transactions', 10) == 50 ? 'selected' : '' }} style="color: #1e3a8a !important;">50</option>
                            <option value="100" {{ request('per_page_transactions', 10) == 100 ? 'selected' : '' }} style="color: #1e3a8a !important;">100</option>
                        </select>
                    </form>
                    <a href="{{ route('admin.users.transactions.export', $user->id) }}" class="btn btn-sm users-export-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8.5 6a.5.5 0 0 0-1 0v1.5H6a.5.5 0 0 0 0 1h1.5V10a.5.5 0 0 0 1 0V8.5H10a.5.5 0 0 0 0-1H8.5V6z"/>
                            <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2zm10-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1z"/>
                        </svg>
                        Export
                    </a>
                </div>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="text-nowrap users-table-header">
                            <tr>
                                <th>Date/Time</th>
                                <th>Transaction ID</th>
                                <th>Payment ID</th>
                                <th>Bank Ref. No.</th>
                                <th>Payment Mode</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($transactions->count() > 0)
                                @foreach($transactions as $transaction)
                                <tr class="align-middle">
                                    <td style="font-size: 0.875rem; color: #1e3a8a;">{{ $transaction->created_at->format('M d, Y h:i A') }}</td>
                                    <td style="font-size: 0.875rem; color: #1e3a8a;">{{ $transaction->transaction_id }}</td>
                                    <td style="font-size: 0.875rem; color: #1e3a8a;">{{ $transaction->payment_id }}</td>
                                    <td style="font-size: 0.875rem; color: #1e3a8a;">
                                        @php
                                            $bankRef = null;
                                            if ($transaction->payu_response && is_array($transaction->payu_response)) {
                                                $bankRef = $transaction->payu_response['bank_ref_num'] ?? null;
                                            }
                                            if (!$bankRef && $transaction->response_message) {
                                                if (preg_match('/Bank Ref:\s*([^\s-]+)/i', $transaction->response_message, $matches)) {
                                                    $bankRef = $matches[1];
                                                }
                                            }
                                        @endphp
                                        {{ $bankRef ?? 'N/A' }}
                                    </td>
                                    <td style="font-size: 0.875rem; color: #1e3a8a;">
                                        @php
                                            $mode = null;
                                            if ($transaction->payu_response && is_array($transaction->payu_response)) {
                                                $mode = $transaction->payu_response['mode'] ?? null;
                                            }
                                        @endphp
                                        {{ $mode ?? 'N/A' }}
                                    </td>
                                    
                                    <td style="font-size: 0.875rem; color: #1e3a8a;">₹{{ number_format($transaction->amount, 2) }}</td>
                                    <td style="font-size: 0.875rem;">
                                        <span class="badge text-capitalize bg-{{ $transaction->payment_status === 'success' ? 'success' : ($transaction->payment_status === 'pending' ? 'warning' : 'danger') }}" style="font-size: 0.75rem;">
                                            {{ strtoupper($transaction->payment_status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No transactions found.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                @php
                    if ($transactions->total()) {
                        $txFirst = $transactions->firstItem();
                        $txLast = $transactions->lastItem();
                        $txTotal = $transactions->total();
                    } else {
                        $txFirst = 0;
                        $txLast = 0;
                        $txTotal = 0;
                    }
                @endphp
                <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap">
                    <div style="font-size: 0.875rem; color: #1e3a8a;">
                        Showing {{ $txFirst }} to {{ $txLast }} of {{ $txTotal }} entries
                    </div>
                    <div>
                        {{ $transactions->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admin Activity Log -->
@if(!$fromMembersPage)
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm" style="border-radius: 16px;">
            <div class="card-header users-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-capitalize text-white" style="font-weight: 600;">Admin Activity Log</h5>
                <div class="d-flex align-items-center gap-3">
                    <form method="GET" action="{{ route('admin.users.show', $user->id) }}" class="d-inline">
                        @if(request('from'))
                            <input type="hidden" name="from" value="{{ request('from') }}">
                        @endif
                        @if(request('stage'))
                            <input type="hidden" name="stage" value="{{ request('stage') }}">
                        @endif
                        @if(request('per_page_transactions'))
                            <input type="hidden" name="per_page_transactions" value="{{ request('per_page_transactions') }}">
                        @endif
                        <select name="per_page_actions" class="form-select form-select-sm users-per-page-select" onchange="this.form.submit()" style="color: #1e3a8a !important;">
                            <option value="10" {{ request('per_page_actions', 10) == 10 ? 'selected' : '' }} style="color: #1e3a8a !important;">10</option>
                            <option value="20" {{ request('per_page_actions', 10) == 20 ? 'selected' : '' }} style="color: #1e3a8a !important;">20</option>
                            <option value="50" {{ request('per_page_actions', 10) == 50 ? 'selected' : '' }} style="color: #1e3a8a !important;">50</option>
                            <option value="100" {{ request('per_page_actions', 10) == 100 ? 'selected' : '' }} style="color: #1e3a8a !important;">100</option>
                        </select>
                    </form>
                    <a href="{{ route('admin.users.admin-actions.export', $user->id) }}" class="btn btn-sm users-export-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8.5 6a.5.5 0 0 0-1 0v1.5H6a.5.5 0 0 0 0 1h1.5V10a.5.5 0 0 0 1 0V8.5H10a.5.5 0 0 0 0-1H8.5V6z"/>
                            <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2zm10-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1z"/>
                        </svg>
                        Export
                    </a>
                </div>
            </div>
            <div class="card-body p-2">
                @if($adminActions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="text-nowrap users-table-header">
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Admin</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($adminActions as $action)
                                <tr class="align-middle">
                                    <td style="font-size: 0.875rem; color: #1e3a8a;">{{ $action->created_at->format('M d, Y h:i A') }}</td>
                                    <td style="font-size: 0.875rem; color: #1e3a8a;">
                                        @if($action->superAdmin)
                                            <span class="badge bg-danger me-1">SuperAdmin</span>{{ $action->superAdmin->name }}
                                        @elseif($action->admin)
                                            {{ $action->admin->name }}
                                        @else
                                            System
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-capitalize">{{ ucfirst(str_replace('_', ' ', $action->action_type)) }}</span>
                                    </td>
                                    <td style="font-size: 0.875rem; color: #1e3a8a;">{{ $action->description }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($adminActions->hasPages())
                    @php
                        if ($adminActions->total()) {
                            $aaFirst = $adminActions->firstItem();
                            $aaLast = $adminActions->lastItem();
                            $aaTotal = $adminActions->total();
                        } else {
                            $aaFirst = 0;
                            $aaLast = 0;
                            $aaTotal = 0;
                        }
                    @endphp
                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap">
                        <div style="font-size: 0.875rem; color: #1e3a8a;">
                            Showing {{ $aaFirst }} to {{ $aaLast }} of {{ $aaTotal }} entries
                        </div>
                        <div>
                            {{ $adminActions->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    </div>
                    @endif
                @else
                    <p class="text-muted mb-0">No admin actions recorded.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@push('styles')
<style>
    /* Theme Colors */
    .users-card-header {
        background: #667eea;
        padding: 1rem 1.25rem;
    }
    
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
    
    /* Buttons */
    .users-update-email-btn {
        background: #667eea;
        color: #ffffff;
        border: none;
        font-weight: 500;
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
        border-radius: 6px;
        transition: all 0.25s ease;
    }
    
    .users-update-email-btn:hover {
        background: #5a67d8;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }
    
    .users-modal-header {
        background: #667eea;
    }
    
    .users-cancel-btn {
        background: #ef4444;
        color: #ffffff;
        border: none;
        font-weight: 500;
        padding: 0.5rem 1.25rem;
        border-radius: 8px;
    }
    
    .users-cancel-btn:hover {
        background: #dc2626;
        color: #ffffff;
    }
    
    .users-save-btn {
        background: #667eea;
        color: #ffffff;
        border: none;
        font-weight: 500;
        padding: 0.5rem 1.25rem;
        border-radius: 8px;
    }
    
    .users-save-btn:hover {
        background: #5a67d8;
        color: #ffffff;
    }
    
    .users-stage-filter {
        min-width: 150px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        color: #1e3a8a;
        font-weight: 500;
        padding: 0.375rem 0.75rem;
        border-radius: 8px;
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
    
    .users-export-btn {
        background: #10b981;
        color: #ffffff;
        border: none;
        font-weight: 500;
        font-size: 0.8rem;
        padding: 0.375rem 0.75rem;
        border-radius: 8px;
        transition: all 0.25s ease;
    }
    
    .users-export-btn:hover {
        background: #059669;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }
    
    /* Application Cards */
    .users-application-card {
        border: 1px solid #e5e7eb;
        background: #ffffff;
    }
    
    .users-app-card-header {
        background: #667eea;
        padding: 1rem 1.25rem;
    }
    
    .users-view-details-btn {
        background: #667eea;
        color: #ffffff;
        border: none;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.25s ease;
    }
    
    .users-view-details-btn:hover {
        background: #5a67d8;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }
    
    .users-manage-btn {
        background: #10b981;
        color: #ffffff;
        border: none;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.25s ease;
    }
    
    .users-manage-btn:hover {
        background: #059669;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }
    
    .users-message-btn {
        background: rgba(255, 255, 255, 0.2);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.3);
        font-weight: 500;
        font-size: 0.8rem;
        padding: 0.375rem 0.75rem;
        border-radius: 8px;
        transition: all 0.25s ease;
    }
    
    .users-message-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.5);
    }
    
    .users-back-btn {
        background: #667eea;
        color: #ffffff;
        border: none;
        font-weight: 500;
        padding: 0.5rem 1.25rem;
        border-radius: 10px;
        transition: all 0.25s ease;
    }
    
    .users-back-btn:hover {
        background: #5a67d8;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .users-delete-btn {
        background: #ef4444;
        color: #ffffff;
        border: none;
        font-weight: 500;
        padding: 0.5rem 1.25rem;
        border-radius: 10px;
        transition: all 0.25s ease;
    }
    
    .users-delete-btn:hover {
        background: #dc2626;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
    
    .accent-line {
        height: 3px;
        width: 60px;
        background: #667eea;
        border-radius: 2px;
        margin-top: 0.5rem;
    }
</style>
@endpush
</div>
@endsection
