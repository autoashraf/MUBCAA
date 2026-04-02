@extends('layouts.app')

@section('content')
    <section class="verification-modal-page">
        <div class="verification-modal-backdrop"></div>
        <div class="wrap">
            <div class="verification-modal">
                <header class="verification-modal-header">
                    <div>
                        <p class="eyebrow">{{ __('Contact Verification') }}</p>
                        <h1>{{ __('Verify your email and mobile number') }}</h1>
                        <p class="lead">{{ __('Your profile has been created as an Unverified Profile. Complete both verification steps to continue with the alumni membership wizard.') }}</p>
                    </div>
                    <a class="mini-link" href="{{ route('home') }}">{{ __('Close') }}</a>
                </header>

                <div class="verification-layout">
                    <div class="list-card verification-summary-card">
                        <h3>{{ __('Verification status') }}</h3>
                        <div class="verification-status-list">
                            <div class="verification-status-item">
                                <span>{{ __('Email Address') }}</span>
                                <strong>{{ $user->email }}</strong>
                                <span class="status-pill status-{{ $user->hasVerifiedEmail() ? 'verified' : 'unverified' }}">
                                    {{ $user->hasVerifiedEmail() ? __('Verified') : __('Pending') }}
                                </span>
                            </div>
                            <div class="verification-status-item">
                                <span>{{ __('Mobile Number') }}</span>
                                <strong>{{ $user->profile?->mobile_number ?: $user->phone }}</strong>
                                <span class="status-pill status-{{ $user->hasVerifiedMobile() ? 'verified' : 'unverified' }}">
                                    {{ $user->hasVerifiedMobile() ? __('Verified') : __('Pending') }}
                                </span>
                            </div>
                        </div>

                    </div>

                    <div class="verification-panels">
                        <div class="form-card verification-panel">
                            <div class="dashboard-form-head">
                                <div>
                                    <p class="panel-card-label">{{ __('Email Verification') }}</p>
                                    <h3>{{ __('Verify your email address') }}</h3>
                                    <p class="dashboard-copy">{{ __('An email OTP has been sent to :email.', ['email' => $user->email]) }}</p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('member.verification.email') }}">
                                @csrf
                                <label>
                                    <span>{{ __('Email OTP Code') }}</span>
                                    <input type="text" name="code" inputmode="numeric" maxlength="6" placeholder="{{ __('Enter 6-digit code') }}">
                                    @error('email_code') <small>{{ $message }}</small> @enderror
                                    @error('code') <small>{{ $message }}</small> @enderror
                                </label>
                                <div class="action-row">
                                    <button class="button button-primary" type="submit">{{ __('Verify Email') }}</button>
                                </div>
                            </form>

                            @if (! $user->hasVerifiedEmail())
                                <form method="POST" action="{{ route('member.verification.resend', 'email') }}">
                                    @csrf
                                    <button class="text-link-button" type="submit">{{ __('Resend email code') }}</button>
                                </form>
                            @endif
                        </div>

                        <div class="form-card verification-panel">
                            <div class="dashboard-form-head">
                                <div>
                                    <p class="panel-card-label">{{ __('Mobile Verification') }}</p>
                                    <h3>{{ __('Verify your mobile number') }}</h3>
                                    <p class="dashboard-copy">{{ __('An SMS OTP has been sent to :mobile.', ['mobile' => $user->profile?->mobile_number ?: $user->phone]) }}</p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('member.verification.mobile') }}">
                                @csrf
                                <label>
                                    <span>{{ __('Mobile OTP Code') }}</span>
                                    <input type="text" name="code" inputmode="numeric" maxlength="6" placeholder="{{ __('Enter 6-digit code') }}">
                                    @error('mobile_code') <small>{{ $message }}</small> @enderror
                                    @error('code') <small>{{ $message }}</small> @enderror
                                </label>
                                <div class="action-row">
                                    <button class="button button-primary" type="submit">{{ __('Verify Mobile') }}</button>
                                </div>
                            </form>

                            @if (! $user->hasVerifiedMobile())
                                <form method="POST" action="{{ route('member.verification.resend', 'mobile') }}">
                                    @csrf
                                    <button class="text-link-button" type="submit">{{ __('Resend mobile code') }}</button>
                                </form>
                            @endif
                        </div>

                        @if ($user->hasCompletedContactVerification())
                            <div class="form-card verification-panel verification-panel-complete">
                                <p class="panel-card-label">{{ __('Ready') }}</p>
                                <h3>{{ __('Both contact methods are verified') }}</h3>
                                <p class="dashboard-copy">{{ __('You can now resume the remaining alumni registration steps from your current draft step.') }}</p>
                                <div class="action-row">
                                    <a class="button button-primary" href="{{ route('member.profile.complete', ['step' => max(2, $user->application?->current_step ?? $user->profile?->completion_step ?? 2)]) }}">{{ __('Continue Registration') }}</a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
