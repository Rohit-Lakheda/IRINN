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
        Schema::create('grievance_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('grievance_categories')->onDelete('cascade');
            $table->foreignId('subcategory_id')->nullable()->constrained('grievance_subcategories')->onDelete('cascade');
            $table->string('assigned_role'); // Role slug (e.g., 'ix_account', 'nodal_officer')
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Lower number = higher priority (for multiple assignments)
            $table->timestamps();
            
            $table->unique(['category_id', 'subcategory_id', 'assigned_role'], 'unique_assignment');
            $table->index('category_id');
            $table->index('subcategory_id');
            $table->index('assigned_role');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grievance_assignments');
    }
};
