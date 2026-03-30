@extends('user.layout')

@section('title', 'IRINN Application')

@push('styles')
<style>
    .flow-wrap { max-width: 1100px; margin: 0 auto; }
    .flow-card { border: 1px solid rgba(124, 58, 237, 0.16); border-radius: 14px; box-shadow: 0 2px 10px rgba(124, 58, 237, 0.08); }
    .flow-step { display: none; }
    .flow-step.active { display: block; }
    .req { color: #dc3545; }
    .readonly-like { background: #f8f9fa; pointer-events: none; }
    .stepper .badge { min-width: 32px; cursor: pointer; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="flow-wrap">
        <div class="mb-3">
            <h2 class="mb-1 text-blue fw-bold">
                @if(!empty($isNormalizedResubmission))
                    Resubmit IRINN application
                @else
                    IRINN Application
                @endif
            </h2>
            <p class="text-muted mb-0">
                @if(!empty($isNormalizedResubmission))
                    Your details are prefilled. Update any fields or documents as needed, then submit. Email and mobile OTP verification is not required for this resubmission.
                @else
                    Configure and verify technical person flow details.
                @endif
            </p>
        </div>

        @if(!empty($isNormalizedResubmission) && !empty($resubmissionReason))
            <div class="alert alert-warning border-warning mb-3">
                <strong>Admin requested changes:</strong>
                <div class="small mt-2 mb-0" style="white-space: pre-wrap;">{{ $resubmissionReason }}</div>
            </div>
        @endif

        @if(!empty($isNormalizedResubmission))
            <div class="alert alert-info border-info mb-3 small">
                <strong>About documents:</strong> Your answers above are pre-filled. Uploaded files cannot appear inside the file picker (browser security).
                Where a document was already submitted, use <strong>View current upload</strong> to open it. Choose a new file only when you need to replace that document.
            </div>
        @endif

        <div class="d-flex align-items-center gap-2 mb-3 stepper">
            <span class="badge bg-primary" id="s1Badge">1</span><small class="text-muted me-2">Organisation</small>
            <span class="badge bg-secondary" id="s2Badge">2</span><small class="text-muted me-2">Management Representative</small>
            <span class="badge bg-secondary" id="s3Badge">3</span><small class="text-muted me-2">Technical Person & Abuse Contact</small>
            <span class="badge bg-secondary" id="s4Badge">4</span><small class="text-muted me-2">Billing Representative</small>
            <span class="badge bg-secondary" id="s5Badge">5</span><small class="text-muted me-2">Network Resources</small>
            <span class="badge bg-secondary" id="s6Badge">6</span><small class="text-muted me-2">Upstream &amp; Signatory</small>
            <span class="badge bg-secondary" id="s7Badge">7</span><small class="text-muted">KYC Documents</small>
        </div>

        <form id="irinnFlowForm" class="flow-card bg-white p-3 p-md-4">
            @csrf
            <input type="hidden" name="irinn_normalized_flow" value="1">
            <input type="hidden" name="irinn_form_version" value="create_new_v1">
            <input type="hidden" name="irinn_current_stage" value="submitted">
            @if(!empty($isNormalizedResubmission) && isset($application))
                <input type="hidden" name="irinn_resubmit_application_id" value="{{ $application->id }}">
            @endif
            <div id="irinnFlowHiddenSync" class="d-none" aria-hidden="true"></div>
            <input type="hidden" id="company_name_source" value="">
            <input type="hidden" id="gst_verification_request_id" name="gst_verification_request_id" value="">
            <input type="hidden" id="mca_verification_request_id" name="mca_verification_request_id" value="">

            <div id="step1" class="flow-step active">
                <h5 class="mb-3">Step 1: Organisation Details</h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Company Type <span class="req">*</span></label>
                        <select class="form-select" id="company_type" name="irinn_company_type" required>
                            <option value="">Select</option>
                            <option value="private_limited">Private Limited</option>
                            <option value="public_limited">Public Limited</option>
                            <option value="llp">Limited Liability Partnership (LLP)</option>
                            <option value="opc">One Person Company (OPC)</option>
                            <option value="psu">Public Sector Undertaking (PSU)</option>
                            <option value="partnership">Partnership</option>
                            <option value="proprietor">Proprietor</option>
                            <option value="government">Government</option>
                            <option value="ngo">NGO</option>
                            <option value="academia_institute">Academia institute</option>
                            <option value="trust">Trust</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="cinWrap" style="display:none;">
                        <label class="form-label">CIN Number <span class="req">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="cin_number" name="irinn_cin_number" placeholder="Enter CIN">
                            <button class="btn btn-primary" type="button" id="verifyCinBtn">Verify CIN</button>
                        </div>
                        <small id="cinStatus" class="text-muted"></small>
                    </div>

                    <div class="col-md-6" id="udyamWrap" style="display:none;">
                        <label class="form-label">Udyam Number <span class="req">*</span></label>
                        <input type="text" class="form-control" id="udyam_number" name="irinn_udyam_number" placeholder="Enter Udyam Number">
                        <small class="text-muted">Manual entry mode (no API integration for now).</small>
                    </div>

                    <div class="col-md-6" id="regDocWrap" style="display:none;">
                        <label class="form-label">Upload Registration Document <span class="req">*</span></label>
                        @include('user.applications.irin.partials.resubmit-doc-hint', ['docColumn' => 'irinn_registration_document_path'])
                        <input type="file" class="form-control" id="registration_document" name="irinn_registration_document" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    </div>
                </div>

                <hr class="my-3">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Organisation Name <span class="req">*</span></label>
                        <input type="text" class="form-control org-field" id="organisation_name" name="irinn_organisation_name" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Address <span class="req">*</span></label>
                        <input type="text" class="form-control org-field" id="organisation_address" name="irinn_organisation_address" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Postcode <span class="req">*</span></label>
                        <input type="text" class="form-control org-field" id="organisation_postcode" name="irinn_organisation_postcode" required maxlength="10">
                    </div>
                    <div class="col-md-6" id="industryTypeWrap">
                        <label class="form-label">Industry Type <span class="req">*</span></label>
                        <select class="form-select" id="industry_type" name="irinn_industry_type" required>
                            <option value="">Select</option>
                            <option>Government</option>
                            <option>Banking and Financial Services</option>
                            <option>ISP A -- All Over India</option>
                            <option>ISP B -- For Particular State</option>
                            <option>ISP C -- For Particular District</option>
                            <option>Hosting or Data Centre</option>
                            <option>IT or Software</option>
                            <option>Enterprise or Manufacturing</option>
                            <option>Media or Entertainment</option>
                            <option>VNO License</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Name (auto-generated)</label>
                        <input type="text" class="form-control readonly-like" id="account_name" name="irinn_account_name" readonly>
                    </div>
                </div>

                <hr class="my-3">

                <h6 class="mb-2">Billing Details</h6>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="has_gst_number" name="irinn_has_gst_number" value="1">
                    <label class="form-check-label" for="has_gst_number">Do you have GST Number?</label>
                </div>

                <div id="gstBillingWrap" style="display:none;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">GST Number <span class="req">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="billing_gstin" name="irinn_billing_gstin" maxlength="15" placeholder="Enter GST Number">
                                <button type="button" class="btn btn-primary" id="verifyBillingGstBtn">Verify GST</button>
                            </div>
                            <small id="gstBillingStatus" class="text-muted"></small>
                        </div>
                    </div>
                </div>

                <div id="nonGstBillingWrap">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">CA Declaration File <span class="req">*</span></label>
                            @include('user.applications.irin.partials.resubmit-doc-hint', ['docColumn' => 'irinn_ca_declaration_path'])
                            <input type="file" class="form-control" id="ca_declaration_file" name="irinn_ca_declaration_file" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <label class="form-label">Legal Name <span class="req">*</span></label>
                        <input type="text" class="form-control bill-field" id="billing_legal_name" name="irinn_billing_legal_name" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">PAN Number <span class="req">*</span></label>
                        <input type="text" class="form-control bill-field" id="billing_pan_number" name="irinn_billing_pan" maxlength="10" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Address <span class="req">*</span></label>
                        <input type="text" class="form-control bill-field" id="billing_address" name="irinn_billing_address" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Postcode <span class="req">*</span></label>
                        <input type="text" class="form-control bill-field" id="billing_postcode" name="irinn_billing_postcode" required maxlength="10">
                    </div>
                </div>
                <small id="nameMatchStatus" class="text-danger d-none">Organisation Name and Legal Name do not match (minimum 70% required).</small>

                <div class="d-flex justify-content-end mt-4">
                    <button type="button" class="btn btn-primary" id="goStep2Btn">Next</button>
                </div>
            </div>

            <div id="step2" class="flow-step">
                <h5 class="mb-3">Step 2: Management Representative Details</h5>
                <div class="alert alert-info small py-2 mb-3" role="alert">
                    <strong>Verification:</strong> Email OTP is sent using your application mail settings.
                    Mobile OTP is sent by SMS when SMS is enabled in server configuration (in local/debug without SMS, the OTP may appear on screen for testing only). After you verify each field, it becomes read-only.
                </div>

                <div class="row g-3">
                    <div class="col-md-12" id="repSelectWrap" style="display:none;">
                        <label class="form-label">Select Director (from CIN) <span class="req">*</span></label>
                        <select class="form-select" id="management_rep_select" name="irinn_management_rep_index">
                            <option value="">Select director</option>
                        </select>
                        <small class="text-muted">Directors are loaded after CIN verification. Only active directors (no end date) are listed.</small>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label">Name <span class="req">*</span></label>
                        <input type="text" class="form-control" id="mr_name" name="irinn_mr_name" required>
                        <input type="hidden" id="mr_director_din" name="irinn_mr_din" value="">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Designation <span class="req">*</span></label>
                        <select class="form-select" id="mr_designation" name="irinn_mr_designation" required>
                            <option value="">Select</option>
                            <option>Chief Executive Officer (CEO)</option>
                            <option>Chief Operating Officer (COO)</option>
                            <option>Chief Financial Officer (CFO)</option>
                            <option>Chief Technology Officer (CTO)</option>
                            <option>Chief Marketing Officer (CMO)</option>
                            <option>Chief Information Officer (CIO)</option>
                            <option>Chief Human Resources Officer (CHRO)</option>
                            <option>Chief Product Officer (CPO)</option>
                            <option>Chief Revenue Officer (CRO)</option>
                            <option>Chief Data Officer (CDO)</option>
                            <option>Chief Strategy Officer (CSO)</option>
                            <option>Chief Compliance Officer (CCO)</option>
                            <option>Chief Legal Officer (CLO)</option>
                            <option>Chief Security Officer (CSO)</option>
                            <option>President</option>
                            <option>Vice President</option>
                            <option>Senior Vice President</option>
                            <option>Executive Vice President</option>
                            <option>VP of Sales</option>
                            <option>VP of Marketing</option>
                            <option>VP of Engineering</option>
                            <option>VP of Operations</option>
                            <option>VP of Finance</option>
                            <option>VP of Human Resources</option>
                            <option>VP of Product</option>
                            <option>VP of Business Development</option>
                            <option>Director</option>
                            <option>Senior Director</option>
                            <option>Managing Director</option>
                            <option>Executive Director</option>
                            <option>Director of Sales</option>
                            <option>Director of Marketing</option>
                            <option>Director of Engineering</option>
                            <option>Director of Operations</option>
                            <option>Director of Finance</option>
                            <option>Director of Human Resources</option>
                            <option>Director of IT</option>
                            <option>Director of Product Management</option>
                            <option>Manager</option>
                            <option>Senior Manager</option>
                            <option>General Manager</option>
                            <option>Project Manager</option>
                            <option>Product Manager</option>
                            <option>Sales Manager</option>
                            <option>Marketing Manager</option>
                            <option>Operations Manager</option>
                            <option>HR Manager</option>
                            <option>IT Manager</option>
                            <option>Software Engineer</option>
                            <option>Senior Software Engineer</option>
                            <option>Full Stack Developer</option>
                            <option>Frontend Developer</option>
                            <option>Backend Developer</option>
                            <option>DevOps Engineer</option>
                            <option>Data Engineer</option>
                            <option>Data Scientist</option>
                            <option>QA Engineer</option>
                            <option>Accountant</option>
                            <option>Financial Analyst</option>
                            <option>HR Specialist</option>
                            <option>Recruiter</option>
                            <option>Customer Service Representative</option>
                            <option>Customer Support Specialist</option>
                            <option>Founder</option>
                            <option>Co-Founder</option>
                            <option>Owner</option>
                            <option>Freelancer</option>
                            <option>Intern</option>
                            <option>Student</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email <span class="req">*</span></label>
                        <div class="input-group">
                            <input type="email" class="form-control" id="mr_email" name="irinn_mr_email" required>
                            <button class="btn btn-primary" type="button" id="sendMrEmailOtpBtn">Send OTP</button>
                        </div>
                        <div class="input-group mt-2 d-none" id="mrEmailOtpWrap">
                            <input type="text" class="form-control" id="mr_email_otp" placeholder="Enter OTP" maxlength="6">
                            <button class="btn btn-success" type="button" id="verifyMrEmailOtpBtn">Verify</button>
                        </div>
                        <small id="mrEmailStatus" class="text-muted"></small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mobile <span class="req">*</span></label>
                        <div class="input-group">
                            <input type="tel" class="form-control" id="mr_mobile" name="irinn_mr_mobile" maxlength="10" required>
                            <button class="btn btn-primary" type="button" id="sendMrMobileOtpBtn">Send OTP</button>
                        </div>
                        <div class="input-group mt-2 d-none" id="mrMobileOtpWrap">
                            <input type="text" class="form-control" id="mr_mobile_otp" placeholder="Enter OTP" maxlength="6">
                            <button class="btn btn-success" type="button" id="verifyMrMobileOtpBtn">Verify</button>
                        </div>
                        <small id="mrMobileStatus" class="text-muted"></small>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-secondary" id="backStep1Btn">Back</button>
                    <button type="button" class="btn btn-primary" id="goStep3Btn">Next</button>
                </div>
            </div>

            <div id="step3" class="flow-step">
                <h5 class="mb-3">Step 3: Technical Person Details & Abuse Contact</h5>

                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Technical Person Details</h6>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="appSameAsMgmtBtn">Same as Management Details</button>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Name <span class="req">*</span></label>
                            <input type="text" class="form-control" id="app_name" name="irinn_tp_name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Designation <span class="req">*</span></label>
                            <select class="form-select" id="app_designation" name="irinn_tp_designation" required></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Email <span class="req">*</span></label>
                            <div class="input-group">
                                <input type="email" class="form-control" id="app_email" name="irinn_tp_email" required>
                                <button class="btn btn-primary" type="button" id="sendAppEmailOtpBtn">Send OTP</button>
                            </div>
                            <div class="input-group mt-2 d-none" id="appEmailOtpWrap">
                                <input type="text" class="form-control" id="app_email_otp" maxlength="6" placeholder="Enter OTP">
                                <button class="btn btn-success" type="button" id="verifyAppEmailOtpBtn">Verify</button>
                            </div>
                            <small id="appEmailStatus" class="text-muted"></small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mobile <span class="req">*</span></label>
                            <div class="input-group">
                                <input type="tel" class="form-control" id="app_mobile" name="irinn_tp_mobile" maxlength="10" required>
                                <button class="btn btn-primary" type="button" id="sendAppMobileOtpBtn">Send OTP</button>
                            </div>
                            <div class="input-group mt-2 d-none" id="appMobileOtpWrap">
                                <input type="text" class="form-control" id="app_mobile_otp" maxlength="6" placeholder="Enter OTP">
                                <button class="btn btn-success" type="button" id="verifyAppMobileOtpBtn">Verify</button>
                            </div>
                            <small id="appMobileStatus" class="text-muted"></small>
                        </div>
                    </div>
                </div>

                <div class="border rounded p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <h6 class="mb-0">Abuse Contact Details</h6>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="abuseSameAsMgmtBtn">Same as Management Details</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="abuseSameAsApplicantBtn">Same as Technical Person Details</button>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Name <span class="req">*</span></label>
                            <input type="text" class="form-control" id="abuse_name" name="irinn_abuse_name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Designation <span class="req">*</span></label>
                            <select class="form-select" id="abuse_designation" name="irinn_abuse_designation" required></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Email <span class="req">*</span></label>
                            <div class="input-group">
                                <input type="email" class="form-control" id="abuse_email" name="irinn_abuse_email" required>
                                <button class="btn btn-primary" type="button" id="sendAbuseEmailOtpBtn">Send OTP</button>
                            </div>
                            <div class="input-group mt-2 d-none" id="abuseEmailOtpWrap">
                                <input type="text" class="form-control" id="abuse_email_otp" maxlength="6" placeholder="Enter OTP">
                                <button class="btn btn-success" type="button" id="verifyAbuseEmailOtpBtn">Verify</button>
                            </div>
                            <small id="abuseEmailStatus" class="text-muted"></small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mobile <span class="req">*</span></label>
                            <div class="input-group">
                                <input type="tel" class="form-control" id="abuse_mobile" name="irinn_abuse_mobile" maxlength="10" required>
                                <button class="btn btn-primary" type="button" id="sendAbuseMobileOtpBtn">Send OTP</button>
                            </div>
                            <div class="input-group mt-2 d-none" id="abuseMobileOtpWrap">
                                <input type="text" class="form-control" id="abuse_mobile_otp" maxlength="6" placeholder="Enter OTP">
                                <button class="btn btn-success" type="button" id="verifyAbuseMobileOtpBtn">Verify</button>
                            </div>
                            <small id="abuseMobileStatus" class="text-muted"></small>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-secondary" id="backStep2Btn">Back</button>
                    <button type="button" class="btn btn-primary" id="goStep4Btn">Next</button>
                </div>
            </div>

            <div id="step4" class="flow-step">
                <h5 class="mb-3">Step 4: Billing Representative Details</h5>

                <div class="border rounded p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <h6 class="mb-0">Billing Representative</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="brSameAsMgmtBtn">Same as Management Details</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="brSameAsApplicantBtn">Same as Technical Person Details</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="brSameAsAbuseBtn">Same as Abuse Contact</button>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Name <span class="req">*</span></label>
                            <input type="text" class="form-control" id="br_name" name="irinn_br_name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Designation <span class="req">*</span></label>
                            <select class="form-select" id="br_designation" name="irinn_br_designation" required></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Email <span class="req">*</span></label>
                            <div class="input-group">
                                <input type="email" class="form-control" id="br_email" name="irinn_br_email" required>
                                <button class="btn btn-primary" type="button" id="sendBrEmailOtpBtn">Send OTP</button>
                            </div>
                            <div class="input-group mt-2 d-none" id="brEmailOtpWrap">
                                <input type="text" class="form-control" id="br_email_otp" maxlength="6" placeholder="Enter OTP">
                                <button class="btn btn-success" type="button" id="verifyBrEmailOtpBtn">Verify</button>
                            </div>
                            <small id="brEmailStatus" class="text-muted"></small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mobile <span class="req">*</span></label>
                            <div class="input-group">
                                <input type="tel" class="form-control" id="br_mobile" name="irinn_br_mobile" maxlength="10" required>
                                <button class="btn btn-primary" type="button" id="sendBrMobileOtpBtn">Send OTP</button>
                            </div>
                            <div class="input-group mt-2 d-none" id="brMobileOtpWrap">
                                <input type="text" class="form-control" id="br_mobile_otp" maxlength="6" placeholder="Enter OTP">
                                <button class="btn btn-success" type="button" id="verifyBrMobileOtpBtn">Verify</button>
                            </div>
                            <small id="brMobileStatus" class="text-muted"></small>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-secondary" id="backStep3Btn">Back</button>
                    <button type="button" class="btn btn-primary" id="goStep5Btn">Next</button>
                </div>
            </div>

            <div id="step5" class="flow-step">
                <h5 class="mb-3">Step 5: Network Resources</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6>IPv4</h6>
                            <div id="ipv4ResourceList" class="d-grid gap-2"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6>IPv6</h6>
                            <div id="ipv6ResourceList" class="d-grid gap-2"></div>
                        </div>
                    </div>
                </div>

                <div class="border rounded p-3 mt-3">
                    <h6>Billing Calculation</h6>
                    <div class="small text-muted">IPv4: 27,500 x 1.35^(log10(addresses) - 8)</div>
                    <div class="small text-muted mb-2">IPv6: 24,199 x 1.35^(log10(addresses) - 22)</div>
                    <div id="resourceBillingBreakup" class="small"></div>
                    <div class="fw-bold mt-2">Final Billing Amount: <span id="finalBillingAmount">₹ 0.00</span></div>
                </div>

                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="asn_required_check" name="irinn_asn_required" value="1">
                    <label class="form-check-label" for="asn_required_check">Autonomous System Number (ASN) required</label>
                </div>
                <p class="small text-muted mb-0">Indicate whether you require an ASN. Upstream provider contact details are collected in the next step.</p>

                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-secondary" id="backStep4Btn">Back</button>
                    <button type="button" class="btn btn-primary" id="goStep6Btn">Next</button>
                </div>
            </div>

            <div id="step6" class="flow-step">
                <h5 class="mb-3">Step 6: Upstream Provider &amp; Authorized Signatory</h5>

                <div class="border rounded p-3 mb-4">
                    <h6 class="mb-2">Upstream Provider Details <span class="req">*</span></h6>
                    <p class="small text-muted mb-3">Mandatory. Provide your upstream provider&rsquo;s contact for ASN / connectivity.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Upstream Provider Name <span class="req">*</span></label>
                            <input type="text" class="form-control" id="asn_name" name="irinn_upstream_provider_name" autocomplete="organization">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">AS Number <span class="req">*</span></label>
                            <input type="text" class="form-control" id="asn_number" name="irinn_upstream_as_number" autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile <span class="req">*</span></label>
                            <div class="input-group">
                                <input type="tel" class="form-control" id="asn_mobile" name="irinn_upstream_mobile" maxlength="10" autocomplete="tel">
                                <button class="btn btn-primary" type="button" id="sendAsnMobileOtpBtn">Send OTP</button>
                            </div>
                            <div class="input-group mt-2 d-none" id="asnMobileOtpWrap">
                                <input type="text" class="form-control" id="asn_mobile_otp" maxlength="6" placeholder="Enter OTP">
                                <button class="btn btn-success" type="button" id="verifyAsnMobileOtpBtn">Verify</button>
                            </div>
                            <small id="asnMobileStatus" class="text-muted"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="req">*</span></label>
                            <div class="input-group">
                                <input type="email" class="form-control" id="asn_email" name="irinn_upstream_email" autocomplete="email">
                                <button class="btn btn-primary" type="button" id="sendAsnEmailOtpBtn">Send OTP</button>
                            </div>
                            <div class="input-group mt-2 d-none" id="asnEmailOtpWrap">
                                <input type="text" class="form-control" id="asn_email_otp" maxlength="6" placeholder="Enter OTP">
                                <button class="btn btn-success" type="button" id="verifyAsnEmailOtpBtn">Verify</button>
                            </div>
                            <small id="asnEmailStatus" class="text-muted"></small>
                        </div>
                    </div>
                </div>

                <h6 class="mb-3">Authorized Signatory Details</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Name (as per PAN) <span class="req">*</span></label>
                        <input type="text" class="form-control" id="sign_name" name="irinn_sign_name" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date of Birth <span class="req">*</span></label>
                        <input type="date" class="form-control" id="sign_dob" name="irinn_sign_dob" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">PAN Number <span class="req">*</span></label>
                        <input type="text" class="form-control" id="sign_pan" name="irinn_sign_pan" maxlength="10" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email ID <span class="req">*</span></label>
                        <div class="input-group">
                            <input type="email" class="form-control" id="sign_email" name="irinn_sign_email" required>
                            <button class="btn btn-primary" type="button" id="sendSignEmailOtpBtn">Send OTP</button>
                        </div>
                        <div class="input-group mt-2 d-none" id="signEmailOtpWrap">
                            <input type="text" class="form-control" id="sign_email_otp" maxlength="6" placeholder="Enter OTP">
                            <button class="btn btn-success" type="button" id="verifySignEmailOtpBtn">Verify</button>
                        </div>
                        <small id="signEmailStatus" class="text-muted"></small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mobile <span class="req">*</span></label>
                        <div class="input-group">
                            <input type="tel" class="form-control" id="sign_mobile" name="irinn_sign_mobile" maxlength="10" required>
                            <button class="btn btn-primary" type="button" id="sendSignMobileOtpBtn">Send OTP</button>
                        </div>
                        <div class="input-group mt-2 d-none" id="signMobileOtpWrap">
                            <input type="text" class="form-control" id="sign_mobile_otp" maxlength="6" placeholder="Enter OTP">
                            <button class="btn btn-success" type="button" id="verifySignMobileOtpBtn">Verify</button>
                        </div>
                        <small id="signMobileStatus" class="text-muted"></small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Signature Proof Document <span class="req">*</span></label>
                        @include('user.applications.irin.partials.resubmit-doc-hint', ['docColumn' => 'irinn_signature_proof_path'])
                        <input type="file" class="form-control" id="signature_proof_file" name="irinn_signature_proof" accept=".pdf,.jpg,.jpeg,.png,.webp" @if(empty($isNormalizedResubmission)) required @endif>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Board Resolution Document <span class="req">*</span></label>
                        @include('user.applications.irin.partials.resubmit-doc-hint', ['docColumn' => 'irinn_board_resolution_path'])
                        <input type="file" class="form-control" id="board_resolution_file_step6" name="irinn_board_resolution" accept=".pdf,.jpg,.jpeg,.png,.webp" @if(empty($isNormalizedResubmission)) required @endif>
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-secondary" id="backStep5Btn">Back</button>
                    <button type="button" class="btn btn-primary" id="goStep7Btn">Next</button>
                </div>
            </div>

            <div id="step7" class="flow-step">
                <h5 class="mb-3">Step 7: KYC Documents</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Network Diagram <span class="req">*</span></label>
                        @include('user.applications.irin.partials.resubmit-doc-hint', ['docColumn' => 'irinn_kyc_network_diagram_path'])
                        <input type="file" class="form-control" id="kyc_network_diagram_file" name="irinn_kyc_network_diagram" accept=".pdf,.jpg,.jpeg,.png,.webp" @if(empty($isNormalizedResubmission)) required @endif>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Equipment Invoices <span class="req">*</span></label>
                        @include('user.applications.irin.partials.resubmit-doc-hint', ['docColumn' => 'irinn_kyc_equipment_invoice_path'])
                        <input type="file" class="form-control" id="kyc_equipment_invoice_file" name="irinn_kyc_equipment_invoice" accept=".pdf,.jpg,.jpeg,.png,.webp" @if(empty($isNormalizedResubmission)) required @endif>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bandwidth Proof <span class="req">*</span></label>
                        @include('user.applications.irin.partials.resubmit-doc-hint', ['docColumn' => 'irinn_kyc_bandwidth_proof_path'])
                        <input type="file" class="form-control" id="kyc_bandwidth_proof_file" name="irinn_kyc_bandwidth_proof" accept=".pdf,.jpg,.jpeg,.png,.webp" @if(empty($isNormalizedResubmission)) required @endif>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">IRINN Agreement Copy <span class="req">*</span></label>
                        <small class="text-muted d-block mb-1">Use the Standard IRINN Affiliation Agreement template (download below). Sign, stamp, add witness details, then upload.</small>
                        @include('user.applications.irin.partials.resubmit-doc-hint', ['docColumn' => 'irinn_kyc_irinn_agreement_path'])
                        <input type="file" class="form-control" id="kyc_irinn_agreement_copy_file" name="irinn_kyc_irinn_agreement" accept=".pdf,.jpg,.jpeg,.png,.webp" @if(empty($isNormalizedResubmission)) required @endif>
                    </div>
                </div>

                @if(!empty($isNormalizedResubmission) && isset($application))
                    @php
                        $otherDocRows = [];
                        for ($oi = 1; $oi <= 5; $oi++) {
                            $op = $application->{"irinn_other_doc_{$oi}_path"};
                            if (filled($op)) {
                                $otherDocRows[] = [
                                    'slot' => $oi,
                                    'label' => $application->{"irinn_other_doc_{$oi}_label"},
                                    'pathColumn' => "irinn_other_doc_{$oi}_path",
                                ];
                            }
                        }
                    @endphp
                    @if(count($otherDocRows) > 0)
                        <div class="border rounded p-3 mt-3 bg-light">
                            <h6 class="mb-2 small fw-semibold text-uppercase text-muted">Optional documents you added before</h6>
                            <p class="small text-muted mb-2">These stay on your application unless you add replacements in &ldquo;Other documents&rdquo; below.</p>
                            <ul class="small mb-0 ps-3">
                                @foreach($otherDocRows as $row)
                                    <li class="mb-1">
                                        @if(filled($row['label']))
                                            <span class="fw-medium">{{ $row['label'] }}</span>
                                        @else
                                            <span class="fw-medium">Additional document {{ $row['slot'] }}</span>
                                        @endif
                                        &mdash;
                                        <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => $row['pathColumn']]) }}" target="_blank" rel="noopener noreferrer">View current file</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endif

                <div class="mt-3">
                    <a href="{{ route('user.applications.irin.download-agreement') }}" class="btn btn-outline-primary" target="_blank">
                        Download Standard IRINN Agreement (prefilled)
                    </a>
                </div>

                <div class="border rounded p-3 mt-4">
                    <h6 class="mb-1">Other documents <span class="text-muted fw-normal">(optional)</span></h6>
                    <p class="small text-muted mb-3">Add up to 5 extra supporting files if needed (e.g. letters, certificates, clarifications).</p>
                    <div id="kycOtherDocumentsList"></div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addKycOtherDocumentBtn">
                        <i class="bi bi-plus-lg"></i> Add other document
                    </button>
                    <small class="text-muted ms-2" id="kycOtherDocumentsCountHint"></small>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-secondary" id="backStep6Btn">Back</button>
                    <button type="button" class="btn btn-success" id="submitIrinApplicationBtn">Submit application</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const cinTypes = ['private_limited', 'public_limited', 'llp', 'opc', 'psu'];
