@extends('layouts.app')

@section('content')
    @php
        $resumeWizardStep = max(2, min(10, $application?->current_step ?? $profile?->completion_step ?? 2));
        $completedWizardStep = max(1, min(10, $profile?->completion_step ?? 1));
        $yesNo = fn ($value, string $fallback = null): string => is_null($value) ? ($fallback ?? __('Not set')) : ($value ? __('Yes') : __('No'));
        $fileUrl = fn (?string $path): ?string => $path ? asset('storage/'.$path) : null;
        $areasOfInterest = collect($profile?->areas_of_interest ?? [])->filter()->values();
        $profilePhotoUrl = $fileUrl($profile?->profile_photo);
        $coverPhotoUrl = $fileUrl($profile?->cover_photo);
        $profileIntro = $profile?->short_bio
            ?: collect([
                $profile?->occupation,
                $profile?->organization_name,
                $profile?->city_district ?: $profile?->current_city,
            ])->filter()->join(' • ');
        $profileIntro = $profileIntro ?: __('MUBCAA alumni member profile.');
        $profileHighlights = array_filter([
            $profile?->passing_year_batch ? __('Batch :batch', ['batch' => $profile->passing_year_batch]) : null,
            $profile?->occupation,
            $profile?->organization_name,
            $profile?->city_district ?: $profile?->current_city,
        ]);
        $communityFlags = [
            ['label' => __('Alumni Activities'), 'value' => $yesNo($profile?->interested_in_alumni_activities)],
            ['label' => __('Volunteer Interest'), 'value' => $yesNo($profile?->volunteer_interest)],
            ['label' => __('Mentor Interest'), 'value' => $yesNo($profile?->mentor_interest)],
            ['label' => __('Donor / Sponsor'), 'value' => $profile?->donor_sponsor_interest ?: __('Not added')],
        ];
        $declarationItems = [
            ['label' => __('Profile Visibility'), 'value' => $profile?->profile_visibility ? __($profile->profile_visibility) : __('Not added')],
            ['label' => __('Contact Visibility'), 'value' => $profile?->contact_visibility ? __($profile->contact_visibility) : __('Not added')],
            ['label' => __('Information Accuracy'), 'value' => $yesNo($profile?->information_accuracy_confirmation, __('Not confirmed'))],
            ['label' => __('Terms & Privacy'), 'value' => $yesNo($profile?->terms_privacy_agreement, __('Not confirmed'))],
            ['label' => __('Admin Verification'), 'value' => $yesNo($profile?->admin_verification_agreement, __('Not confirmed'))],
        ];
        $profileResources = [
            ['label' => __('Facebook Profile'), 'url' => $profile?->facebook_profile_link, 'action' => __('Open Link')],
            ['label' => __('LinkedIn Profile'), 'url' => $profile?->linkedin_profile_link, 'action' => __('Open Link')],
            ['label' => __('Website / Portfolio'), 'url' => $profile?->website_portfolio_link, 'action' => __('Open Link')],
            ['label' => __('Business Card Upload'), 'url' => $fileUrl($profile?->business_card_upload), 'action' => __('Open File')],
            ['label' => __('Certificate / Testimonial'), 'url' => $fileUrl($profile?->certificate_testimonial_upload), 'action' => __('Open File')],
            ['label' => __('Supporting Document'), 'url' => $fileUrl($profile?->supporting_document_upload), 'action' => __('Open File')],
        ];
    @endphp

    <section class="section profile-social-section">
        <div class="wrap profile-social-stack">
            <article class="profile-summary-card profile-social-hero">
                <span class="sr-only">Member Profile</span>
                <div class="profile-social-cover">
                    @if ($coverPhotoUrl)
                        <img src="{{ $coverPhotoUrl }}" alt="{{ $user->name }} cover photo">
                    @endif
                </div>

                <div class="profile-social-head">
                    <div class="profile-social-avatar">
                        @if ($profilePhotoUrl)
                            <img src="{{ $profilePhotoUrl }}" alt="{{ $user->name }}">
                        @else
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        @endif
                    </div>

                    <div class="profile-social-identity">
                        <h1>{{ $user->name }}</h1>
                        <p class="dashboard-copy">
                            {{ $completedWizardStep > 1 ? __('Completed through Step :step.', ['step' => $completedWizardStep]).' ' : __('Basic info completed.').' ' }}
                            {{ __('Resume from Step :step to continue editing.', ['step' => $resumeWizardStep]) }}
                        </p>
                    </div>

                    <div class="profile-social-actions">
                        @if (! $profileLocked)
                            <a class="button button-primary" href="{{ route('member.profile.complete', ['step' => $resumeWizardStep]) }}">{{ __('Resume Editing') }}</a>
                        @endif
                    </div>
                </div>
            </article>
            <div class="profile-social-layout">
                <aside class="profile-social-sidebar">
                    <article class="profile-summary-card profile-social-card">
                        <h3>{{ __('Overview') }}</h3>
                        <p class="profile-note-card">{{ $profileIntro }}</p>
                        <div class="profile-social-list">
                            <div><strong>{{ __('Passing Year / Batch') }}</strong><span>{{ $profile?->passing_year_batch ?: __('Not added') }}</span></div>
                            <div><strong>{{ __('How Did You Find Us?') }}</strong><span>{{ $profile?->how_did_you_find_us ? __($profile->how_did_you_find_us) : __('Not added') }}</span></div>
                            <div><strong>{{ __('Lives In') }}</strong><span>{{ $profile?->city_district ?: ($profile?->current_city ?: __('Not added')) }}</span></div>
                            <div><strong>{{ __('Works As') }}</strong><span>{{ $profile?->occupation ?: __('Not added') }}</span></div>
                            <div><strong>{{ __('Organization') }}</strong><span>{{ $profile?->organization_name ?: __('Not added') }}</span></div>
                        </div>
                    </article>

                    <article class="profile-summary-card profile-social-card">
                        <h3>{{ __('Contact') }}</h3>
                        <div class="profile-social-list">
                            <div><strong>{{ __('Email') }}</strong><span>{{ $profile?->email_address ?: $user->email }}</span></div>
                            <div><strong>{{ __('Primary Mobile') }}</strong><span>{{ $profile?->primary_mobile ?: ($user->phone ?: __('Not added')) }}</span></div>
                            <div><strong>{{ __('Secondary Mobile') }}</strong><span>{{ $profile?->secondary_mobile ?: __('Not added') }}</span></div>
                            <div><strong>{{ __('WhatsApp') }}</strong><span>{{ $profile?->whatsapp_number ?: __('Not added') }}</span></div>
                            <div><strong>{{ __('Country') }}</strong><span>{{ $profile?->country ?: __('Bangladesh') }}</span></div>
                            <div><strong>{{ __('City / District') }}</strong><span>{{ $profile?->city_district ?: ($profile?->current_city ?: __('Not added')) }}</span></div>
                            <div><strong>{{ __('Postal Code') }}</strong><span>{{ $profile?->postal_code ?: __('Not added') }}</span></div>
                        </div>
                        <div class="profile-note-group">
                            <div class="profile-note-card">
                                <strong>{{ __('Present Address') }}</strong>
                                <p>{{ $profile?->present_address ?: __('Not added') }}</p>
                            </div>
                            <div class="profile-note-card">
                                <strong>{{ __('Permanent Address') }}</strong>
                                <p>{{ $profile?->permanent_address ?: __('Not added') }}</p>
                            </div>
                        </div>
                    </article>

                    <article class="profile-summary-card profile-social-card">
                        <h3>{{ __('Community') }}</h3>
                        <div class="profile-status-grid">
                            @foreach ($communityFlags as $item)
                                <div class="profile-status-card">
                                    <span>{{ $item['label'] }}</span>
                                    <strong>{{ $item['value'] }}</strong>
                                </div>
                            @endforeach
                        </div>
                        <div class="profile-tag-block">
                            <span class="profile-tag-heading">{{ __('Areas of Interest') }}</span>
                            @if ($areasOfInterest->isNotEmpty())
                                <div class="profile-tag-list">
                                    @foreach ($areasOfInterest as $interest)
                                        <span class="profile-tag">{{ __($interest) }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="dashboard-copy">{{ __('No areas of interest selected yet.') }}</p>
                            @endif
                        </div>
                        <div class="profile-tag-block">
                            <span class="profile-tag-heading">{{ __('Association Note') }}</span>
                            <p class="profile-note-card">{{ $profile?->suggestions ?: __('No message added for the association yet.') }}</p>
                        </div>
                    </article>
                </aside>

                <div class="profile-social-main">
                    <article class="profile-summary-card profile-social-card">
                        <h3>{{ __('About') }}</h3>
                        <dl class="profile-field-grid">
                            <div class="profile-field"><dt>{{ __('Full Name') }}</dt><dd>{{ $user->name }}</dd></div>
                            <div class="profile-field"><dt>{{ __('Father’s Name') }}</dt><dd>{{ $profile?->father_name ?: __('Not added') }}</dd></div>
                            <div class="profile-field"><dt>{{ __('Mother’s Name') }}</dt><dd>{{ $profile?->mother_name ?: __('Not added') }}</dd></div>
                            <div class="profile-field"><dt>{{ __('Date of Birth') }}</dt><dd>{{ optional($profile?->date_of_birth)->format('d M Y') ?: __('Not added') }}</dd></div>
                            <div class="profile-field"><dt>{{ __('Gender') }}</dt><dd>{{ $profile?->gender ? __($profile->gender) : __('Not added') }}</dd></div>
                            <div class="profile-field"><dt>{{ __('Blood Group') }}</dt><dd>{{ $profile?->blood_group ?: __('Not added') }}</dd></div>
                            <div class="profile-field"><dt>{{ __('Marital Status') }}</dt><dd>{{ $profile?->marital_status ? __($profile->marital_status) : __('Not added') }}</dd></div>
                        </dl>
                    </article>

                    <article class="profile-summary-card profile-social-card">
                        <h3>{{ __('Academic Journey') }}</h3>
                        <dl class="profile-field-grid">
                            <div class="profile-field"><dt>{{ __('SSC Passing Year') }}</dt><dd>{{ $profile?->ssc_passing_year ?: __('Not added') }}</dd></div>
                            <div class="profile-field"><dt>{{ __('HSC Passing Year') }}</dt><dd>{{ $profile?->hsc_passing_year ?: __('Not added') }}</dd></div>
                            <div class="profile-field"><dt>{{ __('Group') }}</dt><dd>{{ $profile?->group ? __($profile->group) : __('Not added') }}</dd></div>
                            <div class="profile-field"><dt>{{ __('Shift') }}</dt><dd>{{ $profile?->shift ? __($profile->shift) : __('Not added') }}</dd></div>
                            <div class="profile-field"><dt>{{ __('Campus / Branch') }}</dt><dd>{{ $profile?->campus_branch ? __($profile->campus_branch) : __('Not added') }}</dd></div>
                        </dl>
                    </article>

                    <article class="profile-summary-card profile-social-card">
                        <h3>{{ __('Professional Journey') }}</h3>
                        <dl class="profile-field-grid">
                            <div class="profile-field"><dt>{{ __('Occupation') }}</dt><dd>{{ $profile?->occupation ? __($profile->occupation) : __('Not added') }}</dd></div>
                            <div class="profile-field"><dt>{{ __('Organization') }}</dt><dd>{{ $profile?->organization_name ?: __('Not added') }}</dd></div>
                            <div class="profile-field"><dt>{{ __('Designation') }}</dt><dd>{{ $profile?->designation ? __($profile->designation) : __('Not added') }}</dd></div>
                            <div class="profile-field"><dt>{{ __('Industry') }}</dt><dd>{{ $profile?->industry ? __($profile->industry) : __('Not added') }}</dd></div>
                            <div class="profile-field profile-field-wide"><dt>{{ __('Office Address') }}</dt><dd>{{ $profile?->office_address ?: __('Not added') }}</dd></div>
                        </dl>
                    </article>

                    <article class="profile-summary-card profile-social-card">
                        <h3>{{ __('Privacy & Declarations') }}</h3>
                        <div class="profile-declaration-grid">
                            @foreach ($declarationItems as $item)
                                <div class="profile-declaration-chip">
                                    <span>{{ $item['label'] }}</span>
                                    <strong>{{ $item['value'] }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </article>

                    <article class="profile-summary-card profile-social-card">
                        <h3>{{ __('Links & Files') }}</h3>
                        <div class="profile-resource-grid">
                            @foreach ($profileResources as $item)
                                <div class="profile-resource-row">
                                    <div class="profile-resource-copy">
                                        <strong>{{ $item['label'] }}</strong>
                                        <span>{{ str_contains($item['action'], __('Link')) ? __('Link') : __('File') }}</span>
                                    </div>
                                    @if ($item['url'])
                                        <a class="mini-link" href="{{ $item['url'] }}" target="_blank">{{ $item['action'] }}</a>
                                    @else
                                        <span class="dashboard-document-badge">{{ __('Missing') }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>
@endsection
