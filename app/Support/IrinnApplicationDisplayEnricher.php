<?php

namespace App\Support;

use App\Models\Application;
use App\Models\Registration;
use App\Models\UserKycProfile;
use Exception;
use Illuminate\Support\Facades\Log;

class IrinnApplicationDisplayEnricher
{
    /**
     * Attach registration_details and kyc_details to the application for IRINN display (user or admin).
     */
    public static function enrich(Application $application): void
    {
        if ($application->application_type !== 'IRINN') {
            return;
        }

        $userId = (int) $application->user_id;
        $user = Registration::find($userId);
        if (! $user) {
            return;
        }

        $application->registration_details = [
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

        try {
            $kycProfile = UserKycProfile::where('user_id', $userId)->latest()->first();
            if ($kycProfile) {
                $application->kyc_details = [
                    'organisation_type' => $kycProfile->organisation_type,
                    'organisation_type_other' => $kycProfile->organisation_type_other,
                    'affiliate_type' => $kycProfile->affiliate_type,
                    'affiliate_verification_mode' => $kycProfile->affiliate_verification_mode,
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
                    'management_name' => $kycProfile->management_name,
                    'management_dob' => $kycProfile->management_dob?->format('Y-m-d'),
                    'management_pan' => $kycProfile->management_pan,
                    'management_email' => $kycProfile->management_email,
                    'management_mobile' => $kycProfile->management_mobile,
                    'management_din' => $kycProfile->management_din,
                    'management_pan_verified' => $kycProfile->management_pan_verified,
                    'management_email_verified' => $kycProfile->management_email_verified,
                    'management_mobile_verified' => $kycProfile->management_mobile_verified,
                    'authorized_name' => $kycProfile->authorized_name,
                    'authorized_dob' => $kycProfile->authorized_dob?->format('Y-m-d'),
                    'authorized_pan' => $kycProfile->authorized_pan,
                    'authorized_email' => $kycProfile->authorized_email,
                    'authorized_mobile' => $kycProfile->authorized_mobile,
                    'authorized_pan_verified' => $kycProfile->authorized_pan_verified,
                    'authorized_email_verified' => $kycProfile->authorized_email_verified,
                    'authorized_mobile_verified' => $kycProfile->authorized_mobile_verified,
                    'whois_source' => $kycProfile->whois_source,
                    'billing_person_name' => $kycProfile->billing_person_name,
                    'billing_person_email' => $kycProfile->billing_person_email,
                    'billing_person_mobile' => $kycProfile->billing_person_mobile,
                    'billing_address_type' => $kycProfile->billing_address_type,
                    'billing_address' => $kycProfile->billing_address,
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
            }
        } catch (Exception $e) {
            Log::error('Error loading KYC details for IRINN display enricher: '.$e->getMessage(), [
                'user_id' => $userId,
                'application_id' => $application->id,
            ]);
        }
    }
}
