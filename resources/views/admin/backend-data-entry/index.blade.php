@extends('admin.layout')

@section('title', 'Backend Data Entry')
@section('content')
<style>
    .theme-forms .form-control[type=file] {
        min-height: auto;
    }
</style>
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-2">Backend Data Entry</h2>
            <p class="mb-0">Register user and create application (No emails will be sent)</p>
            <div class="accent-line"></div>
        </div>
    </div>
    @if(session('success') && session('credentials'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <h5 class="alert-heading">Success!</h5>
        <p>{{ session('success') }}</p>
        <hr>
        <h6 class="fw-semibold">Login Credentials (Please save these):</h6>
        <div class="bg-light p-3 rounded mb-2">
            <p class="mb-1"><strong>Registration ID:</strong> {{ session('credentials.registration_id') }}</p>
            <p class="mb-1"><strong>Email:</strong> {{ session('credentials.email') }}</p>
            <p class="mb-1"><strong>Password:</strong> <code>{{ session('credentials.password') }}</code></p>
            <p class="mb-1"><strong>Email OTP:</strong> <code>{{ session('credentials.email_otp') }}</code></p>
            <p class="mb-1"><strong>Mobile OTP:</strong> <code>{{ session('credentials.mobile_otp') }}</code></p>
            <p class="mb-0"><strong>Application ID(s):</strong> 
                @if(is_array(session('credentials.application_ids')))
                    {{ implode(', ', session('credentials.application_ids')) }}
                @else
                    {{ session('credentials.application_id') ?? 'N/A' }}
                @endif
            </p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.backend-data-entry.store') }}" enctype="multipart/form-data" id="backendDataEntryForm" class="theme-forms">
        @csrf
        <input type="hidden" name="email_otp" id="emailOtpInput" value="">
        <input type="hidden" name="mobile_otp" id="mobileOtpInput" value="">
        <input type="hidden" name="generated_password" id="generatedPasswordInput" value="">

        <!-- Registration Section -->
        <div class="card border-c-blue shadow-sm mb-4" style="border-radius: 16px;">
            <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                <h5 class="mb-0">User Registration Details</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Registration Type</label>
                        <div class="d-flex flex-wrap gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="registration_type" id="reg_type_entity" value="entity" {{ old('registration_type', 'entity') === 'entity' ? 'checked' : '' }}>
                                <label class="form-check-label" for="reg_type_entity">Entity</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="registration_type" id="reg_type_individual" value="individual" {{ old('registration_type') === 'individual' ? 'checked' : '' }}>
                                <label class="form-check-label" for="reg_type_individual">Individual</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="fullname" class="form-label" id="fullnameLabel">Full Name (As per PAN)</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" value="{{ old('fullname') }}">
                    </div>

                    <div class="col-md-6">
                        <label for="dateofbirth" class="form-label" id="dateofbirthLabel">Date of Birth (As per PAN)</label>
                        <input type="date" class="form-control" id="dateofbirth" name="dateofbirth" value="{{ old('dateofbirth') }}">
                    </div>

                    <div class="col-md-6">
                        <label for="pancardno" class="form-label">PAN Number</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="pancardno" name="pancardno" value="{{ old('pancardno') }}" placeholder="ABCDE1234F" maxlength="10">
                            <button type="button" class="btn btn-primary" id="verifyPanBtn" onclick="verifyPan()">Verify PAN</button>
                        </div>
                        <div id="panVerificationStatus" class="mt-2" style="display: none;"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="text" class="form-control" id="email" name="email" value="{{ old('email') }}">
                        <div id="emailOtpDisplay" class="mt-2">
                            <small class="text-muted"><strong>Email OTP (will be generated):</strong> <code id="emailOtpValue" class="text-primary">---</code></small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="mobile" class="form-label">Mobile Number</label>
                        <input type="text" class="form-control" id="mobile" name="mobile" value="{{ old('mobile') }}" placeholder="10-digit mobile number" maxlength="10">
                        <div id="mobileOtpDisplay" class="mt-2">
                            <small class="text-muted"><strong>Mobile OTP (will be generated):</strong> <code id="mobileOtpValue" class="text-primary">---</code></small>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <strong>Note:</strong> OTPs and password will be generated automatically and displayed below the fields. No emails will be sent. These credentials will be shown again after successful submission.
                        </div>
                        <div class="bg-light p-3 rounded">
                            <p class="mb-1"><strong>Generated Password (will be shown):</strong> <code id="generatedPassword" class="text-primary">---</code></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Applications Section -->
        <div class="card border-c-blue shadow-sm mb-4" style="border-radius: 16px;">
            <div class="card-header theme-bg-blue text-white d-flex justify-content-between align-items-center flex-wrap" style="border-radius: 16px 16px 0 0;">
                <h5 class="mb-2">IX Applications</h5>
                <button type="button" class="btn text-white fw-semibold text-capitaliz border-white" onclick="addApplication()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                    </svg>
                    Add Application
                </button>
            </div>
            <div class="card-body p-3">
                <div id="applicationsContainer">
                    <!-- Application 1 will be added here by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Application Template (Hidden) -->
        <template id="applicationTemplate">
            <div class="application-item card border mb-3" data-app-index="0">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Application <span class="app-number">1</span></h6>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeApplication(this)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
                        </svg>
                        Remove
                    </button>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Member Type -->
                        <div class="col-md-6">
                            <label class="form-label">Member Type</label>
                            <select name="applications[0][member_type]" class="form-select member-type-select">
                                <option value="">Select Member Type</option>
                                <option value="isp">ISP</option>
                                <option value="cdn">CDN</option>
                                <option value="vno">VNO</option>
                                <option value="govt">Government Entity</option>
                                <option value="others">Others</option>
                            </select>
                            <input type="text" name="applications[0][member_type_other]" class="form-control mt-2 d-none member-type-other" placeholder="Specify member type">
                        </div>

                        <!-- Location -->
                        <div class="col-md-6">
                            <label class="form-label">NIXI Location</label>
                            <select name="applications[0][location_id]" class="form-select location-select">
                                <option value="">Select Location</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" data-node-type="{{ $location->node_type }}">
                                        {{ $location->name }} ({{ ucfirst($location->node_type) }} - {{ $location->state }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Representative Details -->
                        <div class="col-12">
                            <h6 class="mb-3 text-primary mt-3">Authorized Representative Details</h6>
                            <div class="accent-line shadow" style="height: 1px;"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Representative Name</label>
                            <input type="text" name="applications[0][representative_name]" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Representative PAN</label>
                            <input type="text" name="applications[0][representative_pan]" class="form-control" placeholder="ABCDE1234F" maxlength="10">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Representative Date of Birth</label>
                            <input type="date" name="applications[0][representative_dob]" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Representative Email</label>
                            <input type="text" name="applications[0][representative_email]" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Representative Mobile</label>
                            <input type="text" name="applications[0][representative_mobile]" class="form-control" placeholder="10-digit mobile number" maxlength="10">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">GSTIN</label>
                            <input type="text" name="applications[0][gstin]" class="form-control" placeholder="15-character GSTIN" maxlength="15">
                            <small class="text-muted">GSTIN will be auto-verified for backend entry</small>
                        </div>

                        <!-- Port & Billing -->
                        <div class="col-12">
                            <h6 class="mb-3 text-primary mt-3">Port & Billing Details</h6>
                            <div class="accent-line shadow" style="height: 1px;"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Port Capacity</label>
                            <select name="applications[0][port_capacity]" class="form-select port-capacity-select">
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
                            <label class="form-label">Billing Plan</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="applications[0][billing_plan]" value="arc">
                                    <label class="form-check-label">Annual (ARC)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="applications[0][billing_plan]" value="mrc">
                                    <label class="form-check-label">Monthly (MRC)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="applications[0][billing_plan]" value="quarterly">
                                    <label class="form-check-label">Quarterly</label>
                                </div>
                            </div>
                        </div>

                        <!-- IP Prefix -->
                        <div class="col-12">
                            <h6 class="mb-3 text-primary mt-3">IP Prefix Information</h6>
                            <div class="accent-line shadow" style="height: 1px;"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Number of IP Prefixes</label>
                            <input type="number" name="applications[0][ip_prefix_count]" class="form-control" min="1" max="500">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">IP Prefix Allocation Source</label>
                            <select name="applications[0][ip_prefix_source]" class="form-select ip-prefix-source-select">
                                <option value="">Select Source</option>
                                <option value="irinn">IRINN</option>
                                <option value="apnic">APNIC</option>
                                <option value="others">Others</option>
                            </select>
                        </div>

                        <div class="col-md-4 d-none ip-prefix-provider-wrapper">
                            <label class="form-label">Provider Name</label>
                            <input type="text" name="applications[0][ip_prefix_provider]" class="form-control" placeholder="Enter provider">
                        </div>

                        <!-- Peering Connectivity -->
                        <div class="col-12">
                            <h6 class="mb-3 text-primary mt-3">Peering Connectivity</h6>
                            <div class="accent-line shadow" style="height: 1px;"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Member's Pre-NIXI peering connectivity</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="applications[0][pre_peering_connectivity]" value="none">
                                    <label class="form-check-label">None</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="applications[0][pre_peering_connectivity]" value="single">
                                    <label class="form-check-label">Single</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="applications[0][pre_peering_connectivity]" value="multiple">
                                    <label class="form-check-label">Multiple</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">AS Number used for peering in the NIXI</label>
                            <input type="text" name="applications[0][asn_number]" class="form-control" placeholder="e.g., AS131269">
                        </div>

                        <!-- Router Details -->
                        <div class="col-12">
                            <h6 class="mb-3 text-primary mt-3">Dedicated Router Details (Optional)</h6>
                            <div class="accent-line shadow" style="height: 1px;"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Height in U</label>
                            <input type="number" name="applications[0][router_height_u]" class="form-control" min="1" max="50">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Make & Model</label>
                            <input type="text" name="applications[0][router_make_model]" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Serial Number</label>
                            <input type="text" name="applications[0][router_serial_number]" class="form-control">
                        </div>

                        <!-- Documents -->
                        <div class="col-12">
                            <h6 class="mb-3 text-primary mt-3">Documents (Optional)</h6>
                            <p class="text-muted small">Upload clear PDF copies. Maximum size per document is 10 MB. Documents can be uploaded later through the application update module.</p>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Signed Agreement with NIXI</label>
                            <input type="file" name="applications[0][agreement_file]" class="form-control" accept="application/pdf">
                        </div>

                        <div class="col-md-6 isp-license-container" style="display: none;">
                            <label class="form-label">ISP License</label>
                            <input type="file" name="applications[0][license_isp_file]" class="form-control" accept="application/pdf">
                        </div>

                        <div class="col-md-6 vno-license-container" style="display: none;">
                            <label class="form-label">VNO License</label>
                            <input type="file" name="applications[0][license_vno_file]" class="form-control" accept="application/pdf">
                        </div>

                        <div class="col-md-6 cdn-declaration-container" style="display: none;">
                            <label class="form-label">CDN Declaration</label>
                            <input type="file" name="applications[0][cdn_declaration_file]" class="form-control" accept="application/pdf">
                        </div>

                        <div class="col-md-6 general-declaration-container" style="display: none;">
                            <label class="form-label">General Declaration</label>
                            <input type="file" name="applications[0][general_declaration_file]" class="form-control" accept="application/pdf">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Whois Details</label>
                            <input type="file" name="applications[0][whois_details_file]" class="form-control" accept="application/pdf">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">PAN Document</label>
                            <input type="file" name="applications[0][pan_document_file]" class="form-control" accept="application/pdf">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">GSTIN Document</label>
                            <input type="file" name="applications[0][gstin_document_file]" class="form-control" accept="application/pdf">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">MSME (Udyog/Udyam) Certificate</label>
                            <input type="file" name="applications[0][msme_document_file]" class="form-control" accept="application/pdf">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Certificate of Incorporation</label>
                            <input type="file" name="applications[0][incorporation_document_file]" class="form-control" accept="application/pdf">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Authorized Representative Document</label>
                            <input type="file" name="applications[0][authorized_rep_document_file]" class="form-control" accept="application/pdf">
                        </div>
                    </div>
                </div>
            </template>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-danger text-capitalize">Cancel</a>
            <button type="submit" class="btn btn-primary text-capitalize">Register User & Create Applications</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Generate OTPs and password immediately for display
    function generateOtp() {
        return String(Math.floor(100000 + Math.random() * 900000));
    }

    function generatePassword() {
        const length = 12;
        const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const lowercase = 'abcdefghijklmnopqrstuvwxyz';
        const numbers = '0123456789';
        const special = '!@#$%^&*';
        const all = uppercase + lowercase + numbers + special;

        let password = '';
        password += uppercase[Math.floor(Math.random() * uppercase.length)];
        password += lowercase[Math.floor(Math.random() * lowercase.length)];
        password += numbers[Math.floor(Math.random() * numbers.length)];
        password += special[Math.floor(Math.random() * special.length)];

        for (let i = 4; i < length; i++) {
            password += all[Math.floor(Math.random() * all.length)];
        }

        return password.split('').sort(() => Math.random() - 0.5).join('');
    }

    // Generate and display OTPs and password on page load
    let emailOtp = generateOtp();
    let mobileOtp = generateOtp();
    let generatedPassword = generatePassword();

    document.getElementById('emailOtpValue').textContent = emailOtp;
    document.getElementById('mobileOtpValue').textContent = mobileOtp;
    document.getElementById('generatedPassword').textContent = generatedPassword;

    // Regenerate on input change
    document.getElementById('email').addEventListener('input', function() {
        if (this.value) {
            emailOtp = generateOtp();
            document.getElementById('emailOtpValue').textContent = emailOtp;
            document.getElementById('emailOtpInput').value = emailOtp;
        }
    });

    document.getElementById('mobile').addEventListener('input', function() {
        if (this.value) {
            mobileOtp = generateOtp();
            document.getElementById('mobileOtpValue').textContent = mobileOtp;
            document.getElementById('mobileOtpInput').value = mobileOtp;
        }
    });

    // Update hidden inputs on form submit
    document.getElementById('backendDataEntryForm').addEventListener('submit', function() {
        document.getElementById('emailOtpInput').value = emailOtp;
        document.getElementById('mobileOtpInput').value = mobileOtp;
        document.getElementById('generatedPasswordInput').value = generatedPassword;
    });

    // Initialize hidden inputs
    document.getElementById('emailOtpInput').value = emailOtp;
    document.getElementById('mobileOtpInput').value = mobileOtp;
    document.getElementById('generatedPasswordInput').value = generatedPassword;

    // PAN Verification (optional - no validation)
    async function verifyPan() {
        const panNo = document.getElementById('pancardno').value.trim().toUpperCase();
        const fullName = document.getElementById('fullname').value.trim();
        const dob = document.getElementById('dateofbirth').value;

        // All fields are optional - only verify if all are provided
        if (!panNo || !fullName || !dob) {
            const statusDiv = document.getElementById('panVerificationStatus');
            statusDiv.style.display = 'block';
            statusDiv.className = 'alert alert-info';
            statusDiv.innerHTML = '<i class="bi bi-info-circle"></i> PAN verification skipped (fields optional).';
            return;
        }

        const btn = document.getElementById('verifyPanBtn');
        const statusDiv = document.getElementById('panVerificationStatus');
        
        btn.disabled = true;
        btn.textContent = 'Verifying...';
        statusDiv.style.display = 'block';
        statusDiv.className = 'alert alert-info';
        statusDiv.innerHTML = '<i class="bi bi-hourglass-split"></i> Verifying PAN...';

        try {
            const response = await fetch('{{ route("admin.backend-data-entry.verify-pan") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    pancardno: panNo,
                    fullname: fullName,
                    dateofbirth: dob
                })
            });

            const data = await response.json();

            if (data.success) {
                statusDiv.className = 'alert alert-success';
                statusDiv.innerHTML = '<i class="bi bi-check-circle"></i> PAN verified successfully!';
            } else {
                statusDiv.className = 'alert alert-danger';
                statusDiv.innerHTML = '<i class="bi bi-x-circle"></i> ' + (data.message || 'PAN verification failed');
            }
        } catch (error) {
            statusDiv.className = 'alert alert-danger';
            statusDiv.innerHTML = '<i class="bi bi-x-circle"></i> Error verifying PAN: ' + error.message;
        } finally {
            btn.disabled = false;
            btn.textContent = 'Verify PAN';
        }
    }

    // Add new application
    function addApplication() {
        const template = document.getElementById('applicationTemplate');
        const container = document.getElementById('applicationsContainer');
        const clone = template.content.cloneNode(true);
        
        // Get the next sequential index based on existing applications
        const existingApps = container.querySelectorAll('.application-item');
        const appIndex = existingApps.length; // 0-based index, so length gives us the next index
        
        // Update all field names with new index
        clone.querySelectorAll('[name]').forEach(field => {
            const name = field.getAttribute('name');
            if (name && name.startsWith('applications[0]')) {
                field.setAttribute('name', name.replace('applications[0]', `applications[${appIndex}]`));
            }
        });
        
        // Update IDs to be unique (for radio buttons and other elements)
        clone.querySelectorAll('[id]').forEach(el => {
            if (el.id) {
                el.id = el.id + '_' + appIndex;
            }
        });
        
        // Update 'for' attributes in labels
        clone.querySelectorAll('label[for]').forEach(label => {
            const forAttr = label.getAttribute('for');
            if (forAttr) {
                label.setAttribute('for', forAttr + '_' + appIndex);
            }
        });
        
        // Update data attribute
        clone.querySelector('.application-item').setAttribute('data-app-index', appIndex);
        clone.querySelector('.app-number').textContent = appIndex + 1;
        
        container.appendChild(clone);
        
        // Initialize handlers for new application
        initializeApplicationHandlers(container.lastElementChild, appIndex);
    }

    // Remove application
    function removeApplication(btn) {
        const container = document.getElementById('applicationsContainer');
        const appItem = btn.closest('.application-item');
        if (container.querySelectorAll('.application-item').length > 1) {
            appItem.remove();
            renumberApplications();
        } else {
            alert('At least one application is required.');
        }
    }

    // Renumber all applications sequentially and update field names
    function renumberApplications() {
        const container = document.getElementById('applicationsContainer');
        const applications = container.querySelectorAll('.application-item');
        
        applications.forEach((appItem, index) => {
            // Update the display number
            appItem.querySelector('.app-number').textContent = index + 1;
            
            // Update data attribute
            appItem.setAttribute('data-app-index', index);
            
            // Get the old index from the first field name
            const firstField = appItem.querySelector('[name]');
            if (firstField) {
                const oldName = firstField.getAttribute('name');
                const oldIndexMatch = oldName.match(/applications\[(\d+)\]/);
                if (oldIndexMatch) {
                    const oldIndex = oldIndexMatch[1];
                    
                    // Update all field names to use the new sequential index
                    appItem.querySelectorAll('[name]').forEach(field => {
                        const name = field.getAttribute('name');
                        if (name && name.includes(`applications[${oldIndex}]`)) {
                            field.setAttribute('name', name.replace(`applications[${oldIndex}]`, `applications[${index}]`));
                        }
                    });
                }
            }
        });
    }

    // Initialize handlers for an application
    function initializeApplicationHandlers(appElement, appIndex) {
        // Member Type handler
        const memberTypeSelect = appElement.querySelector('.member-type-select');
        if (memberTypeSelect) {
            memberTypeSelect.addEventListener('change', function() {
                const memberType = this.value;
                const appItem = this.closest('.application-item');
                const ispContainer = appItem.querySelector('.isp-license-container');
                const vnoContainer = appItem.querySelector('.vno-license-container');
                const cdnContainer = appItem.querySelector('.cdn-declaration-container');
                const generalContainer = appItem.querySelector('.general-declaration-container');
                const memberTypeOther = appItem.querySelector('.member-type-other');

                // Hide all first
                if (ispContainer) ispContainer.style.display = 'none';
                if (vnoContainer) vnoContainer.style.display = 'none';
                if (cdnContainer) cdnContainer.style.display = 'none';
                if (generalContainer) generalContainer.style.display = 'none';
                if (memberTypeOther) memberTypeOther.classList.add('d-none');

                // Show relevant fields
                if (memberType === 'isp' && ispContainer) {
                    ispContainer.style.display = 'block';
                } else if (memberType === 'vno' && vnoContainer) {
                    vnoContainer.style.display = 'block';
                } else if (memberType === 'cdn' && cdnContainer) {
                    cdnContainer.style.display = 'block';
                } else if (memberType !== 'isp' && memberType !== 'vno' && memberType !== 'cdn' && generalContainer) {
                    generalContainer.style.display = 'block';
                }

                if (memberType === 'others' && memberTypeOther) {
                    memberTypeOther.classList.remove('d-none');
                }
            });
        }

        // IP Prefix Source handler
        const ipPrefixSource = appElement.querySelector('.ip-prefix-source-select');
        if (ipPrefixSource) {
            ipPrefixSource.addEventListener('change', function() {
                const appItem = this.closest('.application-item');
                const providerWrapper = appItem.querySelector('.ip-prefix-provider-wrapper');
                if (this.value === 'others' && providerWrapper) {
                    providerWrapper.classList.remove('d-none');
                } else if (providerWrapper) {
                    providerWrapper.classList.add('d-none');
                }
            });
        }
    }

    // Initialize first application on page load
    document.addEventListener('DOMContentLoaded', function() {
        addApplication();
    });

    // Registration type change handler
    document.querySelectorAll('input[name="registration_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const fullnameLabel = document.getElementById('fullnameLabel');
            const dateofbirthLabel = document.getElementById('dateofbirthLabel');
            
            if (this.value === 'individual') {
                fullnameLabel.textContent = 'Full Name (As per PAN) *';
                dateofbirthLabel.textContent = 'Date of Birth (As per PAN) *';
            } else {
                fullnameLabel.textContent = 'Entity Name (As per PAN) *';
                dateofbirthLabel.textContent = 'Date of Incorporation (As per PAN) *';
            }
        });
    });
</script>
@endpush
@endsection
