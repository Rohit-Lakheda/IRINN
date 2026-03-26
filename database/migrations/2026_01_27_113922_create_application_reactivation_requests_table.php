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
        Schema::create('application_reactivation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('registrations')->onDelete('cascade');

            $table->string('status')->default('pending'); // pending|approved|rejected|invoiced|paid|completed
            $table->text('user_notes')->nullable();
            $table->text('admin_notes')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('admins')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->timestamp('paid_at')->nullable();
            $table->date('reactivation_date')->nullable();

            $table->timestamps();

            $table->index(['application_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_reactivation_requests');
    }
};
