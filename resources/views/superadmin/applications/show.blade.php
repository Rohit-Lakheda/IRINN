@extends('superadmin.layout')

@section('title', 'Application Details - ' . $application->application_id)

@section('content')
<div class="container-fluid px-2 py-0">
    <!-- Page Header -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2 class="mb-1 fw-semibold text-navy border-0">Application Details</h2>
                <p class="text-muted mb-2">Application ID: <strong>{{ $application->application_id }}</strong></p>
            </div>
            <div>
                <a href="{{ route('superadmin.applications.index') }}" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 1 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                    </svg>
                    Back to List
                </a>
            </div>
        </div>
        <div class="accent-line"></div>
    </div>

    <div class="alert alert-info mb-4" style="border-radius: 16px;">
        <strong>View-only:</strong> Super Admin can view all application details here, but approval/disapproval/actions are disabled. Please use the Admin panel for workflow actions.
    </div>

    <div class="row g-4">
        <!-- Application Information -->
        <div class="col-md-7 col-lg-8">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Application Information</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered border-dark">
                            <tr class="align-middle">
                                <th width="200">Application ID:</th>
                                <td><strong>{{ $application->application_id }}</strong></td>
                            </tr>
                            <tr class="align-middle">
                                <th>User:</th>
                                <td>
                                    <a href="{{ route('superadmin.users.show', $application->user_id) }}" class="fw-semibold">
                                        {{ $application->user->fullname ?? 'N/A' }}
                                    </a><br>
                                    <small class="text-muted">{{ $application->user->email ?? 'N/A' }}</small>
                                </td>
                            </tr>
                            <tr class="align-middle">
                                <th>Status:</th>
                                <td>
                                    <span class="badge rounded-pill px-3 py-1
                                        @if($application->status === 'approved' || $application->status === 'payment_verified') bg-success
                                        @elseif(in_array($application->status, ['ip_assigned', 'invoice_pending'])) bg-info
                                        @elseif($application->status === 'rejected' || $application->status === 'ceo_rejected') bg-danger
                                        @else bg-primary @endif">
                                        {{ $application->status_display }}
                                    </span>
                                </td>
                            </tr>
                            <tr class="align-middle">
                                <th>Live Status:</th>
                                <td>
                                    @if($application->is_active)
                                        <span class="badge bg-success">LIVE</span>
                                    @else
                                        <span class="badge bg-secondary">NOT LIVE</span>
                                    @endif
                                </td>
                            </tr>
                            @if($application->membership_id)
                            <tr class="align-middle">
                                <th>Membership ID:</th>
                                <td><strong>{{ $application->membership_id }}</strong></td>
                            </tr>
                            @endif
                            @if($application->assigned_port_capacity)
                            <tr class="align-middle">
                                <th>Assigned Port Capacity:</th>
                                <td><strong>{{ $application->assigned_port_capacity }}</strong></td>
                            </tr>
                            @endif
                            @if($application->assigned_port_number)
                            <tr class="align-middle">
                                <th>Assigned Port Number:</th>
                                <td><strong>{{ $application->assigned_port_number }}</strong></td>
                            </tr>
                            @endif
                            @if($application->customer_id)
                            <tr class="align-middle">
                                <th>Customer ID:</th>
                                <td><strong>{{ $application->customer_id }}</strong></td>
                            </tr>
                            @endif
                            @if($application->assigned_ip)
                            <tr class="align-middle">
                                <th>Assigned IP:</th>
                                <td><strong>{{ $application->assigned_ip }}</strong></td>
                            </tr>
                            @endif
                            @if($application->service_activation_date)
                            <tr class="align-middle">
                                <th>Service Activation Date:</th>
                                <td><strong>{{ \Carbon\Carbon::parse($application->service_activation_date)->format('d M Y') }}</strong></td>
                            </tr>
                            @endif
                            @if($application->billing_cycle)
                            <tr class="align-middle">
                                <th>Billing Cycle:</th>
                                <td><strong>{{ ucfirst($application->billing_cycle) }}</strong></td>
                            </tr>
                            @endif
                            <tr class="align-middle">
                                <th>Current Stage:</th>
                                <td><span class="badge bg-light text-dark ps-0">{{ $application->current_stage }}</span></td>
                            </tr>
                            <tr class="align-middle">
                                <th>Submitted At:</th>
                                <td class="fw-semibold">{{ $application->submitted_at ? $application->submitted_at->format('d M Y, h:i A') : 'N/A' }}</td>
                            </tr>
                            @if($application->approved_at)
                            <tr class="align-middle">
                                <th>Approved At:</th>
                                <td>{{ $application->approved_at->format('d M Y, h:i A') }}</td>
                            </tr>
                            @endif
                            @if($application->rejection_reason)
                            <tr class="align-middle">
                                <th>Rejection Reason:</th>
                                <td class="text-danger">{{ $application->rejection_reason }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            <!-- Application Data (if available) -->
            @if($application->application_data)
            @php
                $appData = $application->application_data;
            @endphp
            <div class="card border-c-blue shadow-sm mt-4" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Application Data</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <!-- GSTIN -->
                        @if(isset($appData['gstin']))
                        <div class="col-12">
                            <div class="border-bottom pb-3">
                                <h6 class="text-primary mb-2 fw-semibold">GSTIN</h6>
                                <p class="mb-0 fs-6">{{ $appData['gstin'] }}</p>
                            </div>
                        </div>
                        @endif

                        <!-- Location Details -->
                        @if(isset($appData['location']))
                        <div class="col-12">
                            <div class="border-bottom pb-3">
                                <h6 class="text-primary mb-3 fw-semibold">Location Details</h6>
                                <div class="row g-3">
                                    @if(isset($appData['location']['name']))
                                    <div class="col-md-6">
                                        <strong>Location Name:</strong>
                                        <p class="mb-0">{{ $appData['location']['name'] }}</p>
                                    </div>
                                    @endif
                                    @if(isset($appData['location']['state']))
                                    <div class="col-md-6">
                                        <strong>State:</strong>
                                        <p class="mb-0">{{ $appData['location']['state'] }}</p>
                                    </div>
                                    @endif
                                    @if(isset($appData['location']['node_type']))
                                    <div class="col-md-6">
                                        <strong>Node Type:</strong>
                                        <p class="mb-0"><span class="badge bg-info text-dark">{{ ucfirst($appData['location']['node_type']) }}</span></p>
                                    </div>
                                    @endif
                                    @if(isset($appData['location']['switch_details']))
                                    <div class="col-md-6">
                                        <strong>Switch Details:</strong>
                                        <p class="mb-0">{{ $appData['location']['switch_details'] }}</p>
                                    </div>
                                    @endif
                                    @if(isset($appData['location']['nodal_officer']))
                                    <div class="col-md-6">
                                        <strong>Nodal Officer:</strong>
                                        <p class="mb-0">{{ $appData['location']['nodal_officer'] }}</p>
                                    </div>
                                    @endif
                                    @if(isset($appData['location']['id']))
                                    <div class="col-md-6">
                                        <strong>Location ID:</strong>
                                        <p class="mb-0">{{ $appData['location']['id'] }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Port Selection -->
                        @if(isset($appData['port_selection']))
                        <div class="col-12">
                            <div class="border-bottom pb-3">
                                <h6 class="text-primary mb-3 fw-semibold">Port Selection</h6>
                                <div class="row g-3">
                                    @if(isset($appData['port_selection']['capacity']))
                                    <div class="col-md-6">
                                        <strong>Capacity:</strong>
                                        <p class="mb-0 fs-6 fw-semibold">{{ $appData['port_selection']['capacity'] }}</p>
                                    </div>
                                    @endif
                                    @if(isset($appData['port_selection']['number']))
                                    <div class="col-md-6">
                                        <strong>Port Number:</strong>
                                        <p class="mb-0">{{ $appData['port_selection']['number'] }}</p>
                                    </div>
                                    @endif
                                    @if(isset($appData['port_selection']['billing_plan']))
                                    <div class="col-md-6">
                                        <strong>Billing Plan:</strong>
                                        <p class="mb-0"><span class="badge bg-secondary">{{ strtoupper($appData['port_selection']['billing_plan']) }}</span></p>
                                    </div>
                                    @endif
                                    @if(isset($appData['port_selection']['amount']))
                                    <div class="col-md-6">
                                        <strong>Amount:</strong>
                                        <p class="mb-0 fs-6 fw-semibold text-success">
                                            {{ $appData['port_selection']['currency'] ?? 'INR' }} {{ number_format($appData['port_selection']['amount'], 2) }}
                                        </p>
                                    </div>
                                    @endif
                                    @if(isset($appData['port_selection']['currency']))
                                    <div class="col-md-6">
                                        <strong>Currency:</strong>
                                        <p class="mb-0">{{ $appData['port_selection']['currency'] }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- IP Prefix -->
                        @if(isset($appData['ip_prefix']))
                        <div class="col-12">
                            <div class="border-bottom pb-3">
                                <h6 class="text-primary mb-3 fw-semibold">IP Prefix</h6>
                                <div class="row g-3">
                                    @if(isset($appData['ip_prefix']['count']))
                                    <div class="col-md-4">
                                        <strong>Count:</strong>
                                        <p class="mb-0">{{ $appData['ip_prefix']['count'] ?? 'N/A' }}</p>
                                    </div>
                                    @endif
                                    @if(isset($appData['ip_prefix']['source']))
                                    <div class="col-md-4">
                                        <strong>Source:</strong>
                                        <p class="mb-0">{{ $appData['ip_prefix']['source'] ?? 'N/A' }}</p>
                                    </div>
                                    @endif
                                    @if(isset($appData['ip_prefix']['provider']))
                                    <div class="col-md-4">
                                        <strong>Provider:</strong>
                                        <p class="mb-0">{{ $appData['ip_prefix']['provider'] ?? 'N/A' }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Peering -->
                        @if(isset($appData['peering']))
                        <div class="col-12">
                            <div class="border-bottom pb-3">
                                <h6 class="text-primary mb-3 fw-semibold">Peering Details</h6>
                                <div class="row g-3">
                                    @if(isset($appData['peering']['asn_number']))
                                    <div class="col-md-6">
                                        <strong>ASN Number:</strong>
                                        <p class="mb-0 fs-6 fw-semibold">{{ $appData['peering']['asn_number'] }}</p>
                                    </div>
                                    @endif
                                    @if(isset($appData['peering']['asn_name']))
                                    <div class="col-md-6">
                                        <strong>ASN Name:</strong>
                                        <p class="mb-0">{{ $appData['peering']['asn_name'] }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Billing -->
                        @if(isset($appData['billing']))
                        <div class="col-12">
                            <div class="pb-3">
                                <h6 class="text-primary mb-3 fw-semibold">Billing Information</h6>
                                <div class="row g-3">
                                    @if(isset($appData['billing']['plan']))
                                    <div class="col-md-6">
                                        <strong>Billing Plan:</strong>
                                        <p class="mb-0"><span class="badge bg-info text-dark">{{ ucfirst($appData['billing']['plan']) }}</span></p>
                                    </div>
                                    @endif
                                    @if(isset($appData['billing']['cycle']))
                                    <div class="col-md-6">
                                        <strong>Billing Cycle:</strong>
                                        <p class="mb-0">{{ ucfirst($appData['billing']['cycle']) }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Additional Fields (for any other data not covered above) -->
                        @php
                            $coveredKeys = ['gstin', 'location', 'port_selection', 'ip_prefix', 'peering', 'billing'];
                            $otherData = array_diff_key($appData, array_flip($coveredKeys));
                        @endphp
                        @if(!empty($otherData))
                        <div class="col-12">
                            <div class="pb-3">
                                <h6 class="text-primary mb-3 fw-semibold">Additional Information</h6>
                                <div class="row g-3">
                                    @foreach($otherData as $key => $value)
                                        @if(is_array($value) && array_keys($value) !== range(0, count($value) - 1))
                                            <!-- Complex nested object -->
                                            <div class="col-12">
                                                <div class="border-bottom pb-3 mb-3">
                                                    <h6 class="text-primary mb-3 fw-semibold">{{ ucwords(str_replace('_', ' ', $key)) }}</h6>
                                                    <div class="row g-3">
                                                        @foreach($value as $subKey => $subValue)
                                                            <div class="col-md-6">
                                                                <strong>{{ ucwords(str_replace('_', ' ', $subKey)) }}:</strong>
                                                                <p class="mb-0">
                                                                    @if(is_array($subValue))
                                                                        @if(empty($subValue))
                                                                            <span class="text-muted">N/A</span>
                                                                        @else
                                                                            @foreach($subValue as $subSubKey => $subSubValue)
                                                                                <div class="ms-3">
                                                                                    <strong>{{ ucwords(str_replace('_', ' ', $subSubKey)) }}:</strong> 
                                                                                    @if($subSubValue === null)
                                                                                        <span class="text-muted">N/A</span>
                                                                                    @else
                                                                                        {{ $subSubValue }}
                                                                                    @endif
                                                                                </div>
                                                                            @endforeach
                                                                        @endif
                                                                    @else
                                                                        @if($subValue === null)
                                                                            <span class="text-muted">N/A</span>
                                                                        @else
                                                                            {{ $subValue }}
                                                                        @endif
                                                                    @endif
                                                                </p>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif(is_array($value) && empty($value))
                                            <!-- Empty array -->
                                            <div class="col-md-6">
                                                <strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong>
                                                <p class="mb-0 text-muted">N/A</p>
                                            </div>
                                        @elseif(is_array($value))
                                            <!-- Indexed array -->
                                            <div class="col-md-6">
                                                <strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong>
                                                <ul class="mb-0 ps-3">
                                                    @foreach($value as $item)
                                                        <li>{{ is_array($item) ? json_encode($item) : $item }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @else
                                            <!-- Simple value -->
                                            <div class="col-md-6">
                                                <strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong>
                                                <p class="mb-0">
                                                    @if($value === null)
                                                        <span class="text-muted">N/A</span>
                                                    @else
                                                        {{ $value }}
                                                    @endif
                                                </p>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-md-5 col-lg-4">
            <!-- Payment Verification Logs -->
            @if($application->paymentVerificationLogs->count() > 0)
            <div class="card border-info shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Payment Verifications</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($application->paymentVerificationLogs->take(5) as $log)
                        <div class="list-group-item px-0 py-2">
                            <div class="d-flex justify-content-between">
                                <span>
                                    <strong>{{ $log->verification_type === 'initial' ? 'Initial' : 'Recurring' }}</strong>
                                    @if($log->billing_period)
                                        - {{ $log->billing_period }}
                                    @endif
                                </span>
                                <span class="text-black fs-6 fw-medium">{{ $log->verified_at->format('d M Y') }}</span>
                            </div>
                            <div class="text-black fs-6 fw-medium">
                                ₹{{ number_format($log->amount_captured ?? $log->amount, 2) }}
                                @if($log->verifiedBy)
                                    <br>by {{ $log->verifiedBy->name }}
                                @else
                                    <br>(Auto-verified)
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Invoices -->
            @if($application->invoices->count() > 0)
            <div class="card border-success shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-header theme-bg-green text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Recent Invoices</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($application->invoices->take(5) as $invoice)
                        <div class="list-group-item px-0 py-2 flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <strong>{{ $invoice->invoice_number }}</strong>
                                    <a href="{{ route('superadmin.invoices.download', $invoice->id) }}" 
                                       class="btn btn-sm btn-success mb-2" 
                                       target="_blank"
                                       title="Download Invoice PDF">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                            <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                                        </svg>
                                        Download
                                    </a>
                                </div>
                                <span class="badge text-capitalize bg-{{ $invoice->payment_status === 'paid' ? 'success' : ($invoice->payment_status === 'partial' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($invoice->payment_status) }}
                                </span>
                            </div>
                            <div class="text-muted small">
                                <span class="text-green fs-5 fw-semibold"> ₹{{ number_format($invoice->total_amount, 2) }}</span>
                                <br><span class="text-black fs-6 fw-medium">{{ $invoice->invoice_date->format('d M Y') }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Status History -->
            @if($application->statusHistory->count() > 0)
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Status History</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($application->statusHistory->take(10) as $history)
                        <div class="list-group-item px-0 py-2 small flex-wrap">
                            <div class="d-flex justify-content-between">
                                <span><strong>{{ $history->new_status_display ?? $history->new_status }}</strong></span>
                                <span class="text-blue fw-semibold ">{{ $history->created_at->format('d M Y') }}</span>
                            </div>
                            @if($history->notes)
                            <div class="text-muted mt-1">{{ $history->notes }}</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

