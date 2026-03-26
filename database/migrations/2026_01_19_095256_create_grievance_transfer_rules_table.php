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
        Schema::create('grievance_transfer_rules', function (Blueprint $table) {
            $table->id();
            $table->string('from_role'); // Role slug that can transfer
            $table->string('to_role'); // Role slug that can receive
            $table->foreignId('category_id')->nullable()->constrained('grievance_categories')->onDelete('cascade');
            $table->foreignId('subcategory_id')->nullable()->constrained('grievance_subcategories')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('from_role');
            $table->index('to_role');
            $table->index('category_id');
            $table->index('subcategory_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grievance_transfer_rules');
    }
};
