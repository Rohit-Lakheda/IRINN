<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminGrievanceController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\AdminReactivationRequestController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\IxApplicationController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileUpdateRequestController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SuperAdmin\InvoiceBackfillController as SuperAdminInvoiceBackfillController;
use App\Http\Controllers\SuperAdmin\IxLocationController as SuperAdminIxLocationController;
use App\Http\Controllers\SuperAdmin\IxPortPricingController as SuperAdminIxPortPricingController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\SuperAdminGrievanceController;
use App\Http\Controllers\SuperAdminLoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserGrievanceController;
use App\Http\Controllers\UserKycController;
use App\Http\Controllers\UserReactivationRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Home Route
Route::get('/', function () {
    return view('welcome');
});

// SuperAdmin Login Routes (Public - no authentication required)
Route::prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/login', [SuperAdminLoginController::class, 'index'])->name('login');
    Route::post('/login', [SuperAdminLoginController::class, 'login'])->name('login.submit');
    Route::get('/login/verify', [SuperAdminLoginController::class, 'showVerify'])->name('login.verify');
    Route::post('/login/verify', [SuperAdminLoginController::class, 'verifyOtp'])->name('login.verify.otp');
    Route::post('/login/resend-otp', [SuperAdminLoginController::class, 'resendOtp'])->name('login.resend-otp');
    Route::post('/logout', [SuperAdminLoginController::class, 'logout'])->name('logout');
});

