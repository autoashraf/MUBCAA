<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'affiliate_code')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('affiliate_code')->nullable()->unique()->after('approval_step');
            });
        }

        if (! Schema::hasColumn('users', 'referred_by_user_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreignId('referred_by_user_id')->nullable()->after('affiliate_code')->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('pending_registrations', 'referred_by_user_id')) {
            Schema::table('pending_registrations', function (Blueprint $table): void {
                $table->foreignId('referred_by_user_id')->nullable()->after('passing_year_batch')->constrained('users')->nullOnDelete();
            });
        }

        DB::table('users')
            ->select('id')
            ->whereNull('affiliate_code')
            ->orderBy('id')
            ->get()
            ->each(function (object $user): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'affiliate_code' => 'AFF'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
                    ]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('pending_registrations', 'referred_by_user_id')) {
            Schema::table('pending_registrations', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('referred_by_user_id');
            });
        }

        if (Schema::hasColumn('users', 'referred_by_user_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('referred_by_user_id');
            });
        }

        if (Schema::hasColumn('users', 'affiliate_code')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique(['affiliate_code']);
                $table->dropColumn('affiliate_code');
            });
        }
    }
};
