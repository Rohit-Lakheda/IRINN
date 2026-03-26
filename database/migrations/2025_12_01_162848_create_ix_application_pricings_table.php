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
        Schema::create('ix_application_pricings', function (Blueprint $table) {
            $table->id();
            $table->decimal('application_fee', 10, 2)->default(1000.00)->comment('Base application fee');
            $table->decimal('gst_percentage', 5, 2)->default(18.00)->comment('GST percentage');
            $table->decimal('total_amount', 10, 2)->comment('Total amount including GST');
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ix_application_pricings');
    }
};
