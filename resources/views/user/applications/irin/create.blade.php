@extends('user.layout')

@section('title', 'IRINN Application Form')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Varela+Round&display=swap');
    
    /* Reset and Base Styles */
    * {
        font-family: 'Varela Round', sans-serif !important;
        box-sizing: border-box !important;
    }
    
    html, body {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        height: 100% !important;
        overflow-x: hidden !important;
        font-family: 'Varela Round', sans-serif !important;
    }
    
    body {
        background: #ffffff !important;
        color: #000000 !important;
        font-family: 'Varela Round', sans-serif !important;
        overflow-y: auto !important;
    }
    
    .registration-wrapper {
        width: 100% !important;
        min-height: calc(100vh - 56px) !important;
        display: flex !important;
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        position: relative !important;
        margin-top: 0 !important;
    }
    
    /* Left Sidebar - Steps */
    .steps-sidebar {
        width: 300px !important;
        min-width: 300px !important;
        background: #ffffff !important;
        color: #000000 !important;
        padding: 35px 20px !important;
        position: fixed !important;
        left: 0 !important;
        top: 56px !important;
        height: calc(100vh - 56px) !important;
        overflow-y: auto !important;
        box-shadow: 2px 0 10px rgba(0,0,0,0.05) !important;
        z-index: 1000 !important;
        border-right: 1px solid #e8e8e8 !important;
    }
    
    .steps-sidebar h2 {
        color: #DC2626 !important;
        font-size: 20px !important;
        font-weight: 600 !important;
        margin-bottom: 35px !important;
        text-align: center !important;
        padding-bottom: 15px !important;
        border-bottom: 2px solid #DC2626 !important;
        font-family: 'Varela Round', sans-serif !important;
        letter-spacing: 0.5px !important;
    }
    
    .step-item {
        display: flex !important;
        align-items: center !important;
        padding: 12px 10px !important;
        margin-bottom: 8px !important;
        border-radius: 8px !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        border: 1px solid transparent !important;
        position: relative !important;
        background: #ffffff !important;
    }
    
    .step-item:hover:not(.disabled) {
        background: rgba(220, 38, 38, 0.05) !important;
        border-color: rgba(220, 38, 38, 0.2) !important;
        transform: translateX(3px) !important;
    }
    
    .step-item.active {
        background: rgba(220, 38, 38, 0.1) !important;
        color: #DC2626 !important;
        border-color: #DC2626 !important;
        box-shadow: 0 2px 8px rgba(220, 38, 38, 0.15) !important;
    }
    
    .step-item.completed {
        background: rgba(220, 38, 38, 0.05) !important;
        border-color: rgba(220, 38, 38, 0.2) !important;
    }
    
    .step-item.disabled {
        opacity: 0.4 !important;
        cursor: not-allowed !important;
    }
    
    .step-icon {
        width: 36px !important;
        height: 36px !important;
        min-width: 36px !important;
        border-radius: 50% !important;
        background: rgba(220, 38, 38, 0.1) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin-right: 10px !important;
        font-size: 16px !important;
        transition: all 0.2s ease !important;
        color: #DC2626 !important;
    }
    
    .step-item.active .step-icon {
        background: #DC2626 !important;
        color: #ffffff !important;
        transform: scale(1.05) !important;
    }
    
    .step-item.completed .step-icon {
        background: rgba(220, 38, 38, 0.15) !important;
        color: #DC2626 !important;
    }
    
    .step-info {
        flex: 1 !important;
    }
    
    .step-number {
        font-size: 10px !important;
        opacity: 0.6 !important;
        margin-bottom: 2px !important;
        font-family: 'Varela Round', sans-serif !important;
        color: inherit !important;
        letter-spacing: 0.3px !important;
    }
    
    .step-item.active .step-number,
    .step-item.completed .step-number {
        opacity: 1 !important;
        font-weight: 600 !important;
    }
    
    .step-name {
        font-size: 12px !important;
        font-weight: 500 !important;
        line-height: 1.4 !important;
        font-family: 'Varela Round', sans-serif !important;
        color: inherit !important;
    }
    
    /* Right Content Area */
    .form-content-area {
        margin-left: 300px !important;
        flex: 1 !important;
        padding: 40px 50px !important;
        min-height: calc(100vh - 56px) !important;
        background: #fafafa !important;
        width: calc(100% - 300px) !important;
        margin-top: 0 !important;
        overflow-y: auto !important;
    }
    
    .form-header {
        margin-bottom: 35px !important;
    }
    
    .form-header h1 {
        font-size: 26px !important;
        font-weight: 600 !important;
        color: #1a1a1a !important;
        margin-bottom: 6px !important;
        font-family: 'Varela Round', sans-serif !important;
        letter-spacing: -0.3px !important;
    }
    
    .form-header p {
        font-size: 13px !important;
        color: #666 !important;
        margin: 0 !important;
        font-family: 'Varela Round', sans-serif !important;
    }
    
    .form-card {
        background: #ffffff !important;
        border: 1px solid #e8e8e8 !important;
        border-radius: 12px !important;
        padding: 32px !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04) !important;
        transition: all 0.2s ease !important;
        font-family: 'Varela Round', sans-serif !important;
    }
    
    .form-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.06) !important;
        border-color: #d1d1d1 !important;
    }
    
    .form-card h3 {
        color: #1a1a1a !important;
        font-size: 20px !important;
        font-weight: 600 !important;
        margin-bottom: 28px !important;
        padding-bottom: 12px !important;
        border-bottom: 2px solid #DC2626 !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        font-family: 'Varela Round', sans-serif !important;
    }
    
    .form-card h3 i {
        color: #DC2626 !important;
        font-size: 18px !important;
    }
    
    .form-group {
        margin-bottom: 18px !important;
    }
    
    .form-label {
        font-weight: 500 !important;
        color: #333333 !important;
        margin-bottom: 6px !important;
        display: block !important;
        font-size: 12px !important;
        font-family: 'Varela Round', sans-serif !important;
        letter-spacing: 0.2px !important;
    }
    
    .form-label .required {
        color: #DC2626 !important;
        margin-left: 3px !important;
    }
    
    .form-control, .form-select {
        border: 1px solid #d1d1d1 !important;
        border-radius: 6px !important;
        padding: 10px 12px !important;
        width: 100% !important;
        background: #ffffff !important;
        color: #1a1a1a !important;
        font-size: 13px !important;
        transition: all 0.2s ease !important;
        font-family: 'Varela Round', sans-serif !important;
    }
    
    .form-control:focus, .form-select:focus {
        outline: none !important;
        border-color: #DC2626 !important;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1) !important;
    }
    
    .form-control[readonly] {
        background-color: #f5f5f5 !important;
        cursor: not-allowed !important;
        opacity: 0.7 !important;
        border-color: #e0e0e0 !important;
    }
    
    .btn-primary {
        background-color: #DC2626 !important;
        border: 1px solid #DC2626 !important;
        color: #ffffff !important;
        padding: 10px 24px !important;
        border-radius: 6px !important;
        font-weight: 500 !important;
        font-size: 13px !important;
        transition: all 0.2s ease !important;
        cursor: pointer !important;
        box-shadow: 0 2px 6px rgba(220, 38, 38, 0.2) !important;
        font-family: 'Varela Round', sans-serif !important;
    }
    
    .btn-primary:hover {
        background-color: #B91C1C !important;
        border-color: #B91C1C !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 10px rgba(220, 38, 38, 0.3) !important;
        color: #ffffff !important;
    }
    
    .btn-primary:active {
        transform: translateY(0) !important;
    }
    
    .btn-secondary {
        background-color: #ffffff !important;
        border: 1px solid #d1d1d1 !important;
        color: #333333 !important;
        padding: 10px 24px !important;
        border-radius: 6px !important;
        font-weight: 500 !important;
        font-size: 13px !important;
        transition: all 0.2s ease !important;
        cursor: pointer !important;
        font-family: 'Varela Round', sans-serif !important;
    }
    
    .btn-secondary:hover {
        background-color: #f9f9f9 !important;
        border-color: #DC2626 !important;
        color: #DC2626 !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05) !important;
    }
    
    .btn-group {
        display: flex !important;
        gap: 12px !important;
        justify-content: space-between !important;
        margin-top: 30px !important;
        padding-top: 25px !important;
        border-top: 2px solid #f0f0f0 !important;
    }
    
    .radio-group {
        display: flex !important;
        gap: 25px !important;
        margin-top: 12px !important;
    }
    
    .radio-item {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        padding: 12px 20px !important;
        border: 2px solid #e0e0e0 !important;
        border-radius: 10px !important;
        cursor: pointer !important;
        transition: all 0.3s ease !important;
        flex: 1 !important;
    }
    
    .radio-item:hover {
        border-color: #DC2626 !important;
        background: rgba(220, 38, 38, 0.05) !important;
    }
    
    .radio-item input[type="radio"] {
        width: 18px !important;
        height: 18px !important;
        accent-color: #DC2626 !important;
        cursor: pointer !important;
    }
    
    .radio-item:has(input:checked) {
        border-color: #DC2626 !important;
        background: rgba(220, 38, 38, 0.08) !important;
    }
    
    .checkbox-group {
        margin-top: 15px !important;
    }
    
    .checkbox-item {
        margin-bottom: 20px !important;
        padding: 20px !important;
        border: 2px solid #e0e0e0 !important;
        border-radius: 12px !important;
        transition: all 0.3s ease !important;
    }
    
    .checkbox-item:hover {
        border-color: #DC2626 !important;
        box-shadow: 0 2px 6px rgba(220, 38, 38, 0.1) !important;
    }
    
    .checkbox-item input[type="checkbox"] {
        width: 18px !important;
        height: 18px !important;
        accent-color: #DC2626 !important;
        margin-right: 10px !important;
        cursor: pointer !important;
    }
    
    .checkbox-item:has(input:checked) {
        border-color: #DC2626 !important;
        background: rgba(220, 38, 38, 0.06) !important;
        box-shadow: 0 2px 6px rgba(220, 38, 38, 0.15) !important;
    }
    
    .payment-summary {
        background: rgba(220, 38, 38, 0.04) !important;
        border: 1px solid #DC2626 !important;
        border-radius: 8px !important;
        padding: 22px !important;
        margin-top: 20px !important;
    }
    
    .payment-summary h4 {
        color: #1a1a1a !important;
        margin-bottom: 16px !important;
        font-size: 16px !important;
        font-weight: 600 !important;
        font-family: 'Varela Round', sans-serif !important;
    }
    
    .summary-row {
        display: flex !important;
        justify-content: space-between !important;
        padding: 8px 0 !important;
        border-bottom: 1px solid rgba(0,0,0,0.06) !important;
        font-size: 12px !important;
        font-family: 'Varela Round', sans-serif !important;
    }
    
    .summary-row:last-child {
        border-bottom: none !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        margin-top: 6px !important;
        padding-top: 10px !important;
        border-top: 1px solid #DC2626 !important;
    }
    
    .success-message {
        background: rgba(220, 38, 38, 0.04) !important;
        border: 1px solid #DC2626 !important;
        border-radius: 10px !important;
        padding: 35px !important;
        text-align: center !important;
    }
    
    .success-icon {
        font-size: 60px !important;
        color: #DC2626 !important;
        margin-bottom: 18px !important;
        animation: bounce 1s ease !important;
    }
    
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-15px); }
    }
    
    .success-message h2 {
        color: #1a1a1a !important;
        margin-bottom: 16px !important;
        font-size: 22px !important;
        font-weight: 600 !important;
        font-family: 'Varela Round', sans-serif !important;
    }
    
    .alert {
        padding: 18px 20px !important;
        border-radius: 12px !important;
        margin-bottom: 25px !important;
        border: 2px solid !important;
        font-size: 15px !important;
        animation: slideDown 0.3s ease !important;
        font-family: 'Varela Round', sans-serif !important;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .alert-danger {
        background-color: #ffe6e6 !important;
        border-color: #ff0000 !important;
        color: #cc0000 !important;
    }
    
    .alert-success {
        background-color: #e6ffe6 !important;
        border-color: #00cc00 !important;
        color: #009900 !important;
    }
    
    .file-upload {
        border: 2px dashed #e0e0e0 !important;
        border-radius: 10px !important;
        padding: 25px !important;
        text-align: center !important;
        cursor: pointer !important;
        transition: all 0.3s ease !important;
        background: #ffffff !important;
    }
    
    .file-upload:hover {
        border-color: #DC2626 !important;
        background: rgba(220, 38, 38, 0.04) !important;
        transform: translateY(-1px) !important;
    }
    
    .file-upload input[type="file"] {
        display: none !important;
    }
    
    .file-upload p {
        margin: 0 !important;
        font-size: 13px !important;
        color: #1a1a1a !important;
        font-weight: 500 !important;
        font-family: 'Varela Round', sans-serif !important;
    }
    
    .file-upload small {
        display: block !important;
        margin-top: 5px !important;
        color: #666 !important;
        font-size: 11px !important;
        font-family: 'Varela Round', sans-serif !important;
    }
    
    
    /* Bootstrap Grid Override */
    .row {
        display: flex !important;
        gap: 15px !important;
        margin-bottom: 18px !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
    
    .col-md-6 {
        flex: 1 !important;
        padding: 0 !important;
    }
    
    .col-md-4 {
        flex: 1 !important;
        padding: 0 !important;
    }
    
    .col-md-3 {
        flex: 1 !important;
        padding: 0 !important;
    }
    
    /* Override Bootstrap Container */
    .container {
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
    }
    
    /* Responsive */
    @media (max-width: 1024px) {
        .steps-sidebar {
            width: 280px !important;
            min-width: 280px !important;
        }
        
        .form-content-area {
            margin-left: 280px !important;
            padding: 35px 35px !important;
            width: calc(100% - 280px) !important;
        }
    }
    
    @media (max-width: 768px) {
        .steps-sidebar {
            width: 100% !important;
            position: relative !important;
            height: auto !important;
            padding: 20px !important;
            top: 0 !important;
        }
        
        .form-content-area {
            margin-left: 0 !important;
            padding: 30px 20px !important;
            width: 100% !important;
            margin-top: 0 !important;
        }
    }

    /* Verification UI Styles */
    .input-group {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }

    .verification-input {
        flex: 1 !important;
    }

    .verification-input.verified {
        background-color: #d4edda !important;
        border-color: #28a745 !important;
        color: #155724 !important;
    }

    .verification-input.failed {
        background-color: #f8d7da !important;
        border-color: #dc3545 !important;
        color: #721c24 !important;
    }

    .verification-icon {
        font-size: 20px !important;
        padding: 0 8px !important;
    }

    .verification-icon.success {
        color: #28a745 !important;
    }

    .verification-icon.failed {
        color: #dc3545 !important;
    }

    .verification-btn {
        min-width: 100px !important;
        white-space: nowrap !important;
    }

    .verification-btn.verified {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
    }

    .verification-btn.verified:hover {
        background-color: #218838 !important;
        border-color: #218838 !important;
    }

    .verification-status {
        margin-top: 8px !important;
        padding: 8px 12px !important;
        border-radius: 6px !important;
        font-size: 12px !important;
    }

    .verification-status.success {
        background-color: #d4edda !important;
        border: 1px solid #28a745 !important;
        color: #155724 !important;
    }

    .verification-status.failed {
        background-color: #f8d7da !important;
        border: 1px solid #dc3545 !important;
        color: #721c24 !important;
    }

    .verification-status.in-progress {
        background-color: #fff3cd !important;
        border: 1px solid #ffc107 !important;
        color: #856404 !important;
    }
</style>
@endpush

@section('content')
<div class="registration-wrapper">
    <!-- Left Sidebar - Steps -->
    <div class="steps-sidebar">
        <h2><i class="fas fa-file-alt"></i> IRINN Application</h2>
        
        <div class="step-item active" data-step="1" onclick="goToStep(1)">
            <div class="step-icon"><i class="fas fa-search"></i></div>
            <div class="step-info">
                <div class="step-number">Step 1</div>
                <div class="step-name">GSTIN Lookup</div>
            </div>
        </div>
        
        <div class="step-item" data-step="2" onclick="goToStep(2)">
            <div class="step-icon"><i class="fas fa-building"></i></div>
            <div class="step-info">
                <div class="step-number">Step 2</div>
                <div class="step-name">Company Details</div>
            </div>
        </div>
        
        <div class="step-item" data-step="3" onclick="goToStep(3)">
            <div class="step-icon"><i class="fas fa-user"></i></div>
            <div class="step-info">
                <div class="step-number">Step 3</div>
                <div class="step-name">Applicant Details</div>
            </div>
        </div>
        
        <div class="step-item" data-step="4" onclick="goToStep(4)">
            <div class="step-icon"><i class="fas fa-file-alt"></i></div>
            <div class="step-info">
                <div class="step-number">Step 4</div>
                <div class="step-name">KYC Documents</div>
            </div>
        </div>
        
        <div class="step-item" data-step="5" onclick="goToStep(5)">
            <div class="step-icon"><i class="fas fa-server"></i></div>
            <div class="step-info">
                <div class="step-number">Step 5</div>
                <div class="step-name">IRINN Details</div>
            </div>
        </div>
        
        <div class="step-item" data-step="6" onclick="goToStep(6)">
            <div class="step-icon"><i class="fas fa-network-wired"></i></div>
            <div class="step-info">
                <div class="step-number">Step 6</div>
                <div class="step-name">IP Resources</div>
            </div>
        </div>
        
        <div class="step-item" data-step="7" onclick="goToStep(7)">
            <div class="step-icon"><i class="fas fa-building"></i></div>
            <div class="step-info">
                <div class="step-number">Step 7</div>
                <div class="step-name">Business & Network</div>
            </div>
        </div>
        
        <div class="step-item" data-step="8" onclick="goToStep(8)">
            <div class="step-icon"><i class="fas fa-credit-card"></i></div>
            <div class="step-info">
                <div class="step-number">Step 8</div>
                <div class="step-name">Billing Details</div>
            </div>
        </div>
        
        <div class="step-item" data-step="9" onclick="goToStep(9)">
            <div class="step-icon"><i class="fas fa-money-bill-wave"></i></div>
            <div class="step-info">
                <div class="step-number">Step 9</div>
                <div class="step-name">Payment Summary</div>
            </div>
        </div>
        
        <div class="step-item" data-step="10" onclick="goToStep(10)">
            <div class="step-icon"><i class="fas fa-check-circle"></i></div>
            <div class="step-info">
                <div class="step-number">Step 10</div>
                <div class="step-name">Confirmation</div>
            </div>
        </div>
    </div>
    
    <!-- Right Content Area -->
    <div class="form-content-area">
        <div class="form-header">
            <h1>IRINN Application Form</h1>
            <p>Complete all steps to submit your application</p>
        </div>
        
        <div id="alertContainer"></div>
        
        <form id="registrationForm" enctype="multipart/form-data" onsubmit="event.preventDefault(); return false;">
            @csrf
            
            <!-- Step 1: Primary Identifiers -->
            <div class="form-step" data-step="1">
                <div class="form-card">
                    <h3><i class="fas fa-search"></i> Organisation Identifiers</h3>
                    <p style="font-size: 12px; color: #666; margin-bottom: 20px;">Verify at least one of the following registration numbers to proceed. Once verified, the field will be locked and marked with a verification status.</p>
                    
                    <!-- GSTIN Verification -->
                    <div class="form-group">
                        <label class="form-label">GSTIN Number</label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control verification-input" 
                                   id="gstin" 
                                   name="gstin" 
                                   placeholder="Enter 15-digit GSTIN" 
                                   maxlength="15"
                                   pattern="[0-9A-Z]{15}">
                            <span class="verification-icon" id="gstin-icon" style="display: none;"></span>
                            <button type="button" 
                                    class="btn btn-primary verification-btn" 
                                    id="gstin-verify-btn"
                                    onclick="verifyGst()">
                                <span id="gstin-btn-text">Verify</span>
                                <span id="gstin-btn-loader" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>
                        </div>
                        <div id="gstin-status" class="verification-status" style="display: none;"></div>
                        <input type="hidden" id="gstin_verified" name="gstin_verified" value="0">
                    </div>

                    <!-- UDYAM Verification -->
                    <div class="form-group">
                        <label class="form-label">UDYAM Number</label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control verification-input" 
                                   id="udyam_number" 
                                   name="udyam_number" 
                                   placeholder="Enter UDYAM number">
                            <span class="verification-icon" id="udyam-icon" style="display: none;"></span>
                            <button type="button" 
                                    class="btn btn-primary verification-btn" 
                                    id="udyam-verify-btn"
                                    onclick="verifyUdyam()">
                                <span id="udyam-btn-text">Verify</span>
                                <span id="udyam-btn-loader" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>
                        </div>
                        <div id="udyam-status" class="verification-status" style="display: none;"></div>
                        <input type="hidden" id="udyam_verified" name="udyam_verified" value="0">
                    </div>

                    <!-- MCA Verification -->
                    <div class="form-group">
                        <label class="form-label">MCA (CIN)</label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control verification-input" 
                                   id="mca_cin" 
                                   name="mca_cin" 
                                   placeholder="Enter MCA CIN">
                            <span class="verification-icon" id="mca-icon" style="display: none;"></span>
                            <button type="button" 
                                    class="btn btn-primary verification-btn" 
                                    id="mca-verify-btn"
                                    onclick="verifyMca()">
                                <span id="mca-btn-text">Verify</span>
                                <span id="mca-btn-loader" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>
                        </div>
                        <div id="mca-status" class="verification-status" style="display: none;"></div>
                        <input type="hidden" id="mca_verified" name="mca_verified" value="0">
                    </div>

                    <!-- ROC IEC Verification -->
                    <div class="form-group">
                        <label class="form-label">ROC IEC (Import Export Code)</label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control verification-input" 
                                   id="roc_iec" 
                                   name="roc_iec" 
                                   placeholder="Enter Import Export Code">
                            <span class="verification-icon" id="roc-iec-icon" style="display: none;"></span>
                            <button type="button" 
                                    class="btn btn-primary verification-btn" 
                                    id="roc-iec-verify-btn"
                                    onclick="verifyRocIec()">
                                <span id="roc-iec-btn-text">Verify</span>
                                <span id="roc-iec-btn-loader" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>
                        </div>
                        <div id="roc-iec-status" class="verification-status" style="display: none;"></div>
                        <input type="hidden" id="roc_iec_verified" name="roc_iec_verified" value="0">
                    </div>

                    <div class="alert alert-info" style="margin-top: 20px;">
                        <i class="fas fa-info-circle"></i> <strong>Note:</strong> At least one verification must be completed to proceed to the next step.
                    </div>
                    
                    <div class="btn-group">
                        <div></div>
                        <button type="button" class="btn btn-primary" id="step1-next-btn" onclick="nextStep()" disabled>
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Step 2: Company Details -->
            <div class="form-step" data-step="2" style="display: none;">
                <div class="form-card">
                    <h3><i class="fas fa-building"></i> Company Information</h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">GSTIN</label>
                                <input type="text" class="form-control" id="gstin_display" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Legal Name</label>
                                <input type="text" class="form-control" id="legal_name" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Trade Name</label>
                                <input type="text" class="form-control" id="trade_name" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">PAN</label>
                                <input type="text" class="form-control" id="pan" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">State</label>
                                <input type="text" class="form-control" id="state" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Registration Date</label>
                                <input type="text" class="form-control" id="registration_date" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">GST Type</label>
                                <input type="text" class="form-control" id="gst_type" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Company Status</label>
                                <input type="text" class="form-control" id="company_status" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Primary Address</label>
                        <input type="text" class="form-control" id="primary_address" readonly>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Street</label>
                                <input type="text" class="form-control" id="primary_street" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Locality</label>
                                <input type="text" class="form-control" id="primary_locality" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Pincode</label>
                                <input type="text" class="form-control" id="primary_pincode" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Industry Type<span class="required">*</span></label>
                        <select class="form-select" id="industry_type" name="industry_type" required>
                            <option value="">Select Industry Type</option>
                            <option value="Internet Service Provider (ISP)">Internet Service Provider (ISP)</option>
                            <option value="Data Center / Colocation">Data Center / Colocation</option>
                            <option value="Telecom / Carrier">Telecom / Carrier</option>
                            <option value="Cloud / SaaS Provider">Cloud / SaaS Provider</option>
                            <option value="Content Delivery Network (CDN)">Content Delivery Network (CDN)</option>
                            <option value="OTT / Media Platform">OTT / Media Platform</option>
                            <option value="Enterprise / Corporate Network">Enterprise / Corporate Network</option>
                            <option value="Government / PSU">Government / PSU</option>
                            <option value="Academic / Research Network">Academic / Research Network</option>
                            <option value="Managed Service Provider (MSP)">Managed Service Provider (MSP)</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" onclick="previousStep()">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextStep()">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Step 3: Applicant Details -->
            <div class="form-step" data-step="3" style="display: none;">
                <div class="form-card">
                    <h3><i class="fas fa-user"></i> Applicant Details</h3>
                    <p class="text-muted mb-4">Your details are fetched from your profile. Only designation needs to be filled.</p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" id="applicant_name" name="applicant_name" readonly style="background-color: #e9ecef; cursor: not-allowed;" value="{{ $user->fullname ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="applicant_email" name="applicant_email" readonly style="background-color: #e9ecef; cursor: not-allowed;" value="{{ $user->email ?? '' }}">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Mobile</label>
                                <input type="text" class="form-control" id="applicant_mobile" name="applicant_mobile" readonly style="background-color: #e9ecef; cursor: not-allowed;" value="{{ $user->mobile ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Designation<span class="required">*</span></label>
                                <input type="text" class="form-control" id="applicant_designation" name="applicant_designation" required placeholder="Enter your designation">
                            </div>
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" onclick="previousStep()">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextStep()">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Step 4: KYC Documents -->
            <div class="form-step" data-step="4" style="display: none;">
                <div class="form-card">
                    <h3><i class="fas fa-file-alt"></i> KYC Documents for IRINN Affiliates</h3>
                    <p class="text-muted mb-4">Your entity type is automatically determined from your GST verification. Please upload the required documents.</p>
                    
                    <!-- Identity of Affiliates (Readonly from GST) -->
                    <div class="form-group mb-4">
                        <label class="form-label">Identity of Affiliates<span class="required">*</span></label>
                        <input type="text" class="form-control" id="affiliate_identity_display" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                        <input type="hidden" id="affiliate_identity" name="affiliate_identity" required>
                        <small class="text-muted">This is automatically determined from your GST verification.</small>
                    </div>
                    
                    <!-- KYC Point 1: Verify Identity of Affiliates -->
                    <div class="kyc-section" id="kyc_point_1" style="display: none;">
                        <h5 class="mb-3"><strong>1. Verify Identity of Affiliates</strong></h5>
                        <p class="text-muted small mb-3">Copy of One each documents required as applicable</p>
                        
                        <!-- Partnership Firms -->
                        <div class="kyc-option" id="kyc_partnership" style="display: none;">
                            <div class="alert alert-info mb-3">
                                <strong>For Partnership Firms:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Registered Partnership Deed</li>
                                    <li>Entity Pan Card OR GSTIN OR IEC Certificate OR Shop and Establishment License</li>
                                </ul>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Registered Partnership Deed<span class="required">*</span></label>
                                        <input type="file" class="form-control" id="kyc_partnership_deed" name="kyc_partnership_deed" accept=".pdf,.jpg,.jpeg,.png">
                                        <small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Entity Pan Card / GSTIN / IEC / Shop License<span class="required">*</span></label>
                                        <input type="file" class="form-control" id="kyc_partnership_entity_doc" name="kyc_partnership_entity_doc" accept=".pdf,.jpg,.jpeg,.png">
                                        <small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pvt Ltd Co./Ltd Co. and PSU Company -->
                        <div class="kyc-option" id="kyc_pvt_ltd" style="display: none;">
                            <div class="alert alert-info mb-3">
                                <strong>For Pvt Ltd Co./Ltd Co. and PSU Company:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Certificate of Incorporation OR Certificate of Entity from an associated Government agency</li>
                                    <li>Company Pan Card OR GSTIN</li>
                                </ul>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Certificate of Incorporation / Entity Certificate<span class="required">*</span></label>
                                        <input type="file" class="form-control" id="kyc_incorporation_cert" name="kyc_incorporation_cert" accept=".pdf,.jpg,.jpeg,.png">
                                        <small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Company Pan Card / GSTIN<span class="required">*</span></label>
                                        <input type="file" class="form-control" id="kyc_company_pan_gstin" name="kyc_company_pan_gstin" accept=".pdf,.jpg,.jpeg,.png">
                                        <small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sole Proprietorship -->
                        <div class="kyc-option" id="kyc_sole_proprietorship" style="display: none;">
                            <div class="alert alert-info mb-3">
                                <strong>For Sole Proprietorship:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Udyam Certificate (For MSME Applicants Only)</li>
                                    <li>GSTIN OR IEC Certificate OR Shop and Establishment License</li>
                                </ul>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Udyam Certificate (For MSME Only)</label>
                                        <input type="file" class="form-control" id="kyc_udyam_cert" name="kyc_udyam_cert" accept=".pdf,.jpg,.jpeg,.png">
                                        <small class="text-muted">PDF, JPG, PNG (Max 5MB) - Optional for non-MSME</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">GSTIN / IEC / Shop License<span class="required">*</span></label>
                                        <input type="file" class="form-control" id="kyc_sole_proprietorship_doc" name="kyc_sole_proprietorship_doc" accept=".pdf,.jpg,.jpeg,.png">
                                        <small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Schools, College establishments -->
                        <div class="kyc-option" id="kyc_schools_colleges" style="display: none;">
                            <div class="alert alert-info mb-3">
                                <strong>For Schools, College establishments:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Copy of establishment registration document</li>
                                    <li>Pan Card OR GSTIN</li>
                                </ul>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Establishment Registration Document<span class="required">*</span></label>
                                        <input type="file" class="form-control" id="kyc_establishment_reg" name="kyc_establishment_reg" accept=".pdf,.jpg,.jpeg,.png">
                                        <small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Pan Card / GSTIN<span class="required">*</span></label>
                                        <input type="file" class="form-control" id="kyc_school_pan_gstin" name="kyc_school_pan_gstin" accept=".pdf,.jpg,.jpeg,.png">
                                        <small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Private and Nationalised Bank -->
                        <div class="kyc-option" id="kyc_banks" style="display: none;">
                            <div class="alert alert-info mb-3">
                                <strong>For Private and Nationalised Bank:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>License issued by RBI</li>
                                    <li>Pan Card OR GSTIN</li>
                                </ul>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">RBI License<span class="required">*</span></label>
                                        <input type="file" class="form-control" id="kyc_rbi_license" name="kyc_rbi_license" accept=".pdf,.jpg,.jpeg,.png">
                                        <small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Pan Card / GSTIN<span class="required">*</span></label>
                                        <input type="file" class="form-control" id="kyc_bank_pan_gstin" name="kyc_bank_pan_gstin" accept=".pdf,.jpg,.jpeg,.png">
                                        <small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- KYC Point 2: Business Address Proof -->
                    <div class="kyc-section mt-4" id="kyc_point_2" style="display: none;">
                        <h5 class="mb-3"><strong>2. Business Address Proof</strong></h5>
                        <p class="text-muted small mb-3">Copy of one of the following documents is required (not older than 3 months)</p>
                        <div class="alert alert-warning mb-3">
                            <strong>Note:</strong> Utility Bills (Telephone, Electricity bill, Bank A/c statement not older than 3 months). Bank seal and sign is mandatory on Bank A/c statements.
                        </div>
                        <div class="form-group">
                            <label class="form-label">Business Address Proof<span class="required">*</span></label>
                            <input type="file" class="form-control" id="kyc_business_address_proof" name="kyc_business_address_proof" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">Utility Bill / Bank Statement (PDF, JPG, PNG - Max 5MB)</small>
                        </div>
                    </div>
                    
                    <!-- KYC Point 3: Document Authorizing the Authorised Signatory -->
                    <div class="kyc-section mt-4" id="kyc_point_3" style="display: none;">
                        <h5 class="mb-3"><strong>3. Document Authorizing the Authorised Signatory</strong></h5>
                        <p class="text-muted small mb-3">On Company Letter Head</p>
                        <div class="alert alert-info mb-3">
                            <ul class="mb-0">
                                <li>Private/Public Limited Company - Board Resolution</li>
                                <li>Partnership Firm - Authority Letter</li>
                                <li>Proprietorship firm - Self Declaration of being Sole Proprietor</li>
                            </ul>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Authorization Document<span class="required">*</span></label>
                            <input type="file" class="form-control" id="kyc_authorization_doc" name="kyc_authorization_doc" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">On Company Letter Head (PDF, JPG, PNG - Max 5MB)</small>
                        </div>
                    </div>
                    
                    <!-- KYC Point 4: Authorised Signatories - Signature Proof -->
                    <div class="kyc-section mt-4" id="kyc_point_4" style="display: none;">
                        <h5 class="mb-3"><strong>4. Authorised Signatories - Signature Proof</strong></h5>
                        <p class="text-muted small mb-3">Copy of one of the following documents is required</p>
                        <div class="alert alert-info mb-3">
                            <ul class="mb-0">
                                <li>Authorised Signatory PAN Copy</li>
                                <li>Passport</li>
                                <li>Driving License</li>
                            </ul>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Signature Proof<span class="required">*</span></label>
                            <input type="file" class="form-control" id="kyc_signature_proof" name="kyc_signature_proof" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">PAN / Passport / Driving License (PDF, JPG, PNG - Max 5MB)</small>
                        </div>
                    </div>
                    
                    <!-- KYC Point 5: For Billing Purpose: GST Certificate -->
                    <div class="kyc-section mt-4" id="kyc_point_5" style="display: none;">
                        <h5 class="mb-3"><strong>5. For Billing Purpose: GST Certificate</strong></h5>
                        <div class="form-group">
                            <label class="form-label">GST Certificate<span class="required">*</span></label>
                            <input type="file" class="form-control" id="kyc_gst_certificate" name="kyc_gst_certificate" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
                        </div>
                    </div>
                    
                    <!-- Important Notes -->
                    <div class="alert alert-warning mt-4">
                        <strong>Important Notes:</strong>
                        <ul class="mb-0 mt-2">
                            <li>KYC Point-1/2 should be in the name of the Applicant Entity irrespective of Nature of business.</li>
                            <li>Bank seal and sign is mandatory on Bank A/c statements.</li>
                            <li>One Document will not be considered for multiple KYC Points.</li>
                            <li>Resource requests with incomplete documents will not be processed.</li>
                        </ul>
                    </div>
                    
                    <div class="btn-group mt-4">
                        <button type="button" class="btn btn-secondary" onclick="previousStep()">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextStep()">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Step 5: IRINN Specific Details -->
            <div class="form-step" data-step="5" style="display: none;">
                <div class="form-card">
                    <h3><i class="fas fa-server"></i> IRINN Specific Details</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Account Name<span class="required">*</span></label>
                        <input type="text" class="form-control" id="account_name" name="account_name" required readonly style="background-color: #e9ecef; cursor: not-allowed;">
                        <small class="form-text text-muted">Account name is automatically generated for you.</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Is Dot in domain required?<span class="required">*</span></label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" name="dot_in_domain_required" value="1" id="dot_yes" required>
                                <label for="dot_yes">Yes</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="dot_in_domain_required" value="0" id="dot_no" required>
                                <label for="dot_no">No</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" onclick="previousStep()">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextStep()">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Step 6: IP Resources -->
            <div class="form-step" data-step="6" style="display: none;">
                <div class="form-card">
                    <h3><i class="fas fa-network-wired"></i> IP Resources Selection</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Select Internet Protocol Resources</label>
                        
                        <div class="checkbox-group" id="ip-resources-container">
                            <div class="checkbox-item">
                                <label>
                                    <input type="checkbox" name="ipv4_selected" id="ipv4_check" onchange="toggleIPv4Options()">
                                    <strong>IPv4 (Internet Protocol Version 4)</strong>
                                </label>
                                <div id="ipv4_options" style="margin-top: 15px; margin-left: 35px; display: none;">
                                    <div class="radio-group" id="ipv4_radio_group">
                                        <!-- Dynamically populated from backend -->
                                        <div class="text-muted">Loading IPv4 options...</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="checkbox-item">
                                <label>
                                    <input type="checkbox" name="ipv6_selected" id="ipv6_check" onchange="toggleIPv6Options()">
                                    <strong>IPv6 (Internet Protocol Version 6)</strong>
                                </label>
                                <div id="ipv6_options" style="margin-top: 15px; margin-left: 35px; display: none;">
                                    <div class="radio-group" id="ipv6_radio_group">
                                        <!-- Dynamically populated from backend -->
                                        <div class="text-muted">Loading IPv6 options...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="payment-summary" id="feeSummary" style="display: none;">
                        <h4><i class="fas fa-calculator"></i> Fee Calculation</h4>
                        <div class="summary-row">
                            <span>Maximum IP Fee:</span>
                            <span id="max_ip_fee_display">₹ 0</span>
                        </div>
                        <div class="summary-row" id="gst_row" style="display: none;">
                            <span>GST (<span id="gst_percentage_display">0</span>%):</span>
                            <span id="gst_amount_display">₹ 0</span>
                        </div>
                        <div class="summary-row">
                            <span>Total Fee (Including GST):</span>
                            <span id="total_fee_display">₹ 0</span>
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" onclick="previousStep()">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextStep()">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Step 7: Business & Network Details -->
            <div class="form-step" data-step="7" style="display: none;">
                <div class="form-card">
                    <h3><i class="fas fa-building"></i> Business & Network Details</h3>
                    
                    <!-- Nature of Business -->
                    <div class="form-group">
                        <label class="form-label">Nature of your Business<span class="required">*</span></label>
                        <input type="text" class="form-control" id="nature_of_business" name="nature_of_business" required placeholder="Will be fetched from UDYAM">
                        <small class="text-muted">This will be fetched from UDYAM</small>
                    </div>
                    
                    <!-- Network Diagram Upload -->
                    <div class="form-group">
                        <label class="form-label">Upload Network Diagram(s)<span class="required">*</span></label>
                        <div class="file-upload" onclick="document.getElementById('network_plan_file').click()">
                            <input type="file" id="network_plan_file" name="network_plan_file" accept=".pdf" onchange="updateFileName(this, 'network_plan_file_name')" required>
                            <p id="network_plan_file_name"><i class="fas fa-cloud-upload-alt"></i> Click to upload</p>
                            <small>Combine your all Network Plans in a single PDF (Max 10MB)</small>
                        </div>
                    </div>
                    
                    <!-- Payment Receipt Invoices -->
                    <div class="form-group">
                        <label class="form-label">Payment Receipt Invoices (Upstream Providers)<span class="required">*</span></label>
                        <div class="file-upload" onclick="document.getElementById('payment_receipts_file').click()">
                            <input type="file" id="payment_receipts_file" name="payment_receipts_file" accept=".pdf" onchange="updateFileName(this, 'payment_receipts_file_name')" required>
                            <p id="payment_receipts_file_name"><i class="fas fa-cloud-upload-alt"></i> Click to upload</p>
                            <small>PDF (Max 10MB)</small>
                        </div>
                    </div>
                    
                    <!-- Equipment Details -->
                    <div class="form-group">
                        <label class="form-label">Equipment Details with Invoice<span class="required">*</span></label>
                        <div class="file-upload" onclick="document.getElementById('equipment_details_file').click()">
                            <input type="file" id="equipment_details_file" name="equipment_details_file" accept=".pdf" onchange="updateFileName(this, 'equipment_details_file_name')" required>
                            <p id="equipment_details_file_name"><i class="fas fa-cloud-upload-alt"></i> Click to upload</p>
                            <small>Vendor, Model, Quantity etc in one PDF (Max 10MB)</small>
                        </div>
                    </div>
                    
                    <!-- ASN Requirement -->
                    <div class="form-group">
                        <label class="form-label">ASN Requirement<span class="required">*</span></label>
                        <p class="text-muted small mb-3">Do you need ASN?</p>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" name="as_number_required" value="1" id="asn_yes" required onchange="toggleAsnFields()">
                                <label for="asn_yes">Yes</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="as_number_required" value="0" id="asn_no" required onchange="toggleAsnFields()">
                                <label for="asn_no">No</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- If ASN is No -->
                    <div id="asn_no_fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Company ASN</label>
                                    <input type="text" class="form-control" id="company_asn" name="company_asn" placeholder="Enter Company ASN">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">ISP Company Name</label>
                                    <input type="text" class="form-control" id="isp_company_name" name="isp_company_name" placeholder="Enter ISP Company Name">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- If ASN is Yes -->
                    <div id="asn_yes_fields" style="display: none;">
                        <h5 class="mt-3 mb-3">Provide upstream provider details:</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" id="upstream_name" name="upstream_name" placeholder="Enter provider name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Mobile</label>
                                    <input type="text" class="form-control" id="upstream_mobile" name="upstream_mobile" placeholder="Enter provider mobile">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" id="upstream_email" name="upstream_email" placeholder="Enter provider email">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">ASN<span class="required">*</span></label>
                                    <input type="text" class="form-control" id="upstream_asn" name="upstream_asn" required placeholder="Enter ASN">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="btn-group mt-4">
                        <button type="button" class="btn btn-secondary" onclick="previousStep()">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextStep()">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Step 8: Billing Details -->
            <div class="form-step" data-step="8" style="display: none;">
                <div class="form-card">
                    <h3><i class="fas fa-credit-card"></i> Billing Details</h3>
                    <p class="text-muted mb-4">Auto-fetched from GSTIN API</p>
                    
                    <div class="form-group">
                        <label class="form-label">Affiliate Name</label>
                        <input type="text" class="form-control" id="billing_affiliate_name" name="billing_affiliate_name" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="billing_email" name="billing_email" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" id="billing_address" name="billing_address" rows="3" readonly style="background-color: #e9ecef; cursor: not-allowed;"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">State</label>
                                <input type="text" class="form-control" id="billing_state" name="billing_state" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" id="billing_city" name="billing_city" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Mobile Number</label>
                                <input type="text" class="form-control" id="billing_mobile" name="billing_mobile" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Postal Code</label>
                        <input type="text" class="form-control" id="billing_postal_code" name="billing_postal_code" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                    </div>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" onclick="previousStep()">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextStep()">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Step 9: Payment Summary -->
            <div class="form-step" data-step="9" style="display: none;">
                <div class="form-card">
                    <h3><i class="fas fa-money-bill-wave"></i> Payment Summary</h3>
                    
                    <div class="payment-summary">
                        <h4><i class="fas fa-list-alt"></i> Application Summary</h4>
                        
                        <div class="summary-row">
                            <span><strong>GSTIN:</strong></span>
                            <span id="summary_gstin"></span>
                        </div>
                        <div class="summary-row">
                            <span><strong>Company Name:</strong></span>
                            <span id="summary_company"></span>
                        </div>
                        <div class="summary-row">
                            <span><strong>Industry Type:</strong></span>
                            <span id="summary_industry"></span>
                        </div>
                        <div class="summary-row">
                            <span><strong>IPv4 Selection:</strong></span>
                            <span id="summary_ipv4"></span>
                        </div>
                        <div class="summary-row">
                            <span><strong>IPv6 Selection:</strong></span>
                            <span id="summary_ipv6"></span>
                        </div>
                        <div class="summary-row">
                            <span><strong>IPv4 Fee:</strong></span>
                            <span id="summary_ipv4_fee"></span>
                        </div>
                        <div class="summary-row">
                            <span><strong>IPv6 Fee:</strong></span>
                            <span id="summary_ipv6_fee"></span>
                        </div>
                        <div class="summary-row">
                            <span><strong>Total Fee:</strong></span>
                            <span id="summary_total_fee"></span>
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" onclick="previousStep()">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-primary" onclick="submitForm()">
                            <i class="fas fa-check"></i> Submit Application
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Step 10: Confirmation -->
            <div class="form-step" data-step="10" style="display: none;">
                <div class="form-card">
                    <div class="success-message">
                        <div class="success-icon"><i class="fas fa-check-circle"></i></div>
                        <h2>Application Successful!</h2>
                        <p style="color: #000; font-size: 16px; margin-bottom: 30px;">Your IRINN application has been submitted successfully.</p>
                        
                        <div style="text-align: left; max-width: 500px; margin: 0 auto; margin-bottom: 30px;">
                            <div class="summary-row">
                                <span><strong>Application ID:</strong></span>
                                <span id="confirm_application_id"></span>
                            </div>
                            <div class="summary-row">
                                <span><strong>Name:</strong></span>
                                <span id="confirm_name"></span>
                            </div>
                            <div class="summary-row">
                                <span><strong>Company Name:</strong></span>
                                <span id="confirm_company"></span>
                            </div>
                            <div class="summary-row">
                                <span><strong>Email:</strong></span>
                                <span id="confirm_email"></span>
                            </div>
                            <div class="summary-row">
                                <span><strong>Total Fee:</strong></span>
                                <span id="confirm_total_fee" style="color: #DC2626; font-weight: 600;"></span>
                            </div>
                        </div>
                        
                        <div style="text-align: center; margin-top: 30px;">
                            <a href="{{ route('user.applications.index') }}" class="btn-primary" style="padding: 12px 30px; font-size: 16px; text-decoration: none; display: inline-block;">
                                <i class="fas fa-list"></i> View My Applications
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentStep = 1;
let isSubmitting = false;
let completedSteps = new Set([1]); // Track completed steps
let gstData = null;
let calculatedFees = {
    ipv4: 0,
    ipv6: 0,
    maxAmount: 0,
    gstPercentage: 0,
    gstAmount: 0,
    total: 0
};
let managementRepresentative = {
    name: '',
    email: '',
    designation: '',
    mobile: ''
};

// Verification state tracking
let verificationState = {
    gstin: { verified: false, requestId: null },
    udyam: { verified: false, requestId: null },
    mca: { verified: false, requestId: null },
    rocIec: { verified: false, requestId: null }
};

// Check if at least one verification is complete
function checkVerificationStatus() {
    const hasVerification = verificationState.gstin.verified || 
                            verificationState.udyam.verified || 
                            verificationState.mca.verified || 
                            verificationState.rocIec.verified;
    const nextBtn = document.getElementById('step1-next-btn');
    if (nextBtn) {
        nextBtn.disabled = !hasVerification;
    }
}

// Verification functions
async function verifyGst() {
    const gstin = document.getElementById('gstin').value.trim().toUpperCase();
    if (!gstin || !/^[0-9A-Z]{15}$/.test(gstin)) {
        showAlert('Please enter a valid 15-digit GSTIN', 'danger');
        return;
    }

    const btn = document.getElementById('gstin-verify-btn');
    const btnText = document.getElementById('gstin-btn-text');
    const btnLoader = document.getElementById('gstin-btn-loader');
    const input = document.getElementById('gstin');
    const icon = document.getElementById('gstin-icon');
    const status = document.getElementById('gstin-status');

    btn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline-block';
    status.style.display = 'block';
    status.className = 'verification-status in-progress';
    status.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying GSTIN...';

    try {
        const response = await fetch('{{ route("user.applications.irin.verify-gst") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ gstin: gstin })
        });

        const data = await response.json();
        if (data.success && data.request_id) {
            verificationState.gstin.requestId = data.request_id;
            pollVerificationStatus('gstin', data.request_id);
        } else {
            throw new Error(data.message || 'Failed to initiate verification');
        }
    } catch (error) {
        handleVerificationError('gstin', error.message);
    }
}

async function verifyUdyam() {
    const uamNumber = document.getElementById('udyam_number').value.trim();
    if (!uamNumber) {
        showAlert('Please enter UDYAM number', 'danger');
        return;
    }

    const btn = document.getElementById('udyam-verify-btn');
    const btnText = document.getElementById('udyam-btn-text');
    const btnLoader = document.getElementById('udyam-btn-loader');
    const input = document.getElementById('udyam_number');
    const icon = document.getElementById('udyam-icon');
    const status = document.getElementById('udyam-status');

    btn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline-block';
    status.style.display = 'block';
    status.className = 'verification-status in-progress';
    status.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying UDYAM...';

    try {
        // Create AbortController for timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 70000); // 70 seconds timeout
        
        const response = await fetch('{{ route("user.applications.irin.verify-udyam") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ uam_number: uamNumber }),
            signal: controller.signal
        });
        
        clearTimeout(timeoutId);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (data.success && data.request_id) {
            verificationState.udyam.requestId = data.request_id;
            pollVerificationStatus('udyam', data.request_id);
        } else {
            throw new Error(data.message || 'Failed to initiate verification');
        }
    } catch (error) {
        if (error.name === 'AbortError') {
            handleVerificationError('udyam', 'Request timeout. The UDYAM API is taking too long to respond. Please try again.');
        } else {
            handleVerificationError('udyam', error.message || 'Failed to initiate UDYAM verification');
        }
    }
}

