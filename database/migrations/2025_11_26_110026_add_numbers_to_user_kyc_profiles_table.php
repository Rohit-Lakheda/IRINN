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
            // Add columns only if they do not already exist (to avoid duplicate column errors)
            if (! Schema::hasColumn('user_kyc_profiles', 'user_id')) {
                $table->unsignedBigInteger('user_id')->after('id');
            }

            // Organisation details (step 1)
            if (! Schema::hasColumn('user_kyc_profiles', 'gstin')) {
                $table->string('gstin', 15)->nullable();
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'gst_verification_id')) {
                $table->unsignedBigInteger('gst_verification_id')->nullable();
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'udyam_verification_id')) {
                $table->unsignedBigInteger('udyam_verification_id')->nullable();
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'mca_verification_id')) {
                $table->unsignedBigInteger('mca_verification_id')->nullable();
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'udyam_number')) {
                $table->string('udyam_number')->nullable();
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'cin')) {
                $table->string('cin')->nullable();
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'gst_verified')) {
                $table->boolean('gst_verified')->default(false);
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'udyam_verified')) {
                $table->boolean('udyam_verified')->default(false);
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'mca_verified')) {
                $table->boolean('mca_verified')->default(false);
            }

            // Authorised representative / contact details (step 2)
            if (! Schema::hasColumn('user_kyc_profiles', 'contact_name')) {
                $table->string('contact_name')->nullable();
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'contact_dob')) {
                $table->date('contact_dob')->nullable();
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'contact_pan')) {
                $table->string('contact_pan', 10)->nullable();
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'contact_email')) {
                $table->string('contact_email')->nullable();
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'contact_mobile')) {
                $table->string('contact_mobile', 20)->nullable();
            }

            // Verification flags for contact details
            if (! Schema::hasColumn('user_kyc_profiles', 'contact_name_pan_dob_verified')) {
                $table->boolean('contact_name_pan_dob_verified')->default(false);
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'contact_email_verified')) {
                $table->boolean('contact_email_verified')->default(false);
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'contact_mobile_verified')) {
                $table->boolean('contact_mobile_verified')->default(false);
            }

            // Overall KYC status & metadata
            if (! Schema::hasColumn('user_kyc_profiles', 'status')) {
                $table->string('status')->default('pending'); // pending, completed
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'kyc_ip_address')) {
                $table->string('kyc_ip_address')->nullable();
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'kyc_user_agent')) {
                $table->string('kyc_user_agent')->nullable();
            }

            // Billing details
            if (! Schema::hasColumn('user_kyc_profiles', 'billing_address_source')) {
                $table->string('billing_address_source')->nullable();
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'billing_address')) {
                $table->text('billing_address')->nullable();
            }

            // Indexes / foreign keys (soft links; keep FKs simple)
            if (! Schema::hasColumn('user_kyc_profiles', 'user_id')) {
                $table->index('user_id');
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'gst_verification_id')) {
                $table->index('gst_verification_id');
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'udyam_verification_id')) {
                $table->index('udyam_verification_id');
            }
            if (! Schema::hasColumn('user_kyc_profiles', 'mca_verification_id')) {
                $table->index('mca_verification_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_kyc_profiles', function (Blueprint $table) {
            // Drop indexes and columns only if they exist to avoid errors on rollback
            if (Schema::hasColumn('user_kyc_profiles', 'user_id')) {
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('user_kyc_profiles', 'gst_verification_id')) {
                $table->dropIndex(['gst_verification_id']);
                $table->dropColumn('gst_verification_id');
            }
            if (Schema::hasColumn('user_kyc_profiles', 'udyam_verification_id')) {
                $table->dropIndex(['udyam_verification_id']);
                $table->dropColumn('udyam_verification_id');
            }
            if (Schema::hasColumn('user_kyc_profiles', 'mca_verification_id')) {
                $table->dropIndex(['mca_verification_id']);
                $table->dropColumn('mca_verification_id');
            }

            foreach ([
                'gstin',
                'udyam_number',
                'cin',
                'gst_verified',
                'udyam_verified',
                'mca_verified',
                'contact_name',
                'contact_dob',
                'contact_pan',
                'contact_email',
                'contact_mobile',
                'contact_name_pan_dob_verified',
                'contact_email_verified',
                'contact_mobile_verified',
                'status',
                'completed_at',
                'kyc_ip_address',
                'kyc_user_agent',
                'billing_address_source',
                'billing_address',
            ] as $column) {
                if (Schema::hasColumn('user_kyc_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
