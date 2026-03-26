@extends('user.layout')

@section('title', 'New IRINN Application')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center mb-4 w-100">
        <div class="w-100">
            <h2 class="mb-2" style="color:#1f2937;">IRINN Application</h2>
            <p class="text-muted mb-0">Complete all steps to submit your NIXI IRINN request.</p>
        </div>
    </div>

    <div class="card border-c-blue shadow-sm">
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col">
                    <div class="step-indicator active" data-step="1">1. Application Details</div>
                </div>
                <div class="col">
                    <div class="step-indicator" data-step="2">2. Documentation</div>
                </div>
                <div class="col">
                    <div class="step-indicator" data-step="3">3. Payment & Declaration</div>
                </div>
            </div>

            <form id="ixApplicationForm" method="POST" class="theme-forms" action="{{ route('user.applications.ix.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="application_id" id="applicationIdInput" value="">

                <div class="form-step" data-step="1">
                    <div class="row g-4">
                        {{-- Member Type & Location Section --}}
                        <div class="col-12">
                            <div class="border rounded p-4 mb-4 bg-light border-c-blue">
                                <h6 class="mb-3 text-primary">Member Information</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Member Type <span class="text-danger">*</span></label>
                                        <select name="member_type" id="memberType" class="form-select" required>
                                            <option value="">Select Member Type</option>
                                            <option value="isp">ISP</option>
                                            <option value="cdn">CDN</option>
                                            <option value="vno">VNO</option>
                                            <option value="govt">Government Entity</option>
                                            <option value="others">Others</option>
                                        </select>
                                        <input type="text" name="member_type_other" id="memberTypeOther" class="form-control mt-2 d-none" placeholder="Specify member type">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label d-flex justify-content-between align-items-center">
                                            NIXI Location <span class="text-muted small">Filtered by GST state when available</span>
                                        </label>
                                        <select name="location_id" id="locationSelect" class="form-select" required>
                                            <option value="">Select Location</option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->id }}"
                                                    data-node-type="{{ $location->node_type }}"
                                                    data-state="{{ $location->state }}">
                                                    {{ $location->name }} ({{ ucfirst($location->node_type) }} - {{ $location->state }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @if($gstState = $gstState ?? null)
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" id="filterByGstState" checked data-gst-state="{{ $gstState }}">
                                                <label class="form-check-label small text-muted" for="filterByGstState">
                                                    Show locations in GST state ({{ $gstState }})
                                                </label>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Port & Billing Section --}}
                        <div class="col-12">
                            <div class="border rounded p-4 mb-4 bg-light border-c-blue">
                                <h6 class="mb-3 text-primary">Port & Billing Details</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Port Capacity <span class="text-danger">*</span></label>
                            <select name="port_capacity" id="portCapacitySelect" class="form-select" required>
                                <option value="">Select capacity</option>
                                @foreach($portPricings as $nodeType => $entries)
                                    <optgroup label="{{ ucfirst($nodeType) }} nodes">
                                        @foreach($entries as $pricing)
                                            <option value="{{ $pricing->port_capacity }}" data-node-type="{{ $nodeType }}" data-arc="{{ $pricing->price_arc }}" data-mrc="{{ $pricing->price_mrc }}" data-quarterly="{{ $pricing->price_quarterly }}">
                                                {{ $pricing->port_capacity }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Billing Plan <span class="text-danger">*</span></label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="billing_plan" id="planArc" value="arc" required>
                                                <label class="form-check-label" for="planArc">Annual (ARC)</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="billing_plan" id="planMrc" value="mrc" required>
                                                <label class="form-check-label" for="planMrc">Monthly (MRC)</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="billing_plan" id="planQuarterly" value="quarterly" required>
                                                <label class="form-check-label" for="planQuarterly">Quarterly</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- IP Prefix Details Section --}}
                        <div class="col-12">
                            <div class="border rounded p-4 mb-4 bg-light border-c-blue">
                                <h6 class="mb-3 text-primary">IP Prefix Information</h6>
                                <div class="row g-3">
                                    <div class="col-md-6 col-lg-4">
                                        <label class="form-label">Number of IP Prefixes <span class="text-danger">*</span></label>
                                        <input type="number" min="1" class="form-control" name="ip_prefix_count" required>
                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <label class="form-label">IP Prefix Allocation Source <span class="text-danger">*</span></label>
                                        <select name="ip_prefix_source" id="ipPrefixSource" class="form-select" required>
                                            <option value="">Select Source</option>
                                            <option value="irinn">IRINN</option>
                                            <option value="apnic">APNIC</option>
                                            <option value="others">Others</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 col-lg-4 d-none" id="ipPrefixProviderWrapper">
                                        <label class="form-label">Provider Name</label>
                                        <input type="text" name="ip_prefix_provider" class="form-control" placeholder="Enter provider">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Peering Connectivity Section --}}
                        <div class="col-12">
                            <div class="border rounded p-4 mb-4 bg-light border-c-blue">
                                <h6 class="mb-3 text-primary">Peering Connectivity</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Member's Pre-NIXI peering connectivity <span class="text-danger">*</span></label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="pre_peering_connectivity" id="prePeeringNone" value="none" required>
                                                <label class="form-check-label" for="prePeeringNone">None</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="pre_peering_connectivity" id="prePeeringSingle" value="single" required>
                                                <label class="form-check-label" for="prePeeringSingle">Single</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="pre_peering_connectivity" id="prePeeringMultiple" value="multiple" required>
                                                <label class="form-check-label" for="prePeeringMultiple">Multiple</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="asnNumberContainer" style="display: block;">
                                        <label class="form-label">AS Number used for peering in the NIXI <span class="text-danger">*</span></label>
                                        <input type="text" name="asn_number" id="asnNumber" class="form-control" placeholder="e.g., AS131269" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Router Details Section --}}
                        <div class="col-12">
                            <div class="border rounded p-4 mb-4 bg-light border-c-blue">
                                <h6 class="mb-3 text-primary">Dedicated Router Details (Optional)</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Height in U</label>
                                        <input type="number" min="1" max="50" name="router_height_u" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Make &amp; Model</label>
                                        <input type="text" name="router_make_model" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Serial Number</label>
                                        <input type="text" name="router_serial_number" class="form-control">
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2">Indicate if the router is owned, supplied, or co-located at NIXI in the description above.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-step d-none" data-step="2">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                        <h5 class="mb-2">Required Documentation</h5>
                        <a href="{{ route('user.applications.ix.agreement') }}" class="btn btn-primary" target="_blank">
                            Download Agreement Template
                        </a>
                    </div>
                    <p class="text-muted">Upload clear PDF copies. Maximum size per document is 10 MB.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Signed Agreement with NIXI <span class="text-danger">*</span></label>
                            <input type="file" name="agreement_file" class="form-control" accept="application/pdf" required>
                        </div>
                        <div class="col-md-6" id="ispLicenseContainer" style="display: none;">
                            <label class="form-label">ISP License <span class="text-danger">*</span></label>
                            <input type="file" name="license_isp_file" id="licenseIspFile" class="form-control" accept="application/pdf">
                        </div>
                        <div class="col-md-6" id="vnoLicenseContainer" style="display: none;">
                            <label class="form-label">VNO License <span class="text-danger">*</span></label>
                            <input type="file" name="license_vno_file" id="licenseVnoFile" class="form-control" accept="application/pdf">
                        </div>
                        <div class="col-md-6" id="cdnDeclarationContainer" style="display: none;">
                            <label class="form-label">CDN Declaration <span class="text-danger">*</span></label>
                            <input type="file" name="cdn_declaration_file" id="cdnDeclarationFile" class="form-control" accept="application/pdf">
                        </div>
                        <div class="col-md-6" id="generalDeclarationContainer" style="display: none;">
                            <label class="form-label">General Declaration <span class="text-danger">*</span></label>
                            <input type="file" name="general_declaration_file" id="generalDeclarationFile" class="form-control" accept="application/pdf">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Whois Details <span class="text-danger">*</span></label>
                            <input type="file" name="whois_details_file" class="form-control" accept="application/pdf" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PAN Document <span class="text-danger">*</span></label>
                            <input type="file" name="pan_document_file" class="form-control" accept="application/pdf" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GSTIN Document <span class="text-danger">*</span></label>
                            <input type="file" name="gstin_document_file" class="form-control" accept="application/pdf" required>
                        </div>
                        <div class="col-md-6" id="msmeDocumentContainer" style="display: {{ ($kycProfile && $kycProfile->is_msme === true) ? 'block' : 'none' }};">
                            <label class="form-label">MSME (Udyog/Udyam) Certificate <span class="text-danger">*</span></label>
                            <input type="file" name="msme_document_file" id="msmeDocumentFile" class="form-control" accept="application/pdf" {{ ($kycProfile && $kycProfile->is_msme === true) ? 'required' : '' }}>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Certificate of Incorporation <span class="text-danger">*</span></label>
                            <input type="file" name="incorporation_document_file" class="form-control" accept="application/pdf" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Authorized Representative Document <span class="text-danger">*</span></label>
                            <input type="file" name="authorized_rep_document_file" class="form-control" accept="application/pdf" required>
                        </div>
                    </div>
                </div>

                <div class="form-step d-none" data-step="3">
                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="card border border-c-blue shadow-sm h-100">
                                <div class="card-header theme-bg-blue">
                                    <h5 class="mb-0">Application Summary</h5>
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4">Member Type</dt>
                                        <dd class="col-sm-8" id="summaryMemberType">—</dd>
                                        <dt class="col-sm-4">NIXI Location</dt>
                                        <dd class="col-sm-8" id="summaryLocation">—</dd>
                                        <dt class="col-sm-4">Port Capacity</dt>
                                        <dd class="col-sm-8" id="summaryCapacity">—</dd>
                                        <dt class="col-sm-4">Billing Plan</dt>
                                        <dd class="col-sm-8" id="summaryPlan">—</dd>
                                        <dt class="col-sm-4">Estimated Fee</dt>
                                        <dd class="col-sm-8" id="summaryAmount">—</dd>
                                        <dt class="col-sm-4">IP Prefixes</dt>
                                        <dd class="col-sm-8" id="summaryPrefixes">—</dd>
                                        <dt class="col-sm-4">ASN</dt>
                                        <dd class="col-sm-8" id="summaryAsn">—</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="card border border-c-blue shadow-sm h-100">
                                <div class="card-header theme-bg-blue">
                                    <h5 class="mb-0">Declaration & Payment</h5>
                                </div>
                                <div class="card-body">
                                    @php
                                        $currentUser = \App\Models\Registration::find(session('user_id'));
                                        $wallet = $currentUser ? $currentUser->wallet : null;
                                        $walletBalance = $wallet && $wallet->status === 'active' ? (float) $wallet->balance : 0;
                                        $applicationAmount = $applicationPricing ? (float) $applicationPricing->total_amount : 1180.00;
                                        $canPayWithWallet = $wallet && $wallet->status === 'active' && $walletBalance >= $applicationAmount;
                                    @endphp
                                    
                                    @if($canPayWithWallet)
                                    <div class="alert alert-info mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>Advance Amount Available:</strong> ₹{{ number_format($walletBalance, 2) }}
                                                <br>
                                                <small class="text-muted">Application Fee: ₹{{ number_format($applicationAmount, 2) }}</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="paymentMethodWallet" value="wallet" checked>
                                            <label class="form-check-label" for="paymentMethodWallet">
                                                <strong>Pay with Advance Amount</strong> (₹{{ number_format($applicationAmount, 2) }})
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="paymentMethodPayu" value="payu">
                                            <label class="form-check-label" for="paymentMethodPayu">
                                                <strong>Pay with PayU</strong> (Credit/Debit Card, Net Banking, UPI)
                                            </label>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <p class="small text-blue fw-semibold">Declaration:</p>
                                    <p class="small">
                                        We agree to pay Membership fee of Rs.1000 + applicable taxes when demanded at the time of peering and annually once the peering is established. We agree to abide by the Memorandum and Articles of Association and the Rules of the company.
                                    </p>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="declaration_confirmed" id="declarationConfirmed" value="1" required>
                                        <label class="form-check-label" for="declarationConfirmed">
                                            I have read and agree to the declaration above.
                                        </label>
                                    </div>
                                    <div class="alert alert-info mb-0" id="pricingInfo">
                                        <p class="mb-1"><strong>Application Fee:</strong> ₹<span id="applicationFeeDisplay">{{ number_format($applicationPricing->application_fee ?? 1000, 2) }}</span></p>
                                        <p class="mb-1" id="gstDisplay" style="display: {{ (isset($applicationPricing->gst_percentage) && $applicationPricing->gst_percentage > 0) ? 'block' : 'none' }};"><strong>GST (<span id="gstPercentageDisplay">{{ $applicationPricing->gst_percentage ?? 18 }}</span>%):</strong> ₹<span id="gstAmountDisplay">{{ number_format((($applicationPricing->application_fee ?? 1000) * ($applicationPricing->gst_percentage ?? 18)) / 100, 2) }}</span></p>
                                        <p class="mb-1"><strong>Total Amount:</strong> ₹<span id="totalAmountDisplay">{{ number_format($applicationPricing->total_amount ?? 1180, 2) }}</span></p>
                                        <p class="mb-0 small" id="paymentMethodText">@if($canPayWithWallet)You can pay using your advance amount or PayU payment gateway.@else You will be redirected to PayU payment gateway to complete the payment.@endif</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary" id="prevStepBtn" disabled>Previous</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" id="nextStepBtn">Next</button>
                        <button type="submit" class="btn btn-success d-none" id="submitAndPayBtn">Submit and Pay</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Location Directory Modal -->