const udyamTypes = ['partnership', 'proprietor'];
const docTypes = ['government', 'ngo', 'academia_institute', 'trust'];
let isCinFlow = false;
let mcaRepresentativeList = [];
const verifiedEmails = new Set();
const verifiedMobiles = new Set();
const verificationState = {
    mr: { email: false, mobile: false },
    app: { email: false, mobile: false },
    abuse: { email: false, mobile: false },
    br: { email: false, mobile: false },
    asn: { email: false, mobile: false },
    sign: { email: false, mobile: false }
};
const IRINN_RESUBMIT_MODE = @json(!empty($isNormalizedResubmission));
const IRINN_PREFILL = @json($irinnNormalizedPrefill ?? []);
if (IRINN_RESUBMIT_MODE) {
    ['mr', 'app', 'abuse', 'br', 'asn', 'sign'].forEach((k) => {
        verificationState[k] = { email: true, mobile: true };
    });
}
let maxUnlockedStep = 7;
let selectedIPv4Resource = null;
let selectedIPv6Resource = null;
let resourcePool = { ipv4: [], ipv6: [] };
let fullMrDesignationOptionsHtml = '';
const KYC_OTHER_DOCUMENT_MAX = 5;
let kycOtherDocSlotCount = 0;

function updateKycOtherDocUi() {
    const btn = document.getElementById('addKycOtherDocumentBtn');
    const hint = document.getElementById('kycOtherDocumentsCountHint');
    if (!btn || !hint) {
        return;
    }
    const remaining = KYC_OTHER_DOCUMENT_MAX - kycOtherDocSlotCount;
    btn.disabled = kycOtherDocSlotCount >= KYC_OTHER_DOCUMENT_MAX;
    hint.textContent = kycOtherDocSlotCount > 0
        ? `${kycOtherDocSlotCount} of ${KYC_OTHER_DOCUMENT_MAX} added${remaining > 0 ? ` — ${remaining} remaining` : ''}`
        : `Up to ${KYC_OTHER_DOCUMENT_MAX} additional files.`;
}

