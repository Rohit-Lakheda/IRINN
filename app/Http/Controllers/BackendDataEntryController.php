<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\GstVerification;
use App\Models\IxApplicationPricing;
use App\Models\IxLocation;
use App\Models\IxPortPricing;
use App\Models\PanVerification;
use App\Models\PaymentTransaction;
use App\Models\Registration;
use App\Models\UserKycProfile;
use App\Services\IdfyPanService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PDOException;

class BackendDataEntryController extends Controller
{
    /**
     * Display backend data entry form.
     */
    public function index()
    {
        try {
            $locations = IxLocation::active()
                ->orderBy('node_type')
                ->orderBy('state')
                ->orderBy('name')
                ->get();

            $portPricings = IxPortPricing::active()
                ->orderBy('node_type')
                ->orderBy('display_order')
                ->get()
                ->filter(function ($pricing) {
                    // Only include port capacities that have at least one valid pricing plan
                    return $pricing->hasAnyPricing();
                })
                ->groupBy('node_type');

            $applicationPricing = IxApplicationPricing::getActive();

            return view('admin.backend-data-entry.index', compact('locations', 'portPricings', 'applicationPricing'));
        } catch (Exception $e) {
            Log::error('Error loading backend data entry page: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load data entry page.');
        }
    }

    /**
     * Verify PAN for backend data entry.
     */
    public function verifyPan(Request $request)
    {
        try {
            // No validation - all fields optional
            $panNo = $request->input('pancardno') ? strtoupper(trim(preg_replace('/[^A-Z0-9]/', '', $request->input('pancardno')))) : null;
            $fullName = $request->input('fullname') ? trim(strip_tags($request->input('fullname'))) : null;
            $dateOfBirth = $request->input('dateofbirth');

            // If PAN is not provided, return success (no verification needed)
            if (! $panNo || ! $fullName || ! $dateOfBirth) {
                return response()->json([
                    'success' => true,
                    'message' => 'PAN verification skipped (fields optional).',
                    'data' => [
                        'is_verified' => false,
                        'pan_status' => 'skipped',
                    ],
                ]);
            }

            $idfyService = new IdfyPanService;
            $result = $idfyService->verifyPan($panNo, $fullName, $dateOfBirth);

            if ($result['success']) {
                // Structure session data to match what the store method expects
                $panVerificationData = [
                    'request_id' => $result['request_id'] ?? null,
                    'status' => $result['status'] ?? 'completed',
                    'pan_status' => $result['pan_status'] ?? null,
                    'name_match' => $result['name_match'] ?? false,
                    'dob_match' => $result['dob_match'] ?? false,
                    'is_verified' => true,
                    'source_output' => $result['source_output'] ?? null,
                    'full_result' => $result['full_result'] ?? null,
                ];

                session(['pan_verification_data' => $panVerificationData]);

                return response()->json([
                    'success' => true,
                    'message' => 'PAN verified successfully!',
                    'data' => $panVerificationData,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'PAN verification failed.',
            ], 400);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error verifying PAN in backend data entry: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during PAN verification: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store registration and applications in backend data entry.
     */
    public function store(Request $request)
    {
        try {
            // No validation - allow all fields to be optional
            // Get registration data (all optional)
            $registrationType = $request->input('registration_type', 'entity');
            $panNoInput = $request->input('pancardno');
            // Sanitize and set to null if empty to avoid unique constraint violation
            $panNo = $panNoInput ? strtoupper(trim(preg_replace('/[^A-Z0-9]/', '', $panNoInput))) : null;
            $panNo = ! empty($panNo) ? $panNo : null; // Ensure empty strings become null
            $fullName = $request->input('fullname') ? trim(strip_tags($request->input('fullname'))) : null;
            $email = $request->input('email') ? strtolower(trim($request->input('email'))) : null;
            $mobile = $request->input('mobile') ? preg_replace('/[^0-9]/', '', $request->input('mobile')) : null;
            $dateOfBirth = $request->input('dateofbirth');

            // Get applications array
            $applications = $request->input('applications', []);

            if (empty($applications)) {
                return back()->with('error', 'At least one application is required.')
                    ->withInput();
            }

            // PAN verification is optional for backend entry
            $panVerificationData = session('pan_verification_data');
            $panVerified = $panVerificationData && ($panVerificationData['is_verified'] ?? false);

            // Generate OTPs (for display only, not sent)
            // Use provided OTPs from request if available, otherwise generate new ones
            $emailOtp = $request->input('email_otp') ?: str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $mobileOtp = $request->input('mobile_otp') ?: str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Generate password
            $generatedPassword = $request->input('generated_password') ?: $this->generateRandomPassword();

            // Generate registration ID
            $registrationId = Registration::generateRegistrationId();

            // Note: Inputs are already sanitized above (lines 141-145)
            // No need to re-sanitize here

            DB::beginTransaction();

            try {
                // Create registration (all fields optional)
                $registration = Registration::create([
                    'registrationid' => $registrationId,
                    'pancardno' => $panNo, // Use null instead of empty string to avoid unique constraint violation
                    'registration_type' => $registrationType,
                    'pan_verified' => $panVerified,
                    'fullname' => $fullName ?? '',
                    'email' => $email ?? '',
                    'email_otp' => $emailOtp,
                    'email_verified' => true, // Auto-verified in backend entry
                    'mobile' => $mobile ?? '',
                    'mobile_otp' => $mobileOtp,
                    'mobile_verified' => true, // Auto-verified in backend entry
                    'password' => Hash::make($generatedPassword),
                    'dateofbirth' => $dateOfBirth,
                    'registrationdate' => now('Asia/Kolkata')->toDateString(),
                    'registrationtime' => now('Asia/Kolkata')->toTimeString(),
                    'status' => 'approved',
                ]);

                // Create PAN verification record if PAN was verified
                if ($panVerified && $panNo && $panVerificationData) {
                    PanVerification::create([
                        'user_id' => $registration->id,
                        'pan_number' => $panNo,
                        'request_id' => $panVerificationData['request_id'] ?? null,
                        'status' => 'completed',
                        'is_verified' => true,
                        'verification_data' => $panVerificationData['full_result'] ?? null,
                        'full_name' => $fullName,
                        'date_of_birth' => $dateOfBirth,
                        'pan_status' => $panVerificationData['pan_status'] ?? null,
                        'name_match' => $panVerificationData['name_match'] ?? false,
                        'dob_match' => $panVerificationData['dob_match'] ?? false,
                    ]);
                }

                // Prepare registration details
                $registrationDetails = [
                    'registration_id' => $registrationId,
                    'registration_type' => $registrationType,
                    'pancardno' => $panNo ?? null, // Use null instead of empty string
                    'fullname' => $fullName ?? '',
                    'email' => $email ?? '',
                    'mobile' => $mobile ?? '',
                    'dateofbirth' => $dateOfBirth,
                    'registrationdate' => $registration->registrationdate,
                    'registrationtime' => $registration->registrationtime,
                    'pan_verified' => $panVerified,
                    'email_verified' => true,
                    'mobile_verified' => true,
                    'status' => $registration->status,
                ];

                // Get or create KYC details
                $kycProfile = UserKycProfile::where('user_id', $registration->id)->latest()->first();
                $kycDetails = null;
                if ($kycProfile) {
                    $kycDetails = [
                        'is_msme' => $kycProfile->is_msme,
                        'gstin' => $kycProfile->gstin,
                        'gst_verified' => $kycProfile->gst_verified,
                        'udyam_number' => $kycProfile->udyam_number,
                        'udyam_verified' => $kycProfile->udyam_verified,
                        'cin' => $kycProfile->cin,
                        'mca_verified' => $kycProfile->mca_verified,
                        'contact_name' => $kycProfile->contact_name,
                        'contact_dob' => $kycProfile->contact_dob?->format('Y-m-d'),
                        'contact_pan' => $kycProfile->contact_pan,
                        'contact_email' => $kycProfile->contact_email,
                        'contact_mobile' => $kycProfile->contact_mobile,
                        'contact_name_pan_dob_verified' => $kycProfile->contact_name_pan_dob_verified,
                        'contact_email_verified' => $kycProfile->contact_email_verified,
                        'contact_mobile_verified' => $kycProfile->contact_mobile_verified,
                        'billing_address' => $kycProfile->billing_address,
                        'status' => $kycProfile->status,
                        'completed_at' => $kycProfile->completed_at?->format('Y-m-d H:i:s'),
                    ];
                }

                // Get application pricing
                $applicationPricing = IxApplicationPricing::getActive();
                $applicationFee = $applicationPricing ? (float) $applicationPricing->total_amount : 1000.00;

                // Process each application
                $createdApplications = [];
                $changedById = session('admin_id') ?? session('superadmin_id') ?? null;
                $changedByType = session('admin_id') ? 'admin' : (session('superadmin_id') ? 'superadmin' : 'system');

                foreach ($applications as $appIndex => $appData) {
                    // Get GSTIN for this application
                    $gstin = isset($appData['gstin']) ? strtoupper(preg_replace('/[^A-Z0-9]/', '', $appData['gstin'])) : null;

                    // Handle GST verification - auto-verify for backend entry
                    $gstVerification = null;
                    if ($gstin) {
                        $gstVerification = GstVerification::where('user_id', $registration->id)
                            ->where('gstin', $gstin)
                            ->latest()
                            ->first();

                        if (! $gstVerification) {
                            // Generate a unique request_id for backend entry
                            $backendRequestId = 'BACKEND-'.now()->format('YmdHis').'-'.strtoupper(Str::random(8));

                            // Ensure uniqueness
                            while (GstVerification::where('request_id', $backendRequestId)->exists()) {
                                $backendRequestId = 'BACKEND-'.now()->format('YmdHis').'-'.strtoupper(Str::random(8));
                            }

                            // Create GST verification (auto-verified in backend)
                            $gstVerification = GstVerification::create([
                                'user_id' => $registration->id,
                                'gstin' => $gstin,
                                'request_id' => $backendRequestId,
                                'is_verified' => true,
                                'status' => 'completed',
                                'verification_data' => ['backend_entry' => true],
                            ]);
                        } else {
                            // Update existing to verified
                            $gstVerification->update([
                                'is_verified' => true,
                                'status' => 'completed',
                            ]);
                        }
                    }

                    // Get location and pricing (optional)
                    $location = null;
                    $pricing = null;
                    $payableAmount = 0;

                    if (! empty($appData['location_id'])) {
                        try {
                            $location = IxLocation::active()->find($appData['location_id']);

                            if ($location && ! empty($appData['port_capacity'])) {
                                $pricing = IxPortPricing::active()
                                    ->where('node_type', $location->node_type)
                                    ->where('port_capacity', $appData['port_capacity'])
                                    ->first();

                                if ($pricing && ! empty($appData['billing_plan'])) {
                                    $payableAmount = $pricing->getAmountForPlan($appData['billing_plan']);
                                }
                            }
                        } catch (\Exception $e) {
                            Log::warning('Error fetching location/pricing for application: '.$e->getMessage());
                        }
                    }

                    // Handle file uploads for this application
                    $documentFields = [
                        'agreement_file',
                        'license_isp_file',
                        'license_vno_file',
                        'cdn_declaration_file',
                        'general_declaration_file',
                        'whois_details_file',
                        'pan_document_file',
                        'gstin_document_file',
                        'msme_document_file',
                        'incorporation_document_file',
                        'authorized_rep_document_file',
                    ];

                    $storedDocuments = [];
                    $storagePrefix = 'applications/'.$registration->id.'/ix/'.now()->format('YmdHis').'-'.$appIndex;

                    foreach ($documentFields as $field) {
                        $fileKey = "applications.{$appIndex}.{$field}";
                        if ($request->hasFile($fileKey)) {
                            $file = $request->file($fileKey);
                            $originalName = $file->getClientOriginalName();
                            $extension = $file->getClientOriginalExtension();
                            $fileName = 'IX-'.pathinfo($originalName, PATHINFO_FILENAME).'.'.$extension;
                            $storedDocuments[$field] = $file->storeAs($storagePrefix, $fileName, 'public');
                        }
                    }

                    // Determine member type
                    $memberType = null;
                    if (! empty($appData['member_type'])) {
                        $memberType = $appData['member_type'] === 'others'
                            ? ($appData['member_type_other'] ?? 'Others')
                            : strtoupper($appData['member_type']);
                    }

                    // Prepare application data (all fields optional)
                    $applicationData = [
                        'member_type' => $memberType,
                        'representative' => [
                            'name' => $appData['representative_name'] ?? null,
                            'pan' => ! empty($appData['representative_pan']) ? strtoupper(preg_replace('/[^A-Z0-9]/', '', $appData['representative_pan'])) : null,
                            'dob' => $appData['representative_dob'] ?? null,
                            'email' => ! empty($appData['representative_email']) ? strtolower(trim($appData['representative_email'])) : null,
                            'mobile' => ! empty($appData['representative_mobile']) ? preg_replace('/[^0-9]/', '', $appData['representative_mobile']) : null,
                        ],
                        'gstin' => $gstin,
                        'location' => $location ? [
                            'id' => $location->id,
                            'name' => $location->name,
                            'state' => $location->state,
                            'node_type' => $location->node_type,
                            'switch_details' => $location->switch_details,
                            'nodal_officer' => $location->nodal_officer,
                        ] : null,
                        'port_selection' => [
                            'capacity' => $appData['port_capacity'] ?? null,
                            'billing_plan' => $appData['billing_plan'] ?? null,
                            'amount' => $payableAmount,
                            'currency' => $pricing->currency ?? 'INR',
                        ],
                        'ip_prefix' => [
                            'count' => $appData['ip_prefix_count'] ?? null,
                            'source' => $appData['ip_prefix_source'] ?? null,
                            'provider' => $appData['ip_prefix_provider'] ?? null,
                        ],
                        'peering' => [
                            'pre_nixi_connectivity' => $appData['pre_peering_connectivity'] ?? null,
                            'asn_number' => $appData['asn_number'] ?? null,
                        ],
                        'router_details' => [
                            'height_u' => $appData['router_height_u'] ?? null,
                            'make_model' => $appData['router_make_model'] ?? null,
                            'serial_number' => $appData['router_serial_number'] ?? null,
                        ],
                        'documents' => $storedDocuments,
                    ];

                    if ($gstVerification) {
                        $applicationData['gst_verification_id'] = $gstVerification->id;
                    }

                    // Prepare authorized representative details
                    // For first application: use KYC contact details if available
                    // For subsequent applications: use form's authorized representative details
                    $isFirstApplication = ! Application::where('user_id', $registration->id)
                        ->where('application_type', 'IX')
                        ->exists();

                    if ($isFirstApplication && $kycProfile && $kycProfile->contact_name) {
                        // First application: use KYC contact details
                        $authorizedRepresentativeDetails = [
                            'name' => $kycProfile->contact_name,
                            'pan' => $kycProfile->contact_pan,
                            'dob' => $kycProfile->contact_dob?->format('Y-m-d'),
                            'email' => $kycProfile->contact_email,
                            'mobile' => $kycProfile->contact_mobile,
                        ];
                    } else {
                        // Use form's authorized representative details (all optional)
                        $authorizedRepresentativeDetails = [
                            'name' => $appData['representative_name'] ?? null,
                            'pan' => ! empty($appData['representative_pan']) ? strtoupper(preg_replace('/[^A-Z0-9]/', '', $appData['representative_pan'])) : null,
                            'dob' => $appData['representative_dob'] ?? null,
                            'email' => ! empty($appData['representative_email']) ? strtolower(trim($appData['representative_email'])) : null,
                            'mobile' => ! empty($appData['representative_mobile']) ? preg_replace('/[^0-9]/', '', $appData['representative_mobile']) : null,
                        ];
                    }

                    // Generate transaction ID for backend entry
                    $transactionId = 'BACKEND-'.now()->format('YmdHis').'-'.strtoupper(Str::random(8));

                    $applicationData['payment'] = [
                        'status' => 'pending',
                        'plan' => $appData['billing_plan'] ?? null,
                        'amount' => $applicationFee,
                        'application_fee' => $applicationPricing ? (float) $applicationPricing->application_fee : 1000.00,
                        'gst_percentage' => $applicationPricing ? (float) $applicationPricing->gst_percentage : 18.00,
                        'total_amount' => $applicationFee,
                        'currency' => 'INR',
                        'declaration_confirmed_at' => now('Asia/Kolkata')->toDateTimeString(),
                        'transaction_id' => $transactionId,
                        'payment_mode' => 'backend_entry',
                        'completed_at' => now('Asia/Kolkata')->toDateTimeString(),
                    ];

                    // Create application with submitted status (payment is already completed in backend entry)
                    $application = Application::create([
                        'user_id' => $registration->id,
                        'pan_card_no' => $panNo ?? '',
                        'application_id' => Application::generateApplicationId(),
                        'application_type' => 'IX',
                        'status' => 'submitted', // Directly submitted since payment is completed
                        'application_data' => $applicationData,
                        'registration_details' => $registrationDetails,
                        'kyc_details' => $kycDetails,
                        'authorized_representative_details' => $authorizedRepresentativeDetails,
                        'gst_verification_id' => $gstVerification?->id,
                        'is_active' => true, // Member is active by default
                        'submitted_at' => now('Asia/Kolkata'),
                    ]);

                    // Create payment transaction record
                    PaymentTransaction::create([
                        'user_id' => $registration->id,
                        'application_id' => $application->id,
                        'transaction_id' => $transactionId,
                        'payment_status' => 'success',
                        'payment_mode' => 'test', // TODO: Change to 'backend_entry' after running migration
                        'amount' => $applicationFee,
                        'currency' => 'INR',
                        'product_info' => 'IX Application Fee',
                        'response_message' => 'Payment completed via backend data entry',
                    ]);

                    // Log status change
                    ApplicationStatusHistory::log(
                        $application->id,
                        null,
                        'submitted',
                        $changedByType,
                        $changedById ?? 0,
                        'Application created and submitted via backend data entry - Payment completed'
                    );

                    $createdApplications[] = $application->application_id;
                }

                DB::commit();

                // Clear session
                session()->forget('pan_verification_data');

                // Return success with credentials
                return redirect()->route('admin.backend-data-entry')
                    ->with('success', 'User registered and '.count($createdApplications).' application(s) created successfully!')
                    ->with('credentials', [
                        'registration_id' => $registrationId,
                        'email' => $email ?? '',
                        'password' => $generatedPassword,
                        'email_otp' => $emailOtp,
                        'mobile_otp' => $mobileOtp,
                        'application_ids' => $createdApplications,
                    ]);
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Error in backend data entry transaction: '.$e->getMessage(), [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                throw $e;
            }
        } catch (ValidationException $e) {
            // No validation errors - all fields are optional
            return back()->with('error', 'An error occurred: '.$e->getMessage())->withInput();
        } catch (QueryException $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            $sqlState = $e->errorInfo[0] ?? null;
            $sqlErrorCode = $e->errorInfo[1] ?? null;
            $sqlErrorMessage = $e->errorInfo[2] ?? null;

            Log::error('Database QueryException in backend data entry', [
                'message' => $errorMessage,
                'code' => $errorCode,
                'sql_state' => $sqlState,
                'sql_error_code' => $sqlErrorCode,
                'sql_error_message' => $sqlErrorMessage,
                'sql' => $e->getSql() ?? null,
                'bindings' => $e->getBindings() ?? null,
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Check if it's actually a connection error
            $isConnectionError = str_contains($errorMessage, 'Connection') ||
                                 str_contains($errorMessage, 'No connection could be made') ||
                                 in_array($sqlErrorCode, ['2002', '1045', '2006', 'HY000']) ||
                                 str_contains($sqlState ?? '', 'HY000');

            if ($isConnectionError) {
                return back()->with('error', 'Database connection error. Please try again later.')
                    ->withInput();
            }

            // For other database errors, show a more helpful message
            return back()->with('error', 'Database error occurred. Please check the logs for details or contact support.')
                ->withInput();
        } catch (PDOException $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();

            Log::error('PDO Exception in backend data entry', [
                'message' => $errorMessage,
                'code' => $errorCode,
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $isConnectionError = str_contains($errorMessage, 'Connection') ||
                                 str_contains($errorMessage, 'No connection could be made') ||
                                 str_contains($errorMessage, 'SQLSTATE[HY000]');

            if ($isConnectionError) {
                return back()->with('error', 'Database connection error. Please try again later.')
                    ->withInput();
            }

            return back()->with('error', 'Database error occurred. Please check the logs for details or contact support.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('General Exception in backend data entry', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->with('error', 'An error occurred: '.$e->getMessage().'. Please try again or contact support.')
                ->withInput();
        }
    }

    /**
     * Generate a random password.
     */
    private function generateRandomPassword(): string
    {
        $length = 12;
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*';
        $all = $uppercase.$lowercase.$numbers.$special;

        $password = '';
        $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
        $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        $password .= $numbers[rand(0, strlen($numbers) - 1)];
        $password .= $special[rand(0, strlen($special) - 1)];

        for ($i = 4; $i < $length; $i++) {
            $password .= $all[rand(0, strlen($all) - 1)];
        }

        return str_shuffle($password);
    }
}
