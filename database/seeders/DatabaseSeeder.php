<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@mubcaa.test'],
            [
                'name' => 'MUBCAA Admin',
                'phone' => '01700000000',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'membership_status' => 'active',
                'approval_step' => 1,
            ],
        );
    }
}