function appendKycOtherDocumentRow() {
    if (kycOtherDocSlotCount >= KYC_OTHER_DOCUMENT_MAX) {
        return;
    }
    const list = document.getElementById('kycOtherDocumentsList');
    if (!list) {
        return;
    }
    const row = document.createElement('div');
    row.className = 'row g-2 align-items-end mb-3 kyc-other-doc-row';
    row.innerHTML = `
        <div class="col-md-5 col-12">
            <label class="form-label small text-muted mb-1">Document title (optional)</label>
            <input type="text" class="form-control form-control-sm" name="kyc_other_document_label[]" placeholder="e.g. NOC, supplementary certificate">
        </div>
        <div class="col-md-6 col-12">
            <label class="form-label small mb-1">File</label>
            <input type="file" class="form-control form-control-sm" name="kyc_other_document_file[]" accept=".pdf,.jpg,.jpeg,.png,.webp">
        </div>
        <div class="col-md-auto col-12 d-flex align-items-end pb-1">
            <button type="button" class="btn btn-outline-danger btn-sm remove-kyc-other-doc" title="Remove this row">Remove</button>
        </div>
    `;
    list.appendChild(row);
    kycOtherDocSlotCount++;
    updateKycOtherDocUi();
}

function getSectionValues(prefix) {
    return {
        name: document.getElementById(prefix + '_name')?.value || '',
        designation: document.getElementById(prefix + '_designation')?.value || '',
        email: document.getElementById(prefix + '_email')?.value || '',
        mobile: document.getElementById(prefix + '_mobile')?.value || ''
    };
}