// SuperAdmin Routes (Requires authentication)
Route::prefix('superadmin')->name('superadmin.')->middleware(['superadmin'])->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'index'])->name('dashboard');

    // Backend Data Entry routes
    Route::get('/backend-data-entry', [\App\Http\Controllers\BackendDataEntryController::class, 'index'])->name('backend-data-entry');
    Route::post('/backend-data-entry/verify-pan', [\App\Http\Controllers\BackendDataEntryController::class, 'verifyPan'])->name('backend-data-entry.verify-pan');
    Route::post('/backend-data-entry', [\App\Http\Controllers\BackendDataEntryController::class, 'store'])->name('backend-data-entry.store');

    // IX Points management
    Route::get('/ix-points', [SuperAdminController::class, 'ixPoints'])->name('ix-points');
    Route::get('/ix-points/{id}', [SuperAdminController::class, 'showIxPoint'])->name('ix-points.show');

    // User management
    Route::get('/users', [SuperAdminController::class, 'users'])->name('users');
    Route::get('/users/{id}', [SuperAdminController::class, 'showUser'])->name('users.show');
    // View-only: do not allow destructive actions from Super Admin on admin-style pages.
    Route::get('/members', [SuperAdminController::class, 'members'])->name('members');
    Route::get('/members/{id}', [SuperAdminController::class, 'showMember'])->name('members.show');

    // Applications routes
    Route::get('/applications', [SuperAdminController::class, 'applications'])->name('applications.index');
    // Specific routes must come before parameterized routes
    Route::get('/applications/backend-data-entry', [SuperAdminController::class, 'backendDataEntryApplications'])->name('applications.backend-data-entry');
    Route::post('/applications/backend-data-entry/bulk-approve', [SuperAdminController::class, 'bulkApproveBackendApplications'])->name('applications.backend-data-entry.bulk-approve');
    Route::get('/applications/{id}', [SuperAdminController::class, 'showApplication'])->name('applications.show');
    // View-only: no approval/disapproval or member/service-status actions for Super Admin (use Admin panel).

    // Admin management
    Route::get('/admins', [SuperAdminController::class, 'admins'])->name('admins');
    Route::get('/admins/create', [SuperAdminController::class, 'createAdmin'])->name('admins.create');
    Route::post('/admins/check-employee-id', [SuperAdminController::class, 'checkEmployeeId'])->name('admins.check-employee-id');
    Route::post('/admins', [SuperAdminController::class, 'storeAdmin'])->name('admins.store');
    Route::get('/admins/{id}', [SuperAdminController::class, 'showAdmin'])->name('admins.show');
    Route::get('/admins/{id}/edit', [SuperAdminController::class, 'editAdmin'])->name('admins.edit');
    Route::post('/admins/{id}', [SuperAdminController::class, 'updateAdmin'])->name('admins.update');
    Route::get('/admins/{id}/edit-details', [SuperAdminController::class, 'editAdminDetails'])->name('admins.edit-details');
    Route::post('/admins/{id}/update-details', [SuperAdminController::class, 'updateAdminDetails'])->name('admins.update-details');
    Route::get('/admins/{id}/edit-type', [SuperAdminController::class, 'editAdminType'])->name('admins.edit-type');
    Route::post('/admins/{id}/update-type', [SuperAdminController::class, 'updateAdminType'])->name('admins.update-type');
    Route::patch('/admins/{id}/toggle-status', [SuperAdminController::class, 'toggleAdminStatus'])->name('admins.toggle-status');

    // Messages management
    Route::get('/messages', [SuperAdminController::class, 'messages'])->name('messages');
    Route::get('/messages/{id}', [SuperAdminController::class, 'showMessage'])->name('messages.show');

    // IP Pricing management
    Route::get('/ip-pricing', [\App\Http\Controllers\SuperAdmin\IpPricingController::class, 'index'])->name('ip-pricing.index');
    Route::post('/ip-pricing', [\App\Http\Controllers\SuperAdmin\IpPricingController::class, 'store'])->name('ip-pricing.store');
    Route::put('/ip-pricing/{id}', [\App\Http\Controllers\SuperAdmin\IpPricingController::class, 'update'])->name('ip-pricing.update');
    Route::patch('/ip-pricing/{id}/toggle-status', [\App\Http\Controllers\SuperAdmin\IpPricingController::class, 'toggleStatus'])->name('ip-pricing.toggle-status');
    Route::delete('/ip-pricing/{id}', [\App\Http\Controllers\SuperAdmin\IpPricingController::class, 'destroy'])->name('ip-pricing.destroy');
    Route::get('/ip-pricing/{id}/history', [\App\Http\Controllers\SuperAdmin\IpPricingController::class, 'history'])->name('ip-pricing.history');

    // IX Location management
    Route::get('/ix-locations', [SuperAdminIxLocationController::class, 'index'])->name('ix-locations.index');
    Route::post('/ix-locations', [SuperAdminIxLocationController::class, 'store'])->name('ix-locations.store');
    Route::put('/ix-locations/{ixLocation}', [SuperAdminIxLocationController::class, 'update'])->name('ix-locations.update');
    Route::patch('/ix-locations/{ixLocation}/toggle', [SuperAdminIxLocationController::class, 'toggleStatus'])->name('ix-locations.toggle');
    Route::delete('/ix-locations/{ixLocation}', [SuperAdminIxLocationController::class, 'destroy'])->name('ix-locations.destroy');
    Route::get('/ix-locations/{ixLocation}/history', [SuperAdminIxLocationController::class, 'history'])->name('ix-locations.history');

    // IX Port pricing management
    Route::get('/ix-port-pricing', [SuperAdminIxPortPricingController::class, 'index'])->name('ix-port-pricing.index');
    Route::post('/ix-port-pricing', [SuperAdminIxPortPricingController::class, 'store'])->name('ix-port-pricing.store');
    Route::put('/ix-port-pricing/{ixPortPricing}', [SuperAdminIxPortPricingController::class, 'update'])->name('ix-port-pricing.update');
    Route::patch('/ix-port-pricing/{ixPortPricing}/toggle', [SuperAdminIxPortPricingController::class, 'toggleStatus'])->name('ix-port-pricing.toggle');
    Route::delete('/ix-port-pricing/{ixPortPricing}', [SuperAdminIxPortPricingController::class, 'destroy'])->name('ix-port-pricing.destroy');

    // IX Application pricing management
    Route::get('/ix-application-pricing', [\App\Http\Controllers\SuperAdmin\IxApplicationPricingController::class, 'index'])->name('ix-application-pricing.index');
    Route::post('/ix-application-pricing', [\App\Http\Controllers\SuperAdmin\IxApplicationPricingController::class, 'store'])->name('ix-application-pricing.store');
    Route::put('/ix-application-pricing/{ixApplicationPricing}', [\App\Http\Controllers\SuperAdmin\IxApplicationPricingController::class, 'update'])->name('ix-application-pricing.update');
    Route::patch('/ix-application-pricing/{ixApplicationPricing}/toggle', [\App\Http\Controllers\SuperAdmin\IxApplicationPricingController::class, 'toggleStatus'])->name('ix-application-pricing.toggle');
    Route::delete('/ix-application-pricing/{ixApplicationPricing}', [\App\Http\Controllers\SuperAdmin\IxApplicationPricingController::class, 'destroy'])->name('ix-application-pricing.destroy');

    // Reactivation fee settings
    Route::get('/reactivation-fee', [SuperAdminController::class, 'reactivationFee'])->name('reactivation-fee');
    Route::post('/reactivation-fee', [SuperAdminController::class, 'updateReactivationFee'])->name('reactivation-fee.update');

    // IX Membership fee settings
    Route::get('/ix-membership-fee', [SuperAdminController::class, 'ixMembershipFee'])->name('ix-membership-fee');
    Route::post('/ix-membership-fee', [SuperAdminController::class, 'updateIxMembershipFee'])->name('ix-membership-fee.update');
    Route::post('/ix-membership-fee/generate', [SuperAdminController::class, 'generateIxMembershipInvoices'])->name('ix-membership-fee.generate');

    // Nodal Officer Email management
    Route::get('/nodal-officer-emails', [SuperAdminController::class, 'nodalOfficerEmails'])->name('nodal-officer-emails');
    Route::post('/nodal-officer-emails', [SuperAdminController::class, 'storeNodalOfficerEmail'])->name('nodal-officer-emails.store');
    Route::put('/nodal-officer-emails/{id}', [SuperAdminController::class, 'updateNodalOfficerEmail'])->name('nodal-officer-emails.update');
    Route::delete('/nodal-officer-emails/{id}', [SuperAdminController::class, 'destroyNodalOfficerEmail'])->name('nodal-officer-emails.destroy');
    Route::patch('/nodal-officer-emails/{id}/toggle-status', [SuperAdminController::class, 'toggleNodalOfficerEmailStatus'])->name('nodal-officer-emails.toggle-status');

    // Grievance routes
    Route::prefix('grievance')->name('grievance.')->group(function () {
        Route::get('/', [SuperAdminGrievanceController::class, 'index'])->name('index');
        Route::get('/attachments/{id}/download', [SuperAdminGrievanceController::class, 'downloadAttachment'])->name('attachments.download');
        Route::get('/admins-by-role', [SuperAdminGrievanceController::class, 'getAdminsByRole'])->name('admins-by-role');
        Route::get('/{id}', [SuperAdminGrievanceController::class, 'show'])->name('show');
        Route::post('/{id}/assign', [SuperAdminGrievanceController::class, 'assign'])->name('assign');
        Route::post('/{id}/unassign', [SuperAdminGrievanceController::class, 'unassign'])->name('unassign');
    });

    // Grievance Management routes (for managing categories, subcategories, assignments, transfer rules)
    Route::prefix('grievance-management')->name('grievance-management.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SuperAdmin\GrievanceManagementController::class, 'index'])->name('index');

        // Categories
        Route::post('/categories', [\App\Http\Controllers\SuperAdmin\GrievanceManagementController::class, 'storeCategory'])->name('categories.store');
        Route::post('/categories/{category}', [\App\Http\Controllers\SuperAdmin\GrievanceManagementController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [\App\Http\Controllers\SuperAdmin\GrievanceManagementController::class, 'deleteCategory'])->name('categories.delete');

        // Subcategories
        Route::post('/subcategories', [\App\Http\Controllers\SuperAdmin\GrievanceManagementController::class, 'storeSubcategory'])->name('subcategories.store');
        Route::post('/subcategories/{subcategory}', [\App\Http\Controllers\SuperAdmin\GrievanceManagementController::class, 'updateSubcategory'])->name('subcategories.update');
        Route::delete('/subcategories/{subcategory}', [\App\Http\Controllers\SuperAdmin\GrievanceManagementController::class, 'deleteSubcategory'])->name('subcategories.delete');

        // Assignments
        Route::post('/assignments', [\App\Http\Controllers\SuperAdmin\GrievanceManagementController::class, 'storeAssignment'])->name('assignments.store');
        Route::post('/assignments/{assignment}', [\App\Http\Controllers\SuperAdmin\GrievanceManagementController::class, 'updateAssignment'])->name('assignments.update');
        Route::delete('/assignments/{assignment}', [\App\Http\Controllers\SuperAdmin\GrievanceManagementController::class, 'deleteAssignment'])->name('assignments.delete');

        // Transfer Rules
        Route::post('/transfer-rules', [\App\Http\Controllers\SuperAdmin\GrievanceManagementController::class, 'storeTransferRule'])->name('transfer-rules.store');
        Route::post('/transfer-rules/{transferRule}', [\App\Http\Controllers\SuperAdmin\GrievanceManagementController::class, 'updateTransferRule'])->name('transfer-rules.update');
        Route::delete('/transfer-rules/{transferRule}', [\App\Http\Controllers\SuperAdmin\GrievanceManagementController::class, 'deleteTransferRule'])->name('transfer-rules.delete');

        // Escalation Settings
        Route::post('/escalation-settings', [\App\Http\Controllers\SuperAdmin\GrievanceManagementController::class, 'updateEscalationSettings'])->name('escalation.update');

        // Quick Workflow Setup (assignment + forwarding chain)
        Route::post('/quick-setup', [\App\Http\Controllers\SuperAdmin\GrievanceManagementController::class, 'quickSetupWorkflow'])->name('workflow.quick-setup');
    });

    // Invoice management routes
    Route::prefix('invoices')->name('invoices.')->group(function () {
        // Backfill: create already-paid application-fee invoices for selected applications (no email)
        // IMPORTANT: must be defined before the `/{id}` routes to avoid route conflicts.
        Route::get('/backfill-paid', [SuperAdminInvoiceBackfillController::class, 'index'])->name('backfill-paid.index');
        Route::post('/backfill-paid', [SuperAdminInvoiceBackfillController::class, 'store'])->name('backfill-paid.store');

        Route::get('/', [SuperAdminController::class, 'invoices'])->name('index');
        Route::get('/{id}', [SuperAdminController::class, 'showInvoice'])->whereNumber('id')->name('show');
        Route::get('/{id}/download', [SuperAdminController::class, 'downloadInvoice'])->whereNumber('id')->name('download');
        // View-only: invoice status should not be updated from Super Admin.
    });
});