async function verifyMca() {
    const cin = document.getElementById('mca_cin').value.trim();
    if (!cin) {
        showAlert('Please enter MCA CIN', 'danger');
        return;
    }

    const btn = document.getElementById('mca-verify-btn');
    const btnText = document.getElementById('mca-btn-text');
    const btnLoader = document.getElementById('mca-btn-loader');
    const input = document.getElementById('mca_cin');
    const icon = document.getElementById('mca-icon');
    const status = document.getElementById('mca-status');

    btn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline-block';
    status.style.display = 'block';
    status.className = 'verification-status in-progress';
    status.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying MCA...';

    try {
        const response = await fetch('{{ route("user.applications.irin.verify-mca") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ cin: cin })
        });

        const data = await response.json();
        if (data.success && data.request_id) {
            verificationState.mca.requestId = data.request_id;
            pollVerificationStatus('mca', data.request_id);
        } else {
            throw new Error(data.message || 'Failed to initiate verification');
        }
    } catch (error) {
        handleVerificationError('mca', error.message);
    }
}

async function verifyRocIec() {
    const iec = document.getElementById('roc_iec').value.trim();
    if (!iec) {
        showAlert('Please enter Import Export Code', 'danger');
        return;
    }

    const btn = document.getElementById('roc-iec-verify-btn');
    const btnText = document.getElementById('roc-iec-btn-text');
    const btnLoader = document.getElementById('roc-iec-btn-loader');
    const input = document.getElementById('roc_iec');
    const icon = document.getElementById('roc-iec-icon');
    const status = document.getElementById('roc-iec-status');

    btn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline-block';
    status.style.display = 'block';
    status.className = 'verification-status in-progress';
    status.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying ROC IEC...';

    try {
        const response = await fetch('{{ route("user.applications.irin.verify-roc-iec") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ import_export_code: iec })
        });

        const data = await response.json();
        if (data.success && data.request_id) {
            verificationState.rocIec.requestId = data.request_id;
            pollVerificationStatus('rocIec', data.request_id);
        } else {
            throw new Error(data.message || 'Failed to initiate verification');
        }
    } catch (error) {
        handleVerificationError('rocIec', error.message);
    }
}

