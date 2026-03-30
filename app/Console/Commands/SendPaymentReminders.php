<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendPaymentReminders extends Command
{
    protected $signature = 'payments:send-reminders';

    protected $description = 'Legacy IX payment reminders (disabled — IRINN-only portal)';

    public function handle(): int
    {
        $this->info('Skipped: legacy IX payment reminders are disabled in the IRINN-only portal.');

        return Command::SUCCESS;
    }
}