// Admin Login Routes (Public - no authentication required)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'index'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login'])->name('login.submit');
    Route::get('/login/verify', [AdminLoginController::class, 'showVerify'])->name('login.verify');
    Route::post('/login/verify', [AdminLoginController::class, 'verifyOtp'])->name('login.verify.otp');
    Route::post('/login/resend-otp', [AdminLoginController::class, 'resendOtp'])->name('login.resend-otp');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');
});

// Admin Routes (Requires authentication)
Route::prefix('admin')->name('admin.')->middleware(['admin'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');

    // User management
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/export', [AdminController::class, 'exportUsersToExcel'])->name('users.export');
    Route::get('/users/{id}', [AdminController::class, 'showUser'])->name('users.show');
    Route::post('/users/{id}/update-email', [AdminController::class, 'updateUserEmail'])->name('users.update-email');
    Route::get('/users/{id}/transactions/export', [AdminController::class, 'exportUserTransactions'])->name('users.transactions.export');
    Route::get('/users/{id}/admin-actions/export', [AdminController::class, 'exportUserAdminActions'])->name('users.admin-actions.export');
    Route::post('/users/{id}/send-credentials', [AdminController::class, 'sendCredentials'])->name('users.send-credentials');
    Route::post('/users/{id}/send-message', [AdminController::class, 'sendMessage'])->name('users.send-message');
    Route::post('/users/{id}/update-status', [AdminController::class, 'updateUserStatus'])->name('users.update-status');
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('users.delete');

    // Members management (users with membership_id)
    Route::get('/members', [AdminController::class, 'members'])->name('members');
    Route::get('/members/export', [AdminController::class, 'exportMembersToExcel'])->name('members.export');
    Route::get('/members/export-invoice-amounts', [AdminController::class, 'exportInvoiceAmounts'])->name('members.export-invoice-amounts');
    Route::get('/members/export-gst-verification', [AdminController::class, 'exportGstVerificationReport'])->name('members.export-gst-verification');
    Route::get('/applications/export', [AdminController::class, 'exportApplicationsToExcel'])->name('applications.export');
    Route::post('/applications/{applicationId}/toggle-member-status', [AdminController::class, 'toggleMemberStatus'])->name('applications.toggle-member-status');
    Route::post('/applications/{applicationId}/service-status', [AdminController::class, 'updateServiceStatus'])->name('applications.service-status');
    Route::post('/applications/{id}/seller-gst/update', [AdminController::class, 'updateSellerGstForApplication'])->name('applications.seller-gst.update');
    Route::post('/applications/{applicationId}/service-timeline/reset', [AdminController::class, 'resetServiceTimeline'])->name('applications.service-timeline.reset');

    // Profile update requests
    Route::get('/profile-update-requests', [AdminController::class, 'profileUpdateRequests'])->name('profile-update-requests');
    Route::get('/profile-update-requests/{id}', [AdminController::class, 'showProfileUpdateRequest'])->name('profile-update-requests.show');
    Route::post('/profile-updates/{id}/approve', [AdminController::class, 'approveProfileUpdate'])->name('profile-updates.approve');
    Route::post('/profile-updates/{id}/approve-submitted', [AdminController::class, 'approveSubmittedUpdate'])->name('profile-updates.approve-submitted');
    Route::post('/profile-updates/{id}/reject', [AdminController::class, 'rejectProfileUpdate'])->name('profile-updates.reject');

    // GST update requests
    Route::get('/gst-update-requests', [AdminController::class, 'gstUpdateRequests'])->name('gst-update-requests');
    Route::get('/gst-update-requests/{id}', [AdminController::class, 'showGstUpdateRequest'])->name('gst-update-requests.show');
    Route::post('/gst-update-requests/{id}/approve', [AdminController::class, 'approveGstUpdateRequest'])->name('gst-update-requests.approve');
    Route::post('/gst-update-requests/{id}/reject', [AdminController::class, 'rejectGstUpdateRequest'])->name('gst-update-requests.reject');

    // Reactivation requests (disconnected applications only)
    Route::prefix('reactivation-requests')->name('reactivation-requests.')->group(function () {
        Route::get('/', [AdminReactivationRequestController::class, 'index'])->name('index');
        Route::post('/{id}/approve', [AdminReactivationRequestController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [AdminReactivationRequestController::class, 'reject'])->name('reject');
        Route::post('/{id}/set-date', [AdminReactivationRequestController::class, 'setReactivationDate'])->name('set-date');
    });

    // Messages
    Route::get('/messages', [AdminController::class, 'messages'])->name('messages');
    Route::get('/messages/{id}', [AdminController::class, 'showMessage'])->name('messages.show');

    // Requests and Messages combined page
    Route::get('/requests-messages', [AdminController::class, 'requestsAndMessages'])->name('requests-messages');

    // Bulk Notification
    Route::get('/bulk-notification', [AdminController::class, 'bulkNotification'])->name('bulk-notification');
    Route::post('/bulk-notification/send', [AdminController::class, 'sendBulkNotification'])->name('bulk-notification.send');

    // Cron Job Report (IX invoice cron logs)
    Route::get('/cron-report', [AdminController::class, 'cronReport'])->name('cron-report');
    Route::get('/cron-report/export', [AdminController::class, 'exportCronReport'])->name('cron-report.export');

    // Backend Data Entry routes
    Route::get('/backend-data-entry', [\App\Http\Controllers\BackendDataEntryController::class, 'index'])->name('backend-data-entry');
    Route::post('/backend-data-entry/verify-pan', [\App\Http\Controllers\BackendDataEntryController::class, 'verifyPan'])->name('backend-data-entry.verify-pan');
    Route::post('/backend-data-entry', [\App\Http\Controllers\BackendDataEntryController::class, 'store'])->name('backend-data-entry.store');

    // Application management routes
    Route::get('/applications', [AdminController::class, 'applications'])->name('applications');
    Route::get('/applications/update-kyc-details', [AdminController::class, 'updateKycDetails'])->name('applications.update-kyc-details');
    Route::post('/applications/process-update-kyc-details', [AdminController::class, 'processUpdateKycDetails'])->name('applications.process-update-kyc-details');
    Route::get('/applications/{id}/document', [AdminController::class, 'serveDocument'])->name('applications.document');
    Route::get('/applications/{id}/edit', [AdminController::class, 'editApplication'])->name('applications.edit');
    Route::post('/applications/{id}/update', [AdminController::class, 'updateApplication'])->name('applications.update');
    Route::post('/applications/{id}/update-comprehensive', [AdminController::class, 'updateApplicationComprehensive'])->name('applications.update-comprehensive');
    Route::post('/applications/{id}/verify-gst', [AdminController::class, 'verifyGstForApplication'])->name('applications.verify-gst');
    Route::post('/applications/{id}/check-gst-status', [AdminController::class, 'checkGstVerificationStatus'])->name('applications.check-gst-status');
    Route::post('/applications/{id}/complete-kyc', [AdminController::class, 'completeKycForApplication'])->name('applications.complete-kyc');
    Route::get('/applications/{id}', [AdminController::class, 'showApplication'])->name('applications.show');
    Route::get('/applications/{id}/comprehensive', [AdminController::class, 'showApplicationComprehensive'])->name('applications.show-comprehensive');

    // IRINN workflow routes (helpdesk -> hostmaster -> billing + resubmission)
    Route::post('/applications/{id}/irinn/change-stage', [AdminController::class, 'irinnChangeStage'])->name('applications.irinn.change-stage');
    Route::post('/applications/{id}/irinn/request-resubmission', [AdminController::class, 'irinnRequestResubmission'])->name('applications.irinn.request-resubmission');

    // Legacy Processor routes (for backward compatibility)
    Route::post('/applications/{id}/approve-to-finance', [AdminController::class, 'approveToFinance'])->name('applications.approve-to-finance');

    // Legacy Finance routes (for backward compatibility)
    Route::post('/applications/{id}/approve-to-technical', [AdminController::class, 'approveToTechnical'])->name('applications.approve-to-technical');
    Route::post('/applications/{id}/send-back-to-processor', [AdminController::class, 'sendBackToProcessor'])->name('applications.send-back-to-processor');

    // Legacy Technical routes (for backward compatibility)
    Route::post('/applications/{id}/approve', [AdminController::class, 'approveApplication'])->name('applications.approve');
    Route::post('/applications/{id}/send-back-to-finance', [AdminController::class, 'sendBackToFinance'])->name('applications.send-back-to-finance');

    // New IX Workflow routes
    // IX Processor routes
    Route::post('/applications/{id}/ix-processor/forward-to-legal', [AdminController::class, 'ixProcessorForwardToLegal'])->name('applications.ix-processor.forward-to-legal');
    Route::post('/applications/{id}/ix-processor/request-resubmission', [AdminController::class, 'ixProcessorRequestResubmission'])->name('applications.ix-processor.request-resubmission');

    // IX Legal routes
    Route::post('/applications/{id}/ix-legal/forward-to-head', [AdminController::class, 'ixLegalForwardToHead'])->name('applications.ix-legal.forward-to-head');
    Route::post('/applications/{id}/ix-legal/send-back-to-processor', [AdminController::class, 'ixLegalSendBackToProcessor'])->name('applications.ix-legal.send-back-to-processor');

    // IX Head routes
    Route::post('/applications/{id}/ix-head/forward-to-ceo', [AdminController::class, 'ixHeadForwardToCeo'])->name('applications.ix-head.forward-to-ceo');
    Route::post('/applications/{id}/ix-head/send-back-to-processor', [AdminController::class, 'ixHeadSendBackToProcessor'])->name('applications.ix-head.send-back-to-processor');

    // CEO routes
    Route::post('/applications/{id}/ceo/approve', [AdminController::class, 'ceoApprove'])->name('applications.ceo.approve');
    Route::post('/applications/{id}/ceo/reject', [AdminController::class, 'ceoReject'])->name('applications.ceo.reject');
    Route::post('/applications/{id}/ceo/send-back-to-head', [AdminController::class, 'ceoSendBackToHead'])->name('applications.ceo.send-back-to-head');

    // Nodal Officer routes
    Route::post('/applications/{id}/nodal-officer/assign-port', [AdminController::class, 'nodalOfficerAssignPort'])->name('applications.nodal-officer.assign-port');
    Route::post('/applications/{id}/nodal-officer/hold', [AdminController::class, 'nodalOfficerHold'])->name('applications.nodal-officer.hold');
    Route::post('/applications/{id}/nodal-officer/not-feasible', [AdminController::class, 'nodalOfficerNotFeasible'])->name('applications.nodal-officer.not-feasible');
    Route::post('/applications/{id}/nodal-officer/customer-denied', [AdminController::class, 'nodalOfficerCustomerDenied'])->name('applications.nodal-officer.customer-denied');
    Route::post('/applications/{id}/nodal-officer/forward-to-processor', [AdminController::class, 'nodalOfficerForwardToProcessor'])->name('applications.nodal-officer.forward-to-processor');

    // IX Tech Team routes
    Route::post('/applications/{id}/ix-tech-team/assign-ip', [AdminController::class, 'ixTechTeamAssignIp'])->name('applications.ix-tech-team.assign-ip');

    // IX Account routes
    Route::get('/applications/{id}/ix-account/generate-invoice', [AdminController::class, 'ixAccountShowInvoiceForm'])->name('applications.ix-account.generate-invoice');
    Route::get('/applications/{id}/ix-account/generate-pending-invoice', [AdminController::class, 'ixAccountShowPendingInvoiceForm'])->name('applications.ix-account.generate-pending-invoice');
    Route::post('/applications/{id}/ix-account/generate-invoice', [AdminController::class, 'ixAccountGenerateInvoice'])->name('applications.ix-account.generate-invoice.store');
    Route::post('/applications/{id}/ix-account/verify-payment', [AdminController::class, 'ixAccountVerifyPayment'])->name('applications.ix-account.verify-payment');

    // Grievance routes
    Route::prefix('grievance')->name('grievance.')->group(function () {
        Route::get('/', [AdminGrievanceController::class, 'index'])->name('index');
        Route::get('/attachments/{id}/download', [AdminGrievanceController::class, 'downloadAttachment'])->name('attachments.download');
        Route::get('/{id}', [AdminGrievanceController::class, 'show'])->name('show');
        Route::post('/{id}/reply', [AdminGrievanceController::class, 'reply'])->name('reply');
        Route::post('/{id}/forward', [AdminGrievanceController::class, 'forward'])->name('forward');
        Route::post('/{id}/resolve', [AdminGrievanceController::class, 'resolve'])->name('resolve');
        Route::post('/{id}/close', [AdminGrievanceController::class, 'close'])->name('close');
    });

    // IX Account invoice manual payment
    Route::post('/applications/invoice/{invoice}/mark-paid', [AdminController::class, 'ixAccountMarkInvoicePaid'])->name('applications.invoice.mark-paid');

    // IX Account invoice management
    Route::get('/applications/invoice/{invoice}/edit', [AdminController::class, 'ixAccountEditInvoice'])->name('applications.invoice.edit');
    Route::put('/applications/invoice/{invoice}', [AdminController::class, 'ixAccountUpdateInvoice'])->name('applications.invoice.update');
    Route::delete('/applications/invoice/{invoice}', [AdminController::class, 'ixAccountDeleteInvoice'])->name('applications.invoice.delete');
    Route::post('/applications/invoice/{invoice}/cancel', [AdminController::class, 'ixAccountCancelInvoice'])->name('applications.invoice.cancel');
    Route::post('/applications/invoice/{invoice}/credit-note', [AdminController::class, 'ixAccountGenerateCreditNote'])->name('applications.invoice.credit-note');
    Route::post('/applications/invoice/{invoice}/change-status', [AdminController::class, 'ixAccountChangeInvoiceStatus'])->name('applications.invoice.change-status');
    Route::post('/applications/invoice/{invoice}/mark-unpaid', [AdminController::class, 'ixAccountMarkInvoiceUnpaid'])->name('applications.invoice.mark-unpaid');
    Route::get('/applications/invoice/{invoice}/download', [AdminController::class, 'downloadInvoice'])->name('applications.invoice.download');

    // IX Account payment allocation
    Route::get('/applications/payment-allocation', [AdminController::class, 'showPaymentAllocationForm'])->name('applications.payment-allocation.form');
    Route::get('/applications/search-users', [AdminController::class, 'searchUsersForAllocation'])->name('applications.search-users');
    Route::post('/applications/allocate-payment', [AdminController::class, 'ixAccountAllocatePayment'])->name('applications.allocate-payment');
    Route::get('/applications/user/{userId}/invoices', [AdminController::class, 'getUserInvoicesForAllocation'])->name('applications.user.invoices');

    // Invoice routes
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [AdminController::class, 'invoices'])->name('index');
        Route::get('/{id}/download', [AdminController::class, 'downloadInvoice'])->name('download');
    });

    // Plan change routes
    Route::prefix('plan-change')->name('plan-change.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AdminPlanChangeRequestController::class, 'index'])->name('index');
        Route::get('/{id}', [\App\Http\Controllers\AdminPlanChangeRequestController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [\App\Http\Controllers\AdminPlanChangeRequestController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [\App\Http\Controllers\AdminPlanChangeRequestController::class, 'reject'])->name('reject');
        Route::delete('/{id}', [\App\Http\Controllers\AdminPlanChangeRequestController::class, 'destroy'])->name('destroy');
    });

    // IX Points routes
    Route::get('/ix-points', [AdminController::class, 'ixPoints'])->name('ix-points');
    Route::get('/ix-points/{id}', [AdminController::class, 'showIxPoint'])->name('ix-points.show');
    Route::get('/ix-points/{id}/applications', [AdminController::class, 'ixPointApplications'])->name('ix-points.applications');
    Route::get('/ix-points/{id}/members', [AdminController::class, 'ixPointMembers'])->name('ix-points.members');
});