function setSectionValues(prefix, values, makeReadonly = false) {
    const name = document.getElementById(prefix + '_name');
    const designation = document.getElementById(prefix + '_designation');
    const email = document.getElementById(prefix + '_email');
    const mobile = document.getElementById(prefix + '_mobile');
    if (name) {
        name.value = values.name || '';
        name.readOnly = makeReadonly;
        name.classList.toggle('readonly-like', makeReadonly);
    }
    if (designation) {
        designation.value = values.designation || '';
        designation.disabled = makeReadonly;
        designation.classList.toggle('readonly-like', makeReadonly);
    }
    if (email) {
        email.value = values.email || '';
        email.readOnly = makeReadonly;
        email.classList.toggle('readonly-like', makeReadonly);
    }
    if (mobile) {
        mobile.value = values.mobile || '';
        mobile.readOnly = makeReadonly;
        mobile.classList.toggle('readonly-like', makeReadonly);
    }
}

function cloneDesignationOptions(optionsHtml, targetIds) {
    if (!optionsHtml) return;
    targetIds.forEach((id) => {
        const target = document.getElementById(id);
        if (target) target.innerHTML = optionsHtml;
    });
}

function getManagementRepresentativeName() {
    return (document.getElementById('mr_name').value || '').trim();
}

function syncManagementRepresentativeUi(prevIsCinFlow) {
    const repWrap = document.getElementById('repSelectWrap');
    const desig = document.getElementById('mr_designation');
    const mrName = document.getElementById('mr_name');
    const repSelect = document.getElementById('management_rep_select');
    const dinHidden = document.getElementById('mr_director_din');

    if (isCinFlow) {
        repWrap.style.display = '';
        desig.innerHTML = '<option value="Director" selected>Director</option>';
        desig.disabled = true;
        desig.classList.add('readonly-like');
        if (!prevIsCinFlow) {
            repSelect.innerHTML = '<option value="">Select director</option>';
            mcaRepresentativeList = [];
            mrName.value = '';
            dinHidden.value = '';
            mrName.readOnly = false;
            mrName.classList.remove('readonly-like');
        }
    } else {
        repWrap.style.display = 'none';
        repSelect.innerHTML = '<option value="">Select director</option>';
        mcaRepresentativeList = [];
        desig.disabled = false;
        desig.classList.remove('readonly-like');
        desig.innerHTML = fullMrDesignationOptionsHtml;
        mrName.readOnly = false;
        mrName.classList.remove('readonly-like');
        mrName.value = '';
        dinHidden.value = '';
    }
}

function irinnOtpButtonPrefix(prefix) {
    const map = { mr: 'Mr', app: 'App', abuse: 'Abuse', br: 'Br', asn: 'Asn', sign: 'Sign' };
    return map[prefix] || (prefix.charAt(0).toUpperCase() + prefix.slice(1));
}

