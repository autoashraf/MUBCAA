<?php

namespace Database\Seeders;

use App\Models\MembershipType;
use App\Models\MembershipWorkflowStep;
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
        $types = [
            [
                'name' => 'General Membership',
                'slug' => 'general',
                'description' => 'Standard membership for active community participants.',
                'steps_count' => 3,
                'sort_order' => 1,
                'steps' => [
                    ['title' => 'Application Submitted', 'description' => 'Member completes the online registration form.'],
                    ['title' => 'Committee Review', 'description' => 'Admin or committee reviews profile completeness.'],
                    ['title' => 'Approval', 'description' => 'Application is approved and the member becomes active.'],
                ],
            ],
            [
                'name' => 'Lifetime Membership',
                'slug' => 'lifetime',
                'description' => 'Permanent membership for long-term supporters and senior contributors.',
                'steps_count' => 4,
                'sort_order' => 2,
                'steps' => [
                    ['title' => 'Application Submitted', 'description' => 'Online registration is completed.'],
                    ['title' => 'Document Review', 'description' => 'Supporting details are reviewed.'],
                    ['title' => 'Executive Approval', 'description' => 'Committee approves the lifetime category.'],
                    ['title' => 'Activation', 'description' => 'Membership is activated and documents can be issued.'],
                ],
            ],
            [
                'name' => 'Associate Membership',
                'slug' => 'associate',
                'description' => 'A lighter membership model for partners and supporters.',
                'steps_count' => 3,
                'sort_order' => 3,
                'steps' => [
                    ['title' => 'Application Submitted', 'description' => 'Applicant shares profile details.'],
                    ['title' => 'Verification', 'description' => 'Association verifies eligibility.'],
                    ['title' => 'Activation', 'description' => 'Associate membership is activated.'],
                ],
            ],
        ];

        foreach ($types as $typeData) {
            $type = MembershipType::updateOrCreate(
                ['slug' => $typeData['slug']],
                collect($typeData)->except('steps')->all(),
            );

            foreach ($typeData['steps'] as $index => $step) {
                MembershipWorkflowStep::updateOrCreate(
                    [
                        'membership_type_id' => $type->id,
                        'step_number' => $index + 1,
                    ],
                    $step,
                );
            }
        }

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
