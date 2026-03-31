<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\AdminAction;
use App\Models\Application;
use App\Models\ApplicationGstChangeHistory;
use App\Models\ApplicationGstUpdateRequest;
use App\Models\GstVerification;
use App\Models\Message;
use App\Models\Registration;
use App\Models\SuperAdmin;
use App\Models\UserKycProfile;
use App\Services\IdfyVerificationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display the user dashboard.
     */
    public function dashboard()
    {
        try {
            $userId = session('user_id');
            $user = Registration::with('messages')->find($userId);

            if (! $user) {
                session()->forget(['user_id', 'user_email', 'user_name', 'user_registration_id']);

                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            $unreadCount = $user->unreadMessagesCount();

            // Get only live applications (is_active = true)
            $liveApplications = Application::with(['statusHistory'])
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->whereNotNull('service_activation_date')
                ->latest('service_activation_date')
                ->get();

            // Get invoice statistics
            $invoiceCount = \App\Models\Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->count();

            $pendingInvoices = \App\Models\Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->activeForTotals()
                ->where(function ($q) {
                    $q->where('status', 'pending')
                        ->orWhere('payment_status', 'partial');
                })
                ->count();

            $paidInvoices = \App\Models\Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->where('status', 'paid')->count();

            // Calculate outstanding amount (sum of balance amounts for pending and partial invoices)
            $outstandingInvoices = \App\Models\Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->activeForTotals()
                ->where(function ($q) {
                    $q->where('status', 'pending')
                        ->orWhere('payment_status', 'partial');
                })
                ->get();

            $outstandingAmount = $outstandingInvoices->sum(function ($invoice) {
                return (float) ($invoice->balance_amount ?? $invoice->total_amount ?? 0);
            });

            // Get pending invoices with applications for payment list
            $pendingInvoicesList = \App\Models\Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->activeForTotals()
                ->where(function ($q) {
                    $q->where('status', 'pending')
                        ->orWhere('payment_status', 'partial');
                })
                ->with(['application'])
                ->latest('due_date')
                ->get();

            // Get latest GST verification from gst_verifications table
            $gstVerification = GstVerification::where('user_id', $userId)
                ->where('is_verified', true)
                ->latest('updated_at')
                ->first();

            // Get GSTIN and verification status from GST verification
            $gstin = $gstVerification ? $gstVerification->gstin : ($user->gstin ?? null);
            $gstVerified = $gstVerification ? $gstVerification->is_verified : ($user->gst_verified ?? false);

            // Get wallet information
            $wallet = $user->wallet;
            $walletBalance = $wallet ? (float) $wallet->balance : 0;

            return response()->view('user.dashboard', compact('user', 'unreadCount', 'liveApplications', 'invoiceCount', 'pendingInvoices', 'paidInvoices', 'outstandingAmount', 'pendingInvoicesList', 'gstin', 'gstVerified', 'wallet', 'walletBalance'))
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error loading user dashboard: '.$e->getMessage());
            abort(500, 'Unable to load dashboard. Please try again later.');
        }
    }

    /**
     * Display user profile.
     */
    public function profile()
    {
        try {
            $userId = session('user_id');
            $user = Registration::with('profileUpdateRequests')->find($userId);

            if (! $user) {
                session()->forget(['user_id', 'user_email', 'user_name', 'user_registration_id']);

                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            $pendingRequest = $user->pendingProfileUpdateRequest();

            // Get approved request that hasn't been submitted
            $approvedRequest = $user->profileUpdateRequests()
                ->where('status', 'approved')
                ->whereNull('submitted_at')
                ->latest()
                ->first();

            // Get submitted request waiting for approval
            $submittedRequest = $user->profileUpdateRequests()
                ->where('status', 'approved')
                ->whereNotNull('submitted_at')
                ->where('update_approved', false)
                ->latest()
                ->first();

            // Get approved update (if any) - user can apply for new update after this
            $updateApprovedRequest = $user->profileUpdateRequests()
                ->where('status', 'approved')
                ->where('update_approved', true)
                ->latest()
                ->first();

            // Get latest rejected request - user can apply for new update after this
            $rejectedRequest = $user->profileUpdateRequests()
                ->where('status', 'rejected')
                ->latest()
                ->first();

            // Get latest GST verification from gst_verifications table
            $gstVerification = GstVerification::where('user_id', $userId)
                ->where('is_verified', true)
                ->latest('updated_at')
                ->first();

            // Get GSTIN and verification status from GST verification
            $gstin = $gstVerification ? $gstVerification->gstin : ($user->gstin ?? null);
            $gstVerified = $gstVerification ? $gstVerification->is_verified : ($user->gst_verified ?? false);

            $irinnApplication = Application::query()
                ->where('user_id', $userId)
                ->where('application_type', 'IRINN')
                ->latest('id')
                ->first();

            return response()->view('user.profile', compact('user', 'pendingRequest', 'approvedRequest', 'submittedRequest', 'updateApprovedRequest', 'rejectedRequest', 'gstin', 'gstVerified', 'gstVerification', 'irinnApplication'))
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error loading user profile: '.$e->getMessage());
            abort(500, 'Unable to load profile. Please try again later.');
        }
    }

    /**
     * Show the profile edit form.
     */
    public function edit()
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                session()->forget(['user_id', 'user_email', 'user_name', 'user_registration_id']);

                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            $irinnApplication = Application::query()
                ->where('user_id', $userId)
                ->where('application_type', 'IRINN')
                ->latest('id')
                ->first();

            return response()->view('user.profile.edit', compact('user', 'irinnApplication'))
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error loading profile edit form: '.$e->getMessage());

            return redirect()->route('user.profile')
                ->with('error', 'Unable to load edit form. Please try again.');
        }
    }

    /**
     * Update registered email and mobile only (GST is maintained on your IRINN application record).
     */
    public function update(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            $validated = $request->validate([
                'email' => ['required', 'email', 'max:255', Rule::unique('registrations', 'email')->ignore($user->id)],
                'mobile' => ['required', 'string', 'max:15', Rule::unique('registrations', 'mobile')->ignore($user->id)],
            ], [
                'email.unique' => 'This email address is already registered to another account.',
                'mobile.unique' => 'This mobile number is already registered to another account.',
            ]);

            $newEmail = strtolower(trim($validated['email']));
            $newMobile = preg_replace('/\s+/', '', trim($validated['mobile']));

            if ($user->email !== $newEmail) {
                $user->email = $newEmail;
                $user->email_verified = false;
            }

            if ($user->mobile !== $newMobile) {
                $user->mobile = $newMobile;
                $user->mobile_verified = false;
            }

            $user->save();

            session(['user_email' => $user->email]);

            return redirect()->route('user.profile')
                ->with('success', 'Your registered email and mobile number have been saved. If you changed either value, please complete verification when prompted during login or notifications.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Error updating profile: '.$e->getMessage());

            return redirect()->route('user.profile')
                ->with('error', 'An error occurred while updating your profile. Please try again.');
        }
    }

    /**
     * Verify GST for user profile (AJAX endpoint).
     */
    public function verifyGst(Request $request): JsonResponse
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
                'gstin' => 'required|string|size:15',
            ]);

            $gstin = strtoupper(trim($request->input('gstin')));

            // Check if verification already exists for this GSTIN and user
            $existingVerification = GstVerification::where('user_id', $userId)
                ->where('gstin', $gstin)
                ->where('is_verified', true)
                ->latest('updated_at')
                ->first();

            if ($existingVerification) {
                return response()->json([
                    'success' => true,
                    'verification_id' => $existingVerification->id,
                    'request_id' => $existingVerification->request_id,
                    'message' => 'GSTIN already verified.',
                ]);
            }

            // Initiate verification
            $service = new IdfyVerificationService;
            $result = $service->verifyGst($gstin);

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to initiate GST verification',
                ], 400);
            }

            // Create or update verification record
            $verification = GstVerification::updateOrCreate(
                [
                    'user_id' => $userId,
                    'gstin' => $gstin,
                    'status' => 'in_progress',
                ],
                [
                    'request_id' => $result['request_id'],
                    'verification_data' => $result['data'] ?? null,
                ]
            );

            Log::info('GST verification initiated for user', [
                'user_id' => $userId,
                'gstin' => $gstin,
                'verification_id' => $verification->id,
                'request_id' => $result['request_id'],
            ]);

            return response()->json([
                'success' => true,
                'verification_id' => $verification->id,
                'request_id' => $result['request_id'],
                'message' => 'GST verification initiated successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', $e->errors()['gstin'] ?? ['Invalid GSTIN']),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error initiating GST verification: '.$e->getMessage(), [
                'user_id' => session('user_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while initiating GST verification. Please try again.',
            ], 500);
        }
    }

    /**
     * Check GST verification status (AJAX endpoint).
     */
    public function checkGstStatus(Request $request): JsonResponse
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
                'verification_id' => 'required|integer|exists:gst_verifications,id',
            ]);

            $verification = GstVerification::findOrFail($request->input('verification_id'));

            // Verify this verification belongs to the user
            if ($verification->user_id !== $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification does not belong to you.',
                ], 403);
            }

            // If already verified, return immediately
            if ($verification->is_verified && $verification->status === 'completed') {
                return response()->json([
                    'success' => true,
                    'status' => 'completed',
                    'is_verified' => true,
                    'message' => 'GSTIN verified successfully. Please complete the KYC process.',
                    'verification_data' => [
                        'gstin' => $verification->gstin,
                        'legal_name' => $verification->legal_name,
                        'trade_name' => $verification->trade_name,
                        'state' => $verification->state,
                        'pincode' => $verification->pincode,
                        'primary_address' => $verification->primary_address,
                        'registration_date' => $verification->registration_date?->format('Y-m-d'),
                        'gst_type' => $verification->gst_type,
                        'company_status' => $verification->company_status,
                        'constitution_of_business' => $verification->constitution_of_business,
                    ],
                ]);
            }

            // Check status with Idfy API
            $service = new IdfyVerificationService;
            $statusResult = $service->getTaskStatus($verification->request_id);

            if ($statusResult['status'] === 'completed') {
                $result = $statusResult['result'];
                $sourceOutput = $result['source_output'] ?? null;

                if ($sourceOutput) {
                    $isVerified = ($sourceOutput['status'] ?? '') === 'id_found';

                    if ($isVerified) {
                        // Extract and update verification data
                        $updateData = [
                            'status' => 'completed',
                            'is_verified' => true,
                            'verification_data' => $result,
                            'legal_name' => $sourceOutput['legal_name'] ?? null,
                            'trade_name' => $sourceOutput['trade_name'] ?? null,
                        ];

                        // Extract PAN from GSTIN
                        $gstinFromResponse = $sourceOutput['gstin'] ?? $verification->gstin;
                        if ($gstinFromResponse && strlen($gstinFromResponse) >= 10) {
                            $updateData['pan'] = substr($gstinFromResponse, 2, 10);
                        }

                        // Extract state and address
                        $address = $sourceOutput['principal_place_of_business_fields']['principal_place_of_business_address'] ?? null;
                        if ($address) {
                            $updateData['state'] = $address['state_name'] ?? null;
                            $addressParts = array_filter([
                                $address['door_number'] ?? null,
                                $address['building_name'] ?? null,
                                $address['street'] ?? null,
                                $address['location'] ?? null,
                                $address['city'] ?? null,
                                $address['dst'] ?? null,
                            ]);
                            $primaryAddress = implode(', ', $addressParts);

                            // Add pincode if available
                            if (isset($address['pincode']) && $address['pincode']) {
                                $primaryAddress .= ', '.$address['pincode'];
                            }

                            $updateData['primary_address'] = $primaryAddress;
                            $updateData['pincode'] = $address['pincode'] ?? null;
                        }

                        if (isset($sourceOutput['date_of_registration'])) {
                            $updateData['registration_date'] = date('Y-m-d', strtotime($sourceOutput['date_of_registration']));
                        }

                        $updateData['gst_type'] = $sourceOutput['taxpayer_type'] ?? null;
                        $updateData['company_status'] = $sourceOutput['gstin_status'] ?? null;
                        $updateData['constitution_of_business'] = $sourceOutput['constitution_of_business'] ?? null;

                        $verification->update($updateData);

                        // Notify admins about GST verification
                        $this->notifyAdminsOfGstVerification($user, $verification);

                        // Note: We don't update applications or UserKycProfile here
                        // Updates will happen only when user clicks "Update Profile" button

                        Log::info('GST verification completed for user (pending update)', [
                            'user_id' => $userId,
                            'gstin' => $verification->gstin,
                            'verification_id' => $verification->id,
                        ]);

                        return response()->json([
                            'success' => true,
                            'status' => 'completed',
                            'is_verified' => true,
                            'message' => 'GSTIN verified successfully. Please complete the KYC process.',
                            'verification_data' => [
                                'gstin' => $verification->gstin,
                                'legal_name' => $verification->legal_name,
                                'trade_name' => $verification->trade_name,
                                'state' => $verification->state,
                                'pincode' => $verification->pincode,
                                'primary_address' => $verification->primary_address,
                                'registration_date' => $verification->registration_date?->format('Y-m-d'),
                                'gst_type' => $verification->gst_type,
                                'company_status' => $verification->company_status,
                                'constitution_of_business' => $verification->constitution_of_business,
                            ],
                        ]);
                    } else {
                        $errorMessage = $sourceOutput['message'] ?? 'GSTIN verification failed';

                        $verification->update([
                            'status' => 'completed',
                            'is_verified' => false,
                            'verification_data' => $result,
                            'error_message' => $errorMessage,
                        ]);

                        return response()->json([
                            'success' => false,
                            'status' => 'completed',
                            'is_verified' => false,
                            'message' => $errorMessage,
                        ]);
                    }
                }
            } elseif ($statusResult['status'] === 'failed') {
                $verification->update([
                    'status' => 'failed',
                    'is_verified' => false,
                    'error_message' => 'GST verification request failed',
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'failed',
                    'is_verified' => false,
                    'message' => 'GST verification request failed',
                ]);
            }

            return response()->json([
                'success' => true,
                'status' => $statusResult['status'] ?? 'in_progress',
                'is_verified' => false,
                'message' => 'Verification in progress. Please wait...',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', $e->errors()['verification_id'] ?? ['Invalid verification ID']),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error checking GST verification status: '.$e->getMessage(), [
                'user_id' => session('user_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking verification status. Please try again.',
            ], 500);
        }
    }

    /**
     * Complete KYC for user profile (AJAX endpoint).
     */
    public function completeKyc(Request $request): JsonResponse
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

            // Get latest verified GST verification
            $gstVerification = GstVerification::where('user_id', $userId)
                ->where('is_verified', true)
                ->latest('updated_at')
                ->first();

            if (! $gstVerification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please verify your GSTIN before completing KYC.',
                ], 400);
            }

            // Get or create UserKycProfile
            $userKycProfile = UserKycProfile::where('user_id', $userId)
                ->where('status', 'completed')
                ->latest()
                ->first();

            if (! $userKycProfile) {
                $userKycProfile = new UserKycProfile;
                $userKycProfile->user_id = $userId;
                $userKycProfile->status = 'completed';
            }

            // Update KYC details with GST verification data
            $kycDetails = $userKycProfile->kyc_details ?? [];

            // GST verification details
            $kycDetails['gstin'] = $gstVerification->gstin;
            $kycDetails['gst_verified'] = true;
            $kycDetails['gst_verification_id'] = $gstVerification->id;
            $kycDetails['gst_legal_name'] = $gstVerification->legal_name;
            $kycDetails['gst_trade_name'] = $gstVerification->trade_name;
            $kycDetails['gst_pan'] = $gstVerification->pan;
            $kycDetails['gst_state'] = $gstVerification->state;
            $kycDetails['gst_registration_date'] = $gstVerification->registration_date?->format('Y-m-d');
            $kycDetails['gst_type'] = $gstVerification->gst_type;
            $kycDetails['gst_company_status'] = $gstVerification->company_status;
            $kycDetails['gst_verified_at'] = now('Asia/Kolkata')->toDateTimeString();
            $kycDetails['gst_primary_address'] = $gstVerification->primary_address;
            $kycDetails['gst_constitution_of_business'] = $gstVerification->constitution_of_business;
            $kycDetails['gst_verification_data'] = $gstVerification->verification_data;

            // User information
            $kycDetails['user_name'] = $user->fullname;
            $kycDetails['user_email'] = $user->email;
            $kycDetails['user_mobile'] = $user->mobile;

            // Billing address - prioritize GST primary address
            $billingAddress = $gstVerification->primary_address ?? null;
            if (! $billingAddress && $userKycProfile->billing_address) {
                $billingAddressRaw = $userKycProfile->billing_address;

                // Parse JSON if billing_address is a JSON string
                if (is_string($billingAddressRaw) && (str_starts_with($billingAddressRaw, '{') || str_starts_with($billingAddressRaw, '['))) {
                    $decoded = json_decode($billingAddressRaw, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $billingAddress = $decoded['address'] ?? $decoded['label'] ?? $billingAddressRaw;
                    } else {
                        $billingAddress = $billingAddressRaw;
                    }
                } else {
                    $billingAddress = $billingAddressRaw;
                }
            }

            if ($billingAddress) {
                // Extract pincode from address if not already present
                $pincode = null;
                if (preg_match('/\b(\d{6})\b/', $billingAddress, $matches)) {
                    $pincode = $matches[1];
                }

                // If pincode not found in address, try to get from GST verification data
                if (! $pincode && $gstVerification->verification_data) {
                    $verificationData = is_string($gstVerification->verification_data)
                        ? json_decode($gstVerification->verification_data, true)
                        : (is_array($gstVerification->verification_data) ? $gstVerification->verification_data : []);

                    $sourceOutput = $verificationData['source_output'] ?? null;
                    if ($sourceOutput) {
                        $address = $sourceOutput['principal_place_of_business_fields']['principal_place_of_business_address'] ?? null;
                        if ($address && isset($address['pincode'])) {
                            $pincode = $address['pincode'];
                        }
                    }
                }

                // Append pincode if not already in address
                if ($pincode && ! preg_match('/\b'.$pincode.'\b/', $billingAddress)) {
                    $billingAddress .= ', '.$pincode;
                }

                $kycDetails['billing_address'] = $billingAddress;
                $kycDetails['billing_pincode'] = $pincode;
            }

            // Don't save to database yet - just prepare the data
            // The actual save will happen when user clicks "Update Profile" button
            // Store the prepared data in session temporarily
            session([
                'pending_kyc_completion' => true,
                'pending_kyc_gst_verification_id' => $gstVerification->id,
                'pending_kyc_details' => $kycDetails,
            ]);

            Log::info('KYC completion prepared for user (pending update)', [
                'user_id' => $userId,
                'gst_verification_id' => $gstVerification->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'KYC completed successfully.',
            ]);
        } catch (Exception $e) {
            Log::error('Error completing KYC: '.$e->getMessage(), [
                'user_id' => session('user_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while completing KYC. Please try again.',
            ], 500);
        }
    }

    /**
     * Complete KYC when profile is updated.
     */
    private function completeKycOnProfileUpdate(Registration $user, GstVerification $gstVerification): void
    {
        try {
            // Get or create UserKycProfile
            $userKycProfile = UserKycProfile::where('user_id', $user->id)
                ->where('status', 'completed')
                ->latest()
                ->first();

            if (! $userKycProfile) {
                $userKycProfile = new UserKycProfile;
                $userKycProfile->user_id = $user->id;
                $userKycProfile->status = 'completed';
            }

            // Get pending KYC details from session if available, otherwise prepare them
            $kycDetails = session('pending_kyc_details', []);

            // If no pending details, prepare them now
            if (empty($kycDetails)) {
                // GST verification details
                $kycDetails['gstin'] = $gstVerification->gstin;
                $kycDetails['gst_verified'] = true;
                $kycDetails['gst_verification_id'] = $gstVerification->id;
                $kycDetails['gst_legal_name'] = $gstVerification->legal_name;
                $kycDetails['gst_trade_name'] = $gstVerification->trade_name;
                $kycDetails['gst_pan'] = $gstVerification->pan;
                $kycDetails['gst_state'] = $gstVerification->state;
                $kycDetails['gst_registration_date'] = $gstVerification->registration_date
                    ? (is_string($gstVerification->registration_date)
                        ? $gstVerification->registration_date
                        : \Carbon\Carbon::parse($gstVerification->registration_date)->format('Y-m-d'))
                    : null;
                $kycDetails['gst_type'] = $gstVerification->gst_type;
                $kycDetails['gst_company_status'] = $gstVerification->company_status;
                $kycDetails['gst_verified_at'] = now('Asia/Kolkata')->toDateTimeString();
                $kycDetails['gst_primary_address'] = $gstVerification->primary_address;
                $kycDetails['gst_constitution_of_business'] = $gstVerification->constitution_of_business;

                // User information
                $kycDetails['user_name'] = $user->fullname;
                $kycDetails['user_email'] = $user->email;
                $kycDetails['user_mobile'] = $user->mobile;

                // Billing address
                $billingAddress = $gstVerification->primary_address ?? null;
                if ($billingAddress) {
                    $pincode = null;
                    if (preg_match('/\b(\d{6})\b/', $billingAddress, $matches)) {
                        $pincode = $matches[1];
                    }

                    if (! $pincode && $gstVerification->verification_data) {
                        $verificationData = is_string($gstVerification->verification_data)
                            ? json_decode($gstVerification->verification_data, true)
                            : (is_array($gstVerification->verification_data) ? $gstVerification->verification_data : []);

                        $sourceOutput = $verificationData['source_output'] ?? null;
                        if ($sourceOutput) {
                            $address = $sourceOutput['principal_place_of_business_fields']['principal_place_of_business_address'] ?? null;
                            if ($address && isset($address['pincode'])) {
                                $pincode = $address['pincode'];
                            }
                        }
                    }

                    if ($pincode && ! preg_match('/\b'.$pincode.'\b/', $billingAddress)) {
                        $billingAddress .= ', '.$pincode;
                    }

                    $kycDetails['billing_address'] = $billingAddress;
                    $kycDetails['billing_pincode'] = $pincode;
                }
            }

            // Update individual fields
            $userKycProfile->gstin = $gstVerification->gstin;
            $userKycProfile->gst_verified = true;
            $userKycProfile->gst_verification_id = $gstVerification->id;
            $userKycProfile->status = 'completed';
            $userKycProfile->completed_at = now('Asia/Kolkata');

            // Update contact details
            if (isset($kycDetails['user_name'])) {
                $userKycProfile->contact_name = $kycDetails['user_name'] ?? $userKycProfile->contact_name;
            }
            if (isset($kycDetails['user_email'])) {
                $userKycProfile->contact_email = $kycDetails['user_email'] ?? $userKycProfile->contact_email;
            }
            if (isset($kycDetails['user_mobile'])) {
                $userKycProfile->contact_mobile = $kycDetails['user_mobile'] ?? $userKycProfile->contact_mobile;
            }
            if (isset($kycDetails['billing_address'])) {
                $userKycProfile->billing_address = $kycDetails['billing_address'];
            }

            $userKycProfile->save();

            // Update GSTIN in all user's applications' application_data
            $this->updateGstinInApplications($user, $gstVerification->gstin);

            // Update kyc_details in all user's applications
            $this->updateKycDetailsInApplications($user, $gstVerification, $kycDetails);

            // Notify admins about KYC completion
            $this->notifyAdminsOfKycCompletion($user, $gstVerification);

            Log::info('KYC completed during profile update', [
                'user_id' => $user->id,
                'gst_verification_id' => $gstVerification->id,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to complete KYC during profile update: '.$e->getMessage(), [
                'user_id' => $user->id,
                'gst_verification_id' => $gstVerification->id,
            ]);
        }
    }

    /**
     * Update kyc_details in all user's applications.
     */
    private function updateKycDetailsInApplications(Registration $user, GstVerification $gstVerification, array $kycDetails): void
    {
        try {
            // Get all applications for this user
            $applications = Application::where('user_id', $user->id)->get();

            foreach ($applications as $application) {
                // Prepare kyc_details for application
                $appKycDetails = $application->kyc_details ?? [];

                // Update GST verification details
                $appKycDetails['gstin'] = $gstVerification->gstin;
                $appKycDetails['gst_verified'] = true;
                $appKycDetails['gst_verification_id'] = $gstVerification->id;
                $appKycDetails['gst_legal_name'] = $gstVerification->legal_name;
                $appKycDetails['gst_trade_name'] = $gstVerification->trade_name;
                $appKycDetails['gst_pan'] = $gstVerification->pan;
                $appKycDetails['gst_state'] = $gstVerification->state;
                $appKycDetails['gst_registration_date'] = $gstVerification->registration_date
                    ? (is_string($gstVerification->registration_date)
                        ? $gstVerification->registration_date
                        : \Carbon\Carbon::parse($gstVerification->registration_date)->format('Y-m-d'))
                    : null;
                $appKycDetails['gst_type'] = $gstVerification->gst_type;
                $appKycDetails['gst_company_status'] = $gstVerification->company_status;
                $appKycDetails['gst_verified_at'] = now('Asia/Kolkata')->toDateTimeString();
                $appKycDetails['gst_primary_address'] = $gstVerification->primary_address;
                $appKycDetails['gst_constitution_of_business'] = $gstVerification->constitution_of_business;

                // Update user information if available in kycDetails
                if (isset($kycDetails['user_name'])) {
                    $appKycDetails['user_name'] = $kycDetails['user_name'];
                }
                if (isset($kycDetails['user_email'])) {
                    $appKycDetails['user_email'] = $kycDetails['user_email'];
                }
                if (isset($kycDetails['user_mobile'])) {
                    $appKycDetails['user_mobile'] = $kycDetails['user_mobile'];
                }

                // Update billing address if available
                if (isset($kycDetails['billing_address'])) {
                    $appKycDetails['billing_address'] = $kycDetails['billing_address'];
                }
                if (isset($kycDetails['billing_pincode'])) {
                    $appKycDetails['billing_pincode'] = $kycDetails['billing_pincode'];
                }

                // Update status and completion metadata
                $appKycDetails['status'] = 'completed';
                $appKycDetails['completed_at'] = now('Asia/Kolkata')->toDateTimeString();
                $appKycDetails['completed_by'] = 'user';

                // Save updated kyc_details
                $application->update([
                    'kyc_details' => $appKycDetails,
                    'gst_verification_id' => $gstVerification->id,
                ]);
            }

            Log::info('KYC details updated in applications', [
                'user_id' => $user->id,
                'applications_count' => $applications->count(),
                'gst_verification_id' => $gstVerification->id,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to update kyc_details in applications: '.$e->getMessage(), [
                'user_id' => $user->id,
                'gst_verification_id' => $gstVerification->id,
            ]);
        }
    }

    /**
     * Update GSTIN in all user's applications' application_data.
     */
    private function updateGstinInApplications(Registration $user, string $newGstin): void
    {
        try {
            // Get all applications for this user
            $applications = Application::where('user_id', $user->id)->get();

            foreach ($applications as $application) {
                $applicationData = $application->application_data ?? [];

                // Update GSTIN in application_data
                if (is_array($applicationData)) {
                    $applicationData['gstin'] = $newGstin;
                } else {
                    // If application_data is stored as JSON string, decode it
                    $decoded = json_decode($applicationData, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $decoded['gstin'] = $newGstin;
                        $applicationData = $decoded;
                    } else {
                        // If it's not valid JSON, create a new array
                        $applicationData = ['gstin' => $newGstin];
                    }
                }

                // Save updated application_data
                $application->update([
                    'application_data' => $applicationData,
                ]);
            }

            Log::info('GSTIN updated in applications', [
                'user_id' => $user->id,
                'applications_count' => $applications->count(),
                'new_gstin' => $newGstin,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to update GSTIN in applications: '.$e->getMessage(), [
                'user_id' => $user->id,
                'new_gstin' => $newGstin,
            ]);
        }
    }

    /**
     * Log GSTIN change to custom log file.
     */
    private function logGstinChange(Registration $user, ?string $oldGstin, string $newGstin): void
    {
        try {
            $logPath = storage_path('logs/UserSideGSTINChange.log');
            $timestamp = now('Asia/Kolkata')->format('Y-m-d H:i:s');
            $logEntry = sprintf(
                "[%s] User ID: %d | User Name: %s | Registration ID: %s | Email: %s | Old GSTIN: %s | New GSTIN: %s | IP: %s | User Agent: %s\n",
                $timestamp,
                $user->id,
                $user->fullname,
                $user->registrationid,
                $user->email,
                $oldGstin ?? 'NULL',
                $newGstin,
                request()->ip(),
                request()->userAgent() ?? 'Unknown'
            );

            // Append to log file
            File::append($logPath, $logEntry);

            Log::info('User GSTIN changed', [
                'user_id' => $user->id,
                'user_name' => $user->fullname,
                'old_gstin' => $oldGstin,
                'new_gstin' => $newGstin,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log GSTIN change to custom log file: '.$e->getMessage());
        }
    }

    /**
     * Notify admin and superadmin about GSTIN change.
     */
    private function notifyAdminsOfGstinChange(Registration $user, ?string $oldGstin, string $newGstin): void
    {
        try {
            $subject = 'User GSTIN Number Changed';
            $messageText = sprintf(
                "User '%s' (Registration ID: %s, Email: %s) has changed their GSTIN number.\n\nOld GSTIN: %s\nNew GSTIN: %s\n\nPlease review the updated GSTIN verification status.",
                $user->fullname,
                $user->registrationid,
                $user->email,
                $oldGstin ?? 'Not previously set',
                $newGstin
            );

            // Create a message visible to all admins (linked to the user)
            try {
                $message = Message::create([
                    'user_id' => $user->id,
                    'subject' => $subject,
                    'message' => $messageText,
                    'is_read' => false,
                    'admin_read' => false,
                    'sent_by' => 'system',
                ]);

                Log::info('GSTIN change message created for admins', [
                    'user_id' => $user->id,
                    'message_id' => $message->id,
                ]);
            } catch (Exception $e) {
                Log::error('Failed to create GSTIN change message: '.$e->getMessage(), [
                    'user_id' => $user->id,
                ]);
            }

            // Get all active admins
            $admins = Admin::where('is_active', true)->get();

            // Create AdminAction entries for each admin (for notification tracking)
            foreach ($admins as $admin) {
                try {
                    AdminAction::create([
                        'admin_id' => $admin->id,
                        'superadmin_id' => null,
                        'action_type' => 'user_gstin_changed',
                        'actionable_type' => Registration::class,
                        'actionable_id' => $user->id,
                        'description' => "User '{$user->fullname}' changed GSTIN from '{$oldGstin}' to '{$newGstin}'",
                        'metadata' => [
                            'user_id' => $user->id,
                            'user_name' => $user->fullname,
                            'registration_id' => $user->registrationid,
                            'user_email' => $user->email,
                            'old_gstin' => $oldGstin,
                            'new_gstin' => $newGstin,
                            'changed_at' => now('Asia/Kolkata')->toDateTimeString(),
                        ],
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]);
                } catch (Exception $e) {
                    Log::error('Failed to create admin action for GSTIN change notification: '.$e->getMessage(), [
                        'admin_id' => $admin->id,
                        'user_id' => $user->id,
                    ]);
                }
            }

            // Get all active superadmins
            $superAdmins = SuperAdmin::where('is_active', true)->get();

            // Create AdminAction entries for each superadmin
            foreach ($superAdmins as $superAdmin) {
                try {
                    AdminAction::create([
                        'admin_id' => null,
                        'superadmin_id' => $superAdmin->id,
                        'action_type' => 'user_gstin_changed',
                        'actionable_type' => Registration::class,
                        'actionable_id' => $user->id,
                        'description' => "User '{$user->fullname}' changed GSTIN from '{$oldGstin}' to '{$newGstin}'",
                        'metadata' => [
                            'user_id' => $user->id,
                            'user_name' => $user->fullname,
                            'registration_id' => $user->registrationid,
                            'user_email' => $user->email,
                            'old_gstin' => $oldGstin,
                            'new_gstin' => $newGstin,
                            'changed_at' => now('Asia/Kolkata')->toDateTimeString(),
                        ],
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]);
                } catch (Exception $e) {
                    Log::error('Failed to create superadmin action for GSTIN change notification: '.$e->getMessage(), [
                        'superadmin_id' => $superAdmin->id,
                        'user_id' => $user->id,
                    ]);
                }
            }

            Log::info('GSTIN change notifications sent to admins and superadmins', [
                'user_id' => $user->id,
                'admins_count' => $admins->count(),
                'superadmins_count' => $superAdmins->count(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send GSTIN change notifications: '.$e->getMessage(), [
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Notify admin and superadmin about GST verification.
     */
    private function notifyAdminsOfGstVerification(Registration $user, GstVerification $gstVerification): void
    {
        try {
            $subject = 'User GSTIN Verified';
            $messageText = sprintf(
                "User '%s' (Registration ID: %s, Email: %s) has successfully verified their GSTIN.\n\nGSTIN: %s\nLegal Name: %s\nTrade Name: %s\nState: %s\nRegistration Date: %s\n\nPlease review the verification details.",
                $user->fullname,
                $user->registrationid,
                $user->email,
                $gstVerification->gstin,
                $gstVerification->legal_name ?? 'N/A',
                $gstVerification->trade_name ?? 'N/A',
                $gstVerification->state ?? 'N/A',
                $gstVerification->registration_date ? $gstVerification->registration_date->format('d M Y') : 'N/A'
            );

            // Create a message visible to all admins
            try {
                $message = Message::create([
                    'user_id' => $user->id,
                    'subject' => $subject,
                    'message' => $messageText,
                    'is_read' => false,
                    'admin_read' => false,
                    'sent_by' => 'system',
                ]);

                Log::info('GST verification message created for admins', [
                    'user_id' => $user->id,
                    'gst_verification_id' => $gstVerification->id,
                    'message_id' => $message->id,
                ]);
            } catch (Exception $e) {
                Log::error('Failed to create GST verification message: '.$e->getMessage(), [
                    'user_id' => $user->id,
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to send GST verification notifications: '.$e->getMessage(), [
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Notify admin and superadmin about KYC completion.
     */
    private function notifyAdminsOfKycCompletion(Registration $user, GstVerification $gstVerification): void
    {
        try {
            $subject = 'User KYC Completed';
            $messageText = sprintf(
                "User '%s' (Registration ID: %s, Email: %s) has completed their KYC process.\n\nGSTIN: %s\nLegal Name: %s\nTrade Name: %s\nState: %s\n\nKYC has been completed successfully. Please review the KYC details.",
                $user->fullname,
                $user->registrationid,
                $user->email,
                $gstVerification->gstin,
                $gstVerification->legal_name ?? 'N/A',
                $gstVerification->trade_name ?? 'N/A',
                $gstVerification->state ?? 'N/A'
            );

            // Create a message visible to all admins
            try {
                $message = Message::create([
                    'user_id' => $user->id,
                    'subject' => $subject,
                    'message' => $messageText,
                    'is_read' => false,
                    'admin_read' => false,
                    'sent_by' => 'system',
                ]);

                Log::info('KYC completion message created for admins', [
                    'user_id' => $user->id,
                    'gst_verification_id' => $gstVerification->id,
                    'message_id' => $message->id,
                ]);
            } catch (Exception $e) {
                Log::error('Failed to create KYC completion message: '.$e->getMessage(), [
                    'user_id' => $user->id,
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to send KYC completion notifications: '.$e->getMessage(), [
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Show the application-specific GST edit form.
     */
    public function editApplicationGst(Application $application)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                session()->forget(['user_id', 'user_email', 'user_name', 'user_registration_id']);

                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            // Verify the application belongs to the user
            if ($application->user_id !== (int) $userId) {
                return redirect()->route('user.applications.index')
                    ->with('error', 'You do not have permission to access this application.');
            }

            // Get GSTIN from application table columns (no user KYC profile dependency).
            $gstin = null;
            $gstVerified = false;

            if ($application->application_type === 'IRINN') {
                $gstin = $application->irinn_billing_gstin ?? null;
            } else {
                $kycDetails = is_array($application->kyc_details) ? $application->kyc_details : [];
                if (isset($kycDetails['gstin'])) {
                    $gstin = $kycDetails['gstin'];
                    $gstVerified = $kycDetails['gst_verified'] ?? false;
                } else {
                    $applicationData = is_array($application->application_data) ? $application->application_data : [];
                    $gstin = $applicationData['gstin'] ?? null;
                }
            }

            // Get GST verification only for linking (not for displaying data)
            $gstVerification = null;
            if ($application->gst_verification_id) {
                $gstVerification = GstVerification::find($application->gst_verification_id);
                $gstVerified = $gstVerification ? (bool) $gstVerification->is_verified : $gstVerified;
            }

            return response()->view('user.applications.gst.edit', compact('user', 'application', 'gstin', 'gstVerified', 'gstVerification'))
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error loading application GST edit form: '.$e->getMessage());

            return redirect()->route('user.applications.index')
                ->with('error', 'Unable to load GST edit form. Please try again.');
        }
    }

    /**
     * Update application-specific GST (only for the specified application).
     */
    public function updateApplicationGst(Request $request, Application $application)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            // Verify the application belongs to the user
            if ($application->user_id !== (int) $userId) {
                return redirect()->route('user.applications.index')
                    ->with('error', 'You do not have permission to update this application.');
            }

            $validated = $request->validate([
                'gstin' => 'nullable|string|max:15',
                'gst_verification_id' => 'nullable|integer|exists:gst_verifications,id',
                'gst_verified' => 'nullable|boolean',
            ]);

            // Only update GSTIN if provided
            if (isset($validated['gstin'])) {
                if ($application->application_type === 'IRINN') {
                    $newGstin = strtoupper(trim((string) $validated['gstin']));
                    $oldGstin = strtoupper(trim((string) ($application->irinn_billing_gstin ?? '')));
                    $oldCompanyName = $application->irinn_billing_legal_name ?? null;

                    $gstVerificationId = $validated['gst_verification_id'] ?? null;
                    if (! $gstVerificationId) {
                        return back()
                            ->withErrors(['gstin' => 'Please verify GST before updating.'])
                            ->withInput();
                    }

                    $gstVerification = GstVerification::find($gstVerificationId);
                    if (! $gstVerification || (int) $gstVerification->user_id !== (int) $userId) {
                        return back()
                            ->withErrors(['gstin' => 'Invalid or unauthorized GST verification.'])
                            ->withInput();
                    }

                    // We only update application columns after verification is completed.
                    if (! $gstVerification->is_verified) {
                        return back()
                            ->withErrors(['gstin' => 'GST verification is still in progress. Please try again.'])
                            ->withInput();
                    }

                    $newCompanyName = (string) ($gstVerification->legal_name ?? $gstVerification->trade_name ?? '');
                    if ($newCompanyName === '') {
                        return back()
                            ->withErrors(['gstin' => 'GST verification did not return company name. Please try again.'])
                            ->withInput();
                    }

                    // Old/new details stored for audit (mirrors GST change history structure).
                    $oldKycDetails = [
                        'gstin' => $oldGstin !== '' ? $oldGstin : null,
                        'legal_name' => $oldCompanyName,
                        'trade_name' => null,
                        'billing_address' => $application->irinn_billing_address ?? null,
                        'billing_pincode' => $application->irinn_billing_postcode ?? null,
                    ];

                    $newKycDetails = [
                        'gstin' => $gstVerification->gstin,
                        'legal_name' => $gstVerification->legal_name ?? null,
                        'trade_name' => $gstVerification->trade_name ?? null,
                        'billing_address' => $gstVerification->primary_address ?? null,
                        'billing_pincode' => $gstVerification->pincode ?? null,
                    ];

                    if ($oldGstin !== $newGstin) {
                        // Compute similarity to decide between direct update vs admin approval.
                        $similarityScore = null;
                        $requiresApproval = false;

                        if ($oldCompanyName && $newCompanyName) {
                            $similarityScore = $this->calculateStringSimilarity(
                                strtolower(trim($oldCompanyName)),
                                strtolower(trim($newCompanyName))
                            );

                            if ($similarityScore < 70) {
                                $requiresApproval = true;
                            }
                        } elseif ($newCompanyName && ! $oldCompanyName) {
                            $requiresApproval = false;
                        }

                        if ($requiresApproval) {
                            ApplicationGstUpdateRequest::create([
                                'application_id' => $application->id,
                                'user_id' => $userId,
                                'old_gstin' => $oldGstin,
                                'new_gstin' => $newGstin,
                                'old_company_name' => $oldCompanyName,
                                'new_company_name' => $newCompanyName,
                                'similarity_score' => $similarityScore,
                                'old_kyc_details' => $oldKycDetails,
                                'new_kyc_details' => $newKycDetails,
                                'gst_verification_id' => $gstVerification->id,
                                'status' => 'pending',
                            ]);

                            \App\Models\ApplicationStatusHistory::log(
                                $application->id,
                                (string) ($application->status ?? ''),
                                (string) ($application->status ?? ''),
                                'user',
                                $userId,
                                "GST update requested: {$oldGstin} -> {$newGstin}"
                            );

                            return redirect()->route('user.applications.show', $application->id)
                                ->with('info', 'GST update request submitted for admin approval. Company name similarity is '.number_format((float) ($similarityScore ?? 0), 2).'% (requires 70% or more for automatic approval).');
                        }

                        // Direct update: write verified GST details into IRINN columns.
                        $application->gst_verification_id = $gstVerification->id;
                        $application->irinn_has_gst_number = true;
                        $application->irinn_billing_gstin = $gstVerification->gstin;
                        $application->irinn_billing_legal_name = $gstVerification->legal_name ?? null;
                        $application->irinn_billing_pan = $gstVerification->pan ?? null;
                        $application->irinn_billing_address = $gstVerification->primary_address ?? null;
                        $application->irinn_billing_postcode = $gstVerification->pincode ?? null;
                        $application->save();

                        ApplicationGstChangeHistory::log(
                            $application->id,
                            $userId,
                            $oldGstin,
                            $newGstin,
                            $oldKycDetails,
                            $newKycDetails,
                            'user',
                            $userId,
                            'GST updated by user'
                        );

                        // Log GSTIN change for this application (file log + notifications)
                        $this->logApplicationGstinChange($user, $application, $oldGstin !== '' ? $oldGstin : null, $newGstin);
                        $this->notifyAdminsOfApplicationGstinChange($user, $application, $oldGstin !== '' ? $oldGstin : null, $newGstin);

                        \App\Models\ApplicationStatusHistory::log(
                            $application->id,
                            (string) ($application->status ?? ''),
                            (string) ($application->status ?? ''),
                            'user',
                            $userId,
                            "GST updated: {$oldGstin} -> {$newGstin}"
                        );
                    } else {
                        // GSTIN same: still refresh billing details from verified GST.
                        $application->gst_verification_id = $gstVerification->id;
                        $application->irinn_has_gst_number = true;
                        $application->irinn_billing_legal_name = $gstVerification->legal_name ?? null;
                        $application->irinn_billing_pan = $gstVerification->pan ?? null;
                        $application->irinn_billing_address = $gstVerification->primary_address ?? null;
                        $application->irinn_billing_postcode = $gstVerification->pincode ?? null;
                        $application->save();

                        \App\Models\ApplicationStatusHistory::log(
                            $application->id,
                            (string) ($application->status ?? ''),
                            (string) ($application->status ?? ''),
                            'user',
                            $userId,
                            "GST re-verified (same GSTIN): {$newGstin}"
                        );
                    }

                    return redirect()->route('user.applications.show', $application->id)
                        ->with('success', 'GST details updated successfully for this application.');
                }

                // Get old GSTIN from application's kyc_details, application_data, or GST verification
                $oldGstin = null;
                $kycDetails = is_array($application->kyc_details) ? $application->kyc_details : [];
                $oldGstin = $kycDetails['gstin'] ?? null;

                if (! $oldGstin) {
                    $applicationData = is_array($application->application_data) ? $application->application_data : [];
                    $oldGstin = $applicationData['gstin'] ?? null;
                }

                if (! $oldGstin && $application->gst_verification_id) {
                    $oldGstVerification = GstVerification::find($application->gst_verification_id);
                    $oldGstin = $oldGstVerification ? $oldGstVerification->gstin : null;
                }

                $newGstin = strtoupper(trim($validated['gstin']));

                // Check if GSTIN actually changed
                if ($oldGstin !== $newGstin) {
                    // Get old kyc_details before updating (for history)
                    $oldKycDetails = is_array($application->kyc_details) ? $application->kyc_details : [];

                    // Update GSTIN in application_data
                    $applicationData = is_array($application->application_data) ? $application->application_data : [];
                    $applicationData['gstin'] = $newGstin;
                    $application->application_data = $applicationData;

                    // Update GSTIN in kyc_details
                    $kycDetails = is_array($application->kyc_details) ? $application->kyc_details : [];
                    $kycDetails['gstin'] = $newGstin;
                    // Reset GST verification status when GSTIN changes
                    $kycDetails['gst_verified'] = false;

                    // If GST verification ID is provided, check company name similarity
                    if (isset($validated['gst_verification_id'])) {
                        $gstVerification = GstVerification::find($validated['gst_verification_id']);
                        if ($gstVerification && $gstVerification->user_id === $userId) {
                            // Get old company name from kyc_details
                            $oldCompanyName = $oldKycDetails['legal_name'] ?? $oldKycDetails['trade_name'] ?? null;

                            // Get new company name from GST verification
                            $newCompanyName = $gstVerification->legal_name ?? $gstVerification->trade_name ?? null;

                            // Calculate similarity if both names exist
                            $similarityScore = null;
                            $requiresApproval = false;

                            if ($oldCompanyName && $newCompanyName) {
                                $similarityScore = $this->calculateStringSimilarity(
                                    strtolower(trim($oldCompanyName)),
                                    strtolower(trim($newCompanyName))
                                );

                                // If similarity is less than 70%, require admin approval
                                if ($similarityScore < 70) {
                                    $requiresApproval = true;
                                }
                            } elseif ($newCompanyName && ! $oldCompanyName) {
                                // If there's no old company name, allow update (first time)
                                $requiresApproval = false;
                            } else {
                                // If no new company name, allow update (no name change)
                                $requiresApproval = false;
                            }

                            if ($requiresApproval) {
                                // Create GST update request for admin approval
                                $newKycDetails = $kycDetails;

                                // Prepare new kyc_details with GST verification data
                                if ($gstVerification->is_verified) {
                                    $verificationData = $gstVerification->verification_data ?? [];

                                    $newKycDetails['gstin'] = $gstVerification->gstin;
                                    $newKycDetails['gst_verified'] = true;
                                    $newKycDetails['legal_name'] = $gstVerification->legal_name ?? null;
                                    $newKycDetails['trade_name'] = $gstVerification->trade_name ?? null;
                                    $newKycDetails['registration_date'] = $gstVerification->registration_date
                                        ? (is_string($gstVerification->registration_date)
                                            ? $gstVerification->registration_date
                                            : $gstVerification->registration_date->format('Y-m-d'))
                                        : null;
                                    $newKycDetails['constitution_of_business'] = $gstVerification->constitution_of_business ?? null;
                                    $newKycDetails['taxpayer_type'] = $gstVerification->taxpayer_type ?? null;
                                    $newKycDetails['gstin_status'] = $gstVerification->gstin_status ?? null;
                                    $newKycDetails['company_status'] = $gstVerification->company_status ?? null;
                                    // Don't save primary_address - only use it for billing_address
                                    $newKycDetails['state'] = $gstVerification->state ?? null;
                                    $newKycDetails['pincode'] = $gstVerification->pincode ?? null;
                                    $newKycDetails['gst_type'] = $gstVerification->gst_type ?? null;

                                    // Always set billing address from new GST verification primary address
                                    if ($gstVerification->primary_address) {
                                        $newKycDetails['billing_address'] = $gstVerification->primary_address;
                                        $newKycDetails['billing_pincode'] = $gstVerification->pincode;
                                    }

                                    // Remove primary_address from kyc_details if it exists (we only use billing_address)
                                    if (isset($newKycDetails['primary_address'])) {
                                        unset($newKycDetails['primary_address']);
                                    }

                                    if (isset($verificationData['user_name'])) {
                                        $newKycDetails['user_name'] = $verificationData['user_name'];
                                    }
                                    if (isset($verificationData['user_email'])) {
                                        $newKycDetails['user_email'] = $verificationData['user_email'];
                                    }
                                    if (isset($verificationData['user_mobile'])) {
                                        $newKycDetails['user_mobile'] = $verificationData['user_mobile'];
                                    }
                                }

                                ApplicationGstUpdateRequest::create([
                                    'application_id' => $application->id,
                                    'user_id' => $userId,
                                    'old_gstin' => $oldGstin,
                                    'new_gstin' => $newGstin,
                                    'old_company_name' => $oldCompanyName,
                                    'new_company_name' => $newCompanyName,
                                    'similarity_score' => $similarityScore,
                                    'old_kyc_details' => $oldKycDetails,
                                    'new_kyc_details' => $newKycDetails,
                                    'gst_verification_id' => $gstVerification->id,
                                    'status' => 'pending',
                                ]);

                                return redirect()->route('user.applications.show', $application->id)
                                    ->with('info', 'GST update request submitted for admin approval. Company name similarity is '.number_format($similarityScore, 2).'% (requires 70% or more for automatic approval).');
                            }

                            // Similarity >= 70% or no old company name - proceed with direct update
                            // Link GST verification to this application
                            $application->gst_verification_id = $gstVerification->id;

                            // Update all GST fields in kyc_details from GST verification
                            // Replace all old GST data with new GST data
                            if ($gstVerification->is_verified) {
                                $verificationData = $gstVerification->verification_data ?? [];

                                // Replace all GST-related fields in kyc_details with new data
                                $kycDetails['gstin'] = $gstVerification->gstin;
                                $kycDetails['gst_verified'] = true;
                                $kycDetails['legal_name'] = $gstVerification->legal_name ?? null;
                                $kycDetails['trade_name'] = $gstVerification->trade_name ?? null;
                                $kycDetails['registration_date'] = $gstVerification->registration_date
                                    ? (is_string($gstVerification->registration_date)
                                        ? $gstVerification->registration_date
                                        : $gstVerification->registration_date->format('Y-m-d'))
                                    : null;
                                $kycDetails['constitution_of_business'] = $gstVerification->constitution_of_business ?? null;
                                $kycDetails['taxpayer_type'] = $gstVerification->taxpayer_type ?? null;
                                $kycDetails['gstin_status'] = $gstVerification->gstin_status ?? null;
                                $kycDetails['company_status'] = $gstVerification->company_status ?? null;
                                // Don't save primary_address - only use it for billing_address
                                $kycDetails['state'] = $gstVerification->state ?? null;
                                $kycDetails['pincode'] = $gstVerification->pincode ?? null;
                                $kycDetails['gst_type'] = $gstVerification->gst_type ?? null;

                                // Always set billing address from new GST verification primary address
                                if ($gstVerification->primary_address) {
                                    $kycDetails['billing_address'] = $gstVerification->primary_address;
                                    $kycDetails['billing_pincode'] = $gstVerification->pincode;
                                }

                                // Remove primary_address from kyc_details if it exists (we only use billing_address)
                                if (isset($kycDetails['primary_address'])) {
                                    unset($kycDetails['primary_address']);
                                }

                                // Update user information from verification data if available
                                if (isset($verificationData['user_name'])) {
                                    $kycDetails['user_name'] = $verificationData['user_name'];
                                }
                                if (isset($verificationData['user_email'])) {
                                    $kycDetails['user_email'] = $verificationData['user_email'];
                                }
                                if (isset($verificationData['user_mobile'])) {
                                    $kycDetails['user_mobile'] = $verificationData['user_mobile'];
                                }
                            }
                        }
                    }

                    // Save updated kyc_details
                    $application->kyc_details = $kycDetails;
                    $application->save();

                    // Log GST change history in database
                    ApplicationGstChangeHistory::log(
                        $application->id,
                        $userId,
                        $oldGstin,
                        $newGstin,
                        $oldKycDetails,
                        $kycDetails,
                        'user',
                        $userId,
                        'GST updated by user'
                    );

                    // Log GSTIN change for this application (file log)
                    $this->logApplicationGstinChange($user, $application, $oldGstin, $newGstin);

                    // Send notifications to admin and superadmin
                    $this->notifyAdminsOfApplicationGstinChange($user, $application, $oldGstin, $newGstin);
                } else {
                    // GSTIN not changed, just update verification status if provided
                    if (isset($validated['gst_verification_id'])) {
                        $gstVerification = GstVerification::find($validated['gst_verification_id']);
                        if ($gstVerification && $gstVerification->user_id === $userId) {
                            $application->gst_verification_id = $gstVerification->id;

                            // Update GST fields in kyc_details even if GSTIN didn't change
                            $kycDetails = is_array($application->kyc_details) ? $application->kyc_details : [];
                            if ($gstVerification->is_verified) {
                                $verificationData = $gstVerification->verification_data ?? [];
                                $kycDetails['gstin'] = $gstVerification->gstin;
                                $kycDetails['gst_verified'] = true;
                                $kycDetails['legal_name'] = $gstVerification->legal_name ?? null;
                                $kycDetails['trade_name'] = $gstVerification->trade_name ?? null;
                                $kycDetails['registration_date'] = $gstVerification->registration_date
                                    ? (is_string($gstVerification->registration_date)
                                        ? $gstVerification->registration_date
                                        : $gstVerification->registration_date->format('Y-m-d'))
                                    : null;
                                $kycDetails['constitution_of_business'] = $gstVerification->constitution_of_business ?? null;
                                $kycDetails['taxpayer_type'] = $gstVerification->taxpayer_type ?? null;
                                $kycDetails['gstin_status'] = $gstVerification->gstin_status ?? null;
                                $kycDetails['company_status'] = $gstVerification->company_status ?? null;
                                // Don't save primary_address - only use it for billing_address
                                $kycDetails['state'] = $gstVerification->state ?? null;
                                $kycDetails['pincode'] = $gstVerification->pincode ?? null;
                                $kycDetails['gst_type'] = $gstVerification->gst_type ?? null;

                                // Always set billing address from new GST verification primary address
                                if ($gstVerification->primary_address) {
                                    $kycDetails['billing_address'] = $gstVerification->primary_address;
                                    $kycDetails['billing_pincode'] = $gstVerification->pincode;
                                }

                                // Remove primary_address from kyc_details if it exists (we only use billing_address)
                                if (isset($kycDetails['primary_address'])) {
                                    unset($kycDetails['primary_address']);
                                }

                                if (isset($verificationData['user_name'])) {
                                    $kycDetails['user_name'] = $verificationData['user_name'];
                                }
                                if (isset($verificationData['user_email'])) {
                                    $kycDetails['user_email'] = $verificationData['user_email'];
                                }
                                if (isset($verificationData['user_mobile'])) {
                                    $kycDetails['user_mobile'] = $verificationData['user_mobile'];
                                }
                                if (isset($verificationData['billing_address'])) {
                                    $kycDetails['billing_address'] = $verificationData['billing_address'];
                                }
                            }
                            $application->kyc_details = $kycDetails;
                            $application->save();
                        }
                    }
                }
            }

            return redirect()->route('user.applications.index')
                ->with('success', 'GST details updated successfully for this application.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Error updating application GST: '.$e->getMessage());

            return redirect()->route('user.applications.index')
                ->with('error', 'An error occurred while updating GST details. Please try again.');
        }
    }

    /**
     * Verify GST for application (AJAX endpoint).
     */
    public function verifyApplicationGst(Request $request, Application $application): JsonResponse
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

            // Verify the application belongs to the user
            if ($application->user_id !== (int) $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to verify GST for this application.',
                ], 403);
            }

            $request->validate([
                'gstin' => 'required|string|size:15',
            ]);

            $gstin = strtoupper(trim($request->input('gstin')));

            // Check if verification already exists for this GSTIN and user
            $existingVerification = GstVerification::where('user_id', $userId)
                ->where('gstin', $gstin)
                ->where('is_verified', true)
                ->latest('updated_at')
                ->first();

            if ($existingVerification) {
                // Link existing verification to this application
                $application->gst_verification_id = $existingVerification->id;
                $application->save();

                return response()->json([
                    'success' => true,
                    'verification_id' => $existingVerification->id,
                    'request_id' => $existingVerification->request_id,
                    'message' => 'GSTIN already verified and linked to this application.',
                ]);
            }

            // Initiate verification
            $service = new IdfyVerificationService;
            $result = $service->verifyGst($gstin);

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to initiate GST verification',
                ], 400);
            }

            // Create or update verification record
            $verification = GstVerification::updateOrCreate(
                [
                    'user_id' => $userId,
                    'gstin' => $gstin,
                    'status' => 'in_progress',
                ],
                [
                    'request_id' => $result['request_id'],
                    'verification_data' => $result['data'] ?? null,
                ]
            );

            Log::info('GST verification initiated for application', [
                'user_id' => $userId,
                'application_id' => $application->id,
                'gstin' => $gstin,
                'verification_id' => $verification->id,
                'request_id' => $result['request_id'],
            ]);

            return response()->json([
                'success' => true,
                'verification_id' => $verification->id,
                'request_id' => $result['request_id'],
                'message' => 'GST verification initiated successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', $e->errors()['gstin'] ?? ['Invalid GSTIN']),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error initiating application GST verification: '.$e->getMessage(), [
                'user_id' => session('user_id'),
                'application_id' => $application->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while initiating GST verification. Please try again.',
            ], 500);
        }
    }

    /**
     * Log GSTIN change for application to custom log file.
     */
    private function logApplicationGstinChange(Registration $user, Application $application, ?string $oldGstin, string $newGstin): void
    {
        try {
            $logPath = storage_path('logs/ApplicationGSTINChange.log');
            $timestamp = now('Asia/Kolkata')->format('Y-m-d H:i:s');
            $logEntry = sprintf(
                "[%s] User ID: %d | User Name: %s | Registration ID: %s | Email: %s | Application ID: %s | Membership ID: %s | Old GSTIN: %s | New GSTIN: %s | IP: %s | User Agent: %s\n",
                $timestamp,
                $user->id,
                $user->fullname,
                $user->registrationid,
                $user->email,
                $application->application_id,
                $application->membership_id ?? 'N/A',
                $oldGstin ?? 'NULL',
                $newGstin,
                request()->ip(),
                request()->userAgent() ?? 'Unknown'
            );

            // Append to log file
            File::append($logPath, $logEntry);

            Log::info('Application GSTIN changed', [
                'user_id' => $user->id,
                'application_id' => $application->id,
                'membership_id' => $application->membership_id,
                'old_gstin' => $oldGstin,
                'new_gstin' => $newGstin,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log application GSTIN change to custom log file: '.$e->getMessage());
        }
    }

    /**
     * Notify admin and superadmin about application GSTIN change.
     */
    private function notifyAdminsOfApplicationGstinChange(Registration $user, Application $application, ?string $oldGstin, string $newGstin): void
    {
        try {
            $subject = 'Application GSTIN Number Changed';
            $messageText = sprintf(
                "User '%s' (Registration ID: %s, Email: %s) has changed GSTIN number for Application ID: %s (Membership ID: %s).\n\nOld GSTIN: %s\nNew GSTIN: %s\n\nPlease review the updated GSTIN verification status.",
                $user->fullname,
                $user->registrationid,
                $user->email,
                $application->application_id,
                $application->membership_id ?? 'N/A',
                $oldGstin ?? 'Not previously set',
                $newGstin
            );

            // Create a message visible to all admins (linked to the user)
            try {
                $message = Message::create([
                    'user_id' => $user->id,
                    'subject' => $subject,
                    'message' => $messageText,
                    'is_read' => false,
                    'admin_read' => false,
                    'sent_by' => 'system',
                ]);

                Log::info('Application GSTIN change message created for admins', [
                    'user_id' => $user->id,
                    'application_id' => $application->id,
                    'message_id' => $message->id,
                ]);
            } catch (Exception $e) {
                Log::error('Failed to create application GSTIN change message: '.$e->getMessage(), [
                    'user_id' => $user->id,
                    'application_id' => $application->id,
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to send application GSTIN change notifications: '.$e->getMessage(), [
                'user_id' => $user->id,
                'application_id' => $application->id,
            ]);
        }
    }

    /**
     * Calculate string similarity percentage between two strings.
     * Uses similar_text which returns the number of matching characters.
     * Returns a percentage (0-100).
     */
    private function calculateStringSimilarity(string $str1, string $str2): float
    {
        // Remove common words and normalize
        $str1 = $this->normalizeCompanyName($str1);
        $str2 = $this->normalizeCompanyName($str2);

        // Use similar_text to calculate similarity
        similar_text($str1, $str2, $percent);

        return round($percent, 2);
    }

    /**
     * Normalize company name for better comparison.
     * Removes common words, extra spaces, and converts to lowercase.
     */
    private function normalizeCompanyName(string $name): string
    {
        // Convert to lowercase
        $name = strtolower(trim($name));

        // Remove common words that don't affect company identity
        $commonWords = ['private', 'limited', 'ltd', 'pvt', 'ltd.', 'pvt.', 'inc', 'incorporated', 'llp', 'llc', 'corp', 'corporation'];
        foreach ($commonWords as $word) {
            $name = preg_replace('/\b'.preg_quote($word, '/').'\b/i', '', $name);
        }

        // Remove extra spaces and special characters (keep alphanumeric and spaces)
        $name = preg_replace('/[^a-z0-9\s]/', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }
}
