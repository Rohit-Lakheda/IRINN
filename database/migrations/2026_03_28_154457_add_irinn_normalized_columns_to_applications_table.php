<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normalized IRINN (create-new flow) columns — one field per form value + document paths.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('irinn_form_version', 32)->nullable()->after('application_type');
            $table->string('irinn_current_stage', 64)->nullable()->index()->after('irinn_form_version');

            $table->string('irinn_company_type', 64)->nullable()->after('irinn_current_stage');
            $table->string('irinn_cin_number', 32)->nullable()->after('irinn_company_type');
            $table->string('irinn_udyam_number', 64)->nullable()->after('irinn_cin_number');
            $table->string('irinn_registration_document_path')->nullable()->after('irinn_udyam_number');
            $table->string('irinn_organisation_name')->nullable()->after('irinn_registration_document_path');
            $table->text('irinn_organisation_address')->nullable()->after('irinn_organisation_name');
            $table->string('irinn_organisation_postcode', 16)->nullable()->after('irinn_organisation_address');
            $table->string('irinn_industry_type', 128)->nullable()->after('irinn_organisation_postcode');
            $table->string('irinn_account_name', 128)->nullable()->after('irinn_industry_type');
            $table->boolean('irinn_has_gst_number')->nullable()->after('irinn_account_name');
            $table->string('irinn_billing_gstin', 20)->nullable()->after('irinn_has_gst_number');
            $table->string('irinn_ca_declaration_path')->nullable()->after('irinn_billing_gstin');
            $table->string('irinn_billing_legal_name')->nullable()->after('irinn_ca_declaration_path');
            $table->string('irinn_billing_pan', 16)->nullable()->after('irinn_billing_legal_name');
            $table->text('irinn_billing_address')->nullable()->after('irinn_billing_pan');
            $table->string('irinn_billing_postcode', 16)->nullable()->after('irinn_billing_address');

            $table->string('irinn_mr_name')->nullable()->after('irinn_billing_postcode');
            $table->string('irinn_mr_designation')->nullable()->after('irinn_mr_name');
            $table->string('irinn_mr_email')->nullable()->after('irinn_mr_designation');
            $table->string('irinn_mr_mobile', 20)->nullable()->after('irinn_mr_email');
            $table->string('irinn_mr_din', 32)->nullable()->after('irinn_mr_mobile');

            $table->string('irinn_tp_name')->nullable()->after('irinn_mr_din');
            $table->string('irinn_tp_designation')->nullable()->after('irinn_tp_name');
            $table->string('irinn_tp_email')->nullable()->after('irinn_tp_designation');
            $table->string('irinn_tp_mobile', 20)->nullable()->after('irinn_tp_email');

            $table->string('irinn_abuse_name')->nullable()->after('irinn_tp_mobile');
            $table->string('irinn_abuse_designation')->nullable()->after('irinn_abuse_name');
            $table->string('irinn_abuse_email')->nullable()->after('irinn_abuse_designation');
            $table->string('irinn_abuse_mobile', 20)->nullable()->after('irinn_abuse_email');

            $table->string('irinn_br_name')->nullable()->after('irinn_abuse_mobile');
            $table->string('irinn_br_designation')->nullable()->after('irinn_br_name');
            $table->string('irinn_br_email')->nullable()->after('irinn_br_designation');
            $table->string('irinn_br_mobile', 20)->nullable()->after('irinn_br_email');

            $table->boolean('irinn_asn_required')->nullable()->after('irinn_br_mobile');
            $table->string('irinn_ipv4_resource_size', 32)->nullable()->after('irinn_asn_required');
            $table->unsignedBigInteger('irinn_ipv4_resource_addresses')->nullable()->after('irinn_ipv4_resource_size');
            $table->string('irinn_ipv6_resource_size', 32)->nullable()->after('irinn_ipv4_resource_addresses');
            $table->unsignedBigInteger('irinn_ipv6_resource_addresses')->nullable()->after('irinn_ipv6_resource_size');
            $table->decimal('irinn_resource_fee_amount', 15, 2)->nullable()->after('irinn_ipv6_resource_addresses');

            $table->string('irinn_upstream_provider_name')->nullable()->after('irinn_resource_fee_amount');
            $table->string('irinn_upstream_as_number', 64)->nullable()->after('irinn_upstream_provider_name');
            $table->string('irinn_upstream_mobile', 20)->nullable()->after('irinn_upstream_as_number');
            $table->string('irinn_upstream_email')->nullable()->after('irinn_upstream_mobile');

            $table->string('irinn_sign_name')->nullable()->after('irinn_upstream_email');
            $table->date('irinn_sign_dob')->nullable()->after('irinn_sign_name');
            $table->string('irinn_sign_pan', 16)->nullable()->after('irinn_sign_dob');
            $table->string('irinn_sign_email')->nullable()->after('irinn_sign_pan');
            $table->string('irinn_sign_mobile', 20)->nullable()->after('irinn_sign_email');
            $table->string('irinn_signature_proof_path')->nullable()->after('irinn_sign_mobile');
            $table->string('irinn_board_resolution_path')->nullable()->after('irinn_signature_proof_path');

            $table->string('irinn_kyc_network_diagram_path')->nullable()->after('irinn_board_resolution_path');
            $table->string('irinn_kyc_equipment_invoice_path')->nullable()->after('irinn_kyc_network_diagram_path');
            $table->string('irinn_kyc_bandwidth_proof_path')->nullable()->after('irinn_kyc_equipment_invoice_path');
            $table->string('irinn_kyc_irinn_agreement_path')->nullable()->after('irinn_kyc_bandwidth_proof_path');

            $afterOther = 'irinn_kyc_irinn_agreement_path';
            for ($i = 1; $i <= 5; $i++) {
                $table->string("irinn_other_doc_{$i}_label")->nullable()->after($afterOther);
                $afterOther = "irinn_other_doc_{$i}_label";
                $table->string("irinn_other_doc_{$i}_path")->nullable()->after($afterOther);
                $afterOther = "irinn_other_doc_{$i}_path";
            }
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->index(['application_type', 'irinn_current_stage'], 'applications_type_irinn_stage_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex('applications_type_irinn_stage_index');
        });

        $columns = [
            'irinn_form_version',
            'irinn_current_stage',
            'irinn_company_type',
            'irinn_cin_number',
            'irinn_udyam_number',
            'irinn_registration_document_path',
            'irinn_organisation_name',
            'irinn_organisation_address',
            'irinn_organisation_postcode',
            'irinn_industry_type',
            'irinn_account_name',
            'irinn_has_gst_number',
            'irinn_billing_gstin',
            'irinn_ca_declaration_path',
            'irinn_billing_legal_name',
            'irinn_billing_pan',
            'irinn_billing_address',
            'irinn_billing_postcode',
            'irinn_mr_name',
            'irinn_mr_designation',
            'irinn_mr_email',
            'irinn_mr_mobile',
            'irinn_mr_din',
            'irinn_tp_name',
            'irinn_tp_designation',
            'irinn_tp_email',
            'irinn_tp_mobile',
            'irinn_abuse_name',
            'irinn_abuse_designation',
            'irinn_abuse_email',
            'irinn_abuse_mobile',
            'irinn_br_name',
            'irinn_br_designation',
            'irinn_br_email',
            'irinn_br_mobile',
            'irinn_asn_required',
            'irinn_ipv4_resource_size',
            'irinn_ipv4_resource_addresses',
            'irinn_ipv6_resource_size',
            'irinn_ipv6_resource_addresses',
            'irinn_resource_fee_amount',
            'irinn_upstream_provider_name',
            'irinn_upstream_as_number',
            'irinn_upstream_mobile',
            'irinn_upstream_email',
            'irinn_sign_name',
            'irinn_sign_dob',
            'irinn_sign_pan',
            'irinn_sign_email',
            'irinn_sign_mobile',
            'irinn_signature_proof_path',
            'irinn_board_resolution_path',
            'irinn_kyc_network_diagram_path',
            'irinn_kyc_equipment_invoice_path',
            'irinn_kyc_bandwidth_proof_path',
            'irinn_kyc_irinn_agreement_path',
        ];

        for ($i = 1; $i <= 5; $i++) {
            $columns[] = "irinn_other_doc_{$i}_label";
            $columns[] = "irinn_other_doc_{$i}_path";
        }

        Schema::table('applications', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
};
