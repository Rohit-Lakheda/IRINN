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
                            <div class="col-6">
                                <div class="label">Submitted At</div>
                                <div class="value">
                                    {{ optional($application->submitted_at)->format('d M Y, h:i A') ?? '—' }}
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="label">Workflow status</div>
                                <div class="value">
                                    @if($application->status === 'billing_approved')
                                        <span class="badge bg-success-subtle text-success fw-semibold px-3 py-2 rounded-pill">
                                            BILLING APPROVED
                                        </span>
                                    @elseif($application->status === 'billing')
                                        <span class="badge bg-light text-primary border fw-semibold px-3 py-2 rounded-pill">
                                            WITH BILLING
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary fw-semibold px-3 py-2 rounded-pill">
                                            IN PROGRESS
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
                                <div class="label">IPv4 resource</div>
                                <div class="value">
                                    @if($application->hasIrinnNormalizedData())
                                        {{ $application->irinn_ipv4_resource_size ?? '—' }}
                                        @if($application->irinn_ipv4_resource_addresses)
                                            <span class="text-muted small">({{ $application->irinn_ipv4_resource_addresses }} addresses)</span>
                                        @endif
                                    @else
                                        {{ $part2['ipv4_prefix'] ?? $data['ipv4_prefix'] ?? '—' }}
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="label">IPv6 resource</div>
                                <div class="value">
                                    @if($application->hasIrinnNormalizedData())
                                        {{ $application->irinn_ipv6_resource_size ?? '—' }}
                                        @if($application->irinn_ipv6_resource_addresses)
                                            <span class="text-muted small">({{ $application->irinn_ipv6_resource_addresses }} addresses)</span>
                                        @endif
                                    @else
                                        {{ $part2['ipv6_prefix'] ?? $data['ipv6_prefix'] ?? '—' }}
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="label">ASN Required</div>
                                <div class="value">
                                    @if($application->hasIrinnNormalizedData())
                                        {{ $application->irinn_asn_required ? 'Yes' : 'No' }}
                                    @else
                                        @php
                                            $asnRequired = $part2['asn_required'] ?? $data['asn_required'] ?? null;
                                        @endphp
                                        {{ $asnRequired === 'yes' || $asnRequired === '1' ? 'Yes' : ($asnRequired === 'no' || $asnRequired === '0' ? 'No' : '—') }}
                                    @endif
                                </div>
                            </div>
                            @if($application->hasIrinnNormalizedData() && $application->irinn_resource_fee_amount !== null)
                            <div class="col-md-12 mt-2">
                                <div class="label">Calculated resource fee</div>
                                <div class="value">₹{{ number_format((float) $application->irinn_resource_fee_amount, 2) }}</div>
                            </div>
                            @endif
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
                    $atHelpdesk = in_array($irinnStatus, ['helpdesk', 'submitted'], true);
                    $irinnPreviousStage = $data['irinn_previous_stage'] ?? null;
                    $currentStageLabel = $application->current_stage ?? $application->status_display;
                    $selectedRoleLabel = ucfirst($selectedRole ?? (session('admin_selected_role') ?? 'Helpdesk'));
                    $wfRole = session('admin_selected_role');
                    $helpdeskResubmitStatuses = ['submitted', 'helpdesk', 'pending'];
                    $canHelpdeskResubmit = $wfRole === 'helpdesk'
                        && in_array($irinnStatus, $helpdeskResubmitStatuses, true);
                    $canHostmasterResubmit = $wfRole === 'hostmaster'
                        && $irinnStatus === 'hostmaster';
                    $canResubmit = $canHelpdeskResubmit || $canHostmasterResubmit;
                    $hasWorkflowContent =
                        ($wfRole === 'helpdesk' && $atHelpdesk)
                        || ($wfRole === 'hostmaster' && $irinnStatus === 'hostmaster')
                        || ($wfRole === 'billing' && in_array($irinnStatus, ['billing', 'billing_approved'], true))
                        || ($wfRole === 'hostmaster' && $irinnStatus === 'billing_approved')
                        || $canResubmit;
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
                        @if(session('admin_selected_role') === 'helpdesk' && $atHelpdesk)
                            <div class="mb-3">
                                <form method="POST" action="{{ route('admin.applications.irinn.change-stage', $application->id) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="target_stage" value="hostmaster">
                                    <button type="submit" class="btn btn-sm btn-success irin-btn-rounded"
                                            onclick="return confirm('Forward this application to Hostmaster?');">
                                        Approve — forward to Hostmaster
                                    </button>
                                </form>
                            </div>
                        @elseif(session('admin_selected_role') === 'hostmaster' && $irinnStatus === 'hostmaster')
                            <div class="mb-3">
                                <form method="POST" action="{{ route('admin.applications.irinn.change-stage', $application->id) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="target_stage" value="billing">
                                    <button type="submit" class="btn btn-sm btn-success irin-btn-rounded"
                                            onclick="return confirm('Forward this application to Billing?');">
                                        Approve — forward to Billing
                                    </button>
                                </form>
                            </div>
                        @elseif(session('admin_selected_role') === 'billing' && $irinnStatus === 'billing')
                            <div class="mb-3">
                                <form method="POST" action="{{ route('admin.applications.irinn.change-stage', $application->id) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="target_stage" value="billing_approved">
                                    <button type="submit" class="btn btn-sm btn-success irin-btn-rounded"
                                            onclick="return confirm('Mark this application as approved by Billing?');">
                                        Approve (Billing complete)
                                    </button>
                                </form>
                            </div>
                        @endif

                        @if($canResubmit)
                            <div class="irinn-divider my-3"></div>
                            <form method="POST" action="{{ route('admin.applications.irinn.request-resubmission', $application->id) }}">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label fw-semibold">
                                        Request resubmission <span class="text-muted">(message to user)</span>
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
                                        onclick="return confirm('Ask the user to resubmit this IRINN application?');">
                                    Request resubmission
                                </button>
                                @if($irinnPreviousStage)
                                    <small class="text-muted ms-2">
                                        Last stage before resubmission: {{ ucfirst($irinnPreviousStage) }}
                                    </small>
                                @endif
                            </form>
                        @endif

                        @if(session('admin_selected_role') === 'billing' && $irinnStatus === 'billing_approved')
                            <div class="alert alert-success small mb-3">
                                Billing approval is complete. Generate the annual resource invoice below; users pay after logging in to the portal.
                            </div>
                            <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-outline-primary irin-btn-rounded mb-2">Open all invoices</a>
                        @endif

                        @if(session('admin_selected_role') === 'billing' && in_array($irinnStatus, ['billing', 'billing_approved'], true))
                            <div class="irinn-divider my-3"></div>
                            <h6 class="fw-semibold mb-2">Annual IRINN billing</h6>
                            <p class="small text-muted mb-2">Discount % applies to <strong>every</strong> annual invoice for this application. Due date is one month after the invoice date. E-invoice (IRN/ACK) is requested only when the applicant provided a billing GSTIN.</p>

                            <form method="POST" action="{{ route('admin.applications.irinn.billing-discount', $application->id) }}" class="mb-3">
                                @csrf
                                <label class="form-label small fw-semibold">Billing discount (%)</label>
                                <div class="input-group input-group-sm" style="max-width: 220px;">
                                    <input type="number" step="0.01" min="0" max="100" name="irinn_billing_discount_percent" class="form-control"
                                           value="{{ old('irinn_billing_discount_percent', $application->irinn_billing_discount_percent ?? 0) }}" required>
                                    <button type="submit" class="btn btn-outline-secondary">Save</button>
                                </div>
                                @error('irinn_billing_discount_percent')<small class="text-danger">{{ $message }}</small>@enderror
                            </form>

                            <div class="mb-2 d-flex flex-wrap align-items-end gap-2">
                                <div>
                                    <label class="form-label small fw-semibold mb-0">Annual base amount (before discount, ₹)</label>
                                    <input type="number" form="irinn-generate-annual-form" step="0.01" min="0.01" name="annual_base_amount" id="irinn-annual-base-input" class="form-control form-control-sm" style="max-width: 200px;"
                                           value="{{ old('annual_base_amount', $application->irinn_resource_fee_amount) }}" required>
                                </div>
                                @if($irinnStatus === 'billing_approved')
                                    <a href="#" class="btn btn-sm btn-outline-secondary irin-btn-rounded" id="irinn-preview-annual-link">Preview invoice</a>
                                    <form id="irinn-generate-annual-form" method="POST" action="{{ route('admin.applications.irinn.generate-annual-invoice', $application->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary irin-btn-rounded" onclick="return confirm('Generate annual invoice for the current financial year? The user will be emailed when possible.');">Generate annual invoice</button>
                                    </form>
                                @else
                                    <p class="small text-muted mb-0">Generation unlocks after <strong>billing approval</strong>. You can still set the discount above.</p>
                                @endif
                            </div>
                            @error('annual_base_amount')<small class="text-danger d-block">{{ $message }}</small>@enderror

                            <div class="d-flex flex-wrap gap-2 mt-2 mb-2">
                                <a href="{{ route('admin.invoices.index', ['application_record_id' => $application->id]) }}" class="btn btn-sm btn-outline-primary irin-btn-rounded">View all invoices for this application</a>
                            </div>

                            @if(isset($irinnAnnualInvoices) && $irinnAnnualInvoices->isNotEmpty())
                                <div class="small mt-3">
                                    <span class="fw-semibold">Recent annual invoices</span>
                                    <div class="table-responsive mt-2">
                                        <table class="table table-sm table-bordered align-middle mb-0">
                                            <thead class="table-light">
                                            <tr>
                                                <th>Invoice</th>
                                                <th class="text-end">Total</th>
                                                <th>Payment</th>
                                                <th>Actions</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($irinnAnnualInvoices as $inv)
                                                <tr>
                                                    <td class="text-break">{{ $inv->invoice_number }}<br><span class="text-muted">FY {{ $inv->billing_period }}</span></td>
                                                    <td class="text-end">₹{{ number_format((float) $inv->total_amount, 2) }}</td>
                                                    <td><span class="badge bg-secondary text-uppercase">{{ $inv->payment_status }}</span></td>
                                                    <td>
                                                        @if($inv->pdf_path)
                                                            <a href="{{ route('admin.applications.invoice.download', $inv->id) }}" class="btn btn-link btn-sm p-0 me-2">PDF</a>
                                                        @endif
                                                        @if($inv->tds_certificate_path)
                                                            <a href="{{ route('admin.applications.invoice.tds-certificate', $inv->id) }}" class="btn btn-link btn-sm p-0 me-2">TDS cert.</a>
                                                        @endif
                                                        @if($inv->billing_payment_proof_path)
                                                            <a href="{{ route('admin.applications.invoice.billing-payment-proof', $inv->id) }}" class="btn btn-link btn-sm p-0 me-2">Payment proof</a>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @if($inv->payment_status !== 'paid' || (float)($inv->balance_amount ?? 0) > 0.009)
                                                    <tr class="border-top-0">
                                                        <td colspan="4" class="pt-0 pb-3">
                                                            <form method="POST" action="{{ route('admin.applications.irinn.invoice.mark-paid', [$application->id, $inv->id]) }}" enctype="multipart/form-data" class="row g-2 align-items-end small">
                                                                @csrf
                                                                <div class="col-md-3">
                                                                    <label class="form-label mb-0">Payment / txn reference</label>
                                                                    <input type="text" name="manual_payment_id" class="form-control form-control-sm" required value="{{ old('manual_payment_id') }}">
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label class="form-label mb-0">Comment</label>
                                                                    <input type="text" name="manual_payment_notes" class="form-control form-control-sm" value="{{ old('manual_payment_notes') }}">
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <label class="form-label mb-0">Payment document (optional)</label>
                                                                    <input type="file" name="billing_payment_proof" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <button type="submit" class="btn btn-sm btn-success w-100">Mark paid</button>
                                                                </div>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                            @push('scripts')
                                <script>
                                    (function () {
                                        var base = document.getElementById('irinn-annual-base-input');
                                        var link = document.getElementById('irinn-preview-annual-link');
                                        if (!base || !link) return;
                                        var previewUrl = @json(route('admin.applications.irinn.preview-annual-invoice', $application->id));
                                        link.addEventListener('click', function (e) {
                                            e.preventDefault();
                                            var v = base.value;
                                            if (!v || parseFloat(v) <= 0) {
                                                alert('Enter a valid annual base amount first.');
                                                return;
                                            }
                                            window.open(previewUrl + '?annual_base_amount=' + encodeURIComponent(v), '_blank', 'noopener');
                                        });
                                    })();
                                </script>
                            @endpush
                        @endif

                        @if(session('admin_selected_role') === 'hostmaster' && $irinnStatus === 'billing_approved')
                            <div class="alert alert-info small mb-0 mt-3">
                                Resource allocation for this application will be added in a follow-up step.
                            </div>
                        @endif

                        @if(! $hasWorkflowContent)
                            <p class="text-muted mb-0 small">
                                @if(! $wfRole)
                                    Choose Helpdesk, Hostmaster, or Billing in the role switcher to see actions for this application.
                                @else
                                    No workflow actions for your role at this stage.
                                @endif
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Registration summary (KYC summary card removed; full applicant view is on Comprehensive Details) --}}
        <div class="row g-4 mt-1 mt-md-4">
            <div class="col-12 col-lg-8">
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
                        {{-- Registered address omitted here to keep card concise; see View Comprehensive Details for the full applicant view. --}}
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

