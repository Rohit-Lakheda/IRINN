<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return session()->has('admin_id');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'billing_period' => 'nullable|string|max:50',
            'billing_start_date' => 'nullable|date',
            'billing_end_date' => 'nullable|date|after_or_equal:billing_start_date',
            'amount' => 'required|numeric|min:0',
            'gst_amount' => 'required|numeric|min:0',
            'tds_amount' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $baseAmount = (float) request()->input('amount', 0);
                    $tdsAmount = (float) $value;
                    $maxTdsAmount = ($baseAmount * 10) / 100; // 10% of base amount

                    if ($tdsAmount > $maxTdsAmount) {
                        $fail('The TDS amount cannot exceed 10% of the base amount (₹'.number_format($maxTdsAmount, 2).').');
                    }
                },
            ],
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'balance_amount' => 'required|numeric|min:0',
            'payment_status' => 'required|in:pending,partial,paid,overdue,cancelled',
            'status' => 'required|in:pending,paid,overdue,cancelled',
            'carry_forward_amount' => 'nullable|numeric|min:0',
            'has_carry_forward' => 'nullable|boolean',
            'line_items' => 'nullable|array',
            'line_items.*.description' => 'required_with:line_items|string|max:500',
            'line_items.*.quantity' => 'nullable|numeric|min:0',
            'line_items.*.rate' => 'nullable|numeric|min:0',
            'line_items.*.amount' => 'nullable|numeric|min:0',
            'manual_payment_id' => 'nullable|string|max:255',
            'manual_payment_notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'invoice_date.required' => 'Invoice date is required.',
            'invoice_date.date' => 'Invoice date must be a valid date.',
            'due_date.required' => 'Due date is required.',
            'due_date.date' => 'Due date must be a valid date.',
            'due_date.after_or_equal' => 'Due date must be on or after invoice date.',
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a number.',
            'amount.min' => 'Amount cannot be negative.',
            'gst_amount.required' => 'GST amount is required.',
            'gst_amount.numeric' => 'GST amount must be a number.',
            'gst_amount.min' => 'GST amount cannot be negative.',
            'total_amount.required' => 'Total amount is required.',
            'total_amount.numeric' => 'Total amount must be a number.',
            'total_amount.min' => 'Total amount cannot be negative.',
            'payment_status.required' => 'Payment status is required.',
            'payment_status.in' => 'Payment status must be one of: pending, partial, paid, overdue, cancelled.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be one of: pending, paid, overdue, cancelled.',
        ];
    }
}
