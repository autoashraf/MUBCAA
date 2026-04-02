@extends('layouts.app')

@section('content')
    @php
        $activeStep = max(2, (int) old('wizard_step', $currentStep));
        $logoPath = config('site.brand.logo_path');
        $brandName = config('site.brand.name', 'MUBCAA');
    @endphp

    <section class="section wizard-auth-section registration-wizard-section min-h-screen flex items-center justify-center">
        <div class="wrap wizard-auth-wrap registration-shell w-full max-w-md px-4" data-profile-wizard data-initial-step="{{ $activeStep }}" data-step-base-url="{{ url('/membership/profile-completion') }}">
            <form class="form-card registration-form-card wizard-auth-card registration-wizard-card w-full space-y-3" method="POST" action="{{ route('member.profile.complete.save') }}" enctype="multipart/form-data" data-ajax-form="wizard">
                @csrf

                <div class="wizard-auth-brand">
                    @if ($logoPath)
                        <span class="wizard-auth-logo">
                            <img src="{{ asset($logoPath) }}" alt="{{ $brandName }} logo">
                        </span>
                    @else
                        <span class="wizard-auth-logo wizard-auth-logo-fallback">M</span>
                    @endif
                    <p class="wizard-auth-brand-name">{{ $brandName }}</p>
                </div>

                @error('verification')
                    <div class="alert-success alert-warning-like">{{ $message }}</div>
                @enderror

                @if (!empty($profileLocked))
                    <div class="alert-success alert-warning-like">
                        {{ __('Your alumni membership profile has already been submitted for review. You can view your progress from the dashboard, but this form can no longer be resubmitted.') }}
                    </div>
                @endif

                @foreach ($steps as $number => $label)
                    <section class="wizard-panel @if ((int) $activeStep === $number) is-active @endif @if ($number === 7) wizard-step-labels @endif" data-wizard-panel="{{ $number }}">
                        <input type="hidden" name="wizard_step" value="{{ $number }}" @disabled((int) $activeStep !== $number)>
                            <div class="dashboard-form-head wizard-auth-copy">
                                <div>
                                    <p class="panel-card-label">{{ __('Step :number: :label', ['number' => $number, 'label' => $label]) }}</p>
                                    <h3>{{ $label }}</h3>
                                    <p class="dashboard-copy">{{ $stepDescriptions[$number] }}</p>
                                </div>
                            </div>

                            @if ($number === 2)
                                <div class="wizard-form-stack">
                                    <label>
                                        <span>{{ __('Passing Year SSC') }}</span>
                                        <select name="ssc_passing_year">
                                            <option value="">{{ __('SSC Passing Year') }}</option>
                                            @foreach ($passingYears as $option)
                                                <option value="{{ $option }}" @selected(old('ssc_passing_year', $profile?->ssc_passing_year) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('ssc_passing_year') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>{{ __('Passing Year HSC') }}</span>
                                        <select name="hsc_passing_year">
                                            <option value="">{{ __('HSC Passing Year') }}</option>
                                            @foreach ($passingYears as $option)
                                                <option value="{{ $option }}" @selected(old('hsc_passing_year', $profile?->hsc_passing_year) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('hsc_passing_year') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>{{ __('Group') }}</span>
                                        <select name="group">
                                            <option value="">{{ __('Select group') }}</option>
                                            @foreach ($academicGroups as $option)
                                                <option value="{{ $option }}" @selected(old('group', $profile?->group) === $option)>{{ __($option) }}</option>
                                            @endforeach
                                        </select>
                                        @error('group') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>{{ __('Shift') }}</span>
                                        <select name="shift">
                                            <option value="">{{ __('Select shift') }}</option>
                                            @foreach ($academicShifts as $option)
                                                <option value="{{ $option }}" @selected(old('shift', $profile?->shift) === $option)>{{ __($option) }}</option>
                                            @endforeach
                                        </select>
                                        @error('shift') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>{{ __('Campus / Branch') }}</span>
                                        <select name="campus_branch">
                                            <option value="">{{ __('Select campus') }}</option>
                                            @foreach ($campusBranches as $option)
                                                <option value="{{ $option }}" @selected(old('campus_branch', $profile?->campus_branch) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('campus_branch') <small>{{ $message }}</small> @enderror
                                    </label>
                                </div>
                            @elseif ($number === 3)
                                <div class="wizard-form-stack">
                                    <label>
                                        <span>{{ __('Date of Birth') }}</span>
                                        @php
                                            $dateOfBirthValue = old('date_of_birth', optional($profile?->date_of_birth)->format('Y-m-d'));
                                        @endphp
                                        <input
                                            type="{{ $dateOfBirthValue ? 'date' : 'text' }}"
                                            name="date_of_birth"
                                            value="{{ $dateOfBirthValue }}"
                                            placeholder="{{ __('Date of birth') }}"
                                            onfocus="this.type='date'"
                                            onblur="if(!this.value){this.type='text'}"
                                        >
                                        @error('date_of_birth') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>{{ __('Gender') }}</span>
                                        <select name="gender">
                                            <option value="">{{ __('Select gender') }}</option>
                                            @foreach (['Male', 'Female', 'Other'] as $option)
                                                <option value="{{ $option }}" @selected(old('gender', $profile?->gender) === $option)>{{ __($option) }}</option>
                                            @endforeach
                                        </select>
                                        @error('gender') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>{{ __('Blood Group') }}</span>
                                        <select name="blood_group">
                                            <option value="">{{ __('Select blood group') }}</option>
                                            @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $option)
                                                <option value="{{ $option }}" @selected(old('blood_group', $profile?->blood_group) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('blood_group') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>{{ __('Father’s Name') }}</span>
                                        <input type="text" name="father_name" value="{{ old('father_name', $profile?->father_name) }}" placeholder="{{ __('Enter your father’s name') }}">
                                        @error('father_name') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>{{ __('Mother’s Name') }}</span>
                                        <input type="text" name="mother_name" value="{{ old('mother_name', $profile?->mother_name) }}" placeholder="{{ __('Enter your mother’s name') }}">
                                        @error('mother_name') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>{{ __('Marital Status') }}</span>
                                        <select name="marital_status">
                                            <option value="">{{ __('Select marital status') }}</option>
                                            @foreach (['Single', 'Married', 'Other'] as $option)
                                                <option value="{{ $option }}" @selected(old('marital_status', $profile?->marital_status) === $option)>{{ __($option) }}</option>
                                            @endforeach
                                        </select>
                                        @error('marital_status') <small>{{ $message }}</small> @enderror
                                    </label>
                                </div>
                            @elseif ($number === 4)
                                <div class="wizard-form-stack">
                                    <div class="wizard-field">
                                        <span>{{ __('Primary Mobile Number') }}</span>
                                        @php
                                            $primaryMobileParts = \App\Support\PhoneNumber::split($profile?->primary_mobile ?? $user->phone, '+880');
                                            $selectedPrimaryCountryCode = old('primary_mobile_country_code', $primaryMobileParts['country_code']);
                                            $primaryMobileValue = old('primary_mobile', $primaryMobileParts['national_number']);
                                        @endphp
                                        <div class="phone-input-group">
                                            <div class="country-code-dropdown" data-country-code-dropdown>
                                                <input type="hidden" name="primary_mobile_country_code" value="{{ $selectedPrimaryCountryCode }}" data-country-code-value>
                                                <button class="phone-code-trigger" type="button" data-country-code-trigger aria-expanded="false" onclick="(function(btn){var dropdown=btn.closest('[data-country-code-dropdown]');if(!dropdown)return;var isOpen=dropdown.classList.toggle('is-open');btn.setAttribute('aria-expanded',isOpen?'true':'false');})(this)">
                                                    <span data-country-code-label>{{ $selectedPrimaryCountryCode }}</span>
                                                    <span class="phone-code-trigger-icon" aria-hidden="true"></span>
                                                </button>
                                                <div class="country-code-panel" data-country-code-panel>
                                                    @foreach ($countryDialCodes as $countryDialCode)
                                                        <button
                                                            class="country-code-option"
                                                            type="button"
                                                            data-country-code-option
                                                            data-value="{{ $countryDialCode['dial_code'] }}"
                                                            data-display="{{ $countryDialCode['dial_code'] }}"
                                                            onclick="(function(btn){var dropdown=btn.closest('[data-country-code-dropdown]');if(!dropdown)return;var valueInput=dropdown.querySelector('[data-country-code-value]');var label=dropdown.querySelector('[data-country-code-label]');var trigger=dropdown.querySelector('[data-country-code-trigger]');if(valueInput){valueInput.value=btn.dataset.value||'';}if(label){label.textContent=btn.dataset.display||btn.dataset.value||'';}dropdown.classList.remove('is-open');if(trigger){trigger.setAttribute('aria-expanded','false');trigger.focus();}})(this)"
                                                        >
                                                            {{ $countryDialCode['name'] }} ({{ $countryDialCode['dial_code'] }})
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <input type="text" name="primary_mobile" value="{{ $primaryMobileValue }}" placeholder="{{ __('Enter your primary mobile number') }}" aria-label="{{ __('Primary mobile number') }}" readonly>
                                        </div>
                                        @error('primary_mobile') <small>{{ $message }}</small> @enderror
                                    </div>
                                    <div class="wizard-field">
                                        <span>{{ __('Secondary Mobile Number') }}</span>
                                        @php
                                            $secondaryMobileParts = \App\Support\PhoneNumber::split($profile?->secondary_mobile, '+880');
                                            $selectedSecondaryCountryCode = old('secondary_mobile_country_code', $secondaryMobileParts['country_code']);
                                            $secondaryMobileValue = old('secondary_mobile', $secondaryMobileParts['national_number']);
                                        @endphp
                                        <div class="phone-input-group">
                                            <div class="country-code-dropdown" data-country-code-dropdown>
                                                <input type="hidden" name="secondary_mobile_country_code" value="{{ $selectedSecondaryCountryCode }}" data-country-code-value>
                                                <button class="phone-code-trigger" type="button" data-country-code-trigger aria-expanded="false" onclick="(function(btn){var dropdown=btn.closest('[data-country-code-dropdown]');if(!dropdown)return;var isOpen=dropdown.classList.toggle('is-open');btn.setAttribute('aria-expanded',isOpen?'true':'false');})(this)">
                                                    <span data-country-code-label>{{ $selectedSecondaryCountryCode }}</span>
                                                    <span class="phone-code-trigger-icon" aria-hidden="true"></span>
                                                </button>
                                                <div class="country-code-panel" data-country-code-panel>
                                                    @foreach ($countryDialCodes as $countryDialCode)
                                                        <button
                                                            class="country-code-option"
                                                            type="button"
                                                            data-country-code-option
                                                            data-value="{{ $countryDialCode['dial_code'] }}"
                                                            data-display="{{ $countryDialCode['dial_code'] }}"
                                                            onclick="(function(btn){var dropdown=btn.closest('[data-country-code-dropdown]');if(!dropdown)return;var valueInput=dropdown.querySelector('[data-country-code-value]');var label=dropdown.querySelector('[data-country-code-label]');var trigger=dropdown.querySelector('[data-country-code-trigger]');if(valueInput){valueInput.value=btn.dataset.value||'';}if(label){label.textContent=btn.dataset.display||btn.dataset.value||'';}dropdown.classList.remove('is-open');if(trigger){trigger.setAttribute('aria-expanded','false');trigger.focus();}})(this)"
                                                        >
                                                            {{ $countryDialCode['name'] }} ({{ $countryDialCode['dial_code'] }})
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <input type="text" name="secondary_mobile" value="{{ $secondaryMobileValue }}" placeholder="{{ __('Enter your secondary mobile number') }}" aria-label="{{ __('Secondary mobile number') }}">
                                        </div>
                                        @error('secondary_mobile') <small>{{ $message }}</small> @enderror
                                    </div>
                                    <div class="wizard-field">
                                        <span>{{ __('WhatsApp Number') }}</span>
                                        @php
                                            $whatsappMobileParts = \App\Support\PhoneNumber::split($profile?->whatsapp_number, '+880');
                                            $selectedWhatsappCountryCode = old('whatsapp_country_code', $whatsappMobileParts['country_code']);
                                            $whatsappMobileValue = old('whatsapp_number', $whatsappMobileParts['national_number']);
                                        @endphp
                                        <div class="phone-input-group">
                                            <div class="country-code-dropdown" data-country-code-dropdown>
                                                <input type="hidden" name="whatsapp_country_code" value="{{ $selectedWhatsappCountryCode }}" data-country-code-value>
                                                <button class="phone-code-trigger" type="button" data-country-code-trigger aria-expanded="false" onclick="(function(btn){var dropdown=btn.closest('[data-country-code-dropdown]');if(!dropdown)return;var isOpen=dropdown.classList.toggle('is-open');btn.setAttribute('aria-expanded',isOpen?'true':'false');})(this)">
                                                    <span data-country-code-label>{{ $selectedWhatsappCountryCode }}</span>
                                                    <span class="phone-code-trigger-icon" aria-hidden="true"></span>
                                                </button>
                                                <div class="country-code-panel" data-country-code-panel>
                                                    @foreach ($countryDialCodes as $countryDialCode)
                                                        <button
                                                            class="country-code-option"
                                                            type="button"
                                                            data-country-code-option
                                                            data-value="{{ $countryDialCode['dial_code'] }}"
                                                            data-display="{{ $countryDialCode['dial_code'] }}"
                                                            onclick="(function(btn){var dropdown=btn.closest('[data-country-code-dropdown]');if(!dropdown)return;var valueInput=dropdown.querySelector('[data-country-code-value]');var label=dropdown.querySelector('[data-country-code-label]');var trigger=dropdown.querySelector('[data-country-code-trigger]');if(valueInput){valueInput.value=btn.dataset.value||'';}if(label){label.textContent=btn.dataset.display||btn.dataset.value||'';}dropdown.classList.remove('is-open');if(trigger){trigger.setAttribute('aria-expanded','false');trigger.focus();}})(this)"
                                                        >
                                                            {{ $countryDialCode['name'] }} ({{ $countryDialCode['dial_code'] }})
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <input type="text" name="whatsapp_number" value="{{ $whatsappMobileValue }}" placeholder="{{ __('Enter your WhatsApp number') }}" aria-label="{{ __('WhatsApp number') }}">
                                        </div>
                                        <div class="inline-checkbox-option" data-whatsapp-sync-wrapper>
                                            <div class="inline-checkbox-control">
                                                <input
                                                    type="checkbox"
                                                    value="1"
                                                    aria-label="{{ __('Use primary mobile number for WhatsApp') }}"
                                                    data-whatsapp-same-toggle
                                                    data-primary-source="primary_mobile"
                                                    data-whatsapp-target="whatsapp_number"
                                                    @checked($whatsappMobileValue && $whatsappMobileValue === $primaryMobileValue)
                                                >
                                                <span class="inline-checkbox-box" aria-hidden="true"></span>
                                                <span class="inline-checkbox-copy">
                                                    <strong>{{ __('Use primary mobile number') }}</strong>
                                                    <small data-whatsapp-sync-note>{{ __('Synced from primary mobile') }}</small>
                                                </span>
                                            </div>
                                        </div>
                                        @error('whatsapp_number') <small>{{ $message }}</small> @enderror
                                    </div>
                                    <div class="wizard-field">
                                        <span>{{ __('Email Address') }}</span>
                                        <input type="email" name="email_address" value="{{ old('email_address', $profile?->email_address ?? $user->email) }}" placeholder="{{ __('Enter your email address') }}" aria-label="{{ __('Email address') }}" readonly>
                                        @error('email_address') <small>{{ $message }}</small> @enderror
                                    </div>
                                    <div class="wizard-field label-wide">
                                        <span>{{ __('Present Address') }}</span>
                                        <textarea name="present_address" rows="4" placeholder="{{ __('Enter your present address') }}" aria-label="{{ __('Present address') }}">{{ old('present_address', $profile?->present_address) }}</textarea>
                                        @error('present_address') <small>{{ $message }}</small> @enderror
                                    </div>
                                    <div class="wizard-field label-wide">
                                        <span>{{ __('Permanent Address') }}</span>
                                        <textarea name="permanent_address" rows="4" placeholder="{{ __('Enter your permanent address') }}" aria-label="{{ __('Permanent address') }}">{{ old('permanent_address', $profile?->permanent_address) }}</textarea>
                                        <div class="inline-checkbox-option" data-field-sync-wrapper>
                                            <div class="inline-checkbox-control">
                                                <input
                                                    type="checkbox"
                                                    value="1"
                                                    aria-label="{{ __('Use present address as permanent address') }}"
                                                    data-field-sync-toggle
                                                    data-source-field="present_address"
                                                    data-target-field="permanent_address"
                                                    @checked(old('present_address', $profile?->present_address) && old('present_address', $profile?->present_address) === old('permanent_address', $profile?->permanent_address))
                                                >
                                                <span class="inline-checkbox-box" aria-hidden="true"></span>
                                                <span class="inline-checkbox-copy">
                                                    <strong>{{ __('Same as present address') }}</strong>
                                                    <small data-field-sync-note>{{ __('Synced from present address') }}</small>
                                                </span>
                                            </div>
                                        </div>
                                        @error('permanent_address') <small>{{ $message }}</small> @enderror
                                    </div>
                                    <div class="wizard-field">
                                        <span>{{ __('Country') }}</span>
                                        <select name="country" aria-label="{{ __('Country') }}">
                                            <option value="Bangladesh" selected>{{ __('Bangladesh') }}</option>
                                        </select>
                                        @error('country') <small>{{ $message }}</small> @enderror
                                    </div>
                                    <div class="wizard-field">
                                        <span>{{ __('City / District') }}</span>
                                        <select name="city_district" aria-label="{{ __('City or district') }}">
                                            <option value="">{{ __('Select district') }}</option>
                                            @foreach ($districts as $option)
                                                <option value="{{ $option }}" @selected(old('city_district', $profile?->city_district ?? $profile?->current_city) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('city_district') <small>{{ $message }}</small> @enderror
                                    </div>
                                    <div class="wizard-field">
                                        <span>{{ __('Postal Code') }}</span>
                                        <input type="text" name="postal_code" value="{{ old('postal_code', $profile?->postal_code) }}" placeholder="{{ __('Enter your postal code') }}" aria-label="{{ __('Postal code') }}">
                                        @error('postal_code') <small>{{ $message }}</small> @enderror
                                    </div>
                                </div>
                            @elseif ($number === 5)
                                <div class="wizard-form-stack">
                                    <label><span>{{ __('Occupation') }}</span><select name="occupation"><option value="">{{ __('Select occupation') }}</option>@foreach ($occupations as $option)<option value="{{ $option }}" @selected(old('occupation', $profile?->occupation) === $option)>{{ __($option) }}</option>@endforeach</select>@error('occupation') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>{{ __('Organization / Company Name') }}</span><input type="text" name="organization_name" value="{{ old('organization_name', $profile?->organization_name) }}" placeholder="{{ __('Enter your company or organization name') }}">@error('organization_name') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>{{ __('Designation / Job Title') }}</span><select name="designation"><option value="">{{ __('Select designation') }}</option>@foreach ($designations as $option)<option value="{{ $option }}" @selected(old('designation', $profile?->designation) === $option)>{{ __($option) }}</option>@endforeach</select>@error('designation') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>{{ __('Industry') }}</span><select name="industry"><option value="">{{ __('Select industry') }}</option>@foreach ($industries as $option)<option value="{{ $option }}" @selected(old('industry', $profile?->industry) === $option)>{{ __($option) }}</option>@endforeach</select>@error('industry') <small>{{ $message }}</small> @enderror</label>
                                    <label class="label-wide"><span>{{ __('Office Address') }}</span><textarea name="office_address" rows="4" placeholder="{{ __('Enter your office address') }}">{{ old('office_address', $profile?->office_address) }}</textarea>@error('office_address') <small>{{ $message }}</small> @enderror</label>
                                </div>
                            @elseif ($number === 6)
                                <div class="wizard-form-stack">
                                    <label>
                                        <span>{{ __('Profile Photo') }}</span>
                                        <div class="image-preview-field">
                                            @php
                                                $profilePhotoExists = filled($profile?->profile_photo) && \Illuminate\Support\Facades\Storage::disk('public')->exists($profile->profile_photo);
                                            @endphp
                                            <div class="image-preview-box image-preview-box-square" data-image-preview-box>
                                                <button class="image-remove-button @if (! $profilePhotoExists) is-hidden @endif" type="button" data-remove-image-button="profile_photo" aria-label="{{ __('Remove profile photo') }}">×</button>
                                                <input type="hidden" name="remove_profile_photo" value="0" data-remove-image-input="profile_photo">
                                                @if ($profilePhotoExists)
                                                    <img
                                                        src="{{ asset('storage/'.$profile->profile_photo) }}"
                                                        alt="{{ __('Profile photo preview') }}"
                                                        data-image-preview-target="profile_photo"
                                                    >
                                                @else
                                                    <span class="image-preview-placeholder" data-image-preview-target="profile_photo">{{ __('Photo Preview') }}</span>
                                                @endif
                                            </div>
                                            <input class="visually-hidden-file-input" type="file" name="profile_photo" accept="image/*" data-image-preview-input="profile_photo" id="profile-photo-input">
                                            <button class="button button-secondary image-upload-trigger" type="button" data-image-upload-trigger="profile-photo-input">{{ __('Choose Profile Photo') }}</button>
                                        </div>
                                        <small>{{ __('Upload a clear passport-size or professional photo') }}</small>
                                        @error('profile_photo') <small>{{ $message }}</small> @enderror
                                        @error('remove_profile_photo') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>{{ __('Cover Photo') }}</span>
                                        <div class="image-preview-field">
                                            @php
                                                $coverPhotoExists = filled($profile?->cover_photo) && \Illuminate\Support\Facades\Storage::disk('public')->exists($profile->cover_photo);
                                            @endphp
                                            <div class="image-preview-box image-preview-box-cover" data-image-preview-box>
                                                <button class="image-remove-button @if (! $coverPhotoExists) is-hidden @endif" type="button" data-remove-image-button="cover_photo" aria-label="{{ __('Remove cover photo') }}">×</button>
                                                <input type="hidden" name="remove_cover_photo" value="0" data-remove-image-input="cover_photo">
                                                @if ($coverPhotoExists)
                                                    <img
                                                        src="{{ asset('storage/'.$profile->cover_photo) }}"
                                                        alt="{{ __('Cover photo preview') }}"
                                                        data-image-preview-target="cover_photo"
                                                    >
                                                @else
                                                    <span class="image-preview-placeholder" data-image-preview-target="cover_photo">{{ __('Cover Preview') }}</span>
                                                @endif
                                            </div>
                                            <input class="visually-hidden-file-input" type="file" name="cover_photo" accept="image/*" data-image-preview-input="cover_photo" id="cover-photo-input">
                                            <button class="button button-secondary image-upload-trigger" type="button" data-image-upload-trigger="cover-photo-input">{{ __('Choose Cover Photo') }}</button>
                                        </div>
                                        <small>{{ __('Optional') }}</small>
                                        @error('cover_photo') <small>{{ $message }}</small> @enderror
                                        @error('remove_cover_photo') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label><span>{{ __('Business Card Upload') }}</span><input class="visually-hidden-file-input" type="file" name="business_card_upload" accept=".jpg,.jpeg,.png,.webp,.pdf" id="business-card-input"><button class="button button-secondary image-upload-trigger" type="button" data-image-upload-trigger="business-card-input">{{ __('Choose Business Card') }}</button><small>{{ __('Upload your business card') }}</small>@error('business_card_upload') <small>{{ $message }}</small> @enderror</label>
                                </div>
                                <div class="wizard-form-stack">
                                    <label><span>{{ __('Facebook Profile Link') }}</span><input type="url" name="facebook_profile_link" value="{{ old('facebook_profile_link', $profile?->facebook_profile_link) }}" placeholder="{{ __('Paste your Facebook profile URL') }}">@error('facebook_profile_link') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>{{ __('LinkedIn Profile Link') }}</span><input type="url" name="linkedin_profile_link" value="{{ old('linkedin_profile_link', $profile?->linkedin_profile_link) }}" placeholder="{{ __('Paste your LinkedIn profile URL') }}">@error('linkedin_profile_link') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>{{ __('Website / Portfolio Link') }}</span><input type="url" name="website_portfolio_link" value="{{ old('website_portfolio_link', $profile?->website_portfolio_link) }}" placeholder="{{ __('Paste your website or portfolio URL') }}">@error('website_portfolio_link') <small>{{ $message }}</small> @enderror</label>
                                </div>
                            @elseif ($number === 7)
                                <div class="wizard-form-stack">
                                    <label><span>{{ __('Are you interested in joining alumni activities?') }}</span><select name="interested_in_alumni_activities"><option value="1" @selected((string) old('interested_in_alumni_activities', is_null($profile?->interested_in_alumni_activities) ? '1' : (string) (int) $profile?->interested_in_alumni_activities) === '1')>{{ __('Yes') }}</option><option value="0" @selected((string) old('interested_in_alumni_activities', is_null($profile?->interested_in_alumni_activities) ? '1' : (string) (int) $profile?->interested_in_alumni_activities) === '0')>{{ __('No') }}</option></select>@error('interested_in_alumni_activities') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>{{ __('Would you like to volunteer for alumni programs?') }}</span><select name="volunteer_interest"><option value="1" @selected((string) old('volunteer_interest', is_null($profile?->volunteer_interest) ? '1' : (string) (int) $profile?->volunteer_interest) === '1')>{{ __('Yes') }}</option><option value="0" @selected((string) old('volunteer_interest', is_null($profile?->volunteer_interest) ? '1' : (string) (int) $profile?->volunteer_interest) === '0')>{{ __('No') }}</option></select>@error('volunteer_interest') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>{{ __('Would you like to support alumni initiatives in the future as a donor or sponsor?') }}</span><select name="donor_sponsor_interest"><option value="">{{ __('Select one') }}</option>@foreach (['Yes', 'No', 'Maybe Later'] as $option)<option value="{{ $option }}" @selected(old('donor_sponsor_interest', $profile?->donor_sponsor_interest) === $option)>{{ __($option) }}</option>@endforeach</select>@error('donor_sponsor_interest') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>{{ __('Would you be interested in mentoring current students?') }}</span><select name="mentor_interest"><option value="">{{ __('Select one') }}</option><option value="1" @selected((string) old('mentor_interest', (int) $profile?->mentor_interest) === '1')>{{ __('Yes') }}</option><option value="0" @selected((string) old('mentor_interest', (int) $profile?->mentor_interest) === '0')>{{ __('No') }}</option></select>@error('mentor_interest') <small>{{ $message }}</small> @enderror</label>
                                </div>
                                @php
                                    $selectedInterests = old('areas_of_interest', $profile?->areas_of_interest ?? []);
                                @endphp
                                <div class="wizard-field label-wide">
                                    <span>{{ __('Areas of Interest') }}</span>
                                    <div class="multi-select-dropdown @if ($errors->has('areas_of_interest')) is-open @endif" data-multiselect-dropdown>
                                        <button class="multi-select-trigger" type="button" data-multiselect-trigger aria-expanded="@if ($errors->has('areas_of_interest')) true @else false @endif" onclick="(function(btn){var dropdown=btn.closest('[data-multiselect-dropdown]');if(!dropdown)return;var isOpen=dropdown.classList.toggle('is-open');btn.setAttribute('aria-expanded',isOpen?'true':'false');})(this)">
                                            <span class="multi-select-trigger-label" data-multiselect-label data-placeholder="{{ __('Select areas of interest') }}">
                                                @if (count($selectedInterests) === 0)
                                                    {{ __('Select areas of interest') }}
                                                @elseif (count($selectedInterests) <= 2)
                                                    {{ implode(', ', array_map(fn ($interest) => __($interest), $selectedInterests)) }}
                                                @else
                                                    {{ implode(', ', array_map(fn ($interest) => __($interest), array_slice($selectedInterests, 0, 2))) }} +{{ count($selectedInterests) - 2 }}
                                                @endif
                                            </span>
                                            <span class="multi-select-trigger-icon" aria-hidden="true"></span>
                                        </button>
                                        <div class="multi-select-panel" data-multiselect-panel>
                                            <div class="multi-select-options">
                                                @foreach ($areasOfInterest as $interest)
                                                    <label class="multi-select-option">
                                                        <input type="checkbox" name="areas_of_interest[]" value="{{ $interest }}" data-multiselect-option @checked(in_array($interest, $selectedInterests, true)) onchange="(function(input){var dropdown=input.closest('[data-multiselect-dropdown]');if(!dropdown)return;var label=dropdown.querySelector('[data-multiselect-label]');if(!label)return;var placeholder=label.dataset.placeholder||'Select options';var selected=Array.from(dropdown.querySelectorAll('[data-multiselect-option]:checked')).map(function(option){return option.value;});if(selected.length===0){label.textContent=placeholder;}else if(selected.length<=2){label.textContent=selected.join(', ');}else{label.textContent=selected.slice(0,2).join(', ')+' +'+(selected.length-2);}})(this)">
                                                        <span class="multi-select-check" aria-hidden="true"></span>
                                                        <span class="multi-select-option-copy">{{ __($interest) }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <div class="multi-select-actions">
                                                <button class="multi-select-done" type="button" data-multiselect-done onclick="(function(btn){var dropdown=btn.closest('[data-multiselect-dropdown]');if(!dropdown)return;dropdown.classList.remove('is-open');var trigger=dropdown.querySelector('[data-multiselect-trigger]');if(trigger){trigger.setAttribute('aria-expanded','false');trigger.focus();}})(this)">{{ __('Done') }}</button>
                                            </div>
                                        </div>
                                    </div>
                                    <small>{{ __('Select one or more areas from the dropdown.') }}</small>
                                    @error('areas_of_interest') <small>{{ $message }}</small> @enderror
                                </div>
                                <label><span>{{ __('Suggestions for the Alumni Association') }}</span><textarea name="suggestions" rows="5" placeholder="{{ __('Share your ideas or suggestions') }}">{{ old('suggestions', $profile?->suggestions) }}</textarea>@error('suggestions') <small>{{ $message }}</small> @enderror</label>
                            @elseif ($number === 8)
                                <div class="wizard-form-stack wizard-form-stack-with-labels">
                                    <label><span>{{ __('SSC Certificate / Testimonial / Admit Card') }}</span><input type="file" name="certificate_testimonial_upload">@error('certificate_testimonial_upload') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>{{ __('Supporting Document Upload') }}</span><input type="file" name="supporting_document_upload">@error('supporting_document_upload') <small>{{ $message }}</small> @enderror</label>
                                </div>
                            @elseif ($number === 9)
                                <div class="wizard-form-stack">
                                    <label><span>{{ __('Profile Visibility') }}</span><select name="profile_visibility" required><option value="">{{ __('Select visibility') }}</option>@foreach (['Show my profile in the alumni directory', 'Show my profile only to verified members', 'Keep my profile private'] as $option)<option value="{{ $option }}" @selected(old('profile_visibility', $profile?->profile_visibility) === $option)>{{ __($option) }}</option>@endforeach</select>@error('profile_visibility') <small>{{ $message }}</small> @enderror</label>
                                    <label><span>{{ __('Contact Visibility') }}</span><select name="contact_visibility" required><option value="">{{ __('Select contact visibility') }}</option>@foreach (['Show my contact details to verified members only', 'Keep my contact details private'] as $option)<option value="{{ $option }}" @selected(old('contact_visibility', $profile?->contact_visibility) === $option)>{{ __($option) }}</option>@endforeach</select>@error('contact_visibility') <small>{{ $message }}</small> @enderror</label>
                                </div>
                            @elseif ($number === 10)
                                <div class="declaration-stack">
                                    <label class="checkbox-item declaration-item"><input type="checkbox" name="information_accuracy_confirmation" value="1" @checked(old('information_accuracy_confirmation', $profile?->information_accuracy_confirmation))><span>{{ __('I confirm that the information provided by me is true and correct.') }}</span></label>
                                    @error('information_accuracy_confirmation') <small>{{ $message }}</small> @enderror
                                    <label class="checkbox-item declaration-item"><input type="checkbox" name="terms_privacy_agreement" value="1" @checked(old('terms_privacy_agreement', $profile?->terms_privacy_agreement))><span>{{ __('I agree to the Alumni Association’s terms and privacy policy.') }}</span></label>
                                    @error('terms_privacy_agreement') <small>{{ $message }}</small> @enderror
                                    <label class="checkbox-item declaration-item"><input type="checkbox" name="admin_verification_agreement" value="1" @checked(old('admin_verification_agreement', $profile?->admin_verification_agreement))><span>{{ __('I understand that my profile will remain subject to verification and approval by the admin.') }}</span></label>
                                    @error('admin_verification_agreement') <small>{{ $message }}</small> @enderror
                                </div>
                            @endif

                            <div class="action-row wizard-nav-actions wizard-auth-actions">
                                @if ($number > 2)
                                    <button class="button button-secondary wizard-auth-secondary" type="button" data-step-target="{{ $number - 1 }}">{{ __('Previous') }}</button>
                                @endif
                                @if (!empty($profileLocked))
                                    <a class="button button-secondary wizard-auth-secondary" href="{{ route('member.dashboard') }}">{{ __('Back to Dashboard') }}</a>
                                @elseif ($number >= 2 && $number < 10)
                                    <button class="button button-secondary wizard-auth-secondary" type="submit" name="save_as_draft" value="1" data-draft-action-step="{{ $number }}" @if(!empty($stepCompletion[$number])) hidden @endif>{{ __('Save as Draft') }}</button>
                                    <button class="button button-primary wizard-auth-submit" type="submit" name="next_step" value="{{ $number + 1 }}">{{ __('Continue') }}</button>
                                @elseif ($number === 10)
                                    <button class="button button-primary wizard-auth-submit" type="submit" name="submit_for_verification" value="1">{{ __('Submit for Review') }}</button>
                                @endif
                            </div>
                    </section>
                @endforeach
            </form>
        </div>
    </section>
@endsection
