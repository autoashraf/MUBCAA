<?php

namespace App\Http\Controllers;

use App\Support\SiteNavigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user()->load('profile', 'application');
        $profile = $user->profile;

        return view('member.dashboard', [
            'menu' => SiteNavigation::menu(),
            'user' => $user,
            'application' => $user->application,
            'workflowSteps' => $this->applicationWorkflowSteps($user->application),
            'profileCompletion' => $this->profileCompletion($profile),
            'stepLabels' => $this->completionSteps(),
        ]);
    }

    public function showCompletion(Request $request, int $step = 2): View
    {
        $user = $request->user()->load('profile', 'application');
        $profile = $user->profile;
        $steps = $this->completionSteps();
        $step = max(2, min($step, 10));

        return view('member.complete-profile', [
            'menu' => SiteNavigation::menu(),
            'user' => $user,
            'profile' => $profile,
            'application' => $user->application,
            'steps' => $steps,
            'currentStep' => $step,
            'profileCompletion' => $this->profileCompletion($profile),
            'stepDescriptions' => $this->stepDescriptions(),
            'areasOfInterest' => [
                'Networking Events',
                'Career Support',
                'Mentoring',
                'Volunteering',
                'Fundraising Activities',
                'Social Welfare Programs',
                'Business Collaboration',
                'Cultural Programs',
                'Sports Activities',
                'Training & Workshops',
            ],
        ]);
    }

    public function saveCompletion(Request $request): JsonResponse|RedirectResponse
    {
        $step = (int) $request->input('wizard_step', 2);
        $step = max(2, min($step, 10));
        $validated = $request->validate($this->rulesForStep($request, $step));
        $user = $request->user()->load('profile', 'application');
        $profile = $user->profile;

        if ($request->boolean('submit_for_verification') && ! $user->hasCompletedContactVerification()) {
            return back()->withErrors([
                'verification' => 'Verify your mobile number and email address before submitting your profile for admin review.',
            ]);
        }

        DB::transaction(function () use ($request, $validated, $user, $profile, $step): void {
            $profileData = $this->extractProfileData($request, $validated, $step);

            $profile = $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                array_merge($profileData, [
                    'completion_step' => max((int) ($profile?->completion_step ?? 1), $step),
                ]),
            );

            if ($step === 4) {
                $user->update([
                    'email' => $validated['email_address'],
                    'phone' => $validated['primary_mobile'],
                ]);
            }

            $application = $user->application;
            $submitted = $request->boolean('submit_for_verification');
            $status = $submitted ? 'pending_review' : ($step > 1 ? 'in_progress' : 'unverified');
            $approvalStep = 1;

            $user->update([
                'membership_status' => $status,
                'approval_step' => $approvalStep,
            ]);

            $user->application()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'status' => $status,
                    'current_step' => $application?->current_step ?? 1,
                    'total_steps' => $application?->total_steps ?? 10,
                    'submitted_at' => $submitted ? now() : $application?->submitted_at,
                ],
            );

            if ($submitted) {
                $profile->update([
                    'submitted_for_review_at' => now(),
                    'completion_step' => 10,
                ]);
            }
        });

        if ($request->boolean('submit_for_verification')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Thank you for completing your alumni membership profile. Your profile has been submitted successfully and is now Pending Review.',
                    'redirect_url' => route('member.dashboard'),
                    'submitted' => true,
                ]);
            }

            return redirect()
                ->route('member.dashboard')
                ->with('success', 'Thank you for completing your alumni membership profile. Your profile has been submitted successfully and is now Pending Review.');
        }

        if ($request->boolean('save_as_draft')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => "Draft saved for step {$step}. You can continue later.",
                    'next_step' => $step,
                    'submitted' => false,
                ]);
            }

            return redirect()
                ->route('member.profile.complete', ['step' => $step])
                ->with('success', "Draft saved for step {$step}. You can continue later.");
        }

        $nextStep = (int) $request->input('next_step', min($step + 1, 10));

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => "Step {$step} saved. Continue your alumni profile.",
                'next_step' => $nextStep,
                'submitted' => false,
            ]);
        }

        return redirect()
            ->route('member.profile.complete', ['step' => $nextStep])
            ->with('success', "Step {$step} saved. Continue your alumni profile.");
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'present_address' => ['required', 'string', 'max:255'],
            'city_district' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'short_bio' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $user->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
        ]);

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'primary_mobile' => $validated['phone'],
                'mobile_number' => $validated['phone'],
                'present_address' => $validated['present_address'],
                'city_district' => $validated['city_district'],
                'country' => $validated['country'],
                'occupation' => $validated['occupation'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'short_bio' => $validated['short_bio'] ?? null,
            ],
        );

        return back()->with('success', 'Profile updated successfully.');
    }

    private function completionSteps(): array
    {
        return [
            1 => 'Basic Info',
            2 => 'Academic Info',
            3 => 'Personal Info',
            4 => 'Contact Info',
            5 => 'Professional Info',
            6 => 'Media & Links',
            7 => 'Engagement',
            8 => 'Verification',
            9 => 'Privacy',
            10 => 'Declaration',
        ];
    }

    private function stepDescription(int $step): string
    {
        return [
            2 => 'Please provide your academic details for alumni record and verification.',
            3 => 'Please provide your personal details.',
            4 => 'Please provide your contact details.',
            5 => 'Please share your professional background to help us build a stronger alumni network.',
            6 => 'Please upload your profile photo and add your online profile links if available.',
            7 => 'Please let us know how you would like to stay involved with the alumni association.',
            8 => 'Please provide any supporting information to help us verify your alumni profile.',
            9 => 'Please choose how your information will be displayed in the alumni directory.',
            10 => 'Please review your information before submitting your profile for verification.',
        ][$step];
    }

    private function stepDescriptions(): array
    {
        return collect($this->completionSteps())
            ->except(1)
            ->map(fn ($label, $step) => $this->stepDescription($step))
            ->all();
    }

    private function rulesForStep(Request $request, int $step): array
    {
        return match ($step) {
            2 => [
                'ssc_passing_year' => ['nullable', 'string', 'max:50'],
                'hsc_passing_year' => ['nullable', 'string', 'max:50'],
                'group' => ['nullable', 'string', 'max:255'],
                'shift' => ['nullable', 'string', 'max:100'],
                'campus_branch' => ['nullable', 'string', 'max:150'],
            ],
            3 => [
                'date_of_birth' => ['nullable', 'date'],
                'gender' => ['nullable', Rule::in(['Male', 'Female', 'Other'])],
                'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
                'father_name' => ['nullable', 'string', 'max:255'],
                'mother_name' => ['nullable', 'string', 'max:255'],
                'marital_status' => ['nullable', Rule::in(['Single', 'Married', 'Other'])],
            ],
            4 => [
                'primary_mobile' => ['required', 'string', 'max:50'],
                'secondary_mobile' => ['nullable', 'string', 'max:50'],
                'whatsapp_number' => ['nullable', 'string', 'max:50'],
                'email_address' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
                'present_address' => ['nullable', 'string', 'max:1000'],
                'permanent_address' => ['nullable', 'string', 'max:1000'],
                'country' => ['required', 'string', 'max:100'],
                'city_district' => ['required', 'string', 'max:150'],
                'postal_code' => ['nullable', 'string', 'max:50'],
            ],
            5 => [
                'occupation' => ['nullable', 'string', 'max:255'],
                'organization_name' => ['nullable', 'string', 'max:255'],
                'designation' => ['nullable', 'string', 'max:255'],
                'industry' => ['nullable', 'string', 'max:255'],
                'office_address' => ['nullable', 'string', 'max:1000'],
                'work_email' => ['nullable', 'email', 'max:255'],
                'business_name' => ['nullable', 'string', 'max:255'],
                'professional_skills' => ['nullable', 'string', 'max:2000'],
            ],
            6 => [
                'profile_photo' => ['nullable', 'image', 'max:4096'],
                'cover_photo' => ['nullable', 'image', 'max:6144'],
                'short_bio' => ['nullable', 'string', 'max:2000'],
                'facebook_profile_link' => ['nullable', 'url', 'max:255'],
                'linkedin_profile_link' => ['nullable', 'url', 'max:255'],
                'website_portfolio_link' => ['nullable', 'url', 'max:255'],
            ],
            7 => [
                'interested_in_alumni_activities' => ['nullable', Rule::in(['1', '0'])],
                'areas_of_interest' => ['nullable', 'array'],
                'areas_of_interest.*' => ['string', 'max:100'],
                'volunteer_interest' => ['nullable', Rule::in(['1', '0'])],
                'donor_sponsor_interest' => ['nullable', Rule::in(['Yes', 'No', 'Maybe Later'])],
                'mentor_interest' => ['nullable', Rule::in(['1', '0'])],
                'suggestions' => ['nullable', 'string', 'max:2000'],
            ],
            8 => [
                'certificate_testimonial_upload' => ['nullable', 'file', 'max:6144'],
                'supporting_document_upload' => ['nullable', 'file', 'max:6144'],
            ],
            9 => [
                'profile_visibility' => ['required', Rule::in([
                    'Show my profile in the alumni directory',
                    'Show my profile only to verified members',
                    'Keep my profile private',
                ])],
                'contact_visibility' => ['required', Rule::in([
                    'Show my contact details to verified members only',
                    'Keep my contact details private',
                ])],
            ],
            10 => [
                'information_accuracy_confirmation' => ['accepted'],
                'terms_privacy_agreement' => ['accepted'],
                'admin_verification_agreement' => ['accepted'],
            ],
        };
    }

    private function extractProfileData(Request $request, array $validated, int $step): array
    {
        $data = match ($step) {
            2 => $validated,
            3 => $validated,
            4 => [
                'primary_mobile' => $validated['primary_mobile'],
                'mobile_number' => $validated['primary_mobile'],
                'secondary_mobile' => $validated['secondary_mobile'] ?? null,
                'whatsapp_number' => $validated['whatsapp_number'] ?? null,
                'email_address' => $validated['email_address'],
                'present_address' => $validated['present_address'] ?? null,
                'permanent_address' => $validated['permanent_address'] ?? null,
                'country' => $validated['country'],
                'city_district' => $validated['city_district'],
                'postal_code' => $validated['postal_code'] ?? null,
                'current_city' => $validated['city_district'],
            ],
            5 => $validated,
            6 => array_merge(
                collect($validated)->except(['profile_photo', 'cover_photo'])->all(),
                $this->storeUploadedFiles($request, [
                    'profile_photo' => 'profile_photo',
                    'cover_photo' => 'cover_photo',
                ]),
            ),
            7 => [
                'interested_in_alumni_activities' => $request->filled('interested_in_alumni_activities') ? $request->boolean('interested_in_alumni_activities') : null,
                'areas_of_interest' => $validated['areas_of_interest'] ?? [],
                'volunteer_interest' => $request->filled('volunteer_interest') ? $request->boolean('volunteer_interest') : null,
                'donor_sponsor_interest' => $validated['donor_sponsor_interest'] ?? null,
                'mentor_interest' => $request->filled('mentor_interest') ? $request->boolean('mentor_interest') : null,
                'suggestions' => $validated['suggestions'] ?? null,
            ],
            8 => $this->storeUploadedFiles($request, [
                'certificate_testimonial_upload' => 'certificate_testimonial_upload',
                'supporting_document_upload' => 'supporting_document_upload',
            ]),
            9 => $validated,
            10 => [
                'information_accuracy_confirmation' => true,
                'terms_privacy_agreement' => true,
                'admin_verification_agreement' => true,
            ],
        };

        return $data;
    }

    private function storeUploadedFiles(Request $request, array $map): array
    {
        $stored = [];

        foreach ($map as $field => $column) {
            if ($request->hasFile($field)) {
                $stored[$column] = $request->file($field)->store('member-profile', 'public');
            }
        }

        return $stored;
    }

    private function profileCompletion($profile): int
    {
        if (! $profile) {
            return 10;
        }

        $fields = [
            $profile->student_id_or_roll,
            $profile->passing_year_batch,
            $profile->date_of_birth,
            $profile->gender,
            $profile->father_name,
            $profile->mother_name,
            $profile->present_address,
            $profile->country,
            $profile->city_district,
            $profile->occupation,
            $profile->organization_name,
            $profile->short_bio,
            $profile->profile_visibility,
            $profile->contact_visibility,
        ];

        $completed = collect($fields)->filter(fn ($value) => filled($value))->count();

        return (int) max(10, round(($completed / count($fields)) * 100));
    }

    private function applicationWorkflowSteps($application): array
    {
        $steps = [
            ['step_number' => 1, 'title' => 'Profile Created', 'description' => 'Basic registration completed and profile created.'],
            ['step_number' => 2, 'title' => 'Profile In Progress', 'description' => 'Member continues profile completion and contact verification.'],
            ['step_number' => 3, 'title' => 'Pending Review', 'description' => 'Final profile submitted for admin review.'],
            ['step_number' => 4, 'title' => 'Verified Member', 'description' => 'Admin approves the alumni membership profile.'],
        ];

        return collect($steps)
            ->take(max(1, min((int) ($application?->total_steps ?? 4), count($steps))))
            ->map(fn (array $step) => (object) $step)
            ->all();
    }
}
