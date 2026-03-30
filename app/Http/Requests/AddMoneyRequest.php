<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use App\Models\Registration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class AddMoneyRequest extends FormRequest
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
        $userId = session('user_id');
        $user = $userId ? Registration::find($userId) : null;

        if (! $user || ! $user->wallet) {
            return [
                'amount' => ['required', 'numeric', 'min:1', 'max:100000'],
            ];
        }

        $totalBillingCycleAmount = $this->calculateTotalBillingCycleAmount($user);
        $currentBalance = (float) $user->wallet->balance;

        // Calculate minimum amount to add based on current balance
        $minimumAmountToAdd = 1;
        if ($totalBillingCycleAmount > 0 && $currentBalance < $totalBillingCycleAmount) {
            // Need to add at least: (required amount - current balance)
            $minimumAmountToAdd = $totalBillingCycleAmount - $currentBalance;
        }

        return [
            'amount' => [
                'required',
                'numeric',
                'min:'.max(1, $minimumAmountToAdd),
                'max:100000',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $userId = session('user_id');
        $user = $userId ? Registration::find($userId) : null;

        if (! $user || ! $user->wallet) {
            return [
                'amount.required' => 'Amount is required.',
                'amount.numeric' => 'Amount must be a valid number.',
                'amount.min' => 'Minimum amount is ₹1.',
                'amount.max' => 'Maximum amount is ₹1,00,000.',
            ];
        }

        $totalBillingCycleAmount = $this->calculateTotalBillingCycleAmount($user);
        $currentBalance = (float) $user->wallet->balance;

        // Calculate minimum amount to add based on current balance
        $minimumAmountToAdd = 1;
        $minAmountText = 'Minimum amount is ₹1.';

        if ($totalBillingCycleAmount > 0 && $currentBalance < $totalBillingCycleAmount) {
            // Need to add at least: (required amount - current balance)
            $minimumAmountToAdd = $totalBillingCycleAmount - $currentBalance;
            $shortfall = $totalBillingCycleAmount - $currentBalance;
            $minAmountText = 'Minimum amount is ₹'.number_format($minimumAmountToAdd, 2).' (your current balance ₹'.number_format($currentBalance, 2).' is less than required ₹'.number_format($totalBillingCycleAmount, 2).' by ₹'.number_format($shortfall, 2).').';
        } elseif ($totalBillingCycleAmount > 0 && $currentBalance >= $totalBillingCycleAmount) {
            $minAmountText = 'Minimum amount is ₹1. Your current balance is sufficient for the next billing cycle.';
        }

        return [
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => $minAmountText,
            'amount.max' => 'Maximum amount is ₹1,00,000.',
        ];
    }

    /**
     * Calculate total billing cycle amount for all live applications of a user.
     */
    private function calculateTotalBillingCycleAmount(Registration $user): float
    {
        try {
            return (float) Invoice::query()
                ->whereHas('application', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->where('application_type', 'IRINN');
                })
                ->where(function ($q) {
                    $q->where('payment_status', 'pending')
                        ->orWhere('payment_status', 'partial');
                })
                ->where('status', '!=', 'cancelled')
                ->get()
                ->sum(fn ($inv) => (float) ($inv->balance_amount ?? $inv->total_amount ?? 0));
        } catch (\Exception $e) {
            Log::error('Error calculating outstanding invoice total (add money): '.$e->getMessage());

            return 0;
        }
    }
}
