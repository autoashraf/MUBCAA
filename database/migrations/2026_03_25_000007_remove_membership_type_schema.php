<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('member_profiles') && Schema::hasColumn('member_profiles', 'membership_type_id')) {
            Schema::table('member_profiles', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('membership_type_id');
            });
        }

        if (Schema::hasTable('membership_applications') && Schema::hasColumn('membership_applications', 'membership_type_id')) {
            Schema::table('membership_applications', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('membership_type_id');
            });
        }

        if (Schema::hasTable('membership_workflow_steps')) {
            Schema::drop('membership_workflow_steps');
        }

        if (Schema::hasTable('membership_types')) {
            Schema::drop('membership_types');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('membership_types')) {
            Schema::create('membership_types', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->unsignedTinyInteger('steps_count')->default(3);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('membership_workflow_steps')) {
            Schema::create('membership_workflow_steps', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('membership_type_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('step_number');
                $table->string('title');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->unique(['membership_type_id', 'step_number']);
            });
        }

        if (Schema::hasTable('member_profiles') && ! Schema::hasColumn('member_profiles', 'membership_type_id')) {
            Schema::table('member_profiles', function (Blueprint $table): void {
                $table->foreignId('membership_type_id')->nullable()->constrained()->nullOnDelete()->after('user_id');
            });
        }

        if (Schema::hasTable('membership_applications') && ! Schema::hasColumn('membership_applications', 'membership_type_id')) {
            Schema::table('membership_applications', function (Blueprint $table): void {
                $table->foreignId('membership_type_id')->nullable()->constrained()->nullOnDelete()->after('user_id');
            });
        }
    }
};
