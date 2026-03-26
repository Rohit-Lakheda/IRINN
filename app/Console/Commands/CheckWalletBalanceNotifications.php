<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\IxLocation;
use App\Models\IxPortPricing;
use App\Models\Message;
use App\Models\Registration;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckWalletBalanceNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:check-balance-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check wallet balances and send notifications if balance is lower than next billing cycle amount';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->info('Starting wallet balance check...');

            // Get all users with active wallets
            $users = Registration::whereHas('wallet', function ($query) {
                $query->where('status', 'active');
            })->with('wallet')->get();

            $notificationsSent = 0;
            $usersChecked = 0;

            foreach ($users as $user) {
                $usersChecked++;
                $wallet = $user->wallet;

                if (! $wallet || $wallet->status !== 'active') {
                    continue;
                }

                // Calculate total billing cycle amount for all live applications
                $totalBillingCycleAmount = $this->calculateTotalBillingCycleAmount($user);
                $walletBalance = (float) $wallet->balance;

                // Check if balance is lower than next billing cycle amount
                if ($totalBillingCycleAmount > 0 && $walletBalance < $totalBillingCycleAmount) {
                    // Check if we already sent a notification recently (within last 7 days)
                    $recentNotification = Message::where('user_id', $user->id)
                        ->where('subject', 'like', '%Wallet Balance Low%')
                        ->where('created_at', '>=', now()->subDays(7))
                        ->exists();

                    if (! $recentNotification) {
                        // Send notification
                        $this->sendLowBalanceNotification($user, $walletBalance, $totalBillingCycleAmount);
                        $notificationsSent++;
                    }
                }
            }

            $this->info("Checked {$usersChecked} users. Sent {$notificationsSent} notifications.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Error checking wallet balances: '.$e->getMessage());
            $this->error('Error: '.$e->getMessage());

            return Command::FAILURE;
        }
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
            Log::error('Error calculating total billing cycle amount for user '.$user->id.': '.$e->getMessage());
            return 0;
        }
    }

    /**
     * Send low balance notification to user.
     */
    private function sendLowBalanceNotification(Registration $user, float $currentBalance, float $requiredAmount): void
    {
        try {
            $shortfall = $requiredAmount - $currentBalance;
            $subject = 'Wallet Balance Low - Top-up Required';
            $message = "Dear {$user->fullname},\n\n";
            $message .= "Your wallet balance (₹".number_format($currentBalance, 2).") is lower than your next billing cycle amount (₹".number_format($requiredAmount, 2).").\n\n";
            $message .= "Shortfall: ₹".number_format($shortfall, 2)."\n\n";
            $message .= "Please top-up your wallet to ensure uninterrupted service. You can add money from your wallet dashboard.\n\n";
            $message .= "Thank you for your attention.";

            Message::create([
                'user_id' => $user->id,
                'subject' => $subject,
                'message' => $message,
                'is_read' => false,
                'admin_read' => false,
                'sent_by' => 'system',
            ]);

            Log::info("Low balance notification sent to user {$user->id}", [
                'user_id' => $user->id,
                'current_balance' => $currentBalance,
                'required_amount' => $requiredAmount,
                'shortfall' => $shortfall,
            ]);
        } catch (\Exception $e) {
            Log::error("Error sending low balance notification to user {$user->id}: ".$e->getMessage());
        }
    }
}

