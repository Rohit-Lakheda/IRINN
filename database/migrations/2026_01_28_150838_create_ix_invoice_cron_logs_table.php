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
        Schema::create('ix_invoice_cron_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('run_id')->index();
            $table->string('command_name')->default('ix:generate-monthly-invoices');
            $table->boolean('is_dry_run')->default(false);

            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();

            $table->string('application_code')->nullable()->index(); // application_id (human readable) from applications table
            $table->string('billing_period')->nullable()->index();
            $table->date('billing_start_date')->nullable();
            $table->date('billing_end_date')->nullable();

            $table->string('invoice_number')->nullable()->index();

            $table->string('status')->index(); // started|generated|skipped|failed|dry_run
            $table->string('skip_reason')->nullable();
            $table->text('error_message')->nullable();

            $table->boolean('pdf_generated')->default(false);
            $table->string('pdf_path')->nullable();

            $table->boolean('mail_sent')->default(false);
            $table->timestamp('mail_sent_at')->nullable();

            $table->boolean('gstin_inactive')->default(false);
            $table->boolean('einvoice_attempted')->default(false);
            $table->string('einvoice_irn')->nullable();
            $table->string('einvoice_status')->nullable();
            $table->string('einvoice_error_code')->nullable();
            $table->text('einvoice_error_message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'billing_period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ix_invoice_cron_logs');
    }
};
