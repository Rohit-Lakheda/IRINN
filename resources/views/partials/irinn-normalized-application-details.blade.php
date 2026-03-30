{{--
    Normalized IRINN create-new flow fields (applications.irinn_* columns).

    @var \App\Models\Application $application
    @var string $documentRouteName route name for secure download, e.g. user.applications.document or admin.applications.document
--}}
@php
    $doc = function (?string $pathColumn) use ($application, $documentRouteName) {
        if (! $pathColumn || ! $application->{$pathColumn}) {
            return null;
        }

        return route($documentRouteName, ['id' => $application->id, 'doc' => $pathColumn]);
    };
    $yn = function (?bool $v) {
        if ($v === null) {
            return '—';
        }

        return $v ? 'Yes' : 'No';
    };
    $irinnMainFiles = [
        'Registration / incorporation' => 'irinn_registration_document_path',
        'CA declaration' => 'irinn_ca_declaration_path',
        'Signature proof' => 'irinn_signature_proof_path',
        'Board resolution' => 'irinn_board_resolution_path',
        'Network diagram' => 'irinn_kyc_network_diagram_path',
        'Equipment invoice' => 'irinn_kyc_equipment_invoice_path',
        'Bandwidth proof' => 'irinn_kyc_bandwidth_proof_path',
        'IRINN agreement (KYC)' => 'irinn_kyc_irinn_agreement_path',
    ];
@endphp

