<?php

namespace App\Console\Commands;

use App\Mail\IxApplicationInvoiceMail;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\GstVerification;
use App\Models\Invoice;
use App\Models\IxInvoiceCronLog;
use App\Models\PaymentTransaction;
use App\Models\PlanChangeRequest;
use App\Services\IxMembershipInvoiceService;
use App\Services\PayuService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateMonthlyIxInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ix:generate-monthly-invoices
                            {--application-id= : Only process a specific application (DB id)}
                            {--user-id= : Only process applications for a specific user (registration id)}
                            {--dry-run : Show what would be generated, without creating invoices}
                            {--force : Allow running even if today is not the 1st}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate IX service invoices for LIVE members on the 1st of every month (cron)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now('Asia/Kolkata');

        if (! $this->option('force') && (int) $now->format('d') !== 1) {
            $this->info('Skipping: this command is intended to run on the 1st of every month. Use --force to run manually.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $runId = (string) Str::uuid();

        $query = Application::query()
            ->where('application_type', 'IX')
            ->where('is_active', true)
            ->whereNotNull('service_activation_date')
            ->whereNotNull('billing_cycle')
            ->where(function ($q) {
                $q->whereNull('service_status')
                    ->orWhere('service_status', 'live');
            })
            ->with('user');

        if ($this->option('application-id')) {
            $query->where('id', (int) $this->option('application-id'));
        }

        if ($this->option('user-id')) {
            $query->where('user_id', (int) $this->option('user-id'));
        }

        $adminController = app(\App\Http\Controllers\AdminController::class);
        $reflection = new \ReflectionClass($adminController);
        $getCurrentBillingPeriod = $reflection->getMethod('getCurrentBillingPeriod');
        $getCurrentBillingPeriod->setAccessible(true);
        $canGenerateInvoiceForPeriod = $reflection->getMethod('canGenerateInvoiceForPeriod');
        $canGenerateInvoiceForPeriod->setAccessible(true);
        $calculateInvoiceDetails = $reflection->getMethod('calculateInvoiceDetails');
        $calculateInvoiceDetails->setAccessible(true);
        $callEinvoiceApi = $reflection->getMethod('callEinvoiceApi');
        $callEinvoiceApi->setAccessible(true);
        $generateIxInvoicePdf = $reflection->getMethod('generateIxInvoicePdf');
        $generateIxInvoicePdf->setAccessible(true);

        $processed = 0;
        $generated = 0;
        $skipped = 0;
        $failed = 0;

        $this->info('===================================================');
        $this->info('IX Monthly Invoice Cron');
        $this->info('Now: '.$now->format('Y-m-d H:i:s'));
        $this->info('Dry-run: '.($dryRun ? 'YES' : 'NO'));
        $this->info('Run ID: '.$runId);
        $this->info('===================================================');

        $query->orderBy('id')->chunkById(100, function ($applications) use (
            $dryRun,
            $runId,
            $adminController,
            $getCurrentBillingPeriod,
            $canGenerateInvoiceForPeriod,
            $calculateInvoiceDetails,
            $callEinvoiceApi,
            $generateIxInvoicePdf,
            &$processed,
            &$generated,
            &$skipped,
            &$failed
        ) {
            foreach ($applications as $application) {
                $processed++;
                $startedAt = now('Asia/Kolkata');

                try {
                    $billingPeriod = $getCurrentBillingPeriod->invoke($adminController, $application);
                    if (! $billingPeriod) {
                        IxInvoiceCronLog::query()->create([
                            'run_id' => $runId,
                            'is_dry_run' => $dryRun,
                            'application_id' => $application->id,
                            'application_code' => $application->application_id,
                            'status' => 'skipped',
                            'skip_reason' => 'Missing billing period (activation date or billing cycle missing).',
                            'started_at' => $startedAt,
                            'finished_at' => now('Asia/Kolkata'),
                        ]);
                        $skipped++;

                        continue;
                    }

                    // Check if active invoice exists (without credit note) - allow regenerate if credit note exists
                    $alreadyExists = Invoice::query()
                        ->where('application_id', $application->id)
                        ->where('billing_period', $billingPeriod)
                        ->where('status', '!=', 'cancelled')
                        ->whereNull('credit_note_pdf_path')
                        ->exists();

                    if ($alreadyExists) {
                        IxInvoiceCronLog::query()->create([
                            'run_id' => $runId,
                            'is_dry_run' => $dryRun,
                            'application_id' => $application->id,
                            'application_code' => $application->application_id,
                            'billing_period' => $billingPeriod,
                            'status' => 'skipped',
                            'skip_reason' => 'Invoice already exists for billing period.',
                            'started_at' => $startedAt,
                            'finished_at' => now('Asia/Kolkata'),
                        ]);
                        $skipped++;

                        continue;
                    }

                    // If invoice with credit note exists, allow regeneration (continue to generate new invoice)

                    $allowed = (bool) $canGenerateInvoiceForPeriod->invoke($adminController, $application, $billingPeriod);
                    if (! $allowed) {
                        IxInvoiceCronLog::query()->create([
                            'run_id' => $runId,
                            'is_dry_run' => $dryRun,
                            'application_id' => $application->id,
                            'application_code' => $application->application_id,
                            'billing_period' => $billingPeriod,
                            'status' => 'skipped',
                            'skip_reason' => 'Not eligible to generate invoice for this period (advance window / unpaid invoice rules).',
                            'started_at' => $startedAt,
                            'finished_at' => now('Asia/Kolkata'),
                        ]);
                        $skipped++;

                        continue;
                    }

                    $invoiceData = $calculateInvoiceDetails->invoke($adminController, $application);
                    if (is_array($invoiceData) && isset($invoiceData['error'])) {
                        throw new \RuntimeException((string) $invoiceData['error']);
                    }

                    if ($dryRun) {
                        IxInvoiceCronLog::query()->create([
                            'run_id' => $runId,
                            'is_dry_run' => true,
                            'application_id' => $application->id,
                            'application_code' => $application->application_id,
                            'billing_period' => $billingPeriod,
                            'billing_start_date' => $invoiceData['billing_start_date'] ?? null,
                            'billing_end_date' => $invoiceData['billing_end_date'] ?? null,
                            'status' => 'dry_run',
                            'started_at' => $startedAt,
                            'finished_at' => now('Asia/Kolkata'),
                        ]);
                        $this->line("DRY-RUN: would generate invoice for {$application->application_id} (app_id={$application->id}) billing_period={$billingPeriod}");
                        $generated++;

                        continue;
                    }

                    $log = IxInvoiceCronLog::query()->create([
                        'run_id' => $runId,
                        'is_dry_run' => false,
                        'application_id' => $application->id,
                        'application_code' => $application->application_id,
                        'billing_period' => $billingPeriod,
                        'billing_start_date' => $invoiceData['billing_start_date'] ?? null,
                        'billing_end_date' => $invoiceData['billing_end_date'] ?? null,
                        'status' => 'started',
                        'started_at' => $startedAt,
                    ]);

                    $invoice = $this->createServiceInvoiceForApplication(
                        application: $application,
                        invoiceData: $invoiceData,
                        billingPeriod: $billingPeriod,
                        callEinvoiceApi: $callEinvoiceApi,
                        generateIxInvoicePdf: $generateIxInvoicePdf,
                        adminController: $adminController,
                        log: $log
                    );

                    if (! $invoice) {
                        throw new \RuntimeException('Invoice creation returned null.');
                    }

                    $generated++;
                    $this->info("✓ Generated invoice {$invoice->invoice_number} for {$application->application_id}");

                    // Ensure membership invoice exists for this customer (user) for current FY when first service invoice of cycle is generated
                    try {
                        $membershipService = app(IxMembershipInvoiceService::class);
                        $membershipInvoice = $membershipService->ensureMembershipInvoiceForUser($application->user_id, null);
                        if ($membershipInvoice) {
                            $this->info("  ✓ Generated membership invoice {$membershipInvoice->invoice_number} for user {$application->user_id}");
                        }
                    } catch (\Throwable $membershipEx) {
                        Log::warning('IX monthly cron: membership invoice check failed', [
                            'user_id' => $application->user_id,
                            'error' => $membershipEx->getMessage(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    IxInvoiceCronLog::query()->create([
                        'run_id' => $runId,
                        'is_dry_run' => $dryRun,
                        'application_id' => $application->id,
                        'application_code' => $application->application_id,
                        'billing_period' => $billingPeriod ?? null,
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                        'started_at' => $startedAt,
                        'finished_at' => now('Asia/Kolkata'),
                    ]);
                    Log::error('IX monthly invoice cron failed for application '.$application->id.': '.$e->getMessage(), [
                        'application_id' => $application->id,
                        'application_code' => $application->application_id,
                    ]);
                    $this->error("✗ Failed for {$application->application_id}: ".$e->getMessage());
                }
            }
        });

        $this->info('===================================================');
        $this->info("Done. processed={$processed} generated={$generated} skipped={$skipped} failed={$failed}");
        $this->info('===================================================');

        return self::SUCCESS;
    }

    private function createServiceInvoiceForApplication(
        Application $application,
        array $invoiceData,
        string $billingPeriod,
        \ReflectionMethod $callEinvoiceApi,
        \ReflectionMethod $generateIxInvoicePdf,
        mixed $adminController,
        ?IxInvoiceCronLog $log = null
    ): ?Invoice {
        // Prepare values from invoiceData
        $billingStartDate = Carbon::parse($invoiceData['billing_start_date'])->startOfDay();
        $billingEndDate = Carbon::parse($invoiceData['billing_end_date'])->startOfDay();
        $dueDate = Carbon::parse($invoiceData['due_date'])->startOfDay();

        $amount = (float) ($invoiceData['amount'] ?? 0);
        $gstAmount = (float) ($invoiceData['gst_amount'] ?? 0);
        $finalTotalAmount = (float) ($invoiceData['final_total_amount'] ?? ($invoiceData['total_amount'] ?? 0));

        $tdsPercentage = (float) ($invoiceData['tds_percentage'] ?? 0);
        $tdsAmount = (float) ($invoiceData['tds_amount'] ?? 0);

        $carryForwardAmount = (float) ($invoiceData['carry_forward_amount'] ?? 0);
        $hasCarryForward = (bool) ($invoiceData['has_carry_forward'] ?? false);
        $carryForwardInvoices = $invoiceData['carry_forward_invoices'] ?? [];

        $lineItemsData = $invoiceData['segments'] ?? [];

        // Add carry forward as a line item if present (same logic as AdminController)
        if ($hasCarryForward && $carryForwardAmount > 0) {
            $carryForwardDescription = 'Carry Forward from Previous Invoice(s): ';
            $invoiceNumbers = array_map(function ($inv) {
                return $inv['invoice_number'];
            }, $carryForwardInvoices);
            $carryForwardDescription .= implode(', ', $invoiceNumbers);

            $lineItemsData[] = [
                'description' => $carryForwardDescription,
                'quantity' => 1,
                'rate' => $carryForwardAmount,
                'amount' => $carryForwardAmount,
                'is_carry_forward' => true,
            ];
        }

        // Generate invoice number (sequential format: NIXIEX2526-XXXX)
        $baseInvoiceNumber = 'NIXIEX2526-';
        $lastInvoice = DB::table('invoices')
            ->where('invoice_number', 'like', $baseInvoiceNumber.'%')
            ->orderByDesc('id')
            ->value('invoice_number');

        if ($lastInvoice && preg_match('/NIXIEX2526-(\d{4})$/', (string) $lastInvoice, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        } else {
            $nextNumber = 1925;
        }

        $invoiceNumber = $baseInvoiceNumber.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        $counter = 1;
        while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
            $nextNumber++;
            $invoiceNumber = $baseInvoiceNumber.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $counter++;
            if ($counter > 100) {
                throw new \RuntimeException('Unable to generate unique invoice number.');
            }
        }

        // Prepare temp invoice for potential e-invoice API call
        $tempInvoice = new Invoice([
            'application_id' => $application->id,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => now('Asia/Kolkata'),
            'due_date' => $dueDate->format('Y-m-d'),
            'billing_period' => $billingPeriod,
            'billing_start_date' => $billingStartDate->format('Y-m-d'),
            'billing_end_date' => $billingEndDate->format('Y-m-d'),
            'line_items' => $lineItemsData,
            'amount' => $amount,
            'gst_amount' => $gstAmount,
            'tds_percentage' => $tdsPercentage,
            'tds_amount' => $tdsAmount,
            'total_amount' => $finalTotalAmount,
            'paid_amount' => 0,
            'balance_amount' => $finalTotalAmount,
            'payment_status' => 'pending',
            'carry_forward_amount' => $carryForwardAmount,
            'has_carry_forward' => $hasCarryForward,
            'currency' => 'INR',
            'status' => 'pending',
            'payu_payment_link' => null,
            'generated_by' => null, // system
        ]);

        // GSTIN status check (same logic as AdminController)
        $kycDetails = $application->kyc_details ?? [];
        $buyerGstin = $kycDetails['gstin'] ?? ($application->gstin ?? '');
        $isGstinInactive = false;
        $einvoiceData = null;
        $isEinvoiceSuccess = false;

        if (! empty($buyerGstin)) {
            $gstVerification = GstVerification::where('user_id', $application->user_id)
                ->where('gstin', $buyerGstin)
                ->where('is_verified', true)
                ->latest()
                ->first();

            if (! $gstVerification) {
                $gstVerification = GstVerification::where('user_id', $application->user_id)
                    ->where('is_verified', true)
                    ->latest()
                    ->first();
            }

            if ($gstVerification) {
                $companyStatus = strtolower(trim($gstVerification->company_status ?? ''));
                $gstinStatus = '';
                if ($gstVerification->verification_data && is_array($gstVerification->verification_data)) {
                    $sourceOutput = $gstVerification->verification_data['result']['source_output'] ?? [];
                    $gstinStatus = strtolower(trim($sourceOutput['gstin_status'] ?? ''));
                }

                $isGstinInactive = in_array($companyStatus, ['cancelled', 'canceled', 'cancelled/suspended', 'inactive', 'suspended'], true)
                    || in_array($gstinStatus, ['cancelled', 'canceled', 'cancelled/suspended', 'inactive', 'suspended'], true);
            }
        }

        if (! $isGstinInactive) {
            if ($log) {
                $log->update(['einvoice_attempted' => true]);
            }
            try {
                $einvoiceData = $callEinvoiceApi->invoke($adminController, $application, $tempInvoice);
            } catch (\Throwable $e) {
                // Proceed without IRN if API fails (same behavior we want for cron)
                Log::warning('E-invoice API exception during cron invoice generation; proceeding without IRN', [
                    'invoice_number' => $invoiceNumber,
                    'application_id' => $application->id,
                    'error' => $e->getMessage(),
                ]);
                $einvoiceData = null;
            }

            if ($einvoiceData && is_array($einvoiceData)) {
                $status = $einvoiceData['Status'] ?? $einvoiceData['status'] ?? '';
                $irn = $einvoiceData['Irn'] ?? '';
                $errorCode = $einvoiceData['ErrorCode'] ?? '';

                if ($errorCode === '2150' && empty($irn) && isset($einvoiceData['InfoDtls']) && is_array($einvoiceData['InfoDtls'])) {
                    foreach ($einvoiceData['InfoDtls'] as $infoDetail) {
                        if (($infoDetail['InfCd'] ?? null) === 'DUPIRN' && isset($infoDetail['Desc'])) {
                            $desc = $infoDetail['Desc'];
                            $irn = $desc['Irn'] ?? '';
                            $einvoiceData['Irn'] = $irn;
                            $einvoiceData['AckNo'] = $desc['AckNo'] ?? '';
                            $einvoiceData['AckDate'] = $desc['AckDt'] ?? '';
                            break;
                        }
                    }
                }

                $isEinvoiceSuccess = (($status === '1' || $status === 1) && ! empty($irn)) || ($errorCode === '2150' && ! empty($irn));

                if (! $isEinvoiceSuccess) {
                    Log::warning('E-invoice API returned failure during cron invoice generation; proceeding without IRN', [
                        'invoice_number' => $invoiceNumber,
                        'application_id' => $application->id,
                        'response' => $einvoiceData,
                    ]);
                    $einvoiceData = null;
                }
            }
        }

        if ($log) {
            $log->update([
                'gstin_inactive' => $isGstinInactive,
                'einvoice_irn' => is_array($einvoiceData) ? ($einvoiceData['Irn'] ?? null) : null,
                'einvoice_status' => is_array($einvoiceData) ? ($einvoiceData['Status'] ?? ($einvoiceData['status'] ?? null)) : null,
                'einvoice_error_code' => is_array($einvoiceData) ? ($einvoiceData['ErrorCode'] ?? null) : null,
                'einvoice_error_message' => is_array($einvoiceData) ? ($einvoiceData['ErrorMessage'] ?? null) : null,
            ]);
        }

        // Generate PayU payment link
        $payuService = new PayuService;
        $transactionId = 'INV-'.time().'-'.strtoupper(Str::random(8));

        $paymentTransaction = PaymentTransaction::create([
            'user_id' => $application->user_id,
            'application_id' => $application->id,
            'transaction_id' => $transactionId,
            'payment_status' => 'pending',
            'payment_mode' => 'live',
            'amount' => $finalTotalAmount,
            'currency' => 'INR',
            'product_info' => 'NIXI IX Service Invoice - '.$invoiceNumber,
            'response_message' => 'Invoice payment pending',
        ]);

        $paymentData = $payuService->preparePaymentData([
            'transaction_id' => $transactionId,
            'amount' => $finalTotalAmount,
            'product_info' => 'NIXI IX Service Invoice - '.$invoiceNumber,
            'firstname' => $application->user->fullname,
            'email' => $application->user->email,
            'phone' => $application->user->mobile,
            'success_url' => url(route('user.applications.ix.payment-success', [], false)),
            'failure_url' => url(route('user.applications.ix.payment-failure', [], false)),
            'udf1' => $application->application_id,
            'udf2' => (string) $paymentTransaction->id,
            'udf3' => $invoiceNumber,
        ]);

        $invoice = Invoice::create([
            'application_id' => $application->id,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => now('Asia/Kolkata'),
            'due_date' => $dueDate->format('Y-m-d'),
            'billing_period' => $billingPeriod,
            'billing_start_date' => $billingStartDate->format('Y-m-d'),
            'billing_end_date' => $billingEndDate->format('Y-m-d'),
            'line_items' => $lineItemsData,
            'amount' => $amount,
            'gst_amount' => $gstAmount,
            'tds_percentage' => $tdsPercentage,
            'tds_amount' => $tdsAmount,
            'total_amount' => $finalTotalAmount,
            'paid_amount' => 0,
            'balance_amount' => $finalTotalAmount,
            'payment_status' => 'pending',
            'carry_forward_amount' => $carryForwardAmount,
            'has_carry_forward' => $hasCarryForward,
            'currency' => 'INR',
            'status' => 'pending',
            'payu_payment_link' => json_encode($paymentData),
            'generated_by' => null,
        ]);

        if ($log) {
            $log->update([
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'payment_transaction_id' => $paymentTransaction->id,
                'status' => 'generated',
            ]);
        }

        // Mark plan-change adjustments applied (if present with plan_change_id)
        $adjustments = $invoiceData['adjustments'] ?? [];
        if (! empty($adjustments)) {
            foreach ($adjustments as $adj) {
                if (! empty($adj['plan_change_id'])) {
                    PlanChangeRequest::where('id', $adj['plan_change_id'])->update([
                        'adjustment_applied' => true,
                        'adjustment_invoice_id' => $invoice->id,
                    ]);
                }
            }
        }

        // Mark previous invoices as paid if carry forward is applied
        if ($hasCarryForward && $carryForwardAmount > 0 && ! empty($carryForwardInvoices)) {
            foreach ($carryForwardInvoices as $cfInvoice) {
                $previousInvoice = Invoice::find($cfInvoice['invoice_id'] ?? null);
                if (! $previousInvoice) {
                    continue;
                }
                $forwardedAmount = (float) ($cfInvoice['amount'] ?? 0);
                $calculatedPaidAmount = (float) $previousInvoice->total_amount - $forwardedAmount;
                $previousInvoice->update([
                    'payment_status' => 'paid',
                    'status' => 'paid',
                    'paid_amount' => $calculatedPaidAmount,
                    'balance_amount' => 0,
                    'forwarded_amount' => $forwardedAmount,
                    'forwarded_to_invoice_date' => $invoice->invoice_date,
                    'has_carry_forward' => true,
                    'carry_forward_amount' => $forwardedAmount,
                    'paid_at' => now('Asia/Kolkata'),
                    'paid_by' => null,
                    'manual_payment_notes' => ($previousInvoice->manual_payment_notes ? $previousInvoice->manual_payment_notes.' | ' : '').'Amount forwarded to invoice '.$invoice->invoice_number,
                ]);
            }
        }

        // Store e-invoice fields if present (minimal, non-blocking)
        if ($einvoiceData && is_array($einvoiceData)) {
            $updateData = [
                'einvoice_response' => $einvoiceData,
                'einvoice_signed_data' => [
                    'SignedInvoice' => $einvoiceData['SignedInvoice'] ?? null,
                    'SignedQRCode' => $einvoiceData['SignedQRCode'] ?? null,
                ],
                'einvoice_irn' => $einvoiceData['Irn'] ?? null,
                'einvoice_ack_no' => isset($einvoiceData['AckNo']) ? (string) $einvoiceData['AckNo'] : null,
                'einvoice_ack_date' => $einvoiceData['AckDate'] ?? null,
                'einvoice_status' => $einvoiceData['Status'] ?? null,
                'einvoice_error_message' => $einvoiceData['ErrorMessage'] ?? null,
                'einvoice_error_code' => $einvoiceData['ErrorCode'] ?? null,
            ];

            // avoid storing empty signed_data
            if (empty($updateData['einvoice_signed_data']['SignedInvoice']) && empty($updateData['einvoice_signed_data']['SignedQRCode'])) {
                $updateData['einvoice_signed_data'] = null;
            }

            $invoice->update($updateData);
        }

        // Generate PDF (reuse existing function)
        try {
            $invoicePdf = $generateIxInvoicePdf->invoke($adminController, $application, $invoice);
            $invoicePdfPath = 'applications/'.$application->user_id.'/ix/'.$invoiceNumber.'_invoice.pdf';
            Storage::disk('public')->put($invoicePdfPath, $invoicePdf->output());
            $invoice->update(['pdf_path' => $invoicePdfPath]);
            if ($log) {
                $log->update([
                    'pdf_generated' => true,
                    'pdf_path' => $invoicePdfPath,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Cron invoice PDF generation failed: '.$e->getMessage(), [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ]);
        }

        // Log status history
        ApplicationStatusHistory::log(
            $application->id,
            $application->status,
            $application->status,
            'system',
            0,
            'Invoice generated automatically - '.$invoiceNumber
        );

        // Send email (non-blocking)
        try {
            $authorizedPersonName = $application->authorized_representative_details['name']
                ?? ($application->application_data['representative']['name'] ?? $application->user->fullname);
            $ispName = $application->user->fullname;

            Mail::to($application->user->email)->send(new IxApplicationInvoiceMail(
                $application->user->fullname,
                $application->application_id,
                $invoice->invoice_number,
                (float) $invoice->total_amount,
                $application->status,
                $invoice->pdf_path ?? null,
                $payuService->getPaymentUrl(),
                $paymentData,
                $authorizedPersonName,
                $ispName,
                $billingStartDate->format('Y-m-d'),
                $billingEndDate->format('Y-m-d')
            ));

            $invoice->update(['sent_at' => now('Asia/Kolkata')]);
            if ($log) {
                $log->update([
                    'mail_sent' => true,
                    'mail_sent_at' => now('Asia/Kolkata'),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Cron invoice email failed: '.$e->getMessage(), [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ]);
        }

        if ($log) {
            $log->update(['finished_at' => now('Asia/Kolkata')]);
        }

        return $invoice;
    }
}
