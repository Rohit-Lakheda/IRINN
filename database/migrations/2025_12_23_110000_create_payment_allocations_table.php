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
        if (Schema::hasTable('payment_allocations')) {
            return;
        }
        
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('registrations')->onDelete('cascade');
            $table->decimal('allocated_amount', 10, 2);
            $table->string('payment_reference')->nullable(); // Payment ID/Reference from admin
            $table->text('notes')->nullable();
            $table->foreignId('allocated_by')->nullable()->constrained('admins')->onDelete('set null');
            $table->timestamps();
            
            $table->index('invoice_id');
            $table->index('application_id');
            $table->index('user_id');
            $table->index('allocated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};

