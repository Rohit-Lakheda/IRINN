<?php

namespace App\Http\Requests;

use App\Models\Application;
use App\Models\IxLocation;
use App\Models\IxPortPricing;
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
            $totalAmount = 0;

            // Get all live applications
            $liveApplications = Application::where('user_id', $user->id)
                ->where('application_type', 'IX')
                ->where('is_active', true)
                ->whereNotNull('service_activation_date')
                ->whereNotNull('membership_id')
                ->get();

            foreach ($liveApplications as $application) {
                $applicationData = $application->application_data ?? [];
                $billingPlan = $application->billing_cycle ?? ($applicationData['port_selection']['billing_plan'] ?? 'monthly');
                
                // Normalize billing plan
                $billingPlan = strtolower(trim($billingPlan));
                if (in_array($billingPlan, ['arc', 'annual'])) {
                    $billingPlan = 'arc';
                } elseif (in_array($billingPlan, ['mrc', 'monthly'])) {
                    $billingPlan = 'mrc';
                } elseif ($billingPlan === 'quarterly') {
                    $billingPlan = 'quarterly';
                } else {
                    $billingPlan = 'mrc';
                }

                // Get location
                $locationId = $applicationData['location']['id'] ?? null;
                $location = $locationId ? IxLocation::find($locationId) : null;

                if (! $location) {
                    continue;
                }

                // Get port capacity
                $portCapacity = $application->assigned_port_capacity ?? ($applicationData['port_selection']['capacity'] ?? null);
                if (! $portCapacity) {
                    continue;
                }

                // Normalize capacity
                $normalizedCapacity = trim($portCapacity);
                $normalizedCapacity = preg_replace('/\s+/', '', $normalizedCapacity);
                if (stripos($normalizedCapacity, 'Gbps') !== false) {
                    $normalizedCapacity = str_ireplace(['Gbps', 'gbps', 'GBPS'], 'Gig', $normalizedCapacity);
                }
                if (! preg_match('/(Gig|M)$/i', $normalizedCapacity)) {
                    if (preg_match('/^\d+$/', $normalizedCapacity)) {
                        $normalizedCapacity .= 'Gig';
                    }
                }

                // Get pricing
                $pricing = IxPortPricing::active()
                    ->where('node_type', $location->node_type)
                    ->where('port_capacity', $normalizedCapacity)
                    ->first();

                // Try variations if exact match not found
                if (! $pricing) {
                    $variations = [
                        trim($portCapacity),
                        str_replace(' ', '', trim($portCapacity)),
                        preg_replace('/\s+/', '', trim($portCapacity)),
                        str_replace(['Gbps', 'gbps', 'GBPS'], 'Gig', str_replace(' ', '', trim($portCapacity))),
                    ];
                    foreach (array_unique($variations) as $variation) {
                        if (empty($variation)) {
                            continue;
                        }
                        $pricing = IxPortPricing::active()
                            ->where('node_type', $location->node_type)
                            ->where('port_capacity', $variation)
                            ->first();
                        if ($pricing) {
                            break;
                        }
                    }
                }

                if (! $pricing) {
                    continue;
                }

                // Get base amount for billing plan
                $baseAmount = $pricing->getAmountForPlan($billingPlan);
                if (! $baseAmount || $baseAmount <= 0) {
                    continue;
                }

                // Calculate GST
                $gstState = $location->state ?? null;
                $isDelhi = strtolower($gstState ?? '') === 'delhi' || strtolower($gstState ?? '') === 'new delhi';
                
                if ($isDelhi) {
                    $gstAmount = round(($baseAmount * 18) / 100, 2); // CGST + SGST = 18%
                } else {
                    $gstAmount = round(($baseAmount * 18) / 100, 2); // IGST = 18%
                }

                $totalAmount += round($baseAmount + $gstAmount, 2);
            }

            return round($totalAmount, 2);
        } catch (\Exception $e) {
            Log::error('Error calculating total billing cycle amount: '.$e->getMessage());
            return 0;
        }
    }
}
