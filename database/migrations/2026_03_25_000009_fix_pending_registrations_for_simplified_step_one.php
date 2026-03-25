<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE pending_registrations MODIFY student_id_or_roll VARCHAR(255) NULL');
            DB::statement('ALTER TABLE pending_registrations MODIFY current_city VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('UPDATE pending_registrations SET student_id_or_roll = "" WHERE student_id_or_roll IS NULL');
            DB::statement('UPDATE pending_registrations SET current_city = "" WHERE current_city IS NULL');
            DB::statement('ALTER TABLE pending_registrations MODIFY student_id_or_roll VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE pending_registrations MODIFY current_city VARCHAR(255) NOT NULL');
        }
    }
};
