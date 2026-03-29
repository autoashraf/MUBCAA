<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table): void {
            if (! Schema::hasColumn('pending_registrations', 'how_did_you_find_us')) {
                $table->string('how_did_you_find_us')->nullable()->after('passing_year_batch');
            }
        });

        Schema::table('member_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('member_profiles', 'how_did_you_find_us')) {
                $table->string('how_did_you_find_us')->nullable()->after('passing_year_batch');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table): void {
            if (Schema::hasColumn('pending_registrations', 'how_did_you_find_us')) {
                $table->dropColumn('how_did_you_find_us');
            }
        });

        Schema::table('member_profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('member_profiles', 'how_did_you_find_us')) {
                $table->dropColumn('how_did_you_find_us');
            }
        });
    }
};
