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
        Schema::create('ix_port_pricings', function (Blueprint $table) {
            $table->id();
            $table->enum('node_type', ['metro', 'edge'])->default('metro');
            $table->string('port_capacity');
            $table->decimal('price_arc', 12, 2)->default(0);
            $table->decimal('price_mrc', 12, 2)->default(0);
            $table->decimal('price_quarterly', 12, 2)->default(0);
            $table->string('currency')->default('INR');
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['node_type', 'port_capacity']);
            $table->index(['display_order', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ix_port_pricings');
    }
};
