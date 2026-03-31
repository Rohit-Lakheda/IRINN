<?php

namespace App\Http\Requests;

use App\Support\IrinnApplicationFlowOtp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreIrinNewFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $pairs = [
                ['irinn_mr_email', 'irinn_mr_mobile'],
                ['irinn_tp_email', 'irinn_tp_mobile'],
                ['irinn_abuse_email', 'irinn_abuse_mobile'],
                ['irinn_br_email', 'irinn_br_mobile'],
                ['irinn_upstream_email', 'irinn_upstream_mobile'],
                ['irinn_sign_email', 'irinn_sign_mobile'],
            ];
            foreach (IrinnApplicationFlowOtp::assertPairsVerifiedInSession($this, $pairs) as $field => $message) {
                $v->errors()->add($field, $message);
            }
        });
    }

    /**
     * @return array<string, array<int, \Illuminate\Contracts\Validation\ValidationRule|string>>
     */
    public function rules(): array
    {
        $cinTypes = ['private_limited', 'public_limited', 'llp', 'opc', 'psu'];
        $udyamTypes = ['partnership', 'proprietor'];
        $docTypes = ['government', 'ngo', 'academia_institute', 'trust'];

        $companyType = fn () => (string) request()->input('irinn_company_type', '');

        return [
            'irinn_form_version' => ['nullable', 'string', 'max:32'],
            'irinn_current_stage' => ['nullable', 'string', 'max:64'],

            'irinn_company_type' => ['required', 'string', 'max:64'],
            'irinn_cin_number' => [
                'nullable',
                'string',
                'max:32',
                Rule::requiredIf(fn () => in_array($companyType(), $cinTypes, true)),
            ],
            'irinn_udyam_number' => [
                'nullable',
                'string',
                'max:64',
                Rule::requiredIf(fn () => in_array($companyType(), $udyamTypes, true)),
            ],
            'irinn_registration_document' => [
                'nullable',
                'file',
                'mimes:pdf,jpeg,jpg,png,webp',
                'max:10240',
                Rule::requiredIf(fn () => in_array($companyType(), $docTypes, true)),
            ],

            'irinn_organisation_name' => ['required', 'string', 'max:255'],
            'irinn_organisation_address' => ['required', 'string', 'max:2000'],
            'irinn_organisation_postcode' => ['required', 'string', 'max:16'],
            'irinn_industry_type' => ['required', 'string', 'max:128'],
            'irinn_account_name' => ['nullable', 'string', 'max:128'],

            'irinn_has_gst_number' => ['nullable'],
            'irinn_billing_gstin' => [
                'nullable',
                'string',
                'max:20',
                Rule::requiredIf(fn () => request()->has('irinn_has_gst_number')),
            ],
            'irinn_ca_declaration_file' => [
                'nullable',
                'file',
                'mimes:pdf,jpeg,jpg,png,webp',
                'max:10240',
                Rule::requiredIf(fn () => ! request()->has('irinn_has_gst_number')),
            ],

            'irinn_billing_legal_name' => ['required', 'string', 'max:255'],
            'irinn_billing_pan' => ['required', 'string', 'size:10'],
            'irinn_billing_address' => ['required', 'string', 'max:2000'],
            'irinn_billing_postcode' => ['required', 'string', 'max:16'],

            'irinn_mr_name' => ['required', 'string', 'max:255'],
            'irinn_mr_designation' => ['required', 'string', 'max:255'],
            'irinn_mr_email' => ['required', 'email', 'max:255'],
            'irinn_mr_mobile' => ['required', 'string', 'size:10', 'regex:/^[0-9]{10}$/'],
            'irinn_mr_din' => ['nullable', 'string', 'max:32'],

            'irinn_tp_name' => ['required', 'string', 'max:255'],
            'irinn_tp_designation' => ['required', 'string', 'max:255'],
            'irinn_tp_email' => ['required', 'email', 'max:255'],
            'irinn_tp_mobile' => ['required', 'string', 'size:10', 'regex:/^[0-9]{10}$/'],

            'irinn_abuse_name' => ['required', 'string', 'max:255'],
            'irinn_abuse_designation' => ['required', 'string', 'max:255'],
            'irinn_abuse_email' => ['required', 'email', 'max:255'],
            'irinn_abuse_mobile' => ['required', 'string', 'size:10', 'regex:/^[0-9]{10}$/'],

            'irinn_br_name' => ['required', 'string', 'max:255'],
            'irinn_br_designation' => ['required', 'string', 'max:255'],
            'irinn_br_email' => ['required', 'email', 'max:255'],
            'irinn_br_mobile' => ['required', 'string', 'size:10', 'regex:/^[0-9]{10}$/'],

            'irinn_asn_required' => ['nullable'],

            'irinn_ipv4_resource_size' => ['nullable', 'string', 'max:32'],
            'irinn_ipv4_resource_addresses' => ['nullable', 'string', 'regex:/^\d{1,39}$/'],
            'irinn_ipv6_resource_size' => ['nullable', 'string', 'max:32'],
            'irinn_ipv6_resource_addresses' => ['nullable', 'string', 'regex:/^\d{1,39}$/'],
            'irinn_resource_fee_amount' => ['nullable', 'numeric', 'min:0'],

            'irinn_upstream_provider_name' => ['required', 'string', 'max:255'],
            'irinn_upstream_as_number' => ['required', 'string', 'max:64'],
            'irinn_upstream_mobile' => ['required', 'string', 'size:10', 'regex:/^[0-9]{10}$/'],
            'irinn_upstream_email' => ['required', 'email', 'max:255'],

            'irinn_sign_name' => ['required', 'string', 'max:255'],
            'irinn_sign_dob' => ['required', 'date'],
            'irinn_sign_pan' => ['required', 'string', 'size:10'],
            'irinn_sign_email' => ['required', 'email', 'max:255'],
            'irinn_sign_mobile' => ['required', 'string', 'size:10', 'regex:/^[0-9]{10}$/'],

            'irinn_signature_proof' => ['required', 'file', 'mimes:pdf,jpeg,jpg,png,webp', 'max:10240'],
            'irinn_board_resolution' => ['required', 'file', 'mimes:pdf,jpeg,jpg,png,webp', 'max:10240'],

            'irinn_kyc_network_diagram' => ['required', 'file', 'mimes:pdf,jpeg,jpg,png,webp', 'max:10240'],
            'irinn_kyc_equipment_invoice' => ['required', 'file', 'mimes:pdf,jpeg,jpg,png,webp', 'max:10240'],
            'irinn_kyc_bandwidth_proof' => ['required', 'file', 'mimes:pdf,jpeg,jpg,png,webp', 'max:10240'],
            'irinn_kyc_irinn_agreement' => ['required', 'file', 'mimes:pdf,jpeg,jpg,png,webp', 'max:10240'],

            'kyc_other_document_label' => ['nullable', 'array'],
            'kyc_other_document_label.*' => ['nullable', 'string', 'max:255'],
            'kyc_other_document_file' => ['nullable', 'array'],
            'kyc_other_document_file.*' => ['nullable', 'file', 'mimes:pdf,jpeg,jpg,png,webp', 'max:10240'],

            'mca_verification_request_id' => ['nullable', 'string', 'max:64'],
            'gst_verification_request_id' => ['nullable', 'string', 'max:64'],
            'irinn_management_rep_index' => ['nullable', 'string', 'max:8'],
        ];
    }

    /**
     * Human-readable names for validation messages (replaces raw field keys like irinn_tp_designation).
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'irinn_form_version' => 'form version',
            'irinn_current_stage' => 'application stage',
            'irinn_company_type' => 'company type',
            'irinn_cin_number' => 'CIN number',
            'irinn_udyam_number' => 'Udyam number',
            'irinn_registration_document' => 'registration document',
            'irinn_organisation_name' => 'organisation name',
            'irinn_organisation_address' => 'registered address',
            'irinn_organisation_postcode' => 'organisation postcode',
            'irinn_industry_type' => 'industry type',
            'irinn_account_name' => 'IRINN account name',
            'irinn_has_gst_number' => 'GST registration option',
            'irinn_billing_gstin' => 'GSTIN',
            'irinn_ca_declaration_file' => 'CA declaration',
            'irinn_billing_legal_name' => 'billing legal name',
            'irinn_billing_pan' => 'billing PAN',
            'irinn_billing_address' => 'billing address',
            'irinn_billing_postcode' => 'billing postcode',
            'irinn_mr_name' => 'management representative name',
            'irinn_mr_designation' => 'management representative designation',
            'irinn_mr_email' => 'management representative email',
            'irinn_mr_mobile' => 'management representative mobile',
            'irinn_mr_din' => 'management representative DIN',
            'irinn_tp_name' => 'technical person name',
            'irinn_tp_designation' => 'technical person designation',
            'irinn_tp_email' => 'technical person email',
            'irinn_tp_mobile' => 'technical person mobile',
            'irinn_abuse_name' => 'abuse contact name',
            'irinn_abuse_designation' => 'abuse contact designation',
            'irinn_abuse_email' => 'abuse contact email',
            'irinn_abuse_mobile' => 'abuse contact mobile',
            'irinn_br_name' => 'billing representative name',
            'irinn_br_designation' => 'billing representative designation',
            'irinn_br_email' => 'billing representative email',
            'irinn_br_mobile' => 'billing representative mobile',
            'irinn_asn_required' => 'ASN requirement',
            'irinn_ipv4_resource_size' => 'IPv4 resource',
            'irinn_ipv4_resource_addresses' => 'IPv4 address count',
            'irinn_ipv6_resource_size' => 'IPv6 resource',
            'irinn_ipv6_resource_addresses' => 'IPv6 address count',
            'irinn_resource_fee_amount' => 'resource fee',
            'irinn_upstream_provider_name' => 'upstream provider name',
            'irinn_upstream_as_number' => 'upstream AS number',
            'irinn_upstream_mobile' => 'upstream provider mobile',
            'irinn_upstream_email' => 'upstream provider email',
            'irinn_sign_name' => 'authorised signatory name',
            'irinn_sign_dob' => 'signatory date of birth',
            'irinn_sign_pan' => 'signatory PAN',
            'irinn_sign_email' => 'signatory email',
            'irinn_sign_mobile' => 'signatory mobile',
            'irinn_signature_proof' => 'signature proof document',
            'irinn_board_resolution' => 'board resolution document',
            'irinn_kyc_network_diagram' => 'network diagram (KYC)',
            'irinn_kyc_equipment_invoice' => 'equipment invoice (KYC)',
            'irinn_kyc_bandwidth_proof' => 'bandwidth proof (KYC)',
            'irinn_kyc_irinn_agreement' => 'IRINN agreement (KYC)',
            'kyc_other_document_label' => 'additional document label',
            'kyc_other_document_label.*' => 'additional document label',
            'kyc_other_document_file' => 'additional document file',
            'kyc_other_document_file.*' => 'additional document file',
            'mca_verification_request_id' => 'CIN verification reference',
            'gst_verification_request_id' => 'GST verification reference',
            'irinn_management_rep_index' => 'director selection',
        ];
    }

    /**
     * Clearer default phrasing for common rule failures.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'irinn_tp_designation.required' => 'Please select a designation for the technical contact person (Step 3).',
            'irinn_mr_designation.required' => 'Please select a designation for the management representative (Step 2).',
            'irinn_abuse_designation.required' => 'Please select a designation for the abuse contact (Step 3).',
            'irinn_br_designation.required' => 'Please select a designation for the billing representative (Step 4).',
            'irinn_tp_email.required' => 'Please enter and verify the technical person\'s email.',
            'irinn_tp_mobile.required' => 'Please enter and verify the technical person\'s mobile number.',
        ];
    }

    /**
     * Validation rules when resubmitting an existing normalized IRINN application (files optional; server merges with stored paths).
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\ValidationRule|string>>
     */
    public static function rulesForNormalizedResubmit(): array
    {
        $instance = new self;
        $rules = $instance->rules();
        $nullableFile = ['nullable', 'file', 'mimes:pdf,jpeg,jpg,png,webp', 'max:10240'];
        foreach ([
            'irinn_registration_document',
            'irinn_ca_declaration_file',
            'irinn_signature_proof',
            'irinn_board_resolution',
            'irinn_kyc_network_diagram',
            'irinn_kyc_equipment_invoice',
            'irinn_kyc_bandwidth_proof',
            'irinn_kyc_irinn_agreement',
        ] as $key) {
            $rules[$key] = $nullableFile;
        }

        return $rules;
    }
}