// Register Routes (Public - no authentication required)
Route::prefix('register')->name('register.')->group(function () {
    Route::get('/', [RegisterController::class, 'index'])->name('index');
    Route::post('/', [RegisterController::class, 'store'])->name('store');
    Route::post('/send-email-otp', [RegisterController::class, 'sendEmailOtp'])->name('send.email.otp');
    Route::post('/send-mobile-otp', [RegisterController::class, 'sendMobileOtp'])->name('send.mobile.otp');
    Route::post('/verify-email-otp', [RegisterController::class, 'verifyEmailOtp'])->name('verify.email.otp');
    Route::post('/verify-mobile-otp', [RegisterController::class, 'verifyMobileOtp'])->name('verify.mobile.otp');
    Route::post('/verify-pan', [RegisterController::class, 'verifyPan'])->name('verify.pan');
    Route::post('/check-pan-status', [RegisterController::class, 'checkPanStatus'])->name('check.pan.status');
    // Legacy routes (can be removed if not needed)
    Route::get('/verify', [RegisterController::class, 'showVerify'])->name('verify');
    Route::post('/verify', [RegisterController::class, 'verifyOtp'])->name('verify.otp');
    Route::post('/resend-otp', [RegisterController::class, 'resendOtp'])->name('resend.otp');
    // Add more Register routes here
});