<div class="irinn-normalized-details">
    <div id="irinn-detail-step-1" class="irinn-step-panel d-none mb-4" data-irinn-step="1">
    <h6 class="text-primary border-bottom pb-2 mb-3">Step 1 — Organisation details &amp; billing</h6>
    <h6 class="text-secondary small text-uppercase fw-semibold mb-2">Organisation &amp; verification</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-4"><small class="text-muted d-block">Company type</small><strong>{{ $application->irinn_company_type ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">CIN</small><strong>{{ $application->irinn_cin_number ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Udyam</small><strong>{{ $application->irinn_udyam_number ?? '—' }}</strong></div>
        <div class="col-md-6"><small class="text-muted d-block">Organisation name</small><strong>{{ $application->irinn_organisation_name ?? '—' }}</strong></div>
        <div class="col-md-6"><small class="text-muted d-block">Industry type</small><strong>{{ $application->irinn_industry_type ?? '—' }}</strong></div>
        <div class="col-12"><small class="text-muted d-block">Registered address</small><strong>{{ $application->irinn_organisation_address ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Postcode</small><strong>{{ $application->irinn_organisation_postcode ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Account name (IRINN)</small><strong>{{ $application->irinn_account_name ?? '—' }}</strong></div>
    </div>

    <h6 class="text-secondary small text-uppercase fw-semibold mb-2">Billing details</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-4"><small class="text-muted d-block">Has GSTIN</small><strong>{{ $yn($application->irinn_has_gst_number) }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Billing GSTIN</small><strong>{{ $application->irinn_billing_gstin ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Billing PAN</small><strong>{{ $application->irinn_billing_pan ?? '—' }}</strong></div>
        <div class="col-md-6"><small class="text-muted d-block">Legal name</small><strong>{{ $application->irinn_billing_legal_name ?? '—' }}</strong></div>
        <div class="col-12"><small class="text-muted d-block">Billing address</small><strong>{{ $application->irinn_billing_address ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Billing postcode</small><strong>{{ $application->irinn_billing_postcode ?? '—' }}</strong></div>
    </div>
    </div>

    <div id="irinn-detail-step-2" class="irinn-step-panel d-none mb-4" data-irinn-step="2">
    <h6 class="text-primary border-bottom pb-2 mb-3">Step 2 — Management representative</h6>
    <h6 class="text-secondary small text-uppercase fw-semibold mb-2">Contacts</h6>
    <div class="row g-3 mb-2">
        <div class="col-12"><span class="fw-semibold">Management representative</span></div>
        <div class="col-md-4"><small class="text-muted d-block">Name</small><strong>{{ $application->irinn_mr_name ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Designation</small><strong>{{ $application->irinn_mr_designation ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">DIN</small><strong>{{ $application->irinn_mr_din ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Email</small><strong>{{ $application->irinn_mr_email ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Mobile</small><strong>{{ $application->irinn_mr_mobile ?? '—' }}</strong></div>
    </div>
    </div>

    <div id="irinn-detail-step-3" class="irinn-step-panel d-none mb-4" data-irinn-step="3">
    <h6 class="text-primary border-bottom pb-2 mb-3">Step 3 — Technical person &amp; abuse contact</h6>
    <div class="row g-3 mb-2">
        <div class="col-12"><span class="fw-semibold">Technical person</span></div>
        <div class="col-md-4"><small class="text-muted d-block">Name</small><strong>{{ $application->irinn_tp_name ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Designation</small><strong>{{ $application->irinn_tp_designation ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Email</small><strong>{{ $application->irinn_tp_email ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Mobile</small><strong>{{ $application->irinn_tp_mobile ?? '—' }}</strong></div>
    </div>
    <div class="row g-3 mb-2">
        <div class="col-12"><span class="fw-semibold">Abuse contact</span></div>
        <div class="col-md-4"><small class="text-muted d-block">Name</small><strong>{{ $application->irinn_abuse_name ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Designation</small><strong>{{ $application->irinn_abuse_designation ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Email</small><strong>{{ $application->irinn_abuse_email ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Mobile</small><strong>{{ $application->irinn_abuse_mobile ?? '—' }}</strong></div>
    </div>
    </div>

    <div id="irinn-detail-step-4" class="irinn-step-panel d-none mb-4" data-irinn-step="4">
    <h6 class="text-primary border-bottom pb-2 mb-3">Step 4 — Billing representative</h6>
    <div class="row g-3 mb-4">
        <div class="col-12"><span class="fw-semibold">Billing representative</span></div>
        <div class="col-md-4"><small class="text-muted d-block">Name</small><strong>{{ $application->irinn_br_name ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Designation</small><strong>{{ $application->irinn_br_designation ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Email</small><strong>{{ $application->irinn_br_email ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Mobile</small><strong>{{ $application->irinn_br_mobile ?? '—' }}</strong></div>
    </div>
    </div>

    <div id="irinn-detail-step-5" class="irinn-step-panel d-none mb-4" data-irinn-step="5">
    <h6 class="text-primary border-bottom pb-2 mb-3">Step 5 — Network resources</h6>
    <h6 class="text-secondary small text-uppercase fw-semibold mb-2">IP resources &amp; fee</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-4"><small class="text-muted d-block">ASN required</small><strong>{{ $yn($application->irinn_asn_required) }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">IPv4 size</small><strong>{{ $application->irinn_ipv4_resource_size ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">IPv4 addresses</small><strong>{{ $application->irinn_ipv4_resource_addresses ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">IPv6 size</small><strong>{{ $application->irinn_ipv6_resource_size ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">IPv6 addresses</small><strong>{{ $application->irinn_ipv6_resource_addresses ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Resource fee (₹)</small><strong>{{ $application->irinn_resource_fee_amount !== null ? number_format((float) $application->irinn_resource_fee_amount, 2) : '—' }}</strong></div>
    </div>
    </div>

    <div id="irinn-detail-step-6" class="irinn-step-panel d-none mb-4" data-irinn-step="6">
    <h6 class="text-primary border-bottom pb-2 mb-3">Step 6 — Upstream provider &amp; authorised signatory</h6>
    <h6 class="text-secondary small text-uppercase fw-semibold mb-2">Upstream provider</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-6"><small class="text-muted d-block">Provider name</small><strong>{{ $application->irinn_upstream_provider_name ?? '—' }}</strong></div>
        <div class="col-md-6"><small class="text-muted d-block">AS number</small><strong>{{ $application->irinn_upstream_as_number ?? '—' }}</strong></div>
        <div class="col-md-6"><small class="text-muted d-block">Email</small><strong>{{ $application->irinn_upstream_email ?? '—' }}</strong></div>
        <div class="col-md-6"><small class="text-muted d-block">Mobile</small><strong>{{ $application->irinn_upstream_mobile ?? '—' }}</strong></div>
    </div>

    <h6 class="text-secondary small text-uppercase fw-semibold mb-2 mt-3">Authorised signatory</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-4"><small class="text-muted d-block">Name</small><strong>{{ $application->irinn_sign_name ?? '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">Date of birth</small><strong>{{ $application->irinn_sign_dob ? $application->irinn_sign_dob->format('d M Y') : '—' }}</strong></div>
        <div class="col-md-4"><small class="text-muted d-block">PAN</small><strong>{{ $application->irinn_sign_pan ?? '—' }}</strong></div>
        <div class="col-md-6"><small class="text-muted d-block">Email</small><strong>{{ $application->irinn_sign_email ?? '—' }}</strong></div>
        <div class="col-md-6"><small class="text-muted d-block">Mobile</small><strong>{{ $application->irinn_sign_mobile ?? '—' }}</strong></div>
    </div>
    </div>

    <div id="irinn-detail-step-7" class="irinn-step-panel d-none mb-4" data-irinn-step="7">
    <h6 class="text-primary border-bottom pb-2 mb-3">Step 7 — KYC documents</h6>
    <h6 class="text-secondary small text-uppercase fw-semibold mb-2">Uploaded files</h6>
    <div class="row g-2 mb-3">
        @foreach($irinnMainFiles as $label => $col)
            <div class="col-md-6 d-flex justify-content-between align-items-center border rounded px-3 py-2">
                <span class="small">{{ $label }}</span>
                @if($url = $doc($col))
                    <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">View</a>
                @else
                    <span class="text-muted small">—</span>
                @endif
            </div>
        @endforeach
    </div>
    @for($i = 1; $i <= 5; $i++)
        @php
            $labelCol = "irinn_other_doc_{$i}_label";
            $pathCol = "irinn_other_doc_{$i}_path";
            $lbl = $application->{$labelCol};
            $pth = $application->{$pathCol};
        @endphp
        @if($lbl || $pth)
            <div class="d-flex justify-content-between align-items-center border rounded px-3 py-2 mb-2">
                <span class="small"><strong>Other {{ $i }}:</strong> {{ $lbl ?: 'Document' }}</span>
                @if($pth && ($url = $doc($pathCol)))
                    <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">View</a>
                @else
                    <span class="text-muted small">—</span>
                @endif
            </div>
        @endif
    @endfor
    </div>
</div>
