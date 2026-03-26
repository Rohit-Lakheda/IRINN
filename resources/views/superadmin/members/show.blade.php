@extends('superadmin.layout')

@section('title', 'Member Details')

@section('content')
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h2 class="mb-1 fw-semibold text-navy border-0">Member Details</h2>
        </div>
        <a href="{{ route('superadmin.members') }}" class="btn btn-primary px-4">Back to Members</a>
    </div>
    <div class="accent-line"></div>
</div>

<div class="row mb-4 d-none">
    <div class="col-md-12">
        <h1>Member Details</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('superadmin.members') }}" class="btn btn-secondary">Back to Members</a>
        </div>
    </div>
</div>

<!-- Collapsible Sections -->
<div class="row">
    <div class="col-12">
        <!-- Registration Details Section -->
        <div class="card mb-3 border-c-blue shadow-sm" style="border-radius: 16px;">
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
                        <div class="col-md-12 col-lg-12">
                            <table class="table table-bordered border-dark">
                                <tr class="align-middle">
                                    <th width="200">Registration ID:</th>
                                    <td><strong>{{ $user->registrationid }}</strong></td>
                                </tr>
                                <tr class="align-middle">
                                    <th>Full Name:</th>
                                    <td class="text-navy fw-medium">{{ $user->fullname }}</td>
                                </tr>
                                <tr class="align-middle">
                                    <th>Email:</th>
                                    <td class="d-flex align-item-center justify-content-between gap-1 flex-wrap">
                                        <span class="text-navy fw-medium">{{ $user->email }}</span>
                                        @if($user->email_verified)
                                            <span class="badge bg-success">Verified</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="align-middle">
                                    <th>Mobile:</th>
                                    <td class="d-flex align-item-center justify-content-between gap-1 flex-wrap">
                                        <span class="text-navy fw-medium">{{ $user->mobile }}</span>
                                        @if($user->mobile_verified)
                                            <span class="badge bg-success">Verified</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="align-middle">
                                    <th>PAN Card:</th>
                                    <td class="d-flex align-item-center justify-content-between gap-1 flex-wrap">
                                        <span class="text-navy fw-medium">{{ $user->pancardno }}</span>
                                        @if($user->pan_verified)
                                            <span class="badge bg-success ms-2">Verified</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="align-middle">
                                    <th>Date of Birth:</th>
                                    <td class="text-navy fw-medium">{{ $user->dateofbirth ? $user->dateofbirth->format('d M Y') : 'N/A' }}</td>
                                </tr>
                                <tr class="align-middle">
                                    <th>Registration Date:</th>
                                    <td class="text-navy fw-medium">{{ $user->registrationdate ? $user->registrationdate->format('d M Y') : 'N/A' }}</td>
                                </tr>
                                <tr class="align-middle">
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge bg-{{ $user->status === 'approved' || $user->status === 'active' ? 'success' : 'yellow' }}">{{ ucfirst($user->status) }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Application Details Section -->
        <div class="card mb-3 border-c-blue shadow-sm" style="border-radius: 16px;">
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
                            <div class="card border-c-blue mb-0 shadow-sm">
                                <div class="card-header theme-bg-blue">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap">
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
                                        <div class="col-md-12">
                                            <h6 class="mb-3">IP & Port Details:</h6>
                                            <div class="table-responsive">
                                                <table class="table table-bordered border-dark">
                                                    @if($application->assigned_ip)
                                                    <tr class="align-middle">
                                                        <th width="200">IP Address:</th>
                                                        <td><strong>{{ $application->assigned_ip }}</strong></td>
                                                    </tr>
                                                    @endif
                                                    @if($application->assigned_port_number)
                                                    <tr class="align-middle">
                                                        <th width="200">Port Number:</th>
                                                        <td><strong>{{ $application->assigned_port_number }}</strong></td>
                                                    </tr>
                                                    @endif
                                                    @if($application->assigned_port_capacity)
                                                    <tr class="align-middle">
                                                        <th width="200">Port Capacity:</th>
                                                        <td><strong>{{ $application->assigned_port_capacity }}</strong></td>
                                                    </tr>
                                                    @endif
                                                    @if($application->customer_id)
                                                    <tr class="align-middle">
                                                        <th width="200">Customer ID:</th>
                                                        <td><strong>{{ $application->customer_id }}</strong></td>
                                                    </tr>
                                                    @endif
                                                    @if($application->service_activation_date)
                                                    <tr class="align-middle">
                                                        <th width="200">Service Activation:</th>
                                                        <td><strong>{{ $application->service_activation_date->format('d M Y') }}</strong></td>
                                                    </tr>
                                                    @endif
                                                </table>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <h6 class="mb-3">Invoice Summary:</h6>
                                            <div class="table-responsive">
                                            <table class="table table-bordered border-dark">
                                                <tr class="align-middle">
                                                    <th width="200">Total Invoiced:</th>
                                                    <td><strong>₹{{ number_format($totalInvoiced, 2) }}</strong></td>
                                                </tr>
                                                <tr class="align-middle">
                                                    <th>Total Paid:</th>
                                                    <td><strong>₹{{ number_format($totalPaid, 2) }}</strong></td>
                                                </tr>
                                                <tr class="align-middle">
                                                    <th>Outstanding:</th>
                                                    <td>
                                                        <strong class="text-{{ $outstandingAmount > 0 ? 'danger' : 'success' }}">
                                                            ₹{{ number_format($outstandingAmount, 2) }}
                                                        </strong>
                                                    </td>
                                                </tr>
                                                @if($lastPayment)
                                                <tr class="align-middle">
                                                    <th>Last Payment:</th>
                                                    <td>
                                                        <strong>₹{{ number_format($lastPayment->paid_amount, 2) }}</strong>
                                                        <br><small class="text-muted">{{ $lastPayment->paid_at ? $lastPayment->paid_at->format('d M Y') : 'N/A' }}</small>
                                                    </td>
                                                </tr>
                                                @endif
                                                @if($latestInvoice)
                                                <tr>
                                                    <th class="py-3">Latest Invoice:</th>
                                                    <td>
                                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                                            <strong>{{ $latestInvoice->invoice_number }}</strong>
                                                            <a href="{{ route('superadmin.invoices.download', $latestInvoice->id) }}" 
                                                               class="btn btn-sm btn-success" 
                                                               target="_blank"
                                                               title="Download Invoice PDF">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                                                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                                                                </svg>
                                                                Download
                                                            </a>
                                                        </div>
                                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                                            <small class="text-muted">{{ $latestInvoice->invoice_date->format('d M Y') }}</small>
                                                            <span class="badge bg-{{ $latestInvoice->payment_status === 'paid' ? 'success' : ($latestInvoice->payment_status === 'partial' ? 'warning' : 'danger') }}">
                                                                {{ strtoupper($latestInvoice->payment_status) }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endif
                                            </table>
                                        </div>
                                    </div>
                                    <div class="mt-1 d-flex gap-2">
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

