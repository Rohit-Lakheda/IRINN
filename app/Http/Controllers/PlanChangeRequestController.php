<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\IxLocation;
use App\Models\IxPortPricing;
use App\Models\Message;
use App\Models\PlanChangeHistory;
use App\Models\PlanChangeRequest;
use App\Models\Registration;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlanChangeRequestController extends Controller
{
    /**
     * Display plan change request form.
     */
    public function create($applicationId)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (!$user) {
                return redirect()->route('login.index')
                    ->with('error', 'User session expired. Please login again.');
            }

            $application = Application::where('id', $applicationId)
                ->where('user_id', $userId)
                ->where('application_type', 'IX')
                ->firstOrFail();

            // Check if application is approved and has assigned port
            if (!$application->assigned_port_capacity) {
                return redirect()->route('user.applications.show', $applicationId)
                    ->with('error', 'Port capacity must be assigned before requesting a plan change.');
            }

            // Check if there's already a pending request
            $pendingRequest = PlanChangeRequest::where('application_id', $applicationId)
                ->where('status', 'pending')
                ->first();

            if ($pendingRequest) {
                return redirect()->route('user.applications.show', $applicationId)
                    ->with('error', 'You already have a pending plan change request for this application.');
            }

            // Check if there's an approved request that hasn't taken effect yet
            $approvedNotEffective = PlanChangeRequest::where('application_id', $applicationId)
                ->where('status', 'approved')
                ->whereNotNull('effective_from')
                ->where('effective_from', '>', now('Asia/Kolkata'))
                ->latest('effective_from')
                ->first();

            if ($approvedNotEffective) {
                $effectiveDate = \Carbon\Carbon::parse($approvedNotEffective->effective_from)->format('d/m/Y');
                return redirect()->route('user.applications.show', $applicationId)
                    ->with('error', "You cannot request a new plan change until your approved plan change takes effect on {$effectiveDate}. Your plan will be automatically updated from {$approvedNotEffective->current_port_capacity} to {$approvedNotEffective->new_port_capacity} on that date.");
            }

            // Get current port details
            $appData = $application->application_data ?? [];
            $portSelection = $appData['port_selection'] ?? [];
            $locationData = $appData['location'] ?? [];

            // Check if there's an approved request that hasn't taken effect yet
            $approvedNotEffective = PlanChangeRequest::where('application_id', $applicationId)
                ->where('status', 'approved')
                ->whereNotNull('effective_from')
                ->where('effective_from', '>', now('Asia/Kolkata'))
                ->latest('effective_from')
                ->first();

            // Get available port capacities (only those with valid pricing)
            $nodeType = $locationData['node_type'] ?? 'edge';
            $availablePorts = IxPortPricing::where('node_type', $nodeType)
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get()
                ->filter(function ($pricing) {
                    // Only include port capacities that have at least one valid pricing plan
                    return $pricing->hasAnyPricing();
                });

            return view('user.plan-change.create', compact('application', 'portSelection', 'availablePorts', 'approvedNotEffective'));
        } catch (Exception $e) {
            Log::error('Error loading plan change form: '.$e->getMessage());

            return redirect()->route('user.applications.index')
                ->with('error', 'Unable to load plan change form.');
        }
    }

    /**
     * Store plan change request.
     */
    public function store(Request $request, $applicationId)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (!$user) {
                return redirect()->route('login.index')
                    ->with('error', 'User session expired. Please login again.');
            }

            $application = Application::where('id', $applicationId)
                ->where('user_id', $userId)
                ->where('application_type', 'IX')
                ->firstOrFail();

            $validated = $request->validate([
                'new_port_capacity' => 'required|string',
                'new_billing_plan' => 'required|in:arc,mrc,quarterly',
                'reason' => 'required|string|min:10|max:500',
            ]);

            // Check if there's an approved request that hasn't taken effect yet
            $approvedNotEffective = PlanChangeRequest::where('application_id', $applicationId)
                ->where('status', 'approved')
                ->whereNotNull('effective_from')
                ->where('effective_from', '>', now('Asia/Kolkata'))
                ->latest('effective_from')
                ->first();

            if ($approvedNotEffective) {
                $effectiveDate = \Carbon\Carbon::parse($approvedNotEffective->effective_from)->format('d/m/Y');
                return back()->withInput()
                    ->with('error', "You cannot request a new plan change until your approved plan change takes effect on {$effectiveDate}. Your plan will be automatically updated from {$approvedNotEffective->current_port_capacity} to {$approvedNotEffective->new_port_capacity} on that date.");
            }

            // Get current port details
            $appData = $application->application_data ?? [];
            $portSelection = $appData['port_selection'] ?? [];
            $locationData = $appData['location'] ?? [];
            $nodeType = $locationData['node_type'] ?? 'edge';

            $currentPortCapacity = $application->assigned_port_capacity ?? $portSelection['capacity'] ?? null;
            $currentBillingPlan = $portSelection['billing_plan'] ?? null;
            $currentAmount = $portSelection['amount'] ?? 0;

            // Get new pricing
            $newPricing = IxPortPricing::where('node_type', $nodeType)
                ->where('port_capacity', $validated['new_port_capacity'])
                ->where('is_active', true)
                ->firstOrFail();

            $newAmount = $newPricing->getAmountForPlan($validated['new_billing_plan']);
            
            // Validate that pricing exists for the selected plan
            if ($newAmount === null) {
                return back()->withInput()
                    ->with('error', "Pricing is not available for port capacity '{$validated['new_port_capacity']}' with billing plan '{$validated['new_billing_plan']}'. Please select a different combination.");
            }

            // Determine change type
            $changeType = $this->comparePortCapacity($currentPortCapacity, $validated['new_port_capacity']);

            // Calculate adjustment amount
            $adjustmentAmount = $newAmount - $currentAmount;

            DB::beginTransaction();

            // Create plan change request
            $planChangeRequest = PlanChangeRequest::create([
                'application_id' => $application->id,
                'user_id' => $userId,
                'current_port_capacity' => $currentPortCapacity,
                'new_port_capacity' => $validated['new_port_capacity'],
                'current_billing_plan' => $currentBillingPlan,
                'new_billing_plan' => $validated['new_billing_plan'],
                'current_amount' => $currentAmount,
                'new_amount' => $newAmount,
                'adjustment_amount' => $adjustmentAmount,
                'change_type' => $changeType,
                'status' => 'pending',
                'reason' => $validated['reason'],
            ]);

            // Log history
            PlanChangeHistory::create([
                'plan_change_request_id' => $planChangeRequest->id,
                'application_id' => $application->id,
                'old_data' => [
                    'port_capacity' => $currentPortCapacity,
                    'billing_plan' => $currentBillingPlan,
                    'amount' => $currentAmount,
                ],
                'new_data' => [
                    'port_capacity' => $validated['new_port_capacity'],
                    'billing_plan' => $validated['new_billing_plan'],
                    'amount' => $newAmount,
                ],
                'action' => 'requested',
                'performed_by' => "User: {$user->fullname}",
                'notes' => $validated['reason'],
            ]);

            // Send message to user
            Message::create([
                'user_id' => $userId,
                'subject' => 'Plan Change Request Submitted',
                'message' => "Your plan change request for application {$application->application_id} has been submitted successfully. Current: {$currentPortCapacity} ({$currentBillingPlan}), Requested: {$validated['new_port_capacity']} ({$validated['new_billing_plan']}). Adjustment Amount: ₹" . number_format($adjustmentAmount, 2) . ". Your request is pending admin approval.",
                'is_read' => false,
                'sent_by' => 'system',
            ]);

            DB::commit();

            return redirect()->route('user.applications.show', $applicationId)
                ->with('success', 'Plan change request submitted successfully. It will be reviewed by admin.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error submitting plan change request: '.$e->getMessage());

            return back()->withInput()
                ->with('error', 'Failed to submit plan change request. Please try again.');
        }
    }

    /**
     * Compare port capacities to determine upgrade/downgrade.
     */
    private function comparePortCapacity(?string $current, string $new): string
    {
        if (!$current) {
            return 'upgrade';
        }

        // Extract numeric values (e.g., "10Gig" -> 10, "100Gig" -> 100)
        $currentValue = $this->extractPortValue($current);
        $newValue = $this->extractPortValue($new);

        return $newValue > $currentValue ? 'upgrade' : 'downgrade';
    }

    /**
     * Extract numeric value from port capacity string.
     */
    private function extractPortValue(string $capacity): int
    {
        // Extract numbers from strings like "10Gig", "100Gig", "1Gig"
        preg_match('/(\d+)/', $capacity, $matches);
        return isset($matches[1]) ? (int) $matches[1] : 0;
    }
}
