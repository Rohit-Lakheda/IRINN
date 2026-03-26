<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_kyc_profiles', function (Blueprint $table) {
            // Organisation meta
            if (! Schema::hasColumn('user_kyc_profiles', 'organisation_type')) {
                $table->string('organisation_type')->nullable()->after('is_msme');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'organisation_type_other')) {
                $table->string('organisation_type_other')->nullable()->after('organisation_type');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'organisation_license_path')) {
                $table->string('organisation_license_path')->nullable()->after('organisation_type_other');
            }

            // Affiliate meta
            if (! Schema::hasColumn('user_kyc_profiles', 'affiliate_type')) {
                $table->string('affiliate_type')->nullable()->after('organisation_license_path');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'affiliate_verification_mode')) {
                // e.g. cin, gstin, udyam, document
                $table->string('affiliate_verification_mode')->nullable()->after('affiliate_type');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'affiliate_document_path')) {
                $table->string('affiliate_document_path')->nullable()->after('affiliate_verification_mode');
            }

            // Management representative details
            if (! Schema::hasColumn('user_kyc_profiles', 'management_name')) {
                $table->string('management_name')->nullable()->after('billing_address');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'management_dob')) {
                $table->date('management_dob')->nullable()->after('management_name');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'management_pan')) {
                $table->string('management_pan', 10)->nullable()->after('management_dob');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'management_email')) {
                $table->string('management_email')->nullable()->after('management_pan');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'management_mobile')) {
                $table->string('management_mobile', 20)->nullable()->after('management_email');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'management_din')) {
                $table->string('management_din', 50)->nullable()->after('management_mobile');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'management_pan_verified')) {
                $table->boolean('management_pan_verified')->default(false)->after('management_din');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'management_email_verified')) {
                $table->boolean('management_email_verified')->default(false)->after('management_pan_verified');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'management_mobile_verified')) {
                $table->boolean('management_mobile_verified')->default(false)->after('management_email_verified');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'management_din_verified')) {
                // Placeholder for future DIN API integration
                $table->boolean('management_din_verified')->default(false)->after('management_mobile_verified');
            }

            // Authorised representative details
            if (! Schema::hasColumn('user_kyc_profiles', 'authorized_name')) {
                $table->string('authorized_name')->nullable()->after('management_din_verified');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'authorized_dob')) {
                $table->date('authorized_dob')->nullable()->after('authorized_name');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'authorized_pan')) {
                $table->string('authorized_pan', 10)->nullable()->after('authorized_dob');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'authorized_email')) {
                $table->string('authorized_email')->nullable()->after('authorized_pan');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'authorized_mobile')) {
                $table->string('authorized_mobile', 20)->nullable()->after('authorized_email');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'authorized_pan_verified')) {
                $table->boolean('authorized_pan_verified')->default(false)->after('authorized_mobile');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'authorized_email_verified')) {
                $table->boolean('authorized_email_verified')->default(false)->after('authorized_pan_verified');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'authorized_mobile_verified')) {
                $table->boolean('authorized_mobile_verified')->default(false)->after('authorized_email_verified');
            }

            // WHOIS / public contact choice
            if (! Schema::hasColumn('user_kyc_profiles', 'whois_source')) {
                // management / authorized
                $table->string('whois_source')->nullable()->after('authorized_mobile_verified');
            }

            // Billing person meta (optional, separate from address text)
            if (! Schema::hasColumn('user_kyc_profiles', 'billing_person_name')) {
                $table->string('billing_person_name')->nullable()->after('whois_source');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'billing_person_email')) {
                $table->string('billing_person_email')->nullable()->after('billing_person_name');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'billing_person_mobile')) {
                $table->string('billing_person_mobile', 20)->nullable()->after('billing_person_email');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'billing_address_type')) {
                // gstin / mca / udyam / other
                $table->string('billing_address_type')->nullable()->after('billing_person_mobile');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_kyc_profiles', function (Blueprint $table) {
            foreach ([
                'organisation_type',
                'organisation_type_other',
                'organisation_license_path',
                'affiliate_type',
                'affiliate_verification_mode',
                'affiliate_document_path',
                'management_name',
                'management_dob',
                'management_pan',
                'management_email',
                'management_mobile',
                'management_din',
                'management_pan_verified',
                'management_email_verified',
                'management_mobile_verified',
                'management_din_verified',
                'authorized_name',
                'authorized_dob',
                'authorized_pan',
                'authorized_email',
                'authorized_mobile',
                'authorized_pan_verified',
                'authorized_email_verified',
                'authorized_mobile_verified',
                'whois_source',
                'billing_person_name',
                'billing_person_email',
                'billing_person_mobile',
                'billing_address_type',
            ] as $column) {
                if (Schema::hasColumn('user_kyc_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

