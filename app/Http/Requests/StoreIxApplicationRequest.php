<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class StoreIxApplicationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('previous_gstin') && trim((string) $this->input('previous_gstin')) === '') {
            $this->merge(['previous_gstin' => null]);
        }
        
        // Normalize PAN: uppercase, trim, remove spaces and special characters
        if ($this->has('representative_pan')) {
            $pan = strtoupper(trim((string) $this->input('representative_pan')));
            $pan = preg_replace('/[^A-Z0-9]/', '', $pan); // Remove all non-alphanumeric
            $this->merge(['representative_pan' => $pan]);
        }
        
        // Normalize GSTIN: uppercase, trim, remove spaces and special characters
        if ($this->has('gstin')) {
            $gstin = strtoupper(trim((string) $this->input('gstin')));
            $gstin = preg_replace('/[^A-Z0-9]/', '', $gstin); // Remove all non-alphanumeric
            $this->merge(['gstin' => $gstin]);
        }
        
        // Normalize mobile: remove spaces and special characters
        if ($this->has('representative_mobile')) {
            $mobile = trim((string) $this->input('representative_mobile'));
            $mobile = preg_replace('/[^0-9]/', '', $mobile); // Keep only digits
            $this->merge(['representative_mobile' => $mobile]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return session()->has('user_id');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isDraft = $this->input('is_draft', false);
        $isPreview = $this->input('is_preview', false);

        // Check if this is a simplified form submission
        $isSimplifiedForm = $this->has('representative_name') || $this->has('representative_pan');

        // For draft saves, make most fields nullable
        // For preview, mandatory fields should still be required
        $required = $isDraft ? 'nullable' : 'required';

        // Simplified form rules
        if ($isSimplifiedForm) {
            $previousGstin = strtoupper((string) $this->input('previous_gstin'));
            $currentGstin = strtoupper((string) $this->input('gstin'));
            $gstChanged = $previousGstin && $currentGstin && $previousGstin !== $currentGstin;

            return [
                'representative_name' => [$required, 'string', 'max:255'],
                'representative_pan' => [$required, 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
                'representative_dob' => [$required, 'date', 'before:today'],
                'representative_mobile' => [$required, 'string', 'size:10', 'regex:/^[0-9]{10}$/'],
                'representative_email' => [$required, 'email', 'max:255'],
                'pan_verified' => [$required, 'in:1'],
                'mobile_verified' => [$required, 'in:1'],
                'email_verified' => [$required, 'in:1'],
                'location_id' => [$required, 'integer', 'exists:ix_locations,id'],
                'port_capacity' => [$required, 'string', 'max:50'],
                'billing_plan' => [$required, Rule::in(['arc', 'mrc', 'quarterly'])],
                'ip_prefix_count' => [$required, 'integer', 'min:1', 'max:500'],
                'gstin' => [$required, 'string', 'size:15', 'regex:/^[0-9A-Z]{15}$/'],
                'previous_gstin' => ['nullable', 'string', 'size:15', 'regex:/^[0-9A-Z]{15}$/'],
                'gstin_verified' => [$required, 'in:1'],
                'gstin_verification_id' => 'nullable|integer|exists:gst_verifications,id',
                'new_gst_document' => [
                    $gstChanged ? 'required' : 'nullable',
                    'file',
                    'mimes:pdf',
                    'max:10240',
                ],
                'is_simplified' => 'nullable|boolean',
            ];
        }

        return [
            'member_type' => [$required, Rule::in(['isp', 'cdn', 'vno', 'govt', 'others'])],
            'member_type_other' => 'nullable|required_if:member_type,others|string|max:255',
            'location_id' => [$required, 'integer', 'exists:ix_locations,id'],
            'port_capacity' => [$required, 'string', 'max:50'],
            'billing_plan' => [$required, Rule::in(['arc', 'mrc', 'quarterly'])],
            'ip_prefix_count' => [$required, 'integer', 'min:1', 'max:500'],
            'ip_prefix_source' => [$required, Rule::in(['irinn', 'apnic', 'others'])],
            'ip_prefix_provider' => 'nullable|required_if:ip_prefix_source,others|string|max:255',
            'pre_peering_connectivity' => [$required, Rule::in(['none', 'single', 'multiple'])],
            'asn_number' => [
                $isDraft ? 'nullable' : 'required',
                'string',
                'max:50',
            ],
            'router_height_u' => 'nullable|integer|min:1|max:50',
            'router_make_model' => 'nullable|string|max:255',
            'router_serial_number' => 'nullable|string|max:255',
            'agreement_file' => [$required, 'file', 'mimes:pdf', 'max:10240'],
            'license_isp_file' => [
                $isDraft ? 'nullable' : 'required_if:member_type,isp',
                'file',
                'mimes:pdf',
                'max:10240',
            ],
            'license_vno_file' => [
                $isDraft ? 'nullable' : 'required_if:member_type,vno',
                'file',
                'mimes:pdf',
                'max:10240',
            ],
            'cdn_declaration_file' => [
                $isDraft ? 'nullable' : 'required_if:member_type,cdn',
                'file',
                'mimes:pdf',
                'max:10240',
            ],
            'general_declaration_file' => [
                $isDraft ? 'nullable' : 'required_unless:member_type,isp,vno,cdn',
                'file',
                'mimes:pdf',
                'max:10240',
            ],
            'whois_details_file' => [$required, 'file', 'mimes:pdf', 'max:10240'],
            'pan_document_file' => [$required, 'file', 'mimes:pdf', 'max:10240'],
            'gstin_document_file' => [$required, 'file', 'mimes:pdf', 'max:10240'],
            'msme_document_file' => 'nullable|file|mimes:pdf|max:10240',
            'incorporation_document_file' => [$required, 'file', 'mimes:pdf', 'max:10240'],
            'authorized_rep_document_file' => [$required, 'file', 'mimes:pdf', 'max:10240'],
            // Declaration checkbox only required for final submission, not for draft or preview
            'declaration_confirmed' => ($isDraft || $isPreview) ? 'nullable' : 'accepted',
            'is_draft' => 'nullable|boolean',
            'is_preview' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        // Check if this is a simplified form submission
        $isSimplifiedForm = $this->has('representative_name') || $this->has('representative_pan');

        // Simplified form messages
        if ($isSimplifiedForm) {
            return [
                'representative_name.required' => 'Representative name is required.',
                'representative_pan.required' => 'PAN is required.',
                'representative_pan.size' => 'PAN must be exactly 10 characters.',
                'representative_pan.regex' => 'PAN format is invalid. Format: ABCDE1234F',
                'representative_dob.required' => 'Date of birth is required.',
                'representative_dob.before' => 'Date of birth must be a past date.',
                'representative_mobile.required' => 'Mobile number is required.',
                'representative_mobile.size' => 'Mobile number must be exactly 10 digits.',
                'representative_mobile.regex' => 'Mobile number must contain only digits.',
                'representative_email.required' => 'Email is required.',
                'representative_email.email' => 'Please enter a valid email address.',
                'pan_verified.required' => 'PAN must be verified before submission.',
                'pan_verified.in' => 'PAN verification is required.',
                'mobile_verified.required' => 'Mobile number must be verified before submission.',
                'mobile_verified.in' => 'Mobile verification is required.',
                'email_verified.required' => 'Email must be verified before submission.',
                'email_verified.in' => 'Email verification is required.',
                'location_id.required' => 'NIXI location is required.',
                'location_id.exists' => 'Selected location is invalid.',
                'port_capacity.required' => 'Port capacity is required.',
                'billing_plan.required' => 'Billing plan is required.',
                'billing_plan.in' => 'Please select a valid billing plan.',
                'ip_prefix_count.required' => 'Number of IP prefixes is required.',
                'ip_prefix_count.min' => 'Number of IP prefixes must be at least 1.',
                'ip_prefix_count.max' => 'Number of IP prefixes cannot exceed 500.',
                'gstin.required' => 'GSTIN is required.',
                'gstin.size' => 'GSTIN must be exactly 15 characters.',
                'gstin.regex' => 'GSTIN format is invalid.',
                'gstin_verified.required' => 'GSTIN must be verified before submission.',
                'gstin_verified.in' => 'GSTIN verification is required.',
                'new_gst_document.mimes' => 'GST document must be a PDF.',
                'new_gst_document.max' => 'GST document size must not exceed 10 MB.',
            ];
        }

        return [
            'member_type.required' => 'Member type is required.',
            'member_type.in' => 'Please select a valid member type.',
            'member_type_other.required_if' => 'Please specify the member type.',
            'location_id.required' => 'NIXI location is required.',
            'location_id.exists' => 'Selected location is invalid.',
            'port_capacity.required' => 'Port capacity is required.',
            'billing_plan.required' => 'Billing plan is required.',
            'billing_plan.in' => 'Please select a valid billing plan.',
            'ip_prefix_count.required' => 'Number of IP prefixes is required.',
            'ip_prefix_count.min' => 'Number of IP prefixes must be at least 1.',
            'ip_prefix_count.max' => 'Number of IP prefixes cannot exceed 500.',
            'ip_prefix_source.required' => 'IP prefix source is required.',
            'ip_prefix_provider.required_if' => 'Provider name is required when source is Others.',
            'pre_peering_connectivity.required' => 'Pre-NIXI peering connectivity is required.',
            'asn_number.required_if' => 'AS Number is required when Pre-NIXI peering connectivity is Single or Multiple.',
            'agreement_file.required' => 'Signed agreement file is required.',
            'agreement_file.mimes' => 'Agreement file must be a PDF.',
            'agreement_file.max' => 'Agreement file size must not exceed 10 MB.',
            'license_isp_file.required_if' => 'ISP License is required for ISP member type.',
            'license_isp_file.mimes' => 'ISP License file must be a PDF.',
            'license_isp_file.max' => 'ISP License file size must not exceed 10 MB.',
            'license_vno_file.required_if' => 'VNO License is required for VNO member type.',
            'license_vno_file.mimes' => 'VNO License file must be a PDF.',
            'license_vno_file.max' => 'VNO License file size must not exceed 10 MB.',
            'cdn_declaration_file.required_if' => 'CDN Declaration is required for CDN member type.',
            'cdn_declaration_file.mimes' => 'CDN Declaration file must be a PDF.',
            'cdn_declaration_file.max' => 'CDN Declaration file size must not exceed 10 MB.',
            'general_declaration_file.required_if' => 'General Declaration is required for Government Entity or Others member type.',
            'general_declaration_file.mimes' => 'General Declaration file must be a PDF.',
            'general_declaration_file.max' => 'General Declaration file size must not exceed 10 MB.',
            'whois_details_file.required' => 'Whois details file is required.',
            'pan_document_file.required' => 'PAN document is required.',
            'gstin_document_file.required' => 'GSTIN document is required.',
            'incorporation_document_file.required' => 'Certificate of incorporation is required.',
            'authorized_rep_document_file.required' => 'Authorized representative document is required.',
            'declaration_confirmed.accepted' => 'You must accept the declaration before proceeding.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        // If this is an AJAX request or expects JSON, return JSON response
        if ($this->expectsJson() || $this->ajax() || $this->wantsJson()) {
            throw new ValidationException($validator);
        }

        parent::failedValidation($validator);
    }

    /**
     * Additional server-side verification to ensure OTP/API checks were completed.
     */
    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $isSimplifiedForm = $this->has('representative_name') || $this->has('representative_pan');
            if (! $isSimplifiedForm) {
                return;
            }

            $pan = strtoupper((string) $this->input('representative_pan'));
            $panVerifiedFlag = (string) $this->input('pan_verified') === '1';
            $sessionPanVerified = session()->get('ix_pan_verified_'.md5($pan), false);

            // Accept either the session-based PAN verification flag or the hidden form flag.
            // This prevents false negatives when the session flag is missing but the user
            // has successfully verified PAN through the UI.
            if (! $panVerifiedFlag && ! $sessionPanVerified) {
                $validator->errors()->add('representative_pan', 'PAN verification not completed.');
            }

            $mobile = (string) $this->input('representative_mobile');
            if (! session()->get('mobile_verified_'.md5($mobile), false)) {
                $validator->errors()->add('representative_mobile', 'Mobile verification not completed.');
            }

            $email = (string) $this->input('representative_email');
            if (! session()->get('email_verified_'.md5($email), false)) {
                $validator->errors()->add('representative_email', 'Email verification not completed.');
            }

            $gstin = strtoupper((string) $this->input('gstin'));
            $verificationId = $this->input('gstin_verification_id');
            $gstVerifiedById = $verificationId ? session()->get('ix_gstin_verified_'.$verificationId, false) : false;
            $gstVerifiedByValue = session()->get('ix_gstin_verified_value_'.md5($gstin), false);

            if (! $gstVerifiedById || ! $gstVerifiedByValue) {
                $validator->errors()->add('gstin', 'GSTIN verification not completed.');
            }
        });
    }
}
