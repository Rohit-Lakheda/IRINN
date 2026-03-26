<?php

namespace App\Http\Controllers;

use App\Mail\IxReactivationInvoiceMail;
use App\Models\Admin;
use App\Models\ApplicationReactivationRequest;
use App\Models\ReactivationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminReactivationRequestController extends Controller
{
    public function index(Request $request)
    {
        try {
            $adminId = session('admin_id');
            $admin = Admin::with('roles')->findOrFail($adminId);

            $status = $request->string('status', 'pending')->toString();
            $search = $request->string('q')->toString();
            $requestedFrom = $request->string('requested_from')->toString();
            $requestedTo = $request->string('requested_to')->toString();
            $serviceStatus = $request->string('service_status', 'all')->toString();
            $paymentStatus = $request->string('payment_status', 'all')->toString();

            $query = ApplicationReactivationRequest::query()
                ->with(['application.user', 'invoice'])
                ->orderBy('created_at', 'desc');

            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            if ($serviceStatus && $serviceStatus !== 'all') {
                $query->whereHas('application', function ($q) use ($serviceStatus) {
                    $q->where('service_status', $serviceStatus);
                });
            }

            if ($paymentStatus && $paymentStatus !== 'all') {
                if ($paymentStatus === 'none') {
                    $query->whereNull('invoice_id');
                } else {
                    $query->whereHas('invoice', function ($q) use ($paymentStatus) {
                        $q->where('payment_status', $paymentStatus);
                    });
                }
            }

            if ($requestedFrom !== '') {
                $query->whereDate('created_at', '>=', $requestedFrom);
            }

            if ($requestedTo !== '') {
                $query->whereDate('created_at', '<=', $requestedTo);
            }

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('application', function ($appQ) use ($search) {
                        $appQ->where('application_id', 'like', '%'.$search.'%')
                            ->orWhere('membership_id', 'like', '%'.$search.'%');
                    })
                        ->orWhereHas('application.user', function ($userQ) use ($search) {
                            $userQ->where('fullname', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%')
                                ->orWhere('mobile', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('invoice', function ($invQ) use ($search) {
                            $invQ->where('invoice_number', 'like', '%'.$search.'%');
                        });
                });
            }

            $requests = $query->paginate(20)->withQueryString();

            return view('admin.reactivation-requests.index', compact(
                'admin',
                'requests',
                'status',
                'search',
                'requestedFrom',
                'requestedTo',
                'serviceStatus',
                'paymentStatus'
            ));
        } catch (\Exception $e) {
            Log::error('Error loading reactivation requests: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load reactivation requests.');
        }
    }

    public function approve(Request $request, $id)
    {
        try {
            $adminId = session('admin_id');
            $admin = Admin::with('roles')->findOrFail($adminId);

            if (! $admin->hasRole('ix_account')) {
                return back()->with('error', 'Only IX Account can approve reactivation requests.');
            }

            $validated = $request->validate([
                'admin_notes' => 'nullable|string|max:2000',
            ]);

            $reactivationRequest = ApplicationReactivationRequest::with(['application.user'])->findOrFail($id);

            if ($reactivationRequest->status !== 'pending') {
                return back()->with('error', 'This reactivation request has already been processed.');
            }

            $application = $reactivationRequest->application;
            if (($application->service_status ?? 'live') !== 'disconnected') {
                return back()->with('error', 'Reactivation is only available for disconnected applications.');
            }

            $setting = ReactivationSetting::current();
            $feeAmount = (float) $setting->fee_amount;

            if ($feeAmount <= 0) {
                return back()->with('error', 'Reactivation fee is not configured. Please ask Super Admin to set it.');
            }

            // Create reactivation invoice
            $invoice = app(AdminController::class)->createReactivationInvoiceForApplication($application, $admin, $feeAmount);

            $reactivationRequest->update([
                'status' => 'invoiced',
                'approved_by' => $admin->id,
                'approved_at' => now('Asia/Kolkata'),
                'admin_notes' => $validated['admin_notes'] ?? null,
                'invoice_id' => $invoice->id,
            ]);

            // Send mail to user
            try {
                Mail::to($application->user->email)->send(new IxReactivationInvoiceMail(
                    userName: $application->user->fullname,
                    applicationId: $application->application_id,
                    invoiceNumber: $invoice->invoice_number,
                    totalAmount: (float) $invoice->total_amount,
                    invoicePdfPath: $invoice->pdf_path,
                ));
            } catch (\Exception $e) {
                Log::error('Error sending reactivation invoice email: '.$e->getMessage());
            }

            return back()->with('success', 'Reactivation request approved and invoice generated.');
        } catch (\Exception $e) {
            Log::error('Error approving reactivation request: '.$e->getMessage());

            return back()->with('error', 'Unable to approve reactivation request. Please try again.');
        }
    }

    public function reject(Request $request, $id)
    {
        try {
            $adminId = session('admin_id');
            Admin::findOrFail($adminId);

            $validated = $request->validate([
                'admin_notes' => 'nullable|string|max:2000',
            ]);

            $reactivationRequest = ApplicationReactivationRequest::findOrFail($id);

            if ($reactivationRequest->status !== 'pending') {
                return back()->with('error', 'This reactivation request has already been processed.');
            }

            $reactivationRequest->update([
                'status' => 'rejected',
                'approved_by' => $adminId,
                'approved_at' => now('Asia/Kolkata'),
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);

            return back()->with('success', 'Reactivation request rejected.');
        } catch (\Exception $e) {
            Log::error('Error rejecting reactivation request: '.$e->getMessage());

            return back()->with('error', 'Unable to reject reactivation request.');
        }
    }

    public function setReactivationDate(Request $request, $id)
    {
        try {
            $adminId = session('admin_id');
            $admin = Admin::with('roles')->findOrFail($adminId);

            if (! $admin->hasRole('ix_account')) {
                return back()->with('error', 'Only IX Account can set reactivation date.');
            }

            $validated = $request->validate([
                'reactivation_date' => 'required|date',
            ]);

            $reactivationRequest = ApplicationReactivationRequest::with(['application', 'invoice'])->findOrFail($id);

            if (! in_array($reactivationRequest->status, ['paid', 'invoiced'], true)) {
                return back()->with('error', 'Reactivation date can only be set after invoice is generated (and ideally after payment).');
            }

            $invoice = $reactivationRequest->invoice;
            if ($invoice && $invoice->payment_status !== 'paid') {
                return back()->with('error', 'Please wait until the reactivation invoice is paid.');
            }

            $application = $reactivationRequest->application;

            // Mark application live with billing resume date
            $activationDate = \Carbon\Carbon::parse($validated['reactivation_date']);
            $application->update([
                'service_status' => 'live',
                'is_active' => true,
                'billing_resume_date' => $activationDate->format('Y-m-d'),
                'suspended_from' => null,
                'disconnected_at' => null,
                'deactivated_at' => null,
                'deactivated_by' => null,
            ]);

            \App\Models\ApplicationServiceStatusHistory::create([
                'application_id' => $application->id,
                'status' => 'live',
                'effective_from' => $activationDate->format('Y-m-d'),
                'changed_by_type' => 'admin',
                'changed_by_id' => $admin->id,
                'notes' => 'Reactivated after payment',
            ]);

            \App\Models\AdminAction::log(
                $admin->id,
                'member_reactivated',
                $application,
                "Member reactivated: {$application->application_id}",
                ['reactivation_date' => $activationDate->format('Y-m-d')]
            );

            $reactivationRequest->update([
                'status' => 'completed',
                'reactivation_date' => $activationDate->format('Y-m-d'),
            ]);

            return back()->with('success', 'Reactivation date set and application is LIVE again.');
        } catch (\Exception $e) {
            Log::error('Error setting reactivation date: '.$e->getMessage());

            return back()->with('error', 'Unable to set reactivation date.');
        }
    }
}
