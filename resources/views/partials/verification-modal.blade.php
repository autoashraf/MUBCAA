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
                        <p class="eyebrow">Verification Complete</p>
                    </div>
                </header>

                <div class="verification-layout verification-layout-single">
                        <div class="verification-panels">
                            <div class="form-card verification-panel verification-panel-complete">
                                @if (! empty($verificationSuccessMessage))
                                    <p class="dashboard-copy">{{ $verificationSuccessMessage }}</p>
                                @endif
                            <div class="action-row verification-action-row">
                                <a class="button button-primary" href="{{ $verificationContinueUrl }}">Continue Registration</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="button button-secondary" type="submit">Exit</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <footer class="verification-modal-footer">
                    <span>Secure verification complete</span>
                    <span>{{ config('site.brand.name', 'MUBCAA') }}</span>
                </footer>
            </div>
        @else
            <div class="verification-modal">
                <header class="verification-modal-header">
                    <div>
                        <p class="eyebrow">Contact Verification</p>
                        <h1>Verify your email and mobile number</h1>
                        <p class="verification-step-current">Step {{ $currentStepNumber }} of 2</p>
                    </div>
                    <a class="mini-link" href="{{ route('membership.apply') }}" data-close-verification-modal>Close</a>
                </header>

                <div class="verification-layout verification-layout-single">
                    <div class="verification-panels">
                        <div class="verification-stepper" aria-label="Verification progress">
                            <div class="verification-step {{ $emailVerified ? 'is-complete' : 'is-active' }}">
                                <span class="verification-step-index">1</span>
                                <div class="verification-step-copy">
                                    <strong>Email</strong>
                                    <small>{{ $emailVerified ? 'Verified' : 'Current step' }}</small>
                                </div>
                            </div>
                            <div class="verification-step {{ $mobileVerified ? 'is-complete' : ($emailVerified ? 'is-active' : '') }}">
                                <span class="verification-step-index">2</span>
                                <div class="verification-step-copy">
                                    <strong>Mobile</strong>
                                    <small>{{ $mobileVerified ? 'Verified' : ($emailVerified ? 'Current step' : 'Upcoming') }}</small>
                                </div>
                            </div>
                        </div>

                        @if (! $emailVerified)
                            <div class="form-card verification-panel verification-panel-step">
                                <div class="dashboard-form-head verification-panel-head">
                                    <div>
                                        <p class="panel-card-label">Step 1</p>
                                        <h3>Email Verification</h3>
                                    </div>
                                    <span class="verification-panel-icon">E</span>
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
                                        <small class="verification-expiry-note" data-expiry-countdown data-expiry-seconds="{{ $emailExpiryCountdown ?? 0 }}">Code expires in 00:00</small>
                                    </label>
                                    <div class="action-row verification-action-row verification-inline-actions">
                                        <button class="button button-primary" type="submit">Verify Email</button>
                                        <button class="text-link-button text-link-inline" type="submit" form="resend-email-form" data-resend-button data-resend-seconds="{{ $emailResendCooldown ?? 0 }}" data-resend-label="Resend email code">Resend email code</button>
                                    </div>
                                </form>
                                <form id="resend-email-form" method="POST" action="{{ route('member.verification.resend', 'email') }}" data-ajax-form="verification">
                                    @csrf
                                </form>
                            </div>

                            <div class="verification-step-pending">
                                <strong>Next:</strong> Verify your mobile number after email verification is complete.
                            </div>
                        @else
                            <div class="form-card verification-panel verification-panel-step">
                                <div class="dashboard-form-head verification-panel-head">
                                    <div>
                                        <p class="panel-card-label">Step 2</p>
                                        <h3>Mobile Verification</h3>
                                    </div>
                                    <span class="verification-panel-icon">M</span>
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
                                        <small class="verification-expiry-note" data-expiry-countdown data-expiry-seconds="{{ $mobileExpiryCountdown ?? 0 }}">Code expires in 00:00</small>
                                    </label>
                                    <div class="action-row verification-action-row verification-inline-actions">
                                        <button class="button button-primary" type="submit">Verify Mobile</button>
                                        <button class="text-link-button text-link-inline" type="submit" form="resend-mobile-form" data-resend-button data-resend-seconds="{{ $mobileResendCooldown ?? 0 }}" data-resend-label="Resend mobile code">Resend mobile code</button>
                                    </div>
                                </form>
                                <form id="resend-mobile-form" method="POST" action="{{ route('member.verification.resend', 'mobile') }}" data-ajax-form="verification">
                                    @csrf
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
                <footer class="verification-modal-footer">
                    <span>Secure OTP verification</span>
                    <span>{{ config('site.brand.name', 'MUBCAA') }}</span>
                </footer>
            </div>
        @endif
    </div>
</section>