function applyIrinnEmailVerifiedUi(fieldPrefix) {
    const emailInput = document.getElementById(fieldPrefix + '_email');
    const sendBtn = document.getElementById('send' + irinnOtpButtonPrefix(fieldPrefix) + 'EmailOtpBtn');
    const wrap = document.getElementById(fieldPrefix + 'EmailOtpWrap');
    const otpInput = document.getElementById(fieldPrefix + '_email_otp');
    const status = document.getElementById(fieldPrefix + 'EmailStatus');
    if (emailInput) {
        emailInput.readOnly = true;
        emailInput.classList.add('readonly-like');
    }
    if (sendBtn) sendBtn.classList.add('d-none');
    if (wrap) wrap.classList.add('d-none');
    if (otpInput) otpInput.value = '';
    if (status) status.textContent = 'Verified.';
    if (status) status.classList.remove('text-danger');
    if (status) status.classList.add('text-success');
}

function resetIrinnEmailOtpUi(fieldPrefix) {
    const emailInput = document.getElementById(fieldPrefix + '_email');
    const sendBtn = document.getElementById('send' + irinnOtpButtonPrefix(fieldPrefix) + 'EmailOtpBtn');
    const wrap = document.getElementById(fieldPrefix + 'EmailOtpWrap');
    const otpInput = document.getElementById(fieldPrefix + '_email_otp');
    const status = document.getElementById(fieldPrefix + 'EmailStatus');
    if (emailInput) {
        emailInput.readOnly = false;
        emailInput.classList.remove('readonly-like');
    }
    if (sendBtn) sendBtn.classList.remove('d-none');
    if (wrap) wrap.classList.add('d-none');
    if (otpInput) otpInput.value = '';
    if (status) {
        status.textContent = '';
        status.classList.remove('text-success', 'text-danger');
    }
}

function applyIrinnMobileVerifiedUi(fieldPrefix) {
    const mobileInput = document.getElementById(fieldPrefix + '_mobile');
    const sendBtn = document.getElementById('send' + irinnOtpButtonPrefix(fieldPrefix) + 'MobileOtpBtn');
    const wrap = document.getElementById(fieldPrefix + 'MobileOtpWrap');
    const otpInput = document.getElementById(fieldPrefix + '_mobile_otp');
    const status = document.getElementById(fieldPrefix + 'MobileStatus');
    if (mobileInput) {
        mobileInput.readOnly = true;
        mobileInput.classList.add('readonly-like');
    }
    if (sendBtn) sendBtn.classList.add('d-none');
    if (wrap) wrap.classList.add('d-none');
    if (otpInput) otpInput.value = '';
    if (status) status.textContent = 'Verified.';
    if (status) status.classList.remove('text-danger');
    if (status) status.classList.add('text-success');
}

function resetIrinnMobileOtpUi(fieldPrefix) {
    const mobileInput = document.getElementById(fieldPrefix + '_mobile');
    const sendBtn = document.getElementById('send' + irinnOtpButtonPrefix(fieldPrefix) + 'MobileOtpBtn');
    const wrap = document.getElementById(fieldPrefix + 'MobileOtpWrap');
    const otpInput = document.getElementById(fieldPrefix + '_mobile_otp');
    const status = document.getElementById(fieldPrefix + 'MobileStatus');
    if (mobileInput) {
        mobileInput.readOnly = false;
        mobileInput.classList.remove('readonly-like');
    }
    if (sendBtn) sendBtn.classList.remove('d-none');
    if (wrap) wrap.classList.add('d-none');
    if (otpInput) otpInput.value = '';
    if (status) {
        status.textContent = '';
        status.classList.remove('text-success', 'text-danger');
    }
}

function wireOtp(sectionKey, fieldPrefix) {
    const emailInput = document.getElementById(fieldPrefix + '_email');
    const mobileInput = document.getElementById(fieldPrefix + '_mobile');
    const p = irinnOtpButtonPrefix(fieldPrefix);
    const sendEmailBtn = document.getElementById('send' + p + 'EmailOtpBtn');
    const verifyEmailBtn = document.getElementById('verify' + p + 'EmailOtpBtn');
    const sendMobileBtn = document.getElementById('send' + p + 'MobileOtpBtn');
    const verifyMobileBtn = document.getElementById('verify' + p + 'MobileOtpBtn');
    const emailOtpWrap = document.getElementById(fieldPrefix + 'EmailOtpWrap');
    const mobileOtpWrap = document.getElementById(fieldPrefix + 'MobileOtpWrap');
    const emailOtpInput = document.getElementById(fieldPrefix + '_email_otp');
    const mobileOtpInput = document.getElementById(fieldPrefix + '_mobile_otp');
    const emailStatus = document.getElementById(fieldPrefix + 'EmailStatus');
    const mobileStatus = document.getElementById(fieldPrefix + 'MobileStatus');

    if (emailInput) {
        emailInput.addEventListener('input', () => {
            verificationState[sectionKey].email = false;
            resetIrinnEmailOtpUi(fieldPrefix);
        });
    }
    if (mobileInput) {
        mobileInput.addEventListener('input', () => {
            verificationState[sectionKey].mobile = false;
            resetIrinnMobileOtpUi(fieldPrefix);
        });
    }

    if (sendEmailBtn) {
        sendEmailBtn.addEventListener('click', async () => {
            const email = (emailInput.value || '').trim().toLowerCase();
            if (!email) { alert('Enter email first.'); return; }
            if (verifiedEmails.has(email)) {
                verificationState[sectionKey].email = true;
                applyIrinnEmailVerifiedUi(fieldPrefix);
                emailStatus.textContent = 'Already verified for this address.';
                return;
            }
            emailStatus.classList.remove('text-success', 'text-danger');
            const res = await fetch('{{ route("user.applications.irin.flow.send-email-otp") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ email })
            });
            const data = await res.json().catch(() => ({}));
            if (data.success) {
                emailOtpWrap?.classList.remove('d-none');
                emailStatus.textContent = 'OTP sent to your email. Enter it below.';
                emailStatus.classList.add('text-success');
            } else {
                emailStatus.textContent = data.message || 'Failed to send OTP.';
                emailStatus.classList.add('text-danger');
            }
        });
    }

    if (verifyEmailBtn) {
        verifyEmailBtn.addEventListener('click', async () => {
            const email = (emailInput.value || '').trim().toLowerCase();
            const otp = (emailOtpInput?.value || '').trim();
            if (otp.length !== 6) { alert('Enter the 6-digit OTP.'); return; }
            const res = await fetch('{{ route("user.applications.irin.flow.verify-email-otp") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ email, otp })
            });
            const data = await res.json().catch(() => ({}));
            verificationState[sectionKey].email = !!data.success;
            if (data.success) {
                verifiedEmails.add(email);
                applyIrinnEmailVerifiedUi(fieldPrefix);
            } else {
                emailStatus.textContent = data.message || 'Verification failed.';
                emailStatus.classList.remove('text-success');
                emailStatus.classList.add('text-danger');
            }
        });
    }

    if (sendMobileBtn) {
        sendMobileBtn.addEventListener('click', async () => {
            const mobile = (mobileInput.value || '').trim().replace(/\D/g, '').slice(0, 10);
            if (mobile.length !== 10) { alert('Enter valid 10-digit mobile.'); return; }
            if (verifiedMobiles.has(mobile)) {
                verificationState[sectionKey].mobile = true;
                applyIrinnMobileVerifiedUi(fieldPrefix);
                mobileStatus.textContent = 'Already verified for this number.';
                return;
            }
            mobileStatus.classList.remove('text-success', 'text-danger');
            const res = await fetch('{{ route("user.applications.irin.flow.send-mobile-otp") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ mobile })
            });
            const data = await res.json().catch(() => ({}));
            if (data.success) {
                mobileOtpWrap?.classList.remove('d-none');
                const shown = data.otp ? (' (dev only) OTP: ' + data.otp) : '';
                mobileStatus.textContent = (data.message || 'Enter the OTP sent to your mobile.') + shown;
                mobileStatus.classList.add('text-success');
            } else {
                mobileStatus.textContent = data.message || 'Failed to generate OTP.';
                mobileStatus.classList.add('text-danger');
            }
        });
    }

    if (verifyMobileBtn) {
        verifyMobileBtn.addEventListener('click', async () => {
            const mobile = (mobileInput.value || '').trim().replace(/\D/g, '').slice(0, 10);
            const otp = (mobileOtpInput?.value || '').trim();
            if (otp.length !== 6) { alert('Enter the 6-digit OTP.'); return; }
            const res = await fetch('{{ route("user.applications.irin.flow.verify-mobile-otp") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ mobile, otp })
            });
            const data = await res.json().catch(() => ({}));
            verificationState[sectionKey].mobile = !!data.success;
            if (data.success) {
                verifiedMobiles.add(mobile);
                applyIrinnMobileVerifiedUi(fieldPrefix);
            } else {
                mobileStatus.textContent = data.message || 'Verification failed.';
                mobileStatus.classList.remove('text-success');
                mobileStatus.classList.add('text-danger');
            }
        });
    }
}

function nameSimilarityPercent(a, b) {
    const s1 = (a || '').trim().toLowerCase();
    const s2 = (b || '').trim().toLowerCase();
    if (!s1 || !s2) return 0;
    if (s1 === s2) return 100;
    const longer = Math.max(s1.length, s2.length);
    let same = 0;
    for (let i = 0; i < Math.min(s1.length, s2.length); i++) {
        if (s1[i] === s2[i]) same++;
    }
    return Math.round((same / longer) * 100);
}

