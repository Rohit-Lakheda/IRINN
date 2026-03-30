<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIrinNewFlowRequest;
use App\Mail\ApplicationInvoiceMail;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\GstVerification;
use App\Models\IpPricing;
use App\Models\McaVerification;
use App\Models\PaymentTransaction;
use App\Models\Registration;
use App\Models\UserKycProfile;
use App\Services\PayuGatewayPaymentProcessor;
use App\Services\PayuService;
use App\Support\IrinnApplicationDisplayEnricher;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApplicationController extends Controller
{
    /**
     * Display user's applications page.
     */
    public function index(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            // Check if user status is approved
            if ($user->status !== 'approved' && $user->status !== 'active') {
                return redirect()->route('user.dashboard')
                    ->with('error', 'Your account must be approved to access applications.');
            }

            // Build query for user's applications
            $query = Application::with(['statusHistory'])
                ->where('user_id', $userId);

            // Dynamic search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');

                // Map current stage display names to status codes for searching
                $stageToStatusMap = [
                    'Draft' => ['draft'],
                    'Payment Pending' => ['payment_pending'],
                    'Rejected' => ['rejected', 'ceo_rejected'],
                    'Approved' => ['approved'],
                    'Processor' => ['pending', 'processor_review'],
                    'Finance' => ['processor_approved', 'finance_review'],
                    'Technical' => ['finance_approved'],
                    'Helpdesk' => ['helpdesk', 'submitted'],
                    'Hostmaster' => ['hostmaster'],
                    'Billing' => ['billing'],
                    'Billing approved' => ['billing_approved'],
                    'Resubmission Requested' => ['resubmission_requested'],
                    'Pending' => ['pending'],
                ];

                // Find status codes that match the search term in their stage display names
                $matchingStatuses = [];
                foreach ($stageToStatusMap as $stageName => $statuses) {
                    if (stripos($stageName, $search) !== false) {
                        $matchingStatuses = array_merge($matchingStatuses, $statuses);
                    }
                }

                $query->where(function ($q) use ($search, $matchingStatuses) {
                    // Application ID, membership, customer
                    $q->where('application_id', 'like', "%{$search}%")
                        ->orWhere('membership_id', 'like', "%{$search}%")
                        ->orWhere('customer_id', 'like', "%{$search}%")
                        ->orWhere('assigned_ip', 'like', "%{$search}%")
                        ->orWhere('assigned_port_capacity', 'like', "%{$search}%")
                        ->orWhere('assigned_port_number', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('application_type', 'like', "%{$search}%")
                        ->when(! empty($matchingStatuses), function ($q) use ($matchingStatuses) {
                            $q->orWhereIn('status', $matchingStatuses);
                        })
                        // IRINN: part2 IP resources (IPv4, IPv6 prefix, ASN)
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(application_data, '$.part2.ipv4_prefix')) LIKE ?", ["%{$search}%"])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(application_data, '$.part2.ipv6_prefix')) LIKE ?", ["%{$search}%"])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(application_data, '$.part2.asn_required')) LIKE ?", ["%{$search}%"]);
                });
            }

            $applications = $query->latest()->paginate(15)->withQueryString();

            // Get pending invoices for each application to show Pay Now buttons (exclude cancelled and credit note invoices)
            $applicationIds = $applications->pluck('id')->toArray();
            $pendingInvoicesByApplication = \App\Models\Invoice::whereIn('application_id', $applicationIds)
                ->activeForTotals()
                ->where('status', 'pending')
                ->with(['application'])
                ->get()
                ->groupBy('application_id');

            return response()->view('user.applications.index', compact('user', 'applications', 'pendingInvoicesByApplication'))
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error loading applications page: '.$e->getMessage());

            return redirect()->route('user.dashboard')
                ->with('error', 'Unable to load applications. Please try again.');
        }
    }

    /**
     * Show application details for user.
     */
    public function show($id)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            // Check if user status is approved
            if ($user->status !== 'approved' && $user->status !== 'active') {
                return redirect()->route('user.dashboard')
                    ->with('error', 'Your account must be approved to access applications.');
            }

            // Get application with status history and GST change history
            // Do NOT load verification relationships - we'll use data from application table columns only
            // is_active shows live status, not visibility
            $application = Application::with([
                'statusHistory' => fn ($q) => $q->orderBy('created_at'),
                'serviceStatusHistories',
                'gstChangeHistory',
            ])
                ->where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (! $application) {
                Log::warning('Application not found', [
                    'application_id' => $id,
                    'user_id' => $userId,
                    'request_url' => request()->url(),
                ]);

                return redirect()->route('user.applications.index')
                    ->with('error', 'Application not found or you do not have permission to view it.');
            }

            $application->statusHistory->each(fn ($row) => $row->setRelation('application', $application));

            $pendingPlanChange = null;
            $approvedPlanChange = null;

            // Get pending GST update requests for this application
            $pendingGstUpdateRequest = \App\Models\ApplicationGstUpdateRequest::where('application_id', $application->id)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if ($application->application_type === 'IRINN') {
                IrinnApplicationDisplayEnricher::enrich($application);
            } elseif (! $application->registration_details) {
                // Load for non-IRINN if not already set
                $registrationDetails = [
                    'registration_id' => $user->registrationid,
                    'registration_type' => $user->registration_type,
                    'pancardno' => $user->pancardno,
                    'fullname' => $user->fullname,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'dateofbirth' => $user->dateofbirth?->format('Y-m-d'),
                    'registrationdate' => $user->registrationdate?->format('Y-m-d'),
                    'registrationtime' => $user->registrationtime,
                    'pan_verified' => $user->pan_verified,
                    'email_verified' => $user->email_verified,
                    'mobile_verified' => $user->mobile_verified,
                    'status' => $user->status,
                    'address' => $user->address,
                    'city' => $user->city,
                    'state' => $user->state,
                    'pincode' => $user->pincode,
                    'country' => $user->country ?? 'India',
                ];
                $application->registration_details = $registrationDetails;
            }

            if ($application->application_type !== 'IRINN' && ! $application->kyc_details) {
                // Load for non-IRINN if not already set
                try {
                    $kycProfile = UserKycProfile::where('user_id', $userId)->latest()->first();
                    if ($kycProfile) {
                        $kycDetails = [
                            'gstin' => $kycProfile->gstin,
                            'gst_verified' => $kycProfile->gst_verified,
                            'legal_name' => $kycProfile->legal_name,
                            'trade_name' => $kycProfile->trade_name,
                            'taxpayer_type' => $kycProfile->taxpayer_type,
                            'gst_type' => $kycProfile->gst_type,
                            'gstin_status' => $kycProfile->gstin_status,
                            'company_status' => $kycProfile->company_status,
                            'registration_date' => $kycProfile->registration_date,
                            'constitution_of_business' => $kycProfile->constitution_of_business,
                            'state' => $kycProfile->state,
                            'pincode' => $kycProfile->pincode,
                            'primary_address' => $kycProfile->primary_address,
                            'udyam_number' => $kycProfile->udyam_number,
                            'udyam_verified' => $kycProfile->udyam_verified,
                            'cin' => $kycProfile->cin,
                            'mca_verified' => $kycProfile->mca_verified,
                            'roc_iec_number' => $kycProfile->roc_iec_number,
                            'roc_iec_verified' => $kycProfile->roc_iec_verified,
                            'contact_name' => $kycProfile->authorized_name,
                            'contact_pan' => $kycProfile->authorized_pan,
                            'contact_dob' => $kycProfile->authorized_dob?->format('Y-m-d'),
                            'contact_email' => $kycProfile->authorized_email,
                            'contact_mobile' => $kycProfile->authorized_mobile,
                            'contact_email_verified' => $kycProfile->authorized_email_verified,
                            'contact_mobile_verified' => $kycProfile->authorized_mobile_verified,
                            'contact_name_pan_dob_verified' => $kycProfile->authorized_pan_verified,
                            'status' => $kycProfile->status,
                            'completed_at' => $kycProfile->completed_at?->format('Y-m-d H:i:s'),
                        ];
                        $application->kyc_details = $kycDetails;
                    }
                } catch (Exception $e) {
                    Log::error('Error loading KYC details: '.$e->getMessage());
                }
            }

            return response()->view('user.applications.show', compact('user', 'application', 'pendingPlanChange', 'approvedPlanChange', 'pendingGstUpdateRequest'))
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error loading application details: '.$e->getMessage());

            return redirect()->route('user.applications.index')
                ->with('error', 'Application not found.');
        }
    }

    /**
     * Show IRINN application form.
     */
    public function createIrin()
    {
        // abort(404, 'IRINN application workflow is temporarily unavailable.');
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            // Check if user status is approved
            if ($user->status !== 'approved' && $user->status !== 'active') {
                return redirect()->route('user.dashboard')
                    ->with('error', 'Your account must be approved to submit applications.');
            }

            return view('user.applications.irin.create', compact('user'));
        } catch (Exception $e) {
            Log::error('Error loading IRINN application form: '.$e->getMessage());

            return redirect()->route('user.applications.index')
                ->with('error', 'Unable to load application form. Please try again.');
        }
    }

    /**
     * Show new IRINN application form (simplified structure).
     */
    public function createIrinNew()
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            // Check if user status is approved
            if ($user->status !== 'approved' && $user->status !== 'active') {
                return redirect()->route('user.dashboard')
                    ->with('error', 'Your account must be approved to submit applications.');
            }

            // Clear any previous preview/form data from session (unless returning from preview)
            $fromPreview = request()->get('from_preview');
            if (! $fromPreview) {
                session()->forget(['irin_preview_data', 'irin_form_data']);
            }

            return view('user.applications.irin.create-new', compact('user'));
        } catch (Exception $e) {
            Log::error('Error loading new IRINN application form: '.$e->getMessage());

            return redirect()->route('user.applications.index')
                ->with('error', 'Unable to load application form. Please try again.');
        }
    }

    /**
     * Show edit form for IRINN application when admin requested resubmission.
     * All details prefilled; no payment. User can update and resubmit.
     */
    public function editResubmit(int $id)
    {
        $userId = session('user_id');
        $user = Registration::find($userId);
        if (! $user) {
            return redirect()->route('login.index')->with('error', 'Please login again.');
        }

        $application = Application::where('id', $id)
            ->where('user_id', $userId)
            ->where('application_type', 'IRINN')
            ->where('status', 'resubmission_requested')
            ->firstOrFail();

        $data = $application->application_data ?? [];

        if ($application->hasIrinnNormalizedData()) {
            $normalizedPrefill = [];
            foreach ($application->getFillable() as $column) {
                if (! str_starts_with($column, 'irinn_')) {
                    continue;
                }
                $value = $application->getAttribute($column);
                if ($value instanceof \DateTimeInterface) {
                    $normalizedPrefill[$column] = $value->format('Y-m-d');
                } elseif (is_bool($value)) {
                    $normalizedPrefill[$column] = $value;
                } else {
                    $normalizedPrefill[$column] = $value;
                }
            }

            return view('user.applications.irin.create-new', [
                'user' => $user,
                'application' => $application,
                'isNormalizedResubmission' => true,
                'irinnNormalizedPrefill' => $normalizedPrefill,
                'resubmissionReason' => $data['irinn_resubmission_reason'] ?? '',
            ]);
        }

        $part1 = $data['part1'] ?? [];
        $part2 = $data['part2'] ?? [];
        $part3 = $data['part3'] ?? [];
        $part4 = $data['part4'] ?? [];
        $upstream = $part4['upstream_provider'] ?? [];

        $prefill = [
            'affiliate_type' => trim((string) ($part1['affiliate_type'] ?? $data['affiliate_type'] ?? '')),
            'domain_required' => trim((string) ($part1['domain_required'] ?? $data['domain_required'] ?? 'yes')),
            'ipv4_prefix' => trim((string) ($part2['ipv4_prefix'] ?? '')),
            'ipv6_prefix' => trim((string) ($part2['ipv6_prefix'] ?? '')),
            'asn_required' => trim((string) ($part2['asn_required'] ?? 'no')),
            'upstream_name' => trim((string) ($upstream['name'] ?? $data['upstream_name'] ?? '')),
            'upstream_mobile' => trim((string) ($upstream['mobile'] ?? $data['upstream_mobile'] ?? '')),
            'upstream_email' => trim((string) ($upstream['email'] ?? $data['upstream_email'] ?? '')),
            'upstream_org_name' => trim((string) ($upstream['org_name'] ?? $data['upstream_org_name'] ?? '')),
            'upstream_asn_details' => trim((string) ($upstream['asn_details'] ?? $data['upstream_asn_details'] ?? '')),
        ];

        $resubmissionReason = $data['irinn_resubmission_reason'] ?? '';

        return view('user.applications.irin.create-new', [
            'user' => $user,
            'application' => $application,
            'isResubmission' => true,
            'prefill' => $prefill,
            'resubmissionReason' => $resubmissionReason,
        ]);
    }

    /**
     * Store resubmitted IRINN application (no payment). Updates application and sets status to helpdesk.
     */
    public function storeResubmission(Request $request, int $id)
    {
        $userId = session('user_id');
        $user = Registration::find($userId);
        if (! $user) {
            return redirect()->route('login.index')->with('error', 'Please login again.');
        }

        $application = Application::where('id', $id)
            ->where('user_id', $userId)
            ->where('application_type', 'IRINN')
            ->where('status', 'resubmission_requested')
            ->firstOrFail();

        if ($application->hasIrinnNormalizedData()) {
            return redirect()
                ->route('user.applications.irin.resubmit', $application->id)
                ->with('info', 'Please complete your updates using the IRINN application form and submit from there.');
        }

        $existingData = $application->application_data ?? [];
        $part3 = $existingData['part3'] ?? [];
        $part4 = $existingData['part4'] ?? [];

        $rules = [
            'affiliate_type' => 'required|string|in:new,transfer',
            'domain_required' => 'required|string|in:yes,no',
            'ipv4_prefix' => 'required_without:ipv6_prefix|nullable|string|in:/24,/23',
            'ipv6_prefix' => 'required_without:ipv4_prefix|nullable|string|in:/48,/32',
            'asn_required' => 'required|string|in:yes,no',
            'board_resolution_file' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:10240',
            'irinn_agreement_file' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:10240',
            'network_diagram_file' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:10240',
            'equipment_invoice_file' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:10240',
            'bandwidth_invoice_file' => 'nullable|array',
            'bandwidth_invoice_file.*' => 'file|mimes:pdf,jpeg,png,jpg|max:10240',
            'bandwidth_agreement_file' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:10240',
            'upstream_name' => 'required|string|max:255',
            'upstream_mobile' => 'required|string|size:10|regex:/^[0-9]{10}$/',
            'upstream_email' => 'required|email|max:255',
            'upstream_org_name' => 'required|string|max:255',
            'upstream_asn_details' => 'required|string|max:255',
        ];

        $messages = [
            'ipv4_prefix.required_without' => 'Please select at least one: IPv4 or IPv6 prefix.',
            'ipv6_prefix.required_without' => 'Please select at least one: IPv4 or IPv6 prefix.',
        ];

        $validated = $request->validate($rules, $messages);

        $filePaths = [];
        $fileFields = ['board_resolution_file', 'irinn_agreement_file', 'network_diagram_file', 'equipment_invoice_file', 'bandwidth_agreement_file'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $path = $file->store('irinn-applications/'.$userId.'/'.date('Y/m'), 'public');
                $filePaths[$field] = $path;
            } else {
                $filePaths[$field] = $part3[$field] ?? $part4[$field] ?? null;
            }
        }

        if ($request->hasFile('bandwidth_invoice_file')) {
            $bandwidthFiles = [];
            foreach ($request->file('bandwidth_invoice_file') as $file) {
                if ($file && $file->isValid()) {
                    $bandwidthFiles[] = $file->store('irinn-applications/'.$userId.'/'.date('Y/m').'/bandwidth', 'public');
                }
            }
            $filePaths['bandwidth_invoice_file'] = $bandwidthFiles;
        } else {
            $filePaths['bandwidth_invoice_file'] = $part4['bandwidth_invoice_file'] ?? [];
        }

        $applicationData = [
            'form_version' => 'new',
            'part1' => [
                'affiliate_type' => $validated['affiliate_type'],
                'domain_required' => $validated['domain_required'],
            ],
            'part2' => [
                'ipv4_prefix' => $validated['ipv4_prefix'] ?? null,
                'ipv6_prefix' => $validated['ipv6_prefix'] ?? null,
                'asn_required' => $validated['asn_required'],
            ],
            'part3' => [
                'board_resolution_file' => $filePaths['board_resolution_file'] ?? null,
                'irinn_agreement_file' => $filePaths['irinn_agreement_file'] ?? null,
            ],
            'part4' => [
                'network_diagram_file' => $filePaths['network_diagram_file'] ?? null,
                'equipment_invoice_file' => $filePaths['equipment_invoice_file'] ?? null,
                'bandwidth_invoice_file' => $filePaths['bandwidth_invoice_file'] ?? [],
                'bandwidth_agreement_file' => $filePaths['bandwidth_agreement_file'] ?? null,
                'upstream_provider' => [
                    'name' => $validated['upstream_name'],
                    'mobile' => $validated['upstream_mobile'],
                    'email' => $validated['upstream_email'],
                    'org_name' => $validated['upstream_org_name'],
                    'asn_details' => $validated['upstream_asn_details'],
                ],
            ],
            'part5' => $existingData['part5'] ?? ['application_fee' => 1000],
        ];

        // Preserve other top-level keys from existing data (gstin, mr_name, etc.) if present
        foreach (['gstin', 'udyam_number', 'mca_cin', 'roc_iec', 'industry_type', 'mr_name', 'mr_email', 'mr_designation', 'mr_mobile', 'account_name', 'dot_in_domain_required', 'billing_affiliate_name', 'billing_email', 'billing_address', 'billing_state', 'billing_city', 'billing_mobile', 'billing_postal_code', 'nature_of_business', 'gst_data', 'pdfs'] as $key) {
            if (array_key_exists($key, $existingData)) {
                $applicationData[$key] = $existingData[$key];
            }
        }

        $restoreStage = isset($existingData['irinn_previous_stage']) && is_string($existingData['irinn_previous_stage'])
            ? trim($existingData['irinn_previous_stage'])
            : null;
        $allowedRestoreStages = ['helpdesk', 'submitted', 'pending', 'hostmaster'];
        $targetStatus = in_array($restoreStage, $allowedRestoreStages, true) ? $restoreStage : 'helpdesk';

        // Remove resubmission metadata
        unset($applicationData['irinn_resubmission_reason'], $applicationData['irinn_resubmission_requested_at'], $applicationData['irinn_resubmission_requested_by'], $applicationData['irinn_previous_stage']);

        $application->update([
            'status' => $targetStatus,
            'application_data' => $applicationData,
            'irinn_current_stage' => $targetStatus,
        ]);

        ApplicationStatusHistory::log(
            $application->id,
            'resubmission_requested',
            $targetStatus,
            'user',
            $userId,
            'IRINN application resubmitted by user; returned to '.$targetStatus.' stage'
        );

        return redirect()->route('user.applications.show', $application->id)
            ->with('success', 'Application resubmitted successfully. No payment required. It has been returned to '.ucfirst($targetStatus).' for review.');
    }

    /**
     * Serve a document from an IRINN application (for resubmission edit view - View existing file).
     */
    public function serveResubmitDocument(int $id, string $doc)
    {
        $userId = session('user_id');
        if (! $userId) {
            abort(401);
        }

        $application = Application::where('id', $id)
            ->where('user_id', $userId)
            ->where('application_type', 'IRINN')
            ->firstOrFail();

        $data = $application->application_data ?? [];
        $allowedDocs = ['board_resolution_file', 'irinn_agreement_file', 'network_diagram_file', 'equipment_invoice_file', 'bandwidth_agreement_file', 'bandwidth_invoice_file'];
        if (! in_array($doc, $allowedDocs, true)) {
            abort(404);
        }

        $path = null;
        if (in_array($doc, ['board_resolution_file', 'irinn_agreement_file'], true)) {
            $path = data_get($data, 'part3.'.$doc);
        } elseif (in_array($doc, ['network_diagram_file', 'equipment_invoice_file', 'bandwidth_agreement_file'], true)) {
            $path = data_get($data, 'part4.'.$doc);
        } elseif ($doc === 'bandwidth_invoice_file') {
            $files = data_get($data, 'part4.bandwidth_invoice_file', []);
            $index = (int) request()->query('index', 0);
            $path = is_array($files) && isset($files[$index]) ? $files[$index] : null;
        }

        if (! $path || ! is_string($path) || ! Storage::disk('public')->exists($path)) {
            abort(404, 'Document not found.');
        }

        return Storage::disk('public')->response($path);
    }

    /**
     * Download IRINN Agreement PDF with prefilled details.
     */
    public function downloadIrinAgreement()
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                abort(404, 'User not found.');
            }

            // Get user's KYC profile for prefilling
            $kyc = UserKycProfile::where('user_id', $userId)->latest()->first();

            // Get GST verification if available
            $gstVerification = null;
            if ($kyc && $kyc->gst_verification_id) {
                $gstVerification = GstVerification::find($kyc->gst_verification_id);
            }

            $draftIrinn = Application::query()
                ->where('user_id', $userId)
                ->where('application_type', 'IRINN')
                ->latest('updated_at')
                ->first();

            $companyName = $draftIrinn?->irinn_organisation_name
                ?? $draftIrinn?->irinn_billing_legal_name
                ?? ($gstVerification ? ($gstVerification->legal_name ?? $gstVerification->trade_name) : null)
                ?? $user->fullname;

            $postalRaw = '';
            if ($draftIrinn !== null) {
                $postalRaw = trim(implode("\n", array_filter([
                    $draftIrinn->irinn_organisation_address,
                    $draftIrinn->irinn_organisation_postcode !== null && $draftIrinn->irinn_organisation_postcode !== ''
                        ? 'PIN: '.$draftIrinn->irinn_organisation_postcode
                        : null,
                ])));
                if ($postalRaw === '') {
                    $postalRaw = trim(implode("\n", array_filter([
                        $draftIrinn->irinn_billing_address,
                        $draftIrinn->irinn_billing_postcode !== null && $draftIrinn->irinn_billing_postcode !== ''
                            ? 'PIN: '.$draftIrinn->irinn_billing_postcode
                            : null,
                    ])));
                }
            }
            if ($postalRaw === '') {
                $postalRaw = (string) ($gstVerification?->primary_address ?? $kyc?->billing_address ?? '');
            }

            $postalLines = preg_split('/\r\n|\r|\n/', $postalRaw) ?: [];
            $postalLines = array_values(array_filter(array_map('trim', $postalLines), fn (string $l): bool => $l !== ''));
            if ($postalLines === []) {
                $postalLines = ['—'];
            }
            while (count($postalLines) < 3) {
                $postalLines[] = '';
            }
            $postalLines = array_slice($postalLines, 0, 3);

            $emailPrimary = $draftIrinn?->irinn_mr_email
                ?? $kyc?->authorized_email
                ?? $kyc?->contact_email
                ?? $user->email
                ?? '';
            $emailSecondary = (string) ($kyc?->billing_person_email ?? '');
            if ($emailSecondary === $emailPrimary) {
                $emailSecondary = '';
            }

            $irinnAccountName = ($draftIrinn && filled((string) $draftIrinn->irinn_account_name))
                ? (string) $draftIrinn->irinn_account_name
                : '';

            // Prepare data for PDF
            $data = [
                'user' => $user,
                'kyc' => $kyc,
                'gst_verification' => $gstVerification,
                'company_name' => $companyName,
                'company_address' => $postalRaw,
                'address_line_1' => $postalLines[0] ?? '',
                'address_line_2' => $postalLines[1] ?? '',
                'address_line_3' => $postalLines[2] ?? '',
                'email_line_1' => $emailPrimary,
                'email_line_2' => $emailSecondary,
                'irinn_account_name' => $irinnAccountName,
                'gstin' => $kyc?->gstin ?? $gstVerification?->gstin ?? '',
                'pan' => $gstVerification?->pan ?? $user->pancardno ?? '',
                'authorized_name' => $kyc?->authorized_name ?? $kyc?->contact_name ?? '',
                'authorized_email' => $kyc?->authorized_email ?? $kyc?->contact_email ?? '',
                'authorized_mobile' => $kyc?->authorized_mobile ?? $kyc?->contact_mobile ?? '',
                'authorized_pan' => $kyc?->authorized_pan ?? $kyc?->contact_pan ?? '',
                'signatory_rep_name' => $draftIrinn?->irinn_mr_name ?? $kyc?->authorized_name ?? $kyc?->contact_name ?? '',
                'signatory_rep_title' => $draftIrinn?->irinn_mr_designation ?? '',
                'date' => now('Asia/Kolkata')->format('d F Y'),
                'generated_at_short' => now('Asia/Kolkata')->format('d M Y'),
            ];

            $pdf = Pdf::loadView('user.applications.irin.pdf.agreement', $data);

            return $pdf->download('Standard-IRINN-Affiliation-Agreement-'.now('Asia/Kolkata')->format('Y-m-d').'.pdf');
        } catch (Exception $e) {
            Log::error('Error generating IRINN agreement PDF: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Unable to generate agreement PDF. Please try again.');
        }
    }

    /**
     * Fetch GST details from API.
     */
    public function fetchGstDetails(Request $request)
    {
        try {
            $request->validate([
                'gstin' => 'required|string|max:15|min:15',
            ]);

            $gstin = $request->gstin;
            $payload = ['gstin' => $gstin];
            $url = Config::get('gstzen.api_url');
            $apiKey = Config::get('gstzen.api_key');

            // Also check env directly as fallback
            if (empty($apiKey)) {
                $apiKey = env('GSTZEN_API_KEY', 'f15da0e8-e4d0-11ed-b5ea-0242ac120002');
            }

            // Log request details for debugging (without exposing full API key)
            Log::info('GST API Request', [
                'gstin' => $gstin,
                'url' => $url,
                'has_api_key' => ! empty($apiKey),
                'api_key_length' => strlen($apiKey ?? ''),
                'api_key_preview' => ! empty($apiKey) ? substr($apiKey, 0, 8).'...' : 'empty',
            ]);

            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            if ($apiKey) {
                $headers['Token'] = $apiKey;
            } else {
                Log::error('GST API Key is missing!');

                return response()->json([
                    'success' => false,
                    'message' => 'API configuration error. Please contact administrator.',
                ], 500);
            }

            $resp = Http::withHeaders($headers)
                ->asJson()
                ->timeout((int) Config::get('gstzen.timeout', 15))
                ->post($url, $payload);

            $status = $resp->status();
            $body = $resp->body();
            $decoded = json_decode($body, true);

            // Log response for debugging
            Log::info('GST API Response', [
                'status' => $status,
                'response' => $decoded,
                'raw_body' => substr($body, 0, 500), // First 500 chars of response
            ]);

            // Handle 403 Forbidden - Invalid credentials
            if ($status === 403) {
                Log::error('GST API Authentication Failed', [
                    'status' => $status,
                    'message' => $decoded['message'] ?? 'Invalid credentials',
                    'api_key_used' => ! empty($apiKey) ? substr($apiKey, 0, 8).'...' : 'empty',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'API authentication failed. The API key may be invalid or expired. Please contact administrator.',
                ], 403);
            }

            // Check if API response is valid
            if ($status === 200) {
                // Check if response has status field
                if (isset($decoded['status']) && $decoded['status'] == 1) {
                    // Check if GSTIN is valid
                    if (isset($decoded['valid']) && $decoded['valid'] === true && isset($decoded['company_details'])) {
                        return response()->json([
                            'success' => true,
                            'data' => $decoded,
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => isset($decoded['message']) ? $decoded['message'] : 'Invalid GSTIN number or GSTIN not found',
                        ], 400);
                    }
                } else {
                    // API returned status != 1
                    return response()->json([
                        'success' => false,
                        'message' => isset($decoded['message']) ? $decoded['message'] : 'API returned an error. Please check the GSTIN and try again.',
                    ], 400);
                }
            }

            // Non-200 status code
            return response()->json([
                'success' => false,
                'message' => 'API request failed with status code: '.$status.'. Response: '.substr($body, 0, 200),
            ], 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', $e->errors()['gstin'] ?? ['Invalid GSTIN format']),
            ], 422);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('GST API Connection Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to connect to GST API. Please check your internet connection and try again.',
            ], 500);
        } catch (Exception $e) {
            Log::error('GST API Error: '.$e->getMessage());
            Log::error('GST API Error Trace: '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch GST details: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify GSTIN using Idfy API.
     */
    public function verifyGst(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            $request->validate([
                'gstin' => 'required|string|size:15|regex:/^[0-9A-Z]{15}$/',
            ]);

            $gstin = strtoupper($request->input('gstin'));

            $service = new \App\Services\IdfyVerificationService;
            $result = $service->verifyGst($gstin);

            // Create verification record
            $verification = \App\Models\GstVerification::create([
                'user_id' => $userId,
                'gstin' => $gstin,
                'request_id' => $result['request_id'],
                'status' => 'in_progress',
                'is_verified' => false,
            ]);

            return response()->json([
                'success' => true,
                'request_id' => $result['request_id'],
                'verification_id' => $verification->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', $e->errors()['gstin'] ?? ['Invalid GSTIN format']),
            ], 422);
        } catch (Exception $e) {
            Log::error('GST Verification Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate GST verification: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify UDYAM using Idfy API.
     */
    public function verifyUdyam(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            $request->validate([
                'uam_number' => 'required|string',
            ]);

            $uamNumber = $request->input('uam_number');

            $service = new \App\Services\IdfyVerificationService;
            $result = $service->verifyUdyam($uamNumber);

            // Create verification record
            $verification = \App\Models\UdyamVerification::create([
                'user_id' => $userId,
                'uam_number' => $uamNumber,
                'request_id' => $result['request_id'],
                'status' => 'in_progress',
                'is_verified' => false,
            ]);

            return response()->json([
                'success' => true,
                'request_id' => $result['request_id'],
                'verification_id' => $verification->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', $e->errors()['uam_number'] ?? ['Invalid UDYAM number']),
            ], 422);
        } catch (Exception $e) {
            Log::error('UDYAM Verification Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate UDYAM verification: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify MCA using Idfy API.
     */
    public function verifyMca(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            $request->validate([
                'cin' => 'required|string',
            ]);

            $cin = $request->input('cin');

            $service = new \App\Services\IdfyVerificationService;
            $result = $service->verifyMca($cin);

            // Create verification record
            $verification = \App\Models\McaVerification::create([
                'user_id' => $userId,
                'cin' => $cin,
                'request_id' => $result['request_id'],
                'status' => 'in_progress',
                'is_verified' => false,
            ]);

            return response()->json([
                'success' => true,
                'request_id' => $result['request_id'],
                'verification_id' => $verification->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', $e->errors()['cin'] ?? ['Invalid CIN']),
            ], 422);
        } catch (Exception $e) {
            Log::error('MCA Verification Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate MCA verification: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify ROC IEC using Idfy API.
     */
    public function verifyRocIec(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            $request->validate([
                'import_export_code' => 'required|string',
            ]);

            $iec = $request->input('import_export_code');

            $service = new \App\Services\IdfyVerificationService;
            $result = $service->verifyRocIec($iec);

            // Create verification record
            $verification = \App\Models\RocIecVerification::create([
                'user_id' => $userId,
                'import_export_code' => $iec,
                'request_id' => $result['request_id'],
                'status' => 'in_progress',
                'is_verified' => false,
            ]);

            return response()->json([
                'success' => true,
                'request_id' => $result['request_id'],
                'verification_id' => $verification->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', $e->errors()['import_export_code'] ?? ['Invalid Import Export Code']),
            ], 422);
        } catch (Exception $e) {
            Log::error('ROC IEC Verification Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate ROC IEC verification: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check verification status.
     */
    public function checkVerificationStatus(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            $request->validate([
                'type' => 'required|string|in:gstin,udyam,mca,rocIec',
                'request_id' => 'required|string',
            ]);

            $type = $request->input('type');
            $requestId = $request->input('request_id');

            $service = new \App\Services\IdfyVerificationService;
            $statusResult = $service->getTaskStatus($requestId);

            $status = $statusResult['status'];
            $result = $statusResult['result'] ?? null;
            $task = $statusResult['task'] ?? null;

            // Find verification record
            $verification = null;
            $isVerified = false;
            $errorMessage = null;
            $sourceOutput = null;

            if ($status === 'completed') {
                $sourceOutput = $result['source_output'] ?? null;

                if ($sourceOutput) {
                    // Determine verification success based on type
                    switch ($type) {
                        case 'gstin':
                            $verification = \App\Models\GstVerification::where('request_id', $requestId)->first();
                            if ($verification) {
                                $isVerified = ($sourceOutput['status'] ?? '') === 'id_found';
                                if ($isVerified) {
                                    // Extract GST data from the response structure
                                    $verification->legal_name = $sourceOutput['legal_name'] ?? null;
                                    $verification->trade_name = $sourceOutput['trade_name'] ?? null;

                                    // Extract PAN from GSTIN (first 10 characters)
                                    $gstin = $sourceOutput['gstin'] ?? null;
                                    if ($gstin && strlen($gstin) >= 10) {
                                        $verification->pan = substr($gstin, 2, 10);
                                    }

                                    // Extract state from address
                                    $address = $sourceOutput['principal_place_of_business_fields']['principal_place_of_business_address'] ?? null;
                                    if ($address) {
                                        $verification->state = $address['state_name'] ?? null;
                                        // Build primary address
                                        $addressParts = array_filter([
                                            $address['door_number'] ?? null,
                                            $address['building_name'] ?? null,
                                            $address['street'] ?? null,
                                            $address['location'] ?? null,
                                            $address['city'] ?? null,
                                            $address['dst'] ?? null,
                                        ]);
                                        $verification->primary_address = implode(', ', $addressParts);
                                    }

                                    $verification->registration_date = isset($sourceOutput['date_of_registration']) ? date('Y-m-d', strtotime($sourceOutput['date_of_registration'])) : null;
                                    $verification->gst_type = $sourceOutput['taxpayer_type'] ?? null;
                                    $verification->company_status = $sourceOutput['gstin_status'] ?? null;
                                    $verification->constitution_of_business = $sourceOutput['constitution_of_business'] ?? null;
                                } else {
                                    $errorMessage = $sourceOutput['message'] ?? 'GSTIN verification failed';
                                }
                            }
                            break;
                        case 'udyam':
                            $verification = \App\Models\UdyamVerification::where('request_id', $requestId)->first();
                            if ($verification) {
                                $isVerified = ($sourceOutput['status'] ?? '') === 'id_found';
                                if (! $isVerified) {
                                    $errorMessage = $sourceOutput['message'] ?? 'UDYAM verification failed';
                                }
                            }
                            break;
                        case 'mca':
                            $verification = \App\Models\McaVerification::where('request_id', $requestId)->first();
                            if ($verification) {
                                $isVerified = ($sourceOutput['status'] ?? '') === 'id_found';
                                if (! $isVerified) {
                                    $errorMessage = $sourceOutput['message'] ?? 'MCA verification failed';
                                }
                            }
                            break;
                        case 'rocIec':
                            $verification = \App\Models\RocIecVerification::where('request_id', $requestId)->first();
                            if ($verification) {
                                $isVerified = ($sourceOutput['status'] ?? '') === 'id_found';
                                if (! $isVerified) {
                                    $errorMessage = $sourceOutput['message'] ?? 'ROC IEC verification failed';
                                }
                            }
                            break;
                    }

                    // Update verification record
                    if ($verification) {
                        $verification->status = $status;
                        $verification->is_verified = $isVerified;
                        $verification->verification_data = $task;
                        if ($errorMessage) {
                            $verification->error_message = $errorMessage;
                        }
                        $verification->save();
                    }
                }
            } elseif ($status === 'failed') {
                // Find and update verification record
                switch ($type) {
                    case 'gstin':
                        $verification = \App\Models\GstVerification::where('request_id', $requestId)->first();
                        break;
                    case 'udyam':
                        $verification = \App\Models\UdyamVerification::where('request_id', $requestId)->first();
                        break;
                    case 'mca':
                        $verification = \App\Models\McaVerification::where('request_id', $requestId)->first();
                        break;
                    case 'rocIec':
                        $verification = \App\Models\RocIecVerification::where('request_id', $requestId)->first();
                        break;
                }

                if ($verification) {
                    $verification->status = 'failed';
                    $verification->is_verified = false;
                    $verification->verification_data = $task;
                    $verification->error_message = 'Verification task failed';
                    $verification->save();
                }
                $errorMessage = 'Verification task failed';
            }

            // Prepare response with verification data for frontend
            $responseData = [
                'success' => true,
                'status' => $status,
                'is_verified' => $isVerified,
                'message' => $errorMessage,
            ];

            // Include verification data for GST to populate Step 2
            if ($type === 'gstin' && $isVerified && $verification && isset($sourceOutput)) {
                // Map constitution_of_business to affiliate_identity
                $affiliateIdentity = $this->mapConstitutionToAffiliateIdentity($verification->constitution_of_business);
                $affiliateIdentityDisplay = $this->getAffiliateIdentityDisplayName($affiliateIdentity);

                $principalAddr = $sourceOutput['principal_place_of_business_fields']['principal_place_of_business_address'] ?? [];
                $gstPostcode = is_array($principalAddr) ? ($principalAddr['pincode'] ?? null) : null;

                $responseData['verification_data'] = [
                    'gstin' => $verification->gstin,
                    'legal_name' => $verification->legal_name,
                    'trade_name' => $verification->trade_name,
                    'company_name' => $verification->legal_name ?? $verification->trade_name,
                    'pan' => $verification->pan,
                    'registration_date' => $verification->registration_date?->format('Y-m-d'),
                    'gst_type' => $verification->gst_type,
                    'company_status' => $verification->company_status,
                    'primary_address' => $verification->primary_address,
                    'postcode' => $gstPostcode,
                    'constitution_of_business' => $verification->constitution_of_business,
                    'affiliate_identity' => $affiliateIdentity,
                    'affiliate_identity_display' => $affiliateIdentityDisplay,
                    'source_output' => $sourceOutput,
                ];
            }

            // Include verification data for UDYAM to populate Step 2
            if ($type === 'udyam' && $isVerified && $verification && isset($sourceOutput)) {
                $officialAddress = $sourceOutput['official_address'] ?? null;
                $primaryAddress = null;
                $udyamPostcode = null;

                if (is_array($officialAddress)) {
                    $udyamPostcode = $officialAddress['pin'] ?? null;
                    $addressParts = array_filter([
                        $officialAddress['door'] ?? null,
                        $officialAddress['name_of_premises'] ?? null,
                        $officialAddress['road'] ?? null,
                        $officialAddress['area'] ?? null,
                        $officialAddress['city'] ?? null,
                        $officialAddress['district'] ?? null,
                        $officialAddress['state'] ?? null,
                    ]);
                    $primaryAddress = implode(', ', $addressParts);
                }

                $companyName = $sourceOutput['general_details']['enterprise_name'] ?? null;

                $responseData['verification_data'] = [
                    'uam_number' => $verification->uam_number,
                    'company_name' => $companyName,
                    'primary_address' => $primaryAddress,
                    'postcode' => $udyamPostcode,
                    'source_output' => $sourceOutput,
                ];
            }

            // Include verification data for MCA CIN to populate Step 2
            if ($type === 'mca' && $isVerified && $verification && isset($sourceOutput)) {
                $companyName = $sourceOutput['company_name'] ?? null;
                $registeredAddress = $sourceOutput['registered_address'] ?? null;
                $mcaPostcode = $sourceOutput['pincode'] ?? $sourceOutput['pin_code'] ?? null;
                if (! $mcaPostcode && is_string($registeredAddress) && preg_match('/\b(\d{6})\b/', $registeredAddress, $m)) {
                    $mcaPostcode = $m[1];
                }

                $directors = $sourceOutput['directors'] ?? [];
                $directors = is_array($directors) ? $directors : [];

                $responseData['verification_data'] = [
                    'cin' => $verification->cin,
                    'company_name' => $companyName,
                    'primary_address' => $registeredAddress,
                    'postcode' => $mcaPostcode,
                    'directors' => $directors,
                    'source_output' => $sourceOutput,
                ];
            }

            return response()->json($responseData);
        } catch (Exception $e) {
            Log::error('Check Verification Status Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to check verification status: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store IRINN application.
     */
    public function storeIrin(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            // Check if user status is approved
            if ($user->status !== 'approved' && $user->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account must be approved to submit applications.',
                ], 403);
            }

            // Base validation rules
            $rules = [
                'gstin' => 'nullable|string',
                'udyam_number' => 'nullable|string',
                'mca_cin' => 'nullable|string',
                'roc_iec' => 'nullable|string',
                'industry_type' => 'required|string',
                'applicant_name' => 'required|string',
                'applicant_email' => 'required|email',
                'applicant_designation' => 'required|string',
                'applicant_mobile' => 'required|string',
                // Keep MR fields for backward compatibility
                'mr_name' => 'nullable|string',
                'mr_email' => 'nullable|email',
                'mr_designation' => 'nullable|string',
                'mr_mobile' => 'nullable|string',
                'account_name' => 'required|string',
                'dot_in_domain_required' => 'required',
                'billing_affiliate_name' => 'required|string',
                'billing_email' => 'required|email',
                'billing_address' => 'required|string',
                'billing_state' => 'required|string',
                'billing_city' => 'required|string',
                'billing_mobile' => 'required|string',
                'billing_postal_code' => 'required|string',
                'ipv4_selected' => 'nullable',
                'ipv4_size' => 'nullable|string',
                'ipv6_selected' => 'nullable',
                'ipv6_size' => 'nullable|string',
                'affiliate_identity' => 'required|string',
                'nature_of_business' => 'required|string',
                'as_number_required' => 'required',
                'network_plan_file' => 'required|file|mimes:pdf|max:10240',
                'payment_receipts_file' => 'required|file|mimes:pdf|max:10240',
                'equipment_details_file' => 'required|file|mimes:pdf|max:10240',
                // Common KYC files (always required)
                'kyc_business_address_proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_authorization_doc' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_signature_proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_gst_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                // KYC files - conditional based on affiliate_identity (all nullable by default)
                'kyc_partnership_deed' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_partnership_entity_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_incorporation_cert' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_company_pan_gstin' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_udyam_cert' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_sole_proprietorship_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_establishment_reg' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_school_pan_gstin' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_rbi_license' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_bank_pan_gstin' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                // ASN fields
                'company_asn' => 'nullable|string',
                'isp_company_name' => 'nullable|string',
                'upstream_name' => 'nullable|string',
                'upstream_mobile' => 'nullable|string',
                'upstream_email' => 'nullable|email',
                'upstream_asn' => 'nullable|string',
            ];

            // Add conditional validation based on affiliate_identity
            $affiliateIdentity = $request->affiliate_identity;
            if ($affiliateIdentity === 'partnership') {
                $rules['kyc_partnership_deed'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
                $rules['kyc_partnership_entity_doc'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            } elseif ($affiliateIdentity === 'pvt_ltd') {
                $rules['kyc_incorporation_cert'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
                $rules['kyc_company_pan_gstin'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            } elseif ($affiliateIdentity === 'sole_proprietorship') {
                $rules['kyc_sole_proprietorship_doc'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
                // kyc_udyam_cert is optional
            } elseif ($affiliateIdentity === 'schools_colleges') {
                $rules['kyc_establishment_reg'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
                $rules['kyc_school_pan_gstin'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            } elseif ($affiliateIdentity === 'banks') {
                $rules['kyc_rbi_license'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
                $rules['kyc_bank_pan_gstin'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            }

            $validated = $request->validate($rules);

            // Handle file uploads
            $filePaths = [];
            $fileFields = [
                'network_plan_file',
                'payment_receipts_file',
                'equipment_details_file',
            ];

            // Add KYC document fields
            $kycFileFields = [
                'kyc_partnership_deed',
                'kyc_partnership_entity_doc',
                'kyc_incorporation_cert',
                'kyc_company_pan_gstin',
                'kyc_udyam_cert',
                'kyc_sole_proprietorship_doc',
                'kyc_establishment_reg',
                'kyc_school_pan_gstin',
                'kyc_rbi_license',
                'kyc_bank_pan_gstin',
                'kyc_business_address_proof',
                'kyc_authorization_doc',
                'kyc_signature_proof',
                'kyc_gst_certificate',
            ];

            $allFileFields = array_merge($fileFields, $kycFileFields);

            foreach ($allFileFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $path = $file->store('applications/'.$userId.'/irin', 'public');
                    $filePaths[$field] = $path;
                }
            }

            // Calculate fees using maximum amount and GST from backend
            $effectivePricing = IpPricing::getCurrentlyEffective();
            $selectedAmounts = [];
            $maxGstPercentage = 0;
            $ipv4Fee = 0;
            $ipv6Fee = 0;

            if ($request->has('ipv4_selected') && $request->ipv4_size) {
                $pricing = $effectivePricing['ipv4'][$request->ipv4_size] ?? null;
                if ($pricing) {
                    $ipv4Fee = $pricing['price'];
                    $amount = $pricing['amount'] ?? $pricing['price'];
                    $selectedAmounts[] = $amount;
                    if ($pricing['gst_percentage'] > $maxGstPercentage) {
                        $maxGstPercentage = $pricing['gst_percentage'];
                    }
                }
            }

            if ($request->has('ipv6_selected') && $request->ipv6_size) {
                $pricing = $effectivePricing['ipv6'][$request->ipv6_size] ?? null;
                if ($pricing) {
                    $ipv6Fee = $pricing['price'];
                    $amount = $pricing['amount'] ?? $pricing['price'];
                    $selectedAmounts[] = $amount;
                    if ($pricing['gst_percentage'] > $maxGstPercentage) {
                        $maxGstPercentage = $pricing['gst_percentage'];
                    }
                }
            }

            // Calculate maximum amount (not sum)
            $maxAmount = ! empty($selectedAmounts) ? max($selectedAmounts) : 0;

            // Calculate GST on maximum amount
            $gstAmount = $maxAmount * ($maxGstPercentage / 100);
            $totalFee = $maxAmount + $gstAmount;

            // Store fee breakdown for invoice
            $applicationData['max_amount'] = $maxAmount;
            $applicationData['gst_percentage'] = $maxGstPercentage;
            $applicationData['gst_amount'] = $gstAmount;

            // Get PAN from user registration
            $panCardNo = $user->pancardno;

            // Get verification IDs from verified documents
            $gstVerificationId = null;
            $udyamVerificationId = null;
            $mcaVerificationId = null;
            $rocIecVerificationId = null;

            if ($request->has('gstin_verified') && $request->gstin_verified == '1' && $request->gstin) {
                $gstVerification = \App\Models\GstVerification::where('user_id', $userId)
                    ->where('gstin', strtoupper($request->gstin))
                    ->where('is_verified', true)
                    ->latest()
                    ->first();
                if ($gstVerification) {
                    $gstVerificationId = $gstVerification->id;
                }
            }

            if ($request->has('udyam_verified') && $request->udyam_verified == '1' && $request->udyam_number) {
                $udyamVerification = \App\Models\UdyamVerification::where('user_id', $userId)
                    ->where('uam_number', $request->udyam_number)
                    ->where('is_verified', true)
                    ->latest()
                    ->first();
                if ($udyamVerification) {
                    $udyamVerificationId = $udyamVerification->id;
                }
            }

            if ($request->has('mca_verified') && $request->mca_verified == '1' && $request->mca_cin) {
                $mcaVerification = \App\Models\McaVerification::where('user_id', $userId)
                    ->where('cin', $request->mca_cin)
                    ->where('is_verified', true)
                    ->latest()
                    ->first();
                if ($mcaVerification) {
                    $mcaVerificationId = $mcaVerification->id;
                }
            }

            if ($request->has('roc_iec_verified') && $request->roc_iec_verified == '1' && $request->roc_iec) {
                $rocIecVerification = \App\Models\RocIecVerification::where('user_id', $userId)
                    ->where('import_export_code', $request->roc_iec)
                    ->where('is_verified', true)
                    ->latest()
                    ->first();
                if ($rocIecVerification) {
                    $rocIecVerificationId = $rocIecVerification->id;
                }
            }

            // Prepare application data
            $applicationData = [
                'gstin' => $request->gstin,
                'udyam_number' => $request->udyam_number ?? null,
                'mca_cin' => $request->mca_cin ?? null,
                'roc_iec' => $request->roc_iec ?? null,
                'industry_type' => $request->industry_type,
                // Use applicant fields, fallback to MR fields for backward compatibility
                'mr_name' => $request->applicant_name ?? $request->mr_name,
                'mr_email' => $request->applicant_email ?? $request->mr_email,
                'mr_designation' => $request->applicant_designation ?? $request->mr_designation,
                'mr_mobile' => $request->applicant_mobile ?? $request->mr_mobile,
                'account_name' => $request->account_name,
                'dot_in_domain_required' => $request->dot_in_domain_required == '1',
                'billing_affiliate_name' => $request->billing_affiliate_name,
                'billing_email' => $request->billing_email,
                'billing_address' => $request->billing_address,
                'billing_state' => $request->billing_state,
                'billing_city' => $request->billing_city,
                'billing_mobile' => $request->billing_mobile,
                'billing_postal_code' => $request->billing_postal_code,
                'ipv4_selected' => $request->has('ipv4_selected'),
                'ipv4_size' => $request->ipv4_size,
                'ipv6_selected' => $request->has('ipv6_selected'),
                'ipv6_size' => $request->ipv6_size,
                'ipv4_fee' => $ipv4Fee,
                'ipv6_fee' => $ipv6Fee,
                'total_fee' => $totalFee,
                'nature_of_business' => $request->nature_of_business,
                'as_number_required' => $request->as_number_required == '1',
                'affiliate_identity' => $request->affiliate_identity ?? '',
                'company_asn' => $request->company_asn ?? '',
                'isp_company_name' => $request->isp_company_name ?? '',
                'upstream_name' => $request->upstream_name ?? '',
                'upstream_mobile' => $request->upstream_mobile ?? '',
                'upstream_email' => $request->upstream_email ?? '',
                'upstream_asn' => $request->upstream_asn ?? '',
                'files' => $filePaths,
            ];

            // Add GST data if provided (from session or request)
            $gstData = session('gst_data');
            if (! $gstData && $request->has('gst_data')) {
                $gstData = is_string($request->gst_data) ? json_decode($request->gst_data, true) : $request->gst_data;
            }
            if ($gstData && is_array($gstData)) {
                $applicationData['gst_data'] = $gstData;
            }

            // Create application
            $application = Application::create([
                'user_id' => $userId,
                'pan_card_no' => $panCardNo, // Link via PAN
                'application_id' => Application::generateApplicationId(),
                'application_type' => 'IRINN',
                'status' => 'pending',
                'application_data' => $applicationData,
                'gst_verification_id' => $gstVerificationId,
                'udyam_verification_id' => $udyamVerificationId,
                'mca_verification_id' => $mcaVerificationId,
                'roc_iec_verification_id' => $rocIecVerificationId,
                'submitted_at' => now('Asia/Kolkata'),
            ]);

            // Log status change
            ApplicationStatusHistory::log(
                $application->id,
                null,
                'pending',
                'user',
                $userId,
                'IRINN application submitted'
            );

            // Generate PDFs
            try {
                $applicationPdf = $this->generateApplicationPdf($application);
                $invoicePdf = $this->generateInvoicePdf($application);

                // Store PDFs
                $applicationPdfPath = 'applications/'.$userId.'/irin/'.$application->application_id.'_application.pdf';
                $invoicePdfPath = 'applications/'.$userId.'/irin/'.$application->application_id.'_invoice.pdf';

                Storage::disk('public')->put($applicationPdfPath, $applicationPdf->output());
                Storage::disk('public')->put($invoicePdfPath, $invoicePdf->output());

                // Update application data with PDF paths
                $applicationData['pdfs'] = [
                    'application_pdf' => $applicationPdfPath,
                    'invoice_pdf' => $invoicePdfPath,
                ];
                $application->update(['application_data' => $applicationData]);

                // Send invoice email to user
                try {
                    $invoiceNumber = 'NXNIR'.date('y').'-'.(date('y') + 1).'/'.str_pad($application->id, 4, '0', STR_PAD_LEFT);
                    // total_fee already includes GST
                    $totalAmount = round($applicationData['total_fee'] ?? 0);

                    Mail::to($user->email)->send(new ApplicationInvoiceMail(
                        $user->fullname,
                        $application->application_id,
                        $invoiceNumber,
                        $totalAmount,
                        $application->status,
                        $invoicePdfPath
                    ));
                    Log::info("Invoice email sent to {$user->email} for application {$application->application_id}");
                } catch (Exception $e) {
                    Log::error('Invoice Email Error: '.$e->getMessage());
                    // Continue even if email sending fails
                }
            } catch (Exception $e) {
                Log::error('PDF Generation Error: '.$e->getMessage());
                // Continue even if PDF generation fails
            }

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully',
                'application' => $application,
                'application_id' => $application->id,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('IRINN Application Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Application submission failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store new IRINN application (simplified structure).
     */
    public function storeIrinNew(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            // Check if user status is approved
            if ($user->status !== 'approved' && $user->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account must be approved to submit applications.',
                ], 403);
            }

            if ($request->boolean('irinn_normalized_flow')) {
                if ($request->filled('irinn_resubmit_application_id')) {
                    return $this->storeIrinnNormalizedResubmissionAttempt($request, $userId, $user);
                }

                return $this->storeIrinnNormalizedApplication($request, $userId, $user);
            }

            // Check if this is payment for existing draft application
            $existingApplicationId = $request->input('application_id');
            if ($existingApplicationId) {
                $existingApplication = Application::where('id', $existingApplicationId)
                    ->where('user_id', $userId)
                    ->where('application_type', 'IRINN')
                    ->where('status', 'draft')
                    ->first();

                if ($existingApplication) {
                    // Initiate payment for existing draft
                    $paymentData = $existingApplication->application_data['part5'] ?? null;
                    $totalAmount = (float) ($paymentData['total_amount'] ?? 1180.00);

                    // Generate transaction ID
                    $transactionId = 'IRINN-'.time().'-'.strtoupper(\Illuminate\Support\Str::random(8));

                    // Create payment transaction
                    $paymentTransaction = PaymentTransaction::create([
                        'user_id' => $userId,
                        'application_id' => $existingApplication->id,
                        'transaction_id' => $transactionId,
                        'payment_mode' => config('services.payu.mode', 'test'),
                        'payment_status' => 'pending',
                        'amount' => $totalAmount,
                        'currency' => 'INR',
                        'product_info' => 'IRINN Application Fee',
                    ]);

                    // Update application data with payment transaction ID
                    $applicationData = $existingApplication->application_data ?? [];
                    $applicationData['part5']['payment_transaction_id'] = $paymentTransaction->id;
                    $existingApplication->update(['application_data' => $applicationData]);

                    // Prepare payment data for PayU
                    $payuService = new \App\Services\PayuService;
                    $paymentData = $payuService->preparePaymentData([
                        'transaction_id' => $transactionId,
                        'amount' => $totalAmount,
                        'product_info' => 'IRINN Application Fee',
                        'firstname' => $user->fullname,
                        'email' => $user->email,
                        'phone' => $user->mobile,
                        'success_url' => url(route('user.applications.irin.payment-success', [], false)),
                        'failure_url' => url(route('user.applications.irin.payment-failure', [], false)),
                        'udf1' => $existingApplication->application_id,
                        'udf2' => (string) $paymentTransaction->id,
                        'udf3' => 'IRINN',
                    ]);

                    // Store user session data in cookie for PayU return (session may be lost)
                    $userSessionData = [
                        'user_id' => $userId,
                        'user_email' => $user->email,
                        'user_name' => $user->fullname,
                        'user_registration_id' => $user->registrationid,
                    ];
                    $secureCookie = $request->isSecure() || (bool) config('session.secure', false);

                    return response()->json([
                        'success' => true,
                        'message' => 'Redirecting to payment...',
                        'application_id' => $existingApplication->application_id,
                        'payment_url' => $payuService->getPaymentUrl(),
                        'payment_data' => $paymentData,
                    ])->cookie('user_session_data', json_encode($userSessionData), 60, '/', null, $secureCookie, true, false, 'lax');
                }
            }

            $hasExistingApplication = Application::where('user_id', $userId)->exists();
            if ($hasExistingApplication) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can have only one application. Please manage your existing application.',
                ], 422);
            }

            $action = $request->input('action', 'submit'); // save, preview, submit

            // If submitting from preview, restore form data from session
            $fromPreview = $request->input('from_preview');
            if ($fromPreview && $action === 'submit') {
                $storedFormData = session('irin_form_data');
                if ($storedFormData) {
                    // Merge stored form data with current request (files won't be in session)
                    foreach ($storedFormData as $key => $value) {
                        if (! $request->has($key) && ! $request->hasFile($key)) {
                            $request->merge([$key => $value]);
                        }
                    }
                }
            }

            // When submitting from preview page: use session form data; files already in session
            $submitFromPreview = $fromPreview && $action === 'submit' && session()->has('irin_preview_data');
            if ($submitFromPreview) {
                $request->merge(session('irin_form_data') ?? []);
            }

            // Validation rules for new IRINN form (file fields optional when submit from preview)
            $rules = [
                'affiliate_type' => ($action === 'preview' || $submitFromPreview ? 'nullable' : 'required').'|string|in:new,transfer',
                'domain_required' => ($action === 'preview' || $submitFromPreview ? 'nullable' : 'required').'|string|in:yes,no',
                'ipv4_prefix' => ($action === 'preview' || $submitFromPreview ? 'nullable' : 'required_without:ipv6_prefix').'|nullable|string|in:/24,/23',
                'ipv6_prefix' => ($action === 'preview' || $submitFromPreview ? 'nullable' : 'required_without:ipv4_prefix').'|nullable|string|in:/48,/32',
                'asn_required' => ($action === 'preview' || $submitFromPreview ? 'nullable' : 'required').'|string|in:yes,no',
                'board_resolution_file' => ($action === 'submit' && ! $submitFromPreview) ? 'required|file|mimes:pdf,jpeg,png,jpg|max:10240' : 'nullable|file|mimes:pdf,jpeg,png,jpg|max:10240',
                'irinn_agreement_file' => ($action === 'submit' && ! $submitFromPreview) ? 'required|file|mimes:pdf,jpeg,png,jpg|max:10240' : 'nullable|file|mimes:pdf,jpeg,png,jpg|max:10240',
                'network_diagram_file' => ($action === 'submit' && ! $submitFromPreview) ? 'required|file|mimes:pdf,jpeg,png,jpg|max:10240' : 'nullable|file|mimes:pdf,jpeg,png,jpg|max:10240',
                'equipment_invoice_file' => ($action === 'submit' && ! $submitFromPreview) ? 'required|file|mimes:pdf,jpeg,png,jpg|max:10240' : 'nullable|file|mimes:pdf,jpeg,png,jpg|max:10240',
                'bandwidth_invoice_file' => ($action === 'submit' && ! $submitFromPreview) ? 'required|array' : 'nullable|array',
                'bandwidth_invoice_file.*' => 'file|mimes:pdf,jpeg,png,jpg|max:10240',
                'bandwidth_agreement_file' => ($action === 'submit' && ! $submitFromPreview) ? 'required|file|mimes:pdf,jpeg,png,jpg|max:10240' : 'nullable|file|mimes:pdf,jpeg,png,jpg|max:10240',
                'upstream_name' => ($action === 'submit' && ! $submitFromPreview) ? 'required|string|max:255' : 'nullable|string|max:255',
                'upstream_mobile' => ($action === 'submit' && ! $submitFromPreview) ? 'required|string|size:10|regex:/^[0-9]{10}$/' : 'nullable|string|max:20',
                'upstream_email' => ($action === 'submit' && ! $submitFromPreview) ? 'required|email|max:255' : 'nullable|email|max:255',
                'upstream_org_name' => ($action === 'submit' && ! $submitFromPreview) ? 'required|string|max:255' : 'nullable|string|max:255',
                'upstream_asn_details' => ($action === 'submit' && ! $submitFromPreview) ? 'required|string|max:255' : 'nullable|string|max:255',
                'payment_declaration' => ($action === 'submit' && ! $submitFromPreview) ? 'required|accepted' : 'nullable',
            ];

            $messages = [
                'ipv4_prefix.required_without' => 'Please select at least one: IPv4 or IPv6 prefix.',
                'ipv6_prefix.required_without' => 'Please select at least one: IPv4 or IPv6 prefix.',
            ];

            $validated = $request->validate($rules, $messages);

            // Handle file uploads (when submit from preview, use file paths from session preview data)
            $filePaths = [];
            $fileFields = [
                'board_resolution_file',
                'irinn_agreement_file',
                'network_diagram_file',
                'equipment_invoice_file',
                'bandwidth_agreement_file',
            ];

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $path = $file->store('irinn-applications/'.$userId.'/'.date('Y/m'), 'public');
                    $filePaths[$field] = $path;
                }
            }

            // Handle multiple bandwidth invoice files
            if ($request->hasFile('bandwidth_invoice_file')) {
                $bandwidthFiles = [];
                $files = $request->file('bandwidth_invoice_file');
                // Handle both single file and array of files
                if (is_array($files)) {
                    foreach ($files as $file) {
                        if ($file && $file->isValid()) {
                            $path = $file->store('irinn-applications/'.$userId.'/'.date('Y/m').'/bandwidth', 'public');
                            $bandwidthFiles[] = $path;
                        }
                    }
                } else {
                    // Single file
                    if ($files && $files->isValid()) {
                        $path = $files->store('irinn-applications/'.$userId.'/'.date('Y/m').'/bandwidth', 'public');
                        $bandwidthFiles[] = $path;
                    }
                }
                if (! empty($bandwidthFiles)) {
                    $filePaths['bandwidth_invoice_file'] = $bandwidthFiles;
                }
            }

            // Prepare application data (when submit from preview, use stored session data including file paths)
            if ($submitFromPreview) {
                $applicationData = session('irin_preview_data');
            } else {
                // If user is editing from preview and did not re-upload files, keep previous uploaded file paths
                $existingPreview = ($fromPreview && $action === 'preview') ? session('irin_preview_data') : null;

                $applicationData = [
                    'form_version' => 'new',
                    'part1' => [
                        'affiliate_type' => $validated['affiliate_type'] ?? null,
                        'domain_required' => $validated['domain_required'] ?? null,
                    ],
                    'part2' => [
                        'ipv4_prefix' => $validated['ipv4_prefix'] ?? null,
                        'ipv6_prefix' => $validated['ipv6_prefix'] ?? null,
                        'asn_required' => $validated['asn_required'] ?? null,
                    ],
                    'part3' => [
                        'board_resolution_file' => $filePaths['board_resolution_file']
                            ?? ($existingPreview ? data_get($existingPreview, 'part3.board_resolution_file') : null),
                        'irinn_agreement_file' => $filePaths['irinn_agreement_file']
                            ?? ($existingPreview ? data_get($existingPreview, 'part3.irinn_agreement_file') : null),
                    ],
                    'part4' => [
                        'network_diagram_file' => $filePaths['network_diagram_file']
                            ?? ($existingPreview ? data_get($existingPreview, 'part4.network_diagram_file') : null),
                        'equipment_invoice_file' => $filePaths['equipment_invoice_file']
                            ?? ($existingPreview ? data_get($existingPreview, 'part4.equipment_invoice_file') : null),
                        'bandwidth_invoice_file' => $filePaths['bandwidth_invoice_file']
                            ?? ($existingPreview ? (data_get($existingPreview, 'part4.bandwidth_invoice_file', []) ?: []) : []),
                        'bandwidth_agreement_file' => $filePaths['bandwidth_agreement_file']
                            ?? ($existingPreview ? data_get($existingPreview, 'part4.bandwidth_agreement_file') : null),
                        'upstream_provider' => [
                            'name' => $validated['upstream_name'] ?? null,
                            'mobile' => $validated['upstream_mobile'] ?? null,
                            'email' => $validated['upstream_email'] ?? null,
                            'org_name' => $validated['upstream_org_name'] ?? null,
                            'asn_details' => $validated['upstream_asn_details'] ?? null,
                        ],
                    ],
                    'part5' => [
                        'payment_declaration_accepted' => $request->has('payment_declaration'),
                        'application_fee' => 1000,
                    ],
                ];
            }

            // Get user's PAN from registration
            $panCardNo = $user->pancardno;

            if ($action === 'save') {
                // Save as draft (you might want to create a separate drafts table)
                return response()->json([
                    'success' => true,
                    'message' => 'Application saved as draft successfully.',
                ]);
            }

            if ($action === 'preview') {
                // Store preview data in session and redirect to preview page
                session(['irin_preview_data' => $applicationData]);
                // Store raw form data for restoration (exclude file inputs - UploadedFile is not serializable)
                $fileKeys = [
                    'action', '_token',
                    'board_resolution_file', 'irinn_agreement_file',
                    'network_diagram_file', 'equipment_invoice_file',
                    'bandwidth_invoice_file', 'bandwidth_agreement_file',
                ];
                session(['irin_form_data' => $request->except($fileKeys)]);

                return response()->json([
                    'success' => true,
                    'redirect_url' => route('user.applications.irin.preview'),
                ]);
            }

            // Create application - save as draft first (will be updated after payment)
            $application = Application::create([
                'user_id' => $userId,
                'pan_card_no' => $panCardNo,
                'application_id' => Application::generateApplicationId(),
                'application_type' => 'IRINN',
                'status' => 'draft', // Save as draft until payment is successful
                'application_data' => $applicationData,
                'submitted_at' => null, // Will be set after payment
            ]);

            if ($request->boolean('skip_payment')) {
                $applicationData['part5']['application_fee'] = 0;
                $applicationData['part5']['gst_percentage'] = 0;
                $applicationData['part5']['gst_amount'] = 0;
                $applicationData['part5']['total_amount'] = 0;
                $applicationData['part5']['payment_status'] = 'not_required';
                $application->update([
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'application_data' => $applicationData,
                ]);

                if ($submitFromPreview) {
                    session()->forget(['irin_preview_data', 'irin_form_data']);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Application submitted successfully.',
                    'application_id' => $application->application_id,
                    'redirect_url' => route('user.applications.show', $application->id),
                ]);
            }

            // Calculate application fee (Rs. 1000 + GST)
            $applicationFee = 1000.00;
            $gstPercentage = 18; // 18% GST
            $gstAmount = $applicationFee * ($gstPercentage / 100);
            $totalAmount = $applicationFee + $gstAmount;

            // Update application data with payment info
            $applicationData['part5']['application_fee'] = $applicationFee;
            $applicationData['part5']['gst_percentage'] = $gstPercentage;
            $applicationData['part5']['gst_amount'] = $gstAmount;
            $applicationData['part5']['total_amount'] = $totalAmount;
            $applicationData['part5']['payment_status'] = 'pending';
            $application->update(['application_data' => $applicationData]);

            // Generate transaction ID
            $transactionId = 'IRINN-'.time().'-'.strtoupper(\Illuminate\Support\Str::random(8));

            // Create payment transaction
            $paymentTransaction = PaymentTransaction::create([
                'user_id' => $userId,
                'application_id' => $application->id,
                'transaction_id' => $transactionId,
                'payment_mode' => config('services.payu.mode', 'test'),
                'payment_status' => 'pending',
                'amount' => $totalAmount,
                'currency' => 'INR',
                'product_info' => 'IRINN Application Fee',
            ]);

            // Update application data with payment transaction ID
            $applicationData['part5']['payment_transaction_id'] = $paymentTransaction->id;
            $application->update(['application_data' => $applicationData]);

            // Prepare payment data for PayU
            $payuService = new \App\Services\PayuService;
            $paymentData = $payuService->preparePaymentData([
                'transaction_id' => $transactionId,
                'amount' => $totalAmount,
                'product_info' => 'IRINN Application Fee',
                'firstname' => $user->fullname,
                'email' => $user->email,
                'phone' => $user->mobile,
                'success_url' => url(route('user.applications.irin.payment-success', [], false)),
                'failure_url' => url(route('user.applications.irin.payment-failure', [], false)),
                'udf1' => $application->application_id,
                'udf2' => (string) $paymentTransaction->id,
                'udf3' => 'IRINN',
            ]);

            if ($submitFromPreview) {
                session()->forget(['irin_preview_data', 'irin_form_data']);
            }

            // Store user session data in cookie for PayU return (session may be lost)
            $userSessionData = [
                'user_id' => $userId,
                'user_email' => $user->email,
                'user_name' => $user->fullname,
                'user_registration_id' => $user->registrationid,
            ];
            $secureCookie = $request->isSecure() || (bool) config('session.secure', false);

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully. Redirecting to payment...',
                'application_id' => $application->application_id,
                'payment_url' => $payuService->getPaymentUrl(),
                'payment_data' => $paymentData,
            ])->cookie('user_session_data', json_encode($userSessionData), 60, '/', null, $secureCookie, true, false, 'lax');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('IRINN Application Validation Error: '.json_encode($e->errors()));

            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', array_map(function ($errors) {
                    return implode(', ', $errors);
                }, $e->errors())),
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('New IRINN Application Error: '.$e->getMessage().' | Trace: '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Application submission failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show IRINN application preview.
     */
    public function previewIrin()
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            $previewData = session('irin_preview_data');
            if (! $previewData) {
                return redirect()->route('user.irinn.create')
                    ->with('error', 'No preview data found. Please fill the form again.');
            }

            // Get KYC and GST data for display
            $kyc = UserKycProfile::where('user_id', $userId)->latest()->first();
            $gstVerification = null;
            if ($kyc && $kyc->gst_verification_id) {
                $gstVerification = GstVerification::find($kyc->gst_verification_id);
            }

            return view('user.applications.irin.preview', compact('user', 'previewData', 'kyc', 'gstVerification'));
        } catch (Exception $e) {
            Log::error('Error loading IRINN preview: '.$e->getMessage());

            return redirect()->route('user.irinn.create')
                ->with('error', 'Unable to load preview. Please try again.');
        }
    }

    /**
     * Serve IRINN preview documents from session (authenticated).
     */
    public function previewIrinDocument(Request $request, string $doc)
    {
        try {
            $userId = session('user_id');
            if (! $userId) {
                abort(401);
            }

            $previewData = session('irin_preview_data');
            if (! is_array($previewData)) {
                abort(404, 'No preview data found.');
            }

            $allowedDocs = [
                'board_resolution_file',
                'irinn_agreement_file',
                'network_diagram_file',
                'equipment_invoice_file',
                'bandwidth_invoice_file',
                'bandwidth_agreement_file',
            ];

            if (! in_array($doc, $allowedDocs, true)) {
                abort(404);
            }

            $path = null;
            if (in_array($doc, ['board_resolution_file', 'irinn_agreement_file'], true)) {
                $path = data_get($previewData, 'part3.'.$doc);
            } elseif (in_array($doc, ['network_diagram_file', 'equipment_invoice_file', 'bandwidth_agreement_file'], true)) {
                $path = data_get($previewData, 'part4.'.$doc);
            } elseif ($doc === 'bandwidth_invoice_file') {
                $files = data_get($previewData, 'part4.bandwidth_invoice_file', []);
                $index = (int) $request->query('index', 0);
                $path = is_array($files) && isset($files[$index]) ? $files[$index] : null;
            }

            if (! $path || ! is_string($path)) {
                abort(404, 'Document not found.');
            }

            if (! Storage::disk('public')->exists($path)) {
                abort(404, 'File missing.');
            }

            return Storage::disk('public')->response($path);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Error serving IRINN preview document: '.$e->getMessage(), [
                'doc' => $doc,
                'user_id' => session('user_id'),
            ]);
            abort(500, 'Unable to load document.');
        }
    }

    /**
     * Handle PayU payment success callback for IRINN (application fee or invoice / bulk payment).
     */
    public function paymentSuccess(Request $request): RedirectResponse
    {
        try {
            $payuService = new PayuService;
            $verified = $payuService->verifyPayment($request->all());

            $processor = app(PayuGatewayPaymentProcessor::class);
            $payuResponseFields = $processor->extractPayuResponseFields($request);

            $rawUdf3 = $payuResponseFields['udf3'] ?? null;
            $isApplicationFeeMarker = $rawUdf3 === null || $rawUdf3 === '' || strtoupper((string) $rawUdf3) === 'IRINN';
            $isInvoicePayment = $rawUdf3 !== null && $rawUdf3 !== '' && ! $isApplicationFeeMarker;

            $invoicesTarget = route('user.invoices.index', [], false);
            $applicationsTarget = route('user.applications.index', [], false);

            if ($verified['success']) {
                $paymentTransaction = $processor->resolvePaymentTransaction($payuResponseFields);

                if (! $paymentTransaction) {
                    Log::error('IRINN payment success: payment transaction not found', [
                        'txnid' => $payuResponseFields['txnid'] ?? null,
                    ]);

                    return $this->redirectAfterIrinnPayuReturn(
                        $isInvoicePayment ? $invoicesTarget : $applicationsTarget,
                        null,
                        'Payment record not found. Please contact support if money was debited.'
                    );
                }

                $paymentStatus = $processor->normalizePaymentStatus((string) ($payuResponseFields['status'] ?? ''));
                $processor->processAfterGatewayResponse(
                    $request,
                    $paymentTransaction,
                    $payuResponseFields,
                    $paymentStatus,
                    'browser_return'
                );

                $target = $isInvoicePayment ? $invoicesTarget : $applicationsTarget;
                $message = $isInvoicePayment
                    ? 'Payment successful! Your invoice has been updated.'
                    : 'Payment successful! Your IRINN application has been submitted.';

                return $this->redirectAfterIrinnPayuReturn($target, $message, null);
            }

            return $this->redirectAfterIrinnPayuReturn(
                $isInvoicePayment ? $invoicesTarget : $applicationsTarget,
                null,
                $verified['message'] ?: 'Payment verification failed. Please contact support.'
            );
        } catch (Exception $e) {
            Log::error('IRINN Payment Success Error: '.$e->getMessage());

            return $this->redirectAfterIrinnPayuReturn(
                route('user.applications.index', [], false),
                null,
                'Error processing payment. Please contact support.'
            );
        }
    }

    /**
     * Restore session via login-from-cookie when PayU drops the Laravel session on return.
     */
    private function redirectAfterIrinnPayuReturn(string $relativeTarget, ?string $success, ?string $error): RedirectResponse
    {
        if (! session('user_id')) {
            $params = ['redirect' => $relativeTarget];
            if ($success !== null && $success !== '') {
                $params['success'] = urlencode($success);
            }
            if ($error !== null && $error !== '') {
                $params['error'] = urlencode($error);
            }

            return redirect()->route('user.login-from-cookie', $params);
        }

        $redirect = redirect($relativeTarget);
        if ($success !== null && $success !== '') {
            $redirect->with('success', $success);
        }
        if ($error !== null && $error !== '') {
            $redirect->with('error', $error);
        }

        return $redirect;
    }

    /**
     * Handle PayU payment failure callback for IRINN (application fee or invoice payment).
     */
    public function paymentFailure(Request $request): RedirectResponse
    {
        try {
            $processor = app(PayuGatewayPaymentProcessor::class);
            $payuResponseFields = $processor->extractPayuResponseFields($request);

            $rawUdf3 = $payuResponseFields['udf3'] ?? null;
            $isApplicationFeeMarker = $rawUdf3 === null || $rawUdf3 === '' || strtoupper((string) $rawUdf3) === 'IRINN';
            $isInvoicePayment = $rawUdf3 !== null && $rawUdf3 !== '' && ! $isApplicationFeeMarker;

            $transactionId = $request->input('txnid');
            $paymentTransaction = $transactionId
                ? PaymentTransaction::where('transaction_id', $transactionId)->first()
                : null;

            if ($isInvoicePayment && $paymentTransaction) {
                $paymentStatus = $processor->normalizePaymentStatus((string) ($payuResponseFields['status'] ?? ''));
                if ($paymentStatus === 'pending') {
                    $paymentStatus = 'failed';
                }
                $processor->processAfterGatewayResponse(
                    $request,
                    $paymentTransaction,
                    $payuResponseFields,
                    $paymentStatus,
                    'browser_return_failure'
                );

                return $this->redirectAfterIrinnPayuReturn(
                    route('user.invoices.index', [], false),
                    null,
                    'Payment failed or was cancelled. You can try again from your invoices.'
                );
            }

            if ($paymentTransaction) {
                $application = Application::find($paymentTransaction->application_id);

                if ($application && $application->application_type === 'IRINN') {
                    $paymentTransaction->update([
                        'payment_status' => 'failed',
                        'response_message' => $request->input('error') ?? 'Payment failed',
                    ]);

                    $applicationData = $application->application_data ?? [];
                    $applicationData['part5']['payment_status'] = 'pending';
                    $applicationData['part5']['payment_transaction_id'] = $paymentTransaction->id;

                    $application->update([
                        'status' => 'draft',
                        'application_data' => $applicationData,
                    ]);

                    $target = route('user.applications.show', $application->id, false);

                    return $this->redirectAfterIrinnPayuReturn(
                        $target,
                        null,
                        'Payment failed. Your application has been saved as draft. You can pay later from the application details page.'
                    );
                }
            }

            return $this->redirectAfterIrinnPayuReturn(
                route('user.applications.index', [], false),
                null,
                'Payment failed. Please try again or contact support.'
            );
        } catch (Exception $e) {
            Log::error('IRINN Payment Failure Error: '.$e->getMessage());

            return $this->redirectAfterIrinnPayuReturn(
                route('user.applications.index', [], false),
                null,
                'Error processing payment failure. Please contact support.'
            );
        }
    }

    /**
     * Generate Application Details PDF.
     */
    private function generateApplicationPdf(Application $application)
    {
        $data = $application->application_data;
        $user = $application->user;

        // Get company details from GST verification if available
        $gstVerification = $application->gstVerification;
        $companyDetails = [];
        if ($gstVerification) {
            $companyDetails = [
                'legal_name' => $gstVerification->legal_name,
                'trade_name' => $gstVerification->trade_name,
                'pan' => $gstVerification->pan,
                'state' => $gstVerification->state,
                'registration_date' => $gstVerification->registration_date?->format('d/m/Y'),
                'gst_type' => $gstVerification->gst_type,
                'company_status' => $gstVerification->company_status,
                'primary_address' => $gstVerification->primary_address,
            ];

            // Parse primary address if it's a JSON string
            if ($gstVerification->verification_data) {
                $verificationData = is_string($gstVerification->verification_data)
                    ? json_decode($gstVerification->verification_data, true)
                    : $gstVerification->verification_data;

                if (isset($verificationData['result']['source_output']['principal_place_of_business_fields']['principal_place_of_business_address'])) {
                    $address = $verificationData['result']['source_output']['principal_place_of_business_fields']['principal_place_of_business_address'];
                    $companyDetails['pradr'] = [
                        'addr' => trim(($address['door_number'] ?? '').' '.($address['building_name'] ?? '').' '.($address['street'] ?? '').' '.($address['location'] ?? '').' '.($address['dst'] ?? '').' '.($address['city'] ?? '').' '.($address['state_name'] ?? '').' '.($address['pincode'] ?? '')),
                    ];
                }
            }
        }

        // Convert PDF documents to images if possible
        $pdfImages = [];
        if (isset($data['files']) && extension_loaded('imagick')) {
            foreach ($data['files'] as $field => $path) {
                $fullPath = storage_path('app/public/'.$path);
                if (file_exists($fullPath) && strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) === 'pdf') {
                    try {
                        $imagick = new \Imagick;
                        $imagick->setResolution(150, 150);
                        $imagick->readImage($fullPath.'[0]'); // Read first page
                        $imagick->setImageFormat('png');
                        $imagick->setImageCompressionQuality(90);
                        $pdfImages[$field] = base64_encode($imagick->getImageBlob());
                        $imagick->clear();
                        $imagick->destroy();
                    } catch (\Exception $e) {
                        // If conversion fails, leave it null
                        $pdfImages[$field] = null;
                    }
                }
            }
        }

        $pdf = Pdf::loadView('user.applications.irin.pdf.application', [
            'application' => $application,
            'user' => $user,
            'data' => $data,
            'companyDetails' => $companyDetails,
            'pdfImages' => $pdfImages,
        ])->setOption('enable-local-file-access', true);

        return $pdf;
    }

    /**
     * Generate Invoice PDF.
     */
    private function generateInvoicePdf(Application $application)
    {
        $data = $application->application_data;
        $user = $application->user;

        // Get company details from GST verification if available
        $gstVerification = $application->gstVerification;
        $companyDetails = [];
        if ($gstVerification) {
            $companyDetails = [
                'legal_name' => $gstVerification->legal_name,
                'trade_name' => $gstVerification->trade_name,
                'pan' => $gstVerification->pan,
                'state' => $gstVerification->state,
                'registration_date' => $gstVerification->registration_date?->format('d/m/Y'),
                'gst_type' => $gstVerification->gst_type,
                'company_status' => $gstVerification->company_status,
                'primary_address' => $gstVerification->primary_address,
            ];

            // Parse primary address if it's a JSON string
            if ($gstVerification->verification_data) {
                $verificationData = is_string($gstVerification->verification_data)
                    ? json_decode($gstVerification->verification_data, true)
                    : $gstVerification->verification_data;

                if (isset($verificationData['result']['source_output']['principal_place_of_business_fields']['principal_place_of_business_address'])) {
                    $address = $verificationData['result']['source_output']['principal_place_of_business_fields']['principal_place_of_business_address'];
                    $companyDetails['pradr'] = [
                        'addr' => trim(($address['door_number'] ?? '').' '.($address['building_name'] ?? '').' '.($address['street'] ?? '').' '.($address['location'] ?? '').' '.($address['dst'] ?? '').' '.($address['city'] ?? '').' '.($address['state_name'] ?? '').' '.($address['pincode'] ?? '')),
                        'state_name' => $address['state_name'] ?? null,
                    ];
                    $companyDetails['state_info'] = [
                        'name' => $address['state_name'] ?? $gstVerification->state,
                    ];
                }
            }
        }

        // Calculate invoice number (format: NXNIR25-26/XXXX)
        $invoiceNumber = 'NXNIR'.date('y').'-'.(date('y') + 1).'/'.str_pad($application->id, 4, '0', STR_PAD_LEFT);

        $pdf = Pdf::loadView('user.applications.irin.pdf.invoice', [
            'application' => $application,
            'user' => $user,
            'data' => $data,
            'companyDetails' => $companyDetails,
            'invoiceNumber' => $invoiceNumber,
            'invoiceDate' => now('Asia/Kolkata')->format('d/m/Y'),
            'dueDate' => now('Asia/Kolkata')->addDays(28)->format('d/m/Y'),
        ])->setPaper('a4', 'portrait')
            ->setOption('margin-top', 6)
            ->setOption('margin-bottom', 6)
            ->setOption('margin-left', 6)
            ->setOption('margin-right', 6)
            ->setOption('enable-local-file-access', true);

        return $pdf;
    }

    /**
     * Calculate IPv4 fee.
     */
    private function calculateIPv4Fee($size)
    {
        $pricing = IpPricing::getPricing('ipv4', $size);

        if ($pricing) {
            return $pricing->calculateFee();
        }

        // Fallback to old calculation if pricing not found
        $addresses = $size === '/24' ? 256 : 512;

        return 27500 * pow(1.35, log($addresses, 2) - 8);
    }

    /**
     * Download Application PDF.
     */
    public function downloadApplicationPdf($id)
    {
        try {
            $userId = session('user_id');
            $application = Application::with('gstVerification')
                ->where('id', $id)
                ->where('user_id', $userId)
                ->firstOrFail();

            $applicationPdf = $this->generateApplicationPdf($application);

            return $applicationPdf->download($application->application_id.'_application.pdf');
        } catch (Exception $e) {
            Log::error('Error downloading application PDF: '.$e->getMessage());

            return redirect()->route('user.applications.show', $id)
                ->with('error', 'Unable to download application PDF.');
        }
    }

    /**
     * Download Invoice PDF.
     */
    public function downloadInvoicePdf($id)
    {
        try {
            $userId = session('user_id');
            $application = Application::with('gstVerification')
                ->where('id', $id)
                ->where('user_id', $userId)
                ->firstOrFail();

            $invoicePdf = $this->generateInvoicePdf($application);

            return $invoicePdf->download($application->application_id.'_invoice.pdf');
        } catch (Exception $e) {
            Log::error('Error downloading invoice PDF: '.$e->getMessage());

            return redirect()->route('user.applications.show', $id)
                ->with('error', 'Unable to download invoice PDF.');
        }
    }

    /**
     * Serve application document securely.
     */
    public function serveDocument($id, Request $request)
    {
        try {
            $userId = session('user_id');
            $documentKey = $request->input('doc');

            if (! $documentKey) {
                abort(400, 'Document key is required.');
            }

            $application = Application::where('id', $id)
                ->where('user_id', $userId)
                ->firstOrFail();

            $applicationData = $application->application_data ?? [];
            $filePath = null;

            if ($application->application_type !== 'IRINN') {
                abort(404, 'Application not found.');
            }

            if (Application::isIrinnStoredPathDocumentKey($documentKey)) {
                $filePath = $application->getAttribute($documentKey);
            } elseif (isset($applicationData['part3'][$documentKey])) {
                $filePath = $applicationData['part3'][$documentKey];
            } elseif (isset($applicationData['part4'][$documentKey])) {
                $filePath = $applicationData['part4'][$documentKey];
                if ($documentKey === 'bandwidth_invoice_file' && is_array($filePath)) {
                    $fileIndex = $request->input('index', 0);
                    if (isset($filePath[$fileIndex])) {
                        $filePath = $filePath[$fileIndex];
                    } else {
                        abort(404, 'Document index not found.');
                    }
                }
            }

            if (! $filePath) {
                abort(404, 'Document not found.');
            }

            if (! Storage::disk('public')->exists($filePath)) {
                abort(404, 'File not found on server.');
            }

            $fullPath = Storage::disk('public')->path($filePath);
            $fileName = basename($filePath);

            return response()->file($fullPath, [
                'Content-Type' => Storage::disk('public')->mimeType($filePath),
                'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Application not found.');
        } catch (Exception $e) {
            Log::error('Error serving document: '.$e->getMessage());
            abort(500, 'Unable to serve document.');
        }
    }

    /**
     * Calculate IPv6 fee.
     */
    private function calculateIPv6Fee($size)
    {
        $pricing = IpPricing::getPricing('ipv6', $size);

        if ($pricing) {
            return $pricing->calculateFee();
        }

        // Fallback to old calculation if pricing not found
        if ($size === '/48') {
            return 24199;
        } elseif ($size === '/32') {
            $addresses = 16777216;
            $log2Value = log($addresses, 2);

            return 24199 * pow(1.35, $log2Value - 22);
        }

        return 0;
    }

    /**
     * Get IP pricing for frontend (API endpoint).
     * Returns only currently effective pricing based on effective dates.
     */
    public function getIpPricing()
    {
        try {
            $pricings = IpPricing::getCurrentlyEffective();

            return response()->json([
                'success' => true,
                'data' => $pricings,
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching IP pricing: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pricing.',
            ], 500);
        }
    }

    /**
     * Map GST constitution_of_business to affiliate_identity.
     */
    private function mapConstitutionToAffiliateIdentity(?string $constitution): string
    {
        if (empty($constitution)) {
            return 'sole_proprietorship'; // Default fallback
        }

        $constitution = strtolower(trim($constitution));

        // Map various GST constitution types to affiliate_identity
        if (str_contains($constitution, 'proprietorship') || str_contains($constitution, 'proprietor')) {
            return 'sole_proprietorship';
        }

        if (str_contains($constitution, 'partnership')) {
            return 'partnership';
        }

        if (str_contains($constitution, 'private') || str_contains($constitution, 'pvt') ||
            str_contains($constitution, 'limited') || str_contains($constitution, 'ltd') ||
            str_contains($constitution, 'public') || str_contains($constitution, 'psu') ||
            str_contains($constitution, 'company') || str_contains($constitution, 'corporation')) {
            return 'pvt_ltd';
        }

        if (str_contains($constitution, 'school') || str_contains($constitution, 'college') ||
            str_contains($constitution, 'education') || str_contains($constitution, 'institution')) {
            return 'schools_colleges';
        }

        if (str_contains($constitution, 'bank') || str_contains($constitution, 'financial')) {
            return 'banks';
        }

        // Default fallback
        return 'sole_proprietorship';
    }

    /**
     * Get display name for affiliate_identity.
     */
    private function getAffiliateIdentityDisplayName(string $affiliateIdentity): string
    {
        $displayNames = [
            'partnership' => 'Partnership Firms',
            'pvt_ltd' => 'Pvt Ltd Co./Ltd Co. and PSU Company',
            'sole_proprietorship' => 'Sole Proprietorship',
            'schools_colleges' => 'Schools, College establishments',
            'banks' => 'Private and Nationalised Bank',
        ];

        return $displayNames[$affiliateIdentity] ?? 'Sole Proprietorship';
    }

    /**
     * Initiate wallet payment for IRINN application during submission.
     */
    public function initiatePaymentWithWallet(Request $request): JsonResponse
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User session expired. Please login again.',
                ], 401);
            }

            $wallet = $user->wallet;
            if (! $wallet || $wallet->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not available or inactive. Please use PayU payment.',
                ], 400);
            }

            // Get application data from request
            $action = $request->input('action');

            if ($action !== 'submit') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid action. Please submit the application.',
                ], 400);
            }

            // Validate required fields (flat form field names)
            $validated = $request->validate([
                'affiliate_type' => 'required|string|in:new,transfer',
                'domain_required' => 'required|string|in:yes,no',
                'ipv4_prefix' => 'required|string|in:/24,/23',
                'ipv6_prefix' => 'required|string|in:/48,/32',
                'asn_required' => 'required|string|in:yes,no',
            ]);

            // Restructure data into parts (same as storeIrinNew)
            $applicationData = [
                'part1' => [
                    'affiliate_type' => $validated['affiliate_type'],
                    'domain_required' => $validated['domain_required'],
                ],
                'part2' => [
                    'ipv4_prefix' => $validated['ipv4_prefix'],
                    'ipv6_prefix' => $validated['ipv6_prefix'],
                    'asn_required' => $validated['asn_required'],
                ],
            ];

            // Add other form fields to application data
            $allData = $request->except(['action', '_token', 'use_wallet_payment']);

            // Handle file uploads (same as storeIrinNew)
            $filePaths = [];
            $fileFields = [
                'board_resolution_file',
                'irinn_agreement_file',
                'network_diagram_file',
                'equipment_invoice_file',
                'bandwidth_agreement_file',
            ];

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $path = $file->store('irinn-applications/'.$userId.'/'.date('Y/m'), 'public');
                    $filePaths[$field] = $path;
                }
            }

            // Handle multiple bandwidth invoice files
            if ($request->hasFile('bandwidth_invoice_file')) {
                $bandwidthFiles = [];
                $files = $request->file('bandwidth_invoice_file');
                if (is_array($files)) {
                    foreach ($files as $file) {
                        if ($file && $file->isValid()) {
                            $path = $file->store('irinn-applications/'.$userId.'/'.date('Y/m').'/bandwidth', 'public');
                            $bandwidthFiles[] = $path;
                        }
                    }
                } else {
                    if ($files && $files->isValid()) {
                        $path = $files->store('irinn-applications/'.$userId.'/'.date('Y/m').'/bandwidth', 'public');
                        $bandwidthFiles[] = $path;
                    }
                }
                if (! empty($bandwidthFiles)) {
                    $filePaths['bandwidth_invoice_file'] = $bandwidthFiles;
                }
            }

            // Add Part 3: Documents
            $applicationData['part3'] = [
                'board_resolution_file' => $filePaths['board_resolution_file'] ?? null,
                'irinn_agreement_file' => $filePaths['irinn_agreement_file'] ?? null,
            ];

            // Add Part 4: Resource Justification
            $applicationData['part4'] = [
                'network_diagram_file' => $filePaths['network_diagram_file'] ?? null,
                'equipment_invoice_file' => $filePaths['equipment_invoice_file'] ?? null,
                'bandwidth_invoice_file' => $filePaths['bandwidth_invoice_file'] ?? [],
                'bandwidth_agreement_file' => $filePaths['bandwidth_agreement_file'] ?? null,
                'upstream_provider' => [
                    'name' => $request->input('upstream_name'),
                    'mobile' => $request->input('upstream_mobile'),
                    'email' => $request->input('upstream_email'),
                    'org_name' => $request->input('upstream_org_name'),
                    'asn_details' => $request->input('upstream_asn_details'),
                ],
            ];

            // Add Part 5: Payment
            $applicationData['part5'] = [
                'payment_declaration' => $request->input('payment_declaration', false),
            ];

            // Add form version
            $applicationData['form_version'] = 'new';

            // Calculate application fee (Rs. 1000 + GST)
            $applicationFee = 1000.00;
            $gstPercentage = 18;
            $gstAmount = $applicationFee * ($gstPercentage / 100);
            $totalAmount = $applicationFee + $gstAmount;

            // Check wallet balance
            if ($wallet->balance < $totalAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance. Please top-up your wallet or use PayU payment.',
                ], 400);
            }

            // Get PAN from KYC
            $kyc = UserKycProfile::where('user_id', $userId)->latest()->first();
            $panCardNo = $kyc?->authorized_pan ?? $user->pan_card_no;

            // Create application (initially as draft, will move to helpdesk after successful wallet payment)
            $application = Application::create([
                'user_id' => $userId,
                'pan_card_no' => $panCardNo,
                'application_id' => Application::generateApplicationId(),
                'application_type' => 'IRINN',
                'status' => 'draft',
                'application_data' => $applicationData,
                'submitted_at' => null,
            ]);

            // Generate transaction ID
            $transactionId = 'IRINN-WLT-'.time().'-'.strtoupper(Str::random(8));

            // Create payment transaction
            $paymentTransaction = PaymentTransaction::create([
                'user_id' => $userId,
                'application_id' => $application->id,
                'transaction_id' => $transactionId,
                'payment_status' => 'pending',
                'payment_mode' => 'wallet',
                'amount' => $totalAmount,
                'currency' => 'INR',
                'product_info' => 'IRINN Application Fee',
                'response_message' => 'Application payment via wallet',
            ]);

            // Debit wallet
            $walletService = app(\App\Services\WalletService::class);
            $success = $walletService->debitWallet(
                $wallet,
                $totalAmount,
                $transactionId,
                $paymentTransaction->id,
                $application->id,
                "IRINN application fee payment - {$application->application_id}"
            );

            if (! $success) {
                $paymentTransaction->update(['payment_status' => 'failed']);

                return response()->json([
                    'success' => false,
                    'message' => 'Wallet payment failed. Please try again or use PayU payment.',
                ], 500);
            }

            // Update payment transaction
            $paymentTransaction->update([
                'payment_status' => 'success',
                'payment_id' => $transactionId,
            ]);

            // Update application data with payment info
            $applicationData['part5']['payment_transaction_id'] = $paymentTransaction->id;
            $applicationData['part5']['application_fee'] = $applicationFee;
            $applicationData['part5']['gst_percentage'] = $gstPercentage;
            $applicationData['part5']['gst_amount'] = $gstAmount;
            $applicationData['part5']['total_amount'] = $totalAmount;
            $applicationData['part5']['payment_status'] = 'success';
            $applicationData['part5']['payment_id'] = $transactionId;
            $applicationData['part5']['paid_at'] = now('Asia/Kolkata')->toDateTimeString();
            $applicationData['part5']['payment_mode'] = 'wallet';

            // Update application status to helpdesk (submitted; new workflow: helpdesk -> hostmaster -> billing)
            $application->update([
                'status' => 'helpdesk',
                'submitted_at' => now('Asia/Kolkata'),
                'application_data' => $applicationData,
            ]);

            // Log status change for workflow
            ApplicationStatusHistory::log(
                $application->id,
                null,
                'helpdesk',
                'user',
                $userId,
                'IRINN application submitted via wallet payment'
            );

            Log::info("IRINN Application {$application->application_id} submitted via wallet payment. Transaction ID: {$transactionId}");

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully and payment completed via wallet.',
                'application_id' => $application->application_id,
                'redirect_url' => route('user.applications.index'),
            ]);
        } catch (Exception $e) {
            Log::error('IRINN Wallet Payment Error: '.$e->getMessage().' | Trace: '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Unable to process wallet payment. Please try again.',
            ], 500);
        }
    }

    /**
     * Pay application fee with wallet for draft IRINN application.
     */
    public function payNowWithWallet(Request $request, int $id): RedirectResponse
    {
        // Only allow POST requests for security
        if (! $request->isMethod('POST')) {
            return redirect()->route('user.applications.index')
                ->with('error', 'Invalid request method. Please use the payment button to pay with wallet.');
        }

        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User session expired. Please login again.');
            }

            $wallet = $user->wallet;
            if (! $wallet || $wallet->status !== 'active') {
                return redirect()->route('user.applications.show', $id)
                    ->with('error', 'Wallet not available or inactive. Please use PayU payment.');
            }

            $application = Application::where('id', $id)
                ->where('user_id', $userId)
                ->where('application_type', 'IRINN')
                ->where('status', 'draft')
                ->firstOrFail();

            $applicationData = $application->application_data ?? [];
            $paymentData = $applicationData['part5'] ?? null;

            if (! $paymentData || ($paymentData['payment_status'] ?? null) !== 'pending') {
                return redirect()->route('user.applications.show', $id)
                    ->with('error', 'This application is not waiting for payment or has already been paid.');
            }

            // Get amount from application data
            $totalAmount = (float) ($paymentData['total_amount'] ?? 1180.00); // Default: 1000 + 18% GST

            // Check wallet balance
            if ($wallet->balance < $totalAmount) {
                return redirect()->route('user.applications.show', $id)
                    ->with('error', 'Insufficient wallet balance. Please top-up your wallet or use PayU payment.');
            }

            // Generate transaction ID
            $transactionId = 'IRINN-WLT-'.time().'-'.strtoupper(Str::random(8));

            // Create payment transaction
            $paymentTransaction = PaymentTransaction::create([
                'user_id' => $userId,
                'application_id' => $application->id,
                'transaction_id' => $transactionId,
                'payment_status' => 'pending',
                'payment_mode' => 'wallet',
                'amount' => $totalAmount,
                'currency' => 'INR',
                'product_info' => 'IRINN Application Fee',
                'response_message' => 'Application payment via wallet',
            ]);

            // Debit wallet
            $walletService = app(\App\Services\WalletService::class);
            $success = $walletService->debitWallet(
                $wallet,
                $totalAmount,
                $transactionId,
                $paymentTransaction->id,
                $application->id,
                "IRINN application fee payment - {$application->application_id}"
            );

            if (! $success) {
                $paymentTransaction->update(['payment_status' => 'failed']);

                return redirect()->route('user.applications.show', $id)
                    ->with('error', 'Wallet payment failed. Please try again or use PayU payment.');
            }

            // Update payment transaction
            $paymentTransaction->update([
                'payment_status' => 'success',
                'payment_id' => $transactionId,
            ]);

            // Update application payment status and mark as submitted
            $applicationData['part5']['payment_transaction_id'] = $paymentTransaction->id;
            $applicationData['part5']['payment_status'] = 'success';
            $applicationData['part5']['payment_id'] = $transactionId;
            $applicationData['part5']['paid_at'] = now('Asia/Kolkata')->toDateTimeString();
            $applicationData['part5']['payment_mode'] = 'wallet';

            $application->update([
                'status' => 'helpdesk',
                'submitted_at' => now('Asia/Kolkata'),
                'application_data' => $applicationData,
            ]);

            // Log status change for workflow
            ApplicationStatusHistory::log(
                $application->id,
                null,
                'helpdesk',
                'user',
                $userId,
                'IRINN application payment completed via wallet'
            );

            Log::info("IRINN Application {$application->application_id} payment completed via wallet. Transaction ID: {$transactionId}");

            return redirect()->route('user.applications.show', $application->id)
                ->with('success', 'Payment completed successfully. Your application has been submitted.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('user.applications.index')
                ->with('error', 'Application not found or you do not have permission to access it.');
        } catch (Exception $e) {
            Log::error('IRINN Wallet Payment Error: '.$e->getMessage().' | Trace: '.$e->getTraceAsString());

            return redirect()->route('user.applications.show', $id)
                ->with('error', 'Unable to process wallet payment. Please try again.');
        }
    }

    /**
     * Persist IRINN application from the multi-step create-new flow (normalized columns, no JSON blob).
     */
    protected function storeIrinnNormalizedApplication(Request $request, int $userId, Registration $user): JsonResponse
    {
        try {
            return $this->storeIrinnNormalizedApplicationAttempt($request, $userId, $user);
        } catch (Exception $e) {
            Log::error('IRINN normalized application error: '.$e->getMessage().' | '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Application submission failed. Please try again.',
            ], 500);
        }
    }

    protected function storeIrinnNormalizedApplicationAttempt(Request $request, int $userId, Registration $user): JsonResponse
    {
        if (Application::where('user_id', $userId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You can have only one application. Please manage your existing application.',
            ], 422);
        }

        $irinnRequest = StoreIrinNewFlowRequest::createFrom($request);
        $irinnRequest->setContainer(app())->setRedirector(app('redirect'));

        $validator = Validator::make(
            $irinnRequest->all(),
            $irinnRequest->rules(),
            $irinnRequest->messages(),
            $irinnRequest->attributes()
        );
        $irinnRequest->withValidator($validator);
        $validator->after(function (\Illuminate\Validation\Validator $v) use ($irinnRequest): void {
            if (! $irinnRequest->filled('irinn_ipv4_resource_size') && ! $irinnRequest->filled('irinn_ipv6_resource_size')) {
                $v->errors()->add('irinn_ipv4_resource_size', 'Please select at least one IPv4 or IPv6 resource (Step 5).');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $baseDir = 'irinn-applications/'.$userId.'/'.date('Y/m');

        $store = function (string $key) use ($request, $baseDir): ?string {
            if (! $request->hasFile($key)) {
                return null;
            }

            $file = $request->file($key);

            return $file?->store($baseDir, 'public');
        };

        $paths = [
            'irinn_registration_document_path' => $store('irinn_registration_document'),
            'irinn_ca_declaration_path' => $store('irinn_ca_declaration_file'),
            'irinn_signature_proof_path' => $store('irinn_signature_proof'),
            'irinn_board_resolution_path' => $store('irinn_board_resolution'),
            'irinn_kyc_network_diagram_path' => $store('irinn_kyc_network_diagram'),
            'irinn_kyc_equipment_invoice_path' => $store('irinn_kyc_equipment_invoice'),
            'irinn_kyc_bandwidth_proof_path' => $store('irinn_kyc_bandwidth_proof'),
            'irinn_kyc_irinn_agreement_path' => $store('irinn_kyc_irinn_agreement'),
        ];

        $labels = $request->input('kyc_other_document_label', []);
        $otherFiles = $request->file('kyc_other_document_file', []);
        if ($otherFiles instanceof \Illuminate\Http\UploadedFile) {
            $otherFiles = [$otherFiles];
        }
        if (! is_array($labels)) {
            $labels = [];
        }
        if (! is_array($otherFiles)) {
            $otherFiles = [];
        }
        for ($i = 1; $i <= 5; $i++) {
            $idx = $i - 1;
            $labelKey = "irinn_other_doc_{$i}_label";
            $pathKey = "irinn_other_doc_{$i}_path";
            $paths[$labelKey] = isset($labels[$idx]) ? (is_string($labels[$idx]) ? trim($labels[$idx]) : null) : null;
            $file = $otherFiles[$idx] ?? null;
            $paths[$pathKey] = ($file && $file->isValid()) ? $file->store($baseDir.'/other', 'public') : null;
        }

        $gstVerificationId = null;
        if ($request->filled('gst_verification_request_id')) {
            $gstVerificationId = GstVerification::query()
                ->where('user_id', $userId)
                ->where('request_id', $request->input('gst_verification_request_id'))
                ->value('id');
        }

        $mcaVerificationId = null;
        if ($request->filled('mca_verification_request_id')) {
            $mcaVerificationId = McaVerification::query()
                ->where('user_id', $userId)
                ->where('request_id', $request->input('mca_verification_request_id'))
                ->value('id');
        }

        $feeRaw = $request->input('irinn_resource_fee_amount', $request->input('resource_fee_amount'));

        $application = Application::create(array_merge([
            'user_id' => $userId,
            'pan_card_no' => $user->pancardno,
            'application_id' => Application::generateApplicationId(),
            'application_type' => 'IRINN',
            'status' => 'helpdesk',
            'submitted_at' => now(),
            'application_data' => null,
            'gst_verification_id' => $gstVerificationId,
            'mca_verification_id' => $mcaVerificationId,
            'irinn_form_version' => $request->input('irinn_form_version', 'create_new_v1'),
            'irinn_current_stage' => 'helpdesk',
            'irinn_company_type' => $request->input('irinn_company_type'),
            'irinn_cin_number' => $request->input('irinn_cin_number'),
            'irinn_udyam_number' => $request->input('irinn_udyam_number'),
            'irinn_organisation_name' => $request->input('irinn_organisation_name'),
            'irinn_organisation_address' => $request->input('irinn_organisation_address'),
            'irinn_organisation_postcode' => $request->input('irinn_organisation_postcode'),
            'irinn_industry_type' => $request->input('irinn_industry_type'),
            'irinn_account_name' => $request->input('irinn_account_name'),
            'irinn_has_gst_number' => $request->has('irinn_has_gst_number'),
            'irinn_billing_gstin' => $request->input('irinn_billing_gstin'),
            'irinn_billing_legal_name' => $request->input('irinn_billing_legal_name'),
            'irinn_billing_pan' => $request->input('irinn_billing_pan'),
            'irinn_billing_address' => $request->input('irinn_billing_address'),
            'irinn_billing_postcode' => $request->input('irinn_billing_postcode'),
            'irinn_mr_name' => $request->input('irinn_mr_name'),
            'irinn_mr_designation' => $request->input('irinn_mr_designation'),
            'irinn_mr_email' => $request->input('irinn_mr_email'),
            'irinn_mr_mobile' => $request->input('irinn_mr_mobile'),
            'irinn_mr_din' => $request->input('irinn_mr_din'),
            'irinn_tp_name' => $request->input('irinn_tp_name'),
            'irinn_tp_designation' => $request->input('irinn_tp_designation'),
            'irinn_tp_email' => $request->input('irinn_tp_email'),
            'irinn_tp_mobile' => $request->input('irinn_tp_mobile'),
            'irinn_abuse_name' => $request->input('irinn_abuse_name'),
            'irinn_abuse_designation' => $request->input('irinn_abuse_designation'),
            'irinn_abuse_email' => $request->input('irinn_abuse_email'),
            'irinn_abuse_mobile' => $request->input('irinn_abuse_mobile'),
            'irinn_br_name' => $request->input('irinn_br_name'),
            'irinn_br_designation' => $request->input('irinn_br_designation'),
            'irinn_br_email' => $request->input('irinn_br_email'),
            'irinn_br_mobile' => $request->input('irinn_br_mobile'),
            'irinn_asn_required' => $request->has('irinn_asn_required'),
            'irinn_ipv4_resource_size' => $request->input('irinn_ipv4_resource_size'),
            'irinn_ipv4_resource_addresses' => $request->filled('irinn_ipv4_resource_addresses') ? (int) $request->input('irinn_ipv4_resource_addresses') : null,
            'irinn_ipv6_resource_size' => $request->input('irinn_ipv6_resource_size'),
            'irinn_ipv6_resource_addresses' => $request->filled('irinn_ipv6_resource_addresses') ? (int) $request->input('irinn_ipv6_resource_addresses') : null,
            'irinn_resource_fee_amount' => $feeRaw !== null && $feeRaw !== '' ? (float) $feeRaw : null,
            'irinn_upstream_provider_name' => $request->input('irinn_upstream_provider_name'),
            'irinn_upstream_as_number' => $request->input('irinn_upstream_as_number'),
            'irinn_upstream_mobile' => $request->input('irinn_upstream_mobile'),
            'irinn_upstream_email' => $request->input('irinn_upstream_email'),
            'irinn_sign_name' => $request->input('irinn_sign_name'),
            'irinn_sign_dob' => $request->input('irinn_sign_dob'),
            'irinn_sign_pan' => $request->input('irinn_sign_pan'),
            'irinn_sign_email' => $request->input('irinn_sign_email'),
            'irinn_sign_mobile' => $request->input('irinn_sign_mobile'),
        ], $paths));

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully.',
            'application_id' => $application->application_id,
            'redirect_url' => route('user.applications.show', $application->id),
        ]);
    }

    protected function storeIrinnNormalizedResubmissionAttempt(Request $request, int $userId, Registration $user): JsonResponse
    {
        $application = Application::query()
            ->where('id', (int) $request->input('irinn_resubmit_application_id'))
            ->where('user_id', $userId)
            ->where('application_type', 'IRINN')
            ->where('status', 'resubmission_requested')
            ->first();

        if (! $application || ! $application->hasIrinnNormalizedData()) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to resubmit this application.',
            ], 422);
        }

        $irinnRequest = StoreIrinNewFlowRequest::createFrom($request);
        $rules = StoreIrinNewFlowRequest::rulesForNormalizedResubmit();

        $validator = Validator::make(
            $irinnRequest->all(),
            $rules,
            $irinnRequest->messages(),
            $irinnRequest->attributes()
        );

        $validator->after(function (\Illuminate\Validation\Validator $v) use ($request, $application): void {
            if (! $request->filled('irinn_ipv4_resource_size') && ! $request->filled('irinn_ipv6_resource_size')) {
                $v->errors()->add('irinn_ipv4_resource_size', 'Please select at least one IPv4 or IPv6 resource (Step 5).');
            }

            $requireFileOrPath = function (string $input, string $pathColumn) use ($request, $application, $v): void {
                if (! $request->hasFile($input) && empty($application->getAttribute($pathColumn))) {
                    $v->errors()->add($input, 'Please upload this document or keep your previously submitted file.');
                }
            };

            $requireFileOrPath('irinn_signature_proof', 'irinn_signature_proof_path');
            $requireFileOrPath('irinn_board_resolution', 'irinn_board_resolution_path');
            $requireFileOrPath('irinn_kyc_network_diagram', 'irinn_kyc_network_diagram_path');
            $requireFileOrPath('irinn_kyc_equipment_invoice', 'irinn_kyc_equipment_invoice_path');
            $requireFileOrPath('irinn_kyc_bandwidth_proof', 'irinn_kyc_bandwidth_proof_path');
            $requireFileOrPath('irinn_kyc_irinn_agreement', 'irinn_kyc_irinn_agreement_path');

            $companyType = (string) $request->input('irinn_company_type', '');
            if (in_array($companyType, ['government', 'ngo', 'academia_institute', 'trust'], true)) {
                $requireFileOrPath('irinn_registration_document', 'irinn_registration_document_path');
            }

            if (! $request->has('irinn_has_gst_number')) {
                $requireFileOrPath('irinn_ca_declaration_file', 'irinn_ca_declaration_path');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $baseDir = 'irinn-applications/'.$userId.'/'.date('Y/m');

        $store = function (string $key) use ($request, $baseDir): ?string {
            if (! $request->hasFile($key)) {
                return null;
            }

            $file = $request->file($key);

            return $file?->store($baseDir, 'public');
        };

        $mergePath = function (?string $newPath, ?string $existingPath): ?string {
            return $newPath ?: $existingPath;
        };

        $paths = [
            'irinn_registration_document_path' => $mergePath($store('irinn_registration_document'), $application->irinn_registration_document_path),
            'irinn_ca_declaration_path' => $mergePath($store('irinn_ca_declaration_file'), $application->irinn_ca_declaration_path),
            'irinn_signature_proof_path' => $mergePath($store('irinn_signature_proof'), $application->irinn_signature_proof_path),
            'irinn_board_resolution_path' => $mergePath($store('irinn_board_resolution'), $application->irinn_board_resolution_path),
            'irinn_kyc_network_diagram_path' => $mergePath($store('irinn_kyc_network_diagram'), $application->irinn_kyc_network_diagram_path),
            'irinn_kyc_equipment_invoice_path' => $mergePath($store('irinn_kyc_equipment_invoice'), $application->irinn_kyc_equipment_invoice_path),
            'irinn_kyc_bandwidth_proof_path' => $mergePath($store('irinn_kyc_bandwidth_proof'), $application->irinn_kyc_bandwidth_proof_path),
            'irinn_kyc_irinn_agreement_path' => $mergePath($store('irinn_kyc_irinn_agreement'), $application->irinn_kyc_irinn_agreement_path),
        ];

        $labels = $request->input('kyc_other_document_label', []);
        $otherFiles = $request->file('kyc_other_document_file', []);
        if ($otherFiles instanceof \Illuminate\Http\UploadedFile) {
            $otherFiles = [$otherFiles];
        }
        if (! is_array($labels)) {
            $labels = [];
        }
        if (! is_array($otherFiles)) {
            $otherFiles = [];
        }

        for ($i = 1; $i <= 5; $i++) {
            $idx = $i - 1;
            $labelKey = "irinn_other_doc_{$i}_label";
            $pathKey = "irinn_other_doc_{$i}_path";
            $incomingLabel = $labels[$idx] ?? null;
            if (is_string($incomingLabel) && trim($incomingLabel) !== '') {
                $paths[$labelKey] = trim($incomingLabel);
            } else {
                $paths[$labelKey] = $application->getAttribute($labelKey);
            }
            $file = $otherFiles[$idx] ?? null;
            $paths[$pathKey] = ($file && $file->isValid())
                ? $file->store($baseDir.'/other', 'public')
                : $application->getAttribute($pathKey);
        }

        $gstVerificationId = $application->gst_verification_id;
        if ($request->filled('gst_verification_request_id')) {
            $gstVerificationId = GstVerification::query()
                ->where('user_id', $userId)
                ->where('request_id', $request->input('gst_verification_request_id'))
                ->value('id') ?? $gstVerificationId;
        }

        $mcaVerificationId = $application->mca_verification_id;
        if ($request->filled('mca_verification_request_id')) {
            $mcaVerificationId = McaVerification::query()
                ->where('user_id', $userId)
                ->where('request_id', $request->input('mca_verification_request_id'))
                ->value('id') ?? $mcaVerificationId;
        }

        $feeRaw = $request->input('irinn_resource_fee_amount', $request->input('resource_fee_amount'));

        $applicationData = $application->application_data ?? [];
        $restoreStage = isset($applicationData['irinn_previous_stage']) && is_string($applicationData['irinn_previous_stage'])
            ? trim($applicationData['irinn_previous_stage'])
            : null;
        $allowedRestoreStages = ['helpdesk', 'submitted', 'pending', 'hostmaster'];
        $targetStatus = in_array($restoreStage, $allowedRestoreStages, true) ? $restoreStage : 'helpdesk';

        unset(
            $applicationData['irinn_resubmission_reason'],
            $applicationData['irinn_resubmission_requested_at'],
            $applicationData['irinn_resubmission_requested_by'],
            $applicationData['irinn_previous_stage'],
        );

        $application->update(array_merge([
            'status' => $targetStatus,
            'submitted_at' => now(),
            'application_data' => empty($applicationData) ? null : $applicationData,
            'gst_verification_id' => $gstVerificationId,
            'mca_verification_id' => $mcaVerificationId,
            'irinn_form_version' => $request->input('irinn_form_version', 'create_new_v1'),
            'irinn_current_stage' => $targetStatus,
            'irinn_company_type' => $request->input('irinn_company_type'),
            'irinn_cin_number' => $request->input('irinn_cin_number'),
            'irinn_udyam_number' => $request->input('irinn_udyam_number'),
            'irinn_organisation_name' => $request->input('irinn_organisation_name'),
            'irinn_organisation_address' => $request->input('irinn_organisation_address'),
            'irinn_organisation_postcode' => $request->input('irinn_organisation_postcode'),
            'irinn_industry_type' => $request->input('irinn_industry_type'),
            'irinn_account_name' => $request->input('irinn_account_name'),
            'irinn_has_gst_number' => $request->has('irinn_has_gst_number'),
            'irinn_billing_gstin' => $request->input('irinn_billing_gstin'),
            'irinn_billing_legal_name' => $request->input('irinn_billing_legal_name'),
            'irinn_billing_pan' => $request->input('irinn_billing_pan'),
            'irinn_billing_address' => $request->input('irinn_billing_address'),
            'irinn_billing_postcode' => $request->input('irinn_billing_postcode'),
            'irinn_mr_name' => $request->input('irinn_mr_name'),
            'irinn_mr_designation' => $request->input('irinn_mr_designation'),
            'irinn_mr_email' => $request->input('irinn_mr_email'),
            'irinn_mr_mobile' => $request->input('irinn_mr_mobile'),
            'irinn_mr_din' => $request->input('irinn_mr_din'),
            'irinn_tp_name' => $request->input('irinn_tp_name'),
            'irinn_tp_designation' => $request->input('irinn_tp_designation'),
            'irinn_tp_email' => $request->input('irinn_tp_email'),
            'irinn_tp_mobile' => $request->input('irinn_tp_mobile'),
            'irinn_abuse_name' => $request->input('irinn_abuse_name'),
            'irinn_abuse_designation' => $request->input('irinn_abuse_designation'),
            'irinn_abuse_email' => $request->input('irinn_abuse_email'),
            'irinn_abuse_mobile' => $request->input('irinn_abuse_mobile'),
            'irinn_br_name' => $request->input('irinn_br_name'),
            'irinn_br_designation' => $request->input('irinn_br_designation'),
            'irinn_br_email' => $request->input('irinn_br_email'),
            'irinn_br_mobile' => $request->input('irinn_br_mobile'),
            'irinn_asn_required' => $request->has('irinn_asn_required'),
            'irinn_ipv4_resource_size' => $request->input('irinn_ipv4_resource_size'),
            'irinn_ipv4_resource_addresses' => $request->filled('irinn_ipv4_resource_addresses') ? (int) $request->input('irinn_ipv4_resource_addresses') : null,
            'irinn_ipv6_resource_size' => $request->input('irinn_ipv6_resource_size'),
            'irinn_ipv6_resource_addresses' => $request->filled('irinn_ipv6_resource_addresses') ? (int) $request->input('irinn_ipv6_resource_addresses') : null,
            'irinn_resource_fee_amount' => $feeRaw !== null && $feeRaw !== '' ? (float) $feeRaw : null,
            'irinn_upstream_provider_name' => $request->input('irinn_upstream_provider_name'),
            'irinn_upstream_as_number' => $request->input('irinn_upstream_as_number'),
            'irinn_upstream_mobile' => $request->input('irinn_upstream_mobile'),
            'irinn_upstream_email' => $request->input('irinn_upstream_email'),
            'irinn_sign_name' => $request->input('irinn_sign_name'),
            'irinn_sign_dob' => $request->input('irinn_sign_dob'),
            'irinn_sign_pan' => $request->input('irinn_sign_pan'),
            'irinn_sign_email' => $request->input('irinn_sign_email'),
            'irinn_sign_mobile' => $request->input('irinn_sign_mobile'),
        ], $paths));

        ApplicationStatusHistory::log(
            $application->id,
            'resubmission_requested',
            $targetStatus,
            'user',
            $userId,
            'IRINN application resubmitted by user; returned to '.$targetStatus.' stage'
        );

        return response()->json([
            'success' => true,
            'message' => 'Application updated and resubmitted successfully.',
            'application_id' => $application->application_id,
            'redirect_url' => route('user.applications.show', $application->id),
        ]);
    }
}
