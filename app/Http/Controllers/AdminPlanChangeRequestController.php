<?php

namespace App\Http\Controllers;

use App\Mail\PlanChangeCreditDebitNoteMail;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\GstVerification;
use App\Models\Invoice;
use App\Models\IxLocation;
use App\Models\Message;
use App\Models\PlanChangeHistory;
use App\Models\PlanChangeRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AdminPlanChangeRequestController extends Controller
{
    /**
     * Display list of plan change requests.
     */
    public function index(Request $request)
    {
        try {
            $adminId = session('admin_id');
            $admin = Admin::find($adminId);

            if (! $admin) {
                return redirect()->route('admin.login')
                    ->with('error', 'Admin session expired. Please login again.');
            }

            $query = PlanChangeRequest::with(['application.user', 'user', 'reviewedBy']);

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by change type
            if ($request->filled('change_type')) {
                $query->where('change_type', $request->change_type);
            }

            $requests = $query->latest()->paginate(20)->withQueryString();

            return view('admin.plan-change.index', compact('admin', 'requests'));
        } catch (Exception $e) {
            Log::error('Error loading plan change requests: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load plan change requests.');
        }
    }

    /**
     * Show plan change request details.
     */
    public function show($id)
    {
        try {
            $adminId = session('admin_id');
            $admin = Admin::find($adminId);

            if (! $admin) {
                return redirect()->route('admin.login')
                    ->with('error', 'Admin session expired. Please login again.');
            }

            $request = PlanChangeRequest::with(['application.user', 'user', 'reviewedBy', 'history'])
                ->findOrFail($id);

            return view('admin.plan-change.show', compact('admin', 'request'));
        } catch (Exception $e) {
            Log::error('Error loading plan change request details: '.$e->getMessage());

            return redirect()->route('admin.plan-change.index')
                ->with('error', 'Unable to load plan change request details.');
        }
    }

    /**
     * Approve plan change request.
     */
    public function approve(Request $request, $id)
    {
        try {
            $adminId = session('admin_id');
            $admin = Admin::find($adminId);

            if (! $admin) {
                return redirect()->route('admin.login')
                    ->with('error', 'Admin session expired. Please login again.');
            }

            $planChangeRequest = PlanChangeRequest::with('application')->findOrFail($id);

            if ($planChangeRequest->status !== 'pending') {
                return back()->with('error', 'This request has already been processed.');
            }

            $validated = $request->validate([
                'admin_notes' => 'nullable|string|max:1000',
                // Allow admin to select past dates as well
                'effective_from' => 'nullable|date',
            ]);

            DB::beginTransaction();

            $application = $planChangeRequest->application;
            $appData = $application->application_data ?? [];

            // Determine effective_from date
            // For billing cycle changes: effective after current paid period ends
            // For capacity changes: can be immediate or future date
            $effectiveFrom = $validated['effective_from']
                ? \Carbon\Carbon::parse($validated['effective_from'])
                : now('Asia/Kolkata');

            // Find the last paid invoice to determine current paid period
            $lastPaidInvoice = Invoice::where('application_id', $application->id)
                ->where('status', 'paid')
                ->latest('invoice_date')
                ->first();

            // If it's a billing cycle change only (no capacity change),
            // set effective_from to after current paid period
            if ($planChangeRequest->isBillingCycleChangeOnly()) {
                if ($lastPaidInvoice && $lastPaidInvoice->due_date) {
                    // Billing cycle change takes effect after current paid period
                    $effectiveFrom = \Carbon\Carbon::parse($lastPaidInvoice->due_date)->addDay();
                    Log::info("Billing cycle change for application {$application->id}: will take effect after paid period ends on {$effectiveFrom->format('Y-m-d')}");
                }
            }

            // Recalculate adjustment_amount if this is a capacity change that takes effect mid-cycle
            $adjustedAmount = $planChangeRequest->adjustment_amount;
            if ($planChangeRequest->isCapacityChange() && $lastPaidInvoice && $lastPaidInvoice->billing_start_date && $lastPaidInvoice->billing_end_date) {
                $billingStart = \Carbon\Carbon::parse($lastPaidInvoice->billing_start_date);
                $billingEnd = \Carbon\Carbon::parse($lastPaidInvoice->billing_end_date);

                // Check if the change takes effect during the paid period
                if ($effectiveFrom->gte($billingStart) && $effectiveFrom->lt($billingEnd)) {
                    // Calculate remaining days in the paid period
                    $remainingDays = $effectiveFrom->diffInDays($billingEnd) + 1; // +1 to include the effective date
                    $totalDays = $billingStart->diffInDays($billingEnd) + 1; // +1 to include both start and end dates

                    if ($totalDays > 0) {
                        // Get billing cycle days for the plan change request billing plans
                        $currentBillingCycleDays = $this->getBillingCycleDays($planChangeRequest->current_billing_plan);
                        $newBillingCycleDays = $this->getBillingCycleDays($planChangeRequest->new_billing_plan);

                        // Calculate daily rates based on the billing cycle of each plan
                        // current_amount and new_amount are for their respective billing cycles
                        $currentDailyRate = $planChangeRequest->current_amount / $currentBillingCycleDays;
                        $newDailyRate = $planChangeRequest->new_amount / $newBillingCycleDays;

                        // Calculate the difference per day
                        $dailyDifference = $currentDailyRate - $newDailyRate;

                        // Calculate adjustment for remaining days only
                        // For downgrades: dailyDifference is positive, we want negative adjustment (credit)
                        // For upgrades: dailyDifference is negative, we want positive adjustment (debit)
                        // Negate to get correct sign: credit should be negative, debit should be positive
                        $adjustedAmount = round(-($dailyDifference * $remainingDays), 2);

                        Log::info("Prorated adjustment for application {$application->id}: Change effective on {$effectiveFrom->format('Y-m-d')}, remaining days: {$remainingDays} of {$totalDays} days. Current plan daily rate: ₹{$currentDailyRate} (₹{$planChangeRequest->current_amount}/{$currentBillingCycleDays} days), New plan daily rate: ₹{$newDailyRate} (₹{$planChangeRequest->new_amount}/{$newBillingCycleDays} days), Daily difference: ₹{$dailyDifference}, Prorated adjustment: ₹{$adjustedAmount} (".($adjustedAmount < 0 ? 'credit' : 'debit').')');
                    }
                }
            }

            // Update request status with recalculated adjustment
            $planChangeRequest->update([
                'status' => 'approved',
                'admin_notes' => $validated['admin_notes'] ?? null,
                'reviewed_by' => $adminId,
                'reviewed_at' => now('Asia/Kolkata'),
                'effective_from' => $effectiveFrom,
                'adjustment_amount' => $adjustedAmount,
            ]);

            // DO NOT update application immediately - it will be auto-updated when effective_from date arrives
            // This allows users to see when their plan will change and prevents multiple changes before effective date
            if ($planChangeRequest->isCapacityChange()) {
                Log::info("Plan change approved for application {$application->id}: {$planChangeRequest->current_port_capacity} → {$planChangeRequest->new_port_capacity} will take effect on {$effectiveFrom->format('Y-m-d')}. Application will be auto-updated on that date.");
            } else {
                Log::info("Billing cycle change approved for application {$application->id}: new cycle '{$planChangeRequest->new_billing_plan}' will be used after {$effectiveFrom->format('Y-m-d')}");
            }

            // Log application status history
            ApplicationStatusHistory::log(
                $application->id,
                $application->status,
                $application->status, // Status remains same
                'admin',
                $adminId,
                "Plan change approved: {$planChangeRequest->current_port_capacity} ({$planChangeRequest->current_billing_plan}) → {$planChangeRequest->new_port_capacity} ({$planChangeRequest->new_billing_plan}). Adjustment: ₹".number_format($adjustedAmount, 2)." (effective from {$effectiveFrom->format('Y-m-d')})"
            );

            // Log plan change history
            PlanChangeHistory::create([
                'plan_change_request_id' => $planChangeRequest->id,
                'application_id' => $application->id,
                'old_data' => [
                    'port_capacity' => $planChangeRequest->current_port_capacity,
                    'billing_plan' => $planChangeRequest->current_billing_plan,
                    'amount' => $planChangeRequest->current_amount,
                ],
                'new_data' => [
                    'port_capacity' => $planChangeRequest->new_port_capacity,
                    'billing_plan' => $planChangeRequest->new_billing_plan,
                    'amount' => $planChangeRequest->new_amount,
                ],
                'action' => 'approved',
                'performed_by' => "Admin: {$admin->name}",
                'notes' => $validated['admin_notes'] ?? 'Plan change approved by admin.',
            ]);

            // Generate and send credit/debit note if there's an adjustment
            $notePdfPath = null;
            $noteNumber = null;
            if ($adjustedAmount != 0 && $planChangeRequest->isCapacityChange()) {
                try {
                    $noteData = $this->generateCreditDebitNote($planChangeRequest, $application, $adjustedAmount, $effectiveFrom, $lastPaidInvoice);
                    $notePdfPath = $noteData['pdf_path'];
                    $noteNumber = $noteData['note_number'];

                    // Send credit/debit note email
                    $user = $application->user;
                    $applicationData = $application->application_data ?? [];
                    $authorizedPersonName = $application->authorized_representative_details['name']
                        ?? $applicationData['representative']['name']
                        ?? $user->fullname;

                    Mail::to($user->email)->send(new PlanChangeCreditDebitNoteMail(
                        $user->fullname,
                        $application->application_id,
                        $noteNumber,
                        $adjustedAmount < 0 ? 'credit' : 'debit',
                        abs($adjustedAmount),
                        $notePdfPath,
                        $authorizedPersonName,
                        $user->fullname
                    ));

                    Log::info("Credit/Debit note sent to {$user->email} for plan change request {$planChangeRequest->id}");
                } catch (Exception $e) {
                    Log::error('Error generating/sending credit/debit note: '.$e->getMessage());
                    // Continue even if note generation fails
                }
            }

            // Send message to user
            $adjustmentMessage = '';
            if ($adjustedAmount != 0) {
                if ($adjustedAmount < 0) {
                    $adjustmentMessage = 'Credit Amount: ₹'.number_format(abs($adjustedAmount), 2).' (will be applied to your next invoice). ';
                } else {
                    $adjustmentMessage = 'Additional Amount: ₹'.number_format($adjustedAmount, 2).' (will be charged in your next invoice). ';
                }
            }

            $messageText = "Your plan change request for application {$application->application_id} has been approved. New plan: {$planChangeRequest->new_port_capacity} ({$planChangeRequest->new_billing_plan}). Effective from: {$effectiveFrom->format('d M Y')}. {$adjustmentMessage}";
            if ($noteNumber) {
                $messageText .= ' A '.($adjustedAmount < 0 ? 'credit' : 'debit')." note ({$noteNumber}) has been generated and sent to your email.";
            }
            $messageText .= ($validated['admin_notes'] ?? '');

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Plan Change Approved - '.$application->application_id,
                'message' => $messageText,
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            DB::commit();

            return redirect()->route('admin.plan-change.show', $id)
                ->with('success', 'Plan change request approved successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error approving plan change request: '.$e->getMessage());

            return back()->with('error', 'Failed to approve plan change request. Please try again.');
        }
    }

    /**
     * Reject plan change request.
     */
    public function reject(Request $request, $id)
    {
        try {
            $adminId = session('admin_id');
            $admin = Admin::find($adminId);

            if (! $admin) {
                return redirect()->route('admin.login')
                    ->with('error', 'Admin session expired. Please login again.');
            }

            $planChangeRequest = PlanChangeRequest::with('application')->findOrFail($id);

            if ($planChangeRequest->status !== 'pending') {
                return back()->with('error', 'This request has already been processed.');
            }

            $validated = $request->validate([
                'admin_notes' => 'required|string|min:10|max:1000',
            ]);

            DB::beginTransaction();

            // Update request status
            $planChangeRequest->update([
                'status' => 'rejected',
                'admin_notes' => $validated['admin_notes'],
                'reviewed_by' => $adminId,
                'reviewed_at' => now('Asia/Kolkata'),
            ]);

            // Log plan change history
            PlanChangeHistory::create([
                'plan_change_request_id' => $planChangeRequest->id,
                'application_id' => $planChangeRequest->application_id,
                'old_data' => [
                    'port_capacity' => $planChangeRequest->current_port_capacity,
                    'billing_plan' => $planChangeRequest->current_billing_plan,
                    'amount' => $planChangeRequest->current_amount,
                ],
                'new_data' => null,
                'action' => 'rejected',
                'performed_by' => "Admin: {$admin->name}",
                'notes' => $validated['admin_notes'],
            ]);

            // Send message to user
            $application = $planChangeRequest->application;
            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Plan Change Request Rejected - '.$application->application_id,
                'message' => "Your plan change request for application {$application->application_id} has been rejected. Reason: {$validated['admin_notes']}",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            DB::commit();

            return redirect()->route('admin.plan-change.show', $id)
                ->with('success', 'Plan change request rejected.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting plan change request: '.$e->getMessage());

            return back()->with('error', 'Failed to reject plan change request. Please try again.');
        }
    }

    /**
     * Delete plan change request.
     */
    public function destroy($id)
    {
        try {
            $adminId = session('admin_id');
            $admin = Admin::find($adminId);

            if (! $admin) {
                return redirect()->route('admin.login')
                    ->with('error', 'Admin session expired. Please login again.');
            }

            $planChangeRequest = PlanChangeRequest::with('application')->findOrFail($id);

            // Allow deletion of pending and approved requests
            if (! in_array($planChangeRequest->status, ['pending', 'approved'])) {
                return back()->with('error', 'Only pending or approved plan change requests can be deleted.');
            }

            DB::beginTransaction();

            $applicationId = $planChangeRequest->application_id;
            $application = $planChangeRequest->application;

            // If approved and already applied, check if we need to revert
            if ($planChangeRequest->status === 'approved' && $planChangeRequest->effective_from) {
                $effectiveDate = \Carbon\Carbon::parse($planChangeRequest->effective_from);
                $now = now('Asia/Kolkata');

                // If the plan change has already taken effect, check if application was updated
                if ($effectiveDate->lte($now) && $planChangeRequest->isCapacityChange()) {
                    $currentCapacity = $application->assigned_port_capacity ?? ($application->application_data['port_selection']['capacity'] ?? null);

                    // If the application has been updated to the new capacity, revert it
                    if ($currentCapacity === $planChangeRequest->new_port_capacity) {
                        $appData = $application->application_data ?? [];
                        $appData['port_selection'] = [
                            'capacity' => $planChangeRequest->current_port_capacity,
                            'billing_plan' => $planChangeRequest->current_billing_plan ?? ($appData['port_selection']['billing_plan'] ?? 'monthly'),
                            'amount' => $planChangeRequest->current_amount,
                            'currency' => 'INR',
                        ];

                        $application->update([
                            'application_data' => $appData,
                            'assigned_port_capacity' => $planChangeRequest->current_port_capacity,
                        ]);

                        Log::info("Reverted application {$application->id} capacity from {$planChangeRequest->new_port_capacity} back to {$planChangeRequest->current_port_capacity} after deleting approved plan change request.");
                    }
                }

                // If billing cycle was changed and already applied, revert it
                if ($effectiveDate->lte($now) && $planChangeRequest->isBillingCycleChangeOnly()) {
                    $currentBillingCycle = $application->billing_cycle ?? ($application->application_data['port_selection']['billing_plan'] ?? null);

                    if ($currentBillingCycle === $planChangeRequest->new_billing_plan) {
                        $application->update([
                            'billing_cycle' => $planChangeRequest->current_billing_plan,
                        ]);

                        Log::info("Reverted application {$application->id} billing cycle from {$planChangeRequest->new_billing_plan} back to {$planChangeRequest->current_billing_plan} after deleting approved plan change request.");
                    }
                }

                // If adjustment was applied to an invoice, mark it as not applied
                if ($planChangeRequest->adjustment_applied) {
                    $planChangeRequest->update([
                        'adjustment_applied' => false,
                        'adjustment_invoice_id' => null,
                    ]);
                    Log::info("Marked adjustment as not applied for deleted plan change request {$planChangeRequest->id}");
                }
            }

            // Delete related history
            \App\Models\PlanChangeHistory::where('plan_change_request_id', $planChangeRequest->id)->delete();

            // Delete the request
            $planChangeRequest->delete();

            // Log application status history
            ApplicationStatusHistory::log(
                $applicationId,
                $application->status,
                $application->status,
                'admin',
                $adminId,
                'Plan change request deleted by admin. User can now apply for a new plan change.'
            );

            DB::commit();

            return redirect()->route('admin.plan-change.index')
                ->with('success', 'Plan change request deleted successfully. User can now apply for a new plan change.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting plan change request: '.$e->getMessage());

            return back()->with('error', 'Failed to delete plan change request. Please try again.');
        }
    }

    /**
     * Get billing cycle days for a billing plan.
     */
    private function getBillingCycleDays(?string $billingPlan): int
    {
        $plan = strtolower(trim($billingPlan ?? 'monthly'));

        return match ($plan) {
            'annual', 'arc' => 365,
            'quarterly' => 90,
            'monthly', 'mrc' => 30,
            default => 30,
        };
    }

    /**
     * Generate credit/debit note PDF for plan change adjustment.
     */
    private function generateCreditDebitNote(
        PlanChangeRequest $planChangeRequest,
        Application $application,
        float $adjustedAmount,
        \Carbon\Carbon $effectiveFrom,
        ?Invoice $lastPaidInvoice
    ): array {
        $noteType = $adjustedAmount < 0 ? 'credit' : 'debit';
        $noteNumber = strtoupper($noteType).'-'.date('Y').'-'.str_pad($planChangeRequest->id, 6, '0', STR_PAD_LEFT);

        $user = $application->user;
        $applicationData = $application->application_data ?? [];

        // Get buyer details
        $buyerDetails = [
            'name' => $user->fullname,
            'email' => $user->email,
            'phone' => $user->mobile ?? null,
        ];

        // Get GST details
        $gstVerification = GstVerification::where('user_id', $user->id)
            ->where('is_verified', true)
            ->latest()
            ->first();

        if ($gstVerification) {
            $buyerDetails['gstin'] = $gstVerification->gstin;
            $buyerDetails['company_name'] = $gstVerification->legal_name ?? $gstVerification->trade_name ?? $user->fullname;
            $buyerDetails['address'] = $gstVerification->primary_address ?? null;
        } else {
            // Try to get from KYC
            $kyc = \App\Models\UserKycProfile::where('user_id', $user->id)
                ->where('status', 'completed')
                ->first();
            if ($kyc) {
                $buyerDetails['company_name'] = $kyc->company_name ?? $user->fullname;
                $buyerDetails['address'] = $kyc->registered_address ?? null;
            }
        }

        // Get location for GST calculation
        $locationId = $applicationData['location']['id'] ?? null;
        $location = $locationId ? IxLocation::find($locationId) : null;
        $gstState = $location->state ?? ($gstVerification?->state);
        $isDelhi = strtolower($gstState ?? '') === 'delhi' || strtolower($gstState ?? '') === 'new delhi';

        // Calculate GST
        $baseAmount = abs($adjustedAmount);
        if ($isDelhi) {
            $cgstAmount = round(($baseAmount * 9) / 100, 2);
            $sgstAmount = round(($baseAmount * 9) / 100, 2);
            $gstAmount = $cgstAmount + $sgstAmount;
        } else {
            $gstAmount = round(($baseAmount * 18) / 100, 2);
        }

        $totalAmount = $baseAmount + $gstAmount;

        // Calculate remaining days if applicable
        $remainingDays = 0;
        $totalDays = 0;
        if ($lastPaidInvoice && $lastPaidInvoice->billing_start_date && $lastPaidInvoice->billing_end_date) {
            $billingStart = \Carbon\Carbon::parse($lastPaidInvoice->billing_start_date);
            $billingEnd = \Carbon\Carbon::parse($lastPaidInvoice->billing_end_date);
            if ($effectiveFrom->gte($billingStart) && $effectiveFrom->lt($billingEnd)) {
                $remainingDays = $effectiveFrom->diffInDays($billingEnd);
                $totalDays = $billingStart->diffInDays($billingEnd);
            }
        }

        // Generate PDF
        $pdf = Pdf::loadView('admin.plan-change.pdf.credit-debit-note', [
            'noteType' => $noteType,
            'noteNumber' => $noteNumber,
            'noteDate' => now('Asia/Kolkata')->format('d/m/Y'),
            'applicationId' => $application->application_id,
            'changeType' => $planChangeRequest->change_type,
            'currentPortCapacity' => $planChangeRequest->current_port_capacity,
            'newPortCapacity' => $planChangeRequest->new_port_capacity,
            'currentBillingPlan' => strtoupper($planChangeRequest->current_billing_plan),
            'newBillingPlan' => strtoupper($planChangeRequest->new_billing_plan),
            'effectiveFrom' => $effectiveFrom->format('d/m/Y'),
            'remainingDays' => $remainingDays,
            'totalDays' => $totalDays,
            'referenceInvoiceNumber' => $lastPaidInvoice?->invoice_number ?? 'N/A',
            'reason' => $planChangeRequest->reason ?? 'Plan Change Adjustment',
            'buyerDetails' => $buyerDetails,
            'baseAmount' => $baseAmount,
            'gstAmount' => $gstAmount,
            'totalAmount' => $totalAmount,
        ])->setPaper('a4', 'portrait')
            ->setOption('enable-local-file-access', true);

        // Store PDF
        $pdfPath = 'plan-changes/'.$user->id.'/'.$noteNumber.'.pdf';
        Storage::disk('public')->put($pdfPath, $pdf->output());

        return [
            'note_number' => $noteNumber,
            'pdf_path' => $pdfPath,
        ];
    }
}
