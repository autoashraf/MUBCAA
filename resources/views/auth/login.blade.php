@extends('layouts.app')

@section('content')
    @php
        $logoPath = config('site.brand.logo_path');
        $brandName = config('site.brand.name', 'MUBCAA');
    @endphp

    <section class="section login-auth-section">
        <div class="wrap login-auth-wrap">
            <form class="login-auth-card" method="POST" action="{{ route('login.attempt') }}">
                <div class="login-auth-brand">
                    @if ($logoPath)
                        <span class="login-auth-logo">
                            <img src="{{ asset($logoPath) }}" alt="{{ $brandName }} logo">
                        </span>
                    @else
                        <span class="login-auth-logo login-auth-logo-fallback">M</span>
                    @endif
                    <p class="login-auth-brand-name">{{ $brandName }}</p>
                </div>

                <div class="login-auth-copy">
                    <h1>Member Sign In</h1>
                    <p>Use your email or mobile to get an OTP.</p>
                </div>

                <div class="login-auth-form">
                    @csrf
                    <input type="hidden" name="login_type" value="member">
                    @php
                        $selectedLoginChannel = old('login_channel', 'mobile');
                        $selectedLoginCountryCode = old('mobile_country_code', '+880');
                    @endphp
                    <input type="hidden" name="login_channel" value="{{ $selectedLoginChannel }}" data-login-channel-input>

                    <div class="login-auth-tabs" data-login-method-tabs>
                        <button class="login-auth-tab @if ($selectedLoginChannel === 'mobile') is-active @endif" type="button" data-login-method-tab="mobile" aria-pressed="{{ $selectedLoginChannel === 'mobile' ? 'true' : 'false' }}">SMS OTP</button>
                        <button class="login-auth-tab @if ($selectedLoginChannel === 'email') is-active @endif" type="button" data-login-method-tab="email" aria-pressed="{{ $selectedLoginChannel === 'email' ? 'true' : 'false' }}">Email OTP</button>
                    </div>

                    <div class="login-auth-panel" data-login-panel="mobile" @if ($selectedLoginChannel !== 'mobile') hidden @endif>
                        <label class="login-auth-label">
                            <span>Mobile number</span>
                            <div class="phone-input-group">
                                <div class="country-code-dropdown" data-country-code-dropdown>
                                    <input type="hidden" name="mobile_country_code" value="{{ $selectedLoginCountryCode }}" data-country-code-value data-login-country-code-input>
                                    <button class="phone-code-trigger" type="button" data-country-code-trigger aria-expanded="false" onclick="(function(btn){var dropdown=btn.closest('[data-country-code-dropdown]');if(!dropdown)return;var isOpen=dropdown.classList.toggle('is-open');btn.setAttribute('aria-expanded',isOpen?'true':'false');})(this)">
                                        <span data-country-code-label>{{ $selectedLoginCountryCode }}</span>
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
                                <input class="login-auth-input" type="text" name="mobile_identifier" value="{{ old('mobile_identifier') }}" placeholder="Enter your registered mobile number" data-login-identifier-input data-login-channel="mobile" data-login-check-url="{{ route('login.check') }}" @if ($selectedLoginChannel === 'mobile') required @endif>
                            </div>
                            <small class="login-identifier-status" data-login-identifier-status hidden></small>
                            @error('mobile_identifier') <small>{{ $message }}</small> @enderror
                        </label>
                    </div>

                    <div class="login-auth-panel" data-login-panel="email" @if ($selectedLoginChannel !== 'email') hidden @endif>
                        <label class="login-auth-label">
                            <span>Email address</span>
                            <input class="login-auth-input" type="email" name="email_identifier" value="{{ old('email_identifier') }}" placeholder="Enter your registered email address" data-login-identifier-input data-login-channel="email" data-login-check-url="{{ route('login.check') }}" @if ($selectedLoginChannel === 'email') required @endif>
                            <small class="login-identifier-status" data-login-identifier-status hidden></small>
                            @error('email_identifier') <small>{{ $message }}</small> @enderror
                        </label>
                    </div>

                    <div class="login-auth-actions">
                        <button class="button button-primary login-auth-submit" type="submit">Send OTP</button>
                        <a class="button button-secondary login-auth-secondary" href="{{ route('membership.apply') }}">Join</a>
                    </div>
                </div>
            </form>
        </div>
    </section>

    @if ($loginOtpPending)
        <section class="verification-modal-page login-otp-modal-page">
            <div class="verification-modal-backdrop"></div>
            <div class="wrap">
                <div class="verification-modal login-otp-modal">
                    <div class="login-otp-modal-close-row">
                        <a class="mini-link" href="{{ route('login', ['close_otp' => 1]) }}">Close</a>
                    </div>
                    <form class="form-card login-card login-card-otp" method="POST" action="{{ route('login.verify') }}">
                        @csrf

                        <div class="dashboard-form-head login-card-head">
                            <div>
                                <p class="panel-card-label">OTP Verification</p>
                                <h2>Enter sign-in code</h2>
                            </div>
                            <span class="verification-panel-icon">{{ $loginOtpChannel === 'email' ? 'E' : 'M' }}</span>
                        </div>

                        <p class="dashboard-copy">OTP sent to <strong>{{ $loginOtpContact }}</strong></p>
                        @if (! empty($localLoginOtpCode))
                            <div class="alert-success alert-warning-like">
                                Local OTP Debug: {{ $localLoginOtpCode }}
                            </div>
                        @endif

                        <label class="otp-field">
                            <input type="hidden" name="code">
                            <div class="otp-digit-group" data-otp-group>
                                @for ($i = 0; $i < 6; $i++)
                                    <input class="otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code">
                                @endfor
                            </div>
                            <small class="verification-expiry-note" data-expiry-countdown data-expiry-seconds="{{ $loginOtpExpiryCountdown ?? 0 }}">Code expires in 00:00</small>
                            @error('code') <small>{{ $message }}</small> @enderror
                        </label>

                        <div class="action-row verification-action-row verification-inline-actions">
                            <button class="button button-primary" type="submit">Verify and Login</button>
                            <button class="text-link-button text-link-inline" type="submit" form="resend-login-form" data-resend-button data-resend-seconds="{{ $loginOtpResendCooldown ?? 0 }}" data-resend-label="Resend OTP">Resend OTP</button>
                        </div>
                    </form>

                    <form id="resend-login-form" method="POST" action="{{ route('login.resend') }}">
                        @csrf
                    </form>
                </div>
            </div>
        </section>
    @endif
@endsection
