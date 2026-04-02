@extends('layouts.admin')

@section('content')
    @php
        $activeStep = old('wizard_step', $currentStep);
        $mediaUrl = fn (?string $path): ?string => ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) ? asset('storage/'.$path) : null;
        $mediaExtension = fn (?string $path): ?string => $path ? strtoupper(pathinfo($path, PATHINFO_EXTENSION)) : null;
        $mediaPreviewType = fn (?string $path): string => match (strtolower(pathinfo((string) $path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'image',
            'pdf' => 'pdf',
            default => 'file',
        };
    @endphp

    <div class="admin-detail-stack">
        <div class="admin-detail-head">
            <div>
                <p class="eyebrow">Application Review</p>
                <h1>{{ $user->name }}</h1>
                <p class="lead">Review and edit the submitted profile step by step.</p>
            </div>
            <div class="admin-detail-actions">
                <a class="button button-secondary" href="{{ route('admin.applications.index') }}">Back to Applications</a>
            </div>
        </div>

        <section class="admin-review-hero profile-summary-card">
            <div class="admin-review-hero-copy">
                <span class="status-pill status-{{ $application->status }}">{{ str($application->status)->replace('_', ' ')->title() }}</span>
                <span class="dashboard-chip">Profile {{ $profileCompletion }}% complete</span>
                <span class="dashboard-chip">Step {{ $activeStep }} of 10</span>
            </div>
            <div class="admin-review-meta">
                <span>{{ $user->memberNumber() }}</span>
                <span>{{ $user->email }}</span>
                <span>{{ $user->phone ?: 'No phone' }}</span>
            </div>
        </section>

        <nav class="admin-step-nav">
            @foreach ($steps as $number => $label)
                <a class="admin-step-link @if ((int) $activeStep === $number) is-active @endif" href="{{ route('admin.applications.show', ['application' => $application, 'step' => $number]) }}">
                    <span>Step {{ $number }}</span>
                    <strong>{{ $label }}</strong>
                </a>
            @endforeach
        </nav>

        <form class="form-card admin-edit-card" method="POST" action="{{ route('admin.applications.update', $application) }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="wizard_step" value="{{ $activeStep }}">

            <div class="dashboard-form-head">
                <div>
                    <p class="panel-card-label">Step {{ $activeStep }}: {{ $steps[$activeStep] }}</p>
                    <h3>{{ $steps[$activeStep] }}</h3>
                    <p class="dashboard-copy">{{ $stepDescriptions[$activeStep] }}</p>
                </div>
            </div>

            @if ((int) $activeStep === 1)
                <div class="form-grid">
                    <label>
                        <span>Full Name</span>
                        <input type="text" name="full_name" value="{{ old('full_name', $user->name) }}">
                        @error('full_name') <small>{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>Mobile Number</span>
                        <input type="text" name="mobile_number" value="{{ old('mobile_number', $user->phone ?: $profile?->mobile_number) }}">
                        @error('mobile_number') <small>{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>Email Address</span>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}">
                        @error('email') <small>{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>How did you find us?</span>
                        <select name="how_did_you_find_us">
                            <option value="">Select one</option>
                            @foreach ($discoverySources as $option)
                                <option value="{{ $option }}" @selected(old('how_did_you_find_us', $profile?->how_did_you_find_us) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('how_did_you_find_us') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
            @elseif ((int) $activeStep === 2)
                <div class="form-grid">
                    <label><span>Passing Year SSC</span><select name="ssc_passing_year"><option value="">Select year</option>@foreach ($passingYears as $option)<option value="{{ $option }}" @selected(old('ssc_passing_year', $profile?->ssc_passing_year) == $option)>{{ $option }}</option>@endforeach</select>@error('ssc_passing_year') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Passing Year HSC</span><select name="hsc_passing_year"><option value="">Select year</option>@foreach ($passingYears as $option)<option value="{{ $option }}" @selected(old('hsc_passing_year', $profile?->hsc_passing_year) == $option)>{{ $option }}</option>@endforeach</select>@error('hsc_passing_year') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Group</span><select name="group"><option value="">Select group</option>@foreach ($academicGroups as $option)<option value="{{ $option }}" @selected(old('group', $profile?->group) === $option)>{{ $option }}</option>@endforeach</select>@error('group') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Shift</span><select name="shift"><option value="">Select shift</option>@foreach ($academicShifts as $option)<option value="{{ $option }}" @selected(old('shift', $profile?->shift) === $option)>{{ $option }}</option>@endforeach</select>@error('shift') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Campus / Branch</span><select name="campus_branch"><option value="">Select campus</option>@foreach ($campusBranches as $option)<option value="{{ $option }}" @selected(old('campus_branch', $profile?->campus_branch) === $option)>{{ $option }}</option>@endforeach</select>@error('campus_branch') <small>{{ $message }}</small> @enderror</label>
                </div>
            @elseif ((int) $activeStep === 3)
                <div class="form-grid">
                    <label><span>Date of Birth</span><input type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($profile?->date_of_birth)->format('Y-m-d')) }}">@error('date_of_birth') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Gender</span><select name="gender"><option value="">Select gender</option>@foreach (['Male', 'Female', 'Other'] as $option)<option value="{{ $option }}" @selected(old('gender', $profile?->gender) === $option)>{{ $option }}</option>@endforeach</select>@error('gender') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Blood Group</span><select name="blood_group"><option value="">Select blood group</option>@foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $option)<option value="{{ $option }}" @selected(old('blood_group', $profile?->blood_group) === $option)>{{ $option }}</option>@endforeach</select>@error('blood_group') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Father’s Name</span><input type="text" name="father_name" value="{{ old('father_name', $profile?->father_name) }}">@error('father_name') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Mother’s Name</span><input type="text" name="mother_name" value="{{ old('mother_name', $profile?->mother_name) }}">@error('mother_name') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Marital Status</span><select name="marital_status"><option value="">Select marital status</option>@foreach (['Single', 'Married', 'Other'] as $option)<option value="{{ $option }}" @selected(old('marital_status', $profile?->marital_status) === $option)>{{ $option }}</option>@endforeach</select>@error('marital_status') <small>{{ $message }}</small> @enderror</label>
                </div>
            @elseif ((int) $activeStep === 4)
                <div class="form-grid">
                    <label><span>Primary Mobile Number</span><input type="text" name="primary_mobile" value="{{ old('primary_mobile', $profile?->primary_mobile ?? $user->phone) }}" readonly>@error('primary_mobile') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Secondary Mobile Number</span><input type="text" name="secondary_mobile" value="{{ old('secondary_mobile', $profile?->secondary_mobile) }}">@error('secondary_mobile') <small>{{ $message }}</small> @enderror</label>
                    <label><span>WhatsApp Number</span><input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $profile?->whatsapp_number) }}"><div class="inline-checkbox-option" data-whatsapp-sync-wrapper><div class="inline-checkbox-control"><input type="checkbox" value="1" data-whatsapp-same-toggle data-primary-source="primary_mobile" data-whatsapp-target="whatsapp_number" @checked(old('whatsapp_number', $profile?->whatsapp_number) && old('whatsapp_number', $profile?->whatsapp_number) === old('primary_mobile', $profile?->primary_mobile ?? $user->phone))><span class="inline-checkbox-box" aria-hidden="true"></span><span class="inline-checkbox-copy"><strong>Use primary mobile number</strong><small data-whatsapp-sync-note>Synced from primary mobile</small></span></div></div>@error('whatsapp_number') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Email Address</span><input type="email" name="email_address" value="{{ old('email_address', $profile?->email_address ?? $user->email) }}" readonly>@error('email_address') <small>{{ $message }}</small> @enderror</label>
                    <label class="label-wide"><span>Present Address</span><textarea name="present_address" rows="4">{{ old('present_address', $profile?->present_address) }}</textarea>@error('present_address') <small>{{ $message }}</small> @enderror</label>
                    <label class="label-wide"><span>Permanent Address</span><textarea name="permanent_address" rows="4">{{ old('permanent_address', $profile?->permanent_address) }}</textarea>@error('permanent_address') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Country</span><select name="country"><option value="Bangladesh" selected>Bangladesh</option></select>@error('country') <small>{{ $message }}</small> @enderror</label>
                    <label><span>City / District</span><select name="city_district"><option value="">Select district</option>@foreach ($districts as $option)<option value="{{ $option }}" @selected(old('city_district', $profile?->city_district ?? $profile?->current_city) === $option)>{{ $option }}</option>@endforeach</select>@error('city_district') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Postal Code</span><input type="text" name="postal_code" value="{{ old('postal_code', $profile?->postal_code) }}">@error('postal_code') <small>{{ $message }}</small> @enderror</label>
                </div>
            @elseif ((int) $activeStep === 5)
                <div class="form-grid">
                    <label><span>Occupation</span><select name="occupation"><option value="">Select occupation</option>@foreach ($occupations as $option)<option value="{{ $option }}" @selected(old('occupation', $profile?->occupation) === $option)>{{ $option }}</option>@endforeach</select>@error('occupation') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Organization / Company Name</span><input type="text" name="organization_name" value="{{ old('organization_name', $profile?->organization_name) }}">@error('organization_name') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Designation / Job Title</span><select name="designation"><option value="">Select designation</option>@foreach ($designations as $option)<option value="{{ $option }}" @selected(old('designation', $profile?->designation) === $option)>{{ $option }}</option>@endforeach</select>@error('designation') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Industry</span><select name="industry"><option value="">Select industry</option>@foreach ($industries as $option)<option value="{{ $option }}" @selected(old('industry', $profile?->industry) === $option)>{{ $option }}</option>@endforeach</select>@error('industry') <small>{{ $message }}</small> @enderror</label>
                    <label class="label-wide"><span>Office Address</span><textarea name="office_address" rows="4">{{ old('office_address', $profile?->office_address) }}</textarea>@error('office_address') <small>{{ $message }}</small> @enderror</label>
                </div>
            @elseif ((int) $activeStep === 6)
                <div class="form-grid">
                    <label>
                        <span>Profile Photo</span>
                        @php $profilePhotoUrl = $mediaUrl($profile?->profile_photo); @endphp
                        <input type="hidden" name="remove_profile_photo" value="0" data-admin-remove-file-input="profile_photo">
                        <div class="admin-file-manager @if (! $profilePhotoUrl) is-empty @endif" data-admin-file-manager="profile_photo">
                            <div class="admin-file-summary">
                                <strong>{{ $profilePhotoUrl ? 'Current file' : 'No file uploaded' }}</strong>
                                @if ($profilePhotoUrl)
                                    <span>{{ $mediaExtension($profile?->profile_photo) }} file</span>
                                @endif
                            </div>
                            @if ($profilePhotoUrl)
                                <div class="admin-file-actions" data-admin-file-row="profile_photo">
                                    <button
                                        class="file-link-with-icon admin-modal-preview-trigger"
                                        type="button"
                                        data-admin-modal-preview
                                        data-preview-title="Profile Photo"
                                        data-preview-src="{{ $profilePhotoUrl }}"
                                        data-preview-type="image"
                                    >
                                        <span aria-hidden="true">↗</span><span>Preview</span>
                                    </button>
                                    <a class="file-link-with-icon" href="{{ $profilePhotoUrl }}" download><span aria-hidden="true">↓</span><span>Download</span></a>
                                    <button class="file-link-with-icon admin-remove-file-button" type="button" data-admin-remove-file="profile_photo"><span aria-hidden="true">×</span><span>Remove</span></button>
                                </div>
                            @endif
                        </div>
                        <input type="file" name="profile_photo" accept="image/*">
                        @error('profile_photo') <small>{{ $message }}</small> @enderror
                        @error('remove_profile_photo') <small>{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>Cover Photo</span>
                        @php $coverPhotoUrl = $mediaUrl($profile?->cover_photo); @endphp
                        <input type="hidden" name="remove_cover_photo" value="0" data-admin-remove-file-input="cover_photo">
                        <div class="admin-file-manager @if (! $coverPhotoUrl) is-empty @endif" data-admin-file-manager="cover_photo">
                            <div class="admin-file-summary">
                                <strong>{{ $coverPhotoUrl ? 'Current file' : 'No file uploaded' }}</strong>
                                @if ($coverPhotoUrl)
                                    <span>{{ $mediaExtension($profile?->cover_photo) }} file</span>
                                @endif
                            </div>
                            @if ($coverPhotoUrl)
                                <div class="admin-file-actions" data-admin-file-row="cover_photo">
                                    <button
                                        class="file-link-with-icon admin-modal-preview-trigger"
                                        type="button"
                                        data-admin-modal-preview
                                        data-preview-title="Cover Photo"
                                        data-preview-src="{{ $coverPhotoUrl }}"
                                        data-preview-type="image"
                                    >
                                        <span aria-hidden="true">↗</span><span>Preview</span>
                                    </button>
                                    <a class="file-link-with-icon" href="{{ $coverPhotoUrl }}" download><span aria-hidden="true">↓</span><span>Download</span></a>
                                    <button class="file-link-with-icon admin-remove-file-button" type="button" data-admin-remove-file="cover_photo"><span aria-hidden="true">×</span><span>Remove</span></button>
                                </div>
                            @endif
                        </div>
                        <input type="file" name="cover_photo" accept="image/*">
                        @error('cover_photo') <small>{{ $message }}</small> @enderror
                        @error('remove_cover_photo') <small>{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>Business Card Upload</span>
                        @php $businessCardUrl = $mediaUrl($profile?->business_card_upload); @endphp
                        <input type="hidden" name="remove_business_card_upload" value="0" data-admin-remove-file-input="business_card_upload">
                        <div class="admin-file-manager @if (! $businessCardUrl) is-empty @endif" data-admin-file-manager="business_card_upload">
                            <div class="admin-file-summary">
                                <strong>{{ $businessCardUrl ? 'Current file' : 'No file uploaded' }}</strong>
                                @if ($businessCardUrl)
                                    <span>{{ $mediaExtension($profile?->business_card_upload) }} file</span>
                                @endif
                            </div>
                            @if ($businessCardUrl)
                                <div class="admin-file-actions" data-admin-file-row="business_card_upload">
                                    <button
                                        class="file-link-with-icon admin-modal-preview-trigger"
                                        type="button"
                                        data-admin-modal-preview
                                        data-preview-title="Business Card"
                                        data-preview-src="{{ $businessCardUrl }}"
                                        data-preview-type="{{ $mediaPreviewType($profile?->business_card_upload) }}"
                                        data-preview-extension="{{ $mediaExtension($profile?->business_card_upload) }}"
                                    >
                                        <span aria-hidden="true">↗</span><span>Preview</span>
                                    </button>
                                    <a class="file-link-with-icon" href="{{ $businessCardUrl }}" download><span aria-hidden="true">↓</span><span>Download</span></a>
                                    <button class="file-link-with-icon admin-remove-file-button" type="button" data-admin-remove-file="business_card_upload"><span aria-hidden="true">×</span><span>Remove</span></button>
                                </div>
                            @endif
                        </div>
                        <input type="file" name="business_card_upload" accept=".jpg,.jpeg,.png,.webp,.pdf">
                        @error('business_card_upload') <small>{{ $message }}</small> @enderror
                        @error('remove_business_card_upload') <small>{{ $message }}</small> @enderror
                    </label>
                    <label><span>Facebook Profile Link</span><input type="url" name="facebook_profile_link" value="{{ old('facebook_profile_link', $profile?->facebook_profile_link) }}">@error('facebook_profile_link') <small>{{ $message }}</small> @enderror</label>
                    <label><span>LinkedIn Profile Link</span><input type="url" name="linkedin_profile_link" value="{{ old('linkedin_profile_link', $profile?->linkedin_profile_link) }}">@error('linkedin_profile_link') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Website / Portfolio Link</span><input type="url" name="website_portfolio_link" value="{{ old('website_portfolio_link', $profile?->website_portfolio_link) }}">@error('website_portfolio_link') <small>{{ $message }}</small> @enderror</label>
                </div>
            @elseif ((int) $activeStep === 7)
                <div class="form-grid">
                    <label><span>Interested in alumni activities?</span><select name="interested_in_alumni_activities"><option value="1" @selected((string) old('interested_in_alumni_activities', (string) (int) $profile?->interested_in_alumni_activities) === '1')>Yes</option><option value="0" @selected((string) old('interested_in_alumni_activities', (string) (int) $profile?->interested_in_alumni_activities) === '0')>No</option></select>@error('interested_in_alumni_activities') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Volunteer Interest</span><select name="volunteer_interest"><option value="1" @selected((string) old('volunteer_interest', (string) (int) $profile?->volunteer_interest) === '1')>Yes</option><option value="0" @selected((string) old('volunteer_interest', (string) (int) $profile?->volunteer_interest) === '0')>No</option></select>@error('volunteer_interest') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Donor / Sponsor Interest</span><select name="donor_sponsor_interest"><option value="">Select one</option>@foreach (['Yes', 'No', 'Maybe Later'] as $option)<option value="{{ $option }}" @selected(old('donor_sponsor_interest', $profile?->donor_sponsor_interest) === $option)>{{ $option }}</option>@endforeach</select>@error('donor_sponsor_interest') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Mentor Interest</span><select name="mentor_interest"><option value="1" @selected((string) old('mentor_interest', (string) (int) $profile?->mentor_interest) === '1')>Yes</option><option value="0" @selected((string) old('mentor_interest', (string) (int) $profile?->mentor_interest) === '0')>No</option></select>@error('mentor_interest') <small>{{ $message }}</small> @enderror</label>
                </div>
                <fieldset class="checkbox-fieldset">
                    <legend>Areas of Interest</legend>
                    <div class="checkbox-grid">
                        @foreach ($areasOfInterest as $interest)
                            <label class="checkbox-item checkbox-interest-card">
                                <input type="checkbox" name="areas_of_interest[]" value="{{ $interest }}" @checked(in_array($interest, old('areas_of_interest', $profile?->areas_of_interest ?? []), true))>
                                <span class="checkbox-indicator"></span>
                                <span class="checkbox-copy">{{ $interest }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('areas_of_interest') <small>{{ $message }}</small> @enderror
                </fieldset>
                <label><span>Suggestions</span><textarea name="suggestions" rows="5">{{ old('suggestions', $profile?->suggestions) }}</textarea>@error('suggestions') <small>{{ $message }}</small> @enderror</label>
            @elseif ((int) $activeStep === 8)
                <div class="form-grid">
                    <label>
                        <span>SSC Certificate / Testimonial / Admit Card</span>
                        @php $certificateUrl = $mediaUrl($profile?->certificate_testimonial_upload); @endphp
                        <input type="hidden" name="remove_certificate_testimonial_upload" value="0" data-admin-remove-file-input="certificate_testimonial_upload">
                        <div class="admin-file-manager @if (! $certificateUrl) is-empty @endif" data-admin-file-manager="certificate_testimonial_upload">
                            <div class="admin-file-summary">
                                <strong>{{ $certificateUrl ? 'Current file' : 'No file uploaded' }}</strong>
                                @if ($certificateUrl)
                                    <span>{{ $mediaExtension($profile?->certificate_testimonial_upload) }} file</span>
                                @endif
                            </div>
                            @if ($certificateUrl)
                                <div class="admin-file-actions" data-admin-file-row="certificate_testimonial_upload">
                                    <button
                                        class="file-link-with-icon admin-modal-preview-trigger"
                                        type="button"
                                        data-admin-modal-preview
                                        data-preview-title="SSC Certificate / Testimonial / Admit Card"
                                        data-preview-src="{{ $certificateUrl }}"
                                        data-preview-type="{{ $mediaPreviewType($profile?->certificate_testimonial_upload) }}"
                                        data-preview-extension="{{ $mediaExtension($profile?->certificate_testimonial_upload) }}"
                                    >
                                        <span aria-hidden="true">↗</span><span>Preview</span>
                                    </button>
                                    <a class="file-link-with-icon" href="{{ $certificateUrl }}" download><span aria-hidden="true">↓</span><span>Download</span></a>
                                    <button class="file-link-with-icon admin-remove-file-button" type="button" data-admin-remove-file="certificate_testimonial_upload"><span aria-hidden="true">×</span><span>Remove</span></button>
                                </div>
                            @endif
                        </div>
                        <input type="file" name="certificate_testimonial_upload">
                        @error('certificate_testimonial_upload') <small>{{ $message }}</small> @enderror
                        @error('remove_certificate_testimonial_upload') <small>{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>Supporting Document Upload</span>
                        @php $supportingUrl = $mediaUrl($profile?->supporting_document_upload); @endphp
                        <input type="hidden" name="remove_supporting_document_upload" value="0" data-admin-remove-file-input="supporting_document_upload">
                        <div class="admin-file-manager @if (! $supportingUrl) is-empty @endif" data-admin-file-manager="supporting_document_upload">
                            <div class="admin-file-summary">
                                <strong>{{ $supportingUrl ? 'Current file' : 'No file uploaded' }}</strong>
                                @if ($supportingUrl)
                                    <span>{{ $mediaExtension($profile?->supporting_document_upload) }} file</span>
                                @endif
                            </div>
                            @if ($supportingUrl)
                                <div class="admin-file-actions" data-admin-file-row="supporting_document_upload">
                                    <button
                                        class="file-link-with-icon admin-modal-preview-trigger"
                                        type="button"
                                        data-admin-modal-preview
                                        data-preview-title="Supporting Document"
                                        data-preview-src="{{ $supportingUrl }}"
                                        data-preview-type="{{ $mediaPreviewType($profile?->supporting_document_upload) }}"
                                        data-preview-extension="{{ $mediaExtension($profile?->supporting_document_upload) }}"
                                    >
                                        <span aria-hidden="true">↗</span><span>Preview</span>
                                    </button>
                                    <a class="file-link-with-icon" href="{{ $supportingUrl }}" download><span aria-hidden="true">↓</span><span>Download</span></a>
                                    <button class="file-link-with-icon admin-remove-file-button" type="button" data-admin-remove-file="supporting_document_upload"><span aria-hidden="true">×</span><span>Remove</span></button>
                                </div>
                            @endif
                        </div>
                        <input type="file" name="supporting_document_upload">
                        @error('supporting_document_upload') <small>{{ $message }}</small> @enderror
                        @error('remove_supporting_document_upload') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
            @elseif ((int) $activeStep === 9)
                <div class="form-grid">
                    <label><span>Profile Visibility</span><select name="profile_visibility"><option value="">Select visibility</option>@foreach (['Show my profile in the alumni directory', 'Show my profile only to verified members', 'Keep my profile private'] as $option)<option value="{{ $option }}" @selected(old('profile_visibility', $profile?->profile_visibility) === $option)>{{ $option }}</option>@endforeach</select>@error('profile_visibility') <small>{{ $message }}</small> @enderror</label>
                    <label><span>Contact Visibility</span><select name="contact_visibility"><option value="">Select contact visibility</option>@foreach (['Show my contact details to verified members only', 'Keep my contact details private'] as $option)<option value="{{ $option }}" @selected(old('contact_visibility', $profile?->contact_visibility) === $option)>{{ $option }}</option>@endforeach</select>@error('contact_visibility') <small>{{ $message }}</small> @enderror</label>
                </div>
            @elseif ((int) $activeStep === 10)
                <div class="declaration-stack">
                    <label class="checkbox-item declaration-item"><input type="checkbox" name="information_accuracy_confirmation" value="1" @checked(old('information_accuracy_confirmation', $profile?->information_accuracy_confirmation))><span>Information accuracy confirmed</span></label>
                    @error('information_accuracy_confirmation') <small>{{ $message }}</small> @enderror
                    <label class="checkbox-item declaration-item"><input type="checkbox" name="terms_privacy_agreement" value="1" @checked(old('terms_privacy_agreement', $profile?->terms_privacy_agreement))><span>Terms and privacy accepted</span></label>
                    @error('terms_privacy_agreement') <small>{{ $message }}</small> @enderror
                    <label class="checkbox-item declaration-item"><input type="checkbox" name="admin_verification_agreement" value="1" @checked(old('admin_verification_agreement', $profile?->admin_verification_agreement))><span>Admin verification agreement accepted</span></label>
                    @error('admin_verification_agreement') <small>{{ $message }}</small> @enderror
                </div>
            @endif

            <div class="action-row wizard-nav-actions">
                @if ((int) $activeStep > 1)
                    <button class="button button-secondary" type="submit" name="previous_step" value="1">Previous</button>
                @endif
                <button class="button button-secondary" type="submit">Save Changes</button>
                @if ((int) $activeStep < 10)
                    <button class="button button-primary" type="submit" name="save_and_continue" value="1">Save &amp; Continue</button>
                @endif
            </div>
        </form>

        <div class="admin-detail-grid">
            <article class="profile-summary-card">
                <span class="panel-card-label">Review Actions</span>
                @if (! in_array($application->status, ['approved', 'rejected'], true))
                    <div class="admin-actions-grid">
                        <form class="panel-card admin-action-card" method="POST" action="{{ route('admin.applications.approve', $application) }}">
                            @csrf
                            <p class="panel-card-label">Approve</p>
                            <label for="detail-approve-notes">Admin notes</label>
                            <textarea id="detail-approve-notes" name="admin_notes" rows="4" placeholder="Optional approval note"></textarea>
                            <button class="button button-primary" type="submit">Approve Application</button>
                        </form>

                        <form class="panel-card admin-action-card" method="POST" action="{{ route('admin.applications.reject', $application) }}">
                            @csrf
                            <p class="panel-card-label">Reject</p>
                            <label for="detail-reject-notes">Rejection note</label>
                            <textarea id="detail-reject-notes" name="admin_notes" rows="4" placeholder="Required rejection reason" required></textarea>
                            <button class="button danger-button" type="submit">Reject Application</button>
                        </form>
                    </div>
                @else
                    <p class="dashboard-copy">This application has already been finalized.</p>
                @endif
            </article>
        </div>

        <div class="admin-file-preview-modal" data-admin-file-preview-modal hidden>
            <div class="admin-file-preview-backdrop" data-admin-file-preview-close></div>
            <div class="admin-file-preview-dialog" role="dialog" aria-modal="true" aria-labelledby="admin-file-preview-title">
                <div class="admin-file-preview-head">
                    <div>
                        <p class="eyebrow">File Preview</p>
                        <h2 id="admin-file-preview-title">Preview</h2>
                    </div>
                    <button class="admin-file-preview-close" type="button" data-admin-file-preview-close aria-label="Close preview">×</button>
                </div>
                <div class="admin-file-preview-body" data-admin-file-preview-body></div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            document.querySelectorAll('[data-admin-remove-file]').forEach((button) => {
                button.addEventListener('click', () => {
                    const field = button.dataset.adminRemoveFile;
                    const input = document.querySelector(`[data-admin-remove-file-input="${field}"]`);
                    const manager = document.querySelector(`[data-admin-file-manager="${field}"]`);
                    const row = document.querySelector(`[data-admin-file-row="${field}"]`);

                    if (input) {
                        input.value = '1';
                    }

                    if (row) {
                        row.hidden = true;
                    }

                    if (manager) {
                        manager.classList.add('is-empty');

                        const title = manager.querySelector('.admin-file-summary strong');
                        const meta = manager.querySelector('.admin-file-summary span');

                        if (title) {
                            title.textContent = 'File will be removed on save';
                        }

                        if (meta) {
                            meta.textContent = 'Upload a replacement below if needed';
                        }
                    }
                });
            });

            const previewModal = document.querySelector('[data-admin-file-preview-modal]');
            const previewBody = previewModal?.querySelector('[data-admin-file-preview-body]');
            const previewTitle = previewModal?.querySelector('#admin-file-preview-title');
            let previousActiveElement = null;

            const closePreviewModal = () => {
                if (! previewModal || ! previewBody) {
                    return;
                }

                previewModal.hidden = true;
                document.body.classList.remove('admin-preview-open');
                previewBody.innerHTML = '';

                if (previousActiveElement instanceof HTMLElement) {
                    previousActiveElement.focus();
                }
            };

            const openPreviewModal = (button) => {
                if (! previewModal || ! previewBody || ! previewTitle) {
                    return;
                }

                previousActiveElement = button;

                const title = button.dataset.previewTitle || 'Preview';
                const src = button.dataset.previewSrc || '';
                const type = button.dataset.previewType || 'file';
                const extension = button.dataset.previewExtension || 'File';

                previewTitle.textContent = title;
                previewBody.innerHTML = '';

                if (type === 'image') {
                    const image = document.createElement('img');
                    image.className = 'admin-file-preview-image';
                    image.src = src;
                    image.alt = `${title} preview`;
                    previewBody.appendChild(image);
                } else if (type === 'pdf') {
                    const frame = document.createElement('iframe');
                    frame.className = 'admin-file-preview-frame';
                    frame.src = `${src}#toolbar=0`;
                    frame.title = `${title} preview`;
                    previewBody.appendChild(frame);
                } else {
                    const fallback = document.createElement('div');
                    fallback.className = 'admin-file-preview-fallback';
                    fallback.innerHTML = `<strong>${extension} file</strong><p>Preview is not available for this file type.</p>`;

                    const openLink = document.createElement('a');
                    openLink.className = 'button button-secondary';
                    openLink.href = src;
                    openLink.target = '_blank';
                    openLink.rel = 'noopener';
                    openLink.textContent = 'Open File';

                    fallback.appendChild(openLink);
                    previewBody.appendChild(fallback);
                }

                previewModal.hidden = false;
                document.body.classList.add('admin-preview-open');
            };

            document.querySelectorAll('[data-admin-modal-preview]').forEach((button) => {
                button.addEventListener('click', () => openPreviewModal(button));
            });

            previewModal?.querySelectorAll('[data-admin-file-preview-close]').forEach((element) => {
                element.addEventListener('click', closePreviewModal);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && previewModal && ! previewModal.hidden) {
                    closePreviewModal();
                }
            });
        })();
    </script>
@endpush
