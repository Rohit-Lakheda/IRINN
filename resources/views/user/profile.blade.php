@extends('user.layout')

@section('title', 'My Profile')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4" style="color: #2c3e50; font-weight: 600;">My Profile</h2>
            
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue border-0" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Profile Details</h5>
                </div>
                <div class="card-body p-4">
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
                    <div class="row">
                        <div class="col-md-6">
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
                                    <th style="color: #2c3e50; font-weight: 600;">Email Address:</th>
                                    <td>
                                        {{ $user->email }}
                                        @if($user->email_verified)
                                            <span class="badge bg-success ms-2">Verified</span>
                                        @else
                                            <span class="badge bg-danger ms-2">Not Verified</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th style="color: #2c3e50; font-weight: 600;">Mobile Number:</th>
                                    <td>
                                        {{ $user->mobile }}
                                        @if($user->mobile_verified)
                                            <span class="badge bg-success ms-2">Verified</span>
                                        @else
                                            <span class="badge bg-danger ms-2">Not Verified</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%" style="color: #2c3e50; font-weight: 600;">PAN Card Number:</th>
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
                                    <th style="color: #2c3e50; font-weight: 600;">Account Status:</th>
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

                    @if(isset($irinnApplication) && $irinnApplication)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6 class="fw-semibold mb-3" style="color: #2c3e50;">GST &amp; billing (from IRINN application)</h6>
                                <p class="small text-muted mb-2">These details are stored on your application record and are used for invoicing and compliance.</p>
                                <table class="table table-bordered table-sm bg-white">
                                    <tbody>
                                    <tr>
                                        <th class="text-muted" style="width: 28%;">Application ID</th>
                                        <td><strong>{{ $irinnApplication->application_id }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">GST registered for billing</th>
                                        <td>{{ $irinnApplication->irinn_has_gst_number ? 'Yes' : 'No' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Billing GSTIN</th>
                                        <td>{{ $irinnApplication->irinn_billing_gstin ? strtoupper($irinnApplication->irinn_billing_gstin) : '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Billing legal name</th>
                                        <td>{{ $irinnApplication->irinn_billing_legal_name ?: '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Billing PAN</th>
                                        <td>{{ $irinnApplication->irinn_billing_pan ? strtoupper($irinnApplication->irinn_billing_pan) : '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Billing address</th>
                                        <td>{{ trim(implode(', ', array_filter([(string) ($irinnApplication->irinn_billing_address ?? ''), (string) ($irinnApplication->irinn_billing_postcode ?? '')]))) ?: '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Member representative</th>
                                        <td>
                                            {{ $irinnApplication->irinn_mr_name ?: '—' }}
                                            @if($irinnApplication->irinn_mr_email || $irinnApplication->irinn_mr_mobile)
                                                <br><span class="small text-muted">{{ $irinnApplication->irinn_mr_email }} @if($irinnApplication->irinn_mr_mobile) · {{ $irinnApplication->irinn_mr_mobile }} @endif</span>
                                            @endif
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="row mt-4">
                            <div class="col-12">
                                <p class="text-muted small mb-0">No IRINN application found on your account yet. GST and billing details will appear here after you submit an application.</p>
                            </div>
                        </div>
                    @endif

                    <div class="mt-4 pt-3 border-top border-c-yellow">
                        <p class="text-muted small mb-3">
                            <strong>Contact updates:</strong> To change your <strong>registered email</strong> or <strong>registered mobile number</strong>, you must first submit a <strong>profile update request</strong>.
                            Once admin approves it, you can update your details and re-verify them with OTP (same as registration).
                        </p>
                        @if($pendingRequest)
                            <div class="alert alert-info border-0 shadow-sm" style="border-radius: 12px;">
                                <div class="d-flex align-items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#0dcaf0" class="me-2 mt-1" viewBox="0 0 16 16">
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                                    </svg>
                                    <div class="flex-grow-1">
                                        <strong>Profile Update Request Pending</strong><br>
                                        Your request to update profile is pending admin approval. You'll be notified once it's reviewed.
                                        <br><small class="text-muted">Requested on: {{ $pendingRequest->created_at->format('d M Y, h:i A') }}</small>
                                    </div>
                                </div>
                            </div>
                        @elseif($submittedRequest)
                            <div class="alert alert-warning border-0 shadow-sm" style="border-radius: 12px; background-color: #fff9e6; border-left: 4px solid #f39c12;">
                                <div class="d-flex align-items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#f39c12" class="me-2 mt-1" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                        <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                                    </svg>
                                    <div class="flex-grow-1">
                                        <strong>Update Submitted - Waiting for Approval</strong><br>
                                        Your profile update has been submitted and is waiting for admin approval. You will see your changes once approved.
                                        <br><small class="text-muted">Submitted on: {{ $submittedRequest->submitted_at->format('d M Y, h:i A') }}</small>
                                    </div>
                                </div>
                            </div>
                        @elseif($approvedRequest)
                            <div class="alert alert-success border-0 shadow-sm" style="border-radius: 12px; background-color: #e8f8f0; border-left: 4px solid #27ae60;">
                                <div class="d-flex align-items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#27ae60" class="me-2 mt-1" viewBox="0 0 16 16">
                                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 2.384 5.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                                    </svg>
                                    <div class="flex-grow-1">
                                        <strong>Profile Update Request Approved!</strong><br>
                                        Your request has been approved. You can now update your profile once.
                                        <br>
                                        <a href="{{ route('user.profile-update.edit') }}" class="btn btn-success mt-2 px-4" style="border-radius: 8px; font-weight: 500;">Update Profile Now</a>
                                    </div>
                                </div>
                            </div>
                        @elseif($updateApprovedRequest && $updateApprovedRequest->submitted_data)
                            <div class="alert alert-success border-0 shadow-sm" style="border-radius: 12px; background-color: #e8f8f0; border-left: 4px solid #27ae60;">
                                <div class="d-flex align-items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#27ae60" class="me-2 mt-1" viewBox="0 0 16 16">
                                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 2.384 5.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                                    </svg>
                                    <div class="flex-grow-1">
                                        <strong>Profile Update Approved!</strong><br>
                                        Your profile update has been approved and applied. The updated information is now visible in your profile.
                                        <br><small class="text-muted">Approved on: {{ $updateApprovedRequest->update_approved_at->format('d M Y, h:i A') }}</small>
                                        <div class="mt-3">
                                            <a href="{{ route('user.profile-update.request') }}" class="btn btn-primary btn-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                    <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0Zm-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                                    <path d="M2 13c0 1 1 1 1 1h5.256A4.493 4.493 0 0 1 8 12.5a4.49 4.49 0 0 1 1.544-3.393C9.077 9.038 8.564 9 8 9c-5 0-6 3-6 4Z"/>
                                                </svg>
                                                Request Another Profile Update
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif($rejectedRequest)
                            <div class="alert alert-danger border-0 shadow-sm" style="border-radius: 12px; background-color: #ffe6e6; border-left: 4px solid #e74c3c;">
                                <div class="d-flex align-items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#e74c3c" class="me-2 mt-1" viewBox="0 0 16 16">
                                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
                                    </svg>
                                    <div class="flex-grow-1">
                                        <strong style="color: #e74c3c; font-size: 1.1rem;">Profile Update Request Rejected</strong>
                                        <div class="mt-3 mb-3 p-3 bg-white rounded" style="border: 1px solid #f5c6cb;">
                                            @if($rejectedRequest->admin_notes)
                                                <div class="mb-2">
                                                    <strong style="color: #721c24;">Rejection Reason:</strong>
                                                </div>
                                                <div style="color: #2c3e50; white-space: pre-wrap; line-height: 1.6;">{{ $rejectedRequest->admin_notes }}</div>
                                            @else
                                                <div style="color: #2c3e50;">No reason provided by admin.</div>
                                            @endif
                                        </div>
                                        <div class="mb-3">
                                            <small class="text-muted">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                    <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                                </svg>
                                                Rejected on: {{ $rejectedRequest->rejected_at->format('d M Y, h:i A') }}
                                            </small>
                                        </div>
                                        <div class="mt-3">
                                            <a href="{{ route('user.profile-update.request') }}" class="btn btn-primary btn-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                    <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0Zm-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                                    <path d="M2 13c0 1 1 1 1 1h5.256A4.493 4.493 0 0 1 8 12.5a4.49 4.49 0 0 1 1.544-3.393C9.077 9.038 8.564 9 8 9c-5 0-6 3-6 4Z"/>
                                                </svg>
                                                Submit New Update Request
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('user.profile-update.request') }}" class="btn btn-primary px-4 py-2" style="border-radius: 10px; font-weight: 600;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                    <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0Zm-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                    <path d="M2 13c0 1 1 1 1 1h5.256A4.493 4.493 0 0 1 8 12.5a4.49 4.49 0 0 1 1.544-3.393C9.077 9.038 8.564 9 8 9c-5 0-6 3-6 4Z"/>
                                </svg>
                                Request Profile Update
                            </a>
                        @endif
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
