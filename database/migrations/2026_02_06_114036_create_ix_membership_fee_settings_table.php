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
        Schema::create('ix_membership_fee_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->decimal('gst_percentage', 5, 2)->default(18.00);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::table('ix_membership_fee_settings', function (Blueprint $table) {
            $table->foreign('updated_by')->references('id')->on('superadmins')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ix_membership_fee_settings');
    }
};
