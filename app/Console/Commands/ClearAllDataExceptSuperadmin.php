<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearAllDataExceptSuperadmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clear-except-superadmin {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all data from all tables except superadmins table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('force')) {
            if (! $this->confirm('This will delete ALL data from all tables except superadmins. Are you sure?')) {
                $this->info('Operation cancelled.');

                return Command::SUCCESS;
            }
        }

        try {
            // Get all table names from the database
            $tables = DB::select('SHOW TABLES');
            $databaseName = DB::getDatabaseName();
            
            $tablesToClear = [];
            $excludedTables = [
                'superadmins',
                'migrations', // Keep migrations table
            ];

            foreach ($tables as $table) {
                // Get the first property value (table name)
                $tableArray = (array) $table;
                $tableName = reset($tableArray);
                
                if (! in_array($tableName, $excludedTables)) {
                    $tablesToClear[] = $tableName;
                }
            }

            if (empty($tablesToClear)) {
                $this->info('No tables to clear.');

                return Command::SUCCESS;
            }

            $this->info('Clearing data from the following tables:');
            foreach ($tablesToClear as $table) {
                $this->line("  - {$table}");
            }

            // Disable foreign key checks temporarily
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            $clearedCount = 0;
            foreach ($tablesToClear as $table) {
                try {
                    DB::table($table)->truncate();
                    $clearedCount++;
                    $this->info("✓ Cleared: {$table}");
                } catch (\Exception $e) {
                    // If truncate fails (e.g., for tables with foreign keys), try delete
                    try {
                        DB::table($table)->delete();
                        $clearedCount++;
                        $this->info("✓ Cleared (via delete): {$table}");
                    } catch (\Exception $e2) {
                        $this->error("✗ Failed to clear: {$table} - {$e2->getMessage()}");
                    }
                }
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->newLine();
            $this->info("Successfully cleared data from {$clearedCount} table(s).");
            $this->info('superadmins table data has been preserved.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            // Re-enable foreign key checks in case of error
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->error('Error clearing data: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
