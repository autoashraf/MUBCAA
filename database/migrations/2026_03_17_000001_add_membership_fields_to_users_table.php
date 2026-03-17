<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone')->nullable()->after('email');
            $table->string('role')->default('member')->after('password');
            $table->string('membership_status')->default('pending')->after('role');
            $table->unsignedTinyInteger('approval_step')->default(1)->after('membership_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['phone', 'role', 'membership_status', 'approval_step']);
        });
    }
};