// Login Routes (Public - no authentication required)
// Route::get('/login', function () {
//     return redirect()->away('https://staging-ix.nixi.in/login');
// });

Route::prefix('login')->name('login.')->group(function () {
    Route::get('/', [LoginController::class, 'index'])->name('index');
    Route::post('/', [LoginController::class, 'login'])->name('submit');
    Route::get('/verify', [LoginController::class, 'showVerify'])->name('verify');
    Route::post('/verify', [LoginController::class, 'verifyOtp'])->name('verify.otp');
    Route::post('/resend-otp', [LoginController::class, 'resendOtp'])->name('resend.otp');
    Route::get('/forgot-password', [LoginController::class, 'showForgotPassword'])->name('forgot-password');
    Route::post('/forgot-password', [LoginController::class, 'forgotPassword'])->name('forgot-password.submit');
    Route::get('/reset-password/{token}', [LoginController::class, 'showResetPassword'])->name('reset-password');
    Route::post('/reset-password', [LoginController::class, 'resetPassword'])->name('reset-password.submit');
    Route::get('/update-password/{token}', [LoginController::class, 'showUpdatePassword'])->name('update-password');
    Route::post('/update-password', [LoginController::class, 'updatePassword'])->name('update-password.submit');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

// User Routes (Requires authentication)
Route::prefix('user')->name('user.')->middleware(['user.auth'])->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::get('/profile/edit', [UserController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [UserController::class, 'update'])->name('profile.update');
    Route::post('/profile/verify-gst', [UserController::class, 'verifyGst'])->name('profile.verify-gst');
    Route::post('/profile/check-gst-status', [UserController::class, 'checkGstStatus'])->name('profile.check-gst-status');
    Route::post('/profile/complete-kyc', [UserController::class, 'completeKyc'])->name('profile.complete-kyc');

    // IRINN create (short URL: /user/irinn/create)
    Route::get('irinn/create', [ApplicationController::class, 'createIrinNew'])->name('irinn.create');

    // Application-specific GST update routes
    Route::get('/applications/{application}/gst/edit', [UserController::class, 'editApplicationGst'])->name('applications.gst.edit');
    Route::post('/applications/{application}/gst/update', [UserController::class, 'updateApplicationGst'])->name('applications.gst.update');
    Route::post('/applications/{application}/gst/verify-gst', [UserController::class, 'verifyApplicationGst'])->name('applications.gst.verify-gst');

    // KYC routes
    Route::get('/kyc', [UserKycController::class, 'show'])->name('kyc.show');
    Route::post('/kyc', [UserKycController::class, 'store'])->name('kyc.store');

    // Messages routes
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::get('/{id}', [MessageController::class, 'show'])->name('show');
        Route::post('/{id}/reply', [MessageController::class, 'reply'])->name('reply');
        Route::post('/{id}/mark-read', [MessageController::class, 'markAsRead'])->name('mark-read');
        Route::post('/mark-all-read', [MessageController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::get('/unread/count', [MessageController::class, 'unreadCount'])->name('unread.count');
    });

    // Profile update request routes
    Route::prefix('profile-update')->name('profile-update.')->group(function () {
        Route::get('/request', [ProfileUpdateRequestController::class, 'create'])->name('request');
        Route::post('/request', [ProfileUpdateRequestController::class, 'store'])->name('store');
        Route::get('/edit', [ProfileUpdateRequestController::class, 'edit'])->name('edit');
        Route::post('/update', [ProfileUpdateRequestController::class, 'update'])->name('update');
        Route::post('/send-email-otp', [ProfileUpdateRequestController::class, 'sendEmailOtp'])->name('send-email-otp');
        Route::post('/send-mobile-otp', [ProfileUpdateRequestController::class, 'sendMobileOtp'])->name('send-mobile-otp');
        Route::post('/verify-email-otp', [ProfileUpdateRequestController::class, 'verifyEmailOtp'])->name('verify-email-otp');
        Route::post('/verify-mobile-otp', [ProfileUpdateRequestController::class, 'verifyMobileOtp'])->name('verify-mobile-otp');
    });

    // Applications routes (only for approved users)
    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('/', [ApplicationController::class, 'index'])->name('index');

        // IRINN Application routes (must be before {id} route)
        Route::prefix('irin')->name('irin.')->group(function () {
            //Route::get('/create', [ApplicationController::class, 'createIrin'])->name('create');
            Route::get('/create-new', fn () => redirect()->route('user.irinn.create', [], 301))->name('create-new');
            Route::get('/preview', [ApplicationController::class, 'previewIrin'])->name('preview');
            Route::get('/preview-document/{doc}', [ApplicationController::class, 'previewIrinDocument'])->name('preview-document');
            Route::get('/download-agreement', [ApplicationController::class, 'downloadIrinAgreement'])->name('download-agreement');
            Route::post('/fetch-gst', [ApplicationController::class, 'fetchGstDetails'])->name('fetch-gst');
            Route::post('/verify-gst', [ApplicationController::class, 'verifyGst'])->name('verify-gst');
            Route::post('/verify-udyam', [ApplicationController::class, 'verifyUdyam'])->name('verify-udyam');
            Route::post('/verify-mca', [ApplicationController::class, 'verifyMca'])->name('verify-mca');
            Route::post('/verify-roc-iec', [ApplicationController::class, 'verifyRocIec'])->name('verify-roc-iec');
            Route::post('/check-verification-status', [ApplicationController::class, 'checkVerificationStatus'])->name('check-verification-status');
            Route::post('/store', [ApplicationController::class, 'storeIrin'])->name('store');
            Route::post('/store-new', [ApplicationController::class, 'storeIrinNew'])->name('store-new');
            Route::get('/pricing', [ApplicationController::class, 'getIpPricing'])->name('pricing');
            // NOTE: PayU callbacks MUST be outside auth middleware; see global routes below.
            // Route::match(['get', 'post'], '/payment-success', [ApplicationController::class, 'paymentSuccess'])->name('payment-success');
            // Route::match(['get', 'post'], '/payment-failure', [ApplicationController::class, 'paymentFailure'])->name('payment-failure');
            Route::post('/initiate-payment-with-wallet', [ApplicationController::class, 'initiatePaymentWithWallet'])->name('initiate-payment-with-wallet');
            Route::post('/{id}/pay-now-with-wallet', [ApplicationController::class, 'payNowWithWallet'])->name('pay-now-with-wallet');
            Route::get('/{id}/resubmit', [ApplicationController::class, 'editResubmit'])->name('resubmit');
            Route::post('/{id}/resubmit', [ApplicationController::class, 'storeResubmission'])->name('resubmit.store');
            Route::get('/{id}/document/{doc}', [ApplicationController::class, 'serveResubmitDocument'])->name('resubmit.document');
        });

        // IX Application routes
        Route::prefix('ix')->name('ix.')->group(function () {
            // Route::get('/create', [IxApplicationController::class, 'create'])->name('create');
            // Route::get('/create-new', [IxApplicationController::class, 'createNew'])->name('create-new');
            Route::post('/submit', [IxApplicationController::class, 'store'])->name('store');
            Route::post('/initiate-payment', [IxApplicationController::class, 'initiatePayment'])->name('initiate-payment');
            Route::post('/initiate-payment-with-wallet', [IxApplicationController::class, 'initiatePaymentWithWallet'])->name('initiate-payment-with-wallet');
            Route::get('/{id}/pay-now', [IxApplicationController::class, 'payNow'])->name('pay-now');
            Route::match(['get', 'post'], '/{id}/pay-now-with-wallet', [IxApplicationController::class, 'payNowWithWallet'])->name('pay-now-with-wallet');
            Route::get('/preview', [IxApplicationController::class, 'preview'])->name('preview');
            Route::post('/{applicationId}/submit', [IxApplicationController::class, 'finalSubmit'])->name('final-submit');
            Route::get('/agreement', [IxApplicationController::class, 'downloadAgreement'])->name('agreement');
            Route::get('/locations', [IxApplicationController::class, 'locations'])->name('locations');
            Route::get('/pricing', [IxApplicationController::class, 'pricing'])->name('pricing');
            Route::get('/application-pricing', [IxApplicationController::class, 'getApplicationPricing'])->name('application-pricing');
            Route::get('/{id}/download-application-pdf', [IxApplicationController::class, 'downloadApplicationPdf'])->name('download-application-pdf');
            Route::get('/{id}/download-invoice-pdf', [IxApplicationController::class, 'downloadInvoicePdf'])->name('download-invoice-pdf');

            // Verification endpoints
            Route::post('/verify-representative-pan', [IxApplicationController::class, 'verifyRepresentativePan'])->name('verify-representative-pan');
            Route::post('/check-representative-pan-status', [IxApplicationController::class, 'checkRepresentativePanStatus'])->name('check-representative-pan-status');
            Route::post('/send-email-otp', [IxApplicationController::class, 'sendEmailOtp'])->name('send-email-otp');
            Route::post('/verify-email-otp', [IxApplicationController::class, 'verifyEmailOtp'])->name('verify-email-otp');
            Route::post('/send-mobile-otp', [IxApplicationController::class, 'sendMobileOtp'])->name('send-mobile-otp');
            Route::post('/verify-mobile-otp', [IxApplicationController::class, 'verifyMobileOtp'])->name('verify-mobile-otp');
            Route::post('/verify-gstin', [IxApplicationController::class, 'verifyGstin'])->name('verify-gstin');
            Route::post('/check-gstin-status', [IxApplicationController::class, 'checkGstinStatus'])->name('check-gstin-status');
        });

        // PDF download routes (must be before {id} route)
        Route::get('/{id}/download-application-pdf', [ApplicationController::class, 'downloadApplicationPdf'])->name('download-application-pdf');
        Route::get('/{id}/download-invoice-pdf', [ApplicationController::class, 'downloadInvoicePdf'])->name('download-invoice-pdf');
        Route::get('/{id}/document', [ApplicationController::class, 'serveDocument'])->name('document');

        // Show application (must be last)
        Route::get('/{id}', [ApplicationController::class, 'show'])->name('show');
    });

    // Grievance routes
    Route::prefix('grievance')->name('grievance.')->group(function () {
        Route::get('/', [UserGrievanceController::class, 'index'])->name('index');
        Route::get('/create', [UserGrievanceController::class, 'create'])->name('create');
        Route::post('/store', [UserGrievanceController::class, 'store'])->name('store');
        Route::get('/attachments/{id}/download', [UserGrievanceController::class, 'downloadAttachment'])->name('attachments.download');
        Route::get('/{id}', [UserGrievanceController::class, 'show'])->name('show');
        Route::post('/{id}/reply', [UserGrievanceController::class, 'reply'])->name('reply');
    });

    // Plan change routes
    Route::prefix('applications/{applicationId}/plan-change')->name('plan-change.')->group(function () {
        Route::get('/create', [\App\Http\Controllers\PlanChangeRequestController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\PlanChangeRequestController::class, 'store'])->name('store');
    });

    // Invoice routes
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [\App\Http\Controllers\UserInvoiceController::class, 'index'])->name('index');
        Route::get('/{id}/download', [\App\Http\Controllers\UserInvoiceController::class, 'download'])->name('download');
        Route::post('/{id}/update-tds-amount', [\App\Http\Controllers\UserInvoiceController::class, 'updateTdsAmount'])->name('update-tds-amount');
        Route::post('/{id}/upload-tds-certificate', [\App\Http\Controllers\UserInvoiceController::class, 'uploadTdsCertificate'])->name('upload-tds-certificate');
        Route::get('/{id}/view-tds-certificate', [\App\Http\Controllers\UserInvoiceController::class, 'viewTdsCertificate'])->name('view-tds-certificate');
    });

    // Payment routes
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/pending', [\App\Http\Controllers\UserPaymentController::class, 'pending'])->name('pending');
        Route::post('/pay-all', [\App\Http\Controllers\UserPaymentController::class, 'payAll'])->name('pay-all');
        Route::post('/pay-all-with-wallet', [\App\Http\Controllers\UserPaymentController::class, 'payAllWithWallet'])->name('pay-all-with-wallet');
        Route::post('/{invoiceId}/pay-now', [\App\Http\Controllers\UserPaymentController::class, 'payNow'])->name('pay-now');
        Route::post('/{invoiceId}/pay-with-wallet', [\App\Http\Controllers\UserPaymentController::class, 'payWithWallet'])->name('pay-with-wallet');
    });

    // Wallet routes
    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/', [\App\Http\Controllers\WalletController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\WalletController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\WalletController::class, 'store'])->name('store');
        Route::get('/add-money', [\App\Http\Controllers\WalletController::class, 'addMoney'])->name('add-money');
        Route::post('/add-money', [\App\Http\Controllers\WalletController::class, 'processAddMoney'])->name('process-add-money');
        Route::get('/transactions', [\App\Http\Controllers\WalletController::class, 'transactions'])->name('transactions');
        Route::get('/balance', [\App\Http\Controllers\WalletController::class, 'balance'])->name('balance');
        Route::post('/sync-balance', [\App\Http\Controllers\WalletController::class, 'syncBalance'])->name('sync-balance');
        Route::post('/make-payment', [\App\Http\Controllers\WalletController::class, 'makePayment'])->name('make-payment');
    });
    // Reactivation request (for disconnected applications only)
    Route::post('/applications/{applicationId}/reactivation-request', [UserReactivationRequestController::class, 'store'])->name('applications.reactivation-request');
});

