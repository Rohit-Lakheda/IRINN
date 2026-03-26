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
        Schema::create('pricing_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_id')->constrained('ip_pricings')->onDelete('cascade');
            $table->foreignId('payment_type_id')->nullable()->constrained('payment_types')->onDelete('set null');
            $table->json('old_data')->nullable(); // Store old pricing data
            $table->json('new_data')->nullable(); // Store new pricing data
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->string('updated_by')->nullable(); // Who made the change (admin/superadmin name or ID)
            $table->string('change_type'); // 'created', 'updated', 'scheduled', 'activated', 'deactivated', 'deleted'
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('pricing_id');
            $table->index('payment_type_id');
            $table->index('change_type');
            $table->index('effective_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_history');
    }
};
