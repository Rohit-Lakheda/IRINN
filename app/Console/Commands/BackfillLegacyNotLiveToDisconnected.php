<?php

namespace App\Console\Commands;

use App\Models\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillLegacyNotLiveToDisconnected extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-legacy-not-live-to-disconnected {--dry-run : Show how many rows would be updated without changing data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill service_status=disconnected for IRINN members where is_active=false';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Backfilling legacy NOT LIVE members to DISCONNECTED…');

        $baseQuery = Application::query()
            ->where('application_type', 'IRINN')
            ->whereNotNull('membership_id')
            ->where('is_active', false)
            ->where(function ($q) {
                $q->whereNull('service_status')
                    ->orWhere('service_status', 'live');
            });

        $toUpdate = (clone $baseQuery)->count();

        if ($toUpdate === 0) {
            $this->info('No matching legacy not-live members found. Nothing to update.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("Dry run: {$toUpdate} application(s) would be updated.");

            return self::SUCCESS;
        }

        DB::beginTransaction();

        try {
            $nowDate = now('Asia/Kolkata')->toDateString();

            $updated = $baseQuery->update([
                'service_status' => 'disconnected',
                // prefer existing deactivated_at date, otherwise set today (so it shows as disconnected)
                'disconnected_at' => DB::raw("COALESCE(DATE(deactivated_at), disconnected_at, '{$nowDate}')"),
                'updated_at' => now('Asia/Kolkata'),
            ]);

            DB::commit();

            $this->info("Updated {$updated} application(s) to DISCONNECTED.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Backfill failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
