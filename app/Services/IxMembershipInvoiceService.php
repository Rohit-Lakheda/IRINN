<?php

namespace App\Services;

use App\Http\Controllers\AdminController;
use App\Models\Application;
use App\Models\Invoice;
use App\Models\IxMembershipFeeSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IxMembershipInvoiceService
{
    /**
     * Return financial year start/end for a given date (India: Apr–Mar).
     *
     * @return array{start: Carbon, end: Carbon, period: string}
     */
    public function financialYearForDate(Carbon $date): array
    {
        $date = $date->copy()->setTimezone('Asia/Kolkata');
        $year = (int) $date->format('Y');
        $month = (int) $date->format('m');

        if ($month >= 4) {
            $fyStart = Carbon::createFromDate($year, 4, 1)->startOfDay()->setTimezone('Asia/Kolkata');
            $fyEnd = Carbon::createFromDate($year + 1, 3, 31)->startOfDay()->setTimezone('Asia/Kolkata');
        } else {
            $fyStart = Carbon::createFromDate($year - 1, 4, 1)->startOfDay()->setTimezone('Asia/Kolkata');
            $fyEnd = Carbon::createFromDate($year, 3, 31)->startOfDay()->setTimezone('Asia/Kolkata');
        }

        $period = 'MEM-'.$fyStart->format('Y').'-'.$fyEnd->format('Y');

        return [
            'start' => $fyStart,
            'end' => $fyEnd,
            'period' => $period,
        ];
    }

    /**
     * User IDs that have at least one IX application that is live (not suspended/disconnected).
     */
    public function getEligibleUserIds(): array
    {
        return Application::query()
            ->where('application_type', 'IX')
            ->where('is_active', true)
            ->whereNotNull('service_activation_date')
            ->where(function ($q) {
                $q->whereNull('service_status')
                    ->orWhere('service_status', 'live');
            })
            ->distinct()
            ->pluck('user_id')
            ->values()
            ->all();
    }

    /**
     * Check if a membership invoice already exists for this user for the given billing period.
     */
    public function alreadyExistsForUserInPeriod(int $userId, string $billingPeriod): bool
    {
        return Invoice::query()
            ->where('invoice_purpose', 'membership')
            ->where('billing_period', $billingPeriod)
            ->whereHas('application', fn ($q) => $q->where('user_id', $userId))
            ->where('status', '!=', 'cancelled')
            ->exists();
    }

    /**
     * Pick one live IX application for the user (for buyer details / e-invoice).
     */
    public function pickApplicationForUser(int $userId): ?Application
    {
        return Application::query()
            ->where('application_type', 'IX')
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereNotNull('service_activation_date')
            ->where(function ($q) {
                $q->whereNull('service_status')
                    ->orWhere('service_status', 'live');
            })
            ->orderBy('id')
            ->first();
    }

    /**
     * Generate a unique membership invoice number (e.g. NIXIMEM2526-0001).
     */
    public function generateMembershipInvoiceNumber(): string
    {
        $now = now('Asia/Kolkata');
        $fyStartYear = $now->month >= 4 ? $now->year : $now->year - 1;
        $fyEndYear = $fyStartYear + 1;
        $prefix = 'NIXIMEM'.substr((string) $fyStartYear, -2).substr((string) $fyEndYear, -2).'-';

        $lastInvoice = DB::table('invoices')
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('invoice_number');

        if ($lastInvoice && preg_match('/^'.preg_quote($prefix, '/').'(\d{4})$/', (string) $lastInvoice, $m)) {
            $next = ((int) $m[1]) + 1;
        } else {
            $next = 1;
        }

        $invoiceNumber = $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        $attempts = 0;

        while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
            $next++;
            $invoiceNumber = $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $attempts++;
            if ($attempts > 500) {
                throw new \RuntimeException('Unable to generate unique membership invoice number.');
            }
        }

        return $invoiceNumber;
    }

    /**
     * Generate membership invoice for a user for the given billing period (customer-wise).
     * Uses one of the user's live applications for buyer details. Returns the created Invoice or null.
     */
    public function generateForUser(
        int $userId,
        Carbon $billingStart,
        Carbon $billingEnd,
        string $billingPeriod,
        ?int $generatedBy = null
    ): ?Invoice {
        $application = $this->pickApplicationForUser($userId);
        if (! $application) {
            Log::warning('IxMembershipInvoiceService: no live application for user', ['user_id' => $userId]);

            return null;
        }

        $setting = IxMembershipFeeSetting::current();
        $baseAmount = (float) $setting->fee_amount;
        $gstPct = (float) $setting->gst_percentage;

        if ($baseAmount <= 0) {
            Log::warning('IxMembershipInvoiceService: membership fee amount is zero, skipping', ['user_id' => $userId]);

            return null;
        }

        $gstAmount = round($baseAmount * $gstPct / 100, 2);
        $totalAmount = round($baseAmount + $gstAmount, 2);

        $invoiceDate = now('Asia/Kolkata')->startOfDay();
        $dueDate = $invoiceDate->copy()->addDays(28);

        $lineItems = [
            [
                'description' => 'IX Membership Fees',
                'quantity' => 1,
                'rate' => $baseAmount,
                'amount' => $baseAmount,
                'show_period' => true,
                'is_membership_fee' => true,
            ],
            '_metadata' => [
                'type' => 'membership_fee',
            ],
        ];

        $invoiceNumber = $this->generateMembershipInvoiceNumber();

        $adminController = app(AdminController::class);
        $tempInvoice = new Invoice([
            'application_id' => $application->id,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate->format('Y-m-d'),
            'due_date' => $dueDate->format('Y-m-d'),
            'billing_period' => $billingPeriod,
            'billing_start_date' => $billingStart->format('Y-m-d'),
            'billing_end_date' => $billingEnd->format('Y-m-d'),
            'invoice_purpose' => 'membership',
            'line_items' => $lineItems,
            'amount' => $baseAmount,
            'gst_amount' => $gstAmount,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'balance_amount' => $totalAmount,
            'payment_status' => 'pending',
            'currency' => 'INR',
            'status' => 'pending',
        ]);

        $einvoiceData = null;
        try {
            $einvoiceData = $adminController->callEinvoiceApiForInvoice($application, $tempInvoice);
        } catch (\Throwable $e) {
            Log::warning('IxMembershipInvoiceService: e-invoice API failed; creating invoice without IRN', [
                'user_id' => $userId,
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }

        $irn = null;
        $ackNo = null;
        $ackDate = null;
        $status = null;
        $errorCode = null;
        $errorMessage = null;

        if ($einvoiceData && is_array($einvoiceData)) {
            $errorCode = $einvoiceData['ErrorCode'] ?? null;
            $irn = $einvoiceData['Irn'] ?? '';

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

            $ackNo = $einvoiceData['AckNo'] ?? null;
            $ackDate = $einvoiceData['AckDate'] ?? null;
            $status = $einvoiceData['Status'] ?? $einvoiceData['status'] ?? null;
            $errorMessage = $einvoiceData['ErrorMessage'] ?? null;
        }

        $invoice = Invoice::query()->create([
            'application_id' => $application->id,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate->format('Y-m-d'),
            'due_date' => $dueDate->format('Y-m-d'),
            'billing_period' => $billingPeriod,
            'billing_start_date' => $billingStart->format('Y-m-d'),
            'billing_end_date' => $billingEnd->format('Y-m-d'),
            'invoice_purpose' => 'membership',
            'line_items' => $lineItems,
            'amount' => $baseAmount,
            'gst_amount' => $gstAmount,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'balance_amount' => $totalAmount,
            'payment_status' => 'pending',
            'currency' => 'INR',
            'status' => 'pending',
            'generated_by' => $generatedBy,
            'einvoice_irn' => $irn,
            'einvoice_ack_no' => $ackNo,
            'einvoice_ack_date' => $ackDate,
            'einvoice_status' => $status,
            'einvoice_error_code' => $errorCode,
            'einvoice_error_message' => $errorMessage,
            'einvoice_response' => is_array($einvoiceData) ? $einvoiceData : null,
        ]);

        try {
            $pdf = $adminController->generateIxInvoicePdf($application, $invoice);
            $path = 'applications/'.$application->user_id.'/ix/'.str_replace(['/', '\\'], '-', $invoiceNumber).'_invoice.pdf';
            Storage::disk('public')->put($path, $pdf->output());
            $invoice->update(['pdf_path' => $path]);
        } catch (\Throwable $e) {
            Log::warning('IxMembershipInvoiceService: PDF generation failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $invoice;
    }

    /**
     * Earliest service_activation_date among the user's live IX applications (in the given FY), or null.
     */
    public function getEarliestActivationInFy(int $userId, Carbon $fyStart, Carbon $fyEnd): ?Carbon
    {
        $date = Application::query()
            ->where('application_type', 'IX')
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereNotNull('service_activation_date')
            ->where(function ($q) {
                $q->whereNull('service_status')->orWhere('service_status', 'live');
            })
            ->min('service_activation_date');

        if (! $date) {
            return null;
        }

        $d = Carbon::parse($date)->startOfDay()->setTimezone('Asia/Kolkata');
        if ($d->lt($fyStart) || $d->gt($fyEnd)) {
            return null;
        }

        return $d;
    }

    /**
     * Ensure membership invoice exists for this user for the current financial year.
     * Called when the first service invoice of the year is generated for the user.
     * If the user joined mid-year (e.g. March), period is from their earliest activation to 31-Mar; then from 1-Apr next year again.
     */
    public function ensureMembershipInvoiceForUser(int $userId, ?int $generatedBy = null): ?Invoice
    {
        $fy = $this->financialYearForDate(now('Asia/Kolkata'));

        if ($this->alreadyExistsForUserInPeriod($userId, $fy['period'])) {
            return null;
        }

        $periodStart = $fy['start']->copy();
        $earliest = $this->getEarliestActivationInFy($userId, $fy['start'], $fy['end']);
        if ($earliest) {
            $periodStart = $earliest;
        }

        return $this->generateForUser(
            $userId,
            $periodStart,
            $fy['end'],
            $fy['period'],
            $generatedBy
        );
    }

    /**
     * Generate membership invoices for all eligible users for the given financial year (e.g. on 1st April).
     *
     * @return array{generated: int, skipped: int, failed: int}
     */
    public function generateForEligibleUsers(Carbon $billingStart, Carbon $billingEnd, string $billingPeriod, ?int $generatedBy = null): array
    {
        $userIds = $this->getEligibleUserIds();
        $generated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($userIds as $userId) {
            if ($this->alreadyExistsForUserInPeriod($userId, $billingPeriod)) {
                $skipped++;

                continue;
            }

            try {
                $invoice = $this->generateForUser($userId, $billingStart, $billingEnd, $billingPeriod, $generatedBy);
                if ($invoice) {
                    $generated++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error('IxMembershipInvoiceService: failed for user', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['generated' => $generated, 'skipped' => $skipped, 'failed' => $failed];
    }
}
