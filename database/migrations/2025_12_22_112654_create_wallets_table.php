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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('registrations')->onDelete('cascade');
            $table->string('wallet_id')->unique()->nullable(); // PayU wallet identifier
            $table->enum('wallet_type', ['closed_loop', 'open_loop'])->default('closed_loop');
            $table->enum('status', ['active', 'suspended', 'closed'])->default('active');
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->string('currency', 3)->default('INR');
            $table->string('kyc_status')->nullable(); // For open-loop wallets
            $table->json('payu_wallet_data')->nullable(); // PayU response data
            $table->timestamps();

            $table->index('user_id');
            $table->index('wallet_id');
            $table->index('status');
            $table->index('wallet_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
