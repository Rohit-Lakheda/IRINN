<?php

namespace App\Services;

use App\Http\Controllers\AdminController;
use App\Models\Application;
use App\Models\GstVerification;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class IrinnAnnualInvoiceService
{
    public const INVOICE_PURPOSE = 'irinn_annual';

    /**
     * India FY label e.g. 2024-25 and invoice prefix e.g. NXNIR24-25/
     *
     * @return array{fy: string, prefix: string}
     */
    public function fiscalYearAndPrefix(?Carbon $on = null): array
    {
        $d = ($on ?? now('Asia/Kolkata'))->copy();
        $year = (int) $d->year;
        $month = (int) $d->month;
        $fyStart = $month >= 4 ? $year : $year - 1;
        $fyEndShort = ($fyStart + 1) % 100;

        return [
            'fy' => $fyStart.'-'.str_pad((string) $fyEndShort, 2, '0', STR_PAD_LEFT),
            'prefix' => 'NXNIR'.substr((string) $fyStart, -2).'-'.str_pad((string) $fyEndShort, 2, '0', STR_PAD_LEFT).'/',
        ];
    }

    public function nextInvoiceNumber(string $prefix): string
    {
        $last = Invoice::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('invoice_number');

        $seq = 1;
        if ($last && preg_match('/'.preg_quote($prefix, '/').'(\d+)$/', (string) $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix.$seq;
    }

    /**
     * India financial year is April–March.
     *
     * This is kept only as a legacy fallback for older invoices that may not have
     * `resources_as_on_iso` in invoice line meta.
     */
    public function fiscalYearEndMarchDateForInvoice(Carbon $invoiceDate): Carbon
    {
        $y = (int) $invoiceDate->year;
        $m = (int) $invoiceDate->month;
        $fyEndYear = $m >= 4 ? $y + 1 : $y;

        return Carbon::create($fyEndYear, 3, 31, 0, 0, 0, 'Asia/Kolkata')->startOfDay();
    }

    /**
     * Fiscal year bounds (April 1 - March 31) for a given date.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    private function fiscalYearBounds(Carbon $on): array
    {
        $year = (int) $on->year;
        $month = (int) $on->month;

        $fyStartYear = $month >= 4 ? $year : $year - 1;
        $fyEndYear = $fyStartYear + 1;

        return [
            'start' => Carbon::create($fyStartYear, 4, 1, 0, 0, 0, 'Asia/Kolkata')->startOfDay(),
            'end' => Carbon::create($fyEndYear, 3, 31, 0, 0, 0, 'Asia/Kolkata')->startOfDay(),
        ];
    }

    private function ensureIrinnResourcesAllocatedForAnnualInvoice(Application $application): void
    {
        if (! $application->irinn_resources_allocated || ! $application->billing_anchor_date) {
            throw new \InvalidArgumentException(
                'Hostmaster must confirm resource allocation and set the allocation date before generating or previewing annual invoices.'
            );
        }
    }

    /**
     * @return array{base: float, discount_percent: float, discount_amount: float, after_rebate: float, gst_amount: float, total: float}
     */
    public function calculateTotals(float $baseBeforeDiscount, float $discountPercent): array
    {
        $discountPercent = max(0, min(100, $discountPercent));
        $base = round($baseBeforeDiscount, 2);
        $discountAmount = round($base * ($discountPercent / 100), 2);
        $afterRebate = round($base - $discountAmount, 2);
        $gstAmount = round($afterRebate * 0.18, 2);
        $total = round($afterRebate + $gstAmount, 2);

        return [
            'base' => $base,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'after_rebate' => $afterRebate,
            'gst_amount' => $gstAmount,
            'total' => $total,
        ];
    }

    /**
     * Admin preview before generating an annual invoice (no database writes).
     *
     * @return array<string, mixed>
     */
    public function preview(Application $application, float $annualBaseAmount): array
    {
        if ($application->application_type !== 'IRINN') {
            throw new \InvalidArgumentException('Only IRINN applications support this invoice.');
        }

        if ($annualBaseAmount <= 0) {
            throw new \InvalidArgumentException('Annual base amount must be greater than zero.');
        }

        $this->ensureIrinnResourcesAllocatedForAnnualInvoice($application);

        $application->loadMissing('user');

        $tz = 'Asia/Kolkata';
        $allocationDate = $application->billing_anchor_date;
        if (! $allocationDate instanceof Carbon) {
            $allocationDate = Carbon::parse((string) $application->billing_anchor_date, $tz);
        }
        $allocationAsOnDate = $allocationDate->copy()->startOfDay();

        $invoiceDate = now($tz)->startOfDay();
        $dueDate = $invoiceDate->copy()->addMonth();

        $fy = $this->fiscalYearAndPrefix($allocationAsOnDate);
        $billingPeriod = $fy['fy'];
        $proposedInvoiceNumber = $this->nextInvoiceNumber($fy['prefix']);

        $duplicateExists = Invoice::query()
            ->where('application_id', $application->id)
            ->where('billing_period', $billingPeriod)
            ->where('invoice_purpose', self::INVOICE_PURPOSE)
            ->where('status', '!=', 'cancelled')
            ->exists();

        $discountPercent = (float) ($application->irinn_billing_discount_percent ?? 0);
        $amounts = $this->calculateTotals($annualBaseAmount, $discountPercent);

        $ipv4 = (int) ($application->irinn_ipv4_resource_addresses ?? 0);
        $ipv6 = (int) ($application->irinn_ipv6_resource_addresses ?? 0);

        $buyerGstDisplay = $application->irinn_has_gst_number
            ? strtoupper(trim((string) ($application->irinn_billing_gstin ?? '')))
            : 'NA (GST not registered / not provided)';
        if ($buyerGstDisplay === '') {
            $buyerGstDisplay = 'NA';
        }

        $buyerLegalName = $application->irinn_billing_legal_name
            ?? $application->user?->fullname
            ?? '—';

        $buyerAddress = trim(implode(', ', array_filter([
            (string) ($application->irinn_billing_address ?? ''),
            (string) ($application->irinn_billing_postcode ?? ''),
        ]))) ?: '—';

        return [
            'financial_year' => $billingPeriod,
            'proposed_invoice_number' => $proposedInvoiceNumber,
            'invoice_date' => $invoiceDate->format('d/m/Y'),
            'due_date' => $dueDate->format('d/m/Y'),
            'as_on_for_resources' => $allocationAsOnDate->format('d F Y'),
            'allocation_date' => $allocationAsOnDate->format('d F Y'),
            'seller_legal_name' => 'NATIONAL INTERNET EXCHANGE OF INDIA',
            'seller_gstin' => '07AABCN9308A1ZT',
            'seller_address' => '9th Floor, B-Wing, Statesman House, 148, Barakhamba Road, New Delhi-110001',
            'seller_pan' => 'AABCN9308A',
            'buyer_legal_name' => $buyerLegalName,
            'buyer_address' => $buyerAddress,
            'buyer_gstin' => $buyerGstDisplay,
            'buyer_pan' => strtoupper(trim((string) ($application->irinn_billing_pan ?? $application->user?->pancardno ?? ''))) ?: '—',
            'ipv4_addresses' => $ipv4,
            'ipv6_addresses' => $ipv6,
            'annual_base_before_discount' => $amounts['base'],
            'discount_percent' => $amounts['discount_percent'],
            'discount_amount' => $amounts['discount_amount'],
            'taxable_after_discount' => $amounts['after_rebate'],
            'igst_rate_percent' => 18,
            'igst_amount' => $amounts['gst_amount'],
            'grand_total' => $amounts['total'],
            'line_description' => 'Annual renewal fee (IRINN resources) — full financial year '.$billingPeriod,
            'would_request_einvoice' => $this->shouldCallEinvoiceApi($application),
            'duplicate_invoice_exists_for_fy' => $duplicateExists,
        ];
    }

    public function shouldCallEinvoiceApi(Application $application): bool
    {
        if (! $application->irinn_has_gst_number) {
            return false;
        }

        $gst = strtoupper(trim((string) ($application->irinn_billing_gstin ?? '')));

        return strlen($gst) === 15 && preg_match('/^[0-9]{2}[A-Z0-9]{13}$/', $gst) === 1;
    }

    /**
     * @param  array{annual_base_amount: float|int|string}  $input
     */
    public function generate(Application $application, array $input, int $adminId): Invoice
    {
        if ($application->application_type !== 'IRINN') {
            throw new \InvalidArgumentException('Only IRINN applications support this invoice.');
        }

        $baseInput = (float) $input['annual_base_amount'];
        if ($baseInput <= 0) {
            throw new \InvalidArgumentException('Annual base amount must be greater than zero.');
        }

        $this->ensureIrinnResourcesAllocatedForAnnualInvoice($application);

        $tz = 'Asia/Kolkata';
        $allocationDate = $application->billing_anchor_date;
        if (! $allocationDate instanceof Carbon) {
            $allocationDate = Carbon::parse((string) $application->billing_anchor_date, $tz);
        }
        $asOnDate = $allocationDate->copy()->startOfDay();

        $invoiceDate = now($tz)->startOfDay();
        $dueDate = $invoiceDate->copy()->addMonth();

        $fy = $this->fiscalYearAndPrefix($asOnDate);
        $billingPeriod = $fy['fy'];
        $invoiceNumber = $this->nextInvoiceNumber($fy['prefix']);

        $exists = Invoice::query()
            ->where('application_id', $application->id)
            ->where('billing_period', $billingPeriod)
            ->where('invoice_purpose', self::INVOICE_PURPOSE)
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($exists) {
            throw new \RuntimeException('An annual IRINN invoice already exists for fiscal year '.$billingPeriod.'.');
        }

        $discountPercent = (float) ($application->irinn_billing_discount_percent ?? 0);
        $amounts = $this->calculateTotals($baseInput, $discountPercent);

        $ipv4 = (int) ($application->irinn_ipv4_resource_addresses ?? 0);
        $ipv6 = (int) ($application->irinn_ipv6_resource_addresses ?? 0);

        $lineItems = [
            [
                'description' => 'Annual renewal fee (IRINN resources)',
                'quantity' => 1,
                'rate' => $amounts['after_rebate'],
                'amount' => $amounts['after_rebate'],
            ],
        ];

        $bounds = $this->fiscalYearBounds($asOnDate);
        $billingStart = $bounds['start'];
        $billingEnd = $bounds['end'];

        $invoice = Invoice::create([
            'application_id' => $application->id,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'billing_period' => $billingPeriod,
            'billing_start_date' => $billingStart->toDateString(),
            'billing_end_date' => $billingEnd->toDateString(),
            'invoice_purpose' => self::INVOICE_PURPOSE,
            'line_items' => array_merge($lineItems, [
                '_irinn_meta' => [
                    'annual_base_before_discount' => $amounts['base'],
                    'discount_percent' => $amounts['discount_percent'],
                    'discount_amount' => $amounts['discount_amount'],
                    'ipv4_addresses' => $ipv4,
                    'ipv6_addresses' => $ipv6,
                    'resources_as_on_iso' => $asOnDate->toDateString(),
                    'billing_anchor_date' => $application->billing_anchor_date?->toDateString(),
                ],
            ]),
            'amount' => $amounts['after_rebate'],
            'gst_amount' => $amounts['gst_amount'],
            'tds_percentage' => 0,
            'tds_amount' => 0,
            'total_amount' => $amounts['total'],
            'paid_amount' => 0,
            'balance_amount' => $amounts['total'],
            'payment_status' => 'pending',
            'carry_forward_amount' => 0,
            'has_carry_forward' => false,
            'currency' => 'INR',
            'status' => 'pending',
            'generated_by' => $adminId,
        ]);

        $application->load('user');

        if ($this->shouldCallEinvoiceApi($application) && filled((string) config('services.einvoice.url'))) {
            $originalKyc = $application->kyc_details;
            try {
                $mergedKyc = array_merge(is_array($originalKyc) ? $originalKyc : [], $this->buildSyntheticKycForEinvoice($application));
                $application->kyc_details = $mergedKyc;

                $einvoiceData = app(AdminController::class)->callEinvoiceApiForInvoice($application, $invoice);

                if (is_array($einvoiceData)) {
                    $status = $einvoiceData['Status'] ?? $einvoiceData['status'] ?? '';
                    $irn = $einvoiceData['Irn'] ?? '';
                    $err = $einvoiceData['ErrorCode'] ?? '';

                    if (filled($irn) || (string) $status === '1' || strtolower((string) $status) === 'act') {
                        $signed = [];
                        if (isset($einvoiceData['SignedInvoice'])) {
                            $signed['SignedInvoice'] = $einvoiceData['SignedInvoice'];
                        }
                        if (isset($einvoiceData['SignedQRCode'])) {
                            $signed['SignedQRCode'] = $einvoiceData['SignedQRCode'];
                        }
                        $invoice->update([
                            'einvoice_signed_data' => ! empty($signed) ? $signed : null,
                            'einvoice_irn' => $irn ?: null,
                            'einvoice_ack_no' => isset($einvoiceData['AckNo']) ? (string) $einvoiceData['AckNo'] : null,
                            'einvoice_ack_date' => isset($einvoiceData['AckDate']) ? $einvoiceData['AckDate'] : null,
                            'einvoice_status' => is_string($status) ? $status : null,
                            'einvoice_error_code' => $err ?: null,
                            'einvoice_error_message' => $einvoiceData['ErrorMessage'] ?? null,
                            'einvoice_response' => $einvoiceData,
                        ]);
                    } else {
                        Log::warning('IRINN annual e-invoice did not return IRN; invoice saved without IRN.', [
                            'invoice_id' => $invoice->id,
                            'response' => $einvoiceData,
                        ]);
                    }
                }
            } catch (Throwable $e) {
                Log::error('IRINN annual e-invoice API failed; invoice kept without IRN: '.$e->getMessage(), [
                    'invoice_id' => $invoice->id,
                ]);
            } finally {
                $application->kyc_details = $originalKyc;
                $application->syncOriginal();
            }
        }

        $invoice->refresh();
        $this->generateAndStorePdf($application, $invoice, $asOnDate);

        return $invoice->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSyntheticKycForEinvoice(Application $application): array
    {
        $user = $application->user;
        $addr = trim(implode("\n", array_filter([
            (string) ($application->irinn_billing_address ?? ''),
            (string) ($application->irinn_billing_postcode ?? ''),
        ])));

        return [
            'gstin' => strtoupper(trim((string) ($application->irinn_billing_gstin ?? ''))),
            'billing_address' => $addr !== '' ? $addr : ($user->address ?? ''),
            'contact_name' => (string) ($application->irinn_billing_legal_name ?? $application->irinn_mr_name ?? $user?->fullname ?? ''),
            'contact_email' => (string) ($application->irinn_mr_email ?? $user?->email ?? ''),
            'contact_mobile' => (string) ($application->irinn_mr_mobile ?? $user?->mobile ?? ''),
        ];
    }

    public function generateAndStorePdf(Application $application, Invoice $invoice, ?Carbon $asOnDate = null): void
    {
        $meta = is_array($invoice->line_items) ? ($invoice->line_items['_irinn_meta'] ?? []) : [];

        if (! empty($meta['resources_as_on_iso'])) {
            $asOn = Carbon::parse((string) $meta['resources_as_on_iso'], 'Asia/Kolkata')->startOfDay();
        } elseif ($asOnDate instanceof Carbon) {
            $asOn = $asOnDate->copy()->startOfDay();
        } else {
            $invoiceDate = $invoice->invoice_date
                ? Carbon::parse($invoice->invoice_date, 'Asia/Kolkata')->startOfDay()
                : now('Asia/Kolkata')->startOfDay();
            $asOn = $this->fiscalYearEndMarchDateForInvoice($invoiceDate);
        }
        $ipv4 = (int) ($meta['ipv4_addresses'] ?? $application->irinn_ipv4_resource_addresses ?? 0);
        $ipv6 = (int) ($meta['ipv6_addresses'] ?? $application->irinn_ipv6_resource_addresses ?? 0);

        $portalLoginUrl = url(route('login.index', [], false));
        $invoicesUrl = url(route('user.invoices.index', [], false));

        $gstDisplay = $application->irinn_has_gst_number
            ? strtoupper(trim((string) ($application->irinn_billing_gstin ?? '')))
            : 'NA';
        if ($gstDisplay === '') {
            $gstDisplay = 'NA';
        }

        $panDisplay = strtoupper(trim((string) ($application->irinn_billing_pan ?? $application->user?->pancardno ?? '')));
        if ($panDisplay === '') {
            $panDisplay = 'NA';
        }

        $placeOfSupply = $this->resolvePlaceOfSupply($application, $gstDisplay);

        $pdf = Pdf::loadView('user.applications.irin.pdf.annual-billing-invoice', [
            'application' => $application,
            'invoice' => $invoice,
            'user' => $application->user,
            'asOnFormatted' => $asOn->format('d F Y'),
            'ipv4Count' => $ipv4,
            'ipv6Count' => $ipv6,
            'buyerLegalName' => $application->irinn_billing_legal_name ?? $application->user?->fullname ?? '—',
            'buyerAddress' => trim(implode(', ', array_filter([
                (string) ($application->irinn_billing_address ?? ''),
                (string) ($application->irinn_billing_postcode ?? ''),
            ]))) ?: '—',
            'attnName' => $application->irinn_mr_name ?? '—',
            'gstDisplay' => $gstDisplay,
            'panDisplay' => $panDisplay,
            'placeOfSupply' => $placeOfSupply,
            'accountShort' => strtoupper(substr((string) ($application->irinn_account_name ?? 'MEMBER'), 0, 12)),
            'portalLoginUrl' => $portalLoginUrl,
            'invoicesUrl' => $invoicesUrl,
            'meta' => $meta,
        ])->setPaper('a4', 'portrait')
            ->setOption('margin-top', 8)
            ->setOption('margin-bottom', 8)
            ->setOption('margin-left', 8)
            ->setOption('margin-right', 8)
            ->setOption('enable-local-file-access', true);

        $safeName = str_replace(['/', '\\'], '-', $invoice->invoice_number).'_annual.pdf';
        $path = 'applications/'.$application->user_id.'/irin/'.$safeName;
        Storage::disk('public')->put($path, $pdf->output());
        $invoice->update(['pdf_path' => $path]);
    }

    private function resolvePlaceOfSupply(Application $application, string $gstDisplay): string
    {
        if (strlen($gstDisplay) >= 2 && ctype_digit(substr($gstDisplay, 0, 2))) {
            $code = substr($gstDisplay, 0, 2);
            $map = [
                '01' => 'Jammu & Kashmir', '02' => 'Himachal Pradesh', '03' => 'Punjab', '04' => 'Chandigarh',
                '05' => 'Uttarakhand', '06' => 'Haryana', '07' => 'Delhi', '08' => 'Rajasthan',
                '09' => 'Uttar Pradesh', '10' => 'Bihar', '11' => 'Sikkim', '12' => 'Arunachal Pradesh',
                '13' => 'Nagaland', '14' => 'Manipur', '15' => 'Mizoram', '16' => 'Tripura',
                '17' => 'Meghalaya', '18' => 'Assam', '19' => 'West Bengal', '20' => 'Jharkhand',
                '21' => 'Odisha', '22' => 'Chhattisgarh', '23' => 'Madhya Pradesh', '24' => 'Gujarat',
                '26' => 'Dadra and Nagar Haveli and Daman and Diu', '27' => 'Maharashtra', '28' => 'Andhra Pradesh',
                '29' => 'Karnataka', '30' => 'Goa', '31' => 'Lakshadweep', '32' => 'Kerala',
                '33' => 'Tamil Nadu', '34' => 'Puducherry', '35' => 'Andaman & Nicobar Islands', '36' => 'Telangana',
                '37' => 'Andhra Pradesh', '38' => 'Ladakh',
            ];

            return $map[$code] ?? 'India';
        }

        $gst = GstVerification::query()
            ->where('user_id', $application->user_id)
            ->where('is_verified', true)
            ->latest()
            ->first();

        return $gst?->state ?? 'India';
    }
}
