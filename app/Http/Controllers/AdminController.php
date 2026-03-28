<?php

namespace App\Http\Controllers;

use App\Models\MembershipApplication;
use App\Support\SiteNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(Request $request): View
    {
        return view('admin.dashboard', [
            'menu' => SiteNavigation::menu(),
            'summary' => $this->summary(),
        ]);
    }

    public function applications(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $status = (string) $request->input('status', 'all');

        $applications = MembershipApplication::query()
            ->with(['user.profile'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('user', function ($userQuery) use ($search): void {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($status !== 'all', function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->latest()
            ->get();

        $applications->each(function (MembershipApplication $application): void {
            $application->setAttribute('profile_completion', $this->profileCompletion($application));
        });

        return view('admin.applications', [
            'menu' => SiteNavigation::menu(),
            'applications' => $applications,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statusOptions' => [
                'all' => 'All statuses',
                'draft' => 'Draft',
                'unverified' => 'Unverified',
                'in_progress' => 'In Progress',
                'pending_review' => 'Pending Review',
                'under_review' => 'Under Review',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ],
            'summary' => $this->summary(),
        ]);
    }

    public function show(MembershipApplication $application): View
    {
        $application->load(['user.profile', 'reviewer']);
        $user = $application->user;
        $profile = $user->profile;
        $step = max(1, min((int) request()->integer('step', max(1, $profile?->completion_step ?? 1)), 10));

        return view('admin.show', [
            'menu' => SiteNavigation::menu(),
            'application' => $application,
            'user' => $user,
            'profile' => $profile,
            'currentStep' => $step,
            'steps' => $this->completionSteps(),
            'stepDescriptions' => $this->stepDescriptions(),
            'profileCompletion' => $this->profileCompletion($application),
            'passingYears' => $this->passingYearOptions(),
            'districts' => $this->bangladeshDistricts(),
            'occupations' => $this->occupationOptions(),
            'designations' => $this->designationOptions(),
            'industries' => $this->industryOptions(),
            'academicGroups' => $this->academicGroupOptions(),
            'academicShifts' => $this->shiftOptions(),
            'campusBranches' => $this->campusOptions(),
            'areasOfInterest' => $this->areasOfInterestOptions(),
        ]);
    }

    public function update(Request $request, MembershipApplication $application): RedirectResponse
    {
        $application->load('user.profile');
        $user = $application->user;
        $profile = $user->profile;
        $step = max(1, min((int) $request->input('wizard_step', 1), 10));
        $validated = $request->validate($this->rulesForStep($request, $application, $step));

        $profileData = $this->extractProfileData($request, $validated, $step, $profile, $user);

        $profile = $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            array_merge($profileData, [
                'completion_step' => max((int) ($profile?->completion_step ?? 1), $step),
            ]),
        );

        if ($step === 1) {
            $user->update([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['mobile_number'],
            ]);
        }

        if ($step === 4) {
            $user->update([
                'email' => $validated['email_address'],
                'phone' => $validated['primary_mobile'],
            ]);
        }

        if ($step === 10) {
            $profile->update([
                'submitted_for_review_at' => $profile->submitted_for_review_at,
                'completion_step' => 10,
            ]);
        }

        $targetStep = $request->boolean('save_and_continue')
            ? min($step + 1, 10)
            : ($request->boolean('previous_step') ? max(1, $step - 1) : $step);

        return redirect()
            ->route('admin.applications.show', ['application' => $application, 'step' => $targetStep])
            ->with('success', "Application updated for step {$step}.");
    }

    public function approve(Request $request, MembershipApplication $application): RedirectResponse
    {
        $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $application->load('user');

        $application->update([
            'current_step' => $application->total_steps,
            'status' => 'approved',
            'admin_notes' => $request->input('admin_notes'),
            'reviewed_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $application->user->update([
            'approval_step' => $application->total_steps,
            'membership_status' => 'verified',
        ]);

        return back()->with('success', 'Application approved successfully.');
    }

    public function reject(Request $request, MembershipApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'max:2000'],
        ]);

        $application->load('user');

        $application->update([
            'status' => 'rejected',
            'admin_notes' => $validated['admin_notes'],
            'reviewed_by' => $request->user()->id,
        ]);

        $application->user->update([
            'membership_status' => 'rejected',
        ]);

        return back()->with('success', 'Application rejected.');
    }

    private function summary(): array
    {
        $allApplications = MembershipApplication::query()->get();

        return [
            'pending' => $allApplications->whereIn('status', ['draft', 'unverified', 'in_progress', 'pending_review', 'pending'])->count(),
            'under_review' => $allApplications->whereIn('status', ['under_review', 'needs_correction'])->count(),
            'approved' => $allApplications->where('status', 'approved')->count(),
            'rejected' => $allApplications->where('status', 'rejected')->count(),
        ];
    }

    private function profileCompletion(MembershipApplication $application): int
    {
        $user = $application->user;
        $profile = $user?->profile;

        if (! $profile) {
            return 10;
        }

        $fields = [
            $profile?->ssc_passing_year,
            $profile?->hsc_passing_year,
            $profile?->group,
            $profile?->shift,
            $profile?->campus_branch,
            $profile?->father_name,
            $profile?->mother_name,
            $profile?->date_of_birth,
            $profile?->gender,
            $profile?->blood_group,
            $profile?->marital_status,
            $profile?->primary_mobile ?: $user?->phone,
            $profile?->secondary_mobile,
            $profile?->whatsapp_number,
            $profile?->email_address ?: $user?->email,
            $profile?->present_address,
            $profile?->permanent_address,
            $profile?->country,
            $profile?->city_district,
            $profile?->postal_code,
            $profile?->occupation,
            $profile?->organization_name,
            $profile?->designation,
            $profile?->industry,
            $profile?->office_address,
            $profile?->profile_photo,
            $profile?->cover_photo,
            $profile?->business_card_upload,
            $profile?->facebook_profile_link,
            $profile?->linkedin_profile_link,
            $profile?->website_portfolio_link,
            ! is_null($profile?->interested_in_alumni_activities) ? 'filled' : null,
            filled($profile?->areas_of_interest) ? 'filled' : null,
            ! is_null($profile?->volunteer_interest) ? 'filled' : null,
            $profile?->donor_sponsor_interest,
            ! is_null($profile?->mentor_interest) ? 'filled' : null,
            $profile?->suggestions,
            $profile?->certificate_testimonial_upload,
            $profile?->supporting_document_upload,
            $profile?->profile_visibility,
            $profile?->contact_visibility,
            $profile?->information_accuracy_confirmation ? 'filled' : null,
            $profile?->terms_privacy_agreement ? 'filled' : null,
            $profile?->admin_verification_agreement ? 'filled' : null,
        ];

        $completed = collect($fields)->filter(fn ($value) => filled($value))->count();

        return (int) max(10, round(($completed / count($fields)) * 100));
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

    private function stepDescriptions(): array
    {
        return [
            1 => 'Review and edit the member’s basic account details.',
            2 => 'Review academic records and batch details.',
            3 => 'Review personal information.',
            4 => 'Review contact and address information.',
            5 => 'Review professional details.',
            6 => 'Review media uploads and profile links.',
            7 => 'Review engagement preferences.',
            8 => 'Review verification documents.',
            9 => 'Review privacy settings.',
            10 => 'Review declarations before final admin decision.',
        ];
    }

    private function rulesForStep(Request $request, MembershipApplication $application, int $step): array
    {
        $user = $application->user;

        return match ($step) {
            1 => [
                'full_name' => ['required', 'string', 'max:255'],
                'mobile_number' => ['required', 'string', 'max:50'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'passing_year_batch' => ['required', 'string', 'max:50'],
            ],
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
                'email_address' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
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
                'remove_profile_photo' => ['nullable', 'boolean'],
                'remove_cover_photo' => ['nullable', 'boolean'],
                'remove_business_card_upload' => ['nullable', 'boolean'],
                'profile_photo' => [$this->requiredFileRule($request, $application, 'profile_photo', 'remove_profile_photo'), 'image', 'max:4096'],
                'cover_photo' => [$this->requiredFileRule($request, $application, 'cover_photo', 'remove_cover_photo'), 'image', 'max:6144'],
                'business_card_upload' => [$this->requiredFileRule($request, $application, 'business_card_upload', 'remove_business_card_upload'), 'file', 'max:6144'],
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
                'remove_certificate_testimonial_upload' => ['nullable', 'boolean'],
                'remove_supporting_document_upload' => ['nullable', 'boolean'],
                'certificate_testimonial_upload' => [$this->requiredFileRule($request, $application, 'certificate_testimonial_upload', 'remove_certificate_testimonial_upload'), 'file', 'max:6144'],
                'supporting_document_upload' => [$this->requiredFileRule($request, $application, 'supporting_document_upload', 'remove_supporting_document_upload'), 'file', 'max:6144'],
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

    private function extractProfileData(Request $request, array $validated, int $step, $profile, $user): array
    {
        return match ($step) {
            1 => [
                'mobile_number' => $validated['mobile_number'],
                'primary_mobile' => $profile?->primary_mobile ?: $validated['mobile_number'],
                'email_address' => $profile?->email_address ?: $validated['email'],
                'passing_year_batch' => $validated['passing_year_batch'],
            ],
            2 => $validated,
            3 => $validated,
            4 => [
                'primary_mobile' => $validated['primary_mobile'],
                'mobile_number' => $validated['primary_mobile'],
                'secondary_mobile' => $validated['secondary_mobile'],
                'whatsapp_number' => $validated['whatsapp_number'],
                'email_address' => $validated['email_address'],
                'present_address' => $validated['present_address'],
                'permanent_address' => $validated['permanent_address'],
                'country' => $validated['country'],
                'city_district' => $validated['city_district'],
                'postal_code' => $validated['postal_code'],
                'current_city' => $validated['city_district'],
            ],
            5 => $validated,
            6 => array_merge(
                collect($validated)->except([
                    'profile_photo',
                    'cover_photo',
                    'business_card_upload',
                    'remove_profile_photo',
                    'remove_cover_photo',
                    'remove_business_card_upload',
                ])->all(),
                $this->removeRequestedFiles($request, [
                    'remove_profile_photo' => 'profile_photo',
                    'remove_cover_photo' => 'cover_photo',
                    'remove_business_card_upload' => 'business_card_upload',
                ], $profile),
                $this->storeUploadedFiles($request, [
                    'profile_photo' => 'profile_photo',
                    'cover_photo' => 'cover_photo',
                    'business_card_upload' => 'business_card_upload',
                ], $profile)
            ),
            7 => [
                'interested_in_alumni_activities' => $request->boolean('interested_in_alumni_activities'),
                'areas_of_interest' => $validated['areas_of_interest'],
                'volunteer_interest' => $request->boolean('volunteer_interest'),
                'donor_sponsor_interest' => $validated['donor_sponsor_interest'],
                'mentor_interest' => $request->boolean('mentor_interest'),
                'suggestions' => $validated['suggestions'],
            ],
            8 => array_merge(
                $this->removeRequestedFiles($request, [
                    'remove_certificate_testimonial_upload' => 'certificate_testimonial_upload',
                    'remove_supporting_document_upload' => 'supporting_document_upload',
                ], $profile),
                $this->storeUploadedFiles($request, [
                    'certificate_testimonial_upload' => 'certificate_testimonial_upload',
                    'supporting_document_upload' => 'supporting_document_upload',
                ], $profile)
            ),
            9 => $validated,
            10 => [
                'information_accuracy_confirmation' => true,
                'terms_privacy_agreement' => true,
                'admin_verification_agreement' => true,
            ],
        };
    }

    private function requiredFileRule(Request $request, MembershipApplication $application, string $field, ?string $removeField = null): string
    {
        if ($request->hasFile($field)) {
            return 'required';
        }

        if ($removeField && $request->boolean($removeField)) {
            return 'required';
        }

        return filled($application->user?->profile?->{$field}) ? 'nullable' : 'required';
    }

    private function removeRequestedFiles(Request $request, array $map, $profile): array
    {
        $removed = [];

        foreach ($map as $flag => $column) {
            if ($request->boolean($flag) && $profile?->{$column}) {
                Storage::disk('public')->delete($profile->{$column});
                $removed[$column] = null;
            }
        }

        return $removed;
    }

    private function storeUploadedFiles(Request $request, array $map, $profile): array
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

    private function passingYearOptions(): array
    {
        return range((int) now()->year, 1970);
    }

    private function bangladeshDistricts(): array
    {
        return ['Bagerhat', 'Bandarban', 'Barguna', 'Barishal', 'Bhola', 'Bogura', 'Brahmanbaria', 'Chandpur', 'Chattogram', 'Chuadanga', 'Cox\'s Bazar', 'Cumilla', 'Dhaka', 'Dinajpur', 'Faridpur', 'Feni', 'Gaibandha', 'Gazipur', 'Gopalganj', 'Habiganj', 'Jamalpur', 'Jashore', 'Jhalokathi', 'Jhenaidah', 'Joypurhat', 'Khagrachhari', 'Khulna', 'Kishoreganj', 'Kurigram', 'Kushtia', 'Lakshmipur', 'Lalmonirhat', 'Madaripur', 'Magura', 'Manikganj', 'Meherpur', 'Moulvibazar', 'Munshiganj', 'Mymensingh', 'Naogaon', 'Narail', 'Narayanganj', 'Narsingdi', 'Natore', 'Netrokona', 'Nilphamari', 'Noakhali', 'Pabna', 'Panchagarh', 'Patuakhali', 'Pirojpur', 'Rajbari', 'Rajshahi', 'Rangamati', 'Rangpur', 'Satkhira', 'Shariatpur', 'Sherpur', 'Sirajganj', 'Sunamganj', 'Sylhet', 'Tangail', 'Thakurgaon'];
    }

    private function occupationOptions(): array
    {
        return ['Student', 'Teacher', 'Engineer', 'Doctor', 'Government Service', 'Private Service', 'Business', 'Lawyer', 'Banker', 'Entrepreneur', 'Freelancer', 'NGO / Development', 'IT / Software', 'Military', 'Retired', 'Other'];
    }

    private function designationOptions(): array
    {
        return ['Chairman', 'Managing Director', 'Director', 'Chief Executive Officer', 'Chief Operating Officer', 'General Manager', 'Senior Manager', 'Manager', 'Assistant Manager', 'Executive', 'Senior Executive', 'Officer', 'Coordinator', 'Supervisor', 'Engineer', 'Senior Engineer', 'Consultant', 'Lecturer', 'Teacher', 'Doctor', 'Founder', 'Owner', 'Partner', 'Freelancer', 'Other'];
    }

    private function industryOptions(): array
    {
        return ['Education', 'Information Technology', 'Healthcare', 'Banking & Finance', 'Government', 'Business & Commerce', 'Manufacturing', 'Telecommunication', 'Construction', 'Real Estate', 'NGO / Development', 'Media', 'Law', 'Transportation', 'Textile & Garments', 'Agriculture', 'Other'];
    }

    private function academicGroupOptions(): array
    {
        return ['Science', 'Commerce', 'Arts'];
    }

    private function shiftOptions(): array
    {
        return ['Morning', 'Day'];
    }

    private function campusOptions(): array
    {
        return ['Main', 'Shewrapara', 'Ibrahimpur', 'Rupnagar'];
    }

    private function areasOfInterestOptions(): array
    {
        return [
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
        ];
    }
}