// Cookie-based login route (for payment callbacks - no auth required)
Route::get('/user/login-from-cookie', [LoginController::class, 'loginFromCookie'])->name('user.login-from-cookie');

// PayU Callback URLs (MUST be outside auth middleware - PayU redirects user here)
// These routes are accessible without authentication since PayU redirects the user's browser
Route::any('/user/applications/ix/payment-success', [IxApplicationController::class, 'paymentSuccess'])->name('user.applications.ix.payment-success');

Route::any('/user/applications/ix/payment-failure', [IxApplicationController::class, 'paymentFailure'])->name('user.applications.ix.payment-failure');

// IRINN PayU Callback URLs (MUST be outside auth middleware)
Route::any('/user/applications/irin/payment-success', [ApplicationController::class, 'paymentSuccess'])->name('user.applications.irin.payment-success');
Route::any('/user/applications/irin/payment-failure', [ApplicationController::class, 'paymentFailure'])->name('user.applications.irin.payment-failure');

// Wallet Top-up Callback URLs (MUST be outside auth middleware - PayU redirects user here)
Route::any('/user/wallet/add-money/success', [\App\Http\Controllers\WalletController::class, 'addMoneySuccess'])->name('user.wallet.add-money-success');

Route::any('/user/wallet/add-money/failure', [\App\Http\Controllers\WalletController::class, 'addMoneyFailure'])->name('user.wallet.add-money-failure');

