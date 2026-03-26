<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\AdminAction;
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

class DeleteAllUsersAndAdmins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-all-users-and-admins {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all registered users and admins data except super admin. This includes all related data, logs, and storage files.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('force')) {
            if (! $this->confirm('⚠️  WARNING: This will delete ALL users, admins, and their related data (applications, KYC, payments, messages, etc.). Super admin will be preserved. Are you sure you want to continue?')) {
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

            // Step 3: Delete admin-related data
            $this->info('Step 3: Deleting admin-related data...');
            $this->deleteAdminRelatedData();

            // Step 4: Delete all admins
            $this->info('Step 4: Deleting all admins...');
            $deletedAdmins = Admin::query()->delete();
            $this->line("   ✓ Deleted {$deletedAdmins} admins");

            // Step 5: Clear storage files
            $this->info('Step 5: Clearing storage files...');
            $this->clearStorageFiles();

            // Step 6: Clear logs
            $this->info('Step 6: Clearing logs...');
            $this->clearLogs();

            // Step 7: Clear sessions
            $this->info('Step 7: Clearing sessions...');
            $this->clearSessions();

            DB::commit();

            $this->newLine();
            $this->info('✅ Successfully deleted all users and admins data (except super admin).');
            $this->info('   You can now create new users and admins.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error during deletion: '.$e->getMessage());
            Log::error('DeleteAllUsersAndAdmins command failed: '.$e->getMessage(), [
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

        // Applications
        $count = Application::query()->delete();
        $this->line("   ✓ Deleted {$count} applications");

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
     * Delete all admin-related data.
     */
    private function deleteAdminRelatedData(): void
    {
        // Admin Actions (only those related to admins, not super admin)
        $count = AdminAction::whereNotNull('admin_id')->delete();
        $this->line("   ✓ Deleted {$count} admin actions");

        // Admin Role pivot table
        $count = DB::table('admin_role')->delete();
        $this->line("   ✓ Deleted {$count} admin role assignments");

        // Update applications to remove admin references (set to null)
        $count = Application::whereNotNull('current_processor_id')
            ->orWhereNotNull('current_finance_id')
            ->orWhereNotNull('current_technical_id')
            ->orWhereNotNull('current_ix_processor_id')
            ->orWhereNotNull('current_ix_legal_id')
            ->orWhereNotNull('current_ix_head_id')
            ->orWhereNotNull('current_ceo_id')
            ->orWhereNotNull('current_nodal_officer_id')
            ->orWhereNotNull('current_ix_tech_team_id')
            ->orWhereNotNull('current_ix_account_id')
            ->update([
                'current_processor_id' => null,
                'current_finance_id' => null,
                'current_technical_id' => null,
                'current_ix_processor_id' => null,
                'current_ix_legal_id' => null,
                'current_ix_head_id' => null,
                'current_ceo_id' => null,
                'current_nodal_officer_id' => null,
                'current_ix_tech_team_id' => null,
                'current_ix_account_id' => null,
            ]);
        $this->line('   ✓ Cleared admin references from applications');
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
