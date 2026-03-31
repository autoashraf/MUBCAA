@extends('layouts.app')

@section('content')
    @php
        $resumeWizardStep = max(2, min(10, $application?->current_step ?? $profile?->completion_step ?? 2));
        $completedWizardStep = max(1, min(10, $profile?->completion_step ?? 1));
        $yesNo = fn ($value, string $fallback = 'Not set'): string => is_null($value) ? $fallback : ($value ? 'Yes' : 'No');
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
        $profileIntro = $profileIntro ?: 'MUBCAA alumni member profile.';
        $profileHighlights = array_filter([
            $profile?->passing_year_batch ? 'Batch '.$profile->passing_year_batch : null,
            $profile?->occupation,
            $profile?->organization_name,
            $profile?->city_district ?: $profile?->current_city,
        ]);
        $communityFlags = [
            ['label' => 'Alumni Activities', 'value' => $yesNo($profile?->interested_in_alumni_activities)],
            ['label' => 'Volunteer Interest', 'value' => $yesNo($profile?->volunteer_interest)],
            ['label' => 'Mentor Interest', 'value' => $yesNo($profile?->mentor_interest)],
            ['label' => 'Donor / Sponsor', 'value' => $profile?->donor_sponsor_interest ?: 'Not added'],
        ];
        $declarationItems = [
            ['label' => 'Profile Visibility', 'value' => $profile?->profile_visibility ?: 'Not added'],
            ['label' => 'Contact Visibility', 'value' => $profile?->contact_visibility ?: 'Not added'],
            ['label' => 'Information Accuracy', 'value' => $yesNo($profile?->information_accuracy_confirmation, 'Not confirmed')],
            ['label' => 'Terms & Privacy', 'value' => $yesNo($profile?->terms_privacy_agreement, 'Not confirmed')],
            ['label' => 'Admin Verification', 'value' => $yesNo($profile?->admin_verification_agreement, 'Not confirmed')],
        ];
        $profileResources = [
            ['label' => 'Facebook Profile', 'url' => $profile?->facebook_profile_link, 'action' => 'Open Link'],
            ['label' => 'LinkedIn Profile', 'url' => $profile?->linkedin_profile_link, 'action' => 'Open Link'],
            ['label' => 'Website / Portfolio', 'url' => $profile?->website_portfolio_link, 'action' => 'Open Link'],
            ['label' => 'Business Card Upload', 'url' => $fileUrl($profile?->business_card_upload), 'action' => 'Open File'],
            ['label' => 'Certificate / Testimonial', 'url' => $fileUrl($profile?->certificate_testimonial_upload), 'action' => 'Open File'],
            ['label' => 'Supporting Document', 'url' => $fileUrl($profile?->supporting_document_upload), 'action' => 'Open File'],
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
                            {{ $completedWizardStep > 1 ? 'Completed through Step '.$completedWizardStep.'. ' : 'Basic info completed. ' }}
                            Resume from Step {{ $resumeWizardStep }} to continue editing.
                        </p>
                    </div>

                    <div class="profile-social-actions">
                        @if (! $profileLocked)
                            <a class="button button-primary" href="{{ route('member.profile.complete', ['step' => $resumeWizardStep]) }}">Resume Editing</a>
                        @endif
                    </div>
                </div>
            </article>
            <div class="profile-social-layout">
                <aside class="profile-social-sidebar">
                    <article class="profile-summary-card profile-social-card">
                        <h3>Overview</h3>
                        <p class="profile-note-card">{{ $profileIntro }}</p>
                        <div class="profile-social-list">
                            <div><strong>Passing Year / Batch</strong><span>{{ $profile?->passing_year_batch ?: 'Not added' }}</span></div>
                            <div><strong>How Did You Find Us?</strong><span>{{ $profile?->how_did_you_find_us ?: 'Not added' }}</span></div>
                            <div><strong>Lives In</strong><span>{{ $profile?->city_district ?: ($profile?->current_city ?: 'Not added') }}</span></div>
                            <div><strong>Works As</strong><span>{{ $profile?->occupation ?: 'Not added' }}</span></div>
                            <div><strong>Organization</strong><span>{{ $profile?->organization_name ?: 'Not added' }}</span></div>
                        </div>
                    </article>

                    <article class="profile-summary-card profile-social-card">
                        <h3>Contact</h3>
                        <div class="profile-social-list">
                            <div><strong>Email</strong><span>{{ $profile?->email_address ?: $user->email }}</span></div>
                            <div><strong>Primary Mobile</strong><span>{{ $profile?->primary_mobile ?: ($user->phone ?: 'Not added') }}</span></div>
                            <div><strong>Secondary Mobile</strong><span>{{ $profile?->secondary_mobile ?: 'Not added' }}</span></div>
                            <div><strong>WhatsApp</strong><span>{{ $profile?->whatsapp_number ?: 'Not added' }}</span></div>
                            <div><strong>Country</strong><span>{{ $profile?->country ?: 'Bangladesh' }}</span></div>
                            <div><strong>City / District</strong><span>{{ $profile?->city_district ?: ($profile?->current_city ?: 'Not added') }}</span></div>
                            <div><strong>Postal Code</strong><span>{{ $profile?->postal_code ?: 'Not added' }}</span></div>
                        </div>
                        <div class="profile-note-group">
                            <div class="profile-note-card">
                                <strong>Present Address</strong>
                                <p>{{ $profile?->present_address ?: 'Not added' }}</p>
                            </div>
                            <div class="profile-note-card">
                                <strong>Permanent Address</strong>
                                <p>{{ $profile?->permanent_address ?: 'Not added' }}</p>
                            </div>
                        </div>
                    </article>

                    <article class="profile-summary-card profile-social-card">
                        <h3>Community</h3>
                        <div class="profile-status-grid">
                            @foreach ($communityFlags as $item)
                                <div class="profile-status-card">
                                    <span>{{ $item['label'] }}</span>
                                    <strong>{{ $item['value'] }}</strong>
                                </div>
                            @endforeach
                        </div>
                        <div class="profile-tag-block">
                            <span class="profile-tag-heading">Areas of Interest</span>
                            @if ($areasOfInterest->isNotEmpty())
                                <div class="profile-tag-list">
                                    @foreach ($areasOfInterest as $interest)
                                        <span class="profile-tag">{{ $interest }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="dashboard-copy">No areas of interest selected yet.</p>
                            @endif
                        </div>
                        <div class="profile-tag-block">
                            <span class="profile-tag-heading">Association Note</span>
                            <p class="profile-note-card">{{ $profile?->suggestions ?: 'No message added for the association yet.' }}</p>
                        </div>
                    </article>
                </aside>

                <div class="profile-social-main">
                    <article class="profile-summary-card profile-social-card">
                        <h3>About</h3>
                        <dl class="profile-field-grid">
                            <div class="profile-field"><dt>Full Name</dt><dd>{{ $user->name }}</dd></div>
                            <div class="profile-field"><dt>Father’s Name</dt><dd>{{ $profile?->father_name ?: 'Not added' }}</dd></div>
                            <div class="profile-field"><dt>Mother’s Name</dt><dd>{{ $profile?->mother_name ?: 'Not added' }}</dd></div>
                            <div class="profile-field"><dt>Date of Birth</dt><dd>{{ optional($profile?->date_of_birth)->format('d M Y') ?: 'Not added' }}</dd></div>
                            <div class="profile-field"><dt>Gender</dt><dd>{{ $profile?->gender ?: 'Not added' }}</dd></div>
                            <div class="profile-field"><dt>Blood Group</dt><dd>{{ $profile?->blood_group ?: 'Not added' }}</dd></div>
                            <div class="profile-field"><dt>Marital Status</dt><dd>{{ $profile?->marital_status ?: 'Not added' }}</dd></div>
                        </dl>
                    </article>

                    <article class="profile-summary-card profile-social-card">
                        <h3>Academic Journey</h3>
                        <dl class="profile-field-grid">
                            <div class="profile-field"><dt>SSC Passing Year</dt><dd>{{ $profile?->ssc_passing_year ?: 'Not added' }}</dd></div>
                            <div class="profile-field"><dt>HSC Passing Year</dt><dd>{{ $profile?->hsc_passing_year ?: 'Not added' }}</dd></div>
                            <div class="profile-field"><dt>Group</dt><dd>{{ $profile?->group ?: 'Not added' }}</dd></div>
                            <div class="profile-field"><dt>Shift</dt><dd>{{ $profile?->shift ?: 'Not added' }}</dd></div>
                            <div class="profile-field"><dt>Campus / Branch</dt><dd>{{ $profile?->campus_branch ?: 'Not added' }}</dd></div>
                        </dl>
                    </article>

                    <article class="profile-summary-card profile-social-card">
                        <h3>Professional Journey</h3>
                        <dl class="profile-field-grid">
                            <div class="profile-field"><dt>Occupation</dt><dd>{{ $profile?->occupation ?: 'Not added' }}</dd></div>
                            <div class="profile-field"><dt>Organization</dt><dd>{{ $profile?->organization_name ?: 'Not added' }}</dd></div>
                            <div class="profile-field"><dt>Designation</dt><dd>{{ $profile?->designation ?: 'Not added' }}</dd></div>
                            <div class="profile-field"><dt>Industry</dt><dd>{{ $profile?->industry ?: 'Not added' }}</dd></div>
                            <div class="profile-field profile-field-wide"><dt>Office Address</dt><dd>{{ $profile?->office_address ?: 'Not added' }}</dd></div>
                        </dl>
                    </article>

                    <article class="profile-summary-card profile-social-card">
                        <h3>Privacy & Declarations</h3>
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
                        <h3>Links & Files</h3>
                        <div class="profile-resource-grid">
                            @foreach ($profileResources as $item)
                                <div class="profile-resource-row">
                                    <div class="profile-resource-copy">
                                        <strong>{{ $item['label'] }}</strong>
                                        <span>{{ str_contains($item['action'], 'Link') ? 'Link' : 'File' }}</span>
                                    </div>
                                    @if ($item['url'])
                                        <a class="mini-link" href="{{ $item['url'] }}" target="_blank">{{ $item['action'] }}</a>
                                    @else
                                        <span class="dashboard-document-badge">Missing</span>
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
