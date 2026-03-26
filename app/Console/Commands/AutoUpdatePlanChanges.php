<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\Message;
use App\Models\PlanChangeRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoUpdatePlanChanges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plan-changes:auto-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically update applications when plan change effective_from date arrives';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $now = now('Asia/Kolkata');
            $today = $now->format('Y-m-d');
            
            // Find all approved plan changes where effective_from is today or in the past
            // and the application hasn't been updated yet
            $planChangesToApply = PlanChangeRequest::where('status', 'approved')
                ->whereNotNull('effective_from')
                ->whereDate('effective_from', '<=', $today)
                ->with('application')
                ->get()
                ->filter(function ($change) {
                    // Only process if the application hasn't been updated to the new capacity yet
                    $application = $change->application;
                    if (!$application) {
                        return false;
                    }
                    
                    if ($change->isCapacityChange()) {
                        // Check if capacity hasn't been updated yet
                        $currentCapacity = $application->assigned_port_capacity ?? ($application->application_data['port_selection']['capacity'] ?? null);
                        return $currentCapacity !== $change->new_port_capacity;
                    } else {
                        // For billing cycle changes, check if billing_cycle hasn't been updated
                        $currentBillingCycle = $application->billing_cycle ?? ($application->application_data['port_selection']['billing_plan'] ?? null);
                        return $currentBillingCycle !== $change->new_billing_plan;
                    }
                });

            $updatedCount = 0;

            foreach ($planChangesToApply as $planChange) {
                DB::beginTransaction();
                try {
                    $application = $planChange->application;
                    $appData = $application->application_data ?? [];

                    if ($planChange->isCapacityChange()) {
                        // Update capacity
                        $appData['port_selection'] = [
                            'capacity' => $planChange->new_port_capacity,
                            'billing_plan' => $planChange->new_billing_plan ?? ($appData['port_selection']['billing_plan'] ?? 'monthly'),
                            'amount' => $planChange->new_amount,
                            'currency' => 'INR',
                        ];

                        $application->update([
                            'application_data' => $appData,
                            'assigned_port_capacity' => $planChange->new_port_capacity,
                        ]);

                        // Send notification to user
                        Message::create([
                            'user_id' => $application->user_id,
                            'subject' => 'Plan Change Applied',
                            'message' => "Your plan change for application {$application->application_id} has been automatically applied. Port capacity updated from {$planChange->current_port_capacity} to {$planChange->new_port_capacity}.",
                            'is_read' => false,
                            'sent_by' => 'system',
                        ]);

                        Log::info("Auto-updated application {$application->id}: capacity changed from {$planChange->current_port_capacity} to {$planChange->new_port_capacity}");
                    } else {
                        // Update billing cycle only
                        $application->update([
                            'billing_cycle' => $planChange->new_billing_plan,
                        ]);

                        // Send notification to user
                        Message::create([
                            'user_id' => $application->user_id,
                            'subject' => 'Billing Cycle Updated',
                            'message' => "Your billing cycle for application {$application->application_id} has been automatically updated from {$planChange->current_billing_plan} to {$planChange->new_billing_plan}.",
                            'is_read' => false,
                            'sent_by' => 'system',
                        ]);

                        Log::info("Auto-updated application {$application->id}: billing cycle changed from {$planChange->current_billing_plan} to {$planChange->new_billing_plan}");
                    }

                    DB::commit();
                    $updatedCount++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Error auto-updating plan change {$planChange->id}: {$e->getMessage()}");
                }
            }

            $this->info("Plan changes auto-updated: {$updatedCount}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Error in auto-update plan changes command: '.$e->getMessage());
            $this->error('Error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}

