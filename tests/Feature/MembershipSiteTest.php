<?php

namespace Tests\Feature;

use App\Mail\VerificationOtpMail;
use App\Models\ContactSubmission;
use App\Models\GalleryPhoto;
use App\Models\GalleryVideo;
use App\Models\MemorySubmission;
use App\Models\MembershipApplication;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
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
            '/privacy-policy',
            '/terms-and-conditions',
            '/cookie-policy',
            '/disclaimer',
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
            'discovery_source' => 'Facebook',
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
            'mobile_number' => '+8801700000001',
            'how_did_you_find_us' => 'Facebook',
        ]);

        $registration = PendingRegistration::query()->where('email', 'applicant@example.com')->firstOrFail();

        $this->assertNotNull($registration->email_code);
        $this->assertNull($registration->mobile_code);

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

        $registration = $registration->fresh();
        $this->assertNotNull($registration->mobile_code);

        $this->withSession(['pending_registration_id' => $registration->id])
            ->post(route('member.verification.mobile'), ['code' => $registration->mobile_code])
            ->assertRedirect(route('member.profile.complete', ['step' => 2]));

        $member = User::query()->where('email', 'referral@example.com')->firstOrFail();

        $this->assertSame($referrer->id, $member->referred_by_user_id);
        $this->assertNotNull($member->affiliate_code);
    }

    public function test_manual_referral_code_is_captured_in_pending_registration(): void
    {
        Mail::fake();

        $referrer = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'verified',
            'approval_step' => 1,
            'affiliate_code' => 'AFF123456',
        ]);

        $this->withSession([
            'registration_captcha_a' => 3,
            'registration_captcha_b' => 4,
        ])->post('/membership/apply-now', [
            'full_name' => 'Manual Referral Applicant',
            'mobile_number' => '01700000019',
            'email' => 'manual-referral@example.com',
            'passing_year_batch' => '2014',
            'discovery_source' => 'Referral Code',
            'referral_code' => 'AFF123456',
            'captcha_left' => 3,
            'captcha_right' => 4,
            'captcha_answer' => 7,
        ])->assertRedirect(route('member.verification.show'));

        $registration = PendingRegistration::query()->where('email', 'manual-referral@example.com')->firstOrFail();

        $this->assertSame($referrer->id, $registration->referred_by_user_id);
    }

    public function test_registration_check_reports_existing_values_and_valid_referral_code(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'email' => 'taken@example.com',
            'phone' => '01700000199',
            'affiliate_code' => 'AFF654321',
        ]);

        $this->postJson(route('membership.apply.check'), [
            'field' => 'email',
            'value' => 'taken@example.com',
        ])->assertOk()->assertJson([
            'valid' => false,
            'type' => 'error',
        ]);

        $this->postJson(route('membership.apply.check'), [
            'field' => 'mobile_number',
            'value' => '01700000199',
        ])->assertOk()->assertJson([
            'valid' => false,
            'type' => 'error',
        ]);

        $this->postJson(route('membership.apply.check'), [
            'field' => 'referral_code',
            'value' => 'AFF654321',
        ])->assertOk()->assertJson([
            'valid' => true,
            'type' => 'success',
        ]);
    }

    public function test_registration_check_reports_existing_mobile_when_only_profile_has_canonical_number(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'email' => 'profile-mobile@example.com',
            'phone' => null,
        ]);

        $member->profile()->create([
            'mobile_number' => '+8801773658804',
            'primary_mobile' => '+8801773658804',
            'email_address' => 'profile-mobile@example.com',
            'passing_year_batch' => '2012',
        ]);

        $this->postJson(route('membership.apply.check'), [
            'field' => 'mobile_number',
            'value' => '01773658804',
            'mobile_country_code' => '+880',
        ])->assertOk()->assertJson([
            'valid' => false,
            'type' => 'error',
            'message' => 'This mobile number is already registered.',
        ]);
    }

    public function test_registration_check_reuses_exact_pending_registration_context(): void
    {
        PendingRegistration::query()->create([
            'full_name' => 'Existing Applicant',
            'email' => 'pending-check@example.com',
            'mobile_number' => '01700000999',
            'passing_year_batch' => '2010',
            'completed_at' => now()->subDay(),
        ]);

        $this->postJson(route('membership.apply.check'), [
            'field' => 'email',
            'value' => 'pending-check@example.com',
            'context_mobile_number' => '01700000999',
        ])->assertOk()->assertJson([
            'valid' => true,
            'type' => 'success',
            'message' => 'An existing registration was found for this email and mobile number.',
        ]);

        $this->postJson(route('membership.apply.check'), [
            'field' => 'mobile_number',
            'value' => '01700000999',
            'context_email' => 'pending-check@example.com',
        ])->assertOk()->assertJson([
            'valid' => true,
            'type' => 'success',
            'message' => 'An existing registration was found for this email and mobile number.',
        ]);
    }

    public function test_registration_check_rejects_pending_registration_conflicts(): void
    {
        PendingRegistration::query()->create([
            'full_name' => 'Existing Applicant',
            'email' => 'pending-conflict@example.com',
            'mobile_number' => '01700000888',
            'passing_year_batch' => '2010',
            'completed_at' => now()->subDay(),
        ]);

        $this->postJson(route('membership.apply.check'), [
            'field' => 'email',
            'value' => 'pending-conflict@example.com',
            'context_mobile_number' => '01700000777',
        ])->assertOk()->assertJson([
            'valid' => false,
            'type' => 'error',
            'message' => 'This email address is already linked to another registration.',
        ]);

        $this->postJson(route('membership.apply.check'), [
            'field' => 'mobile_number',
            'value' => '01700000888',
            'context_email' => 'different@example.com',
        ])->assertOk()->assertJson([
            'valid' => false,
            'type' => 'error',
            'message' => 'This mobile number is already linked to another registration.',
        ]);
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
            'login_channel' => 'email',
            'email_identifier' => $user->email,
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
            'login_channel' => 'email',
            'identifier' => $member->email,
        ])->assertOk()
            ->assertJson([
                'exists' => true,
                'message' => 'If this is a registered member account, you can request an OTP.',
            ]);
    }

    public function test_member_can_request_login_otp_with_international_mobile_and_selected_country_code(): void
    {
        $user = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'pending_review',
            'approval_step' => 1,
            'phone' => '+15551234567',
        ]);

        $response = $this->post('/login', [
            'login_channel' => 'mobile',
            'mobile_identifier' => '5551234567',
            'mobile_country_code' => '+1',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('login_otp_user_id', $user->id);
        $response->assertSessionHas('login_otp_channel', 'mobile');
        $response->assertSessionHas('login_otp_contact', '+15551234567');
    }

    public function test_member_login_does_not_disclose_when_account_is_missing(): void
    {
        $response = $this->post(route('login.attempt'), [
            'login_channel' => 'email',
            'email_identifier' => 'missing@example.com',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success', 'If a member account matches that email or mobile number, a 6-digit OTP has been sent.');
        $response->assertSessionMissing('login_otp_user_id');
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

        $registration = $registration->fresh();
        $this->assertNotNull($registration->mobile_code);

        $this->withSession(['pending_registration_id' => $registration->id])
            ->post(route('member.verification.mobile'), ['code' => $registration->mobile_code])
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

    public function test_registration_cannot_replace_another_pending_registration(): void
    {
        PendingRegistration::query()->create([
            'full_name' => 'Existing Applicant',
            'email' => 'pending-existing@example.com',
            'mobile_number' => '01712345678',
            'passing_year_batch' => '2010',
        ]);

        $response = $this->withSession([
            'registration_captcha_a' => 1,
            'registration_captcha_b' => 2,
        ])->post(route('membership.apply.store'), [
            'full_name' => 'Second Applicant',
            'email' => 'pending-existing@example.com',
            'mobile_number' => '01700000077',
            'passing_year_batch' => '2012',
            'discovery_source' => 'Facebook',
            'captcha_left' => 1,
            'captcha_right' => 2,
            'captcha_answer' => 3,
        ]);

        $response->assertSessionHasErrors(['email']);

        $this->assertDatabaseHas('pending_registrations', [
            'email' => 'pending-existing@example.com',
            'mobile_number' => '01712345678',
            'full_name' => 'Existing Applicant',
        ]);

        $this->assertSame(1, PendingRegistration::query()->where('email', 'pending-existing@example.com')->count());
    }

    public function test_registration_reuses_matching_pending_registration_when_session_is_missing(): void
    {
        $registration = PendingRegistration::query()->create([
            'full_name' => 'Existing Applicant',
            'email' => 'existing@example.com',
            'mobile_number' => '01712345678',
            'passing_year_batch' => '2010',
            'how_did_you_find_us' => 'Facebook',
        ]);

        $response = $this->withSession([
            'registration_captcha_a' => 1,
            'registration_captcha_b' => 2,
        ])->post(route('membership.apply.store'), [
            'full_name' => 'Existing Applicant Updated',
            'email' => 'existing@example.com',
            'mobile_number' => '01712345678',
            'passing_year_batch' => '2012',
            'discovery_source' => 'Google Search',
            'captcha_left' => 1,
            'captcha_right' => 2,
            'captcha_answer' => 3,
        ]);

        $response->assertRedirect(route('member.verification.show'));

        $this->assertSame(1, PendingRegistration::query()->where('email', 'existing@example.com')->count());

        $this->assertDatabaseHas('pending_registrations', [
            'id' => $registration->id,
            'full_name' => 'Existing Applicant Updated',
            'email' => 'existing@example.com',
            'mobile_number' => '+8801712345678',
            'passing_year_batch' => '2012',
            'how_did_you_find_us' => 'Google Search',
        ]);
    }

    public function test_registration_reuses_matching_completed_pending_registration_when_user_is_missing(): void
    {
        $registration = PendingRegistration::query()->create([
            'full_name' => 'Existing Applicant',
            'email' => 'existing-completed@example.com',
            'mobile_number' => '01712345679',
            'passing_year_batch' => '2010',
            'how_did_you_find_us' => 'Facebook',
            'completed_at' => now()->subDay(),
            'email_verified_at' => now()->subDay(),
            'mobile_verified_at' => now()->subDay(),
            'email_code' => '123456',
            'mobile_code' => '654321',
            'email_code_expires_at' => now()->subMinutes(5),
            'mobile_code_expires_at' => now()->subMinutes(5),
        ]);

        $response = $this->withSession([
            'registration_captcha_a' => 1,
            'registration_captcha_b' => 2,
        ])->post(route('membership.apply.store'), [
            'full_name' => 'Existing Applicant Updated',
            'email' => 'existing-completed@example.com',
            'mobile_number' => '01712345679',
            'passing_year_batch' => '2016',
            'discovery_source' => 'Website',
            'captcha_left' => 1,
            'captcha_right' => 2,
            'captcha_answer' => 3,
        ]);

        $response->assertRedirect(route('member.verification.show'));

        $this->assertSame(1, PendingRegistration::query()->where('email', 'existing-completed@example.com')->count());

        $this->assertDatabaseHas('pending_registrations', [
            'id' => $registration->id,
            'full_name' => 'Existing Applicant Updated',
            'email' => 'existing-completed@example.com',
            'mobile_number' => '+8801712345679',
            'passing_year_batch' => '2016',
            'how_did_you_find_us' => 'Website',
            'completed_at' => null,
            'email_verified_at' => null,
            'mobile_verified_at' => null,
        ]);
    }

    public function test_registration_rejects_completed_pending_registration_conflicts_before_insert(): void
    {
        PendingRegistration::query()->create([
            'full_name' => 'Existing Applicant',
            'email' => 'completed-conflict@example.com',
            'mobile_number' => '01712345680',
            'passing_year_batch' => '2010',
            'completed_at' => now()->subDay(),
        ]);

        $response = $this->withSession([
            'registration_captcha_a' => 1,
            'registration_captcha_b' => 2,
        ])->post(route('membership.apply.store'), [
            'full_name' => 'Different Applicant',
            'email' => 'completed-conflict@example.com',
            'mobile_number' => '01700000088',
            'passing_year_batch' => '2012',
            'discovery_source' => 'Facebook',
            'captcha_left' => 1,
            'captcha_right' => 2,
            'captcha_answer' => 3,
        ]);

        $response->assertSessionHasErrors(['email']);

        $this->assertSame(1, PendingRegistration::query()->where('email', 'completed-conflict@example.com')->count());
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
            'completion_step' => 2,
        ]);

        $this->assertDatabaseHas('membership_applications', [
            'user_id' => $member->id,
            'current_step' => 3,
        ]);

        $this->actingAs($member)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee(route('member.profile.complete', ['step' => 3]), false);
    }

    public function test_member_contact_step_persists_primary_mobile_and_email(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'in_progress',
            'approval_step' => 1,
            'phone' => '01700000021',
            'email' => 'member@example.com',
        ]);

        $member->profile()->create([
            'mobile_number' => '01700000021',
            'passing_year_batch' => '2012',
            'country' => 'Bangladesh',
            'completion_step' => 3,
        ]);

        $member->application()->create([
            'status' => 'in_progress',
            'current_step' => 3,
            'total_steps' => 10,
        ]);

        $this->actingAs($member)
            ->post(route('member.profile.complete.save'), [
                'wizard_step' => 4,
                'primary_mobile' => '01799999999',
                'secondary_mobile' => '01811111111',
                'whatsapp_number' => '01799999999',
                'email_address' => 'updated-contact@example.com',
                'present_address' => 'Present address',
                'permanent_address' => 'Permanent address',
                'country' => 'Bangladesh',
                'city_district' => 'Dhaka',
                'postal_code' => '1207',
                'next_step' => 5,
            ])
            ->assertRedirect(route('member.profile.complete', ['step' => 5]));

        $this->assertDatabaseHas('member_profiles', [
            'user_id' => $member->id,
            'primary_mobile' => '+8801799999999',
            'mobile_number' => '+8801799999999',
            'email_address' => 'updated-contact@example.com',
        ]);
    }

    public function test_member_contact_step_accepts_10_digit_bangladesh_numbers(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'in_progress',
            'approval_step' => 1,
            'phone' => '01700000041',
            'email' => 'bd-contact@example.com',
        ]);

        $member->profile()->create([
            'mobile_number' => '01700000041',
            'passing_year_batch' => '2012',
            'country' => 'Bangladesh',
            'completion_step' => 3,
        ]);

        $member->application()->create([
            'status' => 'in_progress',
            'current_step' => 3,
            'total_steps' => 10,
        ]);

        $this->actingAs($member)
            ->post(route('member.profile.complete.save'), [
                'wizard_step' => 4,
                'primary_mobile_country_code' => '+880',
                'secondary_mobile_country_code' => '+880',
                'whatsapp_country_code' => '+880',
                'primary_mobile' => '01799999999',
                'secondary_mobile' => '1811111111',
                'whatsapp_number' => '1712345678',
                'email_address' => 'updated-bd-contact@example.com',
                'present_address' => 'Present address',
                'permanent_address' => 'Permanent address',
                'country' => 'Bangladesh',
                'city_district' => 'Dhaka',
                'postal_code' => '1207',
                'next_step' => 5,
            ])
            ->assertRedirect(route('member.profile.complete', ['step' => 5]));

        $this->assertDatabaseHas('member_profiles', [
            'user_id' => $member->id,
            'primary_mobile' => '+8801799999999',
            'secondary_mobile' => '+8801811111111',
            'whatsapp_number' => '+8801712345678',
            'email_address' => 'updated-bd-contact@example.com',
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

    public function test_member_profile_completion_advances_application_current_step(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'in_progress',
            'approval_step' => 1,
            'phone' => '01700000031',
        ]);

        $member->profile()->create([
            'mobile_number' => '01700000031',
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
                'mother_name' => 'Test Mother',
                'date_of_birth' => '1990-01-01',
                'gender' => 'Male',
                'blood_group' => 'A+',
                'marital_status' => 'Single',
                'next_step' => 4,
            ])
            ->assertRedirect(route('member.profile.complete', ['step' => 4]));

        $this->assertDatabaseHas('membership_applications', [
            'user_id' => $member->id,
            'current_step' => 3,
        ]);
    }

    public function test_member_cannot_resubmit_profile_after_submission(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'pending_review',
            'approval_step' => 1,
            'email_verified_at' => now(),
        ]);

        $member->profile()->create([
            'mobile_number' => '01712345678',
            'mobile_verified' => true,
            'passing_year_batch' => '2012',
            'completion_step' => 10,
            'submitted_for_review_at' => now(),
        ]);

        $member->application()->create([
            'status' => 'pending_review',
            'current_step' => 10,
            'total_steps' => 10,
            'submitted_at' => now(),
        ]);

        $this->actingAs($member)
            ->post(route('member.profile.complete.save'), [
                'wizard_step' => 10,
                'information_accuracy_confirmation' => '1',
                'terms_privacy_agreement' => '1',
                'admin_verification_agreement' => '1',
                'submit_for_verification' => '1',
            ])
            ->assertRedirect(route('member.dashboard'))
            ->assertSessionHas('error', 'Your alumni membership profile has already been submitted and cannot be resubmitted.');
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

    public function test_admin_step_one_phone_edit_syncs_member_phone_fields(): void
    {
        $admin = User::query()->where('email', 'admin@mubcaa.test')->firstOrFail();
        $member = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'pending_review',
            'approval_step' => 1,
            'email' => 'admin-phone-sync@example.com',
            'phone' => '+8801711111111',
        ]);

        $member->profile()->create([
            'mobile_number' => '+8801711111111',
            'primary_mobile' => '+8801711111111',
            'secondary_mobile' => '+8801811111111',
            'whatsapp_number' => '+8801711111111',
            'email_address' => 'admin-phone-sync@example.com',
            'passing_year_batch' => '2012',
            'completion_step' => 4,
        ]);

        $application = $member->application()->create([
            'status' => 'pending',
            'current_step' => 4,
            'total_steps' => 10,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.applications.update', $application), [
                'wizard_step' => 1,
                'full_name' => $member->name,
                'mobile_number' => '01799999999',
                'email' => 'admin-phone-updated@example.com',
                'passing_year_batch' => '2012',
                'how_did_you_find_us' => 'Facebook',
            ])
            ->assertRedirect(route('admin.applications.show', ['application' => $application, 'step' => 1]));

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'email' => 'admin-phone-updated@example.com',
            'phone' => '+8801799999999',
        ]);

        $this->assertDatabaseHas('member_profiles', [
            'user_id' => $member->id,
            'email_address' => 'admin-phone-updated@example.com',
            'mobile_number' => '+8801799999999',
            'primary_mobile' => '+8801799999999',
            'whatsapp_number' => '+8801799999999',
            'secondary_mobile' => '+8801811111111',
        ]);
    }

    public function test_admin_step_four_primary_mobile_edit_syncs_member_phone_fields(): void
    {
        $admin = User::query()->where('email', 'admin@mubcaa.test')->firstOrFail();
        $member = User::factory()->create([
            'role' => 'member',
            'membership_status' => 'pending_review',
            'approval_step' => 1,
            'email' => 'admin-step-four-sync@example.com',
            'phone' => '+8801712222222',
        ]);

        $member->profile()->create([
            'mobile_number' => '+8801712222222',
            'primary_mobile' => '+8801712222222',
            'secondary_mobile' => '+8801712222222',
            'whatsapp_number' => '+8801712222222',
            'email_address' => 'admin-step-four-sync@example.com',
            'present_address' => 'Old present address',
            'permanent_address' => 'Old permanent address',
            'country' => 'Bangladesh',
            'city_district' => 'Dhaka',
            'postal_code' => '1207',
            'passing_year_batch' => '2012',
            'completion_step' => 4,
        ]);

        $application = $member->application()->create([
            'status' => 'pending',
            'current_step' => 4,
            'total_steps' => 10,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.applications.update', $application), [
                'wizard_step' => 4,
                'primary_mobile' => '01788888888',
                'secondary_mobile' => '01788888888',
                'whatsapp_number' => '01788888888',
                'email_address' => 'admin-step-four-updated@example.com',
                'present_address' => 'New present address',
                'permanent_address' => 'New permanent address',
                'country' => 'Bangladesh',
                'city_district' => 'Dhaka',
                'postal_code' => '1207',
            ])
            ->assertRedirect(route('admin.applications.show', ['application' => $application, 'step' => 4]));

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'email' => 'admin-step-four-updated@example.com',
            'phone' => '+8801788888888',
        ]);

        $this->assertDatabaseHas('member_profiles', [
            'user_id' => $member->id,
            'email_address' => 'admin-step-four-updated@example.com',
            'mobile_number' => '+8801788888888',
            'primary_mobile' => '+8801788888888',
            'secondary_mobile' => '+8801788888888',
            'whatsapp_number' => '+8801788888888',
        ]);
    }

    public function test_login_identifier_check_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $this->postJson(route('login.check'), [
                'login_channel' => 'email',
                'identifier' => 'member@example.com',
            ])->assertOk();
        }

        $this->postJson(route('login.check'), [
            'login_channel' => 'email',
            'identifier' => 'member@example.com',
        ])->assertStatus(429);
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

    public function test_memory_submission_requires_login(): void
    {
        $this->get(route('memories.submit'))
            ->assertRedirect(route('login'));

        $this->post(route('memories.store'), [
            'title' => 'Our first event',
            'memory' => 'It was a meaningful day for every member who attended.',
        ])->assertRedirect(route('login'));
    }

    public function test_member_can_submit_memory(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'phone' => '01700000999',
        ]);

        $response = $this->actingAs($member)->post('/memories/submit-your-memory', [
            'title' => 'Our first event',
            'memory' => 'It was a meaningful day for every member who attended.',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('memory_submissions', [
            'user_id' => $member->id,
            'title' => 'Our first event',
            'status' => 'pending_review',
        ]);
    }

    public function test_member_can_submit_memory_with_photos(): void
    {
        Storage::fake('public');

        $member = User::factory()->create([
            'role' => 'member',
            'phone' => '01700001000',
        ]);

        $response = $this->actingAs($member)->post('/memories/submit-your-memory', [
            'title' => 'Reunion day',
            'memory' => 'We reconnected, shared stories, and took new photos together.',
            'photos' => [
                UploadedFile::fake()->image('memory-one.jpg'),
                UploadedFile::fake()->image('memory-two.jpg'),
            ],
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertCount(2, Storage::disk('public')->files('memory-submissions'));

        $submission = MemorySubmission::query()->where('user_id', $member->id)->firstOrFail();

        $this->assertSame('pending_review', $submission->status);
        $this->assertCount(2, $submission->photos ?? []);
    }

    public function test_admin_can_approve_memory_submission(): void
    {
        $admin = User::query()->where('email', 'admin@mubcaa.test')->firstOrFail();
        $member = User::factory()->create([
            'role' => 'member',
            'phone' => '01700001001',
        ]);

        $submission = MemorySubmission::query()->create([
            'user_id' => $member->id,
            'title' => 'Batch reunion',
            'memory' => 'A strong evening of reconnecting with old classmates.',
            'status' => 'pending_review',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.memories.index'))
            ->assertOk()
            ->assertSee('Batch reunion');

        $this->actingAs($admin)
            ->post(route('admin.memories.approve', $submission), [
                'admin_notes' => 'Suitable for the archive.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('memory_submissions', [
            'id' => $submission->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'admin_notes' => 'Suitable for the archive.',
        ]);
    }

    public function test_admin_can_reject_memory_submission_with_note(): void
    {
        $admin = User::query()->where('email', 'admin@mubcaa.test')->firstOrFail();
        $member = User::factory()->create([
            'role' => 'member',
            'phone' => '01700001002',
        ]);

        $submission = MemorySubmission::query()->create([
            'user_id' => $member->id,
            'title' => 'Incomplete story',
            'memory' => 'This needs more context before publication.',
            'status' => 'pending_review',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.memories.reject', $submission), [
                'admin_notes' => 'Please add more detail and clearer context.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('memory_submissions', [
            'id' => $submission->id,
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'admin_notes' => 'Please add more detail and clearer context.',
        ]);
    }

    public function test_public_memories_page_only_shows_approved_submissions(): void
    {
        $approvedMember = User::factory()->create([
            'role' => 'member',
            'phone' => '01700001003',
        ]);

        $pendingMember = User::factory()->create([
            'role' => 'member',
            'phone' => '01700001004',
        ]);

        MemorySubmission::query()->create([
            'user_id' => $approvedMember->id,
            'title' => 'Approved memory',
            'memory' => 'This approved memory should be visible on the public archive page.',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        MemorySubmission::query()->create([
            'user_id' => $pendingMember->id,
            'title' => 'Pending memory',
            'memory' => 'This pending memory should stay hidden from the public archive page.',
            'status' => 'pending_review',
        ]);

        $this->get(route('memories.list'))
            ->assertOk()
            ->assertSee('Approved memory')
            ->assertSee($approvedMember->name)
            ->assertDontSee('Pending memory')
            ->assertDontSee($pendingMember->name);
    }

    public function test_admin_can_upload_gallery_photo(): void
    {
        Storage::fake('public');

        $admin = User::query()->where('email', 'admin@mubcaa.test')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.gallery.store'), [
                'title' => 'Reunion Day',
                'caption' => 'A packed hall during the annual reunion.',
                'photo' => UploadedFile::fake()->image('reunion-day.jpg'),
            ])
            ->assertRedirect();

        $photo = GalleryPhoto::query()->firstOrFail();

        $this->assertSame('Reunion Day', $photo->title);
        Storage::disk('public')->assertExists($photo->photo_path);
    }

    public function test_public_photo_gallery_shows_uploaded_admin_photos(): void
    {
        $admin = User::query()->where('email', 'admin@mubcaa.test')->firstOrFail();

        GalleryPhoto::query()->create([
            'uploaded_by' => $admin->id,
            'title' => 'Committee Gathering',
            'caption' => 'Members during a planning session.',
            'photo_path' => 'gallery-photos/example.jpg',
        ]);

        $this->get(route('events.photos'))
            ->assertOk()
            ->assertSee('Committee Gathering')
            ->assertSee('Members during a planning session.')
            ->assertSee($admin->name);
    }

    public function test_home_page_uses_uploaded_gallery_photos_for_preview(): void
    {
        $admin = User::query()->where('email', 'admin@mubcaa.test')->firstOrFail();

        GalleryPhoto::query()->create([
            'uploaded_by' => $admin->id,
            'title' => 'Homepage Preview',
            'photo_path' => 'gallery-photos/home-preview.jpg',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(asset('storage/gallery-photos/home-preview.jpg'), false);
    }

    public function test_admin_can_remove_gallery_photo(): void
    {
        Storage::fake('public');

        $admin = User::query()->where('email', 'admin@mubcaa.test')->firstOrFail();
        Storage::disk('public')->put('gallery-photos/remove-me.jpg', 'image-bytes');

        $photo = GalleryPhoto::query()->create([
            'uploaded_by' => $admin->id,
            'title' => 'Remove Me',
            'photo_path' => 'gallery-photos/remove-me.jpg',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.gallery.destroy', $photo))
            ->assertRedirect();

        $this->assertDatabaseMissing('gallery_photos', [
            'id' => $photo->id,
        ]);
        Storage::disk('public')->assertMissing('gallery-photos/remove-me.jpg');
    }

    public function test_admin_can_upload_gallery_video(): void
    {
        Storage::fake('public');

        $admin = User::query()->where('email', 'admin@mubcaa.test')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.videos.store'), [
                'title' => 'Annual Recap',
                'caption' => 'A short recap from the annual event.',
                'video' => UploadedFile::fake()->create('annual-recap.mp4', 1024, 'video/mp4'),
            ])
            ->assertRedirect();

        $video = GalleryVideo::query()->firstOrFail();

        $this->assertSame('Annual Recap', $video->title);
        Storage::disk('public')->assertExists($video->video_path);
    }

    public function test_public_video_gallery_shows_uploaded_admin_videos(): void
    {
        $admin = User::query()->where('email', 'admin@mubcaa.test')->firstOrFail();

        GalleryVideo::query()->create([
            'uploaded_by' => $admin->id,
            'title' => 'Leadership Message',
            'caption' => 'Opening remarks from the event.',
            'video_path' => 'gallery-videos/leadership-message.mp4',
        ]);

        $this->get(route('events.videos'))
            ->assertOk()
            ->assertSee('Leadership Message')
            ->assertSee('Opening remarks from the event.')
            ->assertSee($admin->name);
    }

    public function test_admin_can_remove_gallery_video(): void
    {
        Storage::fake('public');

        $admin = User::query()->where('email', 'admin@mubcaa.test')->firstOrFail();
        Storage::disk('public')->put('gallery-videos/remove-me.mp4', 'video-bytes');

        $video = GalleryVideo::query()->create([
            'uploaded_by' => $admin->id,
            'title' => 'Remove Video',
            'video_path' => 'gallery-videos/remove-me.mp4',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.videos.destroy', $video))
            ->assertRedirect();

        $this->assertDatabaseMissing('gallery_videos', [
            'id' => $video->id,
        ]);
        Storage::disk('public')->assertMissing('gallery-videos/remove-me.mp4');
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

        $this->assertDatabaseHas('contact_submissions', [
            'name' => 'Contact User',
            'email' => 'contact@example.com',
            'subject' => 'Membership enquiry',
        ]);
    }

    public function test_admin_can_view_contact_submissions(): void
    {
        $admin = User::query()->where('email', 'admin@mubcaa.test')->firstOrFail();

        ContactSubmission::query()->create([
            'name' => 'Inbox Sender',
            'email' => 'sender@example.com',
            'phone' => '01712345678',
            'subject' => 'Need membership help',
            'message' => 'Please share the next steps for becoming a member.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.contacts.index'))
            ->assertOk()
            ->assertSee('Need membership help')
            ->assertSee('Inbox Sender')
            ->assertSee('sender@example.com')
            ->assertSee('Please share the next steps for becoming a member.');
    }
}