function autoGenerateAccountName() {
    const org = document.getElementById('organisation_name').value || '';
    const words = org.toUpperCase().replace(/[^A-Z\s]/g, ' ').split(/\s+/).filter(Boolean);
    const initials = words.map(w => w[0]).join('').slice(0, 8) || 'ORG';
    document.getElementById('account_name').value = `${initials}-IND`;
}

function setReadonlyByClass(className, readonly) {
    document.querySelectorAll('.' + className).forEach(el => {
        el.readOnly = readonly;
        el.classList.toggle('readonly-like', readonly);
    });
}

function applyCinVerifiedUi() {
    const cinInput = document.getElementById('cin_number');
    const btn = document.getElementById('verifyCinBtn');
    if (cinInput) {
        cinInput.readOnly = true;
        cinInput.classList.add('readonly-like');
    }
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Verified';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-success');
    }
}

function resetCinVerifyUi() {
    const cinInput = document.getElementById('cin_number');
    const btn = document.getElementById('verifyCinBtn');
    if (cinInput) {
        cinInput.readOnly = false;
        cinInput.classList.remove('readonly-like');
    }
    if (btn) {
        btn.disabled = false;
        btn.textContent = 'Verify CIN';
        btn.classList.add('btn-primary');
        btn.classList.remove('btn-success');
    }
}

function applyGstVerifiedUi() {
    const gstInput = document.getElementById('billing_gstin');
    const btn = document.getElementById('verifyBillingGstBtn');
    if (gstInput) {
        gstInput.readOnly = true;
        gstInput.classList.add('readonly-like');
    }
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Verified';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-success');
    }
}

function resetGstVerifyUi() {
    const gstInput = document.getElementById('billing_gstin');
    const btn = document.getElementById('verifyBillingGstBtn');
    if (gstInput) {
        gstInput.readOnly = false;
        gstInput.classList.remove('readonly-like');
    }
    if (btn) {
        btn.disabled = false;
        btn.textContent = 'Verify GST';
        btn.classList.add('btn-primary');
        btn.classList.remove('btn-success');
    }
}

function toggleCompanyTypeUI() {
    const t = document.getElementById('company_type').value;
    const prevIsCinFlow = isCinFlow;
    isCinFlow = cinTypes.includes(t);
    document.getElementById('cinWrap').style.display = isCinFlow ? '' : 'none';
    document.getElementById('udyamWrap').style.display = udyamTypes.includes(t) ? '' : 'none';
    document.getElementById('regDocWrap').style.display = docTypes.includes(t) ? '' : 'none';
    if (! isCinFlow) {
        resetCinVerifyUi();
    }
    syncManagementRepresentativeUi(prevIsCinFlow);
}

function toggleGstUI() {
    const hasGst = document.getElementById('has_gst_number').checked;
    document.getElementById('gstBillingWrap').style.display = hasGst ? '' : 'none';
    document.getElementById('nonGstBillingWrap').style.display = hasGst ? 'none' : '';
    if (! hasGst) {
        resetGstVerifyUi();
    }
}

async function verifyMca() {
    const cin = document.getElementById('cin_number').value.trim();
    if (!cin) { alert('Enter CIN number first.'); return; }
    const status = document.getElementById('cinStatus');
    status.textContent = 'Starting verification...';

    const startRes = await fetch('{{ route("user.applications.irin.verify-mca") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ cin })
    });
    const startData = await startRes.json();
    if (!startData.success) { status.textContent = startData.message || 'Verification failed.'; return; }

    document.getElementById('mca_verification_request_id').value = startData.request_id;
    status.textContent = 'Verification in progress...';

    let attempts = 0;
    const timer = setInterval(async () => {
        attempts++;
        const pollRes = await fetch('{{ route("user.applications.irin.check-verification-status") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ type: 'mca', request_id: startData.request_id })
        });
        const pollData = await pollRes.json();
        if (pollData.status === 'completed') {
            clearInterval(timer);
            if (pollData.is_verified) {
                status.textContent = 'CIN verified successfully.';
                applyCinVerifiedUi();
                const v = pollData.verification_data || {};
                if (v.company_name) document.getElementById('organisation_name').value = v.company_name;
                const orgAddr = v.primary_address || v.company_address || '';
                if (orgAddr) document.getElementById('organisation_address').value = orgAddr;
                if (v.postcode) document.getElementById('organisation_postcode').value = v.postcode;
                if (v.industry_type) document.getElementById('industry_type').value = v.industry_type;
                setReadonlyByClass('org-field', true);
                autoGenerateAccountName();

                const srcOut = v.source_output || {};
                const rawDirectors = Array.isArray(v.directors) ? v.directors : (Array.isArray(srcOut.directors) ? srcOut.directors : []);
                const activeDirectors = rawDirectors.filter((d) => d && (d.end_date === null || d.end_date === undefined || String(d.end_date).trim() === ''));
                mcaRepresentativeList = activeDirectors;
                const select = document.getElementById('management_rep_select');
                select.innerHTML = '<option value="">Select director</option>';
                mcaRepresentativeList.forEach((r, i) => {
                    const opt = document.createElement('option');
                    opt.value = String(i);
                    const nm = (r.name || '').trim();
                    const din = (r.din || '').trim();
                    opt.textContent = nm ? (din ? `${nm} (DIN: ${din})` : nm) : `Director ${i + 1}`;
                    select.appendChild(opt);
                });

                if (cinTypes.includes(document.getElementById('company_type').value)) {
                    const desig = document.getElementById('mr_designation');
                    desig.innerHTML = '<option value="Director" selected>Director</option>';
                    desig.disabled = true;
                    desig.classList.add('readonly-like');
                }

                const mrNameEl = document.getElementById('mr_name');
                mrNameEl.value = '';
                document.getElementById('mr_director_din').value = '';
                mrNameEl.readOnly = mcaRepresentativeList.length > 0;
                mrNameEl.classList.toggle('readonly-like', mcaRepresentativeList.length > 0);
            } else {
                status.textContent = pollData.message || 'CIN verification failed.';
            }
        } else if (pollData.status === 'failed' || attempts > 15) {
            clearInterval(timer);
            status.textContent = 'CIN verification timed out/failed.';
        }
    }, 2000);
}

async function verifyBillingGst() {
    const gstin = document.getElementById('billing_gstin').value.trim().toUpperCase();
    if (gstin.length !== 15) { alert('Enter valid 15-char GST number.'); return; }
    const status = document.getElementById('gstBillingStatus');
    status.textContent = 'Starting GST verification...';

    const startRes = await fetch('{{ route("user.applications.irin.verify-gst") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ gstin })
    });
    const startData = await startRes.json();
    if (!startData.success) { status.textContent = startData.message || 'GST verify failed.'; return; }

    document.getElementById('gst_verification_request_id').value = startData.request_id;
    let attempts = 0;
    const timer = setInterval(async () => {
        attempts++;
        const pollRes = await fetch('{{ route("user.applications.irin.check-verification-status") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ type: 'gstin', request_id: startData.request_id })
        });
        const pollData = await pollRes.json();
        if (pollData.status === 'completed') {
            clearInterval(timer);
            if (pollData.is_verified) {
                status.textContent = 'GST verified successfully.';
                applyGstVerifiedUi();
                const v = pollData.verification_data || {};
                if (v.company_name || v.legal_name) document.getElementById('billing_legal_name').value = v.company_name || v.legal_name;
                if (v.pan) document.getElementById('billing_pan_number').value = v.pan;
                if (v.primary_address) document.getElementById('billing_address').value = v.primary_address;
                if (v.postcode) document.getElementById('billing_postcode').value = v.postcode;
                setReadonlyByClass('bill-field', true);
            } else {
                status.textContent = pollData.message || 'GST verification failed.';
            }
        } else if (pollData.status === 'failed' || attempts > 15) {
            clearInterval(timer);
            status.textContent = 'GST verification timed out/failed.';
        }
    }, 2000);
}

function goStep(step) {
    if (step > maxUnlockedStep) {
        return;
    }
    document.getElementById('step1').classList.toggle('active', step === 1);
    document.getElementById('step2').classList.toggle('active', step === 2);
    document.getElementById('step3').classList.toggle('active', step === 3);
    document.getElementById('step4').classList.toggle('active', step === 4);
    document.getElementById('step5').classList.toggle('active', step === 5);
    document.getElementById('step6').classList.toggle('active', step === 6);
    document.getElementById('step7').classList.toggle('active', step === 7);
    document.getElementById('s1Badge').className = step === 1 ? 'badge bg-primary' : 'badge bg-success';
    document.getElementById('s2Badge').className = step === 2 ? 'badge bg-primary' : (step > 2 ? 'badge bg-success' : 'badge bg-secondary');
    document.getElementById('s3Badge').className = step === 3 ? 'badge bg-primary' : (step > 3 ? 'badge bg-success' : 'badge bg-secondary');
    document.getElementById('s4Badge').className = step === 4 ? 'badge bg-primary' : (step > 4 ? 'badge bg-success' : 'badge bg-secondary');
    document.getElementById('s5Badge').className = step === 5 ? 'badge bg-primary' : (step > 5 ? 'badge bg-success' : 'badge bg-secondary');
    document.getElementById('s6Badge').className = step === 6 ? 'badge bg-primary' : (step > 6 ? 'badge bg-success' : 'badge bg-secondary');
    document.getElementById('s7Badge').className = step === 7 ? 'badge bg-primary' : 'badge bg-secondary';
}

