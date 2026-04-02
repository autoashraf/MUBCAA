<section class="verification-modal-page">
    <div class="verification-modal-backdrop"></div>
    <div class="wrap">
        @php
            $currentChannel = $emailVerified ? 'mobile' : 'email';
            $currentStepNumber = $currentChannel === 'email' ? 1 : 2;
        @endphp

        @if ($emailVerified && $mobileVerified)
            <div class="verification-modal verification-modal-complete">
                <header class="verification-modal-header verification-modal-header-complete">
                    <div>
                        <p class="eyebrow">{{ __('Verification Complete') }}</p>
                    </div>
                </header>

                <div class="verification-layout verification-layout-single">
                        <div class="verification-panels">
                            <div class="form-card verification-panel verification-panel-complete">
                                @if (! empty($verificationSuccessMessage))
                                    <p class="dashboard-copy">{{ $verificationSuccessMessage }}</p>
                                @endif
                            @if (! empty($localOtpCodes))
                                <div class="alert-success alert-warning-like">
                                    {{ __('Local OTP Debug') }}:
                                    @if (! empty($localOtpCodes['email'])) {{ __('Email') }} {{ $localOtpCodes['email'] }} @endif
                                    @if (! empty($localOtpCodes['mobile'])) {{ __('Mobile') }} {{ $localOtpCodes['mobile'] }} @endif
                                </div>
                            @endif
                            <div class="action-row verification-action-row">
                                <a class="button button-primary" href="{{ $verificationContinueUrl }}">{{ __('Continue Registration') }}</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="button button-secondary" type="submit">{{ __('Exit') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="verification-modal">
                <header class="verification-modal-header">
                    <div>
                        <p class="eyebrow">{{ __('Contact Verification') }}</p>
                        <h1>{{ __('Verify your email and mobile number') }}</h1>
                    </div>
                    <a class="mini-link" href="{{ route('membership.apply') }}" data-close-verification-modal>{{ __('Close') }}</a>
                </header>

                <div class="verification-layout verification-layout-single">
                    <div class="verification-panels">
                        @if (! empty($localOtpCodes))
                            <div class="alert-success alert-warning-like">
                                {{ __('Local OTP Debug') }}:
                                @if (! empty($localOtpCodes['email'])) {{ __('Email') }} {{ $localOtpCodes['email'] }} @endif
                                @if (! empty($localOtpCodes['mobile'])) {{ __('Mobile') }} {{ $localOtpCodes['mobile'] }} @endif
                            </div>
                        @endif
                        <div class="verification-stepper" aria-label="{{ __('Verification progress') }}">
                            <div class="verification-step {{ $emailVerified ? 'is-complete' : 'is-active' }}">
                                <span class="verification-step-index">1</span>
                                <div class="verification-step-copy">
                                    <strong>{{ __('Email') }}</strong>
                                </div>
                            </div>
                            <div class="verification-step {{ $mobileVerified ? 'is-complete' : ($emailVerified ? 'is-active' : '') }}">
                                <span class="verification-step-index">2</span>
                                <div class="verification-step-copy">
                                    <strong>{{ __('Mobile') }}</strong>
                                </div>
                            </div>
                        </div>

                        @if (! $emailVerified)
                            <div class="form-card verification-panel verification-panel-step">
                                <div class="dashboard-form-head verification-panel-head">
                                    <div>
                                        <h3>{{ __('Email Verification') }}</h3>
                                        <p class="dashboard-copy">{{ __('An email OTP has been sent to :email.', ['email' => $verificationEmail]) }}</p>
                                    </div>
                                </div>

                                <form id="verify-email-form" method="POST" action="{{ route('member.verification.email') }}" data-ajax-form="verification">
                                    @csrf
                                    <label class="otp-field">
                                        <input type="hidden" name="code">
                                        <div class="otp-digit-group" data-otp-group>
                                            @for ($i = 0; $i < 6; $i++)
                                                <input class="otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code">
                                            @endfor
                                        </div>
                                        <small class="verification-expiry-note" data-expiry-countdown data-expiry-seconds="{{ $emailExpiryCountdown ?? 0 }}">{{ __('Code expires in 00:00') }}</small>
                                    </label>
                                    <div class="action-row verification-action-row verification-inline-actions">
                                        <button class="button button-primary" type="submit">{{ __('Verify Email') }}</button>
                                        <button class="text-link-button text-link-inline" type="submit" form="resend-email-form" data-resend-button data-resend-seconds="{{ $emailResendCooldown ?? 0 }}" data-resend-label="{{ __('Resend email code') }}">{{ __('Resend email code') }}</button>
                                    </div>
                                </form>
                                <form id="resend-email-form" method="POST" action="{{ route('member.verification.resend', 'email') }}" data-ajax-form="verification">
                                    @csrf
                                </form>
                            </div>
                        @else
                            <div class="form-card verification-panel verification-panel-step">
                                <div class="dashboard-form-head verification-panel-head">
                                    <div>
                                        <h3>{{ __('Mobile Verification') }}</h3>
                                        <p class="dashboard-copy">{{ __('An SMS OTP has been sent to :mobile.', ['mobile' => $verificationMobile]) }}</p>
                                    </div>
                                </div>

                                <form id="verify-mobile-form" method="POST" action="{{ route('member.verification.mobile') }}" data-ajax-form="verification">
                                    @csrf
                                    <label class="otp-field">
                                        <input type="hidden" name="code">
                                        <div class="otp-digit-group" data-otp-group>
                                            @for ($i = 0; $i < 6; $i++)
                                                <input class="otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code">
                                            @endfor
                                        </div>
                                        <small class="verification-expiry-note" data-expiry-countdown data-expiry-seconds="{{ $mobileExpiryCountdown ?? 0 }}">{{ __('Code expires in 00:00') }}</small>
                                    </label>
                                    <div class="action-row verification-action-row verification-inline-actions">
                                        <button class="button button-primary" type="submit">{{ __('Verify Mobile') }}</button>
                                        <button class="text-link-button text-link-inline" type="submit" form="resend-mobile-form" data-resend-button data-resend-seconds="{{ $mobileResendCooldown ?? 0 }}" data-resend-label="{{ __('Resend mobile code') }}">{{ __('Resend mobile code') }}</button>
                                    </div>
                                </form>
                                <form id="resend-mobile-form" method="POST" action="{{ route('member.verification.resend', 'mobile') }}" data-ajax-form="verification">
                                    @csrf
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
