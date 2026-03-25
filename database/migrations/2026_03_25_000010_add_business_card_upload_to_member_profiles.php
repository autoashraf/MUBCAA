<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('member_profiles', 'business_card_upload')) {
                $table->string('business_card_upload')->nullable()->after('cover_photo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('member_profiles', 'business_card_upload')) {
                $table->dropColumn('business_card_upload');
            }
        });
    }
};
