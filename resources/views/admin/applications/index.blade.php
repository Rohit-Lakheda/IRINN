@extends('admin.layout')

@section('title', 'Applications')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="flex-wrap">
                    <h2 class="mb-1 border-0" style="color: #1e3a8a; font-weight: 600;">Applications</h2>
                    <p class="mb-2 text-muted">
                        @php
                            $roleLabels = [
                                'helpdesk' => 'Helpdesk',
                                'hostmaster' => 'Hostmaster',
                                'billing' => 'Billing',
                            ];
                            $selectedRoleLabel = null;
                            if (isset($selectedRole)) {
                                $selectedRoleLabel = $roleLabels[$selectedRole] ?? ucfirst(str_replace('_', ' ', $selectedRole));
                            }
                        @endphp
                        @if($selectedRoleLabel)
                            Viewing as: <strong>{{ $selectedRoleLabel }}</strong>
                        @else
                            View and manage applications
                        @endif
                    </p>
                </div>
            </div>
            <div class="accent-line"></div>
        </div>
    </div>

<!-- Filters and Search Form -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm users-search-card" style="border-radius: 16px;">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.applications') }}" class="theme-forms">
                    @if(request('role'))
                        <input type="hidden" name="role" value="{{ request('role') }}">
                    @endif
                    
                    <!-- Row 1: Search + Filters -->
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-6 col-md-12">
                            <label class="form-label fw-bold">Search</label>
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Search by application ID, applicant name, email, registration ID, membership ID, customer ID, mobile, or status..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label fw-bold">Assigned Role</label>
                            <select name="role_filter" class="form-select users-stage-filter">
                                <option value="">All Roles</option>
                                @if(isset($assignedWorkflowRoles) && $assignedWorkflowRoles->count())
                                    @foreach($assignedWorkflowRoles as $role)
                                        <option value="{{ $role['slug'] }}" {{ request('role_filter') === $role['slug'] ? 'selected' : '' }}>
                                            {{ $role['name'] }}
                                        </option>
                                    @endforeach
                                @else
                                    <option value="helpdesk" {{ request('role_filter') === 'helpdesk' ? 'selected' : '' }}>Helpdesk</option>
                                    <option value="hostmaster" {{ request('role_filter') === 'hostmaster' ? 'selected' : '' }}>Hostmaster</option>
                                    <option value="billing" {{ request('role_filter') === 'billing' ? 'selected' : '' }}>Billing</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label fw-bold">Registration Date</label>
                            <select name="registration_filter" class="form-select users-stage-filter">
                                <option value="">All Time</option>
                                <option value="today" {{ request('registration_filter') === 'today' ? 'selected' : '' }}>Today</option>
                                <option value="this_week" {{ request('registration_filter') === 'this_week' ? 'selected' : '' }}>This Week</option>
                                <option value="this_month" {{ request('registration_filter') === 'this_month' ? 'selected' : '' }}>This Month</option>
                                <option value="this_year" {{ request('registration_filter') === 'this_year' ? 'selected' : '' }}>This Year</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Row 2: Action Buttons -->
                    <div class="row g-2 mt-3">
                        <div class="col-md-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn users-search-btn">Apply Filters</button>
                            <a href="{{ route('admin.applications', ['role' => request('role')]) }}" class="btn users-clear-btn">Clear Filters</a>
                            <a href="{{ route('admin.applications.export', request()->all()) }}" class="btn users-export-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8.5 6a.5.5 0 0 0-1 0v1.5H6a.5.5 0 0 0 0 1h1.5V10a.5.5 0 0 0 1 0V8.5H10a.5.5 0 0 0 0-1H8.5V6z"/>
                                    <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2zm10-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1z"/>
                                </svg>
                                Export to Excel
                            </a>
                        </div>
                    </div>
                    
                    @if(request('search') || request('role_filter') || request('registration_filter'))
                    <div class="row mt-2">
                        <div class="col-12">
                            @php
                                $roleLabels = [
                                    'ix_processor' => 'Helpdesk',
                                    'ix_head' => 'Hostmaster',
                                    'ix_account' => 'Billing',
                                ];
                                $activeRoleLabel = null;
                                if (request('role_filter')) {
                                    $activeRoleLabel = $roleLabels[request('role_filter')] ?? ucfirst(str_replace('_', ' ', request('role_filter')));
                                }
                            @endphp
                            <small class="text-muted">
                                Active filters: 
                                @if(request('search'))<span class="badge bg-info">{{ request('search') }}</span>@endif
                                @if($activeRoleLabel)<span class="badge bg-info">Role: {{ $activeRoleLabel }}</span>@endif
                                @if(request('registration_filter'))<span class="badge bg-info">Date: {{ ucfirst(str_replace('_', ' ', request('registration_filter'))) }}</span>@endif
                            </small>
                        </div>
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
            <div class="card-header users-card-header d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                <h5 class="mb-0 text-capitalize fw-semibold text-white">Applications List</h5>
                <form method="GET" action="{{ route('admin.applications') }}" class="d-inline">
                    @if(request('role'))
                        <input type="hidden" name="role" value="{{ request('role') }}">
                    @endif
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    @if(request('role_filter'))
                        <input type="hidden" name="role_filter" value="{{ request('role_filter') }}">
                    @endif
                    @if(request('registration_filter'))
                        <input type="hidden" name="registration_filter" value="{{ request('registration_filter') }}">
                    @endif
                    <select name="per_page" class="form-select form-select-sm users-per-page-select" onchange="this.form.submit()" style="color: #1e3a8a !important;">
                        <option value="10" {{ request('per_page', 20) == 10 ? 'selected' : '' }} style="color: #1e3a8a !important;">10</option>
                        <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }} style="color: #1e3a8a !important;">20</option>
                        <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }} style="color: #1e3a8a !important;">50</option>
                        <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }} style="color: #1e3a8a !important;">100</option>
                    </select>
                </form>
            </div>
            <div class="card-body">
                @if($applications->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="text-nowrap users-table-header">
                                <tr>
                                    <th>Application ID</th>
                                    <th>Applicant Name</th>
                                    <th>Details</th>
                                    <th>Status</th>
                                    <th>Submitted At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($applications as $application)
                                <tr class="align-middle">
                                    <td><strong style="color: #1e3a8a;">{{ $application->application_id }}</strong></td>
                                    <td>
                                        <a href="{{ route('admin.users.show', $application->user_id) }}" style="color: #1e3a8a; text-decoration: none;">
                                            {{ $application->user->fullname }}
                                        </a><br>
                                        <small class="text-muted">{{ $application->user->email }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $data = $application->application_data ?? [];
                                        @endphp
                                        @if($application->application_type === 'IRINN')
                                            @if($application->hasIrinnNormalizedData())
                                                <div>
                                                    @if(filled($application->irinn_ipv4_resource_size))
                                                        <div style="color:#1e3a8a;font-size:0.875rem;"><strong>IPv4:</strong> {{ $application->irinn_ipv4_resource_size }}</div>
                                                    @endif
                                                    @if(filled($application->irinn_ipv6_resource_size))
                                                        <div style="color:#1e3a8a;font-size:0.875rem;"><strong>IPv6:</strong> {{ $application->irinn_ipv6_resource_size }}</div>
                                                    @endif
                                                    @if(! filled($application->irinn_ipv4_resource_size) && ! filled($application->irinn_ipv6_resource_size))
                                                        <div class="text-muted small">No IP resource sizes recorded</div>
                                                    @endif
                                                    <div class="mt-1">
                                                        <small class="text-muted">ASN Required:</small>
                                                        <span style="color:#1e3a8a;font-size:0.85rem;">
                                                            {{ $application->irinn_asn_required ? 'Yes' : 'No' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @else
                                            @php
                                                $part2 = $data['part2'] ?? [];
                                                $ipv4 = $part2['ipv4_prefix'] ?? null;
                                                $ipv6 = $part2['ipv6_prefix'] ?? null;
                                                $asnRequired = $part2['asn_required'] ?? null;
                                            @endphp
                                            <div>
                                                @if($ipv4)
                                                    <div style="color:#1e3a8a;font-size:0.875rem;"><strong>IPv4:</strong> {{ $ipv4 }}</div>
                                                @endif
                                                @if($ipv6)
                                                    <div style="color:#1e3a8a;font-size:0.875rem;"><strong>IPv6:</strong> {{ $ipv6 }}</div>
                                                @endif
                                                @if(! $ipv4 && ! $ipv6)
                                                    <div class="text-muted small">No IP prefixes selected</div>
                                                @endif
                                                <div class="mt-1">
                                                    <small class="text-muted">ASN Required:</small>
                                                    <span style="color:#1e3a8a;font-size:0.85rem;">
                                                        {{ ($asnRequired === 'yes') ? 'Yes' : 'No' }}
                                                    </span>
                                                </div>
                                            </div>
                                            @endif
                                        @else
                                            @php
                                                $locationData = $data['location'] ?? null;
                                            @endphp
                                            @if($locationData)
                                                <div>{{ $locationData['name'] ?? 'N/A' }}</div>
                                                @if(isset($locationData['node_type']))
                                                    <small class="text-muted">{{ ucfirst($locationData['node_type']) }}</small>
                                                @endif
                                                @if(isset($locationData['state']))
                                                    <br><small class="text-muted">{{ $locationData['state'] }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if($application->application_type === 'IRINN')
                                            {{-- IRINN simplified workflow statuses --}}
                                            @if(in_array($application->status, ['helpdesk', 'submitted'], true))
                                                <span class="badge bg-warning text-dark">Helpdesk</span>
                                            @elseif($application->status === 'hostmaster')
                                                <span class="badge bg-info text-dark">Hostmaster</span>
                                            @elseif($application->status === 'billing')
                                                <span class="badge bg-success">Billing</span>
                                            @elseif($application->status === 'billing_approved')
                                                <span class="badge bg-success">Billing approved</span>
                                            @elseif($application->status === 'resubmission_requested')
                                                <span class="badge bg-danger">Resubmission Requested</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($application->status ?? 'unknown') }}</span>
                                            @endif
                                        @elseif($application->application_type === 'IX')
                                            {{-- IX Workflow Statuses (unchanged) --}}
                                            @if($application->status === 'approved' || $application->status === 'payment_verified')
                                                <span class="badge bg-success">Approved</span>
                                            @elseif($application->status === 'rejected' || $application->status === 'ceo_rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                            @elseif(in_array($application->status, ['submitted', 'resubmitted', 'processor_resubmission', 'legal_sent_back', 'head_sent_back']))
                                                <span class="badge bg-warning">IX Processor Review</span>
                                            @elseif($application->status === 'processor_forwarded_legal')
                                                <span class="badge bg-info">IX Legal Review</span>
                                            @elseif(in_array($application->status, ['legal_forwarded_head', 'ceo_sent_back_head']))
                                                <span class="badge bg-primary">IX Head Review</span>
                                            @elseif($application->status === 'head_forwarded_ceo')
                                                <span class="badge" style="background-color: #6f42c1; color: white;">CEO Review</span>
                                            @elseif($application->status === 'ceo_approved')
                                                <span class="badge bg-info">Nodal Officer Review</span>
                                            @elseif($application->status === 'port_assigned')
                                                <span class="badge bg-primary">IX Tech Team Review</span>
                                            @elseif(in_array($application->status, ['ip_assigned', 'invoice_pending']))
                                                <span class="badge bg-warning">IX Account Review</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $application->status_display }}</span>
                                            @endif
                                        @else
                                            {{-- Legacy non-IX / non-IRINN --}}
                                            <span class="badge bg-secondary">{{ $application->status_display }}</span>
                                        @endif
                                    </td>
                                    <td style="color: #1e3a8a;">{{ $application->submitted_at ? $application->submitted_at->format('d M Y, h:i A') : 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('admin.applications.show', $application->id) }}" class="btn btn-sm users-view-btn">View Details</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @php
                        if ($applications->total()) {
                            $first = $applications->firstItem();
                            $last = $applications->lastItem();
                            $total = $applications->total();
                        } else {
                            $first = 0;
                            $last = 0;
                            $total = 0;
                        }
                    @endphp
                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap">
                        <div style="font-size: 0.875rem; color: #1e3a8a;">
                            Showing {{ $first }} to {{ $last }} of {{ $total }} entries
                        </div>
                        <div>
                            {{ $applications->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" viewBox="0 0 16 16" class="text-muted mb-3">
                            <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                        </svg>
                        <p class="text-muted">No applications available at this stage.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

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

    .users-stage-filter {
        min-width: 150px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        color: #1e3a8a;
        font-weight: 500;
        padding: 0.375rem 0.75rem;
        border-radius: 8px;
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

    .accent-line {
        height: 3px;
        width: 60px;
        background: #667eea;
        border-radius: 2px;
        margin-top: 0.5rem;
    }
</style>
@endpush

