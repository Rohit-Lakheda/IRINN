<?php

use App\Models\Application;
use App\Models\GstVerification;
use App\Models\Registration;
use App\Models\UserKycProfile;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('irinn:backfill-application-kyc', function () {
    $this->info('Starting IRINN application registration/KYC backfill...');

    $count = 0;

    Application::query()
        ->where('application_type', 'IRINN')
        ->orderBy('id')
        ->chunkById(100, function ($applications) use (&$count) {
            /** @var \App\Models\Application $application */
            foreach ($applications as $application) {
                $registration = Registration::find($application->user_id);
                if (! $registration) {
                    continue;
                }

                $kycProfile = UserKycProfile::where('user_id', $registration->id)->latest()->first();

                $gstVerification = $kycProfile?->gstVerification
                    ?? GstVerification::where('user_id', $registration->id)
                        ->where('is_verified', true)
                        ->latest()
                        ->first();

                $verificationData = null;
                if ($gstVerification && $gstVerification->verification_data) {
                    $verificationData = is_array($gstVerification->verification_data)
                        ? $gstVerification->verification_data
                        : json_decode((string) $gstVerification->verification_data, true);
                }

                $principalAddress = $verificationData['result']['source_output']['principal_place_of_business_fields']['principal_place_of_business_address'] ?? [];
                $gstPincode = $principalAddress['pincode'] ?? null;

                // Build registration snapshot
                $registrationDetails = [
                    'registration_id' => $registration->registrationid,
                    'registration_type' => $registration->registration_type,
                    'fullname' => $registration->fullname,
                    'pancardno' => $registration->pancardno,
                    'email' => $registration->email,
                    'mobile' => $registration->mobile,
                    'dateofbirth' => optional($registration->dateofbirth)->format('Y-m-d'),
                    'registrationdate' => optional($registration->registrationdate)->format('Y-m-d'),
                    'pan_verified' => (bool) $registration->pan_verified,
                    'email_verified' => (bool) $registration->email_verified,
                    'mobile_verified' => (bool) $registration->mobile_verified,
                ];

                // Build KYC snapshot from profile + GST verification
                $kycDetails = $application->kyc_details ?? [];

                if ($kycProfile) {
                    // Normalize existing billing address from profile
                    $billingAddress = $kycProfile->billing_address;
                    if (is_string($billingAddress)) {
                        $decoded = json_decode($billingAddress, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $billingAddress = $decoded;
                        }
                    }
                    if (! is_array($billingAddress)) {
                        $billingAddress = [];
                    }

                    if ($gstPincode && empty($billingAddress['pincode'])) {
                        $billingAddress['pincode'] = $gstPincode;
                    }

                    // Optionally merge basic fields from GST address if billing address is empty
                    if (empty($billingAddress) && $principalAddress) {
                        $billingAddress = [
                            'address' => trim(($principalAddress['door_number'] ?? '').' '.($principalAddress['building_name'] ?? '')),
                            'street' => $principalAddress['street'] ?? ($principalAddress['location'] ?? null),
                            'city' => $principalAddress['dst'] ?? $principalAddress['city'] ?? null,
                            'state' => $principalAddress['state_name'] ?? null,
                            'pincode' => $gstPincode,
                        ];
                    }

                    // Persist updated billing address (with pincode) back to KYC profile
                    $kycProfile->billing_address = $billingAddress;
                    $kycProfile->save();

                    // Take all KYC profile fields (step 1 & 2) into kyc_details snapshot
                    $profileArray = $kycProfile->toArray();
                    unset(
                        $profileArray['id'],
                        $profileArray['user_id'],
                        $profileArray['created_at'],
                        $profileArray['updated_at'],
                        $profileArray['kyc_ip_address'],
                        $profileArray['kyc_user_agent']
                    );

                    // Ensure billing_address in snapshot has enriched structure with pincode
                    $profileArray['billing_address'] = $billingAddress;

                    // Attach GST legal / trade names from verification
                    if ($gstVerification) {
                        $profileArray['legal_name'] = $gstVerification->legal_name ?? ($profileArray['legal_name'] ?? null);
                        $profileArray['trade_name'] = $gstVerification->trade_name ?? ($profileArray['trade_name'] ?? null);
                        $profileArray['gstin'] = $profileArray['gstin'] ?? $gstVerification->gstin;
                    }

                    $kycDetails = array_merge($kycDetails, $profileArray);
                } elseif ($gstVerification) {
                    // Fallback when only GST verification is available
                    $kycDetails = array_merge($kycDetails, [
                        'gstin' => $gstVerification->gstin,
                        'gst_verified' => (bool) $gstVerification->is_verified,
                        'legal_name' => $gstVerification->legal_name,
                        'trade_name' => $gstVerification->trade_name,
                        'constitution_of_business' => $gstVerification->constitution_of_business,
                        'primary_address' => $principalAddress,
                    ]);
                }

                $application->registration_details = $registrationDetails;
                $application->kyc_details = $kycDetails;
                $application->save();

                $count++;
            }
        });

    $this->info("Backfill complete. Updated {$count} IRINN applications.");
})->purpose('Backfill IRINN applications with registration and KYC details (including GST pincode in billing address)');

// Schedule ticket escalation to run every hour
Schedule::command('tickets:escalate')
    ->hourly()
    ->timezone('Asia/Kolkata');

// Schedule overdue invoice email reminders to run daily
Schedule::command('invoices:send-overdue-emails')
    ->daily()
    ->at('10:30')
    ->timezone('Asia/Kolkata');
