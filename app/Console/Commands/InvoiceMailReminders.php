<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InvoiceMailReminders extends Command
{
    protected $signature = 'membership:process-invoices {--user-id= : Filter by specific user ID} {--application-id= : Filter by specific application ID}';

    protected $description = 'Legacy IX membership invoice processing (disabled — IRINN-only portal)';

    public function handle(): int
    {
        $this->info('Skipped: legacy IX membership invoice processing is disabled in the IRINN-only portal.');

        return Command::SUCCESS;
    }
}
