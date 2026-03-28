@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="wrap login-shell">
            <div class="login-aside">
                <p class="eyebrow">Member Access</p>
                <h1>Login with OTP</h1>
                <p class="lead">Use your registered email address or mobile number. We will send a 6-digit OTP for secure sign-in.</p>
                <div class="login-aside-points">
                    <div class="login-aside-point">
                        <span>01</span>
                        <div>
                            <strong>Request code</strong>
                            <p>Enter your registered email or mobile number.</p>
                        </div>
                    </div>
                    <div class="login-aside-point">
                        <span>02</span>
                        <div>
                            <strong>Verify OTP</strong>
                            <p>Enter the 6-digit code to access your account.</p>
                        </div>
                    </div>
                    <div class="login-aside-point">
                        <span>03</span>
                        <div>
                            <strong>Continue</strong>
                            <p>Go to your dashboard, profile, or admin panel.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="login-stack">
                <form class="form-card login-card" method="POST" action="{{ route('login.attempt') }}">
                    @csrf
                    <input type="hidden" name="login_type" value="member">

                    <div class="dashboard-form-head login-card-head">
                        <div>
                            <p class="panel-card-label">Request OTP</p>
                            <h2>Send sign-in code</h2>
                        </div>
                    </div>

                    <label>
                        <span>Email address or mobile number</span>
                        <input type="text" name="identifier" value="{{ old('identifier') }}" placeholder="Enter your registered email or mobile" required>
                        @error('identifier') <small>{{ $message }}</small> @enderror
                    </label>

                    <div class="action-row login-card-actions">
                        <button class="button button-primary" type="submit">Send OTP</button>
                        <a class="button button-secondary" href="{{ route('membership.apply') }}">Create account</a>
                    </div>
                    <div class="action-row login-card-actions">
                        <a class="text-link-button text-link-inline" href="{{ route('admin.login') }}">Admin login</a>
                    </div>
                </form>

            </div>
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

                        <label class="otp-field">
                            <input type="hidden" name="code">
                            <div class="otp-digit-group" data-otp-group>
                                @for ($i = 0; $i < 6; $i++)
                                    <input class="otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code">
                                @endfor
                            </div>
                            @error('code') <small>{{ $message }}</small> @enderror
                        </label>

                        <div class="action-row verification-action-row verification-inline-actions">
                            <button class="button button-primary" type="submit">Verify and Login</button>
                            <button class="text-link-button text-link-inline" type="submit" form="resend-login-form">Resend OTP</button>
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
