<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckWalletBalanceNotifications extends Command
{
    protected $signature = 'wallet:check-balance-notifications';

    protected $description = 'Legacy IX wallet billing notifications (disabled — IRINN-only portal)';

    public function handle(): int
    {
        $this->info('Skipped: legacy IX wallet balance checks are disabled in the IRINN-only portal.');

        return Command::SUCCESS;
    }
}
