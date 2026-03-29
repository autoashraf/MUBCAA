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

                    <label class="login-auth-label">
                        <span>Email address or mobile number</span>
                        <input class="login-auth-input" type="text" name="identifier" value="{{ old('identifier') }}" placeholder="Enter your registered email or mobile" required data-login-identifier-input data-login-check-url="{{ route('login.check') }}">
                        <small class="login-identifier-status" data-login-identifier-status hidden></small>
                        @error('identifier') <small>{{ $message }}</small> @enderror
                    </label>

                    <div class="login-auth-meta">
                        <span>OTP-based login only</span>
                        <a href="{{ route('admin.login') }}">Admin login</a>
                    </div>

                    <div class="login-auth-actions">
                        <button class="button button-primary login-auth-submit" type="submit">Send OTP</button>
                        <a class="button button-secondary login-auth-secondary" href="{{ route('membership.apply') }}">Create account</a>
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
