@extends('user.layout')

@section('title', 'New IRINN Application')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center mb-4 w-100">
        <div class="w-100">
            <h2 class="mb-1" style="color:#1f2937;">New IRINN Application</h2>
            <p class="text-muted mb-0">Fill in the required details to submit your new IRINN application.</p>
            @if($firstApplication)
                <div class="alert alert-info mt-2 mb-0">
                    <small><strong>Note:</strong> Documents and other details from your first application ({{ $firstApplication->application_id }}) will be automatically copied to this new application. You only need to fill in the new details below.</small>
                </div>
            @endif
        </div>
       
    </div>

    <div class="card border-c-blue shadow-sm">
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col">
                    <div class="step-indicator active" data-step="1">1. Application Details</div>
                </div>
                <div class="col">
                    <div class="step-indicator" data-step="2">2. Payment</div>
                </div>
            </div>

            <form id="newIxApplicationForm" method="POST" class="theme-forms" action="{{ route('user.applications.ix.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="application_id" id="applicationIdInput" value="">
                <input type="hidden" name="is_simplified" value="1">
                <input type="hidden" name="previous_gstin" id="previousGstin" value="{{ data_get($previousData, 'gstin') }}">
                <input type="hidden" name="kyc_gstin" id="kycGstin" value="{{ $kycGstin ?? '' }}">

                {{-- Step 1: Application Details --}}
                <div class="form-step" data-step="1">
                    {{-- Representative Person Details --}}
                    <div class="card mb-4 border-c-blue shadow-sm">
                        <div class="card-header theme-bg-blue">
                            <h5 class="mb-0">Representative Person Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Name as per PAN <span class="text-danger">*</span></label>
                                    <input type="text" name="representative_name" id="representativeName" class="form-control" 
                                        value="{{ old('representative_name') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">DOB as per PAN <span class="text-danger">*</span></label>
                                    <input type="date" name="representative_dob" id="representativeDob" class="form-control" 
                                        value="{{ old('representative_dob', data_get($firstApplicationData, 'representative.dob')) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">PAN Number <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" name="representative_pan" id="representativePan" class="form-control" 
                                            value="{{ old('representative_pan') }}" 
                                            placeholder="ABCDE1234F" maxlength="10" required>
                                        <button type="button" class="btn btn-primary" id="verifyPanBtn">Verify</button>
                                    </div>
                                    <div id="panVerifyStatus" class="mt-2"></div>
                                    <input type="hidden" name="pan_verified" id="panVerified" value="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="email" name="representative_email" id="representativeEmail" class="form-control" 
                                            value="{{ old('representative_email') }}" required>
                                        <button type="button" class="btn btn-primary" id="sendEmailOtpBtn">Send OTP</button>
                                    </div>
                                    <div id="emailOtpSection" class="mt-2 d-none">
                                        <input type="text" id="emailOtp" class="form-control mb-2" placeholder="Enter OTP" maxlength="6">
                                        <button type="button" class="btn btn-sm btn-success" id="verifyEmailOtpBtn">Verify OTP</button>
                                    </div>
                                    <div id="emailVerifyStatus" class="mt-2"></div>
                                    <input type="hidden" name="email_verified" id="emailVerified" value="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mobile <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" name="representative_mobile" id="representativeMobile" class="form-control" 
                                            value="{{ old('representative_mobile') }}" 
                                            placeholder="10 digit mobile number" maxlength="10" required>
                                        <button type="button" class="btn btn-primary" id="sendMobileOtpBtn">Send OTP</button>
                                    </div>
                                    <div id="mobileOtpSection" class="mt-2 d-none">
                                        <input type="text" id="mobileOtp" class="form-control mb-2" placeholder="Enter OTP" maxlength="6">
                                        <button type="button" class="btn btn-sm btn-success" id="verifyMobileOtpBtn">Verify OTP</button>
                                        <small class="form-text text-muted d-block mt-2" id="mobileOtpDisplay"></small>
                                    </div>
                                    <div id="mobileVerifyStatus" class="mt-2"></div>
                                    <input type="hidden" name="mobile_verified" id="mobileVerified" value="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- GSTIN for Billing --}}
                    <div class="card mb-4 border-c-blue shadow-sm">
                        <div class="card-header theme-bg-blue">
                            <h5 class="mb-0">GSTIN (For Billing)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">GST Number <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" name="gstin" id="gstin" class="form-control" 
                                            value="{{ old('gstin') }}" 
                                            placeholder="15 character GSTIN" maxlength="15" required>
                                        <button type="button" class="btn btn-primary" id="verifyGstinBtn">Verify</button>
                                    </div>
                                    <div id="gstinVerifyStatus" class="mt-2"></div>
                                    <input type="hidden" name="gstin_verified" id="gstinVerified" value="0">
                                    <input type="hidden" name="gstin_verification_id" id="gstinVerificationId" value="">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Documents (shown only if GST doesn't match KYC GST) --}}
                    <div class="card mb-4 border-c-blue shadow-sm" id="documentsSection" style="display: none;">
                        <div class="card-header theme-bg-blue">
                            <h5 class="mb-0">Documents</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">GST Document (PDF) <span class="text-danger">*</span></label>
                                    <input type="file" name="new_gst_document" id="newGstDocument" class="form-control" accept=".pdf">
                                    <small class="text-muted">Upload PDF file (max 10MB)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- NIXI Location --}}
                    <div class="card mb-4 border-c-blue shadow-sm">
                        <div class="card-header theme-bg-blue">
                            <h5 class="mb-0">NIXI Location</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Select Location <span class="text-danger">*</span></label>
                                    <select name="location_id" id="locationSelect" class="form-select" required>
                                        <option value="">Select Location</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}"
                                                data-node-type="{{ $location->node_type }}"
                                                data-state="{{ $location->state }}"
                                                {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                                {{ $location->name }} ({{ ucfirst($location->node_type) }} - {{ $location->state }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($gstState)
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

                    {{-- Port Capacity & Billing Plan --}}
                    <div class="card mb-4 border-c-blue shadow-sm">
                        <div class="card-header theme-bg-blue">
                            <h5 class="mb-0">Port Capacity & Billing Plan</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Port Capacity <span class="text-danger">*</span></label>
                                    <select name="port_capacity" id="portCapacitySelect" class="form-select" required>
                                        <option value="">Select capacity</option>
                                        @foreach($portPricings as $nodeType => $entries)
                                            <optgroup label="{{ ucfirst($nodeType) }} nodes">
                                                @foreach($entries as $pricing)
                                                    <option value="{{ $pricing->port_capacity }}" 
                                                        data-node-type="{{ $nodeType }}"
                                                        data-arc="{{ $pricing->price_arc }}"
                                                        data-mrc="{{ $pricing->price_mrc }}"
                                                        data-quarterly="{{ $pricing->price_quarterly }}"
                                                        {{ old('port_capacity') == $pricing->port_capacity ? 'selected' : '' }}>
                                                        {{ $pricing->port_capacity }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Billing Plan <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-3 radio-btn-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="billing_plan" id="planArc" value="arc" 
                                                {{ old('billing_plan') == 'arc' ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="planArc">Annual (ARC)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="billing_plan" id="planMrc" value="mrc"
                                                {{ old('billing_plan') == 'mrc' ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="planMrc">Monthly (MRC)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="billing_plan" id="planQuarterly" value="quarterly"
                                                {{ old('billing_plan') == 'quarterly' ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="planQuarterly">Quarterly</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- Payment Summary --}}
                            <div id="paymentSummary" class="mt-3" style="display: none;">
                                <div class="alert alert-info">
                                    <h6 class="mb-2">Payment Summary</h6>
                                    <p class="mb-1"><strong>Billing Amount:</strong> ₹<span id="billingAmountDisplay">0.00</span></p>
                                    <p class="mb-1"><strong>GST (<span id="gstPercentageDisplay">{{ $applicationPricing->gst_percentage ?? 18 }}</span>%):</strong> ₹<span id="gstAmountDisplay">0.00</span></p>
                                    <p class="mb-0"><strong>Total Amount:</strong> ₹<span id="totalAmountDisplay">0.00</span></p>
                                    <p class="mb-0 mt-2 small"><strong>Billing Frequency:</strong> <span id="billingFrequencyDisplay">—</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Number of Prefixes --}}
                    <div class="card mb-4 border-c-blue shadow-sm">
                        <div class="card-header theme-bg-blue">
                            <h5 class="mb-0">IP Prefix Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Number of IP Prefixes <span class="text-danger">*</span></label>
                                    <input type="number" name="ip_prefix_count" id="ipPrefixCount" class="form-control" 
                                        value="{{ old('ip_prefix_count') }}" min="1" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 2: Payment --}}
                <div class="form-step d-none" data-step="2">
                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="card border border-c-blue h-100">
                                <div class="card-header theme-bg-blue">
                                    <h5 class="mb-0">Application Summary</h5>
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4">Representative Name</dt>
                                        <dd class="col-sm-8" id="summaryRepresentativeName">—</dd>
                                        <dt class="col-sm-4">PAN</dt>
                                        <dd class="col-sm-8" id="summaryPan">—</dd>
                                        <dt class="col-sm-4">Email</dt>
                                        <dd class="col-sm-8" id="summaryEmail">—</dd>
                                        <dt class="col-sm-4">Mobile</dt>
                                        <dd class="col-sm-8" id="summaryMobile">—</dd>
                                        <dt class="col-sm-4">GSTIN</dt>
                                        <dd class="col-sm-8" id="summaryGstin">—</dd>
                                        <dt class="col-sm-4">NIXI Location</dt>
                                        <dd class="col-sm-8" id="summaryLocation">—</dd>
                                        <dt class="col-sm-4">Port Capacity</dt>
                                        <dd class="col-sm-8" id="summaryCapacity">—</dd>
                                        <dt class="col-sm-4">Billing Plan</dt>
                                        <dd class="col-sm-8" id="summaryPlan">—</dd>
                                        <dt class="col-sm-4">IP Prefixes</dt>
                                        <dd class="col-sm-8" id="summaryPrefixes">—</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="card border border-c-blue h-100">
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
                                    
                                    <p class="small text-blue fw-semi-bold fs-6">Declaration:</p>
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
                                        <p class="mb-1"><strong>Billing Amount:</strong> ₹<span id="summaryBillingAmount">0.00</span></p>
                                        <p class="mb-1"><strong>GST (<span id="summaryGstPercentage">{{ $applicationPricing->gst_percentage ?? 18 }}</span>%):</strong> ₹<span id="summaryGstAmount">0.00</span></p>
                                        <p class="mb-1"><strong>Total Amount:</strong> ₹<span id="summaryTotalAmount">0.00</span></p>
                                        <p class="mb-0 small mt-2" id="paymentMethodText">@if($canPayWithWallet)You can pay using your advance amount or PayU payment gateway.@else You will be redirected to PayU payment gateway to complete the payment.@endif</p>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100 mt-3" id="submitAndPayBtn">
                                        <i class="fas fa-credit-card"></i> Pay Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-primary" id="prevStepBtn" disabled>Previous</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" id="nextStepBtn">Next</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
.step-indicator {
    text-align: center;
    font-size: 16px;
    padding: 14px;
    background: #d4d5e1;
    border-radius: 8px;
    font-weight: 500;
    color: #6c757d;
}
.step-indicator.active {
    background: #0d6efd;
    color: white;
}
.form-step {
    display: block;
}
.form-step.d-none {
    display: none !important;
}
.theme-forms .radio-btn-group .form-check {
    display: flex;
}
.theme-forms .radio-btn-group .form-check-label {
    position: relative;
    top: 4px;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '{{ csrf_token() }}';
    let panRequestId = null;
    let gstinVerificationId = null;
    const previousGstin = document.getElementById('previousGstin')?.value?.toUpperCase() || '';
    const kycGstin = document.getElementById('kycGstin')?.value?.toUpperCase() || '';
    const gstinInput = document.getElementById('gstin');
    const documentsSection = document.getElementById('documentsSection');
    const newGstDocument = document.getElementById('newGstDocument');
    let currentStep = 1;
    const steps = document.querySelectorAll('.form-step');
    const indicators = document.querySelectorAll('.step-indicator');
    const prevBtn = document.getElementById('prevStepBtn');
    const nextBtn = document.getElementById('nextStepBtn');
    const form = document.getElementById('newIxApplicationForm');
    const gstState = '{{ $gstState ?? '' }}';
    const locationSelect = document.getElementById('locationSelect');

    // Auto-format PAN input (uppercase, remove spaces)
    const panInput = document.getElementById('representativePan');
    if (panInput) {
        panInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/\s+/g, '').replace(/[^A-Z0-9]/g, '');
        });
    }

    // Auto-format GSTIN input (uppercase, remove spaces)
    if (gstinInput) {
        gstinInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/\s+/g, '').replace(/[^A-Z0-9]/g, '');
        });
    }

    // Step navigation
    function showStep(stepNumber) {
        currentStep = stepNumber;
        steps.forEach(step => {
            if (Number(step.dataset.step) === currentStep) {
                step.classList.remove('d-none');
            } else {
                step.classList.add('d-none');
            }
        });
        indicators.forEach(indicator => {
            if (Number(indicator.dataset.step) === currentStep) {
                indicator.classList.add('active');
            } else {
                indicator.classList.remove('active');
            }
        });
        prevBtn.disabled = currentStep === 1;
        nextBtn.style.display = currentStep >= steps.length ? 'none' : 'block';
        
        if (currentStep === 2) {
            updateSummary();
            updatePaymentSummary(); // Update payment amounts on payment step
        }
    }

    prevBtn.addEventListener('click', () => {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });

    nextBtn.addEventListener('click', () => {
        if (validateStep1()) {
            showStep(2);
        }
    });

    function validateStep1() {
        if (document.getElementById('panVerified').value !== '1') {
            alert('Please verify PAN before proceeding.');
            return false;
        }
        if (document.getElementById('emailVerified').value !== '1') {
            alert('Please verify email before proceeding.');
            return false;
        }
        if (document.getElementById('mobileVerified').value !== '1') {
            alert('Please verify mobile number before proceeding.');
            return false;
        }
        if (document.getElementById('gstinVerified').value !== '1') {
            alert('Please verify GSTIN before proceeding.');
            return false;
        }
        if (documentsSection.style.display !== 'none' && !newGstDocument.files.length) {
            alert('Please upload GST document.');
            return false;
        }
        return true;
    }

    function updateSummary() {
        document.getElementById('summaryRepresentativeName').textContent = document.getElementById('representativeName').value || '—';
        document.getElementById('summaryPan').textContent = document.getElementById('representativePan').value || '—';
        document.getElementById('summaryEmail').textContent = document.getElementById('representativeEmail').value || '—';
        document.getElementById('summaryMobile').textContent = document.getElementById('representativeMobile').value || '—';
        document.getElementById('summaryGstin').textContent = document.getElementById('gstin').value || '—';
        
        const locationSelect = document.getElementById('locationSelect');
        const selectedLocation = locationSelect.options[locationSelect.selectedIndex];
        document.getElementById('summaryLocation').textContent = selectedLocation.text || '—';
        
        document.getElementById('summaryCapacity').textContent = document.getElementById('portCapacitySelect').value || '—';
        
        const billingPlan = document.querySelector('input[name="billing_plan"]:checked');
        document.getElementById('summaryPlan').textContent = billingPlan ? billingPlan.nextElementSibling.textContent : '—';
        
        document.getElementById('summaryPrefixes').textContent = document.getElementById('ipPrefixCount').value || '—';
    }

    // Location filtering by GST state
    if (gstState) {
        const filterCheckbox = document.getElementById('filterByGstState');
        
        function filterLocations() {
            const showFiltered = filterCheckbox.checked;
            Array.from(locationSelect.options).forEach(option => {
                if (option.value === '') return;
                const optionState = option.dataset.state;
                if (showFiltered && optionState !== gstState) {
                    option.style.display = 'none';
                } else {
                    option.style.display = 'block';
                }
            });
        }
        
        filterCheckbox.addEventListener('change', filterLocations);
        filterLocations();
    }

    // Payment summary calculation
    const portCapacitySelect = document.getElementById('portCapacitySelect');
    const billingPlanRadios = document.querySelectorAll('input[name="billing_plan"]');
    const paymentSummary = document.getElementById('paymentSummary');
    const applicationFee = {{ $applicationPricing->application_fee ?? 1000 }};
    const gstPercentage = {{ $applicationPricing->gst_percentage ?? 18 }};

    function filterPortCapacitiesByNodeType() {
        if (!locationSelect || !portCapacitySelect) {
            return;
        }

        const selectedLocation = locationSelect.options[locationSelect.selectedIndex];

        if (!selectedLocation || !selectedLocation.value) {
            // No location selected: reset and hide capacities
            portCapacitySelect.value = '';
            Array.from(portCapacitySelect.options).forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
            paymentSummary.style.display = 'none';
            return;
        }

        const nodeType = selectedLocation.dataset.nodeType;

        Array.from(portCapacitySelect.options).forEach(option => {
            if (option.value === '') {
                option.style.display = 'block';
                return;
            }
            const optionNodeType = option.dataset.nodeType;
            const shouldShow = optionNodeType === nodeType;
            option.style.display = shouldShow ? 'block' : 'none';

            if (!shouldShow && option.selected) {
                option.selected = false;
            }
        });

        // After filtering, ensure summary reflects any change
        updatePaymentSummary();
    }

    function updatePaymentSummary() {
        const selectedOption = portCapacitySelect.options[portCapacitySelect.selectedIndex];
        const billingPlan = document.querySelector('input[name="billing_plan"]:checked')?.value;

        if (!selectedOption.value || !billingPlan) {
            paymentSummary.style.display = 'none';
            return;
        }

        // Port/Location fee from pricing table (for step 1 display only)
        let portFee = 0;
        if (billingPlan === 'arc') {
            portFee = parseFloat(selectedOption.dataset.arc || 0);
        } else if (billingPlan === 'mrc') {
            portFee = parseFloat(selectedOption.dataset.mrc || 0);
        } else if (billingPlan === 'quarterly') {
            portFee = parseFloat(selectedOption.dataset.quarterly || 0);
        }

        // For step 1, show estimated billing based on port fee.
        // For step 2 (payment), show application fee only, same as full form step 3.
        const isPaymentStep = currentStep === 2;
        const baseAmount = isPaymentStep ? applicationFee : portFee;

        const billingAmount = baseAmount;
        const gstAmount = (billingAmount * gstPercentage) / 100;
        const totalAmount = billingAmount + gstAmount;

        document.getElementById('billingAmountDisplay').textContent = billingAmount.toFixed(2);
        document.getElementById('gstAmountDisplay').textContent = gstAmount.toFixed(2);
        document.getElementById('totalAmountDisplay').textContent = totalAmount.toFixed(2);
        
        const frequencyMap = {
            'arc': 'Annually',
            'mrc': 'Monthly',
            'quarterly': 'Quarterly'
        };
        document.getElementById('billingFrequencyDisplay').textContent = frequencyMap[billingPlan] || '—';
        
        paymentSummary.style.display = 'block';
        
        // Update summary on payment step
        if (isPaymentStep) {
            document.getElementById('summaryBillingAmount').textContent = billingAmount.toFixed(2);
            document.getElementById('summaryGstAmount').textContent = gstAmount.toFixed(2);
            document.getElementById('summaryTotalAmount').textContent = totalAmount.toFixed(2);
        }
    }

    if (locationSelect) {
        locationSelect.addEventListener('change', filterPortCapacitiesByNodeType);
        // Run once on load to respect any pre-selected location
        filterPortCapacitiesByNodeType();
    }

    portCapacitySelect.addEventListener('change', updatePaymentSummary);
    billingPlanRadios.forEach(radio => {
        radio.addEventListener('change', updatePaymentSummary);
    });

    // GST document requirement
    function toggleGstDocRequirement() {
        if (!gstinInput || !documentsSection) {
            return;
        }
        const currentGstin = gstinInput.value.trim().toUpperCase();
        const needsDoc = kycGstin && currentGstin && currentGstin !== kycGstin;
        
        if (needsDoc) {
            documentsSection.style.display = 'block';
            newGstDocument.setAttribute('required', 'required');
        } else {
            documentsSection.style.display = 'none';
            newGstDocument.removeAttribute('required');
            newGstDocument.value = '';
        }
    }
    gstinInput?.addEventListener('input', toggleGstDocRequirement);

    // PAN Verification
    document.getElementById('verifyPanBtn').addEventListener('click', function() {
        const panInput = document.getElementById('representativePan');
        const pan = panInput.value.trim().toUpperCase().replace(/\s+/g, '');
        panInput.value = pan; // Update input with normalized value
        const name = document.getElementById('representativeName').value.trim();
        const dob = document.getElementById('representativeDob').value;

        if (!pan || !name || !dob) {
            alert('Please fill Name, DOB and PAN before verification.');
            return;
        }

        const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
        if (!panRegex.test(pan)) {
            alert('Please enter a valid PAN (e.g. ABCDE1234F).');
            return;
        }

        this.disabled = true;
        this.textContent = 'Verifying...';
        const statusEl = document.getElementById('panVerifyStatus');
        statusEl.innerHTML = '<span class="badge bg-warning">Verifying...</span>';
        const btn = this;

        fetch('{{ route("user.applications.ix.verify-representative-pan") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                pan: pan,
                name: name,
                dob: dob
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                panRequestId = data.request_id;
                checkPanStatus();
            } else {
                statusEl.innerHTML = '<span class="badge bg-danger">' + data.message + '</span>';
                btn.disabled = false;
                btn.textContent = 'Verify';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            statusEl.innerHTML = '<span class="badge bg-danger">Error verifying PAN</span>';
            btn.disabled = false;
            btn.textContent = 'Verify';
        });
    });

    function checkPanStatus() {
        if (!panRequestId) return;

        fetch('{{ route("user.applications.ix.check-representative-pan-status") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                request_id: panRequestId
            })
        })
        .then(response => response.json())
        .then(data => {
            const statusEl = document.getElementById('panVerifyStatus');
            const btn = document.getElementById('verifyPanBtn');

            if (data.success) {
                statusEl.innerHTML = '<span class="badge bg-success">' + data.message + '</span>';
                document.getElementById('panVerified').value = '1';

                // Lock PAN-related fields once verified
                const nameInput = document.getElementById('representativeName');
                const dobInput = document.getElementById('representativeDob');
                const panInput = document.getElementById('representativePan');
                if (nameInput) {
                    nameInput.readOnly = true;
                }
                if (dobInput) {
                    dobInput.readOnly = true;
                }
                if (panInput) {
                    panInput.readOnly = true;
                }

                btn.disabled = true;
                btn.textContent = 'Verified';
            } else if (data.status === 'in_progress' || data.status === 'pending') {
                setTimeout(checkPanStatus, 2000);
            } else {
                statusEl.innerHTML = '<span class="badge bg-danger">' + data.message + '</span>';
                btn.disabled = false;
                btn.textContent = 'Verify';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            setTimeout(checkPanStatus, 2000);
        });
    }

    // Email OTP (reuse registration OTP flow)
    document.getElementById('sendEmailOtpBtn').addEventListener('click', function() {
        const email = document.getElementById('representativeEmail').value.trim();
        if (!email) {
            alert('Please enter a valid email address.');
            return;
        }

        // Check if email is already verified - allow resending
        const isVerified = document.getElementById('emailVerified').value === '1';
        
        this.disabled = true;
        this.textContent = 'Sending...';

        fetch('{{ route("register.send.email.otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ email: email })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('emailOtpSection').classList.remove('d-none');
                // Reset verification status when resending
                if (isVerified) {
                    document.getElementById('emailVerified').value = '0';
                    document.getElementById('emailVerifyStatus').innerHTML = '';
                }
                this.textContent = 'OTP Sent';
                this.disabled = false;
            } else {
                alert(data.message);
                this.disabled = false;
                this.textContent = isVerified ? 'Resend OTP' : 'Send OTP';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending OTP');
            this.disabled = false;
            this.textContent = isVerified ? 'Resend OTP' : 'Send OTP';
        });
    });

    document.getElementById('verifyEmailOtpBtn').addEventListener('click', function() {
        const email = document.getElementById('representativeEmail').value.trim();
        const otp = document.getElementById('emailOtp').value.trim();

        if (!otp || otp.length !== 6) {
            alert('Please enter a valid 6-digit OTP.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Verifying...';

        fetch('{{ route("register.verify.email.otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ email: email, otp: otp, master_otp: otp })
        })
        .then(response => response.json())
        .then(data => {
            const statusEl = document.getElementById('emailVerifyStatus');
            if (data.success) {
                statusEl.innerHTML = '<span class="badge bg-success">' + data.message + '</span>';
                document.getElementById('emailVerified').value = '1';
                document.getElementById('emailOtpSection').classList.add('d-none');

                // Lock email once verified
                const emailInput = document.getElementById('representativeEmail');
                const sendEmailBtn = document.getElementById('sendEmailOtpBtn');
                if (emailInput) {
                    emailInput.readOnly = true;
                }
                if (sendEmailBtn) {
                    sendEmailBtn.disabled = true;
                    sendEmailBtn.textContent = 'Verified';
                }
            } else {
                statusEl.innerHTML = '<span class="badge bg-danger">' + data.message + '</span>';
                this.disabled = false;
                this.textContent = 'Verify OTP';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error verifying OTP');
            this.disabled = false;
            this.textContent = 'Verify OTP';
        });
    });

    // Mobile OTP (show OTP on page, reuse registration OTP flow)
    document.getElementById('sendMobileOtpBtn').addEventListener('click', function() {
        const mobile = document.getElementById('representativeMobile').value.trim();
        if (!mobile || mobile.length !== 10) {
            alert('Please enter a valid 10-digit mobile number.');
            return;
        }

        // Check if mobile is already verified - allow resending
        const isVerified = document.getElementById('mobileVerified').value === '1';

        this.disabled = true;
        this.textContent = 'Sending...';

        fetch('{{ route("register.send.mobile.otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ mobile: mobile })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('mobileOtpSection').classList.remove('d-none');
                if (data.otp) {
                    document.getElementById('mobileOtpDisplay').textContent = 'OTP: ' + data.otp;
                }
                // Reset verification status when resending
                if (isVerified) {
                    document.getElementById('mobileVerified').value = '0';
                    document.getElementById('mobileVerifyStatus').innerHTML = '';
                }
                this.textContent = 'OTP Sent';
                this.disabled = false;
            } else {
                alert(data.message);
                this.disabled = false;
                this.textContent = isVerified ? 'Resend OTP' : 'Send OTP';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending OTP');
            this.disabled = false;
            this.textContent = isVerified ? 'Resend OTP' : 'Send OTP';
        });
    });

    document.getElementById('verifyMobileOtpBtn').addEventListener('click', function() {
        const mobile = document.getElementById('representativeMobile').value.trim();
        const otp = document.getElementById('mobileOtp').value.trim();

        if (!otp || otp.length !== 6) {
            alert('Please enter a valid 6-digit OTP.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Verifying...';

        fetch('{{ route("register.verify.mobile.otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ mobile: mobile, otp: otp, master_otp: otp })
        })
        .then(response => response.json())
        .then(data => {
            const statusEl = document.getElementById('mobileVerifyStatus');
            if (data.success) {
                statusEl.innerHTML = '<span class="badge bg-success">' + data.message + '</span>';
                document.getElementById('mobileVerified').value = '1';
                document.getElementById('mobileOtpSection').classList.add('d-none');

                // Lock mobile once verified
                const mobileInput = document.getElementById('representativeMobile');
                const sendMobileBtn = document.getElementById('sendMobileOtpBtn');
                if (mobileInput) {
                    mobileInput.readOnly = true;
                }
                if (sendMobileBtn) {
                    sendMobileBtn.disabled = true;
                    sendMobileBtn.textContent = 'Verified';
                }
            } else {
                statusEl.innerHTML = '<span class="badge bg-danger">' + data.message + '</span>';
                this.disabled = false;
                this.textContent = 'Verify OTP';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error verifying OTP');
            this.disabled = false;
            this.textContent = 'Verify OTP';
        });
    });

    // GSTIN Verification
    document.getElementById('verifyGstinBtn').addEventListener('click', function() {
        const gstinInput = document.getElementById('gstin');
        const gstin = gstinInput.value.trim().toUpperCase().replace(/\s+/g, '');
        gstinInput.value = gstin; // Update input with normalized value

        if (!gstin || gstin.length !== 15) {
            alert('Please enter a valid 15-character GSTIN.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Verifying...';
        const statusEl = document.getElementById('gstinVerifyStatus');
        statusEl.innerHTML = '<span class="badge bg-warning">Verifying...</span>';
        const btn = this;

        fetch('{{ route("user.applications.ix.verify-gstin") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ gstin: gstin })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                gstinVerificationId = data.verification_id;
                document.getElementById('gstinVerificationId').value = data.verification_id;
                checkGstinStatus();
            } else {
                statusEl.innerHTML = '<span class="badge bg-danger">' + data.message + '</span>';
                btn.disabled = false;
                btn.textContent = 'Verify';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            statusEl.innerHTML = '<span class="badge bg-danger">Error verifying GSTIN</span>';
            btn.disabled = false;
            btn.textContent = 'Verify';
        });
    });

    function checkGstinStatus() {
        if (!gstinVerificationId) return;

        fetch('{{ route("user.applications.ix.check-gstin-status") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                verification_id: gstinVerificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            const statusEl = document.getElementById('gstinVerifyStatus');
            const btn = document.getElementById('verifyGstinBtn');

            if (data.success && data.is_verified) {
                statusEl.innerHTML = '<span class="badge bg-success">' + data.message + '</span>';
                document.getElementById('gstinVerified').value = '1';

                // Lock GSTIN once verified
                if (gstinInput) {
                    gstinInput.readOnly = true;
                }
                btn.disabled = true;
                btn.textContent = 'Verified';
                
                // Check if GST matches KYC GST
                const currentGstin = document.getElementById('gstin').value.trim().toUpperCase();
                if (kycGstin && currentGstin !== kycGstin) {
                    documentsSection.style.display = 'block';
                    newGstDocument.setAttribute('required', 'required');
                } else {
                    documentsSection.style.display = 'none';
                    newGstDocument.removeAttribute('required');
                }
            } else if (data.status === 'in_progress' || data.status === 'pending') {
                setTimeout(checkGstinStatus, 2000);
            } else {
                statusEl.innerHTML = '<span class="badge bg-danger">' + data.message + '</span>';
                btn.disabled = false;
                btn.textContent = 'Verify';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            setTimeout(checkGstinStatus, 2000);
        });
    }

    // Form submission
    form.addEventListener('submit', async function(e) {
        if (currentStep === 1) {
            e.preventDefault();
            if (validateStep1()) {
                showStep(2);
            }
            return false;
        }
        
        // Step 2 submission - proceed to payment
        e.preventDefault();
        
        if (!validateStep1()) {
            alert('Please complete all required fields and verifications.');
            return false;
        }
        
        // Check declaration checkbox
        const declarationCheckbox = document.getElementById('declarationConfirmed');
        if (!declarationCheckbox || !declarationCheckbox.checked) {
            alert('Please accept the declaration before proceeding to payment.');
            return false;
        }
        
        if (!confirm('Are you sure you want to submit this application and proceed to payment? You will not be able to edit it after submission.')) {
            return false;
        }

        // Normalize PAN before submission (uppercase, trim, remove spaces)
        const panInput = document.getElementById('representativePan');
        if (panInput) {
            panInput.value = panInput.value.trim().toUpperCase().replace(/\s+/g, '');
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || csrfToken
                    }
                });
            } else {
                // Use PayU payment endpoint
                formData.append('initiate_payment', '1');
                response = await fetch('{{ route("user.applications.ix.initiate-payment") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || csrfToken
                    }
                });
            }

            // Check response status
            const contentType = response.headers.get('content-type') || '';
            let data;
            
            if (contentType.includes('application/json')) {
                data = await response.json();
            } else {
                // Server returned HTML (validation error page) - try to parse as JSON anyway
                try {
                    const text = await response.text();
                    data = JSON.parse(text);
                } catch (e) {
                    // If parsing fails, show generic error
                    alert('Error submitting application: Validation error. Please check all fields and ensure PAN format is correct (ABCDE1234F).');
                    console.error('Response was not JSON:', await response.text());
                    return;
                }
            }
            
            if (isWalletPayment) {
                // Wallet payment - redirect to applications page
                if (data.success) {
                    alert(data.message || 'Application submitted successfully! Payment completed using wallet.');
                    window.location.href = data.redirect_url || '{{ route("user.applications.index") }}';
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            } else if (data.success && data.payment_form) {
                // PayU payment - redirect to PayU
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
                // Show validation errors if available
                let errorMessage = data.message || 'Unknown error';
                if (data.errors) {
                    const errorList = Object.values(data.errors).flat().join(', ');
                    errorMessage = errorList || errorMessage;
                }
                alert('Error submitting application: ' + errorMessage);
            }
        } catch (error) {
            alert('Error submitting application. Please try again.');
            console.error(error);
        }
    });
});
</script>
@endpush
@endsection
