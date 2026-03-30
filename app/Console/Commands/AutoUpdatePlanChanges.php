<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AutoUpdatePlanChanges extends Command
{
    protected $signature = 'plan-changes:auto-update';

    protected $description = 'Legacy IX plan change auto-update (disabled — IRINN-only portal)';

    public function handle(): int
    {
        $this->info('Skipped: plan change auto-update is disabled in the IRINN-only portal.');

        return Command::SUCCESS;
    }
}