// PayU S2S Webhook (must be outside auth middleware - PayU server calls this directly)
Route::post('/payu/webhook', [IxApplicationController::class, 'handleWebhook'])->name('payu.webhook');

// Application Routes
Route::prefix('application')->name('application.')->middleware(['application'])->group(function () {
    Route::get('/dashboard', [ApplicationController::class, 'index'])->name('dashboard');
    // Add more Application routes here
});

// ⚠️ TEMPORARY: Log Viewer Route - REMOVE AFTER DEBUGGING ⚠️
Route::get('/admin/view-logs', function (Request $request) {
    // Basic security - require user authentication
    if (! session('user_id')) {
        return redirect()->route('login.index')
            ->with('error', 'Please login to view logs.');
    }

    $logFile = storage_path('logs/laravel.log');

    if (! file_exists($logFile)) {
        return response()->json([
            'error' => 'Log file not found',
            'path' => $logFile,
        ], 404);
    }

    // Get filter parameter
    $filter = $request->get('filter', 'all'); // all, payu, errors
    $lines = (int) $request->get('lines', 200); // number of lines to show
    $lines = min($lines, 1000); // limit to 1000 lines max

    // Read log file
    $logContent = file_get_contents($logFile);
    $logLines = explode("\n", $logContent);
    $totalLines = count($logLines);

    // Get last N lines
    $recentLines = array_slice($logLines, -$lines);

    // Apply filter
    $filteredLines = [];
    foreach ($recentLines as $line) {
        $line = trim($line);
        if (empty($line)) {
            continue;
        }

        if ($filter === 'payu') {
            if (stripos($line, 'PayU') !== false ||
                stripos($line, 'payment') !== false ||
                stripos($line, 'Payment') !== false) {
                $filteredLines[] = $line;
            }
        } elseif ($filter === 'gst') {
            if (stripos($line, 'GST') !== false ||
                stripos($line, 'gst') !== false ||
                stripos($line, 'GSTIN') !== false ||
                stripos($line, 'gstin') !== false ||
                stripos($line, 'GST verification') !== false ||
                stripos($line, 'Idfy GST') !== false ||
                stripos($line, 'full_api_response') !== false ||
                stripos($line, 'source_output') !== false) {
                $filteredLines[] = $line;
            }
        } elseif ($filter === 'email') {
            if (stripos($line, 'Mail') !== false ||
                stripos($line, 'email') !== false ||
                stripos($line, 'Email') !== false ||
                stripos($line, 'OTP') !== false ||
                stripos($line, 'sent') !== false) {
                $filteredLines[] = $line;
            }
        } elseif ($filter === 'errors') {
            if (stripos($line, 'ERROR') !== false ||
                stripos($line, 'Exception') !== false ||
                stripos($line, 'Failed') !== false) {
                $filteredLines[] = $line;
            }
        } else {
            $filteredLines[] = $line;
        }
    }

    // Return HTML view
    return response()->view('admin.logs-viewer', [
        'logs' => $filteredLines,
        'totalLines' => $totalLines,
        'showingLines' => count($filteredLines),
        'filter' => $filter,
        'lines' => $lines,
        'logFile' => $logFile,
        'fileSize' => filesize($logFile),
        'lastModified' => date('Y-m-d H:i:s', filemtime($logFile)),
    ]);
})->name('admin.view-logs');