async function pollVerificationStatus(type, requestId, attempts = 0, maxAttempts = 30) {
    if (attempts >= maxAttempts) {
        handleVerificationError(type, 'Verification timeout. Please try again.');
        return;
    }

    try {
        const response = await fetch('{{ route("user.applications.irin.check-verification-status") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ 
                type: type,
                request_id: requestId 
            })
        });

        const data = await response.json();
        if (data.success) {
            if (data.status === 'completed') {
                if (data.is_verified) {
                    handleVerificationSuccess(type, data);
                } else {
                    handleVerificationFailure(type, data.message || 'Verification failed');
                }
            } else if (data.status === 'failed') {
                handleVerificationFailure(type, data.message || 'Verification failed');
            } else {
                // Still in progress, poll again
                setTimeout(() => {
                    pollVerificationStatus(type, requestId, attempts + 1, maxAttempts);
                }, 2000);
            }
        } else {
            throw new Error(data.message || 'Failed to check verification status');
        }
    } catch (error) {
        if (attempts < maxAttempts - 1) {
            setTimeout(() => {
                pollVerificationStatus(type, requestId, attempts + 1, maxAttempts);
            }, 2000);
        } else {
            handleVerificationError(type, error.message);
        }
    }
}

function handleVerificationSuccess(type, data) {
    const config = getVerificationConfig(type);
    const { input, icon, btn, btnText, btnLoader, status, hiddenInput } = config;

    // Mark as verified
    verificationState[type].verified = true;
    hiddenInput.value = '1';

    // Update UI
    input.readOnly = true;
    input.classList.add('verified');
    input.classList.remove('failed');
    icon.style.display = 'inline-block';
    icon.className = 'verification-icon success';
    icon.innerHTML = '<i class="fas fa-check-circle"></i>';
    btn.disabled = true;
    btn.classList.add('verified');
    btnText.textContent = 'Verified';
    btnText.style.display = 'inline-block';
    btnLoader.style.display = 'none';
    status.style.display = 'block';
    status.className = 'verification-status success';
    status.innerHTML = '<i class="fas fa-check-circle"></i> Verification successful';

    // If GST verification, populate Step 2 fields and Step 4 affiliate identity
    if (type === 'gstin' && data.verification_data) {
        populateGstDataToStep2(data.verification_data);
        populateAffiliateIdentity(data.verification_data);
    }

    checkVerificationStatus();
}

