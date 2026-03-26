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
        Schema::table('admin_actions', function (Blueprint $table) {
            $table->string('actionable_type')->nullable()->change();
            $table->unsignedBigInteger('actionable_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_actions', function (Blueprint $table) {
            $table->string('actionable_type')->nullable(false)->change();
            $table->unsignedBigInteger('actionable_id')->nullable(false)->change();
        });
    }
};
