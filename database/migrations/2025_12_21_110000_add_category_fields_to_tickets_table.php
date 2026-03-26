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
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('category')->nullable()->after('type');
            $table->string('sub_category')->nullable()->after('category');
            $table->string('assigned_role')->nullable()->after('assigned_to'); // Store the role slug that should handle this ticket
            $table->foreignId('forwarded_by')->nullable()->after('assigned_by')->constrained('admins')->onDelete('set null');
            $table->timestamp('forwarded_at')->nullable()->after('forwarded_by');
            $table->text('forwarding_notes')->nullable()->after('forwarded_at');
            
            $table->index('category');
            $table->index('sub_category');
            $table->index('assigned_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['forwarded_by']);
            $table->dropIndex(['category']);
            $table->dropIndex(['sub_category']);
            $table->dropIndex(['assigned_role']);
            $table->dropColumn([
                'category',
                'sub_category',
                'assigned_role',
                'forwarded_by',
                'forwarded_at',
                'forwarding_notes',
            ]);
        });
    }
};