// Store GST data globally for later use
let gstVerificationData = null;
let generatedAccountName = null;

// Generate random account name
function generateAccountName() {
    const accountNameInput = document.getElementById('account_name');
    if (!accountNameInput) {
        return;
    }

    // Only generate if not already generated
    if (generatedAccountName) {
        accountNameInput.value = generatedAccountName;
        return;
    }

    // Generate a random account name (e.g., ACC-XXXX-XXXX)
    const prefix = 'ACC';
    const part1 = Math.random().toString(36).substring(2, 6).toUpperCase();
    const part2 = Math.random().toString(36).substring(2, 6).toUpperCase();
    generatedAccountName = `${prefix}-${part1}-${part2}`;
    
    accountNameInput.value = generatedAccountName;
}

function populateGstDataToStep2(gstData) {
    gstVerificationData = gstData;
    
    // Populate Step 2 fields
    const gstinDisplay = document.getElementById('gstin_display');
    const legalName = document.getElementById('legal_name');
    const tradeName = document.getElementById('trade_name');
    const pan = document.getElementById('pan');
    const state = document.getElementById('state');
    const registrationDate = document.getElementById('registration_date');
    const gstType = document.getElementById('gst_type');
    const companyStatus = document.getElementById('company_status');
    const primaryAddress = document.getElementById('primary_address');
    const primaryStreet = document.getElementById('primary_street');
    const primaryLocality = document.getElementById('primary_locality');
    const primaryPincode = document.getElementById('primary_pincode');

    if (gstinDisplay) gstinDisplay.value = gstData.gstin || '';
    if (legalName) legalName.value = gstData.legal_name || '';
    if (tradeName) tradeName.value = gstData.trade_name || '';
    if (pan) pan.value = gstData.pan || '';
    if (state) state.value = gstData.state || '';
    if (registrationDate) registrationDate.value = gstData.registration_date || '';
    if (gstType) gstType.value = gstData.gst_type || '';
    if (companyStatus) companyStatus.value = gstData.company_status || '';
    if (primaryAddress) primaryAddress.value = gstData.primary_address || '';

    // Extract address components from source_output if available
    if (gstData.source_output && gstData.source_output.principal_place_of_business_fields) {
        const address = gstData.source_output.principal_place_of_business_fields.principal_place_of_business_address;
        if (address) {
            if (primaryStreet) {
                const streetParts = [];
                if (address.door_number) streetParts.push(address.door_number);
                if (address.building_name) streetParts.push(address.building_name);
                if (address.street) streetParts.push(address.street);
                primaryStreet.value = streetParts.join(', ') || '';
            }
            if (primaryLocality) {
                const localityParts = [];
                if (address.location) localityParts.push(address.location);
                if (address.dst) localityParts.push(address.dst);
                if (address.city) localityParts.push(address.city);
                primaryLocality.value = localityParts.join(', ') || '';
            }
            if (primaryPincode) primaryPincode.value = address.pincode || '';
        }
    }
}

