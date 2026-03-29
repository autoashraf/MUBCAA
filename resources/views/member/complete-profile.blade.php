@extends('layouts.app')

@section('content')
    @php
        $activeStep = old('wizard_step', $currentStep);
    @endphp

    <section class="page-hero">
        <div class="wrap">
            <div class="dashboard-hero">
                <div class="dashboard-hero-copy">
                    <p class="eyebrow">Alumni Membership Registration Form</p>
                    <h1>Complete your alumni profile</h1>
                    <p class="lead">Please complete your registration step by step. You can save your progress at each step and continue later. Your membership profile will be submitted for admin verification after the final step.</p>
                </div>
                <div class="dashboard-badges">
                    <span class="status-pill status-{{ $application?->status ?? $user->membership_status }}" data-wizard-status-pill>{{ ($application?->status ?? $user->membership_status) === 'pending_review' ? 'Under Review' : str($application?->status ?? $user->membership_status)->replace('_', ' ')->title() }}</span>
                    <span class="dashboard-chip" data-wizard-completion-chip>Profile {{ $profileCompletion }}% complete</span>
                    <span class="dashboard-chip" data-wizard-step-label>Step {{ $activeStep }} of 10</span>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="wrap registration-shell" data-profile-wizard data-initial-step="{{ $activeStep }}" data-step-base-url="{{ url('/membership/profile-completion') }}">
            <form class="form-card registration-form-card" method="POST" action="{{ route('member.profile.complete.save') }}" enctype="multipart/form-data" data-ajax-form="wizard">
                @csrf
                @error('verification')
                    <div class="alert-success alert-warning-like">{{ $message }}</div>
                @enderror

                @foreach ($steps as $number => $label)
                    <section class="wizard-panel @if ((int) $activeStep === $number) is-active @endif" data-wizard-panel="{{ $number }}">
                        @if ($number >= 2)
                            <input type="hidden" name="wizard_step" value="{{ $number }}" @disabled((int) $activeStep !== $number)>
                        @endif
                            <div class="dashboard-form-head">
                                <div>
                                    <p class="panel-card-label">Step {{ $number }}: {{ $label }}</p>
                                    <h3>{{ $label }}</h3>
                                    <p class="dashboard-copy">{{ $number === 1 ? 'Review the information you submitted during Step 1 before continuing to the academic section.' : $stepDescriptions[$number] }}</p>
                                </div>
                            </div>

                            @if ($number === 1)
                                <div class="form-grid">
                                    <label>
                                        <span>Full Name</span>
                                        <input type="text" value="{{ $user->name }}" readonly>
                                    </label>
                                    <label>
                                        <span>Mobile Number</span>
                                        <input type="text" value="{{ $user->phone }}" readonly>
                                    </label>
                                    <label>
                                        <span>Email Address</span>
                                        <input type="text" value="{{ $user->email }}" readonly>
                                    </label>
                                    <label>
                                        <span>Passing Year / Batch</span>
                                        <input type="text" value="{{ $profile?->passing_year_batch }}" readonly>
                                    </label>
                                    <label>
                                        <span>How did you find us?</span>
                                        <input type="text" value="{{ $profile?->how_did_you_find_us ?: 'Not added' }}" readonly>
                                    </label>
                                </div>
                            @elseif ($number === 2)
                                <div class="form-grid">
                                    <label>
                                        <span>Passing Year SSC</span>
                                        <select name="ssc_passing_year">
                                            <option value="">Select year</option>
                                            @foreach ($passingYears as $option)
                                                <option value="{{ $option }}" @selected(old('ssc_passing_year', $profile?->ssc_passing_year ?: $profile?->passing_year_batch) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('ssc_passing_year') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>Passing Year HSC</span>
                                        <select name="hsc_passing_year">
                                            <option value="">Select year</option>
                                            @foreach ($passingYears as $option)
                                                <option value="{{ $option }}" @selected(old('hsc_passing_year', $profile?->hsc_passing_year ?: $profile?->passing_year_batch) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('hsc_passing_year') <small>{{ $message }}</small> @enderror
                                        <small>Enter SSC or HSC year. At least one is required.</small>
                                    </label>
                                    <label>
                                        <span>Group</span>
                                        <select name="group">
                                            <option value="">Select group</option>
                                            @foreach ($academicGroups as $option)
                                                <option value="{{ $option }}" @selected(old('group', $profile?->group) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('group') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>Shift</span>
                                        <select name="shift">
                                            <option value="">Select shift</option>
                                            @foreach ($academicShifts as $option)
                                                <option value="{{ $option }}" @selected(old('shift', $profile?->shift) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('shift') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>Campus / Branch</span>
                                        <select name="campus_branch">
                                            <option value="">Select campus</option>
                                            @foreach ($campusBranches as $option)
                                                <option value="{{ $option }}" @selected(old('campus_branch', $profile?->campus_branch) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('campus_branch') <small>{{ $message }}</small> @enderror
                                    </label>
                                </div>
                            @elseif ($number === 3)
                                <div class="form-grid">
                                    <label>
                                        <span>Date of Birth</span>
                                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($profile?->date_of_birth)->format('Y-m-d')) }}">
                                        @error('date_of_birth') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>Gender</span>
                                        <select name="gender">
                                            <option value="">Select gender</option>
                                            @foreach (['Male', 'Female', 'Other'] as $option)
                                                <option value="{{ $option }}" @selected(old('gender', $profile?->gender) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('gender') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>Blood Group</span>
                                        <select name="blood_group">
                                            <option value="">Select blood group</option>
                                            @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $option)
                                                <option value="{{ $option }}" @selected(old('blood_group', $profile?->blood_group) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('blood_group') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>Father’s Name</span>
                                        <input type="text" name="father_name" value="{{ old('father_name', $profile?->father_name) }}" placeholder="Enter your father’s name">
                                        @error('father_name') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>Mother’s Name</span>
                                        <input type="text" name="mother_name" value="{{ old('mother_name', $profile?->mother_name) }}" placeholder="Enter your mother’s name">
                                        @error('mother_name') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>Marital Status</span>
                                        <select name="marital_status">
                                            <option value="">Select marital status</option>
                                            @foreach (['Single', 'Married', 'Other'] as $option)
                                                <option value="{{ $option }}" @selected(old('marital_status', $profile?->marital_status) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('marital_status') <small>{{ $message }}</small> @enderror
                                    </label>
                                </div>
                            @elseif ($number === 4)
                                <div class="form-grid">
                                    <label>
                                        <span>Primary Mobile Number</span>
                                        <input type="text" name="primary_mobile" value="{{ old('primary_mobile', $profile?->primary_mobile ?? $user->phone) }}" placeholder="Enter your primary mobile number" readonly>
                                        @error('primary_mobile') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>Secondary Mobile Number</span>
                                        <input type="text" name="secondary_mobile" value="{{ old('secondary_mobile', $profile?->secondary_mobile) }}" placeholder="Enter your secondary mobile number">
                                        @error('secondary_mobile') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>WhatsApp Number</span>
                                        <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $profile?->whatsapp_number) }}" placeholder="Enter your WhatsApp number">
                                        <div class="inline-checkbox-option" data-whatsapp-sync-wrapper>
                                            <div class="inline-checkbox-control">
                                                <input
                                                    type="checkbox"
                                                    value="1"
                                                    data-whatsapp-same-toggle
                                                    data-primary-source="primary_mobile"
                                                    data-whatsapp-target="whatsapp_number"
                                                    @checked(old('whatsapp_number', $profile?->whatsapp_number) && old('whatsapp_number', $profile?->whatsapp_number) === old('primary_mobile', $profile?->primary_mobile ?? $user->phone))
                                                >
                                                <span class="inline-checkbox-box" aria-hidden="true"></span>
                                                <span class="inline-checkbox-copy">
                                                    <strong>Use primary mobile number</strong>
                                                    <small data-whatsapp-sync-note>Synced from primary mobile</small>
                                                </span>
                                            </div>
                                        </div>
                                        @error('whatsapp_number') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>Email Address</span>
                                        <input type="email" name="email_address" value="{{ old('email_address', $profile?->email_address ?? $user->email) }}" placeholder="Enter your email address" readonly>
                                        @error('email_address') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label class="label-wide">
                                        <span>Present Address</span>
                                        <textarea name="present_address" rows="4" placeholder="Enter your present address">{{ old('present_address', $profile?->present_address) }}</textarea>
                                        @error('present_address') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label class="label-wide">
                                        <span>Permanent Address</span>
                                        <textarea name="permanent_address" rows="4" placeholder="Enter your permanent address">{{ old('permanent_address', $profile?->permanent_address) }}</textarea>
                                        @error('permanent_address') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>Country</span>
                                        <select name="country">
                                            <option value="Bangladesh" selected>Bangladesh</option>
                                        </select>
                                        @error('country') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>City / District</span>
                                        <select name="city_district">
                                            <option value="">Select district</option>
                                            @foreach ($districts as $option)
                                                <option value="{{ $option }}" @selected(old('city_district', $profile?->city_district ?? $profile?->current_city) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('city_district') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>Postal Code</span>
                                        <input type="text" name="postal_code" value="{{ old('postal_code', $profile?->postal_code) }}" placeholder="Enter your postal code">
                                        @error('postal_code') <small>{{ $message }}</small> @enderror
                                    </label>
                                </div>
                            @elseif ($number === 5)
                                <div class="form-grid">
                                    <label><span>Occupation</span><select name="occupation"><option value="">Select occupation</option>@foreach ($occupations as $option)<option value="{{ $option }}" @selected(old('occupation', $profile?->occupation) === $option)>{{ $option }}</option>@endforeach</select>@error('occupation') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>Organization / Company Name</span><input type="text" name="organization_name" value="{{ old('organization_name', $profile?->organization_name) }}" placeholder="Enter your company or organization name">@error('organization_name') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>Designation / Job Title</span><select name="designation"><option value="">Select designation</option>@foreach ($designations as $option)<option value="{{ $option }}" @selected(old('designation', $profile?->designation) === $option)>{{ $option }}</option>@endforeach</select>@error('designation') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>Industry</span><select name="industry"><option value="">Select industry</option>@foreach ($industries as $option)<option value="{{ $option }}" @selected(old('industry', $profile?->industry) === $option)>{{ $option }}</option>@endforeach</select>@error('industry') <small>{{ $message }}</small> @enderror</label>
                                    <label class="label-wide"><span>Office Address</span><textarea name="office_address" rows="4" placeholder="Enter your office address">{{ old('office_address', $profile?->office_address) }}</textarea>@error('office_address') <small>{{ $message }}</small> @enderror</label>
                                </div>
                            @elseif ($number === 6)
                                <div class="form-grid">
                                    <label>
                                        <span>Profile Photo</span>
                                        <div class="image-preview-field">
                                            @php
                                                $profilePhotoExists = filled($profile?->profile_photo) && \Illuminate\Support\Facades\Storage::disk('public')->exists($profile->profile_photo);
                                            @endphp
                                            <div class="image-preview-box image-preview-box-square" data-image-preview-box>
                                                <button class="image-remove-button @if (! $profilePhotoExists) is-hidden @endif" type="button" data-remove-image-button="profile_photo" aria-label="Remove profile photo">×</button>
                                                <input type="hidden" name="remove_profile_photo" value="0" data-remove-image-input="profile_photo">
                                                @if ($profilePhotoExists)
                                                    <img
                                                        src="{{ asset('storage/'.$profile->profile_photo) }}"
                                                        alt="Profile photo preview"
                                                        data-image-preview-target="profile_photo"
                                                    >
                                                @else
                                                    <span class="image-preview-placeholder" data-image-preview-target="profile_photo">Photo Preview</span>
                                                @endif
                                            </div>
                                            <input class="visually-hidden-file-input" type="file" name="profile_photo" accept="image/*" data-image-preview-input="profile_photo" id="profile-photo-input">
                                            <button class="button button-secondary image-upload-trigger" type="button" data-image-upload-trigger="profile-photo-input">Choose Profile Photo</button>
                                        </div>
                                        <small>Upload a clear passport-size or professional photo</small>
                                        @error('profile_photo') <small>{{ $message }}</small> @enderror
                                        @error('remove_profile_photo') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>Cover Photo</span>
                                        <div class="image-preview-field">
                                            @php
                                                $coverPhotoExists = filled($profile?->cover_photo) && \Illuminate\Support\Facades\Storage::disk('public')->exists($profile->cover_photo);
                                            @endphp
                                            <div class="image-preview-box image-preview-box-cover" data-image-preview-box>
                                                <button class="image-remove-button @if (! $coverPhotoExists) is-hidden @endif" type="button" data-remove-image-button="cover_photo" aria-label="Remove cover photo">×</button>
                                                <input type="hidden" name="remove_cover_photo" value="0" data-remove-image-input="cover_photo">
                                                @if ($coverPhotoExists)
                                                    <img
                                                        src="{{ asset('storage/'.$profile->cover_photo) }}"
                                                        alt="Cover photo preview"
                                                        data-image-preview-target="cover_photo"
                                                    >
                                                @else
                                                    <span class="image-preview-placeholder" data-image-preview-target="cover_photo">Cover Preview</span>
                                                @endif
                                            </div>
                                            <input class="visually-hidden-file-input" type="file" name="cover_photo" accept="image/*" data-image-preview-input="cover_photo" id="cover-photo-input">
                                            <button class="button button-secondary image-upload-trigger" type="button" data-image-upload-trigger="cover-photo-input">Choose Cover Photo</button>
                                        </div>
                                        <small>Optional</small>
                                        @error('cover_photo') <small>{{ $message }}</small> @enderror
                                        @error('remove_cover_photo') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label><span>Business Card Upload</span><input class="visually-hidden-file-input" type="file" name="business_card_upload" accept=".jpg,.jpeg,.png,.webp,.pdf" id="business-card-input"><button class="button button-secondary image-upload-trigger" type="button" data-image-upload-trigger="business-card-input">Choose Business Card</button><small>Upload your business card</small>@error('business_card_upload') <small>{{ $message }}</small> @enderror</label>
                                </div>
                                <div class="form-grid">
                                    <label><span>Facebook Profile Link</span><input type="url" name="facebook_profile_link" value="{{ old('facebook_profile_link', $profile?->facebook_profile_link) }}" placeholder="Paste your Facebook profile URL">@error('facebook_profile_link') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>LinkedIn Profile Link</span><input type="url" name="linkedin_profile_link" value="{{ old('linkedin_profile_link', $profile?->linkedin_profile_link) }}" placeholder="Paste your LinkedIn profile URL">@error('linkedin_profile_link') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>Website / Portfolio Link</span><input type="url" name="website_portfolio_link" value="{{ old('website_portfolio_link', $profile?->website_portfolio_link) }}" placeholder="Paste your website or portfolio URL">@error('website_portfolio_link') <small>{{ $message }}</small> @enderror</label>
                                </div>
                            @elseif ($number === 7)
                                <div class="form-grid">
                                    <label><span>Are you interested in joining alumni activities?</span><select name="interested_in_alumni_activities"><option value="1" @selected((string) old('interested_in_alumni_activities', is_null($profile?->interested_in_alumni_activities) ? '1' : (string) (int) $profile?->interested_in_alumni_activities) === '1')>Yes</option><option value="0" @selected((string) old('interested_in_alumni_activities', is_null($profile?->interested_in_alumni_activities) ? '1' : (string) (int) $profile?->interested_in_alumni_activities) === '0')>No</option></select>@error('interested_in_alumni_activities') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>Would you like to volunteer for alumni programs?</span><select name="volunteer_interest"><option value="1" @selected((string) old('volunteer_interest', is_null($profile?->volunteer_interest) ? '1' : (string) (int) $profile?->volunteer_interest) === '1')>Yes</option><option value="0" @selected((string) old('volunteer_interest', is_null($profile?->volunteer_interest) ? '1' : (string) (int) $profile?->volunteer_interest) === '0')>No</option></select>@error('volunteer_interest') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>Would you like to support alumni initiatives in the future as a donor or sponsor?</span><select name="donor_sponsor_interest"><option value="">Select one</option>@foreach (['Yes', 'No', 'Maybe Later'] as $option)<option value="{{ $option }}" @selected(old('donor_sponsor_interest', $profile?->donor_sponsor_interest) === $option)>{{ $option }}</option>@endforeach</select>@error('donor_sponsor_interest') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>Would you be interested in mentoring current students?</span><select name="mentor_interest"><option value="">Select one</option><option value="1" @selected((string) old('mentor_interest', (int) $profile?->mentor_interest) === '1')>Yes</option><option value="0" @selected((string) old('mentor_interest', (int) $profile?->mentor_interest) === '0')>No</option></select>@error('mentor_interest') <small>{{ $message }}</small> @enderror</label>
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
                                </fieldset>
                                <label><span>Suggestions for the Alumni Association</span><textarea name="suggestions" rows="5" placeholder="Share your ideas or suggestions">{{ old('suggestions', $profile?->suggestions) }}</textarea>@error('suggestions') <small>{{ $message }}</small> @enderror</label>
                            @elseif ($number === 8)
                                <div class="form-grid">
                                    <label><span>SSC Certificate / Testimonial / Admit Card</span><input type="file" name="certificate_testimonial_upload">@error('certificate_testimonial_upload') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>Supporting Document Upload</span><input type="file" name="supporting_document_upload">@error('supporting_document_upload') <small>{{ $message }}</small> @enderror</label>
                                </div>
                            @elseif ($number === 9)
                                <div class="form-grid">
                                    <label><span>Profile Visibility</span><select name="profile_visibility" required><option value="">Select visibility</option>@foreach (['Show my profile in the alumni directory', 'Show my profile only to verified members', 'Keep my profile private'] as $option)<option value="{{ $option }}" @selected(old('profile_visibility', $profile?->profile_visibility) === $option)>{{ $option }}</option>@endforeach</select>@error('profile_visibility') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>Contact Visibility</span><select name="contact_visibility" required><option value="">Select contact visibility</option>@foreach (['Show my contact details to verified members only', 'Keep my contact details private'] as $option)<option value="{{ $option }}" @selected(old('contact_visibility', $profile?->contact_visibility) === $option)>{{ $option }}</option>@endforeach</select>@error('contact_visibility') <small>{{ $message }}</small> @enderror</label>
                                </div>
                            @elseif ($number === 10)
                                <div class="declaration-stack">
                                    <label class="checkbox-item declaration-item"><input type="checkbox" name="information_accuracy_confirmation" value="1" @checked(old('information_accuracy_confirmation', $profile?->information_accuracy_confirmation))><span>I confirm that the information provided by me is true and correct.</span></label>
                                    @error('information_accuracy_confirmation') <small>{{ $message }}</small> @enderror
                                    <label class="checkbox-item declaration-item"><input type="checkbox" name="terms_privacy_agreement" value="1" @checked(old('terms_privacy_agreement', $profile?->terms_privacy_agreement))><span>I agree to the Alumni Association’s terms and privacy policy.</span></label>
                                    @error('terms_privacy_agreement') <small>{{ $message }}</small> @enderror
                                    <label class="checkbox-item declaration-item"><input type="checkbox" name="admin_verification_agreement" value="1" @checked(old('admin_verification_agreement', $profile?->admin_verification_agreement))><span>I understand that my profile will remain subject to verification and approval by the admin.</span></label>
                                    @error('admin_verification_agreement') <small>{{ $message }}</small> @enderror
                                </div>
                            @endif

                            <div class="action-row wizard-nav-actions">
                                @if ($number === 1)
                                    <button class="button button-primary" type="button" data-step-target="2">Continue to Step 2</button>
                                @elseif ($number > 1)
                                    <button class="button button-secondary" type="button" data-step-target="{{ $number - 1 }}">Previous</button>
                                @endif
                                @if ($number >= 2 && $number < 10)
                                    <button class="button button-secondary" type="submit" name="save_as_draft" value="1" data-draft-action-step="{{ $number }}" @if(!empty($stepCompletion[$number])) hidden @endif>Save as Draft</button>
                                    <button class="button button-primary" type="submit" name="next_step" value="{{ $number + 1 }}">Save &amp; Continue</button>
                                @elseif ($number === 10)
                                    <button class="button button-primary" type="submit" name="submit_for_verification" value="1">Submit for Review</button>
                                @endif
                            </div>
                    </section>
                @endforeach
            </form>
        </div>
    </section>
@endsection