<div class="modal fade" id="locationDirectoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">NIXI Location Directory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>State</th>
                                <th>Node Type</th>
                                <th>Switch</th>
                                <th>Ports</th>
                                <th>Nodal Officer</th>
                                <th>Zone</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($locations as $location)
                                <tr>
                                    <td>{{ $location->name }}</td>
                                    <td>{{ $location->state }}</td>
                                    <td>{{ ucfirst($location->node_type) }}</td>
                                    <td>{{ $location->switch_details ?? '—' }}</td>
                                    <td>{{ $location->ports ?? '—' }}</td>
                                    <td>{{ $location->nodal_officer ?? '—' }}</td>
                                    <td>{{ $location->zone ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.step-indicator {
    background-color: #d4d5e1;
    border: 1px solid #d1d5db;
    color: var(--theme-blue);
    border-radius: 8px;
    padding: 12px;
    text-align: center;
    font-weight: 600;
    /* color: #6b7280; */
}
.step-indicator.active {
    /* border-color: #2563eb;
    color: #2563eb;
    background-color: #eff6ff; */
    background: #0d6efd;
    color: white;
}
.form-step {
    animation: fadeIn .2s ease-in-out;
}
.theme-forms .form-check {
    display: flex;
}
.theme-forms .form-check-label {
    position: relative;
    top: 4px;
}
.theme-forms input[type="file"] {
    min-height: auto;
}
@keyframes fadeIn {
    from { opacity:0; transform: translateY(6px); }
    to { opacity:1; transform: translateY(0); }
}
/* Connected container styling */
.form-step .border.rounded {
    /* border: 2px solid #e5e7eb !important; */
    transition: all 0.3s ease;
}
.form-step .border.rounded:hover {
    border-color: #2563eb !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
.form-step .border.rounded h6 {
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 0.75rem;
    margin-bottom: 1rem;
}
.form-step .bg-light {
    /* background-color: #f9fafb !important; */
    background-color: transparent !important;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('ixApplicationForm');
    const steps = Array.from(document.querySelectorAll('.form-step'));
    const indicators = Array.from(document.querySelectorAll('.step-indicator'));

    // Restore step from query string (default to 1)
    const url = new URL(window.location.href);
    const initialStep = parseInt(url.searchParams.get('step') || '1', 10);
    let currentStep = [1, 2, 3].includes(initialStep) ? initialStep : 1;


    const nextBtn = document.getElementById('nextStepBtn');
    const prevBtn = document.getElementById('prevStepBtn');
    const submitAndPayBtn = document.getElementById('submitAndPayBtn');

    // MSME Document visibility based on KYC
    const msmeDocumentContainer = document.getElementById('msmeDocumentContainer');
    const msmeDocumentFile = document.getElementById('msmeDocumentFile');
    const isMsmeFromKyc = {{ ($kycProfile && $kycProfile->is_msme === true) ? 'true' : 'false' }};
    
    if (isMsmeFromKyc) {
        msmeDocumentContainer.style.display = 'block';
        msmeDocumentFile.setAttribute('required', 'required');
    } else {
        msmeDocumentContainer.style.display = 'none';
        msmeDocumentFile.removeAttribute('required');
    }

    // Handle documentation fields visibility based on member type
    const memberTypeSelect = document.getElementById('memberType');
    const ispLicenseContainer = document.getElementById('ispLicenseContainer');
    const vnoLicenseContainer = document.getElementById('vnoLicenseContainer');
    const cdnDeclarationContainer = document.getElementById('cdnDeclarationContainer');
    const generalDeclarationContainer = document.getElementById('generalDeclarationContainer');
    const licenseIspFile = document.getElementById('licenseIspFile');
    const licenseVnoFile = document.getElementById('licenseVnoFile');
    const cdnDeclarationFile = document.getElementById('cdnDeclarationFile');
    const generalDeclarationFile = document.getElementById('generalDeclarationFile');
    
    function toggleDocumentationFields() {
        const memberType = memberTypeSelect.value;
        
        // Hide all containers first
        ispLicenseContainer.style.display = 'none';
        vnoLicenseContainer.style.display = 'none';
        cdnDeclarationContainer.style.display = 'none';
        generalDeclarationContainer.style.display = 'none';
        
        // Remove required attribute from all fields
        licenseIspFile.removeAttribute('required');
        licenseVnoFile.removeAttribute('required');
        cdnDeclarationFile.removeAttribute('required');
        generalDeclarationFile.removeAttribute('required');
        
        // Clear values when hidden
        licenseIspFile.value = '';
        licenseVnoFile.value = '';
        cdnDeclarationFile.value = '';
        generalDeclarationFile.value = '';
        
        // Show and make required the appropriate field based on member type
        if (memberType === 'isp') {
            ispLicenseContainer.style.display = 'block';
            licenseIspFile.setAttribute('required', 'required');
        } else if (memberType === 'vno') {
            vnoLicenseContainer.style.display = 'block';
            licenseVnoFile.setAttribute('required', 'required');
        } else if (memberType === 'cdn') {
            cdnDeclarationContainer.style.display = 'block';
            cdnDeclarationFile.setAttribute('required', 'required');
        } else if (memberType === 'govt' || memberType === 'others') {
            generalDeclarationContainer.style.display = 'block';
            generalDeclarationFile.setAttribute('required', 'required');
        }
    }
    
    // Initialize on page load
    toggleDocumentationFields();
    // Show the initial step based on query string
    showStep(currentStep);

    // Client-side validation
    function validateStep(step, skipDeclaration = false) {
        const stepElement = document.querySelector(`.form-step[data-step="${step}"]`);
        const requiredFields = stepElement.querySelectorAll('[required]');
        let isValid = true;
        const processedRadioGroups = new Set();

        requiredFields.forEach(field => {
            // Skip declaration checkbox validation if skipDeclaration is true
            if (skipDeclaration && field.name === 'declaration_confirmed') {
                return;
            }
            
            // Skip validation for hidden fields
            if (field.closest('[style*="display: none"]') || field.offsetParent === null) {
                return;
            }
            
            // For radio buttons, check if any radio in the group is selected
            if (field.type === 'radio') {
                // Skip if we've already validated this radio group
                if (processedRadioGroups.has(field.name)) {
                    return;
                }
                processedRadioGroups.add(field.name);
                
                // Check if any radio in this group is selected
                const radioGroup = stepElement.querySelectorAll(`input[type="radio"][name="${field.name}"]`);
                const isSelected = Array.from(radioGroup).some(radio => radio.checked);
                
                if (!isSelected) {
                    // Mark all radios in the group as invalid
                    radioGroup.forEach(radio => {
                        radio.classList.add('is-invalid');
                        // Also add visual feedback to the parent form-check
                        const parent = radio.closest('.form-check');
                        if (parent) {
                            parent.classList.add('border-danger', 'rounded', 'p-2');
                        }
                    });
                    isValid = false;
                } else {
                    // Remove invalid class from all radios in the group
                    radioGroup.forEach(radio => {
                        radio.classList.remove('is-invalid');
                        const parent = radio.closest('.form-check');
                        if (parent) {
                            parent.classList.remove('border-danger', 'rounded', 'p-2');
                        }
                    });
                }
            }
            // For checkboxes, check if checked
            else if (field.type === 'checkbox') {
                if (!field.checked) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            }
            // For file inputs, check if file is selected
            else if (field.type === 'file') {
                if (!field.files || field.files.length === 0) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            }
            // For other fields, check if value exists
            else if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });

        // Custom validations
        if (step === 1) {
            // Validate billing plan radio group
            const billingPlan = form.querySelector('input[name="billing_plan"]:checked');
            if (!billingPlan) {
                const billingPlanRadios = stepElement.querySelectorAll('input[name="billing_plan"]');
                billingPlanRadios.forEach(radio => {
                    radio.classList.add('is-invalid');
                    const parent = radio.closest('.form-check');
                    if (parent) {
                        parent.classList.add('border-danger', 'rounded', 'p-2');
                    }
                });
                isValid = false;
            } else {
                const billingPlanRadios = stepElement.querySelectorAll('input[name="billing_plan"]');
                billingPlanRadios.forEach(radio => {
                    radio.classList.remove('is-invalid');
                    const parent = radio.closest('.form-check');
                    if (parent) {
                        parent.classList.remove('border-danger', 'rounded', 'p-2');
                    }
                });
            }
            
            // Validate pre-peering connectivity radio group
            const prePeering = form.querySelector('input[name="pre_peering_connectivity"]:checked');
            if (!prePeering) {
                const prePeeringRadios = stepElement.querySelectorAll('input[name="pre_peering_connectivity"]');
                prePeeringRadios.forEach(radio => {
                    radio.classList.add('is-invalid');
                    const parent = radio.closest('.form-check');
                    if (parent) {
                        parent.classList.add('border-danger', 'rounded', 'p-2');
                    }
                });
                isValid = false;
            } else {
                const prePeeringRadios = stepElement.querySelectorAll('input[name="pre_peering_connectivity"]');
                prePeeringRadios.forEach(radio => {
                    radio.classList.remove('is-invalid');
                    const parent = radio.closest('.form-check');
                    if (parent) {
                        parent.classList.remove('border-danger', 'rounded', 'p-2');
                    }
                });
            }
            
            const memberType = document.getElementById('memberType').value;
            if (memberType === 'others') {
                const memberTypeOther = document.getElementById('memberTypeOther');
                if (!memberTypeOther.value.trim()) {
                    memberTypeOther.classList.add('is-invalid');
                    isValid = false;
                }
            }
            const ipPrefixSource = document.getElementById('ipPrefixSource').value;
            if (ipPrefixSource === 'others') {
                const provider = document.querySelector('#ipPrefixProviderWrapper input');
                if (provider && !provider.value.trim()) {
                    provider.classList.add('is-invalid');
                    isValid = false;
                }
            }
        }

        return isValid;
    }

    function showStep(stepNumber) {
        currentStep = stepNumber;
        steps.forEach(step => step.classList.toggle('d-none', Number(step.dataset.step) !== currentStep));
        indicators.forEach(indicator => indicator.classList.toggle('active', Number(indicator.dataset.step) === currentStep));

        prevBtn.disabled = currentStep === 1;
        nextBtn.classList.toggle('d-none', currentStep >= steps.length);
        submitAndPayBtn.classList.toggle('d-none', currentStep !== 3);

        if (currentStep === 3) {
            updateSummary();
            // Fetch latest pricing from backend
            fetchApplicationPricing();
        }

        // Update ?step= in URL without reloading
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('step', String(currentStep));
        history.replaceState({}, '', currentUrl);
    }

    // Function to fetch and update application pricing
    async function fetchApplicationPricing() {
        try {
            const response = await fetch('{{ route("user.applications.ix.application-pricing") }}');
            const result = await response.json();
            if (result.success && result.data) {
                const pricing = result.data;
                document.getElementById('applicationFeeDisplay').textContent = pricing.application_fee.toFixed(2);
                if (pricing.gst_percentage > 0) {
                    const gstAmount = (pricing.application_fee * pricing.gst_percentage) / 100;
                    document.getElementById('gstPercentageDisplay').textContent = pricing.gst_percentage;
                    document.getElementById('gstAmountDisplay').textContent = gstAmount.toFixed(2);
                    document.getElementById('gstDisplay').style.display = 'block';
                } else {
                    document.getElementById('gstDisplay').style.display = 'none';
                }
                document.getElementById('totalAmountDisplay').textContent = pricing.total_amount.toFixed(2);
            }
        } catch (error) {
            console.error('Error fetching application pricing:', error);
        }
    }

    function updateStep(direction) {
        if (direction === 'next') {
            if (!validateStep(currentStep)) {
                alert('Please fill all required fields before proceeding.');
                return;
            }
            if (currentStep < steps.length) {
                currentStep++;
            }
        } else if (direction === 'prev' && currentStep > 1) {
            currentStep--;
        }

        // Use showStep so UI + URL stay in sync
        showStep(currentStep);
    }

    nextBtn.addEventListener('click', () => updateStep('next'));
    prevBtn.addEventListener('click', () => updateStep('prev'));

    const memberTypeOther = document.getElementById('memberTypeOther');
    memberTypeSelect.addEventListener('change', () => {
        // Handle "others" member type field
        if (memberTypeSelect.value === 'others') {
            memberTypeOther.classList.remove('d-none');
            memberTypeOther.required = true;
        } else {
            memberTypeOther.classList.add('d-none');
            memberTypeOther.required = false;
            memberTypeOther.value = '';
        }
        // Update documentation fields visibility based on member type
        toggleDocumentationFields();
    });

    const ipPrefixSource = document.getElementById('ipPrefixSource');
    const ipPrefixProviderWrapper = document.getElementById('ipPrefixProviderWrapper');
    ipPrefixSource.addEventListener('change', () => {
        if (ipPrefixSource.value === 'others') {
            ipPrefixProviderWrapper.classList.remove('d-none');
            ipPrefixProviderWrapper.querySelector('input').required = true;
        } else {
            ipPrefixProviderWrapper.classList.add('d-none');
            ipPrefixProviderWrapper.querySelector('input').required = false;
            ipPrefixProviderWrapper.querySelector('input').value = '';
        }
    });

    const filterCheckbox = document.getElementById('filterByGstState');
    const locationSelect = document.getElementById('locationSelect');
    function applyLocationFilter() {
        const gstState = filterCheckbox?.dataset.gstState;
        const filterEnabled = filterCheckbox && filterCheckbox.checked && gstState;
        const options = Array.from(locationSelect.options);
        options.forEach(option => {
            if (!option.value) {
                option.hidden = false;
                return;
            }
            const match = option.dataset.state === gstState;
            option.hidden = filterEnabled ? !match : false;
        });
        if (filterEnabled && locationSelect.selectedOptions.length && locationSelect.selectedOptions[0].hidden) {
            locationSelect.value = '';
        }
    }
    if (filterCheckbox) {
        filterCheckbox.addEventListener('change', applyLocationFilter);
        applyLocationFilter();
    }

    const portCapacitySelect = document.getElementById('portCapacitySelect');
    function syncPortOptions() {
        const selectedLocation = locationSelect.selectedOptions[0];
        if (!selectedLocation) {
            return;
        }
        const nodeType = selectedLocation.dataset.nodeType;
        Array.from(portCapacitySelect.options).forEach(option => {
            if (!option.value) {
                option.hidden = false;
                return;
            }
            option.hidden = option.dataset.nodeType !== nodeType;
        });
        if (portCapacitySelect.selectedOptions.length && portCapacitySelect.selectedOptions[0].hidden) {
            portCapacitySelect.value = '';
        }
    }
    locationSelect.addEventListener('change', syncPortOptions);

    function updateSummary() {
        const memberTypeText = memberTypeSelect.value === 'others'
            ? (memberTypeOther.value || 'Others')
            : memberTypeSelect.options[memberTypeSelect.selectedIndex]?.text || '—';
        document.getElementById('summaryMemberType').textContent = memberTypeText || '—';

        const locationOption = locationSelect.options[locationSelect.selectedIndex];
        const locationText = locationOption && locationOption.value ? locationOption.text : '—';
        document.getElementById('summaryLocation').textContent = locationText;

        const capacityOption = portCapacitySelect.options[portCapacitySelect.selectedIndex];
        document.getElementById('summaryCapacity').textContent = (capacityOption && capacityOption.value) ? capacityOption.value : '—';

        const plan = form.querySelector('input[name="billing_plan"]:checked');
        const planReadable = plan ? plan.nextElementSibling.textContent : '—';
        document.getElementById('summaryPlan').textContent = planReadable;

        let amountText = '—';
        if (capacityOption && capacityOption.value && plan) {
            const dataAttr = plan.value === 'arc' ? 'data-arc' : (plan.value === 'mrc' ? 'data-mrc' : 'data-quarterly');
            const amountValue = Number(capacityOption.getAttribute(dataAttr) || 0);
            amountText = amountValue > 0 ? `₹${amountValue.toLocaleString('en-IN', { minimumFractionDigits: 2 })}` : '—';
        }
        document.getElementById('summaryAmount').textContent = amountText;

        const prefixes = form.querySelector('input[name="ip_prefix_count"]')?.value || '—';
        const prefixSourceSelect = document.getElementById('ipPrefixSource');
        const prefixSource = prefixSourceSelect && prefixSourceSelect.options[prefixSourceSelect.selectedIndex] ? prefixSourceSelect.options[prefixSourceSelect.selectedIndex].text : '';
        document.getElementById('summaryPrefixes').textContent = prefixes !== '—' ? prefixes : '—';

        const asnInput = form.querySelector('input[name="asn_number"]');
        document.getElementById('summaryAsn').textContent = asnInput?.value || '—';
    }

    // Form submission
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Validate all steps including declaration checkbox for final submission
        // Step 1: Application Details
        if (!validateStep(1, false)) {
            showStep(1);
            alert('Please fill all required fields in Application Details before submitting.');
            return;
        }
        
        // Step 2: Documentation
        if (!validateStep(2, false)) {
            showStep(2);
            alert('Please upload all required documents before submitting.');
            return;
        }
        
        // Step 3: Payment & Declaration (declaration required for submission)
        if (!validateStep(3, false)) {
            showStep(3);
            alert('Please accept the declaration before submitting.');
            return;
        }
        
        if (!confirm('Are you sure you want to submit this application and proceed to payment? You will not be able to edit it after submission.')) {
            return;
        }

        const formData = new FormData(form);
        formData.append('is_draft', '0');
        
        // Check payment method
        const paymentMethod = form.querySelector('input[name="payment_method"]:checked')?.value || 'payu';
        const isWalletPayment = paymentMethod === 'wallet';
        
        try {
            let response;
            if (isWalletPayment) {
                // Use wallet payment endpoint
                response = await fetch('{{ route("user.applications.ix.initiate-payment-with-wallet") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                    }
                });
            } else {
                // Use PayU payment endpoint
                formData.append('initiate_payment', '1');
                response = await fetch('{{ route("user.applications.ix.initiate-payment") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                    }
                });
            }

            if (response.ok) {
                const data = await response.json();
                
                if (isWalletPayment) {
                    // Wallet payment - redirect to applications page
                    if (data.success) {
                        alert(data.message || 'Application submitted successfully! Payment completed using wallet.');
                        window.location.href = data.redirect_url || '{{ route("user.applications.index") }}';
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error'));
                    }
                } else {
                    // PayU payment - redirect to PayU
                    if (data.success && data.payment_form) {
                        // Create a form and submit to PayU
                        const payuForm = document.createElement('form');
                        payuForm.method = 'POST';
                        payuForm.action = data.payment_url;
                        
                        Object.keys(data.payment_form).forEach(key => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = key;
                            input.value = data.payment_form[key];
                            payuForm.appendChild(input);
                        });
                        
                        document.body.appendChild(payuForm);
                        payuForm.submit();
                    } else {
                        alert('Error initiating payment: ' + (data.message || 'Unknown error'));
                    }
                }
            } else {
                const errorData = await response.json();
                alert('Error submitting application: ' + (errorData.message || 'Please check all fields and try again.'));
            }
        } catch (error) {
            alert('Error submitting application. Please try again.');
            console.error(error);
        }
    });

});
</script>
@endpush