function populateAffiliateIdentity(gstData) {
    if (!gstData.affiliate_identity) {
        return;
    }

    const affiliateIdentityDisplay = document.getElementById('affiliate_identity_display');
    const affiliateIdentity = document.getElementById('affiliate_identity');

    if (affiliateIdentityDisplay && gstData.affiliate_identity_display) {
        affiliateIdentityDisplay.value = gstData.affiliate_identity_display;
    }

    if (affiliateIdentity && gstData.affiliate_identity) {
        affiliateIdentity.value = gstData.affiliate_identity;
        // Trigger updateKycDocuments to show the appropriate documents
        updateKycDocuments();
    }
}

function handleVerificationFailure(type, message) {
    const config = getVerificationConfig(type);
    const { input, icon, btn, btnText, btnLoader, status, hiddenInput } = config;

    // Mark as failed
    verificationState[type].verified = false;
    hiddenInput.value = '0';

    // Update UI
    input.readOnly = false;
    input.classList.add('failed');
    input.classList.remove('verified');
    icon.style.display = 'inline-block';
    icon.className = 'verification-icon failed';
    icon.innerHTML = '<i class="fas fa-times-circle"></i>';
    btn.disabled = false;
    btn.classList.remove('verified');
    btnText.textContent = 'Verify';
    btnText.style.display = 'inline-block';
    btnLoader.style.display = 'none';
    status.style.display = 'block';
    status.className = 'verification-status failed';
    status.innerHTML = '<i class="fas fa-times-circle"></i> Verification mismatched: ' + message;

    checkVerificationStatus();
}

