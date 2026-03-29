<?php

namespace Tests\Feature;

use App\Mail\VerificationOtpMail;
use App\Models\MembershipApplication;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
            '/admin/login',
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertOk();
        }
    }

    public function test_member_can_submit_basic_info_and_create_pending_registration(): void
    {
        Mail::fake();

        $response = $this->withSession([
            'registration_captcha_a' => 4,
            'registration_captcha_b' => 5,
        ])->post('/membership/apply-now', [
            'full_name' => 'Test Applicant',
            'mobile_number' => '01700000001',
            'email' => 'applicant@example.com',
            'passing_year_batch' => '2012',
            'captcha_left' => 4,
            'captcha_right' => 5,
            'captcha_answer' => 9,
        ]);

        $response->assertRedirect(route('member.verification.show'));

        $this->assertDatabaseMissing('users', [
            'email' => 'applicant@example.com',
        ]);

        $this->assertDatabaseHas('pending_registrations', [
            'email' => 'applicant@example.com',
            'mobile_number' => '01700000001',
        ]);

        Mail::assertSent(VerificationOtpMail::class, 1);
    }

    public function test_referral_code_is_captured_in_pending_registration_and_promoted_to_member(): void
    {
        Mail::fake();

        $referrer = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'verified',
            'approval_step' => 1,
        ]);

        $this->get($referrer->affiliateLink())
            ->assertRedirect(route('membership.apply'))
            ->assertSessionHas('affiliate_referrer_id', $referrer->id);

        $this->withSession([
            'registration_captcha_a' => 2,
            'registration_captcha_b' => 3,
            'affiliate_referrer_id' => $referrer->id,
        ])->post('/membership/apply-now', [
            'full_name' => 'Referral Applicant',
            'mobile_number' => '01700000009',
            'email' => 'referral@example.com',
            'passing_year_batch' => '2011',
            'captcha_left' => 2,
            'captcha_right' => 3,
            'captcha_answer' => 5,
        ])->assertRedirect(route('member.verification.show'));

        $registration = PendingRegistration::query()->where('email', 'referral@example.com')->firstOrFail();
        $this->assertSame($referrer->id, $registration->referred_by_user_id);

        $this->withSession(['pending_registration_id' => $registration->id])
            ->post(route('member.verification.email'), ['code' => $registration->email_code])
            ->assertRedirect();

        $this->withSession(['pending_registration_id' => $registration->id])
            ->post(route('member.verification.mobile'), ['code' => $registration->mobile_code])
            ->assertRedirect(route('member.profile.complete', ['step' => 2]));

        $member = User::query()->where('email', 'referral@example.com')->firstOrFail();

        $this->assertSame($referrer->id, $member->referred_by_user_id);
        $this->assertNotNull($member->affiliate_code);
    }

    public function test_member_can_login_and_access_dashboard(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'pending_review',
            'approval_step' => 1,
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'identifier' => $user->email,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('login_otp_user_id', $user->id);

        $code = session('login_otp_code');

        $verifyResponse = $this->post(route('login.verify'), [
            'code' => $code,
        ]);

        $verifyResponse->assertRedirect(route('member.dashboard'));
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get(route('member.profile'))->assertOk()->assertSee('Member Profile');

        Mail::assertSent(VerificationOtpMail::class, 1);
    }

    public function test_login_identifier_check_reports_existing_member(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'email' => 'exists@example.com',
            'phone' => '01700000123',
        ]);

        $this->postJson(route('login.check'), [
            'identifier' => $member->email,
        ])->assertOk()
            ->assertJson([
                'exists' => true,
                'message' => 'Member account found. You can request an OTP.',
            ]);
    }

    public function test_admin_can_login_with_email_and_password(): void
    {
        $admin = User::query()->where('email', 'admin@mubcaa.test')->firstOrFail();

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Admin Panel');
    }

    public function test_pending_registration_creates_member_after_email_and_mobile_otp_verification(): void
    {
        Mail::fake();

        $registration = PendingRegistration::query()->create([
            'full_name' => 'Pending Applicant',
            'email' => 'pending@example.com',
            'mobile_number' => '01712345678',
            'passing_year_batch' => '2012',
            'email_code' => '123456',
            'mobile_code' => '654321',
            'email_code_expires_at' => now()->addMinutes(15),
            'mobile_code_expires_at' => now()->addMinutes(15),
        ]);

        $this->withSession(['pending_registration_id' => $registration->id])
            ->post(route('member.verification.email'), ['code' => '123456'])
            ->assertRedirect();

        $this->withSession(['pending_registration_id' => $registration->id])
            ->post(route('member.verification.mobile'), ['code' => '654321'])
            ->assertRedirect(route('member.profile.complete', ['step' => 2]));

        $member = User::query()->where('email', 'pending@example.com')->first();

        $this->assertNotNull($member);
        $this->assertNotNull($member->email_verified_at);
        $this->assertTrue((bool) $member->profile->mobile_verified);
        $this->assertDatabaseHas('membership_applications', [
            'user_id' => $member->id,
            'status' => 'unverified',
        ]);
        $this->assertNotNull($registration->fresh()->completed_at);
    }

    public function test_member_can_open_and_save_profile_completion_step(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'unverified',
            'approval_step' => 1,
            'phone' => '01700000000',
        ]);

        $member->profile()->create([
            'mobile_number' => '01700000000',
            'passing_year_batch' => '2012',
            'student_id_or_roll' => 'ST-1024',
            'current_city' => 'Dhaka',
            'country' => 'Bangladesh',
            'completion_step' => 1,
        ]);

        $member->application()->create([
            'status' => 'unverified',
            'current_step' => 1,
            'total_steps' => 10,
        ]);

        $this->actingAs($member)
            ->get(route('member.profile.complete', ['step' => 2]))
            ->assertOk()
            ->assertSee('Academic Info');

        $this->actingAs($member)
            ->post(route('member.profile.complete.save'), [
                'wizard_step' => 2,
                'ssc_passing_year' => '2010',
                'hsc_passing_year' => '2012',
                'group' => 'Science',
                'shift' => 'Morning',
                'campus_branch' => 'Main Girls',
                'next_step' => 3,
            ])
            ->assertRedirect(route('member.profile.complete', ['step' => 3]));

        $this->assertDatabaseHas('member_profiles', [
            'user_id' => $member->id,
            'ssc_passing_year' => '2010',
            'group' => 'Science',
            'completion_step' => 2,
        ]);
    }

    public function test_member_can_save_incomplete_step_as_draft(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'unverified',
            'approval_step' => 1,
            'phone' => '01700000011',
        ]);

        $member->profile()->create([
            'mobile_number' => '01700000011',
            'passing_year_batch' => '2012',
            'country' => 'Bangladesh',
            'completion_step' => 2,
        ]);

        $member->application()->create([
            'status' => 'in_progress',
            'current_step' => 2,
            'total_steps' => 10,
        ]);

        $this->actingAs($member)
            ->post(route('member.profile.complete.save'), [
                'wizard_step' => 3,
                'father_name' => 'Test Father',
                'save_as_draft' => '1',
            ])
            ->assertRedirect(route('member.profile.complete', ['step' => 3]));

        $this->assertDatabaseHas('member_profiles', [
            'user_id' => $member->id,
            'father_name' => 'Test Father',
            'completion_step' => 3,
        ]);
    }

    public function test_dashboard_resume_link_opens_last_saved_step(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'in_progress',
            'approval_step' => 1,
            'phone' => '01700000012',
        ]);

        $member->profile()->create([
            'mobile_number' => '01700000012',
            'passing_year_batch' => '2012',
            'country' => 'Bangladesh',
            'completion_step' => 2,
        ]);

        $member->application()->create([
            'status' => 'in_progress',
            'current_step' => 2,
            'total_steps' => 10,
        ]);

        $this->actingAs($member)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee(route('member.profile.complete', ['step' => 2]), false)
            ->assertDontSee(route('member.profile.complete', ['step' => 3]), false);
    }

    public function test_admin_can_approve_application(): void
    {
        $admin = User::query()->where('email', 'admin@mubcaa.test')->firstOrFail();
        $member = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'pending_review',
            'approval_step' => 1,
        ]);

        $application = MembershipApplication::query()->create([
            'user_id' => $member->id,
            'status' => 'pending',
            'current_step' => 1,
            'total_steps' => 10,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.applications.approve', $application), [
                'admin_notes' => 'Looks good.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('membership_applications', [
            'id' => $application->id,
            'current_step' => 10,
            'status' => 'approved',
        ]);
    }

    public function test_admin_dashboard_shows_application_queue_actions(): void
    {
        $admin = User::query()->where('email', 'admin@mubcaa.test')->firstOrFail();
        $member = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'pending_review',
            'approval_step' => 1,
            'phone' => '01700000000',
        ]);

        MembershipApplication::query()->create([
            'user_id' => $member->id,
            'status' => 'pending',
            'current_step' => 1,
            'total_steps' => 10,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Admin Panel')
            ->assertDontSee('Approve Application')
            ->assertDontSee('Reject Application');

        $this->actingAs($admin)
            ->get(route('admin.applications.index', ['search' => $member->email, 'status' => 'pending']))
            ->assertOk()
            ->assertSee('Applications')
            ->assertSee('Open Application')
            ->assertDontSee('Reject Application')
            ->assertSee($member->email);

        $this->actingAs($admin)
            ->get(route('admin.applications.show', $member->application))
            ->assertOk()
            ->assertSee('Application Review')
            ->assertSee($member->name);
    }

    public function test_admin_can_view_affiliate_management_page(): void
    {
        $admin = User::query()->where('email', 'admin@mubcaa.test')->firstOrFail();

        $affiliate = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'verified',
            'approval_step' => 1,
        ]);

        User::factory()->create([
            'role' => 'member',
            'membership_status' => 'pending_review',
            'approval_step' => 1,
            'referred_by_user_id' => $affiliate->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.affiliates.index'))
            ->assertOk()
            ->assertSee('Affiliates')
            ->assertSee($affiliate->affiliate_code)
            ->assertSee('1 referrals');
    }

    public function test_member_document_routes_are_available_and_certificate_requires_active_status(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'pending_review',
            'approval_step' => 1,
        ]);

        $member->profile()->create([
            'mobile_number' => '01711111111',
            'primary_mobile' => '01711111111',
            'present_address' => 'House 1',
            'current_city' => 'Dhaka',
            'city_district' => 'Dhaka',
            'country' => 'Bangladesh',
        ]);

        $member->application()->create([
            'status' => 'pending',
            'current_step' => 1,
            'total_steps' => 3,
            'submitted_at' => now(),
        ]);

        $this->actingAs($member)->get(route('member.documents.profile'))->assertForbidden();
        $this->actingAs($member)->get(route('member.documents.id-card'))->assertForbidden();
        $this->actingAs($member)->get(route('member.documents.certificate'))->assertForbidden();

        $member->update(['membership_status' => 'verified']);
        $member->application()->update(['status' => 'approved', 'approved_at' => now()]);

        $this->actingAs($member)->get(route('member.documents.profile'))->assertOk();
        $this->actingAs($member)->get(route('member.documents.id-card'))->assertOk();
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
