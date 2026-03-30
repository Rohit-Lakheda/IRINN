<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MarkExistingApplicationsRead extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mark-existing-applications-read';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark all existing submitted applications as read for all admins';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Marking existing submitted applications as read for all admins…');

        // Use a transaction to be safe
        DB::beginTransaction();

        try {
            // Get all submitted IX applications (these are considered existing applications)
            $applicationsQuery = Application::query()
                ->where('application_type', 'IRINN')
                ->whereNotNull('submitted_at')
                ->select('id');

            // Get all admins
            $admins = Admin::query()->select('id')->get();

            if ($applicationsQuery->count() === 0 || $admins->isEmpty()) {
                DB::commit();
                $this->info('No submitted applications or admins found. Nothing to mark.');

                return self::SUCCESS;
            }

            // Build rows for pivot table in chunks to avoid memory issues
            $this->info('Preparing records…');

            $now = now();
            $totalInserted = 0;

            $applicationsQuery->chunkById(500, function ($applications) use ($admins, $now, &$totalInserted): void {
                $rows = [];

                foreach ($applications as $application) {
                    foreach ($admins as $admin) {
                        $rows[] = [
                            'admin_id' => $admin->id,
                            'application_id' => $application->id,
                            'read_at' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if (! empty($rows)) {
                    // Use insertOrIgnore to respect the unique constraint without failing
                    $totalInserted += DB::table('admin_application_reads')->insertOrIgnore($rows);
                }
            });

            DB::commit();

            $this->info("Marked existing applications as read. New pivot rows inserted: {$totalInserted}.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed to mark existing applications as read: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