function handleVerificationError(type, message) {
    const config = getVerificationConfig(type);
    const { btn, btnText, btnLoader, status } = config;

    btn.disabled = false;
    btnText.style.display = 'inline-block';
    btnLoader.style.display = 'none';
    status.style.display = 'block';
    status.className = 'verification-status failed';
    status.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;

    checkVerificationStatus();
}

function getVerificationConfig(type) {
    const configs = {
        gstin: {
            input: document.getElementById('gstin'),
            icon: document.getElementById('gstin-icon'),
            btn: document.getElementById('gstin-verify-btn'),
            btnText: document.getElementById('gstin-btn-text'),
            btnLoader: document.getElementById('gstin-btn-loader'),
            status: document.getElementById('gstin-status'),
            hiddenInput: document.getElementById('gstin_verified')
        },
        udyam: {
            input: document.getElementById('udyam_number'),
            icon: document.getElementById('udyam-icon'),
            btn: document.getElementById('udyam-verify-btn'),
            btnText: document.getElementById('udyam-btn-text'),
            btnLoader: document.getElementById('udyam-btn-loader'),
            status: document.getElementById('udyam-status'),
            hiddenInput: document.getElementById('udyam_verified')
        },
        mca: {
            input: document.getElementById('mca_cin'),
            icon: document.getElementById('mca-icon'),
            btn: document.getElementById('mca-verify-btn'),
            btnText: document.getElementById('mca-btn-text'),
            btnLoader: document.getElementById('mca-btn-loader'),
            status: document.getElementById('mca-status'),
            hiddenInput: document.getElementById('mca_verified')
        },
        rocIec: {
            input: document.getElementById('roc_iec'),
            icon: document.getElementById('roc-iec-icon'),
            btn: document.getElementById('roc-iec-verify-btn'),
            btnText: document.getElementById('roc-iec-btn-text'),
            btnLoader: document.getElementById('roc-iec-btn-loader'),
            status: document.getElementById('roc-iec-status'),
            hiddenInput: document.getElementById('roc_iec_verified')
        }
    };
    return configs[type];
}

// Auto-uppercase GSTIN input
document.addEventListener('DOMContentLoaded', function() {
    const gstinInput = document.getElementById('gstin');
    if (gstinInput) {
        gstinInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^0-9A-Z]/g, '');
        });
    }
});

function showAlert(message, type = 'danger') {
    const alertContainer = document.getElementById('alertContainer');
    alertContainer.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    setTimeout(() => {
        alertContainer.innerHTML = '';
    }, 5000);
}

function updateStepIndicator(step) {
    document.querySelectorAll('.step-item').forEach((item, index) => {
        const stepNum = index + 1;
        item.classList.remove('active', 'completed', 'disabled');
        if (stepNum < step) {
            item.classList.add('completed');
        } else if (stepNum === step) {
            item.classList.add('active');
        } else if (stepNum > step && !completedSteps.has(stepNum)) {
            item.classList.add('disabled');
        }
    });
}

function autoPopulateBillingFields() {
    // Auto-populate billing fields from GST API data
    const tradeName = document.getElementById('trade_name')?.value || '';
    const primaryAddress = document.getElementById('primary_address')?.value || '';
    const primaryLocality = document.getElementById('primary_locality')?.value || '';
    const primaryPincode = document.getElementById('primary_pincode')?.value || '';
    const state = document.getElementById('state')?.value || '';
    
    // Extract state name from "29 - Karnataka KA" format if needed
    let stateName = state;
    if (state.includes(' - ')) {
        const parts = state.split(' - ');
        if (parts.length > 1) {
            stateName = parts[1].split(' ')[0]; // Get "Karnataka" from "Karnataka KA"
        }
    }
    
    // Get applicant/MR data as fallback
    const nameToUse = managementRepresentative.name || '';
    const emailToUse = managementRepresentative.email || '';
    const mobileToUse = managementRepresentative.mobile || '';
    
    // Set billing fields from GST data
    const billingAffiliateName = document.getElementById('billing_affiliate_name');
    const billingEmail = document.getElementById('billing_email');
    const billingAddress = document.getElementById('billing_address');
    const billingState = document.getElementById('billing_state');
    const billingCity = document.getElementById('billing_city');
    const billingPostalCode = document.getElementById('billing_postal_code');
    const billingMobile = document.getElementById('billing_mobile');
    
    if (billingAffiliateName) {
        billingAffiliateName.value = tradeName || nameToUse;
    }
    if (billingEmail) {
        billingEmail.value = emailToUse;
    }
    if (billingAddress) {
        billingAddress.value = primaryAddress;
    }
    if (billingState) {
        billingState.value = stateName;
    }
    if (billingCity) {
        billingCity.value = primaryLocality;
    }
    if (billingPostalCode) {
        billingPostalCode.value = primaryPincode;
    }
    if (billingMobile) {
        billingMobile.value = mobileToUse;
    }
}

function autoPopulateFields() {
    // This function is kept for backward compatibility
    // Billing fields are now handled by autoPopulateBillingFields()
}

function showStep(step) {
    document.querySelectorAll('.form-step').forEach((s) => {
        s.style.display = 'none';
    });
    const stepElement = document.querySelector(`.form-step[data-step="${step}"]`);
    if (stepElement) {
        stepElement.style.display = 'block';
        updateStepIndicator(step);
        currentStep = step;
        
        // Populate Step 2 with GST data if available
        if (step === 2 && gstVerificationData) {
            populateGstDataToStep2(gstVerificationData);
        }
        
        // Populate Step 4 affiliate identity if available
        if (step === 4 && gstVerificationData) {
            populateAffiliateIdentity(gstVerificationData);
        }
        
        // Populate applicant details when showing step 3
        if (step === 3) {
            populateApplicantDetails();
        }
        
        // Step 5 - Generate random account name
        if (step === 5) {
            generateAccountName();
        }
        
        // Step 6 - Load IP pricing if not already loaded
        if (step === 6) {
            if (Object.keys(ipPricingData.ipv4).length === 0 && Object.keys(ipPricingData.ipv6).length === 0) {
                fetchIpPricing();
            }
        }
        
        // Step 7 - Initialize ASN fields
        if (step === 7) {
            // Initialize ASN fields based on current selection
            const asnYes = document.getElementById('asn_yes');
            const asnNo = document.getElementById('asn_no');
            if (asnYes && asnYes.checked) {
                toggleAsnFields();
            } else if (asnNo && asnNo.checked) {
                toggleAsnFields();
            }
        }
        
        // Step 8 - Ensure billing fields are populated from GST data
        if (step === 8) {
            autoPopulateBillingFields();
        }
        
        if (step === 9) {
            updatePaymentSummary();
        }
    }
}

function goToStep(step) {
    // Only allow navigation to completed steps or current step
    if (completedSteps.has(step) || step === currentStep || step === 1) {
        showStep(step);
    } else {
        showAlert('Please complete previous steps first');
    }
}

function nextStep() {
    if (currentStep < 10) {
        // Special validation for step 1 - check verification status
        if (currentStep === 1) {
            const hasVerification = verificationState.gstin.verified || 
                                    verificationState.udyam.verified || 
                                    verificationState.mca.verified || 
                                    verificationState.rocIec.verified;
            if (!hasVerification) {
                showAlert('Please verify at least one document (GSTIN, UDYAM, MCA, or ROC IEC) to proceed.', 'danger');
                return;
            }
        }

        const currentStepElement = document.querySelector(`.form-step[data-step="${currentStep}"]`);
        const requiredFields = currentStepElement.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value && !field.files?.length) {
                isValid = false;
                field.style.borderColor = '#ff0000';
                setTimeout(() => {
                    field.style.borderColor = '';
                }, 2000);
            }
        });
        
        if (isValid) {
            if (currentStep === 3) {
                captureManagementRepresentative();
            }
            // Mark current step as completed
            completedSteps.add(currentStep);
            completedSteps.add(currentStep + 1);
            
            showStep(currentStep + 1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            showAlert('Please fill all required fields');
        }
    }
}

function previousStep() {
    if (currentStep > 1) {
        showStep(currentStep - 1);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// Make fetchGstDetails globally accessible
async function fetchGstDetails() {
    const gstin = document.getElementById('gstin').value.trim().toUpperCase();
    
    if (!gstin) {
        showAlert('Please enter GSTIN number');
        return;
    }
    
    if (gstin.length !== 15) {
        showAlert('GSTIN must be exactly 15 characters');
        return;
    }
    
    // Show loading state
    const fetchButton = document.querySelector('button[onclick="fetchGstDetails()"]');
    const originalButtonText = fetchButton ? fetchButton.innerHTML : '';
    if (fetchButton) {
        fetchButton.disabled = true;
        fetchButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Fetching...';
    }
    
    showAlert('Fetching GST details...', 'primary');
    
    try {
        const response = await fetch('{{ route("user.applications.irin.fetch-gst") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ gstin: gstin })
        });
        
        const data = await response.json();
        
        // Restore button
        if (fetchButton) {
            fetchButton.disabled = false;
            fetchButton.innerHTML = originalButtonText;
        }
        
        if (response.ok && data.success && data.data) {
            gstData = data.data;
            populateGstData(data.data);
            completedSteps.add(1);
            completedSteps.add(2);
            showAlert('GST details fetched successfully!', 'success');
            setTimeout(() => nextStep(), 1000);
        } else {
            const errorMessage = data.message || 'Failed to fetch GST details. Please check the GSTIN and try again.';
            showAlert(errorMessage);
            console.error('GST API Error:', data);
        }
    } catch (error) {
        // Restore button
        if (fetchButton) {
            fetchButton.disabled = false;
            fetchButton.innerHTML = originalButtonText;
        }
        console.error('GST Fetch Error:', error);
        showAlert('Error fetching GST details: ' + (error.message || 'Network error. Please try again.'));
    }
}

// Also assign to window for inline handlers
window.fetchGstDetails = fetchGstDetails;

function populateGstData(data) {
    const company = data.company_details || {};
    
    // Helper function to safely set value
    function setValue(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.value = value || '';
        }
    }
    
    // Populate GST/Company details (Step 2)
    setValue('gstin_display', data.gstin);
    setValue('legal_name', company.legal_name);
    setValue('trade_name', company.trade_name);
    setValue('pan', company.pan);
    
    // Handle state - can be "29 - Karnataka KA" or just state name
    const stateValue = company.state || '';
    setValue('state', stateValue);
    
    setValue('registration_date', company.registration_date);
    setValue('gst_type', company.gst_type);
    setValue('company_status', company.company_status);
    
    if (company.pradr) {
        setValue('primary_address', company.pradr.addr);
        setValue('primary_street', company.pradr.street);
        setValue('primary_locality', company.pradr.loc);
        setValue('primary_pincode', company.pradr.pincode);
    }
    
    // Populate billing fields if they exist (for backward compatibility)
    const tradeName = company.trade_name || '';
    setValue('billing_affiliate_name', tradeName);
    setValue('billing_email', '');
    
    // ISP Company Name is now user-entered, not auto-populated
    
    if (company.pradr) {
        setValue('billing_address', company.pradr.addr);
        
        // Extract state name from "29 - Karnataka KA" format if needed
        let stateName = stateValue;
        if (stateValue.includes(' - ')) {
            const parts = stateValue.split(' - ');
            if (parts.length > 1) {
                stateName = parts[1].split(' ')[0]; // Get "Karnataka" from "Karnataka KA"
            }
        }
        setValue('billing_state', stateName || stateValue);
        setValue('billing_city', company.pradr.loc);
        setValue('billing_postal_code', company.pradr.pincode);
    }
}

