@extends('admin.layout')

@section('content')
    <div class="container-fluid px-0 px-md-3 irin-admin-app-show">
        <div class="row align-items-center mb-3 mb-md-4">
            <div class="col-md-7">
                <h1 class="page-title mb-1">Application Details</h1>
                <p class="page-subtitle mb-0">
                    Application ID:
                    <span class="fw-semibold text-theme-primary">{{ $application->application_id }}</span>
                </p>
            </div>
            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                <a href="{{ route('admin.applications.show-comprehensive', $application->id) }}"
                   class="btn btn-outline-primary irin-btn-rounded">
                    View Comprehensive Details
                </a>
            </div>
        </div>

        {{-- Top row: Application Information + Workflow Actions --}}
        <div class="row g-4">
            @php
                $registration = $application->user;
                $regDetails = $application->registration_details ?? [];
            @endphp

            <div class="col-md-6">
                <div class="card irinn-app-card h-100">
                    <div class="card-header irinn-card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Application Information</h5>
                        <!-- <span class="badge bg-light text-theme-blue text-uppercase small">
                            {{ $application->application_type ?? 'IRINN' }}
                        </span> -->
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="label">Application ID</div>
                                <div class="value">{{ $application->application_id }}</div>
                            </div>
                            <div class="col-6">
                                <div class="label">Current Stage</div>
                                <div class="value text-uppercase">
                                    {{ $application->current_stage ?? $application->status_display }}
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            @php
                                $isLive = $application->status === 'billing';
                            @endphp
                            <div class="col-6">
                                <div class="label">Submitted At</div>
                                <div class="value">
                                    {{ optional($application->created_at)->format('d M Y, h:i A') ?? '—' }}
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="label">Live Status</div>
                                <div class="value">
                                    @if($isLive)
                                        <span class="badge bg-success-subtle text-success fw-semibold px-3 py-2 rounded-pill">
                                            LIVE
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary fw-semibold px-3 py-2 rounded-pill">
                                            NOT LIVE
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @php
                            $data = $application->application_data ?? [];
                            $part2 = $data['part2'] ?? [];
                            $part3 = $data['part3'] ?? [];
                        @endphp

                        @php
                            $appUser = $application->user;
                        @endphp
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="label">Registered User</div>
                                <div class="value">
                                    @if($appUser)
                                        <a href="{{ route('admin.users.show', $appUser->id) }}" class="text-decoration-none text-theme-blue fw-semibold">
                                            {{ $appUser->fullname ?? $appUser->registrationid ?? 'View Registered User' }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="irinn-divider my-3"></div>

                        <div class="row mb-3">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="label">IPv4 Prefix</div>
                                <div class="value">
                                    {{ $part2['ipv4_prefix'] ?? $data['ipv4_prefix'] ?? '—' }}
                                </div>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="label">IPv6 Prefix</div>
                                <div class="value">
                                    {{ $part2['ipv6_prefix'] ?? $data['ipv6_prefix'] ?? '—' }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="label">ASN Required</div>
                                <div class="value">
                                    @php
                                        $asnRequired = $part2['asn_required'] ?? $data['asn_required'] ?? null;
                                    @endphp
                                    {{ $asnRequired === 'yes' || $asnRequired === '1' ? 'Yes' : ($asnRequired === 'no' || $asnRequired === '0' ? 'No' : '—') }}
                                </div>
                            </div>
                        </div>

                        {{-- (Fee and PAN moved to detailed sections; keep top card focused on stage and IP info) --}}
                    </div>
                </div>
            </div>

            {{-- Workflow actions --}}
            <div class="col-md-6">
                @php
                    $data = $application->application_data ?? [];
                    $irinnStatus = $application->status ?? 'helpdesk';
                    $irinnPreviousStage = $data['irinn_previous_stage'] ?? null;
                    $currentStageLabel = $application->current_stage ?? $application->status_display;
                    $selectedRoleLabel = ucfirst($selectedRole ?? (session('admin_selected_role') ?? 'Helpdesk'));
                @endphp
                <div class="card irinn-app-card h-100">
                    <div class="card-header irinn-card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Workflow Actions</h5>
                            <small class="d-block mt-1 text-white fw-semibold" style="font-size: 0.8rem;">
                                Viewing as: <span class="fw-bold">{{ $selectedRoleLabel }}</span>
                            </small>
                        </div>
                        <span class="badge bg-white text-theme-blue text-uppercase small fw-semibold px-3 py-1" style="color: green !important;">
                            Stage: {{ strtoupper($currentStageLabel) }}
                        </span>
                    </div>
                    <div class="card-body">
                        @if(session('admin_selected_role') === 'helpdesk' && $irinnStatus === 'helpdesk')
                            <div class="mb-3">
                                <form method="POST" action="{{ route('admin.applications.irinn.change-stage', $application->id) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="target_stage" value="hostmaster">
                                    <button type="submit" class="btn btn-sm btn-success irin-btn-rounded"
                                            onclick="return confirm('Move application to Hostmaster stage?');">
                                        Move to Hostmaster
                                    </button>
                                </form>
                            </div>
                        @elseif(session('admin_selected_role') === 'hostmaster' && $irinnStatus === 'hostmaster')
                            <div class="mb-3">
                                <form method="POST" action="{{ route('admin.applications.irinn.change-stage', $application->id) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="target_stage" value="billing">
                                    <button type="submit" class="btn btn-sm btn-success irin-btn-rounded"
                                            onclick="return confirm('Move application to Billing stage?');">
                                        Move to Billing
                                    </button>
                                </form>
                            </div>
                        @endif

                        @if(in_array(session('admin_selected_role'), ['helpdesk', 'hostmaster'], true) && $irinnStatus !== 'billing')
                            <div class="irinn-divider my-3"></div>
                            <form method="POST" action="{{ route('admin.applications.irinn.request-resubmission', $application->id) }}">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label fw-semibold">
                                        Request Resubmission <span class="text-muted">(message to user)</span>
                                    </label>
                                    <textarea name="resubmission_reason"
                                              rows="3"
                                              class="form-control"
                                              placeholder="Explain what needs to be corrected or updated">{{ old('resubmission_reason') }}</textarea>
                                    @error('resubmission_reason')
                                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                                    @enderror
                                </div>
                                <button type="submit"
                                        class="btn btn-sm btn-outline-danger irin-btn-rounded"
                                        onclick="return confirm('Ask user to resubmit this IRINN application?');">
                                    Request Resubmission
                                </button>
                                @if($irinnPreviousStage)
                                    <small class="text-muted ms-2">
                                        Last stage before resubmission: {{ ucfirst($irinnPreviousStage) }}
                                    </small>
                                @endif
                            </form>
                        @else
                            <p class="text-muted mb-0" style="font-size: 0.85rem;">
                                No workflow actions available in the current stage/view.
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Second row: Registration & KYC --}}
        <div class="row g-4 mt-1 mt-md-4">
            <div class="col-md-6">
                <div class="card irinn-app-card h-100">
                    <div class="card-header irinn-card-header">
                        <h5 class="mb-0">Registration Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="label">Registered Name</div>
                                <div class="value">
                                    {{ $regDetails['fullname'] ?? $registration->fullname ?? '—' }}
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="label">PAN</div>
                                <div class="value">
                                    {{ $regDetails['pancardno'] ?? $registration->pancardno ?? '—' }}
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="label">Email</div>
                                <div class="value">{{ $regDetails['email'] ?? $registration->email ?? '—' }}</div>
                            </div>
                            <div class="col-6">
                                <div class="label">Mobile</div>
                                <div class="value">{{ $regDetails['mobile'] ?? $registration->mobile ?? '—' }}</div>
                            </div>
                        </div>
                        {{-- Registered address omitted here to keep card concise; full address available in KYC/Billing. --}}
                    </div>
                </div>
            </div>

            @php
                $kycDetails = $application->kyc_details ?? [];
            @endphp

            <div class="col-md-6">
                <div class="card irinn-app-card h-100">
                    <div class="card-header irinn-card-header">
                        <h5 class="mb-0">KYC Details</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $gst = $application->gstVerification ?? null;
                            $part1 = $data['part1'] ?? [];
                            $billingAddress = $kycDetails['billing_address'] ?? null;
                            if (is_string($billingAddress)) {
                                $decoded = json_decode($billingAddress, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                    $billingAddress = $decoded;
                                }
                            }
                        @endphp
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="label">GSTIN</div>
                                <div class="value">
                                    {{ $gst->gstin ?? ($kycDetails['gstin'] ?? '—') }}
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="label">Affiliate Type</div>
                                <div class="value">
                                    {{ $kycDetails['affiliate_type'] ?? $part1['affiliate_type'] ?? '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="label">Legal / Trade Name</div>
                                <div class="value">
                                    {{ $gst->legal_name ?? $kycDetails['legal_name'] ?? $kycDetails['trade_name'] ?? '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="label">Billing Address</div>
                                <div class="value">
                                    @if(is_array($billingAddress))
                                        @php
                                            $parts = [];
                                            foreach (['address','street','city','state','pincode','country'] as $field) {
                                                if (!empty($billingAddress[$field])) {
                                                    $parts[] = $billingAddress[$field];
                                                }
                                            }
                                        @endphp
                                        {{ !empty($parts) ? implode(', ', $parts) : '—' }}
                                    @elseif(!empty($billingAddress))
                                        {{ $billingAddress }}
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- (Standard view keeps KYC summary brief; full KYC breakdown is available in comprehensive view) --}}
                    </div>
                </div>
            </div>
        </div>

        {{-- Third row: Status history --}}
        <div class="row mt-1 mt-md-4">
            <div class="col-12">
                <div class="card irinn-app-card">
                    <div class="card-header irinn-card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Status History</h5>
                    </div>
                    <div class="card-body">
                        @if($application->statusHistory && $application->statusHistory->count() > 0)
                            <div class="timeline">
                                @foreach($application->statusHistory->sortBy('created_at') as $history)
                                    <div class="mb-3 pb-3 border-bottom">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong>{{ $history->status_display }}</strong>
                                            </div>
                                            <small class="text-muted">
                                                {{ $history->created_at->format('d M Y, h:i A') }}
                                            </small>
                                        </div>
                                        @if($history->notes)
                                            <p class="mb-0 mt-2">
                                                <small>{{ $history->notes }}</small>
                                            </p>
                                        @endif
                                        @php
                                            $changedBy = $history->changedBy();
                                        @endphp
                                        @if($changedBy)
                                            <p class="mb-0 mt-1">
                                                <small class="text-muted">
                                                    Changed by:
                                                    @if($history->changed_by_type === 'admin')
                                                        {{ $changedBy->name ?? 'Admin' }}
                                                    @elseif($history->changed_by_type === 'superadmin')
                                                        {{ $changedBy->name ?? 'SuperAdmin' }}
                                                    @elseif($history->changed_by_type === 'user')
                                                        User
                                                    @endif
                                                </small>
                                            </p>
                                        @elseif($history->changed_by_type === 'user')
                                            <p class="mb-0 mt-1">
                                                <small class="text-muted">Changed by: User</small>
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted mb-0">No status history available.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        :root {
            --irinn-card-radius: 18px;
        }

        .irin-admin-app-show .page-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #0b1324;
        }

        .irin-admin-app-show .page-subtitle {
            font-size: 0.95rem;
            color: #4b4f5c;
        }

        .irin-admin-app-show .irin-btn-rounded {
            border-radius: 999px;
            padding-inline: 1.4rem;
            padding-block: 0.45rem;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .irinn-app-card {
            border-radius: var(--irinn-card-radius);
            border: none;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            overflow: hidden;
            background: #ffffff;
        }

        .irinn-card-header {
            background: linear-gradient(90deg, var(--theme-blue, #2B2F6C), #4b4fd1);
            color: #ffffff;
            padding: 0.9rem 1.25rem;
        }

        .irinn-card-header h5 {
            font-size: 1rem;
            font-weight: 600;
        }

        .irinn-app-card .card-body {
            padding: 1.1rem 1.25rem 1.25rem;
        }

        .irinn-app-card .label {
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 0.15rem;
        }

        .irinn-app-card .value {
            font-size: 0.95rem;
            color: #0b1324;
        }

        .irinn-divider {
            border-bottom: 1px dashed rgba(148, 163, 184, 0.6);
        }

        .bg-success-subtle {
            background-color: rgba(16, 185, 129, 0.1) !important;
        }

        .bg-secondary-subtle {
            background-color: rgba(148, 163, 184, 0.18) !important;
        }

        .text-theme-blue {
            color: var(--theme-blue, #2B2F6C) !important;
        }

        @media (max-width: 767.98px) {
            .irin-admin-app-show .page-title {
                font-size: 1.3rem;
            }

            .irinn-app-card .card-body {
                padding-inline: 1rem;
            }
        }
    </style>
@endpush

