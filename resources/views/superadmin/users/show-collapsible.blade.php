@extends('superadmin.layout')

@section('title', 'Registration Details')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1>Registration Details</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('superadmin.users') }}" class="btn btn-secondary">Back to Registrations</a>
        </div>
    </div>
</div>
<div class="alert alert-info mb-4">
    <strong>View-only:</strong> Super Admin can view all registration details here, but delete/update actions are disabled. Please use the Admin panel for any changes.
</div>

<!-- Collapsible Sections -->
<div class="row">
    <div class="col-12">
        <!-- Registration Details Section -->
        <div class="card mb-3 border-0 shadow-sm" style="border-radius: 16px;">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center" 
                 style="border-radius: 16px 16px 0 0; cursor: pointer;" 
                 data-bs-toggle="collapse" 
                 data-bs-target="#registrationDetails" 
                 aria-expanded="true"
                 onclick="toggleIcon(this)">
                <h5 class="mb-0">Registration Details</h5>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" class="toggle-icon">
                    <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                </svg>
            </div>
            <div id="registrationDetails" class="collapse show">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%" class="bg-light">Registration ID:</th>
                                    <td><strong>{{ $user->registrationid }}</strong></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Full Name:</th>
                                    <td>{{ $user->fullname }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Email:</th>
                                    <td>
                                        {{ $user->email }}
                                        @if($user->email_verified)
                                            <span class="badge bg-success">Verified</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Mobile:</th>
                                    <td>
                                        {{ $user->mobile }}
                                        @if($user->mobile_verified)
                                            <span class="badge bg-success">Verified</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">PAN Card:</th>
                                    <td>
                                        {{ $user->pancardno }}
                                        @if($user->pan_verified)
                                            <span class="badge bg-success ms-2">Verified</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Date of Birth:</th>
                                    <td>{{ $user->dateofbirth ? $user->dateofbirth->format('d M Y') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Registration Date:</th>
                                    <td>{{ $user->registrationdate ? $user->registrationdate->format('d M Y') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Status:</th>
                                    <td>
                                        <span class="badge bg-{{ $user->status === 'approved' || $user->status === 'active' ? 'success' : 'warning' }}">{{ ucfirst($user->status) }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Application Details Section -->
        <div class="card mb-3 border-0 shadow-sm" style="border-radius: 16px;">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center" 
                 style="border-radius: 16px 16px 0 0; cursor: pointer;" 
                 data-bs-toggle="collapse" 
                 data-bs-target="#applicationDetails" 
                 aria-expanded="true"
                 onclick="toggleIcon(this)">
                <h5 class="mb-0">Application Details ({{ $user->applications->count() }})</h5>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" class="toggle-icon">
                    <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                </svg>
            </div>
            <div id="applicationDetails" class="collapse show">
                <div class="card-body">
                    @if($user->applications->count() > 0)
                        @foreach($user->applications as $application)
                            @php
                                $allInvoices = $application->invoices ?? collect();
                                // Exclude cancelled and credit_note invoices from payment summary
                                $invoices = $allInvoices->filter(fn($inv) => !$inv->isCancelledOrHasCreditNote());
                                $latestInvoice = $invoices->first();
                                $outstandingAmount = $invoices->whereIn('payment_status', ['pending', 'partial'])->sum('balance_amount');
                                $lastPayment = $invoices->where('payment_status', 'paid')->sortByDesc('paid_at')->first();
                                $totalInvoiced = $invoices->sum('total_amount');
                                $totalPaid = $invoices->sum('paid_amount');
                            @endphp
                            <div class="card mb-4 border">
                                <div class="card-header bg-light">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0"><strong>Application ID:</strong> {{ $application->application_id }}</h6>
                                            @if($application->membership_id)
                                                <h6 class="mb-0 mt-1"><strong>Membership ID:</strong> {{ $application->membership_id }}</h6>
                                            @endif
                                        </div>
                                        <div>
                                            @if($application->is_active)
                                                <span class="badge bg-success fs-6">LIVE</span>
                                            @else
                                                <span class="badge bg-danger fs-6">NOT LIVE</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="mb-3">IP & Port Details:</h6>
                                            <table class="table table-borderless table-sm">
                                                @if($application->assigned_ip)
                                                <tr>
                                                    <th width="40%">IP Address:</th>
                                                    <td><strong>{{ $application->assigned_ip }}</strong></td>
                                                </tr>
                                                @endif
                                                @if($application->assigned_port_number)
                                                <tr>
                                                    <th>Port Number:</th>
                                                    <td><strong>{{ $application->assigned_port_number }}</strong></td>
                                                </tr>
                                                @endif
                                                @if($application->assigned_port_capacity)
                                                <tr>
                                                    <th>Port Capacity:</th>
                                                    <td><strong>{{ $application->assigned_port_capacity }}</strong></td>
                                                </tr>
                                                @endif
                                                @if($application->customer_id)
                                                <tr>
                                                    <th>Customer ID:</th>
                                                    <td><strong>{{ $application->customer_id }}</strong></td>
                                                </tr>
                                                @endif
                                                @if($application->service_activation_date)
                                                <tr>
                                                    <th>Service Activation:</th>
                                                    <td><strong>{{ $application->service_activation_date->format('d M Y') }}</strong></td>
                                                </tr>
                                                @endif
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="mb-3">Invoice Summary:</h6>
                                            <table class="table table-borderless table-sm">
                                                <tr>
                                                    <th width="50%">Total Invoiced:</th>
                                                    <td><strong>₹{{ number_format($totalInvoiced, 2) }}</strong></td>
                                                </tr>
                                                <tr>
                                                    <th>Total Paid:</th>
                                                    <td><strong>₹{{ number_format($totalPaid, 2) }}</strong></td>
                                                </tr>
                                                <tr>
                                                    <th>Outstanding:</th>
                                                    <td>
                                                        <strong class="text-{{ $outstandingAmount > 0 ? 'danger' : 'success' }}">
                                                            ₹{{ number_format($outstandingAmount, 2) }}
                                                        </strong>
                                                    </td>
                                                </tr>
                                                @if($lastPayment)
                                                <tr>
                                                    <th>Last Payment:</th>
                                                    <td>
                                                        <strong>₹{{ number_format($lastPayment->paid_amount, 2) }}</strong>
                                                        <br><small class="text-muted">{{ $lastPayment->paid_at ? $lastPayment->paid_at->format('d M Y') : 'N/A' }}</small>
                                                    </td>
                                                </tr>
                                                @endif
                                                @if($latestInvoice)
                                                <tr>
                                                    <th>Latest Invoice:</th>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2 mb-1">
                                                            <strong>{{ $latestInvoice->invoice_number }}</strong>
                                                            <a href="{{ route('superadmin.invoices.download', $latestInvoice->id) }}" 
                                                               class="btn btn-sm btn-outline-success" 
                                                               target="_blank"
                                                               title="Download Invoice PDF">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                                                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                                                                </svg>
                                                            </a>
                                                        </div>
                                                        <br><small class="text-muted">{{ $latestInvoice->invoice_date->format('d M Y') }}</small>
                                                        <br><span class="badge bg-{{ $latestInvoice->payment_status === 'paid' ? 'success' : ($latestInvoice->payment_status === 'partial' ? 'warning' : 'danger') }}">
                                                            {{ strtoupper($latestInvoice->payment_status) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                @endif
                                            </table>
                                        </div>
                                    </div>
                                    <div class="mt-3 d-flex gap-2">
                                        <a href="{{ route('superadmin.applications.show', $application->id) }}" class="btn btn-primary">View Full Application Details</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-info">
                            <strong>No Applications Found:</strong> This user does not have any applications.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Admin Activity Log</h5>
            </div>
            <div class="card-body">
                @if($adminActions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Admin</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($adminActions as $action)
                                <tr>
                                    <td>{{ $action->created_at->format('M d, Y h:i A') }}</td>
                                    <td>
                                        @if($action->superAdmin)
                                            <span class="badge bg-danger">SuperAdmin</span> {{ $action->superAdmin->name }}
                                        @elseif($action->admin)
                                            {{ $action->admin->name }}
                                        @else
                                            System
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $action->action_type)) }}</span>
                                    </td>
                                    <td>{{ $action->description }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">No admin actions recorded.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.toggle-icon {
    transition: transform 0.3s ease;
}
.toggle-icon.rotated {
    transform: rotate(180deg);
}
</style>

<script>
function toggleIcon(element) {
    const icon = element.querySelector('.toggle-icon');
    if (icon) {
        icon.classList.toggle('rotated');
    }
}
</script>
@endsection

