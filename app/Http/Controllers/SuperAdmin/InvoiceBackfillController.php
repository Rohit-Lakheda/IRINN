<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\GstVerification;
use App\Models\Invoice;
use App\Models\IxApplicationPricing;
use App\Models\PaymentTransaction;
use App\Models\PaymentVerificationLog;
use App\Models\SuperAdmin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceBackfillController extends Controller
{
    public function index(Request $request)
    {
        $superAdminId = session('superadmin_id');
        $superAdmin = $superAdminId ? SuperAdmin::find($superAdminId) : null;

        $query = Application::query()
            ->with(['user'])
            ->where('application_type', 'IX')
            ->whereNotNull('submitted_at')
            ->whereDoesntHave('invoices', function ($q) {
                $q->where('status', '!=', 'cancelled')
                    ->where('invoice_purpose', 'application');
            });

        // Only show applications where we can find evidence of an already-paid application fee
        // (either a verified initial payment log or a successful payment transaction for application fee).
        $query->where(function ($q) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('payment_verification_logs')
                    ->whereColumn('payment_verification_logs.application_id', 'applications.id')
                    ->where('payment_verification_logs.verification_type', 'initial');
            })->orWhereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('payment_transactions')
                    ->whereColumn('payment_transactions.application_id', 'applications.id')
                    ->where('payment_transactions.payment_status', 'success')
                    ->where(function ($pq) {
                        $pq->where('payment_transactions.product_info', 'like', '%Application Fee%')
                            ->orWhere('payment_transactions.product_info', 'like', '%NIXI IX Application Fee%')
                            ->orWhere('payment_transactions.product_info', 'like', '%IX Application Fee%');
                    });
            });
        });

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('application_id', 'like', "%{$search}%")
                    ->orWhere('membership_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('registrationid', 'like', "%{$search}%")
                            ->orWhere('fullname', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('submitted_from')) {
            $query->whereDate('submitted_at', '>=', $request->input('submitted_from'));
        }

        if ($request->filled('submitted_to')) {
            $query->whereDate('submitted_at', '<=', $request->input('submitted_to'));
        }

        $applications = $query->latest('submitted_at')->paginate(25)->withQueryString();

        return view('superadmin.invoices.backfill-paid', compact('superAdmin', 'applications'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'application_ids' => 'required|array|min:1',
            'application_ids.*' => 'integer',
        ]);

        $applicationIds = array_values(array_unique($validated['application_ids']));

        $applications = Application::query()
            ->with(['user'])
            ->whereIn('id', $applicationIds)
            ->get()
            ->keyBy('id');

        $generated = [];
        $skipped = [];
        $failed = [];

        DB::beginTransaction();

        try {
            foreach ($applicationIds as $applicationId) {
                $application = $applications->get($applicationId);
                if (! $application) {
                    $failed[] = ['application_id' => $applicationId, 'reason' => 'Application not found'];

                    continue;
                }

                // Safety: only allow backfill for eligible apps
                if ($application->application_type !== 'IX' || ! $application->submitted_at) {
                    $skipped[] = ['application_id' => $application->application_id, 'reason' => 'Not eligible (missing submitted_at/type mismatch)'];

                    continue;
                }
                // Note: This is an application fee invoice (not billing). Live/suspended/disconnected status does not matter here.

                $alreadyHasServiceInvoice = Invoice::query()
                    ->where('application_id', $application->id)
                    ->where('status', '!=', 'cancelled')
                    ->where('invoice_purpose', 'application')
                    ->exists();

                if ($alreadyHasServiceInvoice) {
                    $skipped[] = ['application_id' => $application->application_id, 'reason' => 'Application invoice already exists'];

                    continue;
                }

                $submittedDate = Carbon::parse($application->submitted_at)->startOfDay();
                $billingPeriod = 'APP-'.$submittedDate->format('Ymd').'-'.$application->id;
                $invoiceDate = now('Asia/Kolkata')->startOfDay();

                // Determine already-paid amount for application fee:
                // 1) PaymentVerificationLog (initial) as most reliable
                // 2) PaymentTransaction success (product_info contains "Application Fee")
                // 3) Fallback to current active application pricing
                $amountPaid = null;
                $paymentId = null;

                $verification = PaymentVerificationLog::query()
                    ->where('application_id', $application->id)
                    ->where('verification_type', 'initial')
                    ->latest('verified_at')
                    ->first();

                if ($verification) {
                    $amountPaid = (float) ($verification->amount_captured ?? $verification->amount ?? 0);
                    $paymentId = $verification->payment_id;
                }

                if (! $amountPaid || $amountPaid <= 0) {
                    $tx = PaymentTransaction::query()
                        ->where('application_id', $application->id)
                        ->where('payment_status', 'success')
                        ->where(function ($q) {
                            $q->where('product_info', 'like', '%Application Fee%')
                                ->orWhere('product_info', 'like', '%NIXI IX Application Fee%')
                                ->orWhere('product_info', 'like', '%IX Application Fee%');
                        })
                        ->latest('created_at')
                        ->first();

                    if ($tx) {
                        $amountPaid = (float) $tx->amount;
                        $paymentId = $tx->payment_id;
                    }
                }

                if (! $amountPaid || $amountPaid <= 0) {
                    $pricing = IxApplicationPricing::getActive();
                    $amountPaid = (float) ($pricing ? $pricing->total_amount : 0);
                }

                if (! $amountPaid || $amountPaid <= 0) {
                    $failed[] = ['application_id' => $application->application_id, 'reason' => 'Unable to determine application fee amount (no verified payment found).'];

                    continue;
                }

                $invoiceNumber = $this->generateIxInvoiceNumber();

                $totalPayable = (float) $amountPaid;

                $applicationData = $application->application_data ?? [];
                $paymentData = is_array($applicationData) ? ($applicationData['payment'] ?? []) : [];

                $pricing = IxApplicationPricing::getActive();
                $gstPercentage = isset($paymentData['gst_percentage'])
                    ? (float) $paymentData['gst_percentage']
                    : (float) ($pricing?->gst_percentage ?? 0);

                $baseFee = isset($paymentData['application_fee'])
                    ? (float) $paymentData['application_fee']
                    : (float) ($pricing?->application_fee ?? 0);

                if ($baseFee <= 0 && $gstPercentage > 0) {
                    $baseFee = round($totalPayable / (1 + ($gstPercentage / 100)), 2);
                }

                if ($baseFee <= 0) {
                    $baseFee = $totalPayable;
                }

                $gstAmount = 0.0;
                if ($gstPercentage > 0) {
                    $gstAmount = round(max(0, $totalPayable - $baseFee), 2);
                }

                $lineItems = [
                    [
                        'description' => 'IX Application Fee',
                        'quantity' => 1,
                        'rate' => $baseFee,
                        'amount' => $baseFee,
                        'show_period' => false,
                        'is_application_fee' => true,
                    ],
                    '_metadata' => [
                        'type' => 'application_fee',
                        'note' => 'Backfilled as already paid (no email).',
                    ],
                ];

                // IRN (E-invoice) logic: call only if GSTIN is present + verified active.
                $einvoiceData = null;
                $buyerGstin = '';

                $kycDetails = $application->kyc_details ?? [];
                if (is_array($kycDetails)) {
                    $buyerGstin = (string) ($kycDetails['gstin'] ?? '');
                }

                if (! $buyerGstin && is_array($applicationData)) {
                    $buyerGstin = (string) ($applicationData['gstin'] ?? '');
                }

                if (! $buyerGstin) {
                    $buyerGstin = (string) ($application->gstin ?? '');
                }

                $buyerGstin = strtoupper(trim($buyerGstin));

                $isGstinValid = (bool) preg_match('/^\d{2}[A-Z]{5}\d{4}[A-Z][A-Z0-9]Z[A-Z0-9]$/', $buyerGstin);

                if ($buyerGstin && $isGstinValid) {
                    $gstVerification = GstVerification::query()
                        ->where('user_id', $application->user_id)
                        ->where('gstin', $buyerGstin)
                        ->where('is_verified', true)
                        ->latest()
                        ->first();

                    if ($gstVerification) {
                        $companyStatus = strtolower(trim($gstVerification->company_status ?? ''));
                        $gstinStatus = '';

                        if ($gstVerification->verification_data && is_array($gstVerification->verification_data)) {
                            $verificationData = $gstVerification->verification_data;
                            $sourceOutput = $verificationData['result']['source_output'] ?? [];
                            $gstinStatus = strtolower(trim($sourceOutput['gstin_status'] ?? ''));
                        }

                        $isGstinInactive = in_array($companyStatus, ['cancelled', 'canceled', 'cancelled/suspended', 'inactive', 'suspended'])
                            || in_array($gstinStatus, ['cancelled', 'canceled', 'cancelled/suspended', 'inactive', 'suspended']);

                        if (! $isGstinInactive) {
                            try {
                                $tempInvoice = new Invoice([
                                    'application_id' => $application->id,
                                    'invoice_number' => $invoiceNumber,
                                    'invoice_date' => $invoiceDate->format('Y-m-d'),
                                    'due_date' => $invoiceDate->format('Y-m-d'),
                                    'billing_period' => $billingPeriod,
                                    'billing_start_date' => null,
                                    'billing_end_date' => null,
                                    'invoice_purpose' => 'application',
                                    'line_items' => $lineItems,
                                    'amount' => $baseFee,
                                    'gst_amount' => $gstAmount,
                                    'total_amount' => $totalPayable,
                                    'paid_amount' => $totalPayable,
                                    'balance_amount' => 0,
                                    'payment_status' => 'paid',
                                    'currency' => 'INR',
                                    'status' => 'paid',
                                ]);

                                $reflection = new \ReflectionClass(\App\Http\Controllers\AdminController::class);
                                $method = $reflection->getMethod('callEinvoiceApi');
                                $method->setAccessible(true);
                                $adminController = new \App\Http\Controllers\AdminController;
                                $einvoiceData = $method->invoke($adminController, $application, $tempInvoice);

                                // Normalize duplicate IRN response (2150)
                                if (is_array($einvoiceData)) {
                                    $errorCode = $einvoiceData['ErrorCode'] ?? '';
                                    $irn = $einvoiceData['Irn'] ?? '';

                                    if ($errorCode === '2150' && empty($irn) && isset($einvoiceData['InfoDtls']) && is_array($einvoiceData['InfoDtls'])) {
                                        foreach ($einvoiceData['InfoDtls'] as $infoDetail) {
                                            if (($infoDetail['InfCd'] ?? '') === 'DUPIRN' && isset($infoDetail['Desc']) && is_array($infoDetail['Desc'])) {
                                                $desc = $infoDetail['Desc'];
                                                $einvoiceData['Irn'] = $desc['Irn'] ?? null;
                                                $einvoiceData['AckNo'] = $desc['AckNo'] ?? null;
                                                $einvoiceData['AckDate'] = $desc['AckDt'] ?? null;
                                                break;
                                            }
                                        }
                                    }

                                    $status = $einvoiceData['Status'] ?? $einvoiceData['status'] ?? '';
                                    $irn = $einvoiceData['Irn'] ?? '';
                                    $isEinvoiceSuccess = (($status === '1' || $status === 1) && ! empty($irn))
                                        || (($einvoiceData['ErrorCode'] ?? '') === '2150' && ! empty($irn));

                                    if (! $isEinvoiceSuccess) {
                                        Log::warning('E-invoice API failed for backfill application invoice. Proceeding without IRN.', [
                                            'application_id' => $application->id,
                                            'invoice_number' => $invoiceNumber,
                                            'response' => $einvoiceData,
                                        ]);
                                        $einvoiceData = null;
                                    }
                                } else {
                                    $einvoiceData = null;
                                }
                            } catch (\Throwable $e) {
                                Log::warning('E-invoice API exception for backfill application invoice. Proceeding without IRN.', [
                                    'application_id' => $application->id,
                                    'invoice_number' => $invoiceNumber,
                                    'error' => $e->getMessage(),
                                ]);
                                $einvoiceData = null;
                            }
                        }
                    }
                }

                $invoice = Invoice::query()->create([
                    'application_id' => $application->id,
                    'invoice_number' => $invoiceNumber,
                    'invoice_date' => $invoiceDate->format('Y-m-d'),
                    'due_date' => $invoiceDate->format('Y-m-d'),
                    'billing_period' => $billingPeriod,
                    'billing_start_date' => null,
                    'billing_end_date' => null,
                    'invoice_purpose' => 'application',
                    'line_items' => $lineItems,
                    'amount' => $baseFee,
                    'gst_amount' => $gstAmount,
                    'total_amount' => $totalPayable,
                    'paid_amount' => $totalPayable,
                    'balance_amount' => 0,
                    'payment_status' => 'paid',
                    'status' => 'paid',
                    'currency' => 'INR',
                    'paid_at' => $invoiceDate->copy()->setTimezone('Asia/Kolkata'),
                    'sent_at' => null, // No email for backfilled invoices
                    'payu_payment_link' => null,
                    'manual_payment_id' => $paymentId,
                    'manual_payment_notes' => 'Backfilled as already paid (no email).',
                    'einvoice_irn' => is_array($einvoiceData) ? ($einvoiceData['Irn'] ?? null) : null,
                    'einvoice_ack_no' => is_array($einvoiceData) ? ($einvoiceData['AckNo'] ?? null) : null,
                    'einvoice_ack_date' => is_array($einvoiceData) ? ($einvoiceData['AckDate'] ?? null) : null,
                    'einvoice_status' => is_array($einvoiceData) ? (($einvoiceData['Status'] ?? $einvoiceData['status'] ?? null)) : null,
                    'einvoice_error_code' => is_array($einvoiceData) ? ($einvoiceData['ErrorCode'] ?? null) : null,
                    'einvoice_error_message' => is_array($einvoiceData) ? ($einvoiceData['ErrorMessage'] ?? null) : null,
                    'einvoice_response' => is_array($einvoiceData) ? $einvoiceData : null,
                ]);

                $generated[] = ['application_id' => $application->application_id, 'invoice_number' => $invoice->invoice_number];
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Backfill paid invoices failed: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Backfill failed. No invoices were created.');
        }

        $message = 'Backfill complete. Generated: '.count($generated).', Skipped: '.count($skipped).', Failed: '.count($failed).'.';

        return redirect()->route('superadmin.invoices.backfill-paid.index')
            ->with('success', $message)
            ->with('backfill_generated', $generated)
            ->with('backfill_skipped', $skipped)
            ->with('backfill_failed', $failed);
    }

    private function generateIxInvoiceNumber(): string
    {
        $prefix = $this->getIxInvoicePrefix(); // e.g., NIXIEX2526-

        $lastInvoice = DB::table('invoices')
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderBy('id', 'desc')
            ->value('invoice_number');

        if ($lastInvoice && preg_match('/^'.preg_quote($prefix, '/').'(\d{4})$/', $lastInvoice, $m)) {
            $next = ((int) $m[1]) + 1;
        } else {
            // Match existing production sequence start
            $next = 1925;
        }

        $invoiceNumber = $prefix.str_pad($next, 4, '0', STR_PAD_LEFT);
        $attempts = 0;

        while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
            $next++;
            $invoiceNumber = $prefix.str_pad($next, 4, '0', STR_PAD_LEFT);
            $attempts++;

            if ($attempts > 200) {
                throw new \RuntimeException('Unable to generate unique invoice number.');
            }
        }

        return $invoiceNumber;
    }

    private function getIxInvoicePrefix(): string
    {
        $now = now('Asia/Kolkata');

        // Financial year starts in April (India FY)
        $fyStartYear = $now->month >= 4 ? $now->year : $now->year - 1;
        $fyEndYear = $fyStartYear + 1;

        return 'NIXIEX'.substr((string) $fyStartYear, -2).substr((string) $fyEndYear, -2).'-';
    }
}