// ⚠️ TEMPORARY: Reset Payment Status Route - REMOVE AFTER DEBUGGING ⚠️
Route::post('/admin/reset-payment-status', function (Request $request) {
    // Basic security - require user authentication
    if (! session('user_id')) {
        return redirect()->route('login.index')
            ->with('error', 'Please login to reset payment status.');
    }

    $request->validate([
        'application_identifier' => 'required|string',
    ]);

    $identifier = trim($request->input('application_identifier'));

    // Try to find application by application_id first, then by database ID
    $application = \App\Models\Application::where('application_id', $identifier)
        ->orWhere('id', $identifier)
        ->first();

    if (! $application) {
        return redirect()->route('admin.view-logs')
            ->with('payment_reset_error', 'Application not found with identifier: '.$identifier);
    }

    // Only allow resetting IX applications
    if ($application->application_type !== 'IX') {
        return redirect()->route('admin.view-logs')
            ->with('payment_reset_error', 'This tool only works for IX applications. Found application type: '.$application->application_type);
    }

    // Get application data
    $applicationData = $application->application_data ?? [];

    // Reset payment status to pending
    if (! isset($applicationData['payment'])) {
        $applicationData['payment'] = [];
    }

    $applicationData['payment']['status'] = 'pending';
    $applicationData['payment']['reset_at'] = now('Asia/Kolkata')->toDateTimeString();
    $applicationData['payment']['reset_by'] = session('user_id');

    // Set application status to draft if it's not already
    $oldStatus = $application->status;
    $application->update([
        'status' => 'draft',
        'application_data' => $applicationData,
        'submitted_at' => null, // Clear submitted_at so it can be resubmitted
    ]);

    // Log the status change
    \App\Models\ApplicationStatusHistory::log(
        $application->id,
        $oldStatus,
        'draft',
        'system',
        session('user_id'),
        'Payment status reset to pending via debug tool - Pay Now button enabled'
    );

    return redirect()->route('admin.view-logs')
        ->with('payment_reset_success', 'Payment status reset to pending for application '.$application->application_id.'. The "Pay Now" button should now be visible.');
})->name('admin.reset-payment-status');
