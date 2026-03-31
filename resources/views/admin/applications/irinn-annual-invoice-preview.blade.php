@extends('admin.layout')

@section('content')
    <div class="container-fluid px-0 px-md-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="page-title mb-1">Annual invoice preview</h1>
                <p class="page-subtitle mb-0">
                    {{ $application->application_id }}
                    — FY {{ $preview['financial_year'] }}
                </p>
            </div>
            <a href="{{ route('admin.applications.show', $application->id) }}" class="btn btn-outline-secondary irin-btn-rounded">Back to application</a>
        </div>

        @if(!empty($preview['duplicate_invoice_exists_for_fy']))
            <div class="alert alert-warning">
                An annual invoice for this financial year already exists (or is not cancelled). Generation will be blocked until it is resolved.
            </div>
        @endif

        <div class="card irinn-app-card mb-3">
            <div class="card-header irinn-card-header">
                <h5 class="mb-0">Parties &amp; GST</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="fw-semibold text-muted small text-uppercase">Seller (NIXI)</h6>
                        <p class="mb-1">{{ $preview['seller_legal_name'] }}</p>
                        <p class="mb-1 small">{{ $preview['seller_address'] }}</p>
                        <p class="mb-0 small"><strong>GSTIN:</strong> {{ $preview['seller_gstin'] }} &nbsp;|&nbsp; <strong>PAN:</strong> {{ $preview['seller_pan'] }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-semibold text-muted small text-uppercase">Buyer (member)</h6>
                        <p class="mb-1">{{ $preview['buyer_legal_name'] }}</p>
                        <p class="mb-1 small">{{ $preview['buyer_address'] }}</p>
                        <p class="mb-0 small"><strong>GSTIN/UIN:</strong> {{ $preview['buyer_gstin'] }} &nbsp;|&nbsp; <strong>PAN:</strong> {{ $preview['buyer_pan'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card irinn-app-card mb-3">
            <div class="card-header irinn-card-header">
                <h5 class="mb-0">Invoice header</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm table-bordered mb-0">
                    <tbody>
                    <tr><th class="w-25">Proposed invoice no.</th><td><code>{{ $preview['proposed_invoice_number'] }}</code></td></tr>
                    <tr><th>Invoice date</th><td>{{ $preview['invoice_date'] }}</td></tr>
                    <tr><th>Due date</th><td>{{ $preview['due_date'] }}</td></tr>
                    <tr><th>Resources as on (allocation date)</th><td>{{ $preview['as_on_for_resources'] }}</td></tr>
                    <tr><th>Allocation date (hostmaster)</th><td>{{ $preview['allocation_date'] }}</td></tr>
                    <tr><th>IPv4 / IPv6 (counts)</th><td>{{ $preview['ipv4_addresses'] }} / {{ $preview['ipv6_addresses'] }}</td></tr>
                    <tr><th>E-invoice (IRN)</th><td>{{ $preview['would_request_einvoice'] ? 'Yes — GSTIN present and API configured' : 'No — without valid billing GSTIN or API' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card irinn-app-card mb-4">
            <div class="card-header irinn-card-header">
                <h5 class="mb-0">Amounts</h5>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">{{ $preview['line_description'] }}</p>
                <table class="table table-sm table-bordered mb-0" style="max-width: 520px;">
                    <tbody>
                    <tr><th>Annual base (before discount)</th><td class="text-end">₹{{ number_format($preview['annual_base_before_discount'], 2) }}</td></tr>
                    <tr><th>Discount ({{ number_format($preview['discount_percent'], 2) }}%)</th><td class="text-end">− ₹{{ number_format($preview['discount_amount'], 2) }}</td></tr>
                    <tr><th>Taxable value</th><td class="text-end">₹{{ number_format($preview['taxable_after_discount'], 2) }}</td></tr>
                    <tr><th>IGST @ {{ $preview['igst_rate_percent'] }}%</th><td class="text-end">₹{{ number_format($preview['igst_amount'], 2) }}</td></tr>
                    <tr class="table-light"><th class="fw-bold">Total payable</th><td class="text-end fw-bold">₹{{ number_format($preview['grand_total'], 2) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="small text-muted mb-0">
            Close this tab and use <strong>Generate annual invoice</strong> on the application page only if this preview matches what you intend to bill.
        </p>
    </div>
@endsection
