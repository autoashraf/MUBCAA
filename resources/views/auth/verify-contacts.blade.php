@extends('layouts.app')

@section('content')
    <section class="verification-modal-page">
        <div class="verification-modal-backdrop"></div>
        <div class="wrap">
            <div class="verification-modal">
                <header class="verification-modal-header">
                    <div>
                        <p class="eyebrow">Contact Verification</p>
                        <h1>Verify your email and mobile number</h1>
                        <p class="lead">Your profile has been created as an Unverified Profile. Complete both verification steps to continue with the alumni membership wizard.</p>
                    </div>
                    <a class="mini-link" href="{{ route('home') }}">Close</a>
                </header>

                <div class="verification-layout">
                    <div class="list-card verification-summary-card">
                        <h3>Verification status</h3>
                        <div class="verification-status-list">
                            <div class="verification-status-item">
                                <span>Email Address</span>
                                <strong>{{ $user->email }}</strong>
                                <span class="status-pill status-{{ $user->hasVerifiedEmail() ? 'verified' : 'unverified' }}">
                                    {{ $user->hasVerifiedEmail() ? 'Verified' : 'Pending' }}
                                </span>
                            </div>
                            <div class="verification-status-item">
                                <span>Mobile Number</span>
                                <strong>{{ $user->profile?->mobile_number ?: $user->phone }}</strong>
                                <span class="status-pill status-{{ $user->hasVerifiedMobile() ? 'verified' : 'unverified' }}">
                                    {{ $user->hasVerifiedMobile() ? 'Verified' : 'Pending' }}
                                </span>
                            </div>
                        </div>

                    </div>

                    <div class="verification-panels">
                        @if (session('success'))
                            <div class="alert-success">{{ session('success') }}</div>
                        @endif

                        <div class="form-card verification-panel">
                            <div class="dashboard-form-head">
                                <div>
                                    <p class="panel-card-label">Email Verification</p>
                                    <h3>Verify your email address</h3>
                                    <p class="dashboard-copy">We have sent a 6-digit verification code to {{ $user->email }}.</p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('member.verification.email') }}">
                                @csrf
                                <label>
                                    <span>Email OTP Code</span>
                                    <input type="text" name="code" inputmode="numeric" maxlength="6" placeholder="Enter 6-digit code">
                                    @error('email_code') <small>{{ $message }}</small> @enderror
                                    @error('code') <small>{{ $message }}</small> @enderror
                                </label>
                                <div class="action-row">
                                    <button class="button button-primary" type="submit">Verify Email</button>
                                </div>
                            </form>

                            @if (! $user->hasVerifiedEmail())
                                <form method="POST" action="{{ route('member.verification.resend', 'email') }}">
                                    @csrf
                                    <button class="text-link-button" type="submit">Resend email code</button>
                                </form>
                            @endif
                        </div>

                        <div class="form-card verification-panel">
                            <div class="dashboard-form-head">
                                <div>
                                    <p class="panel-card-label">Mobile Verification</p>
                                    <h3>Verify your mobile number</h3>
                                    <p class="dashboard-copy">A 6-digit OTP has been generated for {{ $user->profile?->mobile_number ?: $user->phone }}.</p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('member.verification.mobile') }}">
                                @csrf
                                <label>
                                    <span>Mobile OTP Code</span>
                                    <input type="text" name="code" inputmode="numeric" maxlength="6" placeholder="Enter 6-digit code">
                                    @error('mobile_code') <small>{{ $message }}</small> @enderror
                                    @error('code') <small>{{ $message }}</small> @enderror
                                </label>
                                <div class="action-row">
                                    <button class="button button-primary" type="submit">Verify Mobile</button>
                                </div>
                            </form>

                            @if (! $user->hasVerifiedMobile())
                                <form method="POST" action="{{ route('member.verification.resend', 'mobile') }}">
                                    @csrf
                                    <button class="text-link-button" type="submit">Resend mobile code</button>
                                </form>
                            @endif
                        </div>

                        @if ($user->hasCompletedContactVerification())
                            <div class="form-card verification-panel verification-panel-complete">
                                <p class="panel-card-label">Ready</p>
                                <h3>Both contact methods are verified</h3>
                                <p class="dashboard-copy">You can continue to the remaining alumni registration steps now.</p>
                                <div class="action-row">
                                    <a class="button button-primary" href="{{ route('member.profile.complete', ['step' => max(2, $user->profile?->completion_step ?? 2)]) }}">Continue Registration</a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
