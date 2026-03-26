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
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('registrationid')->unique();
            $table->string('pancardno', 10)->unique();
            $table->string('fullname');
            $table->string('email')->unique();
            $table->string('mobile', 15);
            $table->string('password');
            $table->date('dateofbirth');
            $table->date('registrationdate');
            $table->time('registrationtime');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
