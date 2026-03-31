@extends('superadmin.layout')

@section('title', 'User Details')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1" style="color: #2c3e50; font-weight: 600;">User Details</h2>
                <p class="text-muted mb-0">{{ $user->fullname }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('superadmin.users') }}" class="btn btn-outline-secondary px-4" style="border-radius: 10px; font-weight: 500;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                    </svg>
                    Back to Users
                </a>
                <button type="button" class="btn btn-danger px-4" style="border-radius: 10px; font-weight: 500;" data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                    Delete User
                </button>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Registration Information -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Registration Information</h5>
                </div>
                <div class="card-body p-4">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%" style="color: #2c3e50; font-weight: 600;">Registration ID:</th>
                            <td><strong style="color: #2c3e50;">{{ $user->registrationid }}</strong></td>
                        </tr>
                        <tr>
                            <th style="color: #2c3e50; font-weight: 600;">Full Name:</th>
                            <td>{{ $user->fullname }}</td>
                        </tr>
                        <tr>
                            <th style="color: #2c3e50; font-weight: 600;">Email:</th>
                            <td>
                                {{ $user->email }}
                                @if($user->email_verified)
                                    <span class="badge bg-success ms-2">Verified</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th style="color: #2c3e50; font-weight: 600;">Mobile:</th>
                            <td>
                                {{ $user->mobile }}
                                @if($user->mobile_verified)
                                    <span class="badge bg-success ms-2">Verified</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th style="color: #2c3e50; font-weight: 600;">PAN Card:</th>
                            <td>
                                {{ $user->pancardno }}
                                @if($user->pan_verified)
                                    <span class="badge bg-success ms-2">Verified</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th style="color: #2c3e50; font-weight: 600;">Date of Birth:</th>
                            <td>{{ $user->dateofbirth->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <th style="color: #2c3e50; font-weight: 600;">Registration Date:</th>
                            <td>{{ $user->registrationdate->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <th style="color: #2c3e50; font-weight: 600;">Registration Time:</th>
                            <td>{{ $user->registrationtime }}</td>
                        </tr>
                        <tr>
                            <th style="color: #2c3e50; font-weight: 600;">Status:</th>
                            <td>
                                <span class="badge rounded-pill px-3 py-1 
                                    @if($user->status === 'approved' || $user->status === 'active') bg-success
                                    @elseif($user->status === 'pending') bg-warning text-dark
                                    @else bg-secondary @endif">
                                    {{ ucfirst($user->status) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Messages & Profile Update Requests -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Messages ({{ $user->messages->count() }})</h5>
                </div>
                <div class="card-body p-4">
                    @if($user->messages->count() > 0)
                        <div class="list-group">
                            @foreach($user->messages->take(5) as $message)
                                <div class="list-group-item border-0 mb-2" style="border-radius: 10px; background-color: #f8f9fa;">
                                    <h6 style="color: #2c3e50; font-weight: 600; margin-bottom: 0.5rem;">{{ $message->subject }}</h6>
                                    <small class="text-muted d-block mb-2">{{ $message->created_at->format('M d, Y h:i A') }}</small>
                                    <p class="mb-0" style="color: #555;">{{ Str::limit($message->message, 50) }}</p>
                                    @if($message->user_reply)
                                        <div class="mt-2 p-2 bg-light rounded">
                                            <small><strong>User Reply:</strong> {{ Str::limit($message->user_reply, 50) }}</small>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-3">
                            <p class="text-muted mb-0">No messages sent.</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-warning text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Profile Update Requests ({{ $user->profileUpdateRequests->count() }})</h5>
                </div>
                <div class="card-body p-4">
                    @if($user->profileUpdateRequests->count() > 0)
                        <div class="list-group">
                            @foreach($user->profileUpdateRequests as $request)
                                <div class="list-group-item border-0 mb-2" style="border-radius: 10px; background-color: #fff9e6;">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge rounded-pill px-3 py-1 
                                            @if($request->status === 'pending') bg-warning text-dark
                                            @elseif($request->status === 'approved') bg-success
                                            @else bg-danger @endif">
                                            {{ ucfirst($request->status) }}
                                        </span>
                                        @if($request->submitted_data && !$request->update_approved)
                                            <span class="badge bg-info">Update Submitted</span>
                                        @elseif($request->update_approved)
                                            <span class="badge bg-success">Update Applied</span>
                                        @endif
                                    </div>
                                    @if($request->requested_changes)
                                        <p class="mb-2 small" style="color: #555;">
                                            <strong>Request:</strong> {{ Str::limit(is_array($request->requested_changes) ? json_encode($request->requested_changes) : $request->requested_changes, 100) }}
                                        </p>
                                    @endif
                                    @if($request->submitted_data)
                                        <div class="mb-2 p-2 bg-light rounded small">
                                            <strong>Submitted:</strong>
                                            @if(isset($request->submitted_data['email']))
                                                <div>Email: {{ $request->submitted_data['email'] }}</div>
                                            @endif
                                            @if(isset($request->submitted_data['mobile']))
                                                <div>Mobile: {{ $request->submitted_data['mobile'] }}</div>
                                            @endif
                                        </div>
                                    @endif
                                    <small class="text-muted d-block">{{ $request->created_at->format('M d, Y h:i A') }}</small>
                                    @if($request->approver)
                                        <small class="text-muted d-block mt-1">
                                            By: 
                                            @if($request->approver instanceof \App\Models\Admin)
                                                {{ $request->approver->name }}
                                            @else
                                                {{ $request->approver->fullname ?? 'Unknown' }}
                                            @endif
                                        </small>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-3">
                            <p class="text-muted mb-0">No profile update requests.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- IRINN Applications & Payment Status -->
    @if(isset($ixApplications) && $ixApplications->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-success text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">IRINN Applications ({{ $ixApplications->count() }})</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="color: #2c3e50; font-weight: 600;">Application ID</th>
                                    <th style="color: #2c3e50; font-weight: 600;">Membership ID</th>
                                    <th style="color: #2c3e50; font-weight: 600;">Status</th>
                                    <th style="color: #2c3e50; font-weight: 600;">Member Status</th>
                                    <th style="color: #2c3e50; font-weight: 600;">Payment Status</th>
                                    <th style="color: #2c3e50; font-weight: 600;">Amount</th>
                                    <th style="color: #2c3e50; font-weight: 600;">Submitted At</th>
                                    <th style="color: #2c3e50; font-weight: 600;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ixApplications as $application)
                                @php
                                    $payment = $paymentTransactions[$application->id] ?? null;
                                    $paymentStatus = $payment ? $payment->payment_status : 'pending';
                                    $isPaymentAccepted = $paymentStatus === 'success';
                                    $isMember = !is_null($application->membership_id);
                                @endphp
                                <tr>
                                    <td><strong>{{ $application->application_id }}</strong></td>
                                    <td>
                                        @if($isMember)
                                            <strong>{{ $application->membership_id }}</strong>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill px-3 py-1
                                            @if($application->status === 'approved' || $application->status === 'payment_verified') bg-success
                                            @elseif($application->status === 'rejected' || $application->status === 'ceo_rejected') bg-danger
                                            @elseif(in_array($application->status, ['submitted', 'resubmitted', 'processor_resubmission'])) bg-warning text-dark
                                            @else bg-secondary @endif">
                                            {{ $application->status_display }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($isMember)
                                            @if($application->is_active)
                                                <span class="badge bg-success">LIVE</span>
                                            @else
                                                <span class="badge bg-danger">NOT LIVE</span>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($isPaymentAccepted)
                                            <span class="badge bg-success">Accepted</span>
                                            @if($payment)
                                                <br><small class="text-muted">ID: {{ $payment->payment_id }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment)
                                            ₹{{ number_format($payment->amount, 2) }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $application->submitted_at ? $application->submitted_at->format('d M Y, h:i A') : 'Not submitted' }}
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-2">
                                            <span class="badge bg-secondary">View-only</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Admin Activity Log -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-dark text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Admin Activity Log</h5>
                </div>
                <div class="card-body p-4">
                    @if($adminActions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="color: #2c3e50; font-weight: 600;">Date/Time</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Admin</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Action</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($adminActions as $action)
                                    <tr>
                                        <td>{{ $action->created_at->format('M d, Y h:i A') }}</td>
                                        <td>
                                            @if($action->superAdmin)
                                                <span class="badge bg-danger me-2">SuperAdmin</span> {{ $action->superAdmin->name }}
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
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">No admin actions recorded.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4">
    <strong>View-only:</strong> Super Admin can view all registration/application details here, but delete/update actions are disabled. Please use the Admin panel for any changes.
</div>
@endsection
