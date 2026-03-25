<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_registrations', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name');
            $table->string('mobile_number')->unique();
            $table->string('email')->unique();
            $table->string('passing_year_batch');
            $table->string('student_id_or_roll')->nullable()->unique();
            $table->string('current_city')->nullable();
            $table->string('email_code', 6)->nullable();
            $table->string('mobile_code', 6)->nullable();
            $table->timestamp('email_code_expires_at')->nullable();
            $table->timestamp('mobile_code_expires_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('mobile_verified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_registrations');
    }
};
