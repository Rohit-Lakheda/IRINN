<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalletPaymentRequest extends FormRequest
{
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
        return [
            'application_id' => ['required', 'integer', 'exists:applications,id'],
            'amount' => ['required', 'numeric', 'min:1'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'application_id.required' => 'Application ID is required.',
            'application_id.exists' => 'Invalid application selected.',
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Amount must be at least ₹1.',
        ];
    }
}
