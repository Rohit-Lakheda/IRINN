<?php

namespace App\Http\Controllers;

use App\Models\GstVerification;
use App\Models\McaVerification;
use App\Models\Registration;
use App\Models\UdyamVerification;
use App\Models\UserKycProfile;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserKycController extends Controller
{
    /**
     * Show the 2-step KYC form for the logged-in user.
     */
    public function show(Request $request)
    {
        try {
            $userId = (int) $request->session()->get('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            // Load or create a pending KYC profile for this user
            $kyc = UserKycProfile::where('user_id', $userId)->latest()->first();

            if (! $kyc) {
                $kyc = UserKycProfile::create([
                    'user_id' => $userId,
                    'status' => 'pending',
                ]);
            }

            // Do not pre-fill Step 2 fields - user must enter them manually

            return view('user.kyc.index', [
                'user' => $user,
                'kyc' => $kyc,
            ]);
        } catch (Exception $e) {
            Log::error('Error loading KYC form: '.$e->getMessage());

            return redirect()->route('user.dashboard')
                ->with('error', 'Unable to load KYC form. Please try again.');
        }
    }

    /**
     * Store / update KYC details after all verifications are completed.
     */
    public function store(Request $request)
    {
        try {
            $userId = (int) $request->session()->get('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            // Base validation rules
            $rules = [
                // Step 1 - organisation & affiliate meta
                'organisation_type' => 'required|string|max:100',
                'organisation_type_other' => 'nullable|string|max:255',
                'affiliate_type' => 'required|string|max:100',
                'affiliate_verification_mode' => 'nullable|string|in:cin,udyam,document',
                // Step 1 - identifiers and verifications (conditional based on affiliate type)
                'cin' => 'nullable|string|max:50',
                'mca_verification_id' => 'nullable|integer',
                'mca_verified' => 'nullable|boolean',
                'gstin' => 'nullable|string|size:15|regex:/^[0-9A-Z]{15}$/',
                'gst_verification_id' => 'nullable|integer',
                'gst_verified' => 'nullable|boolean',
                'udyam_number' => 'nullable|string|max:100',
                'udyam_verification_id' => 'nullable|integer',
                'udyam_verified' => 'nullable|boolean',
                // Management representative (required)
                'management_name' => 'required|string|max:255',
                'management_dob' => 'required|date|before:today',
                'management_pan' => 'required|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                'management_email' => 'required|email:rfc,dns|max:255',
                'management_mobile' => 'required|string|size:10|regex:/^[0-9]{10}$/',
                'management_din' => 'nullable|string|max:50',
                'management_pan_verified' => 'required|boolean|accepted',
                'management_email_verified' => 'required|boolean|accepted',
                'management_mobile_verified' => 'required|boolean|accepted',
                // Step 2 - authorised representative (contact_*)
                'contact_name' => 'required|string|max:255',
                'contact_dob' => 'required|date|before:today',
                'contact_pan' => 'required|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                'contact_email' => 'required|email:rfc,dns|max:255',
                'contact_mobile' => 'required|string|size:10|regex:/^[0-9]{10}$/',
                'contact_name_pan_dob_verified' => 'required|boolean',
                'contact_email_verified' => 'required|boolean',
                'contact_mobile_verified' => 'required|boolean',
                // Billing details (optional but recommended)
                'billing_address_source' => 'nullable|string|max:50',
                'billing_address' => 'nullable|string',
                // WHOIS / public choice
                'whois_source' => 'nullable|string|in:management,authorized',
                // File uploads (conditional)
                'organisation_license_file' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:10240',
                'affiliate_document_file' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:10240',
            ];

            // Conditionally require organisation license file for ISP/VNO types
            $organisationType = $request->input('organisation_type');
            $isIspOrVno = in_array($organisationType, ['isp_a', 'isp_b', 'isp_c', 'vno_a', 'vno_b', 'vno_c']);
            
            if ($isIspOrVno) {
                // For ISP/VNO, require license file if not already uploaded
                $existingKyc = UserKycProfile::where('user_id', $userId)->first();
                if (! $existingKyc || ! $existingKyc->organisation_license_path) {
                    $rules['organisation_license_file'] = 'required|file|mimes:pdf,jpeg,png,jpg|max:10240';
                }
            }

            $validated = $request->validate($rules);

            $affiliateMode = $validated['affiliate_verification_mode'] ?? null;

            // Ensure CIN is verified only when required by affiliate type/mode
            if ($affiliateMode === 'cin') {
                if (empty($validated['mca_verification_id']) || ! $validated['mca_verified']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please verify CIN before submitting KYC.',
                    ], 422);
                }
            }

            // Ensure UDYAM is verified when required by affiliate type (Sole Proprietorship)
            if ($affiliateMode === 'udyam') {
                if (empty($validated['udyam_verification_id']) || ! $validated['udyam_verified']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please verify UDYAM before submitting KYC.',
                    ], 422);
                }
            }

            // Ensure document is uploaded when required by affiliate type
            if ($affiliateMode === 'document') {
                $hasNewDocument = $request->hasFile('affiliate_document_file');
                // Check existing KYC profile for document
                $existingKyc = UserKycProfile::where('user_id', $userId)->latest()->first();
                $hasExistingDocument = $existingKyc && ! empty($existingKyc->affiliate_document_path);
                if (! $hasNewDocument && ! $hasExistingDocument) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please upload the required affiliate document before submitting KYC.',
                    ], 422);
                }
            }

            // GSTIN is optional, but if provided, it must be verified
            if (! empty($validated['gstin'])) {
                if (empty($validated['gst_verification_id']) || ! $validated['gst_verified']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please verify GSTIN before submitting KYC, or leave it blank if you do not have a GSTIN.',
                    ], 422);
                }
            }

            // Ensure CIN verification record is valid and belongs to user
            $mcaVerification = null;
            if ($affiliateMode === 'cin' && ! empty($validated['mca_verification_id'])) {
                $mcaVerification = McaVerification::where('id', $validated['mca_verification_id'])
                    ->where('user_id', $userId)
                    ->where('is_verified', true)
                    ->first();

                if (! $mcaVerification) {
                    return response()->json([
                        'success' => false,
                        'message' => 'CIN is not verified. Please verify again.',
                    ], 422);
                }
            }

            // Ensure GST verification record is valid and belongs to user (only if GSTIN is provided)
            $gstVerification = null;
            if (! empty($validated['gstin']) && ! empty($validated['gst_verification_id'])) {
                $gstVerification = GstVerification::where('id', $validated['gst_verification_id'])
                    ->where('user_id', $userId)
                    ->where('is_verified', true)
                    ->first();

                if (! $gstVerification) {
                    return response()->json([
                        'success' => false,
                        'message' => 'GSTIN is not verified. Please verify again, or leave GSTIN blank if you do not have one.',
                    ], 422);
                }
            }

            $udyamVerification = null;
            if ($affiliateMode === 'udyam' && ! empty($validated['udyam_verification_id'])) {
                $udyamVerification = UdyamVerification::where('id', $validated['udyam_verification_id'])
                    ->where('user_id', $userId)
                    ->where('is_verified', true)
                    ->first();

                if (! $udyamVerification) {
                    return response()->json([
                        'success' => false,
                        'message' => 'UDYAM is not verified. Please verify again.',
                    ], 422);
                }
            }

            // Ensure company names from different documents (GST / UDYAM / CIN) match
            $companyNames = [];

            if ($gstVerification) {
                $name = $gstVerification->legal_name ?? $gstVerification->trade_name;
                if ($name) {
                    $companyNames[] = $name;
                }
            }

            if ($udyamVerification && $udyamVerification->verification_data) {
                $udyamData = is_array($udyamVerification->verification_data)
                    ? $udyamVerification->verification_data
                    : json_decode((string) $udyamVerification->verification_data, true);
                $udyamName = $udyamData['result']['source_output']['general_details']['enterprise_name'] ?? null;
                if ($udyamName) {
                    $companyNames[] = $udyamName;
                }
            }

            if ($mcaVerification && $mcaVerification->verification_data) {
                $mcaData = is_array($mcaVerification->verification_data)
                    ? $mcaVerification->verification_data
                    : json_decode((string) $mcaVerification->verification_data, true);
                $mcaName = $mcaData['result']['source_output']['company_name'] ?? null;
                if ($mcaName) {
                    $companyNames[] = $mcaName;
                }
            }

            // Normalize company names and allow 70% or more match (no exact match required)
            $normalized = [];
            foreach ($companyNames as $name) {
                $normalized[] = trim(mb_strtolower($name));
            }

            $minSimilarityPercent = 70;
            for ($i = 0; $i < count($normalized); $i++) {
                for ($j = $i + 1; $j < count($normalized); $j++) {
                    similar_text($normalized[$i], $normalized[$j], $percent);
                    if ($percent < $minSimilarityPercent) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Company name does not match across verified documents (GST / UDYAM / CIN). At least '.$minSimilarityPercent.'% match is required (current match: '.round($percent, 1).'%). Please verify correct details.',
                        ], 422);
                    }
                }
            }

            // Handle optional file uploads
            $organisationLicensePath = null;
            $affiliateDocumentPath = null;

            if ($request->hasFile('organisation_license_file')) {
                $organisationLicensePath = $request->file('organisation_license_file')
                    ->store('user-kyc/'.$userId, 'public');
            }

            if ($request->hasFile('affiliate_document_file')) {
                $affiliateDocumentPath = $request->file('affiliate_document_file')
                    ->store('user-kyc/'.$userId, 'public');
            }

            // Load or create KYC profile
            $kyc = UserKycProfile::firstOrNew([
                'user_id' => $userId,
            ]);

            // Derive billing address and ensure GST pincode is included
            $billingAddress = $validated['billing_address'] ?? $kyc->billing_address;

            if (is_string($billingAddress)) {
                $decoded = json_decode($billingAddress, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $billingAddress = $decoded;
                }
            }

            if (! is_array($billingAddress)) {
                $billingAddress = [];
            }

            // Try to get pincode from GST verification response
            $gstPincode = null;
            if ($gstVerification && $gstVerification->verification_data) {
                $verificationData = is_array($gstVerification->verification_data)
                    ? $gstVerification->verification_data
                    : json_decode((string) $gstVerification->verification_data, true);

                if (is_array($verificationData)) {
                    $principalAddress = $verificationData['result']['source_output']['principal_place_of_business_fields']['principal_place_of_business_address'] ?? [];
                    $gstPincode = $principalAddress['pincode'] ?? null;
                }
            }

            if ($gstPincode && empty($billingAddress['pincode'])) {
                $billingAddress['pincode'] = $gstPincode;
            }

            $kyc->fill([
                // Organisation & affiliate
                'organisation_type' => $validated['organisation_type'],
                'organisation_type_other' => $validated['organisation_type_other'] ?? null,
                'organisation_license_path' => $organisationLicensePath ?? $kyc->organisation_license_path,
                'affiliate_type' => $validated['affiliate_type'],
                'affiliate_verification_mode' => $affiliateMode,
                'affiliate_document_path' => $affiliateDocumentPath ?? $kyc->affiliate_document_path,
                'gstin' => $validated['gstin'] ?? null,
                'gst_verification_id' => $gstVerification?->id,
                'gst_verified' => (bool) ($validated['gst_verified'] ?? false),
                'udyam_number' => $validated['udyam_number'] ?? null,
                'udyam_verification_id' => $udyamVerification?->id,
                'udyam_verified' => (bool) ($validated['udyam_verified'] ?? false),
                'cin' => $validated['cin'] ?? null,
                'mca_verification_id' => $mcaVerification?->id,
                'mca_verified' => (bool) ($validated['mca_verified'] ?? false),
                // Management representative
                'management_name' => $validated['management_name'] ?? null,
                'management_dob' => $validated['management_dob'] ?? null,
                'management_pan' => isset($validated['management_pan'])
                    ? strtoupper($validated['management_pan'])
                    : null,
                'management_email' => $validated['management_email'] ?? null,
                'management_mobile' => $validated['management_mobile'] ?? null,
                'management_din' => $validated['management_din'] ?? null,
                'management_pan_verified' => (bool) ($validated['management_pan_verified'] ?? false),
                'management_email_verified' => (bool) ($validated['management_email_verified'] ?? false),
                'management_mobile_verified' => (bool) ($validated['management_mobile_verified'] ?? false),
                // Authorised representative (contact_* mirrored into authorized_* fields)
                'contact_name' => $validated['contact_name'],
                'contact_dob' => $validated['contact_dob'],
                'contact_pan' => strtoupper($validated['contact_pan']),
                'contact_email' => $validated['contact_email'],
                'contact_mobile' => $validated['contact_mobile'],
                'contact_name_pan_dob_verified' => (bool) $validated['contact_name_pan_dob_verified'],
                'contact_email_verified' => (bool) $validated['contact_email_verified'],
                'contact_mobile_verified' => (bool) $validated['contact_mobile_verified'],
                'authorized_name' => $validated['contact_name'],
                'authorized_dob' => $validated['contact_dob'],
                'authorized_pan' => strtoupper($validated['contact_pan']),
                'authorized_email' => $validated['contact_email'],
                'authorized_mobile' => $validated['contact_mobile'],
                'authorized_pan_verified' => (bool) $validated['contact_name_pan_dob_verified'],
                'authorized_email_verified' => (bool) $validated['contact_email_verified'],
                'authorized_mobile_verified' => (bool) $validated['contact_mobile_verified'],
                'billing_address_source' => $validated['billing_address_source'] ?? null,
                'billing_address' => $billingAddress,
                'whois_source' => $validated['whois_source'] ?? $kyc->whois_source,
                'status' => 'completed',
                'completed_at' => now('Asia/Kolkata'),
                'kyc_ip_address' => $request->ip(),
                'kyc_user_agent' => (string) $request->userAgent(),
            ]);

            $kyc->save();

            return response()->json([
                'success' => true,
                'message' => 'KYC details submitted successfully.',
            ]);
        } catch (ValidationException $e) {
            Log::error('KYC validation error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Validation failed. Please check your inputs.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error saving KYC details: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to save KYC details. Please try again.',
            ], 500);
        }
    }
}
