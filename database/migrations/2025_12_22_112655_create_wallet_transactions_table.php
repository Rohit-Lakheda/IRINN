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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('registrations')->onDelete('cascade');
            $table->enum('transaction_type', ['credit', 'debit', 'refund', 'creation', 'sync']);
            $table->string('transaction_id')->nullable(); // PayU transaction ID, nullable for sync operations
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->onDelete('set null');
            $table->foreignId('application_id')->nullable()->constrained('applications')->onDelete('set null');
            $table->text('description')->nullable();
            $table->json('payu_response')->nullable();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->boolean('sync_source')->default(false); // true if transaction was synced from PayU
            $table->timestamps();

            $table->index('wallet_id');
            $table->index('user_id');
            $table->index('transaction_id');
            $table->index('transaction_type');
            $table->index('status');
            $table->index('payment_transaction_id');
            $table->index('application_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
