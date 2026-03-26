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
        Schema::table('ix_locations', function (Blueprint $table) {
            $table->string('p2p_capacity')->nullable()->after('zone');
            $table->string('p2p_provider')->nullable()->after('p2p_capacity');
            $table->string('connected_main_node')->nullable()->after('p2p_provider');
            $table->decimal('p2p_arc', 10, 2)->nullable()->after('connected_main_node');
            $table->string('colocation_provider')->nullable()->after('p2p_arc');
            $table->decimal('colocation_arc', 10, 2)->nullable()->after('colocation_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ix_locations', function (Blueprint $table) {
            $table->dropColumn([
                'p2p_capacity',
                'p2p_provider',
                'connected_main_node',
                'p2p_arc',
                'colocation_provider',
                'colocation_arc',
            ]);
        });
    }
};
