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
        Schema::create('ix_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('state');
            $table->string('city')->nullable();
            $table->enum('node_type', ['metro', 'edge'])->default('edge');
            $table->string('switch_details')->nullable();
            $table->unsignedSmallInteger('ports')->nullable();
            $table->string('nodal_officer')->nullable();
            $table->string('zone')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['state', 'node_type']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ix_locations');
    }
};
