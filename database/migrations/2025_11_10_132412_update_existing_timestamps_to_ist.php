<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration converts existing UTC timestamps to IST (UTC+5:30)
     * by adding 5 hours and 30 minutes to all timestamp columns.
     */
    public function up(): void
    {
        // Set timezone for this migration
        date_default_timezone_set('Asia/Kolkata');
        
        // Update registrations table
        if (Schema::hasTable('registrations')) {
            DB::statement("UPDATE registrations SET 
                created_at = DATE_ADD(created_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE,
                updated_at = DATE_ADD(updated_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE
                WHERE created_at < '2025-11-10 12:00:00'");
        }

        // Update admins table
        if (Schema::hasTable('admins')) {
            DB::statement("UPDATE admins SET 
                created_at = DATE_ADD(created_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE,
                updated_at = DATE_ADD(updated_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE
                WHERE created_at < '2025-11-10 12:00:00'");
        }

        // Update superadmins table
        if (Schema::hasTable('superadmins')) {
            DB::statement("UPDATE superadmins SET 
                created_at = DATE_ADD(created_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE,
                updated_at = DATE_ADD(updated_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE
                WHERE created_at < '2025-11-10 12:00:00'");
        }

        // Update messages table
        if (Schema::hasTable('messages')) {
            DB::statement("UPDATE messages SET 
                created_at = DATE_ADD(created_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE,
                updated_at = DATE_ADD(updated_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE,
                read_at = CASE 
                    WHEN read_at IS NOT NULL THEN DATE_ADD(read_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE
                    ELSE NULL
                END
                WHERE created_at < '2025-11-10 12:00:00'");
        }

        // Update profile_update_requests table
        if (Schema::hasTable('profile_update_requests')) {
            DB::statement("UPDATE profile_update_requests SET 
                created_at = DATE_ADD(created_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE,
                updated_at = DATE_ADD(updated_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE,
                approved_at = CASE 
                    WHEN approved_at IS NOT NULL THEN DATE_ADD(approved_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE
                    ELSE NULL
                END,
                rejected_at = CASE 
                    WHEN rejected_at IS NOT NULL THEN DATE_ADD(rejected_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE
                    ELSE NULL
                END
                WHERE created_at < '2025-11-10 12:00:00'");
        }

        // Update applications table
        if (Schema::hasTable('applications')) {
            DB::statement("UPDATE applications SET 
                created_at = DATE_ADD(created_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE,
                updated_at = DATE_ADD(updated_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE,
                submitted_at = CASE 
                    WHEN submitted_at IS NOT NULL THEN DATE_ADD(submitted_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE
                    ELSE NULL
                END,
                approved_at = CASE 
                    WHEN approved_at IS NOT NULL THEN DATE_ADD(approved_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE
                    ELSE NULL
                END
                WHERE created_at < '2025-11-10 12:00:00'");
        }

        // Update application_status_history table
        if (Schema::hasTable('application_status_history')) {
            DB::statement("UPDATE application_status_history SET 
                created_at = DATE_ADD(created_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE,
                updated_at = DATE_ADD(updated_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE
                WHERE created_at < '2025-11-10 12:00:00'");
        }

        // Update admin_actions table
        if (Schema::hasTable('admin_actions')) {
            DB::statement("UPDATE admin_actions SET 
                created_at = DATE_ADD(created_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE,
                updated_at = DATE_ADD(updated_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE
                WHERE created_at < '2025-11-10 12:00:00'");
        }

        // Update roles table
        if (Schema::hasTable('roles')) {
            DB::statement("UPDATE roles SET 
                created_at = DATE_ADD(created_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE,
                updated_at = DATE_ADD(updated_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE
                WHERE created_at < '2025-11-10 12:00:00'");
        }

        // Update admin_role table
        if (Schema::hasTable('admin_role')) {
            DB::statement("UPDATE admin_role SET 
                created_at = DATE_ADD(created_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE,
                updated_at = DATE_ADD(updated_at, INTERVAL 5 HOUR) + INTERVAL 30 MINUTE
                WHERE created_at < '2025-11-10 12:00:00'");
        }
    }

    /**
     * Reverse the migrations.
     * 
     * This will convert IST timestamps back to UTC by subtracting 5:30 hours.
     */
    public function down(): void
    {
        // Update registrations table
        if (Schema::hasTable('registrations')) {
            DB::statement("UPDATE registrations SET 
                created_at = DATE_SUB(created_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE,
                updated_at = DATE_SUB(updated_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE");
        }

        // Update admins table
        if (Schema::hasTable('admins')) {
            DB::statement("UPDATE admins SET 
                created_at = DATE_SUB(created_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE,
                updated_at = DATE_SUB(updated_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE");
        }

        // Update superadmins table
        if (Schema::hasTable('superadmins')) {
            DB::statement("UPDATE superadmins SET 
                created_at = DATE_SUB(created_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE,
                updated_at = DATE_SUB(updated_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE");
        }

        // Update messages table
        if (Schema::hasTable('messages')) {
            DB::statement("UPDATE messages SET 
                created_at = DATE_SUB(created_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE,
                updated_at = DATE_SUB(updated_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE,
                read_at = CASE 
                    WHEN read_at IS NOT NULL THEN DATE_SUB(read_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE
                    ELSE NULL
                END");
        }

        // Update profile_update_requests table
        if (Schema::hasTable('profile_update_requests')) {
            DB::statement("UPDATE profile_update_requests SET 
                created_at = DATE_SUB(created_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE,
                updated_at = DATE_SUB(updated_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE,
                approved_at = CASE 
                    WHEN approved_at IS NOT NULL THEN DATE_SUB(approved_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE
                    ELSE NULL
                END,
                rejected_at = CASE 
                    WHEN rejected_at IS NOT NULL THEN DATE_SUB(rejected_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE
                    ELSE NULL
                END");
        }

        // Update applications table
        if (Schema::hasTable('applications')) {
            DB::statement("UPDATE applications SET 
                created_at = DATE_SUB(created_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE,
                updated_at = DATE_SUB(updated_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE,
                submitted_at = CASE 
                    WHEN submitted_at IS NOT NULL THEN DATE_SUB(submitted_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE
                    ELSE NULL
                END,
                approved_at = CASE 
                    WHEN approved_at IS NOT NULL THEN DATE_SUB(approved_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE
                    ELSE NULL
                END");
        }

        // Update application_status_history table
        if (Schema::hasTable('application_status_history')) {
            DB::statement("UPDATE application_status_history SET 
                created_at = DATE_SUB(created_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE,
                updated_at = DATE_SUB(updated_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE");
        }

        // Update admin_actions table
        if (Schema::hasTable('admin_actions')) {
            DB::statement("UPDATE admin_actions SET 
                created_at = DATE_SUB(created_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE,
                updated_at = DATE_SUB(updated_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE");
        }

        // Update roles table
        if (Schema::hasTable('roles')) {
            DB::statement("UPDATE roles SET 
                created_at = DATE_SUB(created_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE,
                updated_at = DATE_SUB(updated_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE");
        }

        // Update admin_role table
        if (Schema::hasTable('admin_role')) {
            DB::statement("UPDATE admin_role SET 
                created_at = DATE_SUB(created_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE,
                updated_at = DATE_SUB(updated_at, INTERVAL 5 HOUR) - INTERVAL 30 MINUTE");
        }
    }
};
