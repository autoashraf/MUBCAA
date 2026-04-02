<?php

namespace App\Http\Controllers;

use Closure;
use App\Support\CountryDialCodes;
use App\Support\PhoneNumber;
use App\Support\SiteNavigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MemberDashboardController extends Controller
{
    public function profile(Request $request): View
    {
        $user = $request->user()->load('profile', 'application', 'verificationTokens');
        $profile = $user->profile;

        return view('member.profile', [
            'menu' => SiteNavigation::menu(),
            'user' => $user,
            'profile' => $profile,
            'application' => $user->application,
            'profileCompletion' => $this->profileCompletion($user, $profile),
            'workflowSteps' => $this->applicationWorkflowSteps($user->application),
            'missingSubmissionItems' => $this->missingSubmissionItems($user, $profile),
            'profileLocked' => $this->isProfileSubmissionLocked($user),
            ...$this->verificationModalData($request, $user, $profile),
        ]);
    }

    public function index(Request $request): View
    {
        $user = $request->user()->load('profile', 'application', 'verificationTokens');
        $profile = $user->profile;

        return view('member.dashboard', [
            'menu' => SiteNavigation::menu(),
            'user' => $user,
            'application' => $user->application,
            'workflowSteps' => $this->applicationWorkflowSteps($user->application),
            'profileCompletion' => $this->profileCompletion($user, $profile),
            'stepLabels' => $this->completionSteps(),
            'missingSubmissionItems' => $this->missingSubmissionItems($user, $profile),
            'affiliateSummary' => $this->affiliateSummary($user),
            ...$this->verificationModalData($request, $user, $profile),
        ]);
    }

    public function showCompletion(Request $request, int $step = 2): View
    {
        $user = $request->user()->load('profile', 'application');
        $profile = $user->profile;
        $steps = $this->completionFormSteps();
        $step = max(2, min($step, 10));

        return view('member.complete-profile', [
            'menu' => SiteNavigation::menu(),
            'user' => $user,
            'profile' => $profile,
            'application' => $user->application,
            'steps' => $steps,
            'currentStep' => $step,
            'profileCompletion' => $this->profileCompletion($user, $profile),
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
            'academicGroups' => ['Science', 'Commerce', 'Arts'],
            'academicShifts' => ['Morning', 'Day', 'Girls', 'Boys'],
            'campusBranches' => ['Main Girls', 'Boys', 'Ibrahimpur', 'Shewrapara', 'Rupnagar', 'College Campus'],
            'passingYears' => $this->passingYearOptions(),
            'districts' => $this->bangladeshDistricts(),
            'occupations' => $this->occupationOptions(),
            'designations' => $this->designationOptions(),
            'industries' => $this->industryOptions(),
            'countryDialCodes' => CountryDialCodes::all(),
            'stepCompletion' => $this->stepCompletionStates($user, $profile),
            'profileLocked' => $this->isProfileSubmissionLocked($user),
        ]);
    }

    public function saveCompletion(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user()->load('profile', 'application');

        if ($this->isProfileSubmissionLocked($user)) {
            $message = __('Your alumni membership profile has already been submitted and cannot be resubmitted.');

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                ], 409);
            }

            return redirect()
                ->route('member.dashboard')
                ->with('error', $message);
        }

        $step = (int) $request->input('wizard_step', 2);
        $step = max(2, min($step, 10));
        $validated = $request->validate($this->rulesForStep($request, $step));
        $profile = $user->profile;

        if ($request->boolean('submit_for_verification') && ! $user->hasCompletedContactVerification()) {
            return back()->withErrors([
                'verification' => __('Verify your mobile number and email address before submitting your profile for admin review.'),
            ]);
        }

        DB::transaction(function () use ($request, $validated, $user, $profile, $step): void {
            $previousCompletionStep = (int) ($profile?->completion_step ?? 1);
            $profileData = $this->extractProfileData($request, $validated, $step, $profile);

            $profile = $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                array_merge($profileData, [
                    'completion_step' => $previousCompletionStep,
                ]),
            );

            $application = $user->application;
            $submitted = $request->boolean('submit_for_verification');
            $isDraft = $request->boolean('save_as_draft');
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
                    'current_step' => max((int) ($application?->current_step ?? 1), $submitted ? 10 : $step),
                    'total_steps' => $application?->total_steps ?? 10,
                    'submitted_at' => $submitted ? now() : $application?->submitted_at,
                ],
            );

            if ($submitted) {
                $profile->update([
                    'submitted_for_review_at' => now(),
                    'completion_step' => 10,
                ]);

                $freshUser = $user->fresh()->load('profile', 'application');
                $missingFields = $this->missingRequiredSubmissionItems($freshUser, $freshUser->profile);

                if ($missingFields !== []) {
                    throw ValidationException::withMessages([
                        'submit_for_verification' => [
                            __('Complete all required fields before submission. Missing: :fields', [
                                'fields' => implode(', ', array_slice($missingFields, 0, 6)).(count($missingFields) > 6 ? '...' : ''),
                            ]),
                        ],
                    ]);
                }

                return;
            }

            if (! $isDraft || $this->stepIsComplete($user, $step)) {
                $profile->update([
                    'completion_step' => max($previousCompletionStep, $step),
                ]);
            }
        });

        if ($request->boolean('submit_for_verification')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => __('Thank you for completing your alumni membership profile. Your profile has been submitted successfully and is now under review.'),
                    'redirect_url' => route('member.dashboard'),
                    'submitted' => true,
                ]);
            }

            return redirect()
                ->route('member.dashboard')
                ->with('success', __('Thank you for completing your alumni membership profile. Your profile has been submitted successfully and is now under review.'));
        }

        if ($request->boolean('save_as_draft')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => __('Draft saved for step :step. You can continue later.', ['step' => $step]),
                    'next_step' => $step,
                    'step_url' => route('member.profile.complete', ['step' => $step]),
                    'summary' => $this->wizardSummary($request->user()->fresh()->load('profile', 'application'), $step),
                    'step_completion' => $this->stepCompletionStates($request->user()->fresh()->load('profile', 'application'), $request->user()->fresh()->profile),
                    'submitted' => false,
                ]);
            }

            return redirect()
                ->route('member.profile.complete', ['step' => $step])
                ->with('success', __('Draft saved for step :step. You can continue later.', ['step' => $step]));
        }

        $nextStep = (int) $request->input('next_step', min($step + 1, 10));

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => __('Step :step saved. Continue your alumni profile.', ['step' => $step]),
                'next_step' => $nextStep,
                'step_url' => route('member.profile.complete', ['step' => $nextStep]),
                'summary' => $this->wizardSummary($request->user()->fresh()->load('profile', 'application'), $nextStep),
                'step_completion' => $this->stepCompletionStates($request->user()->fresh()->load('profile', 'application'), $request->user()->fresh()->profile),
                'submitted' => false,
            ]);
        }

        return redirect()
            ->route('member.profile.complete', ['step' => $nextStep])
            ->with('success', __('Step :step saved. Continue your alumni profile.', ['step' => $step]));
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
        $canonicalPhone = PhoneNumber::normalize($validated['phone'], '+880');

        $user->update([
            'name' => $validated['name'],
            'phone' => $canonicalPhone ?? $validated['phone'],
        ]);

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'primary_mobile' => $canonicalPhone ?? $validated['phone'],
                'mobile_number' => $canonicalPhone ?? $validated['phone'],
                'present_address' => $validated['present_address'],
                'city_district' => $validated['city_district'],
                'country' => $validated['country'],
                'occupation' => $validated['occupation'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'short_bio' => $validated['short_bio'] ?? null,
            ],
        );

        return back()->with('success', __('Profile updated successfully.'));
    }

    private function completionSteps(): array
    {
        return [
            1 => __('Basic Info'),
            2 => __('Academic Info'),
            3 => __('Personal Info'),
            4 => __('Contact Info'),
            5 => __('Professional Info'),
            6 => __('Media & Links'),
            7 => __('Engagement'),
            8 => __('Verification'),
            9 => __('Privacy'),
            10 => __('Declaration'),
        ];
    }

    private function completionFormSteps(): array
    {
        return collect($this->completionSteps())
            ->except(1)
            ->all();
    }

    private function verificationModalData(Request $request, $user, $profile): array
    {
        $showVerificationModal = $request->boolean('verify_contacts') && ! $user->hasCompletedContactVerification();

        return [
            'showVerificationModal' => $showVerificationModal,
            'verificationEmail' => $user->email,
            'verificationMobile' => $profile?->mobile_number ?: $user->phone,
            'emailVerified' => $user->hasVerifiedEmail(),
            'mobileVerified' => $user->hasVerifiedMobile(),
            'emailResendCooldown' => $this->verificationCooldownRemaining($user, 'email'),
            'mobileResendCooldown' => $this->verificationCooldownRemaining($user, 'mobile'),
            'emailExpiryCountdown' => $this->verificationExpiryRemaining($user, 'email'),
            'mobileExpiryCountdown' => $this->verificationExpiryRemaining($user, 'mobile'),
            'verificationContinueUrl' => route('member.profile.complete', ['step' => $this->wizardResumeStep($user, $profile)]),
            'verificationSuccessMessage' => session('success'),
            'localOtpCodes' => $this->localUserOtpCodes($user),
        ];
    }

    private function wizardResumeStep($user, $profile): int
    {
        return max(2, min(10, (int) ($user->application?->current_step ?? $profile?->completion_step ?? 2)));
    }

    private function stepIsComplete($user, int $step): bool
    {
        $freshUser = $user->fresh()->load('profile', 'application');

        return (bool) ($this->stepCompletionStates($freshUser, $freshUser->profile)[$step] ?? false);
    }

    private function localUserOtpCodes($user): ?array
    {
        if (! App::environment('local')) {
            return null;
        }

        $user->loadMissing('verificationTokens');

        return array_filter([
            'email' => $user->verificationTokens
                ->where('channel', 'email')
                ->whereNull('verified_at')
                ->sortByDesc('sent_at')
                ->first()?->code,
            'mobile' => $user->verificationTokens
                ->where('channel', 'mobile')
                ->whereNull('verified_at')
                ->sortByDesc('sent_at')
                ->first()?->code,
        ]);
    }

    private function verificationCooldownRemaining($user, string $channel): int
    {
        $lastSentAt = $user->verificationTokens
            ->where('channel', $channel)
            ->whereNull('verified_at')
            ->sortByDesc('sent_at')
            ->first()?->sent_at;

        if (! $lastSentAt instanceof Carbon) {
            return 0;
        }

        $elapsed = now()->diffInSeconds($lastSentAt);

        return $elapsed >= 60 ? 0 : 60 - $elapsed;
    }

    private function verificationExpiryRemaining($user, string $channel): int
    {
        $expiresAt = $user->verificationTokens
            ->where('channel', $channel)
            ->whereNull('verified_at')
            ->sortByDesc('sent_at')
            ->first()?->expires_at;

        if (! $expiresAt instanceof Carbon) {
            return 0;
        }

        return max(0, now()->diffInSeconds($expiresAt, false));
    }

    private function affiliateSummary($user): array
    {
        $referrals = $user->referrals()->with('application')->latest()->get();

        return [
            'code' => $user->affiliate_code ?: $user->defaultAffiliateCode(),
            'link' => $user->affiliateLink(),
            'total' => $referrals->count(),
            'verified' => $referrals->where('membership_status', 'verified')->count(),
            'under_review' => $referrals->whereIn('membership_status', ['pending_review', 'under_review'])->count(),
            'recent' => $referrals->take(5),
        ];
    }

    private function stepDescription(int $step): string
    {
        return [
            2 => __('Please provide your academic details for alumni record and verification.'),
            3 => __('Please provide your personal details.'),
            4 => __('Please provide your contact details.'),
            5 => __('Please share your professional background to help us build a stronger alumni network.'),
            6 => __('Please upload your profile photo, business card, and online profile links if available.'),
            7 => __('Please let us know how you would like to stay involved with the alumni association.'),
            8 => __('Please provide any supporting information to help us verify your alumni profile.'),
            9 => __('Please choose how your information will be displayed in the alumni directory.'),
            10 => __('Please review your information before submitting your profile for verification.'),
        ][$step];
    }

    private function stepDescriptions(): array
    {
        return collect($this->completionFormSteps())
            ->map(fn ($label, $step) => $this->stepDescription($step))
            ->all();
    }

    private function rulesForStep(Request $request, int $step): array
    {
        $isDraft = $request->boolean('save_as_draft');

        return match ($step) {
            2 => [
                'ssc_passing_year' => [$isDraft ? 'nullable' : 'required_without:hsc_passing_year', 'nullable', 'string', 'max:50'],
                'hsc_passing_year' => [$isDraft ? 'nullable' : 'required_without:ssc_passing_year', 'nullable', 'string', 'max:50'],
                'group' => [$isDraft ? 'nullable' : 'required', Rule::in($this->academicGroupOptions())],
                'shift' => [$isDraft ? 'nullable' : 'required', Rule::in($this->shiftOptions())],
                'campus_branch' => [$isDraft ? 'nullable' : 'required', Rule::in($this->campusOptions())],
            ],
            3 => [
                'date_of_birth' => [$isDraft ? 'nullable' : 'required', 'date'],
                'gender' => [$isDraft ? 'nullable' : 'required', Rule::in(['Male', 'Female', 'Other'])],
                'blood_group' => [$isDraft ? 'nullable' : 'required', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
                'father_name' => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
                'mother_name' => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
                'marital_status' => [$isDraft ? 'nullable' : 'required', Rule::in(['Single', 'Married', 'Other'])],
            ],
            4 => [
                'primary_mobile_country_code' => ['nullable', 'string', 'max:10'],
                'secondary_mobile_country_code' => ['nullable', 'string', 'max:10'],
                'whatsapp_country_code' => ['nullable', 'string', 'max:10'],
                'primary_mobile' => $this->contactNumberRules($request, 'primary_mobile_country_code', $isDraft),
                'secondary_mobile' => $this->contactNumberRules($request, 'secondary_mobile_country_code', $isDraft),
                'whatsapp_number' => $this->contactNumberRules($request, 'whatsapp_country_code', $isDraft),
                'email_address' => [$isDraft ? 'nullable' : 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
                'present_address' => [$isDraft ? 'nullable' : 'required', 'string', 'max:1000'],
                'permanent_address' => [$isDraft ? 'nullable' : 'required', 'string', 'max:1000'],
                'country' => [$isDraft ? 'nullable' : 'required', 'string', 'max:100'],
                'city_district' => [$isDraft ? 'nullable' : 'required', Rule::in($this->bangladeshDistricts())],
                'postal_code' => [$isDraft ? 'nullable' : 'required', 'string', 'max:50'],
            ],
            5 => [
                'occupation' => [$isDraft ? 'nullable' : 'required', Rule::in($this->occupationOptions())],
                'organization_name' => ['nullable', 'string', 'max:255'],
                'designation' => [$isDraft ? 'nullable' : 'required', Rule::in($this->designationOptions())],
                'industry' => [$isDraft ? 'nullable' : 'required', Rule::in($this->industryOptions())],
                'office_address' => ['nullable', 'string', 'max:1000'],
            ],
            6 => [
                'profile_photo' => [$this->requiredFileRule($request, 'profile_photo', $isDraft), 'image', 'max:4096'],
                'cover_photo' => [$this->requiredFileRule($request, 'cover_photo', $isDraft), 'image', 'max:6144'],
                'business_card_upload' => ['nullable', 'file', 'max:6144'],
                'remove_profile_photo' => ['nullable', 'boolean'],
                'remove_cover_photo' => ['nullable', 'boolean'],
                'facebook_profile_link' => [$isDraft ? 'nullable' : 'required', 'url', 'max:255'],
                'linkedin_profile_link' => ['nullable', 'url', 'max:255'],
                'website_portfolio_link' => ['nullable', 'url', 'max:255'],
            ],
            7 => [
                'interested_in_alumni_activities' => [$isDraft ? 'nullable' : 'required', Rule::in(['1', '0'])],
                'areas_of_interest' => [$isDraft ? 'nullable' : 'required', 'array', 'min:1'],
                'areas_of_interest.*' => ['string', 'max:100'],
                'volunteer_interest' => [$isDraft ? 'nullable' : 'required', Rule::in(['1', '0'])],
                'donor_sponsor_interest' => [$isDraft ? 'nullable' : 'required', Rule::in(['Yes', 'No', 'Maybe Later'])],
                'mentor_interest' => [$isDraft ? 'nullable' : 'required', Rule::in(['1', '0'])],
                'suggestions' => ['nullable', 'string', 'max:2000'],
            ],
            8 => [
                'certificate_testimonial_upload' => [$this->requiredFileRule($request, 'certificate_testimonial_upload', $isDraft), 'file', 'max:6144'],
                'supporting_document_upload' => [$this->requiredFileRule($request, 'supporting_document_upload', $isDraft), 'file', 'max:6144'],
            ],
            9 => [
                'profile_visibility' => [$isDraft ? 'nullable' : 'required', Rule::in([
                    'Show my profile in the alumni directory',
                    'Show my profile only to verified members',
                    'Keep my profile private',
                ])],
                'contact_visibility' => [$isDraft ? 'nullable' : 'required', Rule::in([
                    'Show my contact details to verified members only',
                    'Keep my contact details private',
                ])],
            ],
            10 => [
                'information_accuracy_confirmation' => [$isDraft ? 'nullable' : 'accepted'],
                'terms_privacy_agreement' => [$isDraft ? 'nullable' : 'accepted'],
                'admin_verification_agreement' => [$isDraft ? 'nullable' : 'accepted'],
            ],
        };
    }

    private function extractProfileData(Request $request, array $validated, int $step, $profile = null): array
    {
        $data = match ($step) {
            2 => $validated,
            3 => $validated,
            4 => [
                'primary_mobile' => $this->normalizeContactNumber($validated['primary_mobile'] ?? ($profile?->primary_mobile ?: $request->user()->phone)),
                'mobile_number' => $this->normalizeContactNumber($validated['primary_mobile'] ?? ($profile?->primary_mobile ?: $request->user()->phone)),
                'secondary_mobile' => $this->normalizeContactNumber($validated['secondary_mobile'] ?? null),
                'whatsapp_number' => $this->normalizeContactNumber($validated['whatsapp_number'] ?? null),
                'email_address' => $validated['email_address'] ?? ($profile?->email_address ?: $request->user()->email),
                'present_address' => $validated['present_address'] ?? null,
                'permanent_address' => $validated['permanent_address'] ?? null,
                'country' => $validated['country'] ?? null,
                'city_district' => $validated['city_district'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'current_city' => $validated['city_district'] ?? null,
            ],
            5 => $validated,
            6 => $this->stepSixProfileData($request, $validated, $profile),
            7 => [
                'interested_in_alumni_activities' => $request->filled('interested_in_alumni_activities') ? $request->boolean('interested_in_alumni_activities') : true,
                'areas_of_interest' => $validated['areas_of_interest'] ?? [],
                'volunteer_interest' => $request->filled('volunteer_interest') ? $request->boolean('volunteer_interest') : true,
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

    private function contactNumberRules(Request $request, string $countryCodeField, bool $isDraft): array
    {
        return [
            $isDraft ? 'nullable' : 'required',
            'string',
            'max:50',
            function (string $attribute, mixed $value, Closure $fail) use ($request, $countryCodeField): void {
                if (! filled($value)) {
                    return;
                }

                $countryCode = (string) $request->input($countryCodeField, '+880');
                $normalized = $this->normalizeContactNumber($value, $countryCode);

                if ($normalized === null) {
                    $fail(__('Enter a valid mobile number.'));
                    return;
                }

                $nationalNumber = PhoneNumber::split((string) $value, $countryCode)['national_number'] ?? '';

                if ($countryCode === '+880' && ! in_array(strlen($nationalNumber), [10, 11], true)) {
                    $fail(__('For Bangladesh numbers, enter 10 or 11 digits.'));
                }
            },
        ];
    }

    private function normalizeContactNumber(mixed $value, string $countryCode = '+880'): ?string
    {
        return PhoneNumber::normalize((string) $value, $countryCode);
    }

    private function stepSixProfileData(Request $request, array $validated, $profile = null): array
    {
        $data = collect($validated)
            ->except(['profile_photo', 'cover_photo', 'business_card_upload', 'remove_profile_photo', 'remove_cover_photo'])
            ->all();

        if ($request->boolean('remove_profile_photo') && $profile?->profile_photo) {
            Storage::disk('public')->delete($profile->profile_photo);
            $data['profile_photo'] = null;
        }

        if ($request->boolean('remove_cover_photo') && $profile?->cover_photo) {
            Storage::disk('public')->delete($profile->cover_photo);
            $data['cover_photo'] = null;
        }

        $uploads = $this->storeUploadedFiles($request, [
            'profile_photo' => 'profile_photo',
            'cover_photo' => 'cover_photo',
            'business_card_upload' => 'business_card_upload',
        ], $profile);

        return array_merge($data, $uploads);
    }

    private function storeUploadedFiles(Request $request, array $map, $profile = null): array
    {
        $stored = [];

        foreach ($map as $field => $column) {
            if ($request->hasFile($field)) {
                if ($profile?->{$column}) {
                    Storage::disk('public')->delete($profile->{$column});
                }

                $stored[$column] = $request->file($field)->store('member-profile', 'public');
            }
        }

        return $stored;
    }

    private function profileCompletion($user, $profile): int
    {
        if (! $profile) {
            return 10;
        }

        $fields = collect($this->completionFieldGroups($user, $profile))
            ->flatten(1)
            ->values()
            ->all();

        $completed = collect($fields)->filter(fn ($value) => filled($value))->count();

        return (int) max(10, round(($completed / count($fields)) * 100));
    }

    private function completionFieldGroups($user, $profile): array
    {
        return [
            2 => [
                $profile?->ssc_passing_year || $profile?->hsc_passing_year ? 'filled' : null,
                $profile?->group,
                $profile?->shift,
                $profile?->campus_branch,
            ],
            3 => [
                $profile?->father_name,
                $profile?->mother_name,
                $profile?->date_of_birth,
                $profile?->gender,
                $profile?->blood_group,
                $profile?->marital_status,
            ],
            4 => [
                $profile?->primary_mobile ?: $user->phone,
                $profile?->secondary_mobile,
                $profile?->whatsapp_number,
                $profile?->email_address ?: $user->email,
                $profile?->present_address,
                $profile?->permanent_address,
                $profile?->country,
                $profile?->city_district,
                $profile?->postal_code,
            ],
            5 => [
                $profile?->occupation,
                $profile?->designation,
                $profile?->industry,
            ],
            6 => [
                $profile?->profile_photo,
                $profile?->cover_photo,
                $profile?->facebook_profile_link,
            ],
            7 => [
                ! is_null($profile?->interested_in_alumni_activities) ? 'filled' : null,
                filled($profile?->areas_of_interest) ? 'filled' : null,
                ! is_null($profile?->volunteer_interest) ? 'filled' : null,
                $profile?->donor_sponsor_interest,
                ! is_null($profile?->mentor_interest) ? 'filled' : null,
            ],
            8 => [
                $profile?->certificate_testimonial_upload,
                $profile?->supporting_document_upload,
            ],
            9 => [
                $profile?->profile_visibility,
                $profile?->contact_visibility,
            ],
            10 => [
                $profile?->information_accuracy_confirmation ? 'filled' : null,
                $profile?->terms_privacy_agreement ? 'filled' : null,
                $profile?->admin_verification_agreement ? 'filled' : null,
            ],
        ];
    }

    private function applicationWorkflowSteps($application): array
    {
        $steps = [
            ['step_number' => 1, 'title' => __('Profile Created'), 'description' => __('Basic registration completed and profile created.')],
            ['step_number' => 2, 'title' => __('Profile In Progress'), 'description' => __('Member continues profile completion and contact verification.')],
            ['step_number' => 3, 'title' => __('Under Review'), 'description' => __('Final profile submitted for admin review.')],
            ['step_number' => 4, 'title' => __('Verified Member'), 'description' => __('Admin approves the alumni membership profile.')],
        ];

        return collect($steps)
            ->take(max(1, min((int) ($application?->total_steps ?? 4), count($steps))))
            ->map(fn (array $step) => (object) $step)
            ->all();
    }

    private function missingSubmissionItems($user, $profile): array
    {
        $steps = [
            2 => [
                'Passing Year (SSC or HSC)' => $profile?->ssc_passing_year || $profile?->hsc_passing_year ? 'filled' : null,
                'Group' => $profile?->group,
                'Shift' => $profile?->shift,
                'Campus / Branch' => $profile?->campus_branch,
            ],
            3 => [
                'Father’s Name' => $profile?->father_name,
                'Mother’s Name' => $profile?->mother_name,
                'Date of Birth' => $profile?->date_of_birth,
                'Gender' => $profile?->gender,
                'Blood Group' => $profile?->blood_group,
                'Marital Status' => $profile?->marital_status,
            ],
            4 => [
                'Primary Mobile Number' => $profile?->primary_mobile ?: $user->phone,
                'Email Address' => $profile?->email_address ?: $user->email,
                'Present Address' => $profile?->present_address,
                'Permanent Address' => $profile?->permanent_address,
                'Country' => $profile?->country,
                'City / District' => $profile?->city_district ?: $profile?->current_city,
                'Postal Code' => $profile?->postal_code,
            ],
            5 => [
                'Occupation' => $profile?->occupation,
                'Designation / Job Title' => $profile?->designation,
                'Industry' => $profile?->industry,
            ],
            6 => [
                'Profile Photo' => $profile?->profile_photo,
            ],
            7 => [
                'Areas of Interest' => filled($profile?->areas_of_interest) ? 'filled' : null,
                'Donor / Sponsor Interest' => $profile?->donor_sponsor_interest,
                'Mentor Interest' => ! is_null($profile?->mentor_interest) ? 'filled' : null,
            ],
            8 => [
                'SSC Certificate / Testimonial / Admit Card' => $profile?->certificate_testimonial_upload,
            ],
            9 => [
                'Profile Visibility' => $profile?->profile_visibility,
                'Contact Visibility' => $profile?->contact_visibility,
            ],
            10 => [
                'Information Accuracy Confirmation' => $profile?->information_accuracy_confirmation,
                'Terms & Privacy Agreement' => $profile?->terms_privacy_agreement,
                'Admin Verification Agreement' => $profile?->admin_verification_agreement,
            ],
        ];

        return collect($steps)->map(function (array $fields, int $stepNumber) {
            $missing = collect($fields)
                ->filter(fn ($value) => blank($value) || $value === false)
                ->keys()
                ->values()
                ->all();

            return [
                'step' => $stepNumber,
                'title' => $this->completionSteps()[$stepNumber],
                'missing' => $missing,
            ];
        })->filter(fn (array $item) => count($item['missing']) > 0)->values()->all();
    }

    private function wizardSummary($user, int $step): array
    {
        $status = $user->application?->status ?? $user->membership_status;

        return [
            'status' => $status,
            'status_label' => $status === 'pending_review'
                ? 'Under Review'
                : str($status)->replace('_', ' ')->title()->toString(),
            'completion' => $this->profileCompletion($user, $user->profile),
            'step' => $step,
        ];
    }

    private function isProfileSubmissionLocked($user): bool
    {
        $status = $user->application?->status ?? $user->membership_status;

        return in_array($status, ['pending_review', 'under_review', 'approved', 'verified'], true)
            || ! is_null($user->application?->submitted_at)
            || ! is_null($user->profile?->submitted_for_review_at);
    }

    private function requiredFileRule(Request $request, string $field, bool $isDraft): string
    {
        if ($isDraft) {
            return 'nullable';
        }

        $profile = $request->user()?->profile;

        if ($request->hasFile($field)) {
            return 'required';
        }

        return match ($field) {
            'profile_photo' => blank($profile?->profile_photo) || $request->boolean('remove_profile_photo') ? 'required' : 'nullable',
            'cover_photo' => blank($profile?->cover_photo) || $request->boolean('remove_cover_photo') ? 'required' : 'nullable',
            'certificate_testimonial_upload' => blank($profile?->certificate_testimonial_upload) ? 'required' : 'nullable',
            'supporting_document_upload' => blank($profile?->supporting_document_upload) ? 'required' : 'nullable',
            default => 'required',
        };
    }

    private function missingRequiredSubmissionItems($user, $profile): array
    {
        $fields = [
            'Passing Year (SSC or HSC)' => $profile?->ssc_passing_year || $profile?->hsc_passing_year ? 'filled' : null,
            'Group' => $profile?->group,
            'Shift' => $profile?->shift,
            'Campus / Branch' => $profile?->campus_branch,
            'Father’s Name' => $profile?->father_name,
            'Mother’s Name' => $profile?->mother_name,
            'Date of Birth' => $profile?->date_of_birth,
            'Gender' => $profile?->gender,
            'Blood Group' => $profile?->blood_group,
            'Marital Status' => $profile?->marital_status,
            'Primary Mobile Number' => $profile?->primary_mobile ?: $user->phone,
            'Secondary Mobile Number' => $profile?->secondary_mobile,
            'WhatsApp Number' => $profile?->whatsapp_number,
            'Email Address' => $profile?->email_address ?: $user->email,
            'Present Address' => $profile?->present_address,
            'Permanent Address' => $profile?->permanent_address,
            'Country' => $profile?->country,
            'City / District' => $profile?->city_district ?: $profile?->current_city,
            'Postal Code' => $profile?->postal_code,
            'Occupation' => $profile?->occupation,
            'Designation / Job Title' => $profile?->designation,
            'Industry' => $profile?->industry,
            'Profile Photo' => $profile?->profile_photo,
            'Cover Photo' => $profile?->cover_photo,
            'Facebook Profile Link' => $profile?->facebook_profile_link,
            'Interested in Alumni Activities' => ! is_null($profile?->interested_in_alumni_activities) ? 'filled' : null,
            'Areas of Interest' => filled($profile?->areas_of_interest) ? 'filled' : null,
            'Volunteer Interest' => ! is_null($profile?->volunteer_interest) ? 'filled' : null,
            'Donor / Sponsor Interest' => $profile?->donor_sponsor_interest,
            'Mentor Interest' => ! is_null($profile?->mentor_interest) ? 'filled' : null,
            'SSC Certificate / Testimonial / Admit Card' => $profile?->certificate_testimonial_upload,
            'Supporting Document Upload' => $profile?->supporting_document_upload,
            'Profile Visibility' => $profile?->profile_visibility,
            'Contact Visibility' => $profile?->contact_visibility,
            'Information Accuracy Confirmation' => $profile?->information_accuracy_confirmation ? 'filled' : null,
            'Terms & Privacy Agreement' => $profile?->terms_privacy_agreement ? 'filled' : null,
            'Admin Verification Agreement' => $profile?->admin_verification_agreement ? 'filled' : null,
        ];

        return collect($fields)
            ->filter(fn ($value) => blank($value) || $value === false)
            ->keys()
            ->values()
            ->all();
    }

    private function stepCompletionStates($user, $profile): array
    {
        $requiredByStep = [
            2 => [
                'Passing Year (SSC or HSC)' => $profile?->ssc_passing_year || $profile?->hsc_passing_year ? 'filled' : null,
                'Group' => $profile?->group,
                'Shift' => $profile?->shift,
                'Campus / Branch' => $profile?->campus_branch,
            ],
            3 => [
                'Father’s Name' => $profile?->father_name,
                'Mother’s Name' => $profile?->mother_name,
                'Date of Birth' => $profile?->date_of_birth,
                'Gender' => $profile?->gender,
                'Blood Group' => $profile?->blood_group,
                'Marital Status' => $profile?->marital_status,
            ],
            4 => [
                'Primary Mobile Number' => $profile?->primary_mobile ?: $user->phone,
                'Secondary Mobile Number' => $profile?->secondary_mobile,
                'WhatsApp Number' => $profile?->whatsapp_number,
                'Email Address' => $profile?->email_address ?: $user->email,
                'Present Address' => $profile?->present_address,
                'Permanent Address' => $profile?->permanent_address,
                'Country' => $profile?->country,
                'City / District' => $profile?->city_district ?: $profile?->current_city,
                'Postal Code' => $profile?->postal_code,
            ],
            5 => [
                'Occupation' => $profile?->occupation,
                'Designation / Job Title' => $profile?->designation,
                'Industry' => $profile?->industry,
            ],
            6 => [
                'Profile Photo' => $profile?->profile_photo,
                'Cover Photo' => $profile?->cover_photo,
                'Facebook Profile Link' => $profile?->facebook_profile_link,
            ],
            7 => [
                'Interested in Alumni Activities' => ! is_null($profile?->interested_in_alumni_activities) ? 'filled' : null,
                'Areas of Interest' => filled($profile?->areas_of_interest) ? 'filled' : null,
                'Volunteer Interest' => ! is_null($profile?->volunteer_interest) ? 'filled' : null,
                'Donor / Sponsor Interest' => $profile?->donor_sponsor_interest,
                'Mentor Interest' => ! is_null($profile?->mentor_interest) ? 'filled' : null,
            ],
            8 => [
                'SSC Certificate / Testimonial / Admit Card' => $profile?->certificate_testimonial_upload,
                'Supporting Document Upload' => $profile?->supporting_document_upload,
            ],
            9 => [
                'Profile Visibility' => $profile?->profile_visibility,
                'Contact Visibility' => $profile?->contact_visibility,
            ],
        ];

        return collect($requiredByStep)
            ->map(fn (array $fields) => collect($fields)->every(fn ($value) => filled($value)))
            ->all();
    }

    private function academicGroupOptions(): array
    {
        return ['Science', 'Commerce', 'Arts'];
    }

    private function passingYearOptions(): array
    {
        return array_map('strval', range((int) date('Y'), 1970));
    }

    private function shiftOptions(): array
    {
        return ['Morning', 'Day', 'Girls', 'Boys'];
    }

    private function campusOptions(): array
    {
        return ['Main Girls', 'Boys', 'Ibrahimpur', 'Shewrapara', 'Rupnagar', 'College Campus'];
    }

    private function bangladeshDistricts(): array
    {
        return [
            'Bagerhat', 'Bandarban', 'Barguna', 'Barishal', 'Bhola', 'Bogura', 'Brahmanbaria', 'Chandpur',
            'Chattogram', 'Chuadanga', 'Cox’s Bazar', 'Cumilla', 'Dhaka', 'Dinajpur', 'Faridpur',
            'Feni', 'Gaibandha', 'Gazipur', 'Gopalganj', 'Habiganj', 'Jamalpur', 'Jashore', 'Jhalokathi',
            'Jhenaidah', 'Joypurhat', 'Khagrachhari', 'Khulna', 'Kishoreganj', 'Kurigram', 'Kushtia',
            'Lakshmipur', 'Lalmonirhat', 'Madaripur', 'Magura', 'Manikganj', 'Meherpur', 'Moulvibazar',
            'Munshiganj', 'Mymensingh', 'Naogaon', 'Narail', 'Narayanganj', 'Narsingdi', 'Natore',
            'Netrokona', 'Nilphamari', 'Noakhali', 'Pabna', 'Panchagarh', 'Patuakhali', 'Pirojpur',
            'Rajbari', 'Rajshahi', 'Rangamati', 'Rangpur', 'Satkhira', 'Shariatpur', 'Sherpur', 'Sirajganj',
            'Sunamganj', 'Sylhet', 'Tangail', 'Thakurgaon',
        ];
    }

    private function occupationOptions(): array
    {
        return [
            'Student', 'Teacher', 'Engineer', 'Doctor', 'Business Owner', 'Entrepreneur', 'Banker',
            'Government Service', 'Private Service', 'Lawyer', 'Accountant', 'Architect', 'Freelancer',
            'IT Professional', 'Software Developer', 'Designer', 'Consultant', 'Journalist', 'Researcher',
            'NGO Professional', 'Marketing Professional', 'Sales Professional', 'Social Worker', 'Retired', 'Other',
        ];
    }

    private function designationOptions(): array
    {
        return [
            'Intern', 'Executive', 'Senior Executive', 'Officer', 'Senior Officer', 'Assistant Manager',
            'Deputy Manager', 'Manager', 'Senior Manager', 'Assistant Director', 'Deputy Director',
            'Director', 'Head of Department', 'Coordinator', 'Consultant', 'Lecturer', 'Senior Lecturer',
            'Professor', 'Assistant Professor', 'Associate Professor', 'Engineer', 'Senior Engineer',
            'Doctor', 'Specialist', 'Founder', 'Co-Founder', 'CEO', 'COO', 'CFO', 'Owner', 'Proprietor', 'Other',
        ];
    }

    private function industryOptions(): array
    {
        return [
            'Education', 'Information Technology', 'Software', 'Healthcare', 'Banking', 'Finance',
            'Insurance', 'Government', 'Telecommunication', 'Manufacturing', 'Garments & Textile',
            'Construction', 'Real Estate', 'E-commerce', 'Retail', 'Logistics', 'Transportation',
            'Media & Communication', 'Marketing & Advertising', 'Hospitality', 'Travel & Tourism',
            'NGO & Development', 'Agriculture', 'Pharmaceutical', 'Energy', 'Legal Services', 'Other',
        ];
    }
}
