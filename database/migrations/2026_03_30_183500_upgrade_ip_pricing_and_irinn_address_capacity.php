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
        Schema::table('ip_pricings', function (Blueprint $table) {
            $table->decimal('addresses', 39, 0)->change();
            $table->decimal('amount', 18, 2)->nullable()->change();
            $table->decimal('price', 18, 2)->nullable()->change();
            $table->decimal('igst', 18, 2)->nullable()->change();
            $table->decimal('cgst', 18, 2)->nullable()->change();
            $table->decimal('sgst', 18, 2)->nullable()->change();
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->decimal('irinn_ipv4_resource_addresses', 39, 0)->nullable()->change();
            $table->decimal('irinn_ipv6_resource_addresses', 39, 0)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ip_pricings', function (Blueprint $table) {
            $table->integer('addresses')->change();
            $table->decimal('amount', 10, 2)->nullable()->change();
            $table->decimal('price', 10, 2)->nullable()->change();
            $table->decimal('igst', 10, 2)->nullable()->change();
            $table->decimal('cgst', 10, 2)->nullable()->change();
            $table->decimal('sgst', 10, 2)->nullable()->change();
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->unsignedBigInteger('irinn_ipv4_resource_addresses')->nullable()->change();
            $table->unsignedBigInteger('irinn_ipv6_resource_addresses')->nullable()->change();
        });
    }
};
