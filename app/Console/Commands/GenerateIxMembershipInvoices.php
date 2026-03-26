<?php

namespace App\Console\Commands;

use App\Services\IxMembershipInvoiceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateIxMembershipInvoices extends Command
{
    protected $signature = 'ix:generate-membership-invoices
                            {--fy= : Financial year start (YYYY), e.g. 2025 for FY 2025-26}
                            {--user-id= : Generate only for this user ID}
                            {--force : Run even if not 1st April (for manual/cron)}';

    protected $description = 'Generate IX membership invoices for eligible customers (live applications). Runs on 1st April for new FY, or with --force for manual run.';

    public function handle(): int
    {
        $now = now('Asia/Kolkata');
        $force = (bool) $this->option('force');
        $userId = $this->option('user-id') ? (int) $this->option('user-id') : null;

        if (! $force && ((int) $now->format('d') !== 1 || (int) $now->format('m') !== 4)) {
            $this->info('Skipping: this command is intended to run on 1st April. Use --force to run manually.');

            return self::SUCCESS;
        }

        $service = app(IxMembershipInvoiceService::class);

        $fyYear = $this->option('fy') ? (int) $this->option('fy') : ($now->month >= 4 ? $now->year : $now->year - 1);
        $billingStart = Carbon::createFromDate($fyYear, 4, 1)->startOfDay()->setTimezone('Asia/Kolkata');
        $billingEnd = Carbon::createFromDate($fyYear + 1, 3, 31)->startOfDay()->setTimezone('Asia/Kolkata');
        $billingPeriod = 'MEM-'.$fyYear.'-'.($fyYear + 1);

        $this->info("IX Membership Invoices - FY {$fyYear}-".($fyYear + 1).' ('.$billingStart->format('Y-m-d').' to '.$billingEnd->format('Y-m-d').')');

        if ($userId) {
            if ($service->alreadyExistsForUserInPeriod($userId, $billingPeriod)) {
                $this->warn("User {$userId} already has a membership invoice for this period.");

                return self::SUCCESS;
            }
            $invoice = $service->generateForUser($userId, $billingStart, $billingEnd, $billingPeriod, null);
            if ($invoice) {
                $this->info("Generated membership invoice {$invoice->invoice_number} for user {$userId}");
            } else {
                $this->warn("Could not generate membership invoice for user {$userId} (not eligible or error).");
            }

            return self::SUCCESS;
        }

        $result = $service->generateForEligibleUsers($billingStart, $billingEnd, $billingPeriod, null);
        $this->info("Generated: {$result['generated']}, Skipped: {$result['skipped']}, Failed: {$result['failed']}");

        return self::SUCCESS;
    }
}