function calcIPv4Fee(addresses) {
    return 27500 * Math.pow(1.35, Math.log10(addresses) - 8);
}

function calcIPv6Fee(addresses) {
    return 24199 * Math.pow(1.35, Math.log10(addresses) - 22);
}

function fmtInr(v) {
    return '₹ ' + Number(v || 0).toLocaleString('en-IN', { maximumFractionDigits: 2, minimumFractionDigits: 2 });
}

function renderResourceList() {
    const ipv4Host = document.getElementById('ipv4ResourceList');
    const ipv6Host = document.getElementById('ipv6ResourceList');
    ipv4Host.innerHTML = '';
    ipv6Host.innerHTML = '';
    resourcePool.ipv4.forEach((item, idx) => {
        const id = 'ipv4_res_' + idx;
        const wrap = document.createElement('div');
        wrap.className = 'form-check';
        wrap.innerHTML = `<input class="form-check-input" type="radio" name="ipv4_resource_pick" id="${id}" value="${idx}">
            <label class="form-check-label" for="${id}">${item.size} (${item.addresses} addresses)</label>`;
        ipv4Host.appendChild(wrap);
    });
    resourcePool.ipv6.forEach((item, idx) => {
        const id = 'ipv6_res_' + idx;
        const wrap = document.createElement('div');
        wrap.className = 'form-check';
        wrap.innerHTML = `<input class="form-check-input" type="radio" name="ipv6_resource_pick" id="${id}" value="${idx}">
            <label class="form-check-label" for="${id}">${item.size} (${item.addresses} addresses)</label>`;
        ipv6Host.appendChild(wrap);
    });

    document.querySelectorAll('input[name="ipv4_resource_pick"]').forEach((el) => {
        el.addEventListener('change', () => {
            selectedIPv4Resource = resourcePool.ipv4[Number(el.value)];
            updateResourceBilling();
        });
    });
    document.querySelectorAll('input[name="ipv6_resource_pick"]').forEach((el) => {
        el.addEventListener('change', () => {
            selectedIPv6Resource = resourcePool.ipv6[Number(el.value)];
            updateResourceBilling();
        });
    });
}

function updateResourceBilling() {
    const host = document.getElementById('resourceBillingBreakup');
    const finalEl = document.getElementById('finalBillingAmount');
    let html = '';
    let fee4 = null;
    let fee6 = null;
    if (selectedIPv4Resource) {
        fee4 = calcIPv4Fee(Number(selectedIPv4Resource.addresses || 0));
        html += `<div>IPv4 ${selectedIPv4Resource.size}: ${fmtInr(fee4)}</div>`;
    }
    if (selectedIPv6Resource) {
        fee6 = calcIPv6Fee(Number(selectedIPv6Resource.addresses || 0));
        html += `<div>IPv6 ${selectedIPv6Resource.size}: ${fmtInr(fee6)}</div>`;
    }
    host.innerHTML = html || '<div class="text-muted">Select at least one resource.</div>';
    const final = fee4 !== null && fee6 !== null ? Math.max(fee4, fee6) : (fee4 ?? fee6 ?? 0);
    finalEl.textContent = fmtInr(final);
}

async function fetchResourcesForStep5() {
    try {
        const res = await fetch('{{ route("user.applications.irin.pricing") }}');
        const data = await res.json();
        if (!data.success || !data.data) {
            return;
        }
        const raw4 = data.data.ipv4 || data.data.IPv4 || {};
        const raw6 = data.data.ipv6 || data.data.IPv6 || {};
        resourcePool.ipv4 = Object.keys(raw4).map((size) => ({ size, ...(raw4[size] || {}) }))
            .filter((x) => x.viewInForm !== false);
        resourcePool.ipv6 = Object.keys(raw6).map((size) => ({ size, ...(raw6[size] || {}) }))
            .filter((x) => x.viewInForm !== false);
        renderResourceList();
    } catch (e) {
        // keep silent
    }
}

