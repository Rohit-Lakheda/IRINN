@extends('user.layout')

@section('title', 'New IRINN Application')

@php
    $fromPreview = request()->get('from_preview') == '1';
    $isResubmission = isset($isResubmission) && $isResubmission && isset($application);
    $previewData = $isResubmission ? ($application->application_data ?? []) : session('irin_preview_data', []);
    $hasPreview = function (string $keyPath) use ($previewData): bool {
        $val = data_get($previewData, $keyPath);
        if (is_array($val)) return count($val) > 0;
        return !empty($val);
    };
    $prefill = $prefill ?? [];
    $resubmissionReason = $resubmissionReason ?? '';
    $currentAffiliate = old('affiliate_type', $prefill['affiliate_type'] ?? '');
    $currentDomain = old('domain_required', $prefill['domain_required'] ?? 'yes');
    $currentAsn = old('asn_required', $prefill['asn_required'] ?? 'no');
@endphp

@push('styles')
<style>
    .irinn-form-container {
        max-width: 960px;
        margin: 0 auto;
        padding: 20px;
    }
    .form-step {
        background: #fff;
        border: 1px solid rgba(124, 58, 237, 0.15);
        border-radius: 14px;
        padding: 28px;
        margin-bottom: 30px;
        box-shadow: 0 2px 12px rgba(124, 58, 237, 0.08);
        display: none;
    }
    .form-step.active {
        display: block;
    }
    .form-step h3 {
        color: #5b21b6;
        font-size: 1.15rem;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid rgba(124, 58, 237, 0.35);
    }
    .form-group {
        margin-bottom: 1rem;
    }
    .form-label {
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
        display: block;
    }
    .required {
        color: #7c3aed;
    }
    .file-upload-area {
        border: 2px dashed rgba(124, 58, 237, 0.3);
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        background: rgba(124, 58, 237, 0.04);
        margin-top: 8px;
    }
    .file-upload-area:hover {
        border-color: #7c3aed;
        background: rgba(124, 58, 237, 0.08);
    }
    .btn-action-group {
        display: flex;
        gap: 12px;
        justify-content: space-between;
        align-items: flex-start;
        margin-top: 28px;
        padding-top: 20px;
        border-top: 1px solid rgba(124, 58, 237, 0.12);
    }
    .btn-action-group > button {
        flex-shrink: 0;
        border-radius: 10px;
    }
    .btn-action-group > div {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 10px;
    }
    .declaration-box {
        background: rgba(124, 58, 237, 0.08);
        border: 1px solid rgba(124, 58, 237, 0.25);
        border-radius: 10px;
        padding: 15px;
        margin: 20px 0;
    }
    .upstream-provider-form {
        background: rgba(124, 58, 237, 0.05);
        padding: 18px;
        border-radius: 10px;
        margin-top: 15px;
        border: 1px solid rgba(124, 58, 237, 0.12);
    }
    .pricing-display {
        background: rgba(124, 58, 237, 0.08);
        border: 1px solid rgba(124, 58, 237, 0.25);
        border-radius: 10px;
        padding: 15px;
        margin-top: 15px;
    }
    .pricing-display h5 {
        color: #5b21b6;
        margin-bottom: 10px;
    }
    .pricing-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid rgba(124, 58, 237, 0.15);
    }
    .pricing-row:last-child {
        border-bottom: none;
        font-weight: bold;
        font-size: 1rem;
        margin-top: 10px;
        padding-top: 15px;
        border-top: 2px solid rgba(124, 58, 237, 0.3);
    }
    .step-indicator {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-bottom: 32px;
        flex-wrap: wrap;
    }
    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .step-item:hover.visited .step-dot {
        transform: scale(1.05);
    }
    .step-dot {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #e5e7eb;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.95rem;
        transition: all 0.25s;
    }
    .step-item.visited .step-dot {
        background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%);
        color: #fff;
    }
    .step-item.active .step-dot {
        background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        color: #fff;
        box-shadow: 0 4px 14px rgba(124, 58, 237, 0.4);
    }
    .step-item .step-label {
        font-size: 0.7rem;
        color: #6b7280;
        margin-top: 6px;
        max-width: 72px;
        text-align: center;
        line-height: 1.2;
    }
    .step-item.active .step-label,
    .step-item.visited .step-label {
        color: #5b21b6;
        font-weight: 500;
    }
    .agreement-download-btn {
        margin-bottom: 15px;
    }
    .irinn-form-container .btn-primary {
        background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        border: none;
        border-radius: 10px;
    }
    .irinn-form-container .btn-primary:hover {
        opacity: 0.95;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="irinn-form-container">
        @if($isResubmission)
        <div class="alert alert-warning border-warning mb-4">
            <h5 class="alert-heading">Resubmission requested</h5>
            <p class="mb-2">Admin has requested changes to your application. Please update the details below as needed and resubmit. <strong>No payment is required</strong> — payment was already received.</p>
            @if($resubmissionReason)
            <hr>
            <p class="mb-0"><strong>Admin message:</strong><br>{{ $resubmissionReason }}</p>
            @endif
        </div>
        @endif
        <div class="mb-4">
            <h2 class="mb-2 text-blue fw-bold">{{ $isResubmission ? 'Edit & Resubmit IRINN Application' : 'IRINN Application' }}</h2>
            <p class="text-muted mb-0">{{ $isResubmission ? 'Update the details below and resubmit. All existing data and documents are prefilled.' : 'Complete all steps to submit your IRINN application. You can go back to any step you have already filled.' }}</p>
            <div class="accent-line mt-2"></div>
        </div>

        <!-- Step Indicator with labels -->
        <div class="step-indicator">
            <div class="step-item active visited" data-step="1" title="Application [IRINN]">
                <div class="step-dot">1</div>
                <span class="step-label">Application</span>
            </div>
            <div class="step-item" data-step="2" title="New Resources">
                <div class="step-dot">2</div>
                <span class="step-label">Resources</span>
            </div>
            <div class="step-item" data-step="3" title="Agreement & Documents">
                <div class="step-dot">3</div>
                <span class="step-label">Documents</span>
            </div>
            <div class="step-item" data-step="4" title="Resource Justification">
                <div class="step-dot">4</div>
                <span class="step-label">Justification</span>
            </div>
            <div class="step-item" data-step="5" title="Payment">
                <div class="step-dot">5</div>
                <span class="step-label">Payment</span>
            </div>
        </div>

        <form id="irinnApplicationForm" method="POST" action="{{ $isResubmission ? route('user.applications.irin.resubmit.store', $application->id) : route('user.applications.irin.store-new') }}" enctype="multipart/form-data">
            @csrf
            @if($fromPreview && !$isResubmission)
                <input type="hidden" name="from_preview" value="1">
            @endif

            <!-- Step 1: Application [IRINN] -->
            <div class="form-step active" id="step1" data-step="1">
                <h3>Step 1: Application [IRINN]</h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label class="form-label">Affiliate Type <span class="required">*</span></label>
                            <select class="form-select" name="affiliate_type" id="affiliate_type" required>
                                <option value="">Select affiliate type</option>
                                <option value="new" {{ $currentAffiliate == 'new' ? 'selected' : '' }}>Affiliate – New</option>
                                <option value="transfer" {{ $currentAffiliate == 'transfer' ? 'selected' : '' }}>Affiliate – Transfer from APNIC</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label class="form-label">Is .IN domain name required? <span class="required">*</span></label>
                            <div class="d-flex gap-4 pt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="domain_required" id="domain_yes" value="yes" required {{ $currentDomain == 'yes' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="domain_yes">Yes</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="domain_required" id="domain_no" value="no" required {{ $currentDomain == 'no' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="domain_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="btn-action-group">
                    <div></div>
                    <button type="button" class="btn btn-primary" onclick="nextStep()">Next</button>
                </div>
            </div>

            <!-- Step 2: New Resources -->
            <div class="form-step" id="step2" data-step="2">
                <h3>Step 2: New Resources</h3>
                <p class="text-muted small mb-2">At least one of IPv4 or IPv6 is required. You may fill both or either one.</p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="form-label">IPv4 (Internet Protocol)</label>
                            <select class="form-select" name="ipv4_prefix" id="ipv4_prefix" onchange="updatePricing(); clearIpStep2Validity();">
                                <option value="">Select IPv4 prefix</option>
                            </select>
                            <small class="text-muted" id="ipv4_pricing_info"></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="form-label">IPv6 (Internet Protocol)</label>
                            <select class="form-select" name="ipv6_prefix" id="ipv6_prefix" onchange="updatePricing(); clearIpStep2Validity();">
                                <option value="">Select IPv6 prefix</option>
                            </select>
                            <small class="text-muted" id="ipv6_pricing_info"></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="form-label">Autonomous System Number (ASN) <span class="required">*</span></label>
                            <select class="form-select" name="asn_required" id="asn_required" required>
                                <option value="">Select</option>
                                <option value="yes" {{ $currentAsn == 'yes' ? 'selected' : '' }}>Yes</option>
                                <option value="no" {{ $currentAsn == 'no' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Pricing Display -->
                <div class="pricing-display" id="pricingDisplay" style="display: none;">
                    <h5>Fee Calculation</h5>
                    <div class="pricing-row">
                        <span>Base Amount:</span>
                        <span id="max_ip_fee">₹ 0</span>
                    </div>
                    <div class="pricing-row" id="gst_row" style="display: none;">
                        <span>GST:</span>
                        <span id="gst_amount">₹ 0</span>
                    </div>
                    <div class="pricing-row">
                        <span>Total Fee (Including GST):</span>
                        <span id="total_fee">₹ 0</span>
                    </div>
                </div>

                <div class="btn-action-group">
                    <button type="button" class="btn btn-secondary" onclick="previousStep()">Previous</button>
                    <button type="button" class="btn btn-primary" onclick="nextStep()">Next</button>
                </div>
            </div>

            <!-- Step 3: IRINN Agreement and Documents -->
            <div class="form-step" id="step3" data-step="3">
                <h3>Step 3: IRINN Agreement and Documents</h3>
                
                <div class="form-group">
                    <label class="form-label">Board Resolution / Authority Letter / Self-Declaration <span class="required">*</span></label>
                    <div class="small text-muted mb-2">
                        <ul class="mb-0">
                            <li>Provide board resolution (in case of company) and authority letter (in case of partnership/llp) duly signed and stamped by minimum 50% of directors/ partners except the authorized signatory.</li>
                            <li>Provide self-declaration in case of sole proprietorship.</li>
                        </ul>
                    </div>
                    <div class="file-upload-area">
                        <input type="file" class="form-control" name="board_resolution_file" id="board_resolution_file" accept="application/pdf,image/*" {{ ($fromPreview || $isResubmission) && $hasPreview('part3.board_resolution_file') ? '' : 'required' }}>
                        <small class="text-muted">
                            Upload PDF or Image file
                            @if(($fromPreview || $isResubmission) && $hasPreview('part3.board_resolution_file'))
                                <span class="ms-2 badge rounded-pill text-bg-success">Already uploaded</span>
                                <a class="ms-2" target="_blank" href="{{ $isResubmission ? route('user.applications.irin.resubmit.document', [$application->id, 'board_resolution_file']) : route('user.applications.irin.preview-document', ['doc' => 'board_resolution_file']) }}">View</a>
                            @endif
                        </small>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">IRINN Agreement <span class="required">*</span></label>
                    <div class="small text-muted mb-2">
                        <ul class="mb-0">
                            <li>Provide filled IRINN agreement with all required fields duly signed and stamped by authorized signatory on bottom right of all pages.</li>
                            <li>Signed the same with digital signature (Mention the verification process for the same)</li>
                            <li>If signed physically provide the signature proof (PAN/ Passport/ Driving License)</li>
                        </ul>
                    </div>
                    <div class="agreement-download-btn">
                        <a href="{{ route('user.applications.irin.download-agreement') }}" class="btn btn-outline-primary" target="_blank">
                            <i class="bi bi-download"></i> Download IRINN Agreement (Prefilled)
                        </a>
                    </div>
                    <div class="file-upload-area">
                        <input type="file" class="form-control" name="irinn_agreement_file" id="irinn_agreement_file" accept="application/pdf,image/*" {{ ($fromPreview || $isResubmission) && $hasPreview('part3.irinn_agreement_file') ? '' : 'required' }}>
                        <small class="text-muted">
                            Upload PDF or Image file
                            @if(($fromPreview || $isResubmission) && $hasPreview('part3.irinn_agreement_file'))
                                <span class="ms-2 badge rounded-pill text-bg-success">Already uploaded</span>
                                <a class="ms-2" target="_blank" href="{{ $isResubmission ? route('user.applications.irin.resubmit.document', [$application->id, 'irinn_agreement_file']) : route('user.applications.irin.preview-document', ['doc' => 'irinn_agreement_file']) }}">View</a>
                            @endif
                        </small>
                    </div>
                </div>

                <div class="btn-action-group">
                    <button type="button" class="btn btn-secondary" onclick="previousStep()">Previous</button>
                    <button type="button" class="btn btn-primary" onclick="nextStep()">Next</button>
                </div>
            </div>

            <!-- Step 4: Resource Justification Requirement -->
            <div class="form-step" id="step4" data-step="4">
                <h3>Step 4: Resource Justification Requirement</h3>
                
                <div class="form-group">
                    <label class="form-label">Network Diagram <span class="required">*</span></label>
                    <div class="small text-muted mb-2">
                        Network Diagram with proper IP distribution on each node, number of router, switches, firewall must be mentioned on diagram.
                    </div>
                    <div class="file-upload-area">
                        <input type="file" class="form-control" name="network_diagram_file" id="network_diagram_file" accept="application/pdf,image/*" {{ ($fromPreview || $isResubmission) && $hasPreview('part4.network_diagram_file') ? '' : 'required' }}>
                        <small class="text-muted">
                            Upload PDF or Image file
                            @if(($fromPreview || $isResubmission) && $hasPreview('part4.network_diagram_file'))
                                <span class="ms-2 badge rounded-pill text-bg-success">Already uploaded</span>
                                <a class="ms-2" target="_blank" href="{{ $isResubmission ? route('user.applications.irin.resubmit.document', [$application->id, 'network_diagram_file']) : route('user.applications.irin.preview-document', ['doc' => 'network_diagram_file']) }}">View</a>
                            @endif
                        </small>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Core Physical Equipment Invoice <span class="required">*</span></label>
                    <div class="small text-muted mb-2">
                        Submit the Core physical equipment invoice and Endpoint device invoice signed and stamped by vendor. The invoice shows Equipment like Routers & Switches in the invoice.
                    </div>
                    <div class="file-upload-area">
                        <input type="file" class="form-control" name="equipment_invoice_file" id="equipment_invoice_file" accept="application/pdf,image/*" {{ ($fromPreview || $isResubmission) && $hasPreview('part4.equipment_invoice_file') ? '' : 'required' }}>
                        <small class="text-muted">
                            Upload PDF or Image file
                            @if(($fromPreview || $isResubmission) && $hasPreview('part4.equipment_invoice_file'))
                                <span class="ms-2 badge rounded-pill text-bg-success">Already uploaded</span>
                                <a class="ms-2" target="_blank" href="{{ $isResubmission ? route('user.applications.irin.resubmit.document', [$application->id, 'equipment_invoice_file']) : route('user.applications.irin.preview-document', ['doc' => 'equipment_invoice_file']) }}">View</a>
                            @endif
                        </small>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Bandwidth Invoice (Last 3 months) <span class="required">*</span></label>
                    <div class="file-upload-area">
                        <input type="file" class="form-control" name="bandwidth_invoice_file[]" id="bandwidth_invoice_file" accept="application/pdf,image/*" multiple {{ ($fromPreview || $isResubmission) && $hasPreview('part4.bandwidth_invoice_file') ? '' : 'required' }}>
                        <small class="text-muted">
                            Upload PDF or Image files (can upload multiple files for last 3 months)
                            @if(($fromPreview || $isResubmission) && $hasPreview('part4.bandwidth_invoice_file'))
                                @php $bw = data_get($previewData, 'part4.bandwidth_invoice_file', []); @endphp
                                <span class="ms-2 badge rounded-pill text-bg-success">Already uploaded ({{ is_array($bw) ? count($bw) : 0 }})</span>
                                @if(is_array($bw) && count($bw) > 0)
                                    <span class="ms-2">
                                        @foreach($bw as $i => $p)
                                            <a class="me-2" target="_blank" href="{{ $isResubmission ? route('user.applications.irin.resubmit.document', [$application->id, 'bandwidth_invoice_file']) : route('user.applications.irin.preview-document', ['doc' => 'bandwidth_invoice_file']) }}?index={{ $i }}">View {{ $i+1 }}</a>
                                        @endforeach
                                    </span>
                                @endif
                            @endif
                        </small>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Bandwidth Agreement <span class="required">*</span></label>
                    <div class="small text-muted mb-2">
                        Bandwidth Agreement with your upstream provider signed and stamped by both parties.
                    </div>
                    <div class="file-upload-area">
                        <input type="file" class="form-control" name="bandwidth_agreement_file" id="bandwidth_agreement_file" accept="application/pdf,image/*" {{ ($fromPreview || $isResubmission) && $hasPreview('part4.bandwidth_agreement_file') ? '' : 'required' }}>
                        <small class="text-muted">
                            Upload PDF or Image file
                            @if(($fromPreview || $isResubmission) && $hasPreview('part4.bandwidth_agreement_file'))
                                <span class="ms-2 badge rounded-pill text-bg-success">Already uploaded</span>
                                <a class="ms-2" target="_blank" href="{{ $isResubmission ? route('user.applications.irin.resubmit.document', [$application->id, 'bandwidth_agreement_file']) : route('user.applications.irin.preview-document', ['doc' => 'bandwidth_agreement_file']) }}">View</a>
                            @endif
                        </small>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Upstream Provider Details <span class="required">*</span></label>
                    <div class="small text-muted mb-2">Provide upstream provider details in the below-mentioned format (For verification)</div>
                    <div class="upstream-provider-form">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Name <span class="required">*</span></label>
                                <input type="text" class="form-control" name="upstream_name" id="upstream_name" value="{{ old('upstream_name', $prefill['upstream_name'] ?? '') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Mobile <span class="required">*</span></label>
                                <input type="tel" class="form-control" name="upstream_mobile" id="upstream_mobile" maxlength="10" value="{{ old('upstream_mobile', $prefill['upstream_mobile'] ?? '') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email <span class="required">*</span></label>
                                <input type="email" class="form-control" name="upstream_email" id="upstream_email" value="{{ old('upstream_email', $prefill['upstream_email'] ?? '') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Organization Name <span class="required">*</span></label>
                                <input type="text" class="form-control" name="upstream_org_name" id="upstream_org_name" value="{{ old('upstream_org_name', $prefill['upstream_org_name'] ?? '') }}" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Peering ASN Details <span class="required">*</span></label>
                                <input type="text" class="form-control" name="upstream_asn_details" id="upstream_asn_details" value="{{ old('upstream_asn_details', $prefill['upstream_asn_details'] ?? '') }}" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="btn-action-group">
                    <button type="button" class="btn btn-secondary" onclick="previousStep()">Previous</button>
                    <button type="button" class="btn btn-primary" onclick="nextStep()">Next</button>
                </div>
            </div>

            <!-- Step 5: Payment (or Resubmit only when resubmission) -->
            <div class="form-step" id="step5" data-step="5">
                <h3>Step 5: {{ $isResubmission ? 'Resubmit Application' : 'Payment' }}</h3>
                
                @if(!$isResubmission)
                <div class="declaration-box">
                    <p class="mb-0"><strong>Declaration:</strong> We agree to pay application fee of Rs.1000 + applicable taxes will be demanded at the time of submission of this application and will not adjusted in any future in any another/future invoice.</p>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="payment_declaration" id="payment_declaration" value="1" required>
                    <label class="form-check-label" for="payment_declaration">
                        I agree to the above declaration <span class="required">*</span>
                    </label>
                </div>
                @else
                <p class="text-muted">No payment required. Click <strong>Resubmit Application</strong> below to submit your updated details.</p>
                @endif

                <div class="btn-action-group">
                    <button type="button" class="btn btn-secondary" onclick="previousStep()">Previous</button>
                    <div>
                        @if(!$isResubmission)
                        <button type="button" class="btn btn-info" id="previewBtn">Preview</button>
                        @php
                            $userForm = \App\Models\Registration::find(session('user_id'));
                            $wallet = $userForm ? $userForm->wallet : null;
                            $walletBalance = $wallet && $wallet->status === 'active' ? (float) $wallet->balance : 0;
                            $canPayWithWallet = $wallet && $wallet->status === 'active' && $walletBalance >= 1180.00;
                        @endphp
                        @if($canPayWithWallet)
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="useWalletPayment" name="use_wallet_payment">
                                <label class="form-check-label" for="useWalletPayment">
                                    Pay with Advance Amount (Balance: ₹{{ number_format($walletBalance, 2) }})
                                </label>
                            </div>
                        @endif
                        <button type="submit" class="btn btn-success" id="submitBtn">Final Submit</button>
                        @else
                        <button type="submit" class="btn btn-success" id="submitBtn">Resubmit Application</button>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Resubmission prefill (for IP selects after options load)
window.irinnIsResubmission = @json($isResubmission ?? false);
window.irinnResubmissionPrefill = @json($prefill ?? []);

// Global pricing data
let ipPricingData = {
    ipv4: {},
    ipv6: {}
};

let currentStep = 1;
const totalSteps = 5;
const visitedSteps = new Set([1]);

// Fetch pricing from API
async function fetchIpPricing() {
    try {
        const response = await fetch('{{ route("user.applications.irin.pricing") }}');
        const result = await response.json();
        if (result.success && result.data) {
            const d = result.data;
            ipPricingData = {
                ipv4: d.ipv4 || d.IPv4 || {},
                ipv6: d.ipv6 || d.IPv6 || {}
            };
            populateIpOptions();
        }
    } catch (error) {
        console.error('Error fetching IP pricing:', error);
    }
}

// Populate IP options dynamically from backend data
function populateIpOptions() {
    // Populate IPv4 options
    const ipv4Select = document.getElementById('ipv4_prefix');
    if (ipv4Select && ipPricingData.ipv4) {
        ipv4Select.innerHTML = '<option value="">Select IPv4 prefix</option>';
        const ipv4Sizes = Object.keys(ipPricingData.ipv4).sort();
        
        ipv4Sizes.forEach((size) => {
            const pricing = ipPricingData.ipv4[size];
            const option = document.createElement('option');
            option.value = size;
            option.textContent = `${size} - ₹${pricing.price.toLocaleString('en-IN', {maximumFractionDigits: 2})}`;
            option.setAttribute('data-price', pricing.price);
            option.setAttribute('data-amount', pricing.amount || 0);
            option.setAttribute('data-gst-percentage', pricing.gst_percentage || 0);
            option.setAttribute('data-igst', pricing.igst || 0);
            option.setAttribute('data-cgst', pricing.cgst || 0);
            option.setAttribute('data-sgst', pricing.sgst || 0);
            ipv4Select.appendChild(option);
        });
    }

    // Populate IPv6 options
    const ipv6Select = document.getElementById('ipv6_prefix');
    if (ipv6Select && ipPricingData.ipv6) {
        ipv6Select.innerHTML = '<option value="">Select IPv6 prefix</option>';
        const ipv6Sizes = Object.keys(ipPricingData.ipv6).sort();
        
        ipv6Sizes.forEach((size) => {
            const pricing = ipPricingData.ipv6[size];
            const option = document.createElement('option');
            option.value = size;
            option.textContent = `${size} - ₹${pricing.price.toLocaleString('en-IN', {maximumFractionDigits: 2})}`;
            option.setAttribute('data-price', pricing.price);
            option.setAttribute('data-amount', pricing.amount || 0);
            option.setAttribute('data-gst-percentage', pricing.gst_percentage || 0);
            option.setAttribute('data-igst', pricing.igst || 0);
            option.setAttribute('data-cgst', pricing.cgst || 0);
            option.setAttribute('data-sgst', pricing.sgst || 0);
            ipv6Select.appendChild(option);
        });
    }

    // Helper: set select value; try exact, then with/without leading slash (e.g. /24 vs 24)
    function setSelectValue(select, value) {
        if (!select || !value) return;
        value = String(value).trim();
        select.value = value;
        if (select.value === value) return;
        const withSlash = value.startsWith('/') ? value : '/' + value;
        const withoutSlash = value.replace(/^\//, '');
        select.value = withSlash;
        if (select.value !== withSlash) select.value = withoutSlash;
    }

    // If coming back from preview or resubmission, re-apply select values after options are populated
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const fromPreview = urlParams.get('from_preview');
        if (fromPreview === '1') {
            const stored = sessionStorage.getItem('irin_form_data');
            if (stored) {
                const data = JSON.parse(stored);
                if (ipv4Select && data.ipv4_prefix) setSelectValue(ipv4Select, data.ipv4_prefix);
                if (ipv6Select && data.ipv6_prefix) setSelectValue(ipv6Select, data.ipv6_prefix);
                if (document.getElementById('asn_required') && data.asn_required) document.getElementById('asn_required').value = data.asn_required;
                updatePricing();
            }
        }
        if (window.irinnIsResubmission && window.irinnResubmissionPrefill) {
            const p = window.irinnResubmissionPrefill;
            if (ipv4Select && p.ipv4_prefix) setSelectValue(ipv4Select, p.ipv4_prefix);
            if (ipv6Select && p.ipv6_prefix) setSelectValue(ipv6Select, p.ipv6_prefix);
            if (typeof updatePricing === 'function') updatePricing();
        }
    } catch (e) {
        // ignore restore errors
    }
}

// Re-apply resubmission prefill to IP dropdowns after a short delay (in case options load async)
function applyResubmissionPrefillToSelects() {
    if (!window.irinnIsResubmission || !window.irinnResubmissionPrefill) return;
    const p = window.irinnResubmissionPrefill;
    const ipv4Select = document.getElementById('ipv4_prefix');
    const ipv6Select = document.getElementById('ipv6_prefix');
    function setVal(select, value) {
        if (!select || !value) return;
        value = String(value).trim();
        select.value = value;
        if (select.value !== value) {
            const alt = value.startsWith('/') ? value.replace(/^\//, '') : '/' + value;
            select.value = alt;
        }
    }
    if (ipv4Select && p.ipv4_prefix) setVal(ipv4Select, p.ipv4_prefix);
    if (ipv6Select && p.ipv6_prefix) setVal(ipv6Select, p.ipv6_prefix);
    if (typeof updatePricing === 'function') updatePricing();
}

// Update pricing display
function updatePricing() {
    const ipv4Select = document.getElementById('ipv4_prefix');
    const ipv6Select = document.getElementById('ipv6_prefix');
    const pricingDisplay = document.getElementById('pricingDisplay');
    
    const selectedPricings = [];
    
    // Get IPv4 pricing
    if (ipv4Select && ipv4Select.value) {
        const selectedOption = ipv4Select.options[ipv4Select.selectedIndex];
        const amount = parseFloat(selectedOption.getAttribute('data-amount')) || 0;
        const igst = parseFloat(selectedOption.getAttribute('data-igst')) || 0;
        const cgst = parseFloat(selectedOption.getAttribute('data-cgst')) || 0;
        const sgst = parseFloat(selectedOption.getAttribute('data-sgst')) || 0;
        const gstTotal = igst + cgst + sgst;
        const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
        if (amount > 0 || price > 0) {
            selectedPricings.push({ 
                amount: amount || (price - gstTotal), 
                gst: gstTotal,
                price: price 
            });
        }
    }
    
    // Get IPv6 pricing
    if (ipv6Select && ipv6Select.value) {
        const selectedOption = ipv6Select.options[ipv6Select.selectedIndex];
        const amount = parseFloat(selectedOption.getAttribute('data-amount')) || 0;
        const igst = parseFloat(selectedOption.getAttribute('data-igst')) || 0;
        const cgst = parseFloat(selectedOption.getAttribute('data-cgst')) || 0;
        const sgst = parseFloat(selectedOption.getAttribute('data-sgst')) || 0;
        const gstTotal = igst + cgst + sgst;
        const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
        if (amount > 0 || price > 0) {
            selectedPricings.push({ 
                amount: amount || (price - gstTotal), 
                gst: gstTotal,
                price: price 
            });
        }
    }
    
    if (selectedPricings.length > 0) {
        // Find the pricing with maximum total (base amount + GST)
        const maxPricing = selectedPricings.reduce((max, p) => {
            return (p.price > max.price) ? p : max;
        });
        
        const baseAmount = maxPricing.amount;
        const gstAmount = maxPricing.gst;
        const totalAmount = maxPricing.price;
        
        // Update display
        document.getElementById('max_ip_fee').textContent = '₹ ' + baseAmount.toLocaleString('en-IN', {maximumFractionDigits: 2});
        document.getElementById('gst_amount').textContent = '₹ ' + gstAmount.toLocaleString('en-IN', {maximumFractionDigits: 2});
        document.getElementById('total_fee').textContent = '₹ ' + totalAmount.toLocaleString('en-IN', {maximumFractionDigits: 2});
        
        // Show GST row if GST exists
        if (gstAmount > 0) {
            document.getElementById('gst_row').style.display = 'flex';
        } else {
            document.getElementById('gst_row').style.display = 'none';
        }
        
        pricingDisplay.style.display = 'block';
    } else {
        pricingDisplay.style.display = 'none';
    }
}

// Step navigation
function showStep(step) {
    visitedSteps.add(step);
    currentStep = step;

    document.querySelectorAll('.form-step').forEach(s => {
        s.classList.remove('active');
        s.style.display = 'none';
    });

    const stepElement = document.getElementById('step' + step);
    if (stepElement) {
        stepElement.classList.add('active');
        stepElement.style.display = 'block';
    }

    document.querySelectorAll('.step-item').forEach((item, index) => {
        const stepNum = index + 1;
        item.classList.remove('active', 'visited');
        if (stepNum === step) {
            item.classList.add('active', 'visited');
        } else if (visitedSteps.has(stepNum)) {
            item.classList.add('visited');
        }
    });

    const formContainer = document.querySelector('.irinn-form-container');
    if (formContainer) {
        formContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function clearIpStep2Validity() {
    const ipv4 = document.getElementById('ipv4_prefix');
    const ipv6 = document.getElementById('ipv6_prefix');
    if (ipv4) ipv4.setCustomValidity('');
    if (ipv6) ipv6.setCustomValidity('');
}

function nextStep() {
    // Validate only current step fields
    const currentStepElement = document.getElementById('step' + currentStep);
    if (currentStepElement) {
        let isValid = true;
        let firstInvalidField = null;

        // Step 2: at least one of IPv4 or IPv6 is required
        if (currentStep === 2) {
            const ipv4Select = document.getElementById('ipv4_prefix');
            const ipv6Select = document.getElementById('ipv6_prefix');
            const ipv4Val = ipv4Select ? ipv4Select.value : '';
            const ipv6Val = ipv6Select ? ipv6Select.value : '';
            if (!ipv4Val && !ipv6Val) {
                isValid = false;
                const msg = 'Please select at least one: IPv4 or IPv6 prefix.';
                if (ipv4Select) {
                    ipv4Select.setCustomValidity(msg);
                    firstInvalidField = ipv4Select;
                }
            } else {
                clearIpStep2Validity();
            }
        }

        // Get all required fields in current step
        const requiredFields = currentStepElement.querySelectorAll('input[required]:not([type="file"]):not([type="checkbox"]), select[required], textarea[required]');
        
        requiredFields.forEach(field => {
            // Skip hidden fields and file inputs (validate those on submit only)
            if (field.type === 'hidden' || field.type === 'file' || field.style.display === 'none' || field.closest('[style*="display: none"]')) {
                return;
            }
            
            // Special handling for radio buttons
            if (field.type === 'radio') {
                const radioGroup = currentStepElement.querySelectorAll(`input[name="${field.name}"]`);
                const isRadioValid = Array.from(radioGroup).some(radio => radio.checked);
                if (!isRadioValid) {
                    isValid = false;
                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                }
                return;
            }
            
            // Check if field is valid
            if (!field.checkValidity()) {
                isValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
            }
        });
        
        if (!isValid) {
            // Focus on first invalid field and show validation message
            if (firstInvalidField) {
                firstInvalidField.focus();
                firstInvalidField.reportValidity();
            }
            return;
        }
    }
    
    if (currentStep < totalSteps) {
        showStep(currentStep + 1);
    }
}

function previousStep() {
    if (currentStep > 1) {
        showStep(currentStep - 1);
    }
}

// Step dot click handler
document.addEventListener('DOMContentLoaded', function() {
    // Check if returning from preview
    const urlParams = new URLSearchParams(window.location.search);
    const fromPreview = urlParams.get('from_preview');
    
    // Restore form data from session if returning from preview
    if (fromPreview === '1') {
        // Restore form data from session (stored when preview was clicked)
        const formData = sessionStorage.getItem('irin_form_data');
        if (formData) {
            try {
                const data = JSON.parse(formData);
                // Restore all form fields
                Object.keys(data).forEach(key => {
                    const field = document.querySelector(`[name="${key}"]`);
                    if (field) {
                        if (field.type === 'checkbox' || field.type === 'radio') {
                            field.checked = field.value === data[key];
                        } else {
                            field.value = data[key];
                        }
                    }
                });
                // Go to last step (step 5); mark all steps as visited so user can go back to any
                for (let i = 1; i <= 5; i++) visitedSteps.add(i);
                currentStep = 5;
                showStep(5);
            } catch (e) {
                console.error('Error restoring form data:', e);
                // Fallback to step 1
                currentStep = 1;
                showStep(1);
            }
        } else {
            currentStep = 5;
            showStep(5);
        }
    } else {
        // Normal flow - start at step 1
        // Clear any previous form data from sessionStorage
        sessionStorage.removeItem('irin_form_data');
        
        // Reset form completely
        const form = document.getElementById('irinnApplicationForm');
        if (form) {
            form.reset();
            
            // Clear all file inputs
            const fileInputs = form.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                input.value = '';
            });
            
            // Clear all select dropdowns to first option
            const selects = form.querySelectorAll('select');
            selects.forEach(select => {
                if (select.options.length > 0) {
                    select.selectedIndex = 0;
                }
            });
            
            // Uncheck all checkboxes
            const checkboxes = form.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        }
        
        // Reset pricing display
        const pricingDisplay = document.getElementById('pricingDisplay');
        if (pricingDisplay) {
            pricingDisplay.style.display = 'none';
        }
        
        // Reset pricing values
        const maxIpFee = document.getElementById('max_ip_fee');
        const gstAmount = document.getElementById('gst_amount');
        const totalFee = document.getElementById('total_fee');
        if (maxIpFee) maxIpFee.textContent = '₹ 0';
        if (gstAmount) gstAmount.textContent = '₹ 0';
        if (totalFee) totalFee.textContent = '₹ 0';
        
        // Hide all steps and show only step 1
        document.querySelectorAll('.form-step').forEach(s => {
            s.classList.remove('active');
            s.style.display = 'none';
        });
        const firstStep = document.getElementById('step1');
        if (firstStep) {
            firstStep.classList.add('active');
            firstStep.style.display = 'block';
        }
        currentStep = 1;
        visitedSteps.clear();
        visitedSteps.add(1);

        // Reset step indicators
        document.querySelectorAll('.step-item').forEach((item, index) => {
            const stepNum = index + 1;
            item.classList.remove('active', 'visited');
            if (stepNum === 1) {
                item.classList.add('active', 'visited');
            }
        });
        
        // Re-fetch pricing to ensure fresh data
        fetchIpPricing();
    }
    
    // Step item click – allow navigation to any visited step
    document.querySelectorAll('.step-item').forEach(item => {
        item.addEventListener('click', function() {
            const step = parseInt(this.getAttribute('data-step'), 10);
            if (visitedSteps.has(step) || step === 1) {
                showStep(step);
            }
        });
    });
    
    // Fetch pricing on load
    fetchIpPricing();
    // Resubmission: re-apply dropdown prefill after options may have loaded (async)
    if (window.irinnIsResubmission) {
        setTimeout(applyResubmissionPrefillToSelects, 400);
        setTimeout(applyResubmissionPrefillToSelects, 1000);
    }
});

// Form submission
document.getElementById('irinnApplicationForm').addEventListener('submit', function(e) {
    // Resubmission: allow normal form POST (no AJAX, no payment)
    if (this.getAttribute('action').indexOf('resubmit') !== -1) {
        if (!confirm('Resubmit your application with the updated details? No payment will be charged.')) {
            e.preventDefault();
        }
        return;
    }

    e.preventDefault();
    
    if (!this.checkValidity()) {
        this.reportValidity();
        return;
    }

    if (!confirm('Are you sure you want to submit this application? Once submitted, it cannot be edited unless allowed by admin.')) {
        return;
    }

    const formData = new FormData(this);
    formData.append('action', 'submit');

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';

    // Check if wallet payment is available
    const useWallet = document.getElementById('useWalletPayment')?.checked || false;
    const paymentRoute = useWallet 
        ? '{{ route("user.applications.irin.initiate-payment-with-wallet") }}'
        : '{{ route("user.applications.irin.store-new") }}';

    fetch(paymentRoute, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
            if (data.success) {
                if (data.payment_url && data.payment_data) {
                    // Create and submit payment form to PayU
                    const paymentForm = document.createElement('form');
                    paymentForm.method = 'POST';
                    paymentForm.action = data.payment_url;
                    
                    Object.keys(data.payment_data).forEach(key => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = data.payment_data[key];
                        paymentForm.appendChild(input);
                    });
                    
                    // Clear form data before redirecting to payment
                    sessionStorage.removeItem('irin_form_data');
                    // Clear form data before redirecting to payment
                    sessionStorage.removeItem('irin_form_data');
                    document.body.appendChild(paymentForm);
                    paymentForm.submit();
                } else if (data.redirect_url) {
                    // Wallet payment successful, redirect
                    // Clear form data before redirecting
                    sessionStorage.removeItem('irin_form_data');
                    window.location.href = data.redirect_url;
                } else {
                    alert('Application submitted successfully!');
                    // Clear form data before redirecting
                    sessionStorage.removeItem('irin_form_data');
                    window.location.href = '{{ route("user.applications.index") }}';
                }
            } else {
            alert('Error submitting application: ' + (data.message || 'Unknown error'));
            submitBtn.disabled = false;
            submitBtn.textContent = 'Final Submit';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error submitting application');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Final Submit';
    });
});

// Preview functionality (only when not resubmission)
const previewBtnEl = document.getElementById('previewBtn');
if (previewBtnEl) {
previewBtnEl.addEventListener('click', function() {
    const form = document.getElementById('irinnApplicationForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'preview');
    
    // Show loading
    const previewBtn = document.getElementById('previewBtn');
    const originalText = previewBtn.textContent;
    previewBtn.disabled = true;
    previewBtn.textContent = 'Loading Preview...';
    
    fetch('{{ route("user.applications.irin.store-new") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON. Please try again.');
        }
        return response.json().then(data => {
            if (!response.ok) {
                throw new Error(data.message || (data.errors ? JSON.stringify(data.errors) : 'Server error'));
            }
            return data;
        });
    })
    .then(data => {
        previewBtn.disabled = false;
        previewBtn.textContent = originalText;
        
        if (data.success && data.redirect_url) {
            window.location.href = data.redirect_url;
        } else {
            const errorMsg = data.message || (data.errors ? JSON.stringify(data.errors) : 'Unknown error');
            alert('Error loading preview: ' + errorMsg);
            console.error('Preview error:', data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        previewBtn.disabled = false;
        previewBtn.textContent = originalText;
        alert('Error loading preview: ' + (error.message || 'Please check console for details'));
    });
});
}
</script>
@endpush
@endsection
