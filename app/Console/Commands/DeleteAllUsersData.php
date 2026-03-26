<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\GstVerification;
use App\Models\McaVerification;
use App\Models\Message;
use App\Models\PanVerification;
use App\Models\PaymentTransaction;
use App\Models\ProfileUpdateRequest;
use App\Models\Registration;
use App\Models\RocIecVerification;
use App\Models\UdyamVerification;
use App\Models\UserKycProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeleteAllUsersData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-all-users-data {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all registered users and their related data (applications, KYC, payments, messages, etc.). Admins and Super Admins are preserved.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('force')) {
            if (! $this->confirm('⚠️  WARNING: This will delete ALL users and their related data (applications, KYC, payments, messages, sessions, etc.). Admins and Super Admins will be preserved. Are you sure you want to continue?')) {
                $this->info('Operation cancelled.');

                return Command::SUCCESS;
            }

            if (! $this->confirm('This action cannot be undone. Type "yes" to confirm deletion:')) {
                $this->info('Operation cancelled.');

                return Command::SUCCESS;
            }
        }

        $this->info('Starting deletion process...');
        $this->newLine();

        try {
            DB::beginTransaction();

            // Step 1: Delete all user-related data
            $this->info('Step 1: Deleting user-related data...');
            $this->deleteUserRelatedData();

            // Step 2: Delete all registrations (users)
            $this->info('Step 2: Deleting all user registrations...');
            $deletedUsers = Registration::query()->delete();
            $this->line("   ✓ Deleted {$deletedUsers} user registrations");

            // Also clear Laravel's default users table if it exists
            if (DB::getSchemaBuilder()->hasTable('users')) {
                $count = DB::table('users')->delete();
                $this->line("   ✓ Deleted {$count} records from users table");
            }

            // Clear password reset tokens
            if (DB::getSchemaBuilder()->hasTable('password_reset_tokens')) {
                $count = DB::table('password_reset_tokens')->delete();
                $this->line("   ✓ Deleted {$count} password reset tokens");
            }

            // Step 3: Clear storage files
            $this->info('Step 3: Clearing storage files...');
            $this->clearStorageFiles();

            // Step 4: Clear logs
            $this->info('Step 4: Clearing logs...');
            $this->clearLogs();

            // Step 5: Clear sessions
            $this->info('Step 5: Clearing sessions...');
            $this->clearSessions();

            DB::commit();

            $this->newLine();
            $this->info('✅ Successfully deleted all users data (admins and super admins preserved).');
            $this->info('   You can now create new users.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error during deletion: '.$e->getMessage());
            Log::error('DeleteAllUsersData command failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Delete all user-related data.
     */
    private function deleteUserRelatedData(): void
    {
        // Application Status History (delete before applications due to foreign key)
        $count = ApplicationStatusHistory::query()->delete();
        $this->line("   ✓ Deleted {$count} application status history records");

        // Applications (includes assigned_ip, customer_id, membership_id, etc.)
        $count = Application::query()->delete();
        $this->line("   ✓ Deleted {$count} applications (including assigned IPs, customer IDs, membership IDs)");

        // User KYC Profiles
        $count = UserKycProfile::query()->delete();
        $this->line("   ✓ Deleted {$count} user KYC profiles");

        // Payment Transactions
        $count = PaymentTransaction::query()->delete();
        $this->line("   ✓ Deleted {$count} payment transactions");

        // Messages
        $count = Message::query()->delete();
        $this->line("   ✓ Deleted {$count} messages");

        // Profile Update Requests
        $count = ProfileUpdateRequest::query()->delete();
        $this->line("   ✓ Deleted {$count} profile update requests");

        // Verifications
        $count = PanVerification::query()->delete();
        $this->line("   ✓ Deleted {$count} PAN verifications");

        $count = GstVerification::query()->delete();
        $this->line("   ✓ Deleted {$count} GST verifications");

        $count = UdyamVerification::query()->delete();
        $this->line("   ✓ Deleted {$count} UDYAM verifications");

        $count = McaVerification::query()->delete();
        $this->line("   ✓ Deleted {$count} MCA verifications");

        $count = RocIecVerification::query()->delete();
        $this->line("   ✓ Deleted {$count} ROC IEC verifications");
    }

    /**
     * Clear storage files related to users.
     */
    private function clearStorageFiles(): void
    {
        try {
            // Delete applications directory
            $applicationsPath = storage_path('app/public/applications');
            if (File::exists($applicationsPath)) {
                File::deleteDirectory($applicationsPath);
                $this->line('   ✓ Deleted applications storage directory');
            }

            // Also use Storage facade for public disk
            if (Storage::disk('public')->exists('applications')) {
                Storage::disk('public')->deleteDirectory('applications');
                $this->line('   ✓ Cleared applications from public storage');
            }
        } catch (\Exception $e) {
            $this->warn("   ⚠ Could not delete storage files: {$e->getMessage()}");
        }
    }

    /**
     * Clear application logs.
     */
    private function clearLogs(): void
    {
        try {
            $logPath = storage_path('logs/laravel.log');
            if (File::exists($logPath)) {
                File::put($logPath, '');
                $this->line('   ✓ Cleared Laravel log file');
            }

            $browserLogPath = storage_path('logs/browser.log');
            if (File::exists($browserLogPath)) {
                File::put($browserLogPath, '');
                $this->line('   ✓ Cleared browser log file');
            }
        } catch (\Exception $e) {
            $this->warn("   ⚠ Could not clear logs: {$e->getMessage()}");
        }
    }

    /**
     * Clear session files.
     */
    private function clearSessions(): void
    {
        try {
            $sessionPath = storage_path('framework/sessions');
            if (File::exists($sessionPath)) {
                $files = File::files($sessionPath);
                $count = 0;
                foreach ($files as $file) {
                    if (File::isFile($file)) {
                        File::delete($file);
                        $count++;
                    }
                }
                $this->line("   ✓ Cleared {$count} session files");
            }
        } catch (\Exception $e) {
            $this->warn("   ⚠ Could not clear sessions: {$e->getMessage()}");
        }
    }
}
