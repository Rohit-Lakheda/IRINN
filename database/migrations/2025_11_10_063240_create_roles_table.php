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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Processor, Finance, Technical
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // Insert default roles
        \DB::table('roles')->insert([
            ['name' => 'Processor', 'slug' => 'processor', 'description' => 'Process user applications and requests', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Finance', 'slug' => 'finance', 'description' => 'Handle financial transactions and approvals', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Technical', 'slug' => 'technical', 'description' => 'Handle technical issues and support', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