function toggleIPv4Options() {
    const checkbox = document.getElementById('ipv4_check');
    const options = document.getElementById('ipv4_options');
    options.style.display = checkbox.checked ? 'block' : 'none';
    if (!checkbox.checked) {
        document.querySelectorAll('input[name="ipv4_size"]').forEach(r => r.checked = false);
        calculateFees();
    }
}

function toggleIPv6Options() {
    const checkbox = document.getElementById('ipv6_check');
    const options = document.getElementById('ipv6_options');
    options.style.display = checkbox.checked ? 'block' : 'none';
    if (!checkbox.checked) {
        document.querySelectorAll('input[name="ipv6_size"]').forEach(r => r.checked = false);
        calculateFees();
    }
}

// Global pricing data
let ipPricingData = {
    ipv4: {},
    ipv6: {}
};

// Fetch pricing from API
async function fetchIpPricing() {
    try {
        const response = await fetch('{{ route("user.applications.irin.pricing") }}');
        const result = await response.json();
        if (result.success && result.data) {
            ipPricingData = result.data;
            populateIpOptions();
        }
    } catch (error) {
        console.error('Error fetching IP pricing:', error);
    }
}

// Populate IP options dynamically from backend data
function populateIpOptions() {
    // Populate IPv4 options
    const ipv4RadioGroup = document.getElementById('ipv4_radio_group');
    if (ipv4RadioGroup && ipPricingData.ipv4) {
        ipv4RadioGroup.innerHTML = '';
        const ipv4Sizes = Object.keys(ipPricingData.ipv4).sort();
        
        if (ipv4Sizes.length === 0) {
            ipv4RadioGroup.innerHTML = '<div class="text-muted">No IPv4 options available</div>';
        } else {
            ipv4Sizes.forEach((size, index) => {
                const pricing = ipPricingData.ipv4[size];
                const radioId = `ipv4_${size.replace('/', '_')}`;
                const radioItem = document.createElement('div');
                radioItem.className = 'radio-item';
                radioItem.innerHTML = `
                    <input type="radio" name="ipv4_size" value="${size}" id="${radioId}" onchange="calculateFees()">
                    <label for="${radioId}">${size} (${pricing.addresses.toLocaleString('en-IN')} IPs) - ₹${pricing.price.toLocaleString('en-IN', {maximumFractionDigits: 2})}</label>
                `;
                ipv4RadioGroup.appendChild(radioItem);
            });
        }
    }

    // Populate IPv6 options
    const ipv6RadioGroup = document.getElementById('ipv6_radio_group');
    if (ipv6RadioGroup && ipPricingData.ipv6) {
        ipv6RadioGroup.innerHTML = '';
        const ipv6Sizes = Object.keys(ipPricingData.ipv6).sort();
        
        if (ipv6Sizes.length === 0) {
            ipv6RadioGroup.innerHTML = '<div class="text-muted">No IPv6 options available</div>';
        } else {
            ipv6Sizes.forEach((size, index) => {
                const pricing = ipPricingData.ipv6[size];
                const radioId = `ipv6_${size.replace('/', '_')}`;
                const radioItem = document.createElement('div');
                radioItem.className = 'radio-item';
                radioItem.innerHTML = `
                    <input type="radio" name="ipv6_size" value="${size}" id="${radioId}" onchange="calculateFees()">
                    <label for="${radioId}">${size} (${pricing.addresses.toLocaleString('en-IN')} IPs) - ₹${pricing.price.toLocaleString('en-IN', {maximumFractionDigits: 2})}</label>
                `;
                ipv6RadioGroup.appendChild(radioItem);
            });
        }
    }
}

function getIpPricingInfo(ipType, size) {
    if (ipPricingData[ipType] && ipPricingData[ipType][size]) {
        return ipPricingData[ipType][size];
    }
    return null;
}

function calculateFees() {
    const ipv4Size = document.querySelector('input[name="ipv4_size"]:checked')?.value;
    const ipv6Size = document.querySelector('input[name="ipv6_size"]:checked')?.value;
    
    const selectedPrices = [];
    let maxGstPercentage = 0;
    
    // Get prices for selected IPs
    if (ipv4Size) {
        const pricing = getIpPricingInfo('ipv4', ipv4Size);
        if (pricing) {
            selectedPrices.push({
                type: 'IPv4',
                size: ipv4Size,
                amount: pricing.amount || pricing.price,
                price: pricing.price,
                gst_percentage: pricing.gst_percentage || 0
            });
            if (pricing.gst_percentage > maxGstPercentage) {
                maxGstPercentage = pricing.gst_percentage;
            }
        }
    }
    
    if (ipv6Size) {
        const pricing = getIpPricingInfo('ipv6', ipv6Size);
        if (pricing) {
            selectedPrices.push({
                type: 'IPv6',
                size: ipv6Size,
                amount: pricing.amount || pricing.price,
                price: pricing.price,
                gst_percentage: pricing.gst_percentage || 0
            });
            if (pricing.gst_percentage > maxGstPercentage) {
                maxGstPercentage = pricing.gst_percentage;
            }
        }
    }
    
    // Calculate maximum amount (not sum)
    let maxAmount = 0;
    if (selectedPrices.length > 0) {
        maxAmount = Math.max(...selectedPrices.map(p => p.amount));
    }
    
    // Calculate GST on maximum amount
    const gstAmount = maxAmount * (maxGstPercentage / 100);
    const totalFee = maxAmount + gstAmount;
    
    // Update calculated fees
    calculatedFees.ipv4 = ipv4Size ? (getIpPricingInfo('ipv4', ipv4Size)?.price || 0) : 0;
    calculatedFees.ipv6 = ipv6Size ? (getIpPricingInfo('ipv6', ipv6Size)?.price || 0) : 0;
    calculatedFees.maxAmount = maxAmount;
    calculatedFees.gstPercentage = maxGstPercentage;
    calculatedFees.gstAmount = gstAmount;
    calculatedFees.total = totalFee;
    
    // Update display
    const maxFeeDisplay = document.getElementById('max_ip_fee_display');
    const gstRow = document.getElementById('gst_row');
    const gstPercentageDisplay = document.getElementById('gst_percentage_display');
    const gstAmountDisplay = document.getElementById('gst_amount_display');
    const totalFeeDisplay = document.getElementById('total_fee_display');
    const feeSummary = document.getElementById('feeSummary');
    
    if (maxFeeDisplay) {
        maxFeeDisplay.textContent = '₹ ' + maxAmount.toLocaleString('en-IN', {maximumFractionDigits: 2});
    }
    
    if (maxGstPercentage > 0) {
        if (gstRow) gstRow.style.display = 'flex';
        if (gstPercentageDisplay) gstPercentageDisplay.textContent = maxGstPercentage.toFixed(2);
        if (gstAmountDisplay) {
            gstAmountDisplay.textContent = '₹ ' + gstAmount.toLocaleString('en-IN', {maximumFractionDigits: 2});
        }
    } else {
        if (gstRow) gstRow.style.display = 'none';
    }
    
    if (totalFeeDisplay) {
        totalFeeDisplay.textContent = '₹ ' + totalFee.toLocaleString('en-IN', {maximumFractionDigits: 2});
    }
    
    if (feeSummary) {
        if (maxAmount > 0) {
            feeSummary.style.display = 'block';
        } else {
            feeSummary.style.display = 'none';
        }
    }
}

function updateFileName(input, displayId) {
    if (input.files && input.files[0]) {
        const fileName = input.files[0].name;
        const fileSize = (input.files[0].size / 1024 / 1024).toFixed(2);
        document.getElementById(displayId).innerHTML = `<i class="fas fa-file-check"></i> <strong>${fileName}</strong> (${fileSize} MB)`;
    } else {
        document.getElementById(displayId).innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Click to upload';
    }
}

function updatePaymentSummary() {
    document.getElementById('summary_gstin').textContent = document.getElementById('gstin_display').value || '-';
    document.getElementById('summary_company').textContent = document.getElementById('legal_name').value || '-';
    document.getElementById('summary_industry').textContent = document.getElementById('industry_type').value || '-';
    
    const ipv4Size = document.querySelector('input[name="ipv4_size"]:checked')?.value;
    const ipv6Size = document.querySelector('input[name="ipv6_size"]:checked')?.value;
    
    document.getElementById('summary_ipv4').textContent = ipv4Size ? ipv4Size : 'Not selected';
    document.getElementById('summary_ipv6').textContent = ipv6Size ? ipv6Size : 'Not selected';
    document.getElementById('summary_ipv4_fee').textContent = '₹ ' + calculatedFees.ipv4.toLocaleString('en-IN', {maximumFractionDigits: 2});
    document.getElementById('summary_ipv6_fee').textContent = '₹ ' + calculatedFees.ipv6.toLocaleString('en-IN', {maximumFractionDigits: 2});
    document.getElementById('summary_total_fee').textContent = '₹ ' + calculatedFees.total.toLocaleString('en-IN', {maximumFractionDigits: 2});
}

