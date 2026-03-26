<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('master_otps', function (Blueprint $table) {
            $table->id();
            $table->string('otp', 6)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default master OTP
        DB::table('master_otps')->insert([
            'otp' => '123456',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_otps');
    }
};
