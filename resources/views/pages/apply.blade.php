@extends('layouts.app')

@section('content')
    @php
        $logoPath = config('site.brand.logo_path');
        $brandName = config('site.brand.name', 'MUBCAA');
    @endphp

    <section class="section join-auth-section min-h-screen flex items-center justify-center">
        <div class="wrap join-auth-wrap w-full max-w-md px-4">
            <form class="form-card registration-form-card join-auth-card w-full space-y-3" method="POST" action="{{ route('membership.apply.store') }}" data-ajax-form="registration">
                @csrf

                <div class="join-auth-brand">
                    @if ($logoPath)
                        <span class="join-auth-logo">
                            <img src="{{ asset($logoPath) }}" alt="{{ $brandName }} logo">
                        </span>
                    @else
                        <span class="join-auth-logo join-auth-logo-fallback">M</span>
                    @endif
                    <p class="join-auth-brand-name">{{ $brandName }}</p>
                </div>

                <div class="join-auth-copy">
                    <p class="panel-card-label">Step 1: Basic Info</p>
                    <h1>Join MUBCAA</h1>
                </div>

                @if (! empty($affiliateReferrer))
                    <div class="registration-referral-banner join-auth-referral-banner">
                        <strong>Referred by {{ $affiliateReferrer->name }}</strong>
                        <span>Referral code: {{ $affiliateReferrer->affiliate_code }}</span>
                    </div>
                @endif

                <div class="form-grid join-auth-form-grid grid grid-cols-1 gap-3">
                    <div class="join-auth-label">
                        <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="Enter your full name" aria-label="Full name" required>
                        @error('full_name') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="join-auth-label">
                        @php
                            $selectedMobileCountryCode = old('mobile_country_code', '+880');
                        @endphp
                        <div class="phone-input-group">
                            <div class="country-code-dropdown" data-country-code-dropdown>
                                <input type="hidden" name="mobile_country_code" value="{{ $selectedMobileCountryCode }}" data-country-code-value>
                                <button class="phone-code-trigger" type="button" data-country-code-trigger aria-expanded="false" onclick="(function(btn){var dropdown=btn.closest('[data-country-code-dropdown]');if(!dropdown)return;var isOpen=dropdown.classList.toggle('is-open');btn.setAttribute('aria-expanded',isOpen?'true':'false');})(this)">
                                    <span data-country-code-label>{{ $selectedMobileCountryCode }}</span>
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
                                            onclick="(function(btn){var dropdown=btn.closest('[data-country-code-dropdown]');if(!dropdown)return;var valueInput=dropdown.querySelector('[data-country-code-value]');var label=dropdown.querySelector('[data-country-code-label]');var trigger=dropdown.querySelector('[data-country-code-trigger]');if(valueInput){valueInput.value=btn.dataset.value||'';valueInput.dispatchEvent(new Event('change',{bubbles:true}));}if(label){label.textContent=btn.dataset.display||btn.dataset.value||'';}dropdown.classList.remove('is-open');if(trigger){trigger.setAttribute('aria-expanded','false');trigger.focus();}})(this)"
                                        >
                                            {{ $countryDialCode['name'] }} ({{ $countryDialCode['dial_code'] }})
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <input type="text" name="mobile_number" value="{{ old('mobile_number') }}" placeholder="Enter your mobile number" aria-label="Mobile number" required data-registration-check-input data-registration-check-field="mobile_number" data-registration-check-url="{{ route('membership.apply.check') }}">
                        </div>
                        <small class="field-live-status" data-registration-check-status hidden></small>
                        @error('mobile_number') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="join-auth-label">
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email address" aria-label="Email address" required data-registration-check-input data-registration-check-field="email" data-registration-check-url="{{ route('membership.apply.check') }}">
                        <small class="field-live-status" data-registration-check-status hidden></small>
                        @error('email') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="join-auth-label">
                        <select name="passing_year_batch" aria-label="Passing year or batch" required>
                            <option value="">Passing Year / Batch</option>
                            @foreach ($passingYears as $option)
                                <option value="{{ $option }}" @selected(old('passing_year_batch') === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('passing_year_batch') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="join-auth-label" data-discovery-field-wrapper>
                        <div class="join-auth-field-reset-row">
                            <button class="text-link-button text-link-inline registration-field-reset" type="button" data-referral-code-reset @if (old('discovery_source') !== 'Referral Code') hidden @endif>Choose another source</button>
                        </div>
                        <select name="discovery_source" aria-label="How did you find us" data-discovery-source-select @if (old('discovery_source') === 'Referral Code') hidden @endif>
                            <option value="">How did you find us?</option>
                            @foreach ($discoverySources as $option)
                                <option value="{{ $option }}" @selected(old('discovery_source') === $option)>{{ $option }}</option>
                            @endforeach
                            <option value="Referral Code" @selected(old('discovery_source') === 'Referral Code')>Referral Code</option>
                        </select>
                        <input type="text" name="referral_code" value="{{ old('referral_code') }}" placeholder="Enter referral code" aria-label="Referral code" data-referral-code-input data-registration-check-input data-registration-check-field="referral_code" data-registration-check-url="{{ route('membership.apply.check') }}" @if (old('discovery_source') !== 'Referral Code') hidden @endif>
                        <small class="field-live-status" data-registration-check-status hidden></small>
                        @error('discovery_source') <small>{{ $message }}</small> @enderror
                        @error('referral_code') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="join-auth-label">
                        <div class="captcha-inline">
                            <input type="hidden" name="captcha_left" value="{{ $captchaLeft }}">
                            <input type="hidden" name="captcha_right" value="{{ $captchaRight }}">
                            <span class="captcha-chip">{{ $captchaLeft }} + {{ $captchaRight }} = ?</span>
                            <input type="text" name="captcha_answer" value="{{ old('captcha_answer') }}" placeholder="Enter answer" aria-label="Captcha answer" required>
                        </div>
                        @error('captcha_answer') <small>{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="action-row join-auth-actions flex flex-col gap-3">
                    <button class="button button-primary button-loading-trigger join-auth-submit" type="submit" data-loading-text="Sending OTP...">
                        <span class="button-loading-label">Continue to OTP Verification</span>
                    </button>
                    <a class="button button-secondary join-auth-secondary" href="{{ route('login') }}">Already have an account? Log In</a>
                </div>
            </form>
        </div>
    </section>

@endsection
