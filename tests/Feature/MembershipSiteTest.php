<?php

namespace Tests\Feature;

use App\Models\MembershipApplication;
use App\Models\MembershipType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_key_public_pages_are_available(): void
    {
        $routes = [
            '/',
            '/about-us/mission-vision',
            '/membership/apply-now',
            '/membership/members',
            '/events/upcoming-event',
            '/memories',
            '/contact',
            '/login',
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertOk();
        }
    }

    public function test_member_can_register_and_get_membership_application(): void
    {
        $type = MembershipType::query()->where('slug', 'general')->firstOrFail();

        $response = $this->post('/membership/apply-now', [
            'name' => 'Test Applicant',
            'email' => 'applicant@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'phone' => '01700000000',
            'membership_type_id' => $type->id,
            'address' => '123 Main Street',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'occupation' => 'Engineer',
            'bio' => 'I want to join the association.',
        ]);

        $response->assertRedirect(route('member.dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'applicant@example.com',
            'role' => 'member',
            'membership_status' => 'pending',
        ]);

        $this->assertDatabaseHas('membership_applications', [
            'status' => 'pending',
            'current_step' => 1,
            'membership_type_id' => $type->id,
        ]);
    }

    public function test_member_can_login_and_access_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'pending',
            'approval_step' => 1,
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('member.dashboard'));
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_admin_can_advance_application(): void
    {
        $admin = User::query()->where('email', 'admin@mubcaa.test')->firstOrFail();
        $type = MembershipType::query()->where('slug', 'general')->firstOrFail();
        $member = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'pending',
            'approval_step' => 1,
        ]);

        $application = MembershipApplication::query()->create([
            'user_id' => $member->id,
            'membership_type_id' => $type->id,
            'status' => 'pending',
            'current_step' => 1,
            'total_steps' => $type->steps_count,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.applications.advance', $application), [
                'admin_notes' => 'Looks good.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('membership_applications', [
            'id' => $application->id,
            'current_step' => 2,
            'status' => 'under_review',
        ]);
    }

    public function test_admin_dashboard_shows_application_queue_actions(): void
    {
        $admin = User::query()->where('email', 'admin@mubcaa.test')->firstOrFail();
        $type = MembershipType::query()->where('slug', 'general')->firstOrFail();
        $member = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'pending',
            'approval_step' => 1,
            'phone' => '01700000000',
        ]);

        MembershipApplication::query()->create([
            'user_id' => $member->id,
            'membership_type_id' => $type->id,
            'status' => 'pending',
            'current_step' => 1,
            'total_steps' => $type->steps_count,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Application Queue')
            ->assertSee('Advance to Next Step')
            ->assertSee('Reject Application');
    }

    public function test_member_document_routes_are_available_and_certificate_requires_active_status(): void
    {
        $type = MembershipType::query()->where('slug', 'general')->firstOrFail();
        $member = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'pending',
            'approval_step' => 1,
        ]);

        $member->profile()->create([
            'membership_type_id' => $type->id,
            'phone' => '01711111111',
            'address' => 'House 1',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
        ]);

        $member->application()->create([
            'membership_type_id' => $type->id,
            'status' => 'pending',
            'current_step' => 1,
            'total_steps' => 3,
            'submitted_at' => now(),
        ]);

        $this->actingAs($member)->get(route('member.documents.profile'))->assertOk();
        $this->actingAs($member)->get(route('member.documents.id-card'))->assertOk();
        $this->actingAs($member)->get(route('member.documents.certificate'))->assertForbidden();

        $member->update(['membership_status' => 'active']);
        $member->application()->update(['status' => 'approved', 'approved_at' => now()]);

        $this->actingAs($member)->get(route('member.documents.certificate'))->assertOk();
    }

    public function test_memory_submission_form_validates_and_redirects_back(): void
    {
        $response = $this->post('/memories/submit-your-memory', [
            'name' => 'Memory Author',
            'email' => 'memory@example.com',
            'title' => 'Our first event',
            'memory' => 'It was a meaningful day for every member who attended.',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_contact_form_validates_and_redirects_back(): void
    {
        $response = $this->post('/contact', [
            'name' => 'Contact User',
            'email' => 'contact@example.com',
            'phone' => '01700000000',
            'subject' => 'Membership enquiry',
            'message' => 'I want to know more about joining MUBCAA and upcoming events.',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success');
    }
}
