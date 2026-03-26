<?php

namespace App\Http\Controllers;

use App\Support\SiteNavigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberDashboardController extends Controller
{
    public function profile(Request $request): View
    {
        $user = $request->user()->load('profile', 'application');
        $profile = $user->profile;

        return view('member.profile', [
            'menu' => SiteNavigation::menu(),
            'user' => $user,
            'profile' => $profile,
            'application' => $user->application,
            'profileCompletion' => $this->profileCompletion($user, $profile),
        ]);
    }

    public function index(Request $request): View
    {
        $user = $request->user()->load('profile', 'application');
        $profile = $user->profile;

        return view('member.dashboard', [
            'menu' => SiteNavigation::menu(),
            'user' => $user,
            'application' => $user->application,
            'workflowSteps' => $this->applicationWorkflowSteps($user->application),
            'profileCompletion' => $this->profileCompletion($user, $profile),
            'stepLabels' => $this->completionSteps(),
            'missingSubmissionItems' => $this->missingSubmissionItems($user, $profile),
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
            'academicShifts' => ['Morning', 'Day'],
            'campusBranches' => ['Main', 'Shewrapara', 'Ibrahimpur', 'Rupnagar'],
            'passingYears' => $this->passingYearOptions(),
            'districts' => $this->bangladeshDistricts(),
            'occupations' => $this->occupationOptions(),
            'designations' => $this->designationOptions(),
            'industries' => $this->industryOptions(),
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
            $profileData = $this->extractProfileData($request, $validated, $step, $profile);

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
                    'message' => 'Thank you for completing your alumni membership profile. Your profile has been submitted successfully and is now under review.',
                    'redirect_url' => route('member.dashboard'),
                    'submitted' => true,
                ]);
            }

            return redirect()
                ->route('member.dashboard')
                ->with('success', 'Thank you for completing your alumni membership profile. Your profile has been submitted successfully and is now under review.');
        }

        if ($request->boolean('save_as_draft')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => "Draft saved for step {$step}. You can continue later.",
                    'next_step' => $step,
                    'step_url' => route('member.profile.complete', ['step' => $step]),
                    'summary' => $this->wizardSummary($request->user()->fresh()->load('profile', 'application'), $step),
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
                'step_url' => route('member.profile.complete', ['step' => $nextStep]),
                'summary' => $this->wizardSummary($request->user()->fresh()->load('profile', 'application'), $nextStep),
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
            6 => 'Please upload your profile photo, business card, and online profile links if available.',
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
                'ssc_passing_year' => ['required', 'string', 'max:50'],
                'hsc_passing_year' => ['required', 'string', 'max:50'],
                'group' => ['required', Rule::in($this->academicGroupOptions())],
                'shift' => ['required', Rule::in($this->shiftOptions())],
                'campus_branch' => ['required', Rule::in($this->campusOptions())],
            ],
            3 => [
                'date_of_birth' => ['required', 'date'],
                'gender' => ['required', Rule::in(['Male', 'Female', 'Other'])],
                'blood_group' => ['required', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
                'father_name' => ['required', 'string', 'max:255'],
                'mother_name' => ['required', 'string', 'max:255'],
                'marital_status' => ['required', Rule::in(['Single', 'Married', 'Other'])],
            ],
            4 => [
                'primary_mobile' => ['required', 'string', 'max:50'],
                'secondary_mobile' => ['required', 'string', 'max:50'],
                'whatsapp_number' => ['required', 'string', 'max:50'],
                'email_address' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
                'present_address' => ['required', 'string', 'max:1000'],
                'permanent_address' => ['required', 'string', 'max:1000'],
                'country' => ['required', 'string', 'max:100'],
                'city_district' => ['required', Rule::in($this->bangladeshDistricts())],
                'postal_code' => ['required', 'string', 'max:50'],
            ],
            5 => [
                'occupation' => ['required', Rule::in($this->occupationOptions())],
                'organization_name' => ['required', 'string', 'max:255'],
                'designation' => ['required', Rule::in($this->designationOptions())],
                'industry' => ['required', Rule::in($this->industryOptions())],
                'office_address' => ['required', 'string', 'max:1000'],
            ],
            6 => [
                'profile_photo' => [$this->requiredFileRule($request, 'profile_photo'), 'image', 'max:4096'],
                'cover_photo' => [$this->requiredFileRule($request, 'cover_photo'), 'image', 'max:6144'],
                'business_card_upload' => [$this->requiredFileRule($request, 'business_card_upload'), 'file', 'max:6144'],
                'remove_profile_photo' => ['nullable', 'boolean'],
                'remove_cover_photo' => ['nullable', 'boolean'],
                'facebook_profile_link' => ['required', 'url', 'max:255'],
                'linkedin_profile_link' => ['required', 'url', 'max:255'],
                'website_portfolio_link' => ['required', 'url', 'max:255'],
            ],
            7 => [
                'interested_in_alumni_activities' => ['required', Rule::in(['1', '0'])],
                'areas_of_interest' => ['required', 'array', 'min:1'],
                'areas_of_interest.*' => ['string', 'max:100'],
                'volunteer_interest' => ['required', Rule::in(['1', '0'])],
                'donor_sponsor_interest' => ['required', Rule::in(['Yes', 'No', 'Maybe Later'])],
                'mentor_interest' => ['required', Rule::in(['1', '0'])],
                'suggestions' => ['required', 'string', 'max:2000'],
            ],
            8 => [
                'certificate_testimonial_upload' => [$this->requiredFileRule($request, 'certificate_testimonial_upload'), 'file', 'max:6144'],
                'supporting_document_upload' => [$this->requiredFileRule($request, 'supporting_document_upload'), 'file', 'max:6144'],
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

    private function extractProfileData(Request $request, array $validated, int $step, $profile = null): array
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
                $profile?->ssc_passing_year,
                $profile?->hsc_passing_year,
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
                $profile?->organization_name,
                $profile?->designation,
                $profile?->industry,
                $profile?->office_address,
            ],
            6 => [
                $profile?->profile_photo,
                $profile?->cover_photo,
                $profile?->business_card_upload,
                $profile?->facebook_profile_link,
                $profile?->linkedin_profile_link,
                $profile?->website_portfolio_link,
            ],
            7 => [
                ! is_null($profile?->interested_in_alumni_activities) ? 'filled' : null,
                filled($profile?->areas_of_interest) ? 'filled' : null,
                ! is_null($profile?->volunteer_interest) ? 'filled' : null,
                $profile?->donor_sponsor_interest,
                ! is_null($profile?->mentor_interest) ? 'filled' : null,
                $profile?->suggestions,
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
            ['step_number' => 1, 'title' => 'Profile Created', 'description' => 'Basic registration completed and profile created.'],
            ['step_number' => 2, 'title' => 'Profile In Progress', 'description' => 'Member continues profile completion and contact verification.'],
            ['step_number' => 3, 'title' => 'Under Review', 'description' => 'Final profile submitted for admin review.'],
            ['step_number' => 4, 'title' => 'Verified Member', 'description' => 'Admin approves the alumni membership profile.'],
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
                'Passing Year SSC' => $profile?->ssc_passing_year,
                'Passing Year HSC' => $profile?->hsc_passing_year,
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
                'Organization / Company Name' => $profile?->organization_name,
                'Designation / Job Title' => $profile?->designation,
                'Industry' => $profile?->industry,
                'Office Address' => $profile?->office_address,
            ],
            6 => [
                'Profile Photo' => $profile?->profile_photo,
                'Business Card Upload' => $profile?->business_card_upload,
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

    private function requiredFileRule(Request $request, string $field): string
    {
        $profile = $request->user()?->profile;

        if ($request->hasFile($field)) {
            return 'required';
        }

        return match ($field) {
            'profile_photo' => blank($profile?->profile_photo) || $request->boolean('remove_profile_photo') ? 'required' : 'nullable',
            'cover_photo' => blank($profile?->cover_photo) || $request->boolean('remove_cover_photo') ? 'required' : 'nullable',
            'business_card_upload' => blank($profile?->business_card_upload) ? 'required' : 'nullable',
            'certificate_testimonial_upload' => blank($profile?->certificate_testimonial_upload) ? 'required' : 'nullable',
            'supporting_document_upload' => blank($profile?->supporting_document_upload) ? 'required' : 'nullable',
            default => 'required',
        };
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
        return ['Morning', 'Day'];
    }

    private function campusOptions(): array
    {
        return ['Main', 'Shewrapara', 'Ibrahimpur', 'Rupnagar'];
    }

    private function bangladeshDistricts(): array
    {
        return [
            'Bagerhat', 'Bandarban', 'Barguna', 'Barishal', 'Bhola', 'Bogura', 'Brahmanbaria', 'Chandpur',
            'Chattogram', "Chuadanga", 'Cox’s Bazar', 'Cumilla', 'Dhaka', 'Dinajpur', 'Faridpur',
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