async function submitForm() {
    // Prevent double submission
    if (isSubmitting) {
        return;
    }
    
    const form = document.getElementById('registrationForm');
    const formData = new FormData(form);
    
    // Required files - use safe access
    const networkPlanInput = document.getElementById('network_plan_file');
    const paymentReceiptsInput = document.getElementById('payment_receipts_file');
    const equipmentDetailsInput = document.getElementById('equipment_details_file');
    const kycBusinessAddressProofInput = document.getElementById('kyc_business_address_proof');
    const kycAuthorizationDocInput = document.getElementById('kyc_authorization_doc');
    const kycSignatureProofInput = document.getElementById('kyc_signature_proof');
    const kycGstCertificateInput = document.getElementById('kyc_gst_certificate');
    
    const networkPlanFile = networkPlanInput?.files?.[0];
    const paymentReceiptsFile = paymentReceiptsInput?.files?.[0];
    const equipmentDetailsFile = equipmentDetailsInput?.files?.[0];
    const kycBusinessAddressProof = kycBusinessAddressProofInput?.files?.[0];
    const kycAuthorizationDoc = kycAuthorizationDocInput?.files?.[0];
    const kycSignatureProof = kycSignatureProofInput?.files?.[0];
    const kycGstCertificate = kycGstCertificateInput?.files?.[0];
    
    // Get selected affiliate identity
    const affiliateIdentity = document.getElementById('affiliate_identity')?.value;
    
    // Check required files - always required
    const missingFiles = [];
    if (!networkPlanFile) missingFiles.push('Network Plan');
    if (!paymentReceiptsFile) missingFiles.push('Payment Receipts');
    if (!equipmentDetailsFile) missingFiles.push('Equipment Details');
    if (!kycBusinessAddressProof) missingFiles.push('Business Address Proof');
    if (!kycAuthorizationDoc) missingFiles.push('Authorization Document');
    if (!kycSignatureProof) missingFiles.push('Signature Proof');
    if (!kycGstCertificate) missingFiles.push('GST Certificate');
    
    // Check conditional KYC files based on affiliate identity
    if (affiliateIdentity === 'partnership') {
        const partnershipDeed = document.getElementById('kyc_partnership_deed')?.files?.[0];
        const partnershipEntityDoc = document.getElementById('kyc_partnership_entity_doc')?.files?.[0];
        if (!partnershipDeed) missingFiles.push('Partnership Deed');
        if (!partnershipEntityDoc) missingFiles.push('Partnership Entity Document');
    } else if (affiliateIdentity === 'pvt_ltd') {
        const incorporationCert = document.getElementById('kyc_incorporation_cert')?.files?.[0];
        const companyPanGstin = document.getElementById('kyc_company_pan_gstin')?.files?.[0];
        if (!incorporationCert) missingFiles.push('Certificate of Incorporation');
        if (!companyPanGstin) missingFiles.push('Company Pan Card / GSTIN');
    } else if (affiliateIdentity === 'sole_proprietorship') {
        const soleProprietorshipDoc = document.getElementById('kyc_sole_proprietorship_doc')?.files?.[0];
        if (!soleProprietorshipDoc) missingFiles.push('GSTIN / IEC / Shop License');
        // Udyam certificate is optional
    } else if (affiliateIdentity === 'schools_colleges') {
        const establishmentReg = document.getElementById('kyc_establishment_reg')?.files?.[0];
        const schoolPanGstin = document.getElementById('kyc_school_pan_gstin')?.files?.[0];
        if (!establishmentReg) missingFiles.push('Establishment Registration Document');
        if (!schoolPanGstin) missingFiles.push('School Pan Card / GSTIN');
    } else if (affiliateIdentity === 'banks') {
        const rbiLicense = document.getElementById('kyc_rbi_license')?.files?.[0];
        const bankPanGstin = document.getElementById('kyc_bank_pan_gstin')?.files?.[0];
        if (!rbiLicense) missingFiles.push('RBI License');
        if (!bankPanGstin) missingFiles.push('Bank Pan Card / GSTIN');
    }
    
    if (missingFiles.length > 0) {
        showAlert('Please upload all required files: ' + missingFiles.join(', '));
        return;
    }
    
    // Set submitting flag
    isSubmitting = true;
    
    // Disable submit button
    const submitBtn = document.querySelector('button[onclick="submitForm()"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    }
    
    if (gstData) {
        formData.append('gst_data', JSON.stringify(gstData));
    }
    
    formData.append('ipv4_fee', calculatedFees.ipv4);
    formData.append('ipv6_fee', calculatedFees.ipv6);
    formData.append('total_fee', calculatedFees.total);
    
    // Append required files
    formData.append('network_plan_file', networkPlanFile);
    formData.append('payment_receipts_file', paymentReceiptsFile);
    formData.append('equipment_details_file', equipmentDetailsFile);
    formData.append('kyc_business_address_proof', kycBusinessAddressProof);
    formData.append('kyc_authorization_doc', kycAuthorizationDoc);
    formData.append('kyc_signature_proof', kycSignatureProof);
    formData.append('kyc_gst_certificate', kycGstCertificate);
    
    // Append conditional KYC files based on affiliate identity
    let conditionalKycFiles = [];
    if (affiliateIdentity === 'partnership') {
        conditionalKycFiles = ['kyc_partnership_deed', 'kyc_partnership_entity_doc'];
    } else if (affiliateIdentity === 'pvt_ltd') {
        conditionalKycFiles = ['kyc_incorporation_cert', 'kyc_company_pan_gstin'];
    } else if (affiliateIdentity === 'sole_proprietorship') {
        conditionalKycFiles = ['kyc_sole_proprietorship_doc', 'kyc_udyam_cert']; // Udyam is optional
    } else if (affiliateIdentity === 'schools_colleges') {
        conditionalKycFiles = ['kyc_establishment_reg', 'kyc_school_pan_gstin'];
    } else if (affiliateIdentity === 'banks') {
        conditionalKycFiles = ['kyc_rbi_license', 'kyc_bank_pan_gstin'];
    }
    
    conditionalKycFiles.forEach(fileId => {
        const fileInput = document.getElementById(fileId);
        if (fileInput && fileInput.files && fileInput.files[0]) {
            formData.append(fileId, fileInput.files[0]);
        }
    });
    
    showAlert('Submitting application...', 'success');
    
    try {
        const response = await fetch('{{ route("user.applications.irin.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('confirm_name').textContent = formData.get('applicant_name') || formData.get('mr_name') || '-';
            const legalNameEl = document.getElementById('legal_name');
            document.getElementById('confirm_company').textContent = legalNameEl ? legalNameEl.value : '-';
            document.getElementById('confirm_email').textContent = formData.get('applicant_email') || formData.get('mr_email') || '-';
            document.getElementById('confirm_application_id').textContent = data.application?.application_id || data.application_id || '-';
            document.getElementById('confirm_total_fee').textContent = '₹ ' + calculatedFees.total.toLocaleString('en-IN', { 
                minimumFractionDigits: 2, 
                maximumFractionDigits: 2 
            });
            
            showStep(10);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            let errorMessage = data.message || 'Application submission failed';
            if (data.errors) {
                const errorList = Object.values(data.errors).flat().join(', ');
                errorMessage += ': ' + errorList;
            }
            showAlert(errorMessage);
            isSubmitting = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check"></i> Submit Application';
            }
        }
    } catch (error) {
        showAlert('Error submitting application: ' + error.message);
        isSubmitting = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Submit Application';
        }
    }
}

function populateApplicantDetails() {
    // Applicant details are already populated from server-side
    // This function is called when step 3 is shown
    const designation = document.getElementById('applicant_designation');
    if (designation) {
        designation.focus();
    }
}

function captureManagementRepresentative() {
    // For backward compatibility, use applicant details if MR fields don't exist
    const mrName = document.getElementById('mr_name');
    const applicantName = document.getElementById('applicant_name');
    
    if (mrName) {
        managementRepresentative = {
            name: mrName.value?.trim() || '',
            email: document.getElementById('mr_email')?.value?.trim() || '',
            designation: document.getElementById('mr_designation')?.value?.trim() || '',
            mobile: document.getElementById('mr_mobile')?.value?.trim() || ''
        };
    } else if (applicantName) {
        // Use applicant details for management representative
        managementRepresentative = {
            name: applicantName.value?.trim() || '',
            email: document.getElementById('applicant_email')?.value?.trim() || '',
            designation: document.getElementById('applicant_designation')?.value?.trim() || '',
            mobile: document.getElementById('applicant_mobile')?.value?.trim() || ''
        };
    }
    
    populateIrinnFromMR();
    autoPopulateFields();
}

function populateIrinnFromMR() {
    // This function is kept for backward compatibility
    // Account name should NOT be auto-populated - user must fill it manually
    // Billing fields are handled separately via GST API data
}

// Toggle ASN fields based on selection
function toggleAsnFields() {
    const asnYes = document.getElementById('asn_yes');
    const asnNo = document.getElementById('asn_no');
    const asnYesFields = document.getElementById('asn_yes_fields');
    const asnNoFields = document.getElementById('asn_no_fields');
    
    // Clear all fields when toggling
    const allFields = [
        'upstream_name',
        'upstream_mobile',
        'upstream_email',
        'upstream_asn',
        'company_asn',
        'isp_company_name'
    ];
    
    allFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.value = '';
            // Remove readonly if it exists
            field.removeAttribute('readonly');
            field.style.backgroundColor = '';
            field.style.cursor = '';
        }
    });
    
    if (asnYes && asnYes.checked) {
        asnYesFields.style.display = 'block';
        asnNoFields.style.display = 'none';
        
        // Make ASN field required
        const upstreamAsn = document.getElementById('upstream_asn');
        if (upstreamAsn) {
            upstreamAsn.required = true;
        }
        
        // Make company ASN not required
        const companyAsn = document.getElementById('company_asn');
        if (companyAsn) {
            companyAsn.required = false;
        }
    } else if (asnNo && asnNo.checked) {
        asnYesFields.style.display = 'none';
        asnNoFields.style.display = 'block';
        
        // Make ASN field not required
        const upstreamAsn = document.getElementById('upstream_asn');
        if (upstreamAsn) {
            upstreamAsn.required = false;
        }
    }
}

// Update KYC Documents based on selected affiliate identity
function updateKycDocuments() {
    const selectedIdentity = document.getElementById('affiliate_identity').value;
    
    // Hide all KYC options
    document.querySelectorAll('.kyc-option').forEach(option => {
        option.style.display = 'none';
    });
    
    // Hide all KYC sections initially
    document.querySelectorAll('.kyc-section').forEach(section => {
        section.style.display = 'none';
    });
    
    if (selectedIdentity) {
        // Show KYC Point 1
        document.getElementById('kyc_point_1').style.display = 'block';
        
        // Show relevant option based on selection
        switch(selectedIdentity) {
            case 'partnership':
                document.getElementById('kyc_partnership').style.display = 'block';
                break;
            case 'pvt_ltd':
                document.getElementById('kyc_pvt_ltd').style.display = 'block';
                break;
            case 'sole_proprietorship':
                document.getElementById('kyc_sole_proprietorship').style.display = 'block';
                break;
            case 'schools_colleges':
                document.getElementById('kyc_schools_colleges').style.display = 'block';
                break;
            case 'banks':
                document.getElementById('kyc_banks').style.display = 'block';
                break;
        }
        
        // Show other KYC points
        document.getElementById('kyc_point_2').style.display = 'block';
        document.getElementById('kyc_point_3').style.display = 'block';
        document.getElementById('kyc_point_4').style.display = 'block';
        document.getElementById('kyc_point_5').style.display = 'block';
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    showStep(1);
    
    // Fetch IP pricing on page load
    fetchIpPricing();
    
    // Ensure fetchGstDetails is available globally
    window.fetchGstDetails = fetchGstDetails;
    
    // Add event listeners for applicant designation field
    const applicantDesignation = document.getElementById('applicant_designation');
    if (applicantDesignation) {
        applicantDesignation.addEventListener('input', captureManagementRepresentative);
    }
    
    // Add event listeners for MR fields if they exist (for backward compatibility)
    ['mr_name', 'mr_email', 'mr_designation', 'mr_mobile'].forEach(id => {
        const field = document.getElementById(id);
        if (field) {
            field.addEventListener('input', captureManagementRepresentative);
        }
    });
    
    captureManagementRepresentative();
    
    // Initialize KYC documents if step 4 is shown
    const affiliateIdentity = document.getElementById('affiliate_identity');
    if (affiliateIdentity && affiliateIdentity.value) {
        updateKycDocuments();
    }
});
</script>
@endpush