function applyIrinnNormalizedPrefill(prefill) {
    if (!prefill || typeof prefill !== 'object') {
        return;
    }
    Object.keys(prefill).forEach((name) => {
        const val = prefill[name];
        if (val === null || val === undefined) {
            return;
        }
        const el = document.querySelector('[name="' + String(name).replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"]');
        if (!el) {
            return;
        }
        if (el.type === 'checkbox') {
            el.checked = val === true || val === 1 || val === '1';
        } else {
            el.value = val === false ? '' : String(val);
        }
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    });
    const size4 = prefill.irinn_ipv4_resource_size;
    const addr4 = prefill.irinn_ipv4_resource_addresses;
    if (size4 && resourcePool.ipv4.length) {
        const idx = resourcePool.ipv4.findIndex((it) => it.size === size4 && String(it.addresses) === String(addr4));
        if (idx >= 0) {
            const radio = document.getElementById('ipv4_res_' + idx);
            if (radio) {
                radio.checked = true;
                selectedIPv4Resource = resourcePool.ipv4[idx];
            }
        }
    }
    const size6 = prefill.irinn_ipv6_resource_size;
    const addr6 = prefill.irinn_ipv6_resource_addresses;
    if (size6 && resourcePool.ipv6.length) {
        const idx = resourcePool.ipv6.findIndex((it) => it.size === size6 && String(it.addresses) === String(addr6));
        if (idx >= 0) {
            const radio = document.getElementById('ipv6_res_' + idx);
            if (radio) {
                radio.checked = true;
                selectedIPv6Resource = resourcePool.ipv6[idx];
            }
        }
    }
    updateResourceBilling();
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('company_type').addEventListener('change', toggleCompanyTypeUI);
    document.getElementById('has_gst_number').addEventListener('change', toggleGstUI);
    document.getElementById('organisation_name').addEventListener('input', autoGenerateAccountName);
    document.getElementById('verifyCinBtn').addEventListener('click', verifyMca);
    document.getElementById('verifyBillingGstBtn').addEventListener('click', verifyBillingGst);

    document.getElementById('management_rep_select').addEventListener('change', (e) => {
        const idx = e.target.value;
        const mrNameEl = document.getElementById('mr_name');
        const dinHidden = document.getElementById('mr_director_din');
        if (idx === '') {
            if (mcaRepresentativeList.length > 0) {
                mrNameEl.value = '';
                mrNameEl.readOnly = true;
                mrNameEl.classList.add('readonly-like');
            }
            dinHidden.value = '';
            return;
        }
        const rep = mcaRepresentativeList[Number(idx)] || {};
        mrNameEl.value = (rep.name || '').trim();
        mrNameEl.readOnly = true;
        mrNameEl.classList.add('readonly-like');
        dinHidden.value = (rep.din || '').trim();
    });

    fullMrDesignationOptionsHtml = document.getElementById('mr_designation').innerHTML;
    cloneDesignationOptions(fullMrDesignationOptionsHtml, ['app_designation', 'abuse_designation', 'br_designation']);
    wireOtp('mr', 'mr');
    wireOtp('app', 'app');
    wireOtp('abuse', 'abuse');
    wireOtp('br', 'br');
    wireOtp('asn', 'asn');
    wireOtp('sign', 'sign');

    document.getElementById('goStep2Btn').addEventListener('click', () => {
        const org = document.getElementById('organisation_name').value.trim();
        const legal = document.getElementById('billing_legal_name').value.trim();
        const match = nameSimilarityPercent(org, legal);
        const mismatch = document.getElementById('nameMatchStatus');
        if (match < 70) {
            mismatch.classList.remove('d-none');
            return;
        }
        mismatch.classList.add('d-none');
        goStep(2);
    });

    document.getElementById('goStep3Btn').addEventListener('click', () => {
        if (!getManagementRepresentativeName()) {
            alert('Please enter or select the management representative name.');
            return;
        }
        if (isCinFlow) {
            const repSel = document.getElementById('management_rep_select');
            if (mcaRepresentativeList.length > 0 && repSel.value === '') {
                alert('Please select a director from the CIN list.');
                return;
            }
        } else if (!document.getElementById('mr_designation').value) {
            alert('Please select designation.');
            return;
        }
        if (!IRINN_RESUBMIT_MODE && (!verificationState.mr.email || !verificationState.mr.mobile)) {
            alert('Please complete Email and Mobile OTP verification in Step 2.');
            return;
        }
        verifiedEmails.add((document.getElementById('mr_email').value || '').trim().toLowerCase());
        verifiedMobiles.add((document.getElementById('mr_mobile').value || '').trim().replace(/\D/g, '').slice(0, 10));
        maxUnlockedStep = Math.max(maxUnlockedStep, 3);
        goStep(3);
    });

    document.getElementById('goStep4Btn').addEventListener('click', () => {
        if (!IRINN_RESUBMIT_MODE && (!verificationState.app.email || !verificationState.app.mobile)) {
            alert('Please complete Technical Person email/mobile verification.');
            return;
        }
        if (!IRINN_RESUBMIT_MODE && (!verificationState.abuse.email || !verificationState.abuse.mobile)) {
            alert('Please complete Abuse Contact email/mobile verification.');
            return;
        }
        maxUnlockedStep = Math.max(maxUnlockedStep, 4);
        goStep(4);
    });

    function copyProfile(targetPrefix, sourceValues, sourceVerification, makeReadonly = true) {
        setSectionValues(targetPrefix, sourceValues, makeReadonly);
        if (sourceVerification.email) {
            verificationState[targetPrefix].email = true;
            verifiedEmails.add((sourceValues.email || '').trim().toLowerCase());
            applyIrinnEmailVerifiedUi(targetPrefix);
            const elE = document.getElementById(targetPrefix + 'EmailStatus');
            if (elE) {
                elE.textContent = 'Verified (same contact as previous step).';
                elE.classList.remove('text-danger');
                elE.classList.add('text-success');
            }
        } else {
            resetIrinnEmailOtpUi(targetPrefix);
        }
        if (sourceVerification.mobile) {
            verificationState[targetPrefix].mobile = true;
            verifiedMobiles.add((sourceValues.mobile || '').trim().replace(/\D/g, '').slice(0, 10));
            applyIrinnMobileVerifiedUi(targetPrefix);
            const elM = document.getElementById(targetPrefix + 'MobileStatus');
            if (elM) {
                elM.textContent = 'Verified (same contact as previous step).';
                elM.classList.remove('text-danger');
                elM.classList.add('text-success');
            }
        } else {
            resetIrinnMobileOtpUi(targetPrefix);
        }
    }

    document.getElementById('appSameAsMgmtBtn').addEventListener('click', () => {
        copyProfile('app', {
            name: getManagementRepresentativeName(),
            designation: document.getElementById('mr_designation').value,
            email: document.getElementById('mr_email').value,
            mobile: document.getElementById('mr_mobile').value
        }, verificationState.mr, true);
    });

    document.getElementById('abuseSameAsMgmtBtn').addEventListener('click', () => {
        copyProfile('abuse', {
            name: getManagementRepresentativeName(),
            designation: document.getElementById('mr_designation').value,
            email: document.getElementById('mr_email').value,
            mobile: document.getElementById('mr_mobile').value
        }, verificationState.mr, true);
    });

    document.getElementById('abuseSameAsApplicantBtn').addEventListener('click', () => {
        copyProfile('abuse', getSectionValues('app'), verificationState.app, true);
    });

    document.getElementById('brSameAsMgmtBtn').addEventListener('click', () => {
        copyProfile('br', {
            name: getManagementRepresentativeName(),
            designation: document.getElementById('mr_designation').value,
            email: document.getElementById('mr_email').value,
            mobile: document.getElementById('mr_mobile').value
        }, verificationState.mr, true);
    });

    document.getElementById('brSameAsApplicantBtn').addEventListener('click', () => {
        copyProfile('br', getSectionValues('app'), verificationState.app, true);
    });

    document.getElementById('brSameAsAbuseBtn').addEventListener('click', () => {
        copyProfile('br', getSectionValues('abuse'), verificationState.abuse, true);
    });

    document.getElementById('goStep5Btn').addEventListener('click', () => {
        if (!IRINN_RESUBMIT_MODE && (!verificationState.br.email || !verificationState.br.mobile)) {
            alert('Please complete Billing Representative email/mobile verification.');
            return;
        }
        maxUnlockedStep = Math.max(maxUnlockedStep, 5);
        goStep(5);
    });

    document.getElementById('goStep6Btn').addEventListener('click', () => {
        if (!selectedIPv4Resource && !selectedIPv6Resource) {
            alert('Please select at least one resource from IPv4 or IPv6.');
            return;
        }
        maxUnlockedStep = Math.max(maxUnlockedStep, 6);
        goStep(6);
    });

    document.getElementById('goStep7Btn').addEventListener('click', () => {
        if (!document.getElementById('asn_name').value.trim() || !document.getElementById('asn_number').value.trim()) {
            alert('Please complete Upstream Provider Name and AS Number.');
            return;
        }
        if (!IRINN_RESUBMIT_MODE && (!verificationState.asn.email || !verificationState.asn.mobile)) {
            alert('Please complete Upstream Provider email and mobile OTP verification.');
            return;
        }
        if (!IRINN_RESUBMIT_MODE && (!verificationState.sign.email || !verificationState.sign.mobile)) {
            alert('Please complete Authorized Signatory email/mobile verification.');
            return;
        }
        if (!IRINN_RESUBMIT_MODE && (!document.getElementById('signature_proof_file').files.length || !document.getElementById('board_resolution_file_step6').files.length)) {
            alert('Please upload signature proof and board resolution documents.');
            return;
        }
        maxUnlockedStep = Math.max(maxUnlockedStep, 7);
        goStep(7);
    });

    document.getElementById('submitIrinApplicationBtn').addEventListener('click', async () => {
        if (!IRINN_RESUBMIT_MODE) {
            const required = [
                'kyc_network_diagram_file',
                'kyc_equipment_invoice_file',
                'kyc_bandwidth_proof_file',
                'kyc_irinn_agreement_copy_file'
            ];
            const missing = required.some((id) => !document.getElementById(id).files.length);
            if (missing) {
                alert('Please upload all mandatory KYC documents in Step 7.');
                return;
            }
        }

        const btn = document.getElementById('submitIrinApplicationBtn');
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Submitting...';

        try {
            const formEl = document.getElementById('irinnFlowForm');
            const syncHost = document.getElementById('irinnFlowHiddenSync');
            if (syncHost) {
                syncHost.innerHTML = '';
                const addSyncHidden = (name, value) => {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = name;
                    inp.value = value == null ? '' : String(value);
                    syncHost.appendChild(inp);
                };
                addSyncHidden('irinn_ipv4_resource_size', selectedIPv4Resource?.size ?? '');
                addSyncHidden('irinn_ipv4_resource_addresses', selectedIPv4Resource?.addresses != null ? String(selectedIPv4Resource.addresses) : '');
                addSyncHidden('irinn_ipv6_resource_size', selectedIPv6Resource?.size ?? '');
                addSyncHidden('irinn_ipv6_resource_addresses', selectedIPv6Resource?.addresses != null ? String(selectedIPv6Resource.addresses) : '');
                const feeNum = (document.getElementById('finalBillingAmount').textContent || '0').replace(/[^\d.]/g, '');
                addSyncHidden('irinn_resource_fee_amount', feeNum || '');
            }
            // Disabled <select> fields are omitted from FormData; "Same as …" uses disabled on designation selects.
            const designationSelectIds = ['mr_designation', 'app_designation', 'abuse_designation', 'br_designation'];
            const designationSelectsToRestore = [];
            designationSelectIds.forEach((id) => {
                const el = document.getElementById(id);
                if (el && el.tagName === 'SELECT' && el.disabled) {
                    el.disabled = false;
                    designationSelectsToRestore.push(el);
                }
            });
            const formData = new FormData(formEl);
            designationSelectsToRestore.forEach((el) => {
                el.disabled = true;
            });

            const res = await fetch('{{ route("user.applications.irin.store-new") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: formData
            });

            const data = await res.json();
            if (!res.ok || !data.success) {
                let msg = data.message || 'Submission failed. Please check required fields and try again.';
                if (data.errors && typeof data.errors === 'object') {
                    const lines = Object.values(data.errors).flat().filter(Boolean);
                    if (lines.length > 1) {
                        msg = lines.slice(0, 10).join('\n');
                    }
                }
                alert(msg);
                return;
            }

            if (data.redirect_url) {
                window.location.href = data.redirect_url;
                return;
            }

            alert(data.message || 'Application submitted successfully.');
        } catch (error) {
            alert('Unable to submit right now. Please try again.');
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    });

    document.getElementById('backStep1Btn').addEventListener('click', () => goStep(1));
    document.getElementById('backStep2Btn').addEventListener('click', () => goStep(2));
    document.getElementById('backStep3Btn').addEventListener('click', () => goStep(3));
    document.getElementById('backStep4Btn').addEventListener('click', () => goStep(4));
    document.getElementById('backStep5Btn').addEventListener('click', () => goStep(5));
    document.getElementById('backStep6Btn').addEventListener('click', () => goStep(6));
    document.getElementById('s1Badge').addEventListener('click', () => goStep(1));
    document.getElementById('s2Badge').addEventListener('click', () => goStep(2));
    document.getElementById('s3Badge').addEventListener('click', () => goStep(3));
    document.getElementById('s4Badge').addEventListener('click', () => goStep(4));
    document.getElementById('s5Badge').addEventListener('click', () => goStep(5));
    document.getElementById('s6Badge').addEventListener('click', () => goStep(6));
    document.getElementById('s7Badge').addEventListener('click', () => goStep(7));

    document.getElementById('addKycOtherDocumentBtn')?.addEventListener('click', appendKycOtherDocumentRow);
    document.getElementById('kycOtherDocumentsList')?.addEventListener('click', (e) => {
        if (e.target.closest('.remove-kyc-other-doc')) {
            e.target.closest('.kyc-other-doc-row')?.remove();
            kycOtherDocSlotCount = Math.max(0, kycOtherDocSlotCount - 1);
            updateKycOtherDocUi();
        }
    });
    updateKycOtherDocUi();

    (async () => {
        await fetchResourcesForStep5();
        if (IRINN_RESUBMIT_MODE) {
            applyIrinnNormalizedPrefill(IRINN_PREFILL);
        }
        toggleCompanyTypeUI();
        toggleGstUI();
    })();
});
</script>
@endpush
