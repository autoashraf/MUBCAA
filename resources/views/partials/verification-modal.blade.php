<section class="verification-modal-page">
    <div class="verification-modal-backdrop"></div>
    <div class="wrap">
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
                            <div class="verification-complete-badge">Verified</div>
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
            </div>
        @else
            <div class="verification-modal">
                <header class="verification-modal-header">
                    <div>
                        <p class="eyebrow">Contact Verification</p>
                        <h1>Verify your email and mobile number</h1>
                    </div>
                    <a class="mini-link" href="{{ route('membership.apply') }}" data-close-verification-modal>Close</a>
                </header>

                <div class="verification-layout verification-layout-single">
                    <div class="verification-panels">
                        <div class="form-card verification-panel">
                            <div class="dashboard-form-head verification-panel-head">
                                <div>
                                    <p class="panel-card-label">Email Verification</p>
                                </div>
                                <span class="verification-panel-icon">E</span>
                            </div>

                            @if ($emailVerified)
                                <div class="verification-complete-badge">Verified</div>
                            @else
                                <form id="verify-email-form" method="POST" action="{{ route('member.verification.email') }}" data-ajax-form="verification">
                                    @csrf
                                    <label class="otp-field">
                                        <input type="hidden" name="code">
                                        <div class="otp-digit-group" data-otp-group>
                                            @for ($i = 0; $i < 6; $i++)
                                                <input class="otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code">
                                            @endfor
                                        </div>
                                    </label>
                                    <div class="action-row verification-action-row verification-inline-actions">
                                        <button class="button button-primary" type="submit">Verify Email</button>
                                        <button class="text-link-button text-link-inline" type="submit" form="resend-email-form">Resend email code</button>
                                    </div>
                                </form>
                                <form id="resend-email-form" method="POST" action="{{ route('member.verification.resend', 'email') }}" data-ajax-form="verification">
                                    @csrf
                                </form>
                            @endif
                        </div>

                        <div class="form-card verification-panel">
                            <div class="dashboard-form-head verification-panel-head">
                                <div>
                                    <p class="panel-card-label">Mobile Verification</p>
                                </div>
                                <span class="verification-panel-icon">M</span>
                            </div>

                            @if ($mobileVerified)
                                <div class="verification-complete-badge">Verified</div>
                            @else
                                <form id="verify-mobile-form" method="POST" action="{{ route('member.verification.mobile') }}" data-ajax-form="verification">
                                    @csrf
                                    <label class="otp-field">
                                        <input type="hidden" name="code">
                                        <div class="otp-digit-group" data-otp-group>
                                            @for ($i = 0; $i < 6; $i++)
                                                <input class="otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code">
                                            @endfor
                                        </div>
                                    </label>
                                    <div class="action-row verification-action-row verification-inline-actions">
                                        <button class="button button-primary" type="submit">Verify Mobile</button>
                                        <button class="text-link-button text-link-inline" type="submit" form="resend-mobile-form">Resend mobile code</button>
                                    </div>
                                </form>
                                <form id="resend-mobile-form" method="POST" action="{{ route('member.verification.resend', 'mobile') }}" data-ajax-form="verification">
                                    @csrf
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
