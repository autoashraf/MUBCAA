<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('phone')->nullable()->after('email');
            });
        }

        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('role')->default('member')->after('password');
            });
        }

        if (! Schema::hasColumn('users', 'membership_status')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('membership_status')->default('draft')->after('role');
            });
        }

        if (! Schema::hasColumn('users', 'approval_step')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unsignedTinyInteger('approval_step')->default(1)->after('membership_status');
            });
        }
    }

    public function down(): void
    {
        $columns = collect(['phone', 'role', 'membership_status', 'approval_step'])
            ->filter(fn (string $column): bool => Schema::hasColumn('users', $column))
            ->values()
            ->all();

        if ($columns !== []) {
            Schema::table('users', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
