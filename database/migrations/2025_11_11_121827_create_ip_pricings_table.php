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
        Schema::create('ip_pricings', function (Blueprint $table) {
            $table->id();
            $table->string('ip_type'); // 'ipv4' or 'ipv6'
            $table->string('size'); // '/24', '/23', '/48', '/32'
            $table->integer('addresses'); // Number of IP addresses
            $table->decimal('base_price', 10, 2); // Base price for calculation
            $table->decimal('multiplier', 5, 2)->default(1.35); // Multiplier for calculation
            $table->integer('log_base')->default(8); // Log base for IPv4 (8) or IPv6 (22)
            $table->decimal('fixed_price', 10, 2)->nullable(); // Fixed price if calculation not needed
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['ip_type', 'size']);
            $table->index('ip_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_pricings');
    }
};
