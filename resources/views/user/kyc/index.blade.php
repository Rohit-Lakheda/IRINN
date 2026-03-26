@extends('user.layout')

@section('title', 'KYC Verification')

@push('styles')
<style>
    .kyc-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1050;
    }
    .kyc-modal {
        background: #ffffff;
        border-radius: 12px;
        max-width: 900px;
        width: 100%;
        max-height: 100vh;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    .kyc-modal-header {
        padding: 16px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .kyc-steps {
        display: flex;
        gap: 8px;
    }
    .kyc-step {
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        border: 1px solid #d1d5db;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .kyc-step-active {
        background-color: #10b981;
        color: #ffffff;
        border-color: #10b981;
    }
    .kyc-step-completed {
        background-color: #ecfdf5;
        color: #065f46;
        border-color: #6ee7b7;
    }
    .kyc-modal-body {
        padding: 16px 24px 20px 24px;
        overflow-y: auto;
        max-height: calc(90vh - 130px);
    }
    .kyc-modal-footer {
        padding: 12px 24px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #f9fafb;
    }
    .kyc-readonly {
        background-color: #ecfdf5 !important;
        border-color: #10b981 !important;
        cursor: not-allowed !important;
    }
    .kyc-badge-verified {
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 999px;
        background-color: #ecfdf5;
        color: #16a34a;
        border: 1px solid #6ee7b7;
    }
    .kyc-badge-pending {
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 999px;
        background-color: #fefce8;
        color: #a16207;
        border: 1px solid #facc15;
    }
    .kyc-badge-success {
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 999px;
        background-color: #ecfdf5;
        color: #16a34a;
        border: 1px solid #6ee7b7;
    }
    .kyc-badge-error {
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 999px;
        background-color: #fef2f2;
        color: #dc2626;
        border: 1px solid #fca5a5;
    }
    .theme-forms .form-check {
        display: flex;
        align-items: center;
    }
</style>
@endpush

@section('content')
<div class="kyc-modal-backdrop">
    <div class="kyc-modal">
        <div class="kyc-modal-header flex-wrap">
            <div>
                <h5 class="mb-1">Complete Your KYC</h5>
                <small class="d-block text-muted mb-2">Please complete KYC in 2 quick steps to continue using the portal.</small>
            </div>
            <div class="kyc-steps">
                <div id="kycStepIndicator1" class="kyc-step kyc-step-active">
                    <span class="badge bg-light text-dark border">1</span>
                    <span>Organisation Details</span>
                </div>
                <div id="kycStepIndicator2" class="kyc-step">
                    <span class="badge bg-light text-dark border">2</span>
                    <span>Authorised Representative</span>
                </div>
            </div>
        </div>

        <form id="kycForm" class="theme-forms">
            @csrf
            <input type="hidden" id="gst_verification_id" name="gst_verification_id" value="{{ $kyc->gst_verification_id }}">
            <input type="hidden" id="gst_verified" name="gst_verified" value="{{ $kyc->gst_verified ? '1' : '0' }}">
            <input type="hidden" id="mca_verification_id" name="mca_verification_id" value="{{ $kyc->mca_verification_id }}">
            <input type="hidden" id="mca_verified" name="mca_verified" value="{{ $kyc->mca_verified ? '1' : '0' }}">
            <input type="hidden" id="udyam_verification_id" name="udyam_verification_id" value="{{ $kyc->udyam_verification_id }}">
            <input type="hidden" id="udyam_verified" name="udyam_verified" value="{{ $kyc->udyam_verified ? '1' : '0' }}">

            <input type="hidden" id="contact_name_pan_dob_verified" name="contact_name_pan_dob_verified" value="{{ $kyc->contact_name_pan_dob_verified ? '1' : '0' }}">
            <input type="hidden" id="contact_email_verified" name="contact_email_verified" value="{{ $kyc->contact_email_verified ? '1' : '0' }}">
            <input type="hidden" id="contact_mobile_verified" name="contact_mobile_verified" value="{{ $kyc->contact_mobile_verified ? '1' : '0' }}">

            <div class="kyc-modal-body">
                {{-- Step 1: Organisation Details --}}
                <div id="kycStep1">
                    <h6 class="mb-3">Step 1: Organisation & Affiliate Details</h6>

                    {{-- Organisation Type --}}
                    <div class="mb-3">
                        <label class="form-label">Organisation Type <span class="text-danger">*</span></label>
                        <select
                            class="form-select"
                            id="organisation_type"
                            name="organisation_type"
                            required
                        >
                            <option value="">Select organisation type</option>
                            <option value="government" {{ $kyc->organisation_type === 'government' ? 'selected' : '' }}>Government</option>
                            <option value="academia_institute" {{ $kyc->organisation_type === 'academia_institute' ? 'selected' : '' }}>Academia or Institute</option>
                            <option value="banking_financial" {{ $kyc->organisation_type === 'banking_financial' ? 'selected' : '' }}>Banking and Financial Services</option>
                            <option value="isp_a" {{ $kyc->organisation_type === 'isp_a' ? 'selected' : '' }}>ISP – A</option>
                            <option value="isp_b" {{ $kyc->organisation_type === 'isp_b' ? 'selected' : '' }}>ISP – B</option>
                            <option value="isp_c" {{ $kyc->organisation_type === 'isp_c' ? 'selected' : '' }}>ISP – C</option>
                            <option value="vno_a" {{ $kyc->organisation_type === 'vno_a' ? 'selected' : '' }}>VNO – A</option>
                            <option value="vno_b" {{ $kyc->organisation_type === 'vno_b' ? 'selected' : '' }}>VNO – B</option>
                            <option value="vno_c" {{ $kyc->organisation_type === 'vno_c' ? 'selected' : '' }}>VNO – C</option>
                            <option value="it_software" {{ $kyc->organisation_type === 'it_software' ? 'selected' : '' }}>IT or Software</option>
                            <option value="hosting_dc" {{ $kyc->organisation_type === 'hosting_dc' ? 'selected' : '' }}>Hosting and Data Centre</option>
                            <option value="enterprise_manufacturing" {{ $kyc->organisation_type === 'enterprise_manufacturing' ? 'selected' : '' }}>Enterprises or Manufacturing</option>
                            <option value="media_entertainment" {{ $kyc->organisation_type === 'media_entertainment' ? 'selected' : '' }}>Media or Entertainment</option>
                            <option value="others" {{ $kyc->organisation_type === 'others' ? 'selected' : '' }}>Others</option>
                        </select>
                    </div>

                    {{-- Organisation Type - Others Text --}}
                    <div class="mb-3" id="organisationTypeOtherWrapper" style="display: none;">
                        <label class="form-label">Please specify Organisation Type (Others) <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control"
                            id="organisation_type_other"
                            name="organisation_type_other"
                            value="{{ $kyc->organisation_type_other }}"
                            maxlength="255"
                        >
                    </div>

                    {{-- ISP/VNO License Upload --}}
                    <div class="mb-3" id="organisationLicenseWrapper" style="display: none;">
                        <label class="form-label">Upload License (for ISP/VNO) <span class="text-danger">*</span></label>
                        <input
                            type="file"
                            class="form-control"
                            id="organisation_license_file"
                            name="organisation_license_file"
                            accept="application/pdf,image/*"
                        >
                        @if ($kyc->organisation_license_path)
                            <small class="form-text text-muted">
                                Existing file: {{ basename($kyc->organisation_license_path) }}
                            </small>
                        @endif
                    </div>

                    {{-- Affiliate Type --}}
                    <div class="mb-3">
                        <label class="form-label">Affiliate Type <span class="text-danger">*</span></label>
                        <select
                            class="form-select"
                            id="affiliate_type"
                            name="affiliate_type"
                            required
                        >
                            <option value="">Select affiliate type</option>
                            <option value="private_limited" {{ $kyc->affiliate_type === 'private_limited' ? 'selected' : '' }}>Private Limited Company</option>
                            <option value="limited_company" {{ $kyc->affiliate_type === 'limited_company' ? 'selected' : '' }}>Limited Company</option>
                            <option value="llp" {{ $kyc->affiliate_type === 'llp' ? 'selected' : '' }}>Limited Liability Partnership</option>
                            <option value="partnership" {{ $kyc->affiliate_type === 'partnership' ? 'selected' : '' }}>Partnership having partnership deed and not having certificate of Incorporation</option>
                            <option value="opc" {{ $kyc->affiliate_type === 'opc' ? 'selected' : '' }}>One Person Company (OPC)</option>
                            <option value="gov_incorporation" {{ $kyc->affiliate_type === 'gov_incorporation' ? 'selected' : '' }}>Gazette Notification or Incorporation certificate by government</option>
                            <option value="school_college" {{ $kyc->affiliate_type === 'school_college' ? 'selected' : '' }}>School and College</option>
                            <option value="sole_proprietorship" {{ $kyc->affiliate_type === 'sole_proprietorship' ? 'selected' : '' }}>Sole Proprietorship</option>
                        </select>
                        <input type="hidden" id="affiliate_verification_mode" name="affiliate_verification_mode" value="{{ $kyc->affiliate_verification_mode }}">
                    </div>

                    {{-- Affiliate Document Upload (for document-based types) --}}
                    <div class="mb-3" id="affiliateDocumentWrapper" style="display: none;">
                        <label class="form-label">Upload Affiliate Document <span class="text-danger">*</span></label>
                        <input
                            type="file"
                            class="form-control"
                            id="affiliate_document_file"
                            name="affiliate_document_file"
                            accept="application/pdf,image/*"
                        >
                        @if ($kyc->affiliate_document_path)
                            <small class="form-text text-muted">
                                Existing file: {{ basename($kyc->affiliate_document_path) }}
                            </small>
                        @endif
                        <small class="form-text text-muted">
                            Upload Partnership Deed / Gazette Notification / School or College Registration document as applicable.
                        </small>
                    </div>

                    <div class="alert alert-info py-2" id="verificationInfoAlert">
                        <small>
                            <span id="additionalVerificationText"></span>
                        </small>
                    </div>

                    {{-- CIN Field (shown for CIN-based affiliate types) --}}
                    <div class="mb-3" id="cinField" style="display: none;">
                        <label class="form-label">CIN (MCA) Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text"
                                   class="form-control"
                                   id="cin"
                                   name="cin"
                                   value="{{ $kyc->cin }}">
                            <button class="btn btn-primary" type="button" id="verifyMcaBtn">Verify CIN</button>
                        </div>
                        <small id="mcaStatus" class="form-text text-muted">
                            Provide CIN if the organisation is registered with MCA.
                        </small>
                    </div>

                    {{-- UDYAM Field (shown for Sole Proprietorship) --}}
                    <div class="mb-3" id="udyamField" style="display: none;">
                        <label class="form-label">UDYAM Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text"
                                   class="form-control"
                                   id="udyam_number"
                                   name="udyam_number"
                                   value="{{ $kyc->udyam_number }}"
                                   placeholder="Enter UDYAM number">
                            <button class="btn btn-primary" type="button" id="verifyUdyamBtn">Verify UDYAM</button>
                        </div>
                        <small id="udyamStatus" class="form-text text-muted">
                            Provide UDYAM number for Sole Proprietorship verification.
                        </small>
                    </div>

                    {{-- GSTIN Field (optional) --}}
                    <div class="mb-3">
                        <label class="form-label">GSTIN (To be used for Invoice and Billing)</label>
                        <div class="input-group">
                            <input type="text"
                                   class="form-control"
                                   id="gstin"
                                   name="gstin"
                                   maxlength="15"
                                   value="{{ $kyc->gstin }}"
                                   placeholder="Enter 15 character GSTIN (optional)">
                            <button class="btn btn-primary" type="button" id="verifyGstBtn">Verify GST</button>
                        </div>
                        <small id="gstStatus" class="form-text text-muted">
                            Enter and verify GSTIN if available for billing and invoicing purposes.
                        </small>
                    </div>

                </div>

                {{-- Step 2: Contact & Public Details --}}
                <div id="kycStep2" style="display: none;">
                    <h6 class="mb-3">Step 2: Contact & Public Details</h6>

                    {{-- Management Representative --}}
                    <div class="mb-2">
                        <h6 class="mb-1">A. Management Representative</h6>
                        <small class="text-muted">Primary management contact for the organisation. DIN can be verified later via API.</small>
                    </div>

                    <input type="hidden" id="management_pan_verified" name="management_pan_verified" value="{{ $kyc->management_pan_verified ? '1' : '0' }}">
                    <input type="hidden" id="management_email_verified" name="management_email_verified" value="{{ $kyc->management_email_verified ? '1' : '0' }}">
                    <input type="hidden" id="management_mobile_verified" name="management_mobile_verified" value="{{ $kyc->management_mobile_verified ? '1' : '0' }}">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                id="management_name"
                                name="management_name"
                                value="{{ $kyc->management_name }}"
                                required
                            >
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input
                                type="date"
                                class="form-control"
                                id="management_dob"
                                name="management_dob"
                                value="{{ optional($kyc->management_dob)->format('Y-m-d') }}"
                                required
                            >
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">DIN</label>
                            <input
                                type="text"
                                class="form-control"
                                id="management_din"
                                name="management_din"
                                value="{{ $kyc->management_din }}"
                            >
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PAN <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                id="management_pan"
                                name="management_pan"
                                maxlength="10"
                                value="{{ $kyc->management_pan }}"
                                required
                            >
                        </div>
                        <div class="col-md-6 d-flex flex-column justify-content-end align-items-start">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span id="managementPanVerifyStatus" class="kyc-badge-pending">
                                    PAN / Name / DOB verification required.
                                </span>
                                <button class="btn btn-primary btn-sm" type="button" id="verifyManagementPanBtn">
                                    Verify
                                </button>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input
                                    type="email"
                                    class="form-control"
                                    id="management_email"
                                    name="management_email"
                                    value="{{ $kyc->management_email }}"
                                    required
                                >
                                <button class="btn btn-primary" type="button" id="managementEmailSendOtpBtn">
                                    Send OTP
                                </button>
                            </div>
                            <div class="input-group mt-2 d-none" id="managementEmailOtpRow">
                                <input type="text"
                                       class="form-control"
                                       id="management_email_otp_input"
                                       maxlength="6"
                                       placeholder="Enter 6-digit OTP">
                                <button class="btn btn-success" type="button" id="managementEmailVerifyOtpBtn">
                                    Verify
                                </button>
                            </div>
                            <small id="managementEmailOtpStatus" class="form-text text-muted"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input
                                    type="tel"
                                    class="form-control"
                                    id="management_mobile"
                                    name="management_mobile"
                                    maxlength="10"
                                    value="{{ $kyc->management_mobile }}"
                                    required
                                >
                                <button class="btn btn-primary" type="button" id="managementMobileSendOtpBtn">
                                    Send OTP
                                </button>
                            </div>
                            <div class="input-group mt-2 d-none" id="managementMobileOtpRow">
                                <input type="text"
                                       class="form-control"
                                       id="management_mobile_otp_input"
                                       maxlength="6"
                                       placeholder="Enter 6-digit OTP">
                                <button class="btn btn-success" type="button" id="managementMobileVerifyOtpBtn">
                                    Verify
                                </button>
                            </div>
                            <small id="managementMobileOtpStatus" class="form-text text-muted"></small>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- Authorised Representative --}}
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div>
                            <h6 class="mb-1">B. Authorised Representative</h6>
                            <small class="text-muted">Details used for verification (PAN, Email, Mobile) and communication.</small>
                        </div>
                        <button
                            class="btn btn-outline-secondary btn-sm"
                            type="button"
                            id="copyFromManagementBtn"
                        >
                            Same as Management
                        </button>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text"
                                   class="form-control"
                                   id="contact_name"
                                   name="contact_name"
                                   value="">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth</label>
                            <input type="date"
                                   class="form-control"
                                   id="contact_dob"
                                   name="contact_dob"
                                   value="">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PAN</label>
                            <input type="text"
                                   class="form-control"
                                   id="contact_pan"
                                   name="contact_pan"
                                   maxlength="10"
                                   value="">
                        </div>
                        <div class="col-md-6 d-flex flex-column justify-content-end align-items-start">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span id="panVerifyStatus" class="kyc-badge-pending">
                                    PAN / Name / DOB verification required after any change.
                                </span>
                                <button class="btn btn-primary btn-sm" type="button" id="verifyPanNameBtn">
                                    Verify
                                </button>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <div class="input-group">
                                <input type="email"
                                       class="form-control"
                                       id="contact_email"
                                       name="contact_email"
                                       value="">
                                <button class="btn btn-primary" type="button" id="emailSendOtpBtn">
                                    Send OTP
                                </button>
                            </div>
                            <div class="input-group mt-2 d-none" id="emailOtpRow">
                                <input type="text"
                                       class="form-control"
                                       id="email_otp_input"
                                       maxlength="6"
                                       placeholder="Enter 6-digit OTP">
                                <button class="btn btn-success" type="button" id="emailVerifyOtpBtn">
                                    Verify
                                </button>
                            </div>
                            <small id="emailOtpStatus" class="form-text text-muted"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile</label>
                            <div class="input-group">
                                <input type="tel"
                                       class="form-control"
                                       id="contact_mobile"
                                       name="contact_mobile"
                                       maxlength="10"
                                       value="">
                                <button class="btn btn-primary" type="button" id="mobileSendOtpBtn">
                                    Send OTP
                                </button>
                            </div>
                            <div class="input-group mt-2 d-none" id="mobileOtpRow">
                                <input type="text"
                                       class="form-control"
                                       id="mobile_otp_input"
                                       maxlength="6"
                                       placeholder="Enter 6-digit OTP">
                                <button class="btn btn-success" type="button" id="mobileVerifyOtpBtn">
                                    Verify
                                </button>
                            </div>
                            <small id="mobileOtpStatus" class="form-text text-muted"></small>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- Billing Details --}}
                    <div class="mb-3">
                        <label class="form-label">Billing Address (from verified IDs or Other) <span class="text-danger">*</span></label>
                        <select id="billingAddressSelect" name="billing_address_select" class="form-select" required>
                            <option value="">Select a billing address</option>
                            <option value="other" {{ $kyc->billing_address_source === 'other' ? 'selected' : '' }}>Other (enter manually)</option>
                        </select>
                        <input type="hidden" id="billing_address_source" name="billing_address_source" value="{{ $kyc->billing_address_source }}">
                        <input type="hidden" id="billing_address" name="billing_address" value="{{ $kyc->billing_address }}">
                    </div>

                    <div class="mb-3 {{ $kyc->billing_address_source === 'other' ? '' : 'd-none' }}" id="billingAddressOtherWrapper">
                        <label class="form-label">Billing Address (Other) <span class="text-danger">*</span></label>
                        <textarea
                            class="form-control"
                            id="billing_address_other"
                            name="billing_address_other"
                            rows="3"
                            placeholder="Enter full billing address including PIN code"
                            {{ $kyc->billing_address_source === 'other' ? 'required' : '' }}
                        >{{ $kyc->billing_address_source === 'other' ? $kyc->billing_address : '' }}</textarea>
                    </div>

                    <div id="billingAddressCard" class="card border-0 shadow-sm d-none">
                        <div class="card-body py-2">
                            <small class="text-muted d-block mb-1">Selected Billing Address</small>
                            <div id="billingAddressLabel" class="fw-semibold small mb-1"></div>
                            <div id="billingAddressText" class="small"></div>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- Public WHOIS Details --}}
                    <div class="mb-2">
                        <h6 class="mb-1">C. Public Details (Whois)</h6>
                        <small class="text-muted">
                            Choose whose details (Management or Authorised Representative) should be used for public Whois records.
                        </small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="radio"
                                name="whois_source"
                                id="whois_management"
                                value="management"
                                {{ $kyc->whois_source === 'management' ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="whois_management">
                                Use Management Representative details
                            </label>
                        </div>
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="radio"
                                name="whois_source"
                                id="whois_authorized"
                                value="authorized"
                                {{ $kyc->whois_source === 'authorized' || ! $kyc->whois_source ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="whois_authorized">
                                Use Authorised Representative details
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="kyc-modal-footer flex-wrap">
                <div class="mb-1">
                    <small id="kycOverallStatus" class="text-muted">
                        Complete all verifications to submit KYC.
                    </small>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm me-2" id="prevStepBtn" disabled>Back</button>
                    <button type="button" class="btn btn-success btn-sm" id="nextStepBtn">Next</button>
                    <button type="submit" class="btn btn-success btn-sm d-none" id="submitKycBtn">Submit KYC</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const kycState = {
        currentStep: 1,
        billingAddresses: [], // {key, source, label, address}
        companyName: null, // canonical company name from first verified doc
        emailOtpSent: false,
        mobileOtpSent: false,
    };

    // Company name match: allow if similarity >= 70% (no exact match required)
    const COMPANY_NAME_SIMILARITY_THRESHOLD = 70;

    function levenshteinDistance(a, b) {
        const matrix = [];
        for (let i = 0; i <= b.length; i++) matrix[i] = [i];
        for (let j = 0; j <= a.length; j++) matrix[0][j] = j;
        for (let i = 1; i <= b.length; i++) {
            for (let j = 1; j <= a.length; j++) {
                if (b.charAt(i - 1) === a.charAt(j - 1)) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j - 1] + 1,
                        matrix[i][j - 1] + 1,
                        matrix[i - 1][j] + 1
                    );
                }
            }
        }
        return matrix[b.length][a.length];
    }

    function companyNameSimilarityPercent(str1, str2) {
        if (!str1 || !str2) return str1 === str2 ? 100 : 0;
        const s1 = str1.trim().toLowerCase();
        const s2 = str2.trim().toLowerCase();
        if (s1 === s2) return 100;
        const maxLen = Math.max(s1.length, s2.length);
        if (maxLen === 0) return 100;
        const dist = levenshteinDistance(s1, s2);
        return Math.round((1 - dist / maxLen) * 100);
    }

    function setStep(step) {
        kycState.currentStep = step;

        document.getElementById('kycStep1').style.display = step === 1 ? 'block' : 'none';
        document.getElementById('kycStep2').style.display = step === 2 ? 'block' : 'none';

        document.getElementById('kycStepIndicator1').classList.toggle('kyc-step-active', step === 1);
        document.getElementById('kycStepIndicator1').classList.toggle('kyc-step-completed', step === 2);
        document.getElementById('kycStepIndicator2').classList.toggle('kyc-step-active', step === 2);

        document.getElementById('prevStepBtn').disabled = (step === 1);
        document.getElementById('nextStepBtn').classList.toggle('d-none', step === 2);
        document.getElementById('submitKycBtn').classList.toggle('d-none', step === 1);
    }

    document.getElementById('nextStepBtn').addEventListener('click', function () {
        const gstinInput = document.getElementById('gstin');
        const gstinValue = gstinInput ? gstinInput.value.trim() : '';
        const gstVerified = document.getElementById('gst_verified').value === '1';
        const affiliateMode = document.getElementById('affiliate_verification_mode')?.value || '';

        // GSTIN is optional, but if entered, it must be verified
        if (gstinValue && !gstVerified) {
            alert('Please verify GSTIN before continuing to the next step, or leave it blank if you do not have a GSTIN.');
            return;
        }

        // CIN verification required for CIN-based affiliate types
        if (affiliateMode === 'cin') {
            const mcaVerified = document.getElementById('mca_verified').value === '1';
            if (!mcaVerified) {
                alert('Please verify CIN before continuing to the next step.');
                return;
            }
        }

        // UDYAM verification required for Sole Proprietorship
        if (affiliateMode === 'udyam') {
            const udyamVerified = document.getElementById('udyam_verified').value === '1';
            if (!udyamVerified) {
                alert('Please verify UDYAM before continuing to the next step.');
                return;
            }
        }

        // Document upload required for document-based affiliate types
        if (affiliateMode === 'document') {
            const docWrapper = document.getElementById('affiliateDocumentWrapper');
            const docInput = document.getElementById('affiliate_document_file');
            if (docWrapper && docWrapper.style.display !== 'none') {
                // Check if file is uploaded or if existing file exists
                const hasFile = docInput && docInput.files && docInput.files.length > 0;
                const existingFileNote = docWrapper.querySelector('.form-text.text-muted');
                const hasExistingFile = existingFileNote && existingFileNote.textContent.includes('Existing file');
                
                if (!hasFile && !hasExistingFile) {
                    alert('Please upload the required affiliate document before continuing to the next step.');
                    return;
                }
            }
        }

        // Move to Step 2 (Management Representative verifications will be checked on form submit)
        setStep(2);
    });

    document.getElementById('prevStepBtn').addEventListener('click', function () {
        setStep(1);
    });

    // Organisation Type: toggle Others textbox and ISP/VNO license upload
    function refreshOrganisationTypeUi() {
        const typeSelect = document.getElementById('organisation_type');
        const otherWrapper = document.getElementById('organisationTypeOtherWrapper');
        const licenceWrapper = document.getElementById('organisationLicenseWrapper');

        if (!typeSelect) {
            return;
        }

        const selected = typeSelect.value;

        // Others textbox
        if (selected === 'others') {
            otherWrapper.style.display = 'block';
        } else {
            otherWrapper.style.display = 'none';
            const otherInput = document.getElementById('organisation_type_other');
            if (otherInput) {
                otherInput.value = '';
            }
        }

        // ISP/VNO license upload
        const requiresLicence = ['isp_a', 'isp_b', 'isp_c', 'vno_a', 'vno_b', 'vno_c'].includes(selected);
        licenceWrapper.style.display = requiresLicence ? 'block' : 'none';
    }

    const organisationTypeSelect = document.getElementById('organisation_type');
    if (organisationTypeSelect) {
        organisationTypeSelect.addEventListener('change', refreshOrganisationTypeUi);
        refreshOrganisationTypeUi();
    }

    // Affiliate Type: set verification mode and toggle CIN/UDYAM/document fields
    function refreshAffiliateTypeUi() {
        const affiliateSelect = document.getElementById('affiliate_type');
        const modeInput = document.getElementById('affiliate_verification_mode');
        const cinField = document.getElementById('cinField');
        const udyamField = document.getElementById('udyamField');
        const docWrapper = document.getElementById('affiliateDocumentWrapper');
        const verificationInfoAlert = document.getElementById('verificationInfoAlert');
        const additionalVerificationText = document.getElementById('additionalVerificationText');

        if (!affiliateSelect || !modeInput) {
            return;
        }

        const value = affiliateSelect.value;
        let mode = '';

        // Map affiliate type to verification mode:
        // - CIN verification: Private / Limited / LLP / OPC
        // - UDYAM verification: Sole proprietorship
        // - Document upload: Partnership / Govt incorporation / School & College
        if (['private_limited', 'limited_company', 'llp', 'opc'].includes(value)) {
            mode = 'cin';
        } else if (value === 'sole_proprietorship') {
            mode = 'udyam';
        } else if (['partnership', 'gov_incorporation', 'school_college'].includes(value)) {
            mode = 'document';
        }

        modeInput.value = mode;

        // Show/hide CIN field
        if (cinField) {
            cinField.style.display = mode === 'cin' ? 'block' : 'none';
            const cinInput = document.getElementById('cin');
            if (mode !== 'cin' && cinInput) {
                cinInput.removeAttribute('required');
            } else if (mode === 'cin' && cinInput) {
                cinInput.setAttribute('required', 'required');
            }
        }

        // Show/hide UDYAM field
        if (udyamField) {
            udyamField.style.display = mode === 'udyam' ? 'block' : 'none';
            const udyamInput = document.getElementById('udyam_number');
            if (mode !== 'udyam' && udyamInput) {
                udyamInput.removeAttribute('required');
                // Reset UDYAM fields when hidden
                if (udyamInput) {
                    udyamInput.value = '';
                    document.getElementById('udyam_verified').value = '0';
                    document.getElementById('udyam_verification_id').value = '';
                }
            } else if (mode === 'udyam' && udyamInput) {
                udyamInput.setAttribute('required', 'required');
            }
        }

        // Show/hide document upload field
        if (docWrapper) {
            docWrapper.style.display = mode === 'document' ? 'block' : 'none';
            const docInput = document.getElementById('affiliate_document_file');
            if (mode === 'document' && docInput) {
                docInput.setAttribute('required', 'required');
            } else if (mode !== 'document' && docInput) {
                docInput.removeAttribute('required');
            }
        }

        // Update alert message
        if (verificationInfoAlert && additionalVerificationText) {
            if (mode === 'cin') {
                additionalVerificationText.textContent = 'CIN verification is also required for your selected affiliate type.';
            } else if (mode === 'udyam') {
                additionalVerificationText.textContent = 'UDYAM verification is also required for Sole Proprietorship.';
            } else if (mode === 'document') {
                additionalVerificationText.textContent = 'Please upload the required affiliate document.';
            } else {
                additionalVerificationText.textContent = '';
            }
        }
    }

    const affiliateTypeSelect = document.getElementById('affiliate_type');
    if (affiliateTypeSelect) {
        affiliateTypeSelect.addEventListener('change', refreshAffiliateTypeUi);
        refreshAffiliateTypeUi();
    }

    function refreshBillingAddressOptions() {
        const select = document.getElementById('billingAddressSelect');
        const card = document.getElementById('billingAddressCard');
        const labelEl = document.getElementById('billingAddressLabel');
        const textEl = document.getElementById('billingAddressText');

        // Reset select - keep first option (empty) and "Other" option
        // Remove all options except the first two (empty select and "Other")
        while (select.options.length > 2) {
            select.remove(2);
        }

        // Ensure "Other" option exists (it should already be there from HTML)
        let otherOptionExists = false;
        for (let i = 0; i < select.options.length; i++) {
            if (select.options[i].value === 'other') {
                otherOptionExists = true;
                break;
            }
        }
        if (!otherOptionExists) {
            const otherOpt = document.createElement('option');
            otherOpt.value = 'other';
            otherOpt.textContent = 'Other (enter manually)';
            select.appendChild(otherOpt);
        }

        // Show GST and CIN addresses in billing address options (plus "Other" which is always present)
        const availableAddresses = kycState.billingAddresses.filter(item => item.source === 'gstin' || item.source === 'cin');
        
        availableAddresses.forEach((item) => {
            const opt = document.createElement('option');
            opt.value = item.key;
            opt.textContent = item.label;
            select.appendChild(opt);
        });

        // Don't auto-select if there are no addresses or if user has already selected "Other"
        const currentValue = select.value;
        if (availableAddresses.length === 0) {
            // If no addresses, don't auto-select anything
            if (currentValue !== 'other') {
                select.value = '';
            }
            card.classList.add('d-none');
            if (currentValue !== 'other') {
                document.getElementById('billing_address_source').value = '';
                document.getElementById('billing_address').value = '';
            }
        } else {
            // If there are addresses but user hasn't selected anything yet, pre-select the first one
            // But only if user hasn't manually selected "Other"
            if (!currentValue && availableAddresses.length > 0) {
                const first = availableAddresses[0];
                select.value = first.key;
                document.getElementById('billing_address_source').value = first.source;
                document.getElementById('billing_address').value = JSON.stringify({
                    source: first.source,
                    label: first.label,
                    address: first.address,
                });
                labelEl.textContent = first.label;
                textEl.textContent = first.address;
                card.classList.remove('d-none');
            } else if (currentValue && currentValue !== 'other') {
                // If an address is selected, update the card
                const selected = availableAddresses.find(item => item.key === currentValue);
                if (selected) {
                    document.getElementById('billing_address_source').value = selected.source;
                    document.getElementById('billing_address').value = JSON.stringify({
                        source: selected.source,
                        label: selected.label,
                        address: selected.address,
                    });
                    labelEl.textContent = selected.label;
                    textEl.textContent = selected.address;
                    card.classList.remove('d-none');
                }
            }
        }
    }

    document.getElementById('billingAddressSelect').addEventListener('change', function () {
        const selectedKey = this.value;
        const card = document.getElementById('billingAddressCard');
        const labelEl = document.getElementById('billingAddressLabel');
        const textEl = document.getElementById('billingAddressText');
        const otherWrapper = document.getElementById('billingAddressOtherWrapper');
        const otherTextarea = document.getElementById('billing_address_other');

        if (!selectedKey) {
            card.classList.add('d-none');
            otherWrapper.classList.add('d-none');
            document.getElementById('billing_address_source').value = '';
            document.getElementById('billing_address').value = '';
            return;
        }

        if (selectedKey === 'other') {
            // Manual address entry
            otherWrapper.classList.remove('d-none');
            card.classList.add('d-none');
            document.getElementById('billing_address_source').value = 'other';
            // Update billing_address immediately with current textarea value
            const currentAddress = otherTextarea.value.trim();
            document.getElementById('billing_address').value = currentAddress;
            // Add event listener only once (check if already added)
            if (!otherTextarea.hasAttribute('data-listener-added')) {
                otherTextarea.setAttribute('data-listener-added', 'true');
                otherTextarea.addEventListener('input', function () {
                    document.getElementById('billing_address').value = this.value.trim();
                });
            }
            return;
        }

        otherWrapper.classList.add('d-none');

        // Allow selection of GST or CIN addresses
        const availableAddresses = kycState.billingAddresses.filter(item => item.source === 'gstin' || item.source === 'cin');
        const selected = availableAddresses.find(item => item.key === selectedKey);
        if (selected) {
            document.getElementById('billing_address_source').value = selected.source;
            document.getElementById('billing_address').value = JSON.stringify({
                source: selected.source,
                label: selected.label,
                address: selected.address,
            });
            labelEl.textContent = selected.label;
            textEl.textContent = selected.address;
            card.classList.remove('d-none');
        }
    });

    // "Same as Management" button - copy Management Representative details into Authorised Representative
    // ========== Management Representative Verification ==========

    // Management PAN / Name / DOB verification
    const verifyManagementPanBtn = document.getElementById('verifyManagementPanBtn');
    if (verifyManagementPanBtn) {
        verifyManagementPanBtn.addEventListener('click', function () {
            const nameInput = document.getElementById('management_name');
            const dobInput = document.getElementById('management_dob');
            const panInput = document.getElementById('management_pan');
            const statusEl = document.getElementById('managementPanVerifyStatus');

            const fullname = nameInput.value.trim();
            const dateofbirth = dobInput.value;
            const pancardno = panInput.value.trim().toUpperCase();

            if (!fullname || !dateofbirth || !pancardno) {
                alert('Please fill Name, DOB and PAN before verification.');
                return;
            }

            // Basic PAN format check before hitting API
            const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
            if (!panRegex.test(pancardno)) {
                alert('Please enter a valid PAN (e.g. ABCDE1234F).');
                return;
            }

            this.disabled = true;
            this.textContent = 'Verifying...';
            statusEl.textContent = 'Verifying...';
            statusEl.className = 'kyc-badge-pending';

            const btn = this;

            // First create PAN verification task
            fetch('{{ route("register.verify.pan") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    fullname: fullname,
                    dateofbirth: dateofbirth,
                    pancardno: pancardno
                })
            })
                .then(response => response.text())
                .then(text => {
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        throw new Error('Invalid response from server.');
                    }

                    if (!data || !data.request_id) {
                        throw new Error(data.message || 'Failed to start PAN verification.');
                    }

                    // Poll for verification status
                    pollKycPanStatus(data.request_id, (result) => {
                        if (result.success && result.status === 'completed') {
                            document.getElementById('management_pan_verified').value = '1';
                            nameInput.readOnly = true;
                            nameInput.classList.add('kyc-readonly');
                            dobInput.readOnly = true;
                            dobInput.classList.add('kyc-readonly');
                            panInput.readOnly = true;
                            panInput.classList.add('kyc-readonly');
                            btn.disabled = true;
                            btn.textContent = 'Verified';
                            btn.classList.remove('btn-primary');
                            btn.classList.add('btn-success');
                            statusEl.textContent = 'PAN / Name / DOB verified successfully.';
                            statusEl.className = 'kyc-badge-success';
                        } else {
                            document.getElementById('management_pan_verified').value = '0';
                            statusEl.textContent = result.message || 'PAN verification failed.';
                            statusEl.className = 'kyc-badge-error';
                            btn.disabled = false;
                            btn.textContent = 'Verify';
                        }
                    });
                })
                .catch(error => {
                    console.error(error);
                    statusEl.textContent = error.message || 'An error occurred while verifying PAN.';
                    statusEl.className = 'kyc-badge-error';
                    btn.disabled = false;
                    btn.textContent = 'Verify';
                });
        });
    }

    // Management Email OTP - Send
    const managementEmailSendOtpBtn = document.getElementById('managementEmailSendOtpBtn');
    if (managementEmailSendOtpBtn) {
        managementEmailSendOtpBtn.addEventListener('click', function () {
            const emailInput = document.getElementById('management_email');
            const email = emailInput.value.trim();
            const statusEl = document.getElementById('managementEmailOtpStatus');
            const otpRow = document.getElementById('managementEmailOtpRow');

            if (!email) {
                alert('Please enter email to verify.');
                return;
            }

            this.disabled = true;
            this.textContent = 'Sending...';
            statusEl.textContent = 'Sending OTP to email...';

            fetch('{{ route("register.send.email.otp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ email: email })
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to send OTP.');
                    }

                    otpRow.classList.remove('d-none');
                    statusEl.textContent = 'OTP sent to your email. Enter it below and click Verify.';
                    statusEl.className = 'form-text text-info';
                    this.disabled = false;
                    this.textContent = 'Resend OTP';
                })
                .catch(error => {
                    console.error(error);
                    statusEl.textContent = error.message || 'Error while sending email OTP.';
                    statusEl.className = 'form-text text-danger';
                    this.disabled = false;
                    const isOtpRowVisible = !otpRow.classList.contains('d-none');
                    this.textContent = isOtpRowVisible ? 'Resend OTP' : 'Send OTP';
                });
        });
    }

    // Management Email OTP - Verify
    const managementEmailVerifyOtpBtn = document.getElementById('managementEmailVerifyOtpBtn');
    if (managementEmailVerifyOtpBtn) {
        managementEmailVerifyOtpBtn.addEventListener('click', function () {
            const emailInput = document.getElementById('management_email');
            const email = emailInput.value.trim();
            const statusEl = document.getElementById('managementEmailOtpStatus');
            const otpInput = document.getElementById('management_email_otp_input');
            const otpRow = document.getElementById('managementEmailOtpRow');

            const otp = otpInput.value.trim();
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
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ email: email, otp: otp, master_otp: otp })
            })
                .then(response => response.json())
                .then(data => {
                    if (data && data.success) {
                        document.getElementById('management_email_verified').value = '1';
                        emailInput.readOnly = true;
                        emailInput.classList.add('kyc-readonly');
                        emailInput.classList.add('bg-success', 'text-black');
                        emailInput.value = email;
                        otpRow.classList.add('d-none');
                        const sendBtn = document.getElementById('managementEmailSendOtpBtn');
                        sendBtn.disabled = true;
                        sendBtn.textContent = 'Verified';
                        sendBtn.classList.remove('btn-primary');
                        sendBtn.classList.add('btn-success');
                        statusEl.textContent = 'Email verified.';
                        statusEl.className = 'form-text text-success';
                    } else {
                        document.getElementById('management_email_verified').value = '0';
                        statusEl.textContent = (data && data.message) ? data.message : 'Email verification failed.';
                        statusEl.className = 'form-text text-danger';
                    }
                })
                .catch(error => {
                    console.error(error);
                    statusEl.textContent = error.message || 'Error while verifying email.';
                    statusEl.className = 'form-text text-danger';
                })
                .finally(() => {
                    this.disabled = false;
                    this.textContent = 'Verify';
                });
        });
    }

    // Management Mobile OTP - Send
    const managementMobileSendOtpBtn = document.getElementById('managementMobileSendOtpBtn');
    if (managementMobileSendOtpBtn) {
        managementMobileSendOtpBtn.addEventListener('click', function () {
            const mobileInput = document.getElementById('management_mobile');
            const mobile = mobileInput.value.trim();
            const statusEl = document.getElementById('managementMobileOtpStatus');
            const otpRow = document.getElementById('managementMobileOtpRow');

            if (!mobile) {
                alert('Please enter mobile number to verify.');
                return;
            }

            if (mobile.length !== 10) {
                alert('Please enter a valid 10-digit mobile number.');
                return;
            }

            this.disabled = true;
            this.textContent = 'Sending...';
            statusEl.textContent = 'Sending OTP to mobile...';

            fetch('{{ route("register.send.mobile.otp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ mobile: mobile })
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to send OTP.');
                    }

                    otpRow.classList.remove('d-none');
                    statusEl.textContent = 'OTP sent to your mobile. Enter it below and click Verify.';
                    statusEl.className = 'form-text text-info';
                    this.disabled = false;
                    this.textContent = 'Resend OTP';
                })
                .catch(error => {
                    console.error(error);
                    statusEl.textContent = error.message || 'Error while sending mobile OTP.';
                    statusEl.className = 'form-text text-danger';
                    this.disabled = false;
                    const isOtpRowVisible = !otpRow.classList.contains('d-none');
                    this.textContent = isOtpRowVisible ? 'Resend OTP' : 'Send OTP';
                });
        });
    }

    // Management Mobile OTP - Verify
    const managementMobileVerifyOtpBtn = document.getElementById('managementMobileVerifyOtpBtn');
    if (managementMobileVerifyOtpBtn) {
        managementMobileVerifyOtpBtn.addEventListener('click', function () {
            const mobileInput = document.getElementById('management_mobile');
            const mobile = mobileInput.value.trim();
            const statusEl = document.getElementById('managementMobileOtpStatus');
            const otpInput = document.getElementById('management_mobile_otp_input');
            const otpRow = document.getElementById('managementMobileOtpRow');

            const otp = otpInput.value.trim();
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
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ mobile: mobile, otp: otp, master_otp: otp })
            })
                .then(response => response.json())
                .then(data => {
                    if (data && data.success) {
                        document.getElementById('management_mobile_verified').value = '1';
                        mobileInput.readOnly = true;
                        mobileInput.classList.add('kyc-readonly');
                        mobileInput.classList.add('bg-success', 'text-black');
                        mobileInput.value = mobile;
                        otpRow.classList.add('d-none');
                        const sendBtn = document.getElementById('managementMobileSendOtpBtn');
                        sendBtn.disabled = true;
                        sendBtn.textContent = 'Verified';
                        sendBtn.classList.remove('btn-primary');
                        sendBtn.classList.add('btn-success');
                        statusEl.textContent = 'Mobile verified.';
                        statusEl.className = 'form-text text-success';
                    } else {
                        document.getElementById('management_mobile_verified').value = '0';
                        statusEl.textContent = (data && data.message) ? data.message : 'Mobile verification failed.';
                        statusEl.className = 'form-text text-danger';
                    }
                })
                .catch(error => {
                    console.error(error);
                    statusEl.textContent = error.message || 'Error while verifying mobile.';
                    statusEl.className = 'form-text text-danger';
                })
                .finally(() => {
                    this.disabled = false;
                    this.textContent = 'Verify';
                });
        });
    }

    // ========== End Management Representative Verification ==========

    const copyFromManagementBtn = document.getElementById('copyFromManagementBtn');
    if (copyFromManagementBtn) {
        copyFromManagementBtn.addEventListener('click', function () {
            const mName = document.getElementById('management_name')?.value || '';
            const mDob = document.getElementById('management_dob')?.value || '';
            const mPan = document.getElementById('management_pan')?.value || '';
            const mEmail = document.getElementById('management_email')?.value || '';
            const mMobile = document.getElementById('management_mobile')?.value || '';

            document.getElementById('contact_name').value = mName;
            document.getElementById('contact_dob').value = mDob;
            document.getElementById('contact_pan').value = mPan;
            document.getElementById('contact_email').value = mEmail;
            document.getElementById('contact_mobile').value = mMobile;

            // Reset verification flags so user re-verifies for the copied data
            document.getElementById('contact_name_pan_dob_verified').value = '0';
            document.getElementById('contact_email_verified').value = '0';
            document.getElementById('contact_mobile_verified').value = '0';

            document.getElementById('panVerifyStatus').textContent = 'PAN / Name / DOB verification required after any change.';
            document.getElementById('panVerifyStatus').className = 'kyc-badge-pending';
            document.getElementById('emailOtpStatus').textContent = '';
            document.getElementById('mobileOtpStatus').textContent = '';
        });
    }

    // GST verification (uses existing IRINN verification endpoints)
    document.getElementById('verifyGstBtn').addEventListener('click', function () {
        const gstinInput = document.getElementById('gstin');
        const gstin = gstinInput.value.trim().toUpperCase();
        const statusEl = document.getElementById('gstStatus');

        if (!gstin || gstin.length !== 15) {
            alert('Please enter a valid 15 character GSTIN.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Verifying...';
        statusEl.textContent = 'Initiating GST verification...';
        statusEl.className = 'form-text text-info';

        fetch('{{ route("user.applications.irin.verify-gst") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ gstin: gstin })
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to start GST verification.');
                }

                const requestId = data.request_id;
                statusEl.textContent = 'GST verification in progress...';
                pollVerificationStatus('gstin', requestId, (result) => {
                    if (result.is_verified) {
                        let verificationData = result.verification_data || {};
                        const companyName = verificationData.company_name || verificationData.legal_name || verificationData.trade_name || null;

                        // Company name consistency check (70% or more match allowed)
                        if (companyName) {
                            const normalized = companyName.trim().toLowerCase();
                            if (kycState.companyName) {
                                const similarity = companyNameSimilarityPercent(kycState.companyName, normalized);
                                if (similarity < COMPANY_NAME_SIMILARITY_THRESHOLD) {
                                    alert('Company name from GST does not match previously verified document (match ' + similarity + '%). At least ' + COMPANY_NAME_SIMILARITY_THRESHOLD + '% match is required. Please verify correct details.');
                                    document.getElementById('gst_verified').value = '0';
                                    statusEl.textContent = 'Company name mismatch (' + similarity + '% match). GST verification rejected.';
                                    statusEl.className = 'form-text text-danger';
                                    return;
                                }
                            } else {
                                kycState.companyName = normalized;
                            }
                        }

                        document.getElementById('gst_verified').value = '1';
                        document.getElementById('gst_verification_id').value = data.verification_id;
                        gstinInput.readOnly = true;
                        gstinInput.classList.add('kyc-readonly');
                        const btn = document.getElementById('verifyGstBtn');
                        btn.disabled = true;
                        btn.textContent = 'Verified';
                        btn.classList.remove('btn-outline-success');
                        btn.classList.add('btn-success');
                        statusEl.textContent = 'GSTIN verified successfully.';
                        statusEl.className = 'form-text text-primary';
                        document.getElementById('kycStepIndicator1').classList.add('kyc-step-completed');

                        // Add GST address to billing options if available
                        if (verificationData && verificationData.primary_address) {
                            const existingIndex = kycState.billingAddresses.findIndex(item => item.source === 'gstin');
                            const entry = {
                                key: 'gstin:' + (verificationData.gstin || gstin),
                                source: 'gstin',
                                label: 'GSTIN - ' + (verificationData.gstin || gstin),
                                address: verificationData.primary_address,
                            };
                            if (existingIndex >= 0) {
                                kycState.billingAddresses[existingIndex] = entry;
                            } else {
                                kycState.billingAddresses.push(entry);
                            }
                            refreshBillingAddressOptions();
                        }
                    } else {
                        document.getElementById('gst_verified').value = '0';
                        statusEl.textContent = result.message || 'GSTIN verification failed.';
                        statusEl.className = 'form-text text-danger';
                    }
                });
            })
            .catch(error => {
                console.error(error);
                statusEl.textContent = error.message || 'An error occurred while verifying GSTIN.';
                statusEl.className = 'form-text text-danger';
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'Verify GST';
            });
    });

    function pollVerificationStatus(type, requestId, callback, retries = 0, maxRetries = 10) {
        if (retries > maxRetries) {
            callback({ is_verified: false, message: 'Verification timeout. Please try again.' });
            return;
        }

        fetch('{{ route("user.applications.irin.check-verification-status") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ type: type, request_id: requestId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'completed') {
                    callback(data);
                } else if (data.status === 'failed') {
                    callback({ is_verified: false, message: data.message || 'Verification failed.' });
                } else {
                    setTimeout(() => {
                        pollVerificationStatus(type, requestId, callback, retries + 1, maxRetries);
                    }, 2000);
                }
            })
            .catch(error => {
                console.error(error);
                callback({ is_verified: false, message: 'Error while checking verification status.' });
            });
    }

    // MCA CIN verification
    document.getElementById('verifyMcaBtn').addEventListener('click', function () {
        const cinInput = document.getElementById('cin');
        const cin = cinInput.value.trim();
        const statusEl = document.getElementById('mcaStatus');

        if (!cin) {
            alert('Please enter CIN to verify.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Verifying...';
        statusEl.textContent = 'CIN verification in progress...';
        statusEl.className = 'form-text text-info';

        fetch('{{ route("user.applications.irin.verify-mca") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ cin: cin })
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to start CIN verification.');
                }

                const requestId = data.request_id;
                pollVerificationStatus('mca', requestId, (result) => {
                    if (result.is_verified) {
                        const verificationData = result.verification_data || {};
                        const companyName = verificationData.company_name || null;

                        if (companyName) {
                            const normalized = companyName.trim().toLowerCase();
                            if (kycState.companyName) {
                                const similarity = companyNameSimilarityPercent(kycState.companyName, normalized);
                                if (similarity < COMPANY_NAME_SIMILARITY_THRESHOLD) {
                                    alert('Company name from CIN does not match previously verified document (match ' + similarity + '%). At least ' + COMPANY_NAME_SIMILARITY_THRESHOLD + '% match is required. Please verify correct details.');
                                    document.getElementById('mca_verified').value = '0';
                                    statusEl.textContent = 'Company name mismatch (' + similarity + '% match). CIN verification rejected.';
                                    statusEl.className = 'form-text text-danger';
                                    return;
                                }
                            } else {
                                kycState.companyName = normalized;
                            }
                        }

                        document.getElementById('mca_verified').value = '1';
                        document.getElementById('mca_verification_id').value = data.verification_id;
                        cinInput.readOnly = true;
                        cinInput.classList.add('kyc-readonly');
                        const btn = document.getElementById('verifyMcaBtn');
                        btn.disabled = true;
                        btn.textContent = 'Verified';
                        btn.classList.remove('btn-outline-secondary');
                        btn.classList.add('btn-success');
                        statusEl.textContent = 'CIN verified successfully.';
                        statusEl.className = 'form-text text-primary';

                        // Add CIN address to billing options if available
                        if (verificationData && verificationData.primary_address) {
                            const existingIndex = kycState.billingAddresses.findIndex(item => item.source === 'cin');
                            const entry = {
                                key: 'cin:' + (verificationData.cin || cin),
                                source: 'cin',
                                label: 'CIN - ' + (verificationData.cin || cin),
                                address: verificationData.primary_address,
                            };
                            if (existingIndex >= 0) {
                                kycState.billingAddresses[existingIndex] = entry;
                            } else {
                                kycState.billingAddresses.push(entry);
                            }
                            refreshBillingAddressOptions();
                        }
                    } else {
                        document.getElementById('mca_verified').value = '0';
                        statusEl.textContent = result.message || 'CIN verification failed.';
                        statusEl.className = 'form-text text-danger';
                    }
                });
            })
            .catch(error => {
                console.error(error);
                statusEl.textContent = error.message || 'An error occurred while verifying CIN.';
                statusEl.className = 'form-text text-danger';
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'Verify CIN';
            });
    });

    // UDYAM verification
    document.getElementById('verifyUdyamBtn').addEventListener('click', function () {
        const udyamInput = document.getElementById('udyam_number');
        const udyamNumber = udyamInput.value.trim();
        const statusEl = document.getElementById('udyamStatus');

        if (!udyamNumber) {
            alert('Please enter UDYAM number to verify.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Verifying...';
        statusEl.textContent = 'UDYAM verification in progress...';
        statusEl.className = 'form-text text-info';

        fetch('{{ route("user.applications.irin.verify-udyam") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ uam_number: udyamNumber })
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to start UDYAM verification.');
                }

                const requestId = data.request_id;
                pollVerificationStatus('udyam', requestId, (result) => {
                    if (result.is_verified) {
                        document.getElementById('udyam_verified').value = '1';
                        document.getElementById('udyam_verification_id').value = data.verification_id;
                        udyamInput.readOnly = true;
                        udyamInput.classList.add('kyc-readonly');
                        const btn = document.getElementById('verifyUdyamBtn');
                        btn.disabled = true;
                        btn.textContent = 'Verified';
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-success');
                        statusEl.textContent = 'UDYAM verified successfully.';
                        statusEl.className = 'form-text text-success';
                    } else {
                        document.getElementById('udyam_verified').value = '0';
                        statusEl.textContent = result.message || 'UDYAM verification failed.';
                        statusEl.className = 'form-text text-danger';
                    }
                });
            })
            .catch(error => {
                console.error(error);
                statusEl.textContent = error.message || 'An error occurred while verifying UDYAM.';
                statusEl.className = 'form-text text-danger';
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'Verify UDYAM';
            });
    });

    // PAN / Name / DOB verification (re-use registration PAN API + status polling)
    document.getElementById('verifyPanNameBtn').addEventListener('click', function () {
        const nameInput = document.getElementById('contact_name');
        const dobInput = document.getElementById('contact_dob');
        const panInput = document.getElementById('contact_pan');
        const statusEl = document.getElementById('panVerifyStatus');

        const fullname = nameInput.value.trim();
        const dateofbirth = dobInput.value;
        const pancardno = panInput.value.trim().toUpperCase();

        if (!fullname || !dateofbirth || !pancardno) {
            alert('Please fill Name, DOB and PAN before verification.');
            return;
        }

        // Basic PAN format check before hitting API
        const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
        if (!panRegex.test(pancardno)) {
            alert('Please enter a valid PAN (e.g. ABCDE1234F).');
            return;
        }

        this.disabled = true;
        this.textContent = 'Verifying...';
        statusEl.textContent = 'Verifying...';
        statusEl.className = 'kyc-badge-pending';

        const btn = this;

        // First create PAN verification task
        fetch('{{ route("register.verify.pan") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                fullname: fullname,
                dateofbirth: dateofbirth,
                pancardno: pancardno
            })
        })
            .then(response => response.text())
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Unexpected response from server while initiating PAN verification.');
                }
                if (!data.success || !data.request_id) {
                    throw new Error(data.message || 'Failed to initiate PAN verification.');
                }

                statusEl.textContent = 'Verifying PAN...';

                // Poll PAN status using existing register.check.pan.status endpoint
                pollKycPanStatus(data.request_id, (result) => {
                    if (result.success) {
                        document.getElementById('contact_name_pan_dob_verified').value = '1';
                        nameInput.readOnly = true;
                        dobInput.readOnly = true;
                        panInput.readOnly = true;
                        nameInput.classList.add('kyc-readonly');
                        dobInput.classList.add('kyc-readonly');
                        panInput.classList.add('kyc-readonly');
                        btn.disabled = true;
                        btn.textContent = 'Verified';
                        btn.classList.remove('btn-outline-primary');
                        btn.classList.add('btn-success');
                        statusEl.textContent = 'PAN / Name / DOB verified.';
                        statusEl.className = 'kyc-badge-verified';
                    } else {
                        document.getElementById('contact_name_pan_dob_verified').value = '0';
                        statusEl.textContent = result.message || 'PAN verification failed.';
                        statusEl.className = 'kyc-badge-pending';
                        btn.disabled = false;
                        btn.textContent = 'Verify';
                    }
                });
            })
            .catch(error => {
                console.error(error);
                statusEl.textContent = error.message || 'Error while verifying PAN details.';
                statusEl.className = 'kyc-badge-pending';
                btn.disabled = false;
                btn.textContent = 'Verify';
            });
    });

    // Poll PAN verification status for KYC (uses RegisterController@checkPanStatus)
    function pollKycPanStatus(requestId, callback, retries = 0, maxRetries = 15) {
        fetch('{{ route("register.check.pan.status") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ request_id: requestId })
        })
            .then(response => response.text())
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    callback({
                        success: false,
                        message: 'Unexpected response from server while checking PAN verification status.',
                    });
                    return;
                }
                if (data.status === 'completed') {
                    callback(data);
                } else if (data.status === 'failed') {
                    callback({
                        success: false,
                        message: data.message || 'PAN verification failed.',
                    });
                } else if (retries < maxRetries) {
                    setTimeout(() => pollKycPanStatus(requestId, callback, retries + 1, maxRetries), 2000);
                } else {
                    callback({
                        success: false,
                        message: 'PAN verification timeout. Please try again.',
                    });
                }
            })
            .catch(error => {
                console.error(error);
                callback({
                    success: false,
                    message: 'Error while checking PAN verification status.',
                });
            });
    }

    // Email OTP - Send
    document.getElementById('emailSendOtpBtn').addEventListener('click', function () {
        const emailInput = document.getElementById('contact_email');
        const email = emailInput.value.trim();
        const statusEl = document.getElementById('emailOtpStatus');
        const otpRow = document.getElementById('emailOtpRow');

        if (!email) {
            alert('Please enter email to verify.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Sending...';
        statusEl.textContent = 'Sending OTP to email...';

        fetch('{{ route("register.send.email.otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ email: email })
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to send OTP.');
                }

                kycState.emailOtpSent = true;
                otpRow.classList.remove('d-none');
                statusEl.textContent = 'OTP sent to your email. Enter it below and click Verify.';
                statusEl.className = 'form-text text-info';
                this.disabled = false;
                this.textContent = 'Resend OTP';
            })
            .catch(error => {
                console.error(error);
                statusEl.textContent = error.message || 'Error while sending email OTP.';
                statusEl.className = 'form-text text-danger';
                this.disabled = false;
                const isOtpRowVisible = !otpRow.classList.contains('d-none');
                this.textContent = isOtpRowVisible ? 'Resend OTP' : 'Send OTP';
            });
    });

    // Email OTP - Verify (OTP or Master OTP)
    document.getElementById('emailVerifyOtpBtn').addEventListener('click', function () {
        const emailInput = document.getElementById('contact_email');
        const email = emailInput.value.trim();
        const statusEl = document.getElementById('emailOtpStatus');
        const otpInput = document.getElementById('email_otp_input');
        const otpRow = document.getElementById('emailOtpRow');

        const otp = otpInput.value.trim();
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
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ email: email, otp: otp, master_otp: otp })
        })
            .then(response => response.json())
            .then(data => {
                if (data && data.success) {
                    document.getElementById('contact_email_verified').value = '1';
                    emailInput.readOnly = true;
                    emailInput.classList.add('kyc-readonly');
                    emailInput.classList.add('bg-success', 'text-black');
                    emailInput.value = email;
                    otpRow.classList.add('d-none');
                    const sendBtn = document.getElementById('emailSendOtpBtn');
                    sendBtn.disabled = true;
                    sendBtn.textContent = 'Verified';
                    sendBtn.classList.remove('btn-outline-primary');
                    sendBtn.classList.add('btn-success');
                    statusEl.textContent = 'Email verified.';
                    statusEl.className = 'form-text text-success';
                } else {
                    document.getElementById('contact_email_verified').value = '0';
                    statusEl.textContent = (data && data.message) ? data.message : 'Email verification failed.';
                    statusEl.className = 'form-text text-danger';
                }
            })
            .catch(error => {
                console.error(error);
                statusEl.textContent = error.message || 'Error while verifying email.';
                statusEl.className = 'form-text text-danger';
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'Verify';
            });
    });

    // Mobile OTP - Send
    document.getElementById('mobileSendOtpBtn').addEventListener('click', function () {
        const mobileInput = document.getElementById('contact_mobile');
        const mobile = mobileInput.value.trim();
        const statusEl = document.getElementById('mobileOtpStatus');
        const otpRow = document.getElementById('mobileOtpRow');

        if (!mobile || mobile.length !== 10) {
            alert('Please enter valid 10-digit mobile to verify.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Sending...';
        statusEl.textContent = 'Sending OTP to mobile...';

        fetch('{{ route("register.send.mobile.otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ mobile: mobile })
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to send OTP.');
                }

                kycState.mobileOtpSent = true;
                otpRow.classList.remove('d-none');
                statusEl.textContent = 'OTP sent to your mobile. Enter it below and click Verify.';
                statusEl.className = 'form-text text-info';
                this.disabled = false;
                this.textContent = 'Resend OTP';
            })
            .catch(error => {
                console.error(error);
                statusEl.textContent = error.message || 'Error while sending mobile OTP.';
                statusEl.className = 'form-text text-danger';
                this.disabled = false;
                const isOtpRowVisible = !otpRow.classList.contains('d-none');
                this.textContent = isOtpRowVisible ? 'Resend OTP' : 'Send OTP';
            });
    });

    // Mobile OTP - Verify (OTP or Master OTP)
    document.getElementById('mobileVerifyOtpBtn').addEventListener('click', function () {
        const mobileInput = document.getElementById('contact_mobile');
        const mobile = mobileInput.value.trim();
        const statusEl = document.getElementById('mobileOtpStatus');
        const otpInput = document.getElementById('mobile_otp_input');
        const otpRow = document.getElementById('mobileOtpRow');

        const otp = otpInput.value.trim();
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
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ mobile: mobile, otp: otp, master_otp: otp })
        })
            .then(response => response.json())
            .then(data => {
                if (data && data.success) {
                    document.getElementById('contact_mobile_verified').value = '1';
                    mobileInput.readOnly = true;
                    mobileInput.classList.add('kyc-readonly');
                    mobileInput.classList.add('bg-success', 'text-black');
                    mobileInput.value = mobile;
                    otpRow.classList.add('d-none');
                    const sendBtn = document.getElementById('mobileSendOtpBtn');
                    sendBtn.disabled = true;
                    sendBtn.textContent = 'Verified';
                    sendBtn.classList.remove('btn-outline-primary');
                    sendBtn.classList.add('btn-success');
                    statusEl.textContent = 'Mobile verified.';
                    statusEl.className = 'form-text text-success';
                } else {
                    document.getElementById('contact_mobile_verified').value = '0';
                    statusEl.textContent = (data && data.message) ? data.message : 'Mobile verification failed.';
                    statusEl.className = 'form-text text-danger';
                }
            })
            .catch(error => {
                console.error(error);
                statusEl.textContent = error.message || 'Error while verifying mobile.';
                statusEl.className = 'form-text text-danger';
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'Verify';
            });
    });

    // Final submit
    document.getElementById('kycForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const gstinInput = document.getElementById('gstin');
        const gstinValue = gstinInput ? gstinInput.value.trim() : '';
        const gstVerified = document.getElementById('gst_verified').value === '1';
        const mcaVerified = document.getElementById('mca_verified').value === '1';
        const udyamVerified = document.getElementById('udyam_verified').value === '1';
        const affiliateMode = document.getElementById('affiliate_verification_mode')?.value || '';
        
        // Management Representative verifications
        const managementPanVerified = document.getElementById('management_pan_verified').value === '1';
        const managementEmailVerified = document.getElementById('management_email_verified').value === '1';
        const managementMobileVerified = document.getElementById('management_mobile_verified').value === '1';
        
        // Authorised Representative verifications
        const namePanDobVerified = document.getElementById('contact_name_pan_dob_verified').value === '1';
        const emailVerified = document.getElementById('contact_email_verified').value === '1';
        const mobileVerified = document.getElementById('contact_mobile_verified').value === '1';

        // GSTIN is optional, but if entered, it must be verified
        if (gstinValue && !gstVerified) {
            alert('Please verify GSTIN before submitting, or leave it blank if you do not have a GSTIN.');
            return;
        }

        // CIN verification required for CIN-based affiliate types
        if (affiliateMode === 'cin' && !mcaVerified) {
            alert('Please verify CIN before submitting KYC.');
            return;
        }

        // UDYAM verification required for Sole Proprietorship
        if (affiliateMode === 'udyam' && !udyamVerified) {
            alert('Please verify UDYAM before submitting KYC.');
            return;
        }

        // Document upload required for document-based affiliate types
        if (affiliateMode === 'document') {
            const docWrapper = document.getElementById('affiliateDocumentWrapper');
            const docInput = document.getElementById('affiliate_document_file');
            if (docWrapper && docWrapper.style.display !== 'none') {
                const hasFile = docInput && docInput.files && docInput.files.length > 0;
                const existingFileNote = docWrapper.querySelector('.form-text.text-muted');
                const hasExistingFile = existingFileNote && existingFileNote.textContent.includes('Existing file');
                
                if (!hasFile && !hasExistingFile) {
                    alert('Please upload the required affiliate document before submitting KYC.');
                    return;
                }
            }
        }

        // Management Representative verifications required
        if (!managementPanVerified) {
            alert('Please verify Management Representative PAN / Name / DOB before submitting.');
            return;
        }

        if (!managementEmailVerified) {
            alert('Please verify Management Representative Email before submitting.');
            return;
        }

        if (!managementMobileVerified) {
            alert('Please verify Management Representative Mobile before submitting.');
            return;
        }

        // Authorised Representative verifications required
        if (!namePanDobVerified || !emailVerified || !mobileVerified) {
            alert('Please complete all required verifications for Authorised Representative (PAN details, Email and Mobile) before submitting KYC.');
            return;
        }

        // Billing address validation - must select an address or enter manually
        const billingAddressSelect = document.getElementById('billingAddressSelect');
        const billingAddressSource = document.getElementById('billing_address_source').value;
        const billingAddress = document.getElementById('billing_address').value.trim();
        const billingAddressOther = document.getElementById('billing_address_other').value.trim();

        if (!billingAddressSelect.value) {
            alert('Please select a billing address or choose "Other" to enter manually.');
            return;
        }

        if (billingAddressSelect.value === 'other') {
            if (!billingAddressOther) {
                alert('Please enter the billing address in the textarea.');
                return;
            }
            // Ensure the hidden field is updated with the manual address
            document.getElementById('billing_address').value = billingAddressOther;
            document.getElementById('billing_address_source').value = 'other';
        } else if (!billingAddress) {
            alert('Please select a billing address from the dropdown.');
            return;
        }

        const formData = new FormData(this);
        
        // Add CSRF token to FormData
        formData.append('_token', '{{ csrf_token() }}');

        document.getElementById('submitKycBtn').disabled = true;
        document.getElementById('submitKycBtn').textContent = 'Submitting...';

        fetch('{{ route("user.kyc.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                // Don't set Content-Type header - let browser set it automatically for FormData with files
            },
            body: formData
        })
            .then(response => {
                // Check if response is JSON or needs to be parsed
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error('Invalid response from server.');
                        }
                    });
                }
            })
            .then(data => {
                if (data.success) {
                    alert('KYC submitted successfully.');
                    window.location.href = '{{ route("user.dashboard") }}';
                } else {
                    // Handle validation errors
                    let errorMessage = data.message || 'Failed to submit KYC.';
                    if (data.errors) {
                        const errorMessages = Object.values(data.errors).flat();
                        errorMessage = errorMessages.join('\n');
                    }
                    throw new Error(errorMessage);
                }
            })
            .catch(error => {
                console.error(error);
                alert(error.message || 'Error while submitting KYC.');
            })
            .finally(() => {
                document.getElementById('submitKycBtn').disabled = false;
                document.getElementById('submitKycBtn').textContent = 'Submit KYC';
            });
    });

    // Initial state
    setStep(1);

    // Initialize billing address "Other" wrapper if "Other" is selected
    const billingAddressSelectInit = document.getElementById('billingAddressSelect');
    if (billingAddressSelectInit && billingAddressSelectInit.value === 'other') {
        const otherWrapperInit = document.getElementById('billingAddressOtherWrapper');
        if (otherWrapperInit) {
            otherWrapperInit.classList.remove('d-none');
        }
    }
</script>
@endpush


