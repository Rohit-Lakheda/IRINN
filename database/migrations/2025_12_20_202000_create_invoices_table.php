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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('billing_period')->nullable(); // e.g., "2025-01", "2025-Q1", "2025"
            $table->decimal('amount', 10, 2);
            $table->decimal('gst_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('INR');
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->text('payu_payment_link')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('admins')->onDelete('set null');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->index('application_id');
            $table->index('invoice_number');
            $table->index('status');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
