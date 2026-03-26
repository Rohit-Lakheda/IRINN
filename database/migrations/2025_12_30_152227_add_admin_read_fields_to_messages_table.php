<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('admin_read')->default(false)->after('read_at');
            $table->timestamp('admin_read_at')->nullable()->after('admin_read');
            $table->index('admin_read');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['admin_read']);
            $table->dropColumn(['admin_read', 'admin_read_at']);
        });
    }
};
